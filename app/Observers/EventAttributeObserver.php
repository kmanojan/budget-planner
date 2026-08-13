<?php

namespace App\Observers;

use App\Models\EventAttribute;

class EventAttributeObserver
{
    public function saved(EventAttribute $attribute): void
    {
        $this->recalculate($attribute);
    }

    public function deleted(EventAttribute $attribute): void
    {
        $this->recalculate($attribute);
    }

    private function recalculate(EventAttribute $attribute): void
    {
        $group = $attribute->group;
        if (!$group) return;
        $event = $group->event;
        if (!$event) return;

        $event->recalculateTotals();
    }
}
