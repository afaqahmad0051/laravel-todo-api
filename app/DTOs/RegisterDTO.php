<?php

namespace App\DTOs;

/**
 * Data Transfer Object for user registration data.
 *
 * Carries validated registration input from the controller
 * to the service layer, decoupling the Request from business logic.
 */
readonly class RegisterDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}

    /**
     * Construct from a validated array (e.g., FormRequest->validated()).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
        );
    }
}
