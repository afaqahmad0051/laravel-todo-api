<?php

namespace App\Services;

use App\Models\Todo;
use App\Models\User;
use App\DTOs\ListTodosDTO;
use App\DTOs\CreateTodoDTO;
use App\DTOs\UpdateTodoDTO;
use App\Repositories\Contracts\TodoRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TodoService
{
    public function __construct(
        protected TodoRepositoryInterface $todoRepository,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Todo>
     */
    public function list(User $user, ListTodosDTO $dto): LengthAwarePaginator
    {
        return $this->todoRepository->paginateForUser(
            userId: $user->getKey(),
            search: $dto->search,
            perPage: $dto->perPage,
        );
    }

    public function create(User $user, CreateTodoDTO $dto): Todo
    {
        return $this->todoRepository->create([
            'user_id' => $user->getKey(),
            'title' => $dto->title,
            'description' => $dto->description,
            'status' => $dto->status,
        ]);
    }

    public function update(Todo $todo, UpdateTodoDTO $dto): Todo
    {
        $todo->fill($dto->toAttributes());

        $this->todoRepository->save($todo);

        return $todo;
    }

    public function delete(Todo $todo): void
    {
        $this->todoRepository->delete($todo);
    }
}
