<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        // Create admin user
        User::create([
            'name' => 'Sean	Paterson',
            'username' => 'admin',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Create staff user
        User::create([
            'name' => 'Andrea North',
            'username' => 'staff',
            'password' => Hash::make('password'),
            'role' => 'staff',
        ]);
    }
}
