<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // EXPENSE
            [
                'name' => 'Makanan & Minuman',
                'type' => 'expense',
                'icon' => 'restaurant',
                'color' => '#FF6B6B',
                'is_default' => true,
            ],
            [
                'name' => 'Transportasi',
                'type' => 'expense',
                'icon' => 'directions_car',
                'color' => '#4ECDC4',
                'is_default' => true,
            ],
            [
                'name' => 'Belanja',
                'type' => 'expense',
                'icon' => 'shopping_bag',
                'color' => '#FFE66D',
                'is_default' => true,
            ],
            [
                'name' => 'Hiburan',
                'type' => 'expense',
                'icon' => 'movie',
                'color' => '#A855F7',
                'is_default' => true,
            ],
            [
                'name' => 'Tagihan & Utilitas',
                'type' => 'expense',
                'icon' => 'receipt_long',
                'color' => '#F97316',
                'is_default' => true,
            ],
            [
                'name' => 'Kesehatan',
                'type' => 'expense',
                'icon' => 'local_hospital',
                'color' => '#EF4444',
                'is_default' => true,
            ],
            [
                'name' => 'Pendidikan',
                'type' => 'expense',
                'icon' => 'school',
                'color' => '#3B82F6',
                'is_default' => true,
            ],
            [
                'name' => 'Lainnya',
                'type' => 'expense',
                'icon' => 'more_horiz',
                'color' => '#6B7280',
                'is_default' => true,
            ],

            // INCOME
            [
                'name' => 'Gaji',
                'type' => 'income',
                'icon' => 'payments',
                'color' => '#10B981',
                'is_default' => true,
            ],
            [
                'name' => 'Freelance',
                'type' => 'income',
                'icon' => 'work',
                'color' => '#06B6D4',
                'is_default' => true,
            ],
            [
                'name' => 'Investasi',
                'type' => 'income',
                'icon' => 'trending_up',
                'color' => '#8B5CF6',
                'is_default' => true,
            ],
            [
                'name' => 'Hadiah',
                'type' => 'income',
                'icon' => 'card_giftcard',
                'color' => '#EC4899',
                'is_default' => true,
            ],
            [
                'name' => 'Lainnya',
                'type' => 'income',
                'icon' => 'more_horiz',
                'color' => '#6B7280',
                'is_default' => true,
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                [
                    'name' => $category['name'],
                    'type' => $category['type'],
                    'is_default' => true,
                ],
                $category
            );
        }
    }
}
