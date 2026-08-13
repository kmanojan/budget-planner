<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Traits\FilterableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory, FilterableTrait;

    protected $fillable = [
        'account_id',
        'user_id',
        'category_id',
        'transfer_to_account_id',
        'type',
        'amount',
        'exchange_rate',
        'currency_code',
        'notes',
        'transaction_date',
        'transaction_time',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'status' => TransactionStatus::class,
            'amount' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'transaction_date' => 'date:Y-m-d',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function transferToAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'transfer_to_account_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TransactionAttachment::class);
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'transaction_label');
    }
}
