<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'robert@einsle.com'],
            [
                'name' => 'Robert Einsle',
                'email' => 'robert@einsle.com',
                'is_admin' => true,
                'is_active' => true,
            ]
        );
    }
}
