<?php
// Absolute path - तपाईंको system अनुसार मिलाउनुहोस्
require_once 'C:/wamp64/www/voting-system/includes/db_connection.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$response = ['success' => false, 'constituencies' => [], 'message' => ''];

try {
    // Check if district_id is set
    if (!isset($_GET['district_id']) || empty($_GET['district_id'])) {
        $response['message'] = 'District ID is required';
        echo json_encode($response);
        exit;
    }

    $district_id = intval($_GET['district_id']);
    
    if ($district_id <= 0) {
        $response['message'] = 'Invalid District ID';
        echo json_encode($response);
        exit;
    }

    // Get database connection
    $db = Database::getInstance()->getConnection();
    
    if (!$db) {
        $response['message'] = 'Database connection failed';
        echo json_encode($response);
        exit;
    }
    
    // Query constituencies
    $query = "SELECT id, constituency_number FROM constituencies WHERE district_id = ? ORDER BY constituency_number";
    $stmt = $db->prepare($query);
    
    if (!$stmt) {
        $response['message'] = 'Prepare failed: ' . $db->error;
        echo json_encode($response);
        exit;
    }
    
    $stmt->bind_param("i", $district_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $response['constituencies'][] = [
            'id' => $row['id'],
            'constituency_number' => $row['constituency_number'],
            'name' => 'Constituency ' . $row['constituency_number']
        ];
    }
    
    $response['success'] = true;
    $response['count'] = count($response['constituencies']);
    
    if ($response['count'] == 0) {
        $response['message'] = 'No constituencies found for this district';
    }
    
} catch (Exception $e) {
    $response['message'] = 'Exception: ' . $e->getMessage();
}

echo json_encode($response);
?>