<?php

namespace App\Services;

use App\Models\User;

class SubscriptionService
{
    /**
     * Feature limits for free users.
     */
    private const FREE_LIMITS = [
        'accounts'           => 3,
        'custom_categories'  => 5,
        'labels'             => 5,
        'budgets'            => 2,
        'shared_users'       => 1,
        'currency_converts'  => 3,  // per day
    ];

    /**
     * Features that are Pro-only (no free access at all).
     */
    private const PRO_ONLY_FEATURES = [
        'recurring_transactions',
        'push_notifications',
        'ai_insights',
        'savings_goals',
        'bill_reminders',
        'debt_tracker',
        'financial_calendar',
        'receipt_scanner',
        'split_expense',
        'advanced_analytics',
        'theme_schedule',
        'csv_export',
        'custom_report_range',
    ];

    /**
     * Get the user's current subscription status.
     */
    public function getStatus(User $user): array
    {
        return [
            'plan'           => $user->isPro() ? 'pro' : 'free',
            'is_pro'         => $user->isPro(),
            'expires_at'     => $user->subscription_expires_at?->toIso8601String(),
            'limits'         => $user->isPro() ? null : self::FREE_LIMITS,
            'pro_features'   => self::PRO_ONLY_FEATURES,
        ];
    }

    /**
     * Check if user can access a pro-only feature.
     */
    public function canAccessFeature(User $user, string $feature): bool
    {
        if ($user->isPro()) return true;
        return !in_array($feature, self::PRO_ONLY_FEATURES);
    }

    /**
     * Check if user has reached a free-tier limit.
     * Returns true if they can still add more.
     */
    public function checkLimit(User $user, string $resource, int $currentCount): bool
    {
        if ($user->isPro()) return true;
        $limit = self::FREE_LIMITS[$resource] ?? null;
        if ($limit === null) return true;
        return $currentCount < $limit;
    }

    /**
     * Get the free limit for a resource.
     */
    public function getFreeLimit(string $resource): ?int
    {
        return self::FREE_LIMITS[$resource] ?? null;
    }

    /**
     * Activate Pro subscription.
     */
    public function activatePro(User $user, string $stripeCustomerId, string $stripeSubscriptionId, \DateTime $expiresAt): void
    {
        $user->update([
            'subscription_plan'       => 'pro',
            'subscription_expires_at' => $expiresAt,
            'stripe_customer_id'      => $stripeCustomerId,
            'stripe_subscription_id'  => $stripeSubscriptionId,
        ]);
    }

    /**
     * Cancel subscription (keep pro until expiry).
     */
    public function cancel(User $user): void
    {
        $user->update([
            'stripe_subscription_id' => null,
        ]);
    }

    /**
     * Expire subscription (set to free).
     */
    public function expire(User $user): void
    {
        $user->update([
            'subscription_plan' => 'free',
            'subscription_expires_at' => null,
            'stripe_subscription_id' => null,
        ]);
    }

    /**
     * Renew subscription (extend expiry).
     */
    public function renew(User $user, \DateTime $newExpiresAt): void
    {
        $user->update([
            'subscription_plan'       => 'pro',
            'subscription_expires_at' => $newExpiresAt,
        ]);
    }
}
