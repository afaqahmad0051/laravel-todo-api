<?php

namespace App\DTOs;

/**
 * Data Transfer Object for ToDo listing filters.
 */
readonly class ListTodosDTO
{
    public const MAX_PER_PAGE = 10;

    public function __construct(
        public ?string $search,
        public int $perPage,
    ) {}

    /**
     * Construct from a validated array (e.g., FormRequest->validated()).
     */
    public static function fromArray(array $data): self
    {
        $perPage = (int) ($data['per_page'] ?? self::MAX_PER_PAGE);

        return new self(
            search: isset($data['search']) ? trim((string) $data['search']) : null,
            perPage: min($perPage > 0 ? $perPage : self::MAX_PER_PAGE, self::MAX_PER_PAGE),
        );
    }
}
