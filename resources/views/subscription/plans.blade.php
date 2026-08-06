@extends('layouts.app')

@section('title', 'باقات الاشتراك - Axionyx ERP')
@section('page_title', 'باقات الاشتراك')
@section('page_subtitle', 'اختر الباقة المناسبة لحجم عملك — من الموزع المستقل إلى الشركة الضخمة.')

@section('content')
@if(session('error'))
    <div class="panel" style="border-color: var(--danger);">
        <p style="color: var(--danger); margin:0;">{{ session('error') }}</p>
    </div>
@endif

@if(session('success'))
    <div class="panel" style="border-color: var(--primary);">
        <p style="color: var(--primary); margin:0;">{{ session('success') }}</p>
    </div>
@endif

<div class="panel">
    <h2>مقارنة الباقات</h2>
    <p class="muted">اختر الباقة التي تناسب حجم عملك. يمكنك الترقية في أي وقت.</p>
</div>

<div class="plans-grid">
    @foreach($plans as $plan)
        <div class="plan-card {{ $plan->is_popular ? 'plan-popular' : '' }} plan-tier-{{ $plan->tier }}">
            @if($plan->is_popular)
                <div class="plan-badge">الأكثر شيوعاً</div>
            @endif

            <div class="plan-tier-label">المستوى {{ $plan->tier }}</div>
            <h3 class="plan-name">{{ $plan->name }}</h3>
            <p class="plan-description">{{ $plan->description }}</p>

            <div class="plan-pricing">
                <div class="plan-price">
                    <span class="price-value">{{ number_format($plan->monthly_price, 0) }}</span>
                    <span class="price-currency">ج.م/شهرياً</span>
                </div>
                <div class="plan-setup">
                    تركيب: {{ number_format($plan->setup_price, 0) }} ج.م
                </div>
            </div>

            <div class="plan-limits">
                <div class="limit-row">
                    <span>المستخدمون</span>
                    <span class="limit-value">{{ $plan->max_users == 999 ? 'غير محدود' : $plan->max_users }}</span>
                </div>
                <div class="limit-row">
                    <span>الفروع</span>
                    <span class="limit-value">{{ $plan->max_branches == 999 ? 'غير محدود' : $plan->max_branches }}</span>
                </div>
                <div class="limit-row">
                    <span>المخازن</span>
                    <span class="limit-value">{{ $plan->max_warehouses == 999 ? 'غير محدود' : $plan->max_warehouses }}</span>
                </div>
                <div class="limit-row">
                    <span>الصناديق</span>
                    <span class="limit-value">{{ $plan->max_treasuries == 999 ? 'غير محدود' : $plan->max_treasuries }}</span>
                </div>
            </div>

            <div class="plan-features">
                <h4>الميزات المتاحة:</h4>
                <ul>
                    @foreach($plan->features ?? [] as $feature)
                        <li>
                            <span class="feature-check">✓</span>
                            {{ $feature }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="plan-modules">
                <h4>الوحدات:</h4>
                <div class="module-tags">
                    @foreach($plan->modules as $module)
                        <span class="module-tag">{{ $module->title }}</span>
                    @endforeach
                </div>
            </div>

            <div class="plan-actions">
                <a href="{{ route('subscription-plans.show', $plan->id) }}" class="btn primary" style="width:100%; text-align:center;">
                    عرض التفاصيل
                </a>
                @if($currentPlan && $currentPlan->id !== $plan->id)
                    <form action="{{ route('subscription-plans.assign', $plan->id) }}" method="POST" style="width:100%;">
                        @csrf
                        <button type="submit" class="btn" style="width:100%; text-align:center; margin-top:8px;">
                            {{ $plan->tier > ($currentPlan->tier ?? 0) ? 'ترقية لهذه الباقة' : 'تحويل لهذه الباقة' }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
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
    .plan-popular {
        border-color: var(--primary);
        box-shadow: 0 0 20px rgba(34,197,94,0.15);
    }
    .plan-badge {
        position: absolute;
        top: -10px;
        right: 16px;
        background: linear-gradient(135deg, var(--primary), #10b981);
        color: #052e16;
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
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
    .plan-description {
        color: var(--muted);
        font-size: 13px;
        margin: 0;
        line-height: 1.5;
    }
    .plan-pricing {
        text-align: center;
        padding: 14px 0;
        border-top: 1px solid rgba(148,163,184,0.12);
        border-bottom: 1px solid rgba(148,163,184,0.12);
    }
    .plan-price {
        display: flex;
        align-items: baseline;
        justify-content: center;
        gap: 6px;
    }
    .price-value {
        font-size: 32px;
        font-weight: 800;
        color: var(--primary);
    }
    .price-currency {
        color: var(--muted);
        font-size: 13px;
    }
    .plan-setup {
        color: var(--muted);
        font-size: 12px;
        margin-top: 4px;
    }
    .plan-limits {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .limit-row {
        display: flex;
        justify-content: space-between;
        font-size: 13px;
        color: var(--muted);
    }
    .limit-value {
        color: var(--text);
        font-weight: 600;
    }
    .plan-features h4, .plan-modules h4 {
        font-size: 13px;
        margin: 0 0 8px;
        color: #94a3b8;
    }
    .plan-features ul {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .plan-features li {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        color: var(--muted);
    }
    .feature-check {
        color: var(--primary);
        font-weight: 700;
        font-size: 11px;
    }
    .module-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .module-tag {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 999px;
        font-size: 11px;
        background: rgba(56,189,248,0.12);
        color: #e0f2fe;
        border: 1px solid rgba(56,189,248,0.16);
    }
    .plan-actions {
        margin-top: auto;
        padding-top: 10px;
    }

    @media (max-width: 1200px) {
        .plans-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 768px) {
        .plans-grid { grid-template-columns: 1fr; }
    }
</style>
@endsection
