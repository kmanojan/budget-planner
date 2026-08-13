<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OtpService
{
    /**
     * Generate and send OTP to user's email.
     */
    public function generate(User $user): string
    {
        // Invalidate previous OTPs
        PasswordReset::where('user_id', $user->id)->update(['is_used' => true]);

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PasswordReset::create([
            'user_id' => $user->id,
            'otp' => Hash::make($otp),
            'expires_at' => now()->addMinutes(5),
        ]);

        // Send email with OTP
        Mail::to($user)->send(new OtpMail($otp));

        return $otp;
    }

    /**
     * Verify OTP and return a temporary reset token.
     */
    public function verify(User $user, string $otp): string
    {
        $record = PasswordReset::where('user_id', $user->id)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$record || !Hash::check($otp, $record->otp)) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid or expired OTP'],
            ]);
        }

        $record->update(['is_used' => true]);

        // Generate a temporary reset token
        $resetToken = Str::random(64);

        // Store the reset token
        PasswordReset::create([
            'user_id' => $user->id,
            'otp' => Hash::make('verified'),
            'reset_token' => $resetToken,
            'expires_at' => now()->addMinutes(15),
        ]);

        return $resetToken;
    }

    /**
     * Reset password using the reset token.
     */
    public function resetPassword(string $resetToken, string $newPassword): void
    {
        $record = PasswordReset::where('reset_token', $resetToken)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$record) {
            throw ValidationException::withMessages([
                'reset_token' => ['Invalid or expired reset token'],
            ]);
        }

        $user = $record->user;
        $user->update(['password' => $newPassword]);

        // Mark token as used
        $record->update(['is_used' => true]);

        // Revoke all tokens
        $user->tokens()->delete();
    }
}
