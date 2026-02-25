<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'electionnp');

// Site configuration
define('SITE_URL', 'http://localhost/voting-system/');
define('SITE_NAME', 'VoteNepal');
define('SITE_NAME_NP', 'भोटनेपाल');
define('UPLOAD_PATH', 'C:/wamp64/www/voting-system/assets/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Email Configuration (Gmail SMTP)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'dhirajdhakal460@gmail.com'); // तपाईंको Gmail ठेगाना
define('SMTP_PASS', 'pesh atda fnsl pllj'); // माथि बनाएको 16-digit password
define('SMTP_FROM', 'dhirajdhakal460@gmail.com');
define('SMTP_FROM_NAME', 'VoteNepal System');

// Time zone
date_default_timezone_set('Asia/Kathmandu');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>