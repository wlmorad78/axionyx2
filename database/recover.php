<?php
$path = __DIR__ . '/database.sqlite';
$backup = __DIR__ . '/database_recovered.sqlite';

if (file_exists($backup)) unlink($backup);

echo "Recovering: $path\n";

$src = new SQLite3($path);
$dst = new SQLite3($backup);

$result = $src->query("SELECT name FROM sqlite_master WHERE type='table'");
$tables = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $tables[] = $row['name'];
}
echo "Found " . count($tables) . " tables\n";

foreach ($tables as $table) {
    $rows = 0;
    try {
        $data = $src->query("SELECT * FROM [$table]");
        if (!$data) { echo "$table: skipped (no data)\n"; continue; }

        $firstRow = $data->fetchArray(SQLITE3_ASSOC);
        if (!$firstRow) { echo "$table: empty\n"; continue; }

        $columns = array_keys($firstRow);
        $colDefs = array_map(fn($c) => "[$c]", $columns);
        $placeholders = array_fill(0, count($columns), '?');
        $insertSQL = "INSERT INTO [$table] (" . implode(', ', $colDefs) . ") VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $dst->prepare($insertSQL);

        $types = str_repeat('s', count($columns));
        $refs = [];
        $values = array_values($firstRow);
        foreach ($values as $i => &$v) { $refs[] = &$values[$i]; }
        call_user_func_array([$stmt, 'bind'], array_merge([$types], $refs));
        $stmt->execute();
        $rows++;

        while ($row = $data->fetchArray(SQLITE3_ASSOC)) {
            $vals = array_values($row);
            $refs2 = [];
            foreach ($vals as $i => &$v) { $refs2[] = &$vals[$i]; }
            call_user_func_array([$stmt, 'bind'], array_merge([$types], $refs2));
            $stmt->execute();
            $rows++;
        }
        echo "$table: $rows rows recovered\n";
    } catch (Exception $e) {
        echo "$table: ERROR - " . $e->getMessage() . "\n";
    }
}

$src->close();
$dst->close();

$recoveredSize = filesize($backup);
echo "\nRecovered DB size: $recoveredSize bytes\n";
echo "Done! Review database_recovered.sqlite\n";
