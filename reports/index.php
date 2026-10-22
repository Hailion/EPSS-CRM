<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$min_amount = isset($_GET['min_amount']) ? (float)$_GET['min_amount'] : null;
$max_amount = isset($_GET['max_amount']) ? (float)$_GET['max_amount'] : null;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$region = isset($_GET['region']) ? $_GET['region'] : '';
$zone = isset($_GET['zone']) ? $_GET['zone'] : '';
$level = isset($_GET['level']) ? $_GET['level'] : '';

// Build WHERE clause for filters
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(c.name LIKE ? OR t.id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status) {
    $where_conditions[] = "t.status = ?";
    $params[] = $status;
}

if ($min_amount !== null && $min_amount > 0) {
    $where_conditions[] = "t.amount >= ?";
    $params[] = $min_amount;
}

if ($max_amount !== null && $max_amount > 0) {
    $where_conditions[] = "t.amount <= ?";
    $params[] = $max_amount;
}

if ($start_date) {
    $where_conditions[] = "DATE(t.created_at) >= ?";
    $params[] = $start_date;
}

if ($end_date) {
    $where_conditions[] = "DATE(t.created_at) <= ?";
    $params[] = $end_date;
}

if ($region && trim($region) !== '') {
    $where_conditions[] = "c.region = ?";
    $params[] = trim($region);
}

if ($zone && trim($zone) !== '') {
    $where_conditions[] = "c.Zone = ?";
    $params[] = trim($zone);
}

