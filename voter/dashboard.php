<?php
require_once '../includes/auth.php';
Auth::requireVoter();

$db = Database::getInstance()->getConnection();
$voter_id = $_SESSION['user_id'];

// Fetch voter details with joins
$query = "SELECT v.*, p.name as province_name, p.name_nepali as province_name_np, 
          d.name as district_name, d.name_nepali as district_name_np,
          c.constituency_number,
          (SELECT COUNT(*) FROM votes WHERE voter_id = v.id AND election_type = 'FPTP') as fptp_voted,
          (SELECT COUNT(*) FROM votes WHERE voter_id = v.id AND election_type = 'PR') as pr_voted
          FROM voters v 
          LEFT JOIN provinces p ON v.province_id = p.id 
          LEFT JOIN districts d ON v.district_id = d.id 
          LEFT JOIN constituencies c ON v.constituency_id = c.id 
          WHERE v.id = ?";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $voter_id);
$stmt->execute();
$voter = $stmt->get_result()->fetch_assoc();

// Get voting history
$history_query = "SELECT v.*, c.candidate_name, p.party_name, p.party_logo,
                 v.election_type, v.voted_at
                 FROM votes v
                 JOIN candidates c ON v.candidate_id = c.id
                 JOIN parties p ON c.party_id = p.id
                 WHERE v.voter_id = ?
                 ORDER BY v.voted_at DESC";
$stmt = $db->prepare($history_query);
$stmt->bind_param("i", $voter_id);
$stmt->execute();
$voting_history = $stmt->get_result();

