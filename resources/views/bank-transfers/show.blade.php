@extends('layouts.app')

@section('title', 'عرض تحويل بنكي - Axionyx ERP')
@section('page_title', 'عرض التحويل البنكي')
@section('page_subtitle', 'تفاصيل التحويل: ' . $bankTransfer->transfer_no)

@section('content')
@if(session('success'))
    <div style="padding:12px 16px;border-radius:10px;background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.18);color:#bbf7d0;margin-bottom:16px;font-size:14px;">
        {{ session('success') }}
    </div>
@endif

<div class="grid grid-2">
    <article class="panel">
        <h2>معلومات التحويل</h2>
        <table>
            <tbody>
                <tr><th style="width:160px;">رقم التحويل</th><td><strong style="color:var(--accent);">{{ $bankTransfer->transfer_no }}</strong></td></tr>
                <tr><th>تاريخ التحويل</th><td>{{ $bankTransfer->transfer_date }}</td></tr>
                <tr><th>الحالة</th>
                    <td>
                        @if($bankTransfer->status === 'completed')
                            <span class="status good">✓ مكتمل</span>
                        @elseif($bankTransfer->status === 'draft')
                            <span class="status" style="background:rgba(148,163,184,0.12);color:#94a3b8;">مسودة</span>
                        @elseif($bankTransfer->status === 'cancelled')
                            <span class="status bad">✗ ملغي</span>
                        @else
                            <span class="status">{{ $bankTransfer->status }}</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </article>

    <article class="panel">
        <h2>المبلغ</h2>
        <div style="text-align:center; padding:20px;">
            <div style="font-size:36px; font-weight:700; color:var(--primary);">{{ number_format($bankTransfer->amount, 2) }}</div>
            <div style="color:var(--muted); font-size:13px;">المبلغ المحول</div>
        </div>
    </article>
</div>

<article class="panel">
    <h2>تفاصيل الحسابات</h2>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-top:12px;">
        <div style="padding:16px; background:rgba(251,113,133,0.08); border:1px solid rgba(251,113,133,0.18); border-radius:12px;">
            <h3 style="color:var(--danger);margin-bottom:8px;">من حساب</h3>
            <p><strong>{{ $bankTransfer->fromBankAccount?->bank_name ?? '—' }}</strong></p>
            <p style="font-size:13px;color:var(--muted);">رقم الحساب: {{ $bankTransfer->fromBankAccount?->account_number ?? '—' }}</p>
        </div>
        <div style="padding:16px; background:rgba(34,197,94,0.08); border:1px solid rgba(34,197,94,0.18); border-radius:12px;">
            <h3 style="color:var(--primary);margin-bottom:8px;">إلى حساب</h3>
            <p><strong>{{ $bankTransfer->toBankAccount?->bank_name ?? '—' }}</strong></p>
            <p style="font-size:13px;color:var(--muted);">رقم الحساب: {{ $bankTransfer->toBankAccount?->account_number ?? '—' }}</p>
        </div>
    </div>
</article>

@if($bankTransfer->description)
<article class="panel">
    <h2>الوصف</h2>
    <p style="color:var(--muted);">{{ $bankTransfer->description }}</p>
</article>
@endif

@if($bankTransfer->notes)
<article class="panel">
    <h2>ملاحظات</h2>
    <p style="color:var(--muted);">{{ $bankTransfer->notes }}</p>
</article>
@endif

<div style="display:flex; gap:8px; margin-top:8px;">
    <a href="{{ route('bank-transfers.index') }}" class="btn">↩ العودة إلى القائمة</a>
</div>
@endsection
