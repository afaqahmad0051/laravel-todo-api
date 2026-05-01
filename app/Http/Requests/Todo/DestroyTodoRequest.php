<?php

namespace App\Http\Requests\Todo;

use App\Models\Todo;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Authorizes deleting a single ToDo. Only the owner may delete it.
 */
class DestroyTodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $todo = $this->route('todo');

        return $todo instanceof Todo
            && $todo->isOwnedBy(JWTAuth::user());
    }

    public function rules(): array
    {
        return [];
    }
}