if ($level && trim($level) !== '') {
    $where_conditions[] = "c.level = ?";
    $params[] = trim($level);
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get summary statistics with filters
$stats = [];
$base_query = "
    SELECT COUNT(DISTINCT c.id) as total_customers,
           SUM(CASE WHEN c.status = 'active' THEN 1 ELSE 0 END) as active_customers,
           COALESCE(SUM(t.amount), 0) as total_credits,
           COALESCE(SUM(p.amount), 0) as total_payments,
           SUM(CASE WHEN t.status = 'overdue' THEN 1 ELSE 0 END) as overdue_transactions,
           COALESCE(SUM(CASE WHEN t.status = 'overdue' THEN t.amount - COALESCE(p.total_paid, 0) ELSE 0 END), 0) as total_overdue_amount
    FROM customers c
    LEFT JOIN transactions t ON c.id = t.customer_id
        LEFT JOIN (
        SELECT transaction_id, SUM(amount) as amount, SUM(amount) as total_paid
            FROM payments
            GROUP BY transaction_id
        ) p ON t.id = p.transaction_id
    " . ($where_clause ? $where_clause : "") . "
";

$stmt = $pdo->prepare($base_query);
$stmt->execute($params);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent transactions with filters
$stmt = $pdo->prepare("
    SELECT t.*, c.name as customer_name,
           COALESCE(SUM(p.amount), 0) as amount_paid,
           t.amount - COALESCE(SUM(p.amount), 0) as remaining_amount
    FROM transactions t
    JOIN customers c ON t.customer_id = c.id
    LEFT JOIN payments p ON t.id = p.transaction_id
    " . ($where_clause ? $where_clause : "") . "
    GROUP BY t.id
    ORDER BY t.created_at DESC
    LIMIT 5
");
$stmt->execute($params);
$recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get top customers by credit amount with filters
$stmt = $pdo->prepare("
    SELECT c.name, 
           COUNT(t.id) as total_transactions,
           SUM(t.amount) as total_credit,
           SUM(CASE WHEN t.status = 'overdue' THEN 1 ELSE 0 END) as overdue_transactions,
           COALESCE(SUM(p.amount), 0) as total_paid,
           c.region, c.Zone, c.level
    FROM customers c
    LEFT JOIN transactions t ON c.id = t.customer_id
    LEFT JOIN payments p ON t.id = p.transaction_id
    " . ($where_clause ? $where_clause : "") . "
    GROUP BY c.id, c.name, c.region, c.Zone, c.level
    ORDER BY total_credit DESC
    LIMIT 5
");
$stmt->execute($params);
$top_customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get monthly statistics with filters
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(t.created_at, '%Y-%m') as month,
        COUNT(t.id) as total_transactions,
        SUM(t.amount) as total_amount,
        SUM(CASE WHEN t.status = 'paid' THEN 1 ELSE 0 END) as paid_transactions,
        COALESCE(SUM(p.amount), 0) as total_payments,
        SUM(CASE WHEN t.status = 'overdue' THEN t.amount - COALESCE(p.amount, 0) ELSE 0 END) as overdue_amount,
        COUNT(DISTINCT c.id) as unique_customers,
        SUM(CASE WHEN t.status = 'overdue' THEN 1 ELSE 0 END) as overdue_count
    FROM transactions t
    JOIN customers c ON t.customer_id = c.id
    LEFT JOIN (
        SELECT transaction_id, SUM(amount) as amount
        FROM payments
        GROUP BY transaction_id
    ) p ON t.id = p.transaction_id
    WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    " . ($where_clause ? " AND " . substr($where_clause, 5) : "") . "
    GROUP BY DATE_FORMAT(t.created_at, '%Y-%m')
    ORDER BY month DESC
");
$stmt->execute($params);
$monthly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique values for filters
$regions = $pdo->query("SELECT DISTINCT region FROM customers WHERE region IS NOT NULL ORDER BY region")->fetchAll(PDO::FETCH_COLUMN);
$zones = $pdo->query("SELECT DISTINCT Zone FROM customers WHERE Zone IS NOT NULL ORDER BY Zone")->fetchAll(PDO::FETCH_COLUMN);
$levels = $pdo->query("SELECT DISTINCT level FROM customers WHERE level IS NOT NULL ORDER BY level")->fetchAll(PDO::FETCH_COLUMN);

// Add filter summary
$filter_summary = [];
if ($search) $filter_summary[] = "Search: " . htmlspecialchars($search);
if ($status) $filter_summary[] = "Status: " . ucfirst($status);
if ($min_amount !== null) $filter_summary[] = "Min Amount: " . format_Currency($min_amount);
if ($max_amount !== null) $filter_summary[] = "Max Amount: " . format_Currency($max_amount);
if ($start_date) $filter_summary[] = "From: " . date('M d, Y', strtotime($start_date));
if ($end_date) $filter_summary[] = "To: " . date('M d, Y', strtotime($end_date));
if ($region) $filter_summary[] = "Region: " . htmlspecialchars($region);
if ($zone) $filter_summary[] = "Zone: " . htmlspecialchars($zone);
if ($level) $filter_summary[] = "Level: " . htmlspecialchars($level);

// Function to generate CSV data
function generateCSVData($data, $headers) {
    $output = fopen('php://temp', 'r+');
    fputcsv($output, $headers);
    foreach ($data as $row) {
        fputcsv($output, array_values($row));
    }
    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);
    return $csv;
}

// Handle export request
if (isset($_GET['export'])) {
    $type = $_GET['export'];
    $filename = 'report_' . date('Y-m-d') . '_' . time();
    
    if ($type === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $headers = ['Month', 'Total Transactions', 'Total Amount', 'Paid Transactions', 'Success Rate', 'Growth'];
        echo generateCSVData($monthly_stats, $headers);
        exit;
    } elseif ($type === 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $headers = ['Month', 'Total Transactions', 'Total Amount', 'Paid Transactions', 'Success Rate', 'Growth'];
        echo generateCSVData($monthly_stats, $headers);
        exit;
    }
}

$page_title = t('reports');
require_once '../includes/header.php';
?>

<div class="container">
  <!-- Dashboard Header with Quick Actions -->
  <div class="dashboard-header">
    <div class="header-content">
      <h1 class="dashboard-title">
        <i class="fas fa-chart-line"></i>
        Financial Reports & Analytics
      </h1>
      <p class="dashboard-subtitle">
        Comprehensive overview of credit management performance, Health Facility statistics, and financial trends
      </p>
    </div>
    <div class="header-actions">
      <button class="btn btn-primary print-btn" id="printReportBtn">
        <i class="fas fa-print"></i> Print Report
      </button>
    </div>
  </div>

  <!-- Quick Stats Cards -->
  <div class="quick-stats-grid">
    <div class="quick-stat-card">
      <div class="stat-icon" style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);">
        <i class="fas fa-users" style="color: #1976D2;"></i>
      </div>
      <div class="stat-content">
        <h3>Total Health Facilities</h3>
        <p class="stat-number"><?php echo safe_number_format($stats['total_customers']); ?></p>
        <div class="stat-trend">
          <span class="trend-indicator trend-up">
            <i class="fas fa-arrow-up"></i> 12%
          </span>
          <span class="trend-label">vs last month</span>
        </div>
      </div>
    </div>
    <div class="quick-stat-card">
      <div class="stat-icon" style="background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);">
        <i class="fas fa-money-bill-wave" style="color: #2E7D32;"></i>
      </div>
      <div class="stat-content">
        <h3>Total Credits</h3>
        <p class="stat-number"><?php echo format_Currency($stats['total_credits']); ?></p>
        <div class="stat-trend">
          <span class="trend-indicator trend-up">
            <i class="fas fa-arrow-up"></i> 8%
          </span>
          <span class="trend-label">vs last month</span>
        </div>
      </div>
    </div>
    <div class="quick-stat-card">
      <div class="stat-icon" style="background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);">
        <i class="fas fa-clock" style="color: #E65100;"></i>
      </div>
      <div class="stat-content">
        <h3>Pending Payments</h3>
        <p class="stat-number"><?php echo format_Currency($stats['total_credits'] - $stats['total_payments']); ?></p>
        <div class="stat-trend">
          <span class="trend-indicator trend-down">
            <i class="fas fa-arrow-down"></i> 5%
          </span>
          <span class="trend-label">vs last month</span>
        </div>
      </div>
    </div>
    <div class="quick-stat-card">
      <div class="stat-icon" style="background: linear-gradient(135deg, #fce4ec 0%, #f8bbd0 100%);">
        <i class="fas fa-exclamation-circle" style="color: #C2185B;"></i>
      </div>
      <div class="stat-content">
        <h3>Overdue Amount</h3>
        <p class="stat-number"><?php echo format_Currency($stats['total_overdue_amount']); ?></p>
        <div class="stat-trend">
          <span class="trend-indicator trend-down">
            <i class="fas fa-arrow-down"></i> 3%
          </span>
          <span class="trend-label">vs last month</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Advanced Filters -->
  <div class="card filter-card">
    <div class="filter-header">
      <div class="filter-title">
        <i class="fas fa-filter"></i>
        <h3>Advanced Filters</h3>
      </div>
      <div class="filter-actions">
        <button class="btn btn-outline-secondary" onclick="saveFilterPreset()">
          <i class="fas fa-save"></i> Save Preset
        </button>
        <button onclick="toggleFilters()" class="btn btn-outline-primary">
          <i class="fas fa-sliders-h"></i> Show Filters
        </button>
      </div>
    </div>

    <?php if (!empty($filter_summary)): ?>
    <div class="active-filters">
      <div class="active-filters-header">
        <i class="fas fa-tags"></i>
        <span>Active Filters</span>
      </div>
      <div class="active-filters-content">
        <?php foreach ($filter_summary as $filter): ?>
        <span class="filter-tag">
          <?php echo $filter; ?>
          <button class="remove-filter" onclick="removeFilter(this)">
            <i class="fas fa-times"></i>
          </button>
        </span>
        <?php endforeach; ?>
        <a href="index.php" class="clear-filters">
          <i class="fas fa-times"></i> Clear All
        </a>
      </div>
    </div>
    <?php endif; ?>

    <form id="filterForm" method="GET" class="filter-form" style="display: none;">
      <div class="filter-grid">
        <div class="filter-group">
          <label>Search</label>
          <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control"
            placeholder="Search transactions...">
        </div>

        <div class="filter-group">
          <label>Status</label>
          <select name="status" class="form-control">
            <option value="">All Status</option>
            <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>Paid</option>
            <option value="partial" <?php echo $status === 'partial' ? 'selected' : ''; ?>>Partial</option>
            <option value="overdue" <?php echo $status === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
          </select>
        </div>

        <div class="filter-group">
          <label>Amount Range</label>
          <div class="range-inputs">
            <input type="number" name="min_amount" value="<?php echo $min_amount; ?>" class="form-control"
              placeholder="Min" step="0.01">
            <span class="range-separator">to</span>
            <input type="number" name="max_amount" value="<?php echo $max_amount; ?>" class="form-control"
              placeholder="Max" step="0.01">
          </div>
        </div>

        <div class="filter-group">
          <label>Date Range</label>
          <div class="range-inputs">
            <input type="date" name="start_date" value="<?php echo $start_date; ?>" class="form-control">
            <span class="range-separator">to</span>
            <input type="date" name="end_date" value="<?php echo $end_date; ?>" class="form-control">
          </div>
        </div>

        <div class="filter-group">
          <label>Region</label>
          <select name="region" class="form-control">
            <option value="">All Regions</option>
            <?php foreach ($regions as $r): ?>
            <option value="<?php echo htmlspecialchars($r); ?>" <?php echo $region === $r ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($r); ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="filter-group">
          <label>Zone</label>
          <select name="zone" class="form-control">
            <option value="">All Zones</option>
            <?php foreach ($zones as $z): ?>
            <option value="<?php echo htmlspecialchars($z); ?>" <?php echo $zone === $z ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($z); ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="filter-group">
          <label>Level</label>
          <select name="level" class="form-control">
            <option value="">All Levels</option>
            <?php foreach ($levels as $l): ?>
            <option value="<?php echo htmlspecialchars($l); ?>" <?php echo $level === $l ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($l); ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="filter-actions">
        <a href="index.php" class="btn btn-outline-secondary">
          <i class="fas fa-times"></i> Reset Filters
        </a>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-search"></i> Apply Filters
        </button>
      </div>
    </form>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <div class="content-grid">
      <!-- Monthly Credit Trends -->
      <div class="card trend-card">
      <div class="card-header">
          <h3>Monthly Credit Trends</h3>
        <div class="card-actions">
            <select id="trendPeriod" class="form-select">
              <option value="6">Last 6 Months</option>
              <option value="12">Last 12 Months</option>
              <option value="24">Last 24 Months</option>
          </select>
        </div>
      </div>
        <div class="card-body">
          <div class="chart-container">
            <canvas id="monthlyTrendChart"></canvas>
            </div>
          </div>
          </div>

      <!-- Performance Metrics -->
      <div class="card metrics-card">
        <div class="card-header">
          <h3>Performance Metrics</h3>
          </div>
        <div class="card-body">
          <div class="metrics-grid">
            <div class="metric-item">
          <div class="metric-header">
                <span class="metric-label">Payment Rate</span>
                <span class="metric-value">80%</span>
            </div>
              <div class="mini-chart-container">
                <canvas id="paymentRateChart"></canvas>
          </div>
          </div>
            <div class="metric-item">
              <div class="metric-header">
                <span class="metric-label">Collection Rate</span>
                <span class="metric-value">90%</span>
              </div>
              <div class="mini-chart-container">
            <canvas id="collectionRateChart"></canvas>
          </div>
        </div>
            <div class="metric-item">
          <div class="metric-header">
                <span class="metric-label">Risk Score</span>
                <span class="metric-value">15%</span>
            </div>
              <div class="mini-chart-container">
            <canvas id="riskScoreChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Recent Transactions -->
      <div class="card transactions-card">
        <div class="card-header">
          <h3>Recent Transactions</h3>
          <a href="transactions.php" class="view-all" onclick="return checkPageAccess('transactions.php')">
            <i class="fas fa-external-link-alt"></i> View All
          </a>
        </div>
        <div class="card-body">
    <div class="table-responsive">
            <table class="table">
        <thead>
          <tr>
            <th>Date</th>
                  <th>Customer</th>
                  <th>Type</th>
                  <th>Amount</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent_transactions as $transaction): ?>
          <tr>
                  <td><?php echo date('M d, Y', strtotime($transaction['created_at'])); ?></td>
                  <td><?php echo htmlspecialchars($transaction['customer_name']); ?></td>
                  <td><?php echo ucfirst($transaction['type']); ?></td>
                  <td>ETB <?php echo number_format($transaction['amount'], 2); ?></td>
                  <td>
                    <span class="status-badge <?php echo strtolower($transaction['status']); ?>">
                      <?php echo ucfirst($transaction['status']); ?>
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
          </div>
    </div>
  </div>

  <!-- Top Customers -->
      <div class="card customers-card">
        <div class="card-header">
          <h3>Top Customers</h3>
          <a href="customers.php" class="view-all" onclick="return checkPageAccess('customers.php')">
            <i class="fas fa-external-link-alt"></i> View All
          </a>
        </div>
        <div class="card-body">
    <div class="table-responsive">
            <table class="table">
        <thead>
          <tr>
                  <th>Customer</th>
                  <th>Total Credit</th>
                  <th>Balance</th>
                  <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($top_customers as $customer): ?>
          <tr>
                  <td><?php echo htmlspecialchars($customer['name']); ?></td>
                  <td>ETB <?php echo number_format($customer['total_credit'], 2); ?></td>
                  <td>ETB <?php echo number_format($customer['total_paid'], 2); ?></td>
            <td>
              <?php
                    // Calculate status based on payment information
                    $status = 'pending';
                    if ($customer['total_credit'] > 0) {
                        if ($customer['total_paid'] >= $customer['total_credit']) {
                            $status = 'paid';
                        } elseif ($customer['total_paid'] > 0) {
                            $status = 'partial';
                        } elseif ($customer['overdue_transactions'] > 0) {
                            $status = 'overdue';
                        }
                    }
                    ?>
                    <span class="status-badge <?php echo strtolower($status); ?>">
                      <?php echo ucfirst($status); ?>
              </span>
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
</div>

