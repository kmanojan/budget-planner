<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'auth_provider',
        'currency_code',
        'country',
        'avatar_url',
        'device_token',
        'subscription_plan',
        'subscription_expires_at',
        'stripe_customer_id',
        'stripe_subscription_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'subscription_expires_at' => 'datetime',
        ];
    }

    /**
     * Check if user has active Pro subscription.
     */
    public function isPro(): bool
    {
        return $this->subscription_plan === 'pro'
            && $this->subscription_expires_at
            && $this->subscription_expires_at->isFuture();
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function sharedAccounts(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'account_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function labels(): HasMany
    {
        return $this->hasMany(Label::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function recurringTransactions(): HasMany
    {
        return $this->hasMany(RecurringTransaction::class);
    }

    public function savingsGoals(): HasMany
    {
        return $this->hasMany(SavingsGoal::class);
    }

    public function billReminders(): HasMany
    {
        return $this->hasMany(BillReminder::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function sentInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'sender_id');
    }

    public function receivedInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'receiver_id');
    }

    /**
     * Get all accounts accessible by this user (owned + shared).
     */
    public function accessibleAccountIds(): array
    {
        $ownedIds = $this->accounts()->pluck('id')->toArray();
        $sharedIds = $this->sharedAccounts()->pluck('accounts.id')->toArray();
        return array_unique(array_merge($ownedIds, $sharedIds));
    }
}
