<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$admin = $_SESSION['admin_id'] ?? null;
if (!$admin) {
    redirect('/admin/login.php');
}

$db = new Database();

$db->query("SELECT COUNT(*) as count FROM users");
$userCount = $db->single()->count;

$db->query("SELECT COUNT(*) as count FROM stores WHERE status = 'approved'");
$storeCount = $db->single()->count;

$db->query("SELECT COUNT(*) as count FROM stores WHERE status = 'pending'");
$pendingStores = $db->single()->count;

$db->query("SELECT COUNT(*) as count FROM listings WHERE status = 'pending'");
$pendingListings = $db->single()->count;

$db->query("SELECT COUNT(*) as count FROM products");
$productCount = $db->single()->count;

$db->query("SELECT * FROM admin_action_log ORDER BY timestamp DESC LIMIT 5");
$recentActivities = $db->resultSet();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Control Panel - DealScout</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50">
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <div class="w-10 h-10 rounded-lg bg-secondary text-white flex items-center justify-center font-bold">
                    <i class="fas fa-chart-line"></i>
                </div>
                <span class="text-xl font-black text-slate-900">DealScout Admin</span>
            </div>
            <div class="flex items-center gap-4">
                <a href="<?php echo url('/index.php'); ?>" class="text-slate-600 hover:text-slate-900 font-medium">
                    <i class="fas fa-home mr-2"></i>Home
                </a>
                <a href="<?php echo url('/admin/users.php'); ?>" class="text-slate-600 hover:text-slate-900 font-medium">
                    <i class="fas fa-users mr-2"></i>Users
                </a>
                <a href="<?php echo url('/admin/stores.php'); ?>" class="text-slate-600 hover:text-slate-900 font-medium flex items-center">
                    <i class="fas fa-store mr-2"></i>Stores
                    <?php if(isset($pendingStores) && $pendingStores > 0): ?>
                        <span class="ml-1 bg-yellow-500 text-white text-xs px-1.5 py-0.5 rounded-full"><?php echo $pendingStores; ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo url('/admin/products.php'); ?>" class="text-slate-600 hover:text-slate-900 font-medium">
                    <i class="fas fa-box mr-2"></i>Products
                </a>
                <a href="<?php echo url('/admin/listings-approval.php'); ?>" class="text-slate-600 hover:text-slate-900 font-medium flex items-center">
                    <i class="fas fa-list-check mr-2"></i>Approvals
                    <?php if(isset($pendingListings) && $pendingListings > 0): ?>
                        <span class="ml-1 bg-orange-500 text-white text-xs px-1.5 py-0.5 rounded-full"><?php echo $pendingListings; ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo url('/api/logout.php'); ?>" class="text-red-600 hover:text-red-800 font-medium">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>
    
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-black text-slate-900">Admin Dashboard</h1>
            <p class="text-slate-600 mt-2">Welcome to the DealScout administration panel</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-600 font-semibold text-sm">Total Users</p>
                        <p class="text-3xl font-black text-slate-900 mt-2"><?php echo $userCount; ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xl">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-emerald-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-600 font-semibold text-sm">Approved Stores</p>
                        <p class="text-3xl font-black text-slate-900 mt-2"><?php echo $storeCount; ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl">
                        <i class="fas fa-store"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-600 font-semibold text-sm">Pending Stores</p>
                        <p class="text-3xl font-black text-slate-900 mt-2"><?php echo $pendingStores; ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center text-xl">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-orange-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-600 font-semibold text-sm">Pending Listings</p>
                        <p class="text-3xl font-black text-slate-900 mt-2"><?php echo $pendingListings; ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center text-xl">
                        <i class="fas fa-list"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-600 font-semibold text-sm">Total Products</p>
                        <p class="text-3xl font-black text-slate-900 mt-2"><?php echo $productCount; ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center text-xl">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-slate-900 mb-4">
                        <i class="fas fa-flash text-yellow-500 mr-2"></i>Quick Actions
                    </h2>
                    <div class="space-y-3">
                        <a href="<?php echo url('/admin/stores.php?filter=pending'); ?>" class="w-full bg-yellow-50 hover:bg-yellow-100 border border-yellow-200 text-yellow-700 font-semibold py-3 px-4 rounded-lg transition flex items-center gap-2">
                            <i class="fas fa-check-circle"></i>
                            Review <?php echo $pendingStores; ?> Pending Stores
                        </a>
                        <a href="<?php echo url('/admin/listings-approval.php'); ?>" class="w-full bg-orange-50 hover:bg-orange-100 border border-orange-200 text-orange-700 font-semibold py-3 px-4 rounded-lg transition flex items-center gap-2">
                            <i class="fas fa-list-check"></i>
                            Approve <?php echo $pendingListings; ?> Listings
                        </a>
                        <a href="<?php echo url('/admin/users.php'); ?>" class="w-full bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-700 font-semibold py-3 px-4 rounded-lg transition flex items-center gap-2">
                            <i class="fas fa-users"></i>
                            Manage Users
                        </a>
                        <a href="<?php echo url('/admin/products.php'); ?>" class="w-full bg-purple-50 hover:bg-purple-100 border border-purple-200 text-purple-700 font-semibold py-3 px-4 rounded-lg transition flex items-center gap-2">
                            <i class="fas fa-plus"></i>
                            Add Product
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-slate-900 mb-4">
                        <i class="fas fa-history text-blue-500 mr-2"></i>Recent Activities
                    </h2>
                    <div class="space-y-3">
                        <?php if (count($recentActivities) > 0): ?>
                            <?php foreach ($recentActivities as $activity): ?>
                                <div class="flex items-start gap-4 pb-3 border-b border-slate-200 last:border-0">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 flex-shrink-0">
                                        <i class="fas fa-history text-xs"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-semibold text-slate-900">
                                            <?php echo htmlspecialchars($activity->action); ?>
                                        </p>
                                        <p class="text-xs text-slate-600 mt-1">
                                            <?php echo htmlspecialchars($activity->details ?? 'No details'); ?>
                                        </p>
                                        <p class="text-xs text-slate-500 mt-1">
                                            <?php echo date('M d, Y H:i', strtotime($activity->timestamp)); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-slate-600 text-center py-8">No recent activities</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
