<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
require_once '../includes/languages.php';

$page_title = t('settings');

// Define base URL for assets
$base_url = '';

// Get user settings
$settings = getUserSettings();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theme = $_POST['theme'] ?? 'light';
    $notifications = isset($_POST['notifications']) ? 1 : 0;
    $language = $_POST['language'] ?? 'en';
    
    try {
        if (updateUserSettings($theme, $language, $notifications)) {
            // Update session variables
            $_SESSION['theme'] = $theme;
            $_SESSION['language'] = $language;
            $_SESSION['notifications'] = $notifications;
            
            $success = t('settings_updated');
        } else {
            $error = t('error_updating');
        }
    } catch (PDOException $e) {
        $error = t('error_updating');
    }
}

// Set default values if not set
$theme = $settings['theme'] ?? 'light';
$language = $settings['language'] ?? 'en';
$notifications = $settings['notifications'] ?? 1;
?>

<!DOCTYPE html>
<html <?php echo getLanguageHtmlAttributes(); ?>>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Content-Language" content="<?php echo $language; ?>">
  <title><?php echo $page_title; ?> - Credit Management System</title>
  <!-- Add Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/style.css">
  <?php if ($theme === 'dark'): ?>
  <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/dark-theme.css">
  <?php endif; ?>
  <style>
  .settings-container {
    max-width: 800px;
    margin: 80px auto 20px;
    padding: 20px;
  }

  .settings-card {
    background: var(--card-bg);
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    padding: 20px;
  }

  .settings-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
  }

  .settings-header i {
    font-size: 24px;
    color: var(--primary-color);
  }

  .form-group {
    margin-bottom: 20px;
  }

  .form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
  }

  .form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    background: var(--input-bg);
    color: var(--text-color);
  }

  .checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
  }

  .checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
  }

  .btn-primary {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.3s;
  }

  .btn-primary:hover {
    background: var(--primary-dark);
  }

  .alert {
    padding: 12px;
    border-radius: 4px;
    margin-bottom: 20px;
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
  </style>
</head>

<body class="<?php echo $theme === 'dark' ? 'dark-theme' : ''; ?>">
  <?php include '../includes/header.php'; ?>

  <div class="settings-container">
    <div class="settings-card">
      <div class="settings-header">
        <i class="fas fa-cog"></i>
        <h2><?php echo t('settings'); ?></h2>
      </div>

      <?php if (isset($error)): ?>
      <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo htmlspecialchars($error); ?>
      </div>
      <?php endif; ?>

      <?php if (isset($success)): ?>
      <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($success); ?>
      </div>
      <?php endif; ?>

      <form method="POST" class="form" id="settingsForm">
        <div class="form-group">
          <label for="theme">
            <i class="fas fa-palette"></i>
            <?php echo t('theme'); ?>
          </label>
          <select id="theme" name="theme" class="form-control">
            <option value="light" <?php echo $theme === 'light' ? 'selected' : ''; ?>><?php echo t('light'); ?></option>
            <option value="dark" <?php echo $theme === 'dark' ? 'selected' : ''; ?>><?php echo t('dark'); ?></option>
          </select>
        </div>

        <div class="form-group">
          <label for="language">
            <i class="fas fa-globe"></i>
            <?php echo t('language'); ?>
          </label>
          <select id="language" name="language" class="form-control">
            <option value="en" <?php echo $language === 'en' ? 'selected' : ''; ?>><?php echo t('english'); ?></option>
            <option value="am" <?php echo $language === 'am' ? 'selected' : ''; ?>><?php echo t('amharic'); ?></option>
          </select>
        </div>

        <div class="form-group">
          <label class="checkbox-label">
            <input type="checkbox" name="notifications" <?php echo $notifications ? 'checked' : ''; ?>>
            <i class="fas fa-bell"></i>
            <?php echo t('notifications'); ?>
          </label>
        </div>

        <div class="form-group">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i>
            <?php echo t('save_settings'); ?>
          </button>
        </div>
      </form>
    </div>
  </div>

  <?php include '../includes/footer.php'; ?>

  <script>
  // Only submit the form when the save button is clicked
  document.getElementById('settingsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    this.submit();
  });
  </script>
</body>

</html>