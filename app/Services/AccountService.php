<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountMerge;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class AccountService
{
    public function getAllForUser(User $user): Collection
    {
        return Account::where('user_id', $user->id)
            ->orWhereHas('sharedUsers', fn ($q) => $q->where('users.id', $user->id))
            ->with('sharedUsers')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function create(array $data, User $user): Account
    {
        $data['user_id'] = $user->id;
        return Account::create($data);
    }

    public function update(Account $account, array $data): Account
    {
        $account->update($data);
        return $account->fresh();
    }

    public function delete(Account $account): void
    {
        $account->delete();
    }

    public function share(Account $account, int $userId, string $role): void
    {
        $account->sharedUsers()->syncWithoutDetaching([
            $userId => ['role' => $role],
        ]);
    }

    public function removeSharedUser(Account $account, int $userId): void
    {
        $account->sharedUsers()->detach($userId);
    }

    public function getSharedAccounts(User $user): Collection
    {
        return $user->sharedAccounts()->with('user')->get();
    }

    public function mergeAccounts(array $accountIds, User $user): array
    {
        $mergeGroupId = Str::uuid()->toString();

        foreach ($accountIds as $accountId) {
            AccountMerge::create([
                'user_id' => $user->id,
                'merge_group_id' => $mergeGroupId,
                'account_id' => $accountId,
            ]);
        }

        $accounts = Account::whereIn('id', $accountIds)->get();
        $totalBalance = $accounts->sum('balance');
        $currencyCode = $accounts->first()->currency_code;

        return [
            'merge_group_id' => $mergeGroupId,
            'accounts' => $accounts,
            'total_balance' => $totalBalance,
            'currency_code' => $currencyCode,
        ];
    }

    public function unmerge(string $mergeGroupId, User $user): void
    {
        AccountMerge::where('merge_group_id', $mergeGroupId)
            ->where('user_id', $user->id)
            ->delete();
    }

    /**
     * Update account balance based on a transaction.
     */
    public function adjustBalance(int $accountId, float $amount, string $type, bool $isRevert = false): void
    {
        $account = Account::findOrFail($accountId);
        $adjustment = $isRevert ? -$amount : $amount;

        if ($type === 'income') {
            $account->increment('balance', $adjustment);
        } elseif ($type === 'expense') {
            $account->decrement('balance', $adjustment);
        }
    }

    /**
     * Check if user has access to account (owner or shared).
     */
    public function userHasAccess(Account $account, User $user): bool
    {
        if ($account->user_id === $user->id) {
            return true;
        }
        return $account->sharedUsers()->where('users.id', $user->id)->exists();
    }

    /**
     * Check if user can edit account (owner or editor).
     */
    public function userCanEdit(Account $account, User $user): bool
    {
        if ($account->user_id === $user->id) {
            return true;
        }
        return $account->sharedUsers()
            ->where('users.id', $user->id)
            ->wherePivot('role', 'editor')
            ->exists();
    }
}
