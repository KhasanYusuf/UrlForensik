<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin User
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@forensik.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

        // Create Regular User
        User::create([
            'name' => 'User Demo',
            'email' => 'user@forensik.com',
            'password' => Hash::make('user123'),
            'role' => 'user',
        ]);
    }
}
