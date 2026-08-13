<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'budget_id',
        'category_id',
        'month_id',
        'title',
        'event_date',
        'total_expected_budget',
        'total_actual_budget',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'total_expected_budget' => 'decimal:2',
            'total_actual_budget'   => 'decimal:2',
            'event_date'            => 'date:Y-m-d',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(EventGroup::class)->orderBy('sort_order', 'asc');
    }

    /**
     * Recalculate and persist total_expected_budget and total_actual_budget.
     */
    public function recalculateTotals(): void
    {
        $groupIds = $this->groups()->pluck('id');

        $expected = EventAttribute::whereIn('event_group_id', $groupIds)
            ->where('type', 'budget')
            ->sum('expected_amount');

        $actual = EventAttribute::whereIn('event_group_id', $groupIds)
            ->where('type', 'budget')
            ->sum('actual_amount');

        $this->update([
            'total_expected_budget' => $expected,
            'total_actual_budget'   => $actual,
        ]);
    }
}
