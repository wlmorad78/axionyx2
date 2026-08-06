@extends('layouts.app')

@section('title', $screen->title . ' - Axionyx ERP')
@section('page_title', $screen->title)
@section('page_subtitle', 'صفحة عرض مخصصة للشاشة الإدارية: ' . $screen->key)

@section('content')
<div class="grid grid-2">
    <article class="panel">
        <h2>تفاصيل الشاشة</h2>
        <ul class="mini-list">
            <li class="mini-row"><strong>المفتاح</strong><span>{{ $screen->key }}</span></li>
            <li class="mini-row"><strong>النوع</strong><span>{{ $screen->screen_type }}</span></li>
            <li class="mini-row"><strong>المورد API</strong><span>{{ $screen->api_resource ?? '—' }}</span></li>
            <li class="mini-row"><strong>المسار</strong><span>{{ $screen->route }}</span></li>
        </ul>
    </article>

    <article class="panel">
        <h2>ما الذي يمكن إضافته هنا</h2>
        <div class="mini-list">
            <div class="mini-row"><span>جدول البيانات</span><span class="status good">متوفر</span></div>
            <div class="mini-row"><span>البحث والتصفية</span><span class="status warn">قابل للتوسعة</span></div>
            <div class="mini-row"><span>إضافة وتعديل وحذف</span><span class="status good">مطابق لتطبيق Flutter</span></div>
            <div class="mini-row"><span>الشاشات الفرعية</span><span class="status good">مدعومة</span></div>
        </div>
    </article>
</div>

@if($screen->children->isNotEmpty())
    <article class="panel">
        <h2>الشاشات الفرعية</h2>
        <div class="mini-list">
            @foreach($screen->children as $child)
                <div class="mini-row">
                    <div>
                        <strong>{{ $child->title }}</strong>
                        <div class="muted">{{ $child->key }}</div>
                    </div>
                    <a class="btn" href="{{ url('/admin/screens/'.$child->key) }}">عرض</a>
                </div>
            @endforeach
        </div>
    </article>
@endif

<a class="btn" href="{{ url('/admin') }}">↩ الرجوع إلى قائمة الشاشات</a>
@endsection
