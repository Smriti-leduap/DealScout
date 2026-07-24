<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    redirect('../index.php');
}

require_once __DIR__ . '/../includes/header.php';

$db = new Database();

$stats = [
    'users' => 0,
    'stores' => 0,
    'pending_stores' => 0,
    'pending_listings' => 0,
    'pending_users' => 0
];

$db->query("SELECT COUNT(*) as cnt FROM users WHERE role != 'admin'");
$stats['users'] = $db->single()->cnt;

$db->query("SELECT status, COUNT(*) as cnt FROM stores GROUP BY status");
$stores_res = $db->resultSet();
foreach($stores_res as $res) {
    if($res->status == 'pending') {
        $stats['pending_stores'] = $res->cnt;
    }
    if($res->status == 'approved') {
        $stats['stores'] = $res->cnt;
    }
}

$db->query("SELECT COUNT(*) as cnt FROM users WHERE status = 'pending'");
$stats['pending_users'] = $db->single()->cnt;

$db->query("SELECT COUNT(*) as cnt FROM listings WHERE status = 'pending'");
$stats['pending_listings'] = $db->single()->cnt;

$db->query("SELECT s.*, u.name as owner_name, u.email as owner_email 
            FROM stores s 
            JOIN users u ON s.owner_id = u.id 
            WHERE s.status = 'pending' 
            ORDER BY s.created_at ASC LIMIT 10");
$pending_stores = $db->resultSet();

$db->query("SELECT l.*, p.name as product_name, s.name as store_name
            FROM listings l 
            JOIN products p ON l.product_id = p.id 
            JOIN stores s ON l.store_id = s.id
            WHERE l.status = 'pending' 
            ORDER BY l.last_updated ASC LIMIT 10");
$pending_listings = $db->resultSet();

$db->query("SELECT * FROM users WHERE status = 'pending' ORDER BY created_at ASC LIMIT 10");
$pending_users_list = $db->resultSet();
?>

<div class="bg-gray-100 flex-grow py-8 border-t border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="mb-8 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900"><i class="fa-solid fa-shield-halved text-red-600 mr-2"></i> Admin Portal</h1>
                <p class="text-gray-500 mt-2">Oversee incoming stores, manage users, and approve inventory changes.</p>
            </div>
            
            <span class="bg-red-100 text-red-800 text-sm font-bold px-4 py-2 rounded-full border border-red-200 shadow-sm"><i class="fa-solid fa-lock"></i> Super Administrator</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border-l-4 border-blue-500 p-6 flex flex-col justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Total Users</h3>
                    <p class="text-3xl font-black text-gray-900"><?php echo number_format($stats['users']); ?></p>
                </div>
                <div class="w-10 h-10 bg-blue-50 text-blue-500 rounded-lg flex items-center justify-center mt-4">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border-l-4 border-green-500 p-6 flex flex-col justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Active Stores</h3>
                    <p class="text-3xl font-black text-gray-900"><?php echo number_format($stats['stores']); ?></p>
                </div>
                <div class="w-10 h-10 bg-green-50 text-green-500 rounded-lg flex items-center justify-center mt-4">
                    <i class="fa-solid fa-shop"></i>
                </div>
            </div><div class="bg-white rounded-xl shadow-sm border-l-4 border-yellow-500 p-6 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute -right-2 -bottom-2 text-yellow-50 opacity-10 text-6xl">
                    <i class="fa-solid fa-store"></i>
                </div>
                <div class="relative z-10">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Pending Stores</h3>
                    <p class="text-3xl font-black text-yellow-600"><?php echo number_format($stats['pending_stores']); ?></p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border-l-4 border-orange-500 p-6 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute -right-2 -bottom-2 text-orange-50 opacity-10 text-6xl">
                    <i class="fa-solid fa-tags"></i>
                </div>
                <div class="relative z-10">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Pending Listings</h3>
                    <p class="text-3xl font-black text-orange-600"><?php echo number_format($stats['pending_listings']); ?></p>
                </div>
            </div>
        </div>

       
                <div class="p-0 flex-grow">
                    <ul class="divide-y divide-gray-100">
                        <?php if (count($pending_users_list) > 0): ?>
                            <?php foreach($pending_users_list as $pu): ?>
                            <li class="p-6 hover:bg-red-50 transition-colors">
                                <div class="flex justify-between items-start">
                                    <div class="flex-grow">
                                        <div class="flex items-center gap-3">
                                            <h3 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($pu->name); ?></h3>
                                            <span class="bg-gray-100 text-xs font-semibold px-2 py-0.5 rounded border border-gray-200 text-gray-600">ID: <?php echo $pu->id; ?></span>
                                        </div>
                                        <p class="text-sm text-gray-500 leading-relaxed mt-2"><i class="fa-solid fa-envelope text-gray-400 w-4"></i> <?php echo htmlspecialchars($pu->email); ?></p>
                                        <p class="text-sm text-gray-500 leading-relaxed"><i class="fa-solid fa-map-marker-alt text-gray-400 w-4"></i> <?php echo htmlspecialchars($pu->location ?? 'N/A'); ?></p>
                                    </div>
                                    <div class="flex flex-col gap-2 w-28 shrink-0">
                                        <button onclick="updateUserStatus(<?php echo $pu->id; ?>, 'active')" class="bg-green-500 hover:bg-green-600 text-white shadow font-bold text-xs py-2 px-3 rounded flex justify-center items-center gap-2">
                                            <i class="fa-solid fa-check"></i> Approve
                                        </button>
                                        <button onclick="updateUserStatus(<?php echo $pu->id; ?>, 'inactive')" class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 font-bold text-xs py-2 px-3 rounded flex justify-center items-center gap-2">
                                            <i class="fa-solid fa-times"></i> Reject
                                        </button>
                                    </div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="p-8 text-center text-gray-500">
                                <i class="fa-solid fa-user-clock text-2xl text-red-300 mb-3 block"></i>
                                All caught up! No pending store owners.
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
                <div class="bg-gray-50 p-5 flex justify-between items-center border-b border-gray-200">
                    <h2 class="text-lg font-bold text-gray-800"><i class="fa-solid fa-store text-yellow-500 mr-2"></i> PENDING STORES (<?php echo count($pending_stores); ?>)</h2>
                    <a href="#" class="text-sm text-primary font-medium hover:underline">View All</a>
                </div>
                <div class="p-0 flex-grow">
                    <ul class="divide-y divide-gray-100">
                        <?php if (count($pending_stores) > 0): ?>
                            <?php foreach($pending_stores as $ps): ?>
                            <li class="p-6 hover:bg-yellow-50 transition-colors">
                                <div class="flex justify-between items-start">
                                    <div class="flex-grow">
                                        <div class="flex items-center gap-3">
                                            <h3 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($ps->name); ?></h3>
                                            <span class="bg-gray-100 text-xs font-semibold px-2 py-0.5 rounded border border-gray-200 text-gray-600">ID: <?php echo $ps->id; ?></span>
                                        </div>
                                        <p class="text-sm text-gray-500 leading-relaxed mt-2"><i class="fa-solid fa-location-dot text-gray-400 w-4"></i> <?php echo htmlspecialchars($ps->address); ?></p>
                                        <p class="text-sm text-gray-500 leading-relaxed"><i class="fa-solid fa-user text-gray-400 w-4"></i> <?php echo htmlspecialchars($ps->owner_name); ?> (<?php echo htmlspecialchars($ps->owner_email); ?>)</p>
                                    </div>
                                    <div class="flex flex-col gap-2 w-28 shrink-0">
                                        <button onclick="updateStoreStatus(<?php echo $ps->id; ?>, 'approved')" class="bg-green-500 hover:bg-green-600 text-white shadow font-bold text-xs py-2 px-3 rounded flex justify-center items-center gap-2">
                                            <i class="fa-solid fa-check"></i> Approve
                                        </button>
                                        <button onclick="updateStoreStatus(<?php echo $ps->id; ?>, 'rejected')" class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 font-bold text-xs py-2 px-3 rounded flex justify-center items-center gap-2">
                                            <i class="fa-solid fa-times"></i> Reject
                                        </button>
                                    </div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="p-8 text-center text-gray-500">
                                <i class="fa-solid fa-leaf text-2xl text-green-300 mb-3 block"></i>
                                All caught up! No pending store requests.
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
                <div class="bg-gray-50 p-5 flex justify-between items-center border-b border-gray-200">
                    <h2 class="text-lg font-bold text-gray-800"><i class="fa-solid fa-tags text-orange-500 mr-2"></i> PENDING LISTINGS (<?php echo count($pending_listings); ?>)</h2>
                    <a href="#" class="text-sm text-primary font-medium hover:underline">View All</a>
                </div>
                <div class="p-0 flex-grow">
                    <ul class="divide-y divide-gray-100">
                        <?php if (count($pending_listings) > 0): ?>
                            <?php foreach($pending_listings as $pl): ?>
                            <li class="p-6 hover:bg-orange-50 transition-colors">
                                <div class="flex justify-between items-start">
                                    <div class="flex-grow">
                                        <div class="flex items-center gap-3">
                                            <h3 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($pl->product_name); ?></h3>
                                            <span class="bg-gray-100 text-xs font-semibold px-2 py-0.5 rounded border border-gray-200 text-gray-600">ID: <?php echo $pl->id; ?></span>
                                        </div>
                                        <p class="text-sm font-bold text-primary leading-relaxed mt-2">Rs. <?php echo number_format($pl->price); ?></p>
                                        <p class="text-sm text-gray-500 leading-relaxed"><i class="fa-solid fa-store text-gray-400 w-4"></i> Store: <?php echo htmlspecialchars($pl->store_name); ?></p>
                                    </div>
                                    <div class="flex flex-col gap-2 w-28 shrink-0">
                                        <button onclick="updateListingStatus(<?php echo $pl->id; ?>, 'approved')" class="bg-green-500 hover:bg-green-600 text-white shadow font-bold text-xs py-2 px-3 rounded flex justify-center items-center gap-2">
                                            <i class="fa-solid fa-check"></i> Approve
                                        </button>
                                        <button onclick="updateListingStatus(<?php echo $pl->id; ?>, 'rejected')" class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 font-bold text-xs py-2 px-3 rounded flex justify-center items-center gap-2">
                                            <i class="fa-solid fa-times"></i> Reject
                                        </button>
                                    </div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="p-8 text-center text-gray-500">
                                <i class="fa-solid fa-tags text-2xl text-orange-300 mb-3 block"></i>
                                All caught up! No pending listings.
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function updateStoreStatus(storeId, status) {
    if(!confirm(`Are you sure you want to ${status} this store?`)) return;
    
    const formData = new FormData();
    formData.append('store_id', storeId);
    formData.append('status', status);
    formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');

    fetch('<?php echo url('api/admin-approve-store.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => {
        alert('System error occurred.');
    });
}

function updateListingStatus(listingId, status) {
    if(!confirm(`Are you sure you want to ${status} this product listing?`)) return;
    
    const formData = new FormData();
    formData.append('listing_id', listingId);
    formData.append('status', status);
    formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');

    fetch('<?php echo url('api/admin-approve-listing.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => {
        alert('System error occurred.');
    });
}

function updateUserStatus(userId, status) {
    let actionWord = status === 'active' ? 'approve' : 'reject';
    if(!confirm(`Are you sure you want to ${actionWord} this store owner?`)) return;
    
    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('status', status);
    formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');

    fetch('<?php echo url('api/admin-approve-user.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => {
        alert('System error occurred.');
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

