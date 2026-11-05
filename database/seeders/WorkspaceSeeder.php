<?php

namespace Database\Seeders;

use App\Models\Workspace;
use App\Models\User;
use Illuminate\Database\Seeder;

class WorkspaceSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $emergencyUser = User::create([
                'name' => 'Usuario Emergencia',
                'email' => 'emergencia@example.com',
                'password' => bcrypt('password123'),
            ]);
            $userId = $emergencyUser->id;
        } else {
            $userId = $users->random()->id;
        }

        Workspace::create([
            'title'   => 'Club de Ajedrez Central',
            'status'  => 'active',
            'user_id' => $userId,
        ]);

        Workspace::create([
            'title'   => 'Entrenamiento Alto Rendimiento',
            'status'  => 'active',
            'user_id' => $userId,
        ]);
    }
}
