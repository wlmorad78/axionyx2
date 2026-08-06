@extends('layouts.app')

@section('title', 'أرصدة افتتاحية البنوك - Axionyx ERP')
@section('page_title', 'أرصدة افتتاحية البنوك')
@section('page_subtitle', 'إدارة أرصدة الافتتاحية للبنوك')

@section('content')
<div class="grid grid-3">
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">إجمالي السجلات</div>
                <div class="value">{{ $stats['total'] }}</div>
                <div class="trend">حسابات بنكية مسجلة</div>
            </div>
            <span class="chip">All</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">حسابات برصيد</div>
                <div class="value">{{ $stats['with_balance'] }}</div>
                <div class="trend">لديها رصيد افتتاحي</div>
            </div>
            <span class="chip good">Active</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">إجمالي الأرصدة</div>
                <div class="value">{{ number_format($stats['total_amount'], 2) }}</div>
                <div class="trend">مبلغ الأرصدة الافتتاحية</div>
            </div>
            <span class="chip" style="background:rgba(56,189,248,0.12);color:#e0f2fe;border-color:rgba(56,189,248,0.18);">Total</span>
        </div>
    </article>
</div>

<article class="panel">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h2>قائمة أرصدة افتتاحية البنوك</h2>
        <div style="display:flex; gap:8px;">
            <form method="GET" id="searchForm" style="display:flex; gap:8px; align-items:center;">
                <input type="hidden" name="bank_account_id" id="filterBankAccountId" value="{{ request('bank_account_id') }}">
                <input type="hidden" name="fiscal_year_id" id="filterFiscalYearId" value="{{ request('fiscal_year_id') }}">
                <input type="hidden" name="min_balance" id="filterMinBalance" value="{{ request('min_balance') }}">
                <input type="hidden" name="max_balance" id="filterMaxBalance" value="{{ request('max_balance') }}">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="بحث باسم البنك أو رقم الحساب..." style="background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:8px 12px;color:#fff;font-size:13px;">
                <button type="submit" class="btn">بحث</button>
                <button type="button" onclick="openFilterModal()" class="btn" style="background:rgba(56,189,248,0.12);border-color:rgba(56,189,248,0.25);color:#e0f2fe;">
                    <span style="margin-left:4px;">&#128269;</span> فلاتر
                </button>
                @if(hasActiveFilters())
                <button type="button" onclick="clearAllFilters()" class="btn" style="background:rgba(251,113,133,0.12);border-color:rgba(251,113,133,0.25);color:#fecdd3;">
                    مسح الفلاتر
                </button>
                @endif
            </form>
            <a href="{{ route('bank-opening-balances.create') }}" class="btn primary" style="font-size:13px;padding:8px 16px;">+ إضافة رصيد افتتاحي</a>
        </div>
    </div>

    @if(session('success'))
        <div style="padding:12px 16px;border-radius:10px;background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.18);color:#bbf7d0;margin-bottom:16px;font-size:14px;">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div style="padding:12px 16px;border-radius:10px;background:rgba(251,113,133,0.12);border:1px solid rgba(251,113,133,0.18);color:#fecdd3;margin-bottom:16px;font-size:14px;">
            {{ session('error') }}
        </div>
    @endif

    @if($openingBalances->isEmpty())
        <div style="text-align:center; padding:40px; color:var(--muted);">
            <p style="font-size:18px;">لا توجد أرصدة افتتاحية للبنوك</p>
            <p style="font-size:13px;">ابدأ بإضافة رصيد افتتاحي لأحدى حسابات البنوك</p>
            <a href="{{ route('bank-opening-balances.create') }}" class="btn primary" style="font-size:13px;margin-top:16px;">+ إضافة رصيد افتتاحي</a>
        </div>
    @else
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>اسم البنك</th>
                        <th>اسم الحساب</th>
                        <th>رقم الحساب</th>
                        <th>السنة المالية</th>
                        <th>الرصيد الافتتاحي</th>
                        <th>الرصيد الحالي</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($openingBalances as $index => $ob)
                        <tr>
                            <td>{{ ($openingBalances->currentPage() - 1) * $openingBalances->perPage() + $index + 1 }}</td>
                            <td><strong style="color:var(--accent);">{{ $ob->bankAccount?->bank_name ?? '—' }}</strong></td>
                            <td>{{ $ob->bankAccount?->account_name ?? '—' }}</td>
                            <td>{{ $ob->bankAccount?->account_number ?? '—' }}</td>
                            <td>{{ $ob->fiscalYear?->year ?? '—' }}</td>
                            <td style="font-weight:700;color:var(--primary);">{{ number_format($ob->opening_balance, 2) }}</td>
                            <td style="font-weight:700;color:{{ ($ob->bankAccount->current_balance ?? 0) >= 0 ? 'var(--primary)' : 'var(--danger)' }};">
                                {{ number_format($ob->bankAccount->current_balance ?? 0, 2) }}
                            </td>
                            <td>
                                <div style="display:flex; gap:6px;">
                                    <a href="{{ route('bank-opening-balances.show', $ob->id) }}" class="btn" style="padding:6px 10px;font-size:12px;">عرض</a>
                                    <a href="{{ route('bank-opening-balances.edit', $ob->id) }}" class="btn" style="padding:6px 10px;font-size:12px;">تعديل</a>
                                    <form method="POST" action="{{ route('bank-opening-balances.destroy', $ob->id) }}" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من الحذف؟ سيتم تصفير رصيد البنك.')">
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
            {{ $openingBalances->withQueryString()->links() }}
        </div>
    @endif
