<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'month'      => 'nullable|date_format:Y-m',
            'account_id' => 'nullable|exists:accounts,id',
        ]);

        $monthStr = $request->query('month', now()->format('Y-m'));
        $startOfMonth = Carbon::createFromFormat('Y-m', $monthStr)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $query = Transaction::where('user_id', $request->user()->id)
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()]);

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        $transactions = $query->with(['category', 'account'])->get();

        // Group transactions by date
        $dailySummary = [];
        $totalIncome = 0.0;
        $totalExpense = 0.0;

        foreach ($transactions as $txn) {
            $dateStr = Carbon::parse($txn->date)->toDateString();

            if (!isset($dailySummary[$dateStr])) {
                $dailySummary[$dateStr] = [
                    'date'         => $dateStr,
                    'income'       => 0.0,
                    'expense'      => 0.0,
                    'net'          => 0.0,
                    'transactions' => [],
                ];
            }

            $amount = (float) $txn->amount;

            if ($txn->type === 'income') {
                $dailySummary[$dateStr]['income'] += $amount;
                $totalIncome += $amount;
            } else if ($txn->type === 'expense') {
                $dailySummary[$dateStr]['expense'] += $amount;
                $totalExpense += $amount;
            }

            $dailySummary[$dateStr]['transactions'][] = [
                'id'            => $txn->id,
                'type'          => $txn->type,
                'amount'        => $amount,
                'currency_code' => $txn->currency_code,
                'notes'         => $txn->notes,
                'category_name' => $txn->category?->name,
                'category_icon' => $txn->category?->icon,
                'account_name'  => $txn->account?->name,
            ];
        }

        // Calculate net for each day
        foreach ($dailySummary as $dateStr => &$day) {
            $day['net'] = round($day['income'] - $day['expense'], 2);
            $day['income'] = round($day['income'], 2);
            $day['expense'] = round($day['expense'], 2);
        }

        return response()->json([
            'status' => true,
            'data'   => [
                'month'         => $monthStr,
                'total_income'  => round($totalIncome, 2),
                'total_expense' => round($totalExpense, 2),
                'net_savings'   => round($totalIncome - $totalExpense, 2),
                'days'          => (object) $dailySummary,
            ],
        ]);
    }
}
