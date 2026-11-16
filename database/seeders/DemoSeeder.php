<?php

namespace Database\Seeders;

use App\Models\TrainingSession;
use App\Models\TrainingSessionResponse;
use App\Models\User;
use App\Models\Workspace;
use App\Support\WorkspaceRoles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(WorkspaceRoleSeeder::class);

        $users = [];

        foreach ([
            'owner' => 'Owner demo',
            'coach' => 'Coach demo',
            'student' => 'Student demo',
            'viewer' => 'Viewer demo',
        ] as $role => $name) {
            $users[$role] = User::firstOrCreate(
                [
                    'email' => "{$role}@elometrics.test",
                ],
                [
                    'name' => $name,
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now(),
                ]
            );
        }

        $workspace = Workspace::firstOrCreate(
            [
                'user_id' => $users['owner']->id,
                'title' => 'Demo: grupo avanzado',
            ],
            [
                'status' => 'active',
            ]
        );

        foreach (['coach', 'student', 'viewer'] as $role) {
            $member = $users[$role];

            $workspace->members()
                ->syncWithoutDetaching([$member->id]);

            WorkspaceRoles::within(
                $workspace,
                $member,
                fn (User $user) => $user->syncRoles([$role])
            );
        }

        $endgameSession = TrainingSession::firstOrCreate(
            [
                'user_id' => $users['owner']->id,
                'title' => 'Finales de torres',
            ],
            [
                'description' => 'Actividad del rey, torre activa y peones pasados.',
                'category' => 'endgame',
                'resource' => 'https://lichess.org/study',
            ]
        );

        $calculationSession = TrainingSession::firstOrCreate(
            [
                'user_id' => $users['coach']->id,
                'title' => 'Cálculo: jugadas candidatas',
            ],
            [
                'description' => 'Compara las jugadas candidatas y explica las variantes principales.',
                'category' => 'tactic',
                'resource' => null,
            ]
        );

        $workspace->trainingSessions()->syncWithoutDetaching([
            $endgameSession->id,
            $calculationSession->id,
        ]);

        TrainingSessionResponse::firstOrCreate(
            [
                'workspace_id' => $workspace->id,
                'training_session_id' => $calculationSession->id,
                'user_id' => $users['student']->id,
            ],
            [
                'content' => 'Compararía primero los jaques, capturas y amenazas antes de elegir una variante.',
            ]
        );

        $this->command?->info("Workspace demo: {$workspace->id}");
        $this->command?->info("Sesión de finales: {$endgameSession->id}");
        $this->command?->info("Sesión de cálculo: {$calculationSession->id}");
    }
}