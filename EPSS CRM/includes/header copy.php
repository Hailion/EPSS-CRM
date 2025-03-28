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

// Determine current page
$current_page = '';
$script_name = basename($_SERVER['SCRIPT_NAME']);
$script_path = $_SERVER['SCRIPT_NAME'];

if (strpos($script_path, '/dashboard/') !== false) {
    $current_page = 'dashboard';
} elseif (strpos($script_path, '/customers/') !== false) {
    $current_page = 'customers';
} elseif (strpos($script_path, '/transactions/') !== false) {
    $current_page = 'transactions';
} elseif (strpos($script_path, '/reports/') !== false) {
    $current_page = 'reports';
} elseif (strpos($script_path, '/settings/') !== false) {
    $current_page = 'settings';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Content-Language" content="<?php echo $_SESSION['language'] ?? 'en'; ?>">
  <title><?php echo isset($page_title) ? $page_title . ' - ' . t('app_name') : t('app_name'); ?></title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <!-- Custom CSS -->
  <style>
  :root {
    --sidebar-width: 250px;
    --header-height: 60px;
  }

  body {
    margin: 0;
    padding: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f8f9fa;
  }

  .app-container {
    display: flex;
    min-height: 100vh;
  }

  /* Sidebar Styles */
  .sidebar {
    width: var(--sidebar-width);
    background-color: #2c3e50;
    color: white;
    position: fixed;
    height: 100vh;
    overflow-y: auto;
    transition: all 0.3s ease;
    z-index: 1000;
  }

  .sidebar-header {
    padding: 1rem;
    text-align: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  }

  .sidebar-logo {
    font-size: 1.5rem;
    font-weight: bold;
    color: white;
    text-decoration: none;
  }

  .sidebar-menu {
    padding: 1rem 0;
  }

  .nav-item {
    margin: 0.5rem 1rem;
  }

  .nav-link {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
  }

  .nav-link:hover {
    background-color: rgba(255, 255, 255, 0.1);
    color: white;
  }

  .nav-link.active {
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
  }

  .nav-link i {
    margin-right: 0.75rem;
    width: 20px;
    text-align: center;
  }

  .sidebar-footer {
    padding: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    position: absolute;
    bottom: 0;
    width: 100%;
    background-color: #2c3e50;
  }

  .user-profile {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  .user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .user-info {
    flex: 1;
  }

  .user-name {
    font-weight: 500;
    color: white;
  }

  .user-role {
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.6);
  }

  /* Main Content Area */
  .main-wrapper {
    flex: 1;
    margin-left: var(--sidebar-width);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
  }

  /* Top Navbar */
  .top-navbar {
    height: var(--header-height);
    background-color: white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    position: sticky;
    top: 0;
    z-index: 900;
    display: flex;
    align-items: center;
    padding: 0 1rem;
  }

  .navbar-left {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .sidebar-toggle {
    display: none;
    background: none;
    border: none;
    color: #333;
    font-size: 1.25rem;
    cursor: pointer;
    padding: 0.5rem;
  }

  .navbar-right {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .notification-bell {
    position: relative;
    color: #333;
    font-size: 1.25rem;
    cursor: pointer;
  }

  .notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: #dc3545;
    color: white;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 10px;
  }

  .user-menu {
    position: relative;
  }

  .user-menu-button {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: none;
    border: none;
    color: #333;
    cursor: pointer;
    padding: 0.5rem;
  }

  .user-menu-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    min-width: 200px;
    display: none;
  }

  .user-menu-dropdown.show {
    display: block;
  }

  .dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    color: #333;
    text-decoration: none;
    transition: background-color 0.3s ease;
  }

  .dropdown-item:hover {
    background-color: #f8f9fa;
  }

  .dropdown-divider {
    height: 1px;
    background-color: #dee2e6;
    margin: 0.5rem 0;
  }

  /* Main Content */
  .main-content {
    flex: 1;
    padding: 1rem;
    background-color: #f8f9fa;
  }

  /* Responsive Design */
  @media (max-width: 768px) {
    .sidebar {
      transform: translateX(-100%);
    }

    .sidebar.show {
      transform: translateX(0);
    }

    .main-wrapper {
      margin-left: 0;
    }

    .sidebar-toggle {
      display: block;
    }

    .overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 999;
    }

    .overlay.show {
      display: block;
    }
  }
  </style>
</head>

<body class="<?php echo $_SESSION['theme'] === 'dark' ? 'dark-theme' : ''; ?>">
  <div class="app-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <a href="/" class="sidebar-logo">
          <i class="fas fa-credit-card"></i> EPSS CRM
        </a>
      </div>
      <nav class="sidebar-menu">
        <div class="nav-item">
          <a href="<?php echo $base_url; ?>dashboard/index.php"
            class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            <?php echo t('dashboard'); ?>
          </a>
        </div>
        <div class="nav-item">
          <a href="<?php echo $base_url; ?>customers/list.php"
            class="nav-link <?php echo $current_page === 'customers' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <?php echo t('customers'); ?>
          </a>
        </div>
        <div class="nav-item">
          <a href="<?php echo $base_url; ?>transactions/list.php"
            class="nav-link <?php echo $current_page === 'transactions' ? 'active' : ''; ?>">
            <i class="fas fa-exchange-alt"></i>
            <?php echo t('transactions'); ?>
          </a>
        </div>
        <div class="nav-item">
          <a href="<?php echo $base_url; ?>reports/index.php"
            class="nav-link <?php echo $current_page === 'reports' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i>
            <?php echo t('reports'); ?>
          </a>
        </div>
        <div class="nav-item">
          <a href="<?php echo $base_url; ?>settings/index.php"
            class="nav-link <?php echo $current_page === 'settings' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            <?php echo t('settings'); ?>
          </a>
        </div>
      </nav>
      <div class="sidebar-footer">
        <div class="user-profile">
          <div class="user-avatar">
            <i class="fas fa-user"></i>
          </div>
          <div class="user-info">
            <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></div>
            <div class="user-role"><?php echo t('administrator'); ?></div>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="main-wrapper">
      <!-- Top Navbar -->
      <nav class="top-navbar">
        <div class="navbar-left">
          <button class="sidebar-toggle">
            <i class="fas fa-bars"></i>
          </button>
        </div>
        <div class="navbar-right">
          <div class="notification-bell">
            <i class="fas fa-bell"></i>
            <span class="notification-badge">3</span>
          </div>
          <div class="user-menu">
            <button class="user-menu-button">
              <i class="fas fa-user-circle"></i>
              <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
            </button>
            <div class="user-menu-dropdown">
              <a href="<?php echo $base_url; ?>profile/index.php" class="dropdown-item">
                <i class="fas fa-user"></i>
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
          </div>
        </div>
      </nav>

      <!-- Main Content -->
      <main class="main-content">
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

        <!-- Page content will be injected here -->
        <div class="page-content">
          <?php if (isset($page_content)) echo $page_content; ?>
        </div>
      </main>
    </div>
  </div>
</body>

</html>