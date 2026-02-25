<?php
require_once '../includes/auth.php';
Auth::requireAdmin();

$db = Database::getInstance()->getConnection();
$error = '';
$success = '';

// Fetch parties for dropdown
$parties = getParties();

// Fetch provinces for FPTP candidates
$provinces = getProvinces();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Debug: Check what's coming from form
    error_log("POST data: " . print_r($_POST, true));
    
    $party_id = intval($_POST['party_id'] ?? 0);
    $candidate_name = sanitize($_POST['candidate_name'] ?? '');
    $election_type = $_POST['election_type'] ?? '';
    
    // For FPTP, get constituency_id from form
    $constituency_id = null;
    if ($election_type == 'FPTP') {
        $constituency_id = !empty($_POST['constituency']) ? intval($_POST['constituency']) : null;
        
        // Validate constituency exists
        if ($constituency_id) {
            $check = $db->query("SELECT id FROM constituencies WHERE id = $constituency_id");
            if ($check->num_rows == 0) {
                $error = "Invalid constituency selected. Please select a valid constituency.";
            }
        } else {
            $error = "Please select a constituency for FPTP candidate.";
        }
    }
    
    // Validation
    if (!$party_id) {
        $error = "Please select a party.";
    } elseif (!$candidate_name) {
        $error = "Please enter candidate name.";
    } elseif (!$election_type) {
        $error = "Please select election type.";
    }
    
    // Handle photo upload
    $candidate_photo = '';
    if (empty($error) && isset($_FILES['candidate_photo']) && $_FILES['candidate_photo']['error'] == 0) {
        $upload = uploadImage($_FILES['candidate_photo'], 'candidates');
        if ($upload['success']) {
            $candidate_photo = $upload['filename'];
        } else {
            $error = $upload['message'];
        }
    }
    
    // Insert candidate
    if (empty($error)) {
        // Use NULL for constituency_id if not FPTP
        $final_constituency_id = ($election_type == 'FPTP') ? $constituency_id : null;
        
        $query = "INSERT INTO candidates (party_id, candidate_name, candidate_photo, election_type, constituency_id) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        
        if (!$stmt) {
            $error = "Database error: " . $db->error;
        } else {
            $stmt->bind_param("isssi", $party_id, $candidate_name, $candidate_photo, $election_type, $final_constituency_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Candidate added successfully';
                header("Location: manage_candidates.php");
                exit();
            } else {
                $error = 'Error: ' . $db->error . ' | SQL: ' . $query;
                error_log("Candidate insert error: " . $db->error);
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
    <title>Add Candidate - VoteNepal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
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
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .admin-sidebar {
            width: 280px;
            background: var(--dark);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h2 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
        }

        .sidebar-menu a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .sidebar-menu li.active a {
            background: var(--primary);
            color: white;
        }

        /* Main Content */
        .admin-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .content-header h1 {
            font-size: 24px;
            color: var(--dark);
        }

        /* Form Container */
        .form-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
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
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        select.form-control {
            cursor: pointer;
            background-color: white;
        }

        /* Location Fields */
        .location-fields {
            background: var(--light);
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            display: none;
        }

        .location-fields.show {
            display: block;
        }

        .location-fields h3 {
            margin-bottom: 20px;
            color: var(--dark);
            font-size: 18px;
        }

        /* Form Row */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        /* Photo Preview */
        .photo-preview {
            max-width: 150px;
            max-height: 150px;
            border-radius: 8px;
            margin-top: 10px;
            display: none;
            border: 3px solid var(--primary);
        }

        /* Help Text */
        .help-text {
            font-size: 12px;
            color: var(--gray);
            margin-top: 5px;
        }

        .help-text i {
            color: var(--primary);
            margin-right: 5px;
        }

        /* Form Actions - यहाँ BUTTONS छन् */
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: flex-start;
            border-top: 2px solid var(--light);
            padding-top: 30px;
        }

        /* Buttons */
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-family: 'Inter', sans-serif;
        }

        .btn-primary {
            background: var(--gradient);
            color: white;
            box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(50, 50, 93, 0.1), 0 3px 6px rgba(0, 0, 0, 0.08);
        }

        .btn-primary i {
            color: white;
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

        .btn-outline:hover i {
            color: white;
        }

        .btn i {
            font-size: 16px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-sidebar {
                width: 0;
                display: none;
            }
            
            .admin-content {
                margin-left: 0;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="sidebar-header">
                <h2>🗳️ VoteNepal</h2>
                <p>Admin Panel</p>
                <p style="font-size: 12px; opacity: 0.7;">व्यवस्थापक प्यानल</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="manage_voters.php"><i class="fas fa-users"></i> Voters</a></li>
                <li><a href="manage_parties.php"><i class="fas fa-flag"></i> Parties</a></li>
                <li class="active"><a href="manage_candidates.php"><i class="fas fa-user-tie"></i> Candidates</a></li>
                <li><a href="manage_provinces.php"><i class="fas fa-map"></i> Provinces</a></li>
                <li><a href="manage_districts.php"><i class="fas fa-city"></i> Districts</a></li>
                <li><a href="manage_constituencies.php"><i class="fas fa-map-pin"></i> Constituencies</a></li>
                <li><a href="view_results.php"><i class="fas fa-chart-bar"></i> Results</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="admin-content">
            <div class="content-header">
                <h1><i class="fas fa-user-plus" style="color: var(--primary); margin-right: 10px;"></i>Add New Candidate</h1>
                <a href="manage_candidates.php" class="btn btn-outline" style="padding: 10px 20px;">
                    <i class="fas fa-arrow-left"></i> Back to Candidates
                </a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="POST" enctype="multipart/form-data" id="candidateForm">
                    <!-- Party Selection -->
                    <div class="form-group">
                        <label><i class="fas fa-flag"></i> Political Party *</label>
                        <select name="party_id" id="party_id" class="form-control" required>
                            <option value="">-- Select Party / दल छान्नुहोस् --</option>
                            <?php foreach($parties as $party): ?>
                                <option value="<?php echo $party['id']; ?>">
                                    <?php echo htmlspecialchars($party['party_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Candidate Name -->
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Candidate Full Name *</label>
                        <input type="text" name="candidate_name" class="form-control" 
                               placeholder="Enter candidate name / उम्मेदवारको नाम" required>
                    </div>
                    
                    <!-- Election Type -->
                    <div class="form-group">
                        <label><i class="fas fa-vote-yea"></i> Election Type *</label>
                        <select name="election_type" id="election_type" class="form-control" required>
                            <option value="">-- Select Type --</option>
                            <option value="FPTP">FPTP (First Past The Post) / प्रत्यक्ष</option>
                            <option value="PR">PR (Proportional Representation) / समानुपातिक</option>
                        </select>
                    </div>
                    
                    <!-- FPTP Location Fields (Hidden by default) -->
                    <div id="fptpFields" class="location-fields">
                        <h3><i class="fas fa-map-marker-alt" style="color: var(--primary);"></i> Constituency Details / निर्वाचन क्षेत्र विवरण</h3>
                        
                        <div class="form-group">
                            <label><i class="fas fa-map"></i> Province *</label>
                            <select id="province" class="form-control">
                                <option value="">-- Select Province / प्रदेश छान्नुहोस् --</option>
                                <?php foreach($provinces as $province): ?>
                                    <option value="<?php echo $province['id']; ?>">
                                        <?php echo htmlspecialchars($province['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-city"></i> District *</label>
                            <select id="district" class="form-control" disabled>
                                <option value="">-- Select District / जिल्ला छान्नुहोस् --</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-map-pin"></i> Constituency *</label>
                            <select name="constituency" id="constituency" class="form-control" disabled>
                                <option value="">-- Select Constituency / क्षेत्र छान्नुहोस् --</option>
                            </select>
                            <div class="help-text">
                                <i class="fas fa-info-circle"></i>
                                Select the constituency where this candidate will contest
                            </div>
                        </div>
                    </div>
                    
                    <!-- Photo Upload -->
                    <div class="form-group">
                        <label><i class="fas fa-camera"></i> Candidate Photo</label>
                        <input type="file" name="candidate_photo" id="candidate_photo" 
                               class="form-control" accept="image/*">
                        <img id="photoPreview" class="photo-preview" src="#" alt="Preview">
                        <div class="help-text">
                            <i class="fas fa-info-circle"></i>
                            Allowed: JPG, PNG, GIF. Max size: 5MB
                        </div>
                    </div>
                    
                    <!-- FORM ACTIONS - यहाँ ADD र CANCEL बटन छन् -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Add Candidate / उम्मेदवार थप्नुहोस्
                        </button>
                        <a href="manage_candidates.php" class="btn btn-outline">
                            <i class="fas fa-times"></i> Cancel / रद्द गर्नुहोस्
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle FPTP fields based on election type
        document.getElementById('election_type').addEventListener('change', function() {
            const fptpFields = document.getElementById('fptpFields');
            const constituencySelect = document.getElementById('constituency');
            
            if (this.value === 'FPTP') {
                fptpFields.classList.add('show');
                // Make constituency required for FPTP
                constituencySelect.required = true;
            } else {
                fptpFields.classList.remove('show');
                constituencySelect.required = false;
                // Clear values
                document.getElementById('province').value = '';
                document.getElementById('district').innerHTML = '<option value="">-- Select District / जिल्ला छान्नुहोस् --</option>';
                document.getElementById('constituency').innerHTML = '<option value="">-- Select Constituency / क्षेत्र छान्नुहोस् --</option>';
                document.getElementById('district').disabled = true;
                document.getElementById('constituency').disabled = true;
            }
        });
        
        // Province change - Load districts
        document.getElementById('province').addEventListener('change', function() {
            const provinceId = this.value;
            const districtSelect = document.getElementById('district');
            const constituencySelect = document.getElementById('constituency');
            
            // Reset district and constituency
            districtSelect.innerHTML = '<option value="">Loading districts / जिल्ला लोड हुँदैछ...</option>';
            constituencySelect.innerHTML = '<option value="">-- Select Constituency / क्षेत्र छान्नुहोस् --</option>';
            districtSelect.disabled = false;
            constituencySelect.disabled = true;
            
            if (provinceId) {
                // Fetch districts
                fetch(`../api/get_districts.php?province_id=${provinceId}`)
                    .then(response => response.json())
                    .then(data => {
                        console.log('Districts data:', data);
                        
                        if (data.success && data.districts.length > 0) {
                            districtSelect.innerHTML = '<option value="">-- Select District / जिल्ला छान्नुहोस् --</option>';
                            data.districts.forEach(district => {
                                const option = document.createElement('option');
                                option.value = district.id;
                                option.textContent = district.name_nepali 
                                    ? `${district.name} (${district.name_nepali})` 
                                    : district.name;
                                districtSelect.appendChild(option);
                            });
                            districtSelect.disabled = false;
                        } else {
                            districtSelect.innerHTML = '<option value="">No districts found / जिल्ला फेला परेन</option>';
                            districtSelect.disabled = true;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        districtSelect.innerHTML = '<option value="">Error loading districts / जिल्ला लोड गर्न सकिएन</option>';
                        districtSelect.disabled = true;
                    });
            }
        });
        
        // District change - Load constituencies
        document.getElementById('district').addEventListener('change', function() {
            const districtId = this.value;
            const constituencySelect = document.getElementById('constituency');
            
            // Reset constituency
            constituencySelect.innerHTML = '<option value="">Loading constituencies / क्षेत्र लोड हुँदैछ...</option>';
            constituencySelect.disabled = false;
            
            if (districtId) {
                // Fetch constituencies
                fetch(`../api/get_constituencies.php?district_id=${districtId}`)
                    .then(response => response.json())
                    .then(data => {
                        console.log('Constituencies data:', data);
                        
                        if (data.success && data.constituencies.length > 0) {
                            constituencySelect.innerHTML = '<option value="">-- Select Constituency / क्षेत्र छान्नुहोस् --</option>';
                            data.constituencies.forEach(cons => {
                                const option = document.createElement('option');
                                option.value = cons.id;
                                option.textContent = `Constituency ${cons.constituency_number} / निर्वाचन क्षेत्र ${cons.constituency_number}`;
                                constituencySelect.appendChild(option);
                            });
                            constituencySelect.disabled = false;
                        } else {
                            constituencySelect.innerHTML = '<option value="">No constituencies found / क्षेत्र फेला परेन</option>';
                            constituencySelect.disabled = true;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        constituencySelect.innerHTML = '<option value="">Error loading constituencies / क्षेत्र लोड गर्न सकिएन</option>';
                        constituencySelect.disabled = true;
                    });
            }
        });
        
        // Photo preview
        document.getElementById('candidate_photo').addEventListener('change', function(e) {
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
        
        // Form validation
        document.getElementById('candidateForm').addEventListener('submit', function(e) {
            const electionType = document.getElementById('election_type').value;
            
            if (electionType === 'FPTP') {
                const constituency = document.getElementById('constituency').value;
                if (!constituency) {
                    e.preventDefault();
                    alert('Please select a constituency for FPTP candidate / कृपया FPTP उम्मेदवारको लागि क्षेत्र चयन गर्नुहोस्');
                }
            }
        });
    </script>
</body>
</html>