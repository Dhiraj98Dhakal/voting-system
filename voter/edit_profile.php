<?php
require_once '../includes/auth.php';
Auth::requireVoter();

$db = Database::getInstance()->getConnection();
$voter_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch current data
$query = "SELECT * FROM voters WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $voter_id);
$stmt->execute();
$voter = $stmt->get_result()->fetch_assoc();

// Fetch provinces
$provinces = getProvinces();
$districts = getDistricts($voter['province_id']);
$constituencies = getConstituencies($voter['district_id']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $province_id = intval($_POST['province']);
    $district_id = intval($_POST['district']);
    $constituency_id = intval($_POST['constituency']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $father_name = sanitize($_POST['father_name']);
    $mother_name = sanitize($_POST['mother_name']);
    
    // Validate phone
    if (!preg_match('/^[9][0-9]{9}$/', $phone)) {
        $error = 'Invalid phone number format / फोन नम्बर गलत छ';
    } else {
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
        
        if (empty($error)) {
            $update = "UPDATE voters SET name=?, province_id=?, district_id=?, constituency_id=?,
                      phone=?, address=?, father_name=?, mother_name=?, profile_photo=?
                      WHERE id=?";
            $stmt = $db->prepare($update);
            $stmt->bind_param("siiisssssi", $name, $province_id, $district_id, $constituency_id,
                            $phone, $address, $father_name, $mother_name, $profile_photo, $voter_id);
            
            if ($stmt->execute()) {
                $success = 'Profile updated successfully! / प्रोफाइल सफलतापूर्वक अद्यावधिक भयो!';
                
                // Refresh data
                $stmt = $db->prepare($query);
                $stmt->bind_param("i", $voter_id);
                $stmt->execute();
                $voter = $stmt->get_result()->fetch_assoc();
                
                // Update session name
                $_SESSION['name'] = $name;
            } else {
                $error = 'Error updating profile / प्रोफाइल अद्यावधिक गर्न समस्या भयो';
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
    <title>Edit Profile - VoteNepal</title>
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

        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 30px;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
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
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .btn-logout {
            padding: 8px 20px;
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
        }

        .edit-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .edit-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
        }

        .edit-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .edit-header i {
            font-size: 50px;
            color: var(--primary);
            margin-bottom: 20px;
        }

        .edit-header h1 {
            color: var(--dark);
            margin-bottom: 10px;
        }

        .photo-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .current-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 15px;
            overflow: hidden;
            border: 4px solid var(--primary);
        }

        .current-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-upload {
            border: 2px dashed var(--light);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .photo-upload:hover {
            border-color: var(--primary);
            background: var(--light);
        }

        .photo-upload i {
            font-size: 30px;
            color: var(--primary);
        }

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

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--light);
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 5px rgba(67, 97, 238, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }

        .actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background: var(--gradient);
            color: white;
            flex: 1;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <i class="fas fa-vote-yea"></i>
                <h2>VoteNepal</h2>
            </div>
            <div class="nav-menu">
                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($voter['name']); ?></span>
                <a href="dashboard.php" class="btn-logout" style="background: var(--primary);">Dashboard</a>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </nav>

    <div class="edit-container">
        <div class="edit-card">
            <div class="edit-header">
                <i class="fas fa-edit"></i>
                <h1>Edit Profile / प्रोफाइल सम्पादन</h1>
                <p>Update your personal information / आफ्नो जानकारी अद्यावधिक गर्नुहोस्</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="editForm">
                <!-- Photo Upload -->
                <div class="photo-section">
                    <div class="current-photo">
                        <?php if ($voter['profile_photo']): ?>
                            <img src="../assets/uploads/voters/<?php echo $voter['profile_photo']; ?>" alt="Profile">
                        <?php else: ?>
                            <img src="../assets/images/default-avatar.png" alt="Default">
                        <?php endif; ?>
                    </div>
                    
                    <div class="photo-upload" onclick="document.getElementById('profile_photo').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Click to change photo / फोटो परिवर्तन गर्न क्लिक गर्नुहोस्</p>
                        <small>Max size: 5MB (JPG, PNG)</small>
                    </div>
                    <input type="file" id="profile_photo" name="profile_photo" accept="image/*" style="display: none;">
                </div>

                <!-- Name -->
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Full Name / पूरा नाम</label>
                    <input type="text" name="name" class="form-control" 
                           value="<?php echo htmlspecialchars($voter['name']); ?>" required>
                </div>

                <!-- Location -->
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-map"></i> Province / प्रदेश</label>
                        <select name="province" id="province" class="form-control" required>
                            <option value="">Select Province / प्रदेश छान्नुहोस्</option>
                            <?php foreach($provinces as $province): ?>
                                <option value="<?php echo $province['id']; ?>" 
                                    <?php echo $province['id'] == $voter['province_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($province['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-city"></i> District / जिल्ला</label>
                        <select name="district" id="district" class="form-control" required>
                            <option value="">Select District / जिल्ला छान्नुहोस्</option>
                            <?php foreach($districts as $district): ?>
                                <option value="<?php echo $district['id']; ?>" 
                                    <?php echo $district['id'] == $voter['district_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($district['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-map-pin"></i> Constituency / क्षेत्र</label>
                    <select name="constituency" id="constituency" class="form-control" required>
                        <option value="">Select Constituency / क्षेत्र छान्नुहोस्</option>
                        <?php foreach($constituencies as $constituency): ?>
                            <option value="<?php echo $constituency['id']; ?>" 
                                <?php echo $constituency['id'] == $voter['constituency_id'] ? 'selected' : ''; ?>>
                                Constituency <?php echo $constituency['constituency_number']; ?> / निर्वाचन क्षेत्र <?php echo $constituency['constituency_number']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Family -->
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-user-tie"></i> Father's Name / बुबाको नाम</label>
                        <input type="text" name="father_name" class="form-control" 
                               value="<?php echo htmlspecialchars($voter['father_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-user-tie"></i> Mother's Name / आमाको नाम</label>
                        <input type="text" name="mother_name" class="form-control" 
                               value="<?php echo htmlspecialchars($voter['mother_name']); ?>" required>
                    </div>
                </div>

                <!-- Contact -->
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Phone / फोन</label>
                        <input type="tel" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($voter['phone']); ?>" 
                               pattern="[9][0-9]{9}" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email / ईमेल</label>
                        <input type="email" class="form-control" 
                               value="<?php echo htmlspecialchars($voter['email']); ?>" disabled>
                        <small>Email cannot be changed / ईमेल परिवर्तन गर्न सकिँदैन</small>
                    </div>
                </div>

                <!-- Address -->
                <div class="form-group">
                    <label><i class="fas fa-home"></i> Address / ठेगाना</label>
                    <textarea name="address" class="form-control" rows="3" required><?php echo htmlspecialchars($voter['address']); ?></textarea>
                </div>

                <div class="actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes / परिवर्तन सुरक्षित गर्नुहोस्
                    </button>
                    <a href="profile.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Cancel / रद्द गर्नुहोस्
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Photo preview
        document.getElementById('profile_photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.querySelector('.current-photo img');
                    preview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Province change
        document.getElementById('province').addEventListener('change', function() {
            const provinceId = this.value;
            const districtSelect = document.getElementById('district');
            const constituencySelect = document.getElementById('constituency');
            
            districtSelect.innerHTML = '<option value="">Loading...</option>';
            constituencySelect.innerHTML = '<option value="">Select Constituency</option>';
            
            if (provinceId) {
                fetch(`../api/get_districts.php?province_id=${provinceId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            districtSelect.innerHTML = '<option value="">Select District</option>';
                            data.districts.forEach(district => {
                                const option = document.createElement('option');
                                option.value = district.id;
                                option.textContent = district.name;
                                districtSelect.appendChild(option);
                            });
                        }
                    });
            }
        });

        // District change
        document.getElementById('district').addEventListener('change', function() {
            const districtId = this.value;
            const constituencySelect = document.getElementById('constituency');
            
            constituencySelect.innerHTML = '<option value="">Loading...</option>';
            
            if (districtId) {
                fetch(`../api/get_constituencies.php?district_id=${districtId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            constituencySelect.innerHTML = '<option value="">Select Constituency</option>';
                            data.constituencies.forEach(cons => {
                                const option = document.createElement('option');
                                option.value = cons.id;
                                option.textContent = `Constituency ${cons.constituency_number}`;
                                constituencySelect.appendChild(option);
                            });
                        }
                    });
            }
        });
    </script>
</body>
</html>