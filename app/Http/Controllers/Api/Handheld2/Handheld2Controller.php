<?php

namespace App\Http\Controllers\Api\Handheld2;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\Route;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class Handheld2Controller extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'usercode' => 'required|numeric',
            'password' => 'required',
        ]);

        $user = User::where('usercode', $request->usercode)->first();

        if (! $user || ! \Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'usercode' => ['اسم المستخدم أو الباسورد غير صحيح.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'usercode' => ['الحساب غير نشط.'],
            ]);
        }

        // Create token
        $token = null;
        $lastException = null;
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $user->tokens()->delete();
                $token = $user->createToken('handheld2-token')->plainTextToken;
                break;
            } catch (\Exception $e) {
                $lastException = $e;
                if ($attempt < 3) {
                    usleep(500000);
                    continue;
                }
            }
        }

        if (!$token) {
            throw $lastException ?? new \Exception('حدث خطأ في إنشاء التوكن.');
        }

        $company = $user->company;
        $branch = $user->branch ?? null;
        $warehouse = $branch?->warehouses?->first() ?? null;

        // Get sales territory from the user's branch or company
        $salesArea = null;
        if ($branch && $branch->sales_territory_id) {
            $salesArea = $branch->sales_territory?->name_ar;
        }

        // Count routes assigned to this user/branch
        $routesCount = 0;
        if ($branch) {
            $routesCount = Route::where('branch_id', $branch->id)->where('is_active', true)->count();
        } elseif ($company && $company->id !== null) {
            $routesCount = Route::where('company_id', $company->id)->where('is_active', true)->count();
        }

        // Count customers for this company
        $customersCount = 0;
        if ($company) {
            $customersCount = Customer::where('company_id', $company->id)->where('is_active', true)->count();
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'usercode' => $user->usercode,
                'name' => $user->name,
            ],
            'company' => $company ? [
                'id' => $company->id,
                'code' => $company->code,
                'name_ar' => $company->name_ar,
                'name_en' => $company->name_en,
            ] : null,
            'branch' => $branch ? [
                'id' => $branch->id,
                'code' => $branch->code ?? '',
                'name_ar' => $branch->name_ar,
                'name_en' => $branch->name_en,
            ] : null,
            'warehouse' => $warehouse ? [
                'id' => $warehouse->id,
                'code' => $warehouse->code ?? '',
                'name_ar' => $warehouse->name_ar,
                'name_en' => $warehouse->name_en,
            ] : null,
            'sales_area' => $salesArea,
            'routes_count' => $routesCount,
            'customers_count' => $customersCount,
            'token' => $token,
        ]);
    }
}