<?php
// One-time setup: creates the tables, loads the starting content and the admin account.
require __DIR__ . '/_init.php';

$problem = '';
if (!cms_config()) {
    $problem = 'Copy cms/config.example.php to cms/config.php and fill in your database details, then reload this page.';
} else {
    try {
        cms_create_tables();
    } catch (Throwable $e) {
        $problem = 'Could not connect to the database. Check the details in cms/config.php. (' . $e->getMessage() . ')';
    }
}

if (!$problem && cms_installed()) {
    redirect('login.php');
}

$error = '';
$username = trim((string) ($_POST['username'] ?? ''));
if (!$problem && is_post()) {
    $password = (string) ($_POST['password'] ?? '');
    if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
        $error = 'Username: 3-50 letters, numbers, dots, dashes or underscores.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== (string) ($_POST['password2'] ?? '')) {
        $error = 'The two passwords do not match.';
    } else {
        $pdo = db();
        if ((int) $pdo->query('SELECT COUNT(*) FROM items')->fetchColumn() === 0) {
            $insert = $pdo->prepare('INSERT INTO items (collection, data, sort_order) VALUES (?, ?, ?)');
            foreach (require CMS_ROOT . '/seed.php' as $collection => $rows) {
                foreach ($rows as $order => $row) {
                    $insert->execute([$collection, json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $order]);
                }
            }
        }
        $pdo->prepare('INSERT INTO users (username, password_hash, created_at) VALUES (?, ?, NOW())')
            ->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
        flash('Setup finished. Log in with the account you just created.');
        redirect('login.php');
    }
}

admin_header('Set up your site', '', ['description' => $problem ? '' : 'The database is connected. Create the account you will use to log in.']);
?>
<?php if ($problem): ?>
  <div class="flash error" role="alert"><?= icon('alert', 18) ?><span><?= e($problem) ?></span></div>
<?php else: ?>
  <?php if ($error): ?><div class="flash error" role="alert"><?= icon('alert', 18) ?><span><?= e($error) ?></span></div><?php endif; ?>
  <form method="post" class="auth-form">
    <?= csrf_field() ?>
    <div class="field"><label class="label" for="username">Username</label><input type="text" id="username" name="username" value="<?= e($username) ?>" required autofocus></div>
    <div class="field"><label class="label" for="password">Password</label><input type="password" id="password" name="password" required minlength="8" autocomplete="new-password"></div>
    <div class="field"><label class="label" for="password2">Password again</label><input type="password" id="password2" name="password2" required minlength="8" autocomplete="new-password"></div>
    <button class="button primary block">Create account</button>
  </form>
<?php endif; ?>
<?php admin_footer();
