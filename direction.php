<?php
require_once __DIR__ . '/includes/header.php';

$targetUrl = $_GET['url'] ?? '';

if (empty($targetUrl)) {
    redirect('index.php');
}

if (strpos($targetUrl, 'https://www.google.com/maps/dir/') !== 0) {
    redirect('index.php');
}

if (!isLoggedIn()) {
    $loginUrl = url('login.php') . '?redirect=' . urlencode($targetUrl);
    redirect($loginUrl);
}

header('Location: ' . $targetUrl);
exit;
?>
