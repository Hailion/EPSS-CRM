<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';

$page_title = 'Profile';

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // Validate current password
    if (!empty($current_password)) {
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect";
        }
    }
    
    // Validate new password if provided
    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters long";
        }
        if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
    }
    
    // Update profile if no errors
    if (empty($errors)) {
        try {
            if (!empty($new_password)) {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
                $stmt->execute([$username, $email, password_hash($new_password, PASSWORD_DEFAULT), $_SESSION['user_id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                $stmt->execute([$username, $email, $_SESSION['user_id']]);
            }
            
            $success = "Profile updated successfully";
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
        } catch (PDOException $e) {
            $errors[] = "Error updating profile: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $page_title; ?> - Credit Management System</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
  <?php include '../includes/header.php'; ?>

  <div class="container">
    <div class="card">
      <h2>Profile Settings</h2>

      <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?>
        <p><?php echo htmlspecialchars($error); ?></p>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if (isset($success)): ?>
      <div class="alert alert-success">
        <?php echo htmlspecialchars($success); ?>
      </div>
      <?php endif; ?>

      <form method="POST" class="form">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>"
            required>
        </div>

        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>

        <div class="form-group">
          <label for="current_password">Current Password</label>
          <input type="password" id="current_password" name="current_password">
        </div>

        <div class="form-group">
          <label for="new_password">New Password</label>
          <input type="password" id="new_password" name="new_password">
        </div>

        <div class="form-group">
          <label for="confirm_password">Confirm New Password</label>
          <input type="password" id="confirm_password" name="confirm_password">
        </div>

        <div class="form-group">
          <button type="submit" class="btn btn-primary">Update Profile</button>
        </div>
      </form>
    </div>
  </div>

  <?php include '../includes/footer.php'; ?>
</body>

</html>