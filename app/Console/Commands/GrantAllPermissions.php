<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Console\Command;

class GrantAllPermissions extends Command
{
    protected $signature = 'user:grant-all {userId}';
    protected $description = 'Grant a user full admin access to all screens by assigning the Admin role';

    public function handle()
    {
        $userId = $this->argument('userId');

        // Try by ID first, then by usercode
        $user = User::find($userId);
        if (!$user) {
            $user = User::where('usercode', $userId)->first();
        }
        if (!$user) {
            $this->error("User {$userId} not found (tried both ID and usercode).");
            return 1;
        }

        $this->info("User: {$user->name} (ID: {$user->id}, Usercode: {$user->usercode})");

        // Find or create Admin role
        $adminRole = Role::where('name', 'Admin')->first();
        if (!$adminRole) {
            $this->warn('Admin role not found. Creating it...');
            $adminRole = Role::create([
                'name' => 'Admin',
                'code' => 'admin',
                'description' => 'Full admin access to all modules and screens',
                'is_global' => true,
                'is_system' => true,
            ]);
            $this->info('Admin role created.');
        }

        // Assign Admin role to user
        $hasRole = $user->roles()->where('role_id', $adminRole->id)->exists();
        if ($hasRole) {
            $this->info("User already has the Admin role.");
        } else {
            $user->roles()->attach($adminRole);
            $this->info('Admin role assigned to user.');
        }

        // Also sync ALL permissions to the Admin role (belt + suspenders)
        $allPermissions = Permission::pluck('id')->toArray();
        if (!empty($allPermissions)) {
            $adminRole->permissions()->syncWithoutDetaching($allPermissions);
            $this->info("Synced " . count($allPermissions) . " permissions to Admin role.");
        }

        // Add wildcard permission too
        $wildcardPerm = Permission::where('code', '*')->first();
        if (!$wildcardPerm) {
            $wildcardPerm = Permission::create(['code' => '*', 'name' => 'All Permissions (Wildcard)']);
            $this->info('Wildcard permission created.');
        }
        if (!$adminRole->permissions()->where('permission_id', $wildcardPerm->id)->exists()) {
            $adminRole->permissions()->attach($wildcardPerm);
        }

        $this->newLine();
        $this->info("Done! User {$user->name} (ID: {$userId}) now has full access to all screens.");
        $this->info("The user will see the complete sidebar menu on next login.");

        return 0;
    }
}
