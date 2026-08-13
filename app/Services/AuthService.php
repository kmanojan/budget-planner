<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    public function login(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials'],
            ]);
        }

        // Revoke previous tokens
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    public function googleLogin(string $idToken): array
    {
        // Verify the Google ID token
        $payload = $this->verifyGoogleToken($idToken);

        if (!$payload) {
            throw ValidationException::withMessages([
                'id_token' => ['Invalid Google token'],
            ]);
        }

        $user = User::updateOrCreate(
            ['google_id' => $payload['sub']],
            [
                'name' => $payload['name'] ?? 'Google User',
                'email' => $payload['email'],
                'profile_image' => $payload['picture'] ?? null,
                'email_verified_at' => now(),
            ]
        );

        // Also check if user exists by email but without google_id
        if (!$user->wasRecentlyCreated && !$user->google_id) {
            $existingUser = User::where('email', $payload['email'])->first();
            if ($existingUser) {
                $existingUser->update([
                    'google_id' => $payload['sub'],
                    'profile_image' => $existingUser->profile_image ?? $payload['picture'] ?? null,
                ]);
                $user = $existingUser;
            }
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    /**
     * Verify Google ID token using Google's public endpoint.
     */
    private function verifyGoogleToken(string $idToken): ?array
    {
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get('https://oauth2.googleapis.com/tokeninfo', [
                'query' => ['id_token' => $idToken],
                'timeout' => 10,
            ]);

            $payload = json_decode($response->getBody()->getContents(), true);

            // Verify the token is for our app
            $clientId = config('services.google.client_id');
            if ($clientId && $payload['aud'] !== $clientId) {
                return null;
            }

            return $payload;
        } catch (\Exception $e) {
            return null;
        }
    }
}
