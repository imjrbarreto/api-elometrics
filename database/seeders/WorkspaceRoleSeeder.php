<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class WorkspaceRoleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(WorkspacePermissionSeeder::class);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['owner', 'coach', 'student', 'viewer'] as $roleName) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'api',
                'workspace_id' => null,
            ]);

            if ($roleName === 'owner') {
                $role->syncPermissions([
                    'members.view',
                    'members.add',
                    'members.remove',
                ]);
            }

            if ($roleName === 'student') {
                $role->syncPermissions([
                    'members.view',
                    'training-sessions.respond',
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}