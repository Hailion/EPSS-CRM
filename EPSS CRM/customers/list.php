<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validate CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }
}

// Sanitize and validate input parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(10, min(100, intval($_GET['per_page']))) : 25;
$search = isset($_GET['search']) ? htmlspecialchars(trim($_GET['search'])) : '';
$status_filter = isset($_GET['status']) ? htmlspecialchars(trim($_GET['status'])) : 'all';
$zone_filter = isset($_GET['zone']) ? htmlspecialchars(trim($_GET['zone'])) : 'all';
$level_filter = isset($_GET['level']) ? htmlspecialchars(trim($_GET['level'])) : 'all';
$hub_filter = isset($_GET['hub']) ? htmlspecialchars(trim($_GET['hub'])) : 'all';
$woreda_filter = isset($_GET['woreda']) ? htmlspecialchars(trim($_GET['woreda'])) : 'all';

// Prepare base query with proper indexing hints
$base_query = "
    SELECT SQL_CALC_FOUND_ROWS c.*, 
           c.credit_invoice_number, 
           c.region,
           c.woreda,
           c.level,
           c.hub,
           c.credit_balance,
           c.status,
           c.created_at,
           c.updated_at,
           COUNT(t.id) as total_transactions,
           SUM(CASE WHEN t.status IN ('pending', 'active', 'overdue') THEN 1 ELSE 0 END) as unpaid_transactions,
           SUM(CASE WHEN t.status = 'paid' THEN 1 ELSE 0 END) as paid_transactions,
           SUM(CASE WHEN t.status IN ('pending', 'active', 'overdue') THEN t.amount ELSE 0 END) as total_unpaid_amount,
           MAX(p.payment_date) as last_payment_date,
           MIN(t.credit_date) as first_credit_date,
           SUM(CASE WHEN t.status = 'overdue' THEN 1 ELSE 0 END) as overdue_transactions
    FROM customers c
    LEFT JOIN transactions t ON c.id = t.customer_id
    LEFT JOIN payments p ON t.id = p.transaction_id
    WHERE 1=1
";

// Add search condition if search parameter is provided
if (!empty($search)) {
    $base_query .= " AND (c.name LIKE :search OR c.id LIKE :search OR c.credit_invoice_number LIKE :search)";
}

// Add status filter if specified
if ($status_filter !== 'all') {
    $base_query .= " AND EXISTS (
        SELECT 1 FROM transactions t2 
        WHERE t2.customer_id = c.id 
        AND t2.status = :status
    )";
}

// Add zone filter if specified
if ($zone_filter !== 'all') {
    $base_query .= " AND c.region = :zone";
}

// Add level filter if specified
if ($level_filter !== 'all') {
    $base_query .= " AND c.level = :level";
}

// Add hub filter if specified
if ($hub_filter !== 'all') {
    $base_query .= " AND c.hub = :hub";
}

// Add woreda filter if specified
if ($woreda_filter !== 'all') {
    $base_query .= " AND c.woreda = :woreda";
}

$base_query .= " GROUP BY c.id";

// Add pagination
$base_query .= " LIMIT :offset, :per_page";

// Prepare and execute the query
$stmt = $pdo->prepare($base_query);

// Bind parameters with correct types
$offset = ($page - 1) * $per_page;
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);

if (!empty($search)) {
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
}

if ($status_filter !== 'all') {
    $stmt->bindValue(':status', $status_filter, PDO::PARAM_STR);
}

if ($zone_filter !== 'all') {
    $stmt->bindValue(':zone', $zone_filter, PDO::PARAM_STR);
}

if ($level_filter !== 'all') {
    $stmt->bindValue(':level', $level_filter, PDO::PARAM_STR);
}

if ($hub_filter !== 'all') {
    $stmt->bindValue(':hub', $hub_filter, PDO::PARAM_STR);
}

if ($woreda_filter !== 'all') {
    $stmt->bindValue(':woreda', $woreda_filter, PDO::PARAM_STR);
}

$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$total_count = $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
$total_pages = ceil($total_count / $per_page);

