@extends('layouts.app')

@section('title', 'عرض الرصيد الافتتاحي - Axionyx ERP')
@section('page_title', 'الرصيد الافتتاحي للبنك')
@section('page_subtitle', $bankOpeningBalance->bankAccount?->bank_name ?? 'بنك')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
    <div>
        <h2 style="margin:0;">{{ $bankOpeningBalance->bankAccount?->bank_name ?? 'بنك' }}</h2>
        <p style="margin:4px 0 0;color:var(--muted);font-size:13px;">رقم الحساب: {{ $bankOpeningBalance->bankAccount?->account_number ?? '—' }}</p>
    </div>
    <div style="display:flex; gap:8px;">
        <a href="{{ route('bank-opening-balances.index') }}" class="btn" style="font-size:13px;">العودة للقائمة</a>
        <a href="{{ route('bank-opening-balances.edit', $bankOpeningBalance->id) }}" class="btn" style="font-size:13px;">تعديل</a>
        <form method="POST" action="{{ route('bank-opening-balances.destroy', $bankOpeningBalance->id) }}" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من الحذف؟ سيتم تصفير رصيد البنك.')">
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
                <span style="color:var(--muted);font-size:13px;">البنك</span>
                <span style="font-size:14px;font-weight:600;">{{ $bankOpeningBalance->bankAccount?->bank_name ?? '—' }}</span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">اسم الحساب</span>
                <span style="font-size:14px;">{{ $bankOpeningBalance->bankAccount?->account_name ?? '—' }}</span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">رقم الحساب</span>
                <span style="font-size:14px;">{{ $bankOpeningBalance->bankAccount?->account_number ?? '—' }}</span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">السنة المالية</span>
                <span style="font-size:14px;">{{ $bankOpeningBalance->fiscalYear?->year_code ?? '—' }}</span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">الرصيد الافتتاحي</span>
                <span style="font-size:18px;font-weight:700;color:var(--primary);">{{ number_format($bankOpeningBalance->opening_balance, 2) }}</span>
            </div>
            @if($bankOpeningBalance->notes)
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">ملاحظات</span>
                <span style="font-size:14px;">{{ $bankOpeningBalance->notes }}</span>
            </div>
            @endif
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">تاريخ الإنشاء</span>
                <span style="font-size:14px;">{{ $bankOpeningBalance->created_at?->format('Y-m-d H:i') ?? '—' }}</span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">آخر تحديث</span>
                <span style="font-size:14px;">{{ $bankOpeningBalance->updated_at?->format('Y-m-d H:i') ?? '—' }}</span>
            </div>
        </div>
    </article>

    <article class="panel">
        <h3>معلومات الحساب البنكي</h3>
        <div style="display:flex; flex-direction:column; gap:12px;">
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">اسم البنك</span>
                <span style="font-size:14px;">{{ $bankOpeningBalance->bankAccount?->bank_name ?? '—' }}</span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">رقم الآيبان</span>
                <span style="font-size:14px;">{{ $bankOpeningBalance->bankAccount?->iban ?? '—' }}</span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">الفرع</span>
                <span style="font-size:14px;">{{ $bankOpeningBalance->bankAccount?->branch_name ?? '—' }}</span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">العملة</span>
                <span style="font-size:14px;">{{ $bankOpeningBalance->bankAccount?->currency?->name_ar ?? '—' }}</span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">الحالة</span>
                @if($bankOpeningBalance->bankAccount?->is_active)
                    <span class="status good">نشط</span>
                @else
                    <span class="status bad">غير نشط</span>
                @endif
            </div>
            <div style="display:flex; justify-content:space-between;border-top:1px solid var(--line);padding-top:12px;">
                <span style="color:var(--muted);font-size:13px;">الرصيد الحالي</span>
                <span style="font-size:18px;font-weight:700;color:{{ ($bankOpeningBalance->bankAccount->current_balance ?? 0) >= 0 ? 'var(--primary)' : 'var(--danger)' }};">
                    {{ number_format($bankOpeningBalance->bankAccount->current_balance ?? 0, 2) }}
                </span>
            </div>
        </div>
    </article>
</div>
@endsection
