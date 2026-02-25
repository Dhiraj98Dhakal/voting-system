<?php
require_once '../includes/auth.php';
Auth::requireVoter();

$db = Database::getInstance()->getConnection();
$voter_id = $_SESSION['user_id'];

// Check if already voted PR
if (hasVoted($voter_id, 'PR')) {
    $_SESSION['error'] = 'You have already cast your PR vote';
    header("Location: dashboard.php");
    exit();
}

// Get voter's constituency
$query = "SELECT constituency_id, is_verified FROM voters WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $voter_id);
$stmt->execute();
$voter = $stmt->get_result()->fetch_assoc();

// Check if verified
if (!$voter['is_verified']) {
    $_SESSION['error'] = 'Your account is pending verification. You cannot vote yet.';
    header("Location: dashboard.php");
    exit();
}

// Get PR candidates (national level)
$candidates_query = "SELECT c.*, p.party_name, p.party_logo 
                     FROM candidates c 
                     JOIN parties p ON c.party_id = p.id 
                     WHERE c.election_type = 'PR'
                     ORDER BY p.party_name";
$candidates = $db->query($candidates_query);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid security token';
    } else {
        $candidate_id = intval($_POST['candidate_id']);
        
        // Verify candidate is PR type
        $verify_query = "SELECT id, candidate_name FROM candidates WHERE id = ? AND election_type = 'PR'";
        $verify_stmt = $db->prepare($verify_query);
        $verify_stmt->bind_param("i", $candidate_id);
        $verify_stmt->execute();
        $candidate = $verify_stmt->get_result()->fetch_assoc();
        
        if ($candidate) {
            // Record vote
            $vote_query = "INSERT INTO votes (voter_id, candidate_id, election_type) VALUES (?, ?, 'PR')";
            $vote_stmt = $db->prepare($vote_query);
            $vote_stmt->bind_param("ii", $voter_id, $candidate_id);
            
            if ($vote_stmt->execute()) {
                // Get voter email for confirmation
                $email_query = "SELECT email, name FROM voters WHERE id = ?";
                $email_stmt = $db->prepare($email_query);
                $email_stmt->bind_param("i", $voter_id);
                $email_stmt->execute();
                $voter_info = $email_stmt->get_result()->fetch_assoc();
                
                // Get party name
                $party_query = "SELECT party_name FROM parties p JOIN candidates c ON c.party_id = p.id WHERE c.id = ?";
                $party_stmt = $db->prepare($party_query);
                $party_stmt->bind_param("i", $candidate_id);
                $party_stmt->execute();
                $party = $party_stmt->get_result()->fetch_assoc();
                
                // Send confirmation email
                require_once '../includes/mailer.php';
                Mailer::sendVoteConfirmation(
                    $voter_info['email'],
                    $voter_info['name'],
                    'PR',
                    $candidate['candidate_name'],
                    $party['party_name']
                );
                
                $_SESSION['success'] = 'Your PR vote has been recorded successfully!';
                header("Location: vote_success.php?type=PR");
                exit();
            } else {
                $_SESSION['error'] = 'Error recording vote';
            }
        } else {
            $_SESSION['error'] = 'Invalid candidate selection';
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PR Voting - VoteNepal</title>
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

        .voting-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 36px;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .header h1 i {
            color: var(--primary);
            margin-right: 10px;
        }

        .header p {
            color: var(--gray);
            font-size: 16px;
        }

        .progress-bar {
            max-width: 600px;
            margin: 0 auto 40px;
            display: flex;
            justify-content: space-between;
            position: relative;
        }

        .progress-bar::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--light);
            z-index: 1;
        }

        .step {
            position: relative;
            z-index: 2;
            background: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: var(--gray);
        }

        .step.active {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
        }

        .step.completed {
            border-color: var(--success);
            background: var(--success);
            color: white;
        }

        .step-label {
            position: absolute;
            top: 45px;
            font-size: 12px;
            color: var(--gray);
            white-space: nowrap;
        }

        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .candidate-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: all 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .candidate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(67, 97, 238, 0.15);
        }

        .candidate-card.selected {
            border-color: var(--primary);
            background: #eef2ff;
        }

        .candidate-radio {
            display: none;
        }

        .candidate-content {
            padding: 25px;
        }

        .candidate-photo {
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid var(--light);
        }

        .candidate-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .candidate-info {
            text-align: center;
        }

        .candidate-info h3 {
            font-size: 20px;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .party-name {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 10px;
        }

        .party-logo {
            width: 50px;
            height: 50px;
            margin: 10px auto;
        }

        .party-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .select-indicator {
            width: 30px;
            height: 30px;
            border: 2px solid var(--light);
            border-radius: 50%;
            margin: 15px auto 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .candidate-card.selected .select-indicator {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
        }

        .select-indicator i {
            display: none;
        }

        .candidate-card.selected .select-indicator i {
            display: block;
        }

        .info-note {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }

        .voting-actions {
            text-align: center;
            margin-top: 40px;
            display: flex;
            gap: 20px;
            justify-content: center;
        }

        .btn {
            padding: 15px 40px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--gradient);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
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

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            text-align: center;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-content i {
            font-size: 60px;
            color: var(--warning);
            margin-bottom: 20px;
        }

        .modal-content h3 {
            font-size: 24px;
            margin-bottom: 15px;
        }

        .modal-content p {
            color: var(--gray);
            margin-bottom: 25px;
        }

        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        @media (max-width: 768px) {
            .candidates-grid {
                grid-template-columns: 1fr;
            }
            
            .voting-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="voting-container">
        <div class="header">
            <h1><i class="fas fa-list"></i> PR Voting</h1>
            <p>Proportional Representation - समानुपातिक निर्वाचन</p>
        </div>

        <!-- Progress Bar -->
        <div class="progress-bar">
            <div class="step completed">
                1
                <span class="step-label">Select</span>
            </div>
            <div class="step active">
                2
                <span class="step-label">Confirm</span>
            </div>
            <div class="step">
                3
                <span class="step-label">Complete</span>
            </div>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="info-note">
            <i class="fas fa-info-circle"></i>
            In PR voting, you vote for a party. The seats are distributed proportionally based on the votes each party receives.
            <br>समानुपातिक निर्वाचनमा तपाईंले दललाई मत दिनुहुन्छ। सिटहरू प्रत्येक दलले पाएको मतको आधारमा बाँडफाँड गरिन्छ।
        </div>

        <?php if ($candidates->num_rows == 0): ?>
            <div class="alert alert-warning">
                <i class="fas fa-info-circle"></i>
                No PR candidates found.
            </div>
        <?php else: ?>

        <form method="POST" id="voteForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="candidate_id" id="selectedCandidate" required>

            <div class="candidates-grid">
                <?php while($candidate = $candidates->fetch_assoc()): ?>
                <div class="candidate-card" onclick="selectCandidate(<?php echo $candidate['id']; ?>, this)">
                    <div class="candidate-content">
                        <div class="candidate-photo">
                            <?php if ($candidate['candidate_photo']): ?>
                                <img src="../assets/uploads/candidates/<?php echo $candidate['candidate_photo']; ?>" 
                                     alt="<?php echo htmlspecialchars($candidate['candidate_name']); ?>">
                            <?php else: ?>
                                <img src="../assets/images/default-candidate.png" alt="Candidate">
                            <?php endif; ?>
                        </div>
                        <div class="candidate-info">
                            <h3><?php echo htmlspecialchars($candidate['candidate_name']); ?></h3>
                            <div class="party-name"><?php echo htmlspecialchars($candidate['party_name']); ?></div>
                            <?php if ($candidate['party_logo']): ?>
                            <div class="party-logo">
                                <img src="../assets/uploads/parties/<?php echo $candidate['party_logo']; ?>" 
                                     alt="<?php echo htmlspecialchars($candidate['party_name']); ?>">
                            </div>
                            <?php endif; ?>
                            <div class="select-indicator">
                                <i class="fas fa-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <div class="voting-actions">
                <button type="button" class="btn btn-primary" onclick="showConfirmModal()" id="voteBtn" disabled>
                    <i class="fas fa-check-circle"></i>
                    Continue to Confirm
                </button>
                <a href="dashboard.php" class="btn btn-outline">
                    <i class="fas fa-times"></i>
                    Cancel
                </a>
            </div>
        </form>

        <?php endif; ?>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal" id="confirmModal">
        <div class="modal-content">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Confirm Your Vote</h3>
            <p>Are you sure you want to cast your PR vote for this candidate? This action cannot be undone.</p>
            <p>के तपाईं यो उम्मेदवारलाई मत दिन चाहनुहुन्छ? यो क्रिया फिर्ता गर्न सकिँदैन।</p>
            
            <div id="selectedCandidateInfo" style="margin: 20px 0; padding: 15px; background: var(--light); border-radius: 10px;">
                <!-- Will be filled by JavaScript -->
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-primary" onclick="submitVote()">
                    <i class="fas fa-check"></i>
                    Yes, Vote
                </button>
                <button type="button" class="btn btn-outline" onclick="hideConfirmModal()">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <script>
        let selectedId = null;
        let selectedCard = null;
        const candidates = <?php 
            $candidates->data_seek(0);
            $cand = [];
            while($row = $candidates->fetch_assoc()) {
                $cand[$row['id']] = $row;
            }
            echo json_encode($cand);
        ?>;

        function selectCandidate(id, card) {
            // Remove previous selection
            if (selectedCard) {
                selectedCard.classList.remove('selected');
            }
            
            // Set new selection
            selectedId = id;
            selectedCard = card;
            card.classList.add('selected');
            
            // Enable vote button
            document.getElementById('voteBtn').disabled = false;
            document.getElementById('selectedCandidate').value = id;
        }

        function showConfirmModal() {
            if (!selectedId) return;
            
            const candidate = candidates[selectedId];
            const infoDiv = document.getElementById('selectedCandidateInfo');
            
            infoDiv.innerHTML = `
                <p><strong>Candidate:</strong> ${candidate.candidate_name}</p>
                <p><strong>Party:</strong> ${candidate.party_name}</p>
                <p><strong>Election:</strong> PR</p>
            `;
            
            document.getElementById('confirmModal').classList.add('show');
        }

        function hideConfirmModal() {
            document.getElementById('confirmModal').classList.remove('show');
        }

        function submitVote() {
            document.getElementById('voteForm').submit();
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('confirmModal');
            if (event.target === modal) {
                modal.classList.remove('show');
            }
        }
    </script>
</body>
</html>