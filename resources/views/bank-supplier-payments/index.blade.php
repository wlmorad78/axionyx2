@extends('layouts.app')

@section('title', 'مدفوعات الموردين - Axionyx ERP')
@section('page_title', 'مدفوعات الموردين من البنك')
@section('page_subtitle', 'إدارة الدفعات المدفوعة للموردين من حسابات البنوك')

@section('content')
<div class="grid grid-4">
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">إجمالي المدفوعات</div>
                <div class="value">{{ $payments->total() }}</div>
                <div class="trend">دفعة</div>
            </div>
            <span class="chip">All</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">إجمالي المبلغ المدفوع</div>
                <div class="value">{{ number_format($totalPaid, 2) }}</div>
                <div class="trend">جنيه</div>
            </div>
            <span class="chip" style="background:rgba(245,158,11,0.12);color:#fde68a;border-color:rgba(245,158,11,0.18);">💰</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">المدفوعات المكتملة</div>
                <div class="value">{{ $payments->where('status', 'completed')->count() }}</div>
                <div class="trend">دفعة</div>
            </div>
            <span class="chip" style="background:rgba(34,197,94,0.12);color:#bbf7d0;border-color:rgba(34,197,94,0.18);">✓</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">المسودات</div>
                <div class="value">{{ $payments->where('status', 'draft')->count() }}</div>
                <div class="trend">دفعة</div>
            </div>
            <span class="chip" style="background:rgba(148,163,184,0.12);color:#94a3b8;border-color:rgba(148,163,184,0.18);">📝</span>
        </div>
    </article>
</div>

<article class="panel">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:10px;">
        <h2>قائمة المدفوعات</h2>
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <form method="GET" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                <input type="text" name="search" placeholder="بحث..." value="{{ request('search') }}" style="width:180px;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:8px 10px;color:#fff;font-size:13px;">
                <select name="status" style="background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:8px 10px;color:#fff;font-size:13px;">
                    <option value="">كل الحالات</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>مسودة</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>مكتمل</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ملغي</option>
                </select>
                <button type="submit" class="btn">بحث</button>
            </form>
            <a href="{{ route('bank-supplier-payments.create') }}" class="btn primary">+ دفعة جديدة</a>
        </div>
    </div>

    @if(session('success'))
        <div style="padding:12px 16px;border-radius:10px;background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.18);color:#bbf7d0;margin-bottom:16px;font-size:14px;">
            {{ session('success') }}
        </div>
    @endif

    @if($payments->isEmpty())
        <div style="text-align:center; padding:40px; color:var(--muted);">
            <div style="font-size:48px; margin-bottom:12px;">🏦</div>
            <div style="font-size:16px; margin-bottom:8px;">لا توجد مدفوعات</div>
            <div style="font-size:13px;">ابدأ بإنشاء دفعة جديدة للموردين</div>
        </div>
    @else
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>رقم الدفعة</th>
                        <th>المورد</th>
                        <th>البنك</th>
                        <th>المبلغ</th>
                        <th>التاريخ</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $index => $payment)
                        <tr>
                            <td>{{ ($payments->currentPage() - 1) * $payments->perPage() + $index + 1 }}</td>
                            <td><strong style="color:var(--accent);">{{ $payment->payment_no }}</strong></td>
                            <td>{{ $payment->supplier->supplier_name ?? '-' }}</td>
                            <td>{{ $payment->bankAccount->bank_name ?? '-' }} - {{ $payment->bankAccount->account_number ?? '' }}</td>
                            <td><strong>{{ number_format($payment->amount, 2) }}</strong></td>
                            <td>{{ $payment->payment_date?->format('Y-m-d') ?? '-' }}</td>
                            <td>
                                @if($payment->status === 'completed')
                                    <span class="status good">✓ مكتمل</span>
                                @elseif($payment->status === 'draft')
                                    <span class="status" style="background:rgba(148,163,184,0.12);color:#94a3b8;">مسودة</span>
                                @elseif($payment->status === 'cancelled')
                                    <span class="status bad">✗ ملغي</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex; gap:6px;">
                                    <a href="{{ route('bank-supplier-payments.show', $payment->id) }}" class="btn" style="padding:6px 10px;font-size:12px;">عرض</a>
                                    <form method="POST" action="{{ route('bank-supplier-payments.destroy', $payment->id) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذه الدفعة؟')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn" style="padding:6px 10px;font-size:12px;color:var(--danger);border-color:rgba(251,113,133,0.3);">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="display:flex; justify-content:center; margin-top:16px;">
            {{ $payments->withQueryString()->links() }}
        </div>
    @endif
</article>
@endsection
