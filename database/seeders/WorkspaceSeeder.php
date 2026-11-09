<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class WorkspaceSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('email', 'pepe@mail.com')
            ->firstOrFail();

        foreach ([
            'Club de Ajedrez Central',
            'Entrenamiento Alto Rendimiento',
        ] as $title) {
            Workspace::firstOrCreate(
                [
                    'user_id' => $owner->id,
                    'title' => $title,
                ],
                [
                    'status' => 'active',
                ]
            );
        }
    }
}