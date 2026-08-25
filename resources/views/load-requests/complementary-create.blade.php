@extends('layouts.app')

@section('title', 'أمر تحميل تكميلى - Axionyx ERP')
@section('page_title', 'أمر تحميل تكميلى')
@section('page_subtitle', 'إضافة أصناف لمخزون المندوب بدون ارتجاع الأذن الأصلي - بناءً على أمر التحميل ' . $loadRequest->request_no)

@section('content')
<article class="panel">
    <h2>بيانات أمر التحميل التكميلى</h2>

    <div style="padding:10px 14px;border-radius:8px;background:rgba(56,189,248,0.08);border:1px solid rgba(56,189,248,0.15);margin-bottom:16px;font-size:13px;color:#e0f2fe;">
        الأصناف المضافة هنا ستُضاف إلى مخزون المندوب <strong>{{ $loadRequest->employee?->name ?? '—' }}</strong> مباشرةً عند الاعتماد،
        وترسل إلى جهاز الهاند هولد، <strong>دون الحاجة لإرجاع الأذن الأصلي</strong>.
        وعند إغلاق أمر التحميل الأصلي بنهاية اليوم سيُغلق هذا الأمر التكميلى تلقائياً معه.
    </div>

    @if(session('error'))
        <div style="padding:12px 16px;border-radius:10px;background:rgba(251,113,133,0.12);border:1px solid rgba(251,113,133,0.18);color:#fecdd3;margin-bottom:16px;font-size:14px;">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('load-requests.complementary.store', $loadRequest->id) }}" id="loadRequestForm">
        @csrf

        <div class="grid grid-2" style="margin-bottom:20px;">
            <div>
                <label style="display:block;margin-bottom:6px;color:var(--muted);font-size:13px;">المخزن *</label>
                <select name="warehouse_id" required style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:10px;padding:10px 12px;color:#fff;font-size:14px;">
                    <option value="">-- اختر المخزن --</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" {{ (old('warehouse_id', $loadRequest->warehouse_id) == $wh->id) ? 'selected' : '' }}>
                            {{ $wh->name_ar ?? $wh->name }} ({{ $wh->code }})
                        </option>
                    @endforeach
                </select>
                @error('warehouse_id') <span style="color:var(--danger);font-size:12px;">{{ $message }}</span> @enderror
            </div>
            <div>
                <label style="display:block;margin-bottom:6px;color:var(--muted);font-size:13px;">ملاحظات</label>
                <input type="text" name="notes" value="{{ old('notes') }}" placeholder="ملاحظات على الطلب..." style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:10px;padding:10px 12px;color:#fff;font-size:14px;">
            </div>
        </div>

        <h3 style="margin-bottom:12px;">الأصناف المطلوبة (تكميلى)</h3>

        <div style="overflow-x:auto;">
            <table id="itemsTable" style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr>
                        <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">#</th>
                        <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">الصنف</th>
                        <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">الكمية</th>
                        <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">سعر الوحدة</th>
                        <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">الإجمالي</th>
                        <th style="text-align:left;padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:#cbd5e1;font-weight:600;">حذف</th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" style="text-align:left;padding:10px 8px;font-weight:bold;color:#fff;">الإجمالي</td>
                        <td style="padding:10px 8px;font-weight:bold;color:var(--primary);font-size:16px;" id="totalAmount">0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div style="margin-top:16px; display:flex; gap:8px;">
            <button type="button" onclick="addItemRow()" class="btn" style="border-color:var(--accent);color:var(--accent);">+ إضافة صنف</button>
        </div>

        <div style="margin-top:24px; display:flex; gap:12px; justify-content:flex-end;">
            <a href="{{ route('load-requests.show', $loadRequest->id) }}" class="btn">إلغاء</a>
            <button type="submit" class="btn primary" id="submitBtn">إرسال الأمر التكميلى</button>
        </div>
    </form>
