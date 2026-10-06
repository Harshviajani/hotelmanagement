<?php
// ---- Database settings (XAMPP defaults) ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'hotel_db');
define('DB_USER', 'root');
define('DB_PASS', '');
// Folder name of this project inside htdocs
define('BASE_URL', '/hotel_system');

session_start();

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}

require_once __DIR__ . '/includes/functions.php';
