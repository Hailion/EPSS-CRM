<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';
$error = '';
$success = '';
$facility = null;
$notes = [];

// Get facility ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: list.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $note = trim($_POST['note']);
        
        if (empty($note)) {
            throw new Exception(t('note_required'));
        }
        
        // Insert note
        $sql = "INSERT INTO customer_notes (customer_id, note, created_by) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $note, $_SESSION['user_id']]);
        
        $success = t('note_added_successfully');
        
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
    
    // Get notes
    $notes_stmt = $pdo->prepare("
        SELECT n.*, u.name as user_name 
        FROM customer_notes n 
        LEFT JOIN users u ON n.created_by = u.id 
        WHERE n.customer_id = ? 
        ORDER BY n.created_at DESC
    ");
    $notes_stmt->execute([$id]);
    $notes = $notes_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error fetching facility data: " . $e->getMessage());
    header('Location: list.php');
    exit;
}

$page_title = t('facility_notes');
include '../includes/header.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0"><?php echo t('facility_notes'); ?></h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="../dashboard/"><?php echo t('home'); ?></a></li>
            <li class="breadcrumb-item"><a href="list.php"><?php echo t('health_facilities'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo t('notes'); ?></li>
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
              <h3 class="card-title"><?php echo t('add_note'); ?></h3>
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
                  <label for="note"><?php echo t('note'); ?></label>
                  <textarea class="form-control" id="note" name="note" rows="3" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-plus"></i> <?php echo t('add_note'); ?>
                </button>
              </form>
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <h3 class="card-title"><?php echo t('notes_history'); ?></h3>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table">
                  <thead>
                    <tr>
                      <th><?php echo t('date'); ?></th>
                      <th><?php echo t('note'); ?></th>
                      <th><?php echo t('added_by'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($notes)): ?>
                    <tr>
                      <td colspan="3" class="text-center"><?php echo t('no_notes_found'); ?></td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($notes as $note): ?>
                    <tr>
                      <td><?php echo format_datetime($note['created_at']); ?></td>
                      <td><?php echo nl2br(htmlspecialchars($note['note'])); ?></td>
                      <td><?php echo htmlspecialchars($note['user_name']); ?></td>
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