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
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

$store_id = filter_var($_POST['store_id'] ?? '', FILTER_VALIDATE_INT);
$product_name = sanitize($_POST['product_name'] ?? '');
$brand = sanitize($_POST['brand'] ?? '');
$category_name = sanitize($_POST['category_name'] ?? '');
$price = filter_var($_POST['price'] ?? '', FILTER_VALIDATE_FLOAT);
$stock = filter_var($_POST['stock'] ?? 1, FILTER_VALIDATE_INT);
$latitude = filter_var($_POST['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
$longitude = filter_var($_POST['longitude'] ?? null, FILTER_VALIDATE_FLOAT);

if (!$store_id || !$product_name || empty($category_name) || $price === false || $price <= 0 || $stock < 0) {
    http_response_code(400);    
    echo json_encode(['status' => 'error', 'message' => 'Missing or invalid required fields.']);
    exit;
}


$image_url = null;
if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
    $file_tmp = $_FILES['product_image']['tmp_name'];
    $file_name = $_FILES['product_image']['name'];
    $file_size = $_FILES['product_image']['size'];
    
   
    $file_type = mime_content_type($file_tmp);
    if (!$file_type && function_exists('finfo_file')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $file_type = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);
    }
    if (!$file_type) {
        $file_type = $_FILES['product_image']['type'];
    }

   
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if (!in_array($file_type, $allowed_types)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid image format. Allowed: JPG, PNG, GIF, WebP']);
        exit;
    }

    if ($file_size > $max_size) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Image too large. Maximum 5MB allowed.']);
        exit;
    }

   
    $upload_dir = __DIR__ . '/../uploads/products/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

   
    $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
    $unique_filename = 'product_' . uniqid() . '.' . $file_ext;
    $upload_path = $upload_dir . $unique_filename;

    if (move_uploaded_file($file_tmp, $upload_path)) {
        $image_url = 'uploads/products/' . $unique_filename;
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to upload image.']);
        exit;
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Product image is required.']);
    exit;
}

try {
    $db = new Database();
    
   
    $db->query("SELECT id, status FROM stores WHERE id = :sid AND owner_id = :oid");
    $db->bind(':sid', $store_id);
    $db->bind(':oid', $_SESSION['user_id']);
    $store = $db->single();
    
    if (!$store) {
        http_response_code(403);    
        echo json_encode(['status' => 'error', 'message' => 'Store must exist first.']);
        exit;
    }
    
    
    $db->query("SELECT id FROM categories WHERE LOWER(name) = LOWER(:name)");
    $db->bind(':name', $category_name);
    $category_row = $db->single();
    if ($category_row) {
        $category_id = $category_row->id;
    } else {
        $db->query("INSERT INTO categories (name) VALUES (:name)");
        $db->bind(':name', $category_name);
        $db->execute();
        $category_id = $db->getDbh()->lastInsertId();
    }

   
    $db->query("INSERT INTO products (name, brand, category_id, image_url, created_at) VALUES (:name, :brand, :category_id, :image_url, datetime('now'))");
    $db->bind(':name', $product_name);
    $db->bind(':brand', $brand ?: NULL);
    $db->bind(':category_id', $category_id);
    $db->bind(':image_url', $image_url);
    
    if (!$db->execute()) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to create product.']);
        exit;
    }

    $product_id = $db->getDbh()->lastInsertId();
    
    
    $db->query("INSERT INTO listings (store_id, product_id, price, stock, latitude, longitude, status, last_updated) VALUES (:store_id, :product_id, :price, :stock, :latitude, :longitude, 'pending', datetime('now'))");
    $db->bind(':store_id', $store_id);
    $db->bind(':product_id', $product_id);
    $db->bind(':price', $price);
    $db->bind(':stock', $stock);
    $db->bind(':latitude', $latitude);
    $db->bind(':longitude', $longitude);
    
    if($db->execute()) {
        $listing_id = $db->getDbh()->lastInsertId();
        
       
        $db->query("INSERT INTO approval_requests (type, item_id, requested_by, status, created_at) VALUES ('listing', :item_id, :req_by, 'pending', datetime('now'))");
        $db->bind(':item_id', $listing_id);
        $db->bind(':req_by', $_SESSION['user_id']);
        $db->execute();
        
       
        http_response_code(201);
        echo json_encode(['status' => 'success', 'message' => 'Product created and listed successfully!']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to create listing.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    
    error_log("Add Product Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Something went wrong while saving your product. Please check your inputs and try again.']);
}
?>
