<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecurringTransaction\StoreRecurringTransactionRequest;
use App\Http\Requests\RecurringTransaction\UpdateRecurringTransactionRequest;
use App\Http\Resources\RecurringTransactionResource;
use App\Models\RecurringTransaction;
use App\Services\RecurringTransactionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecurringTransactionController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private RecurringTransactionService $service) {}

    /**
     * API-REC-01: List all recurring transactions.
     */
    public function index(Request $request): JsonResponse
    {
        $recurrings = $this->service->getAll($request->user());
        return $this->success(RecurringTransactionResource::collection($recurrings));
    }

    /**
     * API-REC-02: Create a recurring transaction.
     */
    public function store(StoreRecurringTransactionRequest $request): JsonResponse
    {
        $recurring = $this->service->create($request->validated(), $request->user());
        return $this->created(new RecurringTransactionResource($recurring), 'Recurring transaction created');
    }

    /**
     * API-REC-03: Get recurring transaction detail.
     */
    public function show(Request $request, RecurringTransaction $recurring_transaction): JsonResponse
    {
        if ($recurring_transaction->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $recurring_transaction->load(['category', 'account']);
        return $this->success(new RecurringTransactionResource($recurring_transaction));
    }

    /**
     * API-REC-04: Update a recurring transaction.
     */
    public function update(UpdateRecurringTransactionRequest $request, RecurringTransaction $recurring_transaction): JsonResponse
    {
        if ($recurring_transaction->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $recurring = $this->service->update($recurring_transaction, $request->validated());
        return $this->success(new RecurringTransactionResource($recurring), 'Recurring transaction updated');
    }

    /**
     * API-REC-05: Delete a recurring transaction.
     */
    public function destroy(Request $request, RecurringTransaction $recurring_transaction): JsonResponse
    {
        if ($recurring_transaction->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $this->service->delete($recurring_transaction);
        return $this->noContent('Recurring transaction deleted');
    }

    /**
     * API-REC-06: Toggle active/paused state.
     */
    public function toggleActive(Request $request, RecurringTransaction $recurring_transaction): JsonResponse
    {
        if ($recurring_transaction->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $recurring = $this->service->toggleActive($recurring_transaction);
        return $this->success(new RecurringTransactionResource($recurring), 'Status updated');
    }
}
