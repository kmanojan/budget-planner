<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use App\Services\OtpService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private AuthService $authService,
        private OtpService $otpService
    ) {}

    /**
     * API-AUTH-01: Register new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->created([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Registration successful');
    }

    /**
     * API-AUTH-02: Email/password login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());

            return $this->success([
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ], 'Login successful');
        } catch (\Exception $e) {
            return $this->error('Invalid credentials', 401);
        }
    }

    /**
     * API-AUTH-03: Google sign-in
     */
    public function google(Request $request): JsonResponse
    {
        $request->validate(['id_token' => 'required|string']);

        try {
            $result = $this->authService->googleLogin($request->input('id_token'));

            return $this->success([
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ], 'Login successful');
        } catch (\Exception $e) {
            return $this->error('Google authentication failed', 401);
        }
    }

    /**
     * API-AUTH-04: Logout
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());
        return $this->success(null, 'Logged out successfully');
    }

    /**
     * API-AUTH-05: Get current user
     */
    public function user(Request $request): JsonResponse
    {
        return $this->success(new UserResource($request->user()));
    }

    /**
     * API-AUTH-06: Send OTP to email
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->error('No account found with this email', 404);
        }

        $this->otpService->generate($user);

        // Mask email
        $email = $user->email;
        $parts = explode('@', $email);
        $maskedLocal = substr($parts[0], 0, 1) . '***';
        $maskedEmail = $maskedLocal . '@' . $parts[1];

        return $this->success([
            'email' => $maskedEmail,
            'expires_in' => 300,
        ], 'OTP sent to your email');
    }

    /**
     * API-AUTH-07: Verify OTP code
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->error('No account found with this email', 404);
        }

        try {
            $resetToken = $this->otpService->verify($user, $request->otp);

            return $this->success([
                'reset_token' => $resetToken,
            ], 'OTP verified successfully');
        } catch (\Exception $e) {
            return $this->error('Invalid or expired OTP', 422);
        }
    }

    /**
     * API-AUTH-08: Reset password
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $this->otpService->resetPassword(
                $request->reset_token,
                $request->password
            );

            return $this->success(null, 'Password reset successful. Please login with your new password.');
        } catch (\Exception $e) {
            return $this->error('Invalid or expired reset token', 400);
        }
    }
}
