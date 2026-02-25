<?php
require_once '../includes/auth.php';
Auth::requireAdmin();

$db = Database::getInstance()->getConnection();

// Handle add constituency
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $constituency_number = intval($_POST['constituency_number']);
        $district_id = intval($_POST['district_id']);
        
        // Check if constituency number already exists in this district
        $check = $db->prepare("SELECT id FROM constituencies WHERE district_id = ? AND constituency_number = ?");
        $check->bind_param("ii", $district_id, $constituency_number);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $_SESSION['error'] = 'Constituency number already exists in this district';
        } else {
            $query = "INSERT INTO constituencies (constituency_number, district_id) VALUES (?, ?)";
            $stmt = $db->prepare($query);
            $stmt->bind_param("ii", $constituency_number, $district_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Constituency added successfully';
            } else {
                $_SESSION['error'] = 'Error: ' . $db->error;
            }
        }
    } elseif ($_POST['action'] == 'edit') {
        $id = intval($_POST['id']);
        $constituency_number = intval($_POST['constituency_number']);
        $district_id = intval($_POST['district_id']);
        
        $query = "UPDATE constituencies SET constituency_number = ?, district_id = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("iii", $constituency_number, $district_id, $id);
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Constituency updated successfully';
        } else {
            $_SESSION['error'] = 'Error: ' . $db->error;
        }
    }
    redirect('admin/manage_constituencies.php');
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Check if constituency has candidates
    $check = $db->query("SELECT COUNT(*) as total FROM candidates WHERE constituency_id = $id");
    $count = $check->fetch_assoc()['total'];
    
    if ($count > 0) {
        $_SESSION['error'] = 'Cannot delete constituency with existing candidates';
    } else {
        $query = "DELETE FROM constituencies WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Constituency deleted successfully';
        } else {
            $_SESSION['error'] = 'Error: ' . $db->error;
        }
    }
    redirect('admin/manage_constituencies.php');
}

// Get all constituencies with details
$constituencies = $db->query("
    SELECT c.*, d.name as district_name, p.name as province_name,
           (SELECT COUNT(*) FROM candidates WHERE constituency_id = c.id) as candidate_count
    FROM constituencies c
    JOIN districts d ON c.district_id = d.id
    JOIN provinces p ON d.province_id = p.id
    ORDER BY p.name, d.name, c.constituency_number
");

// Get provinces for filter
$provinces = getProvinces();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Constituencies - VoteNepal</title>
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
                <li><a href="manage_candidates.php">👤 Manage Candidates</a></li>
                <li><a href="manage_provinces.php">🗺️ Manage Provinces</a></li>
                <li><a href="manage_districts.php">🏘️ Manage Districts</a></li>
                <li class="active"><a href="manage_constituencies.php">📍 Manage Constituencies</a></li>
                <li><a href="view_results.php">📊 View Results</a></li>
                <li><a href="logout.php">🚪 Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="admin-content">
            <div class="content-header">
                <h1>Manage Constituencies</h1>
                <button class="btn btn-primary" onclick="showAddModal()">+ Add Constituency</button>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <!-- Filter by Province -->
            <div class="filter-section" style="margin-bottom: 20px;">
                <select id="provinceFilter" onchange="filterByProvince()">
                    <option value="">All Provinces</option>
                    <?php foreach($provinces as $province): ?>
                        <option value="<?php echo $province['id']; ?>">
                            <?php echo htmlspecialchars($province['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="data-table-container">
                <table class="data-table" id="constituenciesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Constituency</th>
                            <th>District</th>
                            <th>Province</th>
                            <th>Candidates</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($constituency = $constituencies->fetch_assoc()): ?>
                        <tr data-province-id="<?php echo $constituency['province_id'] ?? ''; ?>">
                            <td><?php echo $constituency['id']; ?></td>
                            <td><strong>Constituency <?php echo $constituency['constituency_number']; ?></strong></td>
                            <td><?php echo htmlspecialchars($constituency['district_name']); ?></td>
                            <td><?php echo htmlspecialchars($constituency['province_name']); ?></td>
                            <td>
                                <span class="badge badge-info"><?php echo $constituency['candidate_count']; ?> candidates</span>
                            </td>
                            <td><?php echo date('d M Y', strtotime($constituency['created_at'])); ?></td>
                            <td class="actions">
                                <button class="btn-small" onclick="editConstituency(<?php echo $constituency['id']; ?>, <?php echo $constituency['constituency_number']; ?>, <?php echo $constituency['district_id']; ?>)">✏️ Edit</button>
                                <a href="?delete=<?php echo $constituency['id']; ?>" 
                                   class="btn-small delete" 
                                   onclick="return confirm('Delete this constituency?')">🗑️ Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Add Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h3>Add New Constituency</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Province:</label>
                    <select id="add_province" required onchange="loadDistricts('add')">
                        <option value="">Select Province</option>
                        <?php foreach($provinces as $province): ?>
                            <option value="<?php echo $province['id']; ?>">
                                <?php echo htmlspecialchars($province['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>District:</label>
                    <select id="add_district" name="district_id" required disabled>
                        <option value="">Select District</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Constituency Number:</label>
                    <input type="number" name="constituency_number" required min="1" placeholder="e.g., 1">
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">Add Constituency</button>
                    <button type="button" class="btn btn-outline" onclick="hideAddModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3>Edit Constituency</h3>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Province:</label>
                    <select id="edit_province" required onchange="loadDistricts('edit')">
                        <option value="">Select Province</option>
                        <?php foreach($provinces as $province): ?>
                            <option value="<?php echo $province['id']; ?>">
                                <?php echo htmlspecialchars($province['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>District:</label>
                    <select id="edit_district" name="district_id" required>
                        <option value="">Select District</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Constituency Number:</label>
                    <input type="number" name="constituency_number" id="edit_number" required min="1">
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">Update Constituency</button>
                    <button type="button" class="btn btn-outline" onclick="hideEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function showAddModal() {
        document.getElementById('addModal').style.display = 'block';
    }
    function hideAddModal() {
        document.getElementById('addModal').style.display = 'none';
    }
    
    function editConstituency(id, number, districtId) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_number').value = number;
        
        // First, get the province and district info
        fetch(`../api/get_district_info.php?district_id=${districtId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('edit_province').value = data.province_id;
                    loadDistricts('edit', districtId);
                }
            });
        
        document.getElementById('editModal').style.display = 'block';
    }
    function hideEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }
    
    function loadDistricts(type, selectedDistrict = null) {
        const provinceId = type === 'add' 
            ? document.getElementById('add_province').value 
            : document.getElementById('edit_province').value;
        
        const districtSelect = type === 'add' 
            ? document.getElementById('add_district') 
            : document.getElementById('edit_district');
        
        districtSelect.innerHTML = '<option value="">Select District</option>';
        districtSelect.disabled = true;
        
        if (provinceId) {
            fetch(`../api/get_districts.php?province_id=${provinceId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        data.districts.forEach(district => {
                            const option = document.createElement('option');
                            option.value = district.id;
                            option.textContent = district.name;
                            if (selectedDistrict && district.id == selectedDistrict) {
                                option.selected = true;
                            }
                            districtSelect.appendChild(option);
                        });
                        districtSelect.disabled = false;
                    }
                });
        }
    }
    
    function filterByProvince() {
        const provinceId = document.getElementById('provinceFilter').value;
        const rows = document.querySelectorAll('#constituenciesTable tbody tr');
        
        rows.forEach(row => {
            if (!provinceId || row.dataset.provinceId === provinceId) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    </script>
</body>
</html>