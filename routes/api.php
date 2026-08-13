<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BillReminderController;
use App\Http\Controllers\Api\V1\BudgetController;
use App\Http\Controllers\Api\V1\CalendarController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CurrencyController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\InvitationController;
use App\Http\Controllers\Api\V1\LabelController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\RecurringTransactionController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SavingsGoalController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Budget Planner v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ──────────────────────────────────────────────
    // Auth (public)
    // ──────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);           // API-AUTH-01
        Route::post('login', [AuthController::class, 'login']);                 // API-AUTH-02
        Route::post('google', [AuthController::class, 'google']);               // API-AUTH-03
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
            ->middleware('throttle:3,1');                                        // API-AUTH-06
        Route::post('verify-otp', [AuthController::class, 'verifyOtp']);        // API-AUTH-07
        Route::post('reset-password', [AuthController::class, 'resetPassword']); // API-AUTH-08
    });

    // ──────────────────────────────────────────────
    // Protected routes
    // ──────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth (protected)
        Route::post('auth/logout', [AuthController::class, 'logout']);          // API-AUTH-04
        Route::get('auth/user', [AuthController::class, 'user']);               // API-AUTH-05

        // Accounts
        Route::get('accounts/shared', [AccountController::class, 'shared']);     // API-ACCT-08
        Route::post('accounts/merge', [AccountController::class, 'merge']);      // API-ACCT-09
        Route::delete('accounts/merge/{groupId}', [AccountController::class, 'unmerge']); // API-ACCT-10
        Route::apiResource('accounts', AccountController::class);                // API-ACCT-01 to 05
        Route::post('accounts/{account}/share', [AccountController::class, 'share']); // API-ACCT-06
        Route::delete('accounts/{account}/share/{userId}', [AccountController::class, 'removeSharedUser']); // API-ACCT-07

        // Transactions
        Route::get('transactions/pending', [TransactionController::class, 'pending']); // API-TXN-06
        Route::apiResource('transactions', TransactionController::class);        // API-TXN-01 to 05

        // Budgets
        Route::get('budgets/summary', [BudgetController::class, 'summary']);     // API-BDG-06
        Route::apiResource('budgets', BudgetController::class);                  // API-BDG-01 to 05

        // Recurring Transactions
        Route::patch('recurring-transactions/{recurring_transaction}/toggle-active',
            [RecurringTransactionController::class, 'toggleActive']);             // API-REC-06
        Route::apiResource('recurring-transactions', RecurringTransactionController::class); // API-REC-01 to 05

        // Savings Goals
        Route::get('savings-goals/{savings_goal}/transactions', [SavingsGoalController::class, 'transactions']);
        Route::post('savings-goals/{savings_goal}/deposit', [SavingsGoalController::class, 'deposit']);
        Route::post('savings-goals/{savings_goal}/withdraw', [SavingsGoalController::class, 'withdraw']);
        Route::apiResource('savings-goals', SavingsGoalController::class);

        // Bill Reminders
        Route::get('bill-reminders/upcoming', [BillReminderController::class, 'upcoming']);
        Route::post('bill-reminders/{bill_reminder}/mark-paid', [BillReminderController::class, 'markPaid']);
        Route::apiResource('bill-reminders', BillReminderController::class);

        // Financial Calendar
        Route::get('calendar', [CalendarController::class, 'index']);

        // Dashboard
        Route::prefix('dashboard')->group(function () {
            Route::get('summary', [DashboardController::class, 'summary']);                     // API-DASH-01
            Route::get('category-breakdown', [DashboardController::class, 'categoryBreakdown']); // API-DASH-02
            Route::get('monthly-trend', [DashboardController::class, 'monthlyTrend']);           // API-DASH-03
            Route::get('top-spending', [DashboardController::class, 'topSpending']);             // API-DASH-04
            Route::get('accounts', [DashboardController::class, 'accounts']);                    // API-DASH-05
        });

        // Categories
        Route::apiResource('categories', CategoryController::class)->except(['show']); // API-CAT-01 to 04

        // Labels
        Route::apiResource('labels', LabelController::class)->except(['show']);  // API-LBL-01 to 04
        Route::patch('labels/{label}/toggle-pin', [LabelController::class, 'togglePin']); // API-LBL-05

        // Profile
        Route::get('profile', [ProfileController::class, 'show']);                     // API-PRO-01
        Route::post('profile', [ProfileController::class, 'update']);                   // API-PRO-02
        Route::post('profile/settings', [ProfileController::class, 'updateSettings']); // API-PRO-03
        Route::patch('profile/device-token', [ProfileController::class, 'updateDeviceToken']); // API-PRO-04

        // Currency
        Route::get('currencies', [CurrencyController::class, 'currencies']);   // API-CUR-01
        Route::post('currencies/convert', [CurrencyController::class, 'convert']); // API-CUR-02
        Route::get('currencies/rates', [CurrencyController::class, 'rates']);  // API-CUR-03

        // Reports
        Route::post('reports/generate', [ReportController::class, 'generate']);    // API-RPT-01
        Route::post('reports/export-csv', [ReportController::class, 'exportCsv']); // API-RPT-02

        // Invitations
        Route::get('invitations', [InvitationController::class, 'index']);      // API-INV-01
        Route::post('invitations/{invitation}/accept', [InvitationController::class, 'accept']); // API-INV-02
        Route::post('invitations/{invitation}/reject', [InvitationController::class, 'reject']); // API-INV-03

        // User Search
        Route::get('users/search', function (Request $request) {                // API-USR-01
            $request->validate(['email' => 'required|string|email']);
            $user = User::where('email', $request->query('email'))->first();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'User not found'], 404);
            }
            return response()->json([
                'status' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_image' => $user->profile_image,
                ],
            ]);
        });
        // Event Budgets
        Route::get('budgets/{budget}/events', [App\Http\Controllers\Api\V1\EventController::class, 'index']);
        Route::post('budgets/{budget}/events', [App\Http\Controllers\Api\V1\EventController::class, 'store']);

        Route::get('events/{event}/report', [App\Http\Controllers\Api\V1\EventController::class, 'report']);
        Route::apiResource('events', App\Http\Controllers\Api\V1\EventController::class);

        Route::post('events/{event}/groups', [App\Http\Controllers\Api\V1\EventGroupController::class, 'store']);
        Route::put('event-groups/{group}', [App\Http\Controllers\Api\V1\EventGroupController::class, 'update']);
        Route::delete('event-groups/{group}', [App\Http\Controllers\Api\V1\EventGroupController::class, 'destroy']);

        Route::post('event-groups/{group}/attributes', [App\Http\Controllers\Api\V1\EventAttributeController::class, 'store']);
        Route::put('event-attributes/{attribute}', [App\Http\Controllers\Api\V1\EventAttributeController::class, 'update']);
        Route::delete('event-attributes/{attribute}', [App\Http\Controllers\Api\V1\EventAttributeController::class, 'destroy']);

        // Subscription
        Route::get('subscription/status', [SubscriptionController::class, 'status']);       // API-SUB-01
        Route::post('subscription/checkout', [SubscriptionController::class, 'checkout']);   // API-SUB-02
        Route::post('subscription/cancel', [SubscriptionController::class, 'cancel']);       // API-SUB-03
        Route::post('subscription/restore', [SubscriptionController::class, 'restore']);     // API-SUB-04
    });
});

// Stripe webhook (no auth required)
Route::post('v1/subscription/webhook', [SubscriptionController::class, 'webhook']);
