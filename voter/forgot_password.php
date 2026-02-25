<?php
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/mailer.php';

$db = Database::getInstance()->getConnection();
$error = '';
$success = '';
$step = isset($_GET['step']) ? $_GET['step'] : 'request'; // request, verify, reset

// Step 1: Request OTP
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'request') {
        $email = sanitize($_POST['email']);
        
        // Check if email exists
        $query = "SELECT id, name, email FROM voters WHERE email = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $voter = $result->fetch_assoc();
            
            // Generate 6-digit OTP
            $otp = sprintf("%06d", mt_rand(1, 999999));
            $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            // Save OTP to database
            $insert = "INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, ?)";
            $stmt = $db->prepare($insert);
            $stmt->bind_param("sss", $email, $otp, $expires);
            
            if ($stmt->execute()) {
                // Send OTP via email
                $mail_result = Mailer::sendOTP($email, $voter['name'], $otp);
                
                if ($mail_result['success']) {
                    $success = "OTP has been sent to your email. Please check your inbox.";
                    $step = 'verify';
                    $_SESSION['reset_email'] = $email;
                } else {
                    $error = "Failed to send email. Please try again. " . $mail_result['message'];
                }
            } else {
                $error = "Error generating OTP. Please try again.";
            }
        } else {
            $error = "Email address not found in our system.";
        }
    }
    
    // Step 2: Verify OTP
    elseif ($_POST['action'] == 'verify') {
        $email = $_SESSION['reset_email'] ?? '';
        $otp = sanitize($_POST['otp']);
        
        // Check OTP
        $query = "SELECT * FROM password_resets 
                  WHERE email = ? AND otp = ? AND used = FALSE 
                  AND expires_at > NOW() 
                  ORDER BY id DESC LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bind_param("ss", $email, $otp);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $reset = $result->fetch_assoc();
            
            // Mark OTP as used
            $update = "UPDATE password_resets SET used = TRUE WHERE id = ?";
            $stmt = $db->prepare($update);
            $stmt->bind_param("i", $reset['id']);
            $stmt->execute();
            
            $step = 'reset';
        } else {
            $error = "Invalid or expired OTP. Please try again.";
        }
    }
    
    // Step 3: Reset Password
    elseif ($_POST['action'] == 'reset') {
        $email = $_SESSION['reset_email'] ?? '';
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters.";
        } else {
            // Hash new password
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            
            // Update password
            $update = "UPDATE voters SET password = ? WHERE email = ?";
            $stmt = $db->prepare($update);
            $stmt->bind_param("ss", $hashed, $email);
            
            if ($stmt->execute()) {
                // Get voter name for email
                $query = "SELECT name FROM voters WHERE email = ?";
                $stmt = $db->prepare($query);
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $voter = $stmt->get_result()->fetch_assoc();
                
                // Send confirmation email
                Mailer::sendPasswordResetConfirmation($email, $voter['name']);
                
                // Clear session
                unset($_SESSION['reset_email']);
                
                $success = "Password reset successfully! You can now login with your new password.";
                $step = 'completed';
            } else {
                $error = "Error updating password. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - VoteNepal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --primary-light: #4895ef;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --dark: #1e1b4b;
            --light: #f8f9fa;
            --gray: #6c757d;
            --gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: var(--gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Animated Background */
        .animated-bg {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 1;
        }

        .bg-circle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 20s infinite ease-in-out;
        }

        .circle1 {
            width: 300px;
            height: 300px;
            top: -150px;
            right: -150px;
            animation-delay: 0s;
        }

        .circle2 {
            width: 400px;
            height: 400px;
            bottom: -200px;
            left: -200px;
            animation-delay: 5s;
        }

        .circle3 {
            width: 200px;
            height: 200px;
            bottom: 50px;
            right: 100px;
            animation-delay: 10s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-30px) rotate(10deg);
            }
        }

        .container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 500px;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header i {
            font-size: 50px;
            color: var(--primary);
            background: rgba(67, 97, 238, 0.1);
            width: 80px;
            height: 80px;
            line-height: 80px;
            border-radius: 50%;
            margin-bottom: 20px;
        }

        .header h1 {
            color: var(--dark);
            margin-bottom: 10px;
        }

        .header p {
            color: var(--gray);
        }

        /* Progress Bar */
        .progress-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }

        .progress-bar::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--light);
            z-index: 1;
        }

        .step {
            position: relative;
            z-index: 2;
            background: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: var(--gray);
        }

        .step.active {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
        }

        .step.completed {
            border-color: var(--success);
            background: var(--success);
            color: white;
        }

        .step-label {
            position: absolute;
            top: 45px;
            font-size: 12px;
            color: var(--gray);
            white-space: nowrap;
        }

        /* Alerts */
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Form */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark);
            font-weight: 500;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }

        .input-wrapper input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid var(--light);
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .input-wrapper input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 5px rgba(67, 97, 238, 0.1);
        }

        .otp-input {
            text-align: center;
            font-size: 24px;
            letter-spacing: 10px;
            font-weight: bold;
            font-family: monospace;
        }

        /* Timer */
        .timer {
            text-align: center;
            margin: 15px 0;
            color: var(--warning);
            font-weight: 600;
            font-size: 18px;
        }

        .resend {
            text-align: center;
            margin-top: 15px;
        }

        .resend button {
            background: none;
            border: none;
            color: var(--primary);
            cursor: pointer;
            text-decoration: underline;
            font-size: 14px;
        }

        .resend button:disabled {
            color: var(--gray);
            cursor: not-allowed;
            text-decoration: none;
        }

        /* Buttons */
        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-primary {
            background: var(--gradient);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        /* Footer Links */
        .footer-links {
            text-align: center;
            margin-top: 20px;
        }

        .footer-links a {
            color: var(--gray);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: var(--primary);
        }

        .footer-links i {
            margin-right: 5px;
        }

        /* Password Requirements */
        .requirements {
            background: var(--light);
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            font-size: 13px;
        }

        .requirements p {
            margin-bottom: 10px;
            color: var(--dark);
            font-weight: 600;
        }

        .requirements ul {
            list-style: none;
        }

        .requirements li {
            margin-bottom: 5px;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .requirements li.valid i {
            color: var(--success);
        }

        .requirements li.invalid i {
            color: var(--danger);
        }

        /* Responsive */
        @media (max-width: 480px) {
            .card {
                padding: 30px 20px;
            }
            
            .step-label {
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-bg">
        <div class="bg-circle circle1"></div>
        <div class="bg-circle circle2"></div>
        <div class="bg-circle circle3"></div>
    </div>

    <div class="container">
        <div class="card">
            <div class="header">
                <i class="fas fa-key"></i>
                <h1>Forgot Password?</h1>
                <p>पासवर्ड बिर्सनुभयो?</p>
            </div>

            <!-- Progress Bar -->
            <div class="progress-bar">
                <div class="step <?php echo $step == 'request' ? 'active' : ($step != 'request' ? 'completed' : ''); ?>">
                    1
                    <span class="step-label">Email</span>
                </div>
                <div class="step <?php echo $step == 'verify' ? 'active' : ($step == 'reset' || $step == 'completed' ? 'completed' : ''); ?>">
                    2
                    <span class="step-label">OTP</span>
                </div>
                <div class="step <?php echo $step == 'reset' ? 'active' : ($step == 'completed' ? 'completed' : ''); ?>">
                    3
                    <span class="step-label">Reset</span>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $success; ?></span>
                </div>
            <?php endif; ?>

            <!-- Step 1: Request OTP -->
            <?php if ($step == 'request'): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="request">
                    
                    <div class="form-group">
                        <label>Enter your registered email / दर्ता गरेको ईमेल</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" placeholder="your@email.com" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        Send OTP / OTP पठाउनुहोस्
                    </button>
                </form>
            <?php endif; ?>

            <!-- Step 2: Verify OTP -->
            <?php if ($step == 'verify'): ?>
                <form method="POST" id="otpForm">
                    <input type="hidden" name="action" value="verify">
                    
                    <div class="form-group">
                        <label>Enter OTP sent to your email / ईमेलमा आएको OTP कोड</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="text" name="otp" class="otp-input" maxlength="6" 
                                   pattern="[0-9]{6}" placeholder="______" required>
                        </div>
                    </div>

                    <div class="timer" id="timer">10:00 remaining</div>

                    <div class="resend">
                        <button type="button" id="resendBtn" onclick="resendOTP()" disabled>
                            <i class="fas fa-redo-alt"></i> Resend OTP / OTP पुनः पठाउनुहोस्
                        </button>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i>
                        Verify OTP / OTP प्रमाणित गर्नुहोस्
                    </button>
                </form>
            <?php endif; ?>

            <!-- Step 3: Reset Password -->
            <?php if ($step == 'reset'): ?>
                <form method="POST" id="resetForm">
                    <input type="hidden" name="action" value="reset">
                    
                    <div class="form-group">
                        <label>New Password / नयाँ पासवर्ड</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" id="password" 
                                   placeholder="Enter new password" minlength="8" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Confirm Password / पासवर्ड पुष्टि</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="confirm_password" id="confirm_password" 
                                   placeholder="Re-enter password" required>
                        </div>
                    </div>

                    <!-- Password Requirements -->
                    <div class="requirements">
                        <p>Password Requirements / पासवर्ड आवश्यकताहरू:</p>
                        <ul>
                            <li id="reqLength" class="invalid">
                                <i class="fas fa-times-circle"></i> At least 8 characters / कम्तीमा ८ क्यारेक्टर
                            </li>
                            <li id="reqMatch" class="invalid">
                                <i class="fas fa-times-circle"></i> Passwords match / पासवर्ड मिलेको
                            </li>
                        </ul>
                    </div>

                    <button type="submit" class="btn btn-primary" id="resetBtn" disabled>
                        <i class="fas fa-sync-alt"></i> Reset Password / पासवर्ड रिसेट गर्नुहोस्
                    </button>
                </form>
            <?php endif; ?>

            <!-- Step 4: Completed -->
            <?php if ($step == 'completed'): ?>
                <div style="text-align: center;">
                    <i class="fas fa-check-circle" style="font-size: 60px; color: var(--success); margin-bottom: 20px;"></i>
                    <p style="margin-bottom: 30px; font-size: 16px;">Your password has been reset successfully! / तपाईंको पासवर्ड सफलतापूर्वक रिसेट भयो!</p>
                    <a href="login.php" class="btn btn-primary" style="display: inline-block; width: auto; padding: 12px 40px;">
                        <i class="fas fa-sign-in-alt"></i> Login Now / लगइन गर्नुहोस्
                    </a>
                </div>
            <?php endif; ?>

            <div class="footer-links">
                <a href="login.php">
                    <i class="fas fa-arrow-left"></i> Back to Login / लगइनमा फर्कनुहोस्
                </a>
            </div>
        </div>
    </div>

    <script>
        // Timer for OTP
        <?php if ($step == 'verify'): ?>
        let timeLeft = 600; // 10 minutes in seconds
        const timerDisplay = document.getElementById('timer');
        const resendBtn = document.getElementById('resendBtn');
        
        const timer = setInterval(() => {
            timeLeft--;
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')} remaining`;
            
            if (timeLeft <= 0) {
                clearInterval(timer);
                timerDisplay.textContent = 'OTP expired / OTP को म्याद सकियो';
                resendBtn.disabled = false;
            }
        }, 1000);
        <?php endif; ?>

        // Resend OTP function
        function resendOTP() {
            const email = '<?php echo $_SESSION['reset_email'] ?? ''; ?>';
            
            fetch('resend_otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'email=' + encodeURIComponent(email)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('OTP resent successfully! / OTP पुनः पठाइयो!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        // Password validation for reset step
        <?php if ($step == 'reset'): ?>
        const password = document.getElementById('password');
        const confirm = document.getElementById('confirm_password');
        const reqLength = document.getElementById('reqLength');
        const reqMatch = document.getElementById('reqMatch');
        const resetBtn = document.getElementById('resetBtn');

        function validatePassword() {
            const passVal = password.value;
            const confirmVal = confirm.value;
            
            // Check length
            const hasLength = passVal.length >= 8;
            reqLength.className = hasLength ? 'valid' : 'invalid';
            reqLength.innerHTML = `<i class="fas ${hasLength ? 'fa-check-circle' : 'fa-times-circle'}"></i> At least 8 characters / कम्तीमा ८ क्यारेक्टर`;
            
            // Check match
            const hasMatch = passVal !== '' && passVal === confirmVal;
            reqMatch.className = hasMatch ? 'valid' : 'invalid';
            reqMatch.innerHTML = `<i class="fas ${hasMatch ? 'fa-check-circle' : 'fa-times-circle'}"></i> Passwords match / पासवर्ड मिलेको`;
            
            // Enable/disable button
            resetBtn.disabled = !(hasLength && hasMatch);
        }

        password.addEventListener('input', validatePassword);
        confirm.addEventListener('input', validatePassword);
        <?php endif; ?>

        // Auto-submit OTP when 6 digits entered
        document.querySelector('.otp-input')?.addEventListener('input', function() {
            if (this.value.length === 6) {
                document.getElementById('otpForm').submit();
            }
        });

        // Prevent form resubmission
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>