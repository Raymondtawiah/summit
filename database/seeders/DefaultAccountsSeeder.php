<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultAccountsSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@ldssummitpass.com'],
            [
                'name' => 'Administrator',
                'email' => 'admin@ldssummitpass.com',
                'password' => Hash::make('ChangeMe@2026!'),
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'staff@ldssummitpass.com'],
            [
                'name' => 'Summit Staff',
                'email' => 'staff@ldssummitpass.com',
                'password' => Hash::make('ChangeMe@2026!'),
                'role' => 'staff',
            ]
        );
    }
}
