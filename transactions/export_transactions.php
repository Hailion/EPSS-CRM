<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="transactions_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, [
    'Transaction ID',
    'Customer Name',
    'Region',
    'Hub',
    'Woreda',
    'Zone',
    'Level',
    'Total Amount',
    'Amount Paid',
    'Remaining Amount',
    'Status',
    'Created Date',
    'Due Date',
    'Days Overdue',
    'Payment Count'
]);

// Build query based on filters
$query = "
    SELECT 
        t.*,
        c.name as customer_name,
        c.region,
        c.hub,
        c.woreda,
        c.Zone,
        c.level,
        COALESCE(t.amount, 0) as total_amount,
        COALESCE(p.total_paid, 0) as amount_paid,
        COALESCE(t.amount - COALESCE(p.total_paid, 0), t.amount) as remaining_amount,
        DATEDIFF(CURRENT_DATE, t.due_date) as days_overdue,
        (SELECT COUNT(*) FROM payments WHERE transaction_id = t.id) as payment_count
    FROM transactions t
    JOIN customers c ON t.customer_id = c.id
    LEFT JOIN (
        SELECT transaction_id, SUM(amount) as total_paid
        FROM payments
        GROUP BY transaction_id
    ) p ON t.id = p.transaction_id
    WHERE 1=1
";

$params = [];

// Apply filters
if (!empty($_POST['search'])) {
    $query .= " AND (c.name LIKE ? OR t.id LIKE ?)";
    $search = "%{$_POST['search']}%";
    $params[] = $search;
    $params[] = $search;
}

if (!empty($_POST['status'])) {
    switch ($_POST['status']) {
        case 'paid':
            $query .= " AND t.amount = COALESCE(p.total_paid, 0)";
            break;
        case 'partial':
            $query .= " AND t.amount > COALESCE(p.total_paid, 0) AND COALESCE(p.total_paid, 0) > 0";
            break;
        case 'overdue':
            $query .= " AND DATEDIFF(CURRENT_DATE, t.due_date) > 0 AND t.amount > COALESCE(p.total_paid, 0)";
            break;
        case 'pending':
            $query .= " AND COALESCE(p.total_paid, 0) = 0";
            break;
    }
}

if (!empty($_POST['minAmount'])) {
    $query .= " AND t.amount >= ?";
    $params[] = $_POST['minAmount'];
}

if (!empty($_POST['maxAmount'])) {
    $query .= " AND t.amount <= ?";
    $params[] = $_POST['maxAmount'];
}

if (!empty($_POST['startDate'])) {
    $query .= " AND t.created_at >= ?";
    $params[] = $_POST['startDate'];
}

if (!empty($_POST['endDate'])) {
    $query .= " AND t.created_at <= ?";
    $params[] = $_POST['endDate'];
}

if (!empty($_POST['region'])) {
    $query .= " AND c.region = ?";
    $params[] = $_POST['region'];
}

if (!empty($_POST['zone'])) {
    $query .= " AND c.Zone = ?";
    $params[] = $_POST['zone'];
}

if (!empty($_POST['level'])) {
    $query .= " AND c.level = ?";
    $params[] = $_POST['level'];
}

$query .= " ORDER BY t.created_at DESC";

// Execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Write data rows
foreach ($transactions as $transaction) {
    // Determine status
    $status = '';
    if ($transaction['remaining_amount'] == 0) {
        $status = 'Paid';
    } elseif ($transaction['remaining_amount'] < $transaction['total_amount']) {
        $status = 'Partial';
    } elseif ($transaction['days_overdue'] > 0) {
        $status = 'Overdue';
    } else {
        $status = 'Pending';
    }

    fputcsv($output, [
        $transaction['id'],
        $transaction['customer_name'],
        $transaction['region'],
        $transaction['hub'],
        $transaction['woreda'],
        $transaction['Zone'],
        $transaction['level'],
        format_currency($transaction['total_amount']),
        format_currency($transaction['amount_paid']),
        format_currency($transaction['remaining_amount']),
        $status,
        date('Y-m-d', strtotime($transaction['created_at'])),
        date('Y-m-d', strtotime($transaction['due_date'])),
        $transaction['days_overdue'],
        $transaction['payment_count']
    ]);
}

// Close the output stream
fclose($output);
exit; 