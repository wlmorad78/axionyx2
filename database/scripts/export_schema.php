<?php
$path = __DIR__ . '/../database.sqlite';
$output = __DIR__ . '/../schema/schema_export.sql';

if (!file_exists($path)) {
    fwrite(STDERR, "Database file not found: $path\n");
    exit(1);
}

$pdo = new PDO('sqlite:' . $path);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

$lines = ["-- Exported schema from $path", "-- Generated on " . date('Y-m-d H:i:s'), ""];

foreach ($tables as $table) {
    $sql = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='$table'")->fetchColumn();
    if ($sql) {
        $lines[] = $sql . ';';
        $lines[] = "";
    }
}

if (!is_dir(dirname($output))) {
    mkdir(dirname($output), 0777, true);
}

file_put_contents($output, implode("\n", $lines));

echo "Schema exported to $output\n";
	echo "Tables: " . count($tables) . "\n";
