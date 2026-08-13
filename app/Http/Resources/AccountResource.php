<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'type' => $this->type->value,
            'currency_code' => $this->currency_code,
            'balance' => $this->balance,
            'color' => $this->color,
            'icon' => $this->icon,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'shared_users' => $this->whenLoaded('sharedUsers', function () {
                return $this->sharedUsers->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->pivot->role,
                ]);
            }),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
