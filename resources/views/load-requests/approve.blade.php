@extends('layouts.app')

@section('title', 'موافقة على طلب التحميل - Axionyx ERP')
@section('page_title', 'مراجعة طلب التحميل ' . $loadRequest->request_no)
@section('page_subtitle', 'موافقت أمين المخزن على طلب تحميل البضاعة')

@section('content')
<article class="panel" style="border:2px solid rgba(245,158,11,0.3);background:rgba(245,158,11,0.04);">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
        <div style="width:48px;height:48px;border-radius:12px;background:rgba(245,158,11,0.15);display:grid;place-items:center;font-size:24px;">📦</div>
        <div>
            <h2 style="margin:0;">مراجعة طلب التحميل</h2>
            <p style="color:var(--muted);margin:4px 0 0;font-size:13px;">تأكد من توفر البضاعة في المخزن قبل الموافقة</p>
        </div>
    </div>
</article>

<div class="grid grid-2">
    <article class="panel">
        <h3>بيانات الطلب</h3>
        <div style="display:flex;flex-direction:column;gap:12px;">
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid rgba(148,163,184,0.12);padding-bottom:8px;">
                <span style="color:var(--muted);">رقم الطلب</span>
                <strong style="color:var(--accent);">{{ $loadRequest->request_no }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid rgba(148,163,184,0.12);padding-bottom:8px;">
                <span style="color:var(--muted);">المندوب</span>
                <strong>{{ $loadRequest->employee?->name ?? '—' }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid rgba(148,163,184,0.12);padding-bottom:8px;">
                <span style="color:var(--muted);">المخزن</span>
                <strong>{{ $loadRequest->warehouse?->name ?? '—' }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid rgba(148,163,184,0.12);padding-bottom:8px;">
                <span style="color:var(--muted);">تاريخ الطلب</span>
                <strong>{{ $loadRequest->request_date }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid rgba(148,163,184,0.12);padding-bottom:8px;">
                <span style="color:var(--muted);">عدد الأصناف</span>
                <strong>{{ $loadRequest->total_items_count }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid rgba(148,163,184,0.12);padding-bottom:8px;">
                <span style="color:var(--muted);">إجمالي الكمية</span>
                <strong style="color:var(--primary);">{{ number_format($loadRequest->total_quantity, 2) }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;padding-bottom:8px;">
                <span style="color:var(--muted);">إجمالي المبلغ</span>
                <strong style="color:var(--primary);font-size:18px;">{{ number_format($loadRequest->total_amount, 2) }}</strong>
            </div>
            @if($loadRequest->notes)
                <div style="padding:10px;border-radius:8px;background:rgba(148,163,184,0.08);border:1px solid rgba(148,163,184,0.15);">
                    <span style="color:var(--muted);font-size:12px;">ملاحظات المندوب:</span>
                    <div style="color:#e0f2fe;font-size:13px;margin-top:4px;">{{ $loadRequest->notes }}</div>
                </div>
            @endif
        </div>
    </article>

    <article class="panel">
        <h3>الأصناف المطلوبة</h3>
        @if($loadRequest->items->isEmpty())
            <div style="text-align:center;padding:20px;color:var(--muted);">لا توجد أصناف</div>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr>
                            <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">#</th>
                            <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">الصنف</th>
                            <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">الكمية</th>
                            <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">سعر الوحدة</th>
                            <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($loadRequest->items as $idx => $item)
                            <tr>
                                <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);">{{ $idx + 1 }}</td>
                                <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);">{{ $item->item?->name_ar ?? '—' }}</td>
                                <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);text-align:center;">
                                    <input type="number" name="items[{{ $item->id }}][quantity]" value="{{ $item->quantity }}" min="0.01" step="0.01" class="qty-input" data-unit-price="{{ $item->unit_price }}" data-row="{{ $idx }}" style="width:90px;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:6px 8px;color:#fff;font-size:13px;text-align:center;font-weight:bold;">
                                </td>
                                <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);text-align:center;">{{ number_format($item->unit_price, 2) }}</td>
                                <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);text-align:center;color:var(--primary);font-weight:bold;" id="row-total-{{ $idx }}">{{ number_format($item->total_price, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="margin-top:12px;padding:10px;border-radius:8px;background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.2);display:flex;justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">الإجمالي الكلي</span>
                <strong style="color:var(--primary);font-size:16px;" id="grand-total">{{ number_format($loadRequest->total_amount, 2) }} ج.م</strong>
            </div>
        @endif
    </article>
</div>

<article class="panel" style="margin-top:20px;">
    <h3>قرار الموافقة</h3>
    <form method="POST" action="{{ route('load-requests.approval', $loadRequest->id) }}" id="approvalForm">
        @csrf
        @method('PATCH')

        <div style="margin-bottom:16px;">
            <label style="display:block;margin-bottom:6px;color:var(--muted);font-size:13px;">ملاحظات أمين المخزن</label>
            <textarea name="notes" rows="3" placeholder="أدخل ملاحظاتك هنا... مثال: تم التأكد من توفر جميع الأصناف في المخزن" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:10px;padding:10px 12px;color:#fff;font-size:14px;resize:vertical;"></textarea>
        </div>

        <div style="display:flex;gap:12px;justify-content:flex-end;">
            <a href="{{ route('load-requests.show', $loadRequest->id) }}" class="btn">عرض التفاصيل</a>
            <button type="submit" name="action" value="reject" onclick="return confirm('هل أنت متأكد من رفض هذا الطلب؟')" style="background:rgba(251,113,133,0.15);border:1px solid rgba(251,113,133,0.3);border-radius:10px;padding:10px 20px;color:var(--danger);font-weight:600;cursor:pointer;font-size:14px;">رفض الطلب</button>
            <button type="submit" name="action" value="approve" onclick="return confirm('هل أنت متأكد من الموافقة على هذا الطلب؟ سيتم إنشاء إذن صرف تلقائياً')" class="btn primary" style="font-size:14px;padding:10px 20px;">موافقة وإنشاء إذن صرف</button>
        </div>
    </form>
</article>

<script>
document.querySelectorAll('.qty-input').forEach(input => {
    input.addEventListener('input', function() {
        const row = parseInt(this.dataset.row);
        const unitPrice = parseFloat(this.dataset.unitPrice);
        const qty = parseFloat(this.value) || 0;
        const total = qty * unitPrice;
        document.getElementById('row-total-' + row).textContent = total.toFixed(2);
        recalculateGrand();
    });
});

function recalculateGrand() {
    let grand = 0;
    document.querySelectorAll('.qty-input').forEach(input => {
        const row = parseInt(input.dataset.row);
        const unitPrice = parseFloat(input.dataset.unitPrice);
        const qty = parseFloat(input.value) || 0;
        grand += qty * unitPrice;
    });
    document.getElementById('grand-total').textContent = grand.toFixed(2) + ' ج.م';
}
</script>
@endsection
