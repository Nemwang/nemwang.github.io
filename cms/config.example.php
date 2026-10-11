<?php
// Copy this file to config.php and fill in your database details.
// On Hostinger: hPanel -> Databases -> MySQL Databases shows all four values.
// config.php is git-ignored so the password never reaches GitHub.

return [
    'db_host' => 'localhost',
    'db_port' => 3306,
    'db_name' => 'u123456789_portfolio',
    'db_user' => 'u123456789_admin',
    'db_pass' => 'your-database-password',

    // Your site address, used in Google and link previews. Optional; worked out automatically if left out.
    // 'site_url' => 'https://yourdomain.com',

    // Send contact-form emails through your Hostinger mailbox so they reach the inbox, not spam.
    // Create the mailbox in hPanel -> Emails, then fill this in and remove the // at the start of each line.
    // 'smtp' => [
    //     'host' => 'smtp.hostinger.com',
    //     'port' => 465,
    //     'username' => 'hello@yourdomain.com',
    //     'password' => 'the-mailbox-password',
    //     'from_name' => 'Portfolio',
    // ],
];
