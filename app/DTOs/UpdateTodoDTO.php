<?php

namespace App\DTOs;

use App\Enums\TaskStatus;

/**
 * Data Transfer Object for updating a ToDo.
 */
readonly class UpdateTodoDTO
{
    public function __construct(
        public string $title,
        public string $description,
        public ?TaskStatus $status,
    ) {}

    /**
     * Construct from a validated array (e.g., FormRequest->validated()).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            description: $data['description'],
            status: isset($data['status']) ? TaskStatus::from($data['status']) : null,
        );
    }

    /**
     * Returns the attribute payload to apply on the Eloquent model.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        $attributes = [
            'title' => $this->title,
            'description' => $this->description,
        ];

        if ($this->status !== null) {
            $attributes['status'] = $this->status;
        }

        return $attributes;
    }
}
