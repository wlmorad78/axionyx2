<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Console\Command;

class GrantAllPermissions extends Command
{
    protected $signature = 'user:grant-all {userId}';
    protected $description = 'Grant a user full admin access by assigning the Admin user type';

    public function handle()
    {
        $userId = $this->argument('userId');

        $user = User::find($userId) ?? User::where('usercode', $userId)->first();
        if (!$user) {
            $this->error("User {$userId} not found (tried both ID and usercode).");
            return 1;
        }

        $this->info("User: {$user->name} (ID: {$user->id}, Usercode: {$user->usercode})");

        // Find or create Admin user type (global)
        $adminType = UserType::where('company_id', $user->company_id)
            ->where('name_ar', 'Admin')
            ->first();

        if (!$adminType) {
            $adminType = UserType::whereNull('company_id')
                ->where('name_ar', 'Admin')
                ->first();
        }

        if (!$adminType) {
            $adminType = UserType::create([
                'company_id' => $user->company_id,
                'code' => 'admin',
                'name_ar' => 'Admin',
                'name_en' => 'Admin',
                'description' => 'Full admin access to all modules and screens',
                'is_active' => true,
                'is_protected' => true,
            ]);
            $this->info('Admin user type created.');
        }

        $user->update(['user_type_id' => $adminType->id]);
        $this->newLine();
        $this->info("Done! User {$user->name} now has the Admin user type (full access).");

        return 0;
    }
}
