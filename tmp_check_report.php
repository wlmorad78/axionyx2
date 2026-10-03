<?php
$u = \App\Models\User::whereNotNull('company_id')->first();
$req = \Illuminate\Http\Request::create('/api/reports/daily-product-sales', 'GET', [
    'date_from' => '2026-10-01',
    'date_to' => '2026-10-01',
]);
$req->setUserResolver(function () use ($u) { return $u; });
$c = new \App\Http\Controllers\Api\Reports\ReportController();
$res = $c->dailyProductSales($req);
$j = json_decode($res->getContent(), true);
echo json_encode($j, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
