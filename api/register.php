<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($csrf_token)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token mapping']);
    exit;
}

$name = sanitize($_POST['name'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$role = sanitize($_POST['role'] ?? 'user');
$location = sanitize($_POST['location'] ?? '');

if (empty($name) || empty($email) || empty($password)) {
    http_response_code(400);    
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
    exit;
}

if ($password !== $confirm_password) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters long.']);
    exit;
}

if (!in_array($role, ['user', 'store_owner'])) {
    $role = 'user'; 
}

try {
    $db = new Database();
    
    $db->query('SELECT id FROM users WHERE email = :email LIMIT 1');
    $db->bind(':email', $email);
    $existing = $db->single();
    
    if($existing) {
        http_response_code(409); 
        echo json_encode(['status' => 'error', 'message' => 'This email is already registered. Please log in instead.']);
        exit;
    }
    
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    
    $status = 'active';
    
    $db->query('INSERT INTO users (name, email, password, location, role, status) VALUES (:name, :email, :password, :location, :role, :status)');
    $db->bind(':name', $name);
    $db->bind(':email', $email);
    $db->bind(':password', $hashed_password);
    $db->bind(':location', $location);
    $db->bind(':role', $role);
    $db->bind(':status', $status);
    
    if ($db->execute()) {
        $newUserId = $db->getDbh()->lastInsertId();
        
        session_regenerate_id(true);
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_role'] = $role;
        $_SESSION['last_activity'] = time();
        
        $redirect = url('index.php');
        if ($role === 'store_owner') {
            $redirect = url('store/dashboard.php');
        }
        
        http_response_code(201);
        echo json_encode([
            'status' => 'success', 
            'message' => 'Account constructed successfully! Authorizing redirect...',
            'redirect' => $redirect
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'We could not create your account at this time. Please try again.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log("Register Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred. Please try again later.']);
}
?>
