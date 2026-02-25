<?php
require_once '../includes/auth.php';
Auth::requireVoter();

$db = Database::getInstance()->getConnection();
$voter_id = $_SESSION['user_id'];

// Fetch voter details
$query = "SELECT v.*, p.name as province_name, p.name_nepali as province_name_np,
          d.name as district_name, d.name_nepali as district_name_np,
          c.constituency_number
          FROM voters v 
          LEFT JOIN provinces p ON v.province_id = p.id 
          LEFT JOIN districts d ON v.district_id = d.id 
          LEFT JOIN constituencies c ON v.constituency_id = c.id 
          WHERE v.id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $voter_id);
$stmt->execute();
$voter = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - VoteNepal</title>
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
            min-height: 100vh;
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

        /* Profile Container */
        .profile-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .profile-header {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100px;
            background: var(--gradient);
            z-index: 0;
        }

        .profile-photo-large {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 20px;
            overflow: hidden;
            border: 5px solid white;
            box-shadow: 0 10px 30px rgba(67, 97, 238, 0.3);
            position: relative;
            z-index: 1;
            background: white;
        }

        .profile-photo-large img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-name {
            font-size: 28px;
            color: var(--dark);
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .profile-id {
            background: var(--light);
            padding: 8px 25px;
            border-radius: 50px;
            display: inline-block;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 15px;
            font-family: monospace;
            font-size: 18px;
            position: relative;
            z-index: 1;
        }

        .profile-status {
            display: inline-block;
            padding: 5px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }

        .status-verified {
            background: #d1fae5;
            color: #065f46;
        }

        .status-pending {
            background: #fee2e2;
            color: #991b1b;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .profile-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }

        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .card-title {
            font-size: 18px;
            color: var(--dark);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            color: var(--primary);
            font-size: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px dashed var(--light);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--gray);
            font-size: 14px;
        }

        .info-value {
            font-weight: 600;
            color: var(--dark);
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background: var(--gradient);
            color: white;
            box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(50, 50, 93, 0.1);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        .btn-secondary {
            background: var(--gray);
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .profile-header {
                padding: 30px 20px;
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
                <a href="dashboard.php" class="btn-logout" style="background: var(--primary);">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-photo-large">
                <?php if ($voter['profile_photo']): ?>
                    <img src="../assets/uploads/voters/<?php echo $voter['profile_photo']; ?>" alt="Profile">
                <?php else: ?>
                    <img src="../assets/images/default-avatar.png" alt="Default Avatar">
                <?php endif; ?>
            </div>
            <h1 class="profile-name"><?php echo htmlspecialchars($voter['name']); ?></h1>
            <div class="profile-id"><?php echo $voter['voter_id']; ?></div>
            <div class="profile-status <?php echo $voter['is_verified'] ? 'status-verified' : 'status-pending'; ?>">
                <?php echo $voter['is_verified'] ? '✓ Verified Voter / प्रमाणित मतदाता' : '⏳ Pending Verification / प्रमाणीकरण हुन बाँकी'; ?>
            </div>
        </div>

        <div class="profile-grid">
            <!-- Personal Information -->
            <div class="profile-card">
                <h3 class="card-title"><i class="fas fa-user"></i> Personal Information / व्यक्तिगत जानकारी</h3>
                <div class="info-row">
                    <span class="info-label">Full Name / पूरा नाम</span>
                    <span class="info-value"><?php echo htmlspecialchars($voter['name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date of Birth / जन्म मिति</span>
                    <span class="info-value"><?php echo date('d F Y', strtotime($voter['dob'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Age / उमेर</span>
                    <span class="info-value"><?php echo calculateAge($voter['dob']); ?> years</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Citizenship / नागरिकता</span>
                    <span class="info-value"><?php echo htmlspecialchars($voter['citizenship_number']); ?></span>
                </div>
            </div>

            <!-- Family Information -->
            <div class="profile-card">
                <h3 class="card-title"><i class="fas fa-users"></i> Family Details / पारिवारिक विवरण</h3>
                <div class="info-row">
                    <span class="info-label">Father's Name / बुबाको नाम</span>
                    <span class="info-value"><?php echo htmlspecialchars($voter['father_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Mother's Name / आमाको नाम</span>
                    <span class="info-value"><?php echo htmlspecialchars($voter['mother_name']); ?></span>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="profile-card">
                <h3 class="card-title"><i class="fas fa-address-book"></i> Contact Information / सम्पर्क जानकारी</h3>
                <div class="info-row">
                    <span class="info-label">Phone / फोन</span>
                    <span class="info-value"><?php echo htmlspecialchars($voter['phone']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email / ईमेल</span>
                    <span class="info-value"><?php echo htmlspecialchars($voter['email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Address / ठेगाना</span>
                    <span class="info-value"><?php echo nl2br(htmlspecialchars($voter['address'])); ?></span>
                </div>
            </div>

            <!-- Location Information -->
            <div class="profile-card">
                <h3 class="card-title"><i class="fas fa-map-marker-alt"></i> Voting Location / मतदान स्थल</h3>
                <div class="info-row">
                    <span class="info-label">Province / प्रदेश</span>
                    <span class="info-value"><?php echo htmlspecialchars($voter['province_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">District / जिल्ला</span>
                    <span class="info-value"><?php echo htmlspecialchars($voter['district_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Constituency / क्षेत्र</span>
                    <span class="info-value"><?php echo $voter['constituency_number']; ?></span>
                </div>
            </div>

            <!-- Account Information -->
            <div class="profile-card">
                <h3 class="card-title"><i class="fas fa-clock"></i> Account Information / खाता जानकारी</h3>
                <div class="info-row">
                    <span class="info-label">Registered On / दर्ता मिति</span>
                    <span class="info-value"><?php echo date('d M Y', strtotime($voter['created_at'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Last Updated / अन्तिम अद्यावधिक</span>
                    <span class="info-value"><?php echo date('d M Y', strtotime($voter['updated_at'])); ?></span>
                </div>
            </div>

            <!-- Security Information -->
            <div class="profile-card">
                <h3 class="card-title"><i class="fas fa-shield-alt"></i> Security / सुरक्षा</h3>
                <div class="info-row">
                    <span class="info-label">Password / पासवर्ड</span>
                    <span class="info-value">••••••••</span>
                </div>
                <div class="info-row">
                    <span class="info-label">2-Factor Auth</span>
                    <span class="info-value">Not Enabled</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Last Login</span>
                    <span class="info-value"><?php echo date('d M Y H:i'); ?></span>
                </div>
            </div>
        </div>

        <div class="action-buttons">
            <a href="edit_profile.php" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Profile / प्रोफाइल सम्पादन
            </a>
            <a href="change_password.php" class="btn btn-outline">
                <i class="fas fa-key"></i> Change Password / पासवर्ड परिवर्तन
            </a>
            <a href="download_info.php" class="btn btn-secondary">
                <i class="fas fa-download"></i> Download Info / जानकारी डाउनलोड
            </a>
        </div>
    </div>
</body>
</html>