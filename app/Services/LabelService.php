<?php

namespace App\Services;

use App\Models\Label;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class LabelService
{
    public function getAll(User $user): Collection
    {
        return Label::where('user_id', $user->id)
            ->orderBy('is_pinned', 'desc')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function create(array $data, User $user): Label
    {
        $data['user_id'] = $user->id;
        if (isset($data['hex_color'])) {
            $data['color'] = $data['hex_color'];
        }
        return Label::create($data);
    }

    public function update(Label $label, array $data): Label
    {
        if (isset($data['hex_color'])) {
            $data['color'] = $data['hex_color'];
        }
        $label->update($data);
        return $label->fresh();
    }

    public function delete(Label $label): void
    {
        $label->delete();
    }

    public function togglePin(Label $label): Label
    {
        $label->update(['is_pinned' => !$label->is_pinned]);
        return $label->fresh();
    }
}
