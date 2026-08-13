<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventBudgetItemResource;
use App\Http\Resources\EventNoteResource;
use App\Http\Resources\EventTodoItemResource;
use App\Models\EventAttribute;
use App\Models\EventBudgetItem;
use App\Models\EventNote;
use App\Models\EventTodoItem;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventItemController extends Controller
{
    use ApiResponseTrait;

    // ── Budget Items ──

    public function storeBudgetItem(Request $request, EventAttribute $attribute): JsonResponse
    {
        if ($attribute->group->event->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        if ($attribute->type !== 'budget') {
            return $this->error('Attribute is not of type budget', 422);
        }

        $validated = $request->validate([
            'label'           => 'required|string|max:255',
            'expected_amount' => 'required|numeric|min:0',
            'actual_amount'   => 'nullable|numeric|min:0',
            'notes'           => 'nullable|string|max:1000',
        ]);

        $item = $attribute->budgetItems()->create($validated);

        return $this->created(new EventBudgetItemResource($item), 'Budget item created');
    }

    public function updateBudgetItem(Request $request, EventBudgetItem $item): JsonResponse
    {
        if ($item->attribute->group->event->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $validated = $request->validate([
            'label'           => 'sometimes|required|string|max:255',
            'expected_amount' => 'sometimes|required|numeric|min:0',
            'actual_amount'   => 'nullable|numeric|min:0',
            'notes'           => 'nullable|string|max:1000',
        ]);

        $item->update($validated);

        return $this->success(new EventBudgetItemResource($item), 'Budget item updated');
    }

    public function destroyBudgetItem(Request $request, EventBudgetItem $item): JsonResponse
    {
        if ($item->attribute->group->event->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $item->delete();

        return $this->noContent('Budget item deleted');
    }

    // ── Notes ──

    public function storeNote(Request $request, EventAttribute $attribute): JsonResponse
    {
        if ($attribute->group->event->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        if ($attribute->type !== 'notes') {
            return $this->error('Attribute is not of type notes', 422);
        }

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $note = $attribute->notes()->create($validated);

        return $this->created(new EventNoteResource($note), 'Note created');
    }

    public function updateNote(Request $request, EventNote $note): JsonResponse
    {
        if ($note->attribute->group->event->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $note->update($validated);

        return $this->success(new EventNoteResource($note), 'Note updated');
    }

    public function destroyNote(Request $request, EventNote $note): JsonResponse
    {
        if ($note->attribute->group->event->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $note->delete();

        return $this->noContent('Note deleted');
    }

    // ── Todo Items ──

    public function storeTodoItem(Request $request, EventAttribute $attribute): JsonResponse
    {
        if ($attribute->group->event->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        if ($attribute->type !== 'todo') {
            return $this->error('Attribute is not of type todo', 422);
        }

        $validated = $request->validate([
            'title'      => 'required|string|max:255',
            'is_done'    => 'nullable|boolean',
            'due_date'   => 'nullable|date',
            'sort_order' => 'nullable|integer',
        ]);

        $item = $attribute->todoItems()->create([
            'title'      => $validated['title'],
            'is_done'    => $validated['is_done'] ?? false,
            'due_date'   => $validated['due_date'] ?? null,
            'sort_order' => $validated['sort_order'] ?? ($attribute->todoItems()->max('sort_order') + 1),
        ]);

        return $this->created(new EventTodoItemResource($item), 'Todo item created');
    }

    public function updateTodoItem(Request $request, EventTodoItem $item): JsonResponse
    {
        if ($item->attribute->group->event->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $validated = $request->validate([
            'title'      => 'sometimes|required|string|max:255',
            'is_done'    => 'nullable|boolean',
            'due_date'   => 'nullable|date',
            'sort_order' => 'nullable|integer',
        ]);

        $item->update($validated);

        return $this->success(new EventTodoItemResource($item), 'Todo item updated');
    }

    public function destroyTodoItem(Request $request, EventTodoItem $item): JsonResponse
    {
        if ($item->attribute->group->event->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $item->delete();

        return $this->noContent('Todo item deleted');
    }
}
