<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

// Check if user has import permission
if (!hasPermission('customers.import')) {
    die('Permission denied');
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token validation failed');
}

// Check if file was uploaded
if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error'] = 'Please select a valid CSV file to import.';
    header('Location: list.php');
    exit;
}

// Validate file type
$file = $_FILES['csv_file'];
$file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($file_ext !== 'csv') {
    $_SESSION['error'] = 'Only CSV files are allowed.';
    header('Location: list.php');
    exit;
}

// Open CSV file
$handle = fopen($file['tmp_name'], 'r');
if (!$handle) {
    $_SESSION['error'] = 'Error opening the CSV file.';
    header('Location: list.php');
    exit;
}

// Read headers
$headers = fgetcsv($handle);
if (!$headers) {
    fclose($handle);
    $_SESSION['error'] = 'Error reading CSV headers.';
    header('Location: list.php');
    exit;
}

// Validate required columns
$required_columns = [
    'Facility ID', 'Name', 'Region', 'Hub', 'Woreda', 'Zone', 'Level',
    'Credit Invoice Number', 'Credit Balance', 'Status', 'Credit Date', 'Due Date'
];

$missing_columns = array_diff($required_columns, $headers);
if (!empty($missing_columns)) {
    fclose($handle);
    $_SESSION['error'] = 'Missing required columns: ' . implode(', ', $missing_columns);
    header('Location: list.php');
    exit;
}

// Initialize counters
$success_count = 0;
$error_count = 0;
$errors = [];

try {
    // Start transaction
    $pdo->beginTransaction();

    // Process each row
    while (($data = fgetcsv($handle)) !== false) {
        $row_data = array_combine($headers, $data);
        
        // Skip empty rows
        if (empty($row_data['Facility ID'])) {
            continue;
        }

        // Check for duplicate facility ID
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE id = ?");
        $stmt->execute([$row_data['Facility ID']]);
        if ($stmt->fetch()) {
            if (!isset($_POST['skip_duplicates']) || $_POST['skip_duplicates'] !== 'on') {
                $error_count++;
                $errors[] = "Row {$row_data['Facility ID']}: Facility ID already exists.";
                continue;
            }
            continue;
        }

        try {
            // Insert customer record first
            $stmt = $pdo->prepare("
                INSERT INTO customers (
                    id, name, region, hub, woreda, Zone, level,
                    credit_invoice_number, credit_balance, status,
                    created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )
            ");

            $stmt->execute([
                $row_data['Facility ID'],
                $row_data['Name'],
                $row_data['Region'],
                $row_data['Hub'],
                $row_data['Woreda'],
                $row_data['Zone'],
                $row_data['Level'],
                $row_data['Credit Invoice Number'],
                $row_data['Credit Balance'],
                $row_data['Status']
            ]);

            // Now that customer exists, insert transaction record
            if (!empty($row_data['Credit Date']) && !empty($row_data['Due Date'])) {
                $stmt = $pdo->prepare("
                    INSERT INTO transactions (
                        customer_id, user_id, amount, type, status, 
                        credit_date, due_date, description, created_at, updated_at
                    ) VALUES (
                        ?, ?, ?, 'credit', 'pending', 
                        ?, ?, ?, NOW(), NOW()
                    )
                ");

                $stmt->execute([
                    $row_data['Facility ID'],
                    $_SESSION['user_id'],
                    $row_data['Credit Balance'],
                    $row_data['Credit Date'],
                    $row_data['Due Date'],
                    'Initial credit import'
                ]);
            }

            $success_count++;
        } catch (Exception $e) {
            $error_count++;
            $errors[] = "Row {$row_data['Facility ID']}: " . $e->getMessage();
            continue;
        }
    }

    // Commit transaction
    $pdo->commit();

    // Set success message
    $_SESSION['success'] = "Successfully imported {$success_count} health facilities.";
    if ($error_count > 0) {
        $_SESSION['warning'] = "{$error_count} records were skipped due to errors.";
        $_SESSION['import_errors'] = $errors;
    }

} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    $_SESSION['error'] = 'Import failed: ' . $e->getMessage();
}

// Close file handle
fclose($handle);

// Redirect back to list page
header('Location: list.php');
exit;