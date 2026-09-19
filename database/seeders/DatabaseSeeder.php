<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Superadmin User
        User::updateOrCreate(
            ['email' => 'superadmin@system.com'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('password123'),
                'role'     => 'superadmin',
            ]
        );

        // 2. Accounting User
        User::updateOrCreate(
            ['email' => 'accounting@system.com'],
            [
                'name'     => 'Accounting Staff',
                'password' => Hash::make('password123'),
                'role'     => 'accounting',
            ]
        );

        // 3. Encoder User
        User::updateOrCreate(
            ['email' => 'encoder@system.com'],
            [
                'name'     => 'Data Encoder',
                'password' => Hash::make('password123'),
                'role'     => 'encoder',
            ]
        );
    }
}