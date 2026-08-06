@extends('layouts.app')

@section('title', 'تحويلات الخزنة والبنك - Axionyx ERP')
@section('page_title', 'تحويلات الخزنة والبنك')
@section('page_subtitle', 'إدارة التحويلات بين الخزنة وحسابات البنوك')

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
                <div class="muted">من الخزنة للبنك</div>
                <div class="value">{{ number_format($totalTreasuryToBank, 2) }}</div>
                <div class="trend">جنيه</div>
            </div>
            <span class="chip" style="background:rgba(34,197,94,0.12);color:#bbf7d0;border-color:rgba(34,197,94,0.18);">↑</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">من البنك للخزنة</div>
                <div class="value">{{ number_format($totalBankToTreasury, 2) }}</div>
                <div class="trend">جنيه</div>
            </div>
            <span class="chip" style="background:rgba(56,189,248,0.12);color:#e0f2fe;border-color:rgba(56,189,248,0.16);">↓</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">الصافي</div>
                <div class="value">{{ number_format($totalTreasuryToBank - $totalBankToTreasury, 2) }}</div>
                <div class="trend">جنيه</div>
            </div>
            <span class="chip">{{ $totalTreasuryToBank > $totalBankToTreasury ? 'للبنك' : 'للخزنة' }}</span>
        </div>
    </article>
</div>

<article class="panel">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:10px;">
        <h2>قائمة التحويلات</h2>
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <form method="GET" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                <input type="text" name="search" placeholder="بحث..." value="{{ request('search') }}" style="width:180px;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:8px 10px;color:#fff;font-size:13px;">
                <select name="transfer_type" style="background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:8px 10px;color:#fff;font-size:13px;">
                    <option value="">كل الأنواع</option>
                    <option value="treasury_to_bank" {{ request('transfer_type') === 'treasury_to_bank' ? 'selected' : '' }}>من الخزنة للبنك</option>
                    <option value="bank_to_treasury" {{ request('transfer_type') === 'bank_to_treasury' ? 'selected' : '' }}>من البنك للخزنة</option>
                </select>
                <select name="status" style="background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:8px 10px;color:#fff;font-size:13px;">
                    <option value="">كل الحالات</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>مسودة</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>مكتمل</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ملغي</option>
                </select>
                <button type="submit" class="btn">بحث</button>
            </form>
            <a href="{{ route('treasury-bank-transfers.create') }}" class="btn primary">+ تحويل جديد</a>
        </div>
    </div>

    @if(session('success'))
        <div style="padding:12px 16px;border-radius:10px;background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.18);color:#bbf7d0;margin-bottom:16px;font-size:14px;">
            {{ session('success') }}
        </div>
    @endif

    @if($transfers->isEmpty())
        <div style="text-align:center; padding:40px; color:var(--muted);">
            <div style="font-size:48px; margin-bottom:12px;">💸</div>
            <div style="font-size:16px; margin-bottom:8px;">لا توجد تحويلات</div>
            <div style="font-size:13px;">ابدأ بإنشاء تحويل جديد بين الخزنة والبنك</div>
        </div>
    @else
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>رقم التحويل</th>
                        <th>النوع</th>
                        <th>الخزنة</th>
                        <th>البنك</th>
                        <th>المبلغ</th>
                        <th>التاريخ</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transfers as $index => $transfer)
                        <tr>
                            <td>{{ ($transfers->currentPage() - 1) * $transfers->perPage() + $index + 1 }}</td>
                            <td><strong style="color:var(--accent);">{{ $transfer->transfer_no }}</strong></td>
                            <td>
                                @if($transfer->transfer_type === 'treasury_to_bank')
                                    <span class="status good">خزنة ← بنك</span>
                                @else
                                    <span class="status" style="background:rgba(56,189,248,0.12);color:#e0f2fe;border-color:rgba(56,189,248,0.16);">بنك ← خزنة</span>
                                @endif
                            </td>
                            <td>{{ $transfer->treasury->name ?? '-' }}</td>
                            <td>{{ $transfer->bankAccount->bank_name ?? '-' }} - {{ $transfer->bankAccount->account_number ?? '' }}</td>
                            <td><strong>{{ number_format($transfer->amount, 2) }}</strong></td>
                            <td>{{ $transfer->transfer_date?->format('Y-m-d') ?? '-' }}</td>
                            <td>
                                @if($transfer->status === 'completed')
                                    <span class="status good">✓ مكتمل</span>
                                @elseif($transfer->status === 'draft')
                                    <span class="status" style="background:rgba(148,163,184,0.12);color:#94a3b8;">مسودة</span>
                                @elseif($transfer->status === 'cancelled')
                                    <span class="status bad">✗ ملغي</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex; gap:6px;">
                                    <a href="{{ route('treasury-bank-transfers.show', $transfer->id) }}" class="btn" style="padding:6px 10px;font-size:12px;">عرض</a>
                                    <form method="POST" action="{{ route('treasury-bank-transfers.destroy', $transfer->id) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذا التحويل؟')">
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
