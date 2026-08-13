<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private CategoryService $service) {}

    /**
     * API-CAT-01: List all categories
     */
    public function index(Request $request): JsonResponse
    {
        $categories = $this->service->getAll(
            $request->user(),
            $request->query('type')
        );
        return $this->success(CategoryResource::collection($categories));
    }

    /**
     * API-CAT-02: Create category
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->service->create($request->validated(), $request->user());
        return $this->created(new CategoryResource($category), 'Category created');
    }

    /**
     * API-CAT-03: Update category
     */
    public function update(StoreCategoryRequest $request, Category $category): JsonResponse
    {
        if ($category->is_system) {
            return $this->error('Cannot update system category', 403);
        }

        if ($category->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $category = $this->service->update($category, $request->validated());
        return $this->success(new CategoryResource($category), 'Category updated');
    }

    /**
     * API-CAT-04: Delete category
     */
    public function destroy(Request $request, Category $category): JsonResponse
    {
        if ($category->is_system) {
            return $this->error('Cannot delete system category', 403);
        }

        if ($category->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $this->service->delete($category);
        return $this->noContent('Category deleted');
    }
}
