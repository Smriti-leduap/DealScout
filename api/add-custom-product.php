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
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Only store owners can add custom products.']);
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($csrf_token)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

$product_name = sanitize($_POST['product_name'] ?? '');
$brand = sanitize($_POST['brand'] ?? '');
$category_name = sanitize($_POST['category_name'] ?? '');
$description = sanitize($_POST['description'] ?? '');
$price = filter_var($_POST['price'] ?? '', FILTER_VALIDATE_FLOAT);
$store_id = filter_var($_POST['store_id'] ?? '', FILTER_VALIDATE_INT);

if (empty($product_name) || !$price || $price <= 0 || !$store_id || empty($category_name)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields: product name, price, store, and category are mandatory.']);
    exit;
}

if (strlen($product_name) < 3 || strlen($product_name) > 100) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Product name must be between 3 and 100 characters.']);
    exit;
}

$image_path = null;
if (!empty($_FILES['product_image']['name'])) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    $max_size = 5 * 1024 * 1024; 
    
    $file = $_FILES['product_image'];
    
    if (!in_array($file['type'], $allowed_types)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid image format. Allowed: JPEG, PNG, WebP']);
        exit;
    }
    
    if ($file['size'] > $max_size) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Image size must not exceed 5MB']);
        exit;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Image upload failed']);
        exit;
    }
    
    $upload_dir = __DIR__ . '/../uploads/products/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'product_' . $_SESSION['user_id'] . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $file_path = $upload_dir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to save image file']);
        exit;
    }
    
    $image_path = 'uploads/products/' . $filename;
}

try {
    $db = new Database();
    
    $db->query("SELECT id, status FROM stores WHERE id = :sid AND owner_id = :oid");
    $db->bind(':sid', $store_id);
    $db->bind(':oid', $_SESSION['user_id']);
    $store = $db->single();
    
    if (!$store) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Store not found or does not belong to you']);
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
    
    $db->query("SELECT id FROM products WHERE LOWER(name) = LOWER(:name) AND created_by = :uid AND is_custom = TRUE");
    $db->bind(':name', $product_name);
    $db->bind(':uid', $_SESSION['user_id']);
    if ($db->single()) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'You have already created a product with this name. Please use a different name or edit the existing one.']);
        exit;
    }
    
    $db->query("INSERT INTO products (name, brand, category_id, description, image_url, image_file_path, created_by, is_custom, approval_status) 
               VALUES (:name, :brand, :cat_id, :desc, :img_url, :img_path, :creator_id, TRUE, 'pending')");
    $db->bind(':name', $product_name);
    $db->bind(':brand', !empty($brand) ? $brand : 'Custom');
    $db->bind(':cat_id', $category_id);
    $db->bind(':desc', $description);
    $db->bind(':img_url', !empty($image_path) ? $image_path : 'https://via.placeholder.com/300?text=' . urlencode($product_name));
    $db->bind(':img_path', $image_path);
    $db->bind(':creator_id', $_SESSION['user_id']);
    
    if ($db->execute()) {
        $product_id = $db->getDbh()->lastInsertId();
        
        $db->query("INSERT INTO listings (store_id, product_id, price, status) VALUES (:store_id, :product_id, :price, 'pending')");
        $db->bind(':store_id', $store_id);
        $db->bind(':product_id', $product_id);
        $db->bind(':price', $price);
        
        if ($db->execute()) {
            $listing_id = $db->getDbh()->lastInsertId();
            
            $db->query("INSERT INTO approval_requests (type, item_id, requested_by, status) VALUES ('product', :product_id, :user_id, 'pending')");
            $db->bind(':product_id', $product_id);
            $db->bind(':user_id', $_SESSION['user_id']);
            $db->execute();
            
            http_response_code(201);
            echo json_encode([
                'status' => 'success',
                'message' => 'Product created and listed successfully!',
                'product_id' => $product_id,
                'listing_id' => $listing_id
            ]);
        } else {
            $db->query("DELETE FROM products WHERE id = :id");
            $db->bind(':id', $product_id);
            $db->execute();
            
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to create product listing']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to create product']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("Add Custom Product Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Something went wrong while saving your product. Please check your inputs and try again.']);
}
?>
