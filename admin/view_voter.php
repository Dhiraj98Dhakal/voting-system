<?php
require_once '../includes/auth.php';
Auth::requireAdmin();

$db = Database::getInstance()->getConnection();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch voter details with all joins
$query = "SELECT v.*, p.name as province_name, d.name as district_name, 
          c.constituency_number,
          (SELECT COUNT(*) FROM votes WHERE voter_id = v.id AND election_type = 'FPTP') as fptp_voted,
          (SELECT COUNT(*) FROM votes WHERE voter_id = v.id AND election_type = 'PR') as pr_voted
          FROM voters v 
          LEFT JOIN provinces p ON v.province_id = p.id 
          LEFT JOIN districts d ON v.district_id = d.id 
          LEFT JOIN constituencies c ON v.constituency_id = c.id 
          WHERE v.id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$voter = $stmt->get_result()->fetch_assoc();

if (!$voter) {
    $_SESSION['error'] = 'Voter not found';
    redirect('admin/manage_voters.php');
}

// Get voting history
$votes_query = "SELECT votes.*, candidates.candidate_name, parties.party_name,
                election_type 
                FROM votes 
                JOIN candidates ON votes.candidate_id = candidates.id 
                JOIN parties ON candidates.party_id = parties.id 
                WHERE votes.voter_id = ?";
