<?php
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/mailer.php';

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$email = sanitize($_POST['email'] ?? '');

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Email required']);
    exit;
}

// Check if email exists
$query = "SELECT id, name FROM voters WHERE email = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $voter = $result->fetch_assoc();
    
    // Generate new OTP
    $otp = sprintf("%06d", mt_rand(1, 999999));
    $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Save OTP
    $insert = "INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, ?)";
    $stmt = $db->prepare($insert);
    $stmt->bind_param("sss", $email, $otp, $expires);
    
    if ($stmt->execute()) {
        // Send OTP via email
        $mail_result = Mailer::sendOTP($email, $voter['name'], $otp);
        
        if ($mail_result['success']) {
            echo json_encode(['success' => true, 'message' => 'OTP resent successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send email']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error generating OTP']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Email not found']);
}
?>