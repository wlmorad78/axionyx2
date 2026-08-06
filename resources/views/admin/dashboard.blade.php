@extends('layouts.app')

@section('title', $screen->title . ' - Axionyx ERP')
@section('page_title', $screen->title)
@section('page_subtitle', 'لوحة تحكم تفاعلية للشاشة الرئيسية في AxionyxApp، مع بطاقات إحصائية وأحدث الأنشطة.')

@section('content')
<div class="grid grid-4">
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">إجمالي السجلات</div>
                <div class="value">1,248</div>
                <div class="trend">▲ 12% هذا الشهر</div>
            </div>
            <span class="chip">Overview</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">الطلبات الجديدة</div>
                <div class="value">84</div>
                <div class="trend">+6 اليوم</div>
            </div>
            <span class="chip">Orders</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">العناصر المنخفضة</div>
                <div class="value">14</div>
                <div class="trend">يتطلب مراجعة</div>
            </div>
            <span class="chip">Stock</span>
        </div>
    </article>
    <article class="panel">
        <div class="metric">
            <div>
                <div class="muted">العمليات المعلقة</div>
                <div class="value">9</div>
                <div class="trend">انتظار موافقة</div>
            </div>
            <span class="chip">Pending</span>
        </div>
    </article>
</div>

<div class="grid grid-2">
    <article class="panel">
        <h2>أحدث الأنشطة</h2>
        <div class="mini-list">
            <div class="mini-row"><span>تم إنشاء فاتورة جديدة</span><span class="status good">5 دقائق</span></div>
            <div class="mini-row"><span>تم تحديث المخزون</span><span class="status warn">20 دقيقة</span></div>
            <div class="mini-row"><span>تم استلام دفع</span><span class="status good">1 ساعة</span></div>
            <div class="mini-row"><span>تم اعتماد طلب</span><span class="status good">2 ساعة</span></div>
        </div>
    </article>

    <article class="panel">
        <h2>الصفحات المرتبطة</h2>
        <div class="mini-list">
            @foreach($screen->children->take(5) as $child)
                <div class="mini-row">
                    <strong>{{ $child->title }}</strong>
                    <a class="btn" href="{{ url('/admin/screens/'.$child->key) }}">فتح</a>
                </div>
            @endforeach
        </div>
    </article>
</div>

<article class="panel">
    <h2>نظرة عامة على الشاشة الرئيسية</h2>
    <p class="muted">هذه الشاشة تمثل لوحة التحكم الرئيسية في AxionyxApp، وعند ربطها بالبيانات الحقيقية ستعرض مؤشرات الفواتير، المخزون، الموظفين، والأنشطة الحديثة.</p>
</article>

<a class="btn" href="{{ url('/admin') }}">↩ العودة إلى قائمة الشاشات</a>
@endsection
