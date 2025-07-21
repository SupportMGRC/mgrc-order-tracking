<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BlockedDate;
use App\Models\User;
use Carbon\Carbon;

class BlockedDateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first admin user, or create a default user ID
        $adminUser = User::where('role', 'admin')->orWhere('role', 'superadmin')->first();
        $createdBy = $adminUser ? $adminUser->id : 1;

        // Sample blocked dates
        $blockedDates = [
            [
                'blocked_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
                'reason' => 'System Maintenance',
                'type' => 'maintenance',
                'is_active' => true,
                'created_by' => $createdBy,
            ],
            [
                'blocked_date' => Carbon::now()->addDays(14)->format('Y-m-d'),
                'reason' => 'Public Holiday - Independence Day',
                'type' => 'holiday',
                'is_active' => true,
                'created_by' => $createdBy,
            ],
            [
                'blocked_date' => Carbon::now()->addDays(21)->format('Y-m-d'),
                'reason' => 'Office Closure',
                'type' => 'closure',
                'is_active' => true,
                'created_by' => $createdBy,
            ],
        ];

        foreach ($blockedDates as $blockedDate) {
            // Only create if the date doesn't already exist
            BlockedDate::firstOrCreate(
                ['blocked_date' => $blockedDate['blocked_date']],
                $blockedDate
            );
        }
    }
} 