@extends('layouts.app')

@section('title', "تعديل المركبة {$vehicle->vehicle_code} - Axionyx ERP")
@section('page_title', "تعديل المركبة {$vehicle->vehicle_code}")
@section('page_subtitle', 'تحديث بيانات المركبة')

@section('content')
<article class="panel">
    <h2>بيانات المركبة</h2>

    @if(session('error'))
        <div style="padding:12px 16px;border-radius:10px;background:rgba(251,113,133,0.12);border:1px solid rgba(251,113,133,0.18);color:#fecdd3;margin-bottom:16px;font-size:14px;">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('vehicles.update', $vehicle->id) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-2" style="margin-bottom:20px;">
            <div>
                <label style="display:block;margin-bottom:6px;color:var(--muted);font-size:13px;">كود المركبة</label>
                <input type="text" name="vehicle_code" value="{{ old('vehicle_code', $vehicle->vehicle_code) }}" placeholder="مثال: VH-001" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:10px;padding:10px 12px;color:#fff;font-size:14px;">
                @error('vehicle_code') <span style="color:var(--danger);font-size:12px;">{{ $message }}</span> @enderror
            </div>
            <div>
                <label style="display:block;margin-bottom:6px;color:var(--muted);font-size:13px;">رقم اللوحة *</label>
                <input type="text" name="plate_number" value="{{ old('plate_number', $vehicle->plate_number) }}" required placeholder="مثال: أ ب ج 1234" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:10px;padding:10px 12px;color:#fff;font-size:14px;">
                @error('plate_number') <span style="color:var(--danger);font-size:12px;">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="grid grid-2" style="margin-bottom:20px;">
            <div>
                <label style="display:block;margin-bottom:6px;color:var(--muted);font-size:13px;">نوع المركبة *</label>
                <select name="vehicle_type_id" required style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:10px;padding:10px 12px;color:#fff;font-size:14px;">
                    <option value="">-- اختر النوع --</option>
                    @foreach($vehicleTypes as $type)
                        <option value="{{ $type->id }}" {{ old('vehicle_type_id', $vehicle->vehicle_type_id) == $type->id ? 'selected' : '' }}>
                        {{ $type->name }}
                        </option>
                    @endforeach
                </select>
                @error('vehicle_type_id') <span style="color:var(--danger);font-size:12px;">{{ $message }}</span> @enderror
            </div>
            <div>
                <label style="display:block;margin-bottom:6px;color:var(--muted);font-size:13px;">الطراز / الموديل *</label>
                <input type="text" name="model" value="{{ old('model', $vehicle->model) }}" required placeholder="مثال: Toyota Hiace 2024" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:10px;padding:10px 12px;color:#fff;font-size:14px;">
                @error('model') <span style="color:var(--danger);font-size:12px;">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="grid grid-2" style="margin-bottom:20px;">
            <div>
                <label style="display:block;margin-bottom:6px;color:var(--muted);font-size:13px;">سنة الصنع</label>
                <input type="number" name="year" value="{{ old('year', $vehicle->year) }}" min="1900" max="{{ date('Y') + 1 }}" placeholder="مثال: 2024" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:10px;padding:10px 12px;color:#fff;font-size:14px;">
                @error('year') <span style="color:var(--danger);font-size:12px;">{{ $message }}</span> @enderror
            </div>
            <div>
                <label style="display:block;margin-bottom:6px;color:var(--muted);font-size:13px;">السعة (طن)</label>
                <input type="number" name="capacity" value="{{ old('capacity', $vehicle->capacity) }}" min="0" step="0.01" placeholder="مثال: 1.5" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:10px;padding:10px 12px;color:#fff;font-size:14px;">
                @error('capacity') <span style="color:var(--danger);font-size:12px;">{{ $message }}</span> @enderror
            </div>
        </div>

        <div style="margin-bottom:20px;">
            <label style="display:block;margin-bottom:6px;color:var(--muted);font-size:13px;">حالة المركبة *</label>
            <select name="status" required style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:10px;padding:10px 12px;color:#fff;font-size:14px;">
                <option value="active" {{ old('status', $vehicle->status) === 'active' ? 'selected' : '' }}>● نشطة</option>
                <option value="maintenance" {{ old('status', $vehicle->status) === 'maintenance' ? 'selected' : '' }}>● قيد الصيانة</option>
                <option value="inactive" {{ old('status', $vehicle->status) === 'inactive' ? 'selected' : '' }}>● غير نشطة</option>
            </select>
            @error('status') <span style="color:var(--danger);font-size:12px;">{{ $message }}</span> @enderror
        </div>

        <div style="margin-top:24px; display:flex; gap:12px; justify-content:flex-end;">
            <a href="{{ route('vehicles.show', $vehicle->id) }}" class="btn">إلغاء</a>
            <button type="submit" class="btn primary">تحديث البيانات</button>
        </div>
    </form>
</article>
@endsection
