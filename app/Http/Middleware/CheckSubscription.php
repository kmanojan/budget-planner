<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    public function __construct(private SubscriptionService $service) {}

    /**
     * Check if the user has access to a pro feature.
     * Usage in routes: ->middleware('pro:feature_name')
     */
    public function handle(Request $request, Closure $next, string $feature = ''): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        if ($feature && !$this->service->canAccessFeature($user, $feature)) {
            return response()->json([
                'success' => false,
                'message' => 'This feature requires BudgetPro+ subscription',
                'upgrade' => true,
                'feature' => $feature,
            ], 403);
        }

        return $next($request);
    }
}
