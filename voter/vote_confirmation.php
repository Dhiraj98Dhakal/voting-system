<?php
require_once '../includes/auth.php';
Auth::requireVoter();

$db = Database::getInstance()->getConnection();
$voter_id = $_SESSION['user_id'];

// Get voter's pending vote from session
if (!isset($_SESSION['pending_vote'])) {
    header("Location: dashboard.php");
    exit();
}

$pending = $_SESSION['pending_vote'];
$candidate_id = $pending['candidate_id'];
$type = $pending['type'];

// Get candidate details
$query = "SELECT c.*, p.party_name, p.party_logo 
          FROM candidates c 
          JOIN parties p ON c.party_id = p.id 
          WHERE c.id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $candidate_id);
$stmt->execute();
$candidate = $stmt->get_result()->fetch_assoc();

if (!$candidate) {
    unset($_SESSION['pending_vote']);
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Vote - VoteNepal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .confirmation-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
        }

        .confirmation-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            text-align: center;
        }

        .candidate-summary {
            background: var(--light);
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
        }

        .candidate-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 20px;
            overflow: hidden;
            border: 4px solid var(--primary);
        }

        .candidate-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .actions {
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
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <div class="confirmation-card">
            <h1><i class="fas fa-vote-yea" style="color: var(--primary);"></i> Confirm Your Vote</h1>
            <p>Please verify your selection before confirming</p>

            <div class="candidate-summary">
                <div class="candidate-photo">
                    <?php if ($candidate['candidate_photo']): ?>
                        <img src="../assets/uploads/candidates/<?php echo $candidate['candidate_photo']; ?>" alt="">
                    <?php else: ?>
                        <img src="../assets/images/default-candidate.png" alt="">
                    <?php endif; ?>
                </div>
                <h2><?php echo htmlspecialchars($candidate['candidate_name']); ?></h2>
                <p style="color: var(--primary); font-weight: 600;"><?php echo htmlspecialchars($candidate['party_name']); ?></p>
                <p>Election Type: <strong><?php echo $type; ?></strong></p>
            </div>

            <div class="warning-box">
                <i class="fas fa-exclamation-triangle"></i>
                <p>This action cannot be undone. Make sure you have selected the right candidate.</p>
            </div>

            <div class="actions">
                <a href="vote_process.php?confirm=yes" class="btn btn-primary">
                    <i class="fas fa-check"></i> Yes, Confirm Vote
                </a>
                <a href="vote_process.php?confirm=no" class="btn btn-danger">
                    <i class="fas fa-times"></i> No, Cancel
                </a>
            </div>
        </div>
    </div>
</body>
</html>