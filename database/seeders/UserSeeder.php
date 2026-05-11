<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 3 Admin Users
        for ($i = 1; $i <= 3; $i++) {
            User::updateOrCreate(
                ['email' => "admin{$i}@example.com"],
                [
                    'name' => "Admin User {$i}",
                    'password' => 'secret1234',
                    'role' => 'admin',
                ]
            );
        }

        // 3 Helpdesk Agent Users
        for ($i = 1; $i <= 3; $i++) {
            User::updateOrCreate(
                ['email' => "agent{$i}@example.com"],
                [
                    'name' => "Helpdesk Agent {$i}",
                    'password' => 'secret1234',
                    'role' => 'agent',
                ]
            );
        }


    }
}
