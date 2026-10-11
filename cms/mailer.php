<?php
// Sends email. With an 'smtp' block in cms/config.php it signs in to your mailbox
// (e.g. Hostinger: smtp.hostinger.com, port 465) so messages are trusted and reach
// the inbox; without one it falls back to PHP's mail(), which often lands in spam.

// Returns '' when sent, otherwise a readable reason.
function send_mail(string $to, string $subject, string $body, string $replyTo = ''): string
{
    $clean = fn(string $value) => trim(str_replace(["\r", "\n"], ' ', $value));
    $to = $clean($to);
    $subject = $clean($subject);
    $replyTo = filter_var($clean($replyTo), FILTER_VALIDATE_EMAIL) ? $clean($replyTo) : '';
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return 'The address to send to is not a valid email address.';
    }

    $smtp = cms_config()['smtp'] ?? null;
    if (!$smtp || empty($smtp['host'])) {
        $host = preg_replace('/[^a-z0-9.\-]/i', '', $_SERVER['SERVER_NAME'] ?? 'localhost');
        $headers = "From: Portfolio <noreply@$host>\r\nContent-Type: text/plain; charset=utf-8" . ($replyTo ? "\r\nReply-To: $replyTo" : '');
        return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers) ? '' : 'The server could not send the email.';
    }

    try {
        smtp_send($smtp, $to, $subject, $body, $replyTo);
        return '';
    } catch (RuntimeException $e) {
        return $e->getMessage();
    }
}

function smtp_send(array $smtp, string $to, string $subject, string $body, string $replyTo): void
{
    $host = $smtp['host'];
    $port = (int) ($smtp['port'] ?? 465);
    $user = (string) ($smtp['username'] ?? '');
    $from = (string) ($smtp['from'] ?? $user);
    $fromName = str_replace(['"', "\r", "\n"], '', (string) ($smtp['from_name'] ?? 'Portfolio'));
    $secure = $smtp['secure'] ?? ($port === 465 ? 'ssl' : 'tls');

    $socket = @stream_socket_client(($secure === 'ssl' ? 'ssl://' : 'tcp://') . "$host:$port", $errno, $error, 15);
    if (!$socket) {
        throw new RuntimeException("Could not connect to the mail server $host:$port ($error).");
    }
    stream_set_timeout($socket, 15);

    // Reads one reply (possibly several lines) and checks its status code.
    $expect = function (int ...$codes) use ($socket): string {
        $reply = '';
        while (($line = fgets($socket, 1024)) !== false) {
            $reply .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        if (!in_array((int) substr($reply, 0, 3), $codes, true)) {
            throw new RuntimeException('The mail server refused: ' . trim($reply ?: 'no answer'));
        }
        return $reply;
    };
    $send = function (string $command, int ...$codes) use ($socket, $expect): string {
        fwrite($socket, $command . "\r\n");
        return $expect(...$codes);
    };

    try {
        $expect(220);
        $me = preg_replace('/[^a-z0-9.\-]/i', '', $_SERVER['SERVER_NAME'] ?? 'localhost') ?: 'localhost';
        $features = $send("EHLO $me", 250);
        if ($secure === 'tls') {
            $send('STARTTLS', 220);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Could not start a secure connection with the mail server.');
            }
            $features = $send("EHLO $me", 250);
        }
        if ($user !== '') {
            if (stripos($features, 'AUTH') === false) {
                throw new RuntimeException('The mail server does not accept a login on this port.');
            }
            $send('AUTH LOGIN', 334);
            $send(base64_encode($user), 334);
            $send(base64_encode((string) ($smtp['password'] ?? '')), 235);
        }
        $send("MAIL FROM:<$from>", 250);
        $send("RCPT TO:<$to>", 250, 251);
        $send('DATA', 354);

        $headers = [
            'Date: ' . date('r'),
            'From: "' . $fromName . '" <' . $from . '>',
            'To: <' . $to . '>',
            'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . (explode('@', $from)[1] ?? $me) . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=utf-8',
            'Content-Transfer-Encoding: base64',
        ];
        if ($replyTo !== '') {
            $headers[] = 'Reply-To: <' . $replyTo . '>';
        }
        // Base64 lines never start with ".", so the end-of-message marker cannot appear in the body.
        fwrite($socket, implode("\r\n", $headers) . "\r\n\r\n" . rtrim(chunk_split(base64_encode($body), 76, "\r\n")) . "\r\n.\r\n");
        $expect(250);
        // The message is accepted at this point; how the server says goodbye does not matter.
        fwrite($socket, "QUIT\r\n");
    } finally {
        fclose($socket);
    }
}
