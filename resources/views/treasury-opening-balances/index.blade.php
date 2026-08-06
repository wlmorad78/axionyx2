@extends('layouts.app')

@section('title', 'أرصدة افتتاحية الخزنة - Axionyx ERP')
@section('page_title', 'أرصدة افتتاحية الخزنة')
@section('page_subtitle', 'إدارة أرصدة الافتتاحية للخزنة')

@section('content')
<div class="grid grid-3">
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">إجمالي السجلات</div>
                <div class="value">{{ $stats['total'] }}</div>
                <div class="trend">خزنة مسجلة</div>
            </div>
            <span class="chip">All</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">خزنة برصيد</div>
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

<!-- شريط البحث والفلاتر -->
<div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap;">
    <form method="GET" id="searchForm" style="display:flex;gap:8px;align-items:center;flex:1;">
        <input type="hidden" name="treasury_id" id="filterTreasuryId" value="{{ request('treasury_id') }}">
        <input type="hidden" name="fiscal_year_id" id="filterFiscalYearId" value="{{ request('fiscal_year_id') }}">
        <input type="hidden" name="min_balance" id="filterMinBalance" value="{{ request('min_balance') }}">
        <input type="hidden" name="max_balance" id="filterMaxBalance" value="{{ request('max_balance') }}">
        
        <div style="position:relative;flex:1;max-width:400px;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="بحث باسم الخزنة أو الكود..." 
                style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px 10px 36px;color:#fff;font-size:13px;">
            <span style="position:absolute;right:12px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:14px;">🔍</span>
        </div>
        <button type="submit" class="btn" style="padding:10px 16px;">بحث</button>
        <button type="button" onclick="openFilterModal()" class="btn" style="padding:10px 16px;background:rgba(56,189,248,0.12);border-color:rgba(56,189,248,0.25);color:#e0f2fe;">
            الفلاتر
        </button>
    </form>
    
    <a href="{{ route('treasury-opening-balances.create') }}" class="btn primary" style="font-size:13px;padding:10px 16px;">+ إضافة رصيد افتتاحي</a>
</div>

<!-- الفلاتر النشطة -->
@if(hasActiveFilters())
<div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
    <span style="font-size:12px;color:var(--muted);">الفلاتر النشطة:</span>
    
    @if(request('treasury_id'))
        <span class="filter-chip">
            الخزنة: {{ $treasuries->find(request('treasury_id'))?->name_ar ?? request('treasury_id') }}
            <button type="button" onclick="removeFilter('treasury_id')" class="filter-chip-close">&times;</button>
        </span>
    @endif
    
    @if(request('fiscal_year_id'))
        <span class="filter-chip">
            السنة المالية: {{ $fiscalYears->find(request('fiscal_year_id'))?->year ?? request('fiscal_year_id') }}
            <button type="button" onclick="removeFilter('fiscal_year_id')" class="filter-chip-close">&times;</button>
        </span>
    @endif
    
    @if(request('min_balance'))
        <span class="filter-chip">
            الحد الأدنى: {{ number_format(request('min_balance'), 2) }}
            <button type="button" onclick="removeFilter('min_balance')" class="filter-chip-close">&times;</button>
        </span>
    @endif
    
    @if(request('max_balance'))
        <span class="filter-chip">
            الحد الأقصى: {{ number_format(request('max_balance'), 2) }}
            <button type="button" onclick="removeFilter('max_balance')" class="filter-chip-close">&times;</button>
        </span>
    @endif
    
    <button type="button" onclick="clearAllFilters()" class="filter-chip" style="background:rgba(251,113,133,0.12);border-color:rgba(251,113,133,0.25);color:#fecdd3;">
        مسح الكل ✕
    </button>
</div>
@endif

<article class="panel">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h2>قائمة أرصدة افتتاحية الخزنة</h2>
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
            <p style="font-size:18px;">لا توجد أرصدة افتتاحية للخزنة</p>
            <p style="font-size:13px;">ابدأ بإضافة رصيد افتتاحي لأحدى الخزنة</p>
            <a href="{{ route('treasury-opening-balances.create') }}" class="btn primary" style="font-size:13px;margin-top:16px;">+ إضافة رصيد افتتاحي</a>
        </div>
    @else
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>كود الخزنة</th>
                        <th>اسم الخزنة</th>
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
                            <td><strong style="color:var(--accent);">{{ $ob->treasury?->code ?? '—' }}</strong></td>
                            <td>{{ $ob->treasury?->name_ar ?? '—' }}</td>
                            <td>{{ $ob->fiscalYear?->year ?? '—' }}</td>
                            <td style="font-weight:700;color:var(--primary);">{{ number_format($ob->opening_balance, 2) }}</td>
                            <td style="font-weight:700;color:{{ ($ob->treasury->balance ?? 0) >= 0 ? 'var(--primary)' : 'var(--danger)' }};">
                                {{ number_format($ob->treasury->balance ?? 0, 2) }}
                            </td>
                            <td>
                                <div style="display:flex; gap:6px;">
                                    <a href="{{ route('treasury-opening-balances.show', $ob->id) }}" class="btn" style="padding:6px 10px;font-size:12px;">عرض</a>
                                    <a href="{{ route('treasury-opening-balances.edit', $ob->id) }}" class="btn" style="padding:6px 10px;font-size:12px;">تعديل</a>
                                    <form method="POST" action="{{ route('treasury-opening-balances.destroy', $ob->id) }}" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من الحذف؟ سيتم تصفير رصيد الخزنة.')">
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

