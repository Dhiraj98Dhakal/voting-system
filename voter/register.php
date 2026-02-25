<?php
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/mailer.php'; // Email को लागि

$db = Database::getInstance()->getConnection();
$error = '';
$success = '';

// Fetch provinces for dropdown
$provinces = getProvinces();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid security token';
    } else {
        // Sanitize inputs
        $name = sanitize($_POST['name']);
        $province_id = intval($_POST['province']);
        $district_id = intval($_POST['district']);
        $constituency_id = intval($_POST['constituency']);
        $dob = $_POST['dob'];
        $citizenship = sanitize($_POST['citizenship']);
        $father_name = sanitize($_POST['father_name']);
        $mother_name = sanitize($_POST['mother_name']);
        $address = sanitize($_POST['address']);
        $phone = sanitize($_POST['phone']);
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $terms = isset($_POST['terms']) ? true : false;
        
        // Validate
        if (!isVoterEligible($dob)) {
            $error = 'तपाईं १८ वर्ष भन्दा माथि हुनुपर्छ | You must be at least 18 years old';
        } elseif ($password !== $confirm_password) {
            $error = 'पासवर्ड मिलेन | Passwords do not match';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'ईमेल ठेगाना मान्य छैन | Invalid email format';
        } elseif (!preg_match('/^[9][0-9]{9}$/', $phone)) {
            $error = 'फोन नम्बर १० अंकको हुनुपर्छ | Phone must be 10 digits starting with 9';
        } elseif (!$terms) {
            $error = 'नियम र शर्तहरू स्वीकार गर्नुहोस् | Please accept terms and conditions';
        } else {
            // Check if email exists
            if (emailExists($email)) {
                $error = 'ईमेल पहिले नै दर्ता भइसकेको छ | Email already registered';
            } elseif (citizenshipExists($citizenship)) {
                $error = 'नागरिकता नम्बर पहिले नै दर्ता भइसकेको छ | Citizenship number already registered';
            } else {
                // Generate voter ID
                $voter_id = generateVoterId();
                
                // Handle photo upload
                $profile_photo = '';
                if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
                    $upload = uploadImage($_FILES['profile_photo'], 'voters');
                    if ($upload['success']) {
                        $profile_photo = $upload['filename'];
                    } else {
                        $error = $upload['message'];
                    }
                }
                
                if (empty($error)) {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert voter
                    $query = "INSERT INTO voters (voter_id, name, province_id, district_id, constituency_id, 
                              dob, citizenship_number, father_name, mother_name, address, phone, email, 
                              password, profile_photo, is_verified) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";
                    
                    $stmt = $db->prepare($query);
                    $stmt->bind_param("ssiiisssssssss", $voter_id, $name, $province_id, $district_id, 
                                    $constituency_id, $dob, $citizenship, $father_name, $mother_name, 
                                    $address, $phone, $email, $hashed_password, $profile_photo);
                    
                    if ($stmt->execute()) {
                        // Send email with Voter ID
                        $mail_result = Mailer::sendVoterId($email, $name, $voter_id);
                        
                        if ($mail_result['success']) {
                            $success = "दर्ता सफल भयो! तपाईंको मतदाता ID: $voter_id ईमेलमा पठाइएको छ। | Registration successful! Your Voter ID: $voter_id has been sent to your email.";
                        } else {
                            $success = "दर्ता सफल भयो! तपाईंको मतदाता ID: $voter_id (तर ईमेल पठाउन सकिएन: " . $mail_result['message'] . ")";
                            error_log("Email sending failed: " . $mail_result['message']);
                        }
                        
                        // Redirect to login after 5 seconds
                        header("refresh:5;url=login.php");
                    } else {
                        $error = "दर्ता असफल भयो: " . $db->error;
                    }
                }
            }
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Registration - VoteNepal</title>
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
            background: var(--gradient);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .register-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
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

        .register-header {
            background: var(--gradient);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .register-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .register-header p {
            opacity: 0.9;
            font-size: 16px;
        }

        .register-header i {
            font-size: 50px;
            margin-bottom: 20px;
            background: rgba(255,255,255,0.2);
            width: 80px;
            height: 80px;
            line-height: 80px;
            border-radius: 50%;
            display: inline-block;
        }

        .register-body {
            padding: 40px;
        }

        .progress-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
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

        .progress-step {
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

        .progress-step.active {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
        }

        .progress-step.completed {
            border-color: var(--success);
            background: var(--success);
            color: white;
        }

        .form-section {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        .form-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
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
        }

        .input-wrapper input,
        .input-wrapper select,
        .input-wrapper textarea {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid var(--light);
            border-radius: 15px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: inherit;
        }

        .input-wrapper input:focus,
        .input-wrapper select:focus,
        .input-wrapper textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 5px rgba(67, 97, 238, 0.1);
        }

        .photo-upload {
            border: 2px dashed var(--light);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .photo-upload:hover {
            border-color: var(--primary);
            background: var(--light);
        }

        .photo-upload i {
            font-size: 40px;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .photo-upload p {
            color: var(--gray);
        }

        .photo-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin: 10px auto;
            display: none;
            border: 3px solid var(--primary);
        }

        .terms-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
        }

        .terms-checkbox input {
            width: 18px;
            height: 18px;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 15px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-primary {
            background: var(--gradient);
            color: white;
            width: 100%;
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

        .navigation-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
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

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: var(--gray);
        }

        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .register-body {
                padding: 20px;
            }
            
            .register-header {
                padding: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <i class="fas fa-vote-yea"></i>
            <h1>Voter Registration</h1>
            <p>मतदाता दर्ता - नेपालको लोकतान्त्रिक अभियानमा सहभागी हुनुहोस्</p>
        </div>

        <div class="register-body">
            <!-- Progress Bar -->
            <div class="progress-bar">
                <div class="progress-step active" id="step1">1</div>
                <div class="progress-step" id="step2">2</div>
                <div class="progress-step" id="step3">3</div>
                <div class="progress-step" id="step4">4</div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <!-- Step 1: Personal Information -->
                <div class="form-section active" id="section1">
                    <h3 style="margin-bottom: 20px;">📋 Personal Information</h3>
                    
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Full Name / पूरा नाम *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" name="name" placeholder="As per citizenship / नागरिकता अनुसार" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Date of Birth / जन्म मिति *</label>
                            <div class="input-wrapper">
                                <i class="fas fa-calendar"></i>
                                <input type="date" name="dob" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-id-card"></i> Citizenship No / नागरिकता नं *</label>
                            <div class="input-wrapper">
                                <i class="fas fa-id-card"></i>
                                <input type="text" name="citizenship" placeholder="e.g., 12-34-56-78901" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-user-tie"></i> Father's Name / बुबाको नाम *</label>
                            <div class="input-wrapper">
                                <i class="fas fa-user-tie"></i>
                                <input type="text" name="father_name" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-user-tie"></i> Mother's Name / आमाको नाम *</label>
                            <div class="input-wrapper">
                                <i class="fas fa-user-tie"></i>
                                <input type="text" name="mother_name" required>
                            </div>
                        </div>
                    </div>

                    <div class="navigation-buttons">
                        <div></div>
                        <button type="button" class="btn btn-primary" onclick="nextStep(2)">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Location Information -->
                <div class="form-section" id="section2">
                    <h3 style="margin-bottom: 20px;">📍 Location Information</h3>

                    <div class="form-group">
                        <label><i class="fas fa-map"></i> Province / प्रदेश *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-map"></i>
                            <select id="province" name="province" required>
                                <option value="">Select Province / प्रदेश छान्नुहोस्</option>
                                <?php foreach($provinces as $province): ?>
                                    <option value="<?php echo $province['id']; ?>">
                                        <?php echo htmlspecialchars($province['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-city"></i> District / जिल्ला *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-city"></i>
                            <select id="district" name="district" required disabled>
                                <option value="">Select District / जिल्ला छान्नुहोस्</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-map-pin"></i> Constituency / निर्वाचन क्षेत्र *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-map-pin"></i>
                            <select id="constituency" name="constituency" required disabled>
                                <option value="">Select Constituency / क्षेत्र छान्नुहोस्</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-home"></i> Permanent Address / स्थायी ठेगाना *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-home"></i>
                            <textarea name="address" rows="3" placeholder="Ward, Municipality/Village, Tole" required></textarea>
                        </div>
                    </div>

                    <div class="navigation-buttons">
                        <button type="button" class="btn btn-outline" onclick="prevStep(1)">
                            <i class="fas fa-arrow-left"></i> Previous
                        </button>
                        <button type="button" class="btn btn-primary" onclick="nextStep(3)">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 3: Contact Information -->
                <div class="form-section" id="section3">
                    <h3 style="margin-bottom: 20px;">📞 Contact Information</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Phone Number / फोन नं *</label>
                            <div class="input-wrapper">
                                <i class="fas fa-phone"></i>
                                <input type="tel" name="phone" placeholder="98XXXXXXXX" pattern="[9][0-9]{9}" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email Address / ईमेल *</label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="email" placeholder="your@email.com" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> Password / पासवर्ड *</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="password" id="password" 
                                       placeholder="Min 8 characters" minlength="8" required>
                            </div>
                            <small>At least 8 characters with uppercase, lowercase and number</small>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> Confirm Password / पासवर्ड पुष्टि *</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="confirm_password" id="confirm_password" 
                                       placeholder="Re-enter password" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-camera"></i> Profile Photo / फोटो *</label>
                        <div class="photo-upload" onclick="document.getElementById('profile_photo').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Click to upload photo / फोटो अपलोड गर्नुहोस्</p>
                            <img id="photoPreview" class="photo-preview" src="#" alt="Preview">
                        </div>
                        <input type="file" id="profile_photo" name="profile_photo" accept="image/*" style="display: none;" required>
                    </div>

                    <div class="navigation-buttons">
                        <button type="button" class="btn btn-outline" onclick="prevStep(2)">
                            <i class="fas fa-arrow-left"></i> Previous
                        </button>
                        <button type="button" class="btn btn-primary" onclick="nextStep(4)">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 4: Review & Submit -->
                <div class="form-section" id="section4">
                    <h3 style="margin-bottom: 20px;">✅ Review & Submit</h3>

                    <div style="background: var(--light); padding: 20px; border-radius: 15px; margin-bottom: 20px;">
                        <p style="margin-bottom: 15px;">Please verify your information before submitting:</p>
                        
                        <div id="reviewInfo"></div>
                    </div>

                    <div class="terms-checkbox">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">
                            I confirm that all information provided is correct and I am eligible to vote.
                            <br>म पुष्टि गर्दछु कि दिइएको सबै जानकारी सही छ र म मतदान गर्न योग्य छु।
                        </label>
                    </div>

                    <div class="navigation-buttons">
                        <button type="button" class="btn btn-outline" onclick="prevStep(3)">
                            <i class="fas fa-arrow-left"></i> Previous
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check-circle"></i> Complete Registration
                        </button>
                    </div>
                </div>
            </form>

            <div class="login-link">
                Already registered? <a href="login.php">Login here</a> | 
                पहिले नै दर्ता भइसकेको? <a href="login.php">यहाँ लगइन गर्नुहोस्</a>
            </div>
        </div>
    </div>

    <script>
        let currentStep = 1;

        function nextStep(step) {
            // Validate current step
            if (!validateStep(currentStep)) {
                return;
            }

            // Update sections
            document.getElementById(`section${currentStep}`).classList.remove('active');
            document.getElementById(`section${step}`).classList.add('active');
            
            // Update progress bar
            document.getElementById(`step${currentStep}`).classList.add('completed');
            document.getElementById(`step${step}`).classList.add('active');
            
            currentStep = step;

            // If moving to review step, populate review info
            if (step === 4) {
                updateReviewInfo();
            }

            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function prevStep(step) {
            document.getElementById(`section${currentStep}`).classList.remove('active');
            document.getElementById(`section${step}`).classList.add('active');
            
            document.getElementById(`step${currentStep}`).classList.remove('active');
            document.getElementById(`step${step}`).classList.add('active');
            
            currentStep = step;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function validateStep(step) {
            if (step === 1) {
                const name = document.querySelector('input[name="name"]').value;
                const dob = document.querySelector('input[name="dob"]').value;
                const citizenship = document.querySelector('input[name="citizenship"]').value;
                const father = document.querySelector('input[name="father_name"]').value;
                const mother = document.querySelector('input[name="mother_name"]').value;

                if (!name || !dob || !citizenship || !father || !mother) {
                    alert('Please fill all required fields');
                    return false;
                }
                return true;
            }
            else if (step === 2) {
                const province = document.getElementById('province').value;
                const district = document.getElementById('district').value;
                const constituency = document.getElementById('constituency').value;
                const address = document.querySelector('textarea[name="address"]').value;

                if (!province || !district || !constituency || !address) {
                    alert('Please fill all location details');
                    return false;
                }
                return true;
            }
            else if (step === 3) {
                const phone = document.querySelector('input[name="phone"]').value;
                const email = document.querySelector('input[name="email"]').value;
                const password = document.getElementById('password').value;
                const confirm = document.getElementById('confirm_password').value;
                const photo = document.getElementById('profile_photo').value;

                if (!phone || !email || !password || !confirm || !photo) {
                    alert('Please fill all contact details and upload photo');
                    return false;
                }

                if (password !== confirm) {
                    alert('Passwords do not match');
                    return false;
                }

                if (password.length < 8) {
                    alert('Password must be at least 8 characters');
                    return false;
                }

                return true;
            }
            return true;
        }

        function updateReviewInfo() {
            const name = document.querySelector('input[name="name"]').value;
            const dob = document.querySelector('input[name="dob"]').value;
            const citizenship = document.querySelector('input[name="citizenship"]').value;
            const father = document.querySelector('input[name="father_name"]').value;
            const mother = document.querySelector('input[name="mother_name"]').value;
            const province = document.getElementById('province').selectedOptions[0].text;
            const district = document.getElementById('district').selectedOptions[0].text;
            const constituency = document.getElementById('constituency').selectedOptions[0].text;
            const address = document.querySelector('textarea[name="address"]').value;
            const phone = document.querySelector('input[name="phone"]').value;
            const email = document.querySelector('input[name="email"]').value;

            const reviewInfo = document.getElementById('reviewInfo');
            reviewInfo.innerHTML = `
                <table style="width: 100%; border-collapse: collapse;">
                    <tr><td><strong>Name:</strong></td><td>${name}</td></tr>
                    <tr><td><strong>DOB:</strong></td><td>${dob}</td></tr>
                    <tr><td><strong>Citizenship:</strong></td><td>${citizenship}</td></tr>
                    <tr><td><strong>Father's Name:</strong></td><td>${father}</td></tr>
                    <tr><td><strong>Mother's Name:</strong></td><td>${mother}</td></tr>
                    <tr><td><strong>Location:</strong></td><td>${province} > ${district} > ${constituency}</td></tr>
                    <tr><td><strong>Address:</strong></td><td>${address}</td></tr>
                    <tr><td><strong>Phone:</strong></td><td>${phone}</td></tr>
                    <tr><td><strong>Email:</strong></td><td>${email}</td></tr>
                </table>
            `;
        }

        // Photo preview
        document.getElementById('profile_photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('photoPreview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });

        // ========== HARDCODED DISTRICT DATA (API नभएसम्मको लागि) ==========
        const districtData = {
            1: [ // Province 1
                {id: 1, name: 'Taplejung', name_nepali: 'ताप्लेजुङ'},
                {id: 2, name: 'Panchthar', name_nepali: 'पाँचथर'},
                {id: 3, name: 'Ilam', name_nepali: 'इलाम'},
                {id: 4, name: 'Jhapa', name_nepali: 'झापा'},
                {id: 5, name: 'Morang', name_nepali: 'मोरङ'},
                {id: 6, name: 'Sunsari', name_nepali: 'सुनसरी'},
                {id: 7, name: 'Dhankuta', name_nepali: 'धनकुटा'},
                {id: 8, name: 'Terhathum', name_nepali: 'तेह्रथुम'},
                {id: 9, name: 'Sankhuwasabha', name_nepali: 'सङ्खुवासभा'},
                {id: 10, name: 'Bhojpur', name_nepali: 'भोजपुर'},
                {id: 11, name: 'Solukhumbu', name_nepali: 'सोलुखुम्बु'},
                {id: 12, name: 'Okhaldhunga', name_nepali: 'ओखलढुङ्गा'},
                {id: 13, name: 'Khotang', name_nepali: 'खोटाङ'},
                {id: 14, name: 'Udayapur', name_nepali: 'उदयपुर'}
            ],
            2: [ // Province 2
                {id: 15, name: 'Saptari', name_nepali: 'सप्तरी'},
                {id: 16, name: 'Siraha', name_nepali: 'सिराहा'},
                {id: 17, name: 'Dhanusha', name_nepali: 'धनुषा'},
                {id: 18, name: 'Mahottari', name_nepali: 'महोत्तरी'},
                {id: 19, name: 'Sarlahi', name_nepali: 'सर्लाही'},
                {id: 20, name: 'Rautahat', name_nepali: 'रौतहट'},
                {id: 21, name: 'Bara', name_nepali: 'बारा'},
                {id: 22, name: 'Parsa', name_nepali: 'पर्सा'}
            ],
            3: [ // Bagmati
                {id: 23, name: 'Dolakha', name_nepali: 'दोलखा'},
                {id: 24, name: 'Sindhupalchok', name_nepali: 'सिन्धुपाल्चोक'},
                {id: 25, name: 'Rasuwa', name_nepali: 'रसुवा'},
                {id: 26, name: 'Dhading', name_nepali: 'धादिङ'},
                {id: 27, name: 'Nuwakot', name_nepali: 'नुवाकोट'},
                {id: 28, name: 'Kathmandu', name_nepali: 'काठमाडौं'},
                {id: 29, name: 'Bhaktapur', name_nepali: 'भक्तपुर'},
                {id: 30, name: 'Lalitpur', name_nepali: 'ललितपुर'},
                {id: 31, name: 'Kavrepalanchok', name_nepali: 'काभ्रेपलान्चोक'},
                {id: 32, name: 'Ramechhap', name_nepali: 'रामेछाप'},
                {id: 33, name: 'Sindhuli', name_nepali: 'सिन्धुली'},
                {id: 34, name: 'Makwanpur', name_nepali: 'मकवानपुर'},
                {id: 35, name: 'Chitawan', name_nepali: 'चितवन'}
            ],
            4: [ // Gandaki
                {id: 36, name: 'Gorkha', name_nepali: 'गोरखा'},
                {id: 37, name: 'Lamjung', name_nepali: 'लमजुङ'},
                {id: 38, name: 'Tanahun', name_nepali: 'तनहुँ'},
                {id: 39, name: 'Kaski', name_nepali: 'कास्की'},
                {id: 40, name: 'Manang', name_nepali: 'मनाङ'},
                {id: 41, name: 'Mustang', name_nepali: 'मुस्ताङ'},
                {id: 42, name: 'Myagdi', name_nepali: 'म्याग्दी'},
                {id: 43, name: 'Parbat', name_nepali: 'पर्वत'},
                {id: 44, name: 'Syangja', name_nepali: 'स्याङ्जा'},
                {id: 45, name: 'Nawalpur', name_nepali: 'नवलपुर'},
                {id: 46, name: 'Baglung', name_nepali: 'बागलुङ'}
            ],
            5: [ // Lumbini
                {id: 47, name: 'Rukum East', name_nepali: 'पूर्वी रुकुम'},
                {id: 48, name: 'Rolpa', name_nepali: 'रोल्पा'},
                {id: 49, name: 'Pyuthan', name_nepali: 'प्युठान'},
                {id: 50, name: 'Gulmi', name_nepali: 'गुल्मी'},
                {id: 51, name: 'Arghakhanchi', name_nepali: 'अर्घाखाँची'},
                {id: 52, name: 'Palpa', name_nepali: 'पाल्पा'},
                {id: 53, name: 'Rupandehi', name_nepali: 'रुपन्देही'},
                {id: 54, name: 'Kapilvastu', name_nepali: 'कपिलवस्तु'},
                {id: 55, name: 'Dang', name_nepali: 'दाङ'},
                {id: 56, name: 'Banke', name_nepali: 'बाँके'},
                {id: 57, name: 'Bardiya', name_nepali: 'बर्दिया'},
                {id: 58, name: 'Nawalparasi West', name_nepali: 'पश्चिम नवलपरासी'}
            ],
            6: [ // Karnali
                {id: 59, name: 'Dolpa', name_nepali: 'डोल्पा'},
                {id: 60, name: 'Mugu', name_nepali: 'मुगु'},
                {id: 61, name: 'Humla', name_nepali: 'हुम्ला'},
                {id: 62, name: 'Jumla', name_nepali: 'जुम्ला'},
                {id: 63, name: 'Kalikot', name_nepali: 'कालिकोट'},
                {id: 64, name: 'Dailekh', name_nepali: 'दैलेख'},
                {id: 65, name: 'Jajarkot', name_nepali: 'जाजरकोट'},
                {id: 66, name: 'Rukum West', name_nepali: 'पश्चिमी रुकुम'},
                {id: 67, name: 'Salyan', name_nepali: 'सल्यान'},
                {id: 68, name: 'Surkhet', name_nepali: 'सुर्खेत'}
            ],
            7: [ // Sudurpashchim
                {id: 69, name: 'Bajura', name_nepali: 'बाजुरा'},
                {id: 70, name: 'Bajhang', name_nepali: 'बझाङ'},
                {id: 71, name: 'Darchula', name_nepali: 'दार्चुला'},
                {id: 72, name: 'Baitadi', name_nepali: 'बैतडी'},
                {id: 73, name: 'Dadeldhura', name_nepali: 'डडेल्धुरा'},
                {id: 74, name: 'Doti', name_nepali: 'डोटी'},
                {id: 75, name: 'Achham', name_nepali: 'अछाम'},
                {id: 76, name: 'Kailali', name_nepali: 'कैलाली'},
                {id: 77, name: 'Kanchanpur', name_nepali: 'कञ्चनपुर'}
            ]
        };

        // Constituency data (sample - each district has 1-5 constituencies)
        const constituencyData = {};
        
        // Generate constituency data for all districts
        for (let districtId = 1; districtId <= 77; districtId++) {
            const numConstituencies = Math.floor(Math.random() * 4) + 1; // 1-4 constituencies
            constituencyData[districtId] = [];
            for (let j = 1; j <= numConstituencies; j++) {
                constituencyData[districtId].push({
                    id: parseInt(`${districtId}${j}`),
                    number: j
                });
            }
        }

        // Province change - Load districts from hardcoded data
        document.getElementById('province').addEventListener('change', function() {
            const provinceId = this.value;
            const districtSelect = document.getElementById('district');
            const constituencySelect = document.getElementById('constituency');
            
            // Reset
            districtSelect.innerHTML = '<option value="">Select District / जिल्ला छान्नुहोस्</option>';
            constituencySelect.innerHTML = '<option value="">Select Constituency / क्षेत्र छान्नुहोस्</option>';
            
            if (provinceId && districtData[provinceId]) {
                // Enable district select
                districtSelect.disabled = false;
                constituencySelect.disabled = true;
                
                // Add districts
                districtData[provinceId].forEach(district => {
                    const option = document.createElement('option');
                    option.value = district.id;
                    option.textContent = district.name_nepali 
                        ? `${district.name} (${district.name_nepali})` 
                        : district.name;
                    districtSelect.appendChild(option);
                });
            } else {
                districtSelect.disabled = true;
                constituencySelect.disabled = true;
            }
        });

        // District change - Load constituencies from hardcoded data
        document.getElementById('district').addEventListener('change', function() {
            const districtId = this.value;
            const constituencySelect = document.getElementById('constituency');
            
            // Reset
            constituencySelect.innerHTML = '<option value="">Select Constituency / क्षेत्र छान्नुहोस्</option>';
            
            if (districtId && constituencyData[districtId]) {
                // Enable constituency select
                constituencySelect.disabled = false;
                
                // Add constituencies
                constituencyData[districtId].forEach(cons => {
                    const option = document.createElement('option');
                    option.value = cons.id;
                    option.textContent = `Constituency ${cons.number} / निर्वाचन क्षेत्र ${cons.number}`;
                    constituencySelect.appendChild(option);
                });
            } else {
                constituencySelect.disabled = true;
            }
        });

        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.createElement('div');
            strengthDiv.className = 'password-strength';
            
            const hasUpperCase = /[A-Z]/.test(password);
            const hasLowerCase = /[a-z]/.test(password);
            const hasNumbers = /\d/.test(password);
            
            let strength = 0;
            if (hasUpperCase) strength++;
            if (hasLowerCase) strength++;
            if (hasNumbers) strength++;
            
            let strengthText = '';
            let strengthColor = '';
            
            if (password.length < 8) {
                strengthText = 'Too short / धेरै छोटो';
                strengthColor = '#f72585';
            } else if (strength <= 2) {
                strengthText = 'Weak / कमजोर';
                strengthColor = '#f8961e';
            } else {
                strengthText = 'Strong / बलियो';
                strengthColor = '#4cc9f0';
            }
            
            const existingStrength = this.parentElement.querySelector('.password-strength');
            if (existingStrength) {
                existingStrength.remove();
            }
            
            strengthDiv.innerHTML = `Password strength: <span style="color: ${strengthColor};">${strengthText}</span>`;
            this.parentElement.appendChild(strengthDiv);
        });

        // Confirm password match
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            if (this.value !== password) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Auto-hide success message after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>