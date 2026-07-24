<?php
// api/login.php
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
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}


$email = sanitize($_POST['email']);
$password = $_POST['password'];

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all fields']);
    exit;
}

try {
    $db = new Database();
    
    $db->query('SELECT * FROM users WHERE email = :email LIMIT 1');
    $db->bind(':email', $email);
    $user = $db->single();
    
    if ($user && password_verify($password, $user->password)) {
        if ($user->status === 'inactive') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Your account has been deactivated or suspended by an administrator.']);
            exit;
        }
        
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_name'] = $user->name;
        $_SESSION['user_role'] = $user->role;
        $_SESSION['last_activity'] = time(); // For session timeout check
        
        $redirect = url('index.php'); // Default redirect
        
        if (!empty($_POST['redirect_url'])) {
            $redirect = filter_var($_POST['redirect_url'], FILTER_SANITIZE_URL);
        } else if ($user->role === 'admin') {
            $redirect = url('admin/dashboard.php');
        } elseif ($user->role === 'store_owner') {
            $redirect = url('store/dashboard.php');
        }
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Login successful. Redirecting...',
            'redirect' => $redirect
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
}
?>
