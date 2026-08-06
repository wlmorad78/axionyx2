@extends('layouts.app')

@section('title', 'إضافة حساب بنكي - Axionyx ERP')
@section('page_title', 'إضافة حساب بنكي جديد')
@section('page_subtitle', 'أدخل بيانات الحساب البنكي الجديد')

@section('content')
<article class="panel" style="max-width:800px;">
    <h2>بيانات الحساب البنكي</h2>

    <form method="POST" action="{{ route('bank-accounts.store') }}" style="display:flex; flex-direction:column; gap:16px; margin-top:16px;">
        @csrf

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div>
                <label style="display:block; margin-bottom:4px; font-size:13px; color:var(--muted);">اسم البنك <span style="color:var(--danger);">*</span></label>
                <input type="text" name="bank_name" value="{{ old('bank_name') }}" required style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
                @error('bank_name') <div style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</div> @enderror
            </div>
            <div>
                <label style="display:block; margin-bottom:4px; font-size:13px; color:var(--muted);">رقم الحساب <span style="color:var(--danger);">*</span></label>
                <input type="text" name="account_number" value="{{ old('account_number') }}" required style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
                @error('account_number') <div style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</div> @enderror
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div>
                <label style="display:block; margin-bottom:4px; font-size:13px; color:var(--muted);">اسم الحساب</label>
                <input type="text" name="account_name" value="{{ old('account_name') }}" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
            </div>
            <div>
                <label style="display:block; margin-bottom:4px; font-size:13px; color:var(--muted);">رقم الحساب (بديل)</label>
                <input type="text" name="account_no" value="{{ old('account_no') }}" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div>
                <label style="display:block; margin-bottom:4px; font-size:13px; color:var(--muted);">IBAN</label>
                <input type="text" name="iban" value="{{ old('iban') }}" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
            </div>
            <div>
                <label style="display:block; margin-bottom:4px; font-size:13px; color:var(--muted);">SWIFT Code</label>
                <input type="text" name="swift_code" value="{{ old('swift_code') }}" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div>
                <label style="display:block; margin-bottom:4px; font-size:13px; color:var(--muted);">الفرع</label>
                <input type="text" name="branch_name" value="{{ old('branch_name') }}" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
            </div>
            <div>
                <label style="display:block; margin-bottom:4px; font-size:13px; color:var(--muted);">العملة</label>
                <select name="currency_id" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
                    <option value="">اختر العملة</option>
                    @foreach($currencies as $currency)
                        <option value="{{ $currency->id }}" {{ old('currency_id') == $currency->id ? 'selected' : '' }}>{{ $currency->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div>
                <label style="display:block; margin-bottom:4px; font-size:13px; color:var(--muted);">الرصيد الافتتاحي</label>
                <input type="number" step="0.01" name="opening_balance" value="{{ old('opening_balance', 0) }}" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
            </div>
            <div>
                <label style="display:block; margin-bottom:4px; font-size:13px; color:var(--muted);">الرصيد الحالي</label>
                <input type="number" step="0.01" name="current_balance" value="{{ old('current_balance', 0) }}" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
            </div>
        </div>

        <div>
            <label style="display:block; margin-bottom:4px; font-size:13px; color:var(--muted);">ملاحظات</label>
            <textarea name="notes" rows="3" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">{{ old('notes') }}</textarea>
        </div>

        <div style="display:flex; align-items:center; gap:8px;">
            <label>
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                <span style="font-size:13px;">الحساب نشط</span>
            </label>
        </div>

        <div style="display:flex; gap:8px; margin-top:8px;">
            <button type="submit" class="btn primary">حفظ الحساب البنكي</button>
            <a href="{{ route('bank-accounts.index') }}" class="btn">إلغاء</a>
        </div>
    </form>
</article>
@endsection
