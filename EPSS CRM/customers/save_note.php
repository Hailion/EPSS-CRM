<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

// Set the response header to JSON
header('Content-Type: application/json');

// Debug information array
$debug = [
    'session_data' => $_SESSION,
    'user_id' => $_SESSION['user_id'] ?? 'not set',
    'user_role' => $_SESSION['user_role'] ?? 'not set'
];

// Check if user is logged in and has a valid user ID
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not authenticated. Please log in again.',
        'debug' => $debug
    ]);
    exit;
}

// Check if user has permission to create notes
if (!hasPermission('notes.create')) {
    echo json_encode([
        'success' => false,
        'message' => 'You do not have permission to create notes',
        'debug' => $debug
    ]);
    exit;
}

// Get the POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid input data',
        'debug' => $debug
    ]);
    exit;
}

// Validate required fields
if (empty($input['customer_id']) || empty($input['content'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Missing required fields',
        'debug' => $debug
    ]);
    exit;
}

try {
    // First, verify that the user exists and get their details
    $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Add query debug info
    $debug['user_query'] = [
        'sql' => "SELECT id, username, role FROM users WHERE id = ?",
        'params' => [$_SESSION['user_id']],
        'result' => $user
    ];

    if (!$user) {
        // If user not found, clear the session and return error
        session_unset();
        session_destroy();
        throw new Exception('Invalid user account. Please log in again. Debug: ' . json_encode($debug));
    }

    // Then check if the customer exists
    $stmt = $pdo->prepare("SELECT id, name FROM customers WHERE id = ?");
    $stmt->execute([$input['customer_id']]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        throw new Exception('Health Facility not found');
    }

    // Validate note type
    $allowedTypes = ['general', 'payment', 'contact', 'important'];
    $noteType = $input['type'] ?? 'general';
    if (!in_array($noteType, $allowedTypes)) {
        throw new Exception('Invalid note type');
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Insert the note
        $stmt = $pdo->prepare("
            INSERT INTO customer_notes (
                customer_id, 
                content, 
                type, 
                created_by, 
                created_at
            ) VALUES (?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $customer['id'],
            $input['content'],
            $noteType,
            $user['id']
        ]);

        $noteId = $pdo->lastInsertId();

        // Fetch the created note with user details for the response
        $stmt = $pdo->prepare("
            SELECT 
                n.*,
                u.username as created_by_name
            FROM customer_notes n
            LEFT JOIN users u ON n.created_by = u.id
            WHERE n.id = ?
        ");
        $stmt->execute([$noteId]);
        $note = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$note) {
            throw new Exception('Failed to retrieve created note');
        }

        // Commit the transaction
        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Note saved successfully',
            'note' => [
                'id' => $note['id'],
                'content' => $note['content'],
                'type' => $note['type'],
                'created_at' => date('M j, Y', strtotime($note['created_at'])),
                'created_by' => $note['created_by_name']
            ]
        ]);

    } catch (Exception $e) {
        // Rollback the transaction if something goes wrong
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error saving note: ' . $e->getMessage(),
        'debug' => $debug
    ]);
} 