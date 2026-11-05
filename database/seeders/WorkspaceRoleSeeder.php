<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class WorkspaceRoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['owner', 'coach', 'student', 'viewer'] as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'api',
                'workspace_id' => null,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
