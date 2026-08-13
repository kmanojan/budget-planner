<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'account' => $this->whenLoaded('account', fn () => [
                'id' => $this->account->id,
                'name' => $this->account->name,
                'type' => $this->account->type->value,
            ]),
            'sender' => $this->whenLoaded('sender', fn () => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'email' => $this->sender->email,
            ]),
            'receiver' => $this->whenLoaded('receiver', fn () => [
                'id' => $this->receiver->id,
                'name' => $this->receiver->name,
                'email' => $this->receiver->email,
            ]),
            'role' => $this->role,
            'status' => $this->status->value,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
