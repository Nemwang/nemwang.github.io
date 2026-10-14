<?php
// PDF resumes made from the website content (see cms/resume.php).
//   resume.php              the main resume (what the Download Resume buttons give)
//   resume.php?v=12         custom resume 12: only for you while logged in, unless it is set to public
//   &download=1             save the file instead of opening it
//   &preview=html           the page the PDF is made from
require __DIR__ . '/cms/bootstrap.php';

try {
    all_settings();
} catch (Throwable $e) {
    site_unavailable(503, 'Site is being set up', 'Finish the setup at <a href="admin/install.php">admin/install.php</a>.');
}

$version = null;
if (isset($_GET['v'])) {
    $version = resume_version((int) $_GET['v']);
    $loggedIn = false;
    if ($version && $version['public'] !== '1') {
        ini_set('session.use_strict_mode', '1');
        session_name('portfolio_admin');
        session_start(['read_and_close' => true]);
        $loggedIn = !empty($_SESSION['user_id']);
    }
    if (!$version || ($version['public'] !== '1' && !$loggedIn)) {
        site_unavailable(404, 'Resume not found', '<a href="resume.php">See the resume</a>');
    }
    header('X-Robots-Tag: noindex');
}

if (($_GET['preview'] ?? '') === 'html') {
    echo resume_html($version);
    exit;
}

try {
    $file = resume_pdf_file($version);
} catch (Throwable $e) {
    error_log('Resume PDF: ' . $e->getMessage());
    site_unavailable(500, 'The resume could not be made', 'Please try again later.');
}

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($file));
header('Content-Disposition: ' . (isset($_GET['download']) ? 'attachment' : 'inline') . '; filename="' . resume_config($version)['filename'] . '"');
header('Cache-Control: no-cache');
readfile($file);
