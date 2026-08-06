@extends('layouts.app')

@section('title', 'طلبات الارتجاع - Axionyx ERP')
@section('page_title', 'طلبات ارتجاع البضاعة')
@section('page_subtitle', 'إدارة طلبات ارتجاع البضاعة من أجهزة المناديب')

@section('content')

@if(session('success'))
    <div style="padding:12px 16px;border-radius:10px;background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.18);color:#bbf7d0;margin-bottom:16px;font-size:14px;">
        {{ session('success') }}
    </div>
@endif

<article class="panel" style="border: 1px solid rgba(239,68,68,0.3); background: rgba(239,68,68,0.05);">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2 style="color:#ef4444; margin-bottom:4px;">danger zone</h2>
            <p class="muted">حذف جميع البيانات التعاملية: الفواتير، أوامر الصرف، خطط التوزيع، التحميلات، طلبات الارتجاع</p>
        </div>
        <form method="POST" action="{{ route('admin.clear-data.execute') }}">
            @csrf
            <button type="submit" onclick="return confirm('هل أنت متأكد من حذف جميع البيانات؟ لا يمكن التراجع عن هذا الإجراء.')"
                style="background:#ef4444;color:white;padding:10px 24px;border:none;border-radius:8px;font-size:14px;font-weight:bold;cursor:pointer;">
                مسح جميع البيانات
            </button>
        </form>
    </div>
</article>

<div class="grid grid-4">
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">إجمالي الطلبات</div>
                <div class="value">{{ $returnOrders->total() }}</div>
                <div class="trend">طلب ارتجاع</div>
            </div>
            <span class="chip">All</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">قيد المراجعة</div>
                <div class="value">{{ \App\Models\ReturnOrder::where('status_id', 'pending')->count() }}</div>
                <div class="trend">بانتظار الموافقة</div>
            </div>
            <span class="chip warn">Pending</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">تمت الموافقة</div>
                <div class="value">{{ \App\Models\ReturnOrder::where('status_id', 'approved')->count() }}</div>
                <div class="trend">تم الاستلام</div>
            </div>
            <span class="chip good">Approved</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">مرفوض / ملغي</div>
                <div class="value">{{ \App\Models\ReturnOrder::where('status_id', 'cancelled')->count() }}</div>
                <div class="trend">طلبات ملغاة</div>
            </div>
            <span class="chip bad">Rejected</span>
        </div>
    </article>
</div>

<article class="panel">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h2>طلبات الارتجاع</h2>
        <form method="GET" style="display:flex; gap:8px; align-items:center;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="بحث برقم الطلب أو اسم المندوب..." style="background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:8px 12px;color:#fff;font-size:13px;">
            <select name="status" style="background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:8px 12px;color:#fff;font-size:13px;">
                <option value="">جميع الحالات</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>قيد المراجعة</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>تمت الموافقة</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ملغي</option>
            </select>
            <button type="submit" class="btn">بحث</button>
        </form>
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

    @if($returnOrders->isEmpty())
        <div style="text-align:center; padding:40px; color:var(--muted);">
            <p style="font-size:18px;">لا توجد طلبات ارتجاع حالياً</p>
            <p style="font-size:13px;">سيظهر هنا أي طلب ارتجاع يتم إنشاؤه من أجهزة المناديب</p>
        </div>
    @else
        <div style="overflow-x:auto;">
            <table id="dataTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>رقم الطلب</th>
                        <th>المندوب</th>
                        <th>المخزن</th>
                        <th>نوع الارتجاع</th>
                        <th>تاريخ الطلب</th>
                        <th>عدد الأصناف</th>
                        <th>الكمية</th>
                        <th>الإجمالي</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($returnOrders as $index => $ro)
                        <tr>
                            <td>{{ ($returnOrders->currentPage() - 1) * $returnOrders->perPage() + $index + 1 }}</td>
                            <td><strong style="color:var(--accent);">{{ $ro->return_no }}</strong></td>
                            <td>{{ $ro->employee?->name ?? '—' }}</td>
                            <td>{{ $ro->warehouse?->name ?? '—' }}</td>
                            <td>
                                @if($ro->return_type === 'excess')
                                    فائض
                                @elseif($ro->return_type === 'damaged')
                                    تالف
                                @elseif($ro->return_type === 'expired')
                                    منتهي الصلاحية
                                @elseif($ro->return_type === 'wrong_item')
                                    صنف خاطئ
                                @elseif($ro->return_type === 'quality_issue')
                                    مشكلة جودة
                                @else
                                    {{ $ro->return_type ?? '—' }}
                                @endif
                            </td>
                            <td>{{ $ro->return_date?->format('Y-m-d') ?? '—' }}</td>
                            <td>{{ $ro->total_items_count }}</td>
                            <td>{{ number_format($ro->total_quantity, 2) }}</td>
                            <td style="color:var(--primary);font-weight:bold;">{{ number_format($ro->total_amount, 2) }}</td>
                            <td>
                                @if($ro->status_id === 'pending')
                                    <span class="status warn">⏳ قيد المراجعة</span>
                                @elseif($ro->status_id === 'approved')
                                    <span class="status good">✓ تمت الموافقة</span>
                                @elseif($ro->status_id === 'cancelled')
                                    <span class="status bad">✗ ملغي</span>
                                @elseif($ro->status_id === 'received')
                                    <span class="status good">✓ تم الاستلام</span>
                                @else
                                    <span class="status">{{ $ro->status_id ?? '—' }}</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex; gap:6px;">
                                    <a href="{{ route('return-orders.show', $ro->id) }}" class="btn" style="padding:6px 10px;font-size:12px;">عرض</a>
                                    @if($ro->status_id === 'pending')
                                        <a href="{{ route('return-orders.approve', $ro->id) }}" class="btn primary" style="padding:6px 10px;font-size:12px;">موافقة</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="display:flex; justify-content:center; margin-top:16px;">
            {{ $returnOrders->withQueryString()->links() }}
        </div>
    @endif
</article>
@endsection
