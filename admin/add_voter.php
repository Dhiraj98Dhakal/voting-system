<?php
require_once '../includes/auth.php';
Auth::requireAdmin();

$db = Database::getInstance()->getConnection();
$error = '';
$success = '';

// Fetch provinces for dropdown
$provinces = getProvinces();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Similar to voter registration but with admin privileges
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
    $password = $_POST['password'] ?? 'Welcome@123'; // Default password
    
    if (!isVoterEligible($dob)) {
        $error = 'Voter must be at least 18 years old';
    } else {
        // Generate voter ID
        $voter_id = generateVoterId();
        
        // Handle photo upload
        $profile_photo = '';
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
            $upload = uploadImage($_FILES['profile_photo'], 'voters');
            if ($upload['success']) {
                $profile_photo = $upload['filename'];
            }
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert voter
        $query = "INSERT INTO voters (voter_id, name, province_id, district_id, constituency_id, 
                  dob, citizenship_number, father_name, mother_name, address, phone, email, 
                  password, profile_photo, is_verified) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        
        $stmt = $db->prepare($query);
        $stmt->bind_param("ssiiisssssssss", $voter_id, $name, $province_id, $district_id, 
                        $constituency_id, $dob, $citizenship, $father_name, $mother_name, 
                        $address, $phone, $email, $hashed_password, $profile_photo);
        
        if ($stmt->execute()) {
            $success = "Voter added successfully! Voter ID: " . $voter_id;
        } else {
            $error = "Error: " . $db->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Voter - VoteNepal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <!-- Same sidebar as before -->
        </div>
        
        <div class="admin-content">
            <div class="content-header">
                <h1>Add New Voter</h1>
                <a href="manage_voters.php" class="btn btn-outline">← Back to Voters</a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="POST" enctype="multipart/form-data" class="admin-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Full Name *</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="dob">Date of Birth *</label>
                            <input type="date" id="dob" name="dob" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="province">Province *</label>
                            <select id="province" name="province" required>
                                <option value="">Select Province</option>
                                <?php foreach($provinces as $province): ?>
                                    <option value="<?php echo $province['id']; ?>">
                                        <?php echo htmlspecialchars($province['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="district">District *</label>
                            <select id="district" name="district" required disabled>
                                <option value="">Select District</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="constituency">Constituency *</label>
                            <select id="constituency" name="constituency" required disabled>
                                <option value="">Select Constituency</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="citizenship">Citizenship Number *</label>
                            <input type="text" id="citizenship" name="citizenship" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="father_name">Father's Name *</label>
                            <input type="text" id="father_name" name="father_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="mother_name">Mother's Name *</label>
                            <input type="text" id="mother_name" name="mother_name" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address *</label>
                        <textarea id="address" name="address" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="profile_photo">Profile Photo</label>
                        <input type="file" id="profile_photo" name="profile_photo" accept="image/*">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Add Voter</button>
                        <a href="manage_voters.php" class="btn btn-outline">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/registration.js"></script>
</body>
</html>