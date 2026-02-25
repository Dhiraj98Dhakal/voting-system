<?php
require_once '../includes/auth.php';
Auth::requireAdmin();

$db = Database::getInstance()->getConnection();

// Handle delete action
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    if ($_GET['action'] == 'delete') {
        // First get the party logo to delete file
        $query = "SELECT party_logo FROM parties WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $party = $result->fetch_assoc();
        
        // Delete logo file if exists
        if ($party['party_logo'] && file_exists(UPLOAD_PATH . 'parties/' . $party['party_logo'])) {
            unlink(UPLOAD_PATH . 'parties/' . $party['party_logo']);
        }
        
        // Delete from database
        $query = "DELETE FROM parties WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Party deleted successfully';
        }
    }
    
    redirect('admin/manage_parties.php');
}

// Get all parties
$parties = $db->query("SELECT * FROM parties ORDER BY party_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Parties - VoteNepal</title>
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
                <h1>Manage Political Parties</h1>
                <div class="header-actions">
                    <a href="add_party.php" class="btn btn-primary">+ Add New Party</a>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Logo</th>
                            <th>Party Name</th>
                            <th>Total Candidates</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($party = $parties->fetch_assoc()): 
                            // Count candidates for this party
                            $count_query = "SELECT COUNT(*) as total FROM candidates WHERE party_id = ?";
                            $count_stmt = $db->prepare($count_query);
                            $count_stmt->bind_param("i", $party['id']);
                            $count_stmt->execute();
                            $count_result = $count_stmt->get_result();
                            $candidate_count = $count_result->fetch_assoc()['total'];
                        ?>
                        <tr>
                            <td><?php echo $party['id']; ?></td>
                            <td>
                                <?php if ($party['party_logo']): ?>
                                    <img src="../assets/uploads/parties/<?php echo $party['party_logo']; ?>" 
                                         alt="Logo" class="table-thumbnail">
                                <?php else: ?>
                                    <div class="no-photo-small">🎯</div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($party['party_name']); ?></strong></td>
                            <td>
                                <span class="badge badge-info"><?php echo $candidate_count; ?> candidates</span>
                            </td>
                            <td><?php echo date('d M Y', strtotime($party['created_at'])); ?></td>
                            <td class="actions">
                                <a href="edit_party.php?id=<?php echo $party['id']; ?>" class="btn-small">✏️ Edit</a>
                                <a href="?action=delete&id=<?php echo $party['id']; ?>" 
                                   class="btn-small delete" 
                                   onclick="return confirm('Are you sure? This will also delete all candidates under this party!')">🗑️ Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>