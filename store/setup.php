<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'store_owner') {
    redirect('../index.php');
}

require_once __DIR__ . '/../includes/header.php';

$db = new Database();

$db->query("SELECT * FROM stores WHERE owner_id = :id LIMIT 1");
$db->bind(':id', $_SESSION['user_id']);
$store = $db->single();

if ($store) {
    redirect('store/dashboard.php');
}

$db->query("SELECT * FROM users WHERE id = :id");
$db->bind(':id', $_SESSION['user_id']);
$user = $db->single();

?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<div class="bg-gray-50 flex-grow py-12 border-t border-gray-200 min-h-screen flex items-center justify-center">
    <div class="max-w-2xl w-full mx-auto px-4 sm:px-6 lg:px-8 bg-white p-8 rounded-xl shadow-lg border border-gray-100">
        
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center text-primary mx-auto mb-4 text-3xl shadow-sm border border-blue-100">
                <i class="fa-solid fa-shop"></i>
            </div>
            <h1 class="text-3xl font-extrabold text-gray-900">Set Up Your Store</h1>
            <p class="text-gray-500 mt-2">Before listing products, please provide your store details and pinpoint its exact location. This helps customers find you easily.</p>
        </div>

        <form id="registerStoreForm" class="space-y-6">
            <div id="storeAlert" class="hidden rounded-md p-4 mb-4 text-sm"></div>
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Store Name *</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-store text-gray-400"></i>
                        </div>
                        <input type="text" name="name" required class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary focus:border-primary block w-full pl-10 p-2.5 transition" placeholder="e.g. My SuperMart">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Business Email *</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-envelope text-gray-400"></i>
                        </div>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user->email); ?>" required class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary focus:border-primary block w-full pl-10 p-2.5 transition">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Phone Number *</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-phone text-gray-400"></i>
                        </div>
                        <input type="text" name="phone" required class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary focus:border-primary block w-full pl-10 p-2.5 transition" placeholder="e.g. 9800000000">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Street Address *</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-map-pin text-gray-400"></i>
                        </div>
                        <input type="text" name="address" id="addressInput" value="<?php echo htmlspecialchars($user->location ?? ''); ?>" required class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary focus:border-primary block w-full pl-10 p-2.5 transition" placeholder="Enter full address">
                    </div>
                </div>
            </div>

            <div class="border border-gray-200 rounded-xl overflow-hidden bg-white mt-4">
                <div class="p-4 bg-gray-50 border-b border-gray-200">
                    <label class="block text-sm font-semibold text-gray-700">Pinpoint Exact Location *</label>
                    <p class="text-xs text-gray-500 mt-1 mb-3">Click on the map below to exactly pinpoint where your store is located.</p>
                    
                    <div class="flex gap-2">
                        <div class="relative flex-grow">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fa-solid fa-magnifying-glass-location text-gray-400"></i>
                            </div>
                            <input type="text" id="mapSearchInput" class="bg-white border border-gray-300 text-gray-900 rounded-lg focus:ring-primary focus:border-primary block w-full pl-10 p-2 text-sm" placeholder="Search road name, landmark, or area...">
                        </div>
                        <button type="button" id="mapSearchBtn" class="bg-primary hover:bg-blue-700 text-white font-bold text-sm px-4 py-2 rounded-lg transition-colors flex items-center gap-1.5 shadow-sm">
                            <i class="fa-solid fa-search"></i> Search
                        </button>
                    </div>
                </div>
                <div id="setupMap" class="h-64 w-full z-0 cursor-crosshair"></div>
                
                <input type="hidden" name="latitude" id="latInput" required>
                <input type="hidden" name="longitude" id="lngInput" required>
            </div>

            <button type="submit" id="submitStoreBtn" class="w-full bg-primary text-white font-bold py-3.5 rounded-lg shadow-md hover:bg-blue-700 hover:shadow-lg transition-all transform hover:-translate-y-0.5 active:translate-y-0 text-lg uppercase tracking-wide flex items-center justify-center gap-2 mt-8">
                <i class="fa-solid fa-paper-plane"></i> Submit Store for Approval
            </button>
            <p class="text-xs text-gray-500 text-center mt-3">After submission, our admin team will review and approve your store within 24 hours.</p>
        </form>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    let defaultLat = 27.7172;
    let defaultLng = 85.3240;
    
    const map = L.map('setupMap').setView([defaultLat, defaultLng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    
    let marker = null;
    
    const searchInput = document.getElementById('mapSearchInput');
    const searchBtn = document.getElementById('mapSearchBtn');

    function performMapSearch() {
        const query = searchInput.value.trim();
        if (!query) return;

        searchBtn.disabled = true;
        searchBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>Searching...';

        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`)
            .then(res => res.json())
            .then(data => {
                if (data && data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lng = parseFloat(data[0].lon);
                    const displayName = data[0].display_name;

                    map.setView([lat, lng], 16);

                    if (marker) {
                        marker.setLatLng([lat, lng]);
                    } else {
                        marker = L.marker([lat, lng], {draggable: true}).addTo(map);
                        
                        marker.on('dragend', function(event) {
                            const position = marker.getLatLng();
                            document.getElementById('latInput').value = position.lat;
                            document.getElementById('lngInput').value = position.lng;
                        });
                    }

                    document.getElementById('latInput').value = lat;
                    document.getElementById('lngInput').value = lng;
                    
                    const addressField = document.getElementById('addressInput');
                    if (addressField && !addressField.value) {
                        addressField.value = displayName;
                    }
                } else {
                    alert('Location not found. Please try a more specific address or road name.');
                }
            })
            .catch(err => {
                console.error(err);
                alert('An error occurred while searching. Please try again.');
            })
            .finally(() => {
                searchBtn.disabled = false;
                searchBtn.innerHTML = '<i class="fa-solid fa-search"></i> Search';
            });
    }

    searchBtn.addEventListener('click', performMapSearch);
    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            performMapSearch();
        }
    });

    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition((position) => {
            defaultLat = position.coords.latitude;
            defaultLng = position.coords.longitude;
            map.setView([defaultLat, defaultLng], 15);
        });
    }

    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        
        if (marker) {
            marker.setLatLng(e.latlng);
        } else {
            marker = L.marker(e.latlng, {draggable: true}).addTo(map);
            
            marker.on('dragend', function(event) {
                const position = marker.getLatLng();
                document.getElementById('latInput').value = position.lat;
                document.getElementById('lngInput').value = position.lng;
            });
        }
        
        document.getElementById('latInput').value = lat;
        document.getElementById('lngInput').value = lng;
    });

    document.getElementById('registerStoreForm')?.addEventListener('submit', (e) => {
        e.preventDefault();
        
        if (!document.getElementById('latInput').value || !document.getElementById('lngInput').value) {
            alert('Please click on the map to pin point your exact store location.');
            return;
        }

        const btn = document.getElementById('submitStoreBtn');
        const alertBox = document.getElementById('storeAlert');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Submitting...';
       
        fetch('<?php echo url("api/register-store.php"); ?>', {
            method: 'POST',
            credentials: 'same-origin',
            body: new FormData(e.target)
        })
        .then(res => res.json())
        .then(data => {
            alertBox.classList.remove('hidden');
            if(data.status === 'success') {
                alertBox.className = 'rounded-md p-4 mb-4 text-sm bg-green-50 text-green-800';
                alertBox.innerHTML = '<i class="fa-solid fa-check-circle mr-2"></i> ' + data.message;
                setTimeout(() => window.location.href = 'dashboard.php', 2000);
            } else {
                alertBox.className = 'rounded-md p-4 mb-4 text-sm bg-red-50 text-red-800';
                alertBox.innerHTML = '<i class="fa-solid fa-triangle-exclamation mr-2"></i> ' + data.message;
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-paper-plane mr-2"></i> Submit Store for Approval';
            }
        }).catch(err => {
            alertBox.classList.remove('hidden');
            alertBox.className = 'rounded-md p-4 mb-4 text-sm bg-red-50 text-red-800';
            alertBox.innerHTML = '<i class="fa-solid fa-triangle-exclamation mr-2"></i> System Error. Contact Support.';
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-paper-plane mr-2"></i> Submit Store for Approval';
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
