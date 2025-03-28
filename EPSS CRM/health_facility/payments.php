<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

$error = '';
$success = '';
$facility = null;
$payments = [];

// Get facility ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: list.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $amount = (float)$_POST['amount'];
        $payment_date = $_POST['payment_date'];
        $payment_method = trim($_POST['payment_method']);
        $reference_number = trim($_POST['reference_number']);
        
        if ($amount <= 0) {
            throw new Exception(t('invalid_amount'));
        }
        
        if (empty($payment_date)) {
            throw new Exception(t('payment_date_required'));
        }
        
        if (empty($payment_method)) {
            throw new Exception(t('payment_method_required'));
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Insert payment record
        $sql = "INSERT INTO payments (customer_id, amount, payment_date, payment_method, reference_number, created_by) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $amount, $payment_date, $payment_method, $reference_number, $_SESSION['user_id']]);
        
        // Update facility balance
        $update_sql = "UPDATE health_facility 
                      SET paid_amount = paid_amount + ?,
                          remaining_balance = remaining_balance - ?,
                          payment_status = CASE 
                              WHEN remaining_balance - ? <= 0 THEN 'paid'
                              WHEN remaining_balance - ? > 0 AND remaining_balance - ? < total_invoice_amount THEN 'partial'
                              ELSE 'unpaid'
                          END
                      WHERE id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([$amount, $amount, $amount, $amount, $amount, $id]);
        
        $pdo->commit();
        $success = t('payment_added_successfully');
        
    } catch (Exception $e) {
        $pdo->rollBack();
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
    
    // Get payments
    $payments_stmt = $pdo->prepare("
        SELECT p.*, u.name as user_name 
        FROM payments p 
        LEFT JOIN users u ON p.created_by = u.id 
        WHERE p.customer_id = ? 
        ORDER BY p.payment_date DESC
    ");
    $payments_stmt->execute([$id]);
    $payments = $payments_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error fetching facility data: " . $e->getMessage());
    header('Location: list.php');
    exit;
}

$page_title = t('facility_payments');
include '../includes/header.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0"><?php echo t('facility_payments'); ?></h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="../dashboard/"><?php echo t('home'); ?></a></li>
            <li class="breadcrumb-item"><a href="list.php"><?php echo t('health_facilities'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo t('payments'); ?></li>
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
                <strong><?php echo t('paid_amount'); ?>:</strong>
                <?php echo format_currency($facility['paid_amount']); ?>
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
              <h3 class="card-title"><?php echo t('add_payment'); ?></h3>
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
                  <label for="amount"><?php echo t('amount'); ?></label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text">$</span>
                    </div>
                    <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0"
                      max="<?php echo $facility['remaining_balance']; ?>" required>
                  </div>
                  <small class="form-text text-muted">
                    <?php echo t('max_amount'); ?>: <?php echo format_currency($facility['remaining_balance']); ?>
                  </small>
                </div>

                <div class="form-group">
                  <label for="payment_date"><?php echo t('payment_date'); ?></label>
                  <input type="date" class="form-control" id="payment_date" name="payment_date"
                    value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                  <label for="payment_method"><?php echo t('payment_method'); ?></label>
                  <select class="form-control" id="payment_method" name="payment_method" required>
                    <option value=""><?php echo t('select_payment_method'); ?></option>
                    <option value="cash"><?php echo t('cash'); ?></option>
                    <option value="bank_transfer"><?php echo t('bank_transfer'); ?></option>
                    <option value="check"><?php echo t('check'); ?></option>
                    <option value="credit_card"><?php echo t('credit_card'); ?></option>
                    <option value="other"><?php echo t('other'); ?></option>
                  </select>
                </div>

                <div class="form-group">
                  <label for="reference_number"><?php echo t('reference_number'); ?></label>
                  <input type="text" class="form-control" id="reference_number" name="reference_number">
                  <small class="form-text text-muted"><?php echo t('reference_number_optional'); ?></small>
                </div>

                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-plus"></i> <?php echo t('add_payment'); ?>
                </button>
              </form>
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <h3 class="card-title"><?php echo t('payment_history'); ?></h3>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table">
                  <thead>
                    <tr>
                      <th><?php echo t('date'); ?></th>
                      <th><?php echo t('amount'); ?></th>
                      <th><?php echo t('method'); ?></th>
                      <th><?php echo t('reference'); ?></th>
                      <th><?php echo t('added_by'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($payments)): ?>
                    <tr>
                      <td colspan="5" class="text-center"><?php echo t('no_payments_found'); ?></td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                    <tr>
                      <td><?php echo format_date($payment['payment_date']); ?></td>
                      <td><?php echo format_currency($payment['amount']); ?></td>
                      <td><?php echo t($payment['payment_method']); ?></td>
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
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>