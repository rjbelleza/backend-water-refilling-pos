<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name' => 'Admin User',
            'pin' => '123456',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Staff User',
            'pin' => '654321',
            'password' => Hash::make('password'),
            'role' => 'staff',
        ]);
    }
}
