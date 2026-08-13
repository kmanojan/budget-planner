<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventBudgetItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_attribute_id',
        'label',
        'expected_amount',
        'actual_amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'expected_amount' => 'decimal:2',
            'actual_amount'   => 'decimal:2',
        ];
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(EventAttribute::class, 'event_attribute_id');
    }
}
