@extends('layouts.app')

@section('title', 'حركة الصنف - Axionyx ERP')
@section('page_title', 'تقرير حركة الصنف')
@section('page_subtitle', 'سجل حركة الأصناف من الشراء حتى البيع')

@section('content')
<form method="GET" class="panel" style="display:flex; flex-wrap:wrap; gap:12px; align-items:end; padding:18px;">
    <div style="flex:1; min-width:180px;">
        <label style="display:block; font-size:12px; color:var(--muted); margin-bottom:4px;">الصنف</label>
        <select name="item_id" required style="width:100%; background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
            <option value="">-- اختر صنف --</option>
            @foreach($items as $item)
                <option value="{{ $item->id }}" {{ request('item_id') == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
            @endforeach
        </select>
    </div>
    <div style="min-width:150px;">
        <label style="display:block; font-size:12px; color:var(--muted); margin-bottom:4px;">من تاريخ</label>
        <input type="date" name="date_from" value="{{ request('date_from') }}" style="width:100%; background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
    </div>
    <div style="min-width:150px;">
        <label style="display:block; font-size:12px; color:var(--muted); margin-bottom:4px;">إلى تاريخ</label>
        <input type="date" name="date_to" value="{{ request('date_to') }}" style="width:100%; background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
    </div>
    <div style="min-width:150px;">
        <label style="display:block; font-size:12px; color:var(--muted); margin-bottom:4px;">المخزن</label>
        <select name="warehouse_id" style="width:100%; background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
            <option value="">الكل</option>
            @foreach($warehouses as $wh)
                <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
            @endforeach
        </select>
    </div>
    <div style="min-width:150px;">
        <label style="display:block; font-size:12px; color:var(--muted); margin-bottom:4px;">المندوب</label>
        <select name="rep_id" style="width:100%; background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
            <option value="">الكل</option>
            @foreach($reps as $rep)
                <option value="{{ $rep->id }}" {{ request('rep_id') == $rep->id ? 'selected' : '' }}>{{ $rep->first_name_ar }} {{ $rep->last_name_ar }}</option>
            @endforeach
        </select>
    </div>
    <div style="display:flex; gap:8px;">
        <button type="submit" class="btn primary">عرض</button>
        @if(request('item_id'))
            <a href="{{ route('item-ledger.index') }}" class="btn">إعادة</a>
        @endif
    </div>
</form>

@if($selectedItem)
    <div class="panel" style="padding:12px 18px;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <span style="font-size:14px; color:var(--muted);">الصنف :</span>
                <strong style="font-size:20px; color:#fff; margin-right:8px;">{{ $selectedItem->name }}</strong>
            </div>
            <div>
                <span style="font-size:14px; color:var(--muted);">الرصيد الحالي :</span>
                <strong style="font-size:24px; color:var(--accent); margin-right:8px;">{{ number_format($stats['current_balance'], 2) }}</strong>
            </div>
        </div>
    </div>

    <div class="grid grid-4">
        <article class="panel">
            <div class="metric">
                <div>
                    <div class="muted">إجمالي المشتريات</div>
                    <div class="value" style="color:var(--primary);">+{{ number_format($stats['total_purchase'], 2) }}</div>
                    <div class="trend">من الموردين</div>
                </div>
                <span class="chip" style="background:rgba(34,197,94,0.12);border-color:rgba(34,197,94,0.18);color:#bbf7d0;">شراء</span>
            </div>
        </article>
        <article class="panel">
            <div class="metric">
                <div>
                    <div class="muted">إجمالي التحميل</div>
                    <div class="value" style="color:var(--warn);">+{{ number_format($stats['total_load'], 2) }}</div>
                    <div class="trend">تم تحميلها للمندوبين</div>
                </div>
                <span class="chip" style="background:rgba(245,158,11,0.12);border-color:rgba(245,158,11,0.18);color:#fde68a;">تحميل</span>
            </div>
        </article>
        <article class="panel">
            <div class="metric">
                <div>
                    <div class="muted">إجمالي المبيعات</div>
                    <div class="value" style="color:var(--danger);">-{{ number_format($stats['total_sale'], 2) }}</div>
                    <div class="trend">تم بيعها للعملاء</div>
                </div>
                <span class="chip" style="background:rgba(251,113,133,0.12);border-color:rgba(251,113,133,0.18);color:#fecdd3;">بيع</span>
            </div>
        </article>
        <article class="panel">
            <div class="metric">
                <div>
                    <div class="muted">المرتجعات</div>
                    <div class="value" style="color:var(--accent);">+{{ number_format($stats['total_return'], 2) }}</div>
                    <div class="trend">مرتجعة من العملاء</div>
                </div>
                <span class="chip" style="background:rgba(56,189,248,0.12);border-color:rgba(56,189,248,0.18);color:#e0f2fe;">مرتجع</span>
            </div>
        </article>
    </div>

    @if($stats['total_unload'] > 0)
    <div style="font-size:12px; color:var(--muted); text-align:center; margin-top:-8px;">
        تفريغ للمستودع: {{ number_format($stats['total_unload'], 2) }}
    </div>
    @endif

    <article class="panel" style="padding:0;">
        <div style="display:flex; border-bottom:1px solid var(--line); overflow-x:auto;">
            <a href="{{ route('item-ledger.index', array_merge(request()->query(), ['tab' => 'all'])) }}"
               class="tab-link {{ $tab === 'all' ? 'active' : '' }}">الكل</a>
            <a href="{{ route('item-ledger.index', array_merge(request()->query(), ['tab' => 'purchases'])) }}"
               class="tab-link {{ $tab === 'purchases' ? 'active' : '' }}">المشتريات</a>
            <a href="{{ route('item-ledger.index', array_merge(request()->query(), ['tab' => 'loads'])) }}"
               class="tab-link {{ $tab === 'loads' ? 'active' : '' }}">التحميل ({{ number_format($stats['total_load'], 2) }})</a>
            <a href="{{ route('item-ledger.index', array_merge(request()->query(), ['tab' => 'sales'])) }}"
               class="tab-link {{ $tab === 'sales' ? 'active' : '' }}">المبيعات</a>
            <a href="{{ route('item-ledger.index', array_merge(request()->query(), ['tab' => 'returns'])) }}"
               class="tab-link {{ $tab === 'returns' ? 'active' : '' }}">المرتجعات ({{ number_format($stats['total_return'], 2) }})</a>
            <a href="{{ route('item-ledger.index', array_merge(request()->query(), ['tab' => 'rep_balances'])) }}"
               class="tab-link {{ $tab === 'rep_balances' ? 'active' : '' }}">أرصدة المناديب</a>
        </div>

        <div style="padding:16px;">
            @if($tab === 'rep_balances')
                @include('item-ledger.tabs.rep-balances', ['repBalances' => $repBalances])
            @else
                @include('item-ledger.tabs.movements', [
                    'movements' => $filteredMovements,
                    'tab' => $tab,
                ])
            @endif
        </div>
    </article>
@else
    <div class="panel" style="text-align:center; padding:40px;">
        <p style="color:var(--muted); font-size:16px;">اختر صنفاً من القائمة أعلاه واضغط "عرض"</p>
    </div>
@endif
@endsection

@section('styles')
<style>
    .tab-link {
        padding: 14px 22px;
        font-size: 14px;
        font-weight: 600;
        color: var(--muted);
        border-bottom: 3px solid transparent;
        transition: all .15s;
        white-space: nowrap;
    }
    .tab-link:hover {
        color: #fff;
        background: rgba(56,189,248,0.06);
    }
    .tab-link.active {
        color: var(--accent);
        border-bottom-color: var(--accent);
    }
    .badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
    }
    .badge-green { background: rgba(34,197,94,0.12); color: #bbf7d0; border: 1px solid rgba(34,197,94,0.18); }
    .badge-yellow { background: rgba(245,158,11,0.12); color: #fde68a; border: 1px solid rgba(245,158,11,0.18); }
    .badge-red { background: rgba(251,113,133,0.12); color: #fecdd3; border: 1px solid rgba(251,113,133,0.18); }
    .badge-blue { background: rgba(56,189,248,0.12); color: #e0f2fe; border: 1px solid rgba(56,189,248,0.18); }
    .badge-gray { background: rgba(148,163,184,0.12); color: #cbd5e1; border: 1px solid rgba(148,163,184,0.18); }
    .rep-row {
        cursor: pointer;
        transition: background .12s;
    }
    .rep-row:hover {
        background: rgba(56,189,248,0.06);
    }
</style>
@endsection
