<?php
require_once '../includes/auth.php';
Auth::requireVoter();

$db = Database::getInstance()->getConnection();
$voter_id = $_SESSION['user_id'];

if (!isset($_SESSION['pending_vote'])) {
    header("Location: dashboard.php");
    exit();
}

$pending = $_SESSION['pending_vote'];
$candidate_id = $pending['candidate_id'];
$type = $pending['type'];

// Check if user confirmed
if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    // Check if already voted
    if (hasVoted($voter_id, $type)) {
        $_SESSION['error'] = "You have already voted in this election";
        unset($_SESSION['pending_vote']);
        header("Location: dashboard.php");
        exit();
    }
    
    // Record vote
    $query = "INSERT INTO votes (voter_id, candidate_id, election_type) VALUES (?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->bind_param("iis", $voter_id, $candidate_id, $type);
    
    if ($stmt->execute()) {
        unset($_SESSION['pending_vote']);
        $_SESSION['success'] = "Your vote has been recorded successfully!";
        header("Location: vote_success.php?type=$type");
        exit();
    } else {
        $_SESSION['error'] = "Error recording vote";
        header("Location: vote_fptp.php");
        exit();
    }
} else {
    // User cancelled
    unset($_SESSION['pending_vote']);
    $_SESSION['info'] = "Vote cancelled";
    header("Location: " . ($type == 'FPTP' ? 'vote_fptp.php' : 'vote_pr.php'));
    exit();
}
?>