<!-- نافذة الفلاتر المنبثقة -->
<div id="filterModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:1000;justify-content:center;align-items:center;">
    <div style="background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:24px;width:90%;max-width:650px;max-height:85vh;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 style="margin:0;color:var(--text);font-size:18px;">الفلاتر</h3>
            <button onclick="closeFilterModal()" style="background:none;border:none;color:var(--muted);font-size:24px;cursor:pointer;padding:4px 8px;">&times;</button>
        </div>
        
        <form id="filterForm">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <!-- الخزنة -->
                <div>
                    <label style="display:block;margin-bottom:8px;font-size:13px;color:var(--muted);">الخزنة</label>
                    <select id="modalTreasuryId" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
                        <option value="">جميع الخزنة</option>
                        @foreach($treasuries as $treasury)
                            <option value="{{ $treasury->id }}" {{ request('treasury_id') == $treasury->id ? 'selected' : '' }}>{{ $treasury->name_ar }}</option>
                        @endforeach
                    </select>
                </div>
                
                <!-- السنة المالية -->
                <div>
                    <label style="display:block;margin-bottom:8px;font-size:13px;color:var(--muted);">السنة المالية</label>
                    <select id="modalFiscalYearId" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
                        <option value="">جميع السنوات</option>
                        @foreach($fiscalYears as $year)
                            <option value="{{ $year->id }}" {{ request('fiscal_year_id') == $year->id ? 'selected' : '' }}>{{ $year->year_code }}</option>
                        @endforeach
                    </select>
                </div>
                
                <!-- الحد الأدنى للرصيد -->
                <div>
                    <label style="display:block;margin-bottom:8px;font-size:13px;color:var(--muted);">الحد الأدنى للرصيد</label>
                    <input type="number" id="modalMinBalance" value="{{ request('min_balance') }}" step="0.01" min="0" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;" placeholder="0.00">
                </div>
                
                <!-- الحد الأقصى للرصيد -->
                <div>
                    <label style="display:block;margin-bottom:8px;font-size:13px;color:var(--muted);">الحد الأقصى للرصيد</label>
                    <input type="number" id="modalMaxBalance" value="{{ request('max_balance') }}" step="0.01" min="0" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;" placeholder="0.00">
                </div>
            </div>
            
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;padding-top:16px;border-top:1px solid var(--line);">
                <button type="button" onclick="clearModalFilters()" class="btn" style="padding:10px 20px;background:rgba(251,113,133,0.12);border-color:rgba(251,113,133,0.25);color:#fecdd3;">
                    مسح الفلاتر
                </button>
                <button type="button" onclick="applyFilters()" class="btn primary" style="padding:10px 20px;">
                    تطبيق الفلاتر
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .filter-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        background: rgba(56,189,248,0.12);
        color: #e0f2fe;
        border: 1px solid rgba(56,189,248,0.25);
        cursor: default;
    }
    .filter-chip-close {
        background: none;
        border: none;
        color: #e0f2fe;
        cursor: pointer;
        font-size: 14px;
        padding: 0;
        margin-right: -4px;
        opacity: 0.7;
    }
    .filter-chip-close:hover {
        opacity: 1;
    }
</style>

<script>
function openFilterModal() {
    document.getElementById('filterModal').style.display = 'flex';
}

function closeFilterModal() {
    document.getElementById('filterModal').style.display = 'none';
}

function applyFilters() {
    document.getElementById('filterTreasuryId').value = document.getElementById('modalTreasuryId').value;
    document.getElementById('filterFiscalYearId').value = document.getElementById('modalFiscalYearId').value;
    document.getElementById('filterMinBalance').value = document.getElementById('modalMinBalance').value;
    document.getElementById('filterMaxBalance').value = document.getElementById('modalMaxBalance').value;
    
    document.getElementById('searchForm').submit();
    closeFilterModal();
}

function clearModalFilters() {
    document.getElementById('modalTreasuryId').value = '';
    document.getElementById('modalFiscalYearId').value = '';
    document.getElementById('modalMinBalance').value = '';
    document.getElementById('modalMaxBalance').value = '';
}

function removeFilter(param) {
    const url = new URL(window.location);
    url.searchParams.delete(param);
    window.location.href = url.toString();
}

function clearAllFilters() {
    window.location.href = '{{ route("treasury-opening-balances.index") }}';
}
</script>
@endsection
