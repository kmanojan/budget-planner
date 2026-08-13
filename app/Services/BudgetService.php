<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Collection;

class BudgetService
{
    /**
     * Get all budgets for a user with spent amounts calculated.
     */
    public function getAll(User $user): Collection
    {
        $budgets = Budget::where('user_id', $user->id)
            ->where('is_active', true)
            ->with(['category', 'account'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $budgets->map(fn(Budget $budget) => $this->enrichWithSpent($budget, $user));
    }

    /**
     * Get a single budget with spent amount.
     */
    public function getById(int $id, User $user): ?Budget
    {
        $budget = Budget::where('user_id', $user->id)->find($id);

        if ($budget) {
            $budget = $this->enrichWithSpent($budget, $user);
        }

        return $budget;
    }

    /**
     * Create a new budget.
     */
    public function create(array $data, User $user): Budget
    {
        $data['user_id'] = $user->id;

        // Default currency from user's first account if not provided
        if (empty($data['currency_code'])) {
            $firstAccount = $user->accounts()->first();
            $data['currency_code'] = $firstAccount?->currency_code ?? 'LKR';
        }

        // Default start_date to beginning of current period
        if (empty($data['start_date'])) {
            $period = $data['period'] ?? 'monthly';
            $data['start_date'] = match ($period) {
                'weekly' => now()->startOfWeek()->toDateString(),
                'yearly' => now()->startOfYear()->toDateString(),
                default  => now()->startOfMonth()->toDateString(),
            };
        }

        $budget = Budget::create($data);
        $budget->load(['category', 'account']);

        return $this->enrichWithSpent($budget, $user);
    }

    /**
     * Update an existing budget.
     */
    public function update(Budget $budget, array $data): Budget
    {
        $budget->update($data);
        $budget->load(['category', 'account']);
        $user = $budget->user;

        return $this->enrichWithSpent($budget, $user);
    }

    /**
     * Delete a budget.
     */
    public function delete(Budget $budget): void
    {
        $budget->delete();
    }

    /**
     * Get budget summary for dashboard (top 3 active budgets with progress).
     */
    public function getDashboardSummary(User $user, ?int $accountId = null): Collection
    {
        $query = Budget::where('user_id', $user->id)
            ->where('is_active', true)
            ->with(['category']);

        if ($accountId) {
            $query->where(function ($q) use ($accountId) {
                $q->where('account_id', $accountId)
                  ->orWhereNull('account_id');
            });
        }

        $budgets = $query->orderBy('amount', 'desc')->take(3)->get();

        return $budgets->map(fn(Budget $budget) => $this->enrichWithSpent($budget, $user));
    }

    /**
     * Calculate spent amount for a budget's current period.
     */
    private function enrichWithSpent(Budget $budget, User $user): Budget
    {
        $range = $budget->getCurrentPeriodRange();

        $query = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$range['from'], $range['to']]);

        // Filter by category if budget is category-specific
        if ($budget->category_id) {
            $query->where('category_id', $budget->category_id);
        }

        // Filter by account if budget is account-specific
        if ($budget->account_id) {
            $query->where('account_id', $budget->account_id);
        } else {
            // All accessible accounts
            $query->whereIn('account_id', $user->accessibleAccountIds());
        }

        $spent = (float) $query->sum('amount');
        $percentage = $budget->amount > 0 ? round(($spent / $budget->amount) * 100, 1) : 0;

        // Attach computed values as dynamic attributes
        $budget->setAttribute('spent', $spent);
        $budget->setAttribute('percentage', min($percentage, 100));
        $budget->setAttribute('remaining', max($budget->amount - $spent, 0));
        $budget->setAttribute('is_exceeded', $spent > $budget->amount);
        $budget->setAttribute('is_warning', $percentage >= $budget->alert_threshold && !($spent > $budget->amount));
        $budget->setAttribute('period_from', $range['from']);
        $budget->setAttribute('period_to', $range['to']);

        return $budget;
    }
}
