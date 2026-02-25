<?php
require_once '../includes/auth.php';
Auth::requireAdmin();

$db = Database::getInstance()->getConnection();

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    if ($_GET['action'] == 'delete') {
        $query = "DELETE FROM voters WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Voter deleted successfully';
        }
    } elseif ($_GET['action'] == 'verify') {
        $query = "UPDATE voters SET is_verified = 1 WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Voter verified successfully';
        }
    }
    
    redirect('admin/manage_voters.php');
}

// Get all voters with details
$query = "SELECT v.*, p.name as province_name, d.name as district_name, 
          c.constituency_number 
          FROM voters v 
          LEFT JOIN provinces p ON v.province_id = p.id 
          LEFT JOIN districts d ON v.district_id = d.id 
          LEFT JOIN constituencies c ON v.constituency_id = c.id 
          ORDER BY v.created_at DESC";
$voters = $db->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Voters - VoteNepal</title>
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
                <h1>Manage Voters</h1>
                <div class="header-actions">
                    <a href="add_voter.php" class="btn btn-primary">+ Add New Voter</a>
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
                            <th>Photo</th>
                            <th>Voter ID</th>
                            <th>Name</th>
                            <th>Province/District</th>
                            <th>Constituency</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($voter = $voters->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $voter['id']; ?></td>
                            <td>
                                <?php if ($voter['profile_photo']): ?>
                                    <img src="../assets/uploads/voters/<?php echo $voter['profile_photo']; ?>" 
                                         alt="Profile" class="table-thumbnail">
                                <?php else: ?>
                                    <div class="no-photo-small">📸</div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo $voter['voter_id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($voter['name']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($voter['province_name']); ?><br>
                                <small><?php echo htmlspecialchars($voter['district_name']); ?></small>
                            </td>
                            <td><?php echo $voter['constituency_number']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($voter['email']); ?><br>
                                <small><?php echo htmlspecialchars($voter['phone']); ?></small>
                            </td>
                            <td>
                                <span class="badge <?php echo $voter['is_verified'] ? 'badge-success' : 'badge-warning'; ?>">
                                    <?php echo $voter['is_verified'] ? 'Verified' : 'Pending'; ?>
                                </span>
                            </td>
                            <td class="actions">
                                <a href="view_voter.php?id=<?php echo $voter['id']; ?>" class="btn-small">👁️</a>
                                <a href="edit_voter.php?id=<?php echo $voter['id']; ?>" class="btn-small">✏️</a>
                                <?php if (!$voter['is_verified']): ?>
                                    <a href="?action=verify&id=<?php echo $voter['id']; ?>" 
                                       class="btn-small" onclick="return confirm('Verify this voter?')">✅</a>
                                <?php endif; ?>
                                <a href="?action=delete&id=<?php echo $voter['id']; ?>" 
                                   class="btn-small delete" onclick="return confirm('Delete this voter?')">🗑️</a>
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