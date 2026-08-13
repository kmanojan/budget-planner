<?php

namespace App\Observers;

use App\Models\EventBudgetItem;

class EventBudgetItemObserver
{
    public function saved(EventBudgetItem $item): void
    {
        $this->recalculate($item);
    }

    public function deleted(EventBudgetItem $item): void
    {
        $this->recalculate($item);
    }

    private function recalculate(EventBudgetItem $item): void
    {
        $attribute = $item->attribute;
        if (!$attribute) return;
        $group = $attribute->group;
        if (!$group) return;
        $event = $group->event;
        if (!$event) return;

        $event->recalculateTotals();
    }
}
