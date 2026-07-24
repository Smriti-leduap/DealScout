
<?php
require_once __DIR__ . '/includes/header.php';

$db = new Database();
$db->query("SELECT * FROM categories WHERE parent_id IS NULL");
$categories = $db->resultSet();
$catId = isset($_GET['category']) ? $_GET['category'] : '';
$q = isset($_GET['q']) ? $_GET['q'] : '';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<div class="bg-gradient-to-b from-primary via-slate-800 to-slate-900 flex-grow py-12 relative overflow-hidden">
    <div class="absolute inset-0 pointer-events-none overflow-hidden z-0">
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-secondary/10 rounded-full filter blur-3xl"></div>
        <div class="absolute bottom-1/4 left-0 w-96 h-96 bg-accent/10 rounded-full filter blur-3xl"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-20">
        
        <div class="bg-white/95 backdrop-blur-xl rounded-2xl shadow-lg border border-white/30 p-5 mb-10 sticky top-24 z-50 smooth-transition" id="searchHeader">
            <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
                <form id="searchForm" class="flex-grow flex flex-col xl:flex-row w-full gap-3">
                    <div class="w-full xl:w-56 shrink-0">
                        <select name="category" id="filterCategory" class="w-full bg-white/80 backdrop-blur-md border border-white/30 text-primary text-base rounded-xl focus:ring-4 focus:ring-secondary/30 focus:border-secondary block p-3.5 font-semibold outline-none smooth-transition">
                            <option value="" class="bg-primary text-white">All Categories</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat->id; ?>" class="bg-primary text-white" <?php echo ($catId == $cat->id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="w-full xl:w-56 shrink-0">
                        <select name="sort" id="filterSort" class="w-full bg-white/80 backdrop-blur-md border border-white/30 text-primary text-base rounded-xl focus:ring-4 focus:ring-secondary/30 focus:border-secondary block p-3.5 font-semibold outline-none smooth-transition">
                            <option value="price" class="bg-primary text-white">Lowest Price</option>
                            <option value="distance" class="bg-primary text-white">Shortest Distance</option>
                        </select>
                    </div>
                    <div class="w-full flex-grow relative">
                        <input type="text" name="q" id="searchQuery" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search products, brands..." class="bg-white/80 backdrop-blur-md border border-white/30 text-primary text-base rounded-xl focus:ring-4 focus:ring-secondary/30 focus:border-secondary block w-full p-3.5 pl-12 outline-none font-semibold smooth-transition">
                        <i class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-primary/60"></i>
                    </div>
                    <input type="hidden" id="userLat" name="lat" value="27.7172">
                    <input type="hidden" id="userLng" name="lng" value="85.3240">
                    <input type="hidden" id="filterRadius" name="radius" value="100">
                    
                    <button type="submit" class="bg-secondary hover:shadow-lg text-white font-bold rounded-xl text-base px-8 py-3.5 sm:w-auto text-center shrink-0 smooth-transition active:scale-95 shadow-md">Search</button>
                </form>
                
                <div class="flex items-center p-1.5 bg-gray-100 rounded-xl border border-gray-200 shrink-0">
                    <button id="listViewBtn" class="bg-white text-primary shadow-sm rounded-lg px-5 py-2.5 text-sm font-bold flex items-center gap-2 smooth-transition">
                        <i class="fa-solid fa-list"></i> List
                    </button>
                    <button id="mapViewBtn" class="text-slate-500 hover:text-primary rounded-lg px-5 py-2.5 text-sm font-bold flex items-center gap-2 smooth-transition">
                        <i class="fa-solid fa-map"></i> Map
                    </button>
                </div>
            </div>
        </div>

        <div class="flex flex-col md:flex-row gap-8">
            <div class="w-full">
                
                <span id="resultCount" class="hidden">0</span>

                <div id="loader" class="text-center py-16 hidden">
                    <div class="inline-flex">
                        <i class="fa-solid fa-circle-notch fa-spin text-4xl text-secondary mb-4"></i>
                    </div>
                    <p class="text-white font-bold text-lg mt-4">Scanning local stores...</p>
                </div>

                <div id="listViewContainer" class="space-y-6 mt-12">
                </div>

                <div id="mapViewContainer" class="hidden relative h-[600px] rounded-2xl shadow-lg border border-white/20 overflow-hidden z-0">
                    <div id="map" class="absolute inset-0 z-0"></div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<div id="productModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl relative transform transition-all scale-95 opacity-0 duration-200" id="productModalContent">
        <button id="closeModalBtn" class="absolute top-4 right-4 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-full w-8 h-8 flex items-center justify-center transition-colors z-10">
            <i class="fa-solid fa-times"></i>
        </button>
        <div id="modalBody">
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if(navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            pos => {
                document.getElementById('userLat').value = pos.coords.latitude;
                document.getElementById('userLng').value = pos.coords.longitude;
                fetchResults();
            },
            err => console.warn('Geolocation blocked. Defaulting to Kathmandu.', err)
        );
    }

    const listViewBtn = document.getElementById('listViewBtn');
    const mapViewBtn = document.getElementById('mapViewBtn');
    const listViewContainer = document.getElementById('listViewContainer');
    const mapViewContainer = document.getElementById('mapViewContainer');
    const searchForm = document.getElementById('searchForm');
    let mapInitialized = false;
    let map, markersGroup;

    listViewBtn.addEventListener('click', () => {
        listViewBtn.classList.replace('text-slate-500', 'text-primary');
        listViewBtn.classList.add('bg-white', 'shadow-sm');
        
        mapViewBtn.classList.replace('text-primary', 'text-slate-500');
        mapViewBtn.classList.remove('bg-white', 'shadow-sm');
        
        listViewContainer.classList.remove('hidden');
        mapViewContainer.classList.add('hidden');
    });

    mapViewBtn.addEventListener('click', () => {
        mapViewBtn.classList.replace('text-slate-500', 'text-primary');
        mapViewBtn.classList.add('bg-white', 'shadow-sm');
        
        listViewBtn.classList.replace('text-primary', 'text-slate-500');
        listViewBtn.classList.remove('bg-white', 'shadow-sm');
        
        listViewContainer.classList.add('hidden');
        mapViewContainer.classList.remove('hidden');
        
        if(!mapInitialized) {
            initMap();
        } else {
            map.invalidateSize();
        }
    });

    searchForm.addEventListener('submit', (e) => {
        e.preventDefault();
        fetchResults();
    });

    document.getElementById('filterSort')?.addEventListener('change', fetchResults);

    function fetchResults() {
        const url = new URL('<?php echo url("api/search.php"); ?>', window.location.origin);
        const formData = new FormData(searchForm);
        for(let [key, val] of formData.entries()) {
            if(val) url.searchParams.append(key, val);
        }

        if(sessionStorage.getItem('user_lat')) {
            url.searchParams.set('lat', sessionStorage.getItem('user_lat'));
            url.searchParams.set('lng', sessionStorage.getItem('user_lng'));
        }

        document.getElementById('loader').classList.remove('hidden');
        listViewContainer.innerHTML = '';
        
        if(markersGroup) markersGroup.clearLayers();

        fetch(url)
            .then(res => res.json())
            .then(data => {
                document.getElementById('loader').classList.add('hidden');
                document.getElementById('resultCount').innerText = data.count || 0;
                
                if(data.status === 'success' && data.count > 0) {
                    renderListView(data.data);
                    if(mapInitialized) renderMapMarkers(data.data);
                } else {
                    listViewContainer.innerHTML = '<div class="text-center py-10 bg-white rounded-lg border border-gray-100"><p class="text-gray-500 text-lg">No products found matching your criteria.</p></div>';
                }
            })
            .catch(err => {
                console.error(err);
                document.getElementById('loader').classList.add('hidden');
                listViewContainer.innerHTML = '<div class="text-center py-10 text-red-500">Error loading results. Please try again.</div>';
            });
    }

    function renderListView(products) {
        let html = '';
        const userLat = document.getElementById('userLat').value;
        const userLng = document.getElementById('userLng').value;

        products.forEach(p => {
            const isBestDeal = p.is_best_deal ? `<div class="absolute top-4 left-4 bg-[#FEC450] text-[#0f172a] text-[10px] font-black uppercase tracking-wider px-2.5 py-1.5 rounded-md flex items-center gap-1 shadow-md z-10 border border-[#eab308]"><i class="fa-solid fa-star text-[9px]"></i> Top Deal</div>` : '';
            const rawGmapsUrl = `https://www.google.com/maps/dir/?api=1&origin=${userLat},${userLng}&destination=${p.latitude},${p.longitude}`;
            const gmapsUrl = `<?php echo url('direction.php'); ?>?url=${encodeURIComponent(rawGmapsUrl)}`;
            const pJson = JSON.stringify(p).replace(/'/g, "&#39;").replace(/"/g, "&quot;");
            
            html += `
            <div onclick="openProductModal(${pJson})" class="bg-white rounded-3xl shadow-card hover:shadow-floating border border-gray-100/80 p-6 transition-all duration-400 hover:-translate-y-1.5 relative overflow-hidden flex flex-col sm:flex-row gap-8 items-center cursor-pointer group">
                ${isBestDeal}
                <div class="w-32 h-32 sm:w-44 sm:h-44 flex-shrink-0 bg-bgGray rounded-2xl p-4 flex items-center justify-center relative overflow-hidden">
                    <div class="hidden"></div>
                    <img src="${(p.image_url && !p.image_url.startsWith('http') && !p.image_url.startsWith('/Smriti/')) ? '<?php echo url(""); ?>' + p.image_url : (p.image_url || 'https://via.placeholder.com/150')}" alt="${p.product_name}" class="max-h-full max-w-full object-contain mix-blend-multiply drop-shadow-sm group-hover:scale-110 transition-transform duration-500 relative z-10">
                </div>
                
                <div class="flex-grow flex flex-col w-full justify-center">
                    <div class="flex justify-between items-start mb-3">
                        <h3 class="text-2xl font-bold text-primary line-clamp-2 group-hover:text-secondary transition-colors duration-300">${p.product_name}</h3>
                        <span class="text-3xl font-black text-primary whitespace-nowrap ml-6 tracking-tight">Rs. ${parseFloat(p.price).toLocaleString()}</span>
                    </div>
                    
                    <div class="mt-2 inline-flex flex-wrap items-center gap-4">
                        <p class="text-lg text-neutral flex items-center gap-2 font-medium bg-gray-50 px-4 py-2 rounded-xl border border-gray-100">
                            <i class="fa-solid fa-store text-secondary"></i> ${p.store_name} 
                        </p>
                        <p class="text-base text-neutral flex items-center gap-2 font-medium">
                            <i class="fa-solid fa-location-dot text-gray-400"></i> ${p.address} • <span class="text-secondary font-bold">${parseFloat(p.distance) < 0.1 ? '< 0.1' : parseFloat(p.distance).toFixed(1)} km</span>
                        </p>
                    </div>
                </div>
            </div>`;
        });
        listViewContainer.innerHTML = html;
    }

    function initMap() {
        const lat = document.getElementById('userLat').value;
        const lng = document.getElementById('userLng').value;
        
        map = L.map('map').setView([lat, lng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        const userIcon = L.icon({
            iconUrl: 'https://cdn-icons-png.flaticon.com/512/1004/1004093.png',
            iconSize: [32, 32]
        });
        L.marker([lat, lng], {icon: userIcon}).addTo(map).bindPopup("<b>You are here</b>");

        markersGroup = L.layerGroup().addTo(map);
        mapInitialized = true;
        
        fetchResults(); 
    }

    function renderMapMarkers(products) {
        if(!mapInitialized) return;
        markersGroup.clearLayers();
        
        const userLat = document.getElementById('userLat').value;
        const userLng = document.getElementById('userLng').value;
        let bounds = [];

        products.forEach(p => {
            const rawGmapsUrl = `https://www.google.com/maps/dir/?api=1&origin=${userLat},${userLng}&destination=${p.latitude},${p.longitude}`;
            const gmapsUrl = `<?php echo url('direction.php'); ?>?url=${encodeURIComponent(rawGmapsUrl)}`;
            const marker = L.marker([p.latitude, p.longitude]).addTo(markersGroup);
            
            const popupContent = `
                <div class="p-1 min-w-[200px]">
                    <h4 class="font-bold text-gray-900 text-base mb-1 cursor-default">${p.store_name}</h4>
                    <p class="text-xs text-gray-500 mb-1 cursor-default">${p.address}</p>
                    <p class="text-xs text-secondary font-bold mb-3 flex items-center gap-1.5 bg-blue-50 w-max px-2 py-1 rounded cursor-default"><i class="fa-solid fa-location-dot"></i> ${parseFloat(p.distance).toFixed(1)} km away</p>
                    
                    <div class="border-t border-gray-200 mt-2 pt-3">
                        <p class="text-xs text-gray-500 font-medium mb-1 uppercase tracking-wider cursor-default">Best Deal Found</p>
                        <p class="font-bold text-sm text-gray-800 line-clamp-1 cursor-default">${p.product_name}</p>
                        <p class="text-primary font-black text-xl mt-1 cursor-default">Rs. ${parseFloat(p.price).toLocaleString()}</p>
                    </div>
                    
                    <button onclick='openProductModal(${JSON.stringify(p).replace(/'/g, "&#39;").replace(/"/g, "&quot;")})' class="block w-full text-center bg-gray-900 hover:bg-black text-white py-2.5 rounded-lg text-xs font-semibold mt-4 transition shadow flex items-center justify-center gap-2">
                        <i class="fa-solid fa-eye"></i> View Details
                    </button>
                </div>
            `;
            marker.bindPopup(popupContent);
            bounds.push([p.latitude, p.longitude]);
        });
        
        if(bounds.length > 0) {
            map.fitBounds(bounds, {padding: [50, 50]});
        }
    }

    fetchResults();
});

let modalMapInstance = null;

window.openProductModal = function(p) {
    <?php if(!isLoggedIn()): ?>
        window.location.href = '<?php echo url("login.php"); ?>?redirect=' + encodeURIComponent(window.location.href);
        return;
    <?php endif; ?>
    
    const modal = document.getElementById('productModal');
    const modalContent = document.getElementById('productModalContent');
    const modalBody = document.getElementById('modalBody');
    
    const userLat = document.getElementById('userLat').value;
    const userLng = document.getElementById('userLng').value;
    const rawGmapsUrl = `https://www.google.com/maps/dir/?api=1&origin=${userLat},${userLng}&destination=${p.latitude},${p.longitude}`;
    const gmapsUrl = `<?php echo url('direction.php'); ?>?url=${encodeURIComponent(rawGmapsUrl)}`;
    
    modalBody.innerHTML = `
        <div class="p-6">
            <div class="flex gap-6 items-start">
                <div class="w-24 h-24 sm:w-32 sm:h-32 bg-gray-50 rounded-xl p-2 flex items-center justify-center flex-shrink-0">
                    <img src="${(p.image_url && !p.image_url.startsWith('http') && !p.image_url.startsWith('/Smriti/')) ? '<?php echo url(""); ?>' + p.image_url : (p.image_url || 'https://via.placeholder.com/150')}" alt="${p.product_name}" class="max-h-full max-w-full mix-blend-multiply">
                </div>
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-900">${p.product_name}</h2>
                    <p class="text-2xl sm:text-3xl font-black text-primary mt-1">Rs. ${parseFloat(p.price).toLocaleString()}</p>
                    <p class="text-gray-500 mt-1 text-sm">${p.brand || 'Unbranded'} &bull; ${p.stock} in stock</p>
                </div>
            </div>
            
            <hr class="my-6 border-gray-100">
            
            <h3 class="text-lg font-bold text-gray-900 mb-4">Store Inquiry & Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                    <p class="text-sm text-blue-800 font-semibold mb-1"><i class="fa-solid fa-store mr-1"></i> ${p.store_name}</p>
                    <p class="text-gray-700 text-sm mb-1"><i class="fa-solid fa-location-dot mr-1"></i> ${p.address}</p>
                    <p class="text-gray-700 text-sm font-semibold mt-2"><i class="fa-solid fa-phone mr-1"></i> ${p.phone || 'Not available'}</p>
                </div>
                <div class="bg-green-50 p-4 rounded-xl border border-green-100 flex flex-col justify-center items-center text-center">
                    <p class="text-sm font-semibold text-green-800 mb-1">Distance</p>
                    <p class="text-2xl font-black text-green-600">${parseFloat(p.distance).toFixed(2)} km</p>
                </div>
            </div>
            
            <div class="mb-4">
                <p class="text-sm font-bold text-gray-700 mb-2">Map View</p>
                <div id="modalMap" class="w-full h-48 sm:h-64 rounded-xl border border-gray-200 z-0"></div>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-3 mt-6">
                <button onclick="getDirections(${p.latitude}, ${p.longitude})" class="flex-1 text-center bg-gray-900 hover:bg-black text-white py-3 rounded-xl font-bold transition-transform active:scale-95 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-location-arrow"></i> View Route & Directions
                </button>
                <a href="product.php?id=${p.product_id}" class="flex-1 text-center bg-primary hover:bg-red-700 text-white py-3 rounded-xl font-bold transition-transform active:scale-95">
                    More Details
                </a>
            </div>
        </div>
    `;
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(() => {
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
        
        if (modalMapInstance) {
            modalMapInstance.remove();
        }
        modalMapInstance = L.map('modalMap').setView([p.latitude, p.longitude], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(modalMapInstance);
        
        const userIcon = L.icon({
            iconUrl: 'https://cdn-icons-png.flaticon.com/512/1004/1004093.png',
            iconSize: [24, 24]
        });
        L.marker([userLat, userLng], {icon: userIcon}).addTo(modalMapInstance).bindPopup("You");
        
        L.marker([p.latitude, p.longitude]).addTo(modalMapInstance).bindPopup(p.store_name);
        
        const bounds = [
            [userLat, userLng],
            [p.latitude, p.longitude]
        ];
        modalMapInstance.fitBounds(bounds, {padding: [20, 20]});
        
    }, 50);
};

document.getElementById('closeModalBtn').addEventListener('click', () => {
    const modal = document.getElementById('productModal');
    const modalContent = document.getElementById('productModalContent');
    
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }, 200);
});

document.getElementById('productModal').addEventListener('click', (e) => {
    if(e.target === document.getElementById('productModal')) {
        document.getElementById('closeModalBtn').click();
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

