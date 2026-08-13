<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Budget;
use App\Models\Event;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $query = Event::where('user_id', $request->user()->id);

        if ($request->has('budget_id')) {
            $query->where('budget_id', $request->query('budget_id'));
        }

        if ($request->has('month')) {
            $query->where('month_id', $request->query('month'));
        }

        $events = $query->with(['category', 'budget', 'groups.attributes'])
            ->orderBy('event_date', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success(EventResource::collection($events));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'budget_id'   => 'nullable|exists:budgets,id',
            'event_date'  => 'nullable|date',
            'category_id' => 'nullable|exists:categories,id',
            'month_id'    => 'nullable|string|max:20',
            'status'      => 'nullable|in:planning,ongoing,completed',
        ]);

        $event = $request->user()->events()->create([
            'title'       => $validated['title'],
            'budget_id'   => $validated['budget_id'] ?? null,
            'event_date'  => $validated['event_date'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'month_id'    => $validated['month_id'] ?? null,
            'status'      => $validated['status'] ?? 'planning',
        ]);

        $event->load(['category', 'budget', 'groups.attributes']);

        return $this->created(new EventResource($event), 'Event created');
    }

    public function show(Request $request, Event $event): JsonResponse
    {
        if ($event->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $event->load(['category', 'budget', 'groups.attributes']);

        return $this->success(new EventResource($event));
    }

    public function update(Request $request, Event $event): JsonResponse
    {
        if ($event->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $validated = $request->validate([
            'title'       => 'sometimes|required|string|max:255',
            'budget_id'   => 'nullable|exists:budgets,id',
            'event_date'  => 'nullable|date',
            'category_id' => 'nullable|exists:categories,id',
            'month_id'    => 'nullable|string|max:20',
            'status'      => 'sometimes|in:planning,ongoing,completed',
        ]);

        $event->update($validated);
        $event->load(['category', 'budget', 'groups.attributes']);

        return $this->success(new EventResource($event), 'Event updated');
    }

    public function destroy(Request $request, Event $event): JsonResponse
    {
        if ($event->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $event->delete();

        return $this->noContent('Event deleted');
    }

    public function report(Request $request, Event $event): JsonResponse
    {
        if ($event->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $event->recalculateTotals();
        $event->load('groups.attributes');

        $byGroup = [];
        foreach ($event->groups as $group) {
            $groupExpected = 0.0;
            $groupActual = 0.0;

            foreach ($group->attributes as $attr) {
                if ($attr->type === 'budget') {
                    $groupExpected += (float) ($attr->expected_amount ?? 0);
                    $groupActual += (float) ($attr->actual_amount ?? 0);
                }
            }

            $byGroup[] = [
                'group_id' => $group->id,
                'group'    => $group->name,
                'expected' => $groupExpected,
                'actual'   => $groupActual,
                'variance' => $groupExpected - $groupActual,
            ];
        }

        return $this->success([
            'event_id'       => $event->id,
            'title'          => $event->title,
            'expected_total' => (float) $event->total_expected_budget,
            'actual_total'   => (float) $event->total_actual_budget,
            'variance'       => (float) ($event->total_expected_budget - $event->total_actual_budget),
            'by_group'       => $byGroup,
        ]);
    }
}
