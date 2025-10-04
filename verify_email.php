<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/Application.php';
require_once __DIR__ . '/lib/UserManagement.php';
require_once __DIR__ . '/lib/ApplicationUI.php';
require_once __DIR__ . '/settings.php';

// Initialize application
Application::init();

function h($s) { 
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); 
}

// If already logged in, redirect to home
if (current_user()) {
    header('Location: /index.php');
    exit;
}

$token = $_GET['token'] ?? '';
$success = false;
$error = null;
$needsPasswordSetup = false;

if ($token) {
    // Check if user exists and needs password setup
    $st = pdo()->prepare('SELECT * FROM users WHERE email_verify_token = ? LIMIT 1');
    $st->execute([$token]);
    $user = $st->fetch();
    
    if ($user && $user['password_hash'] === '') {
        // User needs to set up password - redirect to password setup
        $needsPasswordSetup = true;
    } elseif (UserManagement::verifyByToken($token)) {
        $success = true;
    } else {
        $error = 'Invalid or expired verification link.';
    }
} else {
    $error = 'Invalid verification link.';
}

// Redirect to password setup if needed
if ($needsPasswordSetup) {
    header('Location: /set_password.php?token=' . urlencode($token));
    exit;
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Verify Email - <?=h(Settings::siteTitle())?></title>
<?=ApplicationUI::cssLink('/styles.css')?></head>
<body class="auth">
  <div class="card">
    <?php 
      $loginImageUrl = Settings::loginImageUrl();
      if ($loginImageUrl !== ''): 
    ?>
      <center>
        <img width="200" src="<?=h($loginImageUrl)?>" alt="Login Logo" class="logo" style="margin-bottom: 16px;">
      </center>
    <?php endif; ?>
    <h1>Email Verification</h1>
    <p class="subtitle"><?=h(Settings::siteTitle())?></p>
    
    <?php if ($success): ?>
      <p class="flash">Your email has been verified successfully!</p>
      <p><a href="/login.php?verified=1">Sign In</a></p>
    <?php else: ?>
      <p class="error"><?=h($error)?></p>
      <p><a href="/login.php?verify_error=1">Back to Login</a></p>
    <?php endif; ?>
  </div>
</body></html>
