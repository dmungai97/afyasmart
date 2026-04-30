<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::firstOrCreate(
            ['email' => 'admin@afyasmart.com'],
            [
                'name'              => 'Admin',
                'phone'             => '+254700000000',
                'email_verified_at' => now(),
                'password'          => Hash::make('password123'),
                'remember_token'    => \Illuminate\Support\Str::random(10),
            ]
        );

        // Seed all data
        $this->call([
            DoctorSeeder::class,
            DrugSeeder::class,
            PharmacySeeder::class,
        ]);
    }
}