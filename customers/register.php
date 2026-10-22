<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => '', 'errors' => []];

    try {
        // Get and sanitize input data
        $name = trim($_POST['name'] ?? '');
        $facility_id = trim($_POST['facility_id'] ?? '');
        $level = trim($_POST['level'] ?? '');
        $region = trim($_POST['region'] ?? '');
        $zone = trim($_POST['zone'] ?? '');
        $hub = trim($_POST['hub'] ?? '');
        $woreda = trim($_POST['woreda'] ?? '');
        $credit_invoice_number = trim($_POST['credit_invoice_number'] ?? '');
        $credit_balance = floatval($_POST['credit_balance'] ?? 0);
        $credit_date = trim($_POST['credit_date'] ?? '');
        $due_date = trim($_POST['due_date'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        
        // Validate required fields
        if (empty($name)) {
            $response['errors']['name'] = "Facility name is required";
        }
        
        if (empty($facility_id)) {
            $response['errors']['facility_id'] = "Facility ID is required";
        } else {
            // Check if facility ID already exists
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE facility_id = ?");
            $stmt->execute([$facility_id]);
            if ($stmt->rowCount() > 0) {
                $response['errors']['facility_id'] = "Facility ID already exists";
            }
        }
        
        if (empty($level)) {
            $response['errors']['level'] = "Facility level is required";
        }
        
        if (empty($region)) {
            $response['errors']['region'] = "Region is required";
        }
        
        if (empty($zone)) {
            $response['errors']['zone'] = "Zone is required";
        }
        
        if (empty($hub)) {
            $response['errors']['hub'] = "Hub is required";
        }
        
        if (empty($woreda)) {
            $response['errors']['woreda'] = "Woreda is required";
        }
        
        if (empty($credit_invoice_number)) {
            $response['errors']['credit_invoice_number'] = "Credit invoice number is required";
        } else {
            // Check if credit invoice number already exists
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE credit_invoice_number = ?");
            $stmt->execute([$credit_invoice_number]);
            if ($stmt->rowCount() > 0) {
                $response['errors']['credit_invoice_number'] = "Credit invoice number already exists";
            }
        }
        
        if ($credit_balance <= 0) {
            $response['errors']['credit_balance'] = "Credit balance must be greater than 0";
        }
        
        if (empty($credit_date)) {
            $response['errors']['credit_date'] = "Credit date is required";
        } elseif (!strtotime($credit_date)) {
            $response['errors']['credit_date'] = "Invalid credit date format";
        }
        
        if (empty($due_date)) {
            $response['errors']['due_date'] = "Due date is required";
        } elseif (!strtotime($due_date)) {
            $response['errors']['due_date'] = "Invalid due date format";
        } elseif (strtotime($due_date) <= strtotime($credit_date)) {
            $response['errors']['due_date'] = "Due date must be after credit date";
        }
        
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['errors']['email'] = "Invalid email format";
        }
        
        if (!empty($phone) && !preg_match("/^[0-9\-\(\)\/\+\s]*$/", $phone)) {
            $response['errors']['phone'] = "Invalid phone number format";
        }
        
        if (empty($response['errors'])) {
            // Start transaction
            $pdo->beginTransaction();

            try {
                // Insert into customers table
                $stmt = $pdo->prepare("INSERT INTO customers (
                    name, level, region, zone, hub, woreda,
                    credit_invoice_number, credit_balance, status
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, 'active'
                )");
                
                $stmt->execute([
                    $name, $level, $region, $zone, $hub, $woreda,
                    $credit_invoice_number, $credit_balance
                ]);
                
                $customerId = $pdo->lastInsertId();

                // Create initial credit transaction
                $stmt = $pdo->prepare("INSERT INTO transactions (
                    customer_id, user_id, type, amount, credit_date, due_date, status
                ) VALUES (?, ?, 'credit', ?, ?, ?, 'active')");
                
                $stmt->execute([
                    $customerId,
                    $_SESSION['user_id'],
                    $credit_balance,
                    $credit_date,
                    $due_date
                ]);

                // Commit transaction
                $pdo->commit();

                $response['success'] = true;
                $response['message'] = "Health Facility registered successfully!";
                $response['customer_id'] = $customerId;
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
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

$message = '';
$error = '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register Health Facility - Credit Management System</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
  /* Modern Container and Card Styles */
  .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    min-height: calc(100vh - 60px);
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  }

  .register-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    margin-top: 1rem;
    overflow: hidden;
    animation: fadeIn 0.5s ease;
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  /* Enhanced Header Design */
  .register-header {
    background: linear-gradient(135deg, #3498db, #2980b9);
    padding: 3rem 2rem;
    position: relative;
    text-align: center;
    overflow: hidden;
  }

  .register-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
    opacity: 0.1;
  }

  .register-header h2 {
    margin: 0;
    font-size: 2.2rem;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    position: relative;
    z-index: 1;
  }

  .register-header p {
    margin-top: 1rem;
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.2rem;
    position: relative;
    z-index: 1;
  }

  /* Modern Form Grid Layout */
  .form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
    padding: 2.5rem;
    background: #f8f9fa;
  }

  /* Enhanced Form Section */
  .form-section {
    background: #fff;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    border: 1px solid rgba(0, 0, 0, 0.05);
  }

  .form-section:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
  }

  .form-section h3 {
    color: #2c3e50;
    font-size: 1.4rem;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e9ecef;
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  .form-section h3 i {
    color: #3498db;
    font-size: 1.6rem;
  }

  /* Modern Input Styles */
  .input-group {
    position: relative;
    margin-bottom: 1.75rem;
  }

  .input-group label {
    display: block;
    margin-bottom: 0.75rem;
    font-weight: 600;
    color: #2c3e50;
    font-size: 1rem;
  }

  .required-field::after {
    content: "*";
    color: #e74c3c;
    margin-left: 0.25rem;
  }

  .input-group input,
  .input-group select {
    width: 100%;
    padding: 1rem 1.25rem 1rem 3rem;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: #f8f9fa;
    color: #2c3e50;
  }

  .input-group select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23666' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1.25rem center;
    padding-right: 3rem;
  }

  .input-group i {
    position: absolute;
    left: 1.25rem;
    top: 50%;
    transform: translateY(-50%);
    color: #3498db;
    font-size: 1.2rem;
  }

  .input-group input:focus,
  .input-group select:focus {
    border-color: #3498db;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
    outline: none;
  }

  /* Enhanced Helper Text */
  .helper-text {
    font-size: 0.9rem;
    color: #6c757d;
    margin-top: 0.5rem;
    padding-left: 3rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .helper-text i {
    color: #3498db;
  }

  /* Modern Error Message */
  .error-message {
    color: #e74c3c;
    font-size: 0.9rem;
    margin-top: 0.5rem;
    padding-left: 3rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    animation: shake 0.5s ease;
  }

  @keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
  }

  /* Enhanced Guidelines Section */
  .guidelines {
    grid-column: 1 / -1;
    background: #fff;
    border-left: 4px solid #3498db;
    padding: 2rem;
    border-radius: 16px;
    margin: 0 2.5rem 2.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  }

  .guidelines h4 {
    color: #2c3e50;
    font-size: 1.3rem;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  .guidelines h4 i {
    color: #3498db;
  }

  .guidelines ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
  }

  .guidelines ul li {
    margin-bottom: 0;
    padding-left: 1.75rem;
    position: relative;
    color: #6c757d;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .guidelines ul li i {
    color: #2ecc71;
    position: absolute;
    left: 0;
  }

  /* Modern Form Actions */
  .form-actions {
    background: #fff;
    padding: 2rem;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin: 0 2.5rem 2.5rem;
    border-radius: 16px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  }

  .btn {
    padding: 1rem 2rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    border: none;
    cursor: pointer;
    text-decoration: none;
  }

  .btn-primary {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: #fff;
    box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
  }

  .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
  }

  .btn-secondary {
    background: #f8f9fa;
    color: #2c3e50;
    border: 2px solid #e9ecef;
  }

  .btn-secondary:hover {
    background: #e9ecef;
    transform: translateY(-2px);
  }

  /* Modern Alert Styles */
  .alert {
    margin: 1rem 2.5rem;
    padding: 1.25rem 2rem;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 1rem;
    animation: slideIn 0.3s ease;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  }

  @keyframes slideIn {
    from {
      transform: translateY(-20px);
      opacity: 0;
    }
    to {
      transform: translateY(0);
      opacity: 1;
    }
  }

  .alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
  }

  .alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
  }

  /* Loading Animation */
  .btn-primary:disabled {
    opacity: 0.8;
    cursor: not-allowed;
  }

  .fa-spinner {
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
  }

  /* Responsive Design */
  @media (max-width: 992px) {
    .form-grid {
      grid-template-columns: 1fr;
      padding: 1.5rem;
    }

    .register-header h2 {
      font-size: 1.8rem;
    }

    .guidelines ul {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 768px) {
    .container {
      padding: 1rem;
    }

    .form-actions {
      flex-direction: column;
      margin: 0 1rem 1rem;
    }

    .btn {
      width: 100%;
      justify-content: center;
    }

    .register-header {
      padding: 2rem 1.5rem;
    }

    .register-header h2 {
      font-size: 1.5rem;
    }

    .form-grid {
      padding: 1rem;
    }

    .form-section {
      padding: 1.5rem;
    }

    .guidelines {
      margin: 0 1rem 1rem;
    }

    .alert {
      margin: 1rem;
    }
  }
  </style>
</head>

<body>
  <?php include '../includes/header.php'; ?>

  <div class="container">
    <div class="register-card">
      <div class="register-header">
        <h2><i class="fas fa-user-plus"></i> New Health Facility Registration</h2>
        <p>Enter Health Facility details to create a new account</p>
      </div>

      <form id="registerForm" class="form">
        <div class="form-grid">
          <!-- Facility Information Section -->
          <div class="form-section">
            <h3><i class="fas fa-hospital"></i> Facility Information</h3>
            <div class="form-group">
              <label class="required-field" for="name">Facility Name</label>
              <div class="input-group">
                <i class="fas fa-hospital"></i>
                <input type="text" id="name" name="name" required placeholder="e.g., Central Hospital">
                <div class="helper-text"><i class="fas fa-info-circle"></i> Enter the complete name of the health facility</div>
                <div class="error-message" id="nameError"></div>
              </div>
            </div>

            <div class="form-group">
              <label class="required-field" for="facility_id">Facility ID</label>
              <div class="input-group">
                <i class="fas fa-id-card"></i>
                <input type="text" id="facility_id" name="facility_id" required placeholder="e.g., HF001">
                <div class="helper-text"><i class="fas fa-info-circle"></i> Unique identifier for the facility</div>
                <div class="error-message" id="facilityIdError"></div>
              </div>
            </div>

            <div class="form-group">
              <label class="required-field" for="level">Facility Level</label>
              <div class="input-group">
                <i class="fas fa-layer-group"></i>
                <select id="level" name="level" required>
                  <option value="">Select facility level</option>
                  <option value="Hospital">Hospital</option>
                  <option value="Health Center">Health Center</option>
                  <option value="Clinic">Clinic</option>
                </select>
                <div class="helper-text"><i class="fas fa-info-circle"></i> Select the appropriate facility level</div>
                <div class="error-message" id="levelError"></div>
              </div>
            </div>
          </div>

          <!-- Location Information Section -->
          <div class="form-section">
            <h3><i class="fas fa-map-marker-alt"></i> Location Information</h3>
            <div class="form-group">
              <label class="required-field" for="region">Region</label>
              <div class="input-group">
                <i class="fas fa-globe"></i>
                <input type="text" id="region" name="region" required placeholder="e.g., Addis Ababa">
                <div class="helper-text"><i class="fas fa-info-circle"></i> Region where the facility is located</div>
                <div class="error-message" id="regionError"></div>
              </div>
            </div>

            <div class="form-group">
              <label class="required-field" for="zone">Zone</label>
              <div class="input-group">
                <i class="fas fa-map"></i>
                <input type="text" id="zone" name="zone" required placeholder="e.g., Central Zone">
                <div class="helper-text"><i class="fas fa-info-circle"></i> Zone where the facility is located</div>
                <div class="error-message" id="zoneError"></div>
              </div>
            </div>

            <div class="form-group">
              <label class="required-field" for="hub">Hub</label>
              <div class="input-group">
                <i class="fas fa-hub"></i>
                <input type="text" id="hub" name="hub" required placeholder="e.g., Central Hub">
                <div class="helper-text"><i class="fas fa-info-circle"></i> Hub associated with the facility</div>
                <div class="error-message" id="hubError"></div>
              </div>
            </div>

            <div class="form-group">
              <label class="required-field" for="woreda">Woreda</label>
              <div class="input-group">
                <i class="fas fa-building"></i>
                <input type="text" id="woreda" name="woreda" required placeholder="e.g., Addis Ketema">
                <div class="helper-text"><i class="fas fa-info-circle"></i> Woreda where the facility is located</div>
                <div class="error-message" id="woredaError"></div>
              </div>
            </div>
          </div>

          <!-- Credit Information Section -->
          <div class="form-section">
            <h3><i class="fas fa-credit-card"></i> Credit Information</h3>
            <div class="form-group">
              <label class="required-field" for="credit_invoice_number">Credit Invoice Number</label>
              <div class="input-group">
                <i class="fas fa-file-invoice"></i>
                <input type="text" id="credit_invoice_number" name="credit_invoice_number" required placeholder="e.g., INV001">
                <div class="helper-text"><i class="fas fa-info-circle"></i> Unique invoice number for credit tracking</div>
                <div class="error-message" id="creditInvoiceNumberError"></div>
              </div>
            </div>

            <div class="form-group">
              <label class="required-field" for="credit_balance">Initial Credit Balance</label>
              <div class="input-group">
                <i class="fas fa-dollar-sign"></i>
                <input type="number" id="credit_balance" name="credit_balance" required min="0" step="0.01" placeholder="0.00">
                <div class="helper-text"><i class="fas fa-info-circle"></i> Enter the initial credit amount</div>
                <div class="error-message" id="creditBalanceError"></div>
              </div>
            </div>

            <div class="form-group">
              <label class="required-field" for="credit_date">Credit Date</label>
              <div class="input-group">
                <i class="fas fa-calendar"></i>
                <input type="date" id="credit_date" name="credit_date" required>
                <div class="helper-text"><i class="fas fa-info-circle"></i> Date when credit was issued</div>
                <div class="error-message" id="creditDateError"></div>
              </div>
            </div>

            <div class="form-group">
              <label class="required-field" for="due_date">Due Date</label>
              <div class="input-group">
                <i class="fas fa-calendar-check"></i>
                <input type="date" id="due_date" name="due_date" required>
                <div class="helper-text"><i class="fas fa-info-circle"></i> Date when credit is due</div>
                <div class="error-message" id="dueDateError"></div>
              </div>
            </div>
          </div>

          <!-- Guidelines Section -->
          <div class="guidelines">
            <h4><i class="fas fa-info-circle"></i> Registration Guidelines</h4>
            <ul>
              <li><i class="fas fa-check-circle"></i> All fields marked with * are required</li>
              <li><i class="fas fa-check-circle"></i> Ensure Facility ID is unique</li>
              <li><i class="fas fa-check-circle"></i> Credit dates must be valid</li>
              <li><i class="fas fa-check-circle"></i> Credit balance must be a positive number</li>
              <li><i class="fas fa-check-circle"></i> Verify all information before submission</li>
            </ul>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Register Health Facility
          </button>
          <a href="list.php" class="btn btn-secondary">
            <i class="fas fa-times"></i> Cancel
          </a>
        </div>
      </form>
    </div>
  </div>

  <?php include '../includes/footer.php'; ?>

  <script>
  document.getElementById('registerForm').addEventListener('submit', function(e) {
    e.preventDefault();

    // Clear previous error messages
    document.querySelectorAll('.error-message').forEach(el => el.textContent = '');

    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering...';
    submitBtn.disabled = true;

    // Get form data
    const formData = new FormData(this);

    // Send AJAX request
    fetch('register.php', {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Show success message
          const alert = document.createElement('div');
          alert.className = 'alert alert-success';
          alert.innerHTML = `<i class="fas fa-check-circle"></i> ${data.message}`;
          this.insertBefore(alert, this.firstChild);

          // Reset form
          this.reset();

          // Redirect after 2 seconds
          setTimeout(() => {
            window.location.href = `view.php?id=${data.customer_id}`;
          }, 2000);
        } else {
          // Show error messages
          if (data.errors) {
            Object.keys(data.errors).forEach(field => {
              const errorDiv = document.getElementById(`${field}Error`);
              if (errorDiv) {
                errorDiv.textContent = data.errors[field];
              }
            });
          }
          if (data.message) {
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger';
            alert.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${data.message}`;
            this.insertBefore(alert, this.firstChild);
          }
        }
      })
      .catch(error => {
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger';
        alert.innerHTML =
          '<i class="fas fa-exclamation-circle"></i> An error occurred. Please try again.';
        this.insertBefore(alert, this.firstChild);
      })
      .finally(() => {
        // Restore button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
      });
  });
  </script>
</body>

</html>