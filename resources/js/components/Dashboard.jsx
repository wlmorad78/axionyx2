import { useMemo } from 'react';

const metrics = [
    { title: 'المبيعات', value: '72.4K', change: '+14%', icon: '💰', color: 'from-cyan-500 to-sky-700' },
    { title: 'العملاء', value: '8.2K', change: '+9%', icon: '👥', color: 'from-violet-500 to-fuchsia-600' },
    { title: 'الأوامر', value: '1.4K', change: '+7%', icon: '📦', color: 'from-emerald-500 to-lime-600' },
    { title: 'الإيرادات', value: '184K', change: '+18%', icon: '📈', color: 'from-amber-500 to-orange-600' },
];

const recentTasks = [
    { id: 1, name: 'تنظيف بيانات العملاء', status: 'اكتملت', tag: 'نجاح', progress: 100 },
    { id: 2, name: 'إعداد تقرير المخزون', status: 'قيد التنفيذ', tag: 'تنبيه', progress: 67 },
    { id: 3, name: 'مراجعة طلبات الموردين', status: 'معلقة', tag: 'انتظار', progress: 38 },
];

const shortcuts = [
    'عرض الطلبات الجديدة',
    'تحليل المبيعات',
    'إدارة العملاء',
    'تصدير التقارير',
];

function MetricCard({ title, value, change, icon, color }) {
    return (
        <div className={`rounded-3xl p-6 shadow-lg ring-1 ring-slate-200/70 bg-gradient-to-br ${color} text-white`}> 
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-sm opacity-90">{title}</p>
                    <h2 className="mt-3 text-3xl font-semibold tracking-tight">{value}</h2>
                </div>
                <div className="grid h-14 w-14 place-items-center rounded-3xl bg-white/20 text-2xl shadow-inner">
                    {icon}
                </div>
            </div>
            <p className="mt-5 text-sm opacity-90">زيادة شهرياً <span className="font-semibold">{change}</span></p>
        </div>
    );
}

