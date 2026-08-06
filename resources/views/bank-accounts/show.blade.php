@extends('layouts.app')

@section('title', 'عرض حساب بنكي - Axionyx ERP')
@section('page_title', 'عرض حساب بنكي')
@section('page_subtitle', 'تفاصيل الحساب البنكي: ' . $bankAccount->bank_name)

@section('content')
@if(session('success'))
    <div style="padding:12px 16px;border-radius:10px;background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.18);color:#bbf7d0;margin-bottom:16px;font-size:14px;">
        {{ session('success') }}
    </div>
@endif

<div class="grid grid-2">
    <article class="panel">
        <h2>معلومات الحساب</h2>
        <table>
            <tbody>
                <tr><th style="width:160px;">اسم البنك</th><td>{{ $bankAccount->bank_name }}</td></tr>
                <tr><th>رقم الحساب</th><td>{{ $bankAccount->account_number ?? '—' }}</td></tr>
                <tr><th>اسم الحساب</th><td>{{ $bankAccount->account_name ?? '—' }}</td></tr>
                <tr><th>IBAN</th><td>{{ $bankAccount->iban ?? '—' }}</td></tr>
                <tr><th>SWIFT Code</th><td>{{ $bankAccount->swift_code ?? '—' }}</td></tr>
                <tr><th>الفرع</th><td>{{ $bankAccount->branch_name ?? '—' }}</td></tr>
                <tr><th>العملة</th><td>{{ $bankAccount->currency?->name ?? '—' }}</td></tr>
                <tr><th>الحالة</th>
                    <td>
                        @if($bankAccount->is_active)
                            <span class="status good">✓ نشط</span>
                        @else
                            <span class="status bad">✗ غير نشط</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </article>

    <article class="panel">
        <h2>الأرصدة</h2>
        <table>
            <tbody>
                <tr><th style="width:160px;">الرصيد الافتتاحي</th><td>{{ number_format($bankAccount->opening_balance, 2) }}</td></tr>
                <tr><th>الرصيد الحالي</th><td><strong style="color:var(--primary);font-size:18px;">{{ number_format($bankAccount->current_balance, 2) }}</strong></td></tr>
            </tbody>
        </table>

        @if($bankAccount->notes)
            <h3 style="margin-top:16px;">ملاحظات</h3>
            <p style="color:var(--muted);font-size:13px;">{{ $bankAccount->notes }}</p>
        @endif
    </article>
</div>

<div style="display:flex; gap:8px; margin-top:8px;">
    <a href="{{ route('bank-accounts.edit', $bankAccount->id) }}" class="btn primary">تعديل الحساب</a>
    <a href="{{ route('bank-accounts.index') }}" class="btn">↩ العودة إلى القائمة</a>
</div>
@endsection
