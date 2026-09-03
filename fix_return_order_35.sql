UPDATE return_order_items
SET returned_quantity = 1030, loaded_qty = 100000, line_total = 1030 * sales_price, updated_at = NOW()
WHERE id = 108;

UPDATE return_order_items
SET returned_quantity = 540, loaded_qty = 50000, line_total = 540 * sales_price, updated_at = NOW()
WHERE id = 109;

UPDATE return_order_items
SET returned_quantity = 160, loaded_qty = 10000, line_total = 160 * sales_price, updated_at = NOW()
WHERE id = 110;

UPDATE return_order_items
SET returned_quantity = 250, loaded_qty = 2000, line_total = 250 * sales_price, updated_at = NOW()
WHERE id = 111;
