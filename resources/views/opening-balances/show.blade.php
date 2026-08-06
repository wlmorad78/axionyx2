@extends('layouts.app')

@section('title', 'عرض قيد الأرصدة الافتتاحية - Axionyx ERP')
@section('page_title', 'قيد الأرصدة الافتتاحية')
@section('page_subtitle', $openingBalance->document_no)

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
    <div>
        <h2 style="margin:0;">{{ $openingBalance->document_no }}</h2>
        <p style="margin:4px 0 0;color:var(--muted);font-size:13px;">تاريخ الإنشاء: {{ $openingBalance->created_at?->format('Y-m-d H:i') ?? '—' }}</p>
    </div>
    <div style="display:flex; gap:8px;">
        <a href="{{ route('opening-balances.index') }}" class="btn" style="font-size:13px;">العودة للقائمة</a>
        @if($openingBalance->status === 'draft')
            <a href="{{ route('opening-balances.edit', $openingBalance->id) }}" class="btn" style="font-size:13px;">تعديل</a>
            <form method="POST" action="{{ route('opening-balances.post', $openingBalance->id) }}" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من اعتماد القيد؟')">
                @csrf
                <button type="submit" class="btn primary" style="font-size:13px;">اعتماد القيد</button>
            </form>
        @endif
        @if($openingBalance->status === 'posted')
            <form method="POST" action="{{ route('opening-balances.cancel', $openingBalance->id) }}" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من إلغاء الاعتماد؟')">
                @csrf
                <button type="submit" class="btn" style="font-size:13px;color:var(--warn);border-color:rgba(245,158,11,0.3);">إلغاء الاعتماد</button>
            </form>
        @endif
        @if($openingBalance->status !== 'posted')
            <form method="POST" action="{{ route('opening-balances.destroy', $openingBalance->id) }}" onsubmit="return confirm('هل أنت متأكد من الحذف؟')">
                @csrf @method('DELETE')
                <button type="submit" class="btn" style="font-size:13px;color:var(--danger);border-color:rgba(251,113,133,0.3);">حذف</button>
            </form>
        @endif
    </div>
</div>

@if(session('success'))
    <div style="padding:12px 16px;border-radius:10px;background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.18);color:#bbf7d0;margin-bottom:16px;font-size:14px;">
        {{ session('success') }}
    </div>
@endif

<div class="grid grid-2" style="margin-bottom:16px;">
    <article class="panel">
        <h3>بيانات القيد</h3>
        <div style="display:flex; flex-direction:column; gap:12px;">
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">رقم القيد</span>
                <span style="font-size:14px;font-weight:600;">{{ $openingBalance->document_no }}</span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">التاريخ</span>
                <span style="font-size:14px;">{{ $openingBalance->document_date?->format('Y-m-d') ?? '—' }}</span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">نوع الرصيد</span>
                <span style="font-size:14px;">
                    @switch($openingBalance->balance_type)
                        @case('cash') الخزنة @break
                        @case('accounts') البنك @break
                        @case('inventory') المنتجات @break
                        @case('suppliers') الموردين @break
                        @case('customers') العملاء @break
                        @case('assets') الأصول @break
                    @endswitch
                </span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">الفرع</span>
                <span style="font-size:14px;">{{ $openingBalance->branch?->name_ar ?? '—' }}</span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">الحالة</span>
                @if($openingBalance->status === 'posted')
                    <span class="status good">✓ معتمد</span>
                @elseif($openingBalance->status === 'cancelled')
                    <span class="status bad">✗ ملغي</span>
                @else
                    <span class="status warn">◐ مسودة</span>
                @endif
            </div>
            @if($openingBalance->notes)
                <div style="border-top:1px solid var(--line);padding-top:12px;">
                    <span style="color:var(--muted);font-size:13px;">ملاحظات</span>
                    <p style="margin:6px 0 0;font-size:14px;">{{ $openingBalance->notes }}</p>
                </div>
            @endif
        </div>
    </article>

    <article class="panel">
        <h3>معلومات الإنشاء والاعتماد</h3>
        <div style="display:flex; flex-direction:column; gap:12px;">
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">أنشأ بواسطة</span>
                <span style="font-size:14px;">{{ $openingBalance->createdBy?->name ?? '—' }}</span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">تاريخ الإنشاء</span>
                <span style="font-size:14px;">{{ $openingBalance->created_at?->format('Y-m-d H:i') ?? '—' }}</span>
            </div>
            @if($openingBalance->postedBy)
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:var(--muted);font-size:13px;">اعتمد بواسطة</span>
                    <span style="font-size:14px;">{{ $openingBalance->postedBy?->name ?? '—' }}</span>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:var(--muted);font-size:13px;">تاريخ الاعتماد</span>
                    <span style="font-size:14px;">{{ $openingBalance->posted_at?->format('Y-m-d H:i') ?? '—' }}</span>
                </div>
            @endif
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">عدد الأسطر</span>
                <span style="font-size:14px;font-weight:600;">{{ $openingBalance->lines->count() }}</span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">إجمالي المدين</span>
                <span style="font-size:16px;font-weight:700;color:var(--primary);">{{ number_format($openingBalance->lines->sum('debit'), 2) }}</span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">إجمالي الدائن</span>
                <span style="font-size:16px;font-weight:700;color:var(--danger);">{{ number_format($openingBalance->lines->sum('credit'), 2) }}</span>
            </div>
            @php
                $balance = $openingBalance->lines->sum('debit') - $openingBalance->lines->sum('credit');
            @endphp
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--muted);font-size:13px;">الفرق</span>
                <span style="font-size:16px;font-weight:700;color:{{ abs($balance) < 0.01 ? 'var(--primary)' : 'var(--danger)' }};">
                    {{ abs($balance) < 0.01 ? '✓ متوازن' : number_format($balance, 2) }}
                </span>
            </div>
        </div>
    </article>
