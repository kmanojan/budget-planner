<?php

namespace App\Models;

use App\Enums\AccountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'currency_code',
        'balance',
        'color',
        'icon',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function sharedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'account_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function merges(): HasMany
    {
        return $this->hasMany(AccountMerge::class);
    }

    public function transfersIn(): HasMany
    {
        return $this->hasMany(Transaction::class, 'transfer_to_account_id');
    }
}
