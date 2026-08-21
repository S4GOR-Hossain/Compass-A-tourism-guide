<?php
/**
 * Database connection (PDO / MySQL)
 * Explore Bangladesh — Smart Tourism Management System
 */

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'explore_bangladesh');
define('DB_USER', 'root');       // change for your local XAMPP/WAMP setup
define('DB_PASS', '');           // change for your local XAMPP/WAMP setup

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
