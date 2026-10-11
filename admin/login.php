<?php
require __DIR__ . '/_init.php';

if (!cms_installed()) {
    redirect('install.php');
}
if (!empty($_SESSION['user_id'])) {
    redirect('index.php');
}

$error = '';
$username = trim((string) ($_POST['username'] ?? ''));
if (is_post()) {
    $pdo = db();
    $ip = client_ip();

    // Five wrong passwords from one address locks it out for 15 minutes.
    $pdo->exec('DELETE FROM login_attempts WHERE attempted_at < NOW() - INTERVAL 15 MINUTE');
    $attempts = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = ?');
    $attempts->execute([$ip]);

    if ((int) $attempts->fetchColumn() >= 5) {
        $error = 'Too many failed attempts. Try again in 15 minutes.';
    } else {
        $find = $pdo->prepare('SELECT id, password_hash FROM users WHERE username = ?');
        $find->execute([$username]);
        $user = $find->fetch();

        if ($user && password_verify((string) ($_POST['password'] ?? ''), $user['password_hash'])) {
            $pdo->prepare('DELETE FROM login_attempts WHERE ip = ?')->execute([$ip]);
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            redirect('index.php');
        }
        $pdo->prepare('INSERT INTO login_attempts (ip, attempted_at) VALUES (?, NOW())')->execute([$ip]);
        $error = 'Wrong username or password.';
    }
}

admin_header('Log in', '', ['description' => 'Sign in to manage ' . s('site_name') . '.']);
?>
  <?php if ($error): ?><div class="flash error" role="alert"><?= icon('alert', 18) ?><span><?= e($error) ?></span></div><?php endif; ?>
  <form method="post" class="auth-form">
    <?= csrf_field() ?>
    <div class="field"><label class="label" for="username">Username</label><input type="text" id="username" name="username" value="<?= e($username) ?>" required autofocus autocomplete="username"></div>
    <div class="field"><label class="label" for="password">Password</label><input type="password" id="password" name="password" required autocomplete="current-password"></div>
    <button class="button primary block">Log in</button>
  </form>
<?php admin_footer();
