<?php

namespace App\Repositories\Contracts;

use App\Models\Todo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Contract for ToDo persistence operations.
 *
 * All lookup methods are user-scoped so the service layer cannot
 * accidentally leak records that belong to another user.
 */
interface TodoRepositoryInterface
{
    /**
     * Paginate ToDos belonging to the given user, optionally filtered by title search.
     *
     * @return LengthAwarePaginator<int, Todo>
     */
    public function paginateForUser(int $userId, ?string $search, int $perPage): LengthAwarePaginator;

    /**
     * Persist a new ToDo record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Todo;

    /**
     * Persist changes on an existing ToDo model.
     */
    public function save(Todo $todo): bool;

    /**
     * Delete a ToDo model.
     */
    public function delete(Todo $todo): bool;
}
