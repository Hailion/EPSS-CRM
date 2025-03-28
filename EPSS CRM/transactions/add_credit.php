<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
require_once '../includes/languages.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Validate and sanitize input
        $customer_id = (int)$_POST['customer_id'];
        $amount = (float)$_POST['amount'];
        $due_date = $_POST['due_date'];
        $notes = trim($_POST['notes']);

        // Insert transaction
        $stmt = $pdo->prepare("
            INSERT INTO transactions (customer_id, amount, due_date, notes, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$customer_id, $amount, $due_date, $notes]);
        $transaction_id = $pdo->lastInsertId();

        $pdo->commit();
        $_SESSION['success'] = t('credit_added_successfully');
        header('Location: view.php?id=' . $transaction_id);
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error adding credit: " . $e->getMessage());
        $_SESSION['error'] = t('error_adding_credit');
    }
}

// Fetch customers for dropdown
$stmt = $pdo->query("
    SELECT id, name, region, hub, woreda, Zone, level 
    FROM customers 
    WHERE status = 'active' 
    ORDER BY name
");
$customers = $stmt->fetchAll();

$page_title = t('add_new_credit');
require_once '../includes/header.php';
?>

<div class="view-container">
    <div class="page-header" style="background: linear-gradient(135deg, #2196F3, #1976D2); color: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div class="header-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <div>
                    <h2 style="margin: 0; font-size: 1.8rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-plus-circle"></i>
                        <?php echo t('add_new_credit'); ?>
                    </h2>
                    <p style="margin: 0.5rem 0 0; opacity: 0.9; font-size: 0.95rem;">
                        Create a new credit transaction for a customer
                    </p>
                </div>
                <a href="list.php" class="btn btn-light" style="background: rgba(255, 255, 255, 0.1);">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>

    <div class="content-card" style="background: white; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <form method="POST" class="credit-form" style="padding: 2rem;">
            <!-- Customer Selection -->
            <div class="form-group" style="margin-bottom: 2rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Select Customer</label>
                <select name="customer_id" required class="form-control" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                    <option value="">Select a customer...</option>
                    <?php foreach ($customers as $customer): ?>
                    <option value="<?php echo $customer['id']; ?>">
                        <?php echo htmlspecialchars($customer['name']); ?> 
                        (<?php echo htmlspecialchars($customer['region']); ?> - <?php echo htmlspecialchars($customer['level']); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Amount Input -->
            <div class="form-group" style="margin-bottom: 2rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Credit Amount</label>
                <div style="position: relative;">
                    <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #666;">ETB</span>
                    <input type="number" name="amount" required min="0" step="0.01" class="form-control"
                        style="width: 100%; padding: 0.75rem; padding-left: 4rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                </div>
            </div>

            <!-- Due Date Input -->
            <div class="form-group" style="margin-bottom: 2rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Due Date</label>
                <input type="date" name="due_date" required class="form-control"
                    style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
            </div>

            <!-- Notes Input -->
            <div class="form-group" style="margin-bottom: 2rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Notes</label>
                <textarea name="notes" rows="4" class="form-control"
                    style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; resize: vertical;"></textarea>
            </div>

            <!-- Submit Button -->
            <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                <a href="list.php" class="btn btn-outline" style="padding: 0.75rem 1.5rem;">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">
                    <i class="fas fa-save"></i> Create Credit
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
    border-color: #2196F3;
    box-shadow: 0 0 0 2px rgba(33, 150, 243, 0.1);
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
    background: #2196F3;
    color: white;
}

.btn-primary:hover {
    background: #1976D2;
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
    
    .credit-form {
        padding: 1.5rem;
    }
}
</style>

<script>
// Add form validation and enhancement
document.querySelector('.credit-form').addEventListener('submit', function(e) {
    const amount = parseFloat(this.amount.value);
    const dueDate = new Date(this.due_date.value);
    const today = new Date();
    
    if (amount <= 0) {
        e.preventDefault();
        alert('Please enter a valid amount greater than 0');
        return;
    }
    
    if (dueDate < today) {
        e.preventDefault();
        alert('Due date cannot be in the past');
        return;
    }
});

// Set minimum date for due date input to today
document.querySelector('input[name="due_date"]').min = new Date().toISOString().split('T')[0];
</script>

<?php require_once '../includes/footer.php'; ?>