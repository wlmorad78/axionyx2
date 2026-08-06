<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Item;
use App\Models\PurchaseExpense;
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
use App\Models\Supplier;
use App\Models\SupplierContact;
use App\Models\SupplierGroup;
use App\Models\SupplierQuotation;
use App\Models\SupplierQuotationItem;
use App\Models\Employee;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class PurchaseFullSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $items = Item::where('company_id', $company->id)->take(5)->get();
            $adminUser = User::where('company_id', $company->id)->first();
            $warehouse = Warehouse::where('company_id', $company->id)->first();
            $employee = Employee::where('company_id', $company->id)->first();

            if ($items->isEmpty()) continue;

            // Supplier Groups
            $groups = [
                ['code' => 'SG-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-01', 'name_ar' => 'مورودين رئيسيين', 'name_en' => 'Main Suppliers'],
                ['code' => 'SG-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-02', 'name_ar' => 'مورودين فرعيين', 'name_en' => 'Sub Suppliers'],
            ];

            $groupModels = [];
            foreach ($groups as $g) {
                $groupModels[] = SupplierGroup::updateOrCreate(
                    ['company_id' => $company->id, 'code' => $g['code']],
                    array_merge($g, ['is_active' => true])
                );
            }

            // Suppliers
            $suppliersData = [
                ['supplier_code' => 'SUP-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-01', 'supplier_name' => 'شركة التوريدات المتحدة', 'phone' => '03-4567890', 'email' => 'supplier1@example.com'],
                ['supplier_code' => 'SUP-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-02', 'supplier_name' => 'مؤسسة الأمل للمواد الغذائية', 'phone' => '02-2345678', 'email' => 'supplier2@example.com'],
                ['supplier_code' => 'SUP-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-03', 'supplier_name' => 'شركة النخبة للتجارة', 'phone' => '011-3456789', 'email' => 'supplier3@example.com'],
            ];

            $supplierModels = [];
            foreach ($suppliersData as $s) {
                $supplierModels[] = Supplier::updateOrCreate(
                    ['company_id' => $company->id, 'supplier_code' => $s['supplier_code']],
                    [
                        'supplier_group_id' => $groupModels[0]?->id,
                        'supplier_name' => $s['supplier_name'],
                        'phone' => $s['phone'],
                        'email' => $s['email'],
                        'tax_number' => 'ST-' . str_pad($company->id * 100 + count($supplierModels) + 1, 6, '0', STR_PAD_LEFT),
                        'payment_term_days' => 60,
                        'is_active' => true,
                    ]
                );

                // Supplier Contact
                SupplierContact::updateOrCreate(
                    ['supplier_id' => $supplierModels[count($supplierModels) - 1]->id, 'is_default' => true],
                    ['contact_name' => 'مدير المبيعات - ' . $s['supplier_name'], 'job_title' => 'مدير المبيعات', 'mobile' => $s['phone'], 'email' => $s['email'], 'is_default' => true]
                );
            }

            // Purchase Requests
            for ($i = 0; $i < 3; $i++) {
                $pr = PurchaseRequest::updateOrCreate(
                    ['company_id' => $company->id, 'request_no' => 'PR-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT)],
                    [
                        'requested_by' => $employee?->id,
                        'request_date' => now()->subDays(20 + $i * 5)->toDateString(),
                        'status' => 'approved',
                        'notes' => 'طلب شراء رقم ' . ($i + 1),
                    ]
                );

                foreach ($items->take(2) as $item) {
                    PurchaseRequestItem::create([
                        'purchase_request_id' => $pr->id,
                        'item_id' => $item->id,
                        'qty' => 50,
                        'notes' => '',
                    ]);
                }
            }

            // Supplier Quotations
            for ($i = 0; $i < 3; $i++) {
                if (!isset($supplierModels[$i % count($supplierModels)])) continue;

                $sq = SupplierQuotation::updateOrCreate(
                    ['company_id' => $company->id, 'quotation_no' => 'SQ-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT)],
                    [
                        'supplier_id' => $supplierModels[$i % count($supplierModels)]->id,
                        'quotation_date' => now()->subDays(15 + $i * 3)->toDateString(),
                        'valid_until' => now()->subDays(15 + $i * 3)->addDays(30)->toDateString(),
                        'status' => 'received',
                    ]
                );

                foreach ($items->take(2) as $j => $item) {
                    SupplierQuotationItem::create([
                        'supplier_quotation_id' => $sq->id,
                        'item_id' => $item->id,
                        'qty' => 50,
                        'price' => 500 + ($j * 50),
                        'net_price' => (50) * (500 + ($j * 50)),
                    ]);
                }
            }

            // Purchase Orders
            for ($i = 0; $i < 3; $i++) {
                if (!isset($supplierModels[$i % count($supplierModels)])) continue;

                $po = PurchaseOrder::updateOrCreate(
                    ['company_id' => $company->id, 'po_no' => 'PO-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT)],
                    [
                        'supplier_id' => $supplierModels[$i % count($supplierModels)]->id,
                        'order_date' => now()->subDays(10 + $i * 3)->toDateString(),
                        'expected_delivery_date' => now()->subDays(10 + $i * 3)->addDays(7)->toDateString(),
                        'subtotal' => 5000 + ($i * 1000),
                        'tax_total' => 700 + ($i * 140),
                        'net_total' => 5700 + ($i * 1140),
                        'status' => 'confirmed',
                        'notes' => 'أمر شراء رقم ' . ($i + 1),
                    ]
                );

                foreach ($items->take(2) as $item) {
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'item_id' => $item->id,
                        'qty' => 50,
                        'price' => 500,
                        'net_amount' => 25000,
                    ]);
                }
            }

            // Purchase Receipts
            for ($i = 0; $i < 3; $i++) {
                $pr = PurchaseReceipt::updateOrCreate(
                    ['company_id' => $company->id, 'receipt_no' => 'PRC-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT)],
                    [
                        'company_id' => $company->id,
                        'warehouse_id' => $warehouse?->id,
                        'supplier_id' => $supplierModels[$i % count($supplierModels)]?->id,
                        'receipt_date' => now()->subDays(7 + $i * 3)->toDateString(),
                        'status' => 'received',
                        'notes' => 'استلام شحنة رقم ' . ($i + 1),
                    ]
                );

                foreach ($items->take(2) as $item) {
                    PurchaseReceiptItem::create([
                        'purchase_receipt_id' => $pr->id,
                        'item_id' => $item->id,
                        'qty' => 50,
                    ]);
                }
            }

            // Purchase Invoices
            for ($i = 0; $i < 3; $i++) {
                $subtotal = 5000 + ($i * 1000);
                $taxTotal = 700 + ($i * 140);
                $netTotal = $subtotal + $taxTotal;

                $pi = PurchaseInvoice::updateOrCreate(
                    ['company_id' => $company->id, 'invoice_no' => 'PINV-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT)],
                    [
                        'supplier_id' => $supplierModels[$i % count($supplierModels)]?->id,
                        'invoice_date' => now()->subDays(5 + $i * 3)->toDateString(),
                        'subtotal' => $subtotal,
                        'tax_total' => $taxTotal,
                        'net_total' => $netTotal,
                        'paid_amount' => $i < 2 ? $netTotal : 0,
                        'remaining_amount' => $i < 2 ? 0 : $netTotal,
                        'status' => $i < 2 ? 'paid' : 'unpaid',
                    ]
                );

                foreach ($items->take(2) as $item) {
                    PurchaseInvoiceItem::create([
                        'purchase_invoice_id' => $pi->id,
                        'item_id' => $item->id,
                        'qty' => 50,
                        'price' => 500,
                        'net_amount' => 25000,
                    ]);
                }
            }

            // Purchase Returns
            if ($items->isNotEmpty()) {
                $ret = PurchaseReturn::updateOrCreate(
                    ['company_id' => $company->id, 'return_no' => 'PRRET-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                    [
                        'supplier_id' => $supplierModels[0]?->id,
                        'return_date' => now()->subDays(3)->toDateString(),
                        'net_total' => 2500,
                        'status' => 'approved',
                    ]
                );

                PurchaseReturnItem::create([
                    'purchase_return_id' => $ret->id,
                    'item_id' => $items[0]->id,
                    'qty' => 5,
                    'price' => 500,
                    'net_amount' => 2500,
                ]);
            }

            // Purchase Expenses
            PurchaseExpense::updateOrCreate(
                ['company_id' => $company->id, 'expense_no' => 'PE-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                [
                    'expense_type' => 'shipping',
                    'amount' => 500,
                    'notes' => 'مصاريف شحن',
                ]
            );
        }
    }
}
