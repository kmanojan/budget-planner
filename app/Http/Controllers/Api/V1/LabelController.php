<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Label\StoreLabelRequest;
use App\Http\Resources\LabelResource;
use App\Models\Label;
use App\Services\LabelService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LabelController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private LabelService $service)
    {
    }

    /**
     * API-LBL-01: List all labels
     */
    public function index(Request $request): JsonResponse
    {
        $labels = $this->service->getAll($request->user());
        return $this->success(LabelResource::collection($labels));
    }

    /**
     * API-LBL-02: Create label
     */
    public function store(StoreLabelRequest $request): JsonResponse
    {
        Log::info('StoreLabelRequest');
        Log::info($request->all());
        $label = $this->service->create($request->validated(), $request->user());
        return $this->created(new LabelResource($label), 'Label created');
    }

    /**
     * API-LBL-03: Update label
     */
    public function update(StoreLabelRequest $request, Label $label): JsonResponse
    {
        if ($label->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $label = $this->service->update($label, $request->validated());
        return $this->success(new LabelResource($label), 'Label updated');
    }

    /**
     * API-LBL-04: Delete label
     */
    public function destroy(Request $request, Label $label): JsonResponse
    {
        if ($label->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $this->service->delete($label);
        return $this->noContent('Label deleted');
    }

    /**
     * API-LBL-05: Toggle pin
     */
    public function togglePin(Request $request, Label $label): JsonResponse
    {
        if ($label->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $label = $this->service->togglePin($label);
        return $this->success(new LabelResource($label), 'Label pin toggled');
    }
}