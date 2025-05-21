<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Visit;
use App\Models\Customer;
use App\Models\User;
use Carbon\Carbon;

class VisitSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::all();
        $user = User::where('role', 'admin')->first();
        
        // Only proceed if we have customers
        if ($customers->count() > 0) {
            $visitPurposes = ['Consultation', 'Product Demo', 'Complaint', 'Maintenance', 'Purchase'];
            $visitStatuses = ['scheduled', 'completed', 'canceled', 'no-show'];
            
            foreach ($customers as $customer) {
                // Each customer has 0-3 visits
                $visitCount = rand(0, 3);
                
                for ($i = 0; $i < $visitCount; $i++) {
                    // Some visits in the past, some in the future
                    $daysOffset = rand(-30, 30);
                    $visitDate = Carbon::now()->addDays($daysOffset);
                    
                    // Past visits are completed, future ones are scheduled
                    $status = $daysOffset < 0 ? 'completed' : 'scheduled';
                    
                    // Occasionally some past visits are canceled or no-show
                    if ($daysOffset < 0 && rand(1, 10) > 8) {
                        $status = rand(0, 1) ? 'canceled' : 'no-show';
                    }
                    
                    Visit::create([
                        'customer_id' => $customer->id,
                        'user_id' => $user->id,
                        'visit_date' => $visitDate,
                        'purpose' => $visitPurposes[array_rand($visitPurposes)],
                        'remarks' => $status === 'completed' ? 'Visit completed successfully' : null,
                        'status' => $status,
                    ]);
                }
            }
        }
    }
} 