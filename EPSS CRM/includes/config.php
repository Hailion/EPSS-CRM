<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'credit_management');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application configuration
define('SITE_NAME', 'Credit Management System');
define('SITE_URL', '');  // Empty string for relative paths

// Email configuration
define('ADMIN_EMAIL', 'admin@creditmm.com');
define('MAIL_FROM_NAME', SITE_NAME);
define('MAIL_CHARSET', 'UTF-8');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Include language settings
require_once __DIR__ . '/languages.php';

// Function to load user settings
function loadUserSettings() {
    global $pdo;
    if (isset($_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM user_settings WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $settings = $stmt->fetch();
            
            if ($settings) {
                $_SESSION['theme'] = $settings['theme'];
                $_SESSION['language'] = $settings['language'];
                $_SESSION['notifications'] = $settings['notifications'];
            } else {
                // Create default settings if none exist
                $stmt = $pdo->prepare("INSERT INTO user_settings (user_id, theme, language, notifications) VALUES (?, 'light', 'en', 1)");
                $stmt->execute([$_SESSION['user_id']]);
                $_SESSION['theme'] = 'light';
                $_SESSION['language'] = 'en';
                $_SESSION['notifications'] = 1;
            }
        } catch (PDOException $e) {
            // Set defaults if database error occurs
            $_SESSION['theme'] = 'light';
            $_SESSION['language'] = 'en';
            $_SESSION['notifications'] = 1;
        }
    } else {
        // Set defaults for non-logged in users
        $_SESSION['theme'] = 'light';
        $_SESSION['language'] = 'en';
        $_SESSION['notifications'] = 1;
    }
}

// Function to get language HTML attributes
function getLanguageHtmlAttributes() {
    $language = $_SESSION['language'] ?? 'en';
    $dir = $language === 'am' ? 'rtl' : 'ltr';
    return 'lang="' . htmlspecialchars($language) . '" dir="' . $dir . '"';
}

// Load user settings
loadUserSettings();

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: auth/login.php");
        exit();
    }
}

// Function to get current user data
function getCurrentUser() {
    global $pdo;
    if (isLoggedIn()) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }
    return null;
}

// Function to get user settings
function getUserSettings() {
    global $pdo;
    if (isLoggedIn()) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM user_settings WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $settings = $stmt->fetch();
            
            if (!$settings) {
                // Return default settings if none exist
                return [
                    'theme' => 'light',
                    'language' => 'en',
                    'notifications' => 1
                ];
            }
            
            return $settings;
        } catch (PDOException $e) {
            // Return default settings if error occurs
            return [
                'theme' => 'light',
                'language' => 'en',
                'notifications' => 1
            ];
        }
    }
    return null;
}

// Function to update user settings
function updateUserSettings($theme, $language, $notifications) {
    global $pdo;
    if (isLoggedIn()) {
        try {
            // Check if settings exist
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_settings WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $exists = $stmt->fetchColumn();

            if ($exists) {
                $stmt = $pdo->prepare("UPDATE user_settings SET theme = ?, language = ?, notifications = ? WHERE user_id = ?");
                $stmt->execute([$theme, $language, $notifications, $_SESSION['user_id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO user_settings (user_id, theme, language, notifications) VALUES (?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $theme, $language, $notifications]);
            }
            
            // Update session variables
            $_SESSION['theme'] = $theme;
            $_SESSION['language'] = $language;
            $_SESSION['notifications'] = $notifications;
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    return false;
}

// Helper functions
function redirect($path) {
    header("Location: " . $path);
    exit;
}

// CSRF protection
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }
    return true;
} 