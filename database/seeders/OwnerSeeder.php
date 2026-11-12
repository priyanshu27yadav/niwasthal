<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OwnerSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'owner@lodgemate.com'],
            [
                'name'     => 'Raj Kumar',
                'mobile'   => '9876543210',
                'role'     => 'owner',
                'password' => Hash::make('password'),
            ]
        );
    }
}
