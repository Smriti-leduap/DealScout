<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

if (!isLoggedIn() || $_SESSION['user_role'] !== 'store_owner') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($csrf_token)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid token']);
    exit;
}

$listing_id = filter_var($_POST['listing_id'] ?? '', FILTER_VALIDATE_INT);
$price = filter_var($_POST['price'] ?? '', FILTER_VALIDATE_FLOAT);
$stock = isset($_POST['stock']) ? filter_var($_POST['stock'], FILTER_VALIDATE_INT) : 1;

if (!$listing_id || $price === false || $price <= 0 || $stock === false || $stock < 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    exit;
}

try {
    $db = new Database();
    
    $db->query("SELECT l.id FROM listings l JOIN stores s ON l.store_id = s.id WHERE l.id = :lid AND s.owner_id = :oid");
    $db->bind(':lid', $listing_id);
    $db->bind(':oid', $_SESSION['user_id']);
    if (!$db->single()) {
        echo json_encode(['status' => 'error', 'message' => 'Listing not found or not yours']);
        exit;
    }
    
    $db->query("UPDATE listings SET price = :price, stock = :stock, last_updated = CURRENT_TIMESTAMP WHERE id = :lid");
    $db->bind(':price', $price);
    $db->bind(':stock', $stock);
    $db->bind(':lid', $listing_id);
    $db->execute();
    
    echo json_encode(['status' => 'success', 'message' => 'Listing updated successfully.']);
} catch(Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
