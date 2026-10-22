<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
require_once '../includes/languages.php';

// Get all transactions with customer details and payment info
$stmt = $pdo->query("
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
        DATEDIFF(t.due_date, t.created_at) as credit_term_days,
        (SELECT COUNT(*) FROM payments WHERE transaction_id = t.id) as payment_count
    FROM transactions t
    JOIN customers c ON t.customer_id = c.id
    LEFT JOIN (
        SELECT transaction_id, SUM(amount) as total_paid
        FROM payments
        GROUP BY transaction_id
    ) p ON t.id = p.transaction_id
    ORDER BY t.created_at DESC
");
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate summary statistics
$total_credit = array_sum(array_column($transactions, 'total_amount'));
$total_paid = array_sum(array_column($transactions, 'amount_paid'));
$total_remaining = array_sum(array_column($transactions, 'remaining_amount'));
$overdue_count = count(array_filter($transactions, function($t) { return $t['days_overdue'] > 0 && $t['remaining_amount'] > 0; }));

$page_title = t('transactions');
require_once '../includes/header.php';
?>

<div class="view-container">
  <!-- Page Header with Summary Stats -->
  <div class="page-header"
    style="background: linear-gradient(135deg, #2196F3, #1976D2); color: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <div class="header-content">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
          <h2 style="margin: 0; font-size: 1.8rem; display: flex; align-items: center; gap: 0.5rem;">
          <i class="fas fa-list"></i>
          <?php echo t('transactions'); ?>
        </h2>
          <p style="margin: 0.5rem 0 0; opacity: 0.9; font-size: 0.95rem;">
            Manage and track all credit transactions and payments
          </p>
        </div>
        <div class="header-actions" style="display: flex; gap: 1rem;">
          <button onclick="exportTransactions()" class="btn btn-light" style="background: rgba(255, 255, 255, 0.1);">
            <i class="fas fa-download"></i> Export
          </button>
          <a href="add_credit.php" class="btn btn-light" style="background: rgba(255, 255, 255, 0.2);">
            <i class="fas fa-plus"></i> <?php echo t('add_new_credit'); ?>
          </a>
        </div>
      </div>

      <!-- Summary Statistics Cards -->
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 2rem;">
        <div class="stat-card" style="background: rgba(255, 255, 255, 0.1); padding: 1.25rem; border-radius: 8px; backdrop-filter: blur(5px);">
          <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="width: 48px; height: 48px; background: rgba(255, 255, 255, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
              <i class="fas fa-money-bill-wave" style="font-size: 1.5rem;"></i>
            </div>
            <div>
          <div style="font-size: 0.9rem; opacity: 0.9;">Total Credit</div>
          <div style="font-size: 1.4rem; font-weight: 600;"><?php echo format_currency($total_credit); ?></div>
            </div>
          </div>
        </div>
        <div class="stat-card" style="background: rgba(255, 255, 255, 0.1); padding: 1.25rem; border-radius: 8px; backdrop-filter: blur(5px);">
          <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="width: 48px; height: 48px; background: rgba(255, 255, 255, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
              <i class="fas fa-check-circle" style="font-size: 1.5rem;"></i>
            </div>
            <div>
          <div style="font-size: 0.9rem; opacity: 0.9;">Total Paid</div>
          <div style="font-size: 1.4rem; font-weight: 600;"><?php echo format_currency($total_paid); ?></div>
            </div>
          </div>
        </div>
        <div class="stat-card" style="background: rgba(255, 255, 255, 0.1); padding: 1.25rem; border-radius: 8px; backdrop-filter: blur(5px);">
          <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="width: 48px; height: 48px; background: rgba(255, 255, 255, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
              <i class="fas fa-balance-scale" style="font-size: 1.5rem;"></i>
            </div>
            <div>
          <div style="font-size: 0.9rem; opacity: 0.9;">Outstanding Balance</div>
          <div style="font-size: 1.4rem; font-weight: 600;"><?php echo format_currency($total_remaining); ?></div>
            </div>
          </div>
        </div>
        <div class="stat-card" style="background: rgba(255, 255, 255, 0.1); padding: 1.25rem; border-radius: 8px; backdrop-filter: blur(5px);">
          <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="width: 48px; height: 48px; background: rgba(255, 255, 255, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
              <i class="fas fa-exclamation-triangle" style="font-size: 1.5rem;"></i>
            </div>
            <div>
          <div style="font-size: 0.9rem; opacity: 0.9;">Overdue Credits</div>
          <div style="font-size: 1.4rem; font-weight: 600;"><?php echo $overdue_count; ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick Actions Bar -->
  <div style="background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
    <div style="display: flex; justify-content: space-between; align-items: center;">
      <div style="display: flex; gap: 1rem;">
        <button onclick="showOverdueReport()" class="btn btn-outline-danger" style="position: relative;">
          <i class="fas fa-file-alt"></i> Overdue Report
          <?php if ($overdue_count > 0): ?>
          <span class="badge" style="position: absolute; top: -8px; right: -8px; background: #dc3545; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem;">
            <?php echo $overdue_count; ?>
          </span>
          <?php endif; ?>
        </button>
        <button onclick="showPaymentHistory()" class="btn btn-outline-primary">
          <i class="fas fa-history"></i> Payment History
        </button>
        <button onclick="showAnalytics()" class="btn btn-outline-success">
          <i class="fas fa-chart-bar"></i> Analytics
        </button>
      </div>
      <div style="display: flex; gap: 0.5rem;">
        <button onclick="toggleFilters()" class="btn btn-outline-secondary">
          <i class="fas fa-sliders-h"></i> Toggle Filters
        </button>
      </div>
    </div>
  </div>

  <!-- Modals -->
  <!-- Overdue Report Modal -->
  <div id="overdueReportModal" class="modal" style="display: none; position: fixed; top: 70px; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div class="modal-content" style="background: white; width: 90%; max-width: 800px; margin: 2rem auto; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
      <div style="padding: 1.5rem; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0; font-size: 1.2rem; color: #dc3545;">
          <i class="fas fa-exclamation-triangle"></i> Overdue Report
        </h3>
        <button onclick="closeModal('overdueReportModal')" class="btn btn-link" style="color: #666;">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div style="padding: 1.5rem; max-height: 70vh; overflow-y: auto;">
        <div class="overdue-summary" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
          <div class="stat-card" style="background: #fff5f5; padding: 1rem; border-radius: 8px; border: 1px solid #ffebee;">
            <div style="font-size: 0.9rem; color: #dc3545;">Total Overdue Amount</div>
            <div style="font-size: 1.4rem; font-weight: 600; color: #dc3545;">
              <?php 
              $total_overdue = array_sum(array_map(function($t) {
                return $t['days_overdue'] > 0 ? $t['remaining_amount'] : 0;
              }, $transactions));
              echo format_currency($total_overdue);
              ?>
            </div>
          </div>
          <div class="stat-card" style="background: #fff5f5; padding: 1rem; border-radius: 8px; border: 1px solid #ffebee;">
            <div style="font-size: 0.9rem; color: #dc3545;">Average Days Overdue</div>
            <div style="font-size: 1.4rem; font-weight: 600; color: #dc3545;">
              <?php 
              $overdue_transactions = array_filter($transactions, function($t) {
                return $t['days_overdue'] > 0;
              });
              $avg_days = count($overdue_transactions) > 0 ? 
                array_sum(array_column($overdue_transactions, 'days_overdue')) / count($overdue_transactions) : 0;
              echo round($avg_days);
              ?> days
            </div>
          </div>
        </div>
        <div class="overdue-list">
          <h4 style="margin-bottom: 1rem;">Overdue Transactions</h4>
          <div style="display: grid; gap: 1rem;">
            <?php foreach ($transactions as $transaction): ?>
              <?php if ($transaction['days_overdue'] > 0 && $transaction['remaining_amount'] > 0): ?>
              <div class="overdue-item" style="background: #fff; border: 1px solid #ffebee; border-radius: 8px; padding: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                  <div style="font-weight: 500;"><?php echo htmlspecialchars($transaction['customer_name']); ?></div>
                  <span class="badge badge-danger" style="background: #ffebee; color: #dc3545; padding: 0.25rem 0.5rem; border-radius: 12px;">
                    <?php echo $transaction['days_overdue']; ?> days overdue
                  </span>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; font-size: 0.9rem;">
                  <div>
                    <div style="color: #666;">Amount</div>
                    <div style="font-weight: 500;"><?php echo format_currency($transaction['remaining_amount']); ?></div>
                  </div>
                  <div>
                    <div style="color: #666;">Due Date</div>
                    <div style="font-weight: 500;"><?php echo date('M d, Y', strtotime($transaction['due_date'])); ?></div>
                  </div>
                  <div>
                    <div style="color: #666;">Location</div>
                    <div style="font-weight: 500;"><?php echo htmlspecialchars($transaction['region']); ?></div>
                  </div>
                </div>
              </div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Payment History Modal -->
  <div id="paymentHistoryModal" class="modal" style="display: none; position: fixed; top: 70px; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div class="modal-content" style="background: white; width: 90%; max-width: 800px; margin: 2rem auto; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
      <div style="padding: 1.5rem; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0; font-size: 1.2rem; color: #2196F3;">
          <i class="fas fa-history"></i> Payment History
        </h3>
        <button onclick="closeModal('paymentHistoryModal')" class="btn btn-link" style="color: #666;">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div style="padding: 1.5rem; max-height: 70vh; overflow-y: auto;">
        <div class="payment-summary" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
          <div class="stat-card" style="background: #e3f2fd; padding: 1rem; border-radius: 8px; border: 1px solid #bbdefb;">
            <div style="font-size: 0.9rem; color: #1565C0;">Total Payments</div>
            <div style="font-size: 1.4rem; font-weight: 600; color: #1565C0;">
              <?php echo format_currency($total_paid); ?>
            </div>
          </div>
          <div class="stat-card" style="background: #e3f2fd; padding: 1rem; border-radius: 8px; border: 1px solid #bbdefb;">
            <div style="font-size: 0.9rem; color: #1565C0;">Average Payment</div>
            <div style="font-size: 1.4rem; font-weight: 600; color: #1565C0;">
              <?php 
              $payment_count = array_sum(array_column($transactions, 'payment_count'));
              echo $payment_count > 0 ? format_currency($total_paid / $payment_count) : format_currency(0);
              ?>
            </div>
          </div>
        </div>
        <div class="payment-list">
          <h4 style="margin-bottom: 1rem;">Recent Payments</h4>
          <div style="display: grid; gap: 1rem;">
            <?php
            // Get recent payments
            $stmt = $pdo->query("
              SELECT p.*, t.id as transaction_id, c.name as customer_name
              FROM payments p
              JOIN transactions t ON p.transaction_id = t.id
              JOIN customers c ON t.customer_id = c.id
              ORDER BY p.created_at DESC
              LIMIT 10
            ");
            $recent_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($recent_payments as $payment):
            ?>
            <div class="payment-item" style="background: #fff; border: 1px solid #e3f2fd; border-radius: 8px; padding: 1rem;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <div style="font-weight: 500;"><?php echo htmlspecialchars($payment['customer_name']); ?></div>
                <span class="badge badge-primary" style="background: #e3f2fd; color: #1565C0; padding: 0.25rem 0.5rem; border-radius: 12px;">
                  <?php echo date('M d, Y', strtotime($payment['created_at'])); ?>
                </span>
              </div>
              <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; font-size: 0.9rem;">
                <div>
                  <div style="color: #666;">Amount</div>
                  <div style="font-weight: 500;"><?php echo format_currency($payment['amount']); ?></div>
                </div>
                <div>
                  <div style="color: #666;">Transaction ID</div>
                  <div style="font-weight: 500;">#<?php echo $payment['transaction_id']; ?></div>
                </div>
                <div>
                  <div style="color: #666;">Payment Method</div>
                  <div style="font-weight: 500;"><?php echo htmlspecialchars($payment['payment_method']); ?></div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Analytics Modal -->
  <div id="analyticsModal" class="modal" style="display: none; position: fixed; top: 70px; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div class="modal-content" style="background: white; width: 90%; max-width: 800px; margin: 2rem auto; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
      <div style="padding: 1.5rem; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0; font-size: 1.2rem; color: #4CAF50;">
          <i class="fas fa-chart-bar"></i> Analytics
        </h3>
        <button onclick="closeModal('analyticsModal')" class="btn btn-link" style="color: #666;">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div style="padding: 1.5rem; max-height: 70vh; overflow-y: auto;">
        <div class="analytics-summary" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
          <div class="stat-card" style="background: #e8f5e9; padding: 1rem; border-radius: 8px; border: 1px solid #c8e6c9;">
            <div style="font-size: 0.9rem; color: #2E7D32;">Collection Rate</div>
            <div style="font-size: 1.4rem; font-weight: 600; color: #2E7D32;">
              <?php 
              $collection_rate = $total_credit > 0 ? ($total_paid / $total_credit) * 100 : 0;
              echo round($collection_rate, 1);
              ?>%
            </div>
          </div>
          <div class="stat-card" style="background: #e8f5e9; padding: 1rem; border-radius: 8px; border: 1px solid #c8e6c9;">
            <div style="font-size: 0.9rem; color: #2E7D32;">Average Credit Term</div>
            <div style="font-size: 1.4rem; font-weight: 600; color: #2E7D32;">
              <?php 
              $avg_term = array_sum(array_column($transactions, 'credit_term_days')) / count($transactions);
              echo round($avg_term);
              ?> days
            </div>
          </div>
        </div>
        <div class="analytics-charts">
          <h4 style="margin-bottom: 1rem;">Payment Trends</h4>
          <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
            <canvas id="paymentTrendsChart"></canvas>
          </div>
          <h4 style="margin-bottom: 1rem;">Status Distribution</h4>
          <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
            <canvas id="statusDistributionChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Content Card -->
  <div class="content-card" style="background: white; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
    <!-- Advanced Filters Panel -->
    <div id="advancedFilters" class="advanced-filters" style="padding: 1.5rem; border-bottom: 1px solid #eee;">
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
        <!-- Search Box -->
        <div class="filter-group">
          <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Search</label>
          <div style="position: relative;">
          <input type="text" id="searchInput" placeholder="Search transactions..."
              style="width: 100%; padding: 0.5rem 1rem; padding-left: 2.5rem; border: 1px solid #ddd; border-radius: 6px;">
            <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #666;"></i>
          </div>
        </div>

        <!-- Status Filter -->
        <div class="filter-group">
          <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Status</label>
          <select id="statusFilter" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 6px;">
            <option value="all">All Statuses</option>
            <option value="paid">Paid</option>
            <option value="partial">Partial</option>
            <option value="overdue">Overdue</option>
            <option value="pending">Pending</option>
          </select>
        </div>

        <!-- Amount Range -->
        <div class="filter-group">
          <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Amount Range</label>
          <div style="display: flex; gap: 0.5rem;">
            <input type="number" id="minAmount" placeholder="Min" 
              style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 6px;">
            <input type="number" id="maxAmount" placeholder="Max" 
              style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 6px;">
          </div>
        </div>

        <!-- Date Range -->
        <div class="filter-group">
          <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Date Range</label>
          <div style="display: flex; gap: 0.5rem;">
            <input type="date" id="startDate" 
              style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 6px;">
            <input type="date" id="endDate" 
              style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 6px;">
          </div>
        </div>

        <!-- Region Filter -->
        <div class="filter-group">
          <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Region</label>
          <select id="regionFilter" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 6px;">
            <option value="">All Regions</option>
            <?php
            $regions = array_unique(array_column($transactions, 'region'));
            foreach ($regions as $region) {
              if (!empty($region)) {
                echo "<option value='" . htmlspecialchars($region) . "'>" . htmlspecialchars($region) . "</option>";
              }
            }
            ?>
          </select>
        </div>

        <!-- Zone Filter -->
        <div class="filter-group">
          <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Zone</label>
          <select id="zoneFilter" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 6px;">
            <option value="">All Zones</option>
            <?php
            $zones = array_unique(array_column($transactions, 'Zone'));
            foreach ($zones as $zone) {
              if (!empty($zone)) {
                echo "<option value='" . htmlspecialchars($zone) . "'>" . htmlspecialchars($zone) . "</option>";
              }
            }
            ?>
          </select>
        </div>

        <!-- Level Filter -->
        <div class="filter-group">
          <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Facility Level</label>
          <select id="levelFilter" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 6px;">
            <option value="">All Levels</option>
            <?php
            $levels = array_unique(array_column($transactions, 'level'));
            foreach ($levels as $level) {
              if (!empty($level)) {
                echo "<option value='" . htmlspecialchars($level) . "'>" . htmlspecialchars($level) . "</option>";
              }
            }
            ?>
          </select>
        </div>
      </div>

      <!-- Filter Actions -->
      <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1rem;">
        <button onclick="resetFilters()" class="btn btn-outline" style="padding: 0.5rem 1rem;">
          <i class="fas fa-undo"></i> Reset Filters
        </button>
        <button onclick="applyFilters()" class="btn btn-primary" style="padding: 0.5rem 1rem;">
          <i class="fas fa-filter"></i> Apply Filters
        </button>
      </div>
    </div>

    <!-- Table Container -->
    <div class="table-container" style="padding: 1rem; overflow-x: auto;">
      <table class="table">
        <thead>
          <tr>
            <th><?php echo t('id'); ?></th>
            <th><?php echo t('customer'); ?></th>
            <th><?php echo t('amount'); ?></th>
            <th><?php echo t('payment_progress'); ?></th>
            <th><?php echo t('dates'); ?></th>
            <th><?php echo t('status'); ?></th>
            <th><?php echo t('actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($transactions as $transaction): ?>
          <?php
          // Determine transaction status
          $status = '';
          if ($transaction['remaining_amount'] == 0) {
              $status = 'paid';
          } elseif ($transaction['remaining_amount'] < $transaction['total_amount']) {
              $status = 'partial';
          } elseif ($transaction['days_overdue'] > 0) {
              $status = 'overdue';
          } else {
              $status = 'pending';
          }
          ?>
          <tr class="transaction-row" data-status="<?php echo $status; ?>">
            <td>
              <strong>#<?php echo $transaction['id']; ?></strong>
            </td>
            <td>
              <div class="customer-info" style="display: flex; gap: 1rem; align-items: center;">
                <div class="avatar"
                  style="width: 40px; height: 40px; background: #e3f2fd; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                  <i class="fas fa-user" style="color: #2196F3;"></i>
                </div>
                <div>
                  <div style="font-weight: 500;"><?php echo htmlspecialchars($transaction['customer_name']); ?></div>
                  <?php if ($transaction['region'] || $transaction['hub'] || $transaction['woreda'] || $transaction['Zone'] || $transaction['level']): ?>
                  <div style="font-size: 0.85rem; color: #666;">
                    <?php if ($transaction['region']): ?>
                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($transaction['region']); ?>
                    <?php endif; ?>
                    <?php if ($transaction['hub']): ?>
                    <br><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($transaction['hub']); ?>
                    <?php endif; ?>
                    <?php if ($transaction['woreda']): ?>
                    <br><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($transaction['woreda']); ?>
                    <?php endif; ?>
                    <?php if ($transaction['Zone']): ?>
                    <br><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($transaction['Zone']); ?>
                    <?php endif; ?>
                    <?php if ($transaction['level']): ?>
                    <br><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($transaction['level']); ?>
                    <?php endif; ?>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td>
              <div style="font-weight: 500;"><?php echo format_currency($transaction['total_amount']); ?></div>
              <?php if ($transaction['amount_paid'] > 0): ?>
              <div style="font-size: 0.85rem; color: #666;">
                Paid: <?php echo format_currency($transaction['amount_paid']); ?>
              </div>
              <?php endif; ?>
            </td>
            <td style="width: 200px;">
              <?php
              $payment_progress = $transaction['total_amount'] > 0 ? 
                  ($transaction['amount_paid'] / $transaction['total_amount']) * 100 : 0;
              $progress_color = $payment_progress >= 100 ? '#4CAF50' : 
                              ($payment_progress >= 50 ? '#2196F3' : 
                              ($payment_progress > 0 ? '#FFC107' : '#F44336'));
              ?>
              <div style="margin-bottom: 0.5rem;">
                <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.25rem;">
                  <span>Payment Progress</span>
                  <span><?php echo round($payment_progress); ?>%</span>
                </div>
                <div class="progress-bar" style="height: 6px; background: #eee; border-radius: 3px; overflow: hidden;">
                  <div class="progress-value"
                    style="width: <?php echo $payment_progress; ?>%; height: 100%; background: <?php echo $progress_color; ?>; transition: width 0.3s ease;">
                  </div>
                </div>
              </div>
              <?php if ($transaction['payment_count'] > 0): ?>
              <div style="font-size: 0.85rem; color: #666;">
                <i class="fas fa-receipt"></i> <?php echo $transaction['payment_count']; ?> payments
              </div>
              <?php endif; ?>
            </td>
            <td>
              <div style="font-size: 0.9rem;">
                <div>
                  <i class="fas fa-calendar-plus"></i> Created:
                  <span><?php echo date('M d, Y', strtotime($transaction['created_at'])); ?></span>
                </div>
                <div style="margin-top: 0.25rem;">
                  <i class="fas fa-calendar-check"></i> Due:
                  <span><?php echo date('M d, Y', strtotime($transaction['due_date'])); ?></span>
                  <?php if ($transaction['days_overdue'] > 0 && $transaction['remaining_amount'] > 0): ?>
                  <span class="badge badge-danger"
                    style="font-size: 0.75rem; padding: 0.25rem 0.5rem; background: #ffebee; color: #c62828; border-radius: 12px; margin-left: 0.5rem;">
                    <?php echo $transaction['days_overdue']; ?> days overdue
                  </span>
                  <?php endif; ?>
                </div>
                <?php if ($transaction['credit_term_days'] > 0): ?>
                <div style="font-size: 0.85rem; color: #666; margin-top: 0.25rem;">
                  <i class="fas fa-clock"></i> <?php echo $transaction['credit_term_days']; ?> day term
                </div>
                <?php endif; ?>
              </div>
            </td>
            <td>
              <?php
              $status_class = '';
              $status_text = '';
              $status_icon = '';
              if ($transaction['remaining_amount'] == 0) {
                  $status_class = 'status-paid';
                  $status_text = t('paid');
                  $status_icon = 'check-circle';
              } elseif ($transaction['remaining_amount'] < $transaction['total_amount']) {
                  $status_class = 'status-partial';
                  $status_text = t('partial');
                  $status_icon = 'clock';
              } elseif ($transaction['days_overdue'] > 0) {
                  $status_class = 'status-overdue';
                  $status_text = t('overdue');
                  $status_icon = 'exclamation-circle';
              } else {
                  $status_class = 'status-pending';
                  $status_text = t('pending');
                  $status_icon = 'hourglass-half';
              }
              ?>
              <span class="status-badge <?php echo $status_class; ?>"
                style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: 500;">
                <i class="fas fa-<?php echo $status_icon; ?>"></i>
                <?php echo $status_text; ?>
              </span>
              <?php if ($transaction['remaining_amount'] > 0): ?>
              <div style="font-size: 0.85rem; color: #666; margin-top: 0.5rem;">
                Remaining: <?php echo format_currency($transaction['remaining_amount']); ?>
              </div>
              <?php endif; ?>
            </td>
            <td>
              <div class="btn-group" style="display: flex; gap: 0.5rem;">
                <a href="view.php?id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-info"
                  style="display: inline-flex; align-items: center; gap: 0.25rem;">
                  <i class="fas fa-eye"></i> <?php echo t('view'); ?>
                </a>
                <?php if ($transaction['remaining_amount'] > 0): ?>
                <a href="add_payment.php?transaction_id=<?php echo $transaction['id']; ?>"
                  class="btn btn-sm btn-success" style="display: inline-flex; align-items: center; gap: 0.25rem;">
                  <i class="fas fa-plus"></i> <?php echo t('add_payment'); ?>
                </a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<style>
/* Enhanced Styles */
.view-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
}

.page-header {
  transition: all 0.3s ease;
}

.page-header:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}

.stat-card {
  transition: all 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  background: rgba(255, 255, 255, 0.15);
}

.btn {
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  border: none;
}

.btn-light {
  background: rgba(255, 255, 255, 0.2);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.3);
}

