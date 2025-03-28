<?php
require_once '../includes/config.php';

try {
    // Create user_settings table
    $sql = "CREATE TABLE IF NOT EXISTS user_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        theme VARCHAR(10) DEFAULT 'light',
        notifications TINYINT(1) DEFAULT 1,
        language VARCHAR(2) DEFAULT 'en',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql);
    echo "User settings table created successfully\n";
    
    // Insert default settings for existing users
    $stmt = $pdo->query("SELECT id FROM users");
    $users = $stmt->fetchAll();
    
    foreach ($users as $user) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO user_settings (user_id) VALUES (?)");
        $stmt->execute([$user['id']]);
    }
    
    echo "Default settings created for existing users\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 