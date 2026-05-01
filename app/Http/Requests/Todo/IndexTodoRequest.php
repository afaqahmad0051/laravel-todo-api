<?php

namespace App\Http\Requests\Todo;

use App\DTOs\ListTodosDTO;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates query parameters for the ToDo listing endpoint.
 */
class IndexTodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.ListTodosDTO::MAX_PER_PAGE],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.max' => 'You may request at most '.ListTodosDTO::MAX_PER_PAGE.' items per page.',
        ];
    }
}
