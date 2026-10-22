<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
require_once '../includes/languages.php';

// Get customer ID from URL
$customer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch customer details with enhanced statistics
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        c.region,
        c.woreda, -- Updated here
        c.zone,
        c.level,
        COUNT(t.id) as total_transactions,
        COALESCE(SUM(t.amount), 0) as total_credit,
        COALESCE(SUM(t.amount - (
            SELECT COALESCE(SUM(p.amount), 0)
            FROM payments p
            WHERE p.transaction_id = t.id
        )), 0) as total_remaining,
        MAX(t.due_date) as latest_due_date,
        SUM(CASE WHEN t.status = 'paid' THEN 1 ELSE 0 END) as paid_transactions,
        SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) as pending_transactions,
        SUM(CASE WHEN t.status = 'overdue' THEN 1 ELSE 0 END) as overdue_transactions,
        MIN(t.created_at) as first_transaction_date,
        MAX(t.created_at) as last_transaction_date
    FROM customers c
    LEFT JOIN transactions t ON c.id = t.customer_id
    WHERE c.id = ?
    GROUP BY c.id
");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    $_SESSION['error'] = t('customer_not_found');
    header('Location: list.php');
    exit;
}

// Fetch recent transactions with enhanced details
$stmt = $pdo->prepare("
    SELECT 
        t.*,
        COALESCE(t.amount, 0) as total_amount,
        COALESCE(t.amount - (
            SELECT COALESCE(SUM(p.amount), 0)
            FROM payments p
            WHERE p.transaction_id = t.id
        ), t.amount) as remaining_amount,
        DATEDIFF(CURRENT_DATE, t.due_date) as days_overdue,
        (
            SELECT COUNT(p.id)
            FROM payments p
            WHERE p.transaction_id = t.id
        ) as payment_count,
        (
            SELECT MAX(p.payment_date)
            FROM payments p
            WHERE p.transaction_id = t.id
        ) as last_payment_date
    FROM transactions t
    WHERE t.customer_id = ?
    ORDER BY t.created_at DESC
    LIMIT 10
");
$stmt->execute([$customer_id]);
$recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate customer activity metrics
$activity_period = null;
$transaction_frequency = 'N/A';
if ($customer['first_transaction_date'] && $customer['last_transaction_date']) {
    $first_date = new DateTime($customer['first_transaction_date']);
    $last_date = new DateTime($customer['last_transaction_date']);
    $activity_period = $first_date->diff($last_date);
    
    if ($customer['total_transactions'] > 1) {
        $days = $activity_period->days ?: 1;
        $avg_days = round($days / ($customer['total_transactions'] - 1));
        $transaction_frequency = $avg_days . ' days';
    }
}

// Include header
$page_title = t('customer_details');
require_once '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($customer['name']); ?> - Health Facility Details</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <!-- Add Chart.js for visualizations -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
  .profile-header {
    background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
    color: white;
    padding: 2rem;
    border-radius: 10px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }

  .customer-avatar {
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin-bottom: 1rem;
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
  }

  .stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s;
  }

  .stat-card:hover {
    transform: translateY(-5px);
  }

  .timeline {
    position: relative;
    padding: 1rem;
  }

  .timeline-item {
    padding: 1rem;
    border-left: 2px solid #3498db;
    margin-bottom: 1rem;
    position: relative;
  }

  .timeline-item::before {
    content: '';
    width: 12px;
    height: 12px;
    background: #3498db;
    border-radius: 50%;
    position: absolute;
    left: -7px;
    top: 1.5rem;
  }

  .chart-container {
    background: white;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
  }

  .notes-section {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
  }

  .note-item {
    border-left: 3px solid #3498db;
    padding: 1rem;
    margin-bottom: 1rem;
    background: #f8f9fa;
  }

  .related-contacts {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
  }

  .contact-card {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  }

  .payment-history {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
  }

  .payment-chart {
    height: 200px;
    margin-bottom: 1rem;
  }

  .modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
  }

  .modal-dialog {
    margin: 2rem auto;
    max-width: 600px;
    background: white;
    border-radius: 8px;
    overflow: hidden;
  }

  .modal-dialog.modal-lg {
    max-width: 800px;
  }

  .modal-header {
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .modal-body {
    padding: 1rem;
  }

  .close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
  }

  .note-item {
    border-left: 3px solid;
    margin-bottom: 1rem;
    padding: 0.75rem;
  }

  .note-item.general {
    border-color: #3498db;
  }

  .note-item.payment {
    border-color: #2ecc71;
  }

  .note-item.contact {
    border-color: #e74c3c;
  }

  .note-item.important {
    border-color: #f1c40f;
  }

  .note-meta {
    font-size: 0.875rem;
    color: #666;
    margin-bottom: 0.5rem;
  }

  .note-meta {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
  }

  .note-info {
    display: flex;
    gap: 1rem;
    font-size: 0.875rem;
    color: #666;
  }

  .note-info i {
    margin-right: 0.25rem;
  }

  .note-actions {
    display: flex;
    gap: 0.5rem;
  }

  .note-actions .btn-link {
    padding: 0.25rem;
    color: #666;
  }

  .note-actions .btn-link:hover {
    color: #333;
  }

  .note-type {
    padding: 0.125rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    background: #f8f9fa;
  }

  .note-item.important .note-type {
    background: #fff3cd;
    color: #856404;
  }

  .note-item.payment .note-type {
    background: #d4edda;
    color: #155724;
  }

  .note-item.contact .note-type {
    background: #cce5ff;
    color: #004085;
  }

  .payment-history-status {
    color: #000000;
  }
  </style>
