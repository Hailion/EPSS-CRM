<?php
// index.php - Main Landing Page
require_once 'includes/config.php';

// Redirect to dashboard if logged in
if (isset($_SESSION['user_id'])) {
    redirect("dashboard/index.php");
}

// Verify login path exists
$login_path = "auth/login.php";
if (!file_exists($login_path)) {
    die("Error: Login file not found. Please check system configuration.");
}

// Attempt to redirect to login page
redirect($login_path);

// Fallback HTML will only be shown if headers were already sent
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="EPSS CRM - Credit Management System">
  <meta name="keywords" content="CRM, Credit Management, EPSS">
  <meta name="author" content="EPSS">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta http-equiv="refresh" content="0;url=<?php echo htmlspecialchars($login_path); ?>">
  <title>EPSS CRM - Credit Management System</title>
  <style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100vh;
    margin: 0;
    background-color: #f5f7f8;
    color: #333;
  }

  .container {
    text-align: center;
    padding: 2.5rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    max-width: 500px;
    width: 90%;
  }

  h1 {
    color: #2c3e50;
    margin-bottom: 1.5rem;
    font-size: 2rem;
  }

  .loading-message {
    color: #666;
    margin-bottom: 1.5rem;
  }

  .link {
    color: #3498db;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
  }

  .link:hover {
    color: #2980b9;
    text-decoration: underline;
  }

  .spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 20px auto;
  }

  @keyframes spin {
    0% {
      transform: rotate(0deg);
    }

    100% {
      transform: rotate(360deg);
    }
  }
  </style>
</head>

<body>
  <div class="container">
    <h1>EPSS CRM</h1>
    <p class="loading-message">Initializing Credit Management System...</p>
    <div class="spinner"></div>
    <p>If you're not redirected automatically, <a href="<?php echo htmlspecialchars($login_path); ?>" class="link">click
        here to continue</a>.</p>
  </div>
</body>

</html>

// Session cleanup code - Only use if explicitly needed
/*
if (isset($_SESSION)) {
// Clear all session variables
$_SESSION = array();

// If you want to destroy the session cookie
if (isset($_COOKIE[session_name()])) {
setcookie(session_name(), '', time()-3600, '/');
}

// Finally, destroy the session
session_destroy();
}
*/