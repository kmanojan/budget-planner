<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'type',
        'icon',
        'sort_order',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(EventAttribute::class)->orderBy('sort_order', 'asc');
    }
}
