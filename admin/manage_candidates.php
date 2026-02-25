<?php
require_once '../includes/auth.php';
Auth::requireAdmin();

$db = Database::getInstance()->getConnection();

// Handle delete action
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    if ($_GET['action'] == 'delete') {
        // Get candidate photo to delete file
        $query = "SELECT candidate_photo FROM candidates WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $candidate = $result->fetch_assoc();
        
        // Delete photo file if exists
        if ($candidate['candidate_photo'] && file_exists(UPLOAD_PATH . 'candidates/' . $candidate['candidate_photo'])) {
            unlink(UPLOAD_PATH . 'candidates/' . $candidate['candidate_photo']);
        }
        
        // Delete from database
        $query = "DELETE FROM candidates WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Candidate deleted successfully';
        }
    }
    
    redirect('admin/manage_candidates.php');
}

// Get all candidates with details
$query = "SELECT c.*, p.party_name, p.party_logo, 
          pr.name as province_name, d.name as district_name, 
          cn.constituency_number 
          FROM candidates c 
          JOIN parties p ON c.party_id = p.id 
          LEFT JOIN constituencies cn ON c.constituency_id = cn.id 
          LEFT JOIN districts d ON cn.district_id = d.id 
          LEFT JOIN provinces pr ON d.province_id = pr.id 
          ORDER BY c.election_type, p.party_name";
$candidates = $db->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Candidates - VoteNepal</title>
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
                <h1>Manage Candidates</h1>
                <div class="header-actions">
                    <a href="add_candidate.php" class="btn btn-primary">+ Add New Candidate</a>
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
                            <th>Candidate Name</th>
                            <th>Party</th>
                            <th>Election Type</th>
                            <th>Constituency</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($candidate = $candidates->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $candidate['id']; ?></td>
                            <td>
                                <?php if ($candidate['candidate_photo']): ?>
                                    <img src="../assets/uploads/candidates/<?php echo $candidate['candidate_photo']; ?>" 
                                         alt="Photo" class="table-thumbnail">
                                <?php else: ?>
                                    <div class="no-photo-small">👤</div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($candidate['candidate_name']); ?></strong></td>
                            <td>
                                <?php if ($candidate['party_logo']): ?>
                                    <img src="../assets/uploads/parties/<?php echo $candidate['party_logo']; ?>" 
                                         alt="Logo" style="width: 20px; height: 20px; vertical-align: middle;">
                                <?php endif; ?>
                                <?php echo htmlspecialchars($candidate['party_name']); ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $candidate['election_type'] == 'FPTP' ? 'badge-info' : 'badge-success'; ?>">
                                    <?php echo $candidate['election_type']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($candidate['election_type'] == 'FPTP'): ?>
                                    <?php echo htmlspecialchars($candidate['province_name'] . ' - ' . $candidate['district_name']); ?><br>
                                    <small>Constituency <?php echo $candidate['constituency_number']; ?></small>
                                <?php else: ?>
                                    <span class="badge badge-warning">National Level</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a href="edit_candidate.php?id=<?php echo $candidate['id']; ?>" class="btn-small">✏️ Edit</a>
                                <a href="?action=delete&id=<?php echo $candidate['id']; ?>" 
                                   class="btn-small delete" 
                                   onclick="return confirm('Delete this candidate?')">🗑️ Delete</a>
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