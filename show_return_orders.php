<?php

$host = '127.0.0.1';
$db   = 'axionyx';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

$options = getopt('', ['status:', 'from:', 'to:', 'employee:', 'limit:', 'items', 'order:']);

$status   = $options['status']   ?? null;
$from     = $options['from']     ?? null;
$to       = $options['to']       ?? null;
$employee = $options['employee'] ?? null;
$limit    = (int)($options['limit'] ?? 20);
$showItems = isset($options['items']);
$orderId  = $options['order']    ?? null;

function formatNum($n, $dec = 2) {
    return number_format((float)$n, $dec);
}

function printTable($headers, $rows) {
    $widths = array_map(fn($h) => strlen($h), $headers);
    foreach ($rows as $row) {
        foreach ($row as $i => $cell) {
            $widths[$i] = max($widths[$i] ?? 0, strlen((string)$cell));
        }
    }
    $sep = '+';
    foreach ($widths as $w) $sep .= str_repeat('-', $w + 2) . '+';
    echo $sep . "\n";
    $line = '|';
    foreach ($headers as $i => $h) $line .= ' ' . str_pad($h, $widths[$i]) . ' |';
    echo $line . "\n";
    echo $sep . "\n";
    foreach ($rows as $row) {
        $line = '|';
        foreach ($row as $i => $cell) $line .= ' ' . str_pad((string)$cell, $widths[$i]) . ' |';
        echo $line . "\n";
    }
    echo $sep . "\n";
}

