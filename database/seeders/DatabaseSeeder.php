<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Database seeder for CyraAgroLink baseline data.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@cyraagrolink.com'],
            [
                'name' => 'System Administrator',
                'phone' => '+233000000001',
                'password' => Hash::make('Password@123'),
                'role' => UserRole::Admin,
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'farmer@cyraagrolink.com'],
            [
                'name' => 'Demo Farmer',
                'phone' => '+233000000002',
                'password' => Hash::make('Password@123'),
                'role' => UserRole::Farmer,
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ]
        );

        $investor = User::query()->updateOrCreate(
            ['email' => 'investor@cyraagrolink.com'],
            [
                'name' => 'Demo Investor',
                'phone' => '+233000000003',
                'password' => Hash::make('Password@123'),
                'role' => UserRole::Investor,
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ]
        );

        Wallet::query()->updateOrCreate(
            ['user_id' => $investor->id],
            [
                'balance' => 2_500_000,
                'currency' => 'NGN',
            ]
        );
    }
}
