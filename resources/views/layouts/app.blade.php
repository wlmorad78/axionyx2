<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'Axionyx ERP'))</title>
    <meta name="description" content="Laravel Blade Templates for Axionyx ERP">
    <style>
        :root {
            color-scheme: light;
            --bg: #0f172a;
            --panel: #111827;
            --panel-2: #1f2937;
            --line: #334155;
            --text: #e5eefb;
            --muted: #cbd5e1;
            --primary: #22c55e;
            --accent: #38bdf8;
            --warn: #f59e0b;
            --danger: #fb7185;
        }

        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: linear-gradient(145deg, #07111f 0%, #111827 32%, #1f2937 100%); color: var(--text); }
        a { color: inherit; text-decoration: none; }

        .shell { min-height: 100vh; display: flex; }
        .sidebar { width: 280px; background: rgba(15, 23, 42, 0.92); border-right: 1px solid var(--line); padding: 20px 14px; display:flex; flex-direction:column; gap:18px; }
        .brand { display:flex; align-items:center; gap:12px; font-weight:700; font-size:18px; }
        .brand-badge { width:42px; height:42px; border-radius:12px; background: linear-gradient(135deg, var(--primary), var(--accent)); display:grid; place-items:center; color:#07213a; font-weight:800; }
        .nav-group { display:flex; flex-direction:column; gap:6px; }
        .nav-label { text-transform:uppercase; letter-spacing: .18em; font-size:11px; color:#94a3b8; padding: 4px 10px; }
        .nav-link { display:flex; align-items:center; justify-content:space-between; padding:10px 12px; color:var(--muted); border-radius:12px; border:1px solid transparent; }
        .nav-link:hover, .nav-link.active { background: rgba(56,189,248,0.12); border-color: rgba(56,189,248,0.25); color: #fff; }
        .pill { display:inline-flex; align-items:center; gap:8px; border-radius:999px; padding:6px 10px; background: rgba(34,197,94,0.12); color:#bbf7d0; font-size:12px; border:1px solid rgba(34,197,94,0.18); }

        .main { flex:1; display:flex; flex-direction:column; }
        .topbar { display:flex; justify-content:space-between; align-items:center; padding:18px 22px; border-bottom:1px solid var(--line); background: rgba(15, 23, 42, 0.72); backdrop-filter: blur(8px); }
        .topbar h1 { margin:0; font-size:22px; }
        .topbar p { margin:6px 0 0; color:var(--muted); font-size:13px; }
        .top-actions { display:flex; gap:10px; align-items:center; }
        .btn { border-radius:10px; padding:10px 12px; border:1px solid var(--line); background:#0b1220; color:#fff; font-weight:600; }
        .btn.primary { background: linear-gradient(135deg, var(--primary), #10b981); color:#052e16; border-color: transparent; }
        .content { padding:22px; display:flex; flex-direction:column; gap:18px; }
        .grid { display:grid; gap:18px; }
        .grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .panel { background: rgba(17,24,39,0.92); border:1px solid var(--line); border-radius:18px; padding:16px; box-shadow: 0 10px 30px rgba(15,23,42,0.35); }
        .panel h2, .panel h3 { margin:0 0 8px; font-size:16px; }
        .muted { color: var(--muted); font-size:13px; }
        .metric { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; }
        .metric .value { font-size:28px; font-weight:700; }
        .metric .trend { color:#bbf7d0; font-size:12px; }
        .chip { display:inline-block; padding:5px 8px; border-radius:999px; font-size:12px; color:#e0f2fe; background: rgba(56,189,248,0.12); border:1px solid rgba(56,189,248,0.16); }
        .mini-list { display:flex; flex-direction:column; gap:10px; }
        .mini-row { display:flex; justify-content:space-between; align-items:center; border-top:1px solid rgba(148,163,184,0.12); padding-top:10px; }
        .mini-row:first-child { border-top:0; padding-top:0; }
        table { width:100%; border-collapse:collapse; font-size:13px; }
        th, td { text-align:left; padding:10px 8px; border-bottom:1px solid rgba(148,163,184,0.12); }
        th { color:#cbd5e1; font-weight:600; }
        .status { display:inline-flex; align-items:center; gap:6px; border-radius:999px; padding:6px 8px; font-size:12px; border:1px solid transparent; }
        .status.good { background: rgba(34,197,94,0.12); color:#bbf7d0; border-color: rgba(34,197,94,0.18); }
        .status.warn { background: rgba(245,158,11,0.12); color:#fde68a; border-color: rgba(245,158,11,0.18); }
        .status.bad { background: rgba(251,113,133,0.12); color:#fecdd3; border-color: rgba(251,113,133,0.18); }
        .footer-note { color: var(--muted); font-size:12px; }

        @media (max-width: 1120px) { .grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); } .grid-2 { grid-template-columns: 1fr; } }
        @media (max-width: 920px) { .shell { flex-direction:column; } .sidebar { width:auto; border-right:0; border-bottom:1px solid var(--line); } }
        @media (max-width: 640px) { .grid-4 { grid-template-columns: 1fr; } .topbar { flex-direction:column; align-items:flex-start; gap:10px; } .top-actions { width:100%; flex-wrap:wrap; } }
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-badge">A</div>
            <div>
                <div>Axionyx ERP</div>
                <div class="muted">Laravel Blade UI</div>
            </div>
        </div>

        <div class="pill">● Live dashboard</div>

        <nav class="nav-group">
            <div class="nav-label">Main</div>
            <a class="nav-link active" href="/">Dashboard <span>&#9656;</span></a>
            <a class="nav-link" href="/bank-accounts">البنوك <span>&#9656;</span></a>
            <a class="nav-link" href="/load-requests">أوامر التحميل <span>&#9656;</span></a>
            <a class="nav-link" href="/vehicles">المركبات <span>&#9656;</span></a>
            <a class="nav-link" href="/return-orders">طلبات الارتجاع <span>&#9656;</span></a>
            <a class="nav-link" href="/item-ledger">حركة الصنف <span>&#9656;</span></a>
            <a class="nav-link" href="/admin">Admin Screens <span>&#9656;</span></a>
            <a class="nav-link" href="/subscription-plans">Subscription Plans <span>&#9656;</span></a>
            <a class="nav-link" href="#">Inventory <span>&#9656;</span></a>
            <a class="nav-link" href="#">Invoices <span>&#9656;</span></a>
            <a class="nav-link" href="#">Customers <span>&#9656;</span></a>
        </nav>

        <nav class="nav-group">
            <div class="nav-label">البنوك</div>
            <a class="nav-link" href="/bank-accounts">حسابات البنوك <span>&#9656;</span></a>
            <a class="nav-link" href="/bank-transfers">التحويلات البنكية <span>&#9656;</span></a>
            <a class="nav-link" href="/treasury-bank-transfers">تحويلات الخزنة↔البنك <span>&#9656;</span></a>
            <a class="nav-link" href="/bank-supplier-payments">مدفوعات الموردين <span>&#9656;</span></a>
            <a class="nav-link" href="/bank-reconciliations">التسويات البنكية <span>&#9656;</span></a>
        </nav>

        <nav class="nav-group">
            <div class="nav-label">Reports</div>
            <a class="nav-link" href="#">Sales Summary <span>▸</span></a>
            <a class="nav-link" href="#">Payroll <span>▸</span></a>
            <a class="nav-link" href="#">Activity Log <span>▸</span></a>
        </nav>

        <nav class="nav-group">
            <div class="nav-label">Admin</div>
            <a class="nav-link" href="/admin/clear-data" style="color: var(--danger);">مسح البيانات <span>&#9656;</span></a>
        </nav>

        <nav class="nav-group">
            <div class="nav-label">الأرصدة الافتتاحية</div>
            <a class="nav-link" href="/opening-balances">قيود الأرصدة الافتتاحية <span>&#9656;</span></a>
            <a class="nav-link" href="/opening-balances?balance_type=cash">أرصدة الخزنة <span>&#9656;</span></a>
            <a class="nav-link" href="/opening-balances?balance_type=accounts">أرصدة البنك <span>&#9656;</span></a>
            <a class="nav-link" href="/opening-balances?balance_type=inventory">أرصدة المنتجات <span>&#9656;</span></a>
            <a class="nav-link" href="/opening-balances?balance_type=suppliers">أرصدة الموردين <span>&#9656;</span></a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <div>
                <h1>@yield('page_title', 'ERP Dashboard')</h1>
                <p>@yield('page_subtitle', 'Monitor operations, inventory, and team work in one place.')</p>
            </div>
            <div class="top-actions">
                <button class="btn">Export</button>
                <button class="btn primary">New Invoice</button>
            </div>
        </header>

        <section class="content">
            @yield('content')
        </section>
    </main>
</div>
</body>
</html>
