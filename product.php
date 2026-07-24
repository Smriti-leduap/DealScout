<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!isLoggedIn()) {
    $redirectUrl = urlencode('product.php?id=' . $productId);
    redirect('login.php?redirect=' . $redirectUrl);
}

require_once __DIR__ . '/includes/header.php';

$defaultLat = 27.7172;
$defaultLng = 85.3240;

$userLat = isset($_COOKIE['user_lat']) ? (float)$_COOKIE['user_lat'] : $defaultLat;
$userLng = isset($_COOKIE['user_lng']) ? (float)$_COOKIE['user_lng'] : $defaultLng;

if ($productId <= 0) {
    redirect('search.php');
}

$db = new Database();

$db->query("SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.id = :id");
$db->bind(':id', $productId);
$product = $db->single();

if (!$product) {
    redirect('search.php');
}

$sql = "SELECT s.id as store_id, s.name as store_name, s.address, s.latitude, s.longitude, s.phone,
               l.price, l.last_updated,
               (6371 * acos(cos(radians(:lat)) * cos(radians(IFNULL(l.latitude, s.latitude))) * cos(radians(IFNULL(l.longitude, s.longitude)) - radians(:lng)) + sin(radians(:lat)) * sin(radians(IFNULL(l.latitude, s.latitude))))) AS distance
        FROM listings l
        JOIN stores s ON l.store_id = s.id
        WHERE l.product_id = :product_id AND l.status = 'approved' AND s.status = 'approved'
        ORDER BY l.price ASC, distance ASC";

$db->query($sql);
$db->bind(':lat', $userLat);
$db->bind(':lng', $userLng);
$db->bind(':product_id', $productId);
$stores = $db->resultSet();

$bestDeal = count($stores) > 0 ? $stores[0] : null;

