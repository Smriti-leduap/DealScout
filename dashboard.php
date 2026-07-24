<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if ($_SESSION['user_role'] === 'admin') {
    redirect('admin/dashboard.php');
} elseif ($_SESSION['user_role'] === 'store_owner') {
    redirect('store/dashboard.php');
}

require_once __DIR__ . '/includes/header.php';

$db = new Database();

$db->query("SELECT * FROM users WHERE id = :id");
$db->bind(':id', $_SESSION['user_id']);
$user = $db->single();

$db->query("SELECT * FROM search_history WHERE user_id = :user_id ORDER BY timestamp DESC LIMIT 10");
$db->bind(':user_id', $_SESSION['user_id']);
$searchHistory = $db->resultSet();

$userLat = 27.7172; // Default Kathmandu
$userLng = 85.3240; 
$db->query("SELECT p.id as product_id, p.name as product_name, p.image_url, l.price, s.name as store_name, s.address,
            (6371 * acos(cos(radians(:lat)) * cos(radians(IFNULL(l.latitude, s.latitude))) * cos(radians(IFNULL(l.longitude, s.longitude)) - radians(:lng)) + sin(radians(:lat)) * sin(radians(IFNULL(l.latitude, s.latitude))))) AS distance
            FROM listings l 
            JOIN products p ON l.product_id = p.id 
            JOIN stores s ON l.store_id = s.id 
            WHERE l.status = 'approved' AND s.status = 'approved'
            ORDER BY l.last_updated DESC LIMIT 4");
$db->bind(':lat', $userLat);
$db->bind(':lng', $userLng);
$recentDeals = $db->resultSet();
?>

<div class="bg-gray-50 flex-grow py-8 border-t border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-gray-900">Consumer Dashboard</h1>
            <p class="text-gray-500 mt-2">Welcome back, <?php echo htmlspecialchars($user->name); ?>! Manage your profile and view your activity.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Profile Sidebar -->
            <div class="md:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="text-center mb-6">
                        <div class="w-24 h-24 bg-blue-100 text-primary mx-auto rounded-full flex items-center justify-center text-4xl mb-4">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($user->name); ?></h2>
                        <span class="inline-block bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-semibold mt-2 border border-gray-200">Consumer</span>
                    </div>

                    <div class="border-t border-gray-100 pt-6">
                        <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Account Details</h3>
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3 text-sm">
                                <i class="fa-solid fa-envelope text-gray-400 mt-1"></i>
                                <div>
                                    <p class="font-medium text-gray-900">Email Address</p>
                                    <p class="text-gray-500 break-all"><?php echo htmlspecialchars($user->email); ?></p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3 text-sm">
                                <i class="fa-regular fa-calendar-days text-gray-400 mt-1"></i>
                                <div>
                                    <p class="font-medium text-gray-900">Joined Date</p>
                                    <p class="text-gray-500"><?php echo date('M j, Y', strtotime($user->created_at)); ?></p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div class="mt-8">
                        <a href="<?php echo url('api/logout.php'); ?>" class="w-full inline-flex justify-center items-center gap-2 bg-gray-50 hover:bg-gray-100 text-gray-700 font-medium py-2 px-4 rounded-lg transition-colors border border-gray-200">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i> Sign out
                        </a>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <h3 class="text-xs font-bold text-red-600 uppercase tracking-wider mb-3 flex items-center gap-1.5"><i class="fa-solid fa-triangle-exclamation"></i> Danger Zone</h3>
                        <button onclick="document.getElementById('deleteUserConfirmDiv').classList.toggle('hidden')" class="w-full inline-flex justify-center items-center gap-2 bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition-colors shadow-sm text-sm">
                            <i class="fa-solid fa-user-xmark"></i> Delete Account
                        </button>
                        
                        <div id="deleteUserConfirmDiv" class="hidden mt-3 bg-red-50 p-3 rounded-lg border border-red-200">
                            <form id="deleteUserForm">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <p class="text-xs text-red-700 mb-2 leading-relaxed">This permanently deletes your account and data. Type <strong>DELETE USER</strong> below to confirm.</p>
                                <input type="text" name="confirmation_text" required autocomplete="off" class="bg-white border border-red-300 text-gray-900 rounded w-full p-2 text-sm focus:ring-red-500 focus:border-red-500 font-mono mb-2">
                                <button type="submit" class="w-full bg-red-800 text-white font-bold py-2 rounded transition hover:bg-red-900 text-xs uppercase tracking-wide">Confirm Deletion</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="md:col-span-2 space-y-8">
                
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                            <i class="fa-solid fa-clock-rotate-left text-primary"></i> Recent Searches
                        </h2>
                    </div>
                    
                    <?php if(count($searchHistory) > 0): ?>
                        <div class="divide-y divide-gray-100">
                            <?php foreach($searchHistory as $search): ?>
                            <a href="<?php echo url('search.php?q=' . urlencode($search->query)); ?>" class="block p-4 hover:bg-gray-50 transition-colors flex justify-between items-center">
                                <div>
                                    <p class="font-medium text-gray-900">"<?php echo htmlspecialchars($search->query); ?>"</p>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo date('M j, Y g:i A', strtotime($search->timestamp)); ?></p>
                                </div>
                                <div class="text-xs font-medium bg-blue-50 text-blue-600 px-2.5 py-1 rounded-full border border-blue-100">
                                    <?php echo $search->result_count; ?> results
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="p-8 text-center">
                            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center text-gray-400 mx-auto mb-3 text-2xl">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900">No recent searches</h3>
                            <p class="text-gray-500 mt-1">When you search for products, they will appear here.</p>
                            <a href="<?php echo url('search.php'); ?>" class="inline-block mt-4 text-primary hover:underline font-medium">Start exploring deals &rarr;</a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="w-full">
                    <div class="flex justify-between items-end mb-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                                <i class="fa-solid fa-fire text-orange-500"></i> New Nearby Deals
                            </h2>
                            <p class="text-sm text-gray-500">Recently approved listings near you</p>
                        </div>
                        <a href="<?php echo url('search.php'); ?>" class="text-sm text-secondary font-medium hover:underline">View map &rarr;</a>
                    </div>
                    
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                        <?php if(count($recentDeals) > 0): ?>
                            <?php foreach($recentDeals as $deal): ?>
                            <a href="<?php echo url('product.php?id=' . $deal->product_id); ?>" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex gap-4 hover:shadow-md hover:-translate-y-0.5 transition-all group overflow-hidden relative block">
                                <div class="w-20 h-20 bg-gray-50 rounded-lg flex-shrink-0 flex items-center justify-center p-2 border border-gray-100">
                                    <img src="<?php echo htmlspecialchars($deal->image_url ?? 'https://via.placeholder.com/80'); ?>" class="max-h-full max-w-full object-contain mix-blend-multiply group-hover:scale-110 transition-transform duration-300">
                                </div>
                                <div class="flex flex-col flex-grow justify-between">
                                    <div class="pr-6">
                                        <h3 class="font-bold text-gray-900 text-sm line-clamp-1 group-hover:text-secondary transition-colors"><?php echo htmlspecialchars($deal->product_name); ?></h3>
                                        <p class="text-xs text-gray-500 mt-1 flex items-center gap-1.5"><i class="fa-solid fa-store w-3 text-gray-400"></i> <span class="truncate"><?php echo htmlspecialchars($deal->store_name); ?></span></p>
                                    </div>
                                    <div class="flex justify-between items-end mt-3 pt-2 border-t border-gray-50">
                                        <p class="font-black text-primary text-base">Rs. <?php echo number_format($deal->price); ?></p>
                                        <span class="text-xs font-semibold text-secondary flex items-center gap-1 bg-secondary/10 px-2.5 py-1 rounded-full"><i class="fa-solid fa-location-dot"></i> <?php echo number_format($deal->distance, 1); ?> km</span>
                                    </div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">
                                <i class="fa-regular fa-face-frown text-3xl mb-3 text-gray-300"></i>
                                <p>No fresh deals available in your area right now.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
</div>

<script>
document.getElementById('deleteUserForm')?.addEventListener('submit', (e) => {
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
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
