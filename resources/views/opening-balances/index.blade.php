@extends('layouts.app')

@section('title', 'الأرصدة الافتتاحية - Axionyx ERP')
@section('page_title', 'الأرصدة الافتتاحية')
@section('page_subtitle', 'إدارة الأرصدة الافتتاحية للخزنة والبنك والمنتجات والموردين')

@section('content')
<div class="grid grid-4">
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">إجمالي القيود</div>
                <div class="value">{{ $stats['total'] }}</div>
                <div class="trend">قيد افتتاحي</div>
            </div>
            <span class="chip">All</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">قيد مسودة</div>
                <div class="value">{{ $stats['draft'] }}</div>
                <div class="trend">غير معتمد</div>
            </div>
            <span class="chip" style="background:rgba(245,158,11,0.12);color:#fde68a;border-color:rgba(245,158,11,0.18);">Draft</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">قيد معتمد</div>
                <div class="value">{{ $stats['posted'] }}</div>
                <div class="trend">تم الاعتماد</div>
            </div>
            <span class="chip good">Posted</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">قيد ملغي</div>
                <div class="value">{{ $stats['cancelled'] }}</div>
                <div class="trend">تم الإلغاء</div>
            </div>
            <span class="chip" style="background:rgba(251,113,133,0.12);color:#fecdd3;border-color:rgba(251,113,133,0.18);">Cancelled</span>
        </div>
    </article>
</div>

<!-- شريط البحث والفلاتر -->
<div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap;">
    <form method="GET" id="searchForm" style="display:flex;gap:8px;align-items:center;flex:1;">
        <input type="hidden" name="status" id="filterStatus" value="{{ request('status') }}">
        <input type="hidden" name="balance_type" id="filterBalanceType" value="{{ request('balance_type') }}">
        <input type="hidden" name="branch_id" id="filterBranchId" value="{{ request('branch_id') }}">
        <input type="hidden" name="date_from" id="filterDateFrom" value="{{ request('date_from') }}">
        <input type="hidden" name="date_to" id="filterDateTo" value="{{ request('date_to') }}">
        <input type="hidden" name="min_amount" id="filterMinAmount" value="{{ request('min_amount') }}">
        <input type="hidden" name="max_amount" id="filterMaxAmount" value="{{ request('max_amount') }}">
        <input type="hidden" name="account_id" id="filterAccountId" value="{{ request('account_id') }}">
        
        <div style="position:relative;flex:1;max-width:400px;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="بحث برقم القيد أو الملاحظات..." 
                style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px 10px 36px;color:#fff;font-size:13px;">
            <span style="position:absolute;right:12px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:14px;">🔍</span>
        </div>
        <button type="submit" class="btn" style="padding:10px 16px;">بحث</button>
        <button type="button" onclick="openFilterModal()" class="btn" style="padding:10px 16px;background:rgba(56,189,248,0.12);border-color:rgba(56,189,248,0.25);color:#e0f2fe;">
            الفلاتر
        </button>
    </form>
    
    <div style="display:flex;gap:4px;">
        <a href="{{ route('opening-balances.create', ['balance_type' => 'cash']) }}" class="btn" style="padding:8px 12px;font-size:12px;background:rgba(34,197,94,0.12);border-color:rgba(34,197,94,0.18);color:#bbf7d0;">+ خزنة</a>
        <a href="{{ route('opening-balances.create', ['balance_type' => 'accounts']) }}" class="btn" style="padding:8px 12px;font-size:12px;background:rgba(56,189,248,0.12);border-color:rgba(56,189,248,0.18);color:#e0f2fe;">+ بنك</a>
        <a href="{{ route('opening-balances.create', ['balance_type' => 'inventory']) }}" class="btn" style="padding:8px 12px;font-size:12px;background:rgba(168,85,247,0.12);border-color:rgba(168,85,247,0.18);color:#e9d5ff;">+ منتجات</a>
        <a href="{{ route('opening-balances.create', ['balance_type' => 'suppliers']) }}" class="btn" style="padding:8px 12px;font-size:12px;background:rgba(245,158,11,0.12);border-color:rgba(245,158,11,0.18);color:#fde68a;">+ موردين</a>
    </div>
