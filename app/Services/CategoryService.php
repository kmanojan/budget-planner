<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    public function getAll(User $user, ?string $type = null): Collection
    {
        $query = Category::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('is_system', true);
        });

        if ($type) {
            $query->where('type', $type);
        }

        return $query->orderBy('sort_order')->orderBy('name')->get();
    }

    public function create(array $data, User $user): Category
    {
        $data['user_id'] = $user->id;
        $data['is_system'] = false;
        return Category::create($data);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);
        return $category->fresh();
    }

    public function delete(Category $category): void
    {
        if ($category->is_system) {
            throw new \Exception('Cannot delete system category');
        }
        $category->delete();
    }
}
