<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderProductSeeder extends Seeder
{
    public function run(): void
    {
        $orders = Order::all();
        $products = Product::all();
        
        // Only proceed if we have orders and products
        if ($orders->count() > 0 && $products->count() > 0) {
            foreach ($orders as $order) {
                // Each order has between 1 and 3 different products
                $orderProductCount = rand(1, 3);
                
                // Get random products for this order
                $orderProducts = $products->random($orderProductCount);
                
                foreach ($orderProducts as $product) {
                    $quantity = rand(1, 5);
                    $batchNumber = 'B' . str_pad(rand(1, 1000), 4, '0', STR_PAD_LEFT);
                    $patientNames = ['John Doe', 'Jane Smith', 'Robert Johnson', 'Maria Garcia', null];
                    $patientName = $patientNames[array_rand($patientNames)];
                    $remarks = rand(0, 1) ? 'Sample remarks for this product' : null;
                    $qcDocumentNumber = 'QC' . str_pad(rand(1, 500), 4, '0', STR_PAD_LEFT);
                    $preparedByNames = ['Dr. Williams', 'Dr. Chen', 'Nurse Johnson', 'Lab Tech Smith', null];
                    $preparedBy = $preparedByNames[array_rand($preparedByNames)];
                    
                    // Add the product to the order
                    DB::table('order_product')->insert([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'batch_number' => $batchNumber,
                        'patient_name' => $patientName,
                        'remarks' => $remarks,
                        'qc_document_number' => $qcDocumentNumber,
                        'prepared_by' => $preparedBy,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
} 