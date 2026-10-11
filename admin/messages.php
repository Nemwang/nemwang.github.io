<?php
// Messages sent through the contact form.
require __DIR__ . '/_init.php';
require_login();

$pdo = db();
$onlyUnread = ($_GET['show'] ?? '') === 'unread';
$self = 'messages.php' . ($onlyUnread ? '?show=unread' : '');

if (is_post()) {
    $id = (int) ($_POST['id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM messages WHERE id = ?')->execute([$id]);
        flash('Message deleted.');
    } elseif ($action === 'read' || $action === 'unread') {
        $pdo->prepare('UPDATE messages SET is_read = ? WHERE id = ?')->execute([$action === 'read' ? 1 : 0, $id]);
    } elseif ($action === 'read_all') {
        $pdo->exec('UPDATE messages SET is_read = 1');
        flash('All messages marked as read.');
    } elseif ($action === 'test_email') {
        $to = s('contact_notify_email');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            flash('Fill in "Email me new messages at" under Home sections → Contact first.', 'error');
        } else {
            require CMS_ROOT . '/mailer.php';
            $problem = send_mail($to, 'Test email from your portfolio', "This is a test from your portfolio admin panel.

If you can read this, contact-form messages will reach you too.
");
            $problem === ''
                ? flash("Test email sent to $to. Check your inbox (and the spam folder).")
                : flash('The test email failed: ' . $problem, 'error');
        }
    }
    redirect($self);
}

$messages = $pdo->query('SELECT * FROM messages' . ($onlyUnread ? ' WHERE is_read = 0' : '') . ' ORDER BY id DESC LIMIT 200')->fetchAll();
$total = (int) $pdo->query('SELECT COUNT(*) FROM messages')->fetchColumn();
$unread = unread_messages();

$notifyTo = s('contact_notify_email');
$smtpHost = cms_config()['smtp']['host'] ?? '';

admin_header('Messages', 'messages', [
    'description' => 'Everything sent through the contact form on your home page.',
    'actions' => '<form method="post">' . csrf_field() . '<button class="button ghost" name="action" value="test_email">' . icon('mail', 16) . '<span>Send test email</span></button></form>'
        . ($unread ? '<form method="post">' . csrf_field() . '<button class="button ghost" name="action" value="read_all">' . icon('check', 16) . '<span>Mark all as read</span></button></form>' : ''),
]);
?>
    <p class="mail-status"><?= icon($notifyTo !== '' ? 'mail' : 'alert', 16) ?>
<?php if ($notifyTo === ''): ?>
      Messages are saved here only. To also get each one by email, fill in <a href="settings.php?g=home#s7">Email me new messages at</a>.
<?php elseif ($smtpHost !== ''): ?>
      A copy of each message is emailed to <strong><?= e($notifyTo) ?></strong> through your mailbox (<?= e($smtpHost) ?>).
<?php else: ?>
      A copy of each message is emailed to <strong><?= e($notifyTo) ?></strong>, but without a mailbox set up it may land in spam. Add the <code>smtp</code> details to <code>cms/config.php</code>.
<?php endif; ?>
    </p>
    <nav class="tabs" aria-label="Filter messages">
      <a href="messages.php"<?= $onlyUnread ? '' : ' class="active"' ?>>All <span><?= $total ?></span></a>
      <a href="messages.php?show=unread"<?= $onlyUnread ? ' class="active"' : '' ?>>Unread <span><?= $unread ?></span></a>
    </nav>
<?php if (!$messages): ?>
    <div class="card empty">
      <?= icon('inbox', 28) ?>
      <p><?= $onlyUnread ? 'You have read everything.' : 'No messages yet. Messages sent from the contact form show up here.' ?></p>
    </div>
<?php endif; ?>
<?php foreach ($messages as $message): ?>
    <article class="card message<?= $message['is_read'] ? '' : ' unread' ?>" id="m<?= (int) $message['id'] ?>">
      <span class="avatar"><?= e(mb_strtoupper(mb_substr($message['name'], 0, 1))) ?></span>
      <div class="message-body">
        <header class="message-head">
          <strong><?= e($message['name']) ?></strong>
<?php if ($message['email']): ?>
          <a href="mailto:<?= e($message['email']) ?>"><?= e($message['email']) ?></a>
<?php endif; ?>
          <time title="<?= e(date('j M Y, H:i', strtotime($message['created_at']))) ?>"><?= e(time_ago($message['created_at'])) ?></time>
<?php if (!$message['is_read']): ?><span class="pill accent">New</span><?php endif; ?>
        </header>
<?php if ($message['subject']): ?>
        <h3><?= e($message['subject']) ?></h3>
<?php endif; ?>
        <p><?= lines($message['message']) ?></p>
        <form method="post" class="message-actions">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $message['id'] ?>">
<?php if ($message['email']): ?>
          <a class="button small" href="mailto:<?= e($message['email']) ?>?subject=<?= rawurlencode('Re: ' . ($message['subject'] ?: 'Your message')) ?>"><?= icon('reply', 15) ?><span>Reply</span></a>
<?php endif; ?>
          <button class="button ghost small" name="action" value="<?= $message['is_read'] ? 'unread' : 'read' ?>"><?= icon($message['is_read'] ? 'mail' : 'check', 15) ?><span>Mark as <?= $message['is_read'] ? 'unread' : 'read' ?></span></button>
          <button class="icon-button danger" name="action" value="delete" title="Delete" aria-label="Delete message" data-confirm="Delete this message? This cannot be undone."><?= icon('trash', 16) ?></button>
        </form>
      </div>
    </article>
<?php endforeach; ?>
<?php admin_footer();
