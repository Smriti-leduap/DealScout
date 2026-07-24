<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized admin access']);
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($csrf_token)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF parameter']);
    exit;
}

$store_id = filter_var($_POST['store_id'] ?? '', FILTER_VALIDATE_INT);
$status = sanitize($_POST['status'] ?? '');

if (!$store_id || !in_array($status, ['approved', 'rejected'])) {
    http_response_code(400);    
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters.']);
    exit;
}

try {
    $db = new Database();
    
    $db->query("SELECT id FROM stores WHERE id = :id");
    $db->bind(':id', $store_id);
    if (!$db->single()) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Store not found.']);
        exit;
    }
    
    $db->query("UPDATE stores SET status = :status WHERE id = :id");
    $db->bind(':status', $status);
    $db->bind(':id', $store_id);
    
    if($db->execute()) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => "Store successfully $status."]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database update logic failed.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'System architectural layer failure detected: ' . $e->getMessage()]);
}
?>
