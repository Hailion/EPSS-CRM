<?php
require_once '../includes/config.php';

try {
    // Check database connection
    echo "Checking database connection...\n";
    $pdo->query("SELECT 1");
    echo "Database connection successful!\n\n";

    // Check users table
    echo "Checking users table...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "Users table exists.\n";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE users");
        echo "Table structure:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "- {$row['Field']}: {$row['Type']}\n";
        }

        // Check for admin user
        $stmt = $pdo->query("SELECT id, username, email, role FROM users WHERE email = 'admin@example.com'");
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($admin) {
            echo "\nAdmin user exists:\n";
            echo "ID: {$admin['id']}\n";
            echo "Username: {$admin['username']}\n";
            echo "Email: {$admin['email']}\n";
            echo "Role: {$admin['role']}\n";
        } else {
            echo "\nAdmin user does not exist.\n";
        }
    } else {
        echo "Users table does not exist.\n";
    }
    echo "\n";

    // Check customers table
    echo "Checking customers table...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'customers'");
    if ($stmt->rowCount() > 0) {
        echo "Customers table exists.\n";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE customers");
        echo "Table structure:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "- {$row['Field']}: {$row['Type']}\n";
        }
    } else {
        echo "Customers table does not exist.\n";
    }
    echo "\n";

    // Check transactions table
    echo "Checking transactions table...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'transactions'");
    if ($stmt->rowCount() > 0) {
        echo "Transactions table exists.\n";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE transactions");
        echo "Table structure:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "- {$row['Field']}: {$row['Type']}\n";
        }
    } else {
        echo "Transactions table does not exist.\n";
    }
    echo "\n";

    // Check payments table
    echo "Checking payments table...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'payments'");
    if ($stmt->rowCount() > 0) {
        echo "Payments table exists.\n";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE payments");
        echo "Table structure:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "- {$row['Field']}: {$row['Type']}\n";
        }
    } else {
        echo "Payments table does not exist.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
} 