<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
    <div>
        <h3 style="margin:0; font-size:18px;">{{ $rep->full_name_ar }}</h3>
        <div class="muted">مندوب مبيعات</div>
    </div>
    <button onclick="closeRepDrawer()" class="btn" style="padding:6px 12px; font-size:13px;">✕ إغلاق</button>
</div>

<div class="panel" style="text-align:center; padding:16px; margin-bottom:16px; background:rgba(56,189,248,0.06); border-color:rgba(56,189,248,0.18);">
    <div class="muted" style="font-size:13px;">الرصيد الحالي</div>
    <div style="font-size:32px; font-weight:700; color:{{ $balance > 0 ? 'var(--primary)' : 'var(--muted)' }};">
        {{ number_format($balance, 2) }}
    </div>
</div>

@if($hydrated->isEmpty())
    <div style="text-align:center; padding:24px; color:var(--muted);">
        لا توجد حركات لهذا المندوب
    </div>
@else
    <div style="display:flex; flex-direction:column; gap:8px;">
        @php $bal = 0; @endphp
        @foreach($hydrated as $m)
            @php
                $bal += (float) $m->qty;
                $isIn = $m->qty > 0;
                $label = match($m->movement_type) {
                    'load' => 'تحميل',
                    'sale' => 'فاتورة بيع',
                    'return' => 'مرتجع',
                    'unload' => 'تفريغ للمستودع',
                    default => $m->txn_type_name ?? 'حركة',
                };
                $badgeColor = $isIn ? 'color:var(--primary);' : 'color:var(--danger);';
            @endphp
            <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 12px; border-radius:10px; background:rgba(255,255,255,0.02); border:1px solid var(--line);">
                <div style="display:flex; flex-direction:column; gap:2px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="font-size:12px; color:var(--muted);">{{ $m->transaction_date }}</span>
                        <span style="font-size:13px; font-weight:600;">{{ $label }}</span>
                    </div>
                    <div style="font-size:12px; color:var(--muted);">
                        @if($m->ref_number)
                            <span style="color:var(--accent);">{{ $m->ref_number }}</span>
                        @endif
                        @if($m->movement_type === 'sale' || $m->movement_type === 'return')
                            ← {{ $m->to_name ?: $m->from_name }}
                        @endif
                    </div>
                </div>
                <div style="text-align:left; direction:ltr;">
                    <div style="font-weight:700; font-size:16px; {{ $badgeColor }}">
                        {{ $isIn ? '+' : '-' }}{{ number_format(abs($m->qty), 2) }}
                    </div>
                    <div style="font-size:11px; color:var(--muted);">
                        الرصيد: {{ number_format($bal, 2) }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
