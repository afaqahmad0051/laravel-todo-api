<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use App\DTOs\ListTodosDTO;
use App\DTOs\CreateTodoDTO;
use App\DTOs\UpdateTodoDTO;
use App\Services\TodoService;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Resources\TodoResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Todo\ShowTodoRequest;
use App\Http\Requests\Todo\IndexTodoRequest;
use App\Http\Requests\Todo\StoreTodoRequest;
use App\Http\Requests\Todo\UpdateTodoRequest;
use App\Http\Requests\Todo\DestroyTodoRequest;

class TodoController extends Controller
{
    public function __construct(protected TodoService $todoService) {}

    public function index(IndexTodoRequest $request): JsonResponse
    {
        $paginator = $this->todoService->list(
            Auth::user(),
            ListTodosDTO::fromArray($request->validated()),
        );

        return response()->json([
            'success' => true,
            'message' => 'ToDos retrieved successfully.',
            'data' => TodoResource::collection($paginator)
                ->response()
                ->getData(true),
        ]);
    }

    public function store(StoreTodoRequest $request): JsonResponse
    {
        $todo = $this->todoService->create(
            JWTAuth::user(),
            CreateTodoDTO::fromArray($request->validated()),
        );

        return response()->json([
            'success' => true,
            'message' => 'ToDo created successfully.',
            'data' => [
                'todo' => new TodoResource($todo),
            ],
        ], 201);
    }

    public function show(ShowTodoRequest $request, Todo $todo): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'ToDo retrieved successfully.',
            'data' => [
                'todo' => new TodoResource($todo),
            ],
        ]);
    }

    public function update(UpdateTodoRequest $request, Todo $todo): JsonResponse
    {
        $todo = $this->todoService->update(
            $todo,
            UpdateTodoDTO::fromArray($request->validated()),
        );

        return response()->json([
            'success' => true,
            'message' => 'ToDo updated successfully.',
            'data' => [
                'todo' => new TodoResource($todo),
            ],
        ]);
    }

    public function destroy(DestroyTodoRequest $request, Todo $todo): JsonResponse
    {
        $this->todoService->delete($todo);

        return response()->json([
            'success' => true,
            'message' => 'ToDo deleted successfully.',
            'data' => null,
        ]);
    }
}
