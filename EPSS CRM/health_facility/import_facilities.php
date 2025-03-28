<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

$error = '';
$success = '';
$imported_count = 0;
$failed_count = 0;
$failed_rows = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    try {
        $file = $_FILES['csv_file'];
        
        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception(t('error_uploading_file'));
        }
        
        if ($file['type'] !== 'text/csv' && $file['type'] !== 'application/vnd.ms-excel') {
            throw new Exception(t('invalid_file_type'));
        }
        
        // Open CSV file
        $handle = fopen($file['tmp_name'], 'r');
        if ($handle === false) {
            throw new Exception(t('error_reading_file'));
        }
        
        // Read header row
        $header = fgetcsv($handle);
        if (!$header) {
            throw new Exception(t('empty_file'));
        }
        
        // Validate required columns
        $required_columns = ['facility_name', 'credit_invoice_number', 'invoice_date', 'total_invoice_amount'];
        $missing_columns = array_diff($required_columns, $header);
        if (!empty($missing_columns)) {
            throw new Exception(t('missing_required_columns') . ': ' . implode(', ', $missing_columns));
        }
        
        // Get column indexes
        $column_indexes = array_flip($header);
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Read data rows
        $row_number = 2; // Start from 2 (after header)
        while (($data = fgetcsv($handle)) !== false) {
            try {
                // Validate required fields
                $facility_name = trim($data[$column_indexes['facility_name']]);
                $credit_invoice_number = trim($data[$column_indexes['credit_invoice_number']]);
                $invoice_date = trim($data[$column_indexes['invoice_date']]);
                $total_invoice_amount = floatval($data[$column_indexes['total_invoice_amount']]);
                
                if (empty($facility_name) || empty($credit_invoice_number) || empty($invoice_date) || $total_invoice_amount <= 0) {
                    throw new Exception(t('invalid_data'));
                }
                
                // Check if invoice number already exists
                $check_stmt = $pdo->prepare("SELECT id FROM health_facility WHERE credit_invoice_number = ?");
                $check_stmt->execute([$credit_invoice_number]);
                if ($check_stmt->fetch()) {
                    throw new Exception(t('invoice_number_exists'));
                }
                
                // Insert facility
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
                    $total_invoice_amount,
                    $data[$column_indexes['contact_person']] ?? '',
                    $data[$column_indexes['phone']] ?? '',
                    $data[$column_indexes['email']] ?? '',
                    $data[$column_indexes['address']] ?? ''
                ]);
                
                $imported_count++;
                
            } catch (Exception $e) {
                $failed_count++;
                $failed_rows[] = [
                    'row' => $row_number,
                    'error' => $e->getMessage()
                ];
            }
            
            $row_number++;
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Close file
        fclose($handle);
        
        $success = sprintf(t('import_completed'), $imported_count, $failed_count);
        
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if (isset($handle) && is_resource($handle)) {
            fclose($handle);
        }
        $error = $e->getMessage();
    }
}

$page_title = t('import_facilities');
include '../includes/header.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0"><?php echo t('import_facilities'); ?></h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="../dashboard/"><?php echo t('home'); ?></a></li>
            <li class="breadcrumb-item"><a href="list.php"><?php echo t('health_facilities'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo t('import_facilities'); ?></li>
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

              <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                  <label for="csv_file"><?php echo t('select_csv_file'); ?></label>
                  <input type="file" class="form-control-file" id="csv_file" name="csv_file" accept=".csv" required>
                  <small class="form-text text-muted">
                    <?php echo t('csv_file_requirements'); ?>
                  </small>
                </div>

                <div class="form-group">
                  <label><?php echo t('required_columns'); ?>:</label>
                  <ul class="list-unstyled">
                    <li><code>facility_name</code> - <?php echo t('facility_name_desc'); ?></li>
                    <li><code>credit_invoice_number</code> - <?php echo t('invoice_number_desc'); ?></li>
                    <li><code>invoice_date</code> - <?php echo t('invoice_date_desc'); ?></li>
                    <li><code>total_invoice_amount</code> - <?php echo t('total_amount_desc'); ?></li>
                  </ul>
                  <label><?php echo t('optional_columns'); ?>:</label>
                  <ul class="list-unstyled">
                    <li><code>contact_person</code> - <?php echo t('contact_person_desc'); ?></li>
                    <li><code>phone</code> - <?php echo t('phone_desc'); ?></li>
                    <li><code>email</code> - <?php echo t('email_desc'); ?></li>
                    <li><code>address</code> - <?php echo t('address_desc'); ?></li>
                  </ul>
                </div>

                <div class="form-group">
                  <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> <?php echo t('import'); ?>
                  </button>
                  <a href="list.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> <?php echo t('cancel'); ?>
                  </a>
                </div>
              </form>

              <?php if (!empty($failed_rows)): ?>
              <div class="mt-4">
                <h4><?php echo t('failed_rows'); ?></h4>
                <div class="table-responsive">
                  <table class="table table-bordered">
                    <thead>
                      <tr>
                        <th><?php echo t('row_number'); ?></th>
                        <th><?php echo t('error'); ?></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($failed_rows as $row): ?>
                      <tr>
                        <td><?php echo $row['row']; ?></td>
                        <td><?php echo $row['error']; ?></td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>