<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\User;
use Carbon\Carbon;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('role', 'admin')->first();

        $customers = [
            [
                'title' => 'Mr.',
                'name' => 'John Smith',
                'gender' => 'Male',
                'birthdate' => '1985-05-15',
                'phoneNo' => '0123456789',
                'email' => 'john.smith@example.com',
                'address' => '123 Main Street, Kuala Lumpur',
                'accStatus' => 'active',
                'cust_group' => 'retail',
                'preferredContactMethod' => 'email',
                'source' => 'website',
                'tag' => 'loyal',
                'remarks' => 'Regular customer',
                'lastInteractionDate' => Carbon::now()->subDays(5),
                'userID' => $user->id,
            ],
            [
                'title' => 'Ms.',
                'name' => 'Sarah Johnson',
                'gender' => 'Female',
                'birthdate' => '1990-08-20',
                'phoneNo' => '0187654321',
                'email' => 'sarah.j@example.com',
                'address' => '456 Park Avenue, Penang',
                'accStatus' => 'active',
                'cust_group' => 'premium',
                'preferredContactMethod' => 'phone',
                'source' => 'referral',
                'tag' => 'VIP',
                'remarks' => 'Prefers premium products',
                'lastInteractionDate' => Carbon::now()->subDays(2),
                'userID' => $user->id,
            ],
            [
                'title' => 'Dr.',
                'name' => 'Michael Wong',
                'gender' => 'Male',
                'birthdate' => '1975-11-30',
                'phoneNo' => '0193456789',
                'email' => 'dr.wong@example.com',
                'address' => '789 Hospital Road, Johor Bahru',
                'accStatus' => 'active',
                'cust_group' => 'wholesale',
                'preferredContactMethod' => 'email',
                'source' => 'exhibition',
                'tag' => 'business',
                'remarks' => 'Orders in bulk',
                'lastInteractionDate' => Carbon::now()->subDays(10),
                'userID' => $user->id,
            ],
        ];

        foreach ($customers as $customerData) {
            Customer::create($customerData);
        }
    }
} 