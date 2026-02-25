<?php
require_once '../includes/session.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// If already logged in, redirect to dashboard
if (isVoter()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$voter_id = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    if (Auth::login($username, $password, 'voter')) {
        // Set remember me cookie if checked
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expiry = time() + (30 * 24 * 60 * 60); // 30 days
            
            setcookie('remember_token', $token, $expiry, '/', '', false, true);
            
            // Save token to database
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE voters SET remember_token = ? WHERE id = ?");
            $stmt->bind_param("si", $token, $_SESSION['user_id']);
            $stmt->execute();
        }
        
        header("Location: dashboard.php");
        exit();
    } else {
        $error = 'Invalid Voter ID or Password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Login - VoteNepal</title>
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

        /* Login Container */
        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 450px;
            padding: 20px;
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

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.4);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header i {
            font-size: 60px;
            color: var(--primary);
            background: rgba(67, 97, 238, 0.1);
            width: 100px;
            height: 100px;
            line-height: 100px;
            border-radius: 50%;
            margin-bottom: 20px;
        }

        .login-header h2 {
            font-size: 28px;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .login-header p {
            color: var(--gray);
            font-size: 14px;
        }

        /* Error Alert */
        .error-alert {
            background: #fee2e2;
            border-left: 4px solid var(--danger);
            padding: 15px 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: shake 0.5s ease;
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

        /* Success Alert */
        .success-alert {
            background: #d1fae5;
            border-left: 4px solid var(--success);
            padding: 15px 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .success-alert i {
            font-size: 24px;
            color: var(--success);
        }

        .success-alert span {
            color: #065f46;
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
            font-size: 14px;
        }

        .form-group label i {
            color: var(--primary);
            margin-right: 8px;
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
            transition: all 0.3s;
        }

        .input-wrapper input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid var(--light);
            border-radius: 15px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .input-wrapper input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 5px rgba(67, 97, 238, 0.1);
        }

        .input-wrapper input:focus + i {
            color: var(--primary);
        }

        /* Password Toggle */
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray);
            cursor: pointer;
            font-size: 18px;
            z-index: 2;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        /* Form Options */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
        }

        .remember-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .remember-checkbox input {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }

        .remember-checkbox span {
            color: var(--gray);
            font-size: 14px;
        }

        .forgot-link {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        /* Login Button */
        .login-btn {
            width: 100%;
            padding: 15px;
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .login-btn i {
            transition: transform 0.3s;
        }

        .login-btn:hover i {
            transform: translateX(5px);
        }

        /* Register Link */
        .register-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid var(--light);
        }

        .register-link p {
            color: var(--gray);
            margin-bottom: 10px;
        }

        .register-btn {
            display: inline-block;
            padding: 12px 30px;
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            border-radius: 15px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .register-btn:hover {
            background: var(--primary);
            color: white;
        }

        /* Back to Home */
        .back-home {
            text-align: center;
            margin-top: 20px;
        }

        .back-home a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            opacity: 0.9;
            transition: all 0.3s;
        }

        .back-home a:hover {
            opacity: 1;
            transform: translateX(-5px);
        }

        /* Loading Animation */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(5px);
        }

        .loading.show {
            display: flex;
        }

        .loader {
            width: 50px;
            height: 50px;
            border: 3px solid white;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-card {
                padding: 30px 20px;
            }
            
            .login-header h2 {
                font-size: 24px;
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

    <!-- Login Container -->
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-vote-yea"></i>
                <h2>Voter Login</h2>
                <p>मतदाता लगइन - आफ्नो मतदाता ID प्रयोग गर्नुहोस्</p>
            </div>

            <?php if ($error): ?>
                <div class="error-alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['registered'])): ?>
                <div class="success-alert">
                    <i class="fas fa-check-circle"></i>
                    <span>Registration successful! Please login with your Voter ID.</span>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['voted'])): ?>
                <div class="success-alert">
                    <i class="fas fa-check-circle"></i>
                    <span>Thank you for voting! You can login to view your dashboard.</span>
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label>
                        <i class="fas fa-id-card"></i>
                        Voter ID or Email / मतदाता ID वा ईमेल
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" 
                               name="username" 
                               placeholder="Enter your Voter ID or Email" 
                               value="<?php echo htmlspecialchars($voter_id); ?>"
                               required 
                               autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-lock"></i>
                        Password / पासवर्ड
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" 
                               name="password" 
                               id="password"
                               placeholder="Enter your password" 
                               required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="remember-checkbox">
                        <input type="checkbox" name="remember" id="remember">
                        <span>Remember me / मलाई सम्झनुहोस्</span>
                    </label>
                    <a href="forgot_password.php" class="forgot-link">
                        Forgot Password?
                    </a>
                </div>

                <button type="submit" class="login-btn" id="loginBtn">
                    <span>Login / लगइन</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="register-link">
                <p>Don't have an account? / खाता छैन?</p>
                <a href="register.php" class="register-btn">
                    <i class="fas fa-user-plus"></i>
                    Register as Voter / दर्ता गर्नुहोस्
                </a>
            </div>
        </div>

        <div class="back-home">
            <a href="../index.php">
                <i class="fas fa-arrow-left"></i>
                Back to Homepage
            </a>
        </div>
    </div>

    <script>
        // Password Toggle
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

        // Form Submit with Loading
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = document.querySelector('input[name="username"]').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!username || !password) {
                showError('Please enter both Voter ID and Password');
                return;
            }
            
            document.getElementById('loading').classList.add('show');
            
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
            
            loginCard.insertBefore(errorDiv, loginCard.firstChild.nextSibling.nextSibling);
            
            setTimeout(() => {
                errorDiv.remove();
            }, 3000);
        }

        // Remember Me Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const rememberCheck = document.getElementById('remember');
            const usernameInput = document.querySelector('input[name="username"]');
            
            const savedUsername = localStorage.getItem('voterUsername');
            if (savedUsername) {
                usernameInput.value = savedUsername;
                rememberCheck.checked = true;
            }
            
            document.getElementById('loginForm').addEventListener('submit', function() {
                if (rememberCheck.checked) {
                    localStorage.setItem('voterUsername', usernameInput.value);
                } else {
                    localStorage.removeItem('voterUsername');
                }
            });
        });

        // Input Animations
        const inputs = document.querySelectorAll('.input-wrapper input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.querySelector('i:first-child').style.color = 'var(--primary)';
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.querySelector('i:first-child').style.color = 'var(--gray)';
                }
            });
        });

        // Prevent Form Resubmission
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>