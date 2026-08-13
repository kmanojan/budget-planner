<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventAttributeResource;
use App\Models\EventAttribute;
use App\Models\EventGroup;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventAttributeController extends Controller
{
    use ApiResponseTrait;

    public function store(Request $request, EventGroup $group): JsonResponse
    {
        if ($group->event->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $validated = $request->validate([
            'type'            => 'nullable|in:budget,notes,todo',
            'name'            => 'required|string|max:255',
            'expected_amount' => 'nullable|numeric|min:0',
            'actual_amount'   => 'nullable|numeric|min:0',
            'content'         => 'nullable|string',
            'is_done'         => 'nullable|boolean',
            'due_date'        => 'nullable|date',
            'sort_order'      => 'nullable|integer',
        ]);

        $attribute = $group->attributes()->create([
            'type'            => $validated['type'] ?? ($group->type ?? 'budget'),
            'name'            => $validated['name'],
            'expected_amount' => $validated['expected_amount'] ?? null,
            'actual_amount'   => $validated['actual_amount'] ?? null,
            'content'         => $validated['content'] ?? null,
            'is_done'         => $validated['is_done'] ?? false,
            'due_date'        => $validated['due_date'] ?? null,
            'sort_order'      => $validated['sort_order'] ?? ($group->attributes()->max('sort_order') + 1),
        ]);

        return $this->created(new EventAttributeResource($attribute), 'Attribute created');
    }

    public function update(Request $request, EventAttribute $attribute): JsonResponse
    {
        if ($attribute->group->event->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $validated = $request->validate([
            'name'            => 'sometimes|required|string|max:255',
            'expected_amount' => 'nullable|numeric|min:0',
            'actual_amount'   => 'nullable|numeric|min:0',
            'content'         => 'nullable|string',
            'is_done'         => 'nullable|boolean',
            'due_date'        => 'nullable|date',
            'sort_order'      => 'nullable|integer',
        ]);

        $attribute->update($validated);

        return $this->success(new EventAttributeResource($attribute), 'Attribute updated');
    }

    public function destroy(Request $request, EventAttribute $attribute): JsonResponse
    {
        if ($attribute->group->event->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $event = $attribute->group->event;
        $attribute->delete();
        $event->recalculateTotals();

        return $this->noContent('Attribute deleted');
    }
}
