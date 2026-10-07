<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder for the real deployment at Radiotel.
 *
 * Creates ONLY the Operational Manager (admin) account. No sample suppliers,
 * products, or customers are added; the company enters its real records
 * through the system's Suppliers, Inventory, and Customers pages.
 *
 * Usage:  php artisan db:seed --class=ProductionSeeder
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        // Never create a second admin, and never touch existing data
        if (User::where('role', 'admin')->exists()) {
            $this->command->warn('An admin account already exists. Nothing was changed.');
            return;
        }

        // The first password comes from .env, so it is never written in the code on GitHub
        $password = env('ADMIN_INITIAL_PASSWORD');

        if (! $password || strlen($password) < 8) {
            $this->command->error('Set ADMIN_INITIAL_PASSWORD in .env (at least 8 characters), then run this again.');
            return;
        }

        User::create([
            'name'                 => 'Edwin Belgado',
            'email'                => 'ebelgado@radiotel.ph',
            'password'             => $password,  // hashed automatically by the User model
            'role'                 => 'admin',
            'status'               => 'active',
            'must_change_password' => true,       // must set a new password on first login
        ]);

        $this->command->info('Admin account created: ebelgado@radiotel.ph');
        $this->command->info('Remove ADMIN_INITIAL_PASSWORD from .env now. The password must be changed on first login.');
    }
}