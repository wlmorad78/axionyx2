@extends('layouts.app')

@section('title', 'تعديل الرصيد الافتتاحي - Axionyx ERP')
@section('page_title', 'تعديل الرصيد الافتتاحي')
@section('page_subtitle', $treasuryOpeningBalance->treasury?->name_ar ?? 'خزنة')

@section('content')
<article class="panel">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h2 style="margin:0;">تعديل الرصيد الافتتاحي</h2>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('treasury-opening-balances.show', $treasuryOpeningBalance->id) }}" class="btn" style="font-size:13px;">عرض التفاصيل</a>
            <a href="{{ route('treasury-opening-balances.index') }}" class="btn" style="font-size:13px;">العودة للقائمة</a>
        </div>
    </div>

    @if(session('error'))
        <div style="padding:12px 16px;border-radius:10px;background:rgba(251,113,133,0.12);border:1px solid rgba(251,113,133,0.18);color:#fecdd3;margin-bottom:16px;font-size:14px;">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('treasury-opening-balances.update', $treasuryOpeningBalance->id) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-2" style="margin-bottom:16px;">
            <div>
                <label style="display:block;margin-bottom:6px;font-size:13px;color:var(--muted);">الخزنة *</label>
                <select name="treasury_id" required style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
                    <option value="">اختر الخزنة</option>
                    @foreach($treasuries as $treasury)
                        <option value="{{ $treasury->id }}" {{ $treasuryOpeningBalance->treasury_id == $treasury->id ? 'selected' : '' }}>
                            {{ $treasury->code }} - {{ $treasury->name_ar }}
                        </option>
                    @endforeach
                </select>
                @error('treasury_id') <span style="color:var(--danger);font-size:12px;">{{ $message }}</span> @enderror
            </div>
            <div>
                <label style="display:block;margin-bottom:6px;font-size:13px;color:var(--muted);">السنة المالية</label>
                <select name="fiscal_year_id" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
                    <option value="">اختر السنة المالية</option>
                    @foreach($fiscalYears as $year)
                        <option value="{{ $year->id }}" {{ $treasuryOpeningBalance->fiscal_year_id == $year->id ? 'selected' : '' }}>
                            {{ $year->year_code }}
                        </option>
                    @endforeach
                </select>
                @error('fiscal_year_id') <span style="color:var(--danger);font-size:12px;">{{ $message }}</span> @enderror
            </div>
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;margin-bottom:6px;font-size:13px;color:var(--muted);">الرصيد الافتتاحي *</label>
            <input type="number" name="opening_balance" step="0.01" min="0" value="{{ old('opening_balance', $treasuryOpeningBalance->opening_balance) }}" required
                style="width:100%;max-width:400px;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;"
                placeholder="أدخل الرصيد الافتتاحي">
            @error('opening_balance') <span style="color:var(--danger);font-size:12px;">{{ $message }}</span> @enderror
        </div>

        <div style="display:flex; gap:8px;">
            <button type="submit" class="btn primary" style="font-size:14px;padding:10px 24px;">تحديث الرصيد</button>
            <a href="{{ route('treasury-opening-balances.index') }}" class="btn" style="font-size:14px;padding:10px 24px;">إلغاء</a>
        </div>
    </form>
</article>
@endsection
