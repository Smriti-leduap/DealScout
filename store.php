<?php
require_once __DIR__ . '/includes/header.php';

$storeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userLat = isset($_GET['lat']) ? (float)$_GET['lat'] : 27.7172;
$userLng = isset($_GET['lng']) ? (float)$_GET['lng'] : 85.3240;

if ($storeId <= 0) {
    redirect('search.php');
}

$db = new Database();

$db->query("SELECT s.*,
                   (6371 * acos(cos(radians(:lat)) * cos(radians(latitude)) * cos(radians(longitude) - radians(:lng)) + sin(radians(:lat)) * sin(radians(latitude)))) AS distance
            FROM stores s 
            WHERE id = :id AND status = 'approved'");
$db->bind(':id', $storeId);
$db->bind(':lat', $userLat);
$db->bind(':lng', $userLng);
$store = $db->single();

if (!$store) {
    redirect('search.php'); 
}

$db->query("SELECT p.id as product_id, p.name as product_name, p.brand, p.image_url,
                   c.name as category_name,
                   l.price, l.last_updated
            FROM listings l
            JOIN products p ON l.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE l.store_id = :store_id AND l.status = 'approved'
            ORDER BY c.name, p.name");
$db->bind(':store_id', $storeId);
$products = $db->resultSet();
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<div class="bg-gray-50 flex-grow py-8 border-t border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <nav class="flex mb-6 text-sm text-gray-500" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="<?php echo url('index.php'); ?>" class="hover:text-primary"><i class="fa-solid fa-home mr-2"></i>Home</a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fa-solid fa-chevron-right text-gray-400 mx-1"></i>
                        <a href="<?php echo url('search.php'); ?>" class="hover:text-primary">Stores</a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <i class="fa-solid fa-chevron-right text-gray-400 mx-1"></i>
                        <span class="text-gray-800 font-medium"><?php echo htmlspecialchars($store->name); ?></span>
                    </div>
                </li>
            </ol>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8 z-10 relative">
            
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-8 flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="bg-blue-50 text-primary w-16 h-16 rounded-lg flex items-center justify-center text-3xl mb-4 shadow-sm border border-blue-100">
                                <i class="fa-solid fa-shop"></i>
                            </div>
                            <h1 class="text-3xl font-extrabold text-gray-900 mb-2"><?php echo htmlspecialchars($store->name); ?></h1>
                            <p class="text-lg text-gray-500 mb-6 flex items-center gap-2">
                                <i class="fa-solid fa-location-dot text-red-500"></i>
                                <?php echo htmlspecialchars($store->address); ?>
                                <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-xs ml-2"><?php echo number_format($store->distance, 2); ?> km away</span>
                            </p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4 border-t border-gray-100 pt-6">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-widest mb-3">Contact Info</h3>
                            <ul class="space-y-3 text-gray-700 font-medium">
                                <li class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center text-gray-400"><i class="fa-solid fa-phone"></i></div>
                                    <?php echo htmlspecialchars($store->phone ?: 'Not provided'); ?>
                                </li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-widest mb-3">Operating Hours</h3>
                            <ul class="space-y-3 text-gray-700 font-medium">
                                <li class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center text-gray-400"><i class="fa-regular fa-clock"></i></div>
                                    <?php echo nl2br(htmlspecialchars($store->opening_hours ?: 'Contact store for details')); ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-100">
                    <a href="https://www.google.com/maps/dir/?api=1&origin=<?php echo $userLat; ?>,<?php echo $userLng; ?>&destination=<?php echo $store->latitude; ?>,<?php echo $store->longitude; ?>" target="_blank" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-primary hover:bg-blue-700 text-white font-medium py-3 px-8 rounded-lg shadow-sm transition-colors cursor-pointer text-lg">
                        <i class="fa-solid fa-route"></i> Get Directions to Store
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-[400px] lg:h-auto z-0">
                <div id="storeLocationMap" class="w-full h-full z-0"></div>
            </div>
        </div>

        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-3">
            <i class="fa-solid fa-box-open text-primary"></i> 
            Products available at <?php echo htmlspecialchars($store->name); ?>
            <span class="text-sm font-normal text-gray-500 bg-gray-200 px-3 py-1 rounded-full"><?php echo count($products); ?> Items</span>
        </h2>

        <?php if(count($products) > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php 
                $currentCategory = '';
                foreach($products as $prod): 
                ?>
                <a href="<?php echo url('product.php?id=' . $prod->product_id); ?>" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 transition-all hover:-translate-y-1 hover:shadow-md group flex flex-col h-full">
                    
                    <div class="bg-gray-50 rounded-lg p-4 h-48 flex items-center justify-center mb-4 relative overflow-hidden group-hover:bg-white transition-colors">
                        <img src="<?php echo htmlspecialchars($prod->image_url ?? 'https://via.placeholder.com/150'); ?>" alt="<?php echo htmlspecialchars($prod->product_name); ?>" class="max-h-full max-w-full object-contain mix-blend-multiply transition-transform duration-300 group-hover:scale-105">
                    </div>

                    <div class="flex-grow flex flex-col justify-between">
                        <div>
                            <div class="text-xs text-gray-500 uppercase font-semibold mb-1 flex justify-between">
                                <span><?php echo htmlspecialchars($prod->brand ?? 'Brand'); ?></span>
                                <span class="bg-blue-50 text-blue-600 px-2 py-0.5 rounded"><?php echo htmlspecialchars($prod->category_name ?? ''); ?></span>
                            </div>
                            <h3 class="font-bold text-gray-800 text-lg mb-2 leading-tight group-hover:text-primary transition-colors"><?php echo htmlspecialchars($prod->product_name); ?></h3>
                        </div>
                        
                        <div class="pt-4 mt-auto border-t border-gray-50 flex items-center justify-between">
                            <div>
                                <span class="text-xs text-gray-400 block mb-0.5">Price at this store</span>
                                <span class="text-xl font-black text-gray-900 group-hover:text-primary transition-colors">Rs. <?php echo number_format($prod->price, 2); ?></span>
                            </div>
                            <div class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 group-hover:bg-blue-50 group-hover:text-primary transition-colors">
                                <i class="fa-solid fa-arrow-right -rotate-45"></i>
                            </div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-xl border border-dashed border-gray-300 p-12 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 text-gray-400 mb-4 text-2xl">
                    <i class="fa-solid fa-box-open"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">No products listed</h3>
                <p class="text-gray-500">This store hasn't added any products to their inventory yet.</p>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const storeLat = <?php echo $store->latitude; ?>;
    const storeLng = <?php echo $store->longitude; ?>;
    
    const storeMap = L.map('storeLocationMap', { zoomControl: false }).setView([storeLat, storeLng], 15);
    L.control.zoom({ position: 'bottomright' }).addTo(storeMap);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OSM'
    }).addTo(storeMap);

    const storeIcon = L.divIcon({
        html: `<div style="background-color: #3B82F6; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px rgba(0,0,0,0.3); border: 2px solid white;"><i class="fa-solid fa-shop text-sm"></i></div>`,
        className: 'custom-store-marker',
        iconSize: [30, 30],
        iconAnchor: [15, 15]
    });

    L.marker([storeLat, storeLng], {icon: storeIcon})
     .addTo(storeMap)
     .bindPopup(`<b><?php echo htmlspecialchars($store->name); ?></b><br><?php echo htmlspecialchars($store->address); ?>`)
     .openPopup();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
