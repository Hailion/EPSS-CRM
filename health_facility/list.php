<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';
// Get all health facilities
try {
    $query = "SELECT * FROM health_facility ORDER BY facility_name";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $facilities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching health facilities: " . $e->getMessage());
    $facilities = [];
}

// Get statistics
try {
    $stats_query = "SELECT 
        COUNT(*) as total_facilities,
        SUM(total_invoice_amount) as total_credit,
        SUM(paid_amount) as total_paid,
        SUM(remaining_balance) as total_remaining,
        COUNT(CASE WHEN payment_status = 'unpaid' THEN 1 END) as unpaid_count,
        COUNT(CASE WHEN payment_status = 'partial' THEN 1 END) as partial_count,
        COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_count
    FROM health_facility";
    $stats_stmt = $pdo->prepare($stats_query);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching statistics: " . $e->getMessage());
    $stats = [];
}

$page_title = t('health_facilities');
include '../includes/header.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0"><?php echo t('health_facilities'); ?></h1>
        </div>
        <div class="col-sm-6">
          <div class="float-sm-right">
            <a href="register.php" class="btn btn-primary">
              <i class="fas fa-plus"></i> <?php echo t('add_facility'); ?>
            </a>
            <a href="import_facilities.php" class="btn btn-secondary">
              <i class="fas fa-file-import"></i> <?php echo t('import_facilities'); ?>
            </a>
            <a href="export_facilities.php" class="btn btn-success">
              <i class="fas fa-file-export"></i> <?php echo t('export_facilities'); ?>
            </a>
            <a href="reports.php" class="btn btn-info">
              <i class="fas fa-chart-bar"></i> <?php echo t('reports'); ?>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <!-- Statistics Cards -->
      <div class="row">
        <div class="col-lg-3 col-6">
          <div class="small-box bg-info">
            <div class="inner">
              <h3><?php echo number_format($stats['total_facilities'] ?? 0); ?></h3>
              <p><?php echo t('total_facilities'); ?></p>
            </div>
            <div class="icon">
              <i class="fas fa-hospital"></i>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box bg-success">
            <div class="inner">
              <h3><?php echo format_currency($stats['total_credit'] ?? 0); ?></h3>
              <p><?php echo t('total_credit'); ?></p>
            </div>
            <div class="icon">
              <i class="fas fa-dollar-sign"></i>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box bg-warning">
            <div class="inner">
              <h3><?php echo format_currency($stats['total_paid'] ?? 0); ?></h3>
              <p><?php echo t('total_paid'); ?></p>
            </div>
            <div class="icon">
              <i class="fas fa-money-bill-wave"></i>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box bg-danger">
            <div class="inner">
              <h3><?php echo format_currency($stats['total_remaining'] ?? 0); ?></h3>
              <p><?php echo t('total_remaining'); ?></p>
            </div>
            <div class="icon">
              <i class="fas fa-balance-scale"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Facilities Table -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title"><?php echo t('health_facilities_list'); ?></h3>
          <div class="card-tools">
            <div class="input-group input-group-sm">
              <select id="statusFilter" class="form-control mr-2">
                <option value=""><?php echo t('all_statuses'); ?></option>
                <option value="unpaid"><?php echo t('unpaid'); ?></option>
                <option value="partial"><?php echo t('partial'); ?></option>
                <option value="paid"><?php echo t('paid'); ?></option>
              </select>
              <input type="text" id="searchInput" class="form-control"
                placeholder="<?php echo t('search_facilities'); ?>">
            </div>
          </div>
        </div>
        <div class="card-body table-responsive p-0">
          <table class="table table-hover text-nowrap">
            <thead>
              <tr>
                <th><?php echo t('facility_name'); ?></th>
                <th><?php echo t('invoice_number'); ?></th>
                <th><?php echo t('invoice_date'); ?></th>
                <th><?php echo t('total_amount'); ?></th>
                <th><?php echo t('paid_amount'); ?></th>
                <th><?php echo t('remaining_balance'); ?></th>
                <th><?php echo t('status'); ?></th>
                <th><?php echo t('actions'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($facilities as $facility): ?>
              <tr>
                <td>
                  <div class="facility-info">
                    <div class="facility-name"><?php echo htmlspecialchars($facility['facility_name']); ?></div>
                    <?php if ($facility['email'] || $facility['phone']): ?>
                    <div class="facility-contact">
                      <?php if ($facility['email']): ?>
                      <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($facility['email']); ?></span>
                      <?php endif; ?>
                      <?php if ($facility['phone']): ?>
                      <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($facility['phone']); ?></span>
                      <?php endif; ?>
                    </div>
                    <?php endif; ?>
                  </div>
                </td>
                <td><?php echo htmlspecialchars($facility['credit_invoice_number']); ?></td>
                <td><?php echo format_date($facility['invoice_date']); ?></td>
                <td><?php echo format_currency($facility['total_invoice_amount']); ?></td>
                <td><?php echo format_currency($facility['paid_amount']); ?></td>
                <td><?php echo format_currency($facility['remaining_balance']); ?></td>
                <td>
                  <span class="badge badge-<?php echo get_status_color($facility['payment_status']); ?>">
                    <?php echo t($facility['payment_status']); ?>
                  </span>
                </td>
                <td>
                  <div class="btn-group">
                    <a href="view.php?id=<?php echo $facility['id']; ?>" class="btn btn-sm btn-info">
                      <i class="fas fa-eye"></i>
                    </a>
                    <a href="edit.php?id=<?php echo $facility['id']; ?>" class="btn btn-sm btn-primary">
                      <i class="fas fa-edit"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-danger delete-facility"
                      data-id="<?php echo $facility['id']; ?>"
                      data-name="<?php echo htmlspecialchars($facility['facility_name']); ?>">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel"
  aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel"><?php echo t('confirm_deletion'); ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <?php echo t('delete_facility_confirm'); ?> <span id="facilityName"></span>?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo t('cancel'); ?></button>
        <form id="deleteForm" action="delete.php" method="POST" style="display: inline;">
          <input type="hidden" name="id" id="facilityId">
          <button type="submit" class="btn btn-danger"><?php echo t('delete'); ?></button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
  // Search functionality
  $("#searchInput").on("keyup", function() {
    filterTable();
  });

  // Status filter functionality
  $("#statusFilter").on("change", function() {
    filterTable();
  });

  function filterTable() {
    var searchValue = $("#searchInput").val().toLowerCase();
    var statusValue = $("#statusFilter").val();

    $("table tbody tr").filter(function() {
      var row = $(this);
      var matchesSearch = row.text().toLowerCase().indexOf(searchValue) > -1;
      var matchesStatus = !statusValue || row.find('.badge').text().trim() === statusValue;
      row.toggle(matchesSearch && matchesStatus);
    });
  }

  // Delete modal
  $('.delete-facility').click(function() {
    var id = $(this).data('id');
    var name = $(this).data('name');
    $('#facilityId').val(id);
    $('#facilityName').text(name);
    $('#deleteModal').modal('show');
  });
});

// Helper function for status colors
function get_status_color(status) {
  switch (status) {
    case 'paid':
      return 'success';
    case 'partial':
      return 'warning';
    case 'unpaid':
      return 'danger';
    default:
      return 'secondary';
  }
}
</script>

<style>
.facility-info {
  display: flex;
  flex-direction: column;
}

.facility-name {
  font-weight: 500;
}

.facility-contact {
  font-size: 0.85em;
  color: #666;
}

.facility-contact span {
  margin-right: 1rem;
}

.facility-contact i {
  margin-right: 0.25rem;
}
</style>

<?php include '../includes/footer.php'; ?>