@if($movements->isEmpty())
    <div style="text-align:center; padding:32px; color:var(--muted);">
        <p style="font-size:16px;">لا توجد حركات للصنف</p>
    </div>
@elseif($tab === 'all')
    {{-- Tab الكل : كشف حساب كامل --}}
    <div style="overflow-x:auto;">
        <table style="width:100%; font-size:13px;">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>العملية</th>
                    <th>المرجع</th>
                    <th>من</th>
                    <th>إلى</th>
                    <th>داخل</th>
                    <th>خارج</th>
                    <th>الرصيد</th>
                </tr>
            </thead>
            <tbody>
                @php $runningBalance = 0; @endphp
                @foreach($movements as $m)
                    @php
                        $runningBalance += (float) $m->qty;
                        $badgeClass = match($m->movement_type) {
                            'purchase', 'purchase_return' => 'badge-green',
                            'load' => 'badge-yellow',
                            'sale' => 'badge-red',
                            'return' => 'badge-blue',
                            'unload' => 'badge-gray',
                            default => 'badge-gray',
                        };
                        $label = match($m->movement_type) {
                            'purchase' => 'شراء',
                            'purchase_return' => 'مرتجع شراء',
                            'load' => 'تحميل',
                            'sale' => 'بيع',
                            'return' => 'مرتجع',
                            'unload' => 'تفريغ',
                            'transfer_rep' => 'تحويل مندوب',
                            'transfer_wh' => 'تحويل مخزني',
                            default => $m->txn_type_name ?? 'أخرى',
                        };
                        $isRepMovement = in_array($m->from_location_type, ['rep', 'customer']) || in_array($m->to_location_type, ['rep', 'customer']);
                    @endphp
                    <tr style="{{ $isRepMovement ? 'opacity:0.85;' : '' }}">
                        <td style="white-space:nowrap;">{{ $m->transaction_date }}</td>
                        <td><span class="badge {{ $badgeClass }}">{{ $label }}</span></td>
                        <td>
                            @if($m->ref_number)
                                <span style="color:var(--accent); font-weight:600;">{{ $m->ref_number }}</span>
                            @else
                                <span style="color:var(--muted); font-size:12px;">{{ $m->transaction_no }}</span>
                            @endif
                        </td>
                        <td style="color:var(--muted); font-size:12px;">{{ $m->from_name }}</td>
                        <td style="color:var(--muted); font-size:12px;">{{ $m->to_name }}</td>
                        <td style="color:var(--primary); font-weight:600;">
                            @if($m->in_qty > 0) +{{ number_format($m->in_qty, 2) }} @endif
                        </td>
                        <td style="color:var(--danger); font-weight:600;">
                            @if($m->out_qty > 0) -{{ number_format($m->out_qty, 2) }} @endif
                        </td>
                        <td style="font-weight:700;">
                            {{ number_format($runningBalance, 2) }}
                            @if($isRepMovement)
                                <span style="color:var(--muted); font-size:11px;" title="رصيد المندوب">*</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="footer-note" style="margin-top:8px; text-align:left;">
        * الرصيد يمثل رصيد المستودع. الحركات ذات العلامة * هي حركات مندوبين لا تؤثر على رصيد المستودع.
    </div>
@elseif($tab === 'purchases')
    <div style="overflow-x:auto;">
        <table style="width:100%; font-size:13px;">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>رقم الفاتورة</th>
                    <th>المورد</th>
                    <th>الكمية</th>
                    <th>سعر الوحدة</th>
                    <th>الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @foreach($movements as $m)
                    <tr>
                        <td>{{ $m->transaction_date }}</td>
                        <td><span style="color:var(--accent);">{{ $m->ref_number ?: $m->transaction_no }}</span></td>
                        <td>{{ $m->from_name }}</td>
                        <td style="color:var(--primary); font-weight:600;">+{{ number_format(abs($m->qty), 2) }}</td>
                        <td>{{ number_format($m->unit_cost, 4) }}</td>
                        <td>{{ number_format($m->total_cost, 4) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@elseif($tab === 'loads')
    <div style="overflow-x:auto;">
        <table style="width:100%; font-size:13px;">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>رقم التحميل</th>
                    <th>من</th>
                    <th>إلى</th>
                    <th>الكمية</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                @foreach($movements as $m)
                    @php
                        $loadStatus = match($m->movement_type) {
                            'load' => 'تم التحميل',
                            'unload' => 'تم التفريغ',
                            default => $m->txn_type_name ?? '—',
                        };
                    @endphp
                    <tr>
                        <td>{{ $m->transaction_date }}</td>
                        <td><span style="color:var(--accent);">{{ $m->ref_number ?: $m->transaction_no }}</span></td>
                        <td>{{ $m->from_name }}</td>
                        <td>{{ $m->to_name }}</td>
                        <td style="font-weight:600;">{{ number_format(abs($m->qty), 2) }}</td>
                        <td><span class="badge {{ $m->movement_type === 'unload' ? 'badge-gray' : 'badge-yellow' }}">{{ $loadStatus }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@elseif($tab === 'sales')
    <div style="overflow-x:auto;">
        <table style="width:100%; font-size:13px;">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>الفاتورة</th>
                    <th>المندوب</th>
                    <th>العميل</th>
                    <th>الكمية</th>
                    <th>قيمة البيع</th>
                </tr>
            </thead>
            <tbody>
                @foreach($movements as $m)
                    <tr>
                        <td>{{ $m->transaction_date }}</td>
                        <td><span style="color:var(--accent);">{{ $m->ref_number ?: $m->transaction_no }}</span></td>
                        <td>{{ $m->from_name }}</td>
                        <td>{{ $m->to_name }}</td>
                        <td style="color:var(--danger); font-weight:600;">-{{ number_format(abs($m->qty), 2) }}</td>
                        <td>{{ number_format(abs($m->total_cost), 4) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@elseif($tab === 'returns')
    <div style="overflow-x:auto;">
        <table style="width:100%; font-size:13px;">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>المرجع</th>
                    <th>المندوب</th>
                    <th>العميل</th>
                    <th>الكمية</th>
                    <th>السبب</th>
                </tr>
            </thead>
            <tbody>
                @foreach($movements as $m)
                    <tr>
                        <td>{{ $m->transaction_date }}</td>
                        <td><span style="color:var(--accent);">{{ $m->ref_number ?: $m->transaction_no }}</span></td>
                        <td>{{ $m->to_name }}</td>
                        <td>{{ $m->from_name }}</td>
                        <td style="color:var(--primary); font-weight:600;">+{{ number_format(abs($m->qty), 2) }}</td>
                        <td style="color:var(--muted); font-size:12px;">{{ $m->txn_notes ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
