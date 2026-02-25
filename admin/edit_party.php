<?php
require_once '../includes/auth.php';
Auth::requireAdmin();

$db = Database::getInstance()->getConnection();
$error = '';
$success = '';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch party details
$query = "SELECT * FROM parties WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$party = $result->fetch_assoc();

if (!$party) {
    $_SESSION['error'] = 'Party not found';
    redirect('admin/manage_parties.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $party_name = sanitize($_POST['party_name']);
    $party_logo = $party['party_logo']; // Keep existing logo
    
    // Handle new logo upload
    if (isset($_FILES['party_logo']) && $_FILES['party_logo']['error'] == 0) {
        $upload = uploadImage($_FILES['party_logo'], 'parties', $party['party_logo']);
        if ($upload['success']) {
            $party_logo = $upload['filename'];
        } else {
            $error = $upload['message'];
        }
    }
    
    if (empty($error)) {
        $query = "UPDATE parties SET party_name = ?, party_logo = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("ssi", $party_name, $party_logo, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Party updated successfully';
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
    <title>Edit Party - VoteNepal</title>
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
                <h1>Edit Party</h1>
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
                               value="<?php echo htmlspecialchars($party['party_name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Current Logo</label>
                        <?php if ($party['party_logo']): ?>
                            <div class="current-image">
                                <img src="../assets/uploads/parties/<?php echo $party['party_logo']; ?>" 
                                     alt="Current Logo" style="max-width: 100px; max-height: 100px;">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="party_logo">New Party Logo (leave empty to keep current)</label>
                        <input type="file" id="party_logo" name="party_logo" accept="image/*">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Party</button>
                        <a href="manage_parties.php" class="btn btn-outline">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>