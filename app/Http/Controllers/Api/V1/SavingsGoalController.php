<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SavingsGoal\StoreSavingsGoalRequest;
use App\Http\Requests\SavingsGoal\UpdateSavingsGoalRequest;
use App\Http\Resources\SavingsGoalResource;
use App\Http\Resources\SavingsGoalTransactionResource;
use App\Models\SavingsGoal;
use App\Models\SavingsGoalTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SavingsGoalController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $goals = $request->user()
            ->savingsGoals()
            ->with('account')
            ->orderBy('is_completed', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        return SavingsGoalResource::collection($goals);
    }

    public function store(StoreSavingsGoalRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        if (empty($data['currency_code'])) {
            $data['currency_code'] = $request->user()->currency_code ?? 'LKR';
        }

        $goal = SavingsGoal::create($data);

        // Check completion status upon creation
        if ($goal->current_amount >= $goal->target_amount) {
            $goal->update(['is_completed' => true]);
        }

        // Log initial deposit if current_amount > 0
        if ($goal->current_amount > 0) {
            SavingsGoalTransaction::create([
                'savings_goal_id' => $goal->id,
                'type'            => 'deposit',
                'amount'          => $goal->current_amount,
                'note'            => 'Initial Funding',
                'transaction_date'=> now(),
                'balance_after'   => $goal->current_amount,
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Savings goal created successfully',
            'data'    => new SavingsGoalResource($goal),
        ], 201);
    }

    public function show(Request $request, SavingsGoal $savingsGoal): JsonResponse
    {
        if ($savingsGoal->user_id !== $request->user()->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $savingsGoal->load('account');

        return response()->json([
            'status' => true,
            'data'   => new SavingsGoalResource($savingsGoal),
        ]);
    }

    public function update(UpdateSavingsGoalRequest $request, SavingsGoal $savingsGoal): JsonResponse
    {
        if ($savingsGoal->user_id !== $request->user()->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $data = $request->validated();
        $savingsGoal->update($data);

        // Check if goal auto-completes
        if ($savingsGoal->current_amount >= $savingsGoal->target_amount && !$savingsGoal->is_completed) {
            $savingsGoal->update(['is_completed' => true]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Savings goal updated successfully',
            'data'    => new SavingsGoalResource($savingsGoal->fresh('account')),
        ]);
    }

    public function destroy(Request $request, SavingsGoal $savingsGoal): JsonResponse
    {
        if ($savingsGoal->user_id !== $request->user()->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $savingsGoal->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Savings goal deleted successfully',
        ]);
    }

    public function deposit(Request $request, SavingsGoal $savingsGoal): JsonResponse
    {
        if ($savingsGoal->user_id !== $request->user()->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'note'   => 'nullable|string|max:255',
        ]);
        $amount = (float) $request->amount;
        $note = $request->input('note') ?? 'Deposit';

        $newAmount = $savingsGoal->current_amount + $amount;
        $isCompleted = $newAmount >= $savingsGoal->target_amount;

        $savingsGoal->update([
            'current_amount' => $newAmount,
            'is_completed'   => $isCompleted,
        ]);

        SavingsGoalTransaction::create([
            'savings_goal_id' => $savingsGoal->id,
            'type'            => 'deposit',
            'amount'          => $amount,
            'note'            => $note,
            'transaction_date'=> now(),
            'balance_after'   => $newAmount,
        ]);

        return response()->json([
            'status'  => true,
            'message' => $isCompleted ? 'Deposit added! Goal completed! 🎉' : 'Deposit added successfully',
            'data'    => new SavingsGoalResource($savingsGoal->fresh('account')),
        ]);
    }

    public function withdraw(Request $request, SavingsGoal $savingsGoal): JsonResponse
    {
        if ($savingsGoal->user_id !== $request->user()->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'note'   => 'nullable|string|max:255',
        ]);
        $amount = (float) $request->amount;
        $note = $request->input('note') ?? 'Withdrawal';

        $newAmount = max(0, $savingsGoal->current_amount - $amount);
        $isCompleted = $newAmount >= $savingsGoal->target_amount;

        $savingsGoal->update([
            'current_amount' => $newAmount,
            'is_completed'   => $isCompleted,
        ]);

        SavingsGoalTransaction::create([
            'savings_goal_id' => $savingsGoal->id,
            'type'            => 'withdraw',
            'amount'          => $amount,
            'note'            => $note,
            'transaction_date'=> now(),
            'balance_after'   => $newAmount,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Funds withdrawn from savings goal',
            'data'    => new SavingsGoalResource($savingsGoal->fresh('account')),
        ]);
    }

    public function transactions(Request $request, SavingsGoal $savingsGoal): JsonResponse
    {
        if ($savingsGoal->user_id !== $request->user()->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $txns = $savingsGoal->transactions()
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $totalDeposits = (float) $savingsGoal->transactions()->where('type', 'deposit')->sum('amount');
        $totalWithdrawals = (float) $savingsGoal->transactions()->where('type', 'withdraw')->sum('amount');
        $netBalance = $totalDeposits - $totalWithdrawals;

        return response()->json([
            'status' => true,
            'data'   => [
                'goal_id'           => $savingsGoal->id,
                'goal_name'         => $savingsGoal->name,
                'target_amount'     => (float) $savingsGoal->target_amount,
                'current_amount'    => (float) $savingsGoal->current_amount,
                'currency_code'     => $savingsGoal->currency_code,
                'total_deposits'    => $totalDeposits,
                'total_withdrawals' => $totalWithdrawals,
                'net_balance'       => $netBalance,
                'remaining_amount'  => (float) $savingsGoal->remaining_amount,
                'transactions'      => SavingsGoalTransactionResource::collection($txns),
            ],
        ]);
    }
}
