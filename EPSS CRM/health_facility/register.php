<?php
require_once '../includes/init.php';
require_once '../includes/auth_check.php';
require_once '../includes/helpers.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        $facility_name = trim($_POST['facility_name']);
        $credit_invoice_number = trim($_POST['credit_invoice_number']);
        $invoice_date = trim($_POST['invoice_date']);
        $total_invoice_amount = floatval($_POST['total_invoice_amount']);
        $contact_person = trim($_POST['contact_person']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $address = trim($_POST['address']);

        if (empty($facility_name)) {
            throw new Exception(t('facility_name_required'));
        }

        if (empty($credit_invoice_number)) {
            throw new Exception(t('invoice_number_required'));
        }

        // Check if invoice number already exists
        $check_stmt = $pdo->prepare("SELECT id FROM health_facility WHERE credit_invoice_number = ?");
        $check_stmt->execute([$credit_invoice_number]);
        if ($check_stmt->fetch()) {
            throw new Exception(t('invoice_number_exists'));
        }

        // Insert new facility
        $sql = "INSERT INTO health_facility (
            facility_name, credit_invoice_number, invoice_date, 
            total_invoice_amount, paid_amount, remaining_balance,
            payment_status, contact_person, phone, email, address
        ) VALUES (
            ?, ?, ?, ?, 0, ?, 'unpaid', ?, ?, ?, ?
        )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $facility_name,
            $credit_invoice_number,
            $invoice_date,
            $total_invoice_amount,
            $total_invoice_amount, // remaining_balance starts as total amount
            $contact_person,
            $phone,
            $email,
            $address
        ]);

        $success = t('facility_added_successfully');
        
        // Redirect to the facility list after successful addition
        header('Location: list.php?success=' . urlencode($success));
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$page_title = t('add_facility');
include '../includes/header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><?php echo t('add_facility'); ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../dashboard/"><?php echo t('home'); ?></a></li>
                        <li class="breadcrumb-item"><a href="list.php"><?php echo t('health_facilities'); ?></a></li>
                        <li class="breadcrumb-item active"><?php echo t('add_facility'); ?></li>
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

                            <form action="" method="POST" id="facilityForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="facility_name"><?php echo t('facility_name'); ?> *</label>
                                            <input type="text" class="form-control" id="facility_name" name="facility_name" 
                                                required value="<?php echo isset($_POST['facility_name']) ? htmlspecialchars($_POST['facility_name']) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="credit_invoice_number"><?php echo t('invoice_number'); ?> *</label>
                                            <input type="text" class="form-control" id="credit_invoice_number" name="credit_invoice_number" 
                                                required value="<?php echo isset($_POST['credit_invoice_number']) ? htmlspecialchars($_POST['credit_invoice_number']) : ''; ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="invoice_date"><?php echo t('invoice_date'); ?> *</label>
                                            <input type="date" class="form-control" id="invoice_date" name="invoice_date" 
                                                required value="<?php echo isset($_POST['invoice_date']) ? htmlspecialchars($_POST['invoice_date']) : date('Y-m-d'); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="total_invoice_amount"><?php echo t('total_amount'); ?> *</label>
                                            <input type="number" step="0.01" class="form-control" id="total_invoice_amount" name="total_invoice_amount" 
                                                required value="<?php echo isset($_POST['total_invoice_amount']) ? htmlspecialchars($_POST['total_invoice_amount']) : '0.00'; ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="contact_person"><?php echo t('contact_person'); ?></label>
                                            <input type="text" class="form-control" id="contact_person" name="contact_person" 
                                                value="<?php echo isset($_POST['contact_person']) ? htmlspecialchars($_POST['contact_person']) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="phone"><?php echo t('phone'); ?></label>
                                            <input type="text" class="form-control" id="phone" name="phone" 
                                                value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email"><?php echo t('email'); ?></label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="address"><?php echo t('address'); ?></label>
                                            <textarea class="form-control" id="address" name="address" rows="3"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
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
    // Form validation
    $('#facilityForm').submit(function(e) {
        var totalAmount = parseFloat($('#total_invoice_amount').val());
        
        if (totalAmount <= 0) {
            e.preventDefault();
            alert(<?php echo json_encode(t('invalid_amount')); ?>);
            return false;
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?> 