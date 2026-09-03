-- =====================================================
-- Step 1: Create Return Order
-- =====================================================
INSERT INTO return_orders (
    company_id, warehouse_id, load_request_id, return_no, return_type,
    return_date, employee_id, status_id, total_items_count, total_quantity,
    total_amount, return_purpose, created_at, updated_at
)
VALUES (
    1,          -- companyId: adjust if needed
    1,          -- warehouseId: adjust if needed
    41,         -- load_request_id: أمر التحميل رقم 41
    'RTN-' || (SELECT COALESCE(MAX(CAST(SUBSTR(return_no, 5) AS INTEGER)), 0) + 1 FROM return_orders WHERE return_no LIKE 'RTN-%'),
    'excess',
    date('now'),
    5,          -- employee_id: المندوب 5
    'pending',
    4,          -- total items
    6930,       -- 4770 + 1690 + 0 + 470
    332387.50,  -- (4770+1690+0+470) * 47.95
    'salesman_return',
    datetime('now'),
    datetime('now')
);

-- =====================================================
-- Step 2: Create Return Order Items
-- =====================================================
-- لوامر الارتجاع رقم 42 (oro gt el ID elly et3ml):
-- SELECT MAX(id) FROM return_orders;

-- Item 1: item_id = 1
INSERT INTO return_order_items (
    return_order_id, item_id, returned_quantity, sold_quantity,
    sales_price, line_total, loaded_qty, t_in_qty, t_out_qty,
    return_condition, created_at, updated_at
)
VALUES (
    (SELECT MAX(id) FROM return_orders),
    1, 4770, 18230, 47.95, 228781.50, 23000, 0, 0, 'good',
    datetime('now'), datetime('now')
);

-- Item 2: item_id = 2
INSERT INTO return_order_items (
    return_order_id, item_id, returned_quantity, sold_quantity,
    sales_price, line_total, loaded_qty, t_in_qty, t_out_qty,
    return_condition, created_at, updated_at
)
VALUES (
    (SELECT MAX(id) FROM return_orders),
    2, 1690, 10810, 47.95, 81035.50, 12500, 0, 0, 'good',
    datetime('now'), datetime('now')
);

-- Item 3: item_id = 3
INSERT INTO return_order_items (
    return_order_id, item_id, returned_quantity, sold_quantity,
    sales_price, line_total, loaded_qty, t_in_qty, t_out_qty,
    return_condition, created_at, updated_at
)
VALUES (
    (SELECT MAX(id) FROM return_orders),
    3, 0, 1500, 47.95, 0, 1500, 0, 0, 'good',
    datetime('now'), datetime('now')
);

-- Item 4: item_id = 4
INSERT INTO return_order_items (
    return_order_id, item_id, returned_quantity, sold_quantity,
    sales_price, line_total, loaded_qty, t_in_qty, t_out_qty,
    return_condition, created_at, updated_at
)
VALUES (
    (SELECT MAX(id) FROM return_orders),
    4, 470, 2030, 47.95, 22536.50, 6025, 0, 0, 'good',
    datetime('now'), datetime('now')
);

-- =====================================================
-- Verify
-- =====================================================
SELECT * FROM return_orders ORDER BY id DESC LIMIT 1;
SELECT * FROM return_order_items WHERE return_order_id = (SELECT MAX(id) FROM return_orders);
