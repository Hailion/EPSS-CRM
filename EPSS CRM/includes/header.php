<?php
// includes/header.php
if (!isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/languages.php';

// Get user data
$user = getCurrentUser();

// Define base URL for assets
$base_url = '../';
?>
<!DOCTYPE html>
<html lang="<?php echo $current_language; ?>" dir="<?php echo $direction; ?>">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Content-Language" content="<?php echo $_SESSION['language'] ?? 'en'; ?>">
  <title><?php echo $page_title; ?> - <?php echo t('app_name'); ?></title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <!-- Custom CSS -->
  <link href="../assets/css/style.css" rel="stylesheet">
  <?php if (isset($additional_css)) echo $additional_css; ?>
</head>

<body class="<?php echo $_SESSION['theme'] === 'dark' ? 'dark-theme' : ''; ?>">
  <header class="header">
    <div class="header-content">
      <div class="logo">
        <i class="fas fa-credit-card"></i>
        <h1>EPSS CRM</h1>
      </div>

      <button class="mobile-menu-btn">
        <i class="fas fa-bars"></i>
      </button>

      <nav class="nav-menu">
        <a href="<?php echo $base_url; ?>dashboard/index.php"
          class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
          <i class="fas fa-home"></i>
          <span><?php echo t('dashboard'); ?></span>
        </a>
        <a href="<?php echo $base_url; ?>customers/list.php"
          class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/customers/') !== false ? 'active' : ''; ?>">
          <i class="fas fa-users"></i>
          <span><?php echo t('Health Facilities'); ?></span>
        </a>
        <a href="<?php echo $base_url; ?>transactions/list.php"
          class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/transactions/') !== false ? 'active' : ''; ?>">
          <i class="fas fa-exchange-alt"></i>
          <span><?php echo t('transactions'); ?></span>
        </a>
        <a href="<?php echo $base_url; ?>reports/index.php"
          class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/reports/') !== false ? 'active' : ''; ?>">
          <i class="fas fa-chart-bar"></i>
          <span><?php echo t('reports'); ?></span>
        </a>
      </nav>

      <div class="user-menu">
        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="user-info">
          <div class="user-avatar">
            <i class="fas fa-user"></i>
          </div>
          <span><?php echo htmlspecialchars($user['username'] ?? 'User'); ?></span>
        </div>

        <div class="dropdown-menu">
          <a href="<?php echo $base_url; ?>profile/index.php" class="dropdown-item">
            <i class="fas fa-user-circle"></i>
            <?php echo t('profile'); ?>
          </a>
          <a href="<?php echo $base_url; ?>settings/index.php" class="dropdown-item">
            <i class="fas fa-cog"></i>
            <?php echo t('settings'); ?>
          </a>
          <div class="dropdown-divider"></div>
          <a href="<?php echo $base_url; ?>auth/logout.php" class="dropdown-item">
            <i class="fas fa-sign-out-alt"></i>
            <?php echo t('logout'); ?>
          </a>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <div class="main-content">
    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?php 
        echo $_SESSION['success'];
        unset($_SESSION['success']);
        ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?php 
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Mobile menu toggle
      const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
      const navMenu = document.querySelector('.nav-menu');

      mobileMenuBtn.addEventListener('click', function() {
        navMenu.classList.toggle('show');
      });

      // Close mobile menu when clicking outside
      document.addEventListener('click', function(event) {
        if (!event.target.closest('.nav-menu') && !event.target.closest('.mobile-menu-btn')) {
          navMenu.classList.remove('show');
        }
      });

      // User dropdown toggle
      const userInfo = document.querySelector('.user-info');
      const dropdownMenu = document.querySelector('.dropdown-menu');

      userInfo.addEventListener('click', function() {
        dropdownMenu.classList.toggle('show');
      });

      // Close dropdown when clicking outside
      document.addEventListener('click', function(event) {
        if (!event.target.closest('.user-menu')) {
          dropdownMenu.classList.remove('show');
        }
      });
    });
    </script>
    <!-- </div> -->
</body>

</html>