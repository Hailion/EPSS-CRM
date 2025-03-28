<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
require_once '../includes/languages.php';

$page_title = t('dashboard');

// Get quick statistics
$stats = [
    'total_customers' => $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn(),
    'active_customers' => $pdo->query("SELECT COUNT(*) FROM customers WHERE status = 'active'")->fetchColumn(),
    'total_credits' => $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM transactions")->fetchColumn(),
    'total_payments' => $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments")->fetchColumn(),
    'overdue_transactions' => $pdo->query("SELECT COUNT(*) FROM transactions WHERE status = 'overdue'")->fetchColumn(),
    'total_overdue_amount' => $pdo->query("
        SELECT COALESCE(SUM(t.amount - COALESCE(
            (SELECT SUM(amount) FROM payments WHERE transaction_id = t.id),
            0
        )), 0)
        FROM transactions t
        WHERE t.status = 'overdue'
    ")->fetchColumn()
];

// Add new statistics
$stats['total_this_month'] = $pdo->query("
    SELECT COALESCE(SUM(amount), 0) 
    FROM transactions 
    WHERE MONTH(created_at) = MONTH(CURRENT_DATE)
")->fetchColumn();

$stats['payments_this_month'] = $pdo->query("
    SELECT COALESCE(SUM(amount), 0) 
    FROM payments 
    WHERE MONTH(created_at) = MONTH(CURRENT_DATE)
")->fetchColumn();

// Get recent transactions
$recent_transactions = $pdo->query("
    SELECT t.*, c.name as customer_name
    FROM transactions t
    JOIN customers c ON t.customer_id = c.id
    ORDER BY t.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Get customers with overdue transactions
$overdue_customers = $pdo->query("
    SELECT c.id, c.name,
           COUNT(t.id) as overdue_count,
           SUM(t.amount - COALESCE(
               (SELECT SUM(amount) FROM payments WHERE transaction_id = t.id),
               0
           )) as total_overdue
    FROM customers c
    JOIN transactions t ON c.id = t.customer_id
    WHERE t.status = 'overdue'
    GROUP BY c.id, c.name
    ORDER BY total_overdue DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Get total credit amount
$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total_credit FROM transactions");
$total_credit = $stmt->fetch(PDO::FETCH_ASSOC)['total_credit'];

// Get total remaining amount
$stmt = $pdo->query("
    SELECT COALESCE(SUM(amount - (
        SELECT COALESCE(SUM(p.amount), 0)
        FROM payments p
        WHERE p.transaction_id = transactions.id
    )), 0) as total_remaining
    FROM transactions
");
$total_remaining = $stmt->fetch(PDO::FETCH_ASSOC)['total_remaining'];

// Get total payments
$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total_payments FROM payments");
$total_payments = $stmt->fetch(PDO::FETCH_ASSOC)['total_payments'];

// Get overdue transactions count
$stmt = $pdo->query("
    SELECT COUNT(*) as overdue_count
    FROM transactions
    WHERE due_date < CURRENT_DATE
    AND amount > (
        SELECT COALESCE(SUM(p.amount), 0)
        FROM payments p
        WHERE p.transaction_id = transactions.id
    )
");
$overdue_count = $stmt->fetch(PDO::FETCH_ASSOC)['overdue_count'];

// Get recent transactions
$stmt = $pdo->query("
    SELECT 
        t.*,
        c.name as customer_name,
        COALESCE(t.amount, 0) as total_amount,
        COALESCE(t.amount - (
            SELECT COALESCE(SUM(p.amount), 0)
            FROM payments p
            WHERE p.transaction_id = t.id
        ), t.amount) as remaining_amount,
        DATEDIFF(CURRENT_DATE, t.due_date) as days_overdue
    FROM transactions t
    JOIN customers c ON t.customer_id = c.id
    ORDER BY t.created_at DESC
    LIMIT 5
");
$recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get monthly trends
$monthly_trends = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COALESCE(SUM(amount), 0) as total_amount
    FROM transactions
    WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Add this after the monthly_trends query to debug
echo "<!-- Debug monthly trends data: ";
print_r($monthly_trends);
echo " -->";
?>

<!DOCTYPE html>
<html <?php echo getLanguageHtmlAttributes(); ?>>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Content-Language" content="<?php echo $_SESSION['language'] ?? 'en'; ?>">
  <title><?php echo $page_title; ?> - Credit Management System</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <?php if ($_SESSION['theme'] === 'dark'): ?>
  <link rel="stylesheet" href="../assets/css/dark-theme.css">
  <?php endif; ?>
  <!-- Add Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- Add custom dashboard styles -->
  <style>
  .dashboard-container {
    padding: 2rem;
    max-width: 1400px;
    margin: 0 auto;
    background: #f8f9fa;
  }

  .welcome-section {
    background: linear-gradient(135deg, #06292b 0%, #3c3d3d 100%);
    color: white;
    padding: 2.5rem;
    border-radius: 20px;
    margin-bottom: 2rem;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
  }

  .welcome-section::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 300px;
    height: 100%;
    background: url('data:image/svg+xml,...') no-repeat center right;
    opacity: 0.1;
  }

  .welcome-content {
    position: relative;
    z-index: 1;
  }

  .welcome-stats {
    display: flex;
    gap: 2rem;
    margin-top: 1.5rem;
  }

  .welcome-stat {
    background: rgba(255, 255, 255, 0.1);
    padding: 1rem;
    border-radius: 10px;
    backdrop-filter: blur(5px);
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    margin-bottom: 2rem;
  }

  .stat-card {
    background: white;
    padding: 3rem 1.8rem;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    border: 1px solid rgba(0, 0, 0, 0.05);
    text-transform: capitalize;
  }

  .stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
  }

  .stat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }

  .stat-icon {
    font-size: 2.5rem;
    background: linear-gradient(135deg, #4A90E2 0%, #357ABD 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  .stat-trend {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: #28a745;
  }

  .stat-trend.negative {
    color: #dc3545;
  }

  .dashboard-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
  }

  .chart-card {
    grid-column: span 1;
    background: white;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
  }

  .chart-card:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
  }

  .chart-card h2 {
    font-size: 1.25rem;
    color: #2C3E50;
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #f0f0f0;
  }

  .activity-feed {
    grid-column: initial;
    background: white;
    padding: 1.8rem;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  }

  .activity-item {
    padding: 1rem;
    border-left: 3px solid #4A90E2;
    background: #f8f9fa;
    margin-bottom: 1rem;
    border-radius: 0 8px 8px 0;
  }

  .quick-actions {
    background: white;
    padding: 1.8rem;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    margin-bottom: 2rem;
  }

  .action-buttons {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
  }

  .action-button {
    padding: 1.2rem;
    border-radius: 12px;
    background: linear-gradient(135deg, #4A90E2 0%, #357ABD 100%);
    color: white;
    text-align: center;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
  }

  .action-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(74, 144, 226, 0.3);
  }

  .data-card {
    background: white;
    padding: 1.8rem;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    margin-bottom: 2rem;
  }

  .data-table {
    border-radius: 8px;
    overflow: hidden;
  }

  .data-table th {
    background: #f8f9fa;
    padding: 1.2rem;
    font-weight: 600;
  }

  .data-table td {
    padding: 1.2rem;
    border-bottom: 1px solid #eee;
  }

  .status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 500;
    text-align: center;
    display: inline-block;
    min-width: 100px;
  }

  .status-paid {
    background: #d4edda;
    color: #155724;
  }

  .status-pending {
    background: #fff3cd;
    color: #856404;
  }

  .status-overdue {
    background: #f8d7da;
    color: #721c24;
  }

  .metric-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
  }

  .metric-card {
    background: white;
    padding: 1.5rem;
    border-radius: 16px;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  }

  .metric-value {
    font-size: 2rem;
    font-weight: bold;
    margin: 1rem 0;
    color: #2C3E50;
  }

  .charts-section {
    margin-top: 2rem;
    margin-bottom: 3rem;
  }

  .chart-container {
    position: relative;
    height: 300px;
    margin-top: 1rem;
  }

  @media (max-width: 1024px) {
    .chart-card.full-width .chart-container {
      height: 300px;
    }
  }

  @media (max-width: 768px) {
    .chart-card.full-width .chart-container {
      height: 250px;
    }
  }

  .dark-theme .chart-card {
    background: #2C3E50;
    border: 1px solid rgba(255, 255, 255, 0.1);
  }

  .dark-theme .chart-card h2 {
    color: #fff;
    border-bottom-color: rgba(255, 255, 255, 0.1);
  }

  .dashboard-grid .full-width {
    grid-column: 1 / -1;
    width: 100%;
  }

  .activity-feed-container {
    margin: 2rem 0;
  }

  .chart-card.full-width .chart-container {
    height: 400px;
  }

  .activity-feed {
    grid-column: initial;
  }
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="<?php echo $_SESSION['theme'] === 'dark' ? 'dark-theme' : ''; ?>">
  <?php include '../includes/header.php'; ?>

  <div class="dashboard-container">
    <!-- Welcome Section -->
    <div class="welcome-section">
      <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
      <p>Here's your credit management overview for today</p>
    </div>

    <!-- Add this section after the welcome section -->
    <div class="metric-cards">
      <div class="metric-card">
        <i class="fas fa-money-bill-wave fa-2x" style="color: #28a745;"></i>
        <h3>Collection Rate</h3>
        <div class="metric-value">
          <?php 
          if ($stats['total_credits'] > 0) {
              $collection_rate = ($stats['total_payments'] / $stats['total_credits']) * 100;
              echo number_format($collection_rate, 1) . '%';
          } else {
              echo '0.0%';
          }
          ?>
        </div>
      </div>
      <div class="metric-card">
        <i class="fas fa-clock fa-2x" style="color: #dc3545;"></i>
        <h3>Average Days Overdue</h3>
        <div class="metric-value">
          <?php
          // Calculate average days overdue with null check
          $avg_days = $pdo->query("
              SELECT COALESCE(AVG(DATEDIFF(CURRENT_DATE, due_date)), 0) as avg_days
              FROM transactions
              WHERE status = 'overdue'
          ")->fetchColumn();
          echo number_format((float)$avg_days, 0);
          ?>
        </div>
      </div>
      <div class="metric-card">
        <i class="fas fa-user-check fa-2x" style="color: #17a2b8;"></i>
        <h3>Active Customers</h3>
        <div class="metric-value">
          <?php echo number_format((float)$stats['active_customers'], 0); ?>
        </div>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
      <div class="stat-card">
        <i class="fas fa-users stat-icon"></i>
        <h3><?php echo t('total_customers'); ?></h3>
        <p class="stat-number"><?php echo number_format($stats['total_customers']); ?></p>
        <p class="stat-trend">↑ 12% from last month</p>
      </div>
      <div class="stat-card">
        <i class="fas fa-dollar-sign stat-icon"></i>
        <h3><?php echo t('total_credits'); ?></h3>
        <p class="stat-number"><?php echo format_currency($stats['total_credits']); ?></p>
        <p class="stat-subtitle"><?php echo t('payments'); ?>: <?php echo format_currency($stats['total_payments']); ?>
        </p>
      </div>
      <div class="stat-card">
        <i class="fas fa-exclamation-triangle stat-icon"></i>
        <h3><?php echo t('overdue_transactions'); ?></h3>
        <p class="stat-number"><?php echo number_format($stats['overdue_transactions']); ?></p>
        <p class="stat-subtitle"><?php echo t('amount'); ?>:
          <?php echo format_currency($stats['total_overdue_amount']); ?></p>
      </div>
      <div class="stat-card">
        <i class="fas fa-chart-line stat-icon"></i>
        <h3><?php echo t('this_month'); ?></h3>
        <p class="stat-number"><?php echo format_currency($stats['total_this_month']); ?></p>
        <p class="stat-subtitle"><?php echo t('payments'); ?>:
          <?php echo format_currency($stats['payments_this_month']); ?></p>
      </div>
    </div>

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">
      <!-- Monthly Trends Chart (Full Width) -->
      <div class="chart-card full-width">
        <h2><?php echo t('monthly_trends'); ?></h2>
        <div class="chart-container">
          <canvas id="trendsChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Activity Feed (in its own container) -->
    <div class="activity-feed-container">
      <div class="activity-feed">
        <h2><?php echo t('recent_activity'); ?></h2>
        <?php foreach ($recent_transactions as $transaction): ?>
        <div class="activity-item">
          <i class="fas fa-exchange-alt"></i>
          <div>
            <strong><?php echo htmlspecialchars($transaction['customer_name']); ?></strong>
            <p><?php echo format_currency($transaction['amount']); ?></p>
            <small><?php echo date('M d, Y', strtotime($transaction['created_at'])); ?></small>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
      <h2><?php echo t('Quick Actions'); ?></h2>
      <div class="action-buttons">
        <a href="../customers/register.php" class="btn btn-primary"><?php echo t('Add New Customer'); ?></a>
        <a href="../transactions/add_credit.php" class="btn btn-success"><?php echo t('Add New Credit'); ?></a>
        <a href="../transactions/add_payment.php" class="btn btn-info"><?php echo t('Record Payment'); ?></a>
        <a href="../reports/index.php" class="btn btn-secondary"><?php echo t('View Reports'); ?></a>
      </div>
    </div>

    <!-- Recent Transactions -->
    <div class="card">
      <h2><?php echo t('Recent Transactions'); ?></h2>
      <table>
        <thead>
          <tr>
            <th><?php echo t('id'); ?></th>
            <th><?php echo t('customer'); ?></th>
            <th><?php echo t('amount'); ?></th>
            <th><?php echo t('date'); ?></th>
            <th><?php echo t('status'); ?></th>
            <th><?php echo t('action'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent_transactions as $transaction): ?>
          <tr>
            <td><?php echo htmlspecialchars($transaction['id']); ?></td>
            <td><?php echo htmlspecialchars($transaction['customer_name']); ?></td>
            <td><?php echo format_currency($transaction['amount']); ?></td>
            <td><?php echo date('Y-m-d', strtotime($transaction['created_at'])); ?></td>
            <td>
              <span class="status-badge status-<?php echo htmlspecialchars($transaction['status']); ?>">
                <?php echo t($transaction['status']); ?>
              </span>
            </td>
            <td>
              <a href="../transactions/view.php?id=<?php echo htmlspecialchars($transaction['id']); ?>"
                class="btn btn-sm btn-info">
                <i class="fas fa-eye"></i> <?php echo t('view'); ?>
              </a>
              <?php if ($transaction['status'] !== 'paid'): ?>
              <a href="../transactions/add_payment.php?transaction_id=<?php echo htmlspecialchars($transaction['id']); ?>"
                class="btn btn-sm btn-success">
                <i class="fas fa-plus"></i> <?php echo t('add_payment'); ?>
              </a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Overdue Customers -->
    <div class="card">
      <h2><?php echo t('overdue_customers'); ?></h2>
      <table>
        <thead>
          <tr>
            <th><?php echo t('customer'); ?></th>
            <th><?php echo t('contact'); ?></th>
            <th><?php echo t('overdue_transactions'); ?></th>
            <th><?php echo t('total_overdue_amount'); ?></th>
            <th><?php echo t('action'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($overdue_customers as $customer): ?>
          <tr>
            <td><?php echo htmlspecialchars($customer['name']); ?></td>
            <td>
              <?php echo htmlspecialchars($customer['email']); ?><br>
              <?php echo htmlspecialchars($customer['phone']); ?>
            </td>
            <td><?php echo number_format($customer['overdue_count']); ?></td>
            <td><?php echo format_currency($customer['total_overdue']); ?></td>
            <td>
              <a href="../customers/view.php?id=<?php echo $customer['id']; ?>"
                class="btn btn-sm btn-info"><?php echo t('view_details'); ?></a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Charts Section -->
    <div class="charts-section">
      <div class="dashboard-grid">
        <div class="chart-card">
          <h2>
            <i class="fas fa-chart-line"></i>
            Credit vs Payment Trends
          </h2>
          <div class="chart-container">
            <canvas id="comparisonChart"></canvas>
          </div>
        </div>
        <div class="chart-card">
          <h2>
            <i class="fas fa-chart-pie"></i>
            Collection Performance
          </h2>
          <div class="chart-container">
            <canvas id="collectionChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Initialize Charts -->
  <script>
  // Monthly Trends Chart
  const ctx = document.getElementById('trendsChart').getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: <?php 
        $labels = array_map(function($item) {
            return date('M Y', strtotime($item['month'] . '-01'));
        }, $monthly_trends);
        echo json_encode($labels);
      ?>,
      datasets: [{
        label: 'Monthly Credits',
        data: <?php echo json_encode(array_map(function($item) {
            return floatval($item['total_amount']);
        }, $monthly_trends)); ?>,
        borderColor: '#4A90E2',
        backgroundColor: 'rgba(74, 144, 226, 0.1)',
        tension: 0.4,
        fill: true
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'top',
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              return 'Credits: ' + context.formattedValue.replace(/\B(?=(\d{3})+(?!\d))/g, ",") + ' ETB';
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return value.toLocaleString() + ' ETB';
            }
          }
        }
      }
    }
  });

  // Update the comparisonChart options
  const comparisonChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      y: {
        beginAtZero: true,
        grid: {
          color: 'rgba(0, 0, 0, 0.05)'
        },
        ticks: {
          callback: function(value) {
            return value.toLocaleString() + ' ETB';
          }
        }
      },
      x: {
        grid: {
          display: false
        }
      }
    },
    plugins: {
      legend: {
        position: 'top',
        align: 'end',
        labels: {
          boxWidth: 12,
          padding: 20
        }
      },
      tooltip: {
        backgroundColor: 'rgba(0, 0, 0, 0.8)',
        padding: 12,
        titleColor: '#fff',
        bodyColor: '#fff',
        cornerRadius: 8,
        callbacks: {
          label: function(context) {
            return context.dataset.label + ': ' +
              context.raw.toLocaleString() + ' ETB';
          }
        }
      }
    }
  };

  // Update the collectionChart options
  const collectionChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '60%',
    plugins: {
      legend: {
        position: 'bottom',
        labels: {
          padding: 20,
          boxWidth: 12
        }
      },
      tooltip: {
        backgroundColor: 'rgba(0, 0, 0, 0.8)',
        padding: 12,
        titleColor: '#fff',
        bodyColor: '#fff',
        cornerRadius: 8,
        callbacks: {
          label: function(context) {
            const value = context.raw;
            const total = context.dataset.data.reduce((a, b) => a + b, 0);
            const percentage = ((value / total) * 100).toFixed(1);
            return context.label + ': ' +
              value.toLocaleString() + ' ETB (' + percentage + '%)';
          }
        }
      }
    }
  };

  // Apply the updated options to your charts
  const compCtx = document.getElementById('comparisonChart').getContext('2d');
  const collCtx = document.getElementById('collectionChart').getContext('2d');
  const compChart = new Chart(compCtx, {
    type: 'bar',
    data: {
      labels: <?php 
        $months = $pdo->query("
          SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') as month,
                 DATE_FORMAT(created_at, '%b %Y') as month_label
          FROM (
              SELECT created_at FROM transactions 
              UNION 
              SELECT created_at FROM payments
          ) combined
          WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
          ORDER BY month ASC
        ")->fetchAll(PDO::FETCH_COLUMN, 1);
        echo json_encode($months);
      ?>,
      datasets: [{
          label: 'Credits',
          data: <?php 
          $credits = $pdo->query("
            SELECT DATE_FORMAT(created_at, '%b %Y') as month,
                   COALESCE(SUM(amount), 0) as total
            FROM transactions
            WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m'), month
            ORDER BY DATE_FORMAT(created_at, '%Y-%m') ASC
          ")->fetchAll(PDO::FETCH_KEY_PAIR);
          echo json_encode(array_values($credits));
        ?>,
          backgroundColor: 'rgba(74, 144, 226, 0.7)',
          borderColor: '#4A90E2',
          borderWidth: 1
        },
        {
          label: 'Payments',
          data: <?php 
          $payments = $pdo->query("
            SELECT DATE_FORMAT(created_at, '%b %Y') as month,
                   COALESCE(SUM(amount), 0) as total
            FROM payments
            WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m'), month
            ORDER BY DATE_FORMAT(created_at, '%Y-%m') ASC
          ")->fetchAll(PDO::FETCH_KEY_PAIR);
          echo json_encode(array_values($payments));
        ?>,
          backgroundColor: 'rgba(40, 167, 69, 0.7)',
          borderColor: '#28a745',
          borderWidth: 1
        }
      ]
    },
    options: comparisonChartOptions
  });

  const collChart = new Chart(collCtx, {
    type: 'doughnut',
    data: {
      labels: ['Collected', 'Outstanding', 'Overdue'],
      datasets: [{
        data: [
          <?php echo floatval($stats['total_payments']); ?>,
          <?php echo floatval($stats['total_credits'] - $stats['total_payments'] - $stats['total_overdue_amount']); ?>,
          <?php echo floatval($stats['total_overdue_amount']); ?>
        ],
        backgroundColor: [
          'rgba(40, 167, 69, 0.8)',
          'rgba(255, 193, 7, 0.8)',
          'rgba(220, 53, 69, 0.8)'
        ],
        borderColor: [
          '#28a745',
          '#ffc107',
          '#dc3545'
        ],
        borderWidth: 1
      }]
    },
    options: collectionChartOptions
  });
  </script>

  <?php include '../includes/footer.php'; ?>
</body>

</html>