<style>
/* Modern Dashboard Styles */
:root {
  --primary-color: #2196F3;
  --primary-dark: #1976D2;
  --success-color: #4CAF50;
  --warning-color: #FF9800;
  --danger-color: #F44336;
  --text-primary: #333;
  --text-secondary: #666;
  --border-radius: 12px;
  --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  --transition: all 0.3s ease;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
}

/* Enhanced Dashboard Header */
.dashboard-header {
  background: linear-gradient(135deg, #1976D2 0%, #2196F3 100%);
  border-radius: var(--border-radius);
  padding: 2.5rem;
  margin-bottom: 2rem;
  color: white;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  position: relative;
  overflow: hidden;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.dashboard-header::before {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  background: linear-gradient(45deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 100%);
  pointer-events: none;
}

.header-content {
  flex: 1;
  margin-right: 2rem;
}

.dashboard-title {
  font-size: 2.2rem;
  font-weight: 600;
  margin-bottom: 0.8rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.dashboard-subtitle {
  font-size: 1.1rem;
  opacity: 0.9;
  max-width: 600px;
}

.header-actions {
  position: relative;
  z-index: 1;
}

.print-btn {
  background: rgba(255, 255, 255, 0.2);
  border: 2px solid rgba(255, 255, 255, 0.3);
  padding: 0.8rem 1.8rem;
  font-size: 1rem;
  transition: all 0.3s ease;
  color: white;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  border-radius: 8px;
}

.print-btn:hover {
  background: rgba(255, 255, 255, 0.3);
  border-color: rgba(255, 255, 255, 0.4);
  transform: translateY(-2px);
}

.print-btn i {
  font-size: 1.1rem;
}

/* Enhanced Quick Stats Cards */
.quick-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.8rem;
  margin-bottom: 2.5rem;
}

.quick-stat-card {
  background: white;
  border-radius: var(--border-radius);
  padding: 1.8rem;
  display: flex;
  align-items: center;
  gap: 1.8rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
  border: 1px solid rgba(0, 0, 0, 0.05);
  position: relative;
  overflow: hidden;
}

.quick-stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
  opacity: 0;
  transition: all 0.3s ease;
}

.quick-stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.quick-stat-card:hover::before {
  opacity: 1;
}

.stat-icon {
  width: 65px;
  height: 65px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.8rem;
  transition: all 0.3s ease;
}

.stat-content {
  flex: 1;
}

.stat-content h3 {
  font-size: 1rem;
  color: #666;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.stat-number {
  font-size: 2rem;
  font-weight: 600;
  color: #333;
  margin: 0.5rem 0;
  line-height: 1.2;
}

.stat-trend {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
}

.trend-indicator {
  display: inline-flex;
  align-items: center;
  padding: 0.3rem 0.8rem;
  border-radius: 20px;
  font-weight: 500;
}

.trend-up {
  background: rgba(76, 175, 80, 0.1);
  color: #4CAF50;
}

.trend-down {
  background: rgba(244, 67, 54, 0.1);
  color: #F44336;
}

.trend-label {
  color: #666;
  font-size: 0.85rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .dashboard-header {
    flex-direction: column;
    padding: 1.5rem;
    text-align: center;
  }

  .header-content {
    margin-right: 0;
    margin-bottom: 1.5rem;
  }

  .dashboard-title {
    justify-content: center;
    font-size: 1.8rem;
  }

  .dashboard-subtitle {
    margin: 0 auto;
  }

  .header-actions {
    width: 100%;
    display: flex;
    justify-content: center;
  }

  .quick-stats-grid {
    grid-template-columns: 1fr;
    gap: 1.2rem;
  }

  .quick-stat-card {
    padding: 1.2rem;
  }

  .stat-icon {
    width: 50px;
    height: 50px;
    font-size: 1.5rem;
  }

  .stat-number {
    font-size: 1.6rem;
  }
}

/* Modern Filter Card */
.filter-card {
  background: white;
  border-radius: var(--border-radius);
  margin-bottom: 2rem;
  box-shadow: var(--card-shadow);
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.filter-header {
  padding: 1.5rem;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.filter-title {
  display: flex;
  align-items: center;
  gap: 1rem;
  font-size: 1.2rem;
  color: var(--text-primary);
}

.filter-form {
  padding: 1.5rem;
}

.filter-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.filter-group {
  margin-bottom: 1rem;
}

.filter-group label {
  display: block;
  margin-bottom: 0.5rem;
  color: var(--text-secondary);
  font-weight: 500;
}

.form-control {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid rgba(0, 0, 0, 0.1);
  border-radius: 8px;
  transition: var(--transition);
}

.form-control:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
  outline: none;
}

/* Active Filters */
.active-filters {
  padding: 1rem 1.5rem;
  background: #f8f9fa;
  border-radius: 0 0 var(--border-radius) var(--border-radius);
}

.filter-tag {
  display: inline-flex;
  align-items: center;
  padding: 0.5rem 1rem;
  background: white;
  border-radius: 20px;
  margin: 0.25rem;
  font-size: 0.9rem;
  color: var(--text-primary);
  border: 1px solid rgba(0, 0, 0, 0.1);
  transition: var(--transition);
}

.filter-tag:hover {
  background: #f8f9fa;
  transform: translateY(-1px);
}

/* Main Content Styles */
.main-content {
  padding: 2rem;
  background: #f8f9fa;
}

.content-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

/* Card Styles */
.card {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.card-header {
  padding: 1.5rem;
  border-bottom: 1px solid #eee;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h3 {
  margin: 0;
  font-size: 1.25rem;
  color: #333;
}

.card-body {
  padding: 1.5rem;
}

/* Chart Container */
.chart-container {
  height: 300px;
  position: relative;
}

/* Metrics Grid */
.metrics-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
}

.metric-item {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 1rem;
}

.metric-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.metric-label {
  color: #666;
  font-size: 0.875rem;
}

.metric-value {
  font-weight: 600;
  color: #333;
}

.mini-chart {
  height: 60px;
}

/* Table Styles */
.table-responsive {
  overflow-x: auto;
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th,
.table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.table th {
  font-weight: 600;
  color: #666;
  background: #f8f9fa;
}

.table tbody tr:hover {
  background: #f8f9fa;
}

/* Status Badges */
.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 500;
}

.status-badge.paid {
  background: rgba(76, 175, 80, 0.1);
  color: #4CAF50;
}

.status-badge.pending {
  background: rgba(255, 152, 0, 0.1);
  color: #FF9800;
}

.status-badge.overdue {
  background: rgba(244, 67, 54, 0.1);
  color: #F44336;
}

/* Form Select */
.form-select {
  padding: 0.5rem 2rem 0.5rem 1rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  background: #fff;
  color: #333;
  font-size: 0.875rem;
  cursor: pointer;
}

.form-select:focus {
  outline: none;
  border-color: #2196F3;
  box-shadow: 0 0 0 2px rgba(33, 150, 243, 0.1);
}

/* View All Link */
.view-all {
  color: #2196F3;
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  transition: all 0.2s ease;
}

.view-all:hover {
  background: rgba(33, 150, 243, 0.1);
  color: #1976D2;
}

.view-all i {
  font-size: 0.8rem;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .content-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .main-content {
    padding: 1rem;
  }

  .metrics-grid {
    grid-template-columns: 1fr;
  }

  .card-header {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }
}

/* Print Styles */
@media print {
  .container {
    padding: 0;
  }

  .dashboard-header {
    background: none;
    color: black;
    padding: 0;
    margin-bottom: 2rem;
  }

  .filter-card,
  .header-actions,
  .btn {
    display: none !important;
  }

  .card {
    break-inside: avoid;
    page-break-inside: avoid;
    border: none;
    box-shadow: none;
  }

  .performance-metric {
    break-inside: avoid;
  }
}

/* Enhanced Performance Metrics */
.metrics-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
}

.metrics-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
  padding: 1rem;
}

