<?php
require_once '../includes/config.php';
require_once '../includes/languages.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard/index.php');
    exit();
}

$error = '';
$success = '';

// Verify token
$token = $_GET['token'] ?? '';
if (empty($token)) {
    header('Location: forgot_password.php');
    exit();
}

try {
    // Check if token exists and is valid
    $stmt = $pdo->prepare("
        SELECT pr.*, u.email 
        FROM password_resets pr 
        JOIN users u ON pr.user_id = u.id 
        WHERE pr.token = ? AND pr.expires_at > NOW() 
        AND pr.used = 0
    ");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if (!$reset) {
        $error = t('invalid_or_expired_token');
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($password) || empty($confirm_password)) {
            $error = t('password_required');
        } else if ($password !== $confirm_password) {
            $error = t('password_match');
        } else if (strlen($password) < 6) {
            $error = t('password_length');
        } else {
            // Update password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $reset['user_id']]);

            // Mark token as used
            $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
            $stmt->execute([$reset['id']]);

            $success = t('password_reset_success');
        }
    }
} catch (PDOException $e) {
    $error = t('error_occurred');
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language'] ?? 'en'; ?>" dir="<?php echo setLanguageDirection(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('reset_password'); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <?php if (isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark'): ?>
    <link rel="stylesheet" href="../assets/css/dark-theme.css">
    <?php endif; ?>
</head>

<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <h1><?php echo t('reset_password'); ?></h1>

            <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                <p><a href="login.php"><?php echo t('back_to_login'); ?></a></p>
            </div>
            <?php elseif (!$error): ?>
            <form method="POST" action="" class="auth-form">
                <div class="form-group">
                    <label for="password"><?php echo t('new_password'); ?></label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password"><?php echo t('confirm_password'); ?></label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block"><?php echo t('reset_password'); ?></button>
            </form>
            <?php endif; ?>

            <?php if (!$success): ?>
            <div class="auth-links">
                <a href="forgot_password.php"><?php echo t('back_to_forgot_password'); ?></a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 