</article>

<!-- Filter Modal -->
<div id="filterModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:1000;justify-content:center;align-items:center;">
    <div style="background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:24px;width:90%;max-width:600px;max-height:80vh;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 style="margin:0;color:var(--text);">الفلاتر المتقدمة</h3>
            <button onclick="closeFilterModal()" style="background:none;border:none;color:var(--muted);font-size:24px;cursor:pointer;">&times;</button>
        </div>
        
        <form id="filterForm">
            <div class="grid grid-2" style="gap:16px;">
                <div>
                    <label style="display:block;margin-bottom:6px;font-size:13px;color:var(--muted);">البنك / الحساب</label>
                    <select id="modalBankAccountId" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
                        <option value="">جميع الحسابات البنكية</option>
                        @foreach($bankAccounts as $account)
                            <option value="{{ $account->id }}" {{ request('bank_account_id') == $account->id ? 'selected' : '' }}>{{ $account->bank_name }} - {{ $account->account_name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label style="display:block;margin-bottom:6px;font-size:13px;color:var(--muted);">السنة المالية</label>
                    <select id="modalFiscalYearId" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
                        <option value="">جميع السنوات</option>
                        @foreach($fiscalYears as $year)
                            <option value="{{ $year->id }}" {{ request('fiscal_year_id') == $year->id ? 'selected' : '' }}>{{ $year->year_code }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label style="display:block;margin-bottom:6px;font-size:13px;color:var(--muted);">الحد الأدنى للرصيد</label>
                    <input type="number" id="modalMinBalance" value="{{ request('min_balance') }}" step="0.01" min="0" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;" placeholder="0.00">
                </div>
                
                <div>
                    <label style="display:block;margin-bottom:6px;font-size:13px;color:var(--muted);">الحد الأقصى للرصيد</label>
                    <input type="number" id="modalMaxBalance" value="{{ request('max_balance') }}" step="0.01" min="0" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;" placeholder="0.00">
                </div>
            </div>
            
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:20px;">
                <button type="button" onclick="clearModalFilters()" class="btn" style="background:rgba(251,113,133,0.12);border-color:rgba(251,113,133,0.25);color:#fecdd3;">
                    مسح الفلاتر
                </button>
                <button type="button" onclick="applyFilters()" class="btn primary">
                    تطبيق الفلاتر
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openFilterModal() {
    document.getElementById('filterModal').style.display = 'flex';
}

function closeFilterModal() {
    document.getElementById('filterModal').style.display = 'none';
}

function applyFilters() {
    document.getElementById('filterBankAccountId').value = document.getElementById('modalBankAccountId').value;
    document.getElementById('filterFiscalYearId').value = document.getElementById('modalFiscalYearId').value;
    document.getElementById('filterMinBalance').value = document.getElementById('modalMinBalance').value;
    document.getElementById('filterMaxBalance').value = document.getElementById('modalMaxBalance').value;
    
    document.getElementById('searchForm').submit();
    closeFilterModal();
}

function clearModalFilters() {
    document.getElementById('modalBankAccountId').value = '';
    document.getElementById('modalFiscalYearId').value = '';
    document.getElementById('modalMinBalance').value = '';
    document.getElementById('modalMaxBalance').value = '';
}

function clearAllFilters() {
    window.location.href = '{{ route("bank-opening-balances.index") }}';
}

function hasActiveFilters() {
    return '{{ request('bank_account_id') }}' || 
           '{{ request('fiscal_year_id') }}' || 
           '{{ request('min_balance') }}' ||
           '{{ request('max_balance') }}';
}
</script>
@endsection
