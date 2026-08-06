@extends('layouts.app')

@section('title', 'Axionyx ERP Dashboard')
@section('page_title', 'ERP Dashboard')
@section('page_subtitle', 'نظرة عامة على التحويلات والمدفوعات')

@section('content')
<div class="grid grid-4">
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
                <div class="muted">مدفوعات الموردين</div>
                <div class="value">{{ number_format($totalBankToSupplier, 2) }}</div>
                <div class="trend">جنيه</div>
            </div>
            <span class="chip" style="background:rgba(245,158,11,0.12);color:#fde68a;border-color:rgba(245,158,11,0.18);">💰</span>
        </div>
    </article>

    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">إجمالي التحويلات</div>
                <div class="value">{{ $treasuryBankTransfers->count() + $bankSupplierPayments->count() }}</div>
                <div class="trend">عملية</div>
            </div>
            <span class="chip">All</span>
        </div>
    </article>
</div>

<div class="grid grid-2">
    <article class="panel">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <h2>تحويلات الخزنة ↔ البنك</h2>
            <a href="{{ route('treasury-bank-transfers.create') }}" class="btn primary" style="padding:6px 10px;font-size:12px;">+ جديد</a>
        </div>

        <div style="display:flex; gap:8px; margin-bottom:12px; flex-wrap:wrap;">
            <select id="filter_tb_type" onchange="filterTable('tb')" style="background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:6px 8px;color:#fff;font-size:12px;">
                <option value="">كل الأنواع</option>
                <option value="treasury_to_bank">من الخزنة للبنك</option>
                <option value="bank_to_treasury">من البنك للخزنة</option>
            </select>
            <select id="filter_tb_status" onchange="filterTable('tb')" style="background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:6px 8px;color:#fff;font-size:12px;">
                <option value="">كل الحالات</option>
                <option value="completed">مكتمل</option>
                <option value="draft">مسودة</option>
                <option value="cancelled">ملغي</option>
            </select>
        </div>

        @if($treasuryBankTransfers->isEmpty())
            <div style="text-align:center; padding:30px; color:var(--muted);">
                <div style="font-size:36px; margin-bottom:8px;">💸</div>
                <div>لا توجد تحويلات</div>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table id="table_tb">
                    <thead>
                        <tr>
                            <th>الرقم</th>
                            <th>النوع</th>
                            <th>الخزنة</th>
                            <th>البنك</th>
                            <th>المبلغ</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($treasuryBankTransfers as $t)
                            <tr data-type="{{ $t->transfer_type }}" data-status="{{ $t->status }}">
                                <td><a href="{{ route('treasury-bank-transfers.show', $t->id) }}" style="color:var(--accent);">{{ $t->transfer_no }}</a></td>
                                <td>
                                    @if($t->transfer_type === 'treasury_to_bank')
                                        <span class="status good" style="font-size:11px;">خزنة←بنك</span>
                                    @else
                                        <span class="status" style="font-size:11px;background:rgba(56,189,248,0.12);color:#e0f2fe;border-color:rgba(56,189,248,0.16);">بنك←خزنة</span>
                                    @endif
                                </td>
                                <td>{{ $t->treasury->name ?? '-' }}</td>
                                <td>{{ $t->bankAccount->bank_name ?? '-' }}</td>
                                <td><strong>{{ number_format($t->amount, 2) }}</strong></td>
                                <td>
                                    @if($t->status === 'completed')
                                        <span class="status good" style="font-size:11px;">✓</span>
                                    @elseif($t->status === 'draft')
                                        <span class="status" style="font-size:11px;background:rgba(148,163,184,0.12);color:#94a3b8;">مسودة</span>
                                    @else
                                        <span class="status bad" style="font-size:11px;">✗</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="text-align:center; margin-top:10px;">
                <a href="{{ route('treasury-bank-transfers.index') }}" style="color:var(--accent);font-size:13px;">عرض الكل ←</a>
            </div>
        @endif
    </article>

    <article class="panel">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <h2>مدفوعات الموردين من البنك</h2>
            <a href="{{ route('bank-supplier-payments.create') }}" class="btn primary" style="padding:6px 10px;font-size:12px;">+ جديد</a>
        </div>

        <div style="display:flex; gap:8px; margin-bottom:12px; flex-wrap:wrap;">
            <select id="filter_bp_status" onchange="filterTable('bp')" style="background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:6px 8px;color:#fff;font-size:12px;">
                <option value="">كل الحالات</option>
                <option value="completed">مكتمل</option>
                <option value="draft">مسودة</option>
                <option value="cancelled">ملغي</option>
            </select>
        </div>

        @if($bankSupplierPayments->isEmpty())
            <div style="text-align:center; padding:30px; color:var(--muted);">
                <div style="font-size:36px; margin-bottom:8px;">🏦</div>
                <div>لا توجد مدفوعات</div>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table id="table_bp">
                    <thead>
                        <tr>
                            <th>الرقم</th>
                            <th>المورد</th>
                            <th>البنك</th>
                            <th>المبلغ</th>
                            <th>التاريخ</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bankSupplierPayments as $p)
                            <tr data-status="{{ $p->status }}">
                                <td><a href="{{ route('bank-supplier-payments.show', $p->id) }}" style="color:var(--accent);">{{ $p->payment_no }}</a></td>
                                <td>{{ $p->supplier->supplier_name ?? '-' }}</td>
                                <td>{{ $p->bankAccount->bank_name ?? '-' }}</td>
                                <td><strong>{{ number_format($p->amount, 2) }}</strong></td>
                                <td>{{ $p->payment_date?->format('Y-m-d') ?? '-' }}</td>
                                <td>
                                    @if($p->status === 'completed')
                                        <span class="status good" style="font-size:11px;">✓</span>
                                    @elseif($p->status === 'draft')
                                        <span class="status" style="font-size:11px;background:rgba(148,163,184,0.12);color:#94a3b8;">مسودة</span>
                                    @else
                                        <span class="status bad" style="font-size:11px;">✗</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="text-align:center; margin-top:10px;">
                <a href="{{ route('bank-supplier-payments.index') }}" style="color:var(--accent);font-size:13px;">عرض الكل ←</a>
            </div>
        @endif
    </article>
</div>

<script>
function filterTable(prefix) {
    if (prefix === 'tb') {
        const type = document.getElementById('filter_tb_type').value;
        const status = document.getElementById('filter_tb_status').value;
        document.querySelectorAll('#table_tb tbody tr').forEach(row => {
            const matchType = !type || row.dataset.type === type;
            const matchStatus = !status || row.dataset.status === status;
            row.style.display = (matchType && matchStatus) ? '' : 'none';
        });
    } else if (prefix === 'bp') {
        const status = document.getElementById('filter_bp_status').value;
        document.querySelectorAll('#table_bp tbody tr').forEach(row => {
            row.style.display = (!status || row.dataset.status === status) ? '' : 'none';
        });
    }
}
</script>
@endsection
