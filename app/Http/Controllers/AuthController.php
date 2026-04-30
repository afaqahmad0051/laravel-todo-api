<?php

namespace App\Http\Controllers;

use App\DTOs\LoginDTO;
use App\DTOs\RegisterDTO;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\UserResource;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register(
            RegisterDTO::fromArray($request->validated())
        );

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'user' => new UserResource($result['user']),
            ],
        ], 201);
    }

    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->verifyEmail($request->validated('code'));

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => null,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 422);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                LoginDTO::fromArray($request->validated())
            );

            return response()->json([
                'success' => true,
                'message' => 'Login successful.',
                'data' => $result,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'data' => null,
            ], 401);
        }
    }

    public function logout(): JsonResponse
    {
        try {
            $result = $this->authService->logout();

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to logout. Token may already be invalid.',
                'data' => null,
            ], 400);
        }
    }
}