.metric-item {
  background: #f8f9fa;
  border-radius: 12px;
  padding: 1.5rem;
  transition: all 0.3s ease;
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.metric-item:hover {
  background: #fff;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  transform: translateY(-2px);
}

.metric-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.metric-label {
  color: #666;
  font-size: 1rem;
  font-weight: 500;
}

.metric-value {
  font-weight: 600;
  color: #333;
  font-size: 1.2rem;
}

.mini-chart-container {
  height: 80px;
  position: relative;
  background: white;
  border-radius: 8px;
  padding: 0.5rem;
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
}

/* Responsive Design */
@media (max-width: 1200px) {
  .metrics-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .metrics-grid {
    grid-template-columns: 1fr;
  }

  .metric-item {
    padding: 1.2rem;
  }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
function exportReport() {
  // Implement export functionality
  alert('Export functionality will be implemented soon');
}

// Define printReport function globally
window.printReport = function() {
  // Create a new window for printing
  const printWindow = window.open('', '_blank');

  // Get the content to print
  const content = document.querySelector('.container').innerHTML;

  // Create the print content with proper styling
  const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>EPSS CRM - Financial Report</title>
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
          margin: 20px;
                    background: white;
                }
                .print-header {
                    text-align: center;
          margin-bottom: 20px;
          padding-bottom: 20px;
                    border-bottom: 2px solid #eee;
                }
                .print-header h1 {
                    color: #2196F3;
          margin: 0;
                }
                .print-header p {
                    color: #666;
          margin: 5px 0 0 0;
        }
        .quick-stats-grid {
                    display: grid;
                    grid-template-columns: repeat(4, 1fr);
          gap: 15px;
          margin-bottom: 20px;
                }
        .quick-stat-card {
                    background: #f8f9fa;
          padding: 15px;
          border-radius: 8px;
                    text-align: center;
                }
        .quick-stat-card h3 {
                    margin: 0;
          font-size: 14px;
                    color: #666;
        }
        .quick-stat-card .stat-number {
          font-size: 20px;
          font-weight: bold;
                    color: #2196F3;
          margin: 5px 0;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
          margin: 15px 0;
                }
                th, td {
          padding: 10px;
                    border: 1px solid #ddd;
          text-align: left;
                }
                th {
                    background: #f8f9fa;
                }
        .status-badge {
                    display: inline-block;
          padding: 4px 8px;
          border-radius: 4px;
          font-size: 12px;
        }
        .status-paid { background: #e8f5e9; color: #4CAF50; }
        .status-pending { background: #fff3e0; color: #FF9800; }
        .status-overdue { background: #fce4ec; color: #F44336; }
                .print-footer {
                    text-align: center;
          margin-top: 20px;
          padding-top: 20px;
          border-top: 1px solid #eee;
          font-size: 12px;
                    color: #666;
                }
                @media print {
          body { margin: 0; }
          .no-print { display: none !important; }
          table { page-break-inside: auto; }
          tr { page-break-inside: avoid; page-break-after: auto; }
                }
            </style>
        </head>
        <body>
                <div class="print-header">
                    <h1>EPSS CRM Financial Report</h1>
        <p>Generated on ${new Date().toLocaleString()}</p>
                </div>

      <div class="quick-stats-grid">
                        ${Array.from(document.querySelectorAll('.quick-stat-card')).map(card => `
          <div class="quick-stat-card">
                                <h3>${card.querySelector('h3').textContent}</h3>
            <div class="stat-number">${card.querySelector('.stat-number').textContent}</div>
                            </div>
                        `).join('')}
                </div>

      <div class="recent-transactions">
        <h2>Recent Transactions</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
              <th>Customer</th>
              <th>Type</th>
              <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${Array.from(document.querySelectorAll('.table-responsive table tbody tr')).slice(0, 5).map(row => `
                                <tr>
                                    <td>${row.cells[0].textContent}</td>
                                    <td>${row.cells[1].textContent}</td>
                                    <td>${row.cells[2].textContent}</td>
                                    <td>${row.cells[3].textContent}</td>
                <td>${row.cells[4].textContent}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>

                <div class="print-footer">
                    <p>This report was generated by EPSS CRM System</p>
                    <p>Page {page} of {pages}</p>
            </div>
        </body>
        </html>
    `;

  // Write the content to the new window
  printWindow.document.write(printContent);
  printWindow.document.close();

  // Wait for content to load before printing
  printWindow.onload = function() {
    setTimeout(() => {
      printWindow.print();
      printWindow.close();
    }, 500);
  };
};

// Initialize print button click handler
document.addEventListener('DOMContentLoaded', function() {
  const printBtn = document.getElementById('printReportBtn');
  if (printBtn) {
    printBtn.addEventListener('click', window.printReport);
  }
});

function saveFilterPreset() {
  const form = document.getElementById('filterForm');
  const formData = new FormData(form);
  const preset = {};
  formData.forEach((value, key) => {
    if (value) preset[key] = value;
  });

  const presets = JSON.parse(localStorage.getItem('filterPresets') || '[]');
  const presetName = prompt('Enter a name for this preset:');

  if (presetName) {
    presets.push({
      name: presetName,
      filters: preset
    });
    localStorage.setItem('filterPresets', JSON.stringify(presets));
    alert('Preset saved successfully!');
  }
}

function loadFilterPreset() {
  const presets = JSON.parse(localStorage.getItem('filterPresets') || '[]');
  if (presets.length === 0) {
    alert('No presets available');
    return;
  }

  const presetNames = presets.map(p => p.name);
  const selectedPreset = prompt('Enter the name of the preset to load:\n' + presetNames.join('\n'));

  if (selectedPreset) {
    const preset = presets.find(p => p.name === selectedPreset);
    if (preset) {
      const form = document.getElementById('filterForm');
      Object.entries(preset.filters).forEach(([key, value]) => {
        const input = form.elements[key];
        if (input) input.value = value;
      });
      form.submit();
    } else {
      alert('Preset not found');
    }
  }
}

function removeFilter(button) {
  const filterTag = button.parentElement;
  const filterText = filterTag.textContent.trim();
  const filterType = filterText.split(':')[0].toLowerCase();

  // Map filter types to form field names
  const filterMap = {
    'search': 'search',
    'status': 'status',
    'min amount': 'min_amount',
    'max amount': 'max_amount',
    'from': 'start_date',
    'to': 'end_date',
    'region': 'region',
    'zone': 'zone',
    'level': 'level'
  };

  const fieldName = filterMap[filterType];
  if (fieldName) {
    const form = document.getElementById('filterForm');
    const input = form.elements[fieldName];
    if (input) {
      input.value = '';
      form.submit();
    }
  }
}

function toggleFilters() {
  const form = document.getElementById('filterForm');
  const button = document.querySelector('.filter-header button:last-child');
  const icon = button.querySelector('i');

  if (form.style.display === 'none') {
    form.style.display = 'block';
    button.innerHTML = '<i class="fas fa-times"></i> Hide Filters';
    icon.className = 'fas fa-times';
  } else {
    form.style.display = 'none';
    button.innerHTML = '<i class="fas fa-sliders-h"></i> Show Filters';
    icon.className = 'fas fa-sliders-h';
  }
}

// Initialize charts when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
  // Monthly Trend Chart
  const monthlyData = <?php echo json_encode(array_reverse($monthly_stats)); ?>;
  const trendCtx = document.getElementById('monthlyTrendChart');

  if (trendCtx && monthlyData && monthlyData.length > 0) {
    try {
      new Chart(trendCtx, {
        type: 'line',
        data: {
          labels: monthlyData.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('en-US', {
              month: 'short',
              year: 'numeric'
            });
          }),
          datasets: [{
            label: 'Total Credits',
            data: monthlyData.map(item => parseFloat(item.total_amount) || 0),
            borderColor: '#2196F3',
            backgroundColor: 'rgba(33, 150, 243, 0.1)',
            tension: 0.4,
            fill: true
          }, {
            label: 'Total Payments',
            data: monthlyData.map(item => parseFloat(item.total_payments) || 0),
            borderColor: '#4CAF50',
            backgroundColor: 'rgba(76, 175, 80, 0.1)',
            tension: 0.4,
            fill: true
          }, {
            label: 'Overdue Amount',
            data: monthlyData.map(item => parseFloat(item.overdue_amount) || 0),
            borderColor: '#F44336',
            backgroundColor: 'rgba(244, 67, 54, 0.1)',
            tension: 0.4,
            fill: true
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: true,
              position: 'top'
            },
            tooltip: {
              mode: 'index',
              intersect: false,
              callbacks: {
                label: function(context) {
                  return context.dataset.label + ': ETB ' + context.raw.toLocaleString();
                }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                callback: function(value) {
                  return 'ETB ' + value.toLocaleString();
                }
              }
            }
          },
          interaction: {
            intersect: false,
            mode: 'index'
          }
        }
      });
    } catch (error) {
      console.error('Error initializing monthly trend chart:', error);
    }
  }

  // Mini Charts
  const miniCharts = [{
      id: 'paymentRateChart',
      data: [65, 70, 68, 72, 75, 78, 80],
      color: '#2196F3'
    },
    {
      id: 'collectionRateChart',
      data: [75, 78, 80, 82, 85, 88, 90],
      color: '#4CAF50'
    },
    {
      id: 'riskScoreChart',
      data: [30, 28, 25, 22, 20, 18, 15],
      color: '#F44336'
    }
  ];

  miniCharts.forEach(chart => {
    const ctx = document.getElementById(chart.id);
    if (ctx) {
      try {
        new Chart(ctx, {
          type: 'line',
          data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
              data: chart.data,
              borderColor: chart.color,
              backgroundColor: chart.color + '20',
              tension: 0.4,
              fill: true,
              borderWidth: 2,
              pointRadius: 0,
              pointHoverRadius: 4
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: false
              },
              tooltip: {
                enabled: true,
                mode: 'index',
                intersect: false,
                backgroundColor: 'rgba(255, 255, 255, 0.9)',
                titleColor: '#333',
                bodyColor: '#666',
                borderColor: '#ddd',
                borderWidth: 1,
                padding: 10,
                displayColors: false,
                callbacks: {
                  label: function(context) {
                    return context.raw + '%';
                  }
                }
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                max: 100,
                display: false,
                grid: {
                  display: false
                }
              },
              x: {
                display: false,
                grid: {
                  display: false
                }
              }
            },
            interaction: {
              intersect: false,
              mode: 'index'
            }
          }
        });
  } catch (error) {
        console.error(`Error initializing ${chart.id}:`, error);
      }
    }
  });
});

