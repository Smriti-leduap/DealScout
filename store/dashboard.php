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

$db->query("SELECT * FROM users WHERE id = :id");
$db->bind(':id', $_SESSION['user_id']);
$user = $db->single();

$stats = [
    'products' => 0,
    'pending_listings' => 0,
    'approved_listings' => 0,
    'rejected_listings' => 0
];
$listings = [];

if ($store) {
    $db->query("SELECT status, COUNT(*) as count FROM listings WHERE store_id = :store_id GROUP BY status");
    $db->bind(':store_id', $store->id);
    $results = $db->resultSet();
    
    foreach ($results as $r) {
        if ($r->status == 'approved') $stats['approved_listings'] = $r->count;
        if ($r->status == 'pending') $stats['pending_listings'] = $r->count;
        if ($r->status == 'rejected') $stats['rejected_listings'] = $r->count;
        $stats['products'] += $r->count;
    }

    $db->query("SELECT l.*, p.name as product_name, p.brand, p.image_url 
                FROM listings l 
                JOIN products p ON l.product_id = p.id 
                WHERE l.store_id = :store_id 
                ORDER BY l.last_updated DESC LIMIT 10");
    $db->bind(':store_id', $store->id);
    $listings = $db->resultSet();
}

?>

<div class="bg-gray-50 flex-grow py-8 border-t border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="mb-8 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900">Store Dashboard</h1>
                <p class="text-gray-500 mt-2">Manage your inventory, adjust prices, and track performance.</p>
            </div>
            
            <?php if($store): ?>
                <div class="flex items-center gap-3">
                    <?php if($store->status === 'approved'): ?>
                    <span class="bg-green-100 text-green-800 text-sm font-semibold px-3 py-1.5 rounded-full border border-green-200 flex items-center gap-2 shadow-sm"><i class="fa-solid fa-circle-check"></i> Store Approved</span>
                    <?php elseif($store->status === 'rejected'): ?>
                    <span class="bg-red-100 text-red-800 text-sm font-semibold px-3 py-1.5 rounded-full border border-red-200 flex items-center gap-2 shadow-sm"><i class="fa-solid fa-times-circle"></i> Store Rejected</span>
                    <?php else: ?>
                    <span class="bg-yellow-100 text-yellow-800 text-sm font-semibold px-3 py-1.5 rounded-full border border-yellow-200 flex items-center gap-2 shadow-sm"><i class="fa-solid fa-clock"></i> Store Pending</span>
                    <?php endif; ?>
                    <button onclick="document.getElementById('editStoreModal').classList.remove('hidden')" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium px-4 py-1.5 rounded-lg shadow-sm flex items-center gap-2 transition-colors"><i class="fa-solid fa-pen-to-square"></i> Edit Store Info</button>
                </div>
            <?php endif; ?>
        </div>

        <?php if(!$store): ?>
            <script>window.location.href = "<?php echo url('store/setup.php'); ?>";</script>
        <?php else: ?>

        <?php if($store->status === 'approved'): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0 mt-0.5"><i class="fa-solid fa-check-circle text-green-500"></i></div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700 font-medium">Your store has been approved! You can now add products and listings to the platform.</p>
                    </div>
                </div>
            </div>
        <?php elseif($store->status === 'rejected'): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0 mt-0.5"><i class="fa-solid fa-times-circle text-red-500"></i></div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700 font-medium">Your store application was rejected by the admin. Please update your store info or contact support.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if($stats['rejected_listings'] > 0): ?>
            <div class="bg-orange-50 border-l-4 border-orange-500 p-4 mb-6 rounded shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0 mt-0.5"><i class="fa-solid fa-triangle-exclamation text-orange-500"></i></div>
                    <div class="ml-3">
                        <p class="text-sm text-orange-800 font-medium">Attention: You have <?php echo $stats['rejected_listings']; ?> product(s) rejected by the admin. Please check the inventory table below to delete or update them.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8 flex flex-col md:flex-row gap-8">
            <div class="flex-grow">
                <h2 class="text-xl font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2"><i class="fa-solid fa-address-card text-primary mr-2"></i> Store Profile</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8">
                    <div>
                        <p class="text-sm font-semibold text-gray-400 uppercase tracking-wider">Store Name</p>
                        <p class="font-medium text-gray-900 mt-1"><i class="fa-solid fa-store w-5 text-gray-400"></i> <?php echo htmlspecialchars($store->name); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-400 uppercase tracking-wider">Business Email</p>
                        <p class="font-medium text-gray-900 mt-1"><i class="fa-solid fa-envelope w-5 text-gray-400"></i> <?php echo htmlspecialchars($store->email ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-400 uppercase tracking-wider">Phone Number</p>
                        <p class="font-medium text-gray-900 mt-1"><i class="fa-solid fa-phone w-5 text-gray-400"></i> <?php echo htmlspecialchars($store->phone ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-400 uppercase tracking-wider">Street Address</p>
                        <p class="font-medium text-gray-900 mt-1"><i class="fa-solid fa-map-pin w-5 text-gray-400"></i> <?php echo htmlspecialchars($store->address); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="w-full md:w-1/3 flex-shrink-0 flex flex-col">
                <p class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-2">Location Map</p>
                <div id="profileMap" class="h-40 w-full rounded-lg border border-gray-200 bg-gray-50 z-0"></div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-2">Total Products</h3>
                    <p class="text-3xl font-black text-gray-900"><?php echo $stats['products']; ?></p>
                </div>
                <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center mt-4 border border-blue-100">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-2">Active Listings</h3>
                    <p class="text-3xl font-black text-green-600"><?php echo $stats['approved_listings']; ?></p>
                </div>
                <div class="w-10 h-10 bg-green-50 text-green-600 rounded-lg flex items-center justify-center mt-4 border border-green-100">
                    <i class="fa-solid fa-check-double"></i>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-2">Pending Approval</h3>
                    <p class="text-3xl font-black text-yellow-600"><?php echo $stats['pending_listings']; ?></p>
                </div>
                <div class="w-10 h-10 bg-yellow-50 text-yellow-600 rounded-lg flex items-center justify-center mt-4 border border-yellow-100">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
            </div>
            
            <div class="bg-secondary rounded-xl shadow-sm p-6 text-white flex flex-col justify-between relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 opacity-20 text-7xl group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-plus-circle"></i>
                </div>
                <div class="relative z-10">
                    <h3 class="text-lg font-bold mb-1">Add Product</h3>
                    <p class="text-sm text-blue-100">List a new item in your inventory</p>
                </div>
                <?php if($store->status === 'approved'): ?>
                <a href="add-product.php" class="relative z-10 mt-4 bg-white text-primary font-bold py-2 px-4 rounded-lg w-full shadow-sm hover:bg-gray-50 transition text-center block">Add Item</a>
                <?php else: ?>
                <button disabled class="relative z-10 mt-4 bg-white/50 text-primary/50 font-bold py-2 px-4 rounded-lg w-full shadow-sm cursor-not-allowed text-center block">Requires Approval</button>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-primary"></i> Your Inventory
                </h2>
                <div class="relative">
                    <input type="text" placeholder="Search inventory..." class="bg-gray-50 border border-gray-300 text-sm rounded-lg pl-8 pr-4 py-2 focus:ring-primary focus:border-primary w-64">
                    <i class="fa-solid fa-search text-gray-400 absolute left-3 top-2.5"></i>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-4 border-b">S.No.</th>
                            <th scope="col" class="px-6 py-4 border-b">Product Name</th>
                            <th scope="col" class="px-6 py-4 border-b">Brand</th>
                            <th scope="col" class="px-6 py-4 border-b">Your Price (Rs.)</th>
                            <th scope="col" class="px-6 py-4 border-b">Stock</th>
                            <th scope="col" class="px-6 py-4 border-b">Status</th>
                            <th scope="col" class="px-6 py-4 border-b">Last Updated</th>
                            <th scope="col" class="px-6 py-4 border-b text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if(count($listings) > 0): ?>
                            <?php $sno = 1; foreach($listings as $listing): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-gray-500 font-medium"><?php echo $sno++; ?></td>
                                <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap flex items-center gap-3">
                                    <div class="w-10 h-10 rounded border border-gray-200 p-1 flex items-center justify-center bg-white shrink-0">
                                        <?php 
                                            $imgUrl = $listing->image_url ?? 'https://via.placeholder.com/50';
                                            $fullImgUrl = (strpos($imgUrl, 'http') === 0 || strpos($imgUrl, '/Smriti/') === 0) ? $imgUrl : url($imgUrl);
                                        ?>
                                        <img src="<?php echo htmlspecialchars($fullImgUrl); ?>" class="max-h-full max-w-full" alt="">
                                    </div>
                                    <?php echo htmlspecialchars($listing->product_name); ?>
                                </td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($listing->brand ?? 'N/A'); ?></td>
                                <td class="px-6 py-4 font-bold text-gray-900"><?php echo number_format($listing->price, 2); ?></td>
                                <td class="px-6 py-4 font-medium text-gray-700"><?php echo (int)($listing->stock ?? 1); ?></td>
                                <td class="px-6 py-4">
                                    <?php if($listing->status === 'approved'): ?>
                                        <span class="bg-green-100 text-green-800 text-xs font-semibold px-2 py-0.5 rounded border border-green-200">Active</span>
                                    <?php elseif($listing->status === 'pending'): ?>
                                        <span class="bg-yellow-100 text-yellow-800 text-xs font-semibold px-2 py-0.5 rounded border border-yellow-200">Pending</span>
                                    <?php else: ?>
                                        <span class="bg-red-100 text-red-800 text-xs font-semibold px-2 py-0.5 rounded border border-red-200">Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <i class="fa-regular fa-clock mr-1"></i> 
                                    <?php 
                                        $dt = new DateTime($listing->last_updated);
                                        echo $dt->format('d M, Y h:i A'); 
                                    ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button onclick="openEditPriceModal(<?php echo $listing->id; ?>, <?php echo $listing->price; ?>, <?php echo (int)($listing->stock ?? 1); ?>)" class="text-blue-600 hover:text-blue-900 font-semibold mr-3 hover:underline"><i class="fa-solid fa-pen-to-square mr-1"></i> Edit Price & Stock</button>
                                    <button onclick="deleteListing(<?php echo $listing->id; ?>)" class="text-red-500 hover:text-red-700"><i class="fa-solid fa-trash-can"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-gray-400">
                                    No products found in your inventory. Start adding products to get visibility!
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if(count($listings) > 0): ?>
            <div class="p-4 border-t border-gray-100 bg-gray-50 text-center">
                <a href="#" class="text-primary hover:underline text-sm font-medium">View All Products &rarr;</a>
            </div>
            <?php endif; ?>
        </div>

        <?php endif; ?>

    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>


<div id="editStoreModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-2xl p-6 relative max-h-[90vh] overflow-y-auto">
        <button onclick="document.getElementById('editStoreModal').classList.add('hidden')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl"><i class="fa-solid fa-xmark"></i></button>
        <h2 class="text-2xl font-bold mb-6">Edit Store Information</h2>
        <?php if(isset($store) && $store): ?>
        <form id="editStoreForm" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700">Store Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($store->name); ?>" required class="mt-1 bg-gray-50 border border-gray-300 rounded-md w-full p-2.5">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Full Address</label>
                <input type="text" name="address" value="<?php echo htmlspecialchars($store->address); ?>" required class="mt-1 bg-gray-50 border border-gray-300 rounded-md w-full p-2.5">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($store->email ?? ''); ?>" required class="mt-1 bg-gray-50 border border-gray-300 rounded-md w-full p-2.5">
            </div>
            
            <div class="border border-gray-200 rounded-xl overflow-hidden mt-4">
                <div class="p-3 bg-gray-50 border-b border-gray-200">
                    <label class="block text-sm font-medium text-gray-700">Update Pin Point Location</label>
                    <div class="flex gap-2 mt-2">
                        <input type="text" id="editMapSearchInput" class="bg-white border border-gray-300 rounded-md w-full p-2 text-xs" placeholder="Search road name, landmark, or area...">
                        <button type="button" id="editMapSearchBtn" class="bg-primary hover:bg-blue-700 text-white font-bold text-xs px-3 py-2 rounded transition-colors shrink-0">
                            <i class="fa-solid fa-search"></i> Search
                        </button>
                    </div>
                </div>
                <div id="editMap" class="h-48 w-full z-10 cursor-crosshair"></div>
            </div>
            <input type="hidden" name="latitude" id="editLat" value="<?php echo htmlspecialchars($store->latitude); ?>">
            <input type="hidden" name="longitude" id="editLng" value="<?php echo htmlspecialchars($store->longitude); ?>">
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Phone</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($store->phone); ?>" class="mt-1 bg-gray-50 border border-gray-300 rounded-md w-full p-2.5">
            </div>
            <button type="submit" class="w-full bg-primary text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition">Save Changes</button>
        </form>
        
        <hr class="my-6 border-gray-200">
        
        <div class="bg-red-50 p-4 rounded-lg border border-red-100">
            <h3 class="text-red-800 font-bold mb-2"><i class="fa-solid fa-triangle-exclamation mr-2"></i> Danger Zone: Delete Account</h3>
            <p class="text-sm text-red-600 mb-3">Deleting your account will permanently remove your store, all products, and your user data. This action cannot be undone.</p>
            <button onclick="document.getElementById('deleteStoreConfirmDiv').classList.toggle('hidden')" class="bg-red-600 text-white font-bold py-2 px-4 rounded hover:bg-red-700 transition text-sm">Delete My Account</button>
            
            <div id="deleteStoreConfirmDiv" class="hidden mt-4 pt-4 border-t border-red-200">
                <form id="deleteAccountForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <label class="block text-sm font-medium text-red-800 mb-1">To confirm, type <strong>DELETE STORE</strong> below:</label>
                    <input type="text" name="confirmation_text" required autocomplete="off" class="bg-white border border-red-300 text-gray-900 rounded-md w-full p-2 mb-2 focus:ring-red-500 focus:border-red-500 font-mono">
                    <button type="submit" class="w-full bg-red-700 text-white font-bold py-2 rounded hover:bg-red-800 transition text-sm">Permanently Delete Everything</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div id="editPriceModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 relative border border-gray-100">
        <button onclick="document.getElementById('editPriceModal').classList.add('hidden')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl"><i class="fa-solid fa-xmark"></i></button>
        <h2 class="text-xl font-bold mb-4 text-primary"><i class="fa-solid fa-edit text-secondary mr-1"></i> Update Listing</h2>
        <form id="editPriceForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="listing_id" id="edit_listing_id">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">New Price (Rs.)</label>
                <input type="number" step="any" name="price" id="edit_price_val" required class="bg-gray-50 border border-gray-300 rounded-lg w-full p-2.5 font-bold text-lg focus:ring-2 focus:ring-secondary focus:border-transparent outline-none">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-1">New Stock Available</label>
                <input type="number" name="stock" id="edit_stock_val" required min="0" class="bg-gray-50 border border-gray-300 rounded-lg w-full p-2.5 font-bold text-lg focus:ring-2 focus:ring-secondary focus:border-transparent outline-none">
            </div>
            <button type="submit" class="w-full bg-secondary text-white font-bold py-3 rounded-lg hover:shadow-lg active:scale-95 smooth-transition shadow-md">Save Changes</button>
        </form>
    </div>
</div>

<script>
function openEditPriceModal(id, currentPrice, currentStock) {
    document.getElementById('edit_listing_id').value = id;
    document.getElementById('edit_price_val').value = currentPrice;
    document.getElementById('edit_stock_val').value = currentStock;
    document.getElementById('editPriceModal').classList.remove('hidden');
}

document.getElementById('editPriceForm')?.addEventListener('submit', (e) => {
    e.preventDefault();
    fetch('<?php echo url("api/edit-listing.php"); ?>', {
        method: 'POST',
        body: new FormData(e.target)
    }).then(res => res.json()).then(data => {
        if(data.status === 'success') {
            window.location.reload();
        } else {
            alert(data.message);
        }
    });
});

document.getElementById('editStoreForm')?.addEventListener('submit', (e) => {
    e.preventDefault();
    fetch('<?php echo url("api/edit-store.php"); ?>', {
        method: 'POST',
        body: new FormData(e.target)
    }).then(res => res.json()).then(data => {
        if(data.status === 'success') {
            window.location.reload();
        } else {
            alert(data.message);
        }
    });
});

document.getElementById('deleteAccountForm')?.addEventListener('submit', (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Deleting...';
    
    fetch('<?php echo url("api/delete-account.php"); ?>', {
        method: 'POST',
        body: new FormData(e.target)
    }).then(res => res.json()).then(data => {
        if(data.status === 'success') {
            alert(data.message);
            window.location.href = '<?php echo url("index.php"); ?>';
        } else {
            alert(data.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }).catch(err => {
        alert('System error occurred.');
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
});

<?php if($store): ?>
document.addEventListener('DOMContentLoaded', () => {
    const profileMap = L.map('profileMap').setView([<?php echo $store->latitude; ?>, <?php echo $store->longitude; ?>], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(profileMap);
    L.marker([<?php echo $store->latitude; ?>, <?php echo $store->longitude; ?>]).addTo(profileMap);
    
    let editMapInitialized = false;
    let editMap;
    let editMarker;
    
    const editModalBtn = document.querySelector('button[onclick*="editStoreModal"]');
    if (editModalBtn) {
        editModalBtn.addEventListener('click', () => {
            setTimeout(() => {
                if (!editMapInitialized) {
                    editMap = L.map('editMap').setView([<?php echo $store->latitude; ?>, <?php echo $store->longitude; ?>], 15);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(editMap);
                    
                    editMarker = L.marker([<?php echo $store->latitude; ?>, <?php echo $store->longitude; ?>], {draggable: true}).addTo(editMap);
                    
                    editMap.on('click', function(e) {
                        editMarker.setLatLng(e.latlng);
                        document.getElementById('editLat').value = e.latlng.lat;
                        document.getElementById('editLng').value = e.latlng.lng;
                    });
                    
                    editMarker.on('dragend', function(e) {
                        const pos = editMarker.getLatLng();
                        document.getElementById('editLat').value = pos.lat;
                        document.getElementById('editLng').value = pos.lng;
                    });

                    const editSearchInput = document.getElementById('editMapSearchInput');
                    const editSearchBtn = document.getElementById('editMapSearchBtn');

                    function performEditMapSearch() {
                        const query = editSearchInput.value.trim();
                        if (!query) return;

                        editSearchBtn.disabled = true;
                        editSearchBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>Searching...';

                        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`)
                            .then(res => res.json())
                            .then(data => {
                                if (data && data.length > 0) {
                                    const lat = parseFloat(data[0].lat);
                                    const lng = parseFloat(data[0].lon);

                                    editMap.setView([lat, lng], 16);
                                    editMarker.setLatLng([lat, lng]);

                                    document.getElementById('editLat').value = lat;
                                    document.getElementById('editLng').value = lng;
                                } else {
                                    alert('Location not found. Please try a more specific address or road name.');
                                }
                            })
                            .catch(err => {
                                console.error(err);
                                alert('An error occurred while searching. Please try again.');
                            })
                            .finally(() => {
                                editSearchBtn.disabled = false;
                                editSearchBtn.innerHTML = '<i class="fa-solid fa-search"></i> Search';
                            });
                    }

                    editSearchBtn.addEventListener('click', performEditMapSearch);
                    editSearchInput.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            performEditMapSearch();
                        }
                    });
                    
                    editMapInitialized = true;
                } else {
                    editMap.invalidateSize();
                }
            }, 200);
        });
    }
});
<?php endif; ?>

function deleteListing(id) {
    if(confirm('Are you securely sure you want to completely remove this listing from your inventory? This cannot be undone.')) {
        const formData = new FormData();
        formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');
        formData.append('listing_id', id);
        
        fetch('<?php echo url("api/delete-listing.php"); ?>', {
            method: 'POST',
            body: formData
        }).then(res => res.json()).then(data => {
            if(data.status === 'success') {
                window.location.reload();
            } else {
                alert(data.message);
            }
        });
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
