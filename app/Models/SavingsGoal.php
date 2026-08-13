<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SavingsGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'account_id',
        'name',
        'target_amount',
        'current_amount',
        'currency_code',
        'deadline',
        'icon',
        'color',
        'is_completed',
    ];

    protected function casts(): array
    {
        return [
            'target_amount'  => 'decimal:2',
            'current_amount' => 'decimal:2',
            'deadline'       => 'date:Y-m-d',
            'is_completed'   => 'boolean',
        ];
    }

    protected $appends = [
        'progress_percentage',
        'remaining_amount',
        'projected_completion_date',
    ];

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SavingsGoalTransaction::class);
    }

    // ── Accessors & Helpers ──

    public function getProgressPercentageAttribute(): float
    {
        if ($this->target_amount <= 0) {
            return 0.0;
        }
        $percentage = ($this->current_amount / $this->target_amount) * 100;
        return round(min($percentage, 100.0), 2);
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0.0, (float)$this->target_amount - (float)$this->current_amount);
    }

    public function getProjectedCompletionDateAttribute(): ?string
    {
        if ($this->is_completed || $this->current_amount >= $this->target_amount) {
            return now()->toDateString();
        }

        $remaining = $this->remaining_amount;
        if ($remaining <= 0) {
            return now()->toDateString();
        }

        // Calculate average monthly saving rate over goal lifetime (or last 30 days)
        $created = $this->created_at ?? now();
        $daysDiff = max(1, now()->diffInDays($created));
        $dailyRate = $this->current_amount > 0 ? ($this->current_amount / $daysDiff) : 0;

        if ($dailyRate <= 0) {
            return null; // Cannot project without active contributions
        }

        $daysNeeded = (int) ceil($remaining / $dailyRate);
        return now()->addDays($daysNeeded)->toDateString();
    }
}
