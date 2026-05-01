<?php

namespace App\Repositories;

use App\Models\Todo;
use App\Repositories\Contracts\TodoRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Eloquent-backed implementation of TodoRepositoryInterface.
 *
 * All direct Eloquent interactions are isolated here,
 * keeping service classes persistence-agnostic.
 */
class TodoRepository implements TodoRepositoryInterface
{
    public function __construct(protected Todo $todo) {}

    public function paginateForUser(int $userId, ?string $search, int $perPage): LengthAwarePaginator
    {
        return $this->todo
            ->newQuery()
            ->where('user_id', $userId)
            ->when($search !== null && $search !== '', function ($query) use ($search): void {
                $query->where('title', 'like', '%'.$search.'%');
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Todo
    {
        return $this->todo->create($data);
    }

    public function save(Todo $todo): bool
    {
        return $todo->save();
    }

    public function delete(Todo $todo): bool
    {
        return (bool) $todo->delete();
    }
}
