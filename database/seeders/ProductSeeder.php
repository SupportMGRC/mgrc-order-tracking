<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'MSC 50M',
                'description' => 'Mesenchymal Stem Cells 50 Million',
                'price' => 0.00,
                'stock' => 99999,
            ],
            [
                'name' => 'MSC 100M',
                'description' => 'Mesenchymal Stem Cells 100 Million',
                'price' => 0.00,
                'stock' => 99999,
            ],
            [
                'name' => 'MSC 150M',
                'description' => 'Mesenchymal Stem Cells 150 Million',
                'price' => 0.00,
                'stock' => 99999,
            ],
            [
                'name' => 'MSC 200M',
                'description' => 'Mesenchymal Stem Cells 200 Million',
                'price' => 0.00,
                'stock' => 99999,
            ],
            [
                'name' => 'NK Cells',
                'description' => 'Natural Killer Cells treatment',
                'price' => 0.00,
                'stock' => 99999,
            ],
            [
                'name' => 'NKT Cells',
                'description' => 'Natural Killer T Cells treatment',
                'price' => 0.00,
                'stock' => 99999,
            ],
            [
                'name' => 'CAR-T Cells',
                'description' => 'CAR-T cell therapy for cancer treatment',
                'price' => 0.00,
                'stock' => 99999,
            ],
            [
                'name' => 'Exosome',
                'description' => 'Exosome therapy for regenerative medicine',
                'price' => 0.00,
                'stock' => 99999,
            ],
            [
                'name' => 'Secrotome',
                'description' => 'Secrotome therapy for tissue regeneration',
                'price' => 0.00,
                'stock' => 99999,
            ],
            [
                'name' => 'RejuvaNAD+',
                'description' => 'NAD+ therapy for anti-aging and cellular health',
                'price' => 0.00,
                'stock' => 99999,
            ],
        ];

        foreach ($products as $productData) {
            Product::updateOrCreate(
                ['name' => $productData['name']],
                $productData
            );
        }
    }
} 