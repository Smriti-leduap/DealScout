<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    redirect('/admin/login.php');
}

$db = new Database();
$filter = $_GET['filter'] ?? 'all';

$query = "SELECT s.*, u.name as owner_name, u.email as owner_email FROM stores s 
          LEFT JOIN users u ON s.owner_id = u.id";

if ($filter === 'pending') {
    $query .= " WHERE s.status = 'pending'";
} elseif ($filter === 'approved') {
    $query .= " WHERE s.status = 'approved'";
}

$query .= " ORDER BY s.created_at DESC";

$db->query($query);
$stores = $db->resultSet();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $storeId = $_POST['store_id'] ?? '';
    
    if ($action && $storeId) {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $db->query("UPDATE stores SET status = :status WHERE id = :id");
        $db->bind(':status', $status);
        $db->bind(':id', $storeId);
        $db->execute();
        
        $details = "Store $storeId " . ($status === 'approved' ? 'approved' : 'rejected');
        $db->query("INSERT INTO admin_action_log (admin_id, action, details, ip_address) 
                   VALUES (:admin_id, :action, :details, :ip)");
        $db->bind(':admin_id', $_SESSION['admin_id']);
        $db->bind(':action', 'STORE_' . strtoupper($action));
        $db->bind(':details', $details);
        $db->bind(':ip', $_SERVER['REMOTE_ADDR']);
        $db->execute();
        
        header('Location: ' . url('/admin/stores.php?filter=' . $filter));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Stores - DealScout Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
</head>
<body class="bg-slate-50">
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="<?php echo url('/admin/panel.php'); ?>" class="text-slate-600 hover:text-slate-900">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
                <h1 class="text-xl font-bold text-slate-900">Manage Stores</h1>
            </div>
            <a href="<?php echo url('/api/logout.php'); ?>" class="text-red-600 hover:text-red-800 font-medium">
                <i class="fas fa-sign-out-alt mr-2"></i>Logout
            </a>
        </div>
    </nav>
    
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-6 flex gap-2">
            <a href="<?php echo url('/admin/stores.php?filter=all'); ?>" class="px-4 py-2 rounded-lg font-semibold <?php echo $filter === 'all' ? 'bg-slate-900 text-white' : 'bg-white text-slate-900 border border-slate-300'; ?>">
                All Stores
            </a>
            <a href="<?php echo url('/admin/stores.php?filter=pending'); ?>" class="px-4 py-2 rounded-lg font-semibold <?php echo $filter === 'pending' ? 'bg-yellow-500 text-white' : 'bg-white text-slate-900 border border-slate-300'; ?>">
                Pending
            </a>
            <a href="<?php echo url('/admin/stores.php?filter=approved'); ?>" class="px-4 py-2 rounded-lg font-semibold <?php echo $filter === 'approved' ? 'bg-green-500 text-white' : 'bg-white text-slate-900 border border-slate-300'; ?>">
                Approved
            </a>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (count($stores) > 0): ?>
                <?php foreach ($stores as $store): ?>
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition">
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900"><?php echo htmlspecialchars($store->name); ?></h3>
                                    <p class="text-sm text-slate-600 mt-1">
                                        <i class="fas fa-map-marker-alt mr-1"></i><?php echo htmlspecialchars($store->address); ?>
                                    </p>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    <?php echo $store->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($store->status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'); ?>">
                                    <?php echo ucfirst($store->status); ?>
                                </span>
                            </div>
                            
                            <div class="space-y-2 text-sm text-slate-600 mb-4">
                                <p><i class="fas fa-phone mr-2 text-slate-400"></i><?php echo htmlspecialchars($store->phone ?? 'N/A'); ?></p>
                                <p><i class="fas fa-envelope mr-2 text-slate-400"></i><?php echo htmlspecialchars($store->email ?? $store->owner_email ?? 'N/A'); ?></p>
                                <p><i class="fas fa-user mr-2 text-slate-400"></i><?php echo htmlspecialchars($store->owner_name ?? 'No owner'); ?></p>
                                <button onclick="viewMap(<?php echo $store->latitude; ?>, <?php echo $store->longitude; ?>, '<?php echo htmlspecialchars(addslashes($store->name)); ?>')" class="text-blue-600 hover:underline text-xs mt-2 block font-semibold"><i class="fas fa-map mr-1"></i> View Pinned Location</button>
                            </div>
                            
                            <?php if ($store->status === 'pending'): ?>
                                <div class="flex gap-2 pt-4 border-t border-slate-200">
                                    <form method="POST" class="flex-1">
                                        <input type="hidden" name="store_id" value="<?php echo $store->id; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-3 rounded text-sm transition">
                                            <i class="fas fa-check mr-1"></i>Approve
                                        </button>
                                    </form>
                                    <form method="POST" class="flex-1">
                                        <input type="hidden" name="store_id" value="<?php echo $store->id; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-3 rounded text-sm transition">
                                            <i class="fas fa-times mr-1"></i>Reject
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-12">
                    <i class="fas fa-inbox text-4xl text-slate-300 mb-4"></i>
                    <p class="text-slate-600 font-semibold">No stores found</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="mapModal" class="hidden fixed inset-0 bg-slate-900 bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-2xl overflow-hidden flex flex-col">
            <div class="p-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-lg text-slate-800" id="mapModalTitle">Store Location</h3>
                <button onclick="closeMap()" class="text-slate-400 hover:text-slate-600 text-xl"><i class="fas fa-times"></i></button>
            </div>
            <div id="adminMap" class="w-full h-96 z-0"></div>
        </div>
    </div>

    <script>
    let adminMap = null;
    let adminMarker = null;

    function viewMap(lat, lng, storeName) {
        document.getElementById('mapModalTitle').innerText = storeName + ' - Location';
        document.getElementById('mapModal').classList.remove('hidden');
        
        setTimeout(() => {
            if (!adminMap) {
                adminMap = L.map('adminMap').setView([lat, lng], 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(adminMap);
                adminMarker = L.marker([lat, lng]).addTo(adminMap);
            } else {
                adminMap.setView([lat, lng], 16);
                adminMarker.setLatLng([lat, lng]);
                adminMap.invalidateSize();
            }
            adminMarker.bindPopup('<b>' + storeName + '</b>').openPopup();
        }, 100);
    }

    function closeMap() {
        document.getElementById('mapModal').classList.add('hidden');
    }
    </script>
</body>
</html>
