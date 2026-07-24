<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$query = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$defaultLat = 27.7172;
$defaultLng = 85.3240;

$lat = isset($_GET['lat']) ? (float)$_GET['lat'] : (isset($_COOKIE['user_lat']) ? (float)$_COOKIE['user_lat'] : $defaultLat);
$lng = isset($_GET['lng']) ? (float)$_GET['lng'] : (isset($_COOKIE['user_lng']) ? (float)$_COOKIE['user_lng'] : $defaultLng);
$radius = isset($_GET['radius']) ? (int)$_GET['radius'] : 100; // in km
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'price';

try {
    $db = new Database();
    
    $sql = "SELECT * FROM (
                SELECT p.id as product_id, p.name as product_name, p.brand, p.image_url, 
                       s.id as store_id, s.name as store_name, s.address, s.phone,
                       IFNULL(l.latitude, s.latitude) as latitude, IFNULL(l.longitude, s.longitude) as longitude,
                       l.price, l.last_updated, IFNULL(l.stock, 1) as stock,
                       (6371 * acos(cos(radians(:lat)) * cos(radians(IFNULL(l.latitude, s.latitude))) * cos(radians(IFNULL(l.longitude, s.longitude)) - radians(:lng)) + sin(radians(:lat)) * sin(radians(IFNULL(l.latitude, s.latitude))))) AS distance
                FROM listings l
                JOIN products p ON l.product_id = p.id
                JOIN stores s ON l.store_id = s.id
                WHERE l.status = 'approved' AND s.status = 'approved' 
            ";
            
    if ($query !== '') {
        $sql .= " AND (LOWER(p.name) LIKE LOWER(:query) OR LOWER(p.brand) LIKE LOWER(:query)) ";
    }
    
    if ($categoryId > 0) {
        $sql .= " AND (p.category_id = :cat OR p.category_id IN (SELECT id FROM categories WHERE parent_id = :cat)) ";
    }
    
    $sql .= " ) WHERE distance < :radius ";
    
    if ($sort === 'distance') {
        $sql .= " ORDER BY distance ASC, price ASC"; 
    } else {
        $sql .= " ORDER BY price ASC, distance ASC"; 
    }
    
    $db->query($sql);
    
    $db->bind(':lat', $lat);
    $db->bind(':lng', $lng);
    $db->bind(':radius', $radius);
    
    if ($query !== '') {
        $db->bind(':query', "%{$query}%");
    }
    
    if ($categoryId > 0) {
        $db->bind(':cat', $categoryId);
    }
    
    $results = $db->resultSet();
    
    if(count($results) > 0) {
        $bestIndex = 0;
        $lowestCost = PHP_FLOAT_MAX;
        
        $travelCostPerKm = 20.0;
        
        for($i = 0; $i < count($results); $i++) {
            $results[$i]->is_best_deal = false; 
            
            $currentCost = (float)$results[$i]->price + ((float)$results[$i]->distance * $travelCostPerKm);
            
            if($currentCost < $lowestCost) {
                $lowestCost = $currentCost;
                $bestIndex = $i;
            }
        }
        
        $results[$bestIndex]->is_best_deal = true;
    }
    
    if (!empty($query)) {
        $db->query("INSERT INTO search_history (query, result_count) VALUES (:query, :count)");
        $db->bind(':query', $query);
        $db->bind(':count', count($results));
        $db->execute();
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $results,
        'count' => count($results)
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
