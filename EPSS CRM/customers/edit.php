<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => '', 'errors' => []];

    try {
        $name = trim($_POST['name'] ?? '');
        $region = trim($_POST['region'] ?? '');
        $woreda = trim($_POST['woreda'] ?? '');
        $zone = trim($_POST['zone'] ?? '');
        $level = trim($_POST['level'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $status = $_POST['status'] ?? 'active';
        
        // Validate inputs
        if (empty($name)) {
            $response['errors']['name'] = "Health Facility name is required";
        }
        if (empty($region)) {
            $response['errors']['region'] = "Region is required";
        }
        if (empty($woreda)) {
            $response['errors']['woreda'] = "Woreda is required";
        }
        if (empty($zone)) {
            $response['errors']['zone'] = "Zone is required";
        }
        if (empty($level)) {
            $response['errors']['level'] = "Level is required";
        }
        
        if (empty($response['errors'])) {
            // Start transaction
            $pdo->beginTransaction();

            // Update customer
            $stmt = $pdo->prepare("
                UPDATE customers 
                SET name = ?, region = ?, woreda = ?, zone = ?, level = ?, status = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$name, $region, $woreda, $zone, $level, $status, $customer_id]);

            // Commit transaction
            $pdo->commit();

            $response['success'] = true;
            $response['message'] = "Health Facility updated successfully!";
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $response['message'] = $e->getMessage();
        http_response_code(400);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $response['message'] = "Database error occurred";
        http_response_code(500);
    }

    echo json_encode($response);
    exit;
}

// Get customer details
$stmt = $pdo->prepare("SELECT id, name, region, woreda, zone, level, status, created_at FROM customers WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    header("Location: list.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Health Facility - Credit Management System</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
  /* Enhanced Container Styles */
  .container {
    max-width: 1200px;
    padding: 2rem;
    margin: 0 auto;
  }

  /* Header Styles */
  .header-actions-wrapper {
    background: #fff;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }

  .title-section {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .title-section h2 {
    color: #2c3e50;
    margin: 0;
    font-size: 1.8rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .title-meta {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    font-size: 0.9rem;
    color: #666;
  }

  /* Stats Cards */
  .customer-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #eee;
  }

  .stat-card {
    background: linear-gradient(145deg, #f8fafc, #f1f5f9);
    padding: 1.25rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform 0.2s;
  }

  .stat-card:hover {
    transform: translateY(-2px);
  }

  .stat-card i {
    font-size: 2rem;
    color: #3498db;
  }

  .stat-content {
    display: flex;
    flex-direction: column;
  }

  .stat-label {
    font-size: 0.875rem;
    color: #64748b;
  }

  .stat-value {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
  }

  /* Main Content Grid */
  .content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-top: 2rem;
  }

  /* Form Styles */
  .main-form.card {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }

  .card-header {
    padding: 1.25rem;
    border-bottom: 1px solid #eee;
  }

  .card-header h3 {
    margin: 0;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    padding: 1.5rem;
  }

  .form-group {
    margin-bottom: 1.25rem;
  }

  .form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: #4a5568;
    font-weight: 500;
  }

  .form-group input,
  .form-group select,
  .form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    transition: border-color 0.2s;
  }

  .form-group input:focus,
  .form-group select:focus,
  .form-group textarea:focus {
    border-color: #3498db;
    outline: none;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
  }

  .input-group {
    display: flex;
    align-items: center;
  }

  .input-group-text {
    background: #f8fafc;
    padding: 0.75rem;
    border: 1px solid #e2e8f0;
    border-right: none;
    border-radius: 6px 0 0 6px;
    color: #64748b;
  }

  .input-group input {
    border-radius: 0 6px 6px 0;
  }

  .full-width {
    grid-column: 1 / -1;
  }

  /* Activity Timeline */
  .activity-timeline {
    padding: 1rem;
  }

  .activity-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    border-left: 2px solid #e2e8f0;
    margin-left: 1rem;
    position: relative;
  }

  .activity-item:before {
    content: '';
    position: absolute;
    left: -0.5rem;
    width: 1rem;
    height: 1rem;
    background: #fff;
    border: 2px solid #3498db;
    border-radius: 50%;
  }

  .activity-icon {
    width: 2.5rem;
    height: 2.5rem;
    background: #ebf8ff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #3498db;
  }

  .activity-content {
    flex: 1;
  }

  .activity-title {
    font-weight: 500;
    color: #2d3748;
  }

  .activity-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.875rem;
    color: #718096;
    margin-top: 0.25rem;
  }

  /* Quick Actions */
  .quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 1rem;
    padding: 1rem;
  }

  .action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 8px;
    text-decoration: none;
    color: #2d3748;
    transition: all 0.2s;
  }

  .action-btn:hover {
    background: #ebf8ff;
    transform: translateY(-2px);
  }

  .action-btn i {
    font-size: 1.5rem;
    color: #3498db;
    margin-bottom: 0.5rem;
  }

  /* Status Badge */
  .status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
  }

  .status-badge.status-active {
    background: #dcfce7;
    color: #166534;
  }

  .status-badge.status-inactive {
    background: #fee2e2;
    color: #991b1b;
  }

  /* Form Actions */
  .form-actions {
    padding: 1.5rem;
    border-top: 1px solid #eee;
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
  }

  .btn {
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
  }

  .btn-primary {
    background: #3498db;
    color: white;
  }

  .btn-primary:hover {
    background: #2980b9;
  }

  .btn-success {
    background: #2ecc71;
    color: white;
  }

  .btn-success:hover {
    background: #27ae60;
  }

  .btn-secondary {
    background: #95a5a6;
    color: white;
  }

  .btn-secondary:hover {
    background: #7f8c8d;
  }

  /* Loading Spinner */
  .loading-spinner {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
  }

  /* Error Messages */
  .error-message {
    color: #dc2626;
    font-size: 0.875rem;
    margin-top: 0.25rem;
  }

  /* Alert Messages */
  .alert {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .alert-success {
    background: #dcfce7;
    color: #166534;
  }

  .alert-danger {
    background: #fee2e2;
    color: #991b1b;
  }

  /* Responsive Design */
  @media (max-width: 768px) {
    .content-grid {
      grid-template-columns: 1fr;
    }

    .form-grid {
      grid-template-columns: 1fr;
    }

    .customer-stats {
      grid-template-columns: 1fr;
    }

    .title-meta {
      flex-direction: column;
      align-items: flex-start;
    }
  }

  /* Modal Styles */
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
    max-width: 800px;
    margin: 2rem auto;
  }

  .modal-content {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  }

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #eee;
  }

  .modal-header h3 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .modal-body {
    padding: 1.5rem;
  }

  .close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #666;
  }

  .close:hover {
    color: #333;
  }

  /* Table Styles */
  .table {
    width: 100%;
    border-collapse: collapse;
  }

  .table th,
  .table td {
    padding: 0.75rem;
    border-bottom: 1px solid #eee;
    text-align: left;
  }

  .table th {
    background: #f8fafc;
    font-weight: 500;
    color: #4a5568;
  }

  .table tr:hover {
    background: #f8fafc;
  }
  </style>
