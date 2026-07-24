<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    redirect('/admin/login.php');
}

$db = new Database();

$db->query("SELECT l.*, p.name as product_name, p.brand, p.image_url, c.name as category_name, s.name as store_name, s.address 
           FROM listings l 
           JOIN products p ON l.product_id = p.id 
           LEFT JOIN categories c ON p.category_id = c.id
           JOIN stores s ON l.store_id = s.id 
           WHERE l.status = 'pending' 
           ORDER BY l.last_updated DESC");
$pendingListings = $db->resultSet();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $listingId = $_POST['listing_id'] ?? '';
    
    if ($action && $listingId) {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $db->query("UPDATE listings SET status = :status WHERE id = :id");
        $db->bind(':status', $status);
        $db->bind(':id', $listingId);
        $db->execute();
        
        $details = "Listing $listingId " . ($status === 'approved' ? 'approved' : 'rejected');
        $db->query("INSERT INTO admin_action_log (admin_id, action, details, ip_address) 
                   VALUES (:admin_id, :action, :details, :ip)");
        $db->bind(':admin_id', $_SESSION['admin_id']);
        $db->bind(':action', 'LISTING_' . strtoupper($action));
        $db->bind(':details', $details);
        $db->bind(':ip', $_SERVER['REMOTE_ADDR']);
        $db->execute();
        
        header('Location: ' . url('/admin/listings-approval.php'));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Listings - DealScout Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50">
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="<?php echo url('/admin/panel.php'); ?>" class="text-slate-600 hover:text-slate-900">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
                <h1 class="text-xl font-bold text-slate-900">Approve Price Listings</h1>
            </div>
            <a href="<?php echo url('/api/logout.php'); ?>" class="text-red-600 hover:text-red-800 font-medium">
                <i class="fas fa-sign-out-alt mr-2"></i>Logout
            </a>
        </div>
    </nav>
    
    <div class="max-w-7xl mx-auto px-4 py-8">
        <?php if (count($pendingListings) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($pendingListings as $listing): ?>
                    <div class="bg-white rounded-lg shadow border border-slate-200 overflow-hidden hover:shadow-md transition">
                        <div class="p-6">
                            <div class="mb-4 flex gap-4">
                                <div class="w-20 h-20 bg-slate-100 rounded-lg flex-shrink-0 flex items-center justify-center p-2 border border-slate-200">
                                    <?php 
                                        $listImg = $listing->image_url ?? 'https://via.placeholder.com/80';
                                        $fullListImg = (strpos($listImg, 'http') === 0 || strpos($listImg, '/Smriti/') === 0) ? $listImg : url($listImg);
                                    ?>
                                    <img src="<?php echo htmlspecialchars($fullListImg); ?>" class="max-h-full max-w-full object-contain">
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900"><?php echo htmlspecialchars($listing->product_name); ?></h3>
                                    <p class="text-sm text-slate-500 font-medium mb-1">
                                        <?php echo htmlspecialchars($listing->brand ?? 'No Brand'); ?> &bull; <?php echo htmlspecialchars($listing->category_name ?? 'Uncategorized'); ?>
                                    </p>
                                    <p class="text-sm text-slate-600">
                                        <i class="fas fa-store w-4 text-center mr-1"></i><?php echo htmlspecialchars($listing->store_name); ?>
                                    </p>
                                    <p class="text-sm text-slate-600">
                                        <i class="fas fa-map-marker-alt w-4 text-center mr-1"></i><?php echo htmlspecialchars($listing->address); ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 mb-4 flex justify-between items-center">
                                <div>
                                    <p class="text-xs text-slate-600 font-semibold uppercase">Listed Price</p>
                                    <p class="text-3xl font-black text-emerald-600">
                                        Rs. <?php echo number_format($listing->price, 2); ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-slate-600 font-semibold uppercase">Stock</p>
                                    <p class="text-2xl font-bold text-slate-700"><?php echo (int)($listing->stock ?? 1); ?></p>
                                </div>
                            </div>
                            
                            <div class="text-xs text-slate-600 mb-4">
                                <p>Last Updated: <?php echo date('M d, Y H:i', strtotime($listing->last_updated)); ?></p>
                                <span class="inline-block mt-2 px-2 py-1 bg-yellow-100 text-yellow-800 rounded font-semibold">
                                    <i class="fas fa-hourglass-half mr-1"></i>Pending Review
                                </span>
                            </div>
                            <div class="flex gap-2 pt-4 border-t border-slate-200">
                                <form method="POST" class="flex-1">
                                    <input type="hidden" name="listing_id" value="<?php echo $listing->id; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-3 rounded transition">
                                        <i class="fas fa-check mr-1"></i>Approve
                                    </button>
                                </form>
                                <form method="POST" class="flex-1">
                                    <input type="hidden" name="listing_id" value="<?php echo $listing->id; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-3 rounded transition">
                                        <i class="fas fa-times mr-1"></i>Reject
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow p-12 text-center">
                <i class="fas fa-check-circle text-5xl text-green-500 mb-4"></i>
                <h2 class="text-2xl font-bold text-slate-900 mb-2">All Set!</h2>
                <p class="text-slate-600 mb-6">There are no pending listings to review right now.</p>
                <a href="<?php echo url('/admin/panel.php'); ?>" class="inline-block bg-slate-900 hover:bg-slate-800 text-white font-bold py-2 px-6 rounded-lg transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
