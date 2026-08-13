<?php

namespace App\Services;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class InvitationService
{
    public function getForUser(User $user): Collection
    {
        return Invitation::where('receiver_id', $user->id)
            ->where('status', 'pending')
            ->with(['account', 'sender', 'receiver'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function createFromShare(int $accountId, int $senderId, int $receiverId, string $role): Invitation
    {
        // Check if invitation already exists
        $existing = Invitation::where('account_id', $accountId)
            ->where('receiver_id', $receiverId)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            $existing->update(['role' => $role]);
            return $existing->fresh()->load(['account', 'sender', 'receiver']);
        }

        return Invitation::create([
            'account_id' => $accountId,
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'role' => $role,
            'status' => 'pending',
        ])->load(['account', 'sender', 'receiver']);
    }

    public function accept(Invitation $invitation): void
    {
        $invitation->update(['status' => 'accepted']);

        // Add user to account_user pivot table
        $invitation->account->sharedUsers()->syncWithoutDetaching([
            $invitation->receiver_id => ['role' => $invitation->role],
        ]);
    }

    public function reject(Invitation $invitation): void
    {
        $invitation->update(['status' => 'rejected']);
    }
}
