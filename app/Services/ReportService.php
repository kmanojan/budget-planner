<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Generate a comprehensive report for a date range.
     */
    public function generateReport(User $user, string $dateFrom, string $dateTo, ?int $accountId = null): array
    {
        $accountIds = $this->getAccountIds($user, $accountId);

        $baseQuery = Transaction::whereIn('account_id', $accountIds)
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$dateFrom, $dateTo]);

        // Summary totals
        $totals = (clone $baseQuery)->select(
            DB::raw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income"),
            DB::raw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense"),
            DB::raw("COUNT(*) as total_transactions")
        )->first();

        $totalIncome = (float) ($totals->total_income ?? 0);
        $totalExpense = (float) ($totals->total_expense ?? 0);

        // Daily breakdown
        $daily = (clone $baseQuery)->select(
            'transaction_date',
            DB::raw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income"),
            DB::raw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense")
        )
            ->groupBy('transaction_date')
            ->orderBy('transaction_date')
            ->get()
            ->map(fn($d) => [
                'date'    => $d->transaction_date,
                'income'  => (float) $d->income,
                'expense' => (float) $d->expense,
                'net'     => (float) $d->income - (float) $d->expense,
            ])->toArray();

        // Category breakdown (expense)
        $expenseByCategory = (clone $baseQuery)
            ->where('transactions.type', 'expense')
            ->whereNotNull('category_id')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->select(
                'categories.name',
                'categories.icon',
                'categories.color',
                DB::raw('SUM(transactions.amount) as total')
            )
            ->groupBy('categories.name', 'categories.icon', 'categories.color')
            ->orderByDesc('total')
            ->get()
            ->map(fn($c) => [
                'name'       => $c->name,
                'icon'       => $c->icon,
                'color'      => $c->color,
                'total'      => (float) $c->total,
                'percentage' => $totalExpense > 0 ? round(($c->total / $totalExpense) * 100, 1) : 0,
            ])->toArray();

        // Category breakdown (income)
        $incomeByCategory = (clone $baseQuery)
            ->where('transactions.type', 'income')
            ->whereNotNull('category_id')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->select(
                'categories.name',
                'categories.icon',
                'categories.color',
                DB::raw('SUM(transactions.amount) as total')
            )
            ->groupBy('categories.name', 'categories.icon', 'categories.color')
            ->orderByDesc('total')
            ->get()
            ->map(fn($c) => [
                'name'       => $c->name,
                'icon'       => $c->icon,
                'color'      => $c->color,
                'total'      => (float) $c->total,
                'percentage' => $totalIncome > 0 ? round(($c->total / $totalIncome) * 100, 1) : 0,
            ])->toArray();

        // Average daily spend
        $dayCount = max(1, (int) now()->parse($dateFrom)->diffInDays(now()->parse($dateTo)) + 1);
        $avgDailyExpense = round($totalExpense / $dayCount, 2);
        $avgDailyIncome = round($totalIncome / $dayCount, 2);

        return [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'summary' => [
                'total_income'       => $totalIncome,
                'total_expense'      => $totalExpense,
                'net_savings'        => $totalIncome - $totalExpense,
                'savings_rate'       => $totalIncome > 0 ? round((($totalIncome - $totalExpense) / $totalIncome) * 100, 1) : 0,
                'total_transactions' => (int) $totals->total_transactions,
                'avg_daily_expense'  => $avgDailyExpense,
                'avg_daily_income'   => $avgDailyIncome,
            ],
            'expense_by_category' => $expenseByCategory,
            'income_by_category'  => $incomeByCategory,
            'daily_breakdown'     => $daily,
        ];
    }

    /**
     * Export transactions as CSV string.
     */
    public function exportCsv(User $user, string $dateFrom, string $dateTo, ?int $accountId = null): string
    {
        $accountIds = $this->getAccountIds($user, $accountId);

        $transactions = Transaction::whereIn('account_id', $accountIds)
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->with(['category', 'account'])
            ->orderBy('transaction_date', 'asc')
            ->get();

        $csv = "Date,Time,Type,Category,Account,Amount,Currency,Notes\n";

        foreach ($transactions as $txn) {
            $date = $txn->transaction_date;
            $time = $txn->transaction_time ?? '';
            $type = ucfirst($txn->type);
            $category = str_replace(',', ' ', $txn->category?->name ?? 'Uncategorized');
            $account = str_replace(',', ' ', $txn->account?->name ?? 'Unknown');
            $amount = $txn->amount;
            $currency = $txn->currency_code;
            $notes = str_replace([',', "\n", "\r"], [' ', ' ', ''], $txn->notes ?? '');

            $csv .= "{$date},{$time},{$type},{$category},{$account},{$amount},{$currency},{$notes}\n";
        }

        return $csv;
    }

    private function getAccountIds(User $user, ?int $accountId): array
    {
        if ($accountId) {
            return [$accountId];
        }
        return $user->accessibleAccountIds();
    }
}
