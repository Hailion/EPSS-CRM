<?php
require_once '../includes/init.php';
require_once '../includes/auth_check.php';
require_once '../includes/helpers.php';

// Get facility ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: list.php');
    exit;
}

// Get facility data
try {
    $stmt = $pdo->prepare("SELECT * FROM health_facility WHERE id = ?");
    $stmt->execute([$id]);
    $facility = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$facility) {
        header('Location: list.php');
        exit;
    }

    // Get payment history
    $payment_stmt = $pdo->prepare("
        SELECT p.*, u.name as user_name 
        FROM payments p 
        LEFT JOIN users u ON p.created_by = u.id 
        WHERE p.health_facility_id = ? 
        ORDER BY p.payment_date DESC
    ");
    $payment_stmt->execute([$id]);
    $payments = $payment_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get reminder history
    $reminder_stmt = $pdo->prepare("
        SELECT r.*, u.name as user_name 
        FROM reminders r 
        LEFT JOIN users u ON r.created_by = u.id 
        WHERE r.health_facility_id = ? 
        ORDER BY r.reminder_date DESC
    ");
    $reminder_stmt->execute([$id]);
    $reminders = $reminder_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching facility data: " . $e->getMessage());
    header('Location: list.php');
    exit;
}

$page_title = t('view_facility');
include '../includes/header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><?php echo htmlspecialchars($facility['facility_name']); ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../dashboard/"><?php echo t('home'); ?></a></li>
                        <li class="breadcrumb-item"><a href="list.php"><?php echo t('health_facilities'); ?></a></li>
                        <li class="breadcrumb-item active"><?php echo t('view_facility'); ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo t('facility_details'); ?></h3>
                            <div class="card-tools">
                                <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i> <?php echo t('edit'); ?>
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="table table-striped">
                                <tr>
                                    <th><?php echo t('facility_name'); ?>:</th>
                                    <td><?php echo htmlspecialchars($facility['facility_name']); ?></td>
                                </tr>
                                <tr>
                                    <th><?php echo t('invoice_number'); ?>:</th>
                                    <td><?php echo htmlspecialchars($facility['credit_invoice_number']); ?></td>
                                </tr>
                                <tr>
                                    <th><?php echo t('invoice_date'); ?>:</th>
                                    <td><?php echo format_date($facility['invoice_date']); ?></td>
                                </tr>
                                <tr>
                                    <th><?php echo t('total_amount'); ?>:</th>
                                    <td><?php echo format_currency($facility['total_invoice_amount']); ?></td>
                                </tr>
                                <tr>
                                    <th><?php echo t('paid_amount'); ?>:</th>
                                    <td><?php echo format_currency($facility['paid_amount']); ?></td>
                                </tr>
                                <tr>
                                    <th><?php echo t('remaining_balance'); ?>:</th>
                                    <td>
                                        <span class="badge badge-<?php echo get_payment_status_class($facility['payment_status']); ?>">
                                            <?php echo format_currency($facility['remaining_balance']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php echo t('payment_status'); ?>:</th>
                                    <td>
                                        <span class="badge badge-<?php echo get_payment_status_class($facility['payment_status']); ?>">
                                            <?php echo t($facility['payment_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php echo t('contact_person'); ?>:</th>
                                    <td><?php echo htmlspecialchars($facility['contact_person']); ?></td>
                                </tr>
                                <tr>
                                    <th><?php echo t('phone'); ?>:</th>
                                    <td><?php echo htmlspecialchars($facility['phone']); ?></td>
                                </tr>
                                <tr>
                                    <th><?php echo t('email'); ?>:</th>
                                    <td><?php echo htmlspecialchars($facility['email']); ?></td>
                                </tr>
                                <tr>
                                    <th><?php echo t('address'); ?>:</th>
                                    <td><?php echo nl2br(htmlspecialchars($facility['address'])); ?></td>
                                </tr>
                                <tr>
                                    <th><?php echo t('status'); ?>:</th>
                                    <td>
                                        <span class="badge badge-<?php echo $facility['status'] == 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo t($facility['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo t('payment_history'); ?></h3>
                            <div class="card-tools">
                                <a href="../payments/add.php?facility_id=<?php echo $id; ?>" class="btn btn-success btn-sm">
                                    <i class="fas fa-plus"></i> <?php echo t('add_payment'); ?>
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th><?php echo t('date'); ?></th>
                                            <th><?php echo t('amount'); ?></th>
                                            <th><?php echo t('reference'); ?></th>
                                            <th><?php echo t('added_by'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($payments)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center"><?php echo t('no_payments_found'); ?></td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($payments as $payment): ?>
                                            <tr>
                                                <td><?php echo format_date($payment['payment_date']); ?></td>
                                                <td><?php echo format_currency($payment['amount']); ?></td>
                                                <td><?php echo htmlspecialchars($payment['reference_number']); ?></td>
                                                <td><?php echo htmlspecialchars($payment['user_name']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo t('reminder_history'); ?></h3>
                            <div class="card-tools">
                                <a href="../reminders/add.php?facility_id=<?php echo $id; ?>" class="btn btn-info btn-sm">
                                    <i class="fas fa-plus"></i> <?php echo t('add_reminder'); ?>
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th><?php echo t('date'); ?></th>
                                            <th><?php echo t('message'); ?></th>
                                            <th><?php echo t('status'); ?></th>
                                            <th><?php echo t('added_by'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($reminders)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center"><?php echo t('no_reminders_found'); ?></td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($reminders as $reminder): ?>
                                            <tr>
                                                <td><?php echo format_date($reminder['reminder_date']); ?></td>
                                                <td><?php echo htmlspecialchars($reminder['message']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo get_reminder_status_class($reminder['status']); ?>">
                                                        <?php echo t($reminder['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($reminder['user_name']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 