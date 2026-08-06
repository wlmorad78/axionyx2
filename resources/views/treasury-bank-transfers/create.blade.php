@extends('layouts.app')

@section('title', 'تحويل جديد - Axionyx ERP')
@section('page_title', 'تحويل بين الخزنة والبنك')
@section('page_subtitle', 'إنشاء تحويل جديد')

@section('content')
<article class="panel" style="max-width:700px;">
    <h2>بيانات التحويل</h2>

    <form method="POST" action="{{ route('treasury-bank-transfers.store') }}" style="display:flex; flex-direction:column; gap:16px; margin-top:16px;">
        @csrf

        <div>
            <label style="display:block; margin-bottom:4px; font-size:13px; color:var(--muted);">نوع التحويل <span style="color:var(--danger);">*</span></label>
            <select name="transfer_type" id="transfer_type" required style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
                <option value="treasury_to_bank" {{ old('transfer_type') === 'treasury_to_bank' ? 'selected' : '' }}>من الخزنة إلى البنك</option>
                <option value="bank_to_treasury" {{ old('transfer_type') === 'bank_to_treasury' ? 'selected' : '' }}>من البنك إلى الخزنة</option>
            </select>
            @error('transfer_type') <div style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</div> @enderror
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div>
                <label style="display:block; margin-bottom:4px; font-size:13px; color:var(--muted);">الخزنة <span style="color:var(--danger);">*</span></label>
                <select name="treasury_id" required style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
                    <option value="">اختر الخزنة</option>
                    @foreach($treasuries as $treasury)
                        <option value="{{ $treasury->id }}" {{ old('treasury_id') == $treasury->id ? 'selected' : '' }}>
                            {{ $treasury->name }} ({{ number_format($treasury->balance, 2) }})
                        </option>
                    @endforeach
                </select>
                @error('treasury_id') <div style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</div> @enderror
            </div>
            <div>
                <label style="display:block; margin-bottom:4px; font-size:13px; color:var(--muted);">حساب البنك <span style="color:var(--danger);">*</span></label>
                <select name="bank_account_id" required style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
                    <option value="">اختر الحساب</option>
                    @foreach($bankAccounts as $account)
                        <option value="{{ $account->id }}" {{ old('bank_account_id') == $account->id ? 'selected' : '' }}>
                            {{ $account->bank_name }} - {{ $account->account_number }} ({{ number_format($account->current_balance, 2) }})
                        </option>
                    @endforeach
                </select>
                @error('bank_account_id') <div style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</div> @enderror
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
                <input type="number" name="amount" value="{{ old('amount') }}" step="0.01" min="0.01" required placeholder="0.00" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
                @error('amount') <div style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</div> @enderror
            </div>
        </div>

        <div>
            <label style="display:block; margin-bottom:4px; font-size:13px; color:var(--muted);">الوصف</label>
            <textarea name="description" rows="2" placeholder="وصف التحويل..." style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">{{ old('description') }}</textarea>
        </div>

        <div>
            <label style="display:block; margin-bottom:4px; font-size:13px; color:var(--muted);">ملاحظات</label>
            <textarea name="notes" rows="2" placeholder="ملاحظات إضافية..." style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">{{ old('notes') }}</textarea>
        </div>

        <div style="display:flex; gap:8px; margin-top:8px;">
            <button type="submit" class="btn primary">تنفيذ التحويل</button>
            <a href="{{ route('treasury-bank-transfers.index') }}" class="btn">إلغاء</a>
        </div>
    </form>
</article>
@endsection
