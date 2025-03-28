<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

// Set the response header to JSON
header('Content-Type: application/json');

// Get note ID from query string
$note_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$note_id) {
    echo json_encode(['success' => false, 'message' => 'Note ID is required']);
    exit;
}

try {
    // Check if user can manage this note
    if (!canManageNote($note_id)) {
        throw new Exception('You do not have permission to delete this note');
    }

    // Delete the note
    $stmt = $pdo->prepare("DELETE FROM customer_notes WHERE id = ?");
    $result = $stmt->execute([$note_id]);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Note deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete note');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 