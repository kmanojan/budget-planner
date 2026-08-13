<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'amount',
        'currency_code',
        'due_date',
        'frequency',
        'remind_days_before',
        'is_paid',
    ];

    protected function casts(): array
    {
        return [
            'amount'             => 'decimal:2',
            'due_date'           => 'date:Y-m-d',
            'remind_days_before' => 'integer',
            'is_paid'            => 'boolean',
        ];
    }

    protected $appends = [
        'is_overdue',
        'days_until_due',
    ];

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // ── Helpers & Accessors ──

    public function getIsOverdueAttribute(): bool
    {
        if ($this->is_paid) {
            return false;
        }
        return now()->startOfDay()->gt($this->due_date);
    }

    public function getDaysUntilDueAttribute(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->due_date, false);
    }
}
