<?php require_once __DIR__ . '/includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>403 - Access Denied</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
  .error-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    background-color: #f5f5f5;
  }

  .error-container {
    max-width: 600px;
    padding: 2rem;
  }

  .error-code {
    font-size: 8rem;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 1rem;
    line-height: 1;
  }

  .error-message {
    font-size: 2rem;
    color: #34495e;
    margin-bottom: 2rem;
  }

  .error-description {
    color: #7f8c8d;
    margin-bottom: 2rem;
  }

  .back-home {
    display: inline-block;
    padding: 1rem 2rem;
    background-color: #3498db;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    transition: background-color 0.3s;
  }

  .back-home:hover {
    background-color: #2980b9;
  }
  </style>
</head>

<body class="error-page">
  <div class="error-container">
    <div class="error-code">403</div>
    <h1 class="error-message">Access Denied</h1>
    <p class="error-description">
      You do not have permission to access this page.
      Please contact your administrator if you believe this is a mistake.
    </p>
    <a href="<?php echo appUrl(); ?>" class="back-home">Back to Home</a>
  </div>
</body>

</html>
