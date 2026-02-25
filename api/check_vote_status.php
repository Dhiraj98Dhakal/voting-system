<?php
require_once '../../includes/session.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'voter') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$voter_id = $_SESSION['user_id'];
$type = isset($_GET['type']) ? $_GET['type'] : '';

$status = [
    'fptp_voted' => hasVoted($voter_id, 'FPTP'),
    'pr_voted' => hasVoted($voter_id, 'PR'),
    'timestamp' => time()
];

echo json_encode($status);
?>