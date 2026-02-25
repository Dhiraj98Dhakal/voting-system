<?php
require_once 'config.php';

// PHPMailer autoload - Composer बाट install गरेको ठाउँ
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    
    /**
     * Send Email using PHPMailer with Gmail SMTP
     * 
     * @param string $to Recipient email
     * @param string $to_name Recipient name
     * @param string $subject Email subject
     * @param string $body HTML email body
     * @param string $alt_body Plain text alternative
     * @return array ['success' => bool, 'message' => string]
     */
    public static function send($to, $to_name, $subject, $body, $alt_body = '') {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->SMTPDebug = SMTP::DEBUG_OFF; // Enable DEBUG_SERVER for testing
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            
            // Recipients
            $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
            $mail->addAddress($to, $to_name);
            $mail->addReplyTo(SMTP_FROM, SMTP_FROM_NAME);
            
            // Content
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = $alt_body ?: strip_tags($body);
            
            $mail->send();
            return ['success' => true, 'message' => 'Email sent successfully'];
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
            return ['success' => false, 'message' => "Email could not be sent. Error: {$mail->ErrorInfo}"];
        }
    }
    
    /**
     * Send Voter ID to email after registration
     * 
     * @param string $email Voter's email
     * @param string $name Voter's name
     * @param string $voter_id Generated voter ID
     * @return array
     */
    public static function sendVoterId($email, $name, $voter_id) {
        $subject = "=?UTF-8?B?" . base64_encode("Your Voter ID - VoteNepal | तपाईंको मतदाता ID") . "?=";
        
        $body = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: "Segoe UI", Arial, sans-serif;
                    line-height: 1.6;
                    margin: 0;
                    padding: 0;
                    background-color: #f4f4f4;
                }
                .container {
                    max-width: 600px;
                    margin: 20px auto;
                    background: white;
                    border-radius: 10px;
                    overflow: hidden;
                    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                }
                .header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 30px;
                    text-align: center;
                }
                .header h1 {
                    margin: 0;
                    font-size: 32px;
                }
                .header p {
                    margin: 10px 0 0;
                    opacity: 0.9;
                }
                .content {
                    padding: 40px 30px;
                    background: #f8f9fa;
                }
                .greeting {
                    font-size: 20px;
                    color: #333;
                    margin-bottom: 20px;
                }
                .voter-id-box {
                    background: white;
                    padding: 30px;
                    text-align: center;
                    border-radius: 10px;
                    margin: 30px 0;
                    border: 3px solid #667eea;
                    box-shadow: 0 2px 4px rgba(102, 126, 234, 0.2);
                }
                .voter-id-box p {
                    margin: 0 0 10px;
                    color: #666;
                    font-size: 14px;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }
                .voter-id-box h2 {
                    color: #667eea;
                    font-size: 36px;
                    margin: 10px 0;
                    letter-spacing: 2px;
                    font-family: monospace;
                }
                .info-section {
                    background: white;
                    padding: 20px;
                    border-radius: 8px;
                    margin: 20px 0;
                }
                .info-section h3 {
                    color: #333;
                    margin-top: 0;
                    border-bottom: 2px solid #667eea;
                    padding-bottom: 10px;
                }
                .info-section ul {
                    padding-left: 20px;
                }
                .info-section li {
                    margin-bottom: 10px;
                    color: #555;
                }
                .button {
                    display: inline-block;
                    padding: 12px 30px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;
                    margin-top: 20px;
                    font-weight: bold;
                }
                .footer {
                    background: #333;
                    color: white;
                    padding: 20px;
                    text-align: center;
                    font-size: 12px;
                }
                .footer p {
                    margin: 5px 0;
                }
                .highlight {
                    background: #fff3cd;
                    padding: 15px;
                    border-radius: 5px;
                    border-left: 4px solid #ffc107;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🇳🇵 VoteNepal</h1>
                    <p>निर्वाचन प्रणाली | Election System of Nepal</p>
                </div>
                
                <div class="content">
                    <div class="greeting">
                        नमस्ते ' . htmlspecialchars($name) . ',<br>
                        <small>Hello ' . htmlspecialchars($name) . ',</small>
                    </div>
                    
                    <p>VoteNepal मा दर्ता गर्नुभएकोमा धन्यवाद! तपाईंको मतदाता ID तल दिइएको छ।</p>
                    <p>Thank you for registering with VoteNepal! Your Voter ID is provided below.</p>
                    
                    <div class="voter-id-box">
                        <p>Your Voter ID | तपाईंको मतदाता ID</p>
                        <h2>' . $voter_id . '</h2>
                    </div>
                    
                    <div class="info-section">
                        <h3>📋 Important Information | महत्वपूर्ण जानकारी</h3>
                        <ul>
                            <li>यो ID प्रयोग गरेर मात्र तपाईं मतदान गर्न सक्नुहुन्छ।</li>
                            <li>आफ्नो ID र पासवर्ड सुरक्षित राख्नुहोस्।</li>
                            <li>मतदानको समयमा यो ID आवश्यक पर्नेछ।</li>
                            <li>यदि कुनै समस्या भएमा, admin@votenepal.gov.np मा सम्पर्क गर्नुहोस्।</li>
                        </ul>
                        <ul>
                            <li>You can only vote using this ID.</li>
                            <li>Keep your ID and password secure.</li>
                            <li>This ID will be required during voting.</li>
                            <li>Contact admin@votenepal.gov.np if you face any issues.</li>
                        </ul>
                    </div>
                    
                    <div class="highlight">
                        <strong>🔐 Security Tip:</strong> Never share your Voter ID and password with anyone.
                        <br>कहिल्यै पनि आफ्नो मतदाता ID र पासवर्ड अरूसँग सेयर नगर्नुहोस्।
                    </div>
                    
                    <div style="text-align: center;">
                        <a href="' . SITE_URL . 'voter/login.php" class="button">
                            🔑 Login Now | लगइन गर्नुहोस्
                        </a>
                    </div>
                </div>
                
                <div class="footer">
                    <p>© ' . date('Y') . ' VoteNepal. All rights reserved.</p>
                    <p>यो ईमेल स्वचालित रूपमा पठाइएको हो। कृपया जवाफ नदिनुहोस्।</p>
                </div>
            </div>
        </body>
        </html>';
        
        $alt_body = "Your Voter ID is: $voter_id\n\n"
                  . "Keep this ID secure. You will need it to vote.\n\n"
                  . "Login at: " . SITE_URL . "voter/login.php";
        
        return self::send($email, $name, $subject, $body, $alt_body);
    }
    
    /**
     * Send OTP for password reset
     * 
     * @param string $email User's email
     * @param string $name User's name
     * @param string $otp 6-digit OTP code
     * @return array
     */
    public static function sendOTP($email, $name, $otp) {
        $subject = "=?UTF-8?B?" . base64_encode("Password Reset OTP - VoteNepal | पासवर्ड रिसेट OTP") . "?=";
        
        $body = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: "Segoe UI", Arial, sans-serif;
                    line-height: 1.6;
                    margin: 0;
                    padding: 0;
                    background-color: #f4f4f4;
                }
                .container {
                    max-width: 600px;
                    margin: 20px auto;
                    background: white;
                    border-radius: 10px;
                    overflow: hidden;
                    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                }
                .header {
                    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                    color: white;
                    padding: 30px;
                    text-align: center;
                }
                .header h1 {
                    margin: 0;
                    font-size: 32px;
                }
                .content {
                    padding: 40px 30px;
                    background: #f8f9fa;
                }
                .otp-box {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 30px;
                    text-align: center;
                    border-radius: 10px;
                    margin: 30px 0;
                }
                .otp-code {
                    font-family: monospace;
                    font-size: 48px;
                    font-weight: bold;
                    letter-spacing: 10px;
                    margin: 20px 0;
                }
                .warning {
                    background: #fff3cd;
                    color: #856404;
                    padding: 15px;
                    border-radius: 5px;
                    border-left: 4px solid #ffc107;
                    margin: 20px 0;
                }
                .footer {
                    background: #333;
                    color: white;
                    padding: 20px;
                    text-align: center;
                    font-size: 12px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🔐 Password Reset</h1>
                    <p>पासवर्ड रिसेट अनुरोध</p>
                </div>
                
                <div class="content">
                    <h2>नमस्ते ' . htmlspecialchars($name) . '</h2>
                    <p>तपाईंको खाताको लागि पासवर्ड रिसेट अनुरोध गरिएको छ। तलको OTP कोड प्रयोग गरेर पासवर्ड रिसेट गर्नुहोस्।</p>
                    
                    <p>A password reset was requested for your account. Use the OTP code below to reset your password.</p>
                    
                    <div class="otp-box">
                        <p style="margin:0; font-size: 14px;">Your OTP Code | तपाईंको OTP कोड</p>
                        <div class="otp-code">' . $otp . '</div>
                        <p style="margin:0; font-size: 12px;">Valid for 10 minutes | १० मिनेटको लागि मान्य</p>
                    </div>
                    
                    <div class="warning">
                        <strong>⚠️ Important:</strong> If you didn\'t request this, please ignore this email or contact support immediately.
                        <br>
                        <strong>⚠️ महत्वपूर्ण:</strong> यदि तपाईंले यो अनुरोध गर्नुभएको छैन भने, यो ईमेललाई बेवास्ता गर्नुहोस् वा तुरुन्त सम्पर्क गर्नुहोस्।
                    </div>
                    
                    <p style="text-align: center; margin-top: 30px;">
                        <a href="' . SITE_URL . 'voter/reset_password.php?email=' . urlencode($email) . '" 
                           style="color: #667eea; text-decoration: none; font-weight: bold;">
                            Click here to reset | रिसेट गर्न यहाँ क्लिक गर्नुहोस्
                        </a>
                    </p>
                </div>
                
                <div class="footer">
                    <p>© ' . date('Y') . ' VoteNepal. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>';
        
        $alt_body = "Your OTP code is: $otp\n\n"
                  . "This code is valid for 10 minutes.\n\n"
                  . "If you didn't request this, please ignore this email.";
        
        return self::send($email, $name, $subject, $body, $alt_body);
    }
    
    /**
     * Send vote confirmation email
     * 
     * @param string $email Voter's email
     * @param string $name Voter's name
     * @param string $type Election type (FPTP/PR)
     * @param string $candidate Candidate name
     * @param string $party Party name
     * @return array
     */
    public static function sendVoteConfirmation($email, $name, $type, $candidate, $party) {
        $subject = "=?UTF-8?B?" . base64_encode("Vote Confirmation - VoteNepal | मतदान पुष्टि") . "?=";
        
        $body = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: "Segoe UI", Arial, sans-serif;
                    line-height: 1.6;
                    margin: 0;
                    padding: 0;
                    background-color: #f4f4f4;
                }
                .container {
                    max-width: 600px;
                    margin: 20px auto;
                    background: white;
                    border-radius: 10px;
                    overflow: hidden;
                    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                }
                .header {
                    background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
                    color: white;
                    padding: 30px;
                    text-align: center;
                }
                .header h1 {
                    margin: 0;
                    font-size: 32px;
                }
                .content {
                    padding: 40px 30px;
                    background: #f8f9fa;
                }
                .success-icon {
                    text-align: center;
                    font-size: 60px;
                    margin-bottom: 20px;
                }
                .vote-details {
                    background: white;
                    padding: 25px;
                    border-radius: 10px;
                    margin: 30px 0;
                    border: 2px solid #4caf50;
                }
                .detail-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 10px 0;
                    border-bottom: 1px solid #eee;
                }
                .detail-label {
                    font-weight: bold;
                    color: #666;
                }
                .detail-value {
                    color: #333;
                }
                .footer {
                    background: #333;
                    color: white;
                    padding: 20px;
                    text-align: center;
                    font-size: 12px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🗳️ Vote Confirmation</h1>
                </div>
                
                <div class="content">
                    <div class="success-icon">✅</div>
                    
                    <h2>नमस्ते ' . htmlspecialchars($name) . '</h2>
                    <p>तपाईंको मतदान सफलतापूर्वक दर्ता भएको छ। नेपालको लोकतान्त्रिक अभियानमा सहभागी हुनुभएकोमा धन्यवाद!</p>
                    
                    <p>Your vote has been successfully recorded. Thank you for participating in Nepal\'s democratic process!</p>
                    
                    <div class="vote-details">
                        <h3 style="margin-top: 0;">Vote Details | मतदान विवरण:</h3>
                        <div class="detail-row">
                            <span class="detail-label">Election Type | निर्वाचन प्रकार:</span>
                            <span class="detail-value">' . $type . '</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Candidate | उम्मेदवार:</span>
                            <span class="detail-value">' . htmlspecialchars($candidate) . '</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Party | दल:</span>
                            <span class="detail-value">' . htmlspecialchars($party) . '</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Date & Time | मिति र समय:</span>
                            <span class="detail-value">' . date('Y-m-d H:i:s') . '</span>
                        </div>
                    </div>
                    
                    <p><em>यो ईमेल तपाईंको रेकर्डको लागि मात्र हो। कृपया यसलाई सुरक्षित राख्नुहोस्।</em></p>
                </div>
                
                <div class="footer">
                    <p>© ' . date('Y') . ' VoteNepal. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>';
        
        $alt_body = "Your $type vote for $candidate ($party) has been recorded successfully.\n\n"
                  . "Thank you for voting!";
        
        return self::send($email, $name, $subject, $body, $alt_body);
    }
    
    /**
     * Send password reset confirmation
     * 
     * @param string $email User's email
     * @param string $name User's name
     * @return array
     */
    public static function sendPasswordResetConfirmation($email, $name) {
        $subject = "=?UTF-8?B?" . base64_encode("Password Reset Successful - VoteNepal") . "?=";
        
        $body = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: "Segoe UI", Arial, sans-serif;
                    line-height: 1.6;
                    margin: 0;
                    padding: 0;
                    background-color: #f4f4f4;
                }
                .container {
                    max-width: 600px;
                    margin: 20px auto;
                    background: white;
                    border-radius: 10px;
                    overflow: hidden;
                    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                }
                .header {
                    background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
                    color: white;
                    padding: 30px;
                    text-align: center;
                }
                .header h1 {
                    margin: 0;
                    font-size: 32px;
                }
                .content {
                    padding: 40px 30px;
                    background: #f8f9fa;
                }
                .success-box {
                    text-align: center;
                    padding: 30px;
                }
                .success-icon {
                    font-size: 60px;
                    color: #4caf50;
                    margin-bottom: 20px;
                }
                .button {
                    display: inline-block;
                    padding: 12px 30px;
                    background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;
                    margin-top: 20px;
                }
                .footer {
                    background: #333;
                    color: white;
                    padding: 20px;
                    text-align: center;
                    font-size: 12px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>✅ Password Reset Successful</h1>
                </div>
                
                <div class="content">
                    <div class="success-box">
                        <div class="success-icon">✓</div>
                        
                        <h2>नमस्ते ' . htmlspecialchars($name) . '</h2>
                        <p>तपाईंको पासवर्ड सफलतापूर्वक रिसेट गरिएको छ। अब तपाईं नयाँ पासवर्ड प्रयोग गरेर लगइन गर्न सक्नुहुन्छ।</p>
                        
                        <p>Your password has been successfully reset. You can now login with your new password.</p>
                        
                        <a href="' . SITE_URL . 'voter/login.php" class="button">
                            🔑 Login Now | लगइन गर्नुहोस्
                        </a>
                    </div>
                </div>
                
                <div class="footer">
                    <p>© ' . date('Y') . ' VoteNepal. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>';
        
        $alt_body = "Your password has been successfully reset.\n\n"
                  . "You can now login with your new password at: " . SITE_URL . "voter/login.php";
        
        return self::send($email, $name, $subject, $body, $alt_body);
    }
}
?>