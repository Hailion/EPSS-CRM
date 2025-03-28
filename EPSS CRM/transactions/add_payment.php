<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
require_once '../includes/languages.php';

// Get transaction ID from URL
$transaction_id = isset($_GET['transaction_id']) ? (int)$_GET['transaction_id'] : 0;

// Fetch transaction details
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
            DATEDIFF(CURRENT_DATE, t.due_date) as days_overdue
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

    // Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $amount = (float)$_POST['amount'];
        $payment_method = $_POST['payment_method'];
    $payment_date = $_POST['payment_date'];
    $notes = trim($_POST['notes']);
    
        // Validate amount
    if ($amount <= 0) {
            $_SESSION['error'] = t('invalid_payment_amount');
    } elseif ($amount > $transaction['remaining_amount']) {
            $_SESSION['error'] = t('payment_amount_exceeds_remaining');
    } else {
        try {
            $pdo->beginTransaction();
            
                // Insert payment
                $stmt = $pdo->prepare("
                    INSERT INTO payments (transaction_id, amount, payment_method, payment_date, notes, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$transaction_id, $amount, $payment_method, $payment_date, $notes]);
            
            $pdo->commit();
                $_SESSION['success'] = t('payment_added_successfully');
                header('Location: view.php?id=' . $transaction_id);
                exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
                error_log("Error adding payment: " . $e->getMessage());
                $_SESSION['error'] = t('error_adding_payment');
        }
    }
}

} catch (PDOException $e) {
    error_log("Error fetching transaction: " . $e->getMessage());
    $_SESSION['error'] = t('error_fetching_transaction');
    header('Location: list.php');
    exit;
}

$page_title = t('add_payment');
require_once '../includes/header.php';
?>

