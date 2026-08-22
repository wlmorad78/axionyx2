@extends('layouts.app')

@section('title', 'تفاصيل طلب الارتجاع - Axionyx ERP')
@section('page_title', 'طلب الارتجاع ' . $returnOrder->return_no)
@section('page_subtitle', 'عرض تفاصيل طلب الارتجاع والأصناف')

@section('content')
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

<div class="grid grid-2">
    <article class="panel">
        <h3>بيانات الطلب</h3>
        <div style="display:flex;flex-direction:column;gap:12px;">
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid rgba(148,163,184,0.12);padding-bottom:8px;">
                <span style="color:var(--muted);">رقم الطلب</span>
                <strong style="color:var(--accent);">{{ $returnOrder->return_no }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid rgba(148,163,184,0.12);padding-bottom:8px;">
                <span style="color:var(--muted);">المندوب</span>
                <strong>{{ $returnOrder->employee?->name ?? '—' }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid rgba(148,163,184,0.12);padding-bottom:8px;">
                <span style="color:var(--muted);">المخزن</span>
                <strong>{{ $returnOrder->warehouse?->name ?? '—' }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid rgba(148,163,184,0.12);padding-bottom:8px;">
                <span style="color:var(--muted);">نوع الارتجاع</span>
                <strong>
                    @if($returnOrder->return_type === 'excess') فائض
                    @elseif($returnOrder->return_type === 'damaged') تالف
                    @elseif($returnOrder->return_type === 'expired') منتهي الصلاحية
                    @elseif($returnOrder->return_type === 'wrong_item') صنف خاطئ
                    @elseif($returnOrder->return_type === 'quality_issue') مشكلة جودة
                    @else {{ $returnOrder->return_type ?? '—' }}
                    @endif
                </strong>
            </div>
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid rgba(148,163,184,0.12);padding-bottom:8px;">
                <span style="color:var(--muted);">تاريخ الطلب</span>
                <strong>{{ $returnOrder->return_date?->format('Y-m-d') ?? '—' }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid rgba(148,163,184,0.12);padding-bottom:8px;">
                <span style="color:var(--muted);">الحالة</span>
                @if($returnOrder->status_id === 'pending')
                    <span class="status warn">⏳ قيد المراجعة</span>
                @elseif($returnOrder->status_id === 'approved')
                    <span class="status good">✓ تمت الموافقة</span>
                @elseif($returnOrder->status_id === 'cancelled')
                    <span class="status bad">✗ ملغي</span>
                @elseif($returnOrder->status_id === 'received')
                    <span class="status good">✓ تم الاستلام</span>
                @else
                    <span class="status">{{ $returnOrder->status_id ?? '—' }}</span>
                @endif
            </div>
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid rgba(148,163,184,0.12);padding-bottom:8px;">
                <span style="color:var(--muted);">عدد الأصناف</span>
                <strong>{{ $returnOrder->total_items_count }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid rgba(148,163,184,0.12);padding-bottom:8px;">
                <span style="color:var(--muted);">إجمالي الكمية</span>
                <strong style="color:var(--primary);">{{ number_format($returnOrder->total_quantity, 2) }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid rgba(148,163,184,0.12);padding-bottom:8px;">
                <span style="color:var(--muted);">إجمالي المبلغ</span>
                <strong style="color:var(--primary);font-size:18px;">{{ number_format($returnOrder->total_amount, 2) }}</strong>
            </div>
            @if($returnOrder->notes)
                <div style="padding:10px;border-radius:8px;background:rgba(148,163,184,0.08);border:1px solid rgba(148,163,184,0.15);">
                    <span style="color:var(--muted);font-size:12px;">ملاحظات:</span>
                    <div style="color:#e0f2fe;font-size:13px;margin-top:4px;">{{ $returnOrder->notes }}</div>
                </div>
            @endif
            @if($returnOrder->approved_by)
                <div style="display:flex;justify-content:space-between;padding-bottom:8px;">
                    <span style="color:var(--muted);">تمت الموافقة بواسطة</span>
                    <strong>{{ $returnOrder->approvedByEmployee?->name ?? '—' }}</strong>
                </div>
            @endif
            @if($returnOrder->approved_at)
                <div style="display:flex;justify-content:space-between;padding-bottom:8px;">
                    <span style="color:var(--muted);">تاريخ الموافقة</span>
                    <strong>{{ $returnOrder->approved_at->format('Y-m-d H:i') }}</strong>
                </div>
            @endif
        </div>
    </article>

    <article class="panel">
        <h3>الأصناف المرتجعة</h3>
        @if($returnOrder->items->isEmpty())
            <div style="text-align:center;padding:20px;color:var(--muted);">لا توجد أصناف</div>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr>
                            <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">#</th>
                            <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">الصنف</th>
                            <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">التحميل</th>
                            <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">المبيعات</th>
                            <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">المرتجع</th>
                            <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">سعر الوحدة</th>
                            <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($returnOrder->items as $idx => $item)
                            <tr>
                                <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);">{{ $idx + 1 }}</td>
                                <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);">{{ $item->item?->name_ar ?? '—' }}</td>
                                <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);text-align:center;font-weight:bold;color:#3b82f6;">{{ number_format($item->loaded_qty ?? 0, 2) }}</td>
                                <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);text-align:center;font-weight:bold;color:#eab308;">{{ number_format($item->sold_quantity, 2) }}</td>
                                <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);text-align:center;font-weight:bold;color:#ef4444;">{{ number_format($item->returned_quantity, 2) }}</td>
                                <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);text-align:center;">{{ number_format($item->sales_price, 2) }}</td>
                                <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);text-align:center;color:var(--primary);font-weight:bold;">{{ number_format($item->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </article>
</div>

<div style="margin-top:20px; display:flex; gap:12px; justify-content:flex-end;">
    <a href="{{ route('return-orders.index') }}" class="btn">العودة للقائمة</a>
    @if($returnOrder->status_id === 'pending')
        <a href="{{ route('return-orders.approve', $returnOrder->id) }}" class="btn primary">الذهاب للموافقة</a>
    @endif
    @if($returnOrder->status_id === 'approved')
        <form method="POST" action="{{ route('return-orders.reopen', $returnOrder->id) }}" onsubmit="return confirm('هل تريد إعادة فتح هذا الطلب؟ سيتم حذف حركة المخزون المرتبطة به.')">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn" style="background:#f59e0b;color:#000;">إعادة فتح</button>
        </form>
    @endif
</div>
@endsection