.btn-light:hover {
  background: rgba(255, 255, 255, 0.3);
}

.btn-outline-danger {
  border: 1px solid #dc3545;
  color: #dc3545;
  background: transparent;
}

.btn-outline-primary {
  border: 1px solid #2196F3;
  color: #2196F3;
  background: transparent;
}

.btn-outline-success {
  border: 1px solid #4CAF50;
  color: #4CAF50;
  background: transparent;
}

.btn-outline-secondary {
  border: 1px solid #6c757d;
  color: #6c757d;
  background: transparent;
}

/* Enhanced Table Styles */
.table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  margin-top: 1rem;
}

.table th {
  background: #f8f9fa;
  padding: 1rem;
  font-weight: 600;
  color: #444;
  text-align: left;
  border-bottom: 2px solid #eee;
  white-space: nowrap;
}

.table td {
  padding: 1rem;
  border-bottom: 1px solid #eee;
  vertical-align: top;
}

.table tr:hover {
  background-color: #f8f9fa;
}

/* Enhanced Status Badges */
.status-badge {
  padding: 0.4rem 0.8rem;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

/* Enhanced Progress Bar */
.progress-bar {
  height: 6px;
  background: #eee;
  border-radius: 3px;
  overflow: hidden;
  margin-top: 0.5rem;
  box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
}

.progress-value {
  height: 100%;
  transition: width 0.3s ease;
  box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

/* Enhanced Filter Styles */
.filter-group {
  margin-bottom: 1rem;
}

.filter-group label {
  color: #666;
  font-size: 0.9rem;
  margin-bottom: 0.5rem;
  display: block;
}

/* Responsive Design */
@media (max-width: 768px) {
  .view-container {
    padding: 1rem;
  }
  
  .header-actions {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .advanced-filters {
    padding: 1rem;
  }
  
  .filter-group {
    margin-bottom: 0.5rem;
  }

  .table td {
    padding: 0.75rem;
  }
}

/* Modal Styles */
.modal {
  display: none;
  position: fixed;
  top: 70px;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.5);
  z-index: 9999;
}

.modal-content {
  background: white;
  width: 90%;
  max-width: 800px;
  margin: 2rem auto;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  animation: slideIn 0.3s ease;
  position: relative;
  z-index: 10000;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideIn {
  from { transform: translateY(-20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.btn-link {
  background: none;
  border: none;
  color: #666;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 50%;
  transition: all 0.2s;
}

.btn-link:hover {
  background: #f8f9fa;
  color: #333;
}

/* Enhanced Card Styles */
.stat-card {
  transition: all 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

/* Badge Styles */
.badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
}

.badge-danger {
  background: #ffebee;
  color: #dc3545;
}

.badge-primary {
  background: #e3f2fd;
  color: #1565C0;
}

.badge-success {
  background: #e8f5e9;
  color: #2E7D32;
}
</style>

<script>
// Enhanced Filter Functionality
let currentFilters = {
  search: '',
  status: 'all',
  minAmount: '',
  maxAmount: '',
  startDate: '',
  endDate: '',
  region: '',
  zone: '',
  level: ''
};

function applyFilters() {
  currentFilters = {
    search: document.getElementById('searchInput').value.toLowerCase(),
    status: document.getElementById('statusFilter').value,
    minAmount: document.getElementById('minAmount').value,
    maxAmount: document.getElementById('maxAmount').value,
    startDate: document.getElementById('startDate').value,
    endDate: document.getElementById('endDate').value,
    region: document.getElementById('regionFilter').value,
    zone: document.getElementById('zoneFilter').value,
    level: document.getElementById('levelFilter').value
  };

  filterTransactions();
}

function resetFilters() {
  document.getElementById('searchInput').value = '';
  document.getElementById('statusFilter').value = 'all';
  document.getElementById('minAmount').value = '';
  document.getElementById('maxAmount').value = '';
  document.getElementById('startDate').value = '';
  document.getElementById('endDate').value = '';
  document.getElementById('regionFilter').value = '';
  document.getElementById('zoneFilter').value = '';
  document.getElementById('levelFilter').value = '';
  
  currentFilters = {
    search: '',
    status: 'all',
    minAmount: '',
    maxAmount: '',
    startDate: '',
    endDate: '',
    region: '',
    zone: '',
    level: ''
  };

  filterTransactions();
}

function filterTransactions() {
  const rows = document.querySelectorAll('.transaction-row');

  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    const rowStatus = row.dataset.status;
    const amount = parseFloat(row.querySelector('td:nth-child(3)').textContent.replace(/[^0-9.-]/g, ''));
    const createdDate = row.querySelector('td:nth-child(5)').textContent;
    
    // Apply all filters
    const matchesSearch = text.includes(currentFilters.search);
    const matchesStatus = currentFilters.status === 'all' || rowStatus === currentFilters.status;
    const matchesAmount = (!currentFilters.minAmount || amount >= parseFloat(currentFilters.minAmount)) &&
                         (!currentFilters.maxAmount || amount <= parseFloat(currentFilters.maxAmount));
    const matchesDate = (!currentFilters.startDate || createdDate >= currentFilters.startDate) &&
                       (!currentFilters.endDate || createdDate <= currentFilters.endDate);
    const matchesRegion = !currentFilters.region || row.querySelector('td:nth-child(2)').textContent.includes(currentFilters.region);
    const matchesZone = !currentFilters.zone || row.querySelector('td:nth-child(2)').textContent.includes(currentFilters.zone);
    const matchesLevel = !currentFilters.level || row.querySelector('td:nth-child(2)').textContent.includes(currentFilters.level);
    
    row.style.display = matchesSearch && matchesStatus && matchesAmount && matchesDate && 
                       matchesRegion && matchesZone && matchesLevel ? '' : 'none';
  });
}

// Modal Functions
function showOverdueReport() {
  document.getElementById('overdueReportModal').style.display = 'block';
}

function showPaymentHistory() {
  document.getElementById('paymentHistoryModal').style.display = 'block';
}

function showAnalytics() {
  document.getElementById('analyticsModal').style.display = 'block';
  initializeCharts();
}

function closeModal(modalId) {
  document.getElementById(modalId).style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
  if (event.target.classList.contains('modal')) {
    event.target.style.display = 'none';
  }
}

// Initialize Charts
function initializeCharts() {
  // Payment Trends Chart
  const paymentTrendsCtx = document.getElementById('paymentTrendsChart').getContext('2d');
  new Chart(paymentTrendsCtx, {
    type: 'line',
    data: {
      labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
      datasets: [{
        label: 'Payments',
        data: [12000, 19000, 15000, 25000, 22000, 30000],
        borderColor: '#2196F3',
        tension: 0.4
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'top',
        }
      }
    }
  });

  // Status Distribution Chart
  const statusDistributionCtx = document.getElementById('statusDistributionChart').getContext('2d');
  new Chart(statusDistributionCtx, {
    type: 'doughnut',
    data: {
      labels: ['Paid', 'Partial', 'Overdue', 'Pending'],
      datasets: [{
        data: [30, 20, 15, 35],
        backgroundColor: ['#4CAF50', '#2196F3', '#dc3545', '#FFC107']
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'top',
        }
      }
    }
  });
}

// Add smooth scrolling
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
    e.preventDefault();
    document.querySelector(this.getAttribute('href')).scrollIntoView({
      behavior: 'smooth'
    });
  });
});

