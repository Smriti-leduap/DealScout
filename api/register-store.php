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
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($csrf_token)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

$name = sanitize($_POST['name'] ?? '');
$address = sanitize($_POST['address'] ?? '');
$latitude = filter_var($_POST['latitude'] ?? '', FILTER_VALIDATE_FLOAT);
$longitude = filter_var($_POST['longitude'] ?? '', FILTER_VALIDATE_FLOAT);
$phone = sanitize($_POST['phone'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);

if (empty($name) || empty($address) || empty($email) || $latitude === false || $longitude === false) {
    http_response_code(400);    
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields logically.']);
    exit;
}

try {
    $db = new Database();
    
    $db->query('SELECT id FROM stores WHERE owner_id = :owner_id LIMIT 1');
    $db->bind(':owner_id', $_SESSION['user_id']);
    if ($db->single()) {
        http_response_code(400); 
        echo json_encode(['status' => 'error', 'message' => 'You already have a registered store on this account.']);
        exit;
    }
    
    $db->query('INSERT INTO stores (name, address, latitude, longitude, phone, email, owner_id, status) VALUES (:name, :address, :lat, :lng, :phone, :email, :owner, :status)');
    $db->bind(':name', $name);
    $db->bind(':address', $address);
    $db->bind(':lat', $latitude);
    $db->bind(':lng', $longitude);
    $db->bind(':phone', $phone);
    $db->bind(':email', $email);
    $db->bind(':owner', $_SESSION['user_id']);
    $db->bind(':status', 'pending');
    
    if ($db->execute()) {
        $storeId = $db->getDbh()->lastInsertId();
        
        http_response_code(201);
        echo json_encode(['status' => 'success', 'message' => 'Store submitted successfully. Please wait for admin approval.']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to save your store. Please try again.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log("Register Store Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred. Please check your inputs and try again.']);
}
?>
