<?php
require_once '../includes/config.php';

try {
    // Check if admin user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['admin@example.com']);
    
    if (!$stmt->fetch()) {
        // Create admin user
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, role, created_at)
            VALUES (?, ?, ?, 'admin', NOW())
        ");
        
        $stmt->execute([
            'Admin User',
            'admin@example.com',
            password_hash('admin123', PASSWORD_DEFAULT)
        ]);
        
        echo "Admin user created successfully!\n";
        echo "Email: admin@example.com\n";
        echo "Password: admin123\n";
    } else {
        echo "Admin user already exists!\n";
        echo "Email: admin@example.com\n";
        echo "Password: admin123\n";
    }
} catch (PDOException $e) {
    echo "Error creating admin user: " . $e->getMessage();
} 