</head>

<body>
  <?php include '../includes/header.php'; ?>

  <div class="container">
    <!-- Enhanced Profile Header -->
    <div class="profile-header">
      <div class="customer-avatar">
        <?php echo strtoupper(substr($customer['name'], 0, 2)); ?>
      </div>
      <h1><?php echo htmlspecialchars($customer['name']); ?></h1>
      <div class="profile-meta">
        <span class="customer-id">ID: #<?php echo $customer['id']; ?></span>
        <span class="status-badge status-<?php echo $customer['status']; ?>">
          <i class="fas fa-circle"></i> <?php echo ucfirst($customer['status']); ?>
        </span>
        <span class="member-since">
          <i class="fas fa-clock"></i> Member since <?php echo date('F j, Y', strtotime($customer['created_at'])); ?>
        </span>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="header-actions" style="margin-bottom: 2rem;">
      <a href="edit.php?id=<?php echo $customer_id; ?>" class="btn btn-primary">
        <i class="fas fa-edit"></i> Edit Health Facility
      </a>
      <a href="../transactions/add_credit.php?customer_id=<?php echo $customer_id; ?>" class="btn btn-success">
        <i class="fas fa-plus-circle"></i> Add Credit
      </a>
      <a href="#" class="btn btn-info" onclick="showPaymentHistory()">
        <i class="fas fa-history"></i> Payment History
      </a>
      <a href="#" class="btn btn-warning" onclick="addNote()">
        <i class="fas fa-sticky-note"></i> Add Note
      </a>
      <a href="list.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to List
      </a>
    </div>

    <!-- Enhanced Stats Grid -->
    <div class="stats-grid">
      <div class="stat-card primary">
        <div class="stat-icon">
          <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-content">
          <h3>Total Credit</h3>
          <div class="stat-number"><?php echo format_currency($customer['total_credit']); ?></div>
          <div class="stat-subtitle"><?php echo $customer['total_transactions']; ?> transactions</div>
        </div>
      </div>
      <div class="stat-card <?php echo $customer['total_remaining'] > 0 ? 'warning' : 'success'; ?>">
        <div class="stat-icon">
          <i class="fas fa-wallet"></i>
        </div>
        <div class="stat-content">
          <h3>Outstanding Balance</h3>
          <div class="stat-number"><?php echo format_currency($customer['total_remaining']); ?></div>
          <div class="stat-subtitle">
            <?php echo $customer['total_remaining'] > 0 ? 'Pending payment' : 'All paid'; ?>
          </div>
        </div>
      </div>
      <div class="stat-card info">
        <div class="stat-icon">
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
          <h3>Transaction Status</h3>
          <div class="stat-number">
            <?php echo $customer['paid_transactions']; ?> Paid /
            <?php echo $customer['pending_transactions']; ?> Pending
          </div>
          <div class="stat-subtitle">
            <?php echo $customer['overdue_transactions']; ?> overdue
          </div>
        </div>
      </div>
      <div class="stat-card success">
        <div class="stat-icon">
          <i class="fas fa-calendar-check"></i>
        </div>
        <div class="stat-content">
          <h3>Activity Period</h3>
          <div class="stat-number"><?php echo $activity_period ? $activity_period->days . ' days' : 'N/A'; ?></div>
          <div class="stat-subtitle">Avg. <?php echo $transaction_frequency; ?> between transactions</div>
        </div>
      </div>
    </div>

    <div class="content-grid">
      <!-- Left Column -->
      <div class="main-content">
        <!-- Payment History with Chart -->
        <div class="payment-history">
          <h3><i class="fas fa-chart-line"></i> Payment Trends</h3>
          <canvas id="paymentChart" class="payment-chart"></canvas>
          <div class="payment-summary">
            <div class="summary-item">
              <span class="label">Payment Reliability Score</span>
              <span class="value"><?php 
                $reliability = ($customer['paid_transactions'] / max(1, $customer['total_transactions'])) * 100;
                echo round($reliability) . '%';
              ?></span>
            </div>
            <div class="summary-item">
              <span class="label">Average Payment Time</span>
              <span class="value"><?php echo $transaction_frequency; ?></span>
            </div>
          </div>
        </div>

        <!-- Activity Timeline -->
        <div class="timeline-section card">
          <div class="card-header">
            <h3><i class="fas fa-history"></i> Recent Activity</h3>
          </div>
          <div class="card-body">
            <div class="timeline">
              <?php foreach ($recent_transactions as $transaction): ?>
              <div class="timeline-item">
                <div class="timeline-date">
                  <?php echo date('M j, Y', strtotime($transaction['created_at'])); ?>
                </div>
                <div class="timeline-content">
                  <h4>New Transaction: <?php echo format_currency($transaction['total_amount']); ?></h4>
                  <p>Due by: <?php echo date('M j, Y', strtotime($transaction['due_date'])); ?></p>
                  <?php if ($transaction['payment_count'] > 0): ?>
                  <div class="timeline-sub">
                    Last payment: <?php echo date('M j', strtotime($transaction['last_payment_date'])); ?>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column -->
      <div class="sidebar-content">


        <!-- Health Facility Information Card -->
        <div class="card">
          <div class="card-header">
            <h3><i class="fas fa-user"></i> Health Facility Information</h3>
          </div>
          <div class="card-body">
            <div class="details-list">
              <div class="detail-item">
                <i class="fas fa-map-marker-alt"></i>
                <div class="detail-content">
                  <span class="label">Region</span>
                  <span class="value"><?php echo htmlspecialchars($customer['region'] ?? 'Not provided'); ?></span>
                </div>
              </div>
              <div class="detail-item">
                <i class="fas fa-map-marker-alt"></i>
                <div class="detail-content">
                  <span class="label">Woreda</span>
                  <span class="value"><?php echo htmlspecialchars($customer['woreda'] ?? 'Not provided'); ?></span>
                </div>
              </div>
              <div class="detail-item">
                <i class="fas fa-map-marker-alt"></i>
                <div class="detail-content">
                  <span class="label">Zone</span>
                  <span class="value"><?php echo htmlspecialchars($customer['zone'] ?? 'Not provided'); ?></span>
                </div>
              </div>
              <div class="detail-item">
                <i class="fas fa-layer-group"></i>
                <div class="detail-content">
                  <span class="label">Level</span>
                  <span class="value"><?php echo htmlspecialchars($customer['level'] ?? 'Not provided'); ?></span>
                </div>
              </div>
              <div class="detail-item">
                <i class="fas fa-calendar-alt"></i>
                <div class="detail-content">
                  <span class="label">Member Since</span>
                  <span class="value">
                    <?php echo date('F j, Y', strtotime($customer['created_at'])); ?>
                    (<?php echo time_elapsed_string($customer['created_at']); ?>)
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Notes Section -->
        <div class="notes-section">
          <h3><i class="fas fa-sticky-note"></i> Notes</h3>
          <?php if (hasPermission('notes.read')): ?>
          <div class="note-list">
            <?php
            // Fetch existing notes
            $stmt = $pdo->prepare("
                SELECT 
                    n.*,
                    u.username as created_by_name
                FROM customer_notes n
                LEFT JOIN users u ON n.created_by = u.id
                WHERE n.customer_id = ?
                ORDER BY n.created_at DESC
            ");
            $stmt->execute([$customer_id]);
            $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($notes)): ?>
            <div class="empty-state">
              <p>No notes added yet</p>
            </div>
            <?php else:
                foreach ($notes as $note): ?>
            <div class="note-item <?php echo htmlspecialchars($note['type']); ?>">
              <div class="note-meta">
                <div class="note-info">
                  <span class="note-date">
                    <i class="fas fa-calendar"></i>
                    Added on <?php echo date('M j, Y', strtotime($note['created_at'])); ?>
                  </span>
                  <span class="note-author">
                    <i class="fas fa-user"></i>
                    by <?php echo htmlspecialchars($note['created_by_name']); ?>
                  </span>
                  <span class="note-type">
                    <i class="fas fa-tag"></i>
                    <?php echo ucfirst(htmlspecialchars($note['type'])); ?>
                  </span>
                </div>
                <?php if (canManageNote($note['id'])): ?>
                <div class="note-actions">
                  <button class="btn btn-sm btn-link" onclick="editNote(<?php echo $note['id']; ?>)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-sm btn-link text-danger" onclick="deleteNote(<?php echo $note['id']; ?>)">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
                <?php endif; ?>
              </div>
              <div class="note-content">
                <?php echo nl2br(htmlspecialchars($note['content'])); ?>
              </div>
            </div>
            <?php endforeach;
            endif; ?>
          </div>
          <?php if (hasPermission('notes.create')): ?>
          <button class="btn btn-sm btn-primary" onclick="addNote()">
            <i class="fas fa-plus"></i> Add Note
          </button>
          <?php endif; ?>
          <?php else: ?>
          <div class="alert alert-info">
            <i class="fas fa-lock"></i> You don't have permission to view notes.
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Payment History Modal -->
  <div class="modal" id="paymentHistoryModal" style="display: none;">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h3><i class="fas fa-history"></i> Payment History</h3>
          <button type="button" class="close" onclick="closeModal('paymentHistoryModal')">&times;</button>
        </div>
        <div class="modal-body">
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Amount</th>
                  <th>Type</th>
                  <th>Status</th>
                  <th>Reference</th>
                </tr>
              </thead>
              <tbody id="paymentHistoryTable">
                <!-- Will be populated by AJAX -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Add Note Modal -->
  <div class="modal" id="addNoteModal" style="display: none;">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h3><i class="fas fa-sticky-note"></i> Add Note</h3>
          <button type="button" class="close" onclick="closeModal('addNoteModal')">&times;</button>
        </div>
        <div class="modal-body">
          <form id="addNoteForm" onsubmit="saveNote(event)">
            <div class="form-group">
              <label for="noteContent">Note Content</label>
              <textarea class="form-control" id="noteContent" rows="4" required></textarea>
            </div>
            <div class="form-group">
              <label for="noteType">Note Type</label>
              <select class="form-control" id="noteType">
                <option value="general">General</option>
                <option value="payment">Payment</option>
                <option value="contact">Contact</option>
                <option value="important">Important</option>
              </select>
            </div>
            <button type="submit" class="btn btn-primary">Save Note</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <?php include '../includes/footer.php'; ?>

  <script>
  // Initialize payment history chart with actual data
  const ctx = document.getElementById('paymentChart').getContext('2d');
  const paymentData = <?php 
    // Get monthly payment totals for the last 6 months
    $stmt = $pdo->prepare("
      SELECT 
        DATE_FORMAT(p.payment_date, '%Y-%m') as month,
        SUM(p.amount) as total
      FROM payments p
      JOIN transactions t ON p.transaction_id = t.id
      WHERE t.customer_id = ?
      AND p.payment_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
      GROUP BY DATE_FORMAT(p.payment_date, '%Y-%m')
      ORDER BY month ASC
    ");
    $stmt->execute([$customer_id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $labels = [];
    $data = [];
    foreach ($payments as $payment) {
      $labels[] = date('M Y', strtotime($payment['month'] . '-01'));
      $data[] = floatval($payment['total']);
    }
    echo json_encode(['labels' => $labels, 'data' => $data]);
  ?>;

  new Chart(ctx, {
    type: 'line',
    data: {
      labels: paymentData.labels,
      datasets: [{
        label: 'Monthly Payments',
        data: paymentData.data,
        borderColor: '#3498db',
        tension: 0.4,
        fill: false
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'top',
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return '₱' + value.toLocaleString();
            }
          }
        }
      }
    }
  });

  // Modal functions
  function showModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
  }

  function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
  }

  // Payment history function
  function showPaymentHistory() {
    showModal('paymentHistoryModal');
    const tbody = document.getElementById('paymentHistoryTable');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center">Loading...</td></tr>';

    // Fetch payment history via AJAX
    fetch(`get_payment_history.php?customer_id=<?php echo $customer_id; ?>`)
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        console.log('Payment history data:', data); // Debug log

        if (!data.success) {
          throw new Error(data.message || 'Failed to load payment history');
        }

        if (!data.payments || data.payments.length === 0) {
          tbody.innerHTML = '<tr><td colspan="5" class="text-center">No payment history found</td></tr>';
          return;
        }

        tbody.innerHTML = data.payments.map(payment => `
          <tr>
            <td>${payment.payment_date}</td>
            <td>${payment.amount}</td>
            <td>${payment.payment_type}</td>
            <td><span class="badge badge-${payment.status === 'Completed' ? 'success' : 'warning'} payment-history-status">${payment.status}</span></td>
            <td>${payment.reference}</td>
          </tr>
        `).join('');
      })
      .catch(error => {
        console.error('Error loading payment history:', error);
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Error: ${error.message}</td></tr>`;
      });
  }

  // Note functions
  function addNote() {
    showModal('addNoteModal');
  }

  function saveNote(event) {
    event.preventDefault();

    if (!confirm('Are you sure you want to save this note?')) {
      return;
    }

    const noteData = {
      customer_id: <?php echo $customer_id; ?>,
      content: document.getElementById('noteContent').value,
      type: document.getElementById('noteType').value
    };

    fetch('save_note.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(noteData)
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Add new note to the list
          const noteList = document.querySelector('.note-list');
          const noteHtml = `
                <div class="note-item ${noteData.type}">
                    <div class="note-meta">
                        <div class="note-info">
                            <span class="note-date">
                                <i class="fas fa-calendar"></i>
                                Added on ${data.note.created_at}
                            </span>
                            <span class="note-author">
                                <i class="fas fa-user"></i>
                                by ${data.note.created_by}
                            </span>
                            <span class="note-type">
                                <i class="fas fa-tag"></i>
                                ${noteData.type.charAt(0).toUpperCase() + noteData.type.slice(1)}
                            </span>
                        </div>
                        <div class="note-actions">
                            <button class="btn btn-sm btn-link" onclick="editNote(${data.note.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-link text-danger" onclick="deleteNote(${data.note.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="note-content">
                        ${noteData.content.replace(/\n/g, '<br>')}
                    </div>
                </div>
            `;

          // If there's an empty state message, remove it
          const emptyState = noteList.querySelector('.empty-state');
          if (emptyState) {
            emptyState.remove();
          }

          noteList.insertAdjacentHTML('afterbegin', noteHtml);

          // Clear form and close modal
          document.getElementById('addNoteForm').reset();
          closeModal('addNoteModal');

          // Show success message
          showToast('Success', 'Note added successfully', 'success');
        } else {
          showToast('Error', data.message, 'error');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showToast('Error', 'Failed to save note. Please try again.', 'error');
      });
  }

  function editNote(noteId) {
    // Implement edit functionality
    alert('Edit functionality will be implemented soon');
  }

  function deleteNote(noteId) {
    if (confirm('Are you sure you want to delete this note?')) {
      fetch(`delete_note.php?id=${noteId}`, {
          method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const noteElement = document.querySelector(`[data-note-id="${noteId}"]`);
            if (noteElement) {
              noteElement.remove();
              showToast('Success', 'Note deleted successfully', 'success');
            }
          } else {
            showToast('Error', data.message, 'error');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showToast('Error', 'Failed to delete note. Please try again.', 'error');
        });
    }
  }

  // Toast notification function
  function showToast(title, message, type = 'info') {
    // Implement toast notification
    alert(`${title}: ${message}`);
  }

  // Close modals when clicking outside
  window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
      event.target.style.display = 'none';
    }
  }
  </script>
</body>

</html>