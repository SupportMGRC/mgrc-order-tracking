<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\Customer;
use App\Models\User;
use Carbon\Carbon;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::all();
        $user = User::where('role', 'admin')->first();
        
        // Only proceed if we have customers
        if ($customers->count() > 0) {
            $statuses = ['new', 'preparing', 'ready', 'delivered', 'cancel'];
            
            // Create a few orders for each customer
            foreach ($customers as $customer) {
                $orderCount = rand(1, 3); // Random number of orders per customer
                
                for ($i = 0; $i < $orderCount; $i++) {
                    $date = Carbon::now()->subDays(rand(1, 30));
                    $delivery = Carbon::now()->addDays(rand(1, 14));
                    $status = $statuses[array_rand($statuses)];
                    
                    // Only delivered orders have a delivered_by value
                    $deliveredBy = null;
                    if ($status === 'delivered') {
                        $deliveredBy = $this->getRandomPersonName();
                    }

                    // Randomly assign delivery type
                    $deliveryType = rand(0, 1) === 1 ? 'delivery' : 'self_collect';
                    
                    Order::create([
                        'customer_id' => $customer->id,
                        'user_id' => $user->id,
                        'order_placed_by' => $this->getRandomPersonName(),
                        'delivered_by' => $deliveredBy,
                        'order_date' => $date->toDateString(),
                        'order_time' => $date->format('H:i:s'),
                        'status' => $status,
                        'delivery_type' => $deliveryType,
                        'pickup_delivery_date' => $delivery->toDateString(),
                        'pickup_delivery_time' => $delivery->format('H:i:s'),
                        'remarks' => $this->getRandomRemark(),
                    ]);
                }
            }
        }
    }
    
    /**
     * Get a random remark for orders
     */
    private function getRandomRemark(): string
    {
        $remarks = [
            'Please deliver during office hours',
            'Contact customer before delivery',
            'Handle with care',
            'Priority delivery',
            'Leave at the door if no one answers',
            'Call on arrival',
            '',
        ];
        
        return $remarks[array_rand($remarks)];
    }
    
    /**
     * Get a random person name for order placement
     */
    private function getRandomPersonName(): string
    {
        $names = [
            'John Smith',
            'Sarah Johnson',
            'Michael Brown',
            'Emma Davis',
            'James Wilson',
            'Jessica Taylor',
            'Daniel Lee',
            'Olivia Martin',
            'William Thompson',
            'Sophia Anderson',
        ];
        
        return $names[array_rand($names)];
    }
} 