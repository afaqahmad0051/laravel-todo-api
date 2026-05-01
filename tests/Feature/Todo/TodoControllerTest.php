<?php

namespace Tests\Feature\Todo;

use Tests\TestCase;
use App\Models\Todo;
use App\Models\User;
use App\Enums\TaskStatus;
use App\Enums\UserStatus;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TodoControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private function authHeaders(User $user): array
    {
        $token = JWTAuth::fromUser($user);

        return ['Authorization' => "Bearer {$token}"];
    }

    private function makeVerifiedUser(): User
    {
        return User::factory()->create([
            'status' => UserStatus::Verified,
            'email_verified_at' => now(),
        ]);
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/todos')->assertStatus(401);
    }

    public function test_index_lists_only_authenticated_users_todos_with_pagination(): void
    {
        $user = $this->makeVerifiedUser();
        $otherUser = $this->makeVerifiedUser();

        $ownTodos = Todo::factory()->count(2)->create(['user_id' => $user->id]);
        Todo::factory()->count(3)->create(['user_id' => $otherUser->id]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/todos');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'ToDos retrieved successfully.')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['data', 'links', 'meta'],
            ])
            ->assertJsonCount(2, 'data.data');

        $returnedIds = collect($response->json('data.data'))
            ->pluck('id')
            ->all();

        foreach ($ownTodos as $todo) {
            $this->assertContains($todo->id, $returnedIds);
        }
    }

    public function test_index_supports_search_filter(): void
    {
        $user = $this->makeVerifiedUser();

        Todo::factory()->create([
            'user_id' => $user->id,
            'title' => 'Buy milk',
        ]);

        Todo::factory()->create([
            'user_id' => $user->id,
            'title' => 'Read a book',
        ]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/todos?search=milk');

        $response->assertOk()->assertJsonCount(1, 'data.data');
        $this->assertSame('Buy milk', $response->json('data.data.0.title'));
    }

    public function test_index_validates_per_page_maximum(): void
    {
        $user = $this->makeVerifiedUser();

        $response = $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/todos?per_page=999');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    public function test_store_requires_authentication(): void
    {
        $this->postJson('/api/todos', [
            'title' => 'Sample',
            'description' => 'Sample description',
        ])->assertStatus(401);
    }

    public function test_store_creates_todo_for_authenticated_user_with_default_status(): void
    {
        $user = $this->makeVerifiedUser();

        $payload = [
            'title' => 'Write tests',
            'description' => 'Cover all controller endpoints',
        ];

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/todos', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'ToDo created successfully.')
            ->assertJsonPath('data.todo.title', 'Write tests')
            ->assertJsonPath('data.todo.status.value', TaskStatus::Pending);

        $this->assertDatabaseHas('todos', [
            'user_id' => $user->id,
            'title' => 'Write tests',
            'description' => 'Cover all controller endpoints',
            'status' => TaskStatus::Pending,
        ]);
    }

    public function test_store_accepts_explicit_status(): void
    {
        $user = $this->makeVerifiedUser();

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/todos', [
                'title' => 'Ship feature',
                'description' => 'Deploy the change',
                'status' => TaskStatus::Completed,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.todo.status.value', TaskStatus::Completed);

        $this->assertDatabaseHas('todos', [
            'user_id' => $user->id,
            'title' => 'Ship feature',
            'status' => TaskStatus::Completed,
        ]);
    }

    public function test_store_validates_required_fields_and_status(): void
    {
        $user = $this->makeVerifiedUser();

        $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/todos', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description']);

        $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/todos', [
                'title' => 'Bad status',
                'description' => 'Invalid value',
                'status' => 'not-a-real-status',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_show_requires_authentication(): void
    {
        $todo = Todo::factory()->create();

        $this->getJson("/api/todos/{$todo->id}")
            ->assertStatus(401);
    }

    public function test_show_returns_todo_for_owner(): void
    {
        $user = $this->makeVerifiedUser();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->getJson("/api/todos/{$todo->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.todo.id', $todo->id)
            ->assertJsonPath('data.todo.title', $todo->title);
    }

    public function test_show_forbids_access_to_non_owner(): void
    {
        $owner = $this->makeVerifiedUser();
        $otherUser = $this->makeVerifiedUser();
        $todo = Todo::factory()->create(['user_id' => $owner->id]);

        $this->withHeaders($this->authHeaders($otherUser))
            ->getJson("/api/todos/{$todo->id}")
            ->assertStatus(403);
    }

    public function test_show_returns_not_found_for_missing_todo(): void
    {
        $user = $this->makeVerifiedUser();

        $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/todos/999999')
            ->assertStatus(404);
    }

    public function test_update_requires_authentication(): void
    {
        $todo = Todo::factory()->create();

        $this->putJson("/api/todos/{$todo->id}", [
            'title' => 'Updated',
            'description' => 'Updated description',
        ])->assertStatus(401);
    }

    public function test_update_updates_todo_for_owner(): void
    {
        $user = $this->makeVerifiedUser();
        $todo = Todo::factory()->create([
            'user_id' => $user->id,
            'status' => TaskStatus::Pending,
        ]);

        $payload = [
            'title' => 'Updated title',
            'description' => 'Updated description',
            'status' => TaskStatus::InProgress,
        ];

        $response = $this->withHeaders($this->authHeaders($user))
            ->putJson("/api/todos/{$todo->id}", $payload);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.todo.title', 'Updated title')
            ->assertJsonPath('data.todo.status.value', TaskStatus::InProgress);

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'title' => 'Updated title',
            'description' => 'Updated description',
            'status' => TaskStatus::InProgress,
        ]);
    }

    public function test_update_does_not_overwrite_status_when_missing(): void
    {
        $user = $this->makeVerifiedUser();
        $todo = Todo::factory()->create([
            'user_id' => $user->id,
            'status' => TaskStatus::Completed,
        ]);

        $this->withHeaders($this->authHeaders($user))
            ->putJson("/api/todos/{$todo->id}", [
                'title' => 'Adjusted title',
                'description' => 'Adjusted description',
            ])
            ->assertOk()
            ->assertJsonPath('data.todo.status.value', TaskStatus::Completed);

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'status' => TaskStatus::Completed,
        ]);
    }

    public function test_update_validates_required_fields(): void
    {
        $user = $this->makeVerifiedUser();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        $this->withHeaders($this->authHeaders($user))
            ->putJson("/api/todos/{$todo->id}", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description']);
    }

    public function test_update_forbids_access_to_non_owner(): void
    {
        $owner = $this->makeVerifiedUser();
        $otherUser = $this->makeVerifiedUser();
        $todo = Todo::factory()->create(['user_id' => $owner->id]);

        $this->withHeaders($this->authHeaders($otherUser))
            ->putJson("/api/todos/{$todo->id}", [
                'title' => 'Should not work',
                'description' => 'Nope',
            ])
            ->assertStatus(403);
    }

    public function test_destroy_requires_authentication(): void
    {
        $todo = Todo::factory()->create();

        $this->deleteJson("/api/todos/{$todo->id}")
            ->assertStatus(401);
    }

    public function test_destroy_deletes_todo_for_owner(): void
    {
        $user = $this->makeVerifiedUser();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->deleteJson("/api/todos/{$todo->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'ToDo deleted successfully.')
            ->assertJsonPath('data', null);

        $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
    }

    public function test_destroy_forbids_access_to_non_owner(): void
    {
        $owner = $this->makeVerifiedUser();
        $otherUser = $this->makeVerifiedUser();
        $todo = Todo::factory()->create(['user_id' => $owner->id]);

        $this->withHeaders($this->authHeaders($otherUser))
            ->deleteJson("/api/todos/{$todo->id}")
            ->assertStatus(403);
    }

    public function test_destroy_returns_not_found_for_missing_todo(): void
    {
        $user = $this->makeVerifiedUser();

        $this->withHeaders($this->authHeaders($user))
            ->deleteJson('/api/todos/999999')
            ->assertStatus(404);
    }
}
