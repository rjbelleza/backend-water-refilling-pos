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
            'fname' => 'Sean',
            'lname' => 'Paterson',
            'username' => 'sean123',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Create staff user
        User::create([
            'fname' => 'Andrea',
            'lname' => 'North',
            'username' => 'andrea123',
            'password' => Hash::make('password'),
            'role' => 'staff',
        ]);
    }
}
