<?php

namespace App\Repositories\Contracts;

use App\Models\User;

/**
 * Contract for all User persistence operations.
 *
 * Keeps the service layer decoupled from Eloquent directly,
 * making it easy to swap data sources or mock in tests.
 */
interface UserRepositoryInterface
{
    /**
     * Persist a new user record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User;

    /**
     * Find a user by their email address.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find a user by their verification code.
     */
    public function findByVerificationCode(string $code): ?User;

    /**
     * Persist changes on an existing user model.
     */
    public function save(User $user): bool;
}
