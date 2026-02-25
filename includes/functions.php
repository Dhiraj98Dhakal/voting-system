<?php
require_once 'db_connection.php';

// Nepal Ko lagi timezone set
date_default_timezone_set('Asia/Kathmandu');

/**
 * Generate unique Voter ID
 * Format: VOT2024000001
 */
function generateVoterId() {
    $db = Database::getInstance()->getConnection();
    $prefix = 'VOT' . date('Y');
    $query = "SELECT COUNT(*) as total FROM voters WHERE voter_id LIKE '$prefix%'";
    $result = $db->query($query);
    $row = $result->fetch_assoc();
    $number = str_pad($row['total'] + 1, 6, '0', STR_PAD_LEFT);
    return $prefix . $number;
}

/**
 * Calculate age from date of birth
 */
function calculateAge($dob) {
    $birthDate = new DateTime($dob);
    $today = new DateTime('today');
    $age = $birthDate->diff($today)->y;
    return $age;
}

/**
 * Upload image with validation
 */
function uploadImage($file, $folder, $old_file = null) {
    $target_dir = UPLOAD_PATH . $folder . '/';
    
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Delete old file if exists
    if ($old_file && file_exists($target_dir . $old_file)) {
        unlink($target_dir . $old_file);
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $target_file = $target_dir . $filename;
    
    // Check file size (5MB max)
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File too large. Max 5MB allowed.'];
    }
    
    // Allow certain file formats
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Only JPG, JPEG, PNG & GIF files are allowed.'];
    }
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'message' => 'Error uploading file.'];
    }
}

/**
 * Check if voter is eligible (18+ years)
 */
function isVoterEligible($dob) {
    return calculateAge($dob) >= 18;
}

/**
 * Check if voter has already voted for specific election type
 */
