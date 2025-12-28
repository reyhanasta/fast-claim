<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed app settings first
        $this->call(AppSettingsSeeder::class);

        // User::factory(10)->create();

        // Admin user
        User::factory()->admin()->create([
            'name' => 'Asta',
            'email' => 'astareyhan@gmail.com',
            'password' => 'admin1234',
        ]);
        // Admin user
        User::factory()->admin()->create([
            'name' => 'Admin Dummy',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        // Operator user (example)
        User::factory()->operator()->create([
            'name' => 'Operator',
            'email' => 'operator@kubr.local',
            'password' => 'operator123',
        ]);

        // Operator user (example)
        User::factory()->operator()->create([
            'name' => 'Syafrul Andri',
            'email' => 'syafrulandri@gmail.com',
            'password' => 'Almonda70@#',
        ]);
    }
}
