<?php
/**
 * Database connection
 * Configured for Aiven via Environment Variables (Works on Railway & XAMPP)
 */

// On Railway, these come from the Environment Variables tab.
// On XAMPP, it falls back to the hardcoded Aiven details.
 $host = getenv('DB_HOST') ?: 'mysql-xxxxxxx.aivencloud.com'; // REPLACE with your Aiven Host
 $port = getenv('DB_PORT') ?: '12345';                         // REPLACE with your Aiven Port
 $db   = getenv('DB_NAME') ?: 'defaultdb';
 $user = getenv('DB_USER') ?: 'avnadmin';
 $pass = getenv('DB_PASS') ?: 'YOUR_AIVEN_PASSWORD';           // REPLACE with your Aiven Password

// Path to the ca.pem file
 $ssl_ca = __DIR__ . '/../ca.pem';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_SSL_CA       => $ssl_ca,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed. Error: " . $e->getMessage());
}