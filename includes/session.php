<?php
// Set session configurations BEFORE starting session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);
ini_set('session.gc_maxlifetime', 1800);
ini_set('session.cookie_lifetime', 0);

// Now start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';
require_once 'functions.php';
// auth.php यहाँ include नगर्नुहोस्
?>