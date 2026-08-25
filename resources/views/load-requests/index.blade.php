@extends('layouts.app')

@section('title', 'أوامر التحميل - Axionyx ERP')
@section('page_title', 'أوامر تحميل البضاعة')
@section('page_subtitle', 'إدارة طلبات تحميل البضاعة على أجهزة المناديب')

@section('content')
<div class="grid grid-4">
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">إجمالي الطلبات</div>
                <div class="value">{{ $loadRequests->total() }}</div>
                <div class="trend">طلب تحميل</div>
            </div>
            <span class="chip">All</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">قيد المراجعة</div>
                <div class="value">{{ \App\Models\LoadRequest::where('status', 'pending')->count() }}</div>
                <div class="trend">بانتظار الموافقة</div>
            </div>
            <span class="chip warn">Pending</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">تمت الموافقة</div>
                <div class="value">{{ \App\Models\LoadRequest::whereIn('status', ['approved', 'loading'])->count() }}</div>
                <div class="trend">جاري التحميل</div>
            </div>
            <span class="chip good">Approved</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">مرفوض / ملغي</div>
                <div class="value">{{ \App\Models\LoadRequest::where('status', 'cancelled')->count() }}</div>
                <div class="trend">طلبات ملغاة</div>
            </div>
            <span class="chip bad">Rejected</span>
        </div>
    </article>
</div>

<article class="panel">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h2>أوامر التحميل</h2>
        <div style="display:flex; gap:8px;">
            <form method="GET" style="display:flex; gap:8px; align-items:center;">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="بحث برقم الطلب أو اسم المندوب..." style="background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:8px 12px;color:#fff;font-size:13px;">
                <select name="status" style="background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:8px 12px;color:#fff;font-size:13px;">
                    <option value="">جميع الحالات</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>مسودة</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>قيد المراجعة</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>تمت الموافقة</option>
                    <option value="loading" {{ request('status') === 'loading' ? 'selected' : '' }}>جاري التحميل</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>مكتمل</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ملغي</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>مغلق</option>
                </select>
                <select name="load_type" style="background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:8px 12px;color:#fff;font-size:13px;">
                    <option value="">جميع الأنواع</option>
                    <option value="standard" {{ request('load_type') === 'standard' ? 'selected' : '' }}>عادي</option>
                    <option value="complementary" {{ request('load_type') === 'complementary' ? 'selected' : '' }}>تكميلى</option>
                </select>
                <button type="submit" class="btn">بحث</button>
            </form>
            <a href="{{ route('load-requests.create') }}" class="btn primary">+ طلب تحميل جديد</a>
        </div>
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

    @if($loadRequests->isEmpty())
        <div style="text-align:center; padding:40px; color:var(--muted);">
            <p style="font-size:18px;">لا توجد أوامر تحميل حالياً</p>
            <p style="font-size:13px;">اضغط على "طلب تحميل جديد" لإنشاء أول طلب</p>
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
                        <th>تاريخ الطلب</th>
                        <th>عدد الأصناف</th>
                        <th>الكمية</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($loadRequests as $index => $lr)
                        <tr>
                            <td>{{ ($loadRequests->currentPage() - 1) * $loadRequests->perPage() + $index + 1 }}</td>
                            <td>
                                <strong style="color:var(--accent);">{{ $lr->request_no }}</strong>
                                @if($lr->load_type === 'complementary')
                                    <span style="display:inline-block;margin-right:6px;padding:2px 8px;border-radius:6px;background:rgba(56,189,248,0.15);color:#38bdf8;font-size:11px;font-weight:600;">تكميلى</span>
                                @endif
                                @if($lr->parent_load_request_id)
                                    <span style="display:inline-block;margin-right:4px;font-size:11px;color:var(--muted);">← {{ $lr->parentRequest->request_no ?? '' }}</span>
                                @endif
                            </td>
                            <td>{{ $lr->employee?->name ?? '—' }}</td>
                            <td>{{ $lr->warehouse?->name ?? '—' }}</td>
                            <td>{{ $lr->request_date }}</td>
                            <td>{{ $lr->total_items_count }}</td>
                            <td>{{ number_format($lr->total_quantity, 2) }}</td>
                            <td>
                                @if($lr->status === 'pending')
                                    <span class="status warn">⏳ قيد المراجعة</span>
                                @elseif(in_array($lr->status, ['approved', 'loading']))
                                    <span class="status good">✓ {{$lr->status === 'loading' ? 'جاري التحميل' : 'تمت الموافقة'}}</span>
                                @elseif($lr->status === 'completed')
                                    <span class="status good">✓ مكتمل</span>
                                @elseif($lr->status === 'cancelled')
                                    <span class="status bad">✗ ملغي</span>
                                @elseif($lr->status === 'draft')
                                    <span class="status" style="background:rgba(148,163,184,0.12);color:#94a3b8;border-color:rgba(148,163,184,0.18);">مسودة</span>
                                @else
                                    <span class="status">{{ $lr->status }}</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex; gap:6px;">
                                    <a href="{{ route('load-requests.show', $lr->id) }}" class="btn" style="padding:6px 10px;font-size:12px;">عرض</a>
                                    @if(in_array($lr->status, ['approved', 'loading']) && $lr->load_type !== 'complementary')
                                        <a href="{{ route('load-requests.complementary.create', $lr->id) }}" class="btn" style="padding:6px 10px;font-size:12px;border-color:var(--accent);color:var(--accent);">+ تكميلى</a>
                                    @endif
                                    @if($lr->status === 'pending')
                                        <a href="{{ route('load-requests.approve', $lr->id) }}" class="btn primary" style="padding:6px 10px;font-size:12px;">موافقة</a>
                                    @endif
                                    @if(in_array($lr->status, ['draft', 'pending']))
                                        <form method="POST" action="{{ route('load-requests.destroy', $lr->id) }}" onsubmit="return confirm('هل أنت متأكد من الحذف؟')">
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
            {{ $loadRequests->withQueryString()->links() }}
        </div>
    @endif
</article>
@endsection