function StatusBadge({ status }) {
    const classes = {
        'اكتملت': 'bg-emerald-100 text-emerald-800',
        'قيد التنفيذ': 'bg-amber-100 text-amber-800',
        'معلقة': 'bg-slate-100 text-slate-800',
    };

    return <span className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ${classes[status] || 'bg-slate-100 text-slate-800'}`}>{status}</span>;
}

export default function Dashboard() {
    const progressSteps = useMemo(
        () => [
            { name: 'الأسبوع الأول', value: 85 },
            { name: 'الأسبوع الثاني', value: 72 },
            { name: 'الأسبوع الثالث', value: 91 },
            { name: 'الأسبوع الرابع', value: 78 },
        ],
        []
    );

    return (
        <main className="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div className="grid gap-8 lg:grid-cols-[280px_1fr]">
                <aside className="space-y-8 rounded-[2rem] border border-slate-200/80 bg-white/95 p-6 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-slate-700/60 dark:bg-slate-900/95 dark:shadow-slate-900/40">
                    <div className="space-y-4">
                        <div className="rounded-3xl bg-slate-900 px-4 py-5 text-slate-100 shadow-[0_18px_50px_-30px_rgba(15,23,42,0.8)]">
                            <p className="text-sm opacity-80">مرحباً بك في</p>
                            <h1 className="mt-3 text-2xl font-semibold tracking-tight">لوحة إدارة أكسيونيكس</h1>
                        </div>
                        <p className="text-sm leading-6 text-slate-600 dark:text-slate-300">تصميم واجهة احترافية بألوان متناسقة مع قسم تمهيدي لعرض أهم البيانات والمستجدات.</p>
                    </div>

                    <div className="space-y-3">
                        <h2 className="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">نظرة سريعة</h2>
                        <div className="space-y-3">
                            {shortcuts.map((item) => (
                                <button key={item} className="w-full rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-3 text-right text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-slate-900">
                                    {item}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="rounded-3xl border border-slate-200/80 bg-slate-50 p-5 dark:border-slate-700/60 dark:bg-slate-950">
                        <h2 className="text-sm font-semibold text-slate-700 dark:text-slate-100">ملخص الأداء</h2>
                        <p className="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">راقب الأرقام الأساسية في مكان واحد مع تفاصيل جاهزة للتقرير.</p>
                        <div className="mt-5 space-y-4">
                            {progressSteps.map((step) => (
                                <div key={step.name} className="space-y-2">
                                    <div className="flex items-center justify-between text-sm text-slate-600 dark:text-slate-300">
                                        <span>{step.name}</span>
                                        <span>{step.value}%</span>
                                    </div>
                                    <div className="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                                        <div className="h-2 rounded-full bg-gradient-to-r from-sky-500 via-cyan-500 to-emerald-500" style={{ width: `${step.value}%` }} />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </aside>

                <section className="space-y-8">
                    <header className="flex flex-col gap-4 rounded-[2rem] border border-slate-200/80 bg-white/95 p-6 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-slate-700/60 dark:bg-slate-950/95 dark:shadow-slate-900/40 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p className="text-sm text-slate-500 dark:text-slate-400">مرحباً بك من جديد</p>
                            <h2 className="mt-2 text-3xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">لوحة تحكم الأعمال</h2>
                        </div>
                        <div className="flex flex-wrap items-center gap-3">
                            <button className="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">إنشاء تقرير</button>
                            <button className="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-slate-900">عرض الإحصائيات</button>
                        </div>
                    </header>

                    <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                        {metrics.map((metric) => (
                            <MetricCard key={metric.title} {...metric} />
                        ))}
                    </div>

                    <div className="grid gap-6 xl:grid-cols-[1.4fr_0.6fr]">
                        <div className="rounded-[2rem] border border-slate-200/80 bg-white/95 p-6 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-slate-700/60 dark:bg-slate-950/95 dark:shadow-slate-900/40">
                            <div className="flex items-center justify-between gap-4">
                                <div>
                                    <h3 className="text-xl font-semibold text-slate-900 dark:text-slate-100">آخر المهام</h3>
                                    <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">مراجعة سريعة لحالة المهام الحالية.</p>
                                </div>
                                <button className="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">عرض الكل</button>
                            </div>
                            <div className="mt-6 space-y-4">
                                {recentTasks.map((task) => (
                                    <div key={task.id} className="rounded-3xl border border-slate-200/80 bg-slate-50 p-4 dark:border-slate-700/60 dark:bg-slate-900">
                                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <h4 className="text-base font-semibold text-slate-900 dark:text-slate-100">{task.name}</h4>
                                                <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">حالة المهمة الحالية وتقدّم التنفيذ.</p>
                                            </div>
                                            <StatusBadge status={task.status} />
                                        </div>
                                        <div className="mt-4 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                                            <div className="h-2 rounded-full bg-sky-500" style={{ width: `${task.progress}%` }} />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="rounded-[2rem] border border-slate-200/80 bg-white/95 p-6 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-slate-700/60 dark:bg-slate-950/95 dark:shadow-slate-900/40">
                            <div className="flex items-center justify-between gap-4">
                                <div>
                                    <h3 className="text-xl font-semibold text-slate-900 dark:text-slate-100">أداء الشهر</h3>
                                    <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">تقدّم الأهداف ونمو الإيرادات مع مرور الأيام.</p>
                                </div>
                                <span className="rounded-2xl bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-slate-600 dark:bg-slate-800 dark:text-slate-300">مستقر</span>
                            </div>
                            <div className="mt-8 space-y-5">
                                <div className="rounded-3xl bg-gradient-to-br from-slate-900 via-slate-700 to-slate-900 px-5 py-6 text-white shadow-2xl shadow-slate-900/20">
                                    <p className="text-sm opacity-80">نسبة الاستهداف مقابل الواقع</p>
                                    <div className="mt-5 flex items-end gap-4">
                                        <div className="space-y-1">
                                            <p className="text-4xl font-semibold">92%</p>
                                            <p className="text-sm opacity-80">مستوى الأداء العام</p>
                                        </div>
                                        <div className="h-28 w-full rounded-3xl bg-slate-900/30 p-3">
                                            <div className="h-full rounded-3xl bg-gradient-to-t from-cyan-400 to-sky-500" style={{ width: '75%' }}></div>
                                        </div>
                                    </div>
                                </div>
                                <div className="space-y-4">
                                    <div className="flex items-center justify-between text-sm text-slate-500 dark:text-slate-400">
                                        <span>الزوار</span>
                                        <span>48.7K</span>
                                    </div>
                                    <div className="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                                        <div className="h-2 rounded-full bg-emerald-500" style={{ width: '84%' }} />
                                    </div>
                                    <div className="flex items-center justify-between text-sm text-slate-500 dark:text-slate-400">
                                        <span>التحويلات</span>
                                        <span>23.4%</span>
                                    </div>
                                    <div className="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                                        <div className="h-2 rounded-full bg-fuchsia-500" style={{ width: '63%' }} />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    );
}
