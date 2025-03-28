<?php
// Database configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'credit_management';

try {
    // Connect to MySQL without database
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Drop database if it exists
    $pdo->exec("DROP DATABASE IF EXISTS $dbname");
    echo "Database '$dbname' dropped successfully!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
} 