<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'profile_image' => $this->profile_image,
            'language' => $this->language,
            'theme' => $this->theme,
            'subscription_plan' => $this->subscription_plan ?? 'free',
            'is_pro' => $this->isPro(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
