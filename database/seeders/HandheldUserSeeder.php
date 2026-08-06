<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class HandheldUserSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();
        $user = null;

        foreach ($companies as $company) {
            $user = User::updateOrCreate(
                ['usercode' => 99999],
                [
                    'name' => 'Handheld Device',
                    'password' => Hash::make('1233456'),
                    'is_active' => true,
                    'company_id' => $company->id,
                ]
            );
        }

        if ($user) {
            $companyIds = $companies->pluck('id')->toArray();
            $user->companies()->syncWithoutDetaching($companyIds);

            $firstBranch = \App\Models\Branch::where('company_id', $user->company_id)
                ->where('is_active', true)
                ->first();

            if ($firstBranch) {
                $user->branches()->syncWithoutDetaching([
                    $firstBranch->id => ['is_default' => true],
                ]);
            }
        }
    }
}
