<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private SubscriptionService $service) {}

    /**
     * Get current subscription status.
     */
    public function status(Request $request): JsonResponse
    {
        return $this->success($this->service->getStatus($request->user()));
    }

    /**
     * Create Stripe Checkout session.
     */
    public function checkout(Request $request): JsonResponse
    {
        $user = $request->user();
        $stripeSecret = config('services.stripe.secret');

        if (!$stripeSecret) {
            return $this->error('Stripe not configured', 500);
        }

        \Stripe\Stripe::setApiKey($stripeSecret);

        // Create or reuse Stripe customer
        if (!$user->stripe_customer_id) {
            $customer = \Stripe\Customer::create([
                'email' => $user->email,
                'name'  => $user->name,
                'metadata' => ['user_id' => $user->id],
            ]);
            $user->update(['stripe_customer_id' => $customer->id]);
        }

        $session = \Stripe\Checkout\Session::create([
            'customer'    => $user->stripe_customer_id,
            'mode'        => 'subscription',
            'line_items'  => [[
                'price' => config('services.stripe.price_id'), // Monthly $2 price ID from Stripe Dashboard
                'quantity' => 1,
            ]],
            'success_url' => config('services.stripe.success_url', 'budgetplanner://subscription-success'),
            'cancel_url'  => config('services.stripe.cancel_url', 'budgetplanner://subscription-cancel'),
            'metadata'    => ['user_id' => $user->id],
        ]);

        return $this->success([
            'checkout_url' => $session->url,
            'session_id'   => $session->id,
        ]);
    }

    /**
     * Handle Stripe webhook.
     */
    public function webhook(Request $request): JsonResponse
    {
        $stripeSecret = config('services.stripe.secret');
        $webhookSecret = config('services.stripe.webhook_secret');

        if (!$stripeSecret || !$webhookSecret) {
            return response()->json(['error' => 'Not configured'], 500);
        }

        \Stripe\Stripe::setApiKey($stripeSecret);

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutComplete($event->data->object);
                break;

            case 'invoice.paid':
                $this->handleInvoicePaid($event->data->object);
                break;

            case 'customer.subscription.deleted':
                $this->handleSubscriptionCancelled($event->data->object);
                break;

            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($event->data->object);
                break;
        }

        return response()->json(['received' => true]);
    }

    /**
     * Cancel subscription.
     */
    public function cancel(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->stripe_subscription_id) {
            return $this->error('No active subscription', 422);
        }

        $stripeSecret = config('services.stripe.secret');
        \Stripe\Stripe::setApiKey($stripeSecret);

        try {
            // Cancel at period end (user keeps pro until expiry)
            \Stripe\Subscription::update($user->stripe_subscription_id, [
                'cancel_at_period_end' => true,
            ]);
            $this->service->cancel($user);

            return $this->success(['message' => 'Subscription will cancel at period end']);
        } catch (\Exception $e) {
            return $this->error('Failed to cancel: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Restore a cancelled (but not yet expired) subscription.
     */
    public function restore(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->stripe_subscription_id) {
            return $this->error('No subscription to restore', 422);
        }

        $stripeSecret = config('services.stripe.secret');
        \Stripe\Stripe::setApiKey($stripeSecret);

        try {
            \Stripe\Subscription::update($user->stripe_subscription_id, [
                'cancel_at_period_end' => false,
            ]);
            return $this->success(['message' => 'Subscription restored']);
        } catch (\Exception $e) {
            return $this->error('Failed to restore: ' . $e->getMessage(), 500);
        }
    }

    // ── Webhook Handlers ──

    private function handleCheckoutComplete($session): void
    {
        $userId = $session->metadata->user_id ?? null;
        if (!$userId) return;

        $user = \App\Models\User::find($userId);
        if (!$user) return;

        $subscriptionId = $session->subscription;
        $stripeSecret = config('services.stripe.secret');
        \Stripe\Stripe::setApiKey($stripeSecret);

        $sub = \Stripe\Subscription::retrieve($subscriptionId);
        $expiresAt = \Carbon\Carbon::createFromTimestamp($sub->current_period_end);

        $this->service->activatePro($user, $session->customer, $subscriptionId, $expiresAt);
    }

    private function handleInvoicePaid($invoice): void
    {
        $customerId = $invoice->customer;
        $user = \App\Models\User::where('stripe_customer_id', $customerId)->first();
        if (!$user) return;

        $subscriptionId = $invoice->subscription;
        $stripeSecret = config('services.stripe.secret');
        \Stripe\Stripe::setApiKey($stripeSecret);

        $sub = \Stripe\Subscription::retrieve($subscriptionId);
        $expiresAt = \Carbon\Carbon::createFromTimestamp($sub->current_period_end);

        $this->service->renew($user, $expiresAt);
    }

    private function handleSubscriptionCancelled($subscription): void
    {
        $customerId = $subscription->customer;
        $user = \App\Models\User::where('stripe_customer_id', $customerId)->first();
        if (!$user) return;

        $this->service->expire($user);
    }

    private function handleSubscriptionUpdated($subscription): void
    {
        if ($subscription->cancel_at_period_end) return; // handled by cancel endpoint

        $customerId = $subscription->customer;
        $user = \App\Models\User::where('stripe_customer_id', $customerId)->first();
        if (!$user) return;

        $expiresAt = \Carbon\Carbon::createFromTimestamp($subscription->current_period_end);
        $this->service->renew($user, $expiresAt);
    }
}
