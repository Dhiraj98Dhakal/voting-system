<?php
require_once '../includes/auth.php';
Auth::requireAdmin();

$db = Database::getInstance()->getConnection();

// Get filter province
$filter_province = isset($_GET['province_id']) ? intval($_GET['province_id']) : 0;

// Handle Add District
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $name = sanitize($_POST['name']);
        $name_np = sanitize($_POST['name_np'] ?? '');
        $province_id = intval($_POST['province_id']);
        $area = sanitize($_POST['area'] ?? '');
        $population = intval($_POST['population'] ?? 0);
        $website = sanitize($_POST['website'] ?? '');
        
        $query = "INSERT INTO districts (name, name_nepali, province_id, area, population, website) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->bind_param("ssisis", $name, $name_np, $province_id, $area, $population, $website);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "जिल्ला सफलतापूर्वक थपियो | District added successfully";
        } else {
            $_SESSION['error'] = "Error: " . $db->error;
        }
    }
    // Handle Edit District
    elseif ($_POST['action'] == 'edit') {
        $id = intval($_POST['id']);
        $name = sanitize($_POST['name']);
        $name_np = sanitize($_POST['name_np'] ?? '');
        $province_id = intval($_POST['province_id']);
        $area = sanitize($_POST['area'] ?? '');
        $population = intval($_POST['population'] ?? 0);
        $website = sanitize($_POST['website'] ?? '');
        
        $query = "UPDATE districts SET name = ?, name_nepali = ?, province_id = ?, 
                  area = ?, population = ?, website = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("ssisisi", $name, $name_np, $province_id, $area, $population, $website, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "जिल्ला सफलतापूर्वक अपडेट गरियो | District updated successfully";
        } else {
            $_SESSION['error'] = "Error: " . $db->error;
        }
    }
    
    redirect('admin/manage_districts.php' . ($filter_province ? '?province_id=' . $filter_province : ''));
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Check if district has constituencies
    $check = $db->query("SELECT COUNT(*) as total FROM constituencies WHERE district_id = $id");
    $count = $check->fetch_assoc()['total'];
    
    if ($count > 0) {
        $_SESSION['error'] = "यो जिल्लामा निर्वाचन क्षेत्रहरू छन्। पहिले क्षेत्रहरू मेटाउनुहोस् | Cannot delete district with existing constituencies";
    } else {
        $query = "DELETE FROM districts WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "जिल्ला सफलतापूर्वक मेटाइयो | District deleted successfully";
        } else {
            $_SESSION['error'] = "Error: " . $db->error;
        }
    }
    redirect('admin/manage_districts.php' . ($filter_province ? '?province_id=' . $filter_province : ''));
}

// Get all provinces for filter
$provinces = $db->query("SELECT * FROM provinces ORDER BY id");

// Build query based on filter
$query = "SELECT d.*, p.name as province_name, p.id as province_id,
          (SELECT COUNT(*) FROM constituencies WHERE district_id = d.id) as constituency_count
          FROM districts d 
          JOIN provinces p ON d.province_id = p.id";
          
if ($filter_province) {
    $query .= " WHERE d.province_id = $filter_province";
}

