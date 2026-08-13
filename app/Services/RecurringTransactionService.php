<?php

namespace App\Services;

use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecurringTransactionService
{
    public function __construct(
        private AccountService $accountService
    ) {}

    /**
     * Get all recurring transactions for a user.
     */
    public function getAll(User $user): Collection
    {
        return RecurringTransaction::where('user_id', $user->id)
            ->with(['category', 'account'])
            ->orderBy('next_occurrence', 'asc')
            ->get();
    }

    /**
     * Create a new recurring transaction.
     */
    public function create(array $data, User $user): RecurringTransaction
    {
        $data['user_id'] = $user->id;

        // Set currency from account
        $account = \App\Models\Account::findOrFail($data['account_id']);
        $data['currency_code'] = $account->currency_code;

        // Set next_occurrence to start_date if not provided
        if (empty($data['next_occurrence'])) {
            $data['next_occurrence'] = $data['start_date'];
        }

        $recurring = RecurringTransaction::create($data);
        $recurring->load(['category', 'account']);

        return $recurring;
    }

    /**
     * Update a recurring transaction.
     */
    public function update(RecurringTransaction $recurring, array $data): RecurringTransaction
    {
        // If account changed, update currency
        if (!empty($data['account_id']) && $data['account_id'] !== $recurring->account_id) {
            $account = \App\Models\Account::findOrFail($data['account_id']);
            $data['currency_code'] = $account->currency_code;
        }

        $recurring->update($data);
        $recurring->load(['category', 'account']);

        return $recurring;
    }

    /**
     * Delete a recurring transaction.
     */
    public function delete(RecurringTransaction $recurring): void
    {
        $recurring->delete();
    }

    /**
     * Toggle active/paused state.
     */
    public function toggleActive(RecurringTransaction $recurring): RecurringTransaction
    {
        $recurring->update(['is_active' => !$recurring->is_active]);
        return $recurring;
    }

    /**
     * Process all due recurring transactions.
     * This should be called daily via scheduler.
     */
    public function processDue(): int
    {
        $dueRecurrings = RecurringTransaction::due()
            ->with(['account'])
            ->get();

        $processed = 0;

        foreach ($dueRecurrings as $recurring) {
            try {
                DB::transaction(function () use ($recurring) {
                    // Create the actual transaction
                    $transaction = Transaction::create([
                        'account_id'       => $recurring->account_id,
                        'user_id'          => $recurring->user_id,
                        'category_id'      => $recurring->category_id,
                        'type'             => $recurring->type,
                        'amount'           => $recurring->amount,
                        'currency_code'    => $recurring->currency_code,
                        'notes'            => $recurring->notes,
                        'transaction_date' => $recurring->next_occurrence->toDateString(),
                        'transaction_time' => now()->format('H:i:s'),
                        'status'           => 'completed',
                    ]);

                    // Adjust account balance
                    $this->accountService->adjustBalance(
                        $recurring->account_id,
                        (float) $recurring->amount,
                        $recurring->type,
                        false
                    );

                    // Send push notification
                    if ($recurring->user) {
                        $pushService = app(\App\Services\PushNotificationService::class);
                        $type = ucfirst($recurring->type);
                        $amount = $recurring->currency_code . ' ' . $recurring->amount;
                        $pushService->sendToUser(
                            $recurring->user,
                            "Recurring $type Added",
                            "An amount of $amount was added for '{$recurring->notes}'",
                            ['transaction_id' => $transaction->id]
                        );
                    }

                    // Calculate next occurrence
                    $nextDate = $recurring->calculateNextOccurrence();

                    if ($nextDate) {
                        $recurring->update([
                            'next_occurrence'   => $nextDate,
                            'last_processed_at' => now()->toDateString(),
                        ]);
                    } else {
                        // End date passed — deactivate
                        $recurring->update([
                            'is_active'         => false,
                            'last_processed_at' => now()->toDateString(),
                        ]);
                    }
                });

                $processed++;
            } catch (\Exception $e) {
                Log::error("Failed to process recurring transaction #{$recurring->id}: {$e->getMessage()}");
            }
        }

        return $processed;
    }
}
