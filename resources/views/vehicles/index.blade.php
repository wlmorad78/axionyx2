@extends('layouts.app')

@section('title', 'إدارة المركبات - Axionyx ERP')
@section('page_title', 'إدارة المركبات')
@section('page_subtitle', 'إدارة بيانات المركبات والأسطول')

@section('content')
<div class="grid grid-4">
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">إجمالي المركبات</div>
                <div class="value">{{ $stats['total'] }}</div>
                <div class="trend">مركبة مسجلة</div>
            </div>
            <span class="chip">All</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">نشطة</div>
                <div class="value">{{ $stats['active'] }}</div>
                <div class="trend">مركبة جاهزة</div>
            </div>
            <span class="chip good">Active</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">قيد الصيانة</div>
                <div class="value">{{ $stats['maintenance'] }}</div>
                <div class="trend">مركبة بالورشة</div>
            </div>
            <span class="chip warn">Maintenance</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">غير نشطة</div>
                <div class="value">{{ $stats['inactive'] }}</div>
                <div class="trend">مركبة معطلة</div>
            </div>
            <span class="chip bad">Inactive</span>
        </div>
    </article>
</div>

<article class="panel">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h2>قائمة المركبات</h2>
        <div style="display:flex; gap:8px;">
            <form method="GET" style="display:flex; gap:8px; align-items:center;">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="بحث بالكود أو رقم اللوحة أو الطراز..." style="background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:8px 12px;color:#fff;font-size:13px;">
                <select name="vehicle_type_id" style="background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:8px 12px;color:#fff;font-size:13px;">
                    <option value="">جميع الأنواع</option>
                    @foreach($vehicleTypes as $type)
                        <option value="{{ $type->id }}" {{ request('vehicle_type_id') == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
                <select name="status" style="background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:8px 12px;color:#fff;font-size:13px;">
                    <option value="">جميع الحالات</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>نشطة</option>
                    <option value="maintenance" {{ request('status') === 'maintenance' ? 'selected' : '' }}>قيد الصيانة</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>غير نشطة</option>
                </select>
                <button type="submit" class="btn">بحث</button>
            </form>
            <a href="{{ route('vehicles.create') }}" class="btn primary">+ مركبة جديدة</a>
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

    @if($vehicles->isEmpty())
        <div style="text-align:center; padding:40px; color:var(--muted);">
            <p style="font-size:18px;">لا توجد مركبات مسجلة حالياً</p>
            <p style="font-size:13px;">اضغط على "مركبة جديدة" لإضافة أول مركبة</p>
        </div>
    @else
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>كود المركبة</th>
                        <th>رقم اللوحة</th>
                        <th>النوع</th>
                        <th>الطراز</th>
                        <th>السنة</th>
                        <th>السعة</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vehicles as $index => $vehicle)
                        <tr>
                            <td>{{ ($vehicles->currentPage() - 1) * $vehicles->perPage() + $index + 1 }}</td>
                            <td><strong style="color:var(--accent);">{{ $vehicle->vehicle_code }}</strong></td>
                            <td>{{ $vehicle->plate_number }}</td>
                            <td>{{ $vehicle->vehicleType?->name ?? '—' }}</td>
                            <td>{{ $vehicle->model }}</td>
                            <td>{{ $vehicle->year ?? '—' }}</td>
                            <td>{{ $vehicle->capacity ? number_format($vehicle->capacity, 2) . ' طن' : '—' }}</td>
                            <td>
                                @if($vehicle->status === 'active')
                                    <span class="status good">● نشطة</span>
                                @elseif($vehicle->status === 'maintenance')
                                    <span class="status warn">● قيد الصيانة</span>
                                @else
                                    <span class="status bad">● غير نشطة</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex; gap:6px;">
                                    <a href="{{ route('vehicles.show', $vehicle->id) }}" class="btn" style="padding:6px 10px;font-size:12px;">عرض</a>
                                    <a href="{{ route('vehicles.edit', $vehicle->id) }}" class="btn" style="padding:6px 10px;font-size:12px;border-color:var(--accent);color:var(--accent);">تعديل</a>
                                    <form method="POST" action="{{ route('vehicles.destroy', $vehicle->id) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذه المركبة؟')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn" style="padding:6px 10px;font-size:12px;color:var(--danger);border-color:rgba(251,113,133,0.3);">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="display:flex; justify-content:center; margin-top:16px;">
            {{ $vehicles->withQueryString()->links() }}
        </div>
    @endif
</article>
@endsection