$db->query("SELECT * FROM products WHERE category_id = :cat_id AND id != :id LIMIT 4");
$db->bind(':cat_id', $product->category_id);
$db->bind(':id', $productId);
$relatedProducts = $db->resultSet();
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<div class="bg-gray-50 flex-grow py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <nav class="flex mb-8 text-sm" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-2">
                <li class="inline-flex items-center">
                    <a href="<?php echo url('index.php'); ?>" class="text-secondary hover:text-primary smooth-transition font-medium"><i class="fa-solid fa-home mr-2"></i>Home</a>
                </li>
                <li>
                    <div class="flex items-center text-gray-400">
                        <i class="fa-solid fa-chevron-right mx-2 text-xs"></i>
                        <a href="<?php echo url('search.php?category=' . $product->category_id); ?>" class="text-neutral hover:text-primary smooth-transition font-medium"><?php echo htmlspecialchars($product->category_name); ?></a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center text-gray-400">
                        <i class="fa-solid fa-chevron-right mx-2 text-xs"></i>
                        <span class="text-primary font-semibold"><?php echo htmlspecialchars($product->name); ?></span>
                    </div>
                </li>
            </ol>
        </nav>

        <div class="bg-white rounded-3xl shadow-md border border-gray-100/60 overflow-hidden mb-12">
            <div class="md:flex">
                <div class="md:w-1/3 p-10 bg-gray-50 flex items-center justify-center border-r border-gray-100 relative">
                    <div class="absolute top-4 left-4">
                        <span class="bg-secondary text-white px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wider shadow-md"><?php echo htmlspecialchars($product->brand ?? 'Featured'); ?></span>
                    </div>
                    <?php 
                        $prodImg = $product->image_url ?? 'https://via.placeholder.com/400';
                        $fullProdImg = (strpos($prodImg, 'http') === 0 || strpos($prodImg, '/Smriti/') === 0) ? $prodImg : url($prodImg);
                    ?>
                    <img src="<?php echo htmlspecialchars($fullProdImg); ?>" alt="<?php echo htmlspecialchars($product->name); ?>" class="max-w-full h-auto object-contain max-h-96 mix-blend-multiply">
                </div>
                
                <div class="md:w-2/3 p-10 flex flex-col justify-between">
                    <div>
                        <h1 class="text-4xl md:text-5xl font-black text-primary mb-4 leading-tight"><?php echo htmlspecialchars($product->name); ?></h1>
                        
                        <div class="flex items-center gap-4 mb-6 py-4 border-y border-gray-100">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-star text-secondary text-xl"></i>
                                <span class="font-bold text-primary">4.8</span>
                                <span class="text-sm text-neutral">(240 reviews)</span>
                            </div>
                        </div>
                        
                        <p class="text-gray-700 mt-6 leading-relaxed text-base">
                            <?php echo nl2br(htmlspecialchars($product->description)); ?>
                        </p>
                    </div>
                    
                    <?php if($bestDeal): ?>
                    <div class="mt-10 bg-green-50 rounded-2xl p-6 border-2 border-secondary/30 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6">
                        <div>
                            <p class="text-sm font-bold text-secondary uppercase tracking-widest mb-2"><i class="fa-solid fa-certificate mr-1"></i> Top Recommendation</p>
                            <p class="text-3xl md:text-4xl font-black text-primary">Rs. <?php echo number_format($bestDeal->price, 0); ?></p>
                            <p class="text-sm text-neutral mt-1"><?php echo htmlspecialchars($bestDeal->store_name); ?> • <?php echo number_format($bestDeal->distance, 1); ?> km away</p>
                        </div>
                        <button onclick="getDirections(<?php echo $bestDeal->latitude; ?>, <?php echo $bestDeal->longitude; ?>)" class="w-full sm:w-auto bg-secondary hover:shadow-lg text-white font-bold py-4 px-8 rounded-xl smooth-transition flex items-center justify-center gap-2 shadow-lg active:scale-95">
                            <i class="fa-solid fa-route text-lg"></i> <span>Get Directions</span>
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="mt-10 bg-yellow-50 rounded-2xl p-6 border-2 border-yellow-200 text-yellow-800 flex items-center gap-4">
                        <i class="fa-solid fa-triangle-exclamation text-2xl"></i>
                        <p class="font-semibold">Currently not available in any nearby stores.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if(count($stores) > 0): ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
            <div class="lg:col-span-2 bg-white rounded-3xl shadow-md border border-gray-100/60 p-8 overflow-hidden">
                <h2 class="text-2xl font-black text-primary mb-8 flex items-center gap-3"><i class="fa-solid fa-list-check text-secondary text-3xl"></i> Compare Prices</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50/80">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-primary uppercase tracking-wider">Store</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-primary uppercase tracking-wider">Price</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-primary uppercase tracking-wider">Distance</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-primary uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach($stores as $index => $store): ?>
                            <tr class="<?php echo $index === 0 ? 'bg-green-50/60' : 'hover:bg-gray-50/50'; ?> smooth-transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <?php if($index === 0): ?>
                                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-secondary/10 text-secondary text-xs font-bold"><i class="fa-solid fa-circle-check"></i> Best Value</span>
                                        <?php endif; ?>
                                        <div>
                                            <a href="<?php echo url('store.php?id=' . $store->store_id); ?>" class="font-bold text-primary hover:text-secondary smooth-transition block"><?php echo htmlspecialchars($store->store_name); ?></a>
                                            <div class="text-xs text-neutral mt-1 flex items-center gap-1"><i class="fa-solid fa-location-dot text-secondary"></i> <?php echo htmlspecialchars($store->address); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-xl font-black <?php echo $index === 0 ? 'text-secondary' : 'text-primary'; ?>">Rs. <?php echo number_format($store->price, 0); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 text-sm font-semibold text-neutral bg-gray-100 px-3 py-1.5 rounded-lg"><i class="fa-solid fa-map-pin"></i> <?php 
                                        $dist = (float)$store->distance;
                                        echo ($dist < 0.1) ? '< 0.1' : number_format($dist, 1);
                                    ?> km</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button onclick="getDirections(<?php echo $store->latitude; ?>, <?php echo $store->longitude; ?>)" class="inline-flex items-center gap-2 text-secondary hover:text-primary font-bold bg-secondary/10 hover:bg-secondary/20 px-4 py-2 rounded-lg smooth-transition">
                                        Directions
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-md border border-gray-100/60 p-8 flex flex-col overflow-hidden">
                <h2 class="text-2xl font-black text-primary mb-6 flex items-center gap-3"><i class="fa-solid fa-map-location-dot text-secondary text-3xl"></i> Locations</h2>
                <div id="productMap" class="w-full flex-grow min-h-[400px] rounded-2xl border border-gray-200 z-0 relative"></div>
            </div>
        </div>
        <?php endif; ?>

        <?php if(count($relatedProducts) > 0): ?>
        <div class="mb-12">
            <h2 class="text-2xl font-black text-primary mb-8">Related Products</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <?php foreach($relatedProducts as $relProduct): ?>
                <a href="<?php echo url('product.php?id=' . $relProduct->id); ?>" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 card-hover group block">
                    <div class="h-40 flex items-center justify-center bg-secondary rounded-xl p-3 mb-4 group-hover:bg-gray-100 smooth-transition">
                        <img src="<?php echo htmlspecialchars((strpos($relProduct->image_url ?? '', 'http') === 0) ? $relProduct->image_url : url($relProduct->image_url ?? 'https://via.placeholder.com/150')); ?>" alt="<?php echo htmlspecialchars($relProduct->name); ?>" class="max-h-full max-w-full object-contain mix-blend-multiply group-hover:scale-110 smooth-transition">
                    </div>
                    <span class="text-xs text-secondary uppercase font-bold"><?php echo htmlspecialchars($relProduct->brand ?? 'Product'); ?></span>
                    <h3 class="font-bold text-primary mt-2 line-clamp-2 group-hover:text-secondary smooth-transition"><?php echo htmlspecialchars($relProduct->name); ?></h3>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    <?php if(count($stores) > 0): ?>
    const map = L.map('productMap').setView([<?php echo $userLat; ?>, <?php echo $userLng; ?>], 13);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    const userIcon = L.icon({
        iconUrl: 'https://cdn-icons-png.flaticon.com/512/1004/1004093.png',
        iconSize: [32, 32]
    });
    L.marker([<?php echo $userLat; ?>, <?php echo $userLng; ?>], {icon: userIcon})
        .addTo(map)
        .bindPopup("<b>Your Location</b>");

    const stores = <?php echo json_encode($stores); ?>;
    const userLat = <?php echo $userLat; ?>;
    const userLng = <?php echo $userLng; ?>;
    let bounds = [[<?php echo $userLat; ?>, <?php echo $userLng; ?>]];

    stores.forEach((store, index) => {
        let markerIcon = new L.Icon.Default(); 
        
        const marker = L.marker([parseFloat(store.latitude), parseFloat(store.longitude)], {icon: markerIcon}).addTo(map);
        
        const gmapsUrl = `https://www.google.com/maps/dir/?api=1&origin=${userLat},${userLng}&destination=${store.latitude},${store.longitude}`;
        const bestDealBadge = index === 0 ? '<div class="bg-green-500 text-white text-[10px] font-bold px-1 py-0.5 rounded inline-block mb-1">BEST DEAL</div>' : '';
        
        const popupContent = `
            <div class="p-1 min-w-[150px]">
                ${bestDealBadge}
                <h4 class="font-bold text-gray-900">${store.store_name}</h4>
                <p class="text-xs text-gray-600 mt-1"><i class="fa-solid fa-phone text-gray-400 mr-1"></i> ${store.phone || 'Not available'}</p>
                <p class="text-primary font-bold text-lg mt-1 border-t pt-1">Rs. ${parseFloat(store.price).toLocaleString()}</p>
                <div class="mt-2 pt-2 border-t flex flex-col gap-1 text-xs">
                    <a href="store.php?id=${store.store_id}" class="text-blue-600 hover:underline">View Store Details</a>
                    <a href="${gmapsUrl}" target="_blank" class="bg-accent text-white text-center py-1 rounded w-full">Directions</a>
                </div>
            </div>
        `;
        
        marker.bindPopup(popupContent);
        bounds.push([parseFloat(store.latitude), parseFloat(store.longitude)]);
    });

    if(bounds.length > 1) {
        map.fitBounds(bounds, {padding: [30, 30]});
    }
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
