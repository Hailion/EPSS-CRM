<?php
// auth/register.php
require_once '../includes/config.php';
require_once '../includes/languages.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard/index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = t('all_fields_required');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = t('invalid_email_format');
    } elseif (strlen($password) < 6) {
        $error = t('password_length');
    } elseif ($password !== $confirm_password) {
        $error = t('password_match');
    } else {
        try {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                $error = t('username_email_exists');
            } else {
                // Create new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
                $stmt->execute([$username, $email, $hashed_password]);
                
                $success = t('registration_success');
            }
        } catch (PDOException $e) {
            $error = t('error_occurred');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language'] ?? 'en'; ?>" dir="<?php echo setLanguageDirection(); ?>">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo t('register'); ?> - <?php echo SITE_NAME; ?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <?php if (isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark'): ?>
  <link rel="stylesheet" href="../assets/css/dark-theme.css">
  <?php endif; ?>
  <style>
  :root {
    --primary-color: #2563eb;
    --primary-dark: #1d4ed8;
    --accent-color: #8b5cf6;
    --success-color: #22c55e;
    --error-color: #ef4444;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --bg-primary: #ffffff;
    --bg-secondary: #f8fafc;
    --border-color: #e2e8f0;
  }

  .auth-page {
    height: 100vh;
    display: flex;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    padding: 0.5rem;
    align-items: center;
    justify-content: center;
    overflow: hidden;
  }

  .auth-container {
    width: 100%;
    max-width: 800px;
    height: min(600px, 95vh);
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    background: var(--bg-primary);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  }

  .auth-welcome {
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    color: white;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  .auth-welcome h2 {
    font-size: 1.4rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
  }

  .auth-welcome p {
    font-size: 0.85rem;
    margin-bottom: 1rem;
    opacity: 0.9;
  }

  .features-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .features-list li {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8rem;
  }

  .feature-icon {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 6px;
    font-size: 0.7rem;
  }

  .auth-card {
    padding: 1.5rem;
    position: relative;
    overflow-y: auto;
  }

  .auth-card h1 {
    font-size: 1.3rem;
    margin-bottom: 1rem;
    color: var(--text-primary);
    text-align: center;
  }

  .auth-form .form-group {
    margin-bottom: 0.75rem;
    position: relative;
  }

  .auth-form label {
    display: block;
    margin-bottom: 0.2rem;
    color: var(--text-primary);
    font-size: 0.8rem;
    font-weight: 500;
  }

  .auth-form input {
    width: 100%;
    padding: 0.5rem 0.75rem 0.5rem 2rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 0.85rem;
    transition: all 0.2s ease;
    background-color: var(--bg-secondary);
  }

  .auth-form input:focus {
    border-color: var(--primary-color);
    background-color: var(--bg-primary);
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
    outline: none;
  }

  .auth-form .input-icon {
    position: absolute;
    left: 0.7rem;
    top: 1.8rem;
    color: var(--text-secondary);
    font-size: 0.8rem;
  }

  .btn-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    color: white;
    width: 100%;
    padding: 0.6rem;
    border: none;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-top: 0.5rem;
  }

  .btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
  }

  .alert {
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.8rem;
  }

  .alert-danger {
    background: #fef2f2;
    color: var(--error-color);
    border: 1px solid #fee2e2;
  }

  .alert-success {
    background: #f0fdf4;
    color: var(--success-color);
    border: 1px solid #dcfce7;
  }

  .auth-links {
    text-align: center;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--border-color);
    font-size: 1rem;
    padding-left: 15%;
  }

  .auth-links a {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
  }

  .auth-links a:hover {
    text-decoration: underline;
  }

  .password-requirements {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.4rem;
    margin: 0.4rem 0;
    font-size: 0.75rem;
  }

  .requirement {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    color: var(--text-secondary);
  }

  .requirement i {
    font-size: 0.65rem;
  }

  .requirement.met {
    color: var(--success-color);
  }

  @media (max-width: 640px) {
    .auth-container {
      grid-template-columns: 1fr;
      max-width: 360px;
      height: auto;
      min-height: 0;
    }

    .auth-welcome {
      padding: 1rem;
      text-align: center;
    }

    .auth-welcome h2 {
      font-size: 1.2rem;
    }

    .features-list {
      flex-direction: row;
      flex-wrap: wrap;
      justify-content: center;
      gap: 0.5rem;
    }

    .features-list li {
      width: auto;
      font-size: 0.75rem;
    }

    .auth-card {
      padding: 1rem;
    }

    .auth-card h1 {
      font-size: 1.2rem;
      margin-bottom: 0.75rem;
    }
  }
  </style>
</head>

