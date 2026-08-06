@extends('layouts.app')

@section('title', 'موافقة على طلب الارتجاع - Axionyx ERP')
@section('page_title', 'مراجعة طلب الارتجاع ' . $returnOrder->return_no)
@section('page_subtitle', 'موافقت أمين المخزن على طلب ارتجاع البضاعة')

@section('content')
<article class="panel" style="border:2px solid rgba(245,158,11,0.3);background:rgba(245,158,11,0.04);">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
        <div style="width:48px;height:48px;border-radius:12px;background:rgba(245,158,11,0.15);display:grid;place-items:center;font-size:24px;">📦</div>
        <div>
            <h2 style="margin:0;">مراجعة طلب الارتجاع</h2>
            <p style="color:var(--muted);margin:4px 0 0;font-size:13px;">تأكد من صحة الأصناف والكميات قبل الموافقة</p>
        </div>
    </div>
</article>

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
                <span style="color:var(--muted);">عدد الأصناف</span>
                <strong>{{ $returnOrder->total_items_count }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid rgba(148,163,184,0.12);padding-bottom:8px;">
                <span style="color:var(--muted);">إجمالي الكمية</span>
                <strong style="color:var(--primary);">{{ number_format($returnOrder->total_quantity, 2) }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;padding-bottom:8px;">
                <span style="color:var(--muted);">إجمالي المبلغ</span>
                <strong style="color:var(--primary);font-size:18px;">{{ number_format($returnOrder->total_amount, 2) }}</strong>
            </div>
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
                            <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">الكمية</th>
                            <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">سعر الوحدة</th>
                            <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($returnOrder->items as $idx => $item)
                            <tr>
                                <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);">{{ $idx + 1 }}</td>
                                <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);">{{ $item->item?->name_ar ?? '—' }}</td>
                                <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);text-align:center;font-weight:bold;color:#fff;">{{ number_format($item->returned_quantity, 2) }}</td>
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

<article class="panel" style="margin-top:20px;">
    <h3>قرار الموافقة</h3>
    <form method="POST" action="{{ route('return-orders.process', $returnOrder->id) }}" id="approvalForm">
        @csrf
        @method('PATCH')

        <div style="margin-bottom:16px;">
            <label style="display:block;margin-bottom:6px;color:var(--muted);font-size:13px;">ملاحظات أمين المخزن</label>
            <textarea name="notes" rows="3" placeholder="أدخل ملاحظاتك هنا... مثال: تم استلام البضاعة المرتجعة" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:10px;padding:10px 12px;color:#fff;font-size:14px;resize:vertical;"></textarea>
        </div>

        <div style="display:flex;gap:12px;justify-content:flex-end;">
            <a href="{{ route('return-orders.show', $returnOrder->id) }}" class="btn">عرض التفاصيل</a>
            <button type="submit" name="action" value="reject" onclick="return confirm('هل أنت متأكد من رفض هذا الطلب؟')" style="background:rgba(251,113,133,0.15);border:1px solid rgba(251,113,133,0.3);border-radius:10px;padding:10px 20px;color:var(--danger);font-weight:600;cursor:pointer;font-size:14px;">رفض الطلب</button>
            <button type="submit" name="action" value="approve" onclick="return confirm('هل أنت متأكد من الموافقة على هذا الطلب؟ سيتم إنشاء أمر بيع تلقائياً')" class="btn primary" style="font-size:14px;padding:10px 20px;">موافقة وإنشاء أمر بيع</button>
        </div>
    </form>
</article>
@endsection
