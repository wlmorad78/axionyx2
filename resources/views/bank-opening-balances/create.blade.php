@extends('layouts.app')

@section('title', 'إضافة رصيد افتتاحي للبنك - Axionyx ERP')
@section('page_title', 'إضافة رصيد افتتاحي للبنك')
@section('page_subtitle', 'تحديد الرصيد الافتتاحي لأحدى حسابات البنوك')

@section('content')
<article class="panel">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h2 style="margin:0;">إضافة رصيد افتتاحي</h2>
        <a href="{{ route('bank-opening-balances.index') }}" class="btn" style="font-size:13px;">العودة للقائمة</a>
    </div>

    @if(session('error'))
        <div style="padding:12px 16px;border-radius:10px;background:rgba(251,113,133,0.12);border:1px solid rgba(251,113,133,0.18);color:#fecdd3;margin-bottom:16px;font-size:14px;">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('bank-opening-balances.store') }}">
        @csrf

        <div class="grid grid-2" style="margin-bottom:16px;">
            <div>
                <label style="display:block;margin-bottom:6px;font-size:13px;color:var(--muted);">الحساب البنكي *</label>
                <select name="bank_account_id" required style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
                    <option value="">اختر الحساب البنكي</option>
                    @foreach($bankAccounts as $account)
                        <option value="{{ $account->id }}" {{ $selectedBankAccount == $account->id ? 'selected' : '' }}>
                            {{ $account->bank_name }} - {{ $account->account_name }} ({{ $account->account_number }})
                        </option>
                    @endforeach
                </select>
                @error('bank_account_id') <span style="color:var(--danger);font-size:12px;">{{ $message }}</span> @enderror
            </div>
            <div>
                <label style="display:block;margin-bottom:6px;font-size:13px;color:var(--muted);">السنة المالية</label>
                <select name="fiscal_year_id" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
                    <option value="">اختر السنة المالية</option>
                    @foreach($fiscalYears as $year)
                        <option value="{{ $year->id }}">{{ $year->year_code }}</option>
                    @endforeach
                </select>
                @error('fiscal_year_id') <span style="color:var(--danger);font-size:12px;">{{ $message }}</span> @enderror
            </div>
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;margin-bottom:6px;font-size:13px;color:var(--muted);">الرصيد الافتتاحي *</label>
            <input type="number" name="opening_balance" step="0.01" min="0" value="{{ old('opening_balance', '0') }}" required
                style="width:100%;max-width:400px;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;"
                placeholder="أدخل الرصيد الافتتاحي">
            @error('opening_balance') <span style="color:var(--danger);font-size:12px;">{{ $message }}</span> @enderror
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;margin-bottom:6px;font-size:13px;color:var(--muted);">ملاحظات</label>
            <textarea name="notes" rows="3" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;resize:vertical;" placeholder="أدخل ملاحظات اختيارية">{{ old('notes') }}</textarea>
            @error('notes') <span style="color:var(--danger);font-size:12px;">{{ $message }}</span> @enderror
        </div>

        <div style="display:flex; gap:8px;">
            <button type="submit" class="btn primary" style="font-size:14px;padding:10px 24px;">حفظ الرصيد</button>
            <a href="{{ route('bank-opening-balances.index') }}" class="btn" style="font-size:14px;padding:10px 24px;">إلغاء</a>
        </div>
    </form>
</article>
@endsection
