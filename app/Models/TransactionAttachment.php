<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'file_path',
        'file_name',
        'file_type',
        'mime_type',
        'file_size',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
