<?php
// Change the admin username and password.
require __DIR__ . '/_init.php';
require_login();

$pdo = db();
$find = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE id = ?');
$find->execute([$_SESSION['user_id']]);
$user = $find->fetch();
$username = $user['username'];

if (is_post()) {
    $username = trim((string) ($_POST['username'] ?? ''));
    $new = (string) ($_POST['new_password'] ?? '');

    if (!password_verify((string) ($_POST['current_password'] ?? ''), $user['password_hash'])) {
        flash('Your current password is not correct.', 'error');
    } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
        flash('Username: 3-50 letters, numbers, dots, dashes or underscores.', 'error');
    } elseif ($new !== '' && strlen($new) < 8) {
        flash('The new password must be at least 8 characters.', 'error');
    } elseif ($new !== (string) ($_POST['new_password2'] ?? '')) {
        flash('The two new passwords do not match.', 'error');
    } else {
        $hash = $new !== '' ? password_hash($new, PASSWORD_DEFAULT) : $user['password_hash'];
        $pdo->prepare('UPDATE users SET username = ?, password_hash = ? WHERE id = ?')->execute([$username, $hash, $user['id']]);
        session_regenerate_id(true);
        flash('Account updated.');
        redirect('account.php');
    }
}

admin_header('Account', 'account', ['description' => 'Change the username and password you log in with.']);
?>
  <form method="post" class="edit-form narrow">
    <?= csrf_field() ?>
    <section class="card">
      <header class="card-head"><h2>Login details</h2></header>
      <div class="card-body">
            <div class="field"><label class="label" for="username">Username</label><input type="text" id="username" name="username" value="<?= e($username) ?>" required autocomplete="username"></div>
            <div class="field"><label class="label" for="new_password">New password</label><input type="password" id="new_password" name="new_password" minlength="8" autocomplete="new-password"><p class="help">Leave empty to keep your current password.</p></div>
            <div class="field"><label class="label" for="new_password2">New password again</label><input type="password" id="new_password2" name="new_password2" minlength="8" autocomplete="new-password"></div>
            <div class="field"><label class="label" for="current_password">Current password (to confirm)</label><input type="password" id="current_password" name="current_password" required autocomplete="current-password"></div>
      </div>
    </section>
    <div class="form-actions"><button class="button primary"><?= icon('check', 16) ?><span>Save changes</span></button></div>
  </form>
<?php admin_footer();
