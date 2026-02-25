<?php
require_once '../includes/auth.php';
Auth::requireAdmin();

$db = Database::getInstance()->getConnection();

// Handle Add Province
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $name = sanitize($_POST['name']);
        $name_np = sanitize($_POST['name_np'] ?? '');
        
        $query = "INSERT INTO provinces (name, name_nepali) VALUES (?, ?)";
        $stmt = $db->prepare($query);
        $stmt->bind_param("ss", $name, $name_np);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "प्रदेश सफलतापूर्वक थपियो | Province added successfully";
        } else {
            $_SESSION['error'] = "Error: " . $db->error;
        }
    }
    // Handle Edit Province
    elseif ($_POST['action'] == 'edit') {
        $id = intval($_POST['id']);
        $name = sanitize($_POST['name']);
        $name_np = sanitize($_POST['name_np'] ?? '');
        
        $query = "UPDATE provinces SET name = ?, name_nepali = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("ssi", $name, $name_np, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "प्रदेश सफलतापूर्वक अपडेट गरियो | Province updated successfully";
        } else {
            $_SESSION['error'] = "Error: " . $db->error;
        }
    }
    
    redirect('admin/manage_provinces.php');
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Check if province has districts
    $check = $db->query("SELECT COUNT(*) as total FROM districts WHERE province_id = $id");
    $count = $check->fetch_assoc()['total'];
    
    if ($count > 0) {
        $_SESSION['error'] = "यो प्रदेशमा जिल्लाहरू छन्। पहिले जिल्लाहरू मेटाउनुहोस् | Cannot delete province with existing districts";
    } else {
        $query = "DELETE FROM provinces WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "प्रदेश सफलतापूर्वक मेटाइयो | Province deleted successfully";
        } else {
            $_SESSION['error'] = "Error: " . $db->error;
        }
    }
    redirect('admin/manage_provinces.php');
}

