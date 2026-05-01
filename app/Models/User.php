<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\UserStatus;
use App\Events\UserRegistered;
use Database\Factories\UserFactory;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable(['name', 'email', 'password', 'status', 'verification_code', 'verification_code_expires_at', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'verification_code_expires_at' => 'datetime',
            'status' => UserStatus::class,
        ];
    }

    protected $dispatchesEvents = [
        'created' => UserRegistered::class,
    ];

    public function isVerified(): bool
    {
        return $this->status?->isVerified() ?? false;
    }

    public function isVerificationCodeValid(string $code): bool
    {
        return $this->verification_code === $code
            && $this->verification_code_expires_at !== null
            && $this->verification_code_expires_at->isFuture();
    }

    /**
     * @return HasMany<Todo, $this>
     */
    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * @return array<string, mixed>
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if ($user->verification_code) {
                return;
            }

            // 6-digit numeric code for simple copy/paste UX.
            $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

            $ttlMinutes = (int) config('auth.verification_code_ttl', 60);

            $user->verification_code = $code;
            $user->verification_code_expires_at = now()->addMinutes($ttlMinutes);
        });
    }
}
