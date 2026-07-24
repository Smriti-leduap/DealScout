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
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Admin access required.']);
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($csrf_token)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

$product_id = filter_var($_POST['product_id'] ?? '', FILTER_VALIDATE_INT);
$status = sanitize($_POST['status'] ?? '');
$rejection_reason = sanitize($_POST['rejection_reason'] ?? '');

if (!$product_id || !in_array($status, ['approved', 'rejected'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid product ID or status']);
    exit;
}

if ($status === 'rejected' && empty($rejection_reason)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Rejection reason is required when rejecting a product']);
    exit;
}

try {
    $db = new Database();
    
    $db->query("SELECT id, is_custom, approval_status FROM products WHERE id = :id");
    $db->bind(':id', $product_id);
    $product = $db->single();
    
    if (!$product) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Product not found']);
        exit;
    }
    
    if (!$product->is_custom) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Cannot approve/reject non-custom products']);
        exit;
    }
    
    if ($product->approval_status !== 'pending') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Product has already been reviewed']);
        exit;
    }
    
    if ($status === 'approved') {
        $db->query("UPDATE products SET approval_status = 'approved', reviewed_by = :admin_id, reviewed_at = CURRENT_TIMESTAMP WHERE id = :id");
        $db->bind(':admin_id', $_SESSION['user_id']);
        $db->bind(':id', $product_id);
    } else {
        $db->query("UPDATE products SET approval_status = 'rejected', rejection_reason = :reason, reviewed_by = :admin_id, reviewed_at = CURRENT_TIMESTAMP WHERE id = :id");
        $db->bind(':reason', $rejection_reason);
        $db->bind(':admin_id', $_SESSION['user_id']);
        $db->bind(':id', $product_id);
    }
    
    if ($db->execute()) {
        if ($status === 'approved') {
            $db->query("UPDATE listings SET status = 'approved' WHERE product_id = :id AND status = 'pending'");
        } else {
            $db->query("UPDATE listings SET status = 'rejected' WHERE product_id = :id AND status = 'pending'");
        }
        $db->bind(':id', $product_id);
        $db->execute();
        
        $db->query("UPDATE approval_requests SET status = :status, reviewed_by = :admin_id, review_date = CURRENT_TIMESTAMP 
                   WHERE type = 'product' AND item_id = :item_id");
        $db->bind(':status', $status);
        $db->bind(':admin_id', $_SESSION['user_id']);
        $db->bind(':item_id', $product_id);
        $db->execute();
        
        $db->query("INSERT INTO admin_action_log (admin_id, action, details, ip_address) 
                   VALUES (:admin_id, :action, :details, :ip)");
        $db->bind(':admin_id', $_SESSION['user_id']);
        $db->bind(':action', 'approve_custom_product');
        $details = "Product ID $product_id - $status";
        if ($status === 'rejected') {
            $details .= " - Reason: $rejection_reason";
        }
        $db->bind(':details', $details);
        $db->bind(':ip', $_SERVER['REMOTE_ADDR'] ?? 'Unknown');
        $db->execute();
        
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => "Product successfully $status",
            'product_id' => $product_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>
