<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Enums\UserStatus;
use App\Events\UserRegistered;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_register_creates_unverified_user_and_returns_resource(): void
    {
        Event::fake([UserRegistered::class]);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Afaq',
            'email' => 'afaq@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'afaq@example.com')
            ->assertJsonPath('data.user.status', 'Unverified')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['user' => ['id', 'email', 'status']],
            ]);

        // Sensitive fields must never leak through the resource.
        $response->assertJsonMissingPath('data.user.password')
            ->assertJsonMissingPath('data.user.verification_code');

        $this->assertDatabaseHas('users', [
            'email' => 'afaq@example.com',
            'status' => UserStatus::Unverified->value,
        ]);

        $user = User::where('email', 'afaq@example.com')->first();
        $this->assertNotNull($user->verification_code);
        $this->assertNotNull($user->verification_code_expires_at);
        $this->assertTrue(Hash::check('password123', $user->password));

        Event::assertDispatched(UserRegistered::class);
    }

    public function test_register_requires_name_email_and_password(): void
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_register_rejects_invalid_email(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Afaq',
            'email' => 'not-an-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_register_rejects_short_password(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Afaq',
            'email' => 'afaq@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_register_rejects_mismatched_password_confirmation(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Afaq',
            'email' => 'afaq@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Afaq',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_verify_email_marks_user_verified_and_clears_code(): void
    {
        $user = User::factory()->create([
            'status' => UserStatus::Unverified,
            'email_verified_at' => null,
            'verification_code' => '123456',
            'verification_code_expires_at' => now()->addHour(),
        ]);

        $response = $this->postJson('/api/auth/verify-email', ['code' => '123456']);

        $response->assertOk()->assertJsonPath('success', true);

        $user->refresh();
        $this->assertSame(UserStatus::Verified, $user->status);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->verification_code);
        $this->assertNull($user->verification_code_expires_at);
    }

    public function test_verify_email_rejects_invalid_code(): void
    {
        User::factory()->create([
            'status' => UserStatus::Unverified,
            'verification_code' => '123456',
            'verification_code_expires_at' => now()->addHour(),
        ]);

        $response = $this->postJson('/api/auth/verify-email', ['code' => '999999']);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_verify_email_rejects_expired_code(): void
    {
        User::factory()->create([
            'status' => UserStatus::Unverified,
            'verification_code' => '123456',
            'verification_code_expires_at' => now()->subMinute(),
        ]);

        $response = $this->postJson('/api/auth/verify-email', ['code' => '123456']);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_verify_email_validates_code_format(): void
    {
        $this->postJson('/api/auth/verify-email', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        $this->postJson('/api/auth/verify-email', ['code' => '123'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_login_returns_jwt_token_for_verified_user(): void
    {
        User::factory()->create([
            'email' => 'afaq@example.com',
            'password' => Hash::make('password123'),
            'status' => UserStatus::Verified,
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'afaq@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'bearer')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['token', 'token_type', 'expires_in'],
            ]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_login_rejects_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'afaq@example.com',
            'password' => Hash::make('password123'),
            'status' => UserStatus::Verified,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'afaq@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_rejects_unknown_email(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'ghost@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_rejects_unverified_user(): void
    {
        User::factory()->create([
            'email' => 'afaq@example.com',
            'password' => Hash::make('password123'),
            'status' => UserStatus::Unverified,
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'afaq@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_validates_required_fields(): void
    {
        $this->postJson('/api/auth/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_validates_email_format(): void
    {
        $this->postJson('/api/auth/login', [
            'email' => 'not-an-email',
            'password' => 'password123',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_logout_invalidates_token_for_authenticated_user(): void
    {
        $user = User::factory()->create([
            'status' => UserStatus::Verified,
            'email_verified_at' => now(),
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout');

        $response->assertOk()->assertJsonPath('success', true);

        // Reusing the same token after logout should now be rejected.
        $second = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout');

        $second->assertStatus(401);
    }

    public function test_logout_requires_authentication(): void
    {
        $this->postJson('/api/auth/logout')->assertStatus(401);
    }

    public function test_logout_rejects_invalid_token(): void
    {
        $this->withHeader('Authorization', 'Bearer not-a-real-token')
            ->postJson('/api/auth/logout')
            ->assertStatus(401);
    }
}