</div>

<!-- الفلاتر النشطة -->
@if(hasActiveFilters())
<div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
    <span style="font-size:12px;color:var(--muted);">الفلاتر النشطة:</span>
    
    @if(request('status'))
        <span class="filter-chip">
            الحالة: @if(request('status') === 'draft') مسودة @elseif(request('status') === 'posted') معتمد @else ملغي @endif
            <button type="button" onclick="removeFilter('status')" class="filter-chip-close">&times;</button>
        </span>
    @endif
    
    @if(request('balance_type'))
        <span class="filter-chip">
            النوع: @if(request('balance_type') === 'cash') الخزنة @elseif(request('balance_type') === 'accounts') البنك @elseif(request('balance_type') === 'inventory') المنتجات @elseif(request('balance_type') === 'suppliers') الموردين @elseif(request('balance_type') === 'customers') العملاء @else الأصول @endif
            <button type="button" onclick="removeFilter('balance_type')" class="filter-chip-close">&times;</button>
        </span>
    @endif
    
    @if(request('branch_id'))
        <span class="filter-chip">
            الفرع: {{ $branches->find(request('branch_id'))?->name_ar ?? request('branch_id') }}
            <button type="button" onclick="removeFilter('branch_id')" class="filter-chip-close">&times;</button>
        </span>
    @endif
    
    @if(request('account_id'))
        <span class="filter-chip">
            الحساب: {{ $accounts->find(request('account_id'))?->name_ar ?? request('account_id') }}
            <button type="button" onclick="removeFilter('account_id')" class="filter-chip-close">&times;</button>
        </span>
    @endif
    
    @if(request('date_from'))
        <span class="filter-chip">
            من: {{ request('date_from') }}
            <button type="button" onclick="removeFilter('date_from')" class="filter-chip-close">&times;</button>
        </span>
    @endif
    
    @if(request('date_to'))
        <span class="filter-chip">
            إلى: {{ request('date_to') }}
            <button type="button" onclick="removeFilter('date_to')" class="filter-chip-close">&times;</button>
        </span>
    @endif
    
    @if(request('min_amount'))
        <span class="filter-chip">
            الحد الأدنى: {{ number_format(request('min_amount'), 2) }}
            <button type="button" onclick="removeFilter('min_amount')" class="filter-chip-close">&times;</button>
        </span>
    @endif
    
    @if(request('max_amount'))
        <span class="filter-chip">
            الحد الأقصى: {{ number_format(request('max_amount'), 2) }}
            <button type="button" onclick="removeFilter('max_amount')" class="filter-chip-close">&times;</button>
        </span>
    @endif
    
    <button type="button" onclick="clearAllFilters()" class="filter-chip" style="background:rgba(251,113,133,0.12);border-color:rgba(251,113,133,0.25);color:#fecdd3;">
        مسح الكل ✕
    </button>
</div>
@endif

