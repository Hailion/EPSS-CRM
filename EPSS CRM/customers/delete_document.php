<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

try {
    if (!isset($_POST['document_id']) || !isset($_POST['customer_id'])) {
        throw new Exception('Missing required parameters');
    }

    $document_id = intval($_POST['document_id']);
    $customer_id = intval($_POST['customer_id']);

    // Start transaction
    $pdo->beginTransaction();

    // Get document information
    $stmt = $pdo->prepare("SELECT * FROM customer_documents WHERE id = ? AND customer_id = ?");
    $stmt->execute([$document_id, $customer_id]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$document) {
        throw new Exception('Document not found');
    }

    // Delete file from filesystem
    $file_path = "../uploads/customers/{$customer_id}/{$document['file_path']}";
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM customer_documents WHERE id = ?");
    $stmt->execute([$document_id]);

    // Record in history
    $stmt = $pdo->prepare("
        INSERT INTO customer_history (
            customer_id, changes, changed_by, changed_at
        ) VALUES (?, ?, ?, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([
        $customer_id,
        "Deleted document: {$document['file_name']}",
        $_SESSION['user_id'] ?? 'system'
    ]);

    // Commit transaction
    $pdo->commit();

    $response['success'] = true;
    $response['message'] = 'Document deleted successfully';
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = $e->getMessage();
    http_response_code(400);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = 'Database error occurred';
    http_response_code(500);
}

echo json_encode($response); 