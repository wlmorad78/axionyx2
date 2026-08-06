@extends('layouts.app')

@section('title', 'عرض تسوية بنكية - Axionyx ERP')
@section('page_title', 'عرض التسوية البنكية')
@section('page_subtitle', 'تفاصيل التسوية البنكية')

@section('content')
@if(session('success'))
    <div style="padding:12px 16px;border-radius:10px;background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.18);color:#bbf7d0;margin-bottom:16px;font-size:14px;">
        {{ session('success') }}
    </div>
@endif

<div class="grid grid-2">
    <article class="panel">
        <h2>معلومات التسوية</h2>
        <table>
            <tbody>
                <tr><th style="width:160px;">الحساب البنكي</th><td><strong>{{ $bankReconciliation->bankAccount?->bank_name ?? '—' }}</strong></td></tr>
                <tr><th>رقم الحساب</th><td>{{ $bankReconciliation->bankAccount?->account_number ?? '—' }}</td></tr>
                <tr><th>تاريخ التسوية</th><td>{{ $bankReconciliation->reconciliation_date }}</td></tr>
                <tr><th>الحالة</th>
                    <td>
                        @if($bankReconciliation->status === 'completed')
                            <span class="status good">✓ مكتمل</span>
                        @elseif($bankReconciliation->status === 'draft')
                            <span class="status" style="background:rgba(148,163,184,0.12);color:#94a3b8;">مسودة</span>
                        @else
                            <span class="status warn">⏳ {{ $bankReconciliation->status }}</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </article>

    <article class="panel">
        <h2>الأرصدة والفرق</h2>
        <table>
            <tbody>
                <tr><th style="width:160px;">رصيد كشف الحساب</th><td>{{ number_format($bankReconciliation->statement_balance, 2) }}</td></tr>
                <tr><th>الرصيد الدفتري</th><td>{{ number_format($bankReconciliation->book_balance ?? $bankReconciliation->system_balance, 2) }}</td></tr>
                <tr><th>الفرق</th>
                    <td>
                        <span style="font-size:18px; font-weight:700; color:{{ $bankReconciliation->difference >= 0 ? 'var(--primary)' : 'var(--danger)' }};">
                            {{ number_format($bankReconciliation->difference, 2) }}
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    </article>
</div>

@if($bankReconciliation->notes)
<article class="panel">
    <h2>ملاحظات</h2>
    <p style="color:var(--muted);">{{ $bankReconciliation->notes }}</p>
</article>
@endif

<div style="display:flex; gap:8px; margin-top:8px;">
    <a href="{{ route('bank-reconciliations.index') }}" class="btn">↩ العودة إلى القائمة</a>
</div>
@endsection
