<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

// Check if user has import permission
if (!hasPermission('customers.import')) {
    die('Permission denied');
}

// Validate CSRF token
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token validation failed');
}

$template_file = __DIR__ . '/templates/health_facilities_template.csv';

// Check if file exists
if (!file_exists($template_file)) {
    die('Template file not found');
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="health_facilities_template.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Output file contents
readfile($template_file);
exit(); 