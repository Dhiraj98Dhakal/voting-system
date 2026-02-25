<?php
require_once '../includes/auth.php';
Auth::requireAdmin();

$db = Database::getInstance()->getConnection();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $party_name = sanitize($_POST['party_name']);
    
    // Handle logo upload
    $party_logo = '';
    if (isset($_FILES['party_logo']) && $_FILES['party_logo']['error'] == 0) {
        $upload = uploadImage($_FILES['party_logo'], 'parties');
        if ($upload['success']) {
            $party_logo = $upload['filename'];
        } else {
            $error = $upload['message'];
        }
    }
    
    if (empty($error)) {
        $query = "INSERT INTO parties (party_name, party_logo) VALUES (?, ?)";
        $stmt = $db->prepare($query);
        $stmt->bind_param("ss", $party_name, $party_logo);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Party added successfully';
            redirect('admin/manage_parties.php');
        } else {
            $error = 'Error: ' . $db->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Party - VoteNepal</title>
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
                <li class="active"><a href="manage_parties.php">🎯 Manage Parties</a></li>
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
                <h1>Add New Party</h1>
                <a href="manage_parties.php" class="btn btn-outline">← Back to Parties</a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="POST" enctype="multipart/form-data" class="admin-form">
                    <div class="form-group">
                        <label for="party_name">Party Name *</label>
                        <input type="text" id="party_name" name="party_name" required 
                               placeholder="Enter party name">
                    </div>
                    
                    <div class="form-group">
                        <label for="party_logo">Party Logo</label>
                        <input type="file" id="party_logo" name="party_logo" accept="image/*">
                        <small>Recommended size: 200x200px. Max 2MB</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Add Party</button>
                        <a href="manage_parties.php" class="btn btn-outline">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>