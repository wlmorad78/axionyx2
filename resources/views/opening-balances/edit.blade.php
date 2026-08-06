@extends('layouts.app')

@section('title', 'تعديل قيد الأرصدة الافتتاحية - Axionyx ERP')
@section('page_title', 'تعديل قيد الأرصدة الافتتاحية')
@section('page_subtitle', $openingBalance->document_no)

@section('content')
<article class="panel">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h2 style="margin:0;">تعديل {{ $openingBalance->document_no }}</h2>
        <a href="{{ route('opening-balances.show', $openingBalance->id) }}" class="btn" style="font-size:13px;">العودة للعرض</a>
    </div>

    @if(session('error'))
        <div style="padding:12px 16px;border-radius:10px;background:rgba(251,113,133,0.12);border:1px solid rgba(251,113,133,0.18);color:#fecdd3;margin-bottom:16px;font-size:14px;">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('opening-balances.update', $openingBalance->id) }}" id="balanceForm">
        @csrf
        @method('PUT')
        <input type="hidden" name="balance_type" id="balanceType" value="{{ $openingBalance->balance_type }}">

        <div class="grid grid-2" style="margin-bottom:16px;">
            <div>
                <label style="display:block;margin-bottom:6px;font-size:13px;color:var(--muted);">التاريخ *</label>
                <input type="date" name="document_date" value="{{ $openingBalance->document_date?->format('Y-m-d') }}" required
                    style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
                @error('document_date') <span style="color:var(--danger);font-size:12px;">{{ $message }}</span> @enderror
            </div>
            <div>
                <label style="display:block;margin-bottom:6px;font-size:13px;color:var(--muted);">الفرع</label>
                <select name="branch_id" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;">
                    <option value="">اختر الفرع</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ $openingBalance->branch_id == $branch->id ? 'selected' : '' }}>{{ $branch->name_ar }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;margin-bottom:6px;font-size:13px;color:var(--muted);">ملاحظات</label>
            <textarea name="notes" rows="2" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:10px 12px;color:#fff;font-size:13px;" placeholder="ملاحظات اختيارية...">{{ $openingBalance->notes }}</textarea>
        </div>

        <div style="display:flex; gap:8px; margin-bottom:16px;">
            <button type="button" onclick="setType('cash')" id="btnCash" class="btn" style="font-size:12px;padding:8px 16px;{{ $openingBalance->balance_type === 'cash' ? 'background:rgba(34,197,94,0.2);border-color:var(--primary);' : '' }}">الخزنة</button>
            <button type="button" onclick="setType('accounts')" id="btnAccounts" class="btn" style="font-size:12px;padding:8px 16px;{{ $openingBalance->balance_type === 'accounts' ? 'background:rgba(56,189,248,0.2);border-color:var(--accent);' : '' }}">البنك</button>
            <button type="button" onclick="setType('inventory')" id="btnInventory" class="btn" style="font-size:12px;padding:8px 16px;{{ $openingBalance->balance_type === 'inventory' ? 'background:rgba(168,85,247,0.2);border-color:#a855f7;' : '' }}">المنتجات</button>
            <button type="button" onclick="setType('suppliers')" id="btnSuppliers" class="btn" style="font-size:12px;padding:8px 16px;{{ $openingBalance->balance_type === 'suppliers' ? 'background:rgba(245,158,11,0.2);border-color:var(--warn);' : '' }}">الموردين</button>
            <button type="button" onclick="setType('customers')" id="btnCustomers" class="btn" style="font-size:12px;padding:8px 16px;{{ $openingBalance->balance_type === 'customers' ? 'background:rgba(236,72,153,0.2);border-color:#ec4899;' : '' }}">العملاء</button>
            <button type="button" onclick="setType('assets')" id="btnAssets" class="btn" style="font-size:12px;padding:8px 16px;{{ $openingBalance->balance_type === 'assets' ? 'background:rgba(99,102,241,0.2);border-color:#6366f1;' : '' }}">الأصول</button>
        </div>

        <div style="margin-bottom:16px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <h3 style="margin:0;">أسطر القيد</h3>
                <button type="button" onclick="addLine()" class="btn primary" style="font-size:12px;padding:6px 12px;">+ إضافة سطر</button>
            </div>

            <div style="overflow-x:auto;">
                <table id="linesTable">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th id="colAccount" style="display:{{ in_array($openingBalance->balance_type, ['accounts', 'cash', 'assets']) ? '' : 'none' }}">الحساب</th>
                            <th id="colCustomer" style="display:{{ $openingBalance->balance_type === 'customers' ? '' : 'none' }}">العميل</th>
                            <th id="colSupplier" style="display:{{ $openingBalance->balance_type === 'suppliers' ? '' : 'none' }}">المورد</th>
                            <th id="colItem" style="display:{{ $openingBalance->balance_type === 'inventory' ? '' : 'none' }}">الصنف</th>
                            <th id="colWarehouse" style="display:{{ $openingBalance->balance_type === 'inventory' ? '' : 'none' }}">المخزن</th>
                            <th id="colUnit" style="display:{{ $openingBalance->balance_type === 'inventory' ? '' : 'none' }}">الوحدة</th>
                            <th id="colQty" style="display:{{ $openingBalance->balance_type === 'inventory' ? '' : 'none' }}">الكمية</th>
                            <th id="colCost" style="display:{{ $openingBalance->balance_type === 'inventory' ? '' : 'none' }}>تكلفة الوحدة</th>
                            <th>مدين</th>
                            <th>دائن</th>
                            <th>البيان</th>
                            <th style="width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="linesBody">
                    </tbody>
                </table>
            </div>

            <div id="emptyMsg" style="text-align:center;padding:24px;color:var(--muted);font-size:13px;">
                اضغط "+ إضافة سطر" لبدء إدخال الأرصدة
            </div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px;background:rgba(17,24,39,0.92);border:1px solid var(--line);border-radius:12px;">
            <div style="display:flex; gap:24px;">
                <div>
                    <div style="font-size:11px;color:var(--muted);">إجمالي المدين</div>
                    <div id="totalDebit" style="font-size:18px;font-weight:700;color:var(--primary);">0.00</div>
                </div>
                <div>
                    <div style="font-size:11px;color:var(--muted);">إجمالي الدائن</div>
                    <div id="totalCredit" style="font-size:18px;font-weight:700;color:var(--danger);">0.00</div>
                </div>
                <div>
                    <div style="font-size:11px;color:var(--muted);">الفرق</div>
                    <div id="balance" style="font-size:18px;font-weight:700;">0.00</div>
                </div>
            </div>
            <button type="submit" class="btn primary" style="font-size:14px;padding:10px 24px;" id="submitBtn">حفظ التعديلات</button>
        </div>
    </form>
</article>

<script>
let lineIndex = 0;
const accounts = @json($accounts);
const customers = @json($customers);
const suppliers = @json($suppliers);
const items = @json($items);
const warehouses = @json($warehouses);
const units = @json($units);
const existingLines = @json($openingBalance->lines);

function setType(type) {
    document.getElementById('balanceType').value = type;
    document.getElementById('linesBody').innerHTML = '';
    lineIndex = 0;
    updateColumns(type);
    updateEmptyMsg();
}

function updateColumns(type) {
    const showAccount = ['accounts', 'cash', 'assets'].includes(type);
    const showCustomer = type === 'customers';
    const showSupplier = type === 'suppliers';
    const showInventory = type === 'inventory';

    document.getElementById('colAccount').style.display = showAccount ? '' : 'none';
    document.getElementById('colCustomer').style.display = showCustomer ? '' : 'none';
    document.getElementById('colSupplier').style.display = showSupplier ? '' : 'none';
    document.getElementById('colItem').style.display = showInventory ? '' : 'none';
    document.getElementById('colWarehouse').style.display = showInventory ? '' : 'none';
    document.getElementById('colUnit').style.display = showInventory ? '' : 'none';
    document.getElementById('colQty').style.display = showInventory ? '' : 'none';
    document.getElementById('colCost').style.display = showInventory ? '' : 'none';
}

function addLine(data = null) {
    const type = document.getElementById('balanceType').value;
    const tbody = document.getElementById('linesBody');
    const tr = document.createElement('tr');
    tr.id = 'line-' + lineIndex;

    let cells = `<td style="text-align:center;">${lineIndex + 1}</td>`;

    if (['accounts', 'cash', 'assets'].includes(type)) {
        cells += `<td><select name="lines[${lineIndex}][account_id]" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:6px;padding:6px;color:#fff;font-size:12px;">
            <option value="">اختر الحساب</option>
            ${accounts.map(a => `<option value="${a.id}" ${data && data.account_id == a.id ? 'selected' : ''}>${a.code} - ${a.name_ar}</option>`).join('')}
        </select></td>`;
    }

    if (type === 'customers') {
        cells += `<td><select name="lines[${lineIndex}][customer_id]" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:6px;padding:6px;color:#fff;font-size:12px;">
            <option value="">اختر العميل</option>
            ${customers.map(c => `<option value="${c.id}" ${data && data.customer_id == c.id ? 'selected' : ''}>${c.code} - ${c.name_ar}</option>`).join('')}
        </select></td>`;
    }

    if (type === 'suppliers') {
        cells += `<td><select name="lines[${lineIndex}][supplier_id]" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:6px;padding:6px;color:#fff;font-size:12px;">
            <option value="">اختر المورد</option>
            ${suppliers.map(s => `<option value="${s.id}" ${data && data.supplier_id == s.id ? 'selected' : ''}>${s.supplier_code} - ${s.name_ar}</option>`).join('')}
        </select></td>`;
    }

    if (type === 'inventory') {
        cells += `<td><select name="lines[${lineIndex}][item_id]" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:6px;padding:6px;color:#fff;font-size:12px;">
            <option value="">اختر الصنف</option>
            ${items.map(i => `<option value="${i.id}" ${data && data.item_id == i.id ? 'selected' : ''}>${i.code} - ${i.name_ar}</option>`).join('')}
        </select></td>`;
        cells += `<td><select name="lines[${lineIndex}][warehouse_id]" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:6px;padding:6px;color:#fff;font-size:12px;">
            <option value="">اختر المخزن</option>
            ${warehouses.map(w => `<option value="${w.id}" ${data && data.warehouse_id == w.id ? 'selected' : ''}>${w.code} - ${w.name_ar}</option>`).join('')}
        </select></td>`;
        cells += `<td><select name="lines[${lineIndex}][unit_id]" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:6px;padding:6px;color:#fff;font-size:12px;">
            <option value="">الوحدة</option>
            ${units.map(u => `<option value="${u.id}" ${data && data.unit_id == u.id ? 'selected' : ''}>${u.code} - ${u.name_ar}</option>`).join('')}
        </select></td>`;
        cells += `<td><input type="number" name="lines[${lineIndex}][qty]" step="0.01" min="0" value="${data ? data.qty : 0}" style="width:70px;background:#0b1220;border:1px solid var(--line);border-radius:6px;padding:6px;color:#fff;font-size:12px;" onchange="updateTotals()"></td>`;
        cells += `<td><input type="number" name="lines[${lineIndex}][unit_cost]" step="0.01" min="0" value="${data ? data.unit_cost : 0}" style="width:90px;background:#0b1220;border:1px solid var(--line);border-radius:6px;padding:6px;color:#fff;font-size:12px;" onchange="updateTotals()"></td>`;
    }

    cells += `<td><input type="number" name="lines[${lineIndex}][debit]" step="0.01" min="0" value="${data ? data.debit : 0}" style="width:100px;background:#0b1220;border:1px solid var(--line);border-radius:6px;padding:6px;color:#fff;font-size:12px;" onchange="updateTotals()"></td>`;
    cells += `<td><input type="number" name="lines[${lineIndex}][credit]" step="0.01" min="0" value="${data ? data.credit : 0}" style="width:100px;background:#0b1220;border:1px solid var(--line);border-radius:6px;padding:6px;color:#fff;font-size:12px;" onchange="updateTotals()"></td>`;
    cells += `<td><input type="text" name="lines[${lineIndex}][description]" value="${data ? (data.description || '') : ''}" style="width:120px;background:#0b1220;border:1px solid var(--line);border-radius:6px;padding:6px;color:#fff;font-size:12px;" placeholder="البيان"></td>`;
    cells += `<td><button type="button" onclick="removeLine(${lineIndex})" style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:16px;">✕</button></td>`;

    tr.innerHTML = cells;
    tbody.appendChild(tr);
    lineIndex++;
    updateTotals();
    updateEmptyMsg();
}

function removeLine(idx) {
    const tr = document.getElementById('line-' + idx);
    if (tr) tr.remove();
    updateTotals();
    updateEmptyMsg();
}

function updateTotals() {
    let totalDebit = 0, totalCredit = 0;
    document.querySelectorAll('#linesBody tr').forEach(tr => {
        const debitInput = tr.querySelector('input[name$="[debit]"]');
        const creditInput = tr.querySelector('input[name$="[credit]"]');
        if (debitInput) totalDebit += parseFloat(debitInput.value) || 0;
        if (creditInput) totalCredit += parseFloat(creditInput.value) || 0;
    });

    document.getElementById('totalDebit').textContent = totalDebit.toFixed(2);
    document.getElementById('totalCredit').textContent = totalCredit.toFixed(2);

    const balance = totalDebit - totalCredit;
    const balanceEl = document.getElementById('balance');
    balanceEl.textContent = balance.toFixed(2);
    balanceEl.style.color = Math.abs(balance) < 0.01 ? 'var(--primary)' : 'var(--danger)';

    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = !(Math.abs(balance) < 0.01 && document.querySelectorAll('#linesBody tr').length > 0);
}

function updateEmptyMsg() {
    const hasLines = document.querySelectorAll('#linesBody tr').length > 0;
    document.getElementById('emptyMsg').style.display = hasLines ? 'none' : '';
}

document.addEventListener('DOMContentLoaded', function() {
    existingLines.forEach(line => addLine(line));
    updateEmptyMsg();
});
</script>
@endsection
