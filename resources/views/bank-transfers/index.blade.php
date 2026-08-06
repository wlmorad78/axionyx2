@extends('layouts.app')

@section('title', 'التحويلات البنكية - Axionyx ERP')
@section('page_title', 'التحويلات البنكية')
@section('page_subtitle', 'إدارة التحويلات بين الحسابات البنكية')

@section('content')
<div class="grid grid-4">
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">إجمالي التحويلات</div>
                <div class="value">{{ $transfers->total() }}</div>
                <div class="trend">عملية تحويل</div>
            </div>
            <span class="chip">All</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">المبلغ الإجمالي</div>
                <div class="value">{{ number_format(\App\Models\BankTransfer::sum('amount'), 2) }}</div>
                <div class="trend">إجمالي المحول</div>
            </div>
            <span class="chip">Amount</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">آخر تحويل</div>
                <div class="value" style="font-size:16px;">{{ \App\Models\BankTransfer::max('transfer_date') ?? '—' }}</div>
                <div class="trend">تاريخ آخر عملية</div>
            </div>
            <span class="chip">Date</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">متوسط المبلغ</div>
                <div class="value">{{ number_format(\App\Models\BankTransfer::avg('amount') ?? 0, 2) }}</div>
                <div class="trend">لكل عملية</div>
            </div>
            <span class="chip">Avg</span>
        </div>
    </article>
</div>

<article class="panel">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h2>قائمة التحويلات البنكية</h2>
        <div style="display:flex; gap:8px;">
            <form method="GET" style="display:flex; gap:8px; align-items:center;">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="بحث برقم التحويل..." style="background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:8px 12px;color:#fff;font-size:13px;">
                <select name="status" style="background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:8px 12px;color:#fff;font-size:13px;">
                    <option value="">جميع الحالات</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>مكتمل</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>مسودة</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ملغي</option>
                </select>
                <button type="submit" class="btn">بحث</button>
            </form>
            <a href="{{ route('bank-transfers.create') }}" class="btn primary">+ تحويل جديد</a>
        </div>
    </div>

    @if(session('success'))
        <div style="padding:12px 16px;border-radius:10px;background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.18);color:#bbf7d0;margin-bottom:16px;font-size:14px;">
            {{ session('success') }}
        </div>
    @endif

    @if($transfers->isEmpty())
        <div style="text-align:center; padding:40px; color:var(--muted);">
            <p style="font-size:18px;">لا توجد تحويلات بنكية حالياً</p>
            <p style="font-size:13px;">اضغط على "تحويل جديد" لإنشاء أول تحويل</p>
        </div>
    @else
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>رقم التحويل</th>
                        <th>من حساب</th>
                        <th>إلى حساب</th>
                        <th>التاريخ</th>
                        <th>المبلغ</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transfers as $index => $transfer)
                        <tr>
                            <td>{{ ($transfers->currentPage() - 1) * $transfers->perPage() + $index + 1 }}</td>
                            <td><strong style="color:var(--accent);">{{ $transfer->transfer_no }}</strong></td>
                            <td>{{ $transfer->fromBankAccount?->bank_name ?? '—' }}</td>
                            <td>{{ $transfer->toBankAccount?->bank_name ?? '—' }}</td>
                            <td>{{ $transfer->transfer_date }}</td>
                            <td>{{ number_format($transfer->amount, 2) }}</td>
                            <td>
                                @if($transfer->status === 'completed')
                                    <span class="status good">✓ مكتمل</span>
                                @elseif($transfer->status === 'draft')
                                    <span class="status" style="background:rgba(148,163,184,0.12);color:#94a3b8;">مسودة</span>
                                @elseif($transfer->status === 'cancelled')
                                    <span class="status bad">✗ ملغي</span>
                                @else
                                    <span class="status">{{ $transfer->status }}</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex; gap:6px;">
                                    <a href="{{ route('bank-transfers.show', $transfer->id) }}" class="btn" style="padding:6px 10px;font-size:12px;">عرض</a>
                                    <form method="POST" action="{{ route('bank-transfers.destroy', $transfer->id) }}" onsubmit="return confirm('هل أنت متأكد من الحذف؟')">
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
            {{ $transfers->withQueryString()->links() }}
        </div>
    @endif
</article>
@endsection
