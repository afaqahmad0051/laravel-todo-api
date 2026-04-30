<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

/**
 * Eloquent-backed implementation of UserRepositoryInterface.
 *
 * All direct Eloquent interactions are isolated here,
 * keeping service classes persistence-agnostic.
 */
class UserRepository implements UserRepositoryInterface
{
    public function __construct(protected User $user) {}

    /**
     * {@inheritdoc}
     */
    public function create(array $data): User
    {
        return $this->user->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function findByEmail(string $email): ?User
    {
        return $this->user->where('email', $email)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findByVerificationCode(string $code): ?User
    {
        return $this->user->where('verification_code', $code)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function save(User $user): bool
    {
        return $user->save();
    }
}