// Get upcoming elections
$upcoming_query = "SELECT * FROM elections WHERE status = 'upcoming' ORDER BY election_date LIMIT 3";
$upcoming = $db->query($upcoming_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Dashboard - VoteNepal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --primary-light: #4895ef;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --dark: #1e1b4b;
            --light: #f8f9fa;
            --gray: #6c757d;
            --gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--light);
        }

        /* Navbar */
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            font-size: 30px;
            color: var(--primary);
        }

        .logo h2 {
            color: var(--dark);
            font-size: 24px;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .welcome-text {
            color: var(--dark);
            font-weight: 500;
        }

        .welcome-text i {
            color: var(--primary);
            margin-right: 5px;
        }

        .btn-logout {
            padding: 8px 20px;
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-logout:hover {
            background: #d81b60;
            transform: translateY(-2px);
        }

        /* Dashboard Container */
        .dashboard-container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 30px;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 30px;
        }

        /* Sidebar */
        .sidebar {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .profile-card {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--light);
            margin-bottom: 20px;
        }

        .profile-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 15px;
            overflow: hidden;
            border: 4px solid var(--primary);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .profile-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .default-avatar {
            width: 100%;
            height: 100%;
            background: var(--gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
        }

        .profile-card h3 {
            color: var(--dark);
            margin-bottom: 5px;
        }

        .voter-id {
            background: var(--light);
            padding: 5px 15px;
            border-radius: 20px;
            display: inline-block;
            font-size: 14px;
            color: var(--primary);
            font-weight: 600;
            letter-spacing: 1px;
        }

        .status-badge {
            margin-top: 10px;
            padding: 5px 15px;
            border-radius: 20px;
            display: inline-block;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.verified {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge.pending {
            background: #fee2e2;
            color: #991b1b;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            border-radius: 12px;
            color: var(--gray);
            text-decoration: none;
            transition: all 0.3s;
        }

        .sidebar-menu a i {
            width: 20px;
            font-size: 18px;
        }

        .sidebar-menu a:hover {
            background: var(--light);
            color: var(--primary);
        }

        .sidebar-menu li.active a {
            background: var(--gradient);
            color: white;
        }

        .sidebar-menu .voted a {
            color: var(--success);
        }

        /* Main Content */
        .main-content {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        .welcome-banner {
            background: var(--gradient);
            border-radius: 15px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-banner h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .welcome-banner p {
            opacity: 0.9;
        }

        .banner-icon {
            font-size: 80px;
            opacity: 0.3;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--light);
            border-radius: 15px;
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            color: var(--primary);
        }

        .stat-content h3 {
            font-size: 28px;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .stat-content p {
            color: var(--gray);
        }

        /* Voting Status Cards */
        .voting-status {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .vote-card {
            background: white;
            border: 2px solid var(--light);
            border-radius: 15px;
            padding: 25px;
            transition: all 0.3s;
        }

        .vote-card.completed {
            border-color: var(--success);
            background: #f0fdf4;
        }

        .vote-card.pending {
            border-color: var(--warning);
            background: #fffbeb;
        }

        .vote-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .vote-header h3 {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .vote-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .vote-card.completed .vote-status {
            background: var(--success);
            color: white;
        }

        .vote-card.pending .vote-status {
            background: var(--warning);
            color: white;
        }

        .vote-btn {
            display: inline-block;
            padding: 10px 25px;
            background: var(--gradient);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 15px;
            transition: all 0.3s;
        }

        .vote-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .vote-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .info-item {
            background: var(--light);
            padding: 15px;
            border-radius: 10px;
        }

        .info-item label {
            font-size: 12px;
            color: var(--gray);
            display: block;
            margin-bottom: 5px;
        }

        .info-item p {
            font-weight: 600;
            color: var(--dark);
        }

        /* History Table */
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .history-table th {
            background: var(--light);
            padding: 12px;
            text-align: left;
            font-size: 14px;
            color: var(--gray);
        }

        .history-table td {
            padding: 12px;
            border-bottom: 1px solid var(--light);
        }

        .badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-fptp {
            background: var(--primary);
            color: white;
        }

        .badge-pr {
            background: var(--success);
            color: white;
        }

        /* Upcoming Elections */
        .upcoming-card {
            background: var(--light);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .upcoming-date {
            background: var(--gradient);
            color: white;
            padding: 10px;
            border-radius: 10px;
            text-align: center;
            min-width: 60px;
        }

        .upcoming-date .day {
            font-size: 20px;
            font-weight: bold;
        }

        .upcoming-date .month {
            font-size: 12px;
        }

        .upcoming-details h4 {
            margin-bottom: 5px;
        }

        .upcoming-details p {
            color: var(--gray);
            font-size: 13px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .voting-status {
                grid-template-columns: 1fr;
            }
            
            .welcome-banner {
                flex-direction: column;
                text-align: center;
            }
            
            .banner-icon {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <i class="fas fa-vote-yea"></i>
                <h2>VoteNepal</h2>
            </div>
            <div class="nav-menu">
                <span class="welcome-text">
                    <i class="fas fa-user"></i>
                    <?php echo htmlspecialchars($voter['name']); ?>
                </span>
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="profile-card">
                <div class="profile-photo">
                    <?php if ($voter['profile_photo']): ?>
                        <img src="../assets/uploads/voters/<?php echo $voter['profile_photo']; ?>" 
                             alt="Profile Photo">
                    <?php else: ?>
                        <div class="default-avatar">
                            <?php echo strtoupper(substr($voter['name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <h3><?php echo htmlspecialchars($voter['name']); ?></h3>
                <div class="voter-id"><?php echo $voter['voter_id']; ?></div>
                <div class="status-badge <?php echo $voter['is_verified'] ? 'verified' : 'pending'; ?>">
                    <?php echo $voter['is_verified'] ? '✓ Verified' : '⏳ Pending'; ?>
                </div>
            </div>

            <ul class="sidebar-menu">
                <li class="active"><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                <li class="<?php echo $voter['fptp_voted'] ? 'voted' : ''; ?>">
                    <a href="vote_fptp.php">
                        <i class="fas fa-vote-yea"></i> 
                        FPTP Vote <?php echo $voter['fptp_voted'] ? '(Voted)' : ''; ?>
                    </a>
                </li>
                <li class="<?php echo $voter['pr_voted'] ? 'voted' : ''; ?>">
                    <a href="vote_pr.php">
                        <i class="fas fa-list"></i> 
                        PR Vote <?php echo $voter['pr_voted'] ? '(Voted)' : ''; ?>
                    </a>
                </li>
                <li><a href="download_info.php"><i class="fas fa-download"></i> Download Info</a></li>
                <li><a href="change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <div>
                    <h1>Welcome, <?php echo htmlspecialchars($voter['name']); ?>!</h1>
                    <p>स्वागत छ, <?php echo htmlspecialchars($voter['name']); ?>!</p>
                    <p style="margin-top: 10px;">Your voice matters. Make it count.</p>
                </div>
                <div class="banner-icon">
                    <i class="fas fa-vote-yea"></i>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo date('d M Y', strtotime($voter['dob'])); ?></h3>
                        <p>Date of Birth</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $voter['constituency_number']; ?></h3>
                        <p>Constituency</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo date('d M Y', strtotime($voter['created_at'])); ?></h3>
                        <p>Registered On</p>
                    </div>
                </div>
            </div>

            <!-- Voting Status -->
            <h2 style="margin-bottom: 20px;">🗳️ Your Voting Status</h2>
            
            <div class="voting-status">
                <!-- FPTP Card -->
                <div class="vote-card <?php echo $voter['fptp_voted'] ? 'completed' : 'pending'; ?>">
                    <div class="vote-header">
                        <h3>
                            <i class="fas fa-vote-yea"></i>
                            FPTP Vote
                        </h3>
                        <span class="vote-status">
                            <?php echo $voter['fptp_voted'] ? 'Voted' : 'Not Voted'; ?>
                        </span>
                    </div>
                    <p>First Past The Post - प्रत्यक्ष निर्वाचन</p>
                    <?php if (!$voter['fptp_voted'] && $voter['is_verified']): ?>
                        <a href="vote_fptp.php" class="vote-btn">
                            <i class="fas fa-arrow-right"></i> Cast Your Vote
                        </a>
                    <?php elseif (!$voter['is_verified']): ?>
                        <p style="color: var(--warning); margin-top: 10px;">
                            ⚠️ Your account is pending verification
                        </p>
                    <?php endif; ?>
                </div>

                <!-- PR Card -->
                <div class="vote-card <?php echo $voter['pr_voted'] ? 'completed' : 'pending'; ?>">
                    <div class="vote-header">
                        <h3>
                            <i class="fas fa-list"></i>
                            PR Vote
                        </h3>
                        <span class="vote-status">
                            <?php echo $voter['pr_voted'] ? 'Voted' : 'Not Voted'; ?>
                        </span>
                    </div>
                    <p>Proportional Representation - समानुपातिक निर्वाचन</p>
                    <?php if (!$voter['pr_voted'] && $voter['is_verified']): ?>
                        <a href="vote_pr.php" class="vote-btn">
                            <i class="fas fa-arrow-right"></i> Cast Your Vote
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Personal Information -->
            <h2 style="margin: 30px 0 20px;">📋 Personal Information</h2>
            
            <div class="info-grid">
                <div class="info-item">
                    <label>Full Name / पूरा नाम</label>
                    <p><?php echo htmlspecialchars($voter['name']); ?></p>
                </div>
                <div class="info-item">
                    <label>Father's Name / बुबाको नाम</label>
                    <p><?php echo htmlspecialchars($voter['father_name']); ?></p>
                </div>
                <div class="info-item">
                    <label>Mother's Name / आमाको नाम</label>
                    <p><?php echo htmlspecialchars($voter['mother_name']); ?></p>
                </div>
                <div class="info-item">
                    <label>Date of Birth / जन्म मिति</label>
                    <p><?php echo date('d F Y', strtotime($voter['dob'])); ?></p>
                </div>
                <div class="info-item">
                    <label>Citizenship / नागरिकता</label>
                    <p><?php echo htmlspecialchars($voter['citizenship_number']); ?></p>
                </div>
                <div class="info-item">
                    <label>Phone / फोन</label>
                    <p><?php echo htmlspecialchars($voter['phone']); ?></p>
                </div>
                <div class="info-item">
                    <label>Email / ईमेल</label>
                    <p><?php echo htmlspecialchars($voter['email']); ?></p>
                </div>
                <div class="info-item">
                    <label>Province / प्रदेश</label>
                    <p><?php echo htmlspecialchars($voter['province_name']); ?></p>
                </div>
                <div class="info-item">
                    <label>District / जिल्ला</label>
                    <p><?php echo htmlspecialchars($voter['district_name']); ?></p>
                </div>
                <div class="info-item">
                    <label>Constituency / क्षेत्र</label>
                    <p><?php echo $voter['constituency_number']; ?></p>
                </div>
                <div class="info-item" style="grid-column: 1/-1;">
                    <label>Address / ठेगाना</label>
                    <p><?php echo nl2br(htmlspecialchars($voter['address'])); ?></p>
                </div>
            </div>

            <!-- Voting History -->
            <?php if ($voting_history->num_rows > 0): ?>
            <h2 style="margin: 30px 0 20px;">📜 Voting History</h2>
            
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Election Type</th>
                        <th>Candidate</th>
                        <th>Party</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($vote = $voting_history->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('d M Y H:i', strtotime($vote['voted_at'])); ?></td>
                        <td>
                            <span class="badge badge-<?php echo strtolower($vote['election_type']); ?>">
                                <?php echo $vote['election_type']; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($vote['candidate_name']); ?></td>
                        <td>
                            <?php if ($vote['party_logo']): ?>
                                <img src="../assets/uploads/parties/<?php echo $vote['party_logo']; ?>" 
                                     alt="" style="width: 20px; height: 20px; vertical-align: middle;">
                            <?php endif; ?>
                            <?php echo htmlspecialchars($vote['party_name']); ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- Upcoming Elections -->
            <?php if ($upcoming && $upcoming->num_rows > 0): ?>
            <h2 style="margin: 30px 0 20px;">📅 Upcoming Elections</h2>
            
            <?php while($election = $upcoming->fetch_assoc()): ?>
            <div class="upcoming-card">
                <div class="upcoming-date">
                    <div class="day"><?php echo date('d', strtotime($election['election_date'])); ?></div>
                    <div class="month"><?php echo date('M', strtotime($election['election_date'])); ?></div>
                </div>
                <div class="upcoming-details">
                    <h4><?php echo htmlspecialchars($election['title']); ?></h4>
                    <p><?php echo htmlspecialchars($election['description']); ?></p>
                </div>
            </div>
            <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto refresh every 30 seconds for live updates
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>