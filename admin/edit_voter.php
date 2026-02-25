<?php
require_once '../includes/auth.php';
Auth::requireAdmin();

$db = Database::getInstance()->getConnection();
$error = '';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch voter details
$query = "SELECT v.*, p.id as province_id, d.id as district_id, c.id as constituency_id 
          FROM voters v 
          LEFT JOIN provinces p ON v.province_id = p.id 
          LEFT JOIN districts d ON v.district_id = d.id 
          LEFT JOIN constituencies c ON v.constituency_id = c.id 
          WHERE v.id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$voter = $result->fetch_assoc();

if (!$voter) {
    $_SESSION['error'] = 'Voter not found';
    redirect('admin/manage_voters.php');
}

// Fetch provinces
$provinces = getProvinces();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
    $is_verified = isset($_POST['is_verified']) ? 1 : 0;
    
    // Handle photo upload
    $profile_photo = $voter['profile_photo'];
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $upload = uploadImage($_FILES['profile_photo'], 'voters', $voter['profile_photo']);
        if ($upload['success']) {
            $profile_photo = $upload['filename'];
        } else {
            $error = $upload['message'];
        }
    }
    
    // Handle password change
    if (!empty($_POST['new_password'])) {
        $password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $query = "UPDATE voters SET name=?, province_id=?, district_id=?, constituency_id=?, 
                  dob=?, citizenship_number=?, father_name=?, mother_name=?, address=?, 
                  phone=?, email=?, profile_photo=?, is_verified=?, password=? WHERE id=?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("siiissssssssisi", $name, $province_id, $district_id, $constituency_id, 
                         $dob, $citizenship, $father_name, $mother_name, $address, $phone, 
                         $email, $profile_photo, $is_verified, $password, $id);
    } else {
        $query = "UPDATE voters SET name=?, province_id=?, district_id=?, constituency_id=?, 
                  dob=?, citizenship_number=?, father_name=?, mother_name=?, address=?, 
                  phone=?, email=?, profile_photo=?, is_verified=? WHERE id=?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("siiisssssssssi", $name, $province_id, $district_id, $constituency_id, 
                         $dob, $citizenship, $father_name, $mother_name, $address, $phone, 
                         $email, $profile_photo, $is_verified, $id);
    }
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Voter updated successfully';
        redirect('admin/manage_voters.php');
    } else {
        $error = 'Error: ' . $db->error;
    }
}

// Get districts for this province
$districts = [];
if ($voter['province_id']) {
    $districts = getDistricts($voter['province_id']);
}

