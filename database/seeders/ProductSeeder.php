<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run()
    {
        Product::insert([
            [
                'name' => 'Mineral Water 500ml',
                'price' => 15.00,
                'stock_quantity' => 100,
                'unit' => 'bottle',
                'category_id' => 1,
                'user_id' => 1,
                'isActive' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Refill 5 Gallons',
                'price' => 30.00,
                'stock_quantity' => 50,
                'unit' => 'gallon',
                'category_id' => 2,
                'user_id' => 1,
                'isActive' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Alkaline Water 1 Liter',
                'price' => 20.00,
                'stock_quantity' => 80,
                'unit' => 'bottle',
                'category_id' => 3,
                'user_id' => 1,
                'isActive' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }
}
