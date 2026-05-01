<?php

namespace App\Http\Requests\Todo;

use App\Models\Todo;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Authorizes viewing a single ToDo. Only the owner may view it.
 */
class ShowTodoRequest extends FormRequest
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
