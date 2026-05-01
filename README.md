# Laravel ToDo API

REST API for user authentication and personal ToDo management, built with Laravel and JWT authentication. This project is structured with DTOs, Services, Repositories, and Form Requests to keep controllers thin and business logic testable.

## Tech stack

- PHP 8.3
- Laravel 13
- JWT authentication (tymon/jwt-auth)
- MySQL (default) and SQLite (testing)
- Vite + Tailwind CSS (frontend tooling; API focused)
- PHPUnit

## System requirements

- PHP 8.3+
- Composer 2+
- Node.js 18+ and npm
- MySQL 8+ (or compatible) for local development

## Installation and setup

1) Clone the repository
2) Install dependencies

```bash
composer install
npm install
```

3) Create environment file and app key

```bash
cp .env.example .env
php artisan key:generate
```

4) Configure environment (see next section)
5) Run migrations

```bash
php artisan migrate
```

6) Start the app

```bash
php artisan serve
npm run dev
```

Optional: run the bundled setup script

```bash
composer run setup
```

## Environment configuration

Update these values in .env for local development:

```dotenv
APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=todo_challenge
DB_USERNAME=root
DB_PASSWORD=
```

### JWT configuration

This project uses tymon/jwt-auth. Ensure a JWT secret is present in .env:

```bash
php artisan jwt:secret
```

If config/jwt.php is missing in your local copy, publish it:

```bash
php artisan vendor:publish --provider="Tymon\\JWTAuth\\Providers\\LaravelServiceProvider"
```

## Database setup and migrations

Create the database and run migrations:

```bash
php artisan migrate
```

### Testing database

Tests run with SQLite in memory (see phpunit.xml). No extra setup is required.

```bash
php artisan test
```

## Running the project locally

- API server: `php artisan serve`
- Frontend tooling: `npm run dev`

The API base URL defaults to:

```
http://localhost/api
```

## Architecture and conventions

- Controllers delegate logic to Services and DTOs.
- Repositories isolate Eloquent queries from business logic.
- Form Requests handle validation and per-resource authorization.
- Resources format API responses consistently.
- Ownership checks are enforced in Form Requests using the authenticated JWT user.

## Authentication flow

1) Register a user
2) Verify email with a 6-digit code
3) Log in to receive a JWT
4) Use the JWT in the Authorization header for all protected endpoints

Authorization header format:

```
Authorization: Bearer <token>
```

## API documentation

All endpoints are prefixed with `/api`.

### Auth

#### POST /api/auth/register

Registers a new user (unverified).

Request body:

```json
{
	"name": "Jane Doe",
	"email": "jane@example.com",
	"password": "password123",
	"password_confirmation": "password123"
}
```

Response 201:

```json
{
	"success": true,
	"message": "Registration successful. Please check your email for the verification code.",
	"data": {
		"user": {
			"id": 1,
			"email": "jane@example.com",
			"status": "Unverified"
		}
	}
}
```

Validation errors: 422 with field errors.

#### POST /api/auth/verify-email

Verifies a user by a 6-digit code.

Request body:

```json
{
	"code": "123456"
}
```

Response 200:

```json
{
	"success": true,
	"message": "Email verified successfully. You can now log in.",
	"data": null
}
```

Invalid or expired code: 422.

#### POST /api/auth/login

Logs in a verified user and returns a JWT.

Request body:

```json
{
	"email": "jane@example.com",
	"password": "password123"
}
```

Response 200:

```json
{
	"success": true,
	"message": "Login successful.",
	"data": {
		"token": "<jwt>",
		"token_type": "bearer",
		"expires_in": 3600
	}
}
```

Invalid credentials or unverified user: 401.

#### POST /api/auth/logout

Requires authentication. Invalidates the current token.

Response 200:

```json
{
	"success": true,
	"message": "Successfully logged out.",
	"data": null
}
```

Missing or invalid token: 401.

### ToDos (all require authentication)

#### GET /api/todos

Lists the authenticated user's todos with pagination.

Query params:

- `search` (optional): filter by title
- `per_page` (optional): items per page (max 10)
- `page` (optional): page number

Response 200:

```json
{
	"success": true,
	"message": "ToDos retrieved successfully.",
	"data": {
		"data": [
			{
				"id": 1,
				"title": "Buy milk",
				"description": "Remember to buy milk",
				"status": { "value": "pending", "label": "Pending" },
				"created_at": "2026-05-01T10:00:00Z",
				"updated_at": "2026-05-01T10:00:00Z"
			}
		],
		"links": {
			"first": "http://localhost/api/todos?page=1",
			"last": "http://localhost/api/todos?page=1",
			"prev": null,
			"next": null
		},
		"meta": {
			"current_page": 1,
			"from": 1,
			"last_page": 1,
			"path": "http://localhost/api/todos",
			"per_page": 10,
			"to": 1,
			"total": 1
		}
	}
}
```

Validation errors: 422 (for example `per_page` over 10).

#### POST /api/todos

Creates a new todo for the authenticated user.

Request body:

```json
{
	"title": "Write tests",
	"description": "Cover all endpoints",
	"status": "pending"
}
```

Response 201:

```json
{
	"success": true,
	"message": "ToDo created successfully.",
	"data": {
		"todo": {
			"id": 1,
			"title": "Write tests",
			"description": "Cover all endpoints",
			"status": { "value": "pending", "label": "Pending" },
			"created_at": "2026-05-01T10:00:00Z",
			"updated_at": "2026-05-01T10:00:00Z"
		}
	}
}
```

Validation errors: 422.

#### GET /api/todos/{todo}

Returns a single todo. Only the owner can access it.

Response 200:

```json
{
	"success": true,
	"message": "ToDo retrieved successfully.",
	"data": {
		"todo": {
			"id": 1,
			"title": "Buy milk",
			"description": "Remember to buy milk",
			"status": { "value": "pending", "label": "Pending" },
			"created_at": "2026-05-01T10:00:00Z",
			"updated_at": "2026-05-01T10:00:00Z"
		}
	}
}
```

Not found: 404. Not owner: 403.

#### PUT /api/todos/{todo}

Updates a todo. Only the owner can update it.

Request body:

```json
{
	"title": "Updated title",
	"description": "Updated description",
	"status": "in_progress"
}
```

Response 200:

```json
{
	"success": true,
	"message": "ToDo updated successfully.",
	"data": {
		"todo": {
			"id": 1,
			"title": "Updated title",
			"description": "Updated description",
			"status": { "value": "in_progress", "label": "In Progress" },
			"created_at": "2026-05-01T10:00:00Z",
			"updated_at": "2026-05-01T10:30:00Z"
		}
	}
}
```

Validation errors: 422. Not owner: 403.

#### DELETE /api/todos/{todo}

Deletes a todo. Only the owner can delete it.

Response 200:

```json
{
	"success": true,
	"message": "ToDo deleted successfully.",
	"data": null
}
```

Not found: 404. Not owner: 403.

### Task status values

- `pending`
- `in_progress`
- `completed`

## Common error responses

JWT middleware returns 401 for missing or invalid tokens:

```json
{
	"status": "error",
	"message": "Token not provided.",
	"data": []
}
```

## Usage guidelines

- Always send the JWT for protected endpoints.
- The API is stateless; do not rely on sessions.
- Each todo is scoped to the authenticated user.
- Use pagination query params to control list size.

## Testing

```bash
php artisan test
```
