<?php
require_once '../includes/auth.php';
Auth::requireAdmin();

$db = Database::getInstance()->getConnection();

// Get statistics
$stats = [];

// Total voters
$result = $db->query("SELECT COUNT(*) as total FROM voters");
$stats['total_voters'] = $result->fetch_assoc()['total'];

// Total parties
$result = $db->query("SELECT COUNT(*) as total FROM parties");
$stats['total_parties'] = $result->fetch_assoc()['total'];

// Total candidates
$result = $db->query("SELECT COUNT(*) as total FROM candidates");
$stats['total_candidates'] = $result->fetch_assoc()['total'];

// Total votes cast
$result = $db->query("SELECT COUNT(*) as total FROM votes");
$stats['total_votes'] = $result->fetch_assoc()['total'];

// Recent voters
$recent_voters = $db->query("SELECT v.*, p.name as province_name 
                             FROM voters v 
                             LEFT JOIN provinces p ON v.province_id = p.id 
                             ORDER BY v.created_at DESC LIMIT 5");

// Recent votes
$recent_votes = $db->query("SELECT votes.*, voters.name as voter_name, 
                            candidates.candidate_name, parties.party_name 
                            FROM votes 
                            JOIN voters ON votes.voter_id = voters.id 
                            JOIN candidates ON votes.candidate_id = candidates.id 
                            JOIN parties ON candidates.party_id = parties.id 
                            ORDER BY votes.voted_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - VoteNepal</title>
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
                <li class="active"><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="manage_voters.php">👥 Manage Voters</a></li>
                <li><a href="manage_parties.php">🎯 Manage Parties</a></li>
                <li><a href="manage_candidates.php">👤 Manage Candidates</a></li>
                <li><a href="manage_provinces.php">🗺️ Manage Provinces</a></li>
                <li><a href="manage_districts.php">🏘️ Manage Districts</a></li>
                <li><a href="manage_constituencies.php">📍 Manage Constituencies</a></li>
                <li><a href="view_results.php">📊 View Results</a></li>
                <li><a href="logout.php">🚪 Logout</a></li>
            </ul>
            
            <div class="sidebar-footer">
                <p>Welcome, <strong><?php echo $_SESSION['username']; ?></strong></p>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="admin-content">
            <div class="content-header">
                <h1>Dashboard</h1>
                <p>Welcome to the admin panel. Here's an overview of the system.</p>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-details">
                        <h3><?php echo $stats['total_voters']; ?></h3>
                        <p>Total Voters</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🎯</div>
                    <div class="stat-details">
                        <h3><?php echo $stats['total_parties']; ?></h3>
                        <p>Political Parties</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👤</div>
                    <div class="stat-details">
                        <h3><?php echo $stats['total_candidates']; ?></h3>
                        <p>Candidates</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🗳️</div>
                    <div class="stat-details">
                        <h3><?php echo $stats['total_votes']; ?></h3>
                        <p>Votes Cast</p>
                    </div>
                </div>
            </div>
            
            <!-- Recent Voters -->
            <div class="data-table-container">
                <h2>Recent Voters</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Voter ID</th>
                            <th>Name</th>
                            <th>Province</th>
                            <th>Registered On</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($voter = $recent_voters->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $voter['voter_id']; ?></td>
                            <td><?php echo htmlspecialchars($voter['name']); ?></td>
                            <td><?php echo htmlspecialchars($voter['province_name']); ?></td>
                            <td><?php echo date('d M Y', strtotime($voter['created_at'])); ?></td>
                            <td>
                                <span class="badge <?php echo $voter['is_verified'] ? 'badge-success' : 'badge-warning'; ?>">
                                    <?php echo $voter['is_verified'] ? 'Verified' : 'Pending'; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <div class="table-footer">
                    <a href="manage_voters.php" class="btn btn-small">View All Voters →</a>
                </div>
            </div>
            
            <!-- Recent Votes -->
            <div class="data-table-container">
                <h2>Recent Votes</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Voter</th>
                            <th>Candidate</th>
                            <th>Party</th>
                            <th>Type</th>
                            <th>Voted At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($vote = $recent_votes->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($vote['voter_name']); ?></td>
                            <td><?php echo htmlspecialchars($vote['candidate_name']); ?></td>
                            <td><?php echo htmlspecialchars($vote['party_name']); ?></td>
                            <td>
                                <span class="badge badge-info"><?php echo $vote['election_type']; ?></span>
                            </td>
                            <td><?php echo date('d M Y H:i', strtotime($vote['voted_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/admin.js"></script>
</body>
</html>