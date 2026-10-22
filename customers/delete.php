<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

try {
    $customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if (!$customer_id) {
        throw new Exception("Invalid Health Facility ID");
    }

    // Start transaction
    $pdo->beginTransaction();

    // Check if customer exists and get their transaction status
    $stmt = $pdo->prepare("
        SELECT c.*, 
               COUNT(t.id) as total_transactions,
               SUM(CASE WHEN t.status != 'paid' THEN 1 ELSE 0 END) as unpaid_transactions
        FROM customers c
        LEFT JOIN transactions t ON c.id = t.customer_id
        WHERE c.id = ?
        GROUP BY c.id
    ");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        throw new Exception("Health Facility not found");
    }

    // Check if customer has unpaid transactions
    if ($customer['unpaid_transactions'] > 0) {
        throw new Exception("Cannot delete Health Facility with unpaid transactions");
    }

    // Delete all paid transactions first
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE customer_id = ? AND status = 'paid'");
    $stmt->execute([$customer_id]);

    // Delete customer
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);

    // Commit transaction
    $pdo->commit();

    $response['success'] = true;
    $response['message'] = "Health Facility deleted successfully";

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = $e->getMessage();
    http_response_code(400);
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = "Database error occurred";
    http_response_code(500);
}

echo json_encode($response);
exit; 