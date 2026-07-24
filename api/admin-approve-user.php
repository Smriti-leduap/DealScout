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
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

$user_id = filter_var($_POST['user_id'] ?? '', FILTER_VALIDATE_INT);
$status = sanitize($_POST['status'] ?? '');

if (!$user_id || !in_array($status, ['active', 'pending', 'inactive'])) {
    http_response_code(400);    
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters.']);
    exit;
}

try {
    $db = new Database();
    
    $db->query("SELECT id FROM users WHERE id = :id");
    $db->bind(':id', $user_id);
    if (!$db->single()) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'User not found.']);
        exit;
    }
    
    $db->query("UPDATE users SET status = :status WHERE id = :id");
    $db->bind(':status', $status);
    $db->bind(':id', $user_id);
    
    if($db->execute()) {
        $db->query("UPDATE approval_requests SET status = :status WHERE type = 'user' AND item_id = :id");
        $db->bind(':status', $status === 'active' ? 'approved' : 'rejected');
        $db->bind(':id', $user_id);
        $db->execute();

        $details = "User ID $user_id status updated to $status";
        $db->query("INSERT INTO admin_action_log (admin_id, action, details, ip_address) VALUES (:admin_id, :action, :details, :ip)");
        $db->bind(':admin_id', $_SESSION['user_id'] ?? 3); // Fallback to Super Admin ID if not set
        $db->bind(':action', 'USER_STATUS_UPDATE');
        $db->bind(':details', $details);
        $db->bind(':ip', $_SERVER['REMOTE_ADDR']);
        $db->execute();

        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => "User successfully set to $status."]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to update user status.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
