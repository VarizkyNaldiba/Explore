<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@wallet.com'],
            [
                'name' => 'Administrator',
                'password' => bcrypt('admin123'),
            ]
        );
    }
}

