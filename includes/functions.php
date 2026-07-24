<?php
session_start();

function url($path) {
    return '/Smriti/' . ltrim($path, '/');
}

function redirect($page) {
    header('Location: ' . url($page));
    exit;
}

function isLoggedIn() {
    if (isset($_SESSION['user_id'])) {
        return true;
    } else {
        return false;
    }
}

function sanitize($string) {
    return htmlspecialchars(trim($string ?? ''), ENT_QUOTES, 'UTF-8');
}

function jsonResponse($data, $statusCode = 200) {
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }
    return false;
}
?>