$query .= " ORDER BY p.id, d.name";
$districts = $db->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Districts - VoteNepal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .districts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .district-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            border: 1px solid var(--border-color);
        }
        
        .district-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .district-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .district-name {
            font-size: 20px;
            font-weight: 600;
        }
        
        .district-name-np {
            font-size: 16px;
            opacity: 0.9;
            margin-top: 5px;
        }
        
        .province-badge {
            background: rgba(255,255,255,0.2);
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
        }
        
        .district-body {
            padding: 15px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px dashed var(--border-color);
        }
        
        .info-label {
            color: #666;
            font-weight: 500;
        }
        
        .info-value {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .district-footer {
            background: var(--light-color);
            padding: 15px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-icon {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }
        
        .btn-edit {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-delete {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-view {
            background: var(--success-color);
            color: white;
        }
        
        .filter-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-select {
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            min-width: 250px;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            width: 600px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 10px;
        }
        
        .close {
            font-size: 30px;
            cursor: pointer;
            color: #999;
        }
        
        .close:hover {
            color: var(--danger-color);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        .nepali-text {
            font-family: 'Nepali', 'Preeti', sans-serif;
        }
        
        @media (max-width: 768px) {
            .districts-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 95%;
            }
            
            .form-row {
                grid-template-columns: 1fr;
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
                <p class="nepali-text">व्यवस्थापक प्यानल</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">📊 Dashboard | ड्यासबोर्ड</a></li>
                <li><a href="manage_voters.php">👥 Voters | मतदाताहरू</a></li>
                <li><a href="manage_parties.php">🎯 Parties | दलहरू</a></li>
                <li><a href="manage_candidates.php">👤 Candidates | उम्मेदवारहरू</a></li>
                <li><a href="manage_provinces.php">🗺️ Provinces | प्रदेशहरू</a></li>
                <li class="active"><a href="manage_districts.php">🏘️ Districts | जिल्लाहरू</a></li>
                <li><a href="manage_constituencies.php">📍 Constituencies | निर्वाचन क्षेत्रहरू</a></li>
                <li><a href="view_results.php">📊 Results | नतिजा</a></li>
                <li><a href="logout.php">🚪 Logout | बहिर्गमन</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="admin-content">
            <div class="content-header">
                <div>
                    <h1>Manage Districts | जिल्ला व्यवस्थापन</h1>
                    <p>नेपालका ७७ वटै जिल्लाहरूको व्यवस्थापन गर्नुहोस्</p>
                </div>
                <button class="btn btn-primary" onclick="showAddModal()">
                    + Add District | जिल्ला थप्नुहोस्
                </button>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <?php
            $total_districts = $db->query("SELECT COUNT(*) as total FROM districts")->fetch_assoc()['total'];
            $total_constituencies = $db->query("SELECT COUNT(*) as total FROM constituencies")->fetch_assoc()['total'];
            ?>
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_districts; ?></div>
                    <div class="stat-label">Total Districts | कुल जिल्ला</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_constituencies; ?></div>
                    <div class="stat-label">Total Constituencies | कुल निर्वाचन क्षेत्र</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">7</div>
                    <div class="stat-label">Provinces | प्रदेश</div>
                </div>
            </div>
            
            <!-- Filter Bar -->
            <div class="filter-bar">
                <label for="provinceFilter">Filter by Province | प्रदेश अनुसार छान्नुहोस्:</label>
                <select id="provinceFilter" class="filter-select" onchange="filterByProvince()">
                    <option value="">All Provinces | सबै प्रदेश</option>
                    <?php 
                    $provinces->data_seek(0);
                    while($province = $provinces->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $province['id']; ?>" 
                            <?php echo ($filter_province == $province['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($province['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                
                <input type="text" id="searchInput" class="filter-select" 
                       placeholder="Search districts... | जिल्ला खोज्नुहोस्...">
            </div>
            
            <!-- Districts Grid -->
            <div class="districts-grid" id="districtsGrid">
                <?php while($district = $districts->fetch_assoc()): ?>
                <div class="district-card" data-province="<?php echo $district['province_id']; ?>" 
                     data-name="<?php echo strtolower($district['name']); ?>">
                    <div class="district-header">
                        <div>
                            <div class="district-name"><?php echo htmlspecialchars($district['name']); ?></div>
                            <?php if (!empty($district['name_nepali'])): ?>
                                <div class="district-name-np nepali-text"><?php echo htmlspecialchars($district['name_nepali']); ?></div>
                            <?php endif; ?>
                        </div>
                        <span class="province-badge">P-<?php echo $district['province_id']; ?></span>
                    </div>
                    
                    <div class="district-body">
                        <div class="info-row">
                            <span class="info-label">Province | प्रदेश:</span>
                            <span class="info-value"><?php echo htmlspecialchars($district['province_name']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Constituencies | क्षेत्रहरू:</span>
                            <span class="info-value"><?php echo $district['constituency_count']; ?></span>
                        </div>
                        <?php if (!empty($district['area'])): ?>
                        <div class="info-row">
                            <span class="info-label">Area | क्षेत्रफल:</span>
                            <span class="info-value"><?php echo $district['area']; ?> km²</span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($district['population'])): ?>
                        <div class="info-row">
                            <span class="info-label">Population | जनसंख्या:</span>
                            <span class="info-value"><?php echo number_format($district['population']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($district['website'])): ?>
                        <div class="info-row">
                            <span class="info-label">Website:</span>
                            <span class="info-value"><a href="<?php echo $district['website']; ?>" target="_blank">Visit</a></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="district-footer">
                        <button class="btn-icon btn-edit" onclick='editDistrict(<?php echo json_encode($district); ?>)'>
                            ✏️ Edit | सम्पादन
                        </button>
                        <a href="manage_constituencies.php?district_id=<?php echo $district['id']; ?>" 
                           class="btn-icon btn-view">
                            👁️ Constituencies | क्षेत्रहरू
                        </a>
                        <a href="?delete=<?php echo $district['id']; ?><?php echo $filter_province ? '&province_id=' . $filter_province : ''; ?>" 
                           class="btn-icon btn-delete" 
                           onclick="return confirm('के तपाईं यो जिल्ला मेटाउन चाहनुहुन्छ? \nAre you sure you want to delete this district?')">
                            🗑️ Delete | मेटाउनुहोस्
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    
    <!-- Add District Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New District | नयाँ जिल्ला थप्नुहोस्</h3>
                <span class="close" onclick="hideAddModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label>Province | प्रदेश <span style="color: red;">*</span></label>
                    <select name="province_id" required>
                        <option value="">Select Province | प्रदेश छान्नुहोस्</option>
                        <?php 
                        $provinces->data_seek(0);
                        while($province = $provinces->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $province['id']; ?>">
                                <?php echo htmlspecialchars($province['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>District Name (English) | अङ्ग्रेजीमा नाम <span style="color: red;">*</span></label>
                        <input type="text" name="name" required placeholder="e.g., Kathmandu">
                    </div>
                    
                    <div class="form-group">
                        <label>District Name (Nepali) | नेपालीमा नाम</label>
                        <input type="text" name="name_np" class="nepali-text" placeholder="जस्तै: काठमाडौं">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Area (km²) | क्षेत्रफल</label>
                        <input type="text" name="area" placeholder="e.g., 395">
                    </div>
                    
                    <div class="form-group">
                        <label>Population | जनसंख्या</label>
                        <input type="number" name="population" placeholder="e.g., 2000000">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Website | वेबसाइट</label>
                    <input type="url" name="website" placeholder="https://...">
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">➕ Add District | थप्नुहोस्</button>
                    <button type="button" class="btn btn-secondary" onclick="hideAddModal()">❌ Cancel | रद्द गर्नुहोस्</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit District Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit District | जिल्ला सम्पादन गर्नुहोस्</h3>
                <span class="close" onclick="hideEditModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label>Province | प्रदेश <span style="color: red;">*</span></label>
                    <select name="province_id" id="edit_province_id" required>
                        <option value="">Select Province | प्रदेश छान्नुहोस्</option>
                        <?php 
                        $provinces->data_seek(0);
                        while($province = $provinces->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $province['id']; ?>">
                                <?php echo htmlspecialchars($province['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>District Name (English) | अङ्ग्रेजीमा नाम <span style="color: red;">*</span></label>
                        <input type="text" name="name" id="edit_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>District Name (Nepali) | नेपालीमा नाम</label>
                        <input type="text" name="name_np" id="edit_name_np" class="nepali-text">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Area (km²) | क्षेत्रफल</label>
                        <input type="text" name="area" id="edit_area">
                    </div>
                    
                    <div class="form-group">
                        <label>Population | जनसंख्या</label>
                        <input type="number" name="population" id="edit_population">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Website | वेबसाइट</label>
                    <input type="url" name="website" id="edit_website">
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">🔄 Update | अपडेट गर्नुहोस्</button>
                    <button type="button" class="btn btn-secondary" onclick="hideEditModal()">❌ Cancel | रद्द गर्नुहोस्</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Show Add Modal
        function showAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        // Hide Add Modal
        function hideAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }
        
        // Show Edit Modal with data
        function editDistrict(district) {
            document.getElementById('edit_id').value = district.id;
            document.getElementById('edit_name').value = district.name;
            document.getElementById('edit_name_np').value = district.name_nepali || '';
            document.getElementById('edit_province_id').value = district.province_id;
            document.getElementById('edit_area').value = district.area || '';
            document.getElementById('edit_population').value = district.population || 0;
            document.getElementById('edit_website').value = district.website || '';
            
            document.getElementById('editModal').style.display = 'block';
        }
        
        // Hide Edit Modal
        function hideEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Filter by province
        function filterByProvince() {
            let provinceId = document.getElementById('provinceFilter').value;
            window.location.href = 'manage_districts.php' + (provinceId ? '?province_id=' + provinceId : '');
        }
        
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let searchText = this.value.toLowerCase();
            let cards = document.querySelectorAll('.district-card');
            
            cards.forEach(card => {
                let name = card.getAttribute('data-name');
                if (name.includes(searchText) || searchText === '') {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>