</article>

<script>
const items = @json($items);
let rowIndex = 0;

function addItemRow() {
    const tbody = document.getElementById('itemsBody');
    const tr = document.createElement('tr');
    tr.id = 'row-' + rowIndex;
    tr.innerHTML = `
        <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);">${rowIndex + 1}</td>
        <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);">
            <select name="items[${rowIndex}][item_id]" required onchange="updateItem(this)" style="width:100%;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:8px;color:#fff;font-size:13px;">
                <option value="">-- اختر صنف --</option>
                ${items.map(i => {
                    // pick base unit (smallest conversion factor)
                    let baseUnitId = '';
                    let baseCf = 999999;
                    if (i.item_units && i.item_units.length) {
                        for (const u of i.item_units) {
                            const cf = parseFloat(u.conversion_factor || 999999);
                            if (cf < baseCf) { baseCf = cf; baseUnitId = u.unit_id; }
                        }
                    }
                    const price = i.prices && i.prices.length ? i.prices[0].price : 0;
                    return `<option value="${i.id}" data-price="${price}" data-unit-id="${baseUnitId}">${i.name_ar || i.name_en} (${i.code})</option>`;
                }).join('')}
            </select>
            <input type="hidden" name="items[${rowIndex}][unit_id]" class="unit-id-field" value="">
        </td>
        <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);">
            <input type="number" name="items[${rowIndex}][quantity]" value="1" min="0.01" step="0.01" required onchange="updateTotal(this)" style="width:80px;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:8px;color:#fff;font-size:13px;text-align:center;">
        </td>
        <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);">
            <input type="number" name="items[${rowIndex}][unit_price]" value="0" min="0" step="0.01" onchange="updateTotal(this)" style="width:100px;background:#0b1220;border:1px solid var(--line);border-radius:8px;padding:8px;color:#fff;font-size:13px;text-align:center;">
        </td>
        <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);color:var(--primary);font-weight:bold;" class="row-total">0.00</td>
        <td style="padding:10px 8px;border-bottom:1px solid rgba(148,163,184,0.12);">
            <button type="button" onclick="removeRow(${rowIndex})" style="background:rgba(251,113,133,0.15);border:1px solid rgba(251,113,133,0.3);border-radius:6px;padding:6px 10px;color:var(--danger);cursor:pointer;font-size:12px;">حذف</button>
        </td>
    `;
    tbody.appendChild(tr);
    rowIndex++;
}

function removeRow(idx) {
    const row = document.getElementById('row-' + idx);
    if (row) row.remove();
    recalculate();
}

function updateItem(select) {
    const option = select.options[select.selectedIndex];
    const price = option.dataset.price || 0;
    const unitId = option.dataset.unitId || '';
    const row = select.closest('tr');
    const priceInput = row.querySelector('input[name*="unit_price"]');
    priceInput.value = parseFloat(price).toFixed(2);
    const unitIdField = row.querySelector('.unit-id-field');
    if (unitIdField) unitIdField.value = unitId;
    updateTotal(select);
}

function updateTotal(el) {
    const row = el.closest('tr');
    const qty = parseFloat(row.querySelector('input[name*="quantity"]').value) || 0;
    const price = parseFloat(row.querySelector('input[name*="unit_price"]').value) || 0;
    const total = qty * price;
    row.querySelector('.row-total').textContent = total.toFixed(2);
    recalculate();
}

function recalculate() {
    let grand = 0;
    document.querySelectorAll('.row-total').forEach(td => {
        grand += parseFloat(td.textContent) || 0;
    });
    document.getElementById('totalAmount').textContent = grand.toFixed(2);
}

document.getElementById('loadRequestForm').addEventListener('submit', function(e) {
    if (document.querySelectorAll('#itemsBody tr').length === 0) {
        e.preventDefault();
        alert('يجب إضافة صنف واحد على الأقل');
    }
});
</script>
@endsection
