<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_group_id',
        'type',
        'name',
        'expected_amount',
        'actual_amount',
        'content',
        'is_done',
        'due_date',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'expected_amount' => 'decimal:2',
            'actual_amount'   => 'decimal:2',
            'is_done'         => 'boolean',
            'due_date'        => 'date:Y-m-d',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(EventGroup::class, 'event_group_id');
    }
}
