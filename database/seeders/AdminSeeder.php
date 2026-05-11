<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::create([
            'name' => 'Admin',
            'email' => 'admin@sajhaauction.com',
            'phone' => '9761834027',
            'role' => 'admin',
            'position' => 'admin',
            'gender' => 'MALE',
            'date_of_joining' => now(),
            'password' => Hash::make('password'),
            'address' => 'Kathmandu',
            'status' => 'active',
        ]);
    }
}
