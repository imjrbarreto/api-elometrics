<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Pepe',
            'email' => 'pepe@mail.com',
            'password' => Hash::make('hola1234'),
            'email_verified_at' => now(),
        ]);

        User::factory()->count(5)->create();
    }
}
