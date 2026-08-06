@extends('layouts.app')

@section('title', $screen->title . ' - Axionyx ERP')
@section('page_title', $screen->title)
@section('page_subtitle', 'عرض وإدارة بيانات: ' . $screen->title)

@section('content')
<div class="grid grid-4">
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">عدد السجلات</div>
                <div class="value">{{ number_format($totalCount) }}</div>
                <div class="trend">إجمالي السجلات</div>
            </div>
            <span class="chip">{{ $screen->key }}</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">الحالة</div>
                <div class="value">نشط</div>
                <div class="trend">جميع العمليات متاحة</div>
            </div>
            <span class="chip">Live</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">النوع</div>
                <div class="value" style="font-size:18px;">{{ $screen->screen_type }}</div>
                <div class="trend">{{ $screen->api_resource ?? '—' }}</div>
            </div>
            <span class="chip">Type</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">آخر صفحة</div>
                <div class="value">{{ $records->currentPage() }}/{{ $records->lastPage() }}</div>
                <div class="trend">{{ $records->perPage() }} سجل/صفحة</div>
            </div>
            <span class="chip">Page</span>
        </div>
    </article>
</div>

<article class="panel">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h2>{{ $screen->title }} — السجلات</h2>
        <div style="display:flex; gap:8px;">
            <input type="text" id="searchInput" placeholder="بحث..." style="background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:8px 12px;color:#fff;font-size:13px;" onkeyup="filterTable()">
        </div>
    </div>

    @if($records->isEmpty())
        <div style="text-align:center; padding:40px; color:var(--muted);">
            <p style="font-size:18px;">لا توجد سجلات حالياً</p>
            <p style="font-size:13px;">لم يتم العثور على بيانات في جدول {{ $screen->title }}</p>
        </div>
    @else
        <div style="overflow-x:auto;">
            <table id="dataTable">
                <thead>
                    <tr>
                        <th>#</th>
                        @foreach($columns as $col)
                            <th>{{ $col['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $index => $record)
                        <tr>
                            <td>{{ ($records->currentPage() - 1) * $records->perPage() + $index + 1 }}</td>
                            @foreach($columns as $col)
                                <td>
                                    @if(($col['type'] ?? '') === 'boolean')
                                        <span class="status {{ $record->{$col['key']} ? 'good' : 'bad' }}">
                                            {{ $record->{$col['key']} ? 'مفعل' : 'غير مفعل' }}
                                        </span>
                                    @elseif(str_contains($col['key'], 'price') || str_contains($col['key'], 'amount') || str_contains($col['key'], 'balance'))
                                        {{ number_format($record->{$col['key']} ?? 0, 2) }}
                                    @else
                                        {{ $record->{$col['key']} ?? '—' }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="display:flex; justify-content:center; margin-top:16px;">
            {{ $records->withQueryString()->links() }}
        </div>
    @endif
</article>

<div class="panel">
    <h2>إجراءات سريعة</h2>
    <div class="mini-list">
        <div class="mini-row">
            <span>إضافة سجل جديد</span>
            <button class="btn primary" disabled title="قريباً">+ جديد</button>
        </div>
        <div class="mini-row">
            <span>تصدير البيانات</span>
            <button class="btn" disabled title="قريباً">Export</button>
        </div>
        <div class="mini-row">
            <span>عرض السجلات المحذوفة</span>
            <button class="btn" disabled title="قريباً">Trash</button>
        </div>
    </div>
</div>

<a class="btn" href="{{ url('/admin') }}">↩ العودة إلى قائمة الوحدات</a>

<script>
function filterTable() {
    var input = document.getElementById('searchInput');
    var filter = input.value.toUpperCase();
    var table = document.getElementById('dataTable');
    if (!table) return;
    var tr = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    for (var i = 0; i < tr.length; i++) {
        var found = false;
        var td = tr[i].getElementsByTagName('td');
        for (var j = 0; j < td.length; j++) {
            if (td[j].textContent.toUpperCase().indexOf(filter) > -1) {
                found = true;
                break;
            }
        }
        tr[i].style.display = found ? '' : 'none';
    }
}
</script>
@endsection