<body class="auth-page">
  <div class="auth-container">
    <div class="auth-welcome">
      <h2>Welcome to <?php echo SITE_NAME; ?></h2>
      <p>Join our community and take control of your credit management journey.</p>

      <div class="features-list">
        <li>
          <div class="feature-icon">
            <i class="fas fa-chart-line"></i>
          </div>
          Track credit history in real-time
        </li>
        <li>
          <div class="feature-icon">
            <i class="fas fa-bell"></i>
          </div>
          Get payment reminders
        </li>
        <li>
          <div class="feature-icon">
            <i class="fas fa-shield-alt"></i>
          </div>
          Secure transactions
        </li>
      </div>
    </div>

    <div class="auth-card">
      <h1><?php echo t('register'); ?></h1>

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
          <label for="username"><?php echo t('username'); ?></label>
          <input type="text" id="username" name="username" required
            value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" placeholder="Choose a username">
          <i class="fas fa-user input-icon"></i>
        </div>

        <div class="form-group">
          <label for="email"><?php echo t('email'); ?></label>
          <input type="email" id="email" name="email" required
            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="Enter your email">
          <i class="fas fa-envelope input-icon"></i>
        </div>

        <div class="form-group">
          <label for="password"><?php echo t('password'); ?></label>
          <input type="password" id="password" name="password" required placeholder="Create a strong password">
          <i class="fas fa-lock input-icon"></i>
        </div>

        <div class="form-group">
          <label for="confirm_password"><?php echo t('confirm_password'); ?></label>
          <input type="password" id="confirm_password" name="confirm_password" required
            placeholder="Confirm your password">
          <i class="fas fa-lock input-icon"></i>
        </div>

        <div class="password-requirements">
          <div class="requirement" id="length">
            <i class="fas fa-circle"></i>
            Min. 6 characters
          </div>
          <div class="requirement" id="lowercase">
            <i class="fas fa-circle"></i>
            Lowercase letter
          </div>
          <div class="requirement" id="uppercase">
            <i class="fas fa-circle"></i>
            Uppercase letter
          </div>
          <div class="requirement" id="number">
            <i class="fas fa-circle"></i>
            Number
          </div>
        </div>

        <button type="submit" class="btn btn-primary">
          <i class="fas fa-user-plus"></i>
          <?php echo t('register'); ?>
        </button>
      </form>

      <div class="auth-links">
        <a href="login.php">
          <i class="fas fa-sign-in-alt"></i>
          <?php echo t('already_have_account'); ?>
        </a>
      </div>
    </div>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const requirements = {
      length: {
        regex: /.{6,}/,
        element: document.getElementById('length')
      },
      lowercase: {
        regex: /[a-z]/,
        element: document.getElementById('lowercase')
      },
      uppercase: {
        regex: /[A-Z]/,
        element: document.getElementById('uppercase')
      },
      number: {
        regex: /[0-9]/,
        element: document.getElementById('number')
      }
    };

    // Add password visibility toggle
    [passwordInput, confirmPasswordInput].forEach(input => {
      const togglePassword = document.createElement('i');
      togglePassword.className = 'fas fa-eye';
      togglePassword.style.cssText = `
                position: absolute;
                right: 1rem;
                top: 2.35rem;
                color: var(--text-secondary);
                cursor: pointer;
                transition: all 0.3s ease;
            `;

      input.parentElement.appendChild(togglePassword);

      togglePassword.addEventListener('click', function() {
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
        this.className = `fas fa-eye${type === 'password' ? '' : '-slash'}`;
      });
    });

    // Password requirements check
    passwordInput.addEventListener('input', function() {
      const password = this.value;

      for (const [key, requirement] of Object.entries(requirements)) {
        const isValid = requirement.regex.test(password);
        requirement.element.classList.toggle('met', isValid);
        requirement.element.querySelector('i').className =
          `fas fa-${isValid ? 'check-circle' : 'circle'}`;
      }
    });

    // Form validation
    const form = document.querySelector('.auth-form');
    const emailInput = document.getElementById('email');
    const usernameInput = document.getElementById('username');

    form.addEventListener('submit', function(e) {
      let isValid = true;

      // Username validation
      if (usernameInput.value.length < 3) {
        showError(usernameInput, 'Username must be at least 3 characters long');
        isValid = false;
      } else {
        removeError(usernameInput);
      }

      // Email validation
      if (!emailInput.value.match(/^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/)) {
        showError(emailInput, '<?php echo t('invalid_email_format'); ?>');
        isValid = false;
      } else {
        removeError(emailInput);
      }

      // Password validation
      if (passwordInput.value.length < 6) {
        showError(passwordInput, '<?php echo t('password_length'); ?>');
        isValid = false;
      } else {
        removeError(passwordInput);
      }

      // Confirm password validation
      if (passwordInput.value !== confirmPasswordInput.value) {
        showError(confirmPasswordInput, '<?php echo t('password_match'); ?>');
        isValid = false;
      } else {
        removeError(confirmPasswordInput);
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
                    color: var(--error-color);
                    font-size: 0.875rem;
                    margin-top: 0.5rem;
                    animation: slideIn 0.3s ease;
                `;
        error.textContent = message;
        input.parentElement.appendChild(error);
      }
      input.style.borderColor = 'var(--error-color)';
    }

    function removeError(input) {
      const error = input.parentElement.querySelector('.error-message');
      if (error) {
        error.remove();
      }
      input.style.borderColor = 'var(--border-color)';
    }
  });
  </script>
</body>

</html>