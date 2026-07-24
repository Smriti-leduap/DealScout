<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    redirect('/admin/login.php');
}

$db = new Database();

$db->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $db->resultSet();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - DealScout Admin</title>
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
                <h1 class="text-xl font-bold text-slate-900">Manage Users</h1>
            </div>
            <a href="<?php echo url('/api/logout.php'); ?>" class="text-red-600 hover:text-red-800 font-medium">
                <i class="fas fa-sign-out-alt mr-2"></i>Logout
            </a>
        </div>
    </nav>
    
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-slate-100 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Name</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Email</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Role</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Location</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Status</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Joined</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4">
                                <span class="font-semibold text-slate-900"><?php echo htmlspecialchars($user->name); ?></span>
                            </td>
                            <td class="px-6 py-4 text-slate-600"><?php echo htmlspecialchars($user->email); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    <?php echo $user->role === 'admin' ? 'bg-red-100 text-red-800' : ($user->role === 'store_owner' ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-800'); ?>">
                                    <?php echo ucfirst($user->role); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-600"><?php echo htmlspecialchars($user->location ?? 'N/A'); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    <?php echo $user->status === 'active' ? 'bg-green-100 text-green-800' : ($user->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                    <?php echo ucfirst($user->status); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                <?php echo date('M d, Y', strtotime($user->created_at)); ?>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium">
                                <?php if ($user->role === 'store_owner' && $user->status === 'pending'): ?>
                                    <button onclick="approveUser(<?php echo $user->id; ?>, 'active')" class="bg-green-500 hover:bg-green-600 text-white font-bold text-xs py-1.5 px-3 rounded shadow transition mr-2">
                                        <i class="fas fa-check mr-1"></i>Approve
                                    </button>
                                    <button onclick="approveUser(<?php echo $user->id; ?>, 'inactive')" class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 font-bold text-xs py-1.5 px-3 rounded transition">
                                        <i class="fas fa-times mr-1"></i>Reject
                                    </button>
                                <?php elseif ($user->role === 'store_owner' && $user->status === 'active'): ?>
                                    <button onclick="approveUser(<?php echo $user->id; ?>, 'inactive')" class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 font-bold text-xs py-1.5 px-3 rounded transition">
                                        <i class="fas fa-ban mr-1"></i>Suspend
                                    </button>
                                <?php elseif ($user->role === 'store_owner' && $user->status === 'inactive'): ?>
                                    <button onclick="approveUser(<?php echo $user->id; ?>, 'active')" class="bg-green-500 hover:bg-green-600 text-white font-bold text-xs py-1.5 px-3 rounded shadow transition">
                                        <i class="fas fa-undo mr-1"></i>Activate
                                    </button>
                                <?php else: ?>
                                    <span class="text-slate-400 text-xs">No Actions</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function approveUser(userId, status) {
        let actionWord = status === 'active' ? 'activate/approve' : (status === 'inactive' ? 'reject/suspend' : status);
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
</body>
</html>
