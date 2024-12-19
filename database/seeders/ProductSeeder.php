<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Product::insert([
            [
                'name' => 'Laptop',
                'price' => 1200.00,
                'description' => 'High-end gaming laptop',
                'image' => 'storage/images/laptop.jpg',
                'quantity' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Smartphone',
                'price' => 800.00,
                'description' => 'Latest smartphone with powerful features',
                'image' => 'storage/images/phone.jpg',
                'quantity' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Headphones',
                'price' => 150.00,
                'description' => 'Noise-cancelling headphones',
                'image' => 'storage/images/headphones.jpg',
                'quantity' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Watch',
                'price' => 200.00,
                'description' => 'Stylish smartwatch with health tracking',
                'image' => 'storage/images/watch.jpg',
                'quantity' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}


