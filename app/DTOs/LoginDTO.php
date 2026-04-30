<?php

namespace App\DTOs;

/**
 * Data Transfer Object for user login credentials.
 *
 * Carries validated login input from the controller
 * to the service layer.
 */
readonly class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}

    /**
     * Construct from a validated array (e.g., FormRequest->validated()).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            password: $data['password'],
        );
    }

    /**
     * Returns credentials array expected by JWTAuth::attempt().
     */
    public function toCredentials(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
        ];
    }
}
