@extends('layouts.app')

@section('title', 'التسويات البنكية - Axionyx ERP')
@section('page_title', 'التسويات البنكية')
@section('page_subtitle', 'إدارة تسويات الحسابات البنكية')

@section('content')
<div class="grid grid-4">
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">إجمالي التسويات</div>
                <div class="value">{{ $reconciliations->total() }}</div>
                <div class="trend">تسوية</div>
            </div>
            <span class="chip">All</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">آخر تسوية</div>
                <div class="value" style="font-size:16px;">{{ \App\Models\BankReconciliation::max('reconciliation_date') ?? '—' }}</div>
                <div class="trend">تاريخ آخر تسوية</div>
            </div>
            <span class="chip">Date</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">إجمالي الفروق</div>
                <div class="value">{{ number_format(\App\Models\BankReconciliation::sum('difference'), 2) }}</div>
                <div class="trend">مجموع الفروقات</div>
            </div>
            <span class="chip">Diff</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">متوسط الفرق</div>
                <div class="value">{{ number_format(\App\Models\BankReconciliation::avg('difference') ?? 0, 2) }}</div>
                <div class="trend">لكل تسوية</div>
            </div>
            <span class="chip">Avg</span>
        </div>
    </article>
</div>

<article class="panel">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h2>قائمة التسويات البنكية</h2>
        <div style="display:flex; gap:8px;">
            <form method="GET" style="display:flex; gap:8px; align-items:center;">
                <select name="status" style="background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:8px 12px;color:#fff;font-size:13px;">
                    <option value="">جميع الحالات</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>مكتمل</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>مسودة</option>
                </select>
                <button type="submit" class="btn">بحث</button>
            </form>
            <a href="{{ route('bank-reconciliations.create') }}" class="btn primary">+ تسوية جديدة</a>
        </div>
    </div>

    @if(session('success'))
        <div style="padding:12px 16px;border-radius:10px;background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.18);color:#bbf7d0;margin-bottom:16px;font-size:14px;">
            {{ session('success') }}
        </div>
    @endif

    @if($reconciliations->isEmpty())
        <div style="text-align:center; padding:40px; color:var(--muted);">
            <p style="font-size:18px;">لا توجد تسويات بنكية حالياً</p>
            <p style="font-size:13px;">اضغط على "تسوية جديدة" لإنشاء أول تسوية</p>
        </div>
    @else
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الحساب البنكي</th>
                        <th>تاريخ التسوية</th>
                        <th>رصيد كشف الحساب</th>
                        <th>رصيد النظام</th>
                        <th>الفرق</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reconciliations as $index => $rec)
                        <tr>
                            <td>{{ ($reconciliations->currentPage() - 1) * $reconciliations->perPage() + $index + 1 }}</td>
                            <td><strong style="color:var(--accent);">{{ $rec->bankAccount?->bank_name ?? '—' }}</strong></td>
                            <td>{{ $rec->reconciliation_date }}</td>
                            <td>{{ number_format($rec->statement_balance, 2) }}</td>
                            <td>{{ number_format($rec->system_balance ?? $rec->book_balance, 2) }}</td>
                            <td>
                                <span style="color:{{ $rec->difference >= 0 ? 'var(--primary)' : 'var(--danger)' }};">
                                    {{ number_format($rec->difference, 2) }}
                                </span>
                            </td>
                            <td>
                                @if($rec->status === 'completed')
                                    <span class="status good">✓ مكتمل</span>
                                @else
                                    <span class="status warn">⏳ {{ $rec->status }}</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex; gap:6px;">
                                    <a href="{{ route('bank-reconciliations.show', $rec->id) }}" class="btn" style="padding:6px 10px;font-size:12px;">عرض</a>
                                    <form method="POST" action="{{ route('bank-reconciliations.destroy', $rec->id) }}" onsubmit="return confirm('هل أنت متأكد من الحذف؟')">
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
            {{ $reconciliations->withQueryString()->links() }}
        </div>
    @endif
</article>
@endsection