function showOrderItems($pdo, $orderId) {
    $stmt = $pdo->prepare("
        SELECT
            roi.id,
            i.name AS item_name,
            u.name AS unit_name,
            roi.returned_quantity,
            roi.sold_quantity,
            roi.loaded_qty,
            roi.sales_price,
            roi.line_total,
            rr.name AS reason_name,
            roi.return_condition,
            roi.notes
        FROM return_order_items roi
        LEFT JOIN items i ON roi.item_id = i.id
        LEFT JOIN units u ON roi.item_unit_id = u.id
        LEFT JOIN return_reasons rr ON roi.return_reason_id = rr.id
        WHERE roi.return_order_id = ?
    ");
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll();

    if (empty($items)) {
        echo "  Order #{$orderId} has no items.\n";
        return;
    }

    $stmt2 = $pdo->prepare("SELECT return_no FROM return_orders WHERE id = ?");
    $stmt2->execute([$orderId]);
    $order = $stmt2->fetch();
    $orderNo = $order['return_no'] ?? "#{$orderId}";

    echo "\n  Items for {$orderNo} (" . count($items) . " items):\n";

    $rows = array_map(fn($i) => [
        $i['id'],
        $i['item_name'] ?? '-',
        $i['unit_name'] ?? '-',
        formatNum($i['returned_quantity']),
        formatNum($i['sold_quantity']),
        formatNum($i['loaded_qty']),
        formatNum($i['sales_price']),
        formatNum($i['line_total']),
        $i['reason_name'] ?? '-',
        $i['return_condition'] ?? '-',
        mb_substr($i['notes'] ?? '-', 0, 30),
    ], $items);

    printTable(['ID', 'Item', 'Unit', 'Returned', 'Sold', 'Loaded', 'Price', 'Total', 'Reason', 'Condition', 'Notes'], $rows);
}

if ($orderId) {
    $stmt = $pdo->prepare("
        SELECT ro.*, lr.request_no AS load_request_no,
               u.name AS user_name,
               CONCAT(e.first_name_ar, ' ', e.last_name_ar) AS employee_name,
               w.name AS warehouse_name,
               b.name AS branch_name
        FROM return_orders ro
        LEFT JOIN load_requests lr ON ro.load_request_id = lr.id
        LEFT JOIN users u ON ro.user_id = u.id
        LEFT JOIN employees e ON ro.employee_id = e.id
        LEFT JOIN warehouses w ON ro.warehouse_id = w.id
        LEFT JOIN branches b ON ro.branch_id = b.id
        WHERE ro.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        echo "Return order #{$orderId} not found.\n";
        exit(1);
    }

    echo "=== Return Order: {$order['return_no']} ===\n\n";

    $details = [
        ['ID', $order['id']],
        ['Return No', $order['return_no'] ?? '-'],
        ['Date', $order['return_date'] ?? '-'],
        ['Type', $order['return_type'] ?? '-'],
        ['Purpose', $order['return_purpose'] ?? '-'],
        ['Status', $order['status_id'] ?? '-'],
        ['Employee', $order['employee_name'] ?: ($order['user_name'] ?? '-')],
        ['Warehouse', $order['warehouse_name'] ?? '-'],
        ['Branch', $order['branch_name'] ?? '-'],
        ['Load Request', $order['load_request_no'] ?? '-'],
        ['Total Items', formatNum($order['total_items_count'], 0)],
        ['Total Qty', formatNum($order['total_quantity'])],
        ['Total Amount', formatNum($order['total_amount'])],
        ['Notes', $order['notes'] ?? '-'],
    ];

    printTable(['Field', 'Value'], $details);
    showOrderItems($pdo, $orderId);
    exit(0);
}

$sql = "
    SELECT
        ro.id, ro.return_no, ro.return_date, ro.return_type, ro.return_purpose,
        ro.status_id, ro.total_items_count, ro.total_quantity, ro.total_amount, ro.notes,
        lr.request_no AS load_request_no,
        u.name AS user_name,
        CONCAT(e.first_name_ar, ' ', e.last_name_ar) AS employee_name,
        w.name AS warehouse_name,
        b.name AS branch_name
    FROM return_orders ro
    LEFT JOIN load_requests lr ON ro.load_request_id = lr.id
    LEFT JOIN users u ON ro.user_id = u.id
    LEFT JOIN employees e ON ro.employee_id = e.id
    LEFT JOIN warehouses w ON ro.warehouse_id = w.id
    LEFT JOIN branches b ON ro.branch_id = b.id
    WHERE 1=1
";

$params = [];

if ($status) {
    $sql .= " AND ro.status_id = ?";
    $params[] = $status;
}
if ($from) {
    $sql .= " AND ro.return_date >= ?";
    $params[] = $from;
}
if ($to) {
    $sql .= " AND ro.return_date <= ?";
    $params[] = $to;
}
if ($employee) {
    $sql .= " AND ro.employee_id = ?";
    $params[] = $employee;
}

$sql .= " ORDER BY ro.id DESC LIMIT ?";
$params[] = $limit;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

if (empty($orders)) {
    echo "No return orders found.\n";
    exit(0);
}

$totalQty = array_sum(array_column($orders, 'total_quantity'));
$totalAmt = array_sum(array_column($orders, 'total_amount'));

echo "Return Orders (" . count($orders) . "):\n\n";

$rows = array_map(fn($o) => [
    $o['id'],
    $o['return_no'] ?? '-',
    $o['return_date'] ?? '-',
    $o['return_type'] ?? '-',
    $o['status_id'] ?? '-',
    $o['employee_name'] ?: ($o['user_name'] ?? '-'),
    $o['warehouse_name'] ?? '-',
    $o['load_request_no'] ?? '-',
    formatNum($o['total_items_count'], 0),
    formatNum($o['total_quantity']),
    formatNum($o['total_amount']),
], $orders);

printTable(['ID', 'Return No', 'Date', 'Type', 'Status', 'Employee', 'Warehouse', 'Load Req', 'Items', 'Qty', 'Amount'], $rows);

echo "\nTotal: Qty = " . formatNum($totalQty) . " | Amount = " . formatNum($totalAmt) . "\n";

if ($showItems) {
    foreach ($orders as $order) {
        showOrderItems($pdo, $order['id']);
    }
}

exit(0);
