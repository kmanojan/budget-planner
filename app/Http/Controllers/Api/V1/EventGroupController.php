<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventGroupResource;
use App\Models\Event;
use App\Models\EventGroup;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventGroupController extends Controller
{
    use ApiResponseTrait;

    public function store(Request $request, Event $event): JsonResponse
    {
        if ($event->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'type'       => 'nullable|in:budget,notes,todo',
            'icon'       => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer',
        ]);

        $group = $event->groups()->create([
            'name'       => $validated['name'],
            'type'       => $validated['type'] ?? 'budget',
            'icon'       => $validated['icon'] ?? null,
            'sort_order' => $validated['sort_order'] ?? ($event->groups()->max('sort_order') + 1),
        ]);

        $group->load('attributes');

        return $this->created(new EventGroupResource($group), 'Group created');
    }

    public function update(Request $request, EventGroup $group): JsonResponse
    {
        if ($group->event->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $validated = $request->validate([
            'name'       => 'sometimes|required|string|max:255',
            'icon'       => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer',
        ]);

        $group->update($validated);
        $group->load('attributes');

        return $this->success(new EventGroupResource($group), 'Group updated');
    }

    public function destroy(Request $request, EventGroup $group): JsonResponse
    {
        if ($group->event->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $event = $group->event;
        $group->delete();
        $event->recalculateTotals();

        return $this->noContent('Group deleted');
    }
}
