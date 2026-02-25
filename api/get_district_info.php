<?php
require_once '../includes/db_connection.php';

header('Content-Type: application/json');

$response = ['success' => false];

try {
    if (!isset($_GET['district_id']) || empty($_GET['district_id'])) {
        $response['message'] = 'District ID is required';
        echo json_encode($response);
        exit;
    }

    $district_id = intval($_GET['district_id']);
    
    $db = Database::getInstance()->getConnection();
    
    $query = "SELECT d.*, p.id as province_id, p.name as province_name 
              FROM districts d 
              JOIN provinces p ON d.province_id = p.id 
              WHERE d.id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $district_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $response['success'] = true;
        $response['district'] = $row;
    } else {
        $response['message'] = 'District not found';
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?>