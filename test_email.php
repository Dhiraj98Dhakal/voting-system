<?php
require_once 'includes/config.php';
require_once 'includes/mailer.php';

echo "<h2>📧 Email Test</h2>";

$test_email = 'dhirajdhakal460@gmail.com'; // आफैलाई पठाउने
$test_name = 'Test User';
$voter_id = 'TEST123456';

echo "<p>Sending test email to: <strong>$test_email</strong></p>";

$result = Mailer::sendVoterId($test_email, $test_name, $voter_id);

if ($result['success']) {
    echo "<p style='color: green; font-weight: bold;'>✅ Email sent successfully!</p>";
    echo "<p>Check your Gmail inbox (also check SPAM folder).</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ Email failed: " . $result['message'] . "</p>";
}
?>