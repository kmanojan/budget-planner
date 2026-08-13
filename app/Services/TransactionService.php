<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TransactionService
{
    public function __construct(
        private ImageUploadService $imageService,
        private AccountService $accountService
    ) {}

    public function getFiltered(array $filters, User $user): LengthAwarePaginator
    {
        $accountIds = $user->accessibleAccountIds();

        $query = Transaction::whereIn('account_id', $accountIds)
            ->with(['account', 'category', 'labels', 'attachments', 'transferToAccount']);

        // Apply filters
        if (!empty($filters['type'])) {
            $query->filterByType($filters['type']);
        }
        if (!empty($filters['account_id'])) {
            $query->filterByAccount((int) $filters['account_id']);
        }
        if (!empty($filters['account_ids'])) {
            $ids = is_string($filters['account_ids']) ? explode(',', $filters['account_ids']) : $filters['account_ids'];
            $query->whereIn('account_id', array_map('intval', $ids));
        }
        if (!empty($filters['category_id'])) {
            $query->filterByCategory((int) $filters['category_id']);
        }
        if (!empty($filters['category_ids'])) {
            $ids = is_string($filters['category_ids']) ? explode(',', $filters['category_ids']) : $filters['category_ids'];
            $query->whereIn('category_id', array_map('intval', $ids));
        }
        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $query->filterByDate($filters['date_from'] ?? null, $filters['date_to'] ?? null);
        }
        if (isset($filters['amount_min']) || isset($filters['amount_max'])) {
            $query->filterByAmountRange(
                isset($filters['amount_min']) ? (float) $filters['amount_min'] : null,
                isset($filters['amount_max']) ? (float) $filters['amount_max'] : null
            );
        }
        if (!empty($filters['status'])) {
            $query->filterByStatus($filters['status']);
        }
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }
        if (!empty($filters['label_ids'])) {
            $labelIds = is_string($filters['label_ids'])
                ? explode(',', $filters['label_ids'])
                : $filters['label_ids'];
            $query->whereHas('labels', fn ($q) => $q->whereIn('labels.id', $labelIds));
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'transaction_date';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Secondary sort for consistent ordering
        if ($sortBy !== 'created_at') {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = $filters['per_page'] ?? 20;

        return $query->paginate($perPage);
    }

    public function create(array $data, User $user): Transaction
    {
        return DB::transaction(function () use ($data, $user) {
            $account = \App\Models\Account::findOrFail($data['account_id']);

            if ($data['type'] === 'transfer') {
                $toAccount = \App\Models\Account::findOrFail($data['transfer_to_account_id']);
                $exchangeRate = $data['exchange_rate'] ?? 1;

                // 1. Create Expense for the From Account
                $expense = Transaction::create([
                    'account_id' => $data['account_id'],
                    'user_id' => $user->id,
                    'category_id' => $data['category_id'] ?? null,
                    'transfer_to_account_id' => $data['transfer_to_account_id'],
                    'type' => 'expense',
                    'amount' => $data['amount'],
                    'exchange_rate' => $exchangeRate,
                    'currency_code' => $account->currency_code,
                    'notes' => $data['notes'] ?? null,
                    'transaction_date' => $data['transaction_date'],
                    'transaction_time' => $data['transaction_time'],
                    'status' => $data['status'] ?? 'completed',
                ]);

                // 2. Create Income for the To Account
                $income = Transaction::create([
                    'account_id' => $data['transfer_to_account_id'],
                    'user_id' => $user->id,
                    'category_id' => $data['category_id'] ?? null,
                    'transfer_to_account_id' => $data['account_id'],
                    'type' => 'income',
                    'amount' => $data['amount'] * $exchangeRate,
                    'exchange_rate' => null,
                    'currency_code' => $toAccount->currency_code,
                    'notes' => $data['notes'] ?? null,
                    'transaction_date' => $data['transaction_date'],
                    'transaction_time' => $data['transaction_time'],
                    'status' => $data['status'] ?? 'completed',
                ]);

                // Sync Labels for both
                if (!empty($data['label_ids'])) {
                    $expense->labels()->sync($data['label_ids']);
                    $income->labels()->sync($data['label_ids']);
                }

                // Upload & Attach Attachments for both
                if (!empty($data['attachments'])) {
                    foreach ($data['attachments'] as $index => $file) {
                        $path = $this->imageService->upload($file, 'attachments');
                        
                        $expense->attachments()->create([
                            'file_path' => $path,
                            'file_name' => $file->getClientOriginalName(),
                            'file_type' => $file->getClientOriginalExtension(),
                            'mime_type' => $file->getMimeType(),
                            'file_size' => $file->getSize(),
                        ]);

                        $income->attachments()->create([
                            'file_path' => $path,
                            'file_name' => $file->getClientOriginalName(),
                            'file_type' => $file->getClientOriginalExtension(),
                            'mime_type' => $file->getMimeType(),
                            'file_size' => $file->getSize(),
                        ]);
                    }
                }

                // Adjust balances
                if (($data['status'] ?? 'completed') === 'completed') {
                    $this->adjustBalancesForTransaction($expense);
                    $this->adjustBalancesForTransaction($income);
                }

                // Return the expense part to the caller
                return $expense->load(['account', 'category', 'labels', 'attachments', 'transferToAccount']);
            }

            // Normal Income/Expense
            $transaction = Transaction::create([
                'account_id' => $data['account_id'],
                'user_id' => $user->id,
                'category_id' => $data['category_id'] ?? null,
                'transfer_to_account_id' => $data['transfer_to_account_id'] ?? null,
                'type' => $data['type'],
                'amount' => $data['amount'],
                'exchange_rate' => $data['exchange_rate'] ?? null,
                'currency_code' => $account->currency_code,
                'notes' => $data['notes'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'transaction_time' => $data['transaction_time'],
                'status' => $data['status'] ?? 'completed',
            ]);

            // Attach labels
            if (!empty($data['label_ids'])) {
                $transaction->labels()->sync($data['label_ids']);
            }

            // Upload attachments
            if (!empty($data['attachments'])) {
                foreach ($data['attachments'] as $index => $file) {
                    $path = $this->imageService->upload($file, 'attachments');
                    $transaction->attachments()->create([
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientOriginalExtension(),
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            // Adjust account balance for completed transactions
            if (($data['status'] ?? 'completed') === 'completed') {
                $this->adjustBalancesForTransaction($transaction);
            }

            return $transaction->load(['account', 'category', 'labels', 'attachments', 'transferToAccount']);
        });
    }

    public function update(Transaction $transaction, array $data): Transaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            // Revert old balance if status was completed
            if ($transaction->status->value === 'completed') {
                $this->adjustBalancesForTransaction($transaction, true);
            }

            $transaction->update($data);

            // Sync labels
            if (array_key_exists('label_ids', $data)) {
                $transaction->labels()->sync($data['label_ids'] ?? []);
            }

            // Remove specified attachments
            if (!empty($data['remove_attachment_ids'])) {
                $attachments = $transaction->attachments()->whereIn('id', $data['remove_attachment_ids'])->get();
                foreach ($attachments as $attachment) {
                    $this->imageService->delete($attachment->file_path);
                    $attachment->delete();
                }
            }

            // Upload new attachments
            if (!empty($data['attachments'])) {
                foreach ($data['attachments'] as $index => $file) {
                    $path = $this->imageService->upload($file, 'attachments');
                    $transaction->attachments()->create([
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientOriginalExtension(),
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            // Apply new balance if status is completed
            if ($transaction->status->value === 'completed') {
                $this->adjustBalancesForTransaction($transaction);
            }

            return $transaction->load(['account', 'category', 'labels', 'attachments', 'transferToAccount']);
        });
    }

    public function delete(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            // Revert balance
            if ($transaction->status->value === 'completed') {
                $this->adjustBalancesForTransaction($transaction, true);
            }

            // Delete attachments from storage
            foreach ($transaction->attachments as $attachment) {
                $this->imageService->delete($attachment->file_path);
            }

            $transaction->delete();
        });
    }

    public function getPending(User $user, ?int $accountId = null): Collection
    {
        if ($accountId) {
            $accountIds = [$accountId];
        } else {
            $accountIds = $user->accessibleAccountIds();
        }

        return Transaction::whereIn('account_id', $accountIds)
            ->where('status', 'pending')
            ->with(['account', 'category', 'labels'])
            ->orderBy('transaction_date', 'desc')
            ->get();
    }

    /**
     * Adjust account balances based on transaction type.
     */
    private function adjustBalancesForTransaction(Transaction $transaction, bool $isRevert = false): void
    {
        $amount = (float) $transaction->amount;
        $type = $transaction->type->value;

        $this->accountService->adjustBalance($transaction->account_id, $amount, $type, $isRevert);

        // For transfers, also adjust the destination account
        if ($type === 'transfer' && $transaction->transfer_to_account_id) {
            $this->accountService->adjustBalance(
                $transaction->transfer_to_account_id,
                $amount * ($transaction->exchange_rate ?? 1),
                'income',
                $isRevert
            );
        }
    }
}
