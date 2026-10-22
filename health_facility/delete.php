<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: list.php');
    exit;
}

// Get facility ID
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    $_SESSION['error'] = t('invalid_facility_id');
    header('Location: list.php');
    exit;
}

try {
    // Check if facility exists
    $check_stmt = $pdo->prepare("SELECT id, facility_name FROM health_facility WHERE id = ?");
    $check_stmt->execute([$id]);
    $facility = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$facility) {
        $_SESSION['error'] = t('facility_not_found');
        header('Location: list.php');
        exit;
    }

    // Check if facility has any payments
    $payment_stmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE health_facility_id = ?");
    $payment_stmt->execute([$id]);
    $has_payments = $payment_stmt->fetchColumn() > 0;

    // Check if facility has any reminders
    $reminder_stmt = $pdo->prepare("SELECT COUNT(*) FROM reminders WHERE health_facility_id = ?");
    $reminder_stmt->execute([$id]);
    $has_reminders = $reminder_stmt->fetchColumn() > 0;

    // If facility has payments or reminders, prevent deletion
    if ($has_payments || $has_reminders) {
        $_SESSION['error'] = t('cannot_delete_facility_with_records');
        header('Location: list.php');
        exit;
    }

    // Delete the facility
    $delete_stmt = $pdo->prepare("DELETE FROM health_facility WHERE id = ?");
    $delete_stmt->execute([$id]);

    $_SESSION['success'] = t('facility_deleted_successfully');
    header('Location: list.php');
    exit;

} catch (PDOException $e) {
    error_log("Error deleting facility: " . $e->getMessage());
    $_SESSION['error'] = t('error_deleting_facility');
    header('Location: list.php');
    exit;
} 