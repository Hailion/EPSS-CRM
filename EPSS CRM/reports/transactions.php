<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';

$page_title = 'All Transactions';
require_once '../includes/header.php';
?>

<div class="container">
  <div class="page-header">
    <h1><i class="fas fa-history"></i> All Transactions</h1>
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
              <th>Date</th>
              <th>Customer</th>
              <th>Type</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            // Get all transactions
            $stmt = $pdo->prepare("
              SELECT t.*, c.name as customer_name,
                     COALESCE(SUM(p.amount), 0) as amount_paid,
                     t.amount - COALESCE(SUM(p.amount), 0) as remaining_amount
              FROM transactions t
              JOIN customers c ON t.customer_id = c.id
              LEFT JOIN payments p ON t.id = p.transaction_id
              GROUP BY t.id
              ORDER BY t.created_at DESC
            ");
            $stmt->execute();
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($transactions as $transaction):
              // Calculate status based on payment information
              $status = 'pending';
              if ($transaction['amount_paid'] >= $transaction['amount']) {
                $status = 'paid';
              } elseif ($transaction['amount_paid'] > 0) {
                $status = 'partial';
              } elseif (strtotime($transaction['due_date']) < time()) {
                $status = 'overdue';
              }
            ?>
            <tr>
              <td><?php echo date('M d, Y', strtotime($transaction['created_at'])); ?></td>
              <td><?php echo htmlspecialchars($transaction['customer_name']); ?></td>
              <td><?php echo ucfirst($transaction['type']); ?></td>
              <td>ETB <?php echo number_format($transaction['amount'], 2); ?></td>
              <td>
                <span class="status-badge <?php echo strtolower($status); ?>">
                  <?php echo ucfirst($status); ?>
                </span>
              </td>
              <td>
                <a href="../transactions/view.php?id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-info">
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