<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_attribute_id',
        'content',
    ];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(EventAttribute::class, 'event_attribute_id');
    }
}
