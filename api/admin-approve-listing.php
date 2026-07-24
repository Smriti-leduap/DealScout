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

$listing_id = filter_var($_POST['listing_id'] ?? '', FILTER_VALIDATE_INT);
$status = sanitize($_POST['status'] ?? '');

if (!$listing_id || !in_array($status, ['approved', 'rejected'])) {
    http_response_code(400);    
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters.']);
    exit;
}

try {
    $db = new Database();
    
    $db->query("SELECT id FROM listings WHERE id = :id");
    $db->bind(':id', $listing_id);
    if (!$db->single()) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Listing not found.']);
        exit;
    }
    
    $db->query("UPDATE listings SET status = :status WHERE id = :id");
    $db->bind(':status', $status);
    $db->bind(':id', $listing_id);
    
    if($db->execute()) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => "Listing successfully $status."]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database update logic failed.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'System architectural layer failure detected: ' . $e->getMessage()]);
}
?>
