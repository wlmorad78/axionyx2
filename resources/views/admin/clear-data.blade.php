@extends('layouts.app')

@section('title', 'مسح البيانات - Axionyx ERP')
@section('page_title', 'مسح البيانات')
@section('page_subtitle', 'حذف البيانات بشكل نهائي - اختر الجدول أو المجموعة المراد مسحها')

@section('content')
<style>
    .clear-card {
        background: rgba(17,24,39,0.92);
        border: 1px solid var(--line);
        border-radius: 14px;
        padding: 16px;
        transition: border-color 0.2s;
    }
    .clear-card:hover { border-color: rgba(251,113,133,0.3); }
    .clear-card h3 {
        font-size: 15px;
        color: var(--danger);
        margin: 0 0 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .clear-card .count {
        font-size: 11px;
        background: rgba(251,113,133,0.12);
        color: var(--danger);
        padding: 2px 8px;
        border-radius: 999px;
        font-weight: 400;
    }
    .table-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 0;
        border-top: 1px solid rgba(148,163,184,0.08);
    }
    .table-row:first-child { border-top: 0; }
    .table-row .name {
        font-size: 13px;
        color: var(--muted);
    }
    .table-row .records {
        font-size: 11px;
        color: #64748b;
        margin-left: 8px;
        min-width: 60px;
        text-align: left;
    }
    .btn-clear {
        background: rgba(251,113,133,0.1);
        color: var(--danger);
        border: 1px solid rgba(251,113,133,0.2);
        padding: 4px 12px;
        border-radius: 8px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s;
        font-weight: 500;
        white-space: nowrap;
    }
    .btn-clear:hover {
        background: rgba(251,113,133,0.2);
        border-color: rgba(251,113,133,0.4);
    }
    .btn-clear-group {
        background: rgba(251,113,133,0.15);
        color: #fff;
        border: 1px solid rgba(251,113,133,0.3);
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s;
        font-weight: 600;
    }
    .btn-clear-group:hover {
        background: rgba(251,113,133,0.25);
        border-color: rgba(251,113,133,0.5);
    }
    .btn-clear-all {
        background: linear-gradient(135deg, #dc2626, #ef4444);
        color: #fff;
        border: none;
        padding: 14px 32px;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 4px 15px rgba(220,38,38,0.3);
    }
    .btn-clear-all:hover {
        box-shadow: 0 6px 25px rgba(220,38,38,0.5);
        transform: translateY(-1px);
    }
    .alert-box {
        background: rgba(245,158,11,0.08);
        border: 1px solid rgba(245,158,11,0.2);
        border-radius: 12px;
        padding: 14px 18px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 13px;
        color: #fde68a;
    }
    .alert-icon { font-size: 20px; }
    .success-msg {
        background: rgba(34,197,94,0.1);
        border: 1px solid rgba(34,197,94,0.2);
        color: #bbf7d0;
        padding: 12px 16px;
        border-radius: 10px;
        font-size: 14px;
        margin-bottom: 16px;
    }
    .error-msg {
        background: rgba(251,113,133,0.1);
        border: 1px solid rgba(251,113,133,0.2);
        color: #fecdd3;
        padding: 12px 16px;
        border-radius: 10px;
        font-size: 14px;
        margin-bottom: 16px;
    }
</style>

@if(session('success'))
    <div class="success-msg">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="error-msg">{{ session('error') }}</div>
@endif

<div class="alert-box">
    <span class="alert-icon">&#9888;</span>
    <div>
        <strong>تحذير:</strong> سيتم حذف البيانات بشكل نهائي ولا يمكن التراجع عن هذا الإجراء.
        يُنصح بعمل نسخة احتياطية قبل المتابعة.
    </div>
</div>

<div style="display: flex; justify-content: space-between; align-items: center; margin: 20px 0 8px;">
    <h2 style="margin: 0; font-size: 18px;">مسح حسب المجموعة</h2>
    <form method="POST" action="{{ route('admin.clear-data.all') }}">
        @csrf
        <button type="submit" class="btn-clear-all"
            onclick="return confirm('هل أنت متأكد من حذف جميع البيانات نهائياً؟ هذا الإجراء لا يمكن التراجع عنه.')">
            مسح جميع البيانات
        </button>
    </form>
</div>

<div class="grid grid-4" style="margin-bottom: 24px;">
    @foreach($groups as $groupKey => $group)
        @php
            $groupCount = 0;
            foreach($group['tables'] as $table => $label) {
                $groupCount += $counts[$table] ?? 0;
            }
        @endphp
        <div class="panel" style="text-align: center; padding: 16px;">
            <div style="font-size: 24px; font-weight: 700; color: var(--danger);">
                {{ number_format($groupCount) }}
            </div>
            <div style="font-size: 13px; color: var(--muted); margin: 4px 0 12px;">
                {{ $group['label'] }}
            </div>
            <form method="POST" action="{{ route('admin.clear-data.group', $groupKey) }}">
                @csrf
                <button type="submit" class="btn-clear-group"
                    onclick="return confirm('هل أنت متأكد من مسح جميع بيانات {{ $group['label'] }}؟')">
                    مسح {{ $group['label'] }}
                </button>
            </form>
        </div>
    @endforeach
</div>

<h2 style="margin: 0 0 12px; font-size: 18px;">مسح حسب الجدول</h2>

<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 14px;">
    @foreach($groups as $groupKey => $group)
        <div class="clear-card">
            <h3>
                {{ $group['label'] }}
                @php
                    $groupCount = 0;
                    foreach($group['tables'] as $table => $label) {
                        $groupCount += $counts[$table] ?? 0;
                    }
                @endphp
                <span class="count">{{ number_format($groupCount) }} سجل</span>
            </h3>
            @foreach($group['tables'] as $table => $label)
                @php $count = $counts[$table] ?? 0; @endphp
                <div class="table-row">
                    <span class="name">{{ $label }}</span>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span class="records">{{ number_format($count) }}</span>
                        @if($count > 0)
                            <form method="POST" action="{{ route('admin.clear-data.table', $table) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn-clear"
                                    onclick="return confirm('هل أنت متأكد من مسح {{ $label }}؟')">
                                    مسح
                                </button>
                            </form>
                        @else
                            <span style="color: #475569; font-size: 11px;">فارغ</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
</div>
@endsection
