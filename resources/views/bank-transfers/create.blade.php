@extends('layouts.app')

@section('title', 'تحويل بنكي جديد - Axionyx ERP')
@section('page_title', 'تحويل بنكي جديد')
@section('page_subtitle', 'إنشاء تحويل بين الحسابات البنكية')

@section('content')
<article class="panel" style="max-width:700px;">
    <h2>بيانات التحويل البنكي</h2>

    <form method="POST" action="{{ route('bank-transfers.store') }}" style="display:flex; flex-direction:column; gap:16px; margin-top:16px;">
        @csrf

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div>
                <label style="display:block; margin-bottom:4px; font-size:13px; color:var(--muted);">من حساب <span style="color:var(--danger);">*</span></label>
                <select name="from_bank_account_id" required style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
                    <option value="">اختر الحساب</option>
                    @foreach($bankAccounts as $account)
                        <option value="{{ $account->id }}" {{ old('from_bank_account_id') == $account->id ? 'selected' : '' }}>{{ $account->bank_name }} - {{ $account->account_number }} ({{ number_format($account->current_balance, 2) }})</option>
                    @endforeach
                </select>
                @error('from_bank_account_id') <div style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</div> @enderror
            </div>
            <div>
                <label style="display:block; margin-bottom:4px; font-size:13px; color:var(--muted);">إلى حساب <span style="color:var(--danger);">*</span></label>
                <select name="to_bank_account_id" required style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
                    <option value="">اختر الحساب</option>
                    @foreach($bankAccounts as $account)
                        <option value="{{ $account->id }}" {{ old('to_bank_account_id') == $account->id ? 'selected' : '' }}>{{ $account->bank_name }} - {{ $account->account_number }} ({{ number_format($account->current_balance, 2) }})</option>
                    @endforeach
                </select>
                @error('to_bank_account_id') <div style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</div> @enderror
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div>
                <label style="display:block; margin-bottom:4px; font-size:13px; color:var(--muted);">تاريخ التحويل <span style="color:var(--danger);">*</span></label>
                <input type="date" name="transfer_date" value="{{ old('transfer_date', date('Y-m-d')) }}" required style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
                @error('transfer_date') <div style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</div> @enderror
            </div>
            <div>
                <label style="display:block; margin-bottom:4px; font-size:13px; color:var(--muted);">المبلغ <span style="color:var(--danger);">*</span></label>
                <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
                @error('amount') <div style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</div> @enderror
            </div>
        </div>

        <div>
            <label style="display:block; margin-bottom:4px; font-size:13px; color:var(--muted);">الوصف</label>
            <textarea name="description" rows="2" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">{{ old('description') }}</textarea>
        </div>

        <div>
            <label style="display:block; margin-bottom:4px; font-size:13px; color:var(--muted);">ملاحظات</label>
            <textarea name="notes" rows="2" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">{{ old('notes') }}</textarea>
        </div>

        <div style="display:flex; gap:8px; margin-top:8px;">
            <button type="submit" class="btn primary">تنفيذ التحويل</button>
            <a href="{{ route('bank-transfers.index') }}" class="btn">إلغاء</a>
        </div>
    </form>
</article>
@endsection
