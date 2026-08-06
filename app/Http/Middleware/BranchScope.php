<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class BranchScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            // 1. من الهيدر
            $headerBranchId = $request->header('X-Branch-Id');

            if ($headerBranchId && is_numeric($headerBranchId)) {
                $request->merge(['branch_id' => (int) $headerBranchId]);
            } elseif (!$request->filled('branch_id')) {
                // 2. من الفرع الافتراضي للمستخدم
                $defaultBranch = DB::table('user_branches')
                    ->where('user_id', $user->id)
                    ->where('is_default', true)
                    ->first();

                if ($defaultBranch) {
                    $request->merge(['branch_id' => (int) $defaultBranch->branch_id]);
                } else {
                    // 3. أول فرع متاح للمستخدم
                    $firstBranch = DB::table('user_branches')
                        ->where('user_id', $user->id)
                        ->orderBy('id')
                        ->first();

                    if ($firstBranch) {
                        $request->merge(['branch_id' => (int) $firstBranch->branch_id]);
                    }
                }
            }

            // Attach user's branch count for controllers that need to know
            $branchCount = DB::table('user_branches')
                ->where('user_id', $user->id)
                ->count();

            $request->merge(['_user_branch_count' => $branchCount]);
        }

        return $next($request);
    }
}
