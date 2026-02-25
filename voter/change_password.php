<?php
require_once '../includes/auth.php';
Auth::requireVoter();

$db = Database::getInstance()->getConnection();
$voter_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    // Verify current password
    $query = "SELECT password FROM voters WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $voter_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $voter = $result->fetch_assoc();
    
    if (!password_verify($current, $voter['password'])) {
        $error = 'Current password is incorrect / हालको पासवर्ड गलत छ';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match / नयाँ पासवर्ड मिलेन';
    } elseif (strlen($new) < 8) {
        $error = 'Password must be at least 8 characters / पासवर्ड कम्तीमा ८ क्यारेक्टर हुनुपर्छ';
    } elseif ($new === $current) {
        $error = 'New password must be different from current password / नयाँ पासवर्ड पुरानो भन्दा फरक हुनुपर्छ';
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $update = "UPDATE voters SET password = ? WHERE id = ?";
        $stmt = $db->prepare($update);
        $stmt->bind_param("si", $hashed, $voter_id);
        
        if ($stmt->execute()) {
            $success = 'Password changed successfully! / पासवर्ड सफलतापूर्वक परिवर्तन भयो!';
            
            // Optional: Send email notification
            // require_once '../includes/mailer.php';
            // $email_query = "SELECT email, name FROM voters WHERE id = ?";
            // $stmt = $db->prepare($email_query);
            // $stmt->bind_param("i", $voter_id);
            // $stmt->execute();
            // $voter_info = $stmt->get_result()->fetch_assoc();
            // Mailer::sendPasswordResetConfirmation($voter_info['email'], $voter_info['name']);
        } else {
            $error = 'Error changing password / पासवर्ड परिवर्तन गर्न समस्या भयो';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - VoteNepal</title>
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
            background: var(--light);
            min-height: 100vh;
        }

        /* Navbar */
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            font-size: 30px;
            color: var(--primary);
        }

        .logo h2 {
            color: var(--dark);
            font-size: 24px;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .welcome-text {
            color: var(--dark);
            font-weight: 500;
        }

        .welcome-text i {
            color: var(--primary);
            margin-right: 5px;
        }

        .btn-logout {
            padding: 8px 20px;
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-logout:hover {
            background: #d81b60;
            transform: translateY(-2px);
        }

        .btn-back {
            padding: 8px 20px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Main Container */
        .password-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 0 20px;
        }

        .password-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
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

        .password-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .password-header i {
            font-size: 50px;
            color: var(--primary);
            margin-bottom: 20px;
            background: rgba(67, 97, 238, 0.1);
            width: 80px;
            height: 80px;
            line-height: 80px;
            border-radius: 50%;
            display: inline-block;
        }

        .password-header h1 {
            color: var(--dark);
            margin-bottom: 10px;
            font-size: 28px;
        }

        .password-header p {
            color: var(--gray);
            font-size: 14px;
        }

        /* Form */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }

        .form-group label i {
            color: var(--primary);
            margin-right: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid var(--light);
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .input-wrapper input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 5px rgba(67, 97, 238, 0.1);
        }

        .input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray);
            cursor: pointer;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        /* Password Strength */
        .password-strength {
            margin-top: 10px;
            font-size: 13px;
        }

        .strength-bar {
            height: 5px;
            background: var(--light);
            border-radius: 5px;
            margin-top: 5px;
            overflow: hidden;
        }

        .strength-bar-fill {
            height: 100%;
            width: 0;
            transition: all 0.3s;
        }

        .strength-weak {
            background: var(--danger);
        }

        .strength-medium {
            background: var(--warning);
        }

        .strength-strong {
            background: var(--success);
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

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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

        .requirements li i {
            font-size: 12px;
        }

        .requirements li.valid i {
            color: var(--success);
        }

        .requirements li.invalid i {
            color: var(--danger);
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

        /* Responsive */
        @media (max-width: 480px) {
            .password-card {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <i class="fas fa-vote-yea"></i>
                <h2>VoteNepal</h2>
            </div>
            <div class="nav-menu">
                <span class="welcome-text">
                    <i class="fas fa-user"></i>
                    <?php echo htmlspecialchars($_SESSION['name'] ?? 'Voter'); ?>
                </span>
                <a href="dashboard.php" class="btn-back">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="password-container">
        <div class="password-card">
            <div class="password-header">
                <i class="fas fa-key"></i>
                <h1>Change Password / पासवर्ड परिवर्तन</h1>
                <p>Update your password to keep your account secure / आफ्नो खाता सुरक्षित राख्न पासवर्ड परिवर्तन गर्नुहोस्</p>
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

            <form method="POST" id="passwordForm">
                <!-- Current Password -->
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Current Password / हालको पासवर्ड</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="current_password" id="current" 
                               placeholder="Enter current password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('current')">
                            <i class="fas fa-eye" id="toggleCurrent"></i>
                        </button>
                    </div>
                </div>

                <!-- New Password -->
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> New Password / नयाँ पासवर्ड</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="new_password" id="new" 
                               placeholder="Enter new password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('new')">
                            <i class="fas fa-eye" id="toggleNew"></i>
                        </button>
                    </div>
                    
                    <!-- Password Strength Indicator -->
                    <div class="password-strength">
                        <div class="strength-bar">
                            <div class="strength-bar-fill" id="strengthBar"></div>
                        </div>
                        <span id="strengthText"></span>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Confirm Password / पासवर्ड पुष्टि</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="confirm_password" id="confirm" 
                               placeholder="Re-enter new password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm')">
                            <i class="fas fa-eye" id="toggleConfirm"></i>
                        </button>
                    </div>
                    <div id="matchIndicator" style="font-size: 13px; margin-top: 5px;"></div>
                </div>

                <!-- Password Requirements -->
                <div class="requirements">
                    <p>Password Requirements / पासवर्ड आवश्यकताहरू:</p>
                    <ul>
                        <li id="reqLength" class="invalid">
                            <i class="fas fa-times-circle"></i> At least 8 characters / कम्तीमा ८ क्यारेक्टर
                        </li>
                        <li id="reqUppercase" class="invalid">
                            <i class="fas fa-times-circle"></i> At least one uppercase letter / कम्तीमा एउटा ठुलो अक्षर
                        </li>
                        <li id="reqLowercase" class="invalid">
                            <i class="fas fa-times-circle"></i> At least one lowercase letter / कम्तीमा एउटा सानो अक्षर
                        </li>
                        <li id="reqNumber" class="invalid">
                            <i class="fas fa-times-circle"></i> At least one number / कम्तीमा एउटा नम्बर
                        </li>
                        <li id="reqSpecial" class="invalid">
                            <i class="fas fa-times-circle"></i> At least one special character / कम्तीमा एउटा विशेष क्यारेक्टर (!@#$%^&*)
                        </li>
                        <li id="reqMatch" class="invalid">
                            <i class="fas fa-times-circle"></i> Passwords match / पासवर्ड मिलेको
                        </li>
                    </ul>
                </div>

                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-sync-alt"></i> Change Password / पासवर्ड परिवर्तन गर्नुहोस्
                </button>
            </form>

            <div class="footer-links">
                <a href="profile.php">
                    <i class="fas fa-arrow-left"></i> Back to Profile / प्रोफाइलमा फर्कनुहोस्
                </a>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword(field) {
            const input = document.getElementById(field);
            const icon = document.getElementById(`toggle${field.charAt(0).toUpperCase() + field.slice(1)}`);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Password strength checker and requirements
        const newPassword = document.getElementById('new');
        const confirmPassword = document.getElementById('confirm');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        const submitBtn = document.getElementById('submitBtn');

        function checkPasswordStrength() {
            const password = newPassword.value;
            
            // Check requirements
            const hasLength = password.length >= 8;
            const hasUppercase = /[A-Z]/.test(password);
            const hasLowercase = /[a-z]/.test(password);
            const hasNumber = /\d/.test(password);
            const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
            
            // Update requirement icons
            document.getElementById('reqLength').className = hasLength ? 'valid' : 'invalid';
            document.getElementById('reqLength').innerHTML = `<i class="fas ${hasLength ? 'fa-check-circle' : 'fa-times-circle'}"></i> At least 8 characters / कम्तीमा ८ क्यारेक्टर`;
            
            document.getElementById('reqUppercase').className = hasUppercase ? 'valid' : 'invalid';
            document.getElementById('reqUppercase').innerHTML = `<i class="fas ${hasUppercase ? 'fa-check-circle' : 'fa-times-circle'}"></i> At least one uppercase letter / कम्तीमा एउटा ठुलो अक्षर`;
            
            document.getElementById('reqLowercase').className = hasLowercase ? 'valid' : 'invalid';
            document.getElementById('reqLowercase').innerHTML = `<i class="fas ${hasLowercase ? 'fa-check-circle' : 'fa-times-circle'}"></i> At least one lowercase letter / कम्तीमा एउटा सानो अक्षर`;
            
            document.getElementById('reqNumber').className = hasNumber ? 'valid' : 'invalid';
            document.getElementById('reqNumber').innerHTML = `<i class="fas ${hasNumber ? 'fa-check-circle' : 'fa-times-circle'}"></i> At least one number / कम्तीमा एउटा नम्बर`;
            
            document.getElementById('reqSpecial').className = hasSpecial ? 'valid' : 'invalid';
            document.getElementById('reqSpecial').innerHTML = `<i class="fas ${hasSpecial ? 'fa-check-circle' : 'fa-times-circle'}"></i> At least one special character / कम्तीमा एउटा विशेष क्यारेक्टर`;
            
            // Calculate strength
            let strength = 0;
            if (hasLength) strength++;
            if (hasUppercase) strength++;
            if (hasLowercase) strength++;
            if (hasNumber) strength++;
            if (hasSpecial) strength++;
            
            // Update strength bar
            const percentage = (strength / 5) * 100;
            strengthBar.style.width = percentage + '%';
            
            if (strength <= 2) {
                strengthBar.className = 'strength-bar-fill strength-weak';
                strengthText.innerHTML = 'Weak Password / कमजोर पासवर्ड';
                strengthText.style.color = '#f72585';
            } else if (strength <= 4) {
                strengthBar.className = 'strength-bar-fill strength-medium';
                strengthText.innerHTML = 'Medium Password / मध्यम पासवर्ड';
                strengthText.style.color = '#f8961e';
            } else {
                strengthBar.className = 'strength-bar-fill strength-strong';
                strengthText.innerHTML = 'Strong Password / बलियो पासवर्ड';
                strengthText.style.color = '#4cc9f0';
            }
            
            checkPasswordMatch();
        }

        function checkPasswordMatch() {
            const password = newPassword.value;
            const confirm = confirmPassword.value;
            
            const match = password === confirm && password !== '';
            
            document.getElementById('reqMatch').className = match ? 'valid' : 'invalid';
            document.getElementById('reqMatch').innerHTML = `<i class="fas ${match ? 'fa-check-circle' : 'fa-times-circle'}"></i> Passwords match / पासवर्ड मिलेको`;
            
            // Enable/disable submit button
            const allValid = document.querySelectorAll('.valid').length === 6;
            submitBtn.disabled = !allValid;
        }

        newPassword.addEventListener('input', checkPasswordStrength);
        confirmPassword.addEventListener('input', checkPasswordMatch);

        // Initial check
        checkPasswordStrength();
    </script>
</body>
</html>