// Calculate summary statistics
$total_customers = $total_count;
$total_credit_balance = array_sum(array_column($customers, 'credit_balance'));
$total_unpaid_amount = array_sum(array_column($customers, 'total_unpaid_amount'));
$active_customers = array_filter($customers, function($customer) {
    return !is_null($customer['last_payment_date']) && 
           strtotime($customer['last_payment_date']) > strtotime('-30 days');
});
$active_customers_count = count($active_customers);
$total_overdue = array_sum(array_column($customers, 'overdue_transactions'));

// Get unique values for filters
$zones = $pdo->query("SELECT DISTINCT region FROM customers ORDER BY region")->fetchAll(PDO::FETCH_COLUMN);
$levels = $pdo->query("SELECT DISTINCT level FROM customers ORDER BY level")->fetchAll(PDO::FETCH_COLUMN);
$hubs = $pdo->query("SELECT DISTINCT hub FROM customers ORDER BY hub")->fetchAll(PDO::FETCH_COLUMN);
$woredas = $pdo->query("SELECT DISTINCT woreda FROM customers ORDER BY woreda")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Health Facilities - Credit Management System</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/customers.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
  /* Search and Filters Styles */
  .search-filters {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    margin-bottom: 20px;
    padding: 15px;
  }

  .filter-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
  }

  .search-box {
    position: relative;
    flex: 1;
    max-width: 400px;
  }

  .search-box input {
    width: 100%;
    padding: 10px 15px 10px 40px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.3s ease;
  }

  .search-box input:focus {
    border-color: #4a90e2;
    box-shadow: 0 0 0 2px rgba(74,144,226,0.1);
    outline: none;
  }

  .search-box i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #666;
  }

  .filter-actions {
    display: flex;
    gap: 10px;
  }

  .btn-toggle-filters,
  .btn-reset {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 15px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    background: #fff;
    color: #333;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .btn-toggle-filters:hover,
  .btn-reset:hover {
    background: #f5f5f5;
    border-color: #d0d0d0;
  }

  .btn-toggle-filters i,
  .btn-reset i {
    font-size: 14px;
  }

  .filter-count {
    background: #4a90e2;
    color: #fff;
    padding: 2px 6px;
    border-radius: 12px;
    font-size: 12px;
    min-width: 20px;
    text-align: center;
  }

  .filter-content {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #f0f0f0;
  }

  .filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
  }

  .filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .filter-group label {
    font-size: 12px;
    color: #666;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .filter-group label i {
    font-size: 12px;
  }

  .filter-group select {
    padding: 8px 12px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    font-size: 14px;
    color: #333;
    background: #fff;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .filter-group select:hover {
    border-color: #d0d0d0;
  }

  .filter-group select:focus {
    border-color: #4a90e2;
    box-shadow: 0 0 0 2px rgba(74,144,226,0.1);
    outline: none;
  }

  .active-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #f0f0f0;
  }

  .filter-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: #f0f7ff;
    border: 1px solid #e0e0e0;
    border-radius: 16px;
    font-size: 12px;
    color: #4a90e2;
  }

  .filter-tag i {
    font-size: 12px;
    cursor: pointer;
    transition: color 0.3s ease;
  }

  .filter-tag i:hover {
    color: #e74c3c;
  }

  /* Responsive adjustments */
  @media (max-width: 768px) {
    .header-actions-wrapper {
      padding: 16px;
    }

    .title-meta {
      flex-direction: column;
      align-items: flex-start;
      gap: 12px;
    }

    .filter-header {
      flex-direction: column;
      align-items: stretch;
    }
    
    .search-box {
      max-width: none;
    }
    
    .filter-grid {
      grid-template-columns: 1fr;
    }

    .action-buttons-group {
      flex-direction: column;
    }

    .action-buttons-group .btn {
      width: 100%;
      justify-content: center;
    }
  }

  /* Modal Styles */
  .modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
  }

  .modal-content {
    background-color: #fff;
    margin: 10% auto;
    padding: 20px;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    position: relative;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  }

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e0e0e0;
  }

  .modal-header h3 {
    margin: 0;
    color: #333;
    font-size: 1.2rem;
  }

  .close-modal {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #666;
    cursor: pointer;
    padding: 0;
    line-height: 1;
  }

  .close-modal:hover {
    color: #333;
  }

  .modal-body {
    padding: 20px 0;
  }

  .form-group {
    margin-bottom: 20px;
  }

  .form-group label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 500;
  }

  .form-group input[type="file"] {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 8px;
  }

  .form-text {
    font-size: 0.85rem;
    color: #666;
    line-height: 1.4;
  }

  /* Button Styles */
  .action-buttons-group {
    display: flex;
    gap: 12px;
    margin-top: 20px;
  }

  .btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
  }

  .btn-outline {
    background: #fff;
    border: 1px solid #e0e0e0;
    color: #666;
  }

  .btn-outline:hover {
    background: #f5f5f5;
    border-color: #d0d0d0;
    color: #333;
  }

  .btn-primary {
    background: #4a90e2;
    border: none;
    color: #fff;
  }

  .btn-primary:hover {
    background: #357abd;
  }

  .btn i {
    font-size: 14px;
  }

  /* Responsive adjustments */
  @media (max-width: 768px) {
    .action-buttons-group {
      flex-direction: column;
    }
    
    .btn {
      width: 100%;
      justify-content: center;
    }
    
    .modal-content {
      width: 95%;
      margin: 5% auto;
    }
  }

  /* Import Modal Styles */
  .import-instructions {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
  }

  .instruction-section {
    margin-bottom: 20px;
  }

  .instruction-section h4 {
    color: #2c3e50;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .instruction-section ol {
    margin: 0;
    padding-left: 20px;
  }

  .instruction-section li {
    margin-bottom: 8px;
    color: #666;
  }

  .template-section {
    text-align: center;
    padding: 15px;
    background: #fff;
    border-radius: 6px;
    border: 1px dashed #ddd;
  }

  .required-fields {
    margin-bottom: 20px;
  }

  .required-fields h4 {
    color: #2c3e50;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .fields-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    background: #fff;
    padding: 15px;
    border-radius: 6px;
    border: 1px solid #e0e0e0;
  }

  .field-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .field-name {
    font-weight: 500;
    color: #2c3e50;
  }

  .field-desc {
    font-size: 0.85rem;
    color: #666;
  }

  .file-input-wrapper {
    margin-top: 8px;
  }

  .file-input-preview {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    margin-top: 8px;
  }

  .file-input-preview span {
    color: #666;
  }

  .import-options {
    margin: 15px 0;
  }

  .checkbox-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #666;
    font-size: 0.9rem;
  }

  .progress-wrapper {
    margin: 15px 0;
  }

  .progress-bar {
    height: 6px;
    background: #e0e0e0;
    border-radius: 3px;
    overflow: hidden;
  }

  .progress-fill {
    height: 100%;
    background: #4a90e2;
    width: 0;
    transition: width 0.3s ease;
  }

  .progress-status {
    text-align: center;
    color: #666;
    font-size: 0.9rem;
    margin-top: 8px;
  }

  .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #e0e0e0;
  }

  .btn-secondary {
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    color: #666;
  }

  .btn-secondary:hover {
    background: #e9ecef;
  }

  /* Responsive adjustments */
  @media (max-width: 768px) {
    .fields-grid {
        grid-template-columns: 1fr;
    }
    
    .modal-footer {
        flex-direction: column;
    }
    
    .modal-footer .btn {
        width: 100%;
    }
  }
  </style>
