<?php
// "Forgot password": emails a one-time link (valid 1 hour) that also tells you your username.
require __DIR__ . '/_init.php';
require_once CMS_ROOT . '/mailer.php';

if (!cms_installed()) {
    redirect('install.php');
}

$sent = false;
$localLink = '';
$error = '';
$who = trim((string) ($_POST['who'] ?? ''));

if (is_post()) {
    $pdo = db();
    $ip = client_ip();

    // At most 3 requests per 15 minutes from one address.
    $recent = $pdo->prepare('SELECT COUNT(*) FROM password_resets WHERE ip = ? AND created_at > NOW() - INTERVAL 15 MINUTE');
    $recent->execute([$ip]);
    if ((int) $recent->fetchColumn() >= 3) {
        $error = 'Too many requests. Try again in 15 minutes.';
    } else {
        // Match the username, or the email saved on the account; with one account, the site's own emails also count.
        $find = $pdo->prepare('SELECT id, username, email FROM users WHERE username = ? OR (email <> \'\' AND email = ?)');
        $find->execute([$who, $who]);
        $user = $find->fetch();
        if (!$user && filter_var($who, FILTER_VALIDATE_EMAIL) && (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 1
            && in_array(strtolower($who), array_map('strtolower', [s('contact_notify_email'), s('contact_email')]), true)) {
            $user = $pdo->query('SELECT id, username, email FROM users LIMIT 1')->fetch();
        }

        $to = $user ? ($user['email'] ?: s('contact_notify_email') ?: s('contact_email')) : '';
        if ($user && filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $token = bin2hex(random_bytes(32));
            $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, ip, created_at, expires_at) VALUES (?, ?, ?, NOW(), NOW() + INTERVAL 1 HOUR)')
                ->execute([$user['id'], hash('sha256', $token), $ip]);
            $link = admin_url('reset.php?token=' . $token, true);

            $problem = send_mail($to, 'Reset your portfolio admin password',
                "Hi {$user['username']},\n\nSomeone asked to reset the password for your portfolio admin panel.\n\n"
                . "Your username is: {$user['username']}\n\nSet a new password here (the link works once, for 1 hour):\n$link\n\n"
                . "If this wasn't you, ignore this email; your password stays the same.\n");

            // On your own computer email usually can't be sent, so the link is shown here instead.
            if ($problem !== '' && is_local_request()) {
                $localLink = $link;
            }
        } else {
            // Record the attempt too, so guessing names is rate-limited the same way.
            $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, ip, created_at, expires_at, used) VALUES (0, ?, ?, NOW(), NOW(), 1)')
                ->execute([hash('sha256', random_bytes(16)), $ip]);
        }
        // Same answer whether or not the account exists, so this page can't be used to find usernames.
        $sent = true;
    }
}

admin_header('Forgot password', '', ['description' => 'Enter your username or email address. We will email you a link to set a new password, along with your username.']);
?>
<?php if ($error): ?>
  <div class="flash error" role="alert"><?= icon('alert', 18) ?><span><?= e($error) ?></span></div>
<?php endif; ?>
<?php if ($sent): ?>
  <div class="flash ok" role="status"><?= icon('check', 18) ?><span>If that matches your account, a reset link is on its way. Check your inbox and spam folder.</span></div>
<?php if ($localLink): ?>
  <p class="reset-link"><strong>On your own computer email can't be sent, so here is the link:</strong><br><a href="<?= e($localLink) ?>"><?= e($localLink) ?></a></p>
<?php endif; ?>
  <p class="auth-foot"><a href="login.php">Back to log in</a></p>
<?php else: ?>
  <form method="post" class="auth-form">
    <?= csrf_field() ?>
    <div class="field"><label class="label" for="who">Username or email</label><input type="text" id="who" name="who" value="<?= e($who) ?>" required autofocus autocomplete="username"></div>
    <button class="button primary block">Send reset link</button>
    <p class="auth-foot"><a href="login.php">Back to log in</a></p>
  </form>
<?php endif; ?>
<?php admin_footer();
