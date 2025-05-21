<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'username' => 'superadmin',
            'email' => 'superadmin@mgrc.com.my',
            'password' => Hash::make('password'),
            'role' => 'superadmin',
            'department' => 'IT',
            'designation' => 'System Administrator',
            'receive_new_order_emails' => false,
            'receive_order_ready_emails' => false,
        ]);

        User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'username' => 'adminuser',
            'email' => 'admin@mgrc.com.my',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'department' => 'HR',
            'designation' => 'HR Manager',
            'receive_new_order_emails' => false,
            'receive_order_ready_emails' => false,
        ]);

        User::create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'username' => 'normaluser',
            'email' => 'user@mgrc.com.my',
            'password' => Hash::make('password'),
            'role' => 'user',
            'department' => 'Marketing',
            'designation' => 'Marketing Executive',
            'receive_new_order_emails' => false,
            'receive_order_ready_emails' => false,
        ]);

        // Admin & Human Resource Department
        User::create([
            'first_name' => 'Zainina',
            'last_name' => 'Iwani',
            'username' => 'Zainina',
            'email' => 'zainina@mgrc.com.my',
            'password' => Hash::make('password'),
            'role' => 'user',
            'department' => 'Admin & Human Resource',
            'designation' => 'HR Specialist',
            'receive_new_order_emails' => false,
            'receive_order_ready_emails' => false,
        ]);

        // Cell Lab Department
        User::create([
            'first_name' => 'Siti',
            'last_name' => 'Zulaiha',
            'username' => 'Siti',
            'email' => 'siti@mgrc.com.my',
            'password' => Hash::make('password'),
            'role' => 'user',
            'department' => 'Cell Lab',
            'designation' => 'Lab Technician',
            'receive_new_order_emails' => false,
            'receive_order_ready_emails' => false,
        ]);

        // Medical Affairs Department
        User::create([
            'first_name' => 'Akma',
            'last_name' => 'Ismail',
            'username' => 'Akma',
            'email' => 'akma@mgrc.com.my',
            'password' => Hash::make('password'),
            'role' => 'user',
            'department' => 'Medical Affairs',
            'designation' => 'Medical Officer',
            'receive_new_order_emails' => false,
            'receive_order_ready_emails' => false,
        ]);

        // Quality Department
        User::create([
            'first_name' => 'Nur',
            'last_name' => 'karim',
            'username' => 'Nur',
            'email' => 'nur@mgrc.com.my',
            'password' => Hash::make('password'),
            'role' => 'user',
            'department' => 'Quality',
            'designation' => 'Quality Assurance Specialist',
            'receive_new_order_emails' => false,
            'receive_order_ready_emails' => false,
        ]);

        // Finance Department
        User::create([
            'first_name' => 'Afiq',
            'last_name' => 'Ismail',
            'username' => 'Afiq',
            'email' => 'afiq@mgrc.com.my',
            'password' => Hash::make('password'),
            'role' => 'user',
            'department' => 'Finance',
            'designation' => 'Financial Analyst',
            'receive_new_order_emails' => false,
            'receive_order_ready_emails' => false,
        ]);

        // Management Department
        User::create([
            'first_name' => 'Yi',
            'last_name' => 'Bin',
            'username' => 'Yi Bin',
            'email' => 'yibin@mgrc.com.my',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'department' => 'Management',
            'designation' => 'Operations Manager',
            'receive_new_order_emails' => false,
            'receive_order_ready_emails' => false,
        ]);

        // Software Department
        User::create([
            'first_name' => 'Yap',
            'last_name' => 'Xin Yi',
            'username' => 'Cindy',
            'email' => 'cindy@mgrc.com.my',
            'password' => Hash::make('password'),
            'role' => 'user',
            'department' => 'Software',
            'designation' => 'Software Developer',
            'receive_new_order_emails' => false,
            'receive_order_ready_emails' => false,
        ]);
    }
}
