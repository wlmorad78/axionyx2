@extends('layouts.app')

@section('title', "تفاصيل المركبة {$vehicle->vehicle_code} - Axionyx ERP")
@section('page_title', "تفاصيل المركبة {$vehicle->vehicle_code}")
@section('page_subtitle', 'عرض بيانات المركبة التفصيلية')

@section('content')
@if(session('success'))
    <div style="padding:12px 16px;border-radius:10px;background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.18);color:#bbf7d0;margin-bottom:16px;font-size:14px;">
        {{ session('success') }}
    </div>
@endif

<div class="grid grid-4">
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">كود المركبة</div>
                <div class="value" style="font-size:20px;color:var(--accent);">{{ $vehicle->vehicle_code }}</div>
            </div>
            <span class="chip">Code</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">رقم اللوحة</div>
                <div class="value" style="font-size:20px;">{{ $vehicle->plate_number }}</div>
            </div>
            <span class="chip">Plate</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">الطراز</div>
                <div class="value" style="font-size:20px;">{{ $vehicle->model }}</div>
            </div>
            <span class="chip">Model</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">الحالة</div>
                <div class="value" style="font-size:20px;">
                    @if($vehicle->status === 'active')
                        <span class="status good">● نشطة</span>
                    @elseif($vehicle->status === 'maintenance')
                        <span class="status warn">● قيد الصيانة</span>
                    @else
                        <span class="status bad">● غير نشطة</span>
                    @endif
                </div>
            </div>
        </div>
    </article>
</div>

<div class="grid grid-2">
    <article class="panel">
        <h3>البيانات الأساسية</h3>
        <div class="mini-list">
            <div class="mini-row">
                <span class="muted">النوع</span>
                <span>{{ $vehicle->vehicleType?->name ?? '—' }}</span>
            </div>
            <div class="mini-row">
                <span class="muted">سنة الصنع</span>
                <span>{{ $vehicle->year ?? '—' }}</span>
            </div>
            <div class="mini-row">
                <span class="muted">السعة</span>
                <span>{{ $vehicle->capacity ? number_format($vehicle->capacity, 2) . ' طن' : '—' }}</span>
            </div>
            <div class="mini-row">
                <span class="muted">تاريخ الإنشاء</span>
                <span>{{ $vehicle->created_at?->format('Y-m-d H:i') ?? '—' }}</span>
            </div>
        </div>
    </article>

    <article class="panel">
        <h3>الملخص</h3>
        <div class="mini-list">
            <div class="mini-row">
                <span class="muted">الوثائق</span>
                <span><span class="chip">{{ $vehicle->documents->count() }}</span></span>
            </div>
            <div class="mini-row">
                <span class="muted">التخصيصات</span>
                <span><span class="chip">{{ $vehicle->assignments->count() }}</span></span>
            </div>
            <div class="mini-row">
                <span class="muted">سجل الصيانة</span>
                <span><span class="chip">{{ $vehicle->maintenance->count() }}</span></span>
            </div>
            <div class="mini-row">
                <span class="muted">الإطارات</span>
                <span><span class="chip">{{ $vehicle->tires->count() }}</span></span>
            </div>
            <div class="mini-row">
                <span class="muted">البطاريات</span>
                <span><span class="chip">{{ $vehicle->batteries->count() }}</span></span>
            </div>
            <div class="mini-row">
                <span class="muted">بوليصات التأمين</span>
                <span><span class="chip">{{ $vehicle->insurance->count() }}</span></span>
            </div>
        </div>
    </article>
</div>

@if($vehicle->assignments->count())
<article class="panel">
    <h3>سجل التخصيصات</h3>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>السائق</th>
                    <th>من تاريخ</th>
                    <th>إلى تاريخ</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                @foreach($vehicle->assignments as $assignment)
                    <tr>
                        <td>{{ $assignment->driver?->employee?->name ?? '—' }}</td>
                        <td>{{ $assignment->from_date }}</td>
                        <td>{{ $assignment->to_date ?? '—' }}</td>
                        <td>
                            @if($assignment->status === 'active')
                                <span class="status good">● نشط</span>
                            @else
                                <span class="status" style="background:rgba(148,163,184,0.12);color:#94a3b8;border-color:rgba(148,163,184,0.18);">{{ $assignment->status }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</article>
@endif

<div style="display:flex; gap:12px; margin-top:20px;">
    <a href="{{ route('vehicles.edit', $vehicle->id) }}" class="btn primary">تعديل المركبة</a>
    <a href="{{ route('vehicles.index') }}" class="btn">العودة للقائمة</a>
    <form method="POST" action="{{ route('vehicles.destroy', $vehicle->id) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذه المركبة؟')">
        @csrf @method('DELETE')
        <button type="submit" class="btn" style="color:var(--danger);border-color:rgba(251,113,133,0.3);">حذف المركبة</button>
    </form>
</div>
@endsection
