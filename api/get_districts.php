<?php
// Absolute path - तपाईंको system अनुसार मिलाउनुहोस्
require_once 'C:/wamp64/www/voting-system/includes/db_connection.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$response = ['success' => false, 'districts' => [], 'message' => ''];

try {
    // Check if province_id is set
    if (!isset($_GET['province_id']) || empty($_GET['province_id'])) {
        $response['message'] = 'Province ID is required';
        echo json_encode($response);
        exit;
    }

    $province_id = intval($_GET['province_id']);
    
    if ($province_id <= 0) {
        $response['message'] = 'Invalid Province ID';
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
    
    // Query districts
    $query = "SELECT id, name, name_nepali FROM districts WHERE province_id = ? ORDER BY name";
    $stmt = $db->prepare($query);
    
    if (!$stmt) {
        $response['message'] = 'Prepare failed: ' . $db->error;
        echo json_encode($response);
        exit;
    }
    
    $stmt->bind_param("i", $province_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $response['districts'][] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'name_nepali' => $row['name_nepali'] ?? ''
        ];
    }
    
    $response['success'] = true;
    $response['count'] = count($response['districts']);
    
    if ($response['count'] == 0) {
        $response['message'] = 'No districts found for this province';
    }
    
} catch (Exception $e) {
    $response['message'] = 'Exception: ' . $e->getMessage();
}

echo json_encode($response);
?>