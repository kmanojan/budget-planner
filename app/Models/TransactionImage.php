<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TransactionImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'image_path',
        'sort_order',
    ];

    protected $appends = ['url'];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->image_path);
    }
}
