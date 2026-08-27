@extends('layouts.app')

@section('title', 'تفاصيل طلب التحميل - Axionyx ERP')
@section('page_title', 'طلب التحميل ' . $loadRequest->request_no)
@section('page_subtitle', 'عرض تفاصيل طلب التحميل والأصناف المطلوبة')

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
                <span style="color:var(--muted);">الحالة</span>
                @if($loadRequest->status === 'pending')
                    <span class="status warn">⏳ قيد المراجعة</span>
                @elseif(in_array($loadRequest->status, ['approved', 'loading']))
                    <span class="status good">✓ {{ $loadRequest->status === 'loading' ? 'جاري التحميل' : 'تمت الموافقة' }}</span>
                @elseif($loadRequest->status === 'completed')
                    <span class="status good">✓ مكتمل</span>
                @elseif($loadRequest->status === 'cancelled')
                    <span class="status bad">✗ ملغي</span>
                @else
                    <span class="status">{{ $loadRequest->status }}</span>
                @endif
            </div>
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid rgba(148,163,184,0.12);padding-bottom:8px;">
                <span style="color:var(--muted);">عدد الأصناف</span>
                <strong>{{ $loadRequest->total_items_count }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid rgba(148,163,184,0.12);padding-bottom:8px;">
                <span style="color:var(--muted);">إجمالي الكمية</span>
                <strong style="color:var(--primary);">{{ number_format($loadRequest->total_quantity, 2) }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid rgba(148,163,184,0.12);padding-bottom:8px;">
                <span style="color:var(--muted);">إجمالي المبلغ</span>
                <strong style="color:var(--primary);font-size:18px;">{{ number_format($loadRequest->total_amount, 2) }}</strong>
            </div>
            @if($loadRequest->notes)
                <div style="display:flex;justify-content:space-between;padding-bottom:8px;">
                    <span style="color:var(--muted);">ملاحظات</span>
                    <strong>{{ $loadRequest->notes }}</strong>
                </div>
            @endif
            @if($loadRequest->create_notes)
                <div style="padding:10px;border-radius:8px;background:rgba(56,189,248,0.08);border:1px solid rgba(56,189,248,0.15);">
                    <span style="color:var(--muted);font-size:12px;">ملاحظات الموافقة:</span>
                    <div style="color:#e0f2fe;font-size:13px;margin-top:4px;">{{ $loadRequest->create_notes }}</div>
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
                            <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">الكمية المطلوبة</th>
                            <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">الكمية المنصرفة</th>
                            <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">سعر الوحدة</th>
                            <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $ioItems = $loadRequest->issueOrder?->items->keyBy('item_id');
                        @endphp
                        @foreach($loadRequest->items as $idx => $item)
                            @php $ioItem = $ioItems?->get($item->item_id); @endphp
                            <tr>
                                <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);">{{ $idx + 1 }}</td>
                                <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);">{{ $item->item?->name_ar ?? '—' }}</td>
                                <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);text-align:center;">{{ number_format($item->quantity, 2) }}</td>
                                <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);text-align:center;">
                                    @if($ioItem)
                                        <span style="color:var(--accent);font-weight:bold;">{{ number_format($ioItem->issued_quantity, 2) }}</span>
                                    @else
                                        <span style="color:var(--muted);">—</span>
                                    @endif
                                </td>
                                <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);text-align:center;">{{ number_format($item->unit_price, 2) }}</td>
                                <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);text-align:center;color:var(--primary);font-weight:bold;">{{ number_format($item->total_price, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </article>
</div>

<div style="margin-top:20px; display:flex; gap:12px; justify-content:flex-end;">
    <a href="{{ route('load-requests.index') }}" class="btn">العودة للقائمة</a>
    @if(in_array($loadRequest->status, ['approved', 'loading']))
        <a href="{{ route('load-requests.complementary.create', $loadRequest->id) }}" class="btn" style="border-color:var(--accent);color:var(--accent);">+ أمر تحميل تكميلى</a>
        <form method="POST" action="{{ route('load-requests.cancel', $loadRequest->id) }}" onsubmit="return confirm('هل أنت متأكد من إلغاء أمر التحميل؟ سيتم إرجاع كامل الكمية للمخزن.')">
            @csrf
            <button type="submit" class="btn" style="color:#f59e0b;border-color:rgba(245,158,11,0.3);">إلغاء الأمر ورد الكمية للمخزن</button>
        </form>
    @endif
    @if($loadRequest->status === 'pending')
        <a href="{{ route('load-requests.approve', $loadRequest->id) }}" class="btn primary">الذهاب للموافقة</a>
    @endif
</div>

@if($loadRequest->complementaryRequests->isNotEmpty())
<div class="grid grid-2" style="margin-top:20px;">
    <article class="panel" style="grid-column:1 / -1;">
        <h3>أوامر التحميل التكميلية التابعة</h3>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr>
                        <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">رقم الأمر</th>
                        <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">المخزن</th>
                        <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">الحالة</th>
                        <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">إجمالي الكمية</th>
                        <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($loadRequest->complementaryRequests as $comp)
                        <tr>
                            <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);">{{ $comp->request_no }}</td>
                            <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);">{{ $comp->warehouse?->name ?? '—' }}</td>
                            <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);">
                                @if(in_array($comp->status, ['approved', 'loading']))
                                    <span class="status good">✓ {{ $comp->status === 'loading' ? 'جاري التحميل' : 'تمت الموافقة' }}</span>
                                @elseif($comp->status === 'pending')
                                    <span class="status warn">⏳ قيد المراجعة</span>
                                @elseif($comp->status === 'closed')
                                    <span class="status">مغلق</span>
                                @else
                                    <span class="status">{{ $comp->status }}</span>
                                @endif
                            </td>
                            <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);">{{ number_format($comp->total_quantity, 2) }}</td>
                            <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);text-align:left;">
                                <a href="{{ route('load-requests.show', $comp->id) }}" class="btn" style="font-size:12px;padding:6px 10px;">عرض</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </article>
</div>
@endif

@if($loadRequest->parentRequest)
<div style="margin-top:16px;padding:10px 14px;border-radius:8px;background:rgba(56,189,248,0.08);border:1px solid rgba(56,189,248,0.15);font-size:13px;color:#e0f2fe;">
    هذا الأمر التكميلى تابع لأمر التحميل
    <a href="{{ route('load-requests.show', $loadRequest->parentRequest->id) }}" style="color:var(--accent);text-decoration:underline;">{{ $loadRequest->parentRequest->request_no }}</a>
    ويُغلق تلقائياً عند إغلاقه.
</div>
@endif
@endsection
