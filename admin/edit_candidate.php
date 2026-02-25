<?php
require_once '../includes/auth.php';
Auth::requireAdmin();

$db = Database::getInstance()->getConnection();
$error = '';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch candidate details with all joins
$query = "SELECT c.*, p.party_name, cn.id as constituency_id, cn.constituency_number,
          d.id as district_id, d.name as district_name,
          pr.id as province_id, pr.name as province_name
          FROM candidates c 
          JOIN parties p ON c.party_id = p.id 
          LEFT JOIN constituencies cn ON c.constituency_id = cn.id 
          LEFT JOIN districts d ON cn.district_id = d.id 
          LEFT JOIN provinces pr ON d.province_id = pr.id 
          WHERE c.id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$candidate = $result->fetch_assoc();

if (!$candidate) {
    $_SESSION['error'] = 'Candidate not found';
    redirect('admin/manage_candidates.php');
}

// Fetch parties for dropdown
$parties = getParties();

// Fetch provinces
$provinces = getProvinces();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $party_id = intval($_POST['party_id']);
    $candidate_name = sanitize($_POST['candidate_name']);
    $election_type = $_POST['election_type'];
    $constituency_id = ($election_type == 'FPTP') ? intval($_POST['constituency']) : null;
    $candidate_photo = $candidate['candidate_photo'];
    
    // Handle photo upload
    if (isset($_FILES['candidate_photo']) && $_FILES['candidate_photo']['error'] == 0) {
        $upload = uploadImage($_FILES['candidate_photo'], 'candidates', $candidate['candidate_photo']);
        if ($upload['success']) {
            $candidate_photo = $upload['filename'];
        } else {
            $error = $upload['message'];
        }
    }
    
    if (empty($error)) {
        $query = "UPDATE candidates SET party_id = ?, candidate_name = ?, candidate_photo = ?, 
                  election_type = ?, constituency_id = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("isssii", $party_id, $candidate_name, $candidate_photo, 
                         $election_type, $constituency_id, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Candidate updated successfully';
            redirect('admin/manage_candidates.php');
        } else {
            $error = 'Error: ' . $db->error;
        }
    }
}

// Get districts and constituencies if FPTP
$districts = [];
$constituencies = [];
if ($candidate['province_id']) {
    $districts = getDistricts($candidate['province_id']);
}
if ($candidate['district_id']) {
    $constituencies = getConstituencies($candidate['district_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Candidate - VoteNepal</title>
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
                <li><a href="manage_voters.php">👥 Manage Voters</a></li>
                <li><a href="manage_parties.php">🎯 Manage Parties</a></li>
                <li class="active"><a href="manage_candidates.php">👤 Manage Candidates</a></li>
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
                <h1>Edit Candidate: <?php echo htmlspecialchars($candidate['candidate_name']); ?></h1>
                <a href="manage_candidates.php" class="btn btn-outline">← Back to Candidates</a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="POST" enctype="multipart/form-data" class="admin-form">
                    <div class="form-group">
                        <label for="party_id">Political Party *</label>
                        <select id="party_id" name="party_id" required>
                            <?php foreach($parties as $party): ?>
                                <option value="<?php echo $party['id']; ?>" 
                                    <?php echo ($party['id'] == $candidate['party_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($party['party_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="candidate_name">Candidate Full Name *</label>
                        <input type="text" id="candidate_name" name="candidate_name" required 
                               value="<?php echo htmlspecialchars($candidate['candidate_name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="election_type">Election Type *</label>
                        <select id="election_type" name="election_type" required>
                            <option value="FPTP" <?php echo ($candidate['election_type'] == 'FPTP') ? 'selected' : ''; ?>>
                                FPTP (First Past The Post)
                            </option>
                            <option value="PR" <?php echo ($candidate['election_type'] == 'PR') ? 'selected' : ''; ?>>
                                PR (Proportional Representation)
                            </option>
                        </select>
                    </div>
                    
                    <div id="fptpFields" style="<?php echo $candidate['election_type'] == 'FPTP' ? 'display:block;' : 'display:none;'; ?>">
                        <div class="form-group">
                            <label for="province">Province</label>
                            <select id="province" name="province">
                                <option value="">Select Province</option>
                                <?php foreach($provinces as $province): ?>
                                    <option value="<?php echo $province['id']; ?>" 
                                        <?php echo ($province['id'] == $candidate['province_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($province['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="district">District</label>
                            <select id="district" name="district" <?php echo $candidate['district_id'] ? '' : 'disabled'; ?>>
                                <option value="">Select District</option>
                                <?php foreach($districts as $district): ?>
                                    <option value="<?php echo $district['id']; ?>" 
                                        <?php echo ($district['id'] == $candidate['district_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($district['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="constituency">Constituency</label>
                            <select id="constituency" name="constituency" <?php echo $candidate['constituency_id'] ? '' : 'disabled'; ?>>
                                <option value="">Select Constituency</option>
                                <?php foreach($constituencies as $constituency): ?>
                                    <option value="<?php echo $constituency['id']; ?>" 
                                        <?php echo ($constituency['id'] == $candidate['constituency_id']) ? 'selected' : ''; ?>>
                                        Constituency <?php echo $constituency['constituency_number']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Current Photo</label>
                        <?php if ($candidate['candidate_photo']): ?>
                            <div class="current-image">
                                <img src="../assets/uploads/candidates/<?php echo $candidate['candidate_photo']; ?>" 
                                     alt="Current Photo" style="max-width: 150px; max-height: 150px; border-radius: 5px;">
                            </div>
                        <?php else: ?>
                            <p>No photo uploaded</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="candidate_photo">New Photo (leave empty to keep current)</label>
                        <input type="file" id="candidate_photo" name="candidate_photo" accept="image/*">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Candidate</button>
                        <a href="manage_candidates.php" class="btn btn-outline">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    document.getElementById('election_type').addEventListener('change', function() {
        const fptpFields = document.getElementById('fptpFields');
        if (this.value === 'FPTP') {
            fptpFields.style.display = 'block';
        } else {
            fptpFields.style.display = 'none';
        }
    });
    
    // Province to District cascade
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
    
    // District to Constituency cascade
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