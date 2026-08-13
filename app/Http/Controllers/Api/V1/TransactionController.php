<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private TransactionService $service) {}

    /**
     * API-TXN-01: List transactions (filtered/paginated)
     */
    public function index(Request $request): JsonResponse
    {
        $transactions = $this->service->getFiltered($request->all(), $request->user());
        return $this->paginated(TransactionResource::collection($transactions));
    }

    /**
     * API-TXN-02: Create transaction
     */
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $data = $request->validated();
        if ($request->hasFile('attachments')) {
            $data['attachments'] = $request->file('attachments');
        }

        $transaction = $this->service->create($data, $request->user());
        return $this->created(new TransactionResource($transaction), 'Transaction created');
    }

    /**
     * API-TXN-03: Get transaction detail
     */
    public function show(Request $request, Transaction $transaction): JsonResponse
    {
        $accessibleIds = $request->user()->accessibleAccountIds();
        if (!in_array($transaction->account_id, $accessibleIds)) {
            return $this->error('Forbidden', 403);
        }

        $transaction->load(['account', 'category', 'labels', 'attachments', 'transferToAccount']);
        return $this->success(new TransactionResource($transaction));
    }

    /**
     * API-TXN-04: Update transaction
     */
    public function update(UpdateTransactionRequest $request, Transaction $transaction): JsonResponse
    {
        $accessibleIds = $request->user()->accessibleAccountIds();
        if (!in_array($transaction->account_id, $accessibleIds)) {
            return $this->error('Forbidden', 403);
        }

        $data = $request->validated();
        if ($request->hasFile('attachments')) {
            $data['attachments'] = $request->file('attachments');
        }

        $transaction = $this->service->update($transaction, $data);
        return $this->success(new TransactionResource($transaction), 'Transaction updated');
    }

    /**
     * API-TXN-05: Delete transaction
     */
    public function destroy(Request $request, Transaction $transaction): JsonResponse
    {
        $accessibleIds = $request->user()->accessibleAccountIds();
        if (!in_array($transaction->account_id, $accessibleIds)) {
            return $this->error('Forbidden', 403);
        }

        $this->service->delete($transaction);
        return $this->noContent('Transaction deleted');
    }

    /**
     * API-TXN-06: Get pending transactions
     */
    public function pending(Request $request): JsonResponse
    {
        $accountId = $request->query('account_id') ? (int) $request->query('account_id') : null;
        $transactions = $this->service->getPending($request->user(), $accountId);
        return $this->success(TransactionResource::collection($transactions));
    }
}
