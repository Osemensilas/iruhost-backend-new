<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\DB;

// Connect to the database
$pdo = DB::connection();

// Get all migration files
$migrations = glob(__DIR__ . '/../migrations/*.php');

foreach ($migrations as $file) {
    echo "Running Migration: " . basename($file) . PHP_EOL; // Correct newline
    $migration = require $file;
    
    if (is_callable($migration)) {
        $migration($pdo); // Run the migration function
    } else {
        echo "Skipped: " . basename($file) . " (not a valid function)" . PHP_EOL;
    }
}

echo "All Migrations Completed" . PHP_EOL;
