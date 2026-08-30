<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\DailyDistributionDashboard;
use App\Models\DailyRoute;
use App\Models\DailyRouteCustomer;
use App\Models\DistributionPlan;
use App\Models\DistributionPlanCustomer;
use App\Models\DistributionPlanItem;
use App\Models\DistributionPlanProduct;
use App\Models\DistributionPlanRep;
use App\Models\Employee;
use App\Models\IssueOrder;
use App\Models\IssueOrderItem;
use App\Models\Item;
use App\Models\Sales\LoadRequest;
use App\Models\Sales\LoadRequestItem;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\RepItemDistribution;
use App\Models\ReturnOrder;
use App\Models\ReturnOrderItem;
use App\Models\Route;
use App\Models\RouteEvent;
use App\Models\RouteVisit;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceDiscount;
use App\Models\SalesInvoiceItem;
use App\Models\SalesInvoiceTax;
use App\Models\SalesmanSettlement;
use App\Models\Sales\RepDailySettlement;
use App\Models\Collection;
use App\Models\CustomerReturn;
use App\Models\CustomerReturnItem;
use App\Models\SalesTerritory;
use App\Models\Supplier;
use App\Models\SupplierContact;
use App\Models\SupplierGroup;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DistributionTenDaysSeeder extends Seeder
{
    private array $counters = [];

    private function clearDemo(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        $companies = Company::all();
        foreach ($companies as $company) {
            $cid = $company->id;

            $planIds = DB::table('distribution_plans')->where('plan_no', 'LIKE', 'DPDEMO%')->where('company_id', $cid)->pluck('id')->all();
            if ($planIds) {
                DB::table('distribution_plan_items')->whereIn('distribution_plan_id', $planIds)->delete();
                DB::table('distribution_plan_customers')->whereIn('distribution_plan_id', $planIds)->delete();
                DB::table('distribution_plan_reps')->whereIn('distribution_plan_id', $planIds)->delete();
                DB::table('distribution_plan_products')->whereIn('distribution_plan_id', $planIds)->delete();
                DB::table('distribution_plans')->whereIn('id', $planIds)->delete();
            }

            $lrIds = DB::table('load_requests')->where('request_no', 'LIKE', 'LRDEMO%')->where('company_id', $cid)->pluck('id')->all();
            if ($lrIds) {
                DB::table('load_request_items')->whereIn('load_request_id', $lrIds)->delete();
            }
            DB::table('load_requests')->where('request_no', 'LIKE', 'LRDEMO%')->where('company_id', $cid)->delete();

            $ioIds = DB::table('issue_orders')->where('company_id', $cid)
                ->where(function ($q) { $q->where('issue_no', 'LIKE', 'IODEMO%')->orWhere('issue_no', 'LIKE', 'IO-0%'); })
                ->pluck('id')->all();
            if ($ioIds) {
                DB::table('issue_order_items')->whereIn('issue_order_id', $ioIds)->delete();
            }
            DB::table('issue_orders')->where('company_id', $cid)
                ->where(function ($q) { $q->where('issue_no', 'LIKE', 'IODEMO%')->orWhere('issue_no', 'LIKE', 'IO-0%'); })
                ->delete();

            $roIds = DB::table('return_orders')->where('return_no', 'LIKE', 'RODEMO%')->where('company_id', $cid)->pluck('id')->all();
            if ($roIds) {
                DB::table('return_order_items')->whereIn('return_order_id', $roIds)->delete();
            }
            DB::table('return_orders')->where('return_no', 'LIKE', 'RODEMO%')->where('company_id', $cid)->delete();

            $crIds = DB::table('customer_returns')->where('return_no', 'LIKE', 'CRETDEMO%')->where('company_id', $cid)->pluck('id')->all();
            if ($crIds) {
                DB::table('customer_return_items')->whereIn('customer_return_id', $crIds)->delete();
            }
            DB::table('customer_returns')->where('return_no', 'LIKE', 'CRETDEMO%')->where('company_id', $cid)->delete();

            $invIds = DB::table('sales_invoices')->where('invoice_no', 'LIKE', 'INVDEMO%')->where('company_id', $cid)->pluck('id')->all();
            if ($invIds) {
                DB::table('sales_invoice_items')->whereIn('sales_invoice_id', $invIds)->delete();
                DB::table('sales_invoice_discounts')->whereIn('sales_invoice_id', $invIds)->delete();
                DB::table('sales_invoice_taxes')->whereIn('sales_invoice_id', $invIds)->delete();
            }
            DB::table('sales_invoices')->where('invoice_no', 'LIKE', 'INVDEMO%')->where('company_id', $cid)->delete();

            DB::table('collections')->where('collection_no', 'LIKE', 'COLDEMO%')->where('company_id', $cid)->delete();
            DB::table('salesman_settlements')->where('settlement_no', 'LIKE', 'STLDEMO%')->where('company_id', $cid)->delete();
            DB::table('rep_daily_settlements')->where('settlement_no', 'LIKE', 'RDSDEMO%')->where('company_id', $cid)->delete();
            DB::table('rep_item_distributions')->where('company_id', $cid)->delete();
            DB::table('daily_distribution_dashboards')->where('company_id', $cid)->delete();
            DB::table('route_visits')->where('company_id', $cid)->delete();

            $routeIds = DB::table('routes')->where('company_id', $cid)->pluck('id')->all();
            if ($routeIds) {
                $drIds = DB::table('daily_routes')->whereIn('route_id', $routeIds)->pluck('id')->all();
                if ($drIds) {
                    DB::table('daily_route_customers')->whereIn('daily_route_id', $drIds)->delete();
                    DB::table('route_events')->whereIn('daily_route_id', $drIds)->delete();
                }
                DB::table('daily_routes')->whereIn('route_id', $routeIds)->delete();
            }

            $prIds = DB::table('purchase_requests')->where('request_no', 'LIKE', 'PRDEMO%')->where('company_id', $cid)->pluck('id')->all();
            if ($prIds) {
                DB::table('purchase_request_items')->whereIn('purchase_request_id', $prIds)->delete();
            }
            DB::table('purchase_requests')->where('request_no', 'LIKE', 'PRDEMO%')->where('company_id', $cid)->delete();

            $poIds = DB::table('purchase_orders')->where('po_no', 'LIKE', 'PODEMO%')->where('company_id', $cid)->pluck('id')->all();
            if ($poIds) {
                DB::table('purchase_order_items')->whereIn('purchase_order_id', $poIds)->delete();
            }
            DB::table('purchase_orders')->where('po_no', 'LIKE', 'PODEMO%')->where('company_id', $cid)->delete();

            $rcIds = DB::table('purchase_receipts')->where('receipt_no', 'LIKE', 'PRCDEMO%')->where('company_id', $cid)->pluck('id')->all();
            if ($rcIds) {
                DB::table('purchase_receipt_items')->whereIn('purchase_receipt_id', $rcIds)->delete();
            }
            DB::table('purchase_receipts')->where('receipt_no', 'LIKE', 'PRCDEMO%')->where('company_id', $cid)->delete();

            $piIds = DB::table('purchase_invoices')->where('invoice_no', 'LIKE', 'PINVDEMO%')->where('company_id', $cid)->pluck('id')->all();
            if ($piIds) {
                DB::table('purchase_invoice_items')->whereIn('purchase_invoice_id', $piIds)->delete();
            }
            DB::table('purchase_invoices')->where('invoice_no', 'LIKE', 'PINVDEMO%')->where('company_id', $cid)->delete();

            $pretIds = DB::table('purchase_returns')->where('return_no', 'LIKE', 'PRETDEMO%')->where('company_id', $cid)->pluck('id')->all();
            if ($pretIds) {
                DB::table('purchase_return_items')->whereIn('purchase_return_id', $pretIds)->delete();
            }
            DB::table('purchase_returns')->where('return_no', 'LIKE', 'PRETDEMO%')->where('company_id', $cid)->delete();
        }

        DB::statement('PRAGMA foreign_keys = ON');
    }

    private function num(string $type, int $companyId, string $prefix): string
    {        $key = $companyId . ':' . $type;
        $this->counters[$key] = ($this->counters[$key] ?? 0) + 1;
        return $prefix . '-' . str_pad($companyId, 2, '0', STR_PAD_LEFT) . '-' . str_pad($this->counters[$key], 5, '0', STR_PAD_LEFT);
    }

    public function run(): void
    {
        $this->clearDemo();

        $workingDays = $this->getWorkingDays(10);
        $this->command?->info('Working days: ' . implode(', ', $workingDays->map(fn($d) => $d->toDateString())->toArray()));

        $companies = Company::all();

        foreach ($companies as $company) {
            $branch = Branch::where('company_id', $company->id)->first();
            $warehouse = Warehouse::where('company_id', $company->id)->first()
                ?? $this->ensureWarehouse($company, $branch);
            $territory = SalesTerritory::where('company_id', $company->id)->first();
            $items = Item::where('company_id', $company->id)->take(8)->get();
            $routes = Route::where('company_id', $company->id)->take(3)->get();
            $reps = Employee::where('company_id', $company->id)->take(4)->get();
            $customers = Customer::where('company_id', $company->id)->take(20)->get();
            $unit = Unit::where('company_id', $company->id)->first();

            if ($items->isEmpty() || $reps->isEmpty() || $customers->isEmpty()) {
                $this->command?->warn("Skipping company {$company->id}: missing master data");
                continue;
            }

            if ($routes->isEmpty()) {
                $routes = collect([Route::create([
                    'company_id' => $company->id,
                    'branch_id' => $branch?->id,
                    'sales_territory_id' => $territory?->id,
                    'code' => 'RT-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-D01',
                    'name_ar' => 'خط سير تجريبي',
                    'is_active' => true,
                ])]);
            }

            $supplier = $this->ensureSupplier($company, $branch);
            $unitPrice = [];
            foreach ($items as $idx => $it) {
                $unitPrice[$it->id] = 5 + ($idx * 3) * 10;
            }

            $planCreatedForWeek = [];

            foreach ($workingDays as $day) {
                $weekKey = $day->copy()->startOfWeek()->toDateString();
                if (!isset($planCreatedForWeek[$weekKey])) {
                    $this->createDistributionPlan($company, $branch, $territory, $items, $reps, $customers, $day);
                    $planCreatedForWeek[$weekKey] = true;
                }

                foreach ($reps as $repIdx => $rep) {
                    $route = $routes[$repIdx % $routes->count()];
                    $this->seedRepDay(
                        $company, $branch, $warehouse, $territory, $route, $rep,
                        $items, $customers, $unit, $unitPrice, $day
                    );
                }

                $this->seedPurchases($company, $branch, $warehouse, $territory, $items, $supplier, $unit, $day);
            }
        }

        $this->command?->info('Distribution 10-day demo data created successfully.');
    }

    private function getWorkingDays(int $count): \Illuminate\Support\Collection
    {
        $days = collect();
        $d = Carbon::today()->subDay();
        while ($days->count() < $count) {
            $w = (int) $d->format('w');
            if ($w !== 5 && $w !== 6) {
                $days->push($d->copy());
            }
            $d->subDay();
        }
        return $days->sort();
    }

    private function seedRepDay(
        Company $company, ?Branch $branch, ?Warehouse $warehouse, ?SalesTerritory $territory,
        Route $route, Employee $rep, $items, $customers, ?Unit $unit, array $unitPrice, Carbon $day
    ): void {
        $loadItems = $items->take(rand(3, 4));
        $dayStr = $day->toDateString();

        // ── Load Request (أمر تحميل) ──
        $lr = LoadRequest::create([
            'company_id' => $company->id,
            'branch_id' => $branch?->id,
            'warehouse_id' => $warehouse?->id,
            'employee_id' => $rep->id,
            'supervisor_employee_id' => $rep->id,
            'sales_territory_id' => $territory?->id,
            'trip_date' => $dayStr,
            'load_type' => 'standard',
            'priority' => 'normal',
            'request_no' => $this->num('lr', $company->id, 'LRDEMO'),
            'request_date' => $dayStr,
            'status' => 'approved',
            'requested_by' => $rep->id,
            'create_by' => $rep->id,
            'create_at' => $day->copy()->setTime(7, 0),
            'notes' => 'طلب تحميل تجريبي',
        ]);

        $loadedTotal = 0;
        $issueItemsData = [];
        foreach ($loadItems as $item) {
            $qty = rand(20, 60);
            $price = $unitPrice[$item->id];
            $total = $qty * $price;
            $loadedTotal += $total;
            LoadRequestItem::create([
                'load_request_id' => $lr->id,
                'item_id' => $item->id,
                'unit_id' => $unit?->id,
                'quantity' => $qty,
                'conversion_factor' => 1,
                'base_quantity' => $qty,
                'unit_price' => $price,
                'total_price' => $total,
                'notes' => '',
            ]);
            $issueItemsData[] = ['item' => $item, 'qty' => $qty, 'price' => $price];
        }

        // ── Issue Order (أمر صرف للسيارة) ──
        $io = IssueOrder::create([
            'company_id' => $company->id,
            'branch_id' => $branch?->id,
            'warehouse_id' => $warehouse?->id,
            'load_request_id' => $lr->id,
            'issue_no' => $this->num('io', $company->id, 'IODEMO'),
            'issue_date' => $dayStr,
            'issue_time' => '08:00',
            'employee_id' => $rep->id,
            'sales_territory_id' => $territory?->id,
            'route_id' => $route->id,
            'status' => 'received',
            'issued_by' => $rep->id,
            'received_by' => $rep->id,
            'received_at' => $day->copy()->setTime(8, 30),
            'notes' => 'صرف تجريبي',
        ]);

        foreach ($issueItemsData as $d) {
            IssueOrderItem::create([
                'issue_order_id' => $io->id,
                'item_id' => $d['item']->id,
                'item_unit_id' => $unit?->id,
                'unit_id' => $unit?->id,
                'requested_quantity' => $d['qty'],
                'issued_quantity' => $d['qty'],
                'conversion_factor' => 1,
                'base_quantity' => $d['qty'],
                'purchase_price' => $d['price'] * 0.7,
                'sales_price' => $d['price'],
                'total_amount' => $d['qty'] * $d['price'],
                'notes' => '',
            ]);
        }

        // ── Daily Route (خط السير اليومي) ──
        $routeCustomers = $customers->take(rand(3, 5));
        $dr = DailyRoute::create([
            'route_id' => $route->id,
            'employee_id' => $rep->id,
            'route_date' => $dayStr,
            'status' => 'completed',
            'planned_start_time' => '08:00',
            'planned_end_time' => '17:00',
            'actual_start_time' => '08:05',
            'actual_end_time' => '16:40',
            'planned_customers' => $routeCustomers->count(),
            'visited_customers' => $routeCustomers->count(),
            'total_distance_km' => rand(15, 60),
            'notes' => 'خط سير تجريبي',
        ]);

        $visitedCount = 0;
        $salesTotal = 0;
        $collectionsTotal = 0;
        $returnsTotal = 0;
        $invoiceIds = [];

        foreach ($routeCustomers as $cIdx => $customer) {
            $checkIn = $day->copy()->setTime(9 + $cIdx, 0);
            $checkOut = $checkIn->copy()->addMinutes(rand(20, 45));
            DailyRouteCustomer::create([
                'daily_route_id' => $dr->id,
                'customer_id' => $customer->id,
                'visit_order' => $cIdx + 1,
                'planned_time' => $checkIn->format('H:i:s'),
                'actual_check_in' => $checkIn->format('H:i:s'),
                'actual_check_out' => $checkOut->format('H:i:s'),
                'latitude' => 31.2 + ($cIdx * 0.005),
                'longitude' => 29.9 + ($cIdx * 0.005),
                'visit_status' => 'visited',
                'notes' => '',
            ]);

            RouteVisit::create([
                'company_id' => $company->id,
                'branch_id' => $branch?->id,
                'route_id' => $route->id,
                'sales_rep_id' => $rep->id,
                'customer_id' => $customer->id,
                'visit_date' => $dayStr,
                'visit_time' => $checkIn->format('H:i:s'),
                'check_in_time' => $checkIn->format('H:i:s'),
                'check_out_time' => $checkOut->format('H:i:s'),
                'latitude' => 31.2 + ($cIdx * 0.005),
                'longitude' => 29.9 + ($cIdx * 0.005),
                'visit_type' => 'planned',
                'visit_status' => 'completed',
                'notes' => '',
            ]);
            $visitedCount++;

            // ── Sales Invoice (فاتورة مبيعات) ──
            if (rand(1, 100) <= 80) {
                $invItems = $items->take(rand(2, 3));
                $subtotal = 0;
                $itemDisc = 0;
                $taxTotal = 0;
                $invoice = SalesInvoice::create([
                    'company_id' => $company->id,
                    'branch_id' => $branch?->id,
                    'warehouse_id' => $warehouse?->id,
                    'load_request_id' => $lr->id,
                    'issue_order_id' => $io->id,
                    'route_id' => $route->id,
                    'sales_territory_id' => $territory?->id,
                    'sales_rep_id' => $rep->id,
                    'customer_id' => $customer->id,
                    'invoice_no' => $this->num('inv', $company->id, 'INVDEMO'),
                    'invoice_date' => $dayStr,
                    'invoice_time' => $checkIn->format('H:i:s'),
                    'subtotal' => 0,
                    'item_discount_total' => 0,
                    'invoice_discount_total' => 0,
                    'tax_total' => 0,
                    'net_total' => 0,
                    'paid_amount' => 0,
                    'remaining_amount' => 0,
                    'status' => 'approved',
                    'notes' => 'فاتورة تجريبية',
                ]);

                foreach ($invItems as $item) {
                    $qty = rand(2, 10);
                    $price = $unitPrice[$item->id];
                    $gross = $qty * $price;
                    $disc = round($gross * 0.05, 2);
                    $tax = round(($gross - $disc) * 0.14, 2);
                    $net = round($gross - $disc + $tax, 2);
                    SalesInvoiceItem::create([
                        'sales_invoice_id' => $invoice->id,
                        'item_id' => $item->id,
                        'unit_id' => $unit?->id,
                        'qty' => $qty,
                        'conversion_factor' => 1,
                        'base_quantity' => $qty,
                        'price' => $price,
                        'gross_amount' => $gross,
                        'discount_type' => 'percentage',
                        'discount_value' => 5,
                        'discount_amount' => $disc,
                        'tax_percent' => 14,
                        'tax_amount' => $tax,
                        'net_amount' => $net,
                        'notes' => '',
                    ]);
                    $subtotal += $gross;
                    $itemDisc += $disc;
                    $taxTotal += $tax;
                }
                $netTotal = round($subtotal - $itemDisc + $taxTotal, 2);
                $paid = rand(0, 1) ? $netTotal : round($netTotal * 0.5, 2);
                $invoice->update([
                    'subtotal' => round($subtotal, 2),
                    'item_discount_total' => round($itemDisc, 2),
                    'tax_total' => round($taxTotal, 2),
                    'net_total' => $netTotal,
                    'paid_amount' => $paid,
                    'remaining_amount' => round($netTotal - $paid, 2),
                ]);
                SalesInvoiceDiscount::create([
                    'sales_invoice_id' => $invoice->id,
                    'discount_type' => 'percentage',
                    'discount_value' => 5,
                    'discount_amount' => round($itemDisc, 2),
                    'reason' => 'خصم عام',
                ]);
                SalesInvoiceTax::create([
                    'sales_invoice_id' => $invoice->id,
                    'tax_name' => 'VAT',
                    'tax_percent' => 14,
                    'tax_amount' => round($taxTotal, 2),
                ]);
                $salesTotal += $netTotal;
                $invoiceIds[] = $invoice;

                if ($paid > 0) {
                    Collection::create([
                        'company_id' => $company->id,
                        'branch_id' => $branch?->id,
                        'collection_no' => $this->num('col', $company->id, 'COLDEMO'),
                        'collection_date' => $dayStr,
                        'collection_time' => $checkOut->format('H:i:s'),
                        'sales_rep_id' => $rep->id,
                        'customer_id' => $customer->id,
                        'sales_invoice_id' => $invoice->id,
                        'amount' => $paid,
                        'status' => 'collected',
                        'notes' => '',
                    ]);
                    $collectionsTotal += $paid;
                }

                // ── Customer Return (ارتجاع من العميل) ──
                if (rand(1, 100) <= 25) {
                    $cItem = $invItems->random();
                    $rqty = rand(1, 3);
                    $cr = CustomerReturn::create([
                        'company_id' => $company->id,
                        'branch_id' => $branch?->id,
                        'warehouse_id' => $warehouse?->id,
                        'return_no' => $this->num('cret', $company->id, 'CRETDEMO'),
                        'return_date' => $dayStr,
                        'return_time' => $checkOut->format('H:i:s'),
                        'sales_invoice_id' => $invoice->id,
                        'customer_id' => $customer->id,
                        'sales_rep_id' => $rep->id,
                        'route_id' => $route->id,
                        'subtotal' => $rqty * $unitPrice[$cItem->id],
                        'tax_total' => 0,
                        'net_total' => $rqty * $unitPrice[$cItem->id],
                        'status' => 'approved',
                        'notes' => 'ارتجاع تجريبي',
                    ]);
                    CustomerReturnItem::create([
                        'customer_return_id' => $cr->id,
                        'item_id' => $cItem->id,
                        'qty' => $rqty,
                        'price' => $unitPrice[$cItem->id],
                        'net_amount' => $rqty * $unitPrice[$cItem->id],
                        'notes' => 'ارتجاع',
                    ]);
                    $returnsTotal -= ($rqty * $unitPrice[$cItem->id]);
                }
            }
        }

        // ── Return Order from rep (طلب ارتجاع المندوب) ──
        if (rand(1, 100) <= 60) {
            $ro = ReturnOrder::create([
                'company_id' => $company->id,
                'branch_id' => $branch?->id,
                'warehouse_id' => $warehouse?->id,
                'load_request_id' => $lr->id,
                'issue_order_id' => $io->id,
                'return_no' => $this->num('ro', $company->id, 'RODEMO'),
                'return_type' => 'damaged',
                'return_date' => $dayStr,
                'employee_id' => $rep->id,
                'sales_territory_id' => $territory?->id,
                'status_id' => 'approved',
                'received_by' => $rep->id,
                'notes' => 'ارتجاع من المندوب',
            ]);
            $rItem = $issueItemsData[array_rand($issueItemsData)];
            $rqty = rand(1, 5);
            ReturnOrderItem::create([
                'return_order_id' => $ro->id,
                'item_id' => $rItem['item']->id,
                'item_unit_id' => $unit?->id,
                'returned_quantity' => $rqty,
                'sold_quantity' => 0,
                'sales_price' => $rItem['price'],
                'line_total' => $rqty * $rItem['price'],
                'return_condition' => 'damaged',
                'notes' => '',
            ]);
            $returnsTotal -= ($rqty * $rItem['price']);
        }

        // ── Rep Item Distributions (توزيع أصناف المندوب) ──
        foreach ($issueItemsData as $d) {
            $loaded = $d['qty'];
            $sold = (int) round($loaded * (rand(60, 90) / 100));
            $returned = (int) round($loaded * (rand(0, 15) / 100));
            $remaining = max(0, $loaded - $sold - $returned);
            RepItemDistribution::create([
                'company_id' => $company->id,
                'employee_id' => $rep->id,
                'item_id' => $d['item']->id,
                'issue_order_id' => $io->id,
                'loaded_qty' => $loaded,
                'sold_qty' => $sold,
                'returned_qty' => $returned,
                'remaining_qty' => $remaining,
                'unit_price' => $d['price'],
                'status' => 'active',
            ]);
        }

        // ── Rep Daily Settlement (تسوية المندوب اليومية) ──
        $expectedCash = $salesTotal;
        $actualCash = round($collectionsTotal + rand(-200, 200), 2);
        $cashDiff = round($actualCash - $expectedCash, 2);
        RepDailySettlement::create([
            'company_id' => $company->id,
            'branch_id' => $branch?->id,
            'settlement_no' => $this->num('rds', $company->id, 'RDSDEMO'),
            'settlement_date' => $dayStr,
            'sales_rep_id' => $rep->id,
            'issue_order_id' => $io->id,
            'total_sales_value' => round($salesTotal, 2),
            'total_collections_value' => round($collectionsTotal, 2),
            'total_expenses' => rand(0, 100),
            'total_from_balance' => 0,
            'expected_cash' => round($expectedCash, 2),
            'actual_cash' => $actualCash,
            'cash_difference' => $cashDiff,
            'shortage' => $cashDiff < 0 ? abs($cashDiff) : 0,
            'shortage_status' => 'pending',
            'notes' => '',
            'status' => 'submitted',
            'created_by' => $rep->id,
        ]);

        // ── Salesman Settlement (تسوية المندوبين) ──
        SalesmanSettlement::create([
            'company_id' => $company->id,
            'branch_id' => $branch?->id,
            'settlement_no' => $this->num('stl', $company->id, 'STLDEMO'),
            'settlement_date' => $dayStr,
            'sales_rep_id' => $rep->id,
            'route_id' => $route->id,
            'load_request_id' => $lr->id,
            'issue_order_id' => $io->id,
            'total_loaded_value' => round($loadedTotal, 2),
            'total_sales_value' => round($salesTotal, 2),
            'total_returns_value' => round(abs($returnsTotal), 2),
            'total_collections_value' => round($collectionsTotal, 2),
            'expected_cash' => round($expectedCash, 2),
            'actual_cash' => $actualCash,
            'cash_difference' => $cashDiff,
            'notes' => '',
            'status' => 'approved',
            'created_by' => $rep->id,
        ]);

        // ── Daily Distribution Dashboard ──
        DailyDistributionDashboard::create([
            'company_id' => $company->id,
            'branch_id' => $branch?->id,
            'dashboard_date' => $dayStr,
            'sales_rep_id' => $rep->id,
            'route_id' => $route->id,
            'planned_customers' => $routeCustomers->count(),
            'visited_customers' => $visitedCount,
            'invoices_count' => count($invoiceIds),
            'sales_amount' => round($salesTotal, 2),
            'returns_amount' => round($returnsTotal, 2),
            'collections_amount' => round($collectionsTotal, 2),
            'loaded_amount' => round($loadedTotal, 2),
            'settled_amount' => round($collectionsTotal, 2),
            'cash_difference' => $cashDiff,
        ]);

        // ── Route Events ──
        RouteEvent::create([
            'daily_route_id' => $dr->id,
            'customer_id' => $routeCustomers->first()?->id,
            'event_type' => 'start',
            'description' => 'بداية الخط',
            'latitude' => 31.2,
            'longitude' => 29.9,
            'event_time' => $day->copy()->setTime(8, 5),
            'severity' => 'info',
            'notes' => '',
        ]);
    }

    private function seedPurchases(
        Company $company, ?Branch $branch, ?Warehouse $warehouse, ?SalesTerritory $territory,
        $items, Supplier $supplier, ?Unit $unit, Carbon $day
    ): void {
        $dayStr = $day->toDateString();
        $pItems = $items->take(2);

        $pr = PurchaseRequest::create([
            'company_id' => $company->id,
            'request_no' => $this->num('pr', $company->id, 'PRDEMO'),
            'requested_by' => null,
            'request_date' => $dayStr,
            'status' => 'approved',
            'notes' => 'طلب شراء تجريبي',
        ]);
        foreach ($pItems as $item) {
            PurchaseRequestItem::create([
                'purchase_request_id' => $pr->id,
                'item_id' => $item->id,
                'qty' => rand(50, 150),
                'notes' => '',
            ]);
        }

        $po = PurchaseOrder::create([
            'company_id' => $company->id,
            'po_no' => $this->num('po', $company->id, 'PODEMO'),
            'supplier_id' => $supplier->id,
            'order_date' => $dayStr,
            'expected_delivery_date' => $day->copy()->addDays(7)->toDateString(),
            'subtotal' => 0,
            'tax_total' => 0,
            'net_total' => 0,
            'status' => 'confirmed',
            'notes' => 'أمر شراء تجريبي',
        ]);
        $poSub = 0;
        foreach ($pItems as $idx => $item) {
            $qty = rand(50, 150);
            $price = 5 + ($idx * 3) * 10;
            $net = $qty * $price;
            $poSub += $net;
            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'item_id' => $item->id,
                'qty' => $qty,
                'price' => $price,
                'net_amount' => $net,
            ]);
        }
        $poTax = round($poSub * 0.14, 2);
        $po->update(['subtotal' => $poSub, 'tax_total' => $poTax, 'net_total' => round($poSub + $poTax, 2)]);

        $rc = PurchaseReceipt::create([
            'company_id' => $company->id,
            'receipt_no' => $this->num('prc', $company->id, 'PRCDEMO'),
            'warehouse_id' => $warehouse?->id,
            'supplier_id' => $supplier->id,
            'receipt_date' => $dayStr,
            'status' => 'received',
            'notes' => 'استلام تجريبي',
        ]);
        foreach ($pItems as $item) {
            PurchaseReceiptItem::create([
                'purchase_receipt_id' => $rc->id,
                'item_id' => $item->id,
                'qty' => rand(50, 150),
            ]);
        }

        $pinv = PurchaseInvoice::create([
            'company_id' => $company->id,
            'invoice_no' => $this->num('pinv', $company->id, 'PINVDEMO'),
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse?->id,
            'invoice_date' => $dayStr,
            'subtotal' => 0,
            'tax_total' => 0,
            'net_total' => 0,
            'paid_amount' => 0,
            'remaining_amount' => 0,
            'status' => 'unpaid',
            'notes' => 'فاتورة مشتريات تجريبية',
        ]);
        $piSub = 0;
        foreach ($pItems as $idx => $item) {
            $qty = rand(50, 150);
            $price = 5 + ($idx * 3) * 10;
            $net = $qty * $price;
            $piSub += $net;
            PurchaseInvoiceItem::create([
                'purchase_invoice_id' => $pinv->id,
                'item_id' => $item->id,
                'qty' => $qty,
                'price' => $price,
                'net_amount' => $net,
            ]);
        }
        $piTax = round($piSub * 0.14, 2);
        $pinv->update([
            'subtotal' => $piSub,
            'tax_total' => $piTax,
            'net_total' => round($piSub + $piTax, 2),
            'paid_amount' => round(($piSub + $piTax) * 0.5, 2),
            'remaining_amount' => round(($piSub + $piTax) * 0.5, 2),
        ]);

        if (rand(1, 100) <= 30) {
            $rItem = $pItems->first();
            $ret = PurchaseReturn::create([
                'company_id' => $company->id,
                'return_no' => $this->num('pret', $company->id, 'PRETDEMO'),
                'supplier_id' => $supplier->id,
                'return_date' => $dayStr,
                'net_total' => 0,
                'status' => 'approved',
                'reason' => 'مرتجع مشتريات تجريبي',
            ]);
            $rqty = rand(2, 10);
            $rprice = 5;
            PurchaseReturnItem::create([
                'purchase_return_id' => $ret->id,
                'item_id' => $rItem->id,
                'qty' => $rqty,
                'price' => $rprice,
                'net_amount' => $rqty * $rprice,
            ]);
            $ret->update(['net_total' => $rqty * $rprice]);
        }
    }

    private function ensureWarehouse(Company $company, ?Branch $branch): Warehouse
    {
        return Warehouse::create([
            'company_id' => $company->id,
            'branch_id' => $branch?->id,
            'code' => 'WH-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-DEMO',
            'name' => 'مخزن تجريبي',
            'name_ar' => 'مخزن تجريبي',
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    private function ensureSupplier(Company $company, ?Branch $branch): Supplier
    {
        $supplier = Supplier::where('company_id', $company->id)->first();
        if ($supplier) return $supplier;

        $group = SupplierGroup::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'SG-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-DEMO'],
            ['name_ar' => 'موردون تجريبيون', 'is_active' => true]
        );
        $supplier = Supplier::create([
            'company_id' => $company->id,
            'supplier_group_id' => $group->id,
            'supplier_code' => 'SUP-DEMO-' . str_pad($company->id, 3, '0', STR_PAD_LEFT),
            'supplier_name' => 'مورد تجريبي',
            'phone' => '03-0000000',
            'email' => 'demo-supplier@example.com',
            'tax_number' => 'ST-' . str_pad($company->id, 6, '0', STR_PAD_LEFT),
            'payment_term_days' => 60,
            'is_active' => true,
        ]);
        SupplierContact::create([
            'supplier_id' => $supplier->id,
            'contact_name' => 'مدير المبيعات',
            'job_title' => 'مدير المبيعات',
            'mobile' => '03-0000000',
            'email' => 'demo-supplier@example.com',
            'is_default' => true,
        ]);
        return $supplier;
    }

    private function createDistributionPlan(
        Company $company, ?Branch $branch, ?SalesTerritory $territory,
        $items, $reps, $customers, Carbon $day
    ): void {
        $plan = DistributionPlan::create([
            'company_id' => $company->id,
            'plan_no' => $this->num('dp', $company->id, 'DPDEMO'),
            'plan_name' => 'خطة توزيع تجريبية ' . $day->toDateString(),
            'plan_date' => $day->toDateString(),
            'history_months' => 6,
            'allocation_factor' => 1,
            'enforce_plan_limit' => false,
            'total_quantity' => 0,
            'total_demand' => 0,
            'units_per_carton' => 50,
            'status' => 'approved',
            'notes' => 'خطة تجريبية',
            'created_by' => $reps->first()?->id,
            'approved_by' => $reps->first()?->id,
            'approved_at' => $day->copy()->setTime(6, 0),
        ]);

        foreach ($items as $item) {
            DistributionPlanProduct::create([
                'distribution_plan_id' => $plan->id,
                'item_id' => $item->id,
                'available_qty' => rand(200, 500),
                'product_ratio' => round(rand(5, 20) / 10, 2),
            ]);
        }

        $totalQuota = 0;
        foreach ($reps as $repIdx => $rep) {
            $repPlan = DistributionPlanRep::create([
                'distribution_plan_id' => $plan->id,
                'sales_rep_id' => $rep->id,
                'route_id' => null,
                'avg_monthly_sales' => rand(100000, 300000),
                'rep_weight' => round(rand(5, 15) / 10, 4),
                'total_quota' => 0,
            ]);
            $repCustomers = $customers->slice($repIdx * 5, 5);
            $repQuota = 0;
            foreach ($repCustomers as $customer) {
                $custPlan = DistributionPlanCustomer::create([
                    'distribution_plan_id' => $plan->id,
                    'distribution_plan_rep_id' => $repPlan->id,
                    'customer_id' => $customer->id,
                    'avg_monthly_sales' => rand(5000, 20000),
                    'customer_weight' => round(rand(5, 20) / 10, 4),
                    'total_quota' => 0,
                    'allocated_qty' => 0,
                    'final_qty' => 0,
                    'is_manual_override' => false,
                ]);
                $alloc = 0;
                foreach ($items->take(3) as $item) {
                    $qty = rand(10, 40);
                    $alloc += $qty;
                    DistributionPlanItem::create([
                        'distribution_plan_id' => $plan->id,
                        'distribution_plan_customer_id' => $custPlan->id,
                        'item_id' => $item->id,
                        'historical_avg' => rand(10, 30),
                        'historical_ratio' => round(rand(5, 20) / 10, 2),
                        'allocated_qty' => $qty,
                        'final_qty' => $qty,
                        'is_manual_override' => false,
                    ]);
                }
                $custPlan->update(['allocated_qty' => $alloc, 'final_qty' => $alloc, 'total_quota' => $alloc]);
                $repQuota += $alloc;
            }
            $repPlan->update(['total_quota' => $repQuota]);
            $totalQuota += $repQuota;
        }
        $plan->update(['total_quantity' => $totalQuota, 'total_demand' => $totalQuota]);
    }
}
