@extends('layouts.app')

@section('title', 'عرض التحويل - Axionyx ERP')
@section('page_title', 'عرض التحويل')
@section('page_subtitle', 'تفاصيل التحويل: ' . $treasuryBankTransfer->transfer_no)

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
                <tr>
                    <th style="width:160px;">رقم التحويل</th>
                    <td><strong style="color:var(--accent);">{{ $treasuryBankTransfer->transfer_no }}</strong></td>
                </tr>
                <tr>
                    <th>نوع التحويل</th>
                    <td>
                        @if($treasuryBankTransfer->transfer_type === 'treasury_to_bank')
                            <span class="status good">من الخزنة إلى البنك</span>
                        @else
                            <span class="status" style="background:rgba(56,189,248,0.12);color:#e0f2fe;border-color:rgba(56,189,248,0.16);">من البنك إلى الخزنة</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>التاريخ</th>
                    <td>{{ $treasuryBankTransfer->transfer_date?->format('Y-m-d') ?? '-' }}</td>
                </tr>
                <tr>
                    <th>الحالة</th>
                    <td>
                        @if($treasuryBankTransfer->status === 'completed')
                            <span class="status good">✓ مكتمل</span>
                        @elseif($treasuryBankTransfer->status === 'draft')
                            <span class="status" style="background:rgba(148,163,184,0.12);color:#94a3b8;">مسودة</span>
                        @elseif($treasuryBankTransfer->status === 'cancelled')
                            <span class="status bad">✗ ملغي</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </article>

    <article class="panel">
        <h2>المبلغ</h2>
        <div style="text-align:center; padding:20px;">
            <div style="font-size:36px; font-weight:700; color:var(--primary);">
                {{ number_format($treasuryBankTransfer->amount, 2) }}
            </div>
            <div style="color:var(--muted); font-size:14px; margin-top:8px;">جنيه مصري</div>
        </div>
    </article>
</div>

<div class="grid grid-2">
    <article class="panel">
        <h2>الخزنة</h2>
        <table>
            <tbody>
                <tr>
                    <th style="width:120px;">الاسم</th>
                    <td>{{ $treasuryBankTransfer->treasury->name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>الرصيد الحالي</th>
                    <td><strong>{{ number_format($treasuryBankTransfer->treasury->balance ?? 0, 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    </article>

    <article class="panel">
        <h2>حساب البنك</h2>
        <table>
            <tbody>
                <tr>
                    <th style="width:120px;">البنك</th>
                    <td>{{ $treasuryBankTransfer->bankAccount->bank_name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>رقم الحساب</th>
                    <td>{{ $treasuryBankTransfer->bankAccount->account_number ?? '-' }}</td>
                </tr>
                <tr>
                    <th>الرصيد الحالي</th>
                    <td><strong>{{ number_format($treasuryBankTransfer->bankAccount->current_balance ?? 0, 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    </article>
</div>

@if($treasuryBankTransfer->description || $treasuryBankTransfer->notes)
    <article class="panel">
        <h2>الوصف والملاحظات</h2>
        @if($treasuryBankTransfer->description)
            <div style="margin-bottom:8px;">
                <strong>الوصف:</strong> {{ $treasuryBankTransfer->description }}
            </div>
        @endif
        @if($treasuryBankTransfer->notes)
            <div>
                <strong>ملاحظات:</strong> {{ $treasuryBankTransfer->notes }}
            </div>
        @endif
    </article>
@endif

<div style="display:flex; gap:8px; margin-top:8px;">
    <a href="{{ route('treasury-bank-transfers.index') }}" class="btn">↩ العودة إلى القائمة</a>
    <form method="POST" action="{{ route('treasury-bank-transfers.destroy', $treasuryBankTransfer->id) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذا التحويل؟')">
        @csrf @method('DELETE')
        <button type="submit" class="btn" style="color:var(--danger);border-color:rgba(251,113,133,0.3);">حذف التحويل</button>
    </form>
</div>
@endsection