// Handle trend period change
document.getElementById('trendPeriod')?.addEventListener('change', function(e) {
  const period = parseInt(e.target.value);
  const chartContainer = document.getElementById('monthlyTrendChart');

  if (!chartContainer) return;

  // Show loading state
  chartContainer.style.opacity = '0.5';

  // Fetch new data
  fetch(`get_monthly_stats.php?period=${period}`)
    .then(response => response.json())
    .then(data => {
      if (data && data.length > 0) {
        // Update chart data
        const chart = Chart.getChart(chartContainer);
        if (chart) {
          chart.data.labels = data.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('en-US', {
              month: 'short',
              year: 'numeric'
            });
          });

          chart.data.datasets[0].data = data.map(item => parseFloat(item.total_amount) || 0);
          chart.data.datasets[1].data = data.map(item => parseFloat(item.total_payments) || 0);
          chart.data.datasets[2].data = data.map(item => parseFloat(item.overdue_amount) || 0);

          chart.update();
        }
      }
    })
    .catch(error => {
      console.error('Error fetching monthly stats:', error);
      alert('Error loading data. Please try again.');
    })
    .finally(() => {
      // Reset loading state
      chartContainer.style.opacity = '1';
    });
});

// Add this new function to check page access
function checkPageAccess(page) {
  // Check if the page exists
  fetch(page)
    .then(response => {
      if (!response.ok) {
        throw new Error('Page not found');
      }
      return true;
    })
    .catch(error => {
      console.error('Error checking page access:', error);
      alert('The requested page is not available. Please try again later.');
      return false;
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>