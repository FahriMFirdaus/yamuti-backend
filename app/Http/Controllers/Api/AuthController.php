<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->successResponse($result, 'Registrasi berhasil', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return $this->successResponse($result, 'Login berhasil');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->successResponse(null, 'Logout berhasil');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = $this->authService->forgotPassword($request->validated());

        return $this->successResponse(null, $status);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = $this->authService->resetPassword($request->validated());

        return $this->successResponse(null, $status);
    }
}
