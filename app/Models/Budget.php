<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'account_id',
        'name',
        'amount',
        'period',
        'currency_code',
        'start_date',
        'end_date',
        'alert_threshold',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount'          => 'decimal:2',
            'start_date'      => 'date:Y-m-d',
            'end_date'        => 'date:Y-m-d',
            'alert_threshold' => 'integer',
            'is_active'       => 'boolean',
        ];
    }

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function events(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Event::class);
    }

    // ── Helpers ──

    /**
     * Get the current period date range based on budget period type.
     */
    public function getCurrentPeriodRange(): array
    {
        $now = now();

        return match ($this->period) {
            'weekly' => [
                'from' => $now->copy()->startOfWeek()->toDateString(),
                'to'   => $now->copy()->endOfWeek()->toDateString(),
            ],
            'yearly' => [
                'from' => $now->copy()->startOfYear()->toDateString(),
                'to'   => $now->copy()->endOfYear()->toDateString(),
            ],
            default => [ // monthly
                'from' => $now->copy()->startOfMonth()->toDateString(),
                'to'   => $now->copy()->endOfMonth()->toDateString(),
            ],
        };
    }
}
