<?php

namespace App\Services;

use App\DTOs\LoginDTO;
use App\DTOs\RegisterDTO;
use App\Enums\UserStatus;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Repositories\Contracts\UserRepositoryInterface;

class AuthService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
    ) {}

    public function register(RegisterDTO $dto): array
    {
        $user = $this->userRepository->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => $dto->password,
            'status' => UserStatus::Unverified,
        ]);

        return [
            'user' => $user,
            'message' => 'Registration successful. Please check your email for the verification code.',
        ];
    }

    public function verifyEmail(string $code): array
    {
        $user = $this->userRepository->findByVerificationCode($code);

        if ($user === null || ! $user->isVerificationCodeValid($code)) {
            throw new \InvalidArgumentException('Invalid or expired verification code.');
        }

        // Mark the user as verified and clear the one-time code
        $user->status = UserStatus::Verified;
        $user->email_verified_at = now();
        $user->verification_code = null;
        $user->verification_code_expires_at = null;

        $this->userRepository->save($user);

        return ['message' => 'Email verified successfully. You can now log in.'];
    }

    public function login(LoginDTO $dto): array
    {
        $token = JWTAuth::attempt($dto->toCredentials());

        if (! $token) {
            throw new \InvalidArgumentException('Invalid email or password.');
        }

        $user = JWTAuth::user();

        if (! $user->isVerified()) {
            JWTAuth::setToken($token)->invalidate();

            throw new \InvalidArgumentException(
                'Your email address is not verified. Please check your inbox for the verification code.'
            );
        }

        return [
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60, // TTL in minutes → seconds
        ];
    }

    public function logout(): array
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return ['message' => 'Successfully logged out.'];
    }
}
