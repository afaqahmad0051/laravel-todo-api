<?php

namespace App\DTOs;

use App\Enums\TaskStatus;

/**
 * Data Transfer Object for creating a ToDo.
 *
 * Carries validated input from the controller to the service layer,
 * decoupling the Request from business logic.
 */
readonly class CreateTodoDTO
{
    public function __construct(
        public string $title,
        public string $description,
        public TaskStatus $status,
    ) {}

    /**
     * Construct from a validated array (e.g., FormRequest->validated()).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            description: $data['description'],
            status: isset($data['status'])
                ? TaskStatus::from($data['status'])
                : TaskStatus::Pending,
        );
    }
}