</head>

<body>
  <?php include '../includes/header.php'; ?>

  <div class="container">
    <!-- Enhanced Header Section -->
    <div class="header-actions-wrapper">
      <div class="header-title">
        <div class="title-section">
          <h2><i class="fas fa-user-edit"></i> Edit Health Facility Profile</h2>
          <div class="title-meta">
            <span class="customer-id"><i class="fas fa-id-card"></i> ID: #<?php echo $customer['id']; ?></span>
            <span class="customer-since"><i class="fas fa-calendar-alt"></i> Customer since:
              <?php echo date('M d, Y', strtotime($customer['created_at'])); ?></span>
            <span class="status-badge status-<?php echo $customer['status']; ?>">
              <i class="fas fa-circle"></i> <?php echo ucfirst($customer['status']); ?>
            </span>
          </div>
        </div>
      </div>

      <!-- Quick Stats -->
      <div class="customer-stats">
        <div class="stat-card">
          <i class="fas fa-money-bill-wave"></i>
          <div class="stat-content">
            <span class="stat-label">Total Credits</span>
            <span class="stat-value">
              <?php
                            $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE customer_id = ?");
                            $stmt->execute([$customer_id]);
                            echo number_format($stmt->fetchColumn(), 2);
                            ?>
            </span>
          </div>
        </div>
        <div class="stat-card">
          <i class="fas fa-receipt"></i>
          <div class="stat-content">
            <span class="stat-label">Total Transactions</span>
            <span class="stat-value">
              <?php
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE customer_id = ?");
                            $stmt->execute([$customer_id]);
                            echo $stmt->fetchColumn();
                            ?>
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content Grid -->
    <div class="content-grid">
      <!-- Left Column - Main Edit Form -->
      <div class="main-form card">
        <div class="card-header">
          <h3><i class="fas fa-user"></i> Basic Information</h3>
        </div>
        <form id="editForm" class="form">
          <div class="form-grid">
            <div class="form-group">
              <label for="name">Health Facility Name *</label>
              <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($customer['name'] ?? ''); ?>"
                required>
              <div class="error-message" id="nameError"></div>
            </div>

            <div class="form-group">
              <label for="region">Region</label>
              <input type="text" id="region" name="region"
                value="<?php echo htmlspecialchars($customer['region'] ?? ''); ?>">
              <div class="error-message" id="regionError"></div>
            </div>

            <div class="form-group">
              <label for="woreda">Woreda</label>
              <input type="text" id="woreda" name="woreda"
                value="<?php echo htmlspecialchars($customer['woreda'] ?? ''); ?>">
              <div class="error-message" id="woredaError"></div>
            </div>

            <div class="form-group">
              <label for="zone">Zone</label>
              <input type="text" id="zone" name="zone" value="<?php echo htmlspecialchars($customer['zone'] ?? ''); ?>">
              <div class="error-message" id="zoneError"></div>
            </div>

            <div class="form-group">
              <label for="level">Level</label>
              <input type="text" id="level" name="level"
                value="<?php echo htmlspecialchars($customer['level'] ?? ''); ?>">
              <div class="error-message" id="levelError"></div>
            </div>

            <div class="form-group">
              <label for="status">Account Status</label>
              <select id="status" name="status" class="status-select">
                <option value="active" <?php echo $customer['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $customer['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive
                </option>
              </select>
            </div>
          </div>

          <div class="form-group full-width">
            <!-- Removed the Address Details field -->
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save"></i> Update Profile
            </button>
            <button type="button" class="btn btn-success"
              onclick="location.href='view.php?id=<?php echo $customer_id; ?>'">
              <i class="fas fa-eye"></i> View Profile
            </button>
            <a href="list.php" class="btn btn-secondary">
              <i class="fas fa-arrow-left"></i> Back to List
            </a>
          </div>
        </form>
      </div>

      <!-- Right Column - Additional Information -->
      <div class="side-content">
        <!-- Recent Activity Card -->
        <div class="card">
          <div class="card-header">
            <h3><i class="fas fa-history"></i> Recent Activity</h3>
          </div>
          <div class="card-body">
            <div class="activity-timeline">
              <?php
                            $stmt = $pdo->prepare("
                                SELECT 'transaction' as type, id, amount as value, created_at, 'New transaction added' as description
                                FROM transactions 
                                WHERE customer_id = ?
                                UNION ALL
                                SELECT 'payment' as type, id, amount as value, payment_date as created_at, 'Payment received' as description
                                FROM payments 
                                WHERE transaction_id IN (SELECT id FROM transactions WHERE customer_id = ?)
                                ORDER BY created_at DESC LIMIT 5
                            ");
                            $stmt->execute([$customer_id, $customer_id]);
                            $activities = $stmt->fetchAll();
                            
                            foreach ($activities as $activity): ?>
              <div class="activity-item">
                <div class="activity-icon <?php echo $activity['type']; ?>">
                  <i
                    class="fas fa-<?php echo $activity['type'] === 'transaction' ? 'file-invoice-dollar' : 'money-bill-wave'; ?>"></i>
                </div>
                <div class="activity-content">
                  <div class="activity-title"><?php echo $activity['description']; ?></div>
                  <div class="activity-meta">
                    <span class="amount"><?php echo number_format($activity['value'], 2); ?></span>
                    <span class="date"><?php echo date('M d, Y', strtotime($activity['created_at'])); ?></span>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Quick Actions Card -->
        <div class="card">
          <div class="card-header">
            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
          </div>
          <div class="card-body">
            <div class="quick-actions">
              <a href="../transactions/add_credit.php?customer_id=<?php echo $customer_id; ?>" class="action-btn">
                <i class="fas fa-plus-circle"></i>
                <span>Add Credit</span>
              </a>
              <a href="#" onclick="showPaymentHistory()" class="action-btn">
                <i class="fas fa-history"></i>
                <span>Payment History</span>
              </a>
              <a href="#" onclick="addNote()" class="action-btn">
                <i class="fas fa-sticky-note"></i>
                <span>Add Note</span>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include '../includes/footer.php'; ?>

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

  <!-- Replace the existing JavaScript functions with these updated versions -->
  <script>
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
        console.log('Payment history data:', data);

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
                    <td><span class="badge badge-${payment.status === 'Completed' ? 'success' : 'warning'}">${payment.status}</span></td>
                    <td>${payment.reference}</td>
                </tr>
            `).join('');
      })
      .catch(error => {
        console.error('Error loading payment history:', error);
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Error: ${error.message}</td></tr>`;
      });
  }

  // Add note function
  function addNote() {
    showModal('addNoteModal');
    document.getElementById('noteContent').value = '';
    document.getElementById('noteType').value = 'general';
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
          alert('Note saved successfully!');
          closeModal('addNoteModal');
          // Optionally refresh the page or update the notes list
          location.reload();
        } else {
          alert('Error: ' + (data.message || 'Failed to save note'));
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving the note');
      });
  }

  // Close modal when clicking outside
  window.onclick = function(event) {
    if (event.target.className === 'modal') {
      event.target.style.display = 'none';
    }
  }
  </script>
</body>

</html>