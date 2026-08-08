@extends('layouts.app')

@section('title', 'تسجيل الدخول - Axionyx ERP')
@section('page_title', 'تسجيل الدخول التجريبي')
@section('page_subtitle', 'اختر مستخدماً للدخول وتجربة كل باقة مع الشاشات المسموح بها.')

@section('content')
@if(session('error'))
    <div class="panel" style="border-color: var(--danger);">
        <p style="color: var(--danger); margin:0;">{{ session('error') }}</p>
    </div>
@endif

<div class="panel">
    <h2>اختر حساباً للتجربة</h2>
    <p class="muted">كل باقة لها شاشات وصلاحيات مختلفة. اختر مستخدماً لتجربة تجربة ذلك المستوى.</p>
</div>

<div class="plans-grid">
    @foreach($users as $user)
        @php
            $plan = $user->company?->subscriptions->first()?->plan;
        @endphp
        <form action="{{ route('demo-login') }}" method="POST">
            @csrf
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <div class="plan-card plan-tier-{{ $plan?->tier ?? 0 }}">
                @if($plan)
                    <div class="plan-tier-label">المستوى {{ $plan->tier }}</div>
                    <h3 class="plan-name">{{ $plan->name }}</h3>
                @else
                    <div class="plan-tier-label">Super Admin</div>
                    <h3 class="plan-name">مدير النظام</h3>
                @endif

                <div style="padding: 10px 0; border-top: 1px solid rgba(148,163,184,0.12);">
                    <div class="limit-row">
                        <span>المستخدم</span>
                        <span class="limit-value">{{ $user->name }}</span>
                    </div>
                    <div class="limit-row">
                        <span>الهاتف</span>
                        <span class="limit-value">{{ $user->phone }}</span>
                    </div>
                    <div class="limit-row">
                        <span>الشركة</span>
                        <span class="limit-value" style="font-size:12px;">{{ $user->company?->name_ar ?? 'النظام العام' }}</span>
                    </div>
                    @if($plan)
                        <div class="limit-row">
                            <span>السعر الشهري</span>
                            <span class="limit-value" style="color:var(--primary);">{{ number_format($plan->monthly_price, 0) }} ج.م</span>
                        </div>
                        <div class="limit-row">
                            <span>الحد الأقصى للمستخدمين</span>
                            <span class="limit-value">{{ $plan->max_users == 999 ? 'غير محدود' : $plan->max_users }}</span>
                        </div>
                    @endif
                </div>

                <button type="submit" class="btn primary" style="width:100%; text-align:center; margin-top:8px;">
                    دخول كـ {{ $user->name }}
                </button>
            </div>
        </form>
    @endforeach
</div>

<style>
    .plans-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
    }
    .plan-card {
        background: rgba(17,24,39,0.92);
        border: 1px solid var(--line);
        border-radius: 18px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 14px;
        position: relative;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .plan-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.4);
    }
    .plan-tier-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        color: #94a3b8;
    }
    .plan-name {
        font-size: 22px;
        margin: 0;
        font-weight: 700;
    }
    .limit-row {
        display: flex;
        justify-content: space-between;
        font-size: 13px;
        color: var(--muted);
        padding: 4px 0;
    }
    .limit-value {
        color: var(--text);
        font-weight: 600;
    }
    @media (max-width: 1200px) {
        .plans-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 768px) {
        .plans-grid { grid-template-columns: 1fr; }
    }
</style>
@endsection