<article class="panel">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h2>قائمة الأرصدة الافتتاحية</h2>
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

    @if($documents->isEmpty())
        <div style="text-align:center; padding:40px; color:var(--muted);">
            <p style="font-size:18px;">لا توجد قيود أرصدة افتتاحية</p>
            <p style="font-size:13px;">اختر نوع الرصيد المطلوب للبدء</p>
            <div style="display:flex; gap:8px; justify-content:center; margin-top:16px;">
                <a href="{{ route('opening-balances.create', ['balance_type' => 'cash']) }}" class="btn primary" style="font-size:13px;">+ رصيد خزنة</a>
                <a href="{{ route('opening-balances.create', ['balance_type' => 'accounts']) }}" class="btn primary" style="font-size:13px;">+ رصيد بنك</a>
                <a href="{{ route('opening-balances.create', ['balance_type' => 'inventory']) }}" class="btn primary" style="font-size:13px;">+ رصيد منتجات</a>
                <a href="{{ route('opening-balances.create', ['balance_type' => 'suppliers']) }}" class="btn primary" style="font-size:13px;">+ رصيد موردين</a>
            </div>
        </div>
    @else
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>رقم القيد</th>
                        <th>التاريخ</th>
                        <th>نوع الرصيد</th>
                        <th>الفرع</th>
                        <th>عدد الأسطر</th>
                        <th>إجمالي المدين</th>
                        <th>إجمالي الدائن</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($documents as $index => $doc)
                        @php
                            $totalDebit = $doc->lines->sum('debit');
                            $totalCredit = $doc->lines->sum('credit');
                        @endphp
                        <tr>
                            <td>{{ ($documents->currentPage() - 1) * $documents->perPage() + $index + 1 }}</td>
                            <td><strong style="color:var(--accent);">{{ $doc->document_no }}</strong></td>
                            <td>{{ $doc->document_date?->format('Y-m-d') ?? '—' }}</td>
                            <td>
                                @switch($doc->balance_type)
                                    @case('cash')
                                        <span style="padding:4px 8px;border-radius:6px;font-size:11px;background:rgba(34,197,94,0.12);color:#bbf7d0;">الخزنة</span>
                                        @break
                                    @case('accounts')
                                        <span style="padding:4px 8px;border-radius:6px;font-size:11px;background:rgba(56,189,248,0.12);color:#e0f2fe;">البنك</span>
                                        @break
                                    @case('inventory')
                                        <span style="padding:4px 8px;border-radius:6px;font-size:11px;background:rgba(168,85,247,0.12);color:#e9d5ff;">المنتجات</span>
                                        @break
                                    @case('suppliers')
                                        <span style="padding:4px 8px;border-radius:6px;font-size:11px;background:rgba(245,158,11,0.12);color:#fde68a;">الموردين</span>
                                        @break
                                    @case('customers')
                                        <span style="padding:4px 8px;border-radius:6px;font-size:11px;background:rgba(236,72,153,0.12);color:#fbcfe8;">العملاء</span>
                                        @break
                                    @case('assets')
                                        <span style="padding:4px 8px;border-radius:6px;font-size:11px;background:rgba(99,102,241,0.12);color:#c7d2fe;">الأصول</span>
                                        @break
                                @endswitch
                            </td>
                            <td>{{ $doc->branch?->name_ar ?? '—' }}</td>
                            <td>{{ $doc->lines->count() }}</td>
                            <td>{{ number_format($totalDebit, 2) }}</td>
                            <td>{{ number_format($totalCredit, 2) }}</td>
                            <td>
                                @if($doc->status === 'posted')
                                    <span class="status good">✓ معتمد</span>
                                @elseif($doc->status === 'cancelled')
                                    <span class="status bad">✗ ملغي</span>
                                @else
                                    <span class="status warn">◐ مسودة</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex; gap:6px;">
                                    <a href="{{ route('opening-balances.show', $doc->id) }}" class="btn" style="padding:6px 10px;font-size:12px;">عرض</a>
                                    @if($doc->status === 'draft')
                                        <a href="{{ route('opening-balances.edit', $doc->id) }}" class="btn" style="padding:6px 10px;font-size:12px;">تعديل</a>
                                        <form method="POST" action="{{ route('opening-balances.post', $doc->id) }}" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من اعتماد القيد؟')">
                                            @csrf
                                            <button type="submit" class="btn" style="padding:6px 10px;font-size:12px;color:var(--primary);border-color:rgba(34,197,94,0.3);">اعتماد</button>
                                        </form>
                                    @endif
                                    @if($doc->status === 'posted')
                                        <form method="POST" action="{{ route('opening-balances.cancel', $doc->id) }}" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من إلغاء الاعتماد؟')">
                                            @csrf
                                            <button type="submit" class="btn" style="padding:6px 10px;font-size:12px;color:var(--warn);border-color:rgba(245,158,11,0.3);">إلغاء</button>
                                        </form>
                                    @endif
                                    @if($doc->status !== 'posted')
                                        <form method="POST" action="{{ route('opening-balances.destroy', $doc->id) }}" onsubmit="return confirm('هل أنت متأكد من الحذف؟')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn" style="padding:6px 10px;font-size:12px;color:var(--danger);border-color:rgba(251,113,133,0.3);">حذف</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="display:flex; justify-content:center; margin-top:16px;">
            {{ $documents->withQueryString()->links() }}
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
                <!-- الحالة -->
                <div>
                    <label style="display:block;margin-bottom:8px;font-size:13px;color:var(--muted);">الحالة</label>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <button type="button" class="filter-option {{ request('status') === '' ? 'active' : '' }}" onclick="setFilterOption(this, 'modalStatus', '')">الكل</button>
                        <button type="button" class="filter-option {{ request('status') === 'draft' ? 'active' : '' }}" onclick="setFilterOption(this, 'modalStatus', 'draft')">مسودة</button>
                        <button type="button" class="filter-option {{ request('status') === 'posted' ? 'active' : '' }}" onclick="setFilterOption(this, 'modalStatus', 'posted')">معتمد</button>
                        <button type="button" class="filter-option {{ request('status') === 'cancelled' ? 'active' : '' }}" onclick="setFilterOption(this, 'modalStatus', 'cancelled')">ملغي</button>
                    </div>
                    <input type="hidden" id="modalStatus" value="{{ request('status') }}">
                </div>
                
                <!-- نوع الرصيد -->
                <div>
                    <label style="display:block;margin-bottom:8px;font-size:13px;color:var(--muted);">نوع الرصيد</label>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <button type="button" class="filter-option {{ request('balance_type') === '' ? 'active' : '' }}" onclick="setFilterOption(this, 'modalBalanceType', '')">الكل</button>
                        <button type="button" class="filter-option {{ request('balance_type') === 'cash' ? 'active' : '' }}" onclick="setFilterOption(this, 'modalBalanceType', 'cash')">الخزنة</button>
                        <button type="button" class="filter-option {{ request('balance_type') === 'accounts' ? 'active' : '' }}" onclick="setFilterOption(this, 'modalBalanceType', 'accounts')">البنك</button>
                        <button type="button" class="filter-option {{ request('balance_type') === 'inventory' ? 'active' : '' }}" onclick="setFilterOption(this, 'modalBalanceType', 'inventory')">المنتجات</button>
                        <button type="button" class="filter-option {{ request('balance_type') === 'suppliers' ? 'active' : '' }}" onclick="setFilterOption(this, 'modalBalanceType', 'suppliers')">الموردين</button>
                        <button type="button" class="filter-option {{ request('balance_type') === 'customers' ? 'active' : '' }}" onclick="setFilterOption(this, 'modalBalanceType', 'customers')">العملاء</button>
                        <button type="button" class="filter-option {{ request('balance_type') === 'assets' ? 'active' : '' }}" onclick="setFilterOption(this, 'modalBalanceType', 'assets')">الأصول</button>
                    </div>
                    <input type="hidden" id="modalBalanceType" value="{{ request('balance_type') }}">
                </div>
                
                <!-- الفرع -->
                <div>
                    <label style="display:block;margin-bottom:8px;font-size:13px;color:var(--muted);">الفرع</label>
                    <select id="modalBranchId" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
                        <option value="">جميع الفروع</option>
                        @if(isset($branches))
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name_ar }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                
                <!-- الحساب -->
                <div>
                    <label style="display:block;margin-bottom:8px;font-size:13px;color:var(--muted);">الحساب</label>
                    <select id="modalAccountId" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
                        <option value="">جميع الحسابات</option>
                        @if(isset($accounts))
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" {{ request('account_id') == $account->id ? 'selected' : '' }}>{{ $account->code }} - {{ $account->name_ar }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                
                <!-- التاريخ -->
                <div>
                    <label style="display:block;margin-bottom:8px;font-size:13px;color:var(--muted);">من تاريخ</label>
                    <input type="date" id="modalDateFrom" value="{{ request('date_from') }}" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
                </div>
                
                <div>
                    <label style="display:block;margin-bottom:8px;font-size:13px;color:var(--muted);">إلى تاريخ</label>
                    <input type="date" id="modalDateTo" value="{{ request('date_to') }}" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
                </div>
                
                <!-- المبلغ -->
                <div>
                    <label style="display:block;margin-bottom:8px;font-size:13px;color:var(--muted);">الحد الأدنى للمبلغ</label>
                    <input type="number" id="modalMinAmount" value="{{ request('min_amount') }}" step="0.01" min="0" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;" placeholder="0.00">
                </div>
                
                <div>
                    <label style="display:block;margin-bottom:8px;font-size:13px;color:var(--muted);">الحد الأقصى للمبلغ</label>
                    <input type="number" id="modalMaxAmount" value="{{ request('max_amount') }}" step="0.01" min="0" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;" placeholder="0.00">
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
    .filter-option {
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 12px;
        background: #0b1220;
        border: 1px solid var(--line);
        color: var(--muted);
        cursor: pointer;
        transition: all 0.2s;
    }
    .filter-option:hover {
        border-color: rgba(56,189,248,0.4);
        color: #fff;
    }
    .filter-option.active {
        background: rgba(56,189,248,0.15);
        border-color: rgba(56,189,248,0.5);
        color: #e0f2fe;
    }