</div>

<article class="panel">
    <h3>أسطر القيد</h3>

    @if($openingBalance->lines->isEmpty())
        <div style="text-align:center;padding:24px;color:var(--muted);">
            لا توجد أسطر في هذا القيد
        </div>
    @else
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        @if(in_array($openingBalance->balance_type, ['accounts', 'cash', 'assets']))
                            <th>الحساب</th>
                        @endif
                        @if($openingBalance->balance_type === 'customers')
                            <th>العميل</th>
                        @endif
                        @if($openingBalance->balance_type === 'suppliers')
                            <th>المورد</th>
                        @endif
                        @if($openingBalance->balance_type === 'inventory')
                            <th>الصنف</th>
                            <th>المخزن</th>
                            <th>الوحدة</th>
                            <th>الكمية</th>
                            <th>تكلفة الوحدة</th>
                            <th>الإجمالي</th>
                        @endif
                        <th>مدين</th>
                        <th>دائن</th>
                        <th>البيان</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($openingBalance->lines as $index => $line)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            @if(in_array($openingBalance->balance_type, ['accounts', 'cash', 'assets']))
                                <td>{{ $line->account?->code }} - {{ $line->account?->name_ar ?? '—' }}</td>
                            @endif
                            @if($openingBalance->balance_type === 'customers')
                                <td>{{ $line->customer?->code }} - {{ $line->customer?->name_ar ?? '—' }}</td>
                            @endif
                            @if($openingBalance->balance_type === 'suppliers')
                                <td>{{ $line->supplier?->supplier_code }} - {{ $line->supplier?->name_ar ?? '—' }}</td>
                            @endif
                            @if($openingBalance->balance_type === 'inventory')
                                <td>{{ $line->item?->code }} - {{ $line->item?->name_ar ?? '—' }}</td>
                                <td>{{ $line->warehouse?->code }} - {{ $line->warehouse?->name_ar ?? '—' }}</td>
                                <td>{{ $line->unit?->code ?? '—' }}</td>
                                <td>{{ number_format($line->qty, 2) }}</td>
                                <td>{{ number_format($line->unit_cost, 2) }}</td>
                                <td>{{ number_format($line->qty * $line->unit_cost, 2) }}</td>
                            @endif
                            <td style="color:{{ $line->debit > 0 ? 'var(--primary)' : 'var(--muted)' }};">{{ $line->debit > 0 ? number_format($line->debit, 2) : '—' }}</td>
                            <td style="color:{{ $line->credit > 0 ? 'var(--danger)' : 'var(--muted)' }};">{{ $line->credit > 0 ? number_format($line->credit, 2) : '—' }}</td>
                            <td>{{ $line->description ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="font-weight:700;">
                        <td colspan="{{ in_array($openingBalance->balance_type, ['inventory']) ? 7 : (in_array($openingBalance->balance_type, ['accounts', 'cash', 'assets', 'customers', 'suppliers']) ? 1 : 1) }}">الإجمالي</td>
                        <td style="color:var(--primary);">{{ number_format($openingBalance->lines->sum('debit'), 2) }}</td>
                        <td style="color:var(--danger);">{{ number_format($openingBalance->lines->sum('credit'), 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</article>
@endsection