// Get all provinces
$provinces = $db->query("SELECT * FROM provinces ORDER BY id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Provinces - VoteNepal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .province-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .province-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            border-left: 4px solid var(--primary-color);
        }
        
        .province-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .province-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
        }
        
        .province-number {
            background: var(--primary-color);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }
        
        .province-name {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .province-name-np {
            font-size: 18px;
            color: #666;
            margin: 5px 0;
        }
        
        .province-stats {
            display: flex;
            gap: 15px;
            margin: 15px 0;
        }
        
        .stat-badge {
            background: var(--light-color);
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 13px;
        }
        
        .province-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-icon {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
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
            width: 500px;
            margin: 100px auto;
            padding: 30px;
            border-radius: 10px;
            position: relative;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 10px;
        }
        
        .modal-header h3 {
            font-size: 24px;
            color: var(--dark-color);
        }
        
        .close {
            font-size: 30px;
            cursor: pointer;
            color: #999;
        }
        
        .close:hover {
            color: var(--danger-color);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-group input:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-secondary {
            background: var(--light-color);
            color: var(--text-color);
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .header-actions {
            display: flex;
            gap: 15px;
        }
        
        .search-box {
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            width: 300px;
        }
        
        .nepali-text {
            font-family: 'Nepali', 'Preeti', sans-serif;
        }
        
        @media (max-width: 768px) {
            .province-list {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 90%;
                margin: 50px auto;
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
                <li class="active"><a href="manage_provinces.php">🗺️ Provinces | प्रदेशहरू</a></li>
                <li><a href="manage_districts.php">🏘️ Districts | जिल्लाहरू</a></li>
                <li><a href="manage_constituencies.php">📍 Constituencies | निर्वाचन क्षेत्रहरू</a></li>
                <li><a href="view_results.php">📊 Results | नतिजा</a></li>
                <li><a href="logout.php">🚪 Logout | बहिर्गमन</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="admin-content">
            <div class="content-header">
                <div>
                    <h1>Manage Provinces | प्रदेश व्यवस्थापन</h1>
                    <p>नेपालका ७ वटै प्रदेशहरूको व्यवस्थापन गर्नुहोस्</p>
                </div>
                <div class="header-actions">
                    <input type="text" id="searchInput" class="search-box" 
                           placeholder="Search provinces... | प्रदेश खोज्नुहोस्...">
                    <button class="btn btn-primary" onclick="showAddModal()">
                        + Add Province | प्रदेश थप्नुहोस्
                    </button>
                </div>
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
            
            <!-- Provinces Grid -->
            <div class="province-list" id="provinceList">
                <?php 
                $province_np = [
                    1 => 'प्रदेश नं. १',
                    2 => 'प्रदेश नं. २',
                    3 => 'बागमती प्रदेश',
                    4 => 'गण्डकी प्रदेश',
                    5 => 'लुम्बिनी प्रदेश',
                    6 => 'कर्णाली प्रदेश',
                    7 => 'सुदूरपश्चिम प्रदेश'
                ];
                
                while($province = $provinces->fetch_assoc()): 
                    // Get district count
                    $district_count = $db->query("SELECT COUNT(*) as total FROM districts WHERE province_id = " . $province['id'])->fetch_assoc()['total'];
                    
                    // Get constituency count
                    $constituency_count = $db->query("
                        SELECT COUNT(*) as total 
                        FROM constituencies c 
                        JOIN districts d ON c.district_id = d.id 
                        WHERE d.province_id = " . $province['id']
                    )->fetch_assoc()['total'];
                ?>
                <div class="province-card" data-name="<?php echo strtolower($province['name']); ?>" data-id="<?php echo $province['id']; ?>">
                    <div class="province-header">
                        <span class="province-number"><?php echo $province['id']; ?></span>
                        <span class="province-name"><?php echo htmlspecialchars($province['name']); ?></span>
                    </div>
                    <div class="province-name-np nepali-text">
                        <?php echo $province_np[$province['id']] ?? ''; ?>
                    </div>
                    <div class="province-stats">
                        <span class="stat-badge">🏘️ <?php echo $district_count; ?> Districts</span>
                        <span class="stat-badge">📍 <?php echo $constituency_count; ?> Constituencies</span>
                    </div>
                    <div class="province-actions">
                        <button class="btn-icon btn-edit" onclick="editProvince(<?php echo $province['id']; ?>, '<?php echo htmlspecialchars($province['name']); ?>', '<?php echo $province_np[$province['id']] ?? ''; ?>')">
                            ✏️ Edit | सम्पादन
                        </button>
                        <a href="manage_districts.php?province_id=<?php echo $province['id']; ?>" class="btn-icon btn-view">
                            👁️ View Districts | जिल्लाहरू
                        </a>
                        <a href="?delete=<?php echo $province['id']; ?>" 
                           class="btn-icon btn-delete" 
                           onclick="return confirm('के तपाईं यो प्रदेश मेटाउन चाहनुहुन्छ? \nAre you sure you want to delete this province?')">
                            🗑️ Delete | मेटाउनुहोस्
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Province Map Section -->
            <div class="data-table-container" style="margin-top: 30px;">
                <h2>Nepal's Provinces | नेपालका प्रदेशहरू</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
                    <div style="background: var(--light-color); padding: 20px; border-radius: 10px;">
                        <h3 style="color: var(--primary-color); margin-bottom: 15px;">Province 1 | प्रदेश नं. १</h3>
                        <p><strong>Capital:</strong> Biratnagar</p>
                        <p><strong>Districts:</strong> 14</p>
                        <p><strong>Area:</strong> 25,905 km²</p>
                    </div>
                    <div style="background: var(--light-color); padding: 20px; border-radius: 10px;">
                        <h3 style="color: var(--primary-color); margin-bottom: 15px;">Province 2 | प्रदेश नं. २</h3>
                        <p><strong>Capital:</strong> Janakpur</p>
                        <p><strong>Districts:</strong> 8</p>
                        <p><strong>Area:</strong> 9,661 km²</p>
                    </div>
                    <div style="background: var(--light-color); padding: 20px; border-radius: 10px;">
                        <h3 style="color: var(--primary-color); margin-bottom: 15px;">Bagmati | बागमती</h3>
                        <p><strong>Capital:</strong> Hetauda</p>
                        <p><strong>Districts:</strong> 13</p>
                        <p><strong>Area:</strong> 20,300 km²</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Province Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Province | नयाँ प्रदेश थप्नुहोस्</h3>
                <span class="close" onclick="hideAddModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label>Province Name (English) | अङ्ग्रेजीमा नाम:</label>
                    <input type="text" name="name" required placeholder="e.g., Province No. 1">
                </div>
                
                <div class="form-group">
                    <label>Province Name (Nepali) | नेपालीमा नाम:</label>
                    <input type="text" name="name_np" class="nepali-text" 
                           placeholder="जस्तै: प्रदेश नं. १">
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">➕ Add Province | थप्नुहोस्</button>
                    <button type="button" class="btn btn-secondary" onclick="hideAddModal()">❌ Cancel | रद्द गर्नुहोस्</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Province Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Province | प्रदेश सम्पादन गर्नुहोस्</h3>
                <span class="close" onclick="hideEditModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label>Province Name (English) | अङ्ग्रेजीमा नाम:</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                
                <div class="form-group">
                    <label>Province Name (Nepali) | नेपालीमा नाम:</label>
                    <input type="text" name="name_np" id="edit_name_np" class="nepali-text">
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
        function editProvince(id, name, name_np) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_name_np').value = name_np;
            document.getElementById('editModal').style.display = 'block';
        }
        
        // Hide Edit Modal
        function hideEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let searchText = this.value.toLowerCase();
            let cards = document.querySelectorAll('.province-card');
            
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