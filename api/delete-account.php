<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($csrf_token)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

$confirmationText = trim($_POST['confirmation_text'] ?? '');
$userRole = $_SESSION['user_role'];
$userId = $_SESSION['user_id'];

if ($userRole === 'store_owner' && $confirmationText !== 'DELETE STORE') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Incorrect confirmation text. Please type exactly DELETE STORE.']);
    exit;
} elseif (($userRole === 'user' || $userRole === 'consumer') && $confirmationText !== 'DELETE USER') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Incorrect confirmation text. Please type exactly DELETE USER.']);
    exit;
} elseif ($userRole === 'admin') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Admins cannot delete their account this way.']);
    exit;
}

try {
    $db = new Database();

    $db->getDbh()->exec('PRAGMA foreign_keys = ON;');
    
    if ($userRole === 'store_owner') {
        $db->query("SELECT id FROM stores WHERE owner_id = :id");
        $db->bind(':id', $userId);
        $store = $db->single();
        if ($store) {
            $db->query("DELETE FROM listings WHERE store_id = :store_id");
            $db->bind(':store_id', $store->id);
            $db->execute();
            
            $db->query("DELETE FROM approval_requests WHERE type='store' AND item_id = :store_id");
            $db->bind(':store_id', $store->id);
            $db->execute();
            
            $db->query("DELETE FROM stores WHERE owner_id = :id");
            $db->bind(':id', $userId);
            $db->execute();
        }
    }

    $db->query("DELETE FROM users WHERE id = :id");
    $db->bind(':id', $userId);
    
    if ($db->execute()) {
        session_destroy();
        echo json_encode(['status' => 'success', 'message' => 'Account deleted successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete account.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log("Delete Account Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred. Please try again later.']);
}
?>
