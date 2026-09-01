<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\DB;

$pdo = DB::connection();

// Drop all existing tables
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    $pdo->exec("DROP TABLE IF EXISTS `$table`");
    echo "Dropped table: $table\n";
}

$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

// Rerun all migrations
$migrations = glob(__DIR__ . '/../migrations/*.php');

foreach ($migrations as $file) {
    echo "Running Migration: " . basename($file) . "\n";
    $migration = require $file;
    $migration($pdo);
}

echo "Database reset and all migrations reapplied.\n";
