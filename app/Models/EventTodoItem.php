<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventTodoItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_attribute_id',
        'title',
        'is_done',
        'due_date',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_done'  => 'boolean',
            'due_date' => 'date:Y-m-d',
        ];
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(EventAttribute::class, 'event_attribute_id');
    }
}
