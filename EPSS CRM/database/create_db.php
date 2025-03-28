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

    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database '$dbname' created successfully!\n";

    // Select the database
    $pdo->exec("USE $dbname");

    // Create users table
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
    echo "Users table created successfully!\n";

    // Create customers table
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
    echo "Customers table created successfully!\n";

    // Create transactions table
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
    echo "Transactions table created successfully!\n";

    // Create payments table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            transaction_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_date DATE NOT NULL,
            payment_method VARCHAR(50),
            reference_number VARCHAR(100),
            notes TEXT,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE
        )
    ");
    echo "Payments table created successfully!\n";

    // Create password_resets table
    $sql = "CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        used TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_token (token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    try {
        $pdo->exec($sql);
        echo "Password resets table created successfully\n";
    } catch (PDOException $e) {
        echo "Error creating password resets table: " . $e->getMessage() . "\n";
    }

    // Create customer_notes table
    $sql = "CREATE TABLE IF NOT EXISTS customer_notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        content TEXT NOT NULL,
        type VARCHAR(50) DEFAULT 'general',
        created_by INT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    try {
        $pdo->exec($sql);
        echo "Customer notes table created successfully\n";
    } catch (PDOException $e) {
        echo "Error creating customer notes table: " . $e->getMessage() . "\n";
    }

    // Create admin user
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE email = 'admin@example.com'");
    $adminExists = $stmt->fetchColumn();

    if (!$adminExists) {
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

    echo "\nDatabase setup completed successfully!\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
} 