function hasVoted($voter_id, $election_type) {
    $db = Database::getInstance()->getConnection();
    $query = "SELECT id FROM votes WHERE voter_id = ? AND election_type = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("is", $voter_id, $election_type);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

/**
 * Sanitize input data
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token for security
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get all provinces
 */
function getProvinces() {
    $db = Database::getInstance()->getConnection();
    $query = "SELECT * FROM provinces ORDER BY id";
    $result = $db->query($query);
    $provinces = [];
    while ($row = $result->fetch_assoc()) {
        $provinces[] = $row;
    }
    return $provinces;
}

/**
 * Get districts by province ID
 */
function getDistricts($province_id) {
    $db = Database::getInstance()->getConnection();
    $query = "SELECT * FROM districts WHERE province_id = ? ORDER BY name";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $province_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $districts = [];
    while ($row = $result->fetch_assoc()) {
        $districts[] = $row;
    }
    return $districts;
}

/**
 * Get constituencies by district ID
 */
function getConstituencies($district_id) {
    $db = Database::getInstance()->getConnection();
    $query = "SELECT * FROM constituencies WHERE district_id = ? ORDER BY constituency_number";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $district_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $constituencies = [];
    while ($row = $result->fetch_assoc()) {
        $constituencies[] = $row;
    }
    return $constituencies;
}

/**
 * Get all political parties
 */
function getParties() {
    $db = Database::getInstance()->getConnection();
    $query = "SELECT * FROM parties ORDER BY party_name";
    $result = $db->query($query);
    $parties = [];
    while ($row = $result->fetch_assoc()) {
        $parties[] = $row;
    }
    return $parties;
}

/**
 * Get candidates with filters
 */
function getCandidates($election_type = null, $constituency_id = null) {
    $db = Database::getInstance()->getConnection();
    $query = "SELECT c.*, p.party_name, p.party_logo 
              FROM candidates c 
              JOIN parties p ON c.party_id = p.id 
              WHERE 1=1";
    $params = [];
    $types = "";
    
    if ($election_type) {
        $query .= " AND c.election_type = ?";
        $params[] = $election_type;
        $types .= "s";
    }
    
    if ($constituency_id) {
        $query .= " AND c.constituency_id = ?";
        $params[] = $constituency_id;
        $types .= "i";
    }
    
    $query .= " ORDER BY p.party_name, c.candidate_name";
    
    $stmt = $db->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $candidates = [];
    while ($row = $result->fetch_assoc()) {
        $candidates[] = $row;
    }
    return $candidates;
}

/**
 * Get voter details by ID
 */
function getVoterById($id) {
    $db = Database::getInstance()->getConnection();
    $query = "SELECT v.*, p.name as province_name, d.name as district_name, 
              c.constituency_number 
              FROM voters v 
              LEFT JOIN provinces p ON v.province_id = p.id 
              LEFT JOIN districts d ON v.district_id = d.id 
              LEFT JOIN constituencies c ON v.constituency_id = c.id 
              WHERE v.id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Get candidate details by ID
 */
function getCandidateById($id) {
    $db = Database::getInstance()->getConnection();
    $query = "SELECT c.*, p.party_name, p.party_logo 
              FROM candidates c 
              JOIN parties p ON c.party_id = p.id 
              WHERE c.id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Get party details by ID
 */
function getPartyById($id) {
    $db = Database::getInstance()->getConnection();
    $query = "SELECT * FROM parties WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Get election results
 */
function getElectionResults($type = 'FPTP') {
    $db = Database::getInstance()->getConnection();
    
    if ($type == 'FPTP') {
        $query = "SELECT 
                    p.name as province_name,
                    d.name as district_name,
                    cn.constituency_number,
                    cand.candidate_name,
                    party.party_name,
                    party.party_logo,
                    COUNT(v.id) as vote_count
                  FROM constituencies cn
                  JOIN districts d ON cn.district_id = d.id
                  JOIN provinces p ON d.province_id = p.id
                  LEFT JOIN candidates cand ON cand.constituency_id = cn.id AND cand.election_type = 'FPTP'
                  LEFT JOIN parties party ON cand.party_id = party.id
                  LEFT JOIN votes v ON v.candidate_id = cand.id
                  GROUP BY cn.id, cand.id
                  ORDER BY p.name, d.name, cn.constituency_number";
    } else {
        $query = "SELECT 
                    party.party_name,
                    party.party_logo,
                    COUNT(v.id) as vote_count,
                    (COUNT(v.id) * 100.0 / (SELECT COUNT(*) FROM votes WHERE election_type = 'PR')) as percentage
                  FROM parties party
                  LEFT JOIN candidates cand ON cand.party_id = party.id AND cand.election_type = 'PR'
                  LEFT JOIN votes v ON v.candidate_id = cand.id
                  GROUP BY party.id
                  ORDER BY vote_count DESC";
    }
    
    $result = $db->query($query);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}

/**
 * Format date in Nepali style
 */
function formatDateNepali($date) {
    if (!$date) return '';
    $timestamp = strtotime($date);
    return date('d F Y, h:i A', $timestamp);
}

/**
 * Get voter turnout percentage
 */
function getVoterTurnout() {
    $db = Database::getInstance()->getConnection();
    
    $total_voters = $db->query("SELECT COUNT(*) as total FROM voters")->fetch_assoc()['total'];
    $total_votes = $db->query("SELECT COUNT(*) as total FROM votes")->fetch_assoc()['total'];
    
    if ($total_voters > 0 && $total_votes > 0) {
        $turnout = ($total_votes / (2 * $total_voters)) * 100;
        return round($turnout, 2);
    }
    return 0;
}

/**
 * Check if email exists
 */
function emailExists($email, $exclude_id = null) {
    $db = Database::getInstance()->getConnection();
    $query = "SELECT id FROM voters WHERE email = ?";
    $params = [$email];
    $types = "s";
    
    if ($exclude_id) {
        $query .= " AND id != ?";
        $params[] = $exclude_id;
        $types .= "i";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

/**
 * Check if citizenship number exists
 */
function citizenshipExists($citizenship, $exclude_id = null) {
    $db = Database::getInstance()->getConnection();
    $query = "SELECT id FROM voters WHERE citizenship_number = ?";
    $params = [$citizenship];
    $types = "s";
    
    if ($exclude_id) {
        $query .= " AND id != ?";
        $params[] = $exclude_id;
        $types .= "i";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

/**
 * Log admin activity
 */
function logActivity($admin_id, $action, $details) {
    $db = Database::getInstance()->getConnection();
    $query = "INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)";
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $db->prepare($query);
    $stmt->bind_param("isss", $admin_id, $action, $details, $ip);
    return $stmt->execute();
}

/**
 * Send email notification (basic)
 */
function sendEmail($to, $subject, $message) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: VoteNepal <noreply@votenepal.gov.np>' . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Generate random password
 */
function generateRandomPassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

/**
 * Validate phone number (Nepal format)
 */
function validatePhone($phone) {
    // Nepal phone numbers: 10 digits, starts with 9
    return preg_match('/^[9][0-9]{9}$/', $phone);
}

/**
 * Get file extension
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Create thumbnail from image
 */
function createThumbnail($source, $destination, $width = 200, $height = 200) {
    list($src_width, $src_height, $type) = getimagesize($source);
    
    $thumb = imagecreatetruecolor($width, $height);
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            $src = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $src = imagecreatefrompng($source);
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            break;
        case IMAGETYPE_GIF:
            $src = imagecreatefromgif($source);
            break;
        default:
            return false;
    }
    
    imagecopyresampled($thumb, $src, 0, 0, 0, 0, $width, $height, $src_width, $src_height);
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($thumb, $destination, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($thumb, $destination, 9);
            break;
        case IMAGETYPE_GIF:
            imagegif($thumb, $destination);
            break;
    }
    
    imagedestroy($src);
    imagedestroy($thumb);
    return true;
}

/**
 * Get system statistics
 */
function getSystemStats() {
    $db = Database::getInstance()->getConnection();
    
    $stats = [];
    
    // Total voters
    $result = $db->query("SELECT COUNT(*) as total FROM voters");
    $stats['total_voters'] = $result->fetch_assoc()['total'];
    
    // Verified voters
    $result = $db->query("SELECT COUNT(*) as total FROM voters WHERE is_verified = 1");
    $stats['verified_voters'] = $result->fetch_assoc()['total'];
    
    // Total parties
    $result = $db->query("SELECT COUNT(*) as total FROM parties");
    $stats['total_parties'] = $result->fetch_assoc()['total'];
    
    // Total candidates
    $result = $db->query("SELECT COUNT(*) as total FROM candidates");
    $stats['total_candidates'] = $result->fetch_assoc()['total'];
    
    // FPTP candidates
    $result = $db->query("SELECT COUNT(*) as total FROM candidates WHERE election_type = 'FPTP'");
    $stats['fptp_candidates'] = $result->fetch_assoc()['total'];
    
    // PR candidates
    $result = $db->query("SELECT COUNT(*) as total FROM candidates WHERE election_type = 'PR'");
    $stats['pr_candidates'] = $result->fetch_assoc()['total'];
    
    // Total votes
    $result = $db->query("SELECT COUNT(*) as total FROM votes");
    $stats['total_votes'] = $result->fetch_assoc()['total'];
    
    // FPTP votes
    $result = $db->query("SELECT COUNT(*) as total FROM votes WHERE election_type = 'FPTP'");
    $stats['fptp_votes'] = $result->fetch_assoc()['total'];
    
    // PR votes
    $result = $db->query("SELECT COUNT(*) as total FROM votes WHERE election_type = 'PR'");
    $stats['pr_votes'] = $result->fetch_assoc()['total'];
    
    // Total constituencies
    $result = $db->query("SELECT COUNT(*) as total FROM constituencies");
    $stats['total_constituencies'] = $result->fetch_assoc()['total'];
    
    return $stats;
}

/**
 * Get recent activities
 */
function getRecentActivities($limit = 10) {
    $db = Database::getInstance()->getConnection();
    
    $activities = [];
    
    // Recent voters
    $voters = $db->query("SELECT 'voter' as type, name, created_at, 'registered' as action 
                          FROM voters ORDER BY created_at DESC LIMIT $limit");
    while ($row = $voters->fetch_assoc()) {
        $activities[] = $row;
    }
    
    // Recent votes
    $votes = $db->query("SELECT 'vote' as type, CONCAT(voters.name, ' voted') as name, 
                         votes.voted_at as created_at, 'voted' as action 
                         FROM votes JOIN voters ON votes.voter_id = voters.id 
                         ORDER BY votes.voted_at DESC LIMIT $limit");
    while ($row = $votes->fetch_assoc()) {
        $activities[] = $row;
    }
    
    // Sort by date
    usort($activities, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    return array_slice($activities, 0, $limit);
}

/**
 * Convert number to Nepali words (for PDF)
 */
function numberToNepaliWords($number) {
    $words = [
        0 => 'शून्य', 1 => 'एक', 2 => 'दुई', 3 => 'तीन', 4 => 'चार',
        5 => 'पाँच', 6 => 'छ', 7 => 'सात', 8 => 'आठ', 9 => 'नौ',
        10 => 'दश', 11 => 'एघार', 12 => 'बाह्र', 13 => 'तेह्र', 14 => 'चौध',
        15 => 'पन्ध्र', 16 => 'सोह्र', 17 => 'सत्र', 18 => 'अठार', 19 => 'उन्नाइस',
        20 => 'बीस'
    ];
    
    if ($number <= 20) {
        return $words[$number];
    }
    
    return $number; // Return as is for complex numbers
}

/**
 * Check if user is logged in (alias for isLoggedIn from auth.php)
 */
function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}



// Add this function to your functions.php
function timeAgo($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);
    
    if ($seconds <= 60) {
        return "Just now";
    } else if ($minutes <= 60) {
        return ($minutes == 1) ? "1 min ago" : "$minutes mins ago";
    } else if ($hours <= 24) {
        return ($hours == 1) ? "1 hour ago" : "$hours hours ago";
    } else if ($days <= 7) {
        return ($days == 1) ? "yesterday" : "$days days ago";
    } else if ($weeks <= 4.3) {
        return ($weeks == 1) ? "1 week ago" : "$weeks weeks ago";
    } else if ($months <= 12) {
        return ($months == 1) ? "1 month ago" : "$months months ago";
    } else {
        return ($years == 1) ? "1 year ago" : "$years years ago";
    }
}

/**
 * Get current user info
 */
function getCurrentUser() {
    if (!isUserLoggedIn()) {
        return null;
    }
    
    $db = Database::getInstance()->getConnection();
    
    if ($_SESSION['user_type'] == 'admin') {
        $query = "SELECT * FROM admins WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    } else {
        return getVoterById($_SESSION['user_id']);
    }
}

?>