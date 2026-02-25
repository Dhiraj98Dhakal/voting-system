<?php
require_once '../includes/db_connection.php';

header('Content-Type: application/json');

// Security: Rate limiting
$ip = $_SERVER['REMOTE_ADDR'];
$cache_file = "../cache/live_$ip.json";
$cache_time = 10; // 10 seconds between requests

if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many requests']);
    exit;
}

file_put_contents($cache_file, time());

$db = Database::getInstance()->getConnection();

// Get live counts
$total_votes = $db->query("SELECT COUNT(*) as total FROM votes")->fetch_assoc()['total'];
$fptp_votes = $db->query("SELECT COUNT(*) as total FROM votes WHERE election_type = 'FPTP'")->fetch_assoc()['total'];
$pr_votes = $db->query("SELECT COUNT(*) as total FROM votes WHERE election_type = 'PR'")->fetch_assoc()['total'];
$total_voters = $db->query("SELECT COUNT(*) as total FROM voters WHERE is_verified = 1")->fetch_assoc()['total'];
$turnout = $total_voters > 0 ? round(($total_votes / ($total_voters * 2)) * 100, 1) : 0;

// Get top parties
$top_parties = $db->query("
    SELECT 
        p.party_name,
        p.party_logo,
        COUNT(v.id) as vote_count
    FROM parties p
    LEFT JOIN candidates c ON c.party_id = p.id
    LEFT JOIN votes v ON v.candidate_id = c.id
    GROUP BY p.id
    ORDER BY vote_count DESC
    LIMIT 5
");

$parties = [];
while ($party = $top_parties->fetch_assoc()) {
    $parties[] = $party;
}

echo json_encode([
    'total_votes' => (int)$total_votes,
    'fptp_votes' => (int)$fptp_votes,
    'pr_votes' => (int)$pr_votes,
    'turnout' => (float)$turnout,
    'top_parties' => $parties,
    'timestamp' => time()
]);
?>