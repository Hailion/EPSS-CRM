<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
require_once '../includes/languages.php';

// Get transaction ID from URL
$transaction_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch transaction details with proper error handling
try {
    $stmt = $pdo->prepare("
        SELECT 
            t.*,
            c.name as customer_name,
            c.region,
            c.hub,
            c.woreda,
            c.Zone,
            c.level,
            COALESCE(t.amount, 0) as total_amount,
            COALESCE(p.total_paid, 0) as amount_paid,
            COALESCE(t.amount - COALESCE(p.total_paid, 0), t.amount) as remaining_amount,
            DATEDIFF(CURRENT_DATE, t.due_date) as days_overdue,
            DATEDIFF(t.due_date, t.created_at) as credit_term_days
        FROM transactions t
        JOIN customers c ON t.customer_id = c.id
        LEFT JOIN (
            SELECT transaction_id, SUM(amount) as total_paid
            FROM payments
            GROUP BY transaction_id
        ) p ON t.id = p.transaction_id
        WHERE t.id = ?
    ");
    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch();

    if (!$transaction) {
        $_SESSION['error'] = t('transaction_not_found');
        header('Location: list.php');
        exit;
    }

    // Fetch payment history
    $stmt = $pdo->prepare("
        SELECT * FROM payments 
        WHERE transaction_id = ? 
        ORDER BY payment_date DESC
    ");
    $stmt->execute([$transaction_id]);
    $payments = $stmt->fetchAll();

    // Calculate payment statistics
    $payment_stats = [
        'total_paid' => array_sum(array_column($payments, 'amount')),
        'payment_count' => count($payments),
        'last_payment' => !empty($payments) ? $payments[0]['payment_date'] : null,
        'payment_methods' => array_count_values(array_column($payments, 'payment_method'))
    ];

    // Calculate days until due date and check if overdue
    $due_date = new DateTime($transaction['due_date']);
    $today = new DateTime();
    $days_until_due = $today->diff($due_date)->days;
    $is_overdue = $today > $due_date && $transaction['remaining_amount'] > 0;

    // Calculate payment progress
    $payment_progress = $transaction['total_amount'] > 0 ? 
        ($payment_stats['total_paid'] / $transaction['total_amount']) * 100 : 0;

} catch (PDOException $e) {
    error_log("Transaction view error: " . $e->getMessage());
    $_SESSION['error'] = t('error_fetching_transaction');
    header('Location: list.php');
    exit;
}

$page_title = t('transaction_details');
require_once '../includes/header.php';
?>

<style>
.transaction-view {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.header-card {
  background: var(--primary-gradient);
  color: white;
  padding: 2rem;
  border-radius: 12px;
  margin-bottom: 2rem;
  position: relative;
  overflow: hidden;
}

.header-card::after {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  width: 30%;
  background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.1));
  transform: skewX(-15deg);
}

.status-timeline {
  display: flex;
  align-items: center;
  margin: 2rem 0;
  position: relative;
}

.timeline-point {
  flex: 1;
  text-align: center;
  position: relative;
}

.timeline-point::before {
  content: '';
  height: 3px;
  background: rgba(255, 255, 255, 0.3);
  position: absolute;
  left: 0;
  right: 50%;
  top: 50%;
  transform: translateY(-50%);
}

.timeline-point:first-child::before {
  display: none;
}

.point-icon {
  width: 40px;
  height: 40px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 0.5rem;
  position: relative;
  z-index: 1;
}

.point-icon.active {
  background: #4CAF50;
}

.point-label {
  font-size: 0.85rem;
  opacity: 0.9;
}

.grid-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.info-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.info-card-header {
  padding: 1.5rem;
  border-bottom: 1px solid #eee;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.info-card-header h3 {
  margin: 0;
  font-size: 1.2rem;
  color: var(--text-primary);
}

.info-card-body {
  padding: 1.5rem;
}

.detail-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.detail-item {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.detail-icon {
  width: 32px;
  height: 32px;
  background: #e3f2fd;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #2196F3;
  flex-shrink: 0;
}

.detail-content {
  flex: 1;
}

.detail-label {
  font-size: 0.85rem;
  color: #666;
  margin-bottom: 0.25rem;
}

.detail-value {
  color: var(--text-primary);
}

.payment-progress {
  margin: 2rem 0;
  text-align: center;
}

.progress-circle {
  width: 120px;
  height: 120px;
  margin: 0 auto 1rem;
  position: relative;
}

.progress-circle canvas {
  position: absolute;
  top: 0;
  left: 0;
}

.progress-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 1.5rem;
  font-weight: 600;
}

.payment-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
  margin-top: 2rem;
}

.stat-item {
  text-align: center;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.stat-value {
  font-size: 1.2rem;
  font-weight: 600;
  color: var(--primary-color);
  margin-bottom: 0.25rem;
}

.stat-label {
  font-size: 0.85rem;
  color: #666;
}

.payment-method-chart {
  height: 200px;
  margin-top: 1rem;
}

.timeline-container {
  margin-top: 2rem;
  padding-left: 2rem;
  position: relative;
}

.timeline-container::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 2px;
  background: #eee;
}

