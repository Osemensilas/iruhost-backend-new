<?php

namespace App\Core;

use PDO;
use PDOException;
use Dotenv\Dotenv;

class DB {
    protected static PDO $pdo;

    public static function connection() {
        if (!isset(self::$pdo)) {

            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();

            $hostname = $_ENV['DB_HOST'];
            $username = $_ENV['DB_USERNAME'];
            $password = $_ENV['DB_PASSWORD'];
            $dbname   = $_ENV['DB_DATABASE'];

            try {
                // Connect without specifying database
                $pdo = new PDO("mysql:host=$hostname", $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);

                // Create database if not exists
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

                // Now connect to that database
                $pdo->exec("USE `$dbname`");

                self::$pdo = $pdo;

            } catch (PDOException $err) {
                die("DB Connection failed: " . $err->getMessage());
            }
        }

        return self::$pdo;
    }
}
