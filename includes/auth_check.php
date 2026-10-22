<?php
require_once __DIR__ . '/config.php';

/**
 * Check if user is admin
 * @return bool Whether user is admin
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Require user to be admin
 * Redirects to dashboard if not admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        $_SESSION['error'] = 'You do not have permission to access this page.';
        redirect(appUrl('dashboard/index.php'));
    }
}

/**
 * Check if user has permission to perform an action
 * @param string $permission Permission to check
 * @return bool
 */
function hasPermission($permission) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        return false;
    }

    // Define role-based permissions
    $permissions = [
        'admin' => [
            // Note permissions
            'notes.create',
            'notes.read',
            'notes.update',
            'notes.delete',
            'notes.manage_all',
            // Customer permissions
            'customers.create',
            'customers.read',
            'customers.update',
            'customers.delete',
            'customers.export',
            'customers.import',
            'customers.manage_all'
        ],
        'manager' => [
            // Note permissions
            'notes.create',
            'notes.read',
            'notes.update',
            'notes.manage_all',
            // Customer permissions
            'customers.create',
            'customers.read',
            'customers.update',
            'customers.export',
            'customers.import'
        ],
        'staff' => [
            // Note permissions
            'notes.create',
            'notes.read',
            // Customer permissions
            'customers.read',
            'customers.export'
        ]
    ];

    $userRole = $_SESSION['user_role'];
    
    // If role doesn't exist in permissions array
    if (!isset($permissions[$userRole])) {
        return false;
    }

    return in_array($permission, $permissions[$userRole]);
}

/**
 * Check if user can manage a specific note
 * @param int $noteId Note ID to check
 * @return bool
 */
function canManageNote($noteId = null) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    // Admins and managers can manage all notes
    if (hasPermission('notes.manage_all')) {
        return true;
    }

    // For new notes
    if ($noteId === null) {
        return hasPermission('notes.create');
    }

    // For existing notes, check if user is the creator
    global $pdo;
    $stmt = $pdo->prepare("SELECT created_by FROM customer_notes WHERE id = ?");
    $stmt->execute([$noteId]);
    $note = $stmt->fetch(PDO::FETCH_ASSOC);

    return $note && $note['created_by'] == $_SESSION['user_id'];
}

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['error'] = 'Please log in to access this page.';
    redirect(appUrl('auth/login.php'));
} 