<div class="view-container">
    <div class="page-header" style="background: linear-gradient(135deg, #4CAF50, #2E7D32); color: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div class="header-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <div>
                    <h2 style="margin: 0; font-size: 1.8rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-money-bill-wave"></i>
                        <?php echo t('add_payment'); ?>
                    </h2>
                    <p style="margin: 0.5rem 0 0; opacity: 0.9; font-size: 0.95rem;">
                        Add a new payment for Transaction #<?php echo $transaction_id; ?>
                    </p>
                </div>
                <a href="view.php?id=<?php echo $transaction_id; ?>" class="btn btn-light" style="background: rgba(255, 255, 255, 0.1);">
                    <i class="fas fa-arrow-left"></i> Back to Transaction
                </a>
            </div>

            <!-- Transaction Summary -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 2rem;">
                <div class="stat-card" style="background: rgba(255, 255, 255, 0.1); padding: 1.25rem; border-radius: 8px; backdrop-filter: blur(5px);">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 48px; height: 48px; background: rgba(255, 255, 255, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-money-bill-wave" style="font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <div style="font-size: 0.9rem; opacity: 0.9;">Total Amount</div>
                            <div style="font-size: 1.4rem; font-weight: 600;"><?php echo format_currency($transaction['total_amount']); ?></div>
                        </div>
                    </div>
                </div>
                <div class="stat-card" style="background: rgba(255, 255, 255, 0.1); padding: 1.25rem; border-radius: 8px; backdrop-filter: blur(5px);">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 48px; height: 48px; background: rgba(255, 255, 255, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-check-circle" style="font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <div style="font-size: 0.9rem; opacity: 0.9;">Amount Paid</div>
                            <div style="font-size: 1.4rem; font-weight: 600;"><?php echo format_currency($transaction['amount_paid']); ?></div>
                        </div>
                    </div>
                </div>
                <div class="stat-card" style="background: rgba(255, 255, 255, 0.1); padding: 1.25rem; border-radius: 8px; backdrop-filter: blur(5px);">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 48px; height: 48px; background: rgba(255, 255, 255, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-balance-scale" style="font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <div style="font-size: 0.9rem; opacity: 0.9;">Remaining Balance</div>
                            <div style="font-size: 1.4rem; font-weight: 600;"><?php echo format_currency($transaction['remaining_amount']); ?></div>
                        </div>
                    </div>
                </div>
                <div class="stat-card" style="background: rgba(255, 255, 255, 0.1); padding: 1.25rem; border-radius: 8px; backdrop-filter: blur(5px);">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 48px; height: 48px; background: rgba(255, 255, 255, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-calendar" style="font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <div style="font-size: 0.9rem; opacity: 0.9;">Due Date</div>
                            <div style="font-size: 1.4rem; font-weight: 600;"><?php echo date('M d, Y', strtotime($transaction['due_date'])); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="content-card" style="background: white; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <form method="POST" class="payment-form" style="padding: 2rem;">
            <!-- Customer Information -->
            <div class="info-section" style="margin-bottom: 2rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px;">
                <h3 style="margin: 0 0 1rem; font-size: 1.2rem; color: #333;">Customer Information</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div>
                        <div style="font-size: 0.9rem; color: #666;">Customer Name</div>
                        <div style="font-weight: 500;"><?php echo htmlspecialchars($transaction['customer_name']); ?></div>
                    </div>
                    <div>
                        <div style="font-size: 0.9rem; color: #666;">Region</div>
                        <div style="font-weight: 500;"><?php echo htmlspecialchars($transaction['region']); ?></div>
                    </div>
                    <div>
                        <div style="font-size: 0.9rem; color: #666;">Hub</div>
                        <div style="font-weight: 500;"><?php echo htmlspecialchars($transaction['hub']); ?></div>
                    </div>
                    <div>
                        <div style="font-size: 0.9rem; color: #666;">Woreda</div>
                        <div style="font-weight: 500;"><?php echo htmlspecialchars($transaction['woreda']); ?></div>
                    </div>
                    <div>
                        <div style="font-size: 0.9rem; color: #666;">Zone</div>
                        <div style="font-weight: 500;"><?php echo htmlspecialchars($transaction['Zone']); ?></div>
                    </div>
                    <div>
                        <div style="font-size: 0.9rem; color: #666;">Level</div>
                        <div style="font-weight: 500;"><?php echo htmlspecialchars($transaction['level']); ?></div>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <div class="form-group" style="margin-bottom: 2rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Payment Amount</label>
                <div style="position: relative;">
                    <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #666;">ETB</span>
                    <input type="number" name="amount" required min="0" max="<?php echo $transaction['remaining_amount']; ?>" step="0.01" class="form-control"
                        style="width: 100%; padding: 0.75rem; padding-left: 4rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                </div>
                <div style="font-size: 0.85rem; color: #666; margin-top: 0.5rem;">
                    Maximum amount: <?php echo format_currency($transaction['remaining_amount']); ?>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 2rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Payment Method</label>
                <select name="payment_method" required class="form-control"
                    style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                    <option value="">Select payment method...</option>
                    <option value="Cash">Cash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Check">Check</option>
                    <option value="Mobile Money">Mobile Money</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 2rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Payment Date</label>
                <input type="date" name="payment_date" required class="form-control"
                    style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
            </div>

            <div class="form-group" style="margin-bottom: 2rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Notes</label>
                <textarea name="notes" rows="4" class="form-control"
                    style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; resize: vertical;"></textarea>
            </div>

            <!-- Submit Button -->
            <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                <a href="view.php?id=<?php echo $transaction_id; ?>" class="btn btn-outline" style="padding: 0.75rem 1.5rem;">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">
                    <i class="fas fa-save"></i> Add Payment
                </button>
            </div>
        </form>
    </div>
</div>

  <style>
.view-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem;
  }

  .form-control {
    transition: all 0.2s;
  }

  .form-control:focus {
    outline: none;
    border-color: #4CAF50;
    box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.1);
  }

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.btn-primary {
    background: #4CAF50;
    color: white;
  }

  .btn-primary:hover {
    background: #2E7D32;
  }

.btn-outline {
    background: transparent;
    border: 1px solid #ddd;
    color: #666;
  }

.btn-outline:hover {
    background: #f8f9fa;
  }

.btn-light {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.2);
  }

.btn-light:hover {
    background: rgba(255, 255, 255, 0.2);
  }

@media (max-width: 768px) {
    .view-container {
        padding: 1rem;
    }
    
    .page-header {
    padding: 1.5rem;
  }

    .payment-form {
        padding: 1.5rem;
    }
  }
  </style>

  <script>
// Add form validation and enhancement
document.querySelector('.payment-form').addEventListener('submit', function(e) {
    const amount = parseFloat(this.amount.value);
    const maxAmount = parseFloat(this.amount.max);
    const paymentDate = new Date(this.payment_date.value);
    const today = new Date();

    if (amount <= 0) {
      e.preventDefault();
        alert('Please enter a valid amount greater than 0');
        return;
    }
    
    if (amount > maxAmount) {
      e.preventDefault();
      alert('Payment amount cannot exceed the remaining balance');
        return;
    }
    
    if (paymentDate > today) {
        e.preventDefault();
        alert('Payment date cannot be in the future');
        return;
    }
  });

// Set maximum date for payment date input to today
document.querySelector('input[name="payment_date"]').max = new Date().toISOString().split('T')[0];
  </script>

<?php require_once '../includes/footer.php'; ?>