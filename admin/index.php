<?php
require_once '../includes/session.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// If already logged in as admin, redirect to dashboard
if (isAdmin()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    if (Auth::login($username, $password, 'admin')) {
        header("Location: dashboard.php");
        exit();
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - VoteNepal</title>
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
            --gray: #adb5bd;
            --gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 15px 50px rgba(0, 0, 0, 0.15);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            min-height: 100vh;
            background: var(--gradient);
            display: flex;
            align-items: center;
            justify-content: center;
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

        /* Main Container */
        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 1200px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 50px;
            animation: fadeIn 1s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Left Side - Branding */
        .brand-section {
            flex: 1;
            color: white;
            padding: 40px;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        .brand-logo i {
            font-size: 50px;
            background: rgba(255, 255, 255, 0.2);
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7);
            }
            70% {
                transform: scale(1.05);
                box-shadow: 0 0 0 15px rgba(255, 255, 255, 0);
            }
            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0);
            }
        }

        .brand-logo h1 {
            font-size: 42px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .brand-tagline {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 50px;
            line-height: 1.6;
        }

        .feature-list {
            list-style: none;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            animation: slideIn 0.5s ease-out;
            animation-fill-mode: both;
        }

        .feature-item:nth-child(1) { animation-delay: 0.2s; }
        .feature-item:nth-child(2) { animation-delay: 0.4s; }
        .feature-item:nth-child(3) { animation-delay: 0.6s; }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .feature-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            backdrop-filter: blur(5px);
        }

        .feature-text h3 {
            font-size: 18px;
            margin-bottom: 5px;
        }

        .feature-text p {
            font-size: 14px;
            opacity: 0.8;
        }

        /* Right Side - Login Form */
        .login-card {
            flex: 0.8;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 40px;
            padding: 50px;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
            max-width: 450px;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header h2 {
            font-size: 36px;
            color: var(--dark);
            margin-bottom: 10px;
            font-weight: 700;
        }

        .login-header p {
            color: var(--gray);
            font-size: 16px;
        }

        .login-header p i {
            color: var(--primary);
            margin: 0 5px;
        }

        /* Error Alert */
        .error-alert {
            background: #fee2e2;
            border-left: 4px solid var(--danger);
            padding: 16px 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .error-alert i {
            font-size: 24px;
            color: var(--danger);
        }

        .error-alert span {
            color: #991b1b;
            font-size: 14px;
            font-weight: 500;
        }

        /* Form */
        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            color: var(--dark);
            font-weight: 500;
            font-size: 14px;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 20px;
            color: var(--gray);
            font-size: 20px;
            transition: all 0.3s ease;
            z-index: 1;
        }

        .input-field {
            width: 100%;
            padding: 18px 20px 18px 55px;
            border: 2px solid #e9ecef;
            border-radius: 20px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
            font-family: 'Inter', sans-serif;
        }

        .input-field:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 5px rgba(67, 97, 238, 0.1);
            transform: scale(1.02);
        }

        .input-field:focus + .input-icon {
            color: var(--primary);
            transform: scale(1.1);
        }

        /* Password Toggle */
        .password-toggle {
            position: absolute;
            right: 20px;
            color: var(--gray);
            cursor: pointer;
            font-size: 20px;
            transition: all 0.3s ease;
            background: none;
            border: none;
            padding: 0;
        }

        .password-toggle:hover {
            color: var(--primary);
            transform: scale(1.1);
        }

        /* Remember & Forgot */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .remember-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .remember-checkbox input {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--primary);
        }

        .remember-checkbox span {
            color: var(--dark);
            font-size: 14px;
        }

        .forgot-link {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .forgot-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        /* Login Button */
        .login-btn {
            width: 100%;
            padding: 18px;
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 20px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .login-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }

        .login-btn:active {
            transform: translateY(1px);
        }

        .login-btn i {
            margin-right: 10px;
            transition: transform 0.3s ease;
        }

        .login-btn:hover i {
            transform: translateX(5px);
        }

        /* Demo Credentials */
        .demo-credentials {
            margin-top: 40px;
            padding: 25px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 25px;
            text-align: center;
        }

        .demo-title {
            font-size: 16px;
            color: var(--dark);
            margin-bottom: 15px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .demo-badge {
            display: inline-block;
            background: var(--gradient);
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 500;
            margin: 5px;
            box-shadow: 0 3px 10px rgba(67, 97, 238, 0.3);
        }

        .demo-badge i {
            margin-right: 8px;
        }

        .demo-note {
            margin-top: 15px;
            font-size: 12px;
            color: var(--gray);
        }

        /* Loading Animation */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--gradient);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            animation: fadeIn 0.3s ease;
        }

        .loading.show {
            display: flex;
        }

        .loader {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Back to Home */
        .back-home {
            text-align: center;
            margin-top: 25px;
        }

        .back-home a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-home a:hover {
            color: white;
            transform: translateX(-5px);
        }

        /* Responsive */
        @media (max-width: 968px) {
            .login-container {
                flex-direction: column;
                padding: 20px;
            }

            .brand-section {
                text-align: center;
                padding: 20px;
            }

            .brand-logo {
                justify-content: center;
            }

            .feature-list {
                max-width: 400px;
                margin: 0 auto;
            }

            .login-card {
                width: 100%;
                padding: 40px 30px;
            }
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 30px 20px;
            }

            .login-header h2 {
                font-size: 28px;
            }

            .form-options {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
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

    <!-- Loading Animation -->
    <div class="loading" id="loading">
        <div class="loader"></div>
    </div>

    <!-- Main Container -->
    <div class="login-container">
        <!-- Left Brand Section -->
        <div class="brand-section">
            <div class="brand-logo">
                <i class="fas fa-vote-yea"></i>
                <h1>VoteNepal</h1>
            </div>
            <p class="brand-tagline">
                Secure, Transparent, and Accessible<br>
                Voting System for All Nepali Citizens
            </p>
            
            <ul class="feature-list">
                <li class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="feature-text">
                        <h3>End-to-End Encryption</h3>
                        <p>Your votes are secure and anonymous</p>
                    </div>
                </li>
                <li class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Real-Time Results</h3>
                        <p>Live counting with instant updates</p>
                    </div>
                </li>
                <li class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Mobile Friendly</h3>
                        <p>Access from any device, anywhere</p>
                    </div>
                </li>
            </ul>
        </div>

        <!-- Right Login Card -->
        <div class="login-card">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>
                    <i class="fas fa-lock"></i>
                    Admin Portal
                    <i class="fas fa-lock"></i>
                </p>
            </div>

            <?php if ($error): ?>
                <div class="error-alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['logout'])): ?>
                <div class="error-alert" style="background: #d1fae5; border-left-color: var(--success);">
                    <i class="fas fa-check-circle" style="color: var(--success);"></i>
                    <span style="color: #065f46;">You have been logged out successfully</span>
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label class="form-label" for="username">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" 
                               class="input-field" 
                               id="username" 
                               name="username" 
                               placeholder="Enter your username"
                               value="<?php echo htmlspecialchars($username); ?>" 
                               required 
                               autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" 
                               class="input-field" 
                               id="password" 
                               name="password" 
                               placeholder="Enter your password"
                               required>
                        <button type="button" class="password-toggle" onclick="togglePassword()" tabindex="-1">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="remember-checkbox">
                        <input type="checkbox" id="remember" name="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="#" class="forgot-link" onclick="showForgotPassword()">
                        Forgot Password?
                    </a>
                </div>

                <button type="submit" class="login-btn" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i>
                    Login to Dashboard
                </button>

                <div class="demo-credentials">
                    <div class="demo-title">
                        <i class="fas fa-code"></i> Demo Credentials
                    </div>
                    <div>
                        <span class="demo-badge">
                            <i class="fas fa-user"></i> admin
                        </span>
                        <span class="demo-badge">
                            <i class="fas fa-lock"></i> ••••••
                        </span>
                    </div>
                    <p class="demo-note">
                        <i class="fas fa-info-circle"></i>
                        Use these credentials for testing
                    </p>
                </div>
            </form>
        </div>
    </div>

    <!-- Back to Home -->
    <div class="back-home">
        <a href="../index.php">
            <i class="fas fa-arrow-left"></i>
            Back to Homepage
        </a>
    </div>

    <!-- Forgot Password Modal -->
    <div id="forgotModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 10000;">
        <div style="background: white; padding: 40px; border-radius: 30px; max-width: 400px; width: 90%; animation: slideIn 0.3s ease;">
            <h3 style="margin-bottom: 20px; color: var(--dark);">Reset Password</h3>
            <p style="margin-bottom: 20px; color: var(--gray);">Please contact system administrator to reset your password.</p>
            <p style="margin-bottom: 30px; padding: 15px; background: var(--light); border-radius: 15px;">
                <i class="fas fa-envelope" style="color: var(--primary); margin-right: 10px;"></i>
                admin@votenepal.gov.np
            </p>
            <button onclick="hideForgotModal()" class="login-btn" style="padding: 12px;">Close</button>
        </div>
    </div>

    <script>
        // Password Toggle Function
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Enter Key Support
        document.getElementById('loginForm').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('loginBtn').click();
            }
        });

        // Form Submit with Loading Animation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!username || !password) {
                showError('Please enter both username and password');
                return;
            }
            
            // Show loading
            document.getElementById('loading').classList.add('show');
            
            // Submit form after short delay (for animation)
            setTimeout(() => {
                this.submit();
            }, 500);
        });

        // Show Error Function
        function showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-alert';
            errorDiv.innerHTML = `
                <i class="fas fa-exclamation-circle"></i>
                <span>${message}</span>
            `;
            
            const loginCard = document.querySelector('.login-card');
            const existingError = document.querySelector('.error-alert');
            
            if (existingError) {
                existingError.remove();
            }
            
            loginCard.insertBefore(errorDiv, loginCard.firstChild.nextSibling);
            
            setTimeout(() => {
                errorDiv.remove();
            }, 3000);
        }

        // Forgot Password Modal
        function showForgotPassword() {
            document.getElementById('forgotModal').style.display = 'flex';
        }

        function hideForgotModal() {
            document.getElementById('forgotModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('forgotModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }

        // Remember Me functionality
        document.addEventListener('DOMContentLoaded', function() {
            const rememberCheck = document.getElementById('remember');
            const usernameInput = document.getElementById('username');
            
            // Check if username is saved
            const savedUsername = localStorage.getItem('adminUsername');
            if (savedUsername) {
                usernameInput.value = savedUsername;
                rememberCheck.checked = true;
            }
            
            // Save username when form submits
            document.getElementById('loginForm').addEventListener('submit', function() {
                if (rememberCheck.checked) {
                    localStorage.setItem('adminUsername', usernameInput.value);
                } else {
                    localStorage.removeItem('adminUsername');
                }
            });
        });

        // Input field animations
        const inputs = document.querySelectorAll('.input-field');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.querySelector('.input-icon').style.color = 'var(--primary)';
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.querySelector('.input-icon').style.color = 'var(--gray)';
                }
            });
        });

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Smooth scroll to error
        if (document.querySelector('.error-alert')) {
            document.querySelector('.error-alert').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    </script>
</body>
</html>