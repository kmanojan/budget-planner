<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Budget\StoreBudgetRequest;
use App\Http\Requests\Budget\UpdateBudgetRequest;
use App\Http\Resources\BudgetResource;
use App\Models\Budget;
use App\Services\BudgetService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private BudgetService $service) {}

    /**
     * API-BDG-01: List all budgets with spent progress.
     */
    public function index(Request $request): JsonResponse
    {
        $budgets = $this->service->getAll($request->user());
        return $this->success(BudgetResource::collection($budgets));
    }

    /**
     * API-BDG-02: Create a new budget.
     */
    public function store(StoreBudgetRequest $request): JsonResponse
    {
        $budget = $this->service->create($request->validated(), $request->user());
        return $this->created(new BudgetResource($budget), 'Budget created');
    }

    /**
     * API-BDG-03: Get budget detail.
     */
    public function show(Request $request, Budget $budget): JsonResponse
    {
        if ($budget->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $budget = $this->service->getById($budget->id, $request->user());
        return $this->success(new BudgetResource($budget));
    }

    /**
     * API-BDG-04: Update a budget.
     */
    public function update(UpdateBudgetRequest $request, Budget $budget): JsonResponse
    {
        if ($budget->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $budget = $this->service->update($budget, $request->validated());
        return $this->success(new BudgetResource($budget), 'Budget updated');
    }

    /**
     * API-BDG-05: Delete a budget.
     */
    public function destroy(Request $request, Budget $budget): JsonResponse
    {
        if ($budget->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $this->service->delete($budget);
        return $this->noContent('Budget deleted');
    }

    /**
     * API-BDG-06: Dashboard summary (top budgets with progress).
     */
    public function summary(Request $request): JsonResponse
    {
        $accountId = $request->query('account_id') ? (int) $request->query('account_id') : null;
        $budgets = $this->service->getDashboardSummary($request->user(), $accountId);
        return $this->success(BudgetResource::collection($budgets));
    }
}
