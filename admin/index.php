<?php
require __DIR__ . '/_init.php';
require_login();

$counts = [];
foreach (db()->query('SELECT collection, COUNT(*) AS total FROM items GROUP BY collection') as $row) {
    $counts[$row['collection']] = (int) $row['total'];
}
$latest = db()->query('SELECT id, name, subject, message, is_read, created_at FROM messages ORDER BY id DESC LIMIT 5')->fetchAll();
$totalMessages = (int) db()->query('SELECT COUNT(*) FROM messages')->fetchColumn();
$unread = unread_messages();

$pages = [
    'home' => ['Home page', 'home', '../', 'settings.php?g=home'],
    'ux' => ['UI/UX page', 'image', '../ux.php', 'settings.php?g=ux'],
    'projects' => ['Projects page', 'folder', '../projects.php', 'settings.php?g=projects'],
];
$online = count(array_filter(array_keys($pages), fn($key) => on($key . '_enabled')));

admin_header('Welcome back, ' . current_username(), 'dashboard', [
    'description' => 'Everything on your portfolio, in one place.',
]);
?>
    <div class="stats">
      <a class="stat" href="messages.php?show=unread">
        <span class="stat-icon"><?= icon('inbox', 20) ?></span>
        <span><strong><?= $unread ?></strong>Unread message<?= $unread === 1 ? '' : 's' ?></span>
      </a>
      <div class="stat">
        <span class="stat-icon"><?= icon('eye', 20) ?></span>
        <span><strong><?= $online ?> / <?= count($pages) ?></strong>Pages online</span>
      </div>
      <a class="stat" href="items.php?c=gallery">
        <span class="stat-icon"><?= icon('image', 20) ?></span>
        <span><strong><?= $counts['gallery'] ?? 0 ?></strong>UI/UX designs</span>
      </a>
      <a class="stat" href="items.php?c=projects">
        <span class="stat-icon"><?= icon('folder', 20) ?></span>
        <span><strong><?= $counts['projects'] ?? 0 ?></strong>Projects</span>
      </a>
    </div>

<?php $customResumes = array_slice(array_reverse(items('resumes', false)), 0, 3); ?>
    <section class="card resume-card">
      <div class="resume-card-main">
        <span class="stat-icon"><?= icon('file-text', 22) ?></span>
        <div>
          <h2>Resumes</h2>
          <p class="muted">Pick a target job, paste the posting, and get an ATS-friendly PDF made from your portfolio content.</p>
        </div>
        <div class="page-actions">
          <a class="button ghost" href="../resume.php" target="_blank" rel="noopener"><?= icon('external', 16) ?><span>Main resume</span></a>
          <a class="button primary" href="items.php?c=resumes&amp;new=1"><?= icon('plus', 16) ?><span>Create a resume for a job</span></a>
        </div>
      </div>
<?php if ($customResumes): ?>
      <div class="resume-card-list">
<?php foreach ($customResumes as $custom): ?>
        <a href="items.php?c=resumes&amp;id=<?= $custom['id'] ?>"><?= icon('file-text', 15) ?><?= e($custom['label'] ?: 'Untitled resume') ?></a>
<?php endforeach; ?>
        <a class="muted" href="items.php?c=resumes">All custom resumes →</a>
      </div>
<?php endif; ?>
    </section>

    <div class="dash-grid">
      <section class="card">
        <header class="card-head"><h2>Pages</h2></header>
        <div class="page-list">
<?php foreach ($pages as $key => [$label, $iconName, $url, $edit]): $isOn = on($key . '_enabled'); ?>
          <div class="page-row">
            <span class="page-icon"><?= icon($iconName, 18) ?></span>
            <span class="page-name"><?= e($label) ?><span class="status <?= $isOn ? 'on' : 'off' ?>"><?= $isOn ? 'Online' : 'Offline' ?></span></span>
            <a class="button ghost small" href="<?= $edit ?>">Edit</a>
            <a class="icon-button" href="<?= $url ?>" target="_blank" rel="noopener" title="View page" aria-label="View <?= e($label) ?>"><?= icon('external', 16) ?></a>
          </div>
<?php endforeach; ?>
        </div>
      </section>

      <section class="card">
        <header class="card-head">
          <h2>Latest messages</h2>
<?php if ($totalMessages): ?>
          <a class="card-link" href="messages.php">View all <?= $totalMessages ?></a>
<?php endif; ?>
        </header>
<?php if (!$latest): ?>
        <div class="empty small"><?= icon('inbox', 24) ?><p>No messages yet. Messages sent from the contact form show up here.</p></div>
<?php else: ?>
        <div class="mini-messages">
<?php foreach ($latest as $message): ?>
          <a class="mini-message<?= $message['is_read'] ? '' : ' unread' ?>" href="messages.php#m<?= (int) $message['id'] ?>">
            <span class="avatar small"><?= e(mb_strtoupper(mb_substr($message['name'], 0, 1))) ?></span>
            <span class="mini-body">
              <span class="mini-top"><strong><?= e($message['name']) ?></strong><time><?= e(time_ago($message['created_at'])) ?></time></span>
              <span class="mini-text"><?= e(mb_strimwidth(($message['subject'] ? $message['subject'] . ' — ' : '') . $message['message'], 0, 80, '…')) ?></span>
            </span>
          </a>
<?php endforeach; ?>
        </div>
<?php endif; ?>
      </section>
    </div>

    <h2 class="section-title">Content</h2>
    <div class="content-grid">
<?php foreach (schema()['collections'] as $key => $collection): ?>
      <a class="content-tile" href="items.php?c=<?= $key ?>">
        <span class="stat-icon"><?= icon($collection['icon'] ?? 'list', 18) ?></span>
        <span class="content-name"><?= e($collection['label']) ?></span>
        <span class="content-count"><?= $counts[$key] ?? 0 ?></span>
      </a>
<?php endforeach; ?>
    </div>
<?php admin_footer();
