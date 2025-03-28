<?php
require_once '../includes/config.php';

try {
    // Reset admin password
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        UPDATE users 
        SET password = ?, updated_at = NOW()
        WHERE email = 'admin@example.com'
    ");
    $stmt->execute([$password]);

    // Verify the password was updated
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'admin@example.com'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify('admin123', $user['password'])) {
        echo "Admin password reset successfully!\n";
        echo "Email: admin@example.com\n";
        echo "Password: admin123\n";
    } else {
        echo "Error: Failed to reset admin password.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
} 