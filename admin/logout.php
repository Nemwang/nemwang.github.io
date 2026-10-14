<?php
require __DIR__ . '/_init.php';

if (is_post()) {
    forget_login();
    $_SESSION = [];
    session_destroy();
}
redirect('login.php');
