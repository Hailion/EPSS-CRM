<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

// Check if user has export permission
if (!hasPermission('customers.export')) {
    die('Permission denied');
}

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$zone = isset($_GET['zone']) ? $_GET['zone'] : '';
$level = isset($_GET['level']) ? $_GET['level'] : '';
$hub = isset($_GET['hub']) ? $_GET['hub'] : '';
$woreda = isset($_GET['woreda']) ? $_GET['woreda'] : '';

// Prepare base query
$query = "
    SELECT 
        c.id,
        c.name,
        c.region,
        c.hub,
        c.woreda,
        c.Zone,
        c.level,
        c.credit_invoice_number,
        c.credit_balance,
        c.status,
        c.created_at,
        c.updated_at,
        COUNT(t.id) as total_transactions,
        SUM(CASE WHEN t.status = 'paid' THEN t.amount ELSE 0 END) as total_paid,
        SUM(CASE WHEN t.status IN ('pending', 'active', 'overdue') THEN t.amount ELSE 0 END) as total_pending,
        SUM(CASE WHEN t.status = 'overdue' THEN t.amount ELSE 0 END) as total_overdue,
        COUNT(CASE WHEN t.status IN ('pending', 'active', 'overdue') THEN 1 END) as pending_transactions,
        COUNT(CASE WHEN t.status = 'overdue' THEN 1 END) as overdue_transactions,
        MAX(p.payment_date) as last_payment_date,
        MIN(CASE WHEN t.type = 'credit' AND t.amount > 0 THEN t.credit_date END) as first_credit_date
    FROM customers c
    LEFT JOIN transactions t ON c.id = t.customer_id
    LEFT JOIN payments p ON t.id = p.transaction_id
    WHERE 1=1
";

// Add filters
if (!empty($search)) {
    $query .= " AND (c.name LIKE :search OR c.id LIKE :search OR c.credit_invoice_number LIKE :search)";
}

if (!empty($status)) {
    $query .= " AND EXISTS (
        SELECT 1 FROM transactions t2 
        WHERE t2.customer_id = c.id 
        AND t2.status = :status
    )";
}

if (!empty($zone)) {
    $query .= " AND c.region = :zone";
}

if (!empty($level)) {
    $query .= " AND c.level = :level";
}

if (!empty($hub)) {
    $query .= " AND c.hub = :hub";
}

if (!empty($woreda)) {
    $query .= " AND c.woreda = :woreda";
}

$query .= " GROUP BY c.id";

// Prepare and execute query
$stmt = $pdo->prepare($query);

// Bind parameters
if (!empty($search)) {
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
}

if (!empty($status)) {
    $stmt->bindValue(':status', $status, PDO::PARAM_STR);
}

if (!empty($zone)) {
    $stmt->bindValue(':zone', $zone, PDO::PARAM_STR);
}

if (!empty($level)) {
    $stmt->bindValue(':level', $level, PDO::PARAM_STR);
}

if (!empty($hub)) {
    $stmt->bindValue(':hub', $hub, PDO::PARAM_STR);
}

if (!empty($woreda)) {
    $stmt->bindValue(':woreda', $woreda, PDO::PARAM_STR);
}

$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=health_facilities_' . date('Y-m-d') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write CSV headers
fputcsv($output, [
    'Facility ID',
    'Name',
    'Region',
    'Hub',
    'Woreda',
    'Zone',
    'Level',
    'Credit Invoice Number',
    'Credit Balance',
    'Status',
    'Created At',
    'Updated At',
    'Total Transactions',
    'Total Paid Amount',
    'Total Pending Amount',
    'Total Overdue Amount',
    'Pending Transactions',
    'Overdue Transactions',
    'Last Payment Date',
    'First Credit Date'
]);

// Write data rows
foreach ($customers as $customer) {
    fputcsv($output, [
        $customer['id'],
        $customer['name'],
        $customer['region'],
        $customer['hub'],
        $customer['woreda'],
        $customer['Zone'],
        $customer['level'],
        $customer['credit_invoice_number'],
        $customer['credit_balance'],
        $customer['status'],
        $customer['created_at'],
        $customer['updated_at'],
        $customer['total_transactions'],
        $customer['total_paid'],
        $customer['total_pending'],
        $customer['total_overdue'],
        $customer['pending_transactions'],
        $customer['overdue_transactions'],
        $customer['last_payment_date'] ?: 'N/A',
        $customer['first_credit_date'] ?: 'N/A'
    ]);
}

// Close the output stream
fclose($output);
exit; 