</style>

<script>
function openFilterModal() {
    document.getElementById('filterModal').style.display = 'flex';
}

function closeFilterModal() {
    document.getElementById('filterModal').style.display = 'none';
}

function setFilterOption(btn, inputId, value) {
    document.getElementById(inputId).value = value;
    btn.parentElement.querySelectorAll('.filter-option').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

function applyFilters() {
    document.getElementById('filterStatus').value = document.getElementById('modalStatus').value;
    document.getElementById('filterBalanceType').value = document.getElementById('modalBalanceType').value;
    document.getElementById('filterBranchId').value = document.getElementById('modalBranchId').value;
    document.getElementById('filterAccountId').value = document.getElementById('modalAccountId').value;
    document.getElementById('filterDateFrom').value = document.getElementById('modalDateFrom').value;
    document.getElementById('filterDateTo').value = document.getElementById('modalDateTo').value;
    document.getElementById('filterMinAmount').value = document.getElementById('modalMinAmount').value;
    document.getElementById('filterMaxAmount').value = document.getElementById('modalMaxAmount').value;
    
    document.getElementById('searchForm').submit();
    closeFilterModal();
}

function clearModalFilters() {
    document.getElementById('modalStatus').value = '';
    document.getElementById('modalBalanceType').value = '';
    document.getElementById('modalBranchId').value = '';
    document.getElementById('modalAccountId').value = '';
    document.getElementById('modalDateFrom').value = '';
    document.getElementById('modalDateTo').value = '';
    document.getElementById('modalMinAmount').value = '';
    document.getElementById('modalMaxAmount').value = '';
    
    document.querySelectorAll('.filter-option').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.filter-option:first-child').forEach(b => b.classList.add('active'));
}

function removeFilter(param) {
    const url = new URL(window.location);
    url.searchParams.delete(param);
    window.location.href = url.toString();
}

function clearAllFilters() {
    window.location.href = '{{ route("opening-balances.index") }}';
}
</script>
@endsection
