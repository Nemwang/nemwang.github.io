<?php
// Sets a new password from a one-time link sent by forgot.php.
require __DIR__ . '/_init.php';

$token = (string) ($_GET['token'] ?? '');
$pdo = db();

$find = $pdo->prepare('SELECT r.id, r.user_id, u.username FROM password_resets r JOIN users u ON u.id = r.user_id
    WHERE r.token_hash = ? AND r.used = 0 AND r.expires_at > NOW()');
$find->execute([hash('sha256', $token)]);
$reset = $token !== '' ? $find->fetch() : false;

$error = '';
if ($reset && is_post()) {
    $password = (string) ($_POST['password'] ?? '');
    if (strlen($password) < 8) {
        $error = 'The password must be at least 8 characters.';
    } elseif ($password !== (string) ($_POST['password2'] ?? '')) {
        $error = 'The two passwords do not match.';
    } else {
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $reset['user_id']]);
        $pdo->prepare('UPDATE password_resets SET used = 1 WHERE user_id = ?')->execute([$reset['user_id']]);
        $pdo->prepare('DELETE FROM login_attempts WHERE ip = ?')->execute([client_ip()]);
        forget_all_logins((int) $reset['user_id']);
        flash('Your password has been changed. Log in with your new password.');
        redirect('login.php');
    }
}

admin_header('Set a new password', '', ['description' => $reset ? 'For the account "' . $reset['username'] . '".' : '']);
?>
<?php if (!$reset): ?>
  <div class="flash error" role="alert"><?= icon('alert', 18) ?><span>This link has expired or was already used. Ask for a new one.</span></div>
  <p class="auth-foot"><a href="forgot.php">Send a new reset link</a> · <a href="login.php">Log in</a></p>
<?php else: ?>
<?php if ($error): ?>
  <div class="flash error" role="alert"><?= icon('alert', 18) ?><span><?= e($error) ?></span></div>
<?php endif; ?>
  <form method="post" class="auth-form">
    <?= csrf_field() ?>
    <div class="field"><label class="label" for="password">New password</label><input type="password" id="password" name="password" required minlength="8" autofocus autocomplete="new-password"></div>
    <div class="field"><label class="label" for="password2">New password again</label><input type="password" id="password2" name="password2" required minlength="8" autocomplete="new-password"></div>
    <button class="button primary block">Save new password</button>
  </form>
<?php endif; ?>
<?php admin_footer();
