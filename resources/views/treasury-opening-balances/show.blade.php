@extends('layouts.app')

@section('title', 'عرض الرصيد الافتتاحي - Axionyx ERP')
@section('page_title', 'الرصيد الافتتاحي للخزنة')
@section('page_subtitle', $treasuryOpeningBalance->treasury?->name_ar ?? 'خزنة')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
    <div>
        <h2 style="margin:0;">{{ $treasuryOpeningBalance->treasury?->name_ar ?? 'خزنة' }}</h2>
        <p style="margin:4px 0 0;color:var(--muted);font-size:13px;">كود الخزنة: {{ $treasuryOpeningBalance->treasury?->code ?? '—' }}</p>
    </div>
    <div style="display:flex; gap:8px;">
        <a href="{{ route('treasury-opening-balances.index') }}" class="btn" style="font-size:13px;">العودة للقائمة</a>
        <a href="{{ route('treasury-opening-balances.edit', $treasuryOpeningBalance->id) }}" class="btn" style="font-size:13px;">تعديل</a>
        <form method="POST" action="{{ route('treasury-opening-balances.destroy', $treasuryOpeningBalance->id) }}" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من الحذف؟ سيتم تصفير رصيد الخزنة.')">
            @csrf @method('DELETE')
            <button type="submit" class="btn" style="font-size:13px;color:var(--danger);border-color:rgba(251,113,133,0.3);">حذف</button>
        </form>
    </div>
</div>

@if(session('success'))
    <div style="padding:12px 16px;border-radius:10px;background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.18);color:#bbf7d0;margin-bottom:16px;font-size:14px;">
        {{ session('success') }}
    </div>
@endif

<div class="grid grid-2" style="margin-bottom:16px;">
    <article class="panel">
        <h3>بيانات الرصيد الافتتاحي</h3>
        <div style="display:flex; flex-direction:column; gap:12px;">
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">الخزنة</span>
                <span style="font-size:14px;font-weight:600;">{{ $treasuryOpeningBalance->treasury?->name_ar ?? '—' }}</span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">كود الخزنة</span>
                <span style="font-size:14px;">{{ $treasuryOpeningBalance->treasury?->code ?? '—' }}</span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">السنة المالية</span>
                <span style="font-size:14px;">{{ $treasuryOpeningBalance->fiscalYear?->year_code ?? '—' }}</span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">الرصيد الافتتاحي</span>
                <span style="font-size:18px;font-weight:700;color:var(--primary);">{{ number_format($treasuryOpeningBalance->opening_balance, 2) }}</span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">تاريخ الإنشاء</span>
                <span style="font-size:14px;">{{ $treasuryOpeningBalance->created_at?->format('Y-m-d H:i') ?? '—' }}</span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">آخر تحديث</span>
                <span style="font-size:14px;">{{ $treasuryOpeningBalance->updated_at?->format('Y-m-d H:i') ?? '—' }}</span>
            </div>
        </div>
    </article>

    <article class="panel">
        <h3>معلومات الخزنة</h3>
        <div style="display:flex; flex-direction:column; gap:12px;">
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">النوع</span>
                <span style="font-size:14px;">{{ $treasuryOpeningBalance->treasury?->treasuryType?->name_ar ?? '—' }}</span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">العملة</span>
                <span style="font-size:14px;">{{ $treasuryOpeningBalance->treasury?->currency?->name_ar ?? '—' }}</span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">الفرع</span>
                <span style="font-size:14px;">{{ $treasuryOpeningBalance->treasury?->branch?->name_ar ?? '—' }}</span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">الحالة</span>
                @if($treasuryOpeningBalance->treasury?->is_active)
                    <span class="status good">✓ نشط</span>
                @else
                    <span class="status bad">✗ غير نشط</span>
                @endif
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">خزنة رئيسية</span>
                @if($treasuryOpeningBalance->treasury?->is_main)
                    <span class="status good">✓ نعم</span>
                @else
                    <span class="status warn">لا</span>
                @endif
            </div>
            <div style="display:flex; justify-content:space-between;border-top:1px solid var(--line);padding-top:12px;">
                <span style="color:var(--muted);font-size:13px;">الرصيد الحالي</span>
                <span style="font-size:18px;font-weight:700;color:{{ ($treasuryOpeningBalance->treasury->balance ?? 0) >= 0 ? 'var(--primary)' : 'var(--danger)' }};">
                    {{ number_format($treasuryOpeningBalance->treasury->balance ?? 0, 2) }}
                </span>
            </div>
        </div>
    </article>
</div>
@endsection
