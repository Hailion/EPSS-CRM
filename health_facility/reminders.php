<?php
require_once '../includes/init.php';
require_once '../includes/auth_check.php';
require_once '../includes/helpers.php';

$error = '';
$success = '';
$facility = null;
$reminders = [];

// Get facility ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: list.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $reminder_date = $_POST['reminder_date'];
        $reminder_type = trim($_POST['reminder_type']);
        $message = trim($_POST['message']);
        $status = trim($_POST['status']);
        
        if (empty($reminder_date)) {
            throw new Exception(t('reminder_date_required'));
        }
        
        if (empty($reminder_type)) {
            throw new Exception(t('reminder_type_required'));
        }
        
        if (empty($message)) {
            throw new Exception(t('message_required'));
        }
        
        // Insert reminder
        $sql = "INSERT INTO reminders (customer_id, reminder_date, reminder_type, message, status, created_by) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $reminder_date, $reminder_type, $message, $status, $_SESSION['user_id']]);
        
        $success = t('reminder_added_successfully');
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
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
    
    // Get reminders
    $reminders_stmt = $pdo->prepare("
        SELECT r.*, u.name as user_name 
        FROM reminders r 
        LEFT JOIN users u ON r.created_by = u.id 
        WHERE r.customer_id = ? 
        ORDER BY r.reminder_date DESC
    ");
    $reminders_stmt->execute([$id]);
    $reminders = $reminders_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error fetching facility data: " . $e->getMessage());
    header('Location: list.php');
    exit;
}

$page_title = t('facility_reminders');
include '../includes/header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><?php echo t('facility_reminders'); ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../dashboard/"><?php echo t('home'); ?></a></li>
                        <li class="breadcrumb-item"><a href="list.php"><?php echo t('health_facilities'); ?></a></li>
                        <li class="breadcrumb-item active"><?php echo t('reminders'); ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo t('facility_info'); ?></h3>
                        </div>
                        <div class="card-body">
                            <h5><?php echo htmlspecialchars($facility['facility_name']); ?></h5>
                            <p class="mb-1">
                                <strong><?php echo t('invoice_number'); ?>:</strong> 
                                <?php echo htmlspecialchars($facility['credit_invoice_number']); ?>
                            </p>
                            <p class="mb-1">
                                <strong><?php echo t('total_amount'); ?>:</strong> 
                                <?php echo format_currency($facility['total_invoice_amount']); ?>
                            </p>
                            <p class="mb-1">
                                <strong><?php echo t('remaining_balance'); ?>:</strong> 
                                <?php echo format_currency($facility['remaining_balance']); ?>
                            </p>
                            <p class="mb-0">
                                <strong><?php echo t('status'); ?>:</strong> 
                                <span class="badge badge-<?php echo get_status_color($facility['payment_status']); ?>">
                                    <?php echo t($facility['payment_status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo t('add_reminder'); ?></h3>
                        </div>
                        <div class="card-body">
                            <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>

                            <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                            <?php endif; ?>

                            <form action="" method="POST">
                                <div class="form-group">
                                    <label for="reminder_date"><?php echo t('reminder_date'); ?></label>
                                    <input type="date" class="form-control" id="reminder_date" name="reminder_date" 
                                           value="<?php echo date('Y-m-d'); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="reminder_type"><?php echo t('reminder_type'); ?></label>
                                    <select class="form-control" id="reminder_type" name="reminder_type" required>
                                        <option value=""><?php echo t('select_reminder_type'); ?></option>
                                        <option value="email"><?php echo t('email'); ?></option>
                                        <option value="phone"><?php echo t('phone'); ?></option>
                                        <option value="letter"><?php echo t('letter'); ?></option>
                                        <option value="visit"><?php echo t('visit'); ?></option>
                                        <option value="other"><?php echo t('other'); ?></option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="message"><?php echo t('message'); ?></label>
                                    <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="status"><?php echo t('status'); ?></label>
                                    <select class="form-control" id="status" name="status" required>
                                        <option value="pending"><?php echo t('pending'); ?></option>
                                        <option value="completed"><?php echo t('completed'); ?></option>
                                        <option value="cancelled"><?php echo t('cancelled'); ?></option>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> <?php echo t('add_reminder'); ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo t('reminder_history'); ?></h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th><?php echo t('date'); ?></th>
                                            <th><?php echo t('type'); ?></th>
                                            <th><?php echo t('message'); ?></th>
                                            <th><?php echo t('status'); ?></th>
                                            <th><?php echo t('added_by'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($reminders)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center"><?php echo t('no_reminders_found'); ?></td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($reminders as $reminder): ?>
                                            <tr>
                                                <td><?php echo format_date($reminder['reminder_date']); ?></td>
                                                <td><?php echo t($reminder['reminder_type']); ?></td>
                                                <td><?php echo nl2br(htmlspecialchars($reminder['message'])); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo get_reminder_status_color($reminder['status']); ?>">
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