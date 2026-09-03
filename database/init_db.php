<?php
$path = __DIR__ . '/database.sqlite';
if (file_exists($path)) unlink($path);
$db = new PDO('sqlite:' . $path);
$db->exec('PRAGMA journal_mode = DELETE');
$db->exec('CREATE TABLE _init (id INTEGER)');
$db->exec('DROP TABLE _init');
echo 'Created: ' . filesize($path) . " bytes\n";

// Verify
$db2 = new PDO('sqlite:' . $path);
$r = $db2->query('SELECT name FROM sqlite_master WHERE type="table"')->fetchAll();
echo 'Tables: ' . count($r) . "\n";