</head>

<body>
  <?php include '../includes/header.php'; ?>

  <div class="container">
    <!-- Summary Statistics -->
    <div class="stats-grid">
      <div class="stat-card primary">
        <div class="stat-icon">
          <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
          <h3>Total health facilities</h3>
          <div class="stat-number"><?php echo $total_customers; ?></div>
          <div class="stat-subtitle">Registered health facilities</div>
        </div>
      </div>
      <div class="stat-card success">
        <div class="stat-icon">
          <i class="fas fa-user-check"></i>
        </div>
        <div class="stat-content">
          <h3>Active health facilities</h3>
          <div class="stat-number"><?php echo $active_customers_count; ?></div>
          <div class="stat-subtitle">Active in last 30 days</div>
        </div>
      </div>
      <div class="stat-card info">
        <div class="stat-icon">
          <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="stat-content">
          <h3>Total Credit Balance</h3>
          <div class="stat-number">$<?php echo number_format($total_credit_balance, 2); ?></div>
          <div class="stat-subtitle">Outstanding balance</div>
        </div>
      </div>
      <div class="stat-card warning">
        <div class="stat-icon">
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
          <h3>Total Unpaid Amount</h3>
          <div class="stat-number">$<?php echo number_format($total_unpaid_amount, 2); ?></div>
          <div class="stat-subtitle">Pending payments</div>
        </div>
      </div>
      <div class="stat-card danger">
        <div class="stat-icon">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-content">
          <h3>Overdue Transactions</h3>
          <div class="stat-number"><?php echo $total_overdue; ?></div>
          <div class="stat-subtitle">Need immediate attention</div>
        </div>
      </div>
    </div>

    <!-- Header Actions Wrapper -->
    <div class="header-actions-wrapper">
      <div class="header-title">
        <div class="title-section">
          <h2><i class="fas fa-users"></i> Health Facilities</h2>
          <div class="title-meta">
            <span class="total-count"><?php echo $total_customers; ?> total facilities</span>
            <div class="quick-stats">
              <div class="quick-stat">
                <i class="fas fa-user-check text-success"></i>
                <span><?php echo $active_customers_count; ?> active</span>
              </div>
              <div class="quick-stat">
                <i class="fas fa-clock text-warning"></i>
                <span><?php echo array_sum(array_column($customers, 'unpaid_transactions')); ?> pending</span>
              </div>
              <div class="quick-stat">
                <i class="fas fa-exclamation-circle text-danger"></i>
                <span><?php echo $total_overdue; ?> overdue</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="header-actions">
        <!-- Search and Filters -->
        <div class="search-filters">
          <div class="filter-header">
          <div class="search-box">
              <input type="text" id="customerSearch" placeholder="Search health facilities..." 
                     value="<?php echo htmlspecialchars($search); ?>"
                     onkeyup="debounceSearch()">
            <i class="fas fa-search"></i>
          </div>
            <div class="filter-actions">
              <button class="btn-toggle-filters" onclick="toggleFilters()">
                <i class="fas fa-filter"></i>
                <span>Filters</span>
                <span class="filter-count" id="filterCount">0</span>
              </button>
              <button class="btn-reset" onclick="resetFilters()">
                <i class="fas fa-undo"></i>
                <span>Reset</span>
              </button>
            </div>
          </div>

          <div id="filterContent" class="filter-content" style="display: none;">
            <div class="filter-grid">
            <div class="filter-group">
                <label><i class="fas fa-filter"></i> Status</label>
                <select id="statusFilter" onchange="handleFilterChange('status', this.value)">
                  <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                  <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                  <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                  <option value="overdue" <?php echo $status_filter === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
              </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-map-marker-alt"></i> Zone</label>
                <select id="zoneFilter" onchange="handleFilterChange('zone', this.value)">
                  <option value="all">All Zones</option>
                  <?php foreach ($zones as $zone): ?>
                    <option value="<?php echo htmlspecialchars($zone); ?>" <?php echo $zone_filter === $zone ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($zone); ?>
                    </option>
                  <?php endforeach; ?>
              </select>
            </div>
              <div class="filter-group">
                <label><i class="fas fa-hospital"></i> Level</label>
                <select id="levelFilter" onchange="handleFilterChange('level', this.value)">
                  <option value="all">All Levels</option>
                  <?php foreach ($levels as $level): ?>
                    <option value="<?php echo htmlspecialchars($level); ?>" <?php echo $level_filter === $level ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($level); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
          </div>
              <div class="filter-group">
                <label><i class="fas fa-hub"></i> Hub</label>
                <select id="hubFilter" onchange="handleFilterChange('hub', this.value)">
                  <option value="all">All Hubs</option>
                  <?php foreach ($hubs as $hub): ?>
                    <option value="<?php echo htmlspecialchars($hub); ?>" <?php echo $hub_filter === $hub ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($hub); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
        </div>
              <div class="filter-group">
                <label><i class="fas fa-building"></i> Woreda</label>
                <select id="woredaFilter" onchange="handleFilterChange('woreda', this.value)">
                  <option value="all">All Woredas</option>
                  <?php foreach ($woredas as $woreda): ?>
                    <option value="<?php echo htmlspecialchars($woreda); ?>" <?php echo $woreda_filter === $woreda ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($woreda); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="filter-group">
                <label><i class="fas fa-list-ol"></i> Per Page</label>
                <select id="perPageFilter" onchange="handleFilterChange('per_page', this.value)">
                  <option value="25" <?php echo $per_page === 25 ? 'selected' : ''; ?>>25 per page</option>
                  <option value="50" <?php echo $per_page === 50 ? 'selected' : ''; ?>>50 per page</option>
                  <option value="100" <?php echo $per_page === 100 ? 'selected' : ''; ?>>100 per page</option>
                </select>
              </div>
            </div>

            <div class="active-filters" id="activeFilters"></div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons-group">
          <button class="btn btn-outline" onclick="exportCustomers()" title="Export filtered health facilities">
            <i class="fas fa-download"></i>
            <span>Export</span>
          </button>
          <button class="btn btn-outline" onclick="showImportModal()" title="Import health facilities from CSV">
            <i class="fas fa-upload"></i>
            <span>Import</span>
          </button>
          <a href="register.php" class="btn btn-primary" title="Register a new health facility">
            <i class="fas fa-plus"></i>
            <span>Add New Health Facility</span>
          </a>
        </div>
      </div>
    </div>

    <div class="table-responsive card">
      <table class="table" id="customersTable">
        <thead>
          <tr>
            <th>Health Facility Info</th>
            <th>Location</th>
            <th>Level</th>
            <th>Credit Invoice Number</th>
            <th>Financial Status</th>
            <th>Transaction History</th>
            <th>Registration Info</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($customers as $customer): ?>
          <?php 
            $status = '';
            if ($customer['overdue_transactions'] > 0) {
              $status = 'overdue';
            } elseif ($customer['unpaid_transactions'] > 0) {
              $status = 'pending';
            } else {
              $status = 'active';
            }
          ?>
          <tr data-status="<?php echo $status; ?>">
            <td>
              <div class="customer-info">
                <div class="customer-avatar">
                  <?php echo strtoupper(substr($customer['name'], 0, 2)); ?>
                </div>
                <div class="customer-details">
                  <div class="customer-name"><?php echo htmlspecialchars($customer['name']); ?></div>
                  <div class="customer-id">ID: #<?php echo $customer['id']; ?></div>
                  <span class="status-badge status-<?php echo $status; ?>">
                    <i class="fas fa-circle"></i>
                    <?php echo ucfirst($status); ?>
                  </span>
                </div>
              </div>
            </td>
            <td>
              <div class="location-info">
                <div class="region">
                  <i class="fas fa-map-marker-alt"></i>
                  <strong>Region:</strong> <?php echo htmlspecialchars($customer['region'] ?? 'N/A'); ?>
                </div>
                <div class="woreda">
                  <i class="fas fa-building"></i>
                  <strong>Woreda:</strong> <?php echo htmlspecialchars($customer['woreda'] ?? 'N/A'); ?>
                </div>
                <div class="hub">
                  <i class="fas fa-hub"></i>
                  <strong>Hub:</strong> <?php echo htmlspecialchars($customer['hub'] ?? 'N/A'); ?>
                </div>
              </div>
            </td>
            <td>
              <div class="level-info">
                <i class="fas fa-hospital"></i>
                <span><?php echo htmlspecialchars($customer['level'] ?? 'N/A'); ?></span>
              </div>
            </td>
            <td>
              <?php echo htmlspecialchars($customer['credit_invoice_number'] ?? 'N/A'); ?>
            </td>
            <td>
              <div class="financial-status">
                <div class="credit-balance">
                  <strong>Credit Balance:</strong>
                  <span class="amount <?php echo $customer['credit_balance'] < 0 ? 'text-danger' : 'text-success'; ?>">
                    $<?php echo number_format($customer['credit_balance'], 2); ?>
                  </span>
                </div>
                <div class="unpaid-amount">
                  <strong>Unpaid Amount:</strong>
                  <span class="amount text-danger">
                    $<?php echo number_format($customer['total_unpaid_amount'] ?? 0, 2); ?>
                  </span>
                </div>
                <div class="progress-bar">
                  <div class="progress" style="width: <?php 
                    echo $customer['credit_balance'] > 0 ? 
                      min(100, ($customer['total_unpaid_amount'] / $customer['credit_balance']) * 100) : 
                      100; 
                  ?>%"></div>
                </div>
              </div>
            </td>
            <td>
              <div class="transaction-history">
                <div class="transaction-counts">
                  <span class="total">
                    <i class="fas fa-list"></i>
                    Total: <?php echo $customer['total_transactions']; ?>
                  </span>
                  <span class="unpaid text-danger">
                    <i class="fas fa-clock"></i>
                    Unpaid: <?php echo $customer['unpaid_transactions']; ?>
                  </span>
                  <span class="paid text-success">
                    <i class="fas fa-check-circle"></i>
                    Paid: <?php echo $customer['paid_transactions']; ?>
                  </span>
                </div>
                <?php if (!is_null($customer['last_payment_date'])): ?>
                <div class="last-transaction">
                  <i class="fas fa-calendar-alt"></i>
                  <small>Last payment:
                    <?php echo date('M d, Y', strtotime($customer['last_payment_date'])); ?></small>
                </div>
                <?php else: ?>
                <div class="last-transaction">
                  <i class="fas fa-exclamation-circle"></i>
                  <small>No payments yet</small>
                </div>
                <?php endif; ?>
              </div>
            </td>
            <td>
              <div class="registration-info">
                <div class="created-at">
                  <i class="fas fa-user-plus"></i>
                  <strong>Created:</strong>
                  <span><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></span>
                </div>
                <div class="updated-at">
                  <i class="fas fa-clock"></i>
                  <strong>Updated:</strong>
                  <span><?php echo date('M d, Y', strtotime($customer['updated_at'])); ?></span>
                </div>
              </div>
            </td>
            <td>
              <div class="action-buttons">
                <a href="view.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-info"
                  title="View Customer Details">
                  <i class="fas fa-eye"></i>
                </a>
                <a href="edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-warning"
                  title="Edit Customer">
                  <i class="fas fa-edit"></i>
                </a>
                <a href="../transactions/add_credit.php?customer_id=<?php echo $customer['id']; ?>"
                  class="btn btn-sm btn-success" title="Add New Credit">
                  <i class="fas fa-plus-circle"></i>
                </a>
                <button class="btn btn-sm btn-danger" title="Delete Customer"
                  onclick="confirmDelete(<?php echo $customer['id']; ?>, '<?php echo addslashes(htmlspecialchars($customer['name'])); ?>')">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&per_page=<?php echo $per_page; ?>" class="btn btn-outline">
          <i class="fas fa-chevron-left"></i> Previous
        </a>
      <?php endif; ?>
      
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&per_page=<?php echo $per_page; ?>" 
           class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?>">
          <?php echo $i; ?>
        </a>
      <?php endfor; ?>
      
      <?php if ($page < $total_pages): ?>
        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&per_page=<?php echo $per_page; ?>" class="btn btn-outline">
          Next <i class="fas fa-chevron-right"></i>
        </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <?php include '../includes/footer.php'; ?>

  <!-- Import Modal -->
  <div id="importModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
            <h3><i class="fas fa-file-import"></i> Import Health Facilities</h3>
        <button class="close-modal" onclick="closeImportModal()">&times;</button>
      </div>
      <div class="modal-body">
        <div class="import-instructions">
                <div class="instruction-section">
                    <h4><i class="fas fa-info-circle"></i> Instructions</h4>
                    <ol>
                        <li>Download the template file using the button below</li>
                        <li>Fill in the required information in the template</li>
                        <li>Save the file as CSV format</li>
                        <li>Upload the completed file using the form below</li>
                    </ol>
        </div>
                <div class="template-section">
                    <a href="download_template.php?csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-outline">
                        <i class="fas fa-download"></i> Download Template
                    </a>
                </div>
            </div>

            <div class="required-fields">
                <h4><i class="fas fa-asterisk"></i> Required Fields</h4>
                <div class="fields-grid">
                    <div class="field-item">
                        <span class="field-name">Facility ID</span>
                        <span class="field-desc">Unique identifier for the health facility</span>
                    </div>
                    <div class="field-item">
                        <span class="field-name">Name</span>
                        <span class="field-desc">Full name of the health facility</span>
                    </div>
                    <div class="field-item">
                        <span class="field-name">Region</span>
                        <span class="field-desc">Region where the facility is located</span>
                    </div>
                    <div class="field-item">
                        <span class="field-name">Hub</span>
                        <span class="field-desc">Hub associated with the facility</span>
                    </div>
                    <div class="field-item">
                        <span class="field-name">Woreda</span>
                        <span class="field-desc">Woreda where the facility is located</span>
                    </div>
                    <div class="field-item">
                        <span class="field-name">Zone</span>
                        <span class="field-desc">Zone where the facility is located</span>
                    </div>
                    <div class="field-item">
                        <span class="field-name">Level</span>
                        <span class="field-desc">Facility level (e.g., Hospital, Health Center)</span>
                    </div>
                    <div class="field-item">
                        <span class="field-name">Credit Invoice Number</span>
                        <span class="field-desc">Unique invoice number for credit tracking</span>
                    </div>
                    <div class="field-item">
                        <span class="field-name">Credit Balance</span>
                        <span class="field-desc">Initial credit balance (numeric value)</span>
                    </div>
                    <div class="field-item">
                        <span class="field-name">Status</span>
                        <span class="field-desc">Facility status (active/inactive)</span>
                    </div>
                    <div class="field-item">
                        <span class="field-name">Credit Date</span>
                        <span class="field-desc">Date when credit was issued (YYYY-MM-DD)</span>
                    </div>
                    <div class="field-item">
                        <span class="field-name">Due Date</span>
                        <span class="field-desc">Date when credit is due (YYYY-MM-DD)</span>
                    </div>
                </div>
            </div>

        <form id="importForm" action="import_customers.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
          <div class="form-group">
                    <label for="csv_file">
                        <i class="fas fa-file-csv"></i> Select CSV File
                    </label>
            <div class="file-input-wrapper">
              <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
              <div class="file-input-preview">
                <span id="fileName">No file selected</span>
                            <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('csv_file').click()">
                  Browse
                </button>
              </div>
            </div>
          </div>
          <div class="import-options">
            <label class="checkbox-wrapper">
              <input type="checkbox" name="skip_duplicates" checked>
                        <span>Skip duplicate entries (based on Facility ID)</span>
            </label>
          </div>
          <div id="importProgress" class="progress-wrapper" style="display: none;">
            <div class="progress-bar">
              <div class="progress-fill"></div>
            </div>
            <div class="progress-status">Processing...</div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeImportModal()">Cancel</button>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-upload"></i> Import
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
  let filterTimeout;
  const filterState = {
    status: 'all',
    zone: 'all',
    level: 'all',
    hub: 'all',
    woreda: 'all',
    per_page: 25
  };

  function toggleFilters() {
    const filterContent = document.getElementById('filterContent');
    filterContent.style.display = filterContent.style.display === 'none' ? 'block' : 'none';
  }

  function handleFilterChange(type, value) {
    filterState[type] = value;
    updateFilterCount();
    updateActiveFilters();
    applyFilters();
  }

  function updateFilterCount() {
    const activeFilters = Object.values(filterState).filter(value => value !== 'all').length;
    document.getElementById('filterCount').textContent = activeFilters;
  }

  function updateActiveFilters() {
    const activeFiltersContainer = document.getElementById('activeFilters');
    activeFiltersContainer.innerHTML = '';

    Object.entries(filterState).forEach(([type, value]) => {
      if (value !== 'all') {
        const filterTag = document.createElement('div');
        filterTag.className = 'filter-tag';
        filterTag.innerHTML = `
          <span>${type.charAt(0).toUpperCase() + type.slice(1)}: ${value}</span>
          <i class="fas fa-times" onclick="removeFilter('${type}')"></i>
        `;
        activeFiltersContainer.appendChild(filterTag);
      }
    });
  }

  function removeFilter(type) {
    filterState[type] = 'all';
    document.getElementById(`${type}Filter`).value = 'all';
    updateFilterCount();
    updateActiveFilters();
    applyFilters();
  }

  function resetFilters() {
    Object.keys(filterState).forEach(type => {
      filterState[type] = 'all';
      const select = document.getElementById(`${type}Filter`);
      if (select) select.value = 'all';
    });
    updateFilterCount();
    updateActiveFilters();
    applyFilters();
  }

  function debounceSearch() {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
      applyFilters();
    }, 300);
  }

  function applyFilters() {
    const search = document.getElementById('customerSearch').value;
    const params = new URLSearchParams({
      page: '1',
      search: search,
      ...filterState
    });
    
    window.location.href = `list.php?${params.toString()}`;
  }

  // Initialize filter count and active filters on page load
  document.addEventListener('DOMContentLoaded', () => {
    updateFilterCount();
    updateActiveFilters();
  });

  function exportCustomers() {
    // Get current filter values
    const search = document.getElementById('customerSearch').value;
    const status = document.getElementById('statusFilter').value;
    const zone = document.getElementById('zoneFilter').value;
    const level = document.getElementById('levelFilter').value;
    const hub = document.getElementById('hubFilter').value;
    const woreda = document.getElementById('woredaFilter').value;
    
    // Build the export URL with current filters
    let exportUrl = 'export_customers.php?';
    if (search) exportUrl += `search=${encodeURIComponent(search)}&`;
    if (status !== 'all') exportUrl += `status=${encodeURIComponent(status)}&`;
    if (zone !== 'all') exportUrl += `zone=${encodeURIComponent(zone)}&`;
    if (level !== 'all') exportUrl += `level=${encodeURIComponent(level)}&`;
    if (hub !== 'all') exportUrl += `hub=${encodeURIComponent(hub)}&`;
    if (woreda !== 'all') exportUrl += `woreda=${encodeURIComponent(woreda)}&`;
    
    // Remove trailing & if exists
    exportUrl = exportUrl.replace(/&$/, '');
    
    // Trigger the download
    window.location.href = exportUrl;
  }

  function showImportModal() {
    document.getElementById('importModal').style.display = 'block';
  }

  function closeImportModal() {
    document.getElementById('importModal').style.display = 'none';
  }

  // Close modal when clicking outside
  window.onclick = function(event) {
    const modal = document.getElementById('importModal');
    if (event.target === modal) {
      closeImportModal();
    }
  }

  // Update the file input preview
  document.getElementById('csv_file').addEventListener('change', function(e) {
    const fileName = e.target.files[0]?.name || 'No file selected';
    document.getElementById('fileName').textContent = fileName;
  });

  // Update the import form submission
  document.getElementById('importForm').addEventListener('submit', function(e) {
    const fileInput = document.getElementById('csv_file');
    const file = fileInput.files[0];
    
    if (!file) {
        e.preventDefault();
        alert('Please select a CSV file to import.');
        return;
    }
    
    if (!file.name.endsWith('.csv')) {
        e.preventDefault();
        alert('Please select a valid CSV file.');
        return;
    }
    
    // Show progress bar
    document.getElementById('importProgress').style.display = 'block';
    document.querySelector('.progress-fill').style.width = '100%';
  });

  // Add error alert close functionality
  document.addEventListener('DOMContentLoaded', function() {
    const closeButtons = document.querySelectorAll('.btn-close');
    closeButtons.forEach(button => {
      button.addEventListener('click', function() {
        const alert = this.closest('.alert');
        if (alert) {
          alert.style.display = 'none';
        }
      });
    });
  });

  function confirmDelete(customerId, customerName) {
    if (confirm(`Are you sure you want to delete health_facility "${customerName}" ? \n\nThis will also delete all associated transactions and cannot be undone.`)) {
      const loadingOverlay = document.createElement('div');
      loadingOverlay.className = 'loading-overlay';
      loadingOverlay.innerHTML = '<div class="spinner"></div>';
      document.body.appendChild(loadingOverlay);

      fetch(`delete.php?id=${customerId}`, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json'
          },
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const row = document.querySelector(`tr[data-customer-id="${customerId}"]`);
  if (row) {
    row.remove();
  }
          alert('health_facility deleted successfully');
  window.location.reload();
        } else {
    throw new Error(data.message || 'Failed to delete health_facility');
  }
      })
      .catch(error => {
          alert('Error: ' + error.message);
        })
        .finally(() => {
          document.body.removeChild(loadingOverlay);
        });
    }
  }
  </script>
</body>

</html>