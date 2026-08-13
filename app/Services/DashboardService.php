<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountMerge;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Get income/expense/net summary.
     */
    public function getSummary(User $user, ?string $dateFrom, ?string $dateTo, ?int $accountId): array
    {
        $accountIds = $this->getFilteredAccountIds($user, $accountId);
        $period = $this->getPeriod($dateFrom, $dateTo);

        $query = Transaction::whereIn('account_id', $accountIds)
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$period['from'], $period['to']]);

        $totals = $query->select(
            DB::raw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income"),
            DB::raw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense")
        )->first();

        $totalIncome = (float) ($totals->total_income ?? 0);
        $totalExpense = (float) ($totals->total_expense ?? 0);

        return [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_profit' => $totalIncome - $totalExpense,
            'currency_code' => $this->getPrimaryCurrency($user, $accountId),
            'period' => $period,
        ];
    }

    /**
     * Get category-wise breakdown.
     */
    public function getCategoryBreakdown(User $user, ?string $dateFrom, ?string $dateTo, ?int $accountId, string $type = 'expense'): array
    {
        $accountIds = $this->getFilteredAccountIds($user, $accountId);
        $period = $this->getPeriod($dateFrom, $dateTo);

        $categories = Transaction::whereIn('transactions.account_id', $accountIds)
            ->where('transactions.type', $type)
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.transaction_date', [$period['from'], $period['to']])
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->select(
                'categories.id',
                'categories.name',
                'categories.icon',
                'categories.color',
                DB::raw('SUM(transactions.amount) as total')
            )
            ->groupBy('categories.id', 'categories.name', 'categories.icon', 'categories.color')
            ->orderByDesc('total')
            ->get();

        $grandTotal = $categories->sum('total');

        $categoriesWithPercentage = $categories->map(function ($cat) use ($grandTotal) {
            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'icon' => $cat->icon,
                'color' => $cat->color,
                'total' => (float) $cat->total,
                'percentage' => $grandTotal > 0 ? round(($cat->total / $grandTotal) * 100, 1) : 0,
            ];
        });

        return [
            'type' => $type,
            'categories' => $categoriesWithPercentage->values()->toArray(),
            'total' => $grandTotal,
        ];
    }

    /**
     * Get monthly trend for the last 6 months.
     */
    public function getMonthlyTrend(User $user, ?int $accountId): array
    {
        $accountIds = $this->getFilteredAccountIds($user, $accountId);

        $sixMonthsAgo = now()->subMonths(5)->startOfMonth()->format('Y-m-d');
        $endDate = now()->endOfMonth()->format('Y-m-d');

        $monthFormat = DB::connection()->getDriverName() === 'sqlite' 
            ? "strftime('%Y-%m', transaction_date)" 
            : "DATE_FORMAT(transaction_date, '%Y-%m')";

        $trends = Transaction::whereIn('account_id', $accountIds)
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$sixMonthsAgo, $endDate])
            ->select(
                DB::raw("$monthFormat as month"),
                DB::raw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income"),
                DB::raw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense")
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $trends->map(fn ($t) => [
            'month' => $t->month,
            'income' => (float) $t->income,
            'expense' => (float) $t->expense,
            'net' => (float) $t->income - (float) $t->expense,
        ])->toArray();
    }

    /**
     * Get top 5 spending categories.
     */
    public function getTopSpending(User $user, ?string $dateFrom, ?string $dateTo, ?int $accountId): array
    {
        $accountIds = $this->getFilteredAccountIds($user, $accountId);
        $period = $this->getPeriod($dateFrom, $dateTo);

        return Transaction::whereIn('transactions.account_id', $accountIds)
            ->where('transactions.type', 'expense')
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.transaction_date', [$period['from'], $period['to']])
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->select(
                'categories.id',
                'categories.name',
                'categories.icon',
                'categories.color',
                DB::raw('SUM(transactions.amount) as total')
            )
            ->groupBy('categories.id', 'categories.name', 'categories.icon', 'categories.color')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'icon' => $cat->icon,
                'color' => $cat->color,
                'total' => (float) $cat->total,
            ])
            ->toArray();
    }

    /**
     * Get accounts with merge group info.
     */
    public function getAccountsOverview(User $user): array
    {
        $accounts = Account::where('user_id', $user->id)
            ->orWhereHas('sharedUsers', fn ($q) => $q->where('users.id', $user->id))
            ->with('sharedUsers')
            ->get();

        // Get merge groups
        $mergeGroups = AccountMerge::where('user_id', $user->id)
            ->get()
            ->groupBy('merge_group_id');

        $mergedGroupData = [];
        foreach ($mergeGroups as $groupId => $merges) {
            $groupAccountIds = $merges->pluck('account_id')->toArray();
            $groupAccounts = $accounts->whereIn('id', $groupAccountIds);
            $mergedGroupData[] = [
                'merge_group_id' => $groupId,
                'accounts' => $groupAccounts->values(),
                'total_balance' => $groupAccounts->sum('balance'),
                'currency_code' => $groupAccounts->first()?->currency_code ?? 'LKR',
            ];
        }

        return [
            'accounts' => $accounts,
            'merge_groups' => $mergedGroupData,
            'total_balance' => $accounts->sum('balance'),
        ];
    }

    private function getFilteredAccountIds(User $user, ?int $accountId): array
    {
        if ($accountId) {
            return [$accountId];
        }
        return $user->accessibleAccountIds();
    }

    private function getPeriod(?string $dateFrom, ?string $dateTo): array
    {
        return [
            'from' => $dateFrom ?? now()->startOfMonth()->format('Y-m-d'),
            'to' => $dateTo ?? now()->endOfMonth()->format('Y-m-d'),
        ];
    }

    private function getPrimaryCurrency(User $user, ?int $accountId): string
    {
        if ($accountId) {
            $account = Account::find($accountId);
            return $account?->currency_code ?? 'LKR';
        }

        $primaryAccount = Account::where('user_id', $user->id)->first();
        return $primaryAccount?->currency_code ?? 'LKR';
    }
}
