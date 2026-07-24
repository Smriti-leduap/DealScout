<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: ' . url('/admin/panel.php'));
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        try {
            $db = new Database();
            $db->query("SELECT * FROM admins WHERE username = :username LIMIT 1");
            $db->bind(':username', $username);
            $admin = $db->single();
            
            if ($admin && password_verify($password, $admin->password)) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $admin->id;
                $_SESSION['admin_username'] = $admin->username;
                $_SESSION['admin_role'] = $admin->role;
                
                $db->query("UPDATE admins SET last_login = CURRENT_TIMESTAMP WHERE id = :id");
                $db->bind(':id', $admin->id);
                $db->execute();
                
                header('Location: ' . url('/admin/panel.php'));
                exit;
            } else {
                $error = 'Invalid username or password';
            }
        } catch (Exception $e) {
            $error = 'An error occurred: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - DealScout</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 min-h-screen">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <div class="w-16 h-16 rounded-2xl bg-secondary text-white flex items-center justify-center text-2xl mx-auto mb-4 shadow-lg">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h1 class="text-3xl font-black text-white">DealScout</h1>
                <p class="text-emerald-300 font-semibold mt-2">Admin Login</p>
            </div>
            
            <div class="bg-white rounded-2xl shadow-2xl p-8">
                <?php if ($error): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">
                        <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-5">
                    <div>
                        <label for="username" class="block text-sm font-semibold text-slate-700 mb-2">Username</label>
                        <input type="text" name="username" id="username" required class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none" placeholder="Enter username">
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">Password</label>
                        <input type="password" name="password" id="password" required class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none" placeholder="Enter password">
                    </div>
                    
                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-lg transition">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </button>
                </form>
                
                <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-sm text-blue-800"><i class="fas fa-info-circle mr-2"></i>Demo credentials:</p>
                    <p class="text-xs text-blue-700 mt-2 font-mono">Username: admin</p>
                    <p class="text-xs text-blue-700 font-mono">Password: (set during setup)</p>
                </div>
            </div>
            
            <div class="text-center mt-8">
                <a href="<?php echo url('/index.php'); ?>" class="text-slate-400 hover:text-white text-sm">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>
