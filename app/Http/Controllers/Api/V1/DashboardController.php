<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private DashboardService $service) {}

    /**
     * API-DASH-01: Income/Expense/Net summary
     */
    public function summary(Request $request): JsonResponse
    {
        $data = $this->service->getSummary(
            $request->user(),
            $request->query('date_from'),
            $request->query('date_to'),
            $request->query('account_id') ? (int) $request->query('account_id') : null
        );

        return $this->success($data);
    }

    /**
     * API-DASH-02: Category-wise breakdown
     */
    public function categoryBreakdown(Request $request): JsonResponse
    {
        $data = $this->service->getCategoryBreakdown(
            $request->user(),
            $request->query('date_from'),
            $request->query('date_to'),
            $request->query('account_id') ? (int) $request->query('account_id') : null,
            $request->query('type', 'expense')
        );

        return $this->success($data);
    }

    /**
     * API-DASH-03: Monthly trend (6 months)
     */
    public function monthlyTrend(Request $request): JsonResponse
    {
        $data = $this->service->getMonthlyTrend(
            $request->user(),
            $request->query('account_id') ? (int) $request->query('account_id') : null
        );

        return $this->success($data);
    }

    /**
     * API-DASH-04: Top 5 spending categories
     */
    public function topSpending(Request $request): JsonResponse
    {
        $data = $this->service->getTopSpending(
            $request->user(),
            $request->query('date_from'),
            $request->query('date_to'),
            $request->query('account_id') ? (int) $request->query('account_id') : null
        );

        return $this->success($data);
    }

    /**
     * API-DASH-05: Accounts with merge info
     */
    public function accounts(Request $request): JsonResponse
    {
        $data = $this->service->getAccountsOverview($request->user());
        return $this->success($data);
    }
}
