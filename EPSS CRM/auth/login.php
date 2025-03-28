<?php
// auth/login.php
require_once '../includes/config.php';
require_once '../includes/languages.php';

// Initialize variables
$error = '';
$success = '';

// Handle remember me cookie
if (!isset($_POST['email']) && isset($_COOKIE['remember_email'])) {
    $remembered_email = $_COOKIE['remember_email'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember_me = isset($_POST['remember_me']);

        // Validate input
        if (empty($email) || empty($password)) {
            throw new Exception(t('email_password_required'));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception(t('invalid_email_format'));
        }

        // Query user
        $stmt = $pdo->prepare("
            SELECT 
                id,
                username,
                email,
                password,
                role,
                credit_balance,
                created_at,
                updated_at
            FROM users 
            WHERE email = ?
        ");
        
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            throw new Exception(t('invalid_credentials'));
        }

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['credit_balance'] = $user['credit_balance'];

        // Handle remember me
        if ($remember_me) {
            setcookie('remember_email', $email, time() + (30 * 24 * 60 * 60), '/');
        } else {
            setcookie('remember_email', '', time() - 3600, '/');
        }

        // Update last login time
        $update_stmt = $pdo->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
        $update_stmt->execute([$user['id']]);

        // Redirect to dashboard
        header("Location: ../dashboard/index.php");
        exit;

    } catch (PDOException $e) {
        error_log("Database error in login.php: " . $e->getMessage());
        $error = t('error_occurred');
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language'] ?? 'en'; ?>" dir="<?php echo setLanguageDirection(); ?>">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo t('login'); ?> - <?php echo SITE_NAME; ?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <?php if (isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark'): ?>
  <link rel="stylesheet" href="../assets/css/dark-theme.css">
  <?php endif; ?>
  <style>
  .auth-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    padding: 2rem;
  }

  .auth-container {
    width: 100%;
    max-width: 420px;
  }

  .auth-card {
    background: white;
    padding: 2.5rem;
    border-radius: 20px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
  }

  .auth-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(to right, #006064, #00bcd4);
  }

  .auth-card h1 {
    color: #1a237e;
    font-size: 2rem;
    margin-bottom: 2rem;
    text-align: center;
    font-weight: 700;
  }

  .auth-form .form-group {
    margin-bottom: 1.5rem;
    position: relative;
  }

  .auth-form label {
    display: block;
    margin-bottom: 0.5rem;
    color: #455a64;
    font-weight: 500;
    font-size: 0.95rem;
  }

  .auth-form input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background-color: #f8f9fa;
  }

  .auth-form input:focus {
    border-color: #006064;
    background-color: white;
    box-shadow: 0 0 0 4px rgba(0, 96, 100, 0.1);
    outline: none;
  }

  .auth-form .input-icon {
    position: absolute;
    left: 1rem;
    top: 2.35rem;
    color: #90a4ae;
    transition: all 0.3s ease;
  }

  .auth-form input:focus+.input-icon {
    color: #006064;
  }

  .remember-me {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 1rem 0;
  }

  .remember-me input[type="checkbox"] {
    width: auto;
    margin: 0;
  }

  .btn-primary {
    background: linear-gradient(135deg, #006064 0%, #00838f 100%);
    color: white;
    width: 100%;
    padding: 1rem;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    position: relative;
    overflow: hidden;
  }

  .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 96, 100, 0.2);
  }

  .btn-primary::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.2), transparent);
    transform: translateX(-100%);
    transition: transform 0.5s ease;
  }

  .btn-primary:hover::after {
    transform: translateX(100%);
  }

  .auth-links {
    display: flex;
    justify-content: space-between;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e0e0e0;
  }

  .auth-links a {
    color: #006064;
    text-decoration: none;
    font-size: 0.95rem;
    font-weight: 500;
    transition: all 0.3s ease;
  }

  .auth-links a:hover {
    color: #00838f;
    text-decoration: underline;
  }

  .alert {
    padding: 1rem 1.25rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 500;
    animation: slideIn 0.3s ease;
  }

  @keyframes slideIn {
    from {
      opacity: 0;
      transform: translateY(-10px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .alert-danger {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fee2e2;
  }

  .alert-success {
    background: #f0fdf4;
    color: #16a34a;
    border: 1px solid #dcfce7;
  }

  @media (max-width: 480px) {
    .auth-page {
      padding: 1rem;
    }

    .auth-card {
      padding: 1.5rem;
    }

    .auth-links {
      flex-direction: column;
      gap: 1rem;
      text-align: center;
    }
  }
  </style>
</head>

<body class="auth-page">
  <div class="auth-container">
    <div class="auth-card">
      <h1><?php echo t('login'); ?></h1>

      <?php if ($error): ?>
      <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo htmlspecialchars($error); ?>
      </div>
      <?php endif; ?>

      <?php if ($success): ?>
      <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($success); ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="" class="auth-form">
        <div class="form-group">
          <label for="email"><?php echo t('email'); ?></label>
          <input type="email" id="email" name="email" required
            value="<?php echo htmlspecialchars($remembered_email ?? ($_POST['email'] ?? '')); ?>"
            placeholder="<?php echo t('enter_your_email'); ?>">
          <i class="fas fa-envelope input-icon"></i>
        </div>

        <div class="form-group">
          <label for="password"><?php echo t('password'); ?></label>
          <input type="password" id="password" name="password" required
            placeholder="<?php echo t('enter_your_password'); ?>">
          <i class="fas fa-lock input-icon"></i>
        </div>

        <div class="remember-me">
          <input type="checkbox" id="remember_me" name="remember_me"
            <?php echo isset($remembered_email) ? 'checked' : ''; ?>>
          <label for="remember_me"><?php echo t('remember_me'); ?></label>
        </div>

        <button type="submit" class="btn btn-primary">
          <i class="fas fa-sign-in-alt"></i>
          <?php echo t('login'); ?>
        </button>
      </form>

      <div class="auth-links">
        <a href="forgot_password.php">
          <i class="fas fa-key"></i>
          <?php echo t('forgot_password'); ?>
        </a>
        <a href="register.php">
          <i class="fas fa-user-plus"></i>
          <?php echo t('create_account'); ?>
        </a>
      </div>
    </div>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', function() {
    // Add password visibility toggle
    const passwordInput = document.getElementById('password');
    const togglePassword = document.createElement('i');
    togglePassword.className = 'fas fa-eye';
    togglePassword.style.cssText = `
      position: absolute;
      right: 1rem;
      top: 2.35rem;
      color: #90a4ae;
      cursor: pointer;
      transition: all 0.3s ease;
    `;

    passwordInput.parentElement.appendChild(togglePassword);

    togglePassword.addEventListener('click', function() {
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordInput.setAttribute('type', type);
      this.className = `fas fa-eye${type === 'password' ? '' : '-slash'}`;
    });

    // Add form validation
    const form = document.querySelector('.auth-form');
    const emailInput = document.getElementById('email');

    form.addEventListener('submit', function(e) {
      let isValid = true;

      // Email validation
      if (!emailInput.value.match(/^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/)) {
        showError(emailInput, '<?php echo t('invalid_email_format'); ?>');
        isValid = false;
      } else {
        removeError(emailInput);
      }

      // Password validation
      if (passwordInput.value.length < 6) {
        showError(passwordInput, '<?php echo t('password_min_length'); ?>');
        isValid = false;
      } else {
        removeError(passwordInput);
      }

      if (!isValid) {
        e.preventDefault();
      }
    });

    function showError(input, message) {
      const existingError = input.parentElement.querySelector('.error-message');
      if (!existingError) {
        const error = document.createElement('div');
        error.className = 'error-message';
        error.style.cssText = `
          color: #dc2626;
          font-size: 0.875rem;
          margin-top: 0.5rem;
          animation: slideIn 0.3s ease;
        `;
        error.textContent = message;
        input.parentElement.appendChild(error);
      }
      input.style.borderColor = '#dc2626';
    }

    function removeError(input) {
      const error = input.parentElement.querySelector('.error-message');
      if (error) {
        error.remove();
      }
      input.style.borderColor = '#e0e0e0';
    }
  });
  </script>
</body>

</html>