.timeline-item {
  position: relative;
  padding-bottom: 2rem;
}

.timeline-item::before {
  content: '';
  position: absolute;
  left: -2rem;
  top: 0;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: var(--primary-color);
  border: 2px solid white;
}

.timeline-date {
  font-size: 0.85rem;
  color: #666;
  margin-bottom: 0.5rem;
}

.timeline-content {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 8px;
}

.header-actions {
  display: flex;
  gap: 1rem;
  z-index: 2;
  position: relative;
}

.header-actions .btn {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  text-decoration: none;
  border: none;
  cursor: pointer;
}

.header-actions .btn-light {
  background: rgba(255, 255, 255, 0.2);
  color: white;
  backdrop-filter: blur(5px);
}

.header-actions .btn-light:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: translateY(-2px);
}

.header-actions .btn i {
  font-size: 1rem;
}
</style>

<div class="transaction-view">
  <!-- Header Card -->
  <div class="header-card">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem;">
      <div>
        <h2 style="margin: 0; font-size: 1.8rem;">
          Transaction #<?php echo $transaction['id']; ?>
        </h2>
        <p style="margin: 0.5rem 0 0; opacity: 0.9;">
          <?php echo htmlspecialchars($transaction['customer_name']); ?>
        </p>
      </div>
      <div class="header-actions">
        <?php if ($transaction['remaining_amount'] > 0): ?>
        <a href="add_payment.php?transaction_id=<?php echo $transaction_id; ?>" class="btn btn-light">
          <i class="fas fa-plus"></i> Add Payment
        </a>
        <?php endif; ?>
        <a href="edit.php?id=<?php echo $transaction_id; ?>" class="btn btn-light">
          <i class="fas fa-edit"></i> Edit
        </a>
        <a href="list.php" class="btn btn-light">
          <i class="fas fa-arrow-left"></i> Back
        </a>
      </div>
    </div>

    <!-- Payment Progress Stats -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
      <div class="stat-box">
        <div style="font-size: 0.9rem; opacity: 0.8;">Total Amount</div>
        <div style="font-size: 1.8rem; font-weight: 600; margin-top: 0.25rem;">
          <?php echo format_currency($transaction['total_amount']); ?>
        </div>
      </div>
      <div class="stat-box">
        <div style="font-size: 0.9rem; opacity: 0.8;">Amount Paid</div>
        <div style="font-size: 1.8rem; font-weight: 600; margin-top: 0.25rem;">
          <?php echo format_currency($payment_stats['total_paid']); ?>
        </div>
      </div>
      <div class="stat-box">
        <div style="font-size: 0.9rem; opacity: 0.8;">Remaining</div>
        <div style="font-size: 1.8rem; font-weight: 600; margin-top: 0.25rem;">
          <?php echo format_currency($transaction['remaining_amount']); ?>
        </div>
      </div>
      <div class="stat-box">
        <div style="font-size: 0.9rem; opacity: 0.8;">Payment Progress</div>
        <div style="font-size: 1.8rem; font-weight: 600; margin-top: 0.25rem;">
          <?php echo round($payment_progress); ?>%
        </div>
      </div>
    </div>

    <!-- Status Timeline -->
    <div class="status-timeline">
      <div class="timeline-point">
        <div class="point-icon active">
          <i class="fas fa-file-invoice"></i>
        </div>
        <div class="point-label">Created</div>
        <div style="font-size: 0.8rem; opacity: 0.8;">
          <?php echo date('M d, Y', strtotime($transaction['created_at'])); ?>
        </div>
      </div>
      <div class="timeline-point">
        <div class="point-icon <?php echo $payment_stats['payment_count'] > 0 ? 'active' : ''; ?>">
          <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="point-label">Payment Started</div>
        <div style="font-size: 0.8rem; opacity: 0.8;">
          <?php echo $payment_stats['payment_count'] > 0 ? 
                        date('M d, Y', strtotime($payments[count($payments)-1]['payment_date'])) : 
                        'Pending'; ?>
        </div>
      </div>
      <div class="timeline-point">
        <div class="point-icon <?php echo $transaction['remaining_amount'] == 0 ? 'active' : ''; ?>">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="point-label">Completed</div>
        <div style="font-size: 0.8rem; opacity: 0.8;">
          <?php echo $transaction['remaining_amount'] == 0 ? 
                        date('M d, Y', strtotime($payments[0]['payment_date'])) : 
                        ($is_overdue ? 'Overdue' : 'Pending'); ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Content Grid -->
  <div class="grid-container">
    <!-- Customer Information -->
    <div class="info-card">
      <div class="info-card-header">
        <div class="detail-icon">
          <i class="fas fa-building"></i>
        </div>
        <h3><?php echo t('customer_information'); ?></h3>
      </div>
      <div class="info-card-body">
        <div class="detail-list">
          <div class="detail-item">
            <div class="detail-content">
              <div class="detail-label"><?php echo t('customer_name'); ?></div>
              <div class="detail-value"><?php echo htmlspecialchars($transaction['customer_name']); ?></div>
            </div>
          </div>
          <div class="detail-item">
            <div class="detail-content">
              <div class="detail-label"><?php echo t('region'); ?></div>
              <div class="detail-value"><?php echo htmlspecialchars($transaction['region']); ?></div>
            </div>
          </div>
          <div class="detail-item">
            <div class="detail-content">
              <div class="detail-label"><?php echo t('hub'); ?></div>
              <div class="detail-value"><?php echo htmlspecialchars($transaction['hub']); ?></div>
            </div>
          </div>
          <div class="detail-item">
            <div class="detail-content">
              <div class="detail-label"><?php echo t('woreda'); ?></div>
              <div class="detail-value"><?php echo htmlspecialchars($transaction['woreda']); ?></div>
            </div>
          </div>
          <div class="detail-item">
            <div class="detail-content">
              <div class="detail-label"><?php echo t('zone'); ?></div>
              <div class="detail-value"><?php echo htmlspecialchars($transaction['Zone']); ?></div>
            </div>
          </div>
          <div class="detail-item">
            <div class="detail-content">
              <div class="detail-label"><?php echo t('level'); ?></div>
              <div class="detail-value"><?php echo htmlspecialchars($transaction['level']); ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Payment Progress -->
    <div class="info-card">
      <div class="info-card-header">
        <div class="detail-icon">
          <i class="fas fa-chart-pie"></i>
        </div>
        <h3>Payment Progress</h3>
      </div>
      <div class="info-card-body">
        <div class="payment-progress">
          <div class="progress-circle">
            <canvas id="progressChart"></canvas>
            <div class="progress-text"><?php echo round($payment_progress); ?>%</div>
          </div>
        </div>
        <div class="payment-stats">
          <div class="stat-item">
            <div class="stat-value"><?php echo $payment_stats['payment_count']; ?></div>
            <div class="stat-label">Payments Made</div>
          </div>
          <div class="stat-item">
            <div class="stat-value"><?php echo $transaction['credit_term_days']; ?></div>
            <div class="stat-label">Credit Term (Days)</div>
          </div>
          <div class="stat-item">
            <div class="stat-value">
              <?php echo $is_overdue ? $transaction['days_overdue'] : '0'; ?>
            </div>
            <div class="stat-label">Days Overdue</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Payment History -->
  <div class="info-card">
    <div class="info-card-header">
      <div class="detail-icon">
        <i class="fas fa-history"></i>
      </div>
      <h3>Payment History</h3>
    </div>
    <div class="info-card-body">
      <?php if (empty($payments)): ?>
      <div style="text-align: center; padding: 2rem;">
        <div style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;">
          <i class="fas fa-receipt"></i>
        </div>
        <p style="color: #666;">No payments have been made yet.</p>
        <?php if ($transaction['remaining_amount'] > 0): ?>
        <a href="add_payment.php?transaction_id=<?php echo $transaction_id; ?>" class="btn btn-primary">
          <i class="fas fa-plus"></i> Add First Payment
        </a>
        <?php endif; ?>
      </div>
      <?php else: ?>
      <div class="timeline-container">
        <?php foreach ($payments as $payment): ?>
        <div class="timeline-item">
          <div class="timeline-date">
            <?php echo date('F d, Y', strtotime($payment['payment_date'])); ?>
          </div>
          <div class="timeline-content">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
              <div>
                <div style="font-weight: 500; margin-bottom: 0.5rem;">
                  Payment Received
                </div>
                <div style="font-size: 0.9rem; color: #666;">
                  <i class="fas fa-money-bill-wave"></i>
                  Amount: <?php echo format_currency($payment['amount']); ?>
                </div>
                <div style="font-size: 0.9rem; color: #666;">
                  <i class="fas fa-credit-card"></i>
                  Method: <?php echo htmlspecialchars($payment['payment_method']); ?>
                </div>
                <?php if ($payment['notes']): ?>
                <div style="font-size: 0.9rem; color: #666; margin-top: 0.5rem;">
                  <i class="fas fa-comment"></i>
                  Note: <?php echo htmlspecialchars($payment['notes']); ?>
                </div>
                <?php endif; ?>
              </div>
              <div
                style="background: #e8f5e9; color: #2e7d32; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem;">
                <i class="fas fa-check-circle"></i> Confirmed
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Progress Circle Chart
const progressCtx = document.getElementById('progressChart').getContext('2d');
new Chart(progressCtx, {
  type: 'doughnut',
  data: {
    datasets: [{
      data: [<?php echo $payment_progress; ?>, <?php echo 100 - $payment_progress; ?>],
      backgroundColor: [
        '<?php echo $payment_progress >= 100 ? "#4CAF50" : 
                           ($payment_progress >= 50 ? "#2196F3" : 
                           ($payment_progress > 0 ? "#FFC107" : "#F44336")); ?>',
        '#eee'
      ],
      borderWidth: 0,
      cutout: '80%'
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
      legend: {
        display: false
      },
      tooltip: {
        enabled: false
      }
    }
  }
});
</script>

<?php require_once '../includes/footer.php'; ?>