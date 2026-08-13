<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BillReminder\StoreBillReminderRequest;
use App\Http\Requests\BillReminder\UpdateBillReminderRequest;
use App\Http\Resources\BillReminderResource;
use App\Models\BillReminder;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BillReminderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $bills = $request->user()
            ->billReminders()
            ->with('category')
            ->orderBy('is_paid', 'asc')
            ->orderBy('due_date', 'asc')
            ->get();

        return BillReminderResource::collection($bills);
    }

    public function store(StoreBillReminderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        if (empty($data['currency_code'])) {
            $data['currency_code'] = $request->user()->currency_code ?? 'LKR';
        }

        $bill = BillReminder::create($data);

        return response()->json([
            'status'  => true,
            'message' => 'Bill reminder created successfully',
            'data'    => new BillReminderResource($bill->load('category')),
        ], 201);
    }

    public function show(Request $request, BillReminder $billReminder): JsonResponse
    {
        if ($billReminder->user_id !== $request->user()->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'status' => true,
            'data'   => new BillReminderResource($billReminder->load('category')),
        ]);
    }

    public function update(UpdateBillReminderRequest $request, BillReminder $billReminder): JsonResponse
    {
        if ($billReminder->user_id !== $request->user()->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $billReminder->update($request->validated());

        return response()->json([
            'status'  => true,
            'message' => 'Bill reminder updated successfully',
            'data'    => new BillReminderResource($billReminder->fresh('category')),
        ]);
    }

    public function destroy(Request $request, BillReminder $billReminder): JsonResponse
    {
        if ($billReminder->user_id !== $request->user()->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $billReminder->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Bill reminder deleted successfully',
        ]);
    }

    public function markPaid(Request $request, BillReminder $billReminder): JsonResponse
    {
        if ($billReminder->user_id !== $request->user()->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'create_transaction' => 'nullable|boolean',
            'account_id'         => 'required_if:create_transaction,true|nullable|exists:accounts,id',
        ]);

        // Mark as paid
        $billReminder->update(['is_paid' => true]);

        // If user requested transaction generation
        if ($request->boolean('create_transaction') && $request->account_id) {
            Transaction::create([
                'user_id'       => $request->user()->id,
                'account_id'    => $request->account_id,
                'category_id'   => $billReminder->category_id,
                'type'          => 'expense',
                'amount'        => $billReminder->amount,
                'currency_code' => $billReminder->currency_code,
                'date'          => now()->toDateString(),
                'notes'         => 'Bill Payment: ' . $billReminder->name,
            ]);
        }

        // If frequency is monthly/yearly, automatically schedule the next occurrence!
        if ($billReminder->frequency === 'monthly') {
            BillReminder::create([
                'user_id'            => $billReminder->user_id,
                'category_id'        => $billReminder->category_id,
                'name'               => $billReminder->name,
                'amount'             => $billReminder->amount,
                'currency_code'      => $billReminder->currency_code,
                'due_date'           => $billReminder->due_date->addMonth()->toDateString(),
                'frequency'          => 'monthly',
                'remind_days_before' => $billReminder->remind_days_before,
                'is_paid'            => false,
            ]);
        } else if ($billReminder->frequency === 'yearly') {
            BillReminder::create([
                'user_id'            => $billReminder->user_id,
                'category_id'        => $billReminder->category_id,
                'name'               => $billReminder->name,
                'amount'             => $billReminder->amount,
                'currency_code'      => $billReminder->currency_code,
                'due_date'           => $billReminder->due_date->addYear()->toDateString(),
                'frequency'          => 'yearly',
                'remind_days_before' => $billReminder->remind_days_before,
                'is_paid'            => false,
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Bill marked as paid!',
            'data'    => new BillReminderResource($billReminder->fresh('category')),
        ]);
    }

    public function upcoming(Request $request): AnonymousResourceCollection
    {
        $upcoming = $request->user()
            ->billReminders()
            ->with('category')
            ->where('is_paid', false)
            ->where('due_date', '>=', now()->startOfDay())
            ->orderBy('due_date', 'asc')
            ->take(5)
            ->get();

        return BillReminderResource::collection($upcoming);
    }
}
