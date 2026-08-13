<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavingsGoalTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'savings_goal_id',
        'type',
        'amount',
        'note',
        'transaction_date',
        'balance_after',
    ];

    protected function casts(): array
    {
        return [
            'amount'           => 'decimal:2',
            'balance_after'    => 'decimal:2',
            'transaction_date' => 'datetime',
        ];
    }

    public function savingsGoal(): BelongsTo
    {
        return $this->belongsTo(SavingsGoal::class);
    }
}
