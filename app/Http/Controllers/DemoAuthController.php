<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoAuthController extends Controller
{
    public function showLogin()
    {
        $users = User::with('company')
            ->where('is_active', true)
            ->whereHas('company', fn ($q) => $q->where('is_active', true))
            ->orderBy('usercode')
            ->get();

        return view('auth.demo-login', compact('users'));
    }

    public function login(Request $request)
    {
        $userId = $request->input('user_id');
        $user = User::find($userId);

        if (!$user || !$user->is_active) {
            return back()->with('error', 'مستخدم غير صالح.');
        }

        Auth::login($user);

        session(['company_id' => $user->company_id]);

        return redirect()->intended('/admin');
    }

    public function logout()
    {
        Auth::logout();
        session()->flush();
        return redirect('/demo-login');
    }
}
