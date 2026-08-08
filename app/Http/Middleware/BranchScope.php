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
                // نحتاج العدد فقط
                $branchCount = DB::table('user_branches')->where('user_id', $user->id)->count();
                $request->merge(['_user_branch_count' => $branchCount]);
            } else {
                // جلب كل فروع المستخدم في استعلام واحد (يستخدم لتحديد الفرع + العدد)
                $userBranches = DB::table('user_branches')
                    ->where('user_id', $user->id)
                    ->orderBy('is_default', 'desc')
                    ->orderBy('id')
                    ->get();

                $request->merge(['_user_branch_count' => $userBranches->count()]);

                if ($userBranches->isNotEmpty()) {
                    $defaultBranch = $userBranches->firstWhere('is_default', true);
                    $request->merge(['branch_id' => (int) ($defaultBranch->branch_id ?? $userBranches->first()->branch_id)]);
                }
            }
        }

        return $next($request);
    }
}
