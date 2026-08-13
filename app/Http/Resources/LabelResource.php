<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'hex_color' => $this->color,
            'is_pinned' => (bool) $this->is_pinned,
            'sort_order' => $this->sort_order,
        ];
    }
}
