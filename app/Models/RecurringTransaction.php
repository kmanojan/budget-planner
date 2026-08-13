<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'account_id',
        'category_id',
        'type',
        'amount',
        'currency_code',
        'notes',
        'frequency',
        'start_date',
        'end_date',
        'next_occurrence',
        'last_processed_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount'            => 'decimal:2',
            'start_date'        => 'date:Y-m-d',
            'end_date'          => 'date:Y-m-d',
            'next_occurrence'   => 'date:Y-m-d',
            'last_processed_at' => 'date:Y-m-d',
            'is_active'         => 'boolean',
        ];
    }

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // ── Helpers ──

    /**
     * Calculate the next occurrence date based on frequency.
     */
    public function calculateNextOccurrence(): ?string
    {
        $current = $this->next_occurrence ?? $this->start_date;
        $next = match ($this->frequency) {
            'daily'   => $current->copy()->addDay(),
            'weekly'  => $current->copy()->addWeek(),
            'monthly' => $current->copy()->addMonth(),
            'yearly'  => $current->copy()->addYear(),
            default   => null,
        };

        // If end_date is set and next is beyond it, return null (expired)
        if ($next && $this->end_date && $next->greaterThan($this->end_date)) {
            return null;
        }

        return $next?->toDateString();
    }

    /**
     * Scope: due recurring transactions.
     */
    public function scopeDue($query)
    {
        return $query->where('is_active', true)
            ->where('next_occurrence', '<=', now()->toDateString());
    }
}
