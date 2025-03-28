<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';

// Set the response header to JSON
header('Content-Type: application/json');

// Get customer ID from query string
$customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;

if (!$customer_id) {
    echo json_encode(['success' => false, 'message' => 'Health Facility ID is required']);
    exit;
}

try {
    // Fetch all payments for the customer
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            t.amount as transaction_amount,
            t.due_date,
            (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE transaction_id = t.id) as total_paid
        FROM payments p
        JOIN transactions t ON p.transaction_id = t.id
        WHERE t.customer_id = ?
        ORDER BY p.payment_date DESC
    ");

    $stmt->execute([$customer_id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the payments data
    $formatted_payments = array_map(function($payment) {
        return [
            'payment_date' => date('M j, Y', strtotime($payment['payment_date'])),
            'amount' => format_currency($payment['amount']),
            'payment_type' => ucfirst($payment['payment_method']),
            'status' => $payment['total_paid'] >= $payment['transaction_amount'] ? 'Completed' : 'Partial',
            'reference' => $payment['reference_number'] ?? '-'
        ];
    }, $payments);

    echo json_encode([
        'success' => true,
        'payments' => $formatted_payments
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching payment history: ' . $e->getMessage()
    ]);
} 