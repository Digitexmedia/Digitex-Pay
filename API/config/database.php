<?php
/**
 * Digitex Pay – Database Connection (cPanel)
 * Using PDO (secure & recommended)
 */

// ================================
// DATABASE CONFIG (CHANGE THESE)
// ================================
$DB_HOST = "localhost";          // cPanel always localhost
$DB_NAME = "cpanel_dbname";      // e.g. digitex_pay
$DB_USER = "cpanel_dbuser";      // e.g. digitex_user
$DB_PASS = "cpanel_dbpassword";  // your database password

// ================================
// PDO CONNECTION
// ================================
try {
    $db = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {

    // NEVER expose DB errors in production
    http_response_code(500);
    echo json_encode([
        "status" => false,
        "message" => "Database connection failed"
    ]);
    exit;
}
