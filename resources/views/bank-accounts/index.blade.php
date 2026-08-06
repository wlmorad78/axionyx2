@extends('layouts.app')

@section('title', 'حسابات البنوك - Axionyx ERP')
@section('page_title', 'حسابات البنوك')
@section('page_subtitle', 'إدارة حسابات البنوك الخاصة بالشركة')

@section('content')
<div class="grid grid-4">
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">إجمالي الحسابات</div>
                <div class="value">{{ $bankAccounts->total() }}</div>
                <div class="trend">حساب بنكي</div>
            </div>
            <span class="chip">All</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">حسابات نشطة</div>
                <div class="value">{{ \App\Models\BankAccount::where('is_active', true)->count() }}</div>
                <div class="trend">قيد التشغيل</div>
            </div>
            <span class="chip good">Active</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">إجمالي الأرصدة</div>
                <div class="value">{{ number_format(\App\Models\BankAccount::sum('current_balance'), 2) }}</div>
                <div class="trend">رصيد إجمالي</div>
            </div>
            <span class="chip">Balance</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">آخر تحديث</div>
                <div class="value" style="font-size:18px;">{{ \App\Models\BankAccount::max('updated_at') ? \Carbon\Carbon::parse(\App\Models\BankAccount::max('updated_at'))->format('Y-m-d') : '—' }}</div>
                <div class="trend">تاريخ آخر نشاط</div>
            </div>
            <span class="chip">Date</span>
        </div>
    </article>
</div>

<article class="panel">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h2>قائمة حسابات البنوك</h2>
        <div style="display:flex; gap:8px;">
            <form method="GET" style="display:flex; gap:8px; align-items:center;">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="بحث باسم البنك أو رقم الحساب..." style="background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:8px 12px;color:#fff;font-size:13px;">
                <select name="is_active" style="background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:8px 12px;color:#fff;font-size:13px;">
                    <option value="">جميع الحالات</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>نشط</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>غير نشط</option>
                </select>
                <button type="submit" class="btn">بحث</button>
            </form>
            <a href="{{ route('bank-accounts.create') }}" class="btn primary">+ حساب بنكي جديد</a>
        </div>
    </div>

    @if(session('success'))
        <div style="padding:12px 16px;border-radius:10px;background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.18);color:#bbf7d0;margin-bottom:16px;font-size:14px;">
            {{ session('success') }}
        </div>
    @endif

    @if($bankAccounts->isEmpty())
        <div style="text-align:center; padding:40px; color:var(--muted);">
            <p style="font-size:18px;">لا توجد حسابات بنوك حالياً</p>
            <p style="font-size:13px;">اضغط على "حساب بنكي جديد" لإنشاء أول حساب</p>
        </div>
    @else
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>اسم البنك</th>
                        <th>رقم الحساب</th>
                        <th>الفرع</th>
                        <th>العملة</th>
                        <th>الرصيد الحالي</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bankAccounts as $index => $account)
                        <tr>
                            <td>{{ ($bankAccounts->currentPage() - 1) * $bankAccounts->perPage() + $index + 1 }}</td>
                            <td><strong style="color:var(--accent);">{{ $account->bank_name }}</strong></td>
                            <td>{{ $account->account_number ?? '—' }}</td>
                            <td>{{ $account->branch_name ?? '—' }}</td>
                            <td>{{ $account->currency?->name ?? '—' }}</td>
                            <td>{{ number_format($account->current_balance, 2) }}</td>
                            <td>
                                @if($account->is_active)
                                    <span class="status good">✓ نشط</span>
                                @else
                                    <span class="status bad">✗ غير نشط</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex; gap:6px;">
                                    <a href="{{ route('bank-accounts.show', $account->id) }}" class="btn" style="padding:6px 10px;font-size:12px;">عرض</a>
                                    <a href="{{ route('bank-accounts.edit', $account->id) }}" class="btn" style="padding:6px 10px;font-size:12px;">تعديل</a>
                                    <form method="POST" action="{{ route('bank-accounts.destroy', $account->id) }}" onsubmit="return confirm('هل أنت متأكد من الحذف؟')">
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
            {{ $bankAccounts->withQueryString()->links() }}
        </div>
    @endif
</article>
@endsection