// Get constituencies for this district
$constituencies = [];
if ($voter['district_id']) {
    $constituencies = getConstituencies($voter['district_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Voter - VoteNepal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="sidebar-header">
                <h2>🗳️ VoteNepal</h2>
                <p>Admin Panel</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li class="active"><a href="manage_voters.php">👥 Manage Voters</a></li>
                <li><a href="manage_parties.php">🎯 Manage Parties</a></li>
                <li><a href="manage_candidates.php">👤 Manage Candidates</a></li>
                <li><a href="manage_provinces.php">🗺️ Manage Provinces</a></li>
                <li><a href="manage_districts.php">🏘️ Manage Districts</a></li>
                <li><a href="manage_constituencies.php">📍 Manage Constituencies</a></li>
                <li><a href="view_results.php">📊 View Results</a></li>
                <li><a href="logout.php">🚪 Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="admin-content">
            <div class="content-header">
                <h1>Edit Voter: <?php echo htmlspecialchars($voter['name']); ?></h1>
                <a href="manage_voters.php" class="btn btn-outline">← Back to Voters</a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="POST" enctype="multipart/form-data" class="admin-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Voter ID</label>
                            <input type="text" value="<?php echo $voter['voter_id']; ?>" readonly disabled>
                        </div>
                        <div class="form-group">
                            <label>Registration Date</label>
                            <input type="text" value="<?php echo date('d M Y', strtotime($voter['created_at'])); ?>" readonly disabled>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_verified" <?php echo $voter['is_verified'] ? 'checked' : ''; ?>>
                            Verified Voter
                        </label>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Full Name *</label>
                            <input type="text" id="name" name="name" required 
                                   value="<?php echo htmlspecialchars($voter['name']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="dob">Date of Birth *</label>
                            <input type="date" id="dob" name="dob" required 
                                   value="<?php echo $voter['dob']; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="province">Province *</label>
                            <select id="province" name="province" required>
                                <option value="">Select Province</option>
                                <?php foreach($provinces as $province): ?>
                                    <option value="<?php echo $province['id']; ?>" 
                                        <?php echo ($province['id'] == $voter['province_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($province['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="district">District *</label>
                            <select id="district" name="district" required>
                                <option value="">Select District</option>
                                <?php foreach($districts as $district): ?>
                                    <option value="<?php echo $district['id']; ?>" 
                                        <?php echo ($district['id'] == $voter['district_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($district['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="constituency">Constituency *</label>
                            <select id="constituency" name="constituency" required>
                                <option value="">Select Constituency</option>
                                <?php foreach($constituencies as $constituency): ?>
                                    <option value="<?php echo $constituency['id']; ?>" 
                                        <?php echo ($constituency['id'] == $voter['constituency_id']) ? 'selected' : ''; ?>>
                                        Constituency <?php echo $constituency['constituency_number']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="citizenship">Citizenship Number *</label>
                            <input type="text" id="citizenship" name="citizenship" required 
                                   value="<?php echo htmlspecialchars($voter['citizenship_number']); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="father_name">Father's Name *</label>
                            <input type="text" id="father_name" name="father_name" required 
                                   value="<?php echo htmlspecialchars($voter['father_name']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="mother_name">Mother's Name *</label>
                            <input type="text" id="mother_name" name="mother_name" required 
                                   value="<?php echo htmlspecialchars($voter['mother_name']); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address *</label>
                        <textarea id="address" name="address" rows="3" required><?php echo htmlspecialchars($voter['address']); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" required 
                                   value="<?php echo htmlspecialchars($voter['phone']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" required 
                                   value="<?php echo htmlspecialchars($voter['email']); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Current Photo</label>
                        <?php if ($voter['profile_photo']): ?>
                            <div class="current-image">
                                <img src="../assets/uploads/voters/<?php echo $voter['profile_photo']; ?>" 
                                     alt="Profile Photo" style="max-width: 150px; max-height: 150px; border-radius: 50%;">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="profile_photo">New Profile Photo (leave empty to keep current)</label>
                        <input type="file" id="profile_photo" name="profile_photo" accept="image/*">
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password (leave empty to keep current)</label>
                        <input type="password" id="new_password" name="new_password" placeholder="Enter new password">
                        <small>Min 8 characters with uppercase, lowercase and number</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Voter</button>
                        <a href="manage_voters.php" class="btn btn-outline">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    // Dynamic dropdowns
    document.getElementById('province').addEventListener('change', function() {
        const provinceId = this.value;
        const districtSelect = document.getElementById('district');
        const constituencySelect = document.getElementById('constituency');
        
        districtSelect.innerHTML = '<option value="">Select District</option>';
        constituencySelect.innerHTML = '<option value="">Select Constituency</option>';
        districtSelect.disabled = true;
        constituencySelect.disabled = true;
        
        if (provinceId) {
            fetch(`../api/get_districts.php?province_id=${provinceId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        data.districts.forEach(district => {
                            const option = document.createElement('option');
                            option.value = district.id;
                            option.textContent = district.name;
                            districtSelect.appendChild(option);
                        });
                        districtSelect.disabled = false;
                    }
                });
        }
    });
    
    document.getElementById('district').addEventListener('change', function() {
        const districtId = this.value;
        const constituencySelect = document.getElementById('constituency');
        
        constituencySelect.innerHTML = '<option value="">Select Constituency</option>';
        constituencySelect.disabled = true;
        
        if (districtId) {
            fetch(`../api/get_constituencies.php?district_id=${districtId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        data.constituencies.forEach(constituency => {
                            const option = document.createElement('option');
                            option.value = constituency.id;
                            option.textContent = `Constituency ${constituency.constituency_number}`;
                            constituencySelect.appendChild(option);
                        });
                        constituencySelect.disabled = false;
                    }
                });
        }
    });
    </script>
</body>
</html>