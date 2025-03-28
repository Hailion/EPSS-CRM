<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

$error = '';
$success = '';
$facility = null;

// Get facility ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: list.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        $facility_name = trim($_POST['facility_name']);
        $credit_invoice_number = trim($_POST['credit_invoice_number']);
        $invoice_date = trim($_POST['invoice_date']);
        $total_invoice_amount = floatval($_POST['total_invoice_amount']);
        $paid_amount = floatval($_POST['paid_amount']);
        $contact_person = trim($_POST['contact_person']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $address = trim($_POST['address']);
        $status = $_POST['status'];

        if (empty($facility_name)) {
            throw new Exception(t('facility_name_required'));
        }

        if (empty($credit_invoice_number)) {
            throw new Exception(t('invoice_number_required'));
        }

        // Check if invoice number exists for other facilities
        $check_stmt = $pdo->prepare("SELECT id FROM health_facility WHERE credit_invoice_number = ? AND id != ?");
        $check_stmt->execute([$credit_invoice_number, $id]);
        if ($check_stmt->fetch()) {
            throw new Exception(t('invoice_number_exists'));
        }

        // Calculate remaining balance and payment status
        $remaining_balance = $total_invoice_amount - $paid_amount;
        $payment_status = 'unpaid';
        if ($remaining_balance <= 0) {
            $payment_status = 'paid';
            $remaining_balance = 0;
        } elseif ($paid_amount > 0) {
            $payment_status = 'partial';
        }

        // Update facility
        $sql = "UPDATE health_facility SET 
            facility_name = ?,
            credit_invoice_number = ?,
            invoice_date = ?,
            total_invoice_amount = ?,
            paid_amount = ?,
            remaining_balance = ?,
            payment_status = ?,
            contact_person = ?,
            phone = ?,
            email = ?,
            address = ?,
            status = ?
            WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $facility_name,
            $credit_invoice_number,
            $invoice_date,
            $total_invoice_amount,
            $paid_amount,
            $remaining_balance,
            $payment_status,
            $contact_person,
            $phone,
            $email,
            $address,
            $status,
            $id
        ]);

        $success = t('facility_updated_successfully');
        
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
} catch (PDOException $e) {
    error_log("Error fetching facility: " . $e->getMessage());
    header('Location: list.php');
    exit;
}

$page_title = t('edit_facility');
include '../includes/header.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0"><?php echo t('edit_facility'); ?></h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="../dashboard/"><?php echo t('home'); ?></a></li>
            <li class="breadcrumb-item"><a href="list.php"><?php echo t('health_facilities'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo t('edit_facility'); ?></li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-body">
              <?php if ($error): ?>
              <div class="alert alert-danger"><?php echo $error; ?></div>
              <?php endif; ?>

              <?php if ($success): ?>
              <div class="alert alert-success"><?php echo $success; ?></div>
              <?php endif; ?>

              <form action="" method="POST" id="facilityForm">
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="facility_name"><?php echo t('facility_name'); ?> *</label>
                      <input type="text" class="form-control" id="facility_name" name="facility_name" required
                        value="<?php echo htmlspecialchars($facility['facility_name']); ?>">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="credit_invoice_number"><?php echo t('invoice_number'); ?> *</label>
                      <input type="text" class="form-control" id="credit_invoice_number" name="credit_invoice_number"
                        required value="<?php echo htmlspecialchars($facility['credit_invoice_number']); ?>">
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="invoice_date"><?php echo t('invoice_date'); ?> *</label>
                      <input type="date" class="form-control" id="invoice_date" name="invoice_date" required
                        value="<?php echo htmlspecialchars($facility['invoice_date']); ?>">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="total_invoice_amount"><?php echo t('total_amount'); ?> *</label>
                      <input type="number" step="0.01" class="form-control" id="total_invoice_amount"
                        name="total_invoice_amount" required
                        value="<?php echo htmlspecialchars($facility['total_invoice_amount']); ?>">
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="paid_amount"><?php echo t('paid_amount'); ?></label>
                      <input type="number" step="0.01" class="form-control" id="paid_amount" name="paid_amount"
                        value="<?php echo htmlspecialchars($facility['paid_amount']); ?>">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="remaining_balance"><?php echo t('remaining_balance'); ?></label>
                      <input type="number" step="0.01" class="form-control" id="remaining_balance"
                        value="<?php echo htmlspecialchars($facility['remaining_balance']); ?>" readonly>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="contact_person"><?php echo t('contact_person'); ?></label>
                      <input type="text" class="form-control" id="contact_person" name="contact_person"
                        value="<?php echo htmlspecialchars($facility['contact_person']); ?>">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="phone"><?php echo t('phone'); ?></label>
                      <input type="text" class="form-control" id="phone" name="phone"
                        value="<?php echo htmlspecialchars($facility['phone']); ?>">
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="email"><?php echo t('email'); ?></label>
                      <input type="email" class="form-control" id="email" name="email"
                        value="<?php echo htmlspecialchars($facility['email']); ?>">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="status"><?php echo t('status'); ?></label>
                      <select class="form-control" id="status" name="status">
                        <option value="active" <?php echo $facility['status'] == 'active' ? 'selected' : ''; ?>>
                          <?php echo t('active'); ?>
                        </option>
                        <option value="inactive" <?php echo $facility['status'] == 'inactive' ? 'selected' : ''; ?>>
                          <?php echo t('inactive'); ?>
                        </option>
                      </select>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-12">
                    <div class="form-group">
                      <label for="address"><?php echo t('address'); ?></label>
                      <textarea class="form-control" id="address" name="address"
                        rows="3"><?php echo htmlspecialchars($facility['address']); ?></textarea>
                    </div>
                  </div>
                </div>

                <div class="row mt-4">
                  <div class="col-12">
                    <button type="submit" class="btn btn-primary"><?php echo t('save'); ?></button>
                    <a href="list.php" class="btn btn-secondary"><?php echo t('cancel'); ?></a>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
  // Calculate remaining balance when total amount or paid amount changes
  function updateRemainingBalance() {
    var totalAmount = parseFloat($('#total_invoice_amount').val()) || 0;
    var paidAmount = parseFloat($('#paid_amount').val()) || 0;
    var remainingBalance = totalAmount - paidAmount;
    $('#remaining_balance').val(remainingBalance.toFixed(2));
  }

  $('#total_invoice_amount, #paid_amount').on('input', updateRemainingBalance);

  // Form validation
  $('#facilityForm').submit(function(e) {
    var totalAmount = parseFloat($('#total_invoice_amount').val());
    var paidAmount = parseFloat($('#paid_amount').val());

    if (totalAmount <= 0) {
      e.preventDefault();
      alert(<?php echo json_encode(t('invalid_amount')); ?>);
      return false;
    }

    if (paidAmount > totalAmount) {
      e.preventDefault();
      alert(<?php echo json_encode(t('paid_amount_exceeds_total')); ?>);
      return false;
    }
  });
});
</script>

<?php include '../includes/footer.php'; ?>