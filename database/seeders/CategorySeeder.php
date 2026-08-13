<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // Expense categories
            ['name' => 'Food & Dining', 'type' => 'expense', 'icon' => 'restaurant', 'color' => '#E74C3C', 'is_system' => true, 'sort_order' => 1],
            ['name' => 'Shopping', 'type' => 'expense', 'icon' => 'shopping_bag', 'color' => '#6C5CE7', 'is_system' => true, 'sort_order' => 2],
            ['name' => 'Transport', 'type' => 'expense', 'icon' => 'directions_car', 'color' => '#0984E3', 'is_system' => true, 'sort_order' => 3],
            ['name' => 'Entertainment', 'type' => 'expense', 'icon' => 'movie', 'color' => '#FDCB6E', 'is_system' => true, 'sort_order' => 4],
            ['name' => 'Bills & Utilities', 'type' => 'expense', 'icon' => 'receipt_long', 'color' => '#00CEC9', 'is_system' => true, 'sort_order' => 5],
            ['name' => 'Health & Fitness', 'type' => 'expense', 'icon' => 'fitness_center', 'color' => '#E17055', 'is_system' => true, 'sort_order' => 6],
            ['name' => 'Education', 'type' => 'expense', 'icon' => 'school', 'color' => '#A29BFE', 'is_system' => true, 'sort_order' => 7],
            ['name' => 'Personal Care', 'type' => 'expense', 'icon' => 'spa', 'color' => '#FD79A8', 'is_system' => true, 'sort_order' => 8],
            ['name' => 'Groceries', 'type' => 'expense', 'icon' => 'shopping_cart', 'color' => '#00B894', 'is_system' => true, 'sort_order' => 9],
            ['name' => 'Rent', 'type' => 'expense', 'icon' => 'home', 'color' => '#636E72', 'is_system' => true, 'sort_order' => 10],
            ['name' => 'Insurance', 'type' => 'expense', 'icon' => 'security', 'color' => '#2D3436', 'is_system' => true, 'sort_order' => 11],
            ['name' => 'Travel', 'type' => 'expense', 'icon' => 'flight', 'color' => '#74B9FF', 'is_system' => true, 'sort_order' => 12],
            ['name' => 'Other Expense', 'type' => 'expense', 'icon' => 'more_horiz', 'color' => '#B2BEC3', 'is_system' => true, 'sort_order' => 99],

            // Income categories
            ['name' => 'Salary', 'type' => 'income', 'icon' => 'payments', 'color' => '#00B894', 'is_system' => true, 'sort_order' => 1],
            ['name' => 'Freelance', 'type' => 'income', 'icon' => 'laptop', 'color' => '#6C5CE7', 'is_system' => true, 'sort_order' => 2],
            ['name' => 'Investment', 'type' => 'income', 'icon' => 'trending_up', 'color' => '#0984E3', 'is_system' => true, 'sort_order' => 3],
            ['name' => 'Business', 'type' => 'income', 'icon' => 'business', 'color' => '#FDCB6E', 'is_system' => true, 'sort_order' => 4],
            ['name' => 'Gifts', 'type' => 'income', 'icon' => 'card_giftcard', 'color' => '#E17055', 'is_system' => true, 'sort_order' => 5],
            ['name' => 'Other Income', 'type' => 'income', 'icon' => 'more_horiz', 'color' => '#B2BEC3', 'is_system' => true, 'sort_order' => 99],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name'], 'is_system' => true],
                $category
            );
        }
    }
}
