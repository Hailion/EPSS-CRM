<?php
require_once '../includes/config.php';

try {
    // Check and create users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
            credit_balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "Users table checked/created successfully!\n";

    // Check and create customers table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            phone VARCHAR(20),
            address TEXT,
            status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "Customers table checked/created successfully!\n";

    // Check and create transactions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            credit_date DATE NOT NULL,
            due_date DATE NOT NULL,
            status ENUM('pending', 'active', 'overdue', 'paid') NOT NULL DEFAULT 'pending',
            notes TEXT,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
        )
    ");
    echo "Transactions table checked/created successfully!\n";

    // Check and create payments table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            transaction_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_date DATE NOT NULL,
            payment_method VARCHAR(50),
            notes TEXT,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE
        )
    ");
    echo "Payments table checked/created successfully!\n";

    // Check if admin user exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE email = 'admin@example.com'");
    $adminExists = $stmt->fetchColumn();

    if (!$adminExists) {
        // Create admin user
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, role)
            VALUES ('admin', 'admin@example.com', ?, 'admin')
        ");
        $stmt->execute([$password]);
        echo "Admin user created successfully!\n";
        echo "Email: admin@example.com\n";
        echo "Password: admin123\n";
    } else {
        echo "Admin user already exists.\n";
    }

    echo "\nAll tables checked/created successfully!\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
} 