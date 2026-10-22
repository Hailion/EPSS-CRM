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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = t('email_required');
    } else {
        try {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Store reset token
                $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user['id'], $token, $expires]);
                
                // Create HTML email message
                $reset_link = SITE_URL . '/auth/reset_password.php?token=' . $token;
                $html_message = '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="' . MAIL_CHARSET . '">
                    <title>' . t('password_reset_subject') . '</title>
                </head>
                <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                        <h2 style="color: #2c3e50;">' . t('password_reset_subject') . '</h2>
                        <p>Hello ' . htmlspecialchars($user['username']) . ',</p>
                        <p>You have requested to reset your password. Click the button below to reset it:</p>
                        <p style="text-align: center;">
                            <a href="' . $reset_link . '" style="display: inline-block; padding: 10px 20px; background-color: #3498db; color: #ffffff; text-decoration: none; border-radius: 5px;">Reset Password</a>
                        </p>
                        <p>This link will expire at ' . $expires . '.</p>
                        <p>If you did not request this reset, please ignore this email.</p>
                        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
                        <p style="color: #7f8c8d; font-size: 12px;">This is an automated message from ' . SITE_NAME . '. Please do not reply to this email.</p>
                    </div>
                </body>
                </html>';
                
                // Create plain text version
                $text_message = sprintf(
                    t('password_reset_message'),
                    $user['username'],
                    $reset_link,
                    $expires
                );
                
                // Email headers
                $boundary = md5(uniqid(time()));
                $headers = array(
                    'MIME-Version: 1.0',
                    'Content-Type: multipart/alternative; boundary=' . $boundary,
                    'From: ' . MAIL_FROM_NAME . ' <' . ADMIN_EMAIL . '>',
                    'Reply-To: ' . ADMIN_EMAIL,
                    'X-Mailer: PHP/' . phpversion()
                );
                
                // Construct the email body with both HTML and plain text versions
                $body = '';
                
                // Plain text version
                $body .= '--' . $boundary . "\r\n";
                $body .= 'Content-Type: text/plain; charset=' . MAIL_CHARSET . "\r\n";
                $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
                $body .= $text_message . "\r\n\r\n";
                
                // HTML version
                $body .= '--' . $boundary . "\r\n";
                $body .= 'Content-Type: text/html; charset=' . MAIL_CHARSET . "\r\n";
                $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
                $body .= $html_message . "\r\n\r\n";
                
                $body .= '--' . $boundary . '--';
                
                if (mail($email, t('password_reset_subject'), $body, implode("\r\n", $headers))) {
                    $success = t('reset_email_sent');
                } else {
                    $error = t('error_sending_email');
                }
            } else {
                // Don't reveal if email exists or not
                $success = t('reset_email_sent');
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
  <title><?php echo t('forgot_password'); ?> - <?php echo SITE_NAME; ?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <?php if (isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark'): ?>
  <link rel="stylesheet" href="../assets/css/dark-theme.css">
  <?php endif; ?>
</head>

<body>
  <div class="auth-container">
    <div class="auth-box">
      <h1><?php echo t('forgot_password'); ?></h1>

      <?php if ($error): ?>
      <div class="alert alert-danger">
        <?php echo htmlspecialchars($error); ?>
      </div>
      <?php endif; ?>

      <?php if ($success): ?>
      <div class="alert alert-success">
        <?php echo htmlspecialchars($success); ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="form-group">
          <label for="email"><?php echo t('email'); ?></label>
          <input type="email" id="email" name="email" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary"><?php echo t('send_reset_link'); ?></button>
      </form>

      <div class="auth-links">
        <a href="login.php"><?php echo t('back_to_login'); ?></a>
      </div>
    </div>
  </div>
</body>

</html>