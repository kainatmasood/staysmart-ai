<?php
/**
 * Database connection configuration
 * StaySmart AI - Backend
 */

$DB_HOST = "127.0.0.1";
$DB_NAME = "staysmart_ai";
$DB_USER = "root";       // change to your MySQL username
$DB_PASS = "";           // change to your MySQL password

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed: " . $e->getMessage()]);
    exit;
}
