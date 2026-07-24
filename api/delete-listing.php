<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

if (!isLoggedIn() || $_SESSION['user_role'] !== 'store_owner') {
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($csrf_token)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid token']);
    exit;
}

$listing_id = filter_var($_POST['listing_id'] ?? '', FILTER_VALIDATE_INT);

try {
    $db = new Database();
    
    $db->query("SELECT l.id FROM listings l JOIN stores s ON l.store_id = s.id WHERE l.id = :lid AND s.owner_id = :oid");
    $db->bind(':lid', $listing_id);
    $db->bind(':oid', $_SESSION['user_id']);
    if (!$db->single()) {
        echo json_encode(['status' => 'error', 'message' => 'Not found']);
        exit;
    }
    
    $db->query("DELETE FROM listings WHERE id = :lid");
    $db->bind(':lid', $listing_id);
    $db->execute();
    
    echo json_encode(['status' => 'success', 'message' => 'Product removed from inventory.']);
} catch(Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
