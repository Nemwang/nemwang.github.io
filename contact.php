<?php
// Receives the contact form (js/scripts.js posts here) and saves the message.
require __DIR__ . '/cms/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function respond(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['error' => 'Send the form with POST.']);
}

// Hidden field that only bots fill in: pretend it worked and store nothing.
if (trim((string) ($_POST['website'] ?? '')) !== '') {
    respond(201, ['ok' => true]);
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$subject = trim(preg_replace('/\s+/', ' ', (string) ($_POST['subject'] ?? '')));
$message = trim((string) ($_POST['message'] ?? ''));

if ($name === '' || mb_strlen($name) > 100) {
    respond(400, ['error' => 'Name is required (max 100 characters).']);
}
if ($email !== '' && (mb_strlen($email) > 200 || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
    respond(400, ['error' => 'Email address is not valid.']);
}
if (mb_strlen($subject) > 200) {
    respond(400, ['error' => 'Subject is too long (max 200 characters).']);
}
if ($message === '' || mb_strlen($message) > 5000) {
    respond(400, ['error' => 'Message is required (max 5000 characters).']);
}

try {
    $recent = db()->prepare('SELECT COUNT(*) FROM messages WHERE ip = ? AND created_at > NOW() - INTERVAL 15 MINUTE');
    $recent->execute([client_ip()]);
    if ((int) $recent->fetchColumn() >= 5) {
        respond(429, ['error' => 'Too many messages, please try again later.']);
    }

    db()->prepare('INSERT INTO messages (name, email, subject, message, ip, created_at) VALUES (?, ?, ?, ?, ?, NOW())')
        ->execute([$name, $email ?: null, $subject ?: null, $message, client_ip()]);

    $notify = s('contact_notify_email');
} catch (Throwable $e) {
    error_log('Contact form: ' . $e->getMessage());
    respond(500, ['error' => 'Could not save your message.']);
}

// The message is saved: answer the visitor now, then email the copy, so they never wait
// for the mail server and a failed email cannot fail their message.
if (!filter_var($notify, FILTER_VALIDATE_EMAIL)) {
    respond(201, ['ok' => true]);
}

http_response_code(201);
echo json_encode(['ok' => true]);
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} elseif (function_exists('litespeed_finish_request')) {
    litespeed_finish_request();
} else {
    flush();
}
ignore_user_abort(true);

require __DIR__ . '/cms/mailer.php';
$body = "New message from your portfolio contact form.\n\nName: $name\nEmail: " . ($email ?: '(not given)')
    . "\nSubject: " . ($subject ?: '(none)') . "\n\n$message\n\n— Reply to this email to answer $name directly.\n";
$problem = send_mail($notify, 'New portfolio message' . ($subject !== '' ? ": $subject" : " from $name"), $body, $email);
if ($problem !== '') {
    error_log('Contact form email: ' . $problem);
}
