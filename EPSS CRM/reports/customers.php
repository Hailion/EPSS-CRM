<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';

$page_title = 'All Customers';
require_once '../includes/header.php';
?>

<div class="container">
  <div class="page-header">
    <h1><i class="fas fa-users"></i> All Customers</h1>
    <div class="header-actions">
      <button class="btn btn-primary" onclick="window.print()">
        <i class="fas fa-print"></i> Print
      </button>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Customer Name</th>
              <th>Region</th>
              <th>Zone</th>
              <th>Level</th>
              <th>Total Credit</th>
              <th>Balance</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            // Get all customers with their transaction information
            $stmt = $pdo->prepare("
              SELECT c.*,
                     COUNT(t.id) as total_transactions,
                     SUM(t.amount) as total_credit,
                     COALESCE(SUM(p.amount), 0) as total_paid,
                     SUM(CASE WHEN t.status = 'overdue' THEN 1 ELSE 0 END) as overdue_transactions
              FROM customers c
              LEFT JOIN transactions t ON c.id = t.customer_id
              LEFT JOIN payments p ON t.id = p.transaction_id
              GROUP BY c.id
              ORDER BY c.name ASC
            ");
            $stmt->execute();
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($customers as $customer):
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
            <tr>
              <td><?php echo htmlspecialchars($customer['name']); ?></td>
              <td><?php echo htmlspecialchars($customer['region']); ?></td>
              <td><?php echo htmlspecialchars($customer['Zone']); ?></td>
              <td><?php echo htmlspecialchars($customer['level']); ?></td>
              <td>ETB <?php echo number_format($customer['total_credit'], 2); ?></td>
              <td>ETB <?php echo number_format($customer['total_credit'] - $customer['total_paid'], 2); ?></td>
              <td>
                <span class="status-badge <?php echo strtolower($status); ?>">
                  <?php echo ucfirst($status); ?>
                </span>
              </td>
              <td>
                <a href="../customers/view.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-info">
                  <i class="fas fa-eye"></i> View
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<style>
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.page-header h1 {
  margin: 0;
  font-size: 1.8rem;
  color: #333;
}

.page-header h1 i {
  margin-right: 0.5rem;
  color: #2196F3;
}

.table th {
  background: #f8f9fa;
  font-weight: 600;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}

@media print {
  .header-actions,
  .btn {
    display: none !important;
  }
}
</style>

<?php require_once '../includes/footer.php'; ?> 