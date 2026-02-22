<?php
// ============================================================
// Database Configuration
// ============================================================
// For local (XAMPP): DB_HOST = 'localhost', DB_USER = 'root', DB_PASS = ''
// For cPanel: use the DB credentials from your cPanel → MySQL Databases
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'sericsof_portfolio');  // cPanel database name
define('DB_USER', 'sericsof_gideon');    // cPanel database user
define('DB_PASS', 'YOUR_DB_PASSWORD');   // ← Replace with your DB password
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
            exit;
        }
    }
    return $pdo;
}
