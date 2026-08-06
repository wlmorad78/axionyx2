@extends('layouts.app')

@section('title', 'الشاشات الإدارية - Axionyx ERP')
@section('page_title', 'الشاشات الإدارية')
@section('page_subtitle', 'عرض الوحدات والشاشات التي يدعمها تطبيق AxionyxApp مع واجهة Blade.')

@section('content')
<div class="panel">
    <h2>نظرة عامة على الشاشات</h2>
    <p class="muted">هذه الصفحة تمثل النسخة الويب من نظام الشاشات الإدارية الموجود في تطبيق AxionyxApp، وتعرض الوحدات الرئيسية والصفحات الفرعية المرتبطة بها.</p>
</div>

@if($modules->isEmpty())
    <article class="panel">
        <h2>لا توجد شاشات مفعّلة حالياً</h2>
        <p class="muted">بعد إدخال بيانات AdminModule وAdminScreen من لوحة الإدارة، ستظهر هنا جميع الشاشات تلقائياً.</p>
    </article>
@else
    <div class="grid grid-4">
        @foreach($modules as $module)
            <article class="panel">
                <div class="metric">
                    <div>
                        <div class="muted">الوحدة</div>
                        <div class="value" style="font-size: 20px;">{{ $module->title }}</div>
                        <div class="trend">{{ $module->screens->count() }} شاشة</div>
                    </div>
                    <span class="chip">{{ $module->key }}</span>
                </div>
            </article>
        @endforeach
    </div>

    <div class="grid grid-2">
        @foreach($modules as $module)
            <article class="panel">
                <h2>{{ $module->title }}</h2>
                <p class="muted">الشاشات المرتبطة بهذه الوحدة:</p>
                <div class="mini-list">
                    @foreach($module->screens as $screen)
                        <div class="mini-row">
                            <div>
                                <strong>{{ $screen->title }}</strong>
                                <div class="muted">{{ $screen->key }} · {{ $screen->screen_type }}</div>
                            </div>
                            <a class="btn primary" href="{{ url('/admin/screens/'.$screen->key) }}">فتح</a>
                        </div>
                    @endforeach
                </div>
            </article>
        @endforeach
    </div>
@endif

<div class="panel" style="margin-top: 20px;">
    <h2>أدوات النظام</h2>
    <a href="{{ route('admin.clear-data.index') }}" style="display: inline-block; background: #ef4444; color: white; padding: 10px 24px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 14px;">مسح جميع البيانات التعاملية</a>
</div>

<p class="footer-note">هذه الصفحات مصممة لتكون نقطة بداية لنسخة Blade من جميع شاشات AxionyxApp، ويمكن توسيعها لاحقاً لعرض الجداول والنماذج الحقيقية.</p>
@endsection
