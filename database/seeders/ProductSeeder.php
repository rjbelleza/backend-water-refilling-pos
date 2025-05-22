<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::create([
            'name' => '5-Gallon Round',
            'price' => 25.00,
            'track_stock' => false,
            'stock_quantity' => 0,
            'category_id' => 1,
            'user_id' => 1
        ]);

        Product::create([
            'name' => '2.5-Gallon',
            'price' => 10.00,
            'track_stock' => false,
            'stock_quantity' => 0,
            'category_id' => 1,
            'user_id' => 1
        ]);

        Product::create([
            'name' => '5-Gallon Round',
            'price' => 300.00,
            'track_stock' => true,
            'stock_quantity' => 100,
            'category_id' => 2,
            'user_id' => 1
        ]);

        Product::create([
            'name' => '2.5-Gallon',
            'price' => 189.00,
            'track_stock' => true,
            'stock_quantity' => 100,
            'category_id' => 2,
            'user_id' => 1
        ]);
    }
}
