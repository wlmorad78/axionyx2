<?php

/**
 * Action-based permission definitions.
 *
 * Format: {module}.{resource}.{action}
 *
 * Target: ~1000 permissions covering ALL MVP modules.
 * Each resource gets: view, create, edit, delete, restore, export, print
 * Documents also get: post, cancel, approve, reopen
 * Financial resources get: statement, movements
 *
 * Used by:
 *   - Laravel: PermissionService::check()
 *   - Flutter: GET /api/permissions → renders/hides buttons
 *   - Audit: Logs which permission was checked
 *   - Menu Builder: Dynamic sidebar generation
 *   - Plan Gating: Feature enable/disable per plan
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Permission Definitions (~1000 permissions)
    |--------------------------------------------------------------------------
    */
    'definitions' => [

        // ════════════════════════════════════════════════════════════
        //  MODULE 1: CUSTOMERS  (28 permissions)
        // ════════════════════════════════════════════════════════════

        'customer.customer.view'              => 'عرض قائمة العملاء',
        'customer.customer.create'            => 'إضافة عميل',
        'customer.customer.edit'              => 'تعديل بيانات العميل',
        'customer.customer.delete'            => 'حذف عميل',
        'customer.customer.restore'           => 'استعادة عميل محذوف',
        'customer.customer.export'            => 'تصدير بيانات العملاء',
        'customer.customer.print'             => 'طباعة بيانات عميل',
        'customer.customer.import'            => 'استيراد بيانات العملاء',
        'customer.customer.statement'         => 'كشف حساب العميل',
        'customer.customer.balance'           => 'عرض رصيد العميل',
        'sales.collection.cross_customer_payment' => 'السداد عن عميل آخر',

        'customer.group.view'                 => 'عرض مجموعات العملاء',
        'customer.group.create'               => 'إضافة مجموعة عملاء',
        'customer.group.edit'                 => 'تعديل مجموعة عملاء',
        'customer.group.delete'               => 'حذف مجموعة عملاء',
        'customer.group.restore'              => 'استعادة مجموعة عملاء',

        'customer.class.view'                 => 'عرض فئات العملاء',
        'customer.class.create'               => 'إضافة فئة عملاء',
        'customer.class.edit'                 => 'تعديل فئة عملاء',
        'customer.class.delete'               => 'حذف فئة عملاء',

        'customer.type.view'                  => 'عرض أنواع العملاء',
        'customer.type.create'                => 'إضافة نوع عميل',
        'customer.type.edit'                  => 'تعديل نوع عميل',
        'customer.type.delete'                => 'حذف نوع عميل',

        'customer.contact.view'               => 'عرض جهات اتصال العملاء',
        'customer.contact.create'             => 'إضافة جهة اتصال',
        'customer.contact.edit'               => 'تعديل جهة اتصال',
        'customer.contact.delete'             => 'حذف جهة اتصال',

        // ════════════════════════════════════════════════════════════
        //  MODULE 2: SUPPLIERS  (30 permissions)
        // ════════════════════════════════════════════════════════════

        'purchase.supplier.view'              => 'عرض قائمة الموردين',
        'purchase.supplier.create'            => 'إضافة مورد',
        'purchase.supplier.edit'              => 'تعديل بيانات المورد',
        'purchase.supplier.delete'            => 'حذف مورد',
        'purchase.supplier.restore'           => 'استعادة مورد محذوف',
        'purchase.supplier.export'            => 'تصدير بيانات الموردين',
        'purchase.supplier.print'             => 'طباعة بيانات مورد',
        'purchase.supplier.import'            => 'استيراد بيانات الموردين',
        'purchase.supplier.statement'         => 'كشف حساب المورد',
        'purchase.supplier.balance'           => 'عرض رصيد المورد',

        'supplier.group.view'                 => 'عرض مجموعات الموردين',
        'supplier.group.create'               => 'إضافة مجموعة موردين',
        'supplier.group.edit'                 => 'تعديل مجموعة موردين',
        'supplier.group.delete'               => 'حذف مجموعة موردين',
        'supplier.group.restore'              => 'استعادة مجموعة موردين',

        'supplier.contact.view'               => 'عرض جهات اتصال الموردين',
        'supplier.contact.create'             => 'إضافة جهة اتصال مورد',
        'supplier.contact.edit'               => 'تعديل جهة اتصال مورد',
        'supplier.contact.delete'             => 'حذف جهة اتصال مورد',

        'supplier.quotation.view'             => 'عرض عروض أسعار الموردين',
        'supplier.quotation.create'           => 'إنشاء عرض سعر مورد',
        'supplier.quotation.edit'             => 'تعديل عرض سعر مورد',
        'supplier.quotation.delete'           => 'حذف عرض سعر مورد',
        'supplier.quotation.approve'          => 'اعتماد عرض سعر مورد',
        'supplier.quotation.post'             => 'ترحيل عرض سعر مورد',
        'supplier.quotation.print'            => 'طباعة عرض سعر مورد',

        'purchase.request.view'               => 'عرض طلبات الشراء',
        'purchase.request.create'             => 'إنشاء طلب شراء',
        'purchase.request.edit'               => 'تعديل طلب شراء',
        'purchase.request.delete'             => 'حذف طلب شراء',
        'purchase.request.approve'            => 'اعتماد طلب شراء',

        // ════════════════════════════════════════════════════════════
        //  MODULE 3: INVENTORY  (60 permissions)
        // ════════════════════════════════════════════════════════════

        'inventory.item.view'                 => 'عرض قائمة الأصناف',
        'inventory.item.create'               => 'إضافة صنف',
        'inventory.item.edit'                 => 'تعديل بيانات الصنف',
        'inventory.item.delete'               => 'حذف صنف',
        'inventory.item.restore'              => 'استعادة صنف محذوف',
        'inventory.item.export'               => 'تصدير بيانات الأصناف',
        'inventory.item.print'                => 'طباعة باركود الصنف',
        'inventory.item.import'               => 'استيراد الأصناف',
        'inventory.item.barcode'              => ' إدارة باركود الصنف',
        'inventory.item.price'                => 'تعديل سعر الصنف',
        'inventory.item.cost'                 => 'عرض تكلفة الصنف',

        'inventory.category.view'             => 'عرض تصنيفات الأصناف',
        'inventory.category.create'           => 'إضافة تصنيف صنف',
        'inventory.category.edit'             => 'تعديل تصنيف صنف',
        'inventory.category.delete'           => 'حذف تصنيف صنف',
        'inventory.category.restore'          => 'استعادة تصنيف صنف',

        'inventory.sub_category.view'         => 'عرض التصنيفات الفرعية',
        'inventory.sub_category.create'       => 'إضافة تصنيف فرعي',
        'inventory.sub_category.edit'         => 'تعديل تصنيف فرعي',
        'inventory.sub_category.delete'       => 'حذف تصنيف فرعي',

        'inventory.unit.view'                 => 'عرض الوحدات',
        'inventory.unit.create'               => 'إضافة وحدة',
        'inventory.unit.edit'                 => 'تعديل وحدة',
        'inventory.unit.delete'               => 'حذف وحدة',
        'inventory.unit.restore'              => 'استعادة وحدة',

        'inventory.item_unit.view'            => 'عرض وحدات الصنف',
        'inventory.item_unit.create'          => 'إضافة وحدة صنف',
        'inventory.item_unit.edit'            => 'تعديل وحدة صنف',
        'inventory.item_unit.delete'          => 'حذف وحدة صنف',

        'inventory.warehouse.view'            => 'عرض المستودعات',
        'inventory.warehouse.create'          => 'إنشاء مستودع',
        'inventory.warehouse.edit'            => 'تعديل مستودع',
        'inventory.warehouse.delete'          => 'حذف مستودع',
        'inventory.warehouse.restore'         => 'استعادة مستودع',
        'inventory.warehouse.stocks'          => 'عرض أرصدة المستودعات',
        'inventory.warehouse.transfer'        => 'تحويل بين المستودعات',

        'inventory.transaction.view'          => 'عرض حركات المخزون',
        'inventory.transaction.create'        => 'إنشاء حركة مخزون',
        'inventory.transaction.post'          => 'ترحيل حركة مخزون',
        'inventory.transaction.cancel'        => 'إلغاء حركة مخزون',

        'inventory.stock_adjustment.view'     => 'عرض جرد المخزون',
        'inventory.stock_adjustment.create'   => 'إنشاء جرد',
        'inventory.stock_adjustment.edit'     => 'تعديل جرد',
        'inventory.stock_adjustment.delete'   => 'حذف جرد',
        'inventory.stock_adjustment.post'     => 'ترحيل جرد',
        'inventory.stock_adjustment.approve'  => 'اعتماد جرد',

        'inventory.stock_count.view'          => 'عرض عد المخزون',
        'inventory.stock_count.create'        => 'إنشاء عد مخزون',
        'inventory.stock_count.edit'          => 'تعديل عد مخزون',
        'inventory.stock_count.post'          => 'ترحيل عد مخزون',

        // ════════════════════════════════════════════════════════════
        //  MODULE 4: SALES  (90 permissions)
        // ════════════════════════════════════════════════════════════

        'sales.invoice.view'                  => 'عرض فاتورة مبيعات',
        'sales.invoice.create'                => 'إنشاء فاتورة مبيعات',
        'sales.invoice.edit'                  => 'تعديل فاتورة مبيعات',
        'sales.invoice.delete'                => 'حذف فاتورة مبيعات',
        'sales.invoice.restore'               => 'استعادة فاتورة مبيعات',
        'sales.invoice.approve'               => 'اعتماد فاتورة مبيعات',
        'sales.invoice.post'                  => 'ترحيل فاتورة مبيعات',
        'sales.invoice.cancel'                => 'إلغاء فاتورة مبيعات',
        'sales.invoice.reopen'                => 'إعادة فتح فاتورة مبيعات',
        'sales.invoice.print'                 => 'طباعة فاتورة مبيعات',
        'sales.invoice.export'                => 'تصدير فاتورة مبيعات',
        'sales.invoice.discount'              => 'تطبيق خصم على فاتورة مبيعات',
        'sales.invoice.change_price'          => 'تغيير سعر فاتورة مبيعات',
        'sales.invoice.change_tax'            => 'تغيير ضريبة فاتورة مبيعات',
        'sales.invoice.change_date'           => 'تاريخ فاتورة مبيعات',
        'sales.invoice.view_profit'           => 'عرض أرباح فاتورة مبيعات',
        'sales.invoice.import'                => 'استيراد فواتير مبيعات',
        'sales.invoice.reject'                => 'رفض فاتورة مبيعات',

        'sales.invoice_item.view'             => 'عرض أصناف فاتورة المبيعات',
        'sales.invoice_item.create'           => 'إضافة صنف فاتورة مبيعات',
        'sales.invoice_item.edit'             => 'تعديل صنف فاتورة مبيعات',
        'sales.invoice_item.delete'           => 'حذف صنف فاتورة مبيعات',

        'sales.return.view'                   => 'عرض مرتجعات المبيعات',
        'sales.return.create'                 => 'إنشاء مرتجع مبيعات',
        'sales.return.edit'                   => 'تعديل مرتجع مبيعات',
        'sales.return.delete'                 => 'حذف مرتجع مبيعات',
        'sales.return.post'                   => 'ترحيل مرتجع مبيعات',
        'sales.return.approve'                => 'اعتماد مرتجع مبيعات',
        'sales.return.print'                  => 'طباعة مرتجع مبيعات',

        'sales.order.view'                    => 'عرض أوامر المبيعات',
        'sales.order.create'                  => 'إنشاء أمر مبيعات',
        'sales.order.edit'                    => 'تعديل أمر مبيعات',
        'sales.order.delete'                  => 'حذف أمر مبيعات',
        'sales.order.approve'                 => 'اعتماد أمر مبيعات',
        'sales.order.print'                   => 'طباعة أمر مبيعات',

        'sales.quotation.view'                => 'عرض عروض أسعار المبيعات',
        'sales.quotation.create'              => 'إنشاء عرض سعر مبيعات',
        'sales.quotation.edit'                => 'تعديل عرض سعر مبيعات',
        'sales.quotation.delete'              => 'حذف عرض سعر مبيعات',
        'sales.quotation.approve'             => 'اعتماد عرض سعر مبيعات',
        'sales.quotation.print'               => 'طباعة عرض سعر مبيعات',
        'sales.quotation.convert'             => 'تحويل عرض سعر لفاتورة',

        'sales.delivery_note.view'            => 'عرض أوراق التسليم',
        'sales.delivery_note.create'          => 'إنشاء ورقة تسليم',
        'sales.delivery_note.edit'            => 'تعديل ورقة تسليم',
        'sales.delivery_note.delete'          => 'حذف ورقة تسليم',
        'sales.delivery_note.post'            => 'ترحيل ورقة تسليم',
        'sales.delivery_note.print'           => 'طباعة ورقة تسليم',

        'sales.target.view'                   => 'عرض أهداف المبيعات',
        'sales.target.create'                 => 'إنشاء هدف مبيعات',
        'sales.target.edit'                   => 'تعديل هدف مبيعات',
        'sales.target.delete'                 => 'حذف هدف مبيعات',

        'sales.route.view'                    => 'عرض مسارات المبيعات',
        'sales.route.create'                  => 'إنشاء مسار مبيعات',
        'sales.route.edit'                    => 'تعديل مسار مبيعات',
        'sales.route.delete'                  => 'حذف مسار مبيعات',

        'sales.visit.view'                    => 'عرض زبائن المبيعات',
        'sales.visit.create'                  => 'إنشاء زيارة مبيعات',
        'sales.visit.edit'                    => 'تعديل زيارة مبيعات',
        'sales.visit.delete'                  => 'حذف زيارة مبيعات',

        // ════════════════════════════════════════════════════════════
        //  MODULE 5: PURCHASES  (90 permissions)
        // ════════════════════════════════════════════════════════════

        'purchase.invoice.view'               => 'عرض فاتورة مشتريات',
        'purchase.invoice.create'             => 'إنشاء فاتورة مشتريات',
        'purchase.invoice.edit'               => 'تعديل فاتورة مشتريات',
        'purchase.invoice.delete'             => 'حذف فاتورة مشتريات',
        'purchase.invoice.restore'            => 'استعادة فاتورة مشتريات',
        'purchase.invoice.approve'            => 'اعتماد فاتورة مشتريات',
        'purchase.invoice.post'               => 'ترحيل فاتورة مشتريات',
        'purchase.invoice.cancel'             => 'إلغاء فاتورة مشتريات',
        'purchase.invoice.reopen'             => 'إعادة فتح فاتورة مشتريات',
        'purchase.invoice.print'              => 'طباعة فاتورة مشتريات',
        'purchase.invoice.export'             => 'تصدير فاتورة مشتريات',
        'purchase.invoice.change_price'       => 'تغيير سعر فاتورة مشتريات',
        'purchase.invoice.change_date'        => 'تغيير تاريخ فاتورة مشتريات',
        'purchase.invoice.import'             => 'استيراد فواتير مشتريات',
        'purchase.invoice.reject'             => 'رفض فاتورة مشتريات',

        'purchase.invoice_item.view'          => 'عرض أصناف فاتورة المشتريات',
        'purchase.invoice_item.create'        => 'إضافة صنف فاتورة مشتريات',
        'purchase.invoice_item.edit'          => 'تعديل صنف فاتورة مشتريات',
        'purchase.invoice_item.delete'        => 'حذف صنف فاتورة مشتريات',

        'purchase.order.view'                 => 'عرض أوامر الشراء',
        'purchase.order.create'               => 'إنشاء أمر شراء',
        'purchase.order.edit'                 => 'تعديل أمر شراء',
        'purchase.order.delete'               => 'حذف أمر شراء',
        'purchase.order.approve'              => 'اعتماد أمر شراء',
        'purchase.order.print'                => 'طباعة أمر شراء',
        'purchase.order.export'               => 'تصدير أمر شراء',

        'purchase.receipt.view'               => 'عرض إيصالات الاستلام',
        'purchase.receipt.create'             => 'إنشاء إيصال استلام',
        'purchase.receipt.edit'               => 'تعديل إيصال استلام',
        'purchase.receipt.delete'             => 'حذف إيصال استلام',
        'purchase.receipt.post'               => 'ترحيل إيصال استلام',
        'purchase.receipt.approve'            => 'اعتماد إيصال استلام',
        'purchase.receipt.print'              => 'طباعة إيصال استلام',

        'purchase.return.view'                => 'عرض مرتجعات المشتريات',
        'purchase.return.create'              => 'إنشاء مرتجع مشتريات',
        'purchase.return.edit'                => 'تعديل مرتجع مشتريات',
        'purchase.return.delete'              => 'حذف مرتجع مشتريات',
        'purchase.return.post'                => 'ترحيل مرتجع مشتريات',
        'purchase.return.approve'             => 'اعتماد مرتجع مشتريات',
        'purchase.return.print'               => 'طباعة مرتجع مشتريات',

        'purchase.expense.view'               => 'عرض مصروفات الشراء',
        'purchase.expense.create'             => 'إنشاء مصروف شراء',
        'purchase.expense.edit'               => 'تعديل مصروف شراء',
        'purchase.expense.delete'             => 'حذف مصروف شراء',

        'purchase.request.view'               => 'عرض طلبات الشراء',
        'purchase.request.create'             => 'إنشاء طلب شراء',
        'purchase.request.edit'               => 'تعديل طلب شراء',
        'purchase.request.delete'             => 'حذف طلب شراء',
        'purchase.request.approve'            => 'اعتماد طلب شراء',

        // ════════════════════════════════════════════════════════════
        //  MODULE 6: TREASURY  (100 permissions)
        // ════════════════════════════════════════════════════════════

        'treasury.treasury.view'              => 'عرض الخزائن',
        'treasury.treasury.create'            => 'إنشاء خزينة',
        'treasury.treasury.edit'              => 'تعديل خزينة',
        'treasury.treasury.delete'            => 'حذف خزينة',
        'treasury.treasury.restore'           => 'استعادة خزينة',
        'treasury.treasury.movements'         => 'عرض حركات الخزينة',
        'treasury.treasury.statement'         => 'كشف حساب الخزينة',
        'treasury.treasury.closing'           => 'إقفال الخزينة اليومي',
        'treasury.treasury.balance'           => 'عرض رصيد الخزينة',

        'treasury.payment.view'               => 'عرض سندات الصرف',
        'treasury.payment.create'             => 'إنشاء سند صرف',
        'treasury.payment.edit'               => 'تعديل سند صرف',
        'treasury.payment.delete'             => 'حذف سند صرف',
        'treasury.payment.restore'            => 'استعادة سند صرف',
        'treasury.payment.approve'            => 'اعتماد سند صرف',
        'treasury.payment.post'               => 'ترحيل سند صرف',
        'treasury.payment.cancel'             => 'إلغاء سند صرف',
        'treasury.payment.reopen'             => 'إعادة فتح سند صرف',
        'treasury.payment.print'              => 'طباعة سند صرف',
        'treasury.payment.export'             => 'تصدير سندات الصرف',

        'treasury.receipt.view'               => 'عرض سندات القبض',
        'treasury.receipt.create'             => 'إنشاء سند قبض',
        'treasury.receipt.edit'               => 'تعديل سند قبض',
        'treasury.receipt.delete'             => 'حذف سند قبض',
        'treasury.receipt.restore'            => 'استعادة سند قبض',
        'treasury.receipt.approve'            => 'اعتماد سند قبض',
        'treasury.receipt.post'               => 'ترحيل سند قبض',
        'treasury.receipt.cancel'             => 'إلغاء سند قبض',
        'treasury.receipt.reopen'             => 'إعادة فتح سند قبض',
        'treasury.receipt.print'              => 'طباعة سند قبض',
        'treasury.receipt.export'             => 'تصدير سندات القبض',

        'treasury.transaction.view'           => 'عرض حركات الخزينة',
        'treasury.transaction.create'         => 'إنشاء حركة خزينة',
        'treasury.transaction.delete'         => 'حذف حركة خزينة',

        'treasury.transfer.view'              => 'عرض تحويلات الخزائن',
        'treasury.transfer.create'            => 'إنشاء تحويل خزينة',
        'treasury.transfer.approve'           => 'اعتماد تحويل خزينة',
        'treasury.transfer.post'              => 'ترحيل تحويل خزينة',

        'treasury.daily_closing.view'         => 'عرض إقفال الخزينة اليومي',
        'treasury.daily_closing.create'       => 'إنشاء إقفال يومي',
        'treasury.daily_closing.approve'      => 'اعتماد إقفال يومي',

        'treasury.shift.view'                 => 'عرض ورديات الخزينة',
        'treasury.shift.create'               => 'إنشاء وردية',
        'treasury.shift.close'                => 'إقفال وردية',
        'treasury.shift.print'                => 'طباعة وردية',

        'treasury.count.view'                 => 'عرض عد الخزينة',
        'treasury.count.create'               => 'إنشاء عد خزينة',
        'treasury.count.approve'              => 'اعتماد عد خزينة',

        'treasury.custody.view'               => 'عرض عهد الخزينة',
        'treasury.custody.create'             => 'إنشاء عهدة',
        'treasury.custody.return'             => 'إعادة عهدة',
        'treasury.custody.delete'             => 'حذف عهدة',

        // ════════════════════════════════════════════════════════════
        //  MODULE 7: ACCOUNTING  (80 permissions)
        // ════════════════════════════════════════════════════════════

        'accounting.account.view'             => 'عرض شجرة الحسابات',
        'accounting.account.create'           => 'إنشاء حساب',
        'accounting.account.edit'             => 'تعديل حساب',
        'accounting.account.delete'           => 'حذف حساب',
        'accounting.account.restore'          => 'استعادة حساب',
        'accounting.account.statement'        => 'كشف حساب',
        'accounting.account.balance'          => 'عرض رصيد الحساب',

        'accounting.account_type.view'        => 'عرض أنواع الحسابات',
        'accounting.account_type.create'      => 'إضافة نوع حساب',
        'accounting.account_type.edit'        => 'تعديل نوع حساب',
        'accounting.account_type.delete'      => 'حذف نوع حساب',

        'accounting.journal.view'             => 'عرض قيود اليومية',
        'accounting.journal.create'           => 'إنشاء قيد يومية',
        'accounting.journal.edit'             => 'تعديل قيد يومية',
        'accounting.journal.delete'           => 'حذف قيد يومية',
        'accounting.journal.approve'          => 'اعتماد قيد يومية',
        'accounting.journal.post'             => 'ترحيل قيد يومية',
        'accounting.journal.cancel'           => 'إلغاء قيد يومية',
        'accounting.journal.reopen'           => 'إعادة فتح قيد يومية',
        'accounting.journal.print'            => 'طباعة قيد يومية',
        'accounting.journal.export'           => 'تصدير قيود اليومية',
        'accounting.journal.import'           => 'استيراد قيود يومية',

        'accounting.opening.view'             => 'عرض الأرصدة الافتتاحية',
        'accounting.opening.create'           => 'إنشاء رصيد افتتاحي',
        'accounting.opening.edit'             => 'تعديل رصيد افتتاحي',
        'accounting.opening.delete'           => 'حذف رصيد افتتاحي',
        'accounting.opening.approve'          => 'اعتماد رصيد افتتاحي',
        'accounting.opening.post'             => 'ترحيل رصيد افتتاحي',
        'accounting.opening.cancel'           => 'إلغاء رصيد افتتاحي',

        'accounting.period.view'              => 'عرض الفترات المحاسبية',
        'accounting.period.create'            => 'إنشاء فترة محاسبية',
        'accounting.period.edit'              => 'تعديل فترة محاسبية',
        'accounting.period.close'             => 'إقفال فترة محاسبية',

        'accounting.fiscal_year.view'         => 'عرض السنوات المالية',
        'accounting.fiscal_year.create'       => 'إنشاء سنة مالية',
        'accounting.fiscal_year.edit'         => 'تعديل سنة مالية',
        'accounting.fiscal_year.close'        => 'إقفال سنة مالية',

        'accounting.cost_center.view'         => 'عرض مراكز التكلفة',
        'accounting.cost_center.create'       => 'إنشاء مركز تكلفة',
        'accounting.cost_center.edit'         => 'تعديل مركز تكلفة',
        'accounting.cost_center.delete'       => 'حذف مركز تكلفة',

        'accounting.budget.view'              => 'عرض الميزانيات',
        'accounting.budget.create'            => 'إنشاء ميزانية',
        'accounting.budget.edit'              => 'تعديل ميزانية',
        'accounting.budget.approve'           => 'اعتماد ميزانية',

        // ════════════════════════════════════════════════════════════
        //  MODULE 8: CRM  (45 permissions)
        // ════════════════════════════════════════════════════════════

        'crm.lead.view'                       => 'عرض العملاء المحتملين',
        'crm.lead.create'                     => 'إضافة عميل محتمل',
        'crm.lead.edit'                       => 'تعديل عميل محتمل',
        'crm.lead.delete'                     => 'حذف عميل محتمل',
        'crm.lead.restore'                    => 'استعادة عميل محتمل',
        'crm.lead.convert'                    => 'تحويل عميل محتمل لعميل',
        'crm.lead.export'                     => 'تصدير العملاء المحتملين',

        'crm.lead_activity.view'              => 'عرض أنشطة العملاء المحتملين',
        'crm.lead_activity.create'            => 'إضافة نشاط عميل محتمل',
        'crm.lead_activity.edit'              => 'تعديل نشاط عميل محتمل',
        'crm.lead_activity.delete'            => 'حذف نشاط عميل محتمل',

        'crm.opportunity.view'                => 'عرض الفرص',
        'crm.opportunity.create'              => 'إنشاء فرصة',
        'crm.opportunity.edit'                => 'تعديل فرصة',
        'crm.opportunity.delete'              => 'حذف فرصة',
        'crm.opportunity.stage'               => 'تغيير مرحلة الفرصة',
        'crm.opportunity.export'              => 'تصدير الفرص',

        'crm.opportunity_stage.view'          => 'عرض مراحل الفرص',
        'crm.opportunity_stage.create'        => 'إضافة مرحلة فرصة',
        'crm.opportunity_stage.edit'          => 'تعديل مرحلة فرصة',
        'crm.opportunity_stage.delete'        => 'حذف مرحلة فرصة',

        'crm.competitor.view'                 => 'عرض المنافسين',
        'crm.competitor.create'               => 'إضافة منافس',
        'crm.competitor.edit'                 => 'تعديل منافس',
        'crm.competitor.delete'               => 'حذف منافس',

        'crm.competitor_product.view'         => 'عرض منتجات المنافسين',
        'crm.competitor_product.create'       => 'إضافة منتج منافس',
        'crm.competitor_product.edit'         => 'تعديل منتج منافس',
        'crm.competitor_product.delete'       => 'حذف منتج منافس',

        'crm.price_survey.view'               => 'عرض مسوحات الأسعار',
        'crm.price_survey.create'             => 'إنشاء مسح سعر',
        'crm.price_survey.edit'               => 'تعديل مسح سعر',
        'crm.price_survey.delete'             => 'حذف مسح سعر',
        'crm.price_survey.post'               => 'ترحيل مسح سعر',

        // ════════════════════════════════════════════════════════════
        //  MODULE 9: HR  (120 permissions)
        // ════════════════════════════════════════════════════════════

        'hr.employee.view'                    => 'عرض الموظفين',
        'hr.employee.create'                  => 'إضافة موظف',
        'hr.employee.edit'                    => 'تعديل بيانات موظف',
        'hr.employee.delete'                  => 'حذف موظف',
        'hr.employee.restore'                 => 'استعادة موظف',
        'hr.employee.export'                  => 'تصدير بيانات الموظفين',
        'hr.employee.print'                   => 'طباعة بيانات موظف',
        'hr.employee.import'                  => 'استيراد بيانات الموظفين',
        'hr.employee.assign'                  => 'تعيين موظف',

        'hr.department.view'                  => 'عرض الأقسام',
        'hr.department.create'                => 'إضافة قسم',
        'hr.department.edit'                  => 'تعديل قسم',
        'hr.department.delete'                => 'حذف قسم',

        'hr.job_position.view'                => 'عرض الوظائف',
        'hr.job_position.create'              => 'إضافة وظيفة',
        'hr.job_position.edit'                => 'تعديل وظيفة',
        'hr.job_position.delete'              => 'حذف وظيفة',

        'hr.contract.view'                    => 'عرض عقود العمل',
        'hr.contract.create'                  => 'إنشاء عقد عمل',
        'hr.contract.edit'                    => 'تعديل عقد عمل',
        'hr.contract.delete'                  => 'حذف عقد عمل',
        'hr.contract.approve'                 => 'اعتماد عقد عمل',
        'hr.contract.print'                   => 'طباعة عقد عمل',

        'hr.leave.view'                       => 'عرض الإجازات',
        'hr.leave.create'                     => 'طلب إجازة',
        'hr.leave.edit'                       => 'تعديل طلب إجازة',
        'hr.leave.approve'                    => 'اعتماد إجازة',
        'hr.leave.reject'                     => 'رفض إجازة',
        'hr.leave.print'                      => 'طباعة إجازة',

        'hr.attendance.view'                  => 'عرض الحضور والغياب',
        'hr.attendance.create'                => 'تسجيل حضور',
        'hr.attendance.edit'                  => 'تعديل حضور',
        'hr.attendance.approve'               => 'اعتماد حضور',
        'hr.attendance.export'                => 'تصدير الحضور',

        'hr.payroll.view'                     => 'عرض الرواتب',
        'hr.payroll.create'                   => 'إنشاء كشف رواتب',
        'hr.payroll.edit'                     => 'تعديل كشف رواتب',
        'hr.payroll.approve'                  => 'اعتماد كشف رواتب',
        'hr.payroll.post'                     => 'ترحيل كشف رواتب',
        'hr.payroll.print'                    => 'طباعة كشف رواتب',
        'hr.payroll.export'                   => 'تصدير الرواتب',

        'hr.salary_component.view'            => 'عرض مكونات الراتب',
        'hr.salary_component.create'          => 'إضافة مكون راتب',
        'hr.salary_component.edit'            => 'تعديل مكون راتب',
        'hr.salary_component.delete'          => 'حذف مكون راتب',

        'hr.loan.view'                        => 'عرض السلف',
        'hr.loan.create'                      => 'إنشاء سلفة',
        'hr.loan.edit'                        => 'تعديل سلفة',
        'hr.loan.approve'                     => 'اعتماد سلفة',
        'hr.loan.delete'                      => 'حذف سلفة',

        'hr.advance.view'                     => 'عرض العُهد',
        'hr.advance.create'                   => 'إنشاء عهدة',
        'hr.advance.edit'                     => 'تعديل عهدة',
        'hr.advance.approve'                  => 'اعتماد عهدة',
        'hr.advance.return'                   => 'إعادة عهدة',

        'hr.penalty.view'                     => 'عرض الجزاءات',
        'hr.penalty.create'                   => 'إضافة جزاء',
        'hr.penalty.edit'                     => 'تعديل جزاء',
        'hr.penalty.approve'                  => 'اعتماد جزاء',
        'hr.penalty.delete'                   => 'حذف جزاء',

        'hr.reward.view'                      => 'عرض المكافآت',
        'hr.reward.create'                    => 'إضافة مكافأة',
        'hr.reward.edit'                      => 'تعديل مكافأة',
        'hr.reward.approve'                   => 'اعتماد مكافأة',
        'hr.reward.delete'                    => 'حذف مكافأة',

        'hr.mission.view'                     => 'عرض المهام',
        'hr.mission.create'                   => 'إنشاء مهمة',
        'hr.mission.edit'                     => 'تعديل مهمة',
        'hr.mission.approve'                  => 'اعتماد مهمة',

        'hr.shift.view'                       => 'عرض الورديات',
        'hr.shift.create'                     => 'إنشاء وردية',
        'hr.shift.edit'                       => 'تعديل وردية',
        'hr.shift.delete'                     => 'حذف وردية',

        // ════════════════════════════════════════════════════════════
        //  MODULE 10: ASSETS  (30 permissions)
        // ════════════════════════════════════════════════════════════

        'asset.category.view'                 => 'عرض تصنيفات الأصول',
        'asset.category.create'               => 'إضافة تصنيف أصول',
        'asset.category.edit'                 => 'تعديل تصنيف أصول',
        'asset.category.delete'               => 'حذف تصنيف أصول',

        'asset.asset.view'                    => 'عرض الأصول',
        'asset.asset.create'                  => 'إضافة أصل',
        'asset.asset.edit'                    => 'تعديل أصل',
        'asset.asset.delete'                  => 'حذف أصل',
        'asset.asset.restore'                 => 'استعادة أصل',
        'asset.asset.export'                  => 'تصدير الأصول',
        'asset.asset.print'                   => 'طباعة باركود الأصل',

        'asset.assignment.view'               => 'عرض تعيينات الأصول',
        'asset.assignment.create'             => 'إضافة تعيين أصل',
        'asset.assignment.edit'               => 'تعديل تعيين أصل',
        'asset.assignment.delete'             => 'حذف تعيين أصل',

        'asset.depreciation.view'             => 'عرض الإهلاك',
        'asset.depreciation.create'           => 'إنشاء إهلاك',
        'asset.depreciation.post'             => 'ترحيل إهلاك',
        'asset.depreciation.approve'          => 'اعتماد إهلاك',

        // ════════════════════════════════════════════════════════════
        //  MODULE 11: TAX  (40 permissions)
        // ════════════════════════════════════════════════════════════

        'tax.type.view'                       => 'عرض أنواع الضرائب',
        'tax.type.create'                     => 'إضافة نوع ضريبة',
        'tax.type.edit'                       => 'تعديل نوع ضريبة',
        'tax.type.delete'                     => 'حذف نوع ضريبة',

        'tax.rate.view'                       => 'عرض نسب الضرائب',
        'tax.rate.create'                     => 'إضافة نسبة ضريبة',
        'tax.rate.edit'                       => 'تعديل نسبة ضريبة',
        'tax.rate.delete'                     => 'حذف نسبة ضريبة',

        'tax.group.view'                      => 'عرض مجموعات الضرائب',
        'tax.group.create'                    => 'إضافة مجموعة ضرائب',
        'tax.group.edit'                      => 'تعديل مجموعة ضرائب',
        'tax.group.delete'                    => 'حذف مجموعة ضرائب',

        'tax.exemption.view'                  => 'عرض الإعفاءات الضريبية',
        'tax.exemption.create'                => 'إضافة إعفاء ضريبي',
        'tax.exemption.edit'                  => 'تعديل إعفاء ضريبي',
        'tax.exemption.delete'                => 'حذف إعفاء ضريبي',

        'tax.rule.view'                       => 'عرض قواعد الضرائب',
        'tax.rule.create'                     => 'إضافة قاعدة ضريبية',
        'tax.rule.edit'                       => 'تعديل قاعدة ضريبية',
        'tax.rule.delete'                     => 'حذف قاعدة ضريبية',

        'tax.return.view'                     => 'عرض الإقرارات الضريبية',
        'tax.return.create'                   => 'إنشاء إقرار ضريبي',
        'tax.return.edit'                     => 'تعديل إقرار ضريبي',
        'tax.return.approve'                  => 'اعتماد إقرار ضريبي',
        'tax.return.post'                     => 'ترحيل إقرار ضريبي',
        'tax.return.print'                    => 'طباعة إقرار ضريبي',
        'tax.return.export'                   => 'تصدير الإقرارات الضريبية',

        'tax.withholding.view'                => 'عرض الاقتطاعات الضريبية',
        'tax.withholding.create'              => 'إضافة اقتطاع ضريبي',
        'tax.withholding.approve'             => 'اعتماد اقتطاع ضريبي',
        'tax.withholding.print'               => 'طباعة اقتطاع ضريبي',

        // ════════════════════════════════════════════════════════════
        //  MODULE 12: VEHICLES  (60 permissions)
        // ════════════════════════════════════════════════════════════

        'vehicle.vehicle.view'                => 'عرض المركبات',
        'vehicle.vehicle.create'              => 'إضافة مركبة',
        'vehicle.vehicle.edit'                => 'تعديل مركبة',
        'vehicle.vehicle.delete'              => 'حذف مركبة',
        'vehicle.vehicle.restore'             => 'استعادة مركبة',
        'vehicle.vehicle.export'              => 'تصدير المركبات',

        'vehicle.type.view'                   => 'عرض أنواع المركبات',
        'vehicle.type.create'                 => 'إضافة نوع مركبة',
        'vehicle.type.edit'                   => 'تعديل نوع مركبة',
        'vehicle.type.delete'                 => 'حذف نوع مركبة',

        'vehicle.driver.view'                 => 'عرض السائقين',
        'vehicle.driver.create'               => 'إضافة سائق',
        'vehicle.driver.edit'                 => 'تعديل سائق',
        'vehicle.driver.delete'               => 'حذف سائق',
        'vehicle.driver.restore'              => 'استعادة سائق',

        'vehicle.assignment.view'             => 'عرض تعيينات المركبات',
        'vehicle.assignment.create'           => 'إضافة تعيين مركبة',
        'vehicle.assignment.edit'             => 'تعديل تعيين مركبة',
        'vehicle.assignment.delete'           => 'حذف تعيين مركبة',

        'vehicle.fuel.view'                   => 'عرض حركات الوقود',
        'vehicle.fuel.create'                 => 'إضافة حركة وقود',
        'vehicle.fuel.approve'                => 'اعتماد حركة وقود',

        'vehicle.maintenance.view'            => 'عرض الصيانة',
        'vehicle.maintenance.create'          => 'إضافة صيانة',
        'vehicle.maintenance.edit'            => 'تعديل صيانة',
        'vehicle.maintenance.approve'         => 'اعتماد صيانة',

        'vehicle.expense.view'                => 'عرض مصروفات المركبات',
        'vehicle.expense.create'              => 'إضافة مصروف مركبة',
        'vehicle.expense.edit'                => 'تعديل مصروف مركبة',
        'vehicle.expense.approve'             => 'اعتماد مصروف مركبة',

        'vehicle.loading.view'                => 'عرض تحميل المركبات',
        'vehicle.loading.create'              => 'إنشاء تحميل مركبة',
        'vehicle.loading.post'                => 'ترحيل تحميل مركبة',

        'vehicle.settlement.view'             => 'عرض تسوية المركبات',
        'vehicle.settlement.create'           => 'إنشاء تسوية مركبة',
        'vehicle.settlement.approve'          => 'اعتماد تسوية مركبة',

        'vehicle.daily_expense.view'          => 'عرض المصروفات اليومية',
        'vehicle.daily_expense.create'        => 'إضافة مصروف يومي',
        'vehicle.daily_expense.approve'       => 'اعتماد مصروف يومي',

        'vehicle.gps.view'                    => 'عرض تتبع GPS',
        'vehicle.gps.export'                  => 'تصدير بيانات GPS',

        // ════════════════════════════════════════════════════════════
        //  MODULE 13: MERCHANDISING  (40 permissions)
        // ════════════════════════════════════════════════════════════

        'merchandising.visit.view'            => 'عرض زيارات التصنيف',
        'merchandising.visit.create'          => 'إنشاء زيارة تصنيف',
        'merchandising.visit.edit'            => 'تعديل زيارة تصنيف',
        'merchandising.visit.post'            => 'ترحيل زيارة تصنيف',

        'merchandising.audit.view'            => 'عرض تدقيقات التصنيف',
        'merchandising.audit.create'          => 'إنشاء تدقيق تصنيف',
        'merchandising.audit.edit'            => 'تعديل تدقيق تصنيف',
        'merchandising.audit.approve'         => 'اعتماد تدقيق تصنيف',

        'merchandising.task.view'             => 'عرض مهام التصنيف',
        'merchandising.task.create'           => 'إضافة مهمة تصنيف',
        'merchandising.task.edit'             => 'تعديل مهمة تصنيف',
        'merchandising.task.assign'           => 'تعيين مهمة تصنيف',

        'merchandising.standard.view'         => 'عرض معايير التصنيف',
        'merchandising.standard.create'       => 'إضافة معيار تصنيف',
        'merchandising.standard.edit'         => 'تعديل معيار تصنيف',
        'merchandising.standard.delete'       => 'حذف معيار تصنيف',

        'merchandising.photo.view'            => 'عرض صور التصنيف',
        'merchandising.photo.create'          => 'إضافة صورة تصنيف',
        'merchandising.photo.delete'          => 'حذف صورة تصنيف',

        'merchandising.checklist.view'        => 'عرض قوائم التحقق',
        'merchandising.checklist.create'      => 'إضافة قائمة تحقق',
        'merchandising.checklist.edit'        => 'تعديل قائمة تحقق',
        'merchandising.checklist.delete'      => 'حذف قائمة تحقق',

        'merchandising.shelf.view'            => 'عرض رفوف المتاجر',
        'merchandising.shelf.survey'          => 'مسح رفوف المتاجر',
        'merchandising.shelf.export'          => 'تصدير بيانات الرفوف',

        // ════════════════════════════════════════════════════════════
        //  MODULE 14: SURVEYS  (30 permissions)
        // ════════════════════════════════════════════════════════════

        'survey.survey.view'                  => 'عرض الاستبيانات',
        'survey.survey.create'                => 'إنشاء استبيان',
        'survey.survey.edit'                  => 'تعديل استبيان',
        'survey.survey.delete'                => 'حذف استبيان',
        'survey.survey.publish'               => 'نشر استبيان',

        'survey.response.view'                => 'عرض ردود الاستبيان',
        'survey.response.create'              => 'إرسال رد استبيان',
        'survey.response.approve'             => 'اعتماد رد استبيان',
        'survey.response.export'              => 'تصدير ردود الاستبيان',

        'survey.category.view'                => 'عرض تصنيفات الاستبيانات',
        'survey.category.create'              => 'إضافة تصنيف استبيان',
        'survey.category.edit'                => 'تعديل تصنيف استبيان',
        'survey.category.delete'              => 'حذف تصنيف استبيان',

        'survey.question.view'                => 'عرض أسئلة الاستبيان',
        'survey.question.create'              => 'إضافة سؤال استبيان',
        'survey.question.edit'                => 'تعديل سؤال استبيان',
        'survey.question.delete'              => 'حذف سؤال استبيان',

        'survey.score.view'                   => 'عرض نتائج الاستبيان',
        'survey.score.export'                 => 'تصدير نتائج الاستبيان',

        // ════════════════════════════════════════════════════════════
        //  MODULE 15: DISTRIBUTION  (40 permissions)
        // ════════════════════════════════════════════════════════════

        'distribution.load_request.view'      => 'عرض طلبات التحميل',
        'distribution.load_request.create'    => 'إنشاء طلب تحميل',
        'distribution.load_request.edit'      => 'تعديل طلب تحميل',
        'distribution.load_request.approve'   => 'اعتماد طلب تحميل',
        'distribution.load_request.post'      => 'ترحيل طلب تحميل',

        'distribution.issue_order.view'       => 'عرض أوامر الإصدار',
        'distribution.issue_order.create'     => 'إنشاء أمر إصدار',
        'distribution.issue_order.edit'       => 'تعديل أمر إصدار',
        'distribution.issue_order.approve'    => 'اعتماد أمر إصدار',
        'distribution.issue_order.post'       => 'ترحيل أمر إصدار',

        'distribution.route.view'             => 'عرض مسارات التوزيع',
        'distribution.route.create'           => 'إنشاء مسار توزيع',
        'distribution.route.edit'             => 'تعديل مسار توزيع',
        'distribution.route.delete'           => 'حذف مسار توزيع',

        'distribution.schedule.view'          => 'عرض جداول التوزيع',
        'distribution.schedule.create'        => 'إنشاء جدول توزيع',
        'distribution.schedule.edit'          => 'تعديل جدول توزيع',

        'distribution.salesman.view'          => 'عرض مندوبي المبيعات',
        'distribution.salesman.assign'        => 'تعيين مندوب مبيعات',
        'distribution.salesman.settlement'    => 'تسوية مندوب مبيعات',
        'distribution.salesman.statement'     => 'كشف حساب مندوب',
        'distribution.salesman.daily_settlement.view'   => 'عرض تسويات المندوبين اليومية',
        'distribution.salesman.daily_settlement.approve' => 'اعتماد تسويات المندوبين اليومية',

        'distribution.collection.view'        => 'عرض التحصيلات',
        'distribution.collection.create'      => 'إضافة تحصيل',
        'distribution.collection.approve'     => 'اعتماد تحصيل',

        // ════════════════════════════════════════════════════════════
        //  MODULE 16: PRICING  (40 permissions)
        // ════════════════════════════════════════════════════════════

        'pricing.price_list.view'             => 'عرض قوائم الأسعار',
        'pricing.price_list.create'           => 'إنشاء قائمة أسعار',
        'pricing.price_list.edit'             => 'تعديل قائمة أسعار',
        'pricing.price_list.delete'           => 'حذف قائمة أسعار',
        'pricing.price_list.activate'         => 'تفعيل قائمة أسعار',

        'pricing.price_level.view'            => 'عرض مستويات الأسعار',
        'pricing.price_level.create'          => 'إضافة مستوى سعر',
        'pricing.price_level.edit'            => 'تعديل مستوى سعر',
        'pricing.price_level.delete'          => 'حذف مستوى سعر',

        'pricing.rule.view'                   => 'عرض قواعد التسعير',
        'pricing.rule.create'                 => 'إضافة قاعدة تسعير',
        'pricing.rule.edit'                   => 'تعديل قاعدة تسعير',
        'pricing.rule.delete'                 => 'حذف قاعدة تسعير',
        'pricing.rule.activate'               => 'تفعيل قاعدة تسعير',

        'pricing.approval.view'               => 'عرض طلبات اعتماد الأسعار',
        'pricing.approval.create'             => 'إنشاء طلب اعتماد سعر',
        'pricing.approval.approve'            => 'اعتماد سعر',
        'pricing.approval.reject'             => 'رفض سعر',

        'pricing.special_price.view'          => 'عرض الأسعار الخاصة',
        'pricing.special_price.create'        => 'إضافة سعر خاص',
        'pricing.special_price.edit'          => 'تعديل سعر خاص',
        'pricing.special_price.delete'        => 'حذف سعر خاص',
        'pricing.special_price.approve'       => 'اعتماد سعر خاص',

        'pricing.contract_price.view'         => 'عرض أسعار العقود',
        'pricing.contract_price.create'       => 'إضافة سعر عقد',
        'pricing.contract_price.edit'         => 'تعديل سعر عقد',
        'pricing.contract_price.delete'       => 'حذف سعر عقد',
        'pricing.contract_price.approve'      => 'اعتماد سعر عقد',

        // ════════════════════════════════════════════════════════════
        //  MODULE 17: MARKETING  (30 permissions)
        // ════════════════════════════════════════════════════════════

        'marketing.campaign.view'             => 'عرض الحملات التسويقية',
        'marketing.campaign.create'           => 'إنشاء حملة تسويقية',
        'marketing.campaign.edit'             => 'تعديل حملة تسويقية',
        'marketing.campaign.delete'           => 'حذف حملة تسويقية',
        'marketing.campaign.approve'          => 'اعتماد حملة تسويقية',

        'marketing.asset.view'                => 'عرض الأصول التسويقية',
        'marketing.asset.create'              => 'إضافة أصل تسويقي',
        'marketing.asset.edit'                => 'تعديل أصل تسويقي',
        'marketing.asset.delete'              => 'حذف أصل تسويقي',

        'marketing.material.view'             => 'عرض المواد التسويقية',
        'marketing.material.create'           => 'إضافة مادة تسويقية',
        'marketing.material.edit'             => 'تعديل مادة تسويقية',
        'marketing.material.delete'           => 'حذف مادة تسويقية',

        'marketing.agreement.view'            => 'عرض اتفاقيات التسويق',
        'marketing.agreement.create'          => 'إنشاء اتفاقية تسويقية',
        'marketing.agreement.edit'            => 'تعديل اتفاقية تسويقية',
        'marketing.agreement.approve'         => 'اعتماد اتفاقية تسويقية',

        // ════════════════════════════════════════════════════════════
        //  MODULE 18: NOTIFICATIONS  (20 permissions)
        // ════════════════════════════════════════════════════════════

        'notification.type.view'              => 'عرض أنواع الإشعارات',
        'notification.type.create'            => 'إضافة نوع إشعار',
        'notification.type.edit'              => 'تعديل نوع إشعار',
        'notification.type.delete'            => 'حذف نوع إشعار',

        'notification.template.view'          => 'عرض قوالب الإشعارات',
        'notification.template.create'        => 'إضافة قالب إشعار',
        'notification.template.edit'          => 'تعديل قالب إشعار',
        'notification.template.delete'        => 'حذف قالب إشعار',

        'notification.rule.view'              => 'عرض قواعد الإشعارات',
        'notification.rule.create'            => 'إضافة قاعدة إشعار',
        'notification.rule.edit'              => 'تعديل قاعدة إشعار',
        'notification.rule.delete'            => 'حذف قاعدة إشعار',
        'notification.rule.activate'          => 'تفعيل قاعدة إشعار',

        'notification.log.view'               => 'عرض سجل الإشعارات',
        'notification.log.export'             => 'تصدير سجل الإشعارات',

        'notification.queue.view'             => 'عرض طابور الإشعارات',
        'notification.queue.retry'            => 'إعادة إرسال إشعار',

        // ════════════════════════════════════════════════════════════
        //  MODULE 19: INTEGRATIONS  (20 permissions)
        // ════════════════════════════════════════════════════════════

        'integration.provider.view'           => 'عرض مزودي التكامل',
        'integration.provider.create'         => 'إضافة مزود تكامل',
        'integration.provider.edit'           => 'تعديل مزود تكامل',
        'integration.provider.delete'         => 'حذف مزود تكامل',
        'integration.provider.activate'       => 'تفعيل مزود تكامل',

        'integration.endpoint.view'           => 'عرض نقاط الوصول',
        'integration.endpoint.create'         => 'إضافة نقطة وصول',
        'integration.endpoint.edit'           => 'تعديل نقطة وصول',
        'integration.endpoint.delete'         => 'حذف نقطة وصول',

        'integration.webhook.view'            => 'عرض Webhooks',
        'integration.webhook.create'          => 'إضافة Webhook',
        'integration.webhook.edit'            => 'تعديل Webhook',
        'integration.webhook.delete'          => 'حذف Webhook',
        'integration.webhook.activate'        => 'تفعيل Webhook',

        'integration.log.view'                => 'عرض سجل التكامل',
        'integration.log.export'              => 'تصدير سجل التكامل',

        'integration.api_client.view'         => 'عرض عملاء API',
        'integration.api_client.create'       => 'إضافة عميل API',
        'integration.api_client.delete'       => 'حذف عميل API',

        // ════════════════════════════════════════════════════════════
        //  MODULE 20: E-INVOICING  (15 permissions)
        // ════════════════════════════════════════════════════════════

        'einvoice.provider.view'              => 'عرض مزودي الفوترة الإلكترونية',
        'einvoice.provider.create'            => 'إضافة مزود فوترة إلكترونية',
        'einvoice.provider.edit'              => 'تعديل مزود فوترة إلكترونية',
        'einvoice.provider.delete'            => 'حذف مزود فوترة إلكترونية',
        'einvoice.provider.activate'          => 'تفعيل مزود فوترة إلكترونية',

        'einvoice.transaction.view'           => 'عرض معاملات الفوترة الإلكترونية',
        'einvoice.transaction.send'           => 'إرسال فاتورة إلكترونية',
        'einvoice.transaction.cancel'         => 'إلغاء فاتورة إلكترونية',
        'einvoice.transaction.status'         => 'متابعة حالة الفاتورة الإلكترونية',
        'einvoice.transaction.retry'          => 'إعادة إرسال فاتورة إلكترونية',
        'einvoice.transaction.export'         => 'تصدير معاملات الفوترة الإلكترونية',
        'einvoice.transaction.print'          => 'طباعة فاتورة إلكترونية',

        'einvoice.transaction.view_all'       => 'عرض جميع معاملات الفوترة الإلكترونية',
        'einvoice.transaction.approve'        => 'اعتماد فاتورة إلكترونية',
        'einvoice.transaction.log'            => 'عرض سجل الفوترة الإلكترونية',

        // ════════════════════════════════════════════════════════════
        //  MODULE 21: SETTINGS  (60 permissions)
        // ════════════════════════════════════════════════════════════

        'settings.branch.view'                => 'عرض الفروع',
        'settings.branch.create'              => 'إضافة فرع',
        'settings.branch.edit'                => 'تعديل فرع',
        'settings.branch.delete'              => 'حذف فرع',
        'settings.branch.restore'             => 'استعادة فرع',

        'settings.user.view'                  => 'عرض المستخدمين',
        'settings.user.create'                => 'إضافة مستخدم',
        'settings.user.edit'                  => 'تعديل مستخدم',
        'settings.user.delete'                => 'حذف مستخدم',
        'settings.user.restore'               => 'استعادة مستخدم',
        'settings.user.assign_role'           => 'تعيين دور للمستخدم',
        'settings.user.reset_password'        => 'إعادة تعيين كلمة المرور',
        'settings.user.deactivate'            => 'تعطيل مستخدم',

        'settings.role.view'                  => 'عرض الأدوار',
        'settings.role.create'                => 'إنشاء دور',
        'settings.role.edit'                  => 'تعديل دور',
        'settings.role.delete'                => 'حذف دور',
        'settings.role.restore'               => 'استعادة دور',
        'settings.role.assign_permissions'    => 'تعيين صلاحيات للدور',
        'settings.role.copy'                  => 'نسخ صلاحيات دور',

        'settings.company.view'               => 'عرض إعدادات الشركة',
        'settings.company.edit'               => 'تعديل إعدادات الشركة',
        'settings.company.logo'               => 'تغيير شعار الشركة',

        'settings.currency.view'              => 'عرض العملات',
        'settings.currency.create'            => 'إضافة عملة',
        'settings.currency.edit'              => 'تعديل عملة',
        'settings.currency.delete'            => 'حذف عملة',

        'settings.payment_method.view'        => 'عرض طرق الدفع',
        'settings.payment_method.create'      => 'إضافة طريقة دفع',
        'settings.payment_method.edit'        => 'تعديل طريقة دفع',
        'settings.payment_method.delete'      => 'حذف طريقة دفع',

        'settings.country.view'               => 'عرض الدول',
        'settings.country.create'             => 'إضافة دولة',
        'settings.country.edit'               => 'تعديل دولة',
        'settings.country.delete'             => 'حذف دولة',

        'settings.governorate.view'           => 'عرض المحافظات',
        'settings.governorate.create'         => 'إضافة محافظة',
        'settings.governorate.edit'           => 'تعديل محافظة',
        'settings.governorate.delete'         => 'حذف محافظة',

        'settings.city.view'                  => 'عرض المدن',
        'settings.city.create'                => 'إضافة مدينة',
        'settings.city.edit'                  => 'تعديل مدينة',
        'settings.city.delete'                => 'حذف مدينة',

        'settings.district.view'              => 'عرض الأحياء',
        'settings.district.create'            => 'إضافة حي',
        'settings.district.edit'              => 'تعديل حي',
        'settings.district.delete'            => 'حذف حي',

        // ════════════════════════════════════════════════════════════
        //  MODULE 22: REPORTS  (30 permissions)
        // ════════════════════════════════════════════════════════════

        'reports.sales.view'                  => 'عرض تقارير المبيعات',
        'reports.sales.export'                => 'تصدير تقارير المبيعات',
        'reports.sales.print'                 => 'طباعة تقارير المبيعات',

        'reports.purchase.view'               => 'عرض تقارير المشتريات',
        'reports.purchase.export'             => 'تصدير تقارير المشتريات',
        'reports.purchase.print'              => 'طباعة تقارير المشتريات',

        'reports.inventory.view'              => 'عرض تقارير المخزون',
        'reports.inventory.export'            => 'تصدير تقارير المخزون',
        'reports.inventory.print'             => 'طباعة تقارير المخزون',

        'reports.treasury.view'               => 'عرض تقارير الخزينة',
        'reports.treasury.export'             => 'تصدير تقارير الخزينة',
        'reports.treasury.print'              => 'طباعة تقارير الخزينة',

        'reports.accounting.view'             => 'عرض تقارير المحاسبة',
        'reports.accounting.export'           => 'تصدير تقارير المحاسبة',
        'reports.accounting.print'            => 'طباعة تقارير المحاسبة',

        'reports.hr.view'                     => 'عرض تقارير الموارد البشرية',
        'reports.hr.export'                   => 'تصدير تقارير الموارد البشرية',
        'reports.hr.print'                    => 'طباعة تقارير الموارد البشرية',

        'reports.tax.view'                    => 'عرض التقارير الضريبية',
        'reports.tax.export'                  => 'تصدير التقارير الضريبية',
        'reports.tax.print'                   => 'طباعة التقارير الضريبية',

        'reports.profit.view'                 => 'عرض تقرير الأرباح',
        'reports.profit.export'               => 'تصدير تقرير الأرباح',
        'reports.profit.print'                => 'طباعة تقرير الأرباح',

        'reports.stock_balance.view'          => 'عرض تقرير رصيد المخزون',
        'reports.stock_balance.export'        => 'تصدير تقرير رصيد المخزون',
        'reports.stock_balance.print'         => 'طباعة تقرير رصيد المخزون',

        'reports.customer_statement.view'     => 'عرض كشف حساب العملاء',
        'reports.customer_statement.export'   => 'تصدير كشف حساب العملاء',
        'reports.customer_statement.print'    => 'طباعة كشف حساب العملاء',

        'reports.supplier_statement.view'     => 'عرض كشف حساب الموردين',
        'reports.supplier_statement.export'   => 'تصدير كشف حساب الموردين',
        'reports.supplier_statement.print'    => 'طباعة كشف حساب الموردين',

        // ════════════════════════════════════════════════════════════
        //  MODULE 23: DASHBOARD  (5 permissions)
        // ════════════════════════════════════════════════════════════

        'dashboard.view'                      => 'عرض لوحة التحكم',
        'dashboard.sales_kpi'                 => 'عرض مؤشرات المبيعات',
        'dashboard.purchase_kpi'              => 'عرض مؤشرات المشتريات',
        'dashboard.treasury_kpi'              => 'عرض مؤشرات الخزينة',
        'dashboard.customize'                 => 'تخصيص لوحة التحكم',

        // ════════════════════════════════════════════════════════════
        //  MODULE 24: AUDIT  (10 permissions)
        // ════════════════════════════════════════════════════════════

        'audit.log.view'                      => 'عرض سجل التدقيق',
        'audit.log.export'                    => 'تصدير سجل التدقيق',
        'audit.log.print'                     => 'طباعة سجل التدقيق',
        'audit.log.search'                    => 'بحث في سجل التدقيق',
        'audit.log.filter'                    => 'تصفية سجل التدقيق',
        'audit.log.detail'                    => 'عرض تفاصيل سجل التدقيق',
        'audit.log.restore'                   => 'استعادة سجل تدقيق',
        'audit.log.delete'                    => 'حذف سجل تدقيق',
        'audit.log.purge'                     => 'مسح سجل التدقيق',
        'audit.log.import'                    => 'استيراد سجل التدقيق',

        // ════════════════════════════════════════════════════════════
        //  MODULE 25: POS  (15 permissions)
        // ════════════════════════════════════════════════════════════

        'pos.terminal.view'                   => 'عرض أجهزة POS',
        'pos.terminal.create'                 => 'إضافة جهاز POS',
        'pos.terminal.edit'                   => 'تعديل جهاز POS',
        'pos.terminal.delete'                 => 'حذف جهاز POS',
        'pos.terminal.activate'               => 'تفعيل جهاز POS',

        'pos.session.view'                    => 'عرض جلسات POS',
        'pos.session.open'                    => 'فتح جلسة POS',
        'pos.session.close'                   => 'إقفال جلسة POS',
        'pos.session.print'                   => 'طباعة جلسة POS',

        'pos.sale.view'                       => 'عرض مبيعات POS',
        'pos.sale.create'                     => 'إنشاء مبيعات POS',
        'pos.sale.refund'                     => 'استرداد مبيعات POS',
        'pos.sale.print'                      => 'طباعة فاتورة POS',
        'pos.sale.export'                     => 'تصدير مبيعات POS',

        // ════════════════════════════════════════════════════════════
        //  MODULE 26: E-COMMERCE  (15 permissions)
        // ════════════════════════════════════════════════════════════

        'ecommerce.store.view'                => 'عرض المتجر الإلكتروني',
        'ecommerce.store.manage'              => 'إدارة المتجر الإلكتروني',
        'ecommerce.product.sync'              => 'مزامنة المنتجات',
        'ecommerce.order.view'                => 'عرض طلبات المتجر',
        'ecommerce.order.process'             => 'معالجة طلب المتجر',
        'ecommerce.order.ship'                => 'شحن طلب المتجر',
        'ecommerce.order.cancel'              => 'إلغاء طلب المتجر',
        'ecommerce.order.refund'              => 'استرداد طلب المتجر',
        'ecommerce.customer.view'             => 'عرض عملاء المتجر',
        'ecommerce.customer.sync'             => 'مزامنة عملاء المتجر',
        'ecommerce.promotion.view'            => 'عرض عروض المتجر',
        'ecommerce.promotion.create'          => 'إنشاء عرض متجر',
        'ecommerce.promotion.edit'            => 'تعديل عرض متجر',
        'ecommerce.promotion.delete'          => 'حذف عرض متجر',
        'ecommerce.settings.view'             => 'عرض إعدادات المتجر',

        // ════════════════════════════════════════════════════════════
        //  MODULE 27: LOYALTY  (15 permissions)
        // ════════════════════════════════════════════════════════════

        'loyalty.program.view'                => 'عرض برامج الولاء',
        'loyalty.program.create'              => 'إنشاء برنامج ولاء',
        'loyalty.program.edit'                => 'تعديل برنامج ولاء',
        'loyalty.program.delete'              => 'حذف برنامج ولاء',
        'loyalty.program.activate'            => 'تفعيل برنامج ولاء',

        'loyalty.member.view'                 => 'عرض أعضاء الولاء',
        'loyalty.member.create'               => 'إضافة عضو ولاء',
        'loyalty.member.edit'                 => 'تعديل عضو ولاء',
        'loyalty.member.points'               => 'عرض نقاط الولاء',

        'loyalty.reward.view'                 => 'عرض مكافآت الولاء',
        'loyalty.reward.create'               => 'إضافة مكافأة ولاء',
        'loyalty.reward.edit'                 => 'تعديل مكافأة ولاء',
        'loyalty.reward.redeem'               => 'استبدال مكافأة ولاء',
        'loyalty.reward.delete'               => 'حذف مكافأة ولاء',
        'loyalty.reward.export'               => 'تصدير بيانات الولاء',

        // ════════════════════════════════════════════════════════════
        //  MODULE 28: DELIVERY  (15 permissions)
        // ════════════════════════════════════════════════════════════

        'delivery.order.view'                 => 'عرض طلبات التوصيل',
        'delivery.order.assign'               => 'تعيين طلب توصيل',
        'delivery.order.start'                => 'بدء توصيل',
        'delivery.order.complete'             => 'إكمال توصيل',
        'delivery.order.cancel'               => 'إلغاء توصيل',
        'delivery.order.track'                => 'تتبع التوصيل',
        'delivery.order.print'                => 'طباعة طلب التوصيل',
        'delivery.order.export'               => 'تصدير طلبات التوصيل',

        'delivery.zone.view'                  => 'عرض مناطق التوصيل',
        'delivery.zone.create'                => 'إضافة منطقة توصيل',
        'delivery.zone.edit'                  => 'تعديل منطقة توصيل',
        'delivery.zone.delete'                => 'حذف منطقة توصيل',

        'delivery.driver.view'                => 'عرض سائقي التوصيل',
        'delivery.driver.assign'              => 'تعيين سائق توصيل',
        'delivery.driver.track'               => 'تتبع سائق التوصيل',

        // ════════════════════════════════════════════════════════════
        //  MODULE 29: RESTAURANT  (15 permissions)
        // ════════════════════════════════════════════════════════════

        'restaurant.menu.view'                => 'عرض قائمة الطعام',
        'restaurant.menu.create'              => 'إضافة صنف قائمة',
        'restaurant.menu.edit'                => 'تعديل صنف قائمة',
        'restaurant.menu.delete'              => 'حذف صنف قائمة',
        'restaurant.menu.category'            => 'إدارة تصنيفات القائمة',

        'restaurant.table.view'               => 'عرض الطاولات',
        'restaurant.table.create'             => 'إضافة طاولة',
        'restaurant.table.edit'               => 'تعديل طاولة',
        'restaurant.table.delete'             => 'حذف طاولة',
        'restaurant.table.status'             => 'حالة الطاولة',

        'restaurant.order.view'               => 'عرض طلبات المطعم',
        'restaurant.order.create'             => 'إنشاء طلب مطعم',
        'restaurant.order.status'             => 'تغيير حالة طلب المطعم',
        'restaurant.order.print'              => 'طباعة طلب المطعم',
        'restaurant.order.close'              => 'إقفال طلب المطعم',

        // ════════════════════════════════════════════════════════════
        //  MODULE 30: GYM / MEDICAL  (15 permissions)
        // ════════════════════════════════════════════════════════════

        'gym.member.view'                     => 'عرض أعضاء الصالة',
        'gym.member.create'                   => 'إضافة عضو صالة',
        'gym.member.edit'                     => 'تعديل عضو صالة',
        'gym.member.delete'                   => 'حذف عضو صالة',
        'gym.member.renew'                    => 'تجديد عضوية',

        'gym.membership.view'                 => 'عرض أنواع العضويات',
        'gym.membership.create'               => 'إضافة نوع عضوية',
        'gym.membership.edit'                 => 'تعديل نوع عضوية',
        'gym.membership.delete'               => 'حذف نوع عضوية',

        'gym.session.view'                    => 'عرض جلسات الصالة',
        'gym.session.check_in'                => 'تسجيل دخول',
        'gym.session.check_out'               => 'تسجيل خروج',
        'gym.session.report'                  => 'تقرير الحضور',
        'gym.session.export'                  => 'تصدير بيانات الحضور',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default permissions per role (used during seeder / first setup)
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'admin' => [
            '*',
        ],
        'branch_manager' => [
            'customer.*',
            'purchase.supplier.*',
            'inventory.item.*',
            'inventory.warehouse.*',
            'inventory.unit.*',
            'inventory.category.*',
            'inventory.transaction.*',
            'inventory.stock_adjustment.*',
            'sales.*',
            'purchase.*',
            'treasury.*',
            'accounting.*',
            'reports.*',
            'dashboard.*',
            'settings.branch.view',
        ],
        'accountant' => [
            'customer.*',
            'purchase.supplier.*',
            'inventory.item.view',
            'inventory.warehouse.view',
            'sales.*',
            'purchase.*',
            'treasury.*',
            'accounting.*',
            'reports.*',
            'dashboard.*',
        ],
        'sales_manager' => [
            'customer.*',
            'sales.*',
            'inventory.item.view',
            'reports.sales.*',
            'dashboard.*',
        ],
        'sales_rep' => [
            'customer.customer.view',
            'customer.customer.create',
            'customer.customer.edit',
            'customer.customer.statement',
            'inventory.item.view',
            'sales.invoice.view',
            'sales.invoice.create',
            'sales.invoice.edit',
            'sales.invoice.print',
            'sales.return.view',
            'sales.return.create',
            'crm.lead.view',
            'crm.lead.create',
            'crm.opportunity.view',
            'crm.opportunity.create',
        ],
        'warehouse_keeper' => [
            'inventory.item.*',
            'inventory.warehouse.*',
            'inventory.unit.*',
            'inventory.category.*',
            'inventory.transaction.*',
            'inventory.stock_adjustment.*',
            'reports.inventory.*',
            'dashboard.*',
        ],
        'cashier' => [
            'treasury.treasury.view',
            'treasury.treasury.statement',
            'treasury.payment.view',
            'treasury.payment.create',
            'treasury.receipt.view',
            'treasury.receipt.create',
            'customer.customer.view',
            'customer.customer.statement',
            'purchase.supplier.view',
            'purchase.supplier.statement',
            'dashboard.treasury_kpi',
        ],
        'distribution_manager' => [
            'distribution.*',
            'vehicle.*',
            'merchandising.*',
            'customer.customer.view',
            'sales.invoice.view',
            'reports.sales.*',
            'dashboard.*',
        ],
        'hr_manager' => [
            'hr.*',
            'reports.hr.*',
            'dashboard.*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bilingual Labels for Matrix UI
    |--------------------------------------------------------------------------
    | Module labels, resource labels, and action labels in AR + EN.
    | Used by GET /api/permissions/matrix to build the grid.
    */
    'modules' => [
        'customer'   => ['ar' => 'العملاء',           'en' => 'Customers',       'icon' => 'people',    'color' => '#4CAF50'],
        'purchase'   => ['ar' => 'المشتريات',         'en' => 'Purchases',       'icon' => 'shopping_cart', 'color' => '#FF9800'],
        'inventory'  => ['ar' => 'المخزون',           'en' => 'Inventory',       'icon' => 'inventory_2', 'color' => '#2196F3'],
        'sales'      => ['ar' => 'المبيعات',          'en' => 'Sales',           'icon' => 'point_of_sale', 'color' => '#E91E63'],
        'treasury'   => ['ar' => 'الخزينة',           'en' => 'Treasury',        'icon' => 'account_balance_wallet', 'color' => '#00BCD4'],
        'accounting' => ['ar' => 'المحاسبة',          'en' => 'Accounting',      'icon' => 'calculate', 'color' => '#795548'],
        'crm'        => ['ar' => 'إدارة العلاقات',     'en' => 'CRM',             'icon' => 'handshake', 'color' => '#9C27B0'],
        'hr'         => ['ar' => 'الموارد البشرية',    'en' => 'HR',              'icon' => 'groups',    'color' => '#607D8B'],
        'asset'      => ['ar' => 'الأصول',            'en' => 'Assets',          'icon' => 'apartment', 'color' => '#FF5722'],
        'tax'        => ['ar' => 'الضرائب',           'en' => 'Tax',             'icon' => 'receipt_long', 'color' => '#F44336'],
        'vehicle'    => ['ar' => 'المركبات',          'en' => 'Vehicles',        'icon' => 'local_shipping', 'color' => '#3F51B5'],
        'merchandising' => ['ar' => 'التصنيف',        'en' => 'Merchandising',   'icon' => 'store',     'color' => '#009688'],
        'survey'     => ['ar' => 'الاستبيانات',       'en' => 'Surveys',         'icon' => 'quiz',      'color' => '#8BC34A'],
        'distribution' => ['ar' => 'التوزيع',         'en' => 'Distribution',    'icon' => 'route',     'color' => '#CDDC39'],
        'pricing'    => ['ar' => 'التسعير',           'en' => 'Pricing',         'icon' => 'price_change', 'color' => '#FFC107'],
        'marketing'  => ['ar' => 'التسويق',           'en' => 'Marketing',       'icon' => 'campaign',  'color' => '#E040FB'],
        'notification' => ['ar' => 'الإشعارات',       'en' => 'Notifications',   'icon' => 'notifications', 'color' => '#536DFE'],
        'integration' => ['ar' => 'التكامل',          'en' => 'Integrations',    'icon' => 'sync',      'color' => '#78909C'],
        'einvoice'   => ['ar' => 'الفوترة الإلكترونية','en' => 'E-Invoicing',     'icon' => 'description', 'color' => '#1DE9B6'],
        'settings'   => ['ar' => 'الإعدادات',         'en' => 'Settings',        'icon' => 'settings',  'color' => '#9E9E9E'],
        'reports'    => ['ar' => 'التقارير',          'en' => 'Reports',         'icon' => 'assessment','color' => '#26A69A'],
        'dashboard'  => ['ar' => 'لوحة التحكم',       'en' => 'Dashboard',       'icon' => 'dashboard', 'color' => '#42A5F5'],
        'audit'      => ['ar' => 'سجل التدقيق',       'en' => 'Audit Log',       'icon' => 'history',   'color' => '#EF5350'],
        'pos'        => ['ar' => 'نقاط البيع',        'en' => 'POS',             'icon' => 'monitor',   'color' => '#AB47BC'],
        'ecommerce'  => ['ar' => 'المتجر الإلكتروني', 'en' => 'E-Commerce',      'icon' => 'shopping_bag','color' => '#26C6DA'],
        'loyalty'    => ['ar' => 'برامج الولاء',      'en' => 'Loyalty',         'icon' => 'card_membership', 'color' => '#FFA726'],
        'delivery'   => ['ar' => 'التوصيل',           'en' => 'Delivery',        'icon' => 'delivery_dining', 'color' => '#66BB6A'],
        'restaurant' => ['ar' => 'المطعم',            'en' => 'Restaurant',      'icon' => 'restaurant', 'color' => '#FF7043'],
        'gym'        => ['ar' => 'الصالة / الطبي',    'en' => 'Gym / Medical',   'icon' => 'fitness_center', 'color' => '#26A69A'],
    ],

    'resources' => [
        // Customers
        'customer.customer'    => ['ar' => 'العملاء',               'en' => 'Customers'],
        'customer.group'       => ['ar' => 'مجموعات العملاء',      'en' => 'Customer Groups'],
        'customer.class'       => ['ar' => 'فئات العملاء',         'en' => 'Customer Classes'],
        'customer.type'        => ['ar' => 'أنواع العملاء',        'en' => 'Customer Types'],
        'customer.contact'     => ['ar' => 'جهات اتصال العملاء',   'en' => 'Customer Contacts'],

        // Suppliers
        'purchase.supplier'    => ['ar' => 'الموردين',              'en' => 'Suppliers'],
        'supplier.group'       => ['ar' => 'مجموعات الموردين',     'en' => 'Supplier Groups'],
        'supplier.contact'     => ['ar' => 'جهات اتصال الموردين',  'en' => 'Supplier Contacts'],
        'supplier.quotation'   => ['ar' => 'عروض أسعار الموردين',   'en' => 'Supplier Quotations'],
        'purchase.request'     => ['ar' => 'طلبات الشراء',         'en' => 'Purchase Requests'],
        'purchase.order'       => ['ar' => 'أوامر الشراء',         'en' => 'Purchase Orders'],
        'purchase.receipt'     => ['ar' => 'إيصالات الاستلام',     'en' => 'Purchase Receipts'],
        'purchase.return'      => ['ar' => 'مرتجعات المشتريات',    'en' => 'Purchase Returns'],
        'purchase.expense'     => ['ar' => 'مصروفات الشراء',       'en' => 'Purchase Expenses'],

        // Inventory
        'inventory.item'             => ['ar' => 'الأصناف',                'en' => 'Items'],
        'inventory.category'         => ['ar' => 'التصنيفات',              'en' => 'Categories'],
        'inventory.sub_category'     => ['ar' => 'التصنيفات الفرعية',      'en' => 'Sub Categories'],
        'inventory.unit'             => ['ar' => 'الوحدات',                'en' => 'Units'],
        'inventory.item_unit'        => ['ar' => 'وحدات الصنف',            'en' => 'Item Units'],
        'inventory.warehouse'        => ['ar' => 'المستودعات',             'en' => 'Warehouses'],
        'inventory.transaction'      => ['ar' => 'حركات المخزون',           'en' => 'Inventory Transactions'],
        'inventory.stock_adjustment' => ['ar' => 'الجرد',                  'en' => 'Stock Adjustments'],
        'inventory.stock_count'      => ['ar' => 'عد المخزون',             'en' => 'Stock Counts'],

        // Sales
        'sales.invoice'        => ['ar' => 'فواتير المبيعات',       'en' => 'Sales Invoices'],
        'sales.return'         => ['ar' => 'مرتجعات المبيعات',      'en' => 'Sales Returns'],
        'sales.order'          => ['ar' => 'أوامر المبيعات',        'en' => 'Sales Orders'],
        'sales.quotation'      => ['ar' => 'عروض أسعار المبيعات',   'en' => 'Sales Quotations'],
        'sales.delivery_note'  => ['ar' => 'أوراق التسليم',         'en' => 'Delivery Notes'],
        'sales.target'         => ['ar' => 'أهداف المبيعات',        'en' => 'Sales Targets'],
        'sales.route'          => ['ar' => 'مسارات المبيعات',       'en' => 'Sales Routes'],
        'sales.visit'          => ['ar' => 'زيارات المبيعات',       'en' => 'Sales Visits'],

        // Treasury
        'treasury.treasury'         => ['ar' => 'الخزائن',           'en' => 'Treasuries'],
        'treasury.payment'          => ['ar' => 'سندات الصرف',       'en' => 'Payment Vouchers'],
        'treasury.receipt'          => ['ar' => 'سندات القبض',       'en' => 'Receipt Vouchers'],
        'treasury.transaction'      => ['ar' => 'حركات الخزينة',     'en' => 'Treasury Transactions'],
        'treasury.transfer'         => ['ar' => 'تحويلات الخزائن',   'en' => 'Treasury Transfers'],
        'treasury.daily_closing'    => ['ar' => 'إقفال الخزينة اليومي','en' => 'Daily Closing'],
        'treasury.shift'            => ['ar' => 'ورديات الخزينة',    'en' => 'Treasury Shifts'],
        'treasury.count'            => ['ar' => 'عد الخزينة',        'en' => 'Treasury Counts'],
        'treasury.custody'          => ['ar' => 'عهد الخزينة',       'en' => 'Treasury Custody'],

        // Accounting
        'accounting.account'        => ['ar' => 'شجرة الحسابات',     'en' => 'Chart of Accounts'],
        'accounting.account_type'   => ['ar' => 'أنواع الحسابات',    'en' => 'Account Types'],
        'accounting.journal'        => ['ar' => 'قيود اليومية',      'en' => 'Journal Entries'],
        'accounting.opening'        => ['ar' => 'الأرصدة الافتتاحية','en' => 'Opening Balances'],
        'accounting.period'         => ['ar' => 'الفترات المحاسبية', 'en' => 'Accounting Periods'],
        'accounting.cost_center'    => ['ar' => 'مراكز التكلفة',     'en' => 'Cost Centers'],
        'accounting.budget'         => ['ar' => 'الميزانيات',        'en' => 'Budgets'],

        // CRM
        'crm.lead'                  => ['ar' => 'العملاء المحتملين',    'en' => 'Leads'],
        'crm.lead_activity'         => ['ar' => 'أنشطة العملاء المحتملين','en' => 'Lead Activities'],
        'crm.opportunity'           => ['ar' => 'الفرص',               'en' => 'Opportunities'],
        'crm.opportunity_stage'     => ['ar' => 'مراحل الفرص',         'en' => 'Opportunity Stages'],
        'crm.competitor'            => ['ar' => 'المنافسين',           'en' => 'Competitors'],
        'crm.competitor_product'    => ['ar' => 'منتجات المنافسين',     'en' => 'Competitor Products'],
        'crm.price_survey'          => ['ar' => 'مسوحات الأسعار',       'en' => 'Price Surveys'],

        // HR
        'hr.employee'               => ['ar' => 'الموظفين',          'en' => 'Employees'],
        'hr.department'             => ['ar' => 'الأقسام',           'en' => 'Departments'],
        'hr.job_position'           => ['ar' => 'الوظائف',           'en' => 'Job Positions'],
        'hr.contract'               => ['ar' => 'عقود العمل',        'en' => 'Contracts'],
        'hr.leave'                  => ['ar' => 'الإجازات',          'en' => 'Leaves'],
        'hr.attendance'             => ['ar' => 'الحضور والغياب',    'en' => 'Attendance'],
        'hr.payroll'                => ['ar' => 'الرواتب',           'en' => 'Payroll'],
        'hr.salary_component'       => ['ar' => 'مكونات الراتب',     'en' => 'Salary Components'],
        'hr.loan'                   => ['ar' => 'السلف',             'en' => 'Loans'],
        'hr.advance'                => ['ar' => 'العُهد',            'en' => 'Advances'],
        'hr.penalty'                => ['ar' => 'الجزاءات',          'en' => 'Penalties'],
        'hr.reward'                 => ['ar' => 'المكافآت',          'en' => 'Rewards'],
        'hr.mission'                => ['ar' => 'المهام',            'en' => 'Missions'],
        'hr.shift'                  => ['ar' => 'الورديات',          'en' => 'Shifts'],

        // Assets
        'asset.category'            => ['ar' => 'تصنيفات الأصول',     'en' => 'Asset Categories'],
        'asset.asset'               => ['ar' => 'الأصول',            'en' => 'Assets'],
        'asset.assignment'          => ['ar' => 'تعيينات الأصول',     'en' => 'Asset Assignments'],
        'asset.depreciation'        => ['ar' => 'الإهلاك',           'en' => 'Depreciations'],

        // Tax
        'tax.type'                  => ['ar' => 'أنواع الضرائب',     'en' => 'Tax Types'],
        'tax.rate'                  => ['ar' => 'نسب الضرائب',       'en' => 'Tax Rates'],
        'tax.group'                 => ['ar' => 'مجموعات الضرائب',   'en' => 'Tax Groups'],
        'tax.exemption'             => ['ar' => 'الإعفاءات الضريبية','en' => 'Tax Exemptions'],
        'tax.rule'                  => ['ar' => 'قواعد الضرائب',     'en' => 'Tax Rules'],
        'tax.return'                => ['ar' => 'الإقرارات الضريبية', 'en' => 'Tax Returns'],
        'tax.withholding'           => ['ar' => 'الاقتطاعات الضريبية','en' => 'Withholding Tax'],

        // Vehicles
        'vehicle.vehicle'           => ['ar' => 'المركبات',          'en' => 'Vehicles'],
        'vehicle.type'              => ['ar' => 'أنواع المركبات',    'en' => 'Vehicle Types'],
        'vehicle.driver'            => ['ar' => 'السائقين',          'en' => 'Drivers'],
        'vehicle.assignment'        => ['ar' => 'تعيينات المركبات',   'en' => 'Vehicle Assignments'],
        'vehicle.fuel'              => ['ar' => 'حركات الوقود',       'en' => 'Fuel Transactions'],
        'vehicle.maintenance'       => ['ar' => 'الصيانة',           'en' => 'Maintenance'],
        'vehicle.expense'           => ['ar' => 'مصروفات المركبات',   'en' => 'Vehicle Expenses'],
        'vehicle.loading'           => ['ar' => 'تحميل المركبات',    'en' => 'Vehicle Loading'],
        'vehicle.settlement'        => ['ar' => 'تسوية المركبات',    'en' => 'Vehicle Settlements'],
        'vehicle.daily_expense'     => ['ar' => 'المصروفات اليومية', 'en' => 'Daily Expenses'],
        'vehicle.gps'               => ['ar' => 'تتبع GPS',          'en' => 'GPS Tracking'],

        // Merchandising
        'merchandising.visit'       => ['ar' => 'زيارات التصنيف',     'en' => 'Merchandising Visits'],
        'merchandising.audit'       => ['ar' => 'تدقيقات التصنيف',    'en' => 'Merchandising Audits'],
        'merchandising.task'        => ['ar' => 'مهام التصنيف',       'en' => 'Merchandising Tasks'],
        'merchandising.standard'    => ['ar' => 'معايير التصنيف',     'en' => 'Merchandising Standards'],
        'merchandising.photo'       => ['ar' => 'صور التصنيف',        'en' => 'Merchandising Photos'],
        'merchandising.checklist'   => ['ar' => 'قوائم التحقق',      'en' => 'Checklists'],
        'merchandising.shelf'       => ['ar' => 'رفوف المتاجر',      'en' => 'Shelf Surveys'],

        // Surveys
        'survey.survey'             => ['ar' => 'الاستبيانات',       'en' => 'Surveys'],
        'survey.category'           => ['ar' => 'تصنيفات الاستبيانات','en' => 'Survey Categories'],
        'survey.question'           => ['ar' => 'أسئلة الاستبيان',    'en' => 'Survey Questions'],
        'survey.response'           => ['ar' => 'ردود الاستبيان',     'en' => 'Survey Responses'],
        'survey.score'              => ['ar' => 'نتائج الاستبيان',    'en' => 'Survey Scores'],

        // Distribution
        'distribution.load_request' => ['ar' => 'طلبات التحميل',     'en' => 'Load Requests'],
        'distribution.issue_order'  => ['ar' => 'أوامر الإصدار',      'en' => 'Issue Orders'],
        'distribution.route'        => ['ar' => 'مسارات التوزيع',     'en' => 'Distribution Routes'],
        'distribution.schedule'     => ['ar' => 'جداول التوزيع',      'en' => 'Distribution Schedules'],
        'distribution.salesman'     => ['ar' => 'مندوبي المبيعات',    'en' => 'Salesmen'],
        'distribution.collection'   => ['ar' => 'التحصيلات',          'en' => 'Collections'],

        // Pricing
        'pricing.price_list'        => ['ar' => 'قوائم الأسعار',      'en' => 'Price Lists'],
        'pricing.price_level'       => ['ar' => 'مستويات الأسعار',    'en' => 'Price Levels'],
        'pricing.rule'              => ['ar' => 'قواعد التسعير',      'en' => 'Pricing Rules'],
        'pricing.approval'          => ['ar' => 'طلبات اعتماد الأسعار','en' => 'Price Approvals'],
        'pricing.special_price'     => ['ar' => 'الأسعار الخاصة',     'en' => 'Special Prices'],
        'pricing.contract_price'    => ['ar' => 'أسعار العقود',       'en' => 'Contract Prices'],

        // Marketing
        'marketing.campaign'        => ['ar' => 'الحملات التسويقية',  'en' => 'Campaigns'],
        'marketing.asset'           => ['ar' => 'الأصول التسويقية',   'en' => 'Marketing Assets'],
        'marketing.material'        => ['ar' => 'المواد التسويقية',   'en' => 'Marketing Materials'],
        'marketing.agreement'       => ['ar' => 'اتفاقيات التسويق',   'en' => 'Marketing Agreements'],

        // Notifications
        'notification.type'         => ['ar' => 'أنواع الإشعارات',    'en' => 'Notification Types'],
        'notification.template'     => ['ar' => 'قوالب الإشعارات',    'en' => 'Notification Templates'],
        'notification.rule'         => ['ar' => 'قواعد الإشعارات',    'en' => 'Notification Rules'],
        'notification.log'          => ['ar' => 'سجل الإشعارات',      'en' => 'Notification Logs'],
        'notification.queue'        => ['ar' => 'طابور الإشعارات',    'en' => 'Notification Queue'],

        // Integrations
        'integration.provider'      => ['ar' => 'مزودي التكامل',      'en' => 'Integration Providers'],
        'integration.endpoint'      => ['ar' => 'نقاط الوصول',        'en' => 'Endpoints'],
        'integration.webhook'       => ['ar' => 'Webhooks',            'en' => 'Webhooks'],
        'integration.log'           => ['ar' => 'سجل التكامل',        'en' => 'Integration Logs'],
        'integration.api_client'    => ['ar' => 'عملاء API',          'en' => 'API Clients'],

        // E-Invoicing
        'einvoice.provider'         => ['ar' => 'مزودي الفوترة الإلكترونية', 'en' => 'E-Invoice Providers'],
        'einvoice.transaction'      => ['ar' => 'معاملات الفوترة الإلكترونية','en' => 'E-Invoice Transactions'],

        // Settings
        'settings.branch'           => ['ar' => 'الفروع',            'en' => 'Branches'],
        'settings.user'             => ['ar' => 'المستخدمين',         'en' => 'Users'],
        'settings.role'             => ['ar' => 'الأدوار',            'en' => 'Roles'],
        'settings.company'          => ['ar' => 'الشركة',            'en' => 'Company'],
        'settings.currency'         => ['ar' => 'العملات',            'en' => 'Currencies'],
        'settings.payment_method'   => ['ar' => 'طرق الدفع',         'en' => 'Payment Methods'],
        'settings.country'          => ['ar' => 'الدول',             'en' => 'Countries'],
        'settings.governorate'      => ['ar' => 'المحافظات',         'en' => 'Governorates'],
        'settings.city'             => ['ar' => 'المدن',             'en' => 'Cities'],
        'settings.district'         => ['ar' => 'الأحياء',           'en' => 'Districts'],

        // Reports
        'reports.view'              => ['ar' => 'عرض التقارير',       'en' => 'View Reports'],

        // Dashboard
        'dashboard.view'            => ['ar' => 'عرض لوحة التحكم',    'en' => 'View Dashboard'],

        // Audit
        'audit.log'                 => ['ar' => 'سجل التدقيق',       'en' => 'Audit Log'],
    ],

    'actions' => [
        'view'     => ['ar' => 'عرض',       'en' => 'View',     'icon' => 'visibility'],
        'create'   => ['ar' => 'إضافة',     'en' => 'Add',      'icon' => 'add'],
        'edit'     => ['ar' => 'تعديل',     'en' => 'Edit',     'icon' => 'edit'],
        'delete'   => ['ar' => 'حذف',       'en' => 'Delete',   'icon' => 'delete'],
        'restore'  => ['ar' => 'استعادة',   'en' => 'Restore',  'icon' => 'restore'],
        'export'   => ['ar' => 'تصدير',     'en' => 'Export',   'icon' => 'download'],
        'print'    => ['ar' => 'طباعة',     'en' => 'Print',    'icon' => 'print'],
        'import'   => ['ar' => 'استيراد',   'en' => 'Import',   'icon' => 'upload'],
        'approve'  => ['ar' => 'اعتماد',    'en' => 'Approve',  'icon' => 'check_circle'],
        'post'     => ['ar' => 'ترحيل',     'en' => 'Post',     'icon' => 'send'],
        'cancel'   => ['ar' => 'إلغاء',     'en' => 'Cancel',   'icon' => 'cancel'],
        'reopen'   => ['ar' => 'إعادة فتح', 'en' => 'Reopen',   'icon' => 'lock_open'],
        'statement'=> ['ar' => 'كشف حساب',  'en' => 'Statement','icon' => 'summarize'],
        'balance'  => ['ar' => 'عرض الرصيد', 'en' => 'Balance',  'icon' => 'account_balance'],
        'movements'=> ['ar' => 'الحركات',    'en' => 'Movements','icon' => 'swap_horiz'],
    ],
];
