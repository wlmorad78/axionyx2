<?php
/**
 * =====================================================================
 * متحكم (Controller): DemoAuthController
 * الوحدة (Module): عام (عام)
 * المورد (Resource): Demo Auth
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Demo Auth" ضمن وحدة "عام".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoAuthController extends Controller
{
    /**
     * دالة معالجة: showLogin — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Demo Auth).
     */
    public function showLogin()
    {
        $users = User::with('company')
            ->where('is_active', true)
            ->whereHas('company', fn ($q) => $q->where('is_active', true))
            ->orderBy('usercode')
            ->get();

        return view('auth.demo-login', compact('users'));
    }

    /**
     * دالة معالجة: login — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Demo Auth).
     */
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

    /**
     * دالة معالجة: logout — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Demo Auth).
     */
    public function logout()
    {
        Auth::logout();
        session()->flush();
        return redirect('/demo-login');
    }
}
