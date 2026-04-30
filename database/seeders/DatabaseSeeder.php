<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Safe for production — no faker, no factory
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name'              => 'Test User',
                'phone'             => '+254700000000',
                'email_verified_at' => now(),
                'password'          => Hash::make('password'),
                'remember_token'    => \Illuminate\Support\Str::random(10),
            ]
        );
    }
}