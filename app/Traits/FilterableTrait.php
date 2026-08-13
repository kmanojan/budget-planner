<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait FilterableTrait
{
    public function scopeFilterByDate(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->where('transaction_date', '>=', $from);
        }
        if ($to) {
            $query->where('transaction_date', '<=', $to);
        }
        return $query;
    }

    public function scopeFilterByType(Builder $query, ?string $type): Builder
    {
        if ($type) {
            $query->where('type', $type);
        }
        return $query;
    }

    public function scopeFilterByCategory(Builder $query, ?int $categoryId): Builder
    {
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        return $query;
    }

    public function scopeFilterByAccount(Builder $query, ?int $accountId): Builder
    {
        if ($accountId) {
            $query->where('account_id', $accountId);
        }
        return $query;
    }

    public function scopeFilterByLabel(Builder $query, ?int $labelId): Builder
    {
        if ($labelId) {
            $query->whereHas('labels', function ($q) use ($labelId) {
                $q->where('labels.id', $labelId);
            });
        }
        return $query;
    }

    public function scopeFilterByAmountRange(Builder $query, ?float $min, ?float $max): Builder
    {
        if ($min !== null) {
            $query->where('amount', '>=', $min);
        }
        if ($max !== null) {
            $query->where('amount', '<=', $max);
        }
        return $query;
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                  ->orWhere('amount', 'like', "%{$search}%")
                  ->orWhereHas('category', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('account', function ($aq) use ($search) {
                      $aq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('labels', function ($lq) use ($search) {
                      $lq->where('name', 'like', "%{$search}%");
                  });
            });
        }
        return $query;
    }

    public function scopeFilterByStatus(Builder $query, ?string $status): Builder
    {
        if ($status) {
            $query->where('status', $status);
        }
        return $query;
    }
}
