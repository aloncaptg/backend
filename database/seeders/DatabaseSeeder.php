<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'password' => \Illuminate\Support\Facades\Hash::make('Rinasasecurevps*5912'),
                'phone_number' => '081234567890',
                'role' => 'admin',
                'status' => true,
            ]
        );

        // Test Employee
        User::firstOrCreate(
            ['email' => 'employee@example.com'],
            [
                'name' => 'Karyawan Biasa',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'phone_number' => '081234567891',
                'role' => 'employee',
                'status' => true,
            ]
        );

        // Test Chef
        User::firstOrCreate(
            ['email' => 'chef@example.com'],
            [
                'name' => 'Chef Utama',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'phone_number' => '081234567892',
                'role' => 'chef',
                'status' => true,
            ]
        );

        // Test Driver (Kurir Pasar)
        User::firstOrCreate(
            ['email' => 'driver@example.com'],
            [
                'name' => 'Driver Pasar',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'phone_number' => '081234567893',
                'role' => 'driver',
                'status' => true,
            ]
        );

        // Test Courier (Kurir Pengiriman)
        User::firstOrCreate(
            ['email' => 'courier@example.com'],
            [
                'name' => 'Kurir Antar',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'phone_number' => '081234567894',
                'role' => 'courier',
                'status' => true,
            ]
        );
    }
}
