<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            WorkspacePermissionSeeder::class,
            WorkspaceRoleSeeder::class,
            UserSeeder::class,
            WorkspaceSeeder::class,
        ]
            
        );

        // Artisan::call('passport:client', [
        //     '--personal' => true,
        //     '--name' => 'Laravel Personal Access Client',
        //     '--no-interaction' => true,
        // ]);
    }
}