$stmt = $db->prepare($votes_query);
$stmt->bind_param("i", $id);
$stmt->execute();
$votes = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Voter - VoteNepal</title>
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
                <h1>Voter Details</h1>
                <div class="header-actions">
                    <a href="edit_voter.php?id=<?php echo $id; ?>" class="btn btn-primary">✏️ Edit Voter</a>
                    <a href="manage_voters.php" class="btn btn-outline">← Back to Voters</a>
                </div>
            </div>
            
            <div class="voter-profile-card">
                <div class="profile-header">
                    <div class="profile-photo-large">
                        <?php if ($voter['profile_photo']): ?>
                            <img src="../assets/uploads/voters/<?php echo $voter['profile_photo']; ?>" 
                                 alt="Profile Photo">
                        <?php else: ?>
                            <div class="default-avatar-large">
                                <?php echo strtoupper(substr($voter['name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-title">
                        <h2><?php echo htmlspecialchars($voter['name']); ?></h2>
                        <p class="voter-id-badge">Voter ID: <?php echo $voter['voter_id']; ?></p>
                        <p class="voter-status <?php echo $voter['is_verified'] ? 'verified' : 'pending'; ?>">
                            <?php echo $voter['is_verified'] ? '✓ Verified Voter' : '⏳ Pending Verification'; ?>
                        </p>
                    </div>
                </div>
                
                <div class="info-sections">
                    <!-- Personal Information -->
                    <div class="info-section">
                        <h3>Personal Information</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Full Name</label>
                                <p><?php echo htmlspecialchars($voter['name']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Date of Birth</label>
                                <p><?php echo date('d F Y', strtotime($voter['dob'])); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Age</label>
                                <p><?php echo calculateAge($voter['dob']); ?> years</p>
                            </div>
                            <div class="info-item">
                                <label>Citizenship No.</label>
                                <p><?php echo htmlspecialchars($voter['citizenship_number']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Father's Name</label>
                                <p><?php echo htmlspecialchars($voter['father_name']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Mother's Name</label>
                                <p><?php echo htmlspecialchars($voter['mother_name']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contact Information -->
                    <div class="info-section">
                        <h3>Contact Information</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Email Address</label>
                                <p><a href="mailto:<?php echo $voter['email']; ?>"><?php echo $voter['email']; ?></a></p>
                            </div>
                            <div class="info-item">
                                <label>Phone Number</label>
                                <p><a href="tel:<?php echo $voter['phone']; ?>"><?php echo $voter['phone']; ?></a></p>
                            </div>
                            <div class="info-item full-width">
                                <label>Address</label>
                                <p><?php echo nl2br(htmlspecialchars($voter['address'])); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Location Information -->
                    <div class="info-section">
                        <h3>Voting Location</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Province</label>
                                <p><?php echo htmlspecialchars($voter['province_name']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>District</label>
                                <p><?php echo htmlspecialchars($voter['district_name']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Constituency</label>
                                <p><?php echo $voter['constituency_number']; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Voting Status -->
                    <div class="info-section">
                        <h3>Voting Status</h3>
                        <div class="voting-status-cards">
                            <div class="status-card <?php echo $voter['fptp_voted'] ? 'completed' : 'pending'; ?>">
                                <div class="status-icon">🗳️</div>
                                <div class="status-details">
                                    <h4>FPTP Vote</h4>
                                    <span class="status-badge">
                                        <?php echo $voter['fptp_voted'] ? 'Voted' : 'Not Voted'; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="status-card <?php echo $voter['pr_voted'] ? 'completed' : 'pending'; ?>">
                                <div class="status-icon">📋</div>
                                <div class="status-details">
                                    <h4>PR Vote</h4>
                                    <span class="status-badge">
                                        <?php echo $voter['pr_voted'] ? 'Voted' : 'Not Voted'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($votes->num_rows > 0): ?>
                        <h4 style="margin-top: 20px;">Voting History</h4>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Election Type</th>
                                    <th>Candidate</th>
                                    <th>Party</th>
                                    <th>Voted At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($vote = $votes->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span class="badge <?php echo $vote['election_type'] == 'FPTP' ? 'badge-info' : 'badge-success'; ?>">
                                            <?php echo $vote['election_type']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($vote['candidate_name']); ?></td>
                                    <td><?php echo htmlspecialchars($vote['party_name']); ?></td>
                                    <td><?php echo date('d M Y H:i', strtotime($vote['voted_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Account Information -->
                    <div class="info-section">
                        <h3>Account Information</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Registered On</label>
                                <p><?php echo date('d F Y, h:i A', strtotime($voter['created_at'])); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Last Updated</label>
                                <p><?php echo date('d F Y, h:i A', strtotime($voter['updated_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .voter-profile-card {
        background: white;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .profile-header {
        display: flex;
        align-items: center;
        gap: 30px;
        margin-bottom: 30px;
        padding-bottom: 30px;
        border-bottom: 2px solid var(--border-color);
    }
    
    .profile-photo-large {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        overflow: hidden;
        border: 4px solid var(--primary-color);
    }
    
    .profile-photo-large img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .default-avatar-large {
        width: 100%;
        height: 100%;
        background: var(--primary-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 60px;
        font-weight: bold;
    }
    
    .profile-title h2 {
        font-size: 32px;
        margin-bottom: 10px;
        color: var(--dark-color);
    }
    
    .voter-id-badge {
        background: var(--light-color);
        padding: 8px 15px;
        border-radius: 20px;
        display: inline-block;
        font-weight: 600;
        margin-bottom: 10px;
    }
    
    .voter-status {
        font-size: 16px;
        font-weight: 500;
    }
    
    .voter-status.verified {
        color: var(--success-color);
    }
    
    .voter-status.pending {
        color: var(--warning-color);
    }
    
    .info-sections {
        display: flex;
        flex-direction: column;
        gap: 30px;
    }
    
    .info-section {
        background: var(--light-color);
        border-radius: 8px;
        padding: 20px;
    }
    
    .info-section h3 {
        margin-bottom: 20px;
        color: var(--dark-color);
        font-size: 18px;
    }
    
    .info-section h4 {
        margin: 15px 0 10px;
        color: var(--text-color);
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .info-item.full-width {
        grid-column: 1 / -1;
    }
    
    .info-item label {
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 5px;
        display: block;
    }
    
    .info-item p {
        font-weight: 500;
        color: var(--dark-color);
    }
    
    .voting-status-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .status-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .status-card.completed {
        border-left: 4px solid var(--success-color);
    }
    
    .status-card.pending {
        border-left: 4px solid var(--warning-color);
    }
    
    .status-icon {
        font-size: 30px;
    }
    
    .status-details h4 {
        margin-bottom: 5px;
    }
    
    .status-badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .status-card.completed .status-badge {
        background: #d1fae5;
        color: var(--success-color);
    }
    
    .status-card.pending .status-badge {
        background: #fed7aa;
        color: var(--warning-color);
    }
    </style>
</body>
</html>