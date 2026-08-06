@extends('layouts.app')

@section('title', 'تسوية بنكية جديدة - Axionyx ERP')
@section('page_title', 'تسوية بنكية جديدة')
@section('page_subtitle', 'إنشاء تسوية للحساب البنكي')

@section('content')
<article class="panel" style="max-width:700px;">
    <h2>بيانات التسوية البنكية</h2>

    <form method="POST" action="{{ route('bank-reconciliations.store') }}" style="display:flex; flex-direction:column; gap:16px; margin-top:16px;">
        @csrf

        <div>
            <label style="display:block; margin-bottom:4px; font-size:13px; color:var(--muted);">الحساب البنكي <span style="color:var(--danger);">*</span></label>
            <select name="bank_account_id" required style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
                <option value="">اختر الحساب</option>
                @foreach($bankAccounts as $account)
                    <option value="{{ $account->id }}" {{ old('bank_account_id') == $account->id ? 'selected' : '' }}>{{ $account->bank_name }} - {{ $account->account_number }} ({{ number_format($account->current_balance, 2) }})</option>
                @endforeach
            </select>
            @error('bank_account_id') <div style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</div> @enderror
        </div>

        <div>
            <label style="display:block; margin-bottom:4px; font-size:13px; color:var(--muted);">تاريخ التسوية <span style="color:var(--danger);">*</span></label>
            <input type="date" name="reconciliation_date" value="{{ old('reconciliation_date', date('Y-m-d')) }}" required style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
            @error('reconciliation_date') <div style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</div> @enderror
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div>
                <label style="display:block; margin-bottom:4px; font-size:13px; color:var(--muted);">رصيد كشف الحساب <span style="color:var(--danger);">*</span></label>
                <input type="number" step="0.01" name="statement_balance" value="{{ old('statement_balance') }}" required style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
                @error('statement_balance') <div style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</div> @enderror
            </div>
            <div>
                <label style="display:block; margin-bottom:4px; font-size:13px; color:var(--muted);">الرصيد الدفتري <span style="color:var(--danger);">*</span></label>
                <input type="number" step="0.01" name="book_balance" value="{{ old('book_balance') }}" required style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
                @error('book_balance') <div style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</div> @enderror
            </div>
        </div>

        <div>
            <label style="display:block; margin-bottom:4px; font-size:13px; color:var(--muted);">ملاحظات</label>
            <textarea name="notes" rows="3" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">{{ old('notes') }}</textarea>
        </div>

        <div style="display:flex; gap:8px; margin-top:8px;">
            <button type="submit" class="btn primary">حفظ التسوية</button>
            <a href="{{ route('bank-reconciliations.index') }}" class="btn">إلغاء</a>
        </div>
    </form>
</article>
@endsection