// Export Functionality
function exportTransactions() {
  // Create a temporary form to submit the export request
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = 'export_transactions.php';

  // Add any necessary filters as hidden inputs
  const filters = {
    search: document.getElementById('searchInput').value,
    status: document.getElementById('statusFilter').value,
    minAmount: document.getElementById('minAmount').value,
    maxAmount: document.getElementById('maxAmount').value,
    startDate: document.getElementById('startDate').value,
    endDate: document.getElementById('endDate').value,
    region: document.getElementById('regionFilter').value,
    zone: document.getElementById('zoneFilter').value,
    level: document.getElementById('levelFilter').value
  };

  // Add filters to form
  Object.entries(filters).forEach(([key, value]) => {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = key;
    input.value = value;
    form.appendChild(input);
  });

  // Add CSRF token if needed
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
  if (csrfToken) {
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = 'csrf_token';
    csrfInput.value = csrfToken;
    form.appendChild(csrfInput);
  }

  // Add form to body and submit
  document.body.appendChild(form);
  form.submit();
  document.body.removeChild(form);
}

function toggleFilters() {
    const filtersPanel = document.getElementById('advancedFilters');
    const isVisible = filtersPanel.style.display !== 'none';
    filtersPanel.style.display = isVisible ? 'none' : 'block';
    
    // Update button text and icon
    const button = document.querySelector('.btn-outline-secondary');
    const icon = button.querySelector('i');
    if (isVisible) {
        button.innerHTML = '<i class="fas fa-sliders-h"></i> Show Filters';
        icon.classList.remove('fa-times');
        icon.classList.add('fa-sliders-h');
    } else {
        button.innerHTML = '<i class="fas fa-times"></i> Hide Filters';
        icon.classList.remove('fa-sliders-h');
        icon.classList.add('fa-times');
    }
}

// Initialize filters panel visibility
document.addEventListener('DOMContentLoaded', function() {
    const filtersPanel = document.getElementById('advancedFilters');
    filtersPanel.style.display = 'none'; // Hide filters by default
});
</script>

<?php require_once '../includes/footer.php'; ?>