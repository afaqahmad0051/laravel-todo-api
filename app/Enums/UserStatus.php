<?php

namespace App\Enums;

/**
 * Represents the email verification status of a user account.
 */
enum UserStatus: string
{
    case Unverified = 'unverified';
    case Verified = 'verified';

    /**
     * Returns a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Unverified => 'Unverified',
            self::Verified => 'Verified',
        };
    }

    public function isVerified(): bool
    {
        return $this === self::Verified;
    }
}
