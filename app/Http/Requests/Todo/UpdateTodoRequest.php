<?php

namespace App\Http\Requests\Todo;

use App\Models\Todo;
use App\Enums\TaskStatus;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the payload for updating a ToDo.
 */
class UpdateTodoRequest extends FormRequest
{
    /**
     * Only the owner of the ToDo may update it.
     */
    public function authorize(): bool
    {
        $todo = $this->route('todo');

        return $todo instanceof Todo
            && $todo->isOwnedBy(JWTAuth::user());
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'status' => ['sometimes', 'string', new Enum(TaskStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Title is required.',
            'description.required' => 'Description is required.',
        ];
    }
}
