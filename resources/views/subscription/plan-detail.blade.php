@extends('layouts.app')

@section('title', $plan->name . ' - تفاصيل الباقة')
@section('page_title', 'تفاصيل الباقة: ' . $plan->name)
@section('page_subtitle', $plan->description)

@section('content')
<div class="grid grid-4">
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">المستوى</div>
                <div class="value">{{ $plan->tier }}</div>
                <div class="trend">من 6 مستويات</div>
            </div>
            <span class="chip">Tier</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">السعر الشهري</div>
                <div class="value" style="font-size:20px;">{{ number_format($plan->monthly_price, 0) }} ج.م</div>
                <div class="trend">اشتراك شهري</div>
            </div>
            <span class="chip">Monthly</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">رسوم التركيب</div>
                <div class="value" style="font-size:20px;">{{ number_format($plan->setup_price, 0) }} ج.م</div>
                <div class="trend">دفعة واحدة</div>
            </div>
            <span class="chip">Setup</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">الحد الأقصى للمستخدمين</div>
                <div class="value">{{ $plan->max_users == 999 ? 'غير محدود' : $plan->max_users }}</div>
                <div class="trend">مستخدم</div>
            </div>
            <span class="chip">Users</span>
        </div>
    </article>
</div>

<div class="grid grid-2">
    <article class="panel">
        <h2>الحدود</h2>
        <div class="mini-list">
            <div class="mini-row">
                <strong>الفروع</strong>
                <span>{{ $plan->max_branches == 999 ? 'غير محدود' : $plan->max_branches }}</span>
            </div>
            <div class="mini-row">
                <strong>المخازن</strong>
                <span>{{ $plan->max_warehouses == 999 ? 'غير محدود' : $plan->max_warehouses }}</span>
            </div>
            <div class="mini-row">
                <strong>الصناديق</strong>
                <span>{{ $plan->max_treasuries == 999 ? 'غير محدود' : $plan->max_treasuries }}</span>
            </div>
            <div class="mini-row">
                <strong>مدة الاشتراك</strong>
                <span>{{ $plan->duration_months }} شهر</span>
            </div>
            <div class="mini-row">
                <strong>فترة السماح</strong>
                <span>{{ $plan->grace_period_days }} يوم</span>
            </div>
            <div class="mini-row">
                <strong>الحالة</strong>
                <span class="status {{ $plan->is_active ? 'good' : 'bad' }}">{{ $plan->is_active ? 'نشط' : 'غير نشط' }}</span>
            </div>
        </div>
    </article>

    <article class="panel">
        <h2>الميزات</h2>
        <div class="mini-list">
            @foreach($plan->features ?? [] as $feature)
                <div class="mini-row">
                    <span style="color: var(--primary);">✓</span>
                    <strong>{{ $feature }}</strong>
                </div>
            @endforeach
        </div>
    </article>
</div>

<article class="panel">
    <h2>الوحدات المتاحة</h2>
    <table>
        <thead>
            <tr>
                <th>الوحدة</th>
                <th>عرض</th>
                <th>إنشاء</th>
                <th>تعديل</th>
                <th>حذف</th>
            </tr>
        </thead>
        <tbody>
            @forelse($plan->modules as $module)
                <tr>
                    <td><strong>{{ $module->title }}</strong></td>
                    <td><span class="status {{ $module->pivot->can_view ? 'good' : 'bad' }}">{{ $module->pivot->can_view ? 'نعم' : 'لا' }}</span></td>
                    <td><span class="status {{ $module->pivot->can_create ? 'good' : 'bad' }}">{{ $module->pivot->can_create ? 'نعم' : 'لا' }}</span></td>
                    <td><span class="status {{ $module->pivot->can_edit ? 'good' : 'bad' }}">{{ $module->pivot->can_edit ? 'نعم' : 'لا' }}</span></td>
                    <td><span class="status {{ $module->pivot->can_delete ? 'good' : 'bad' }}">{{ $module->pivot->can_delete ? 'نعم' : 'لا' }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center; color: var(--muted);">لا توجد وحدات مخصصة لهذه الباقة</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</article>

<div class="panel">
    <h2>إجراءات</h2>
    <div class="mini-list">
        <div class="mini-row">
            <span>تعيين هذه الباقة لشركة</span>
            <form action="{{ route('subscription-plans.assign', $plan->id) }}" method="POST" style="display:inline;">
                @csrf
                <button type="submit" class="btn primary">تعيين الباقة</button>
            </form>
        </div>
        <div class="mini-row">
            <span>العودة لقائمة الباقات</span>
            <a class="btn" href="{{ route('subscription-plans.index') }}">الباقات</a>
        </div>
    </div>
</div>
@endsection
