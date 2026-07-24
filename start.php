<?php

require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DealScout - Welcome & Diagnostics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 min-h-screen">
    <div class="min-h-screen flex items-center justify-center px-4 py-8">
        <div class="w-full max-w-3xl">
            <div class="text-center mb-12">
                <div class="w-24 h-24 rounded-3xl bg-secondary text-white flex items-center justify-center text-5xl mx-auto mb-6 shadow-2xl">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h1 class="text-4xl font-black text-white mb-2">Welcome to DealScout</h1>
                <p class="text-emerald-300 font-semibold">Nearby Price Comparison Platform</p>
            </div>
            
            <div class="bg-white rounded-2xl shadow-2xl p-8 mb-8">
                <h2 class="text-2xl font-bold text-slate-900 mb-6">
                    <i class="fas fa-stethoscope text-blue-600 mr-2"></i>System Diagnostics
                </h2>
                
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg border border-slate-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
                                <i class="fas fa-code"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-900">PHP Version</p>
                                <p class="text-sm text-slate-600"><?php echo phpversion(); ?></p>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-full bg-green-100 text-green-800 text-xs font-bold">
                            <i class="fas fa-check-circle mr-1"></i>OK
                        </span>
                    </div>
                    
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg border border-slate-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center">
                                <i class="fas fa-database"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-900">SQLite Support</p>
                                <p class="text-sm text-slate-600">
                                    <?php 
                                    $hasSQLite = extension_loaded('pdo_sqlite');
                                    echo $hasSQLite ? 'PDO SQLite enabled' : 'Not available';
                                    ?>
                                </p>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-full <?php echo $hasSQLite ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> text-xs font-bold">
                            <i class="fas fa-<?php echo $hasSQLite ? 'check-circle' : 'times-circle'; ?> mr-1"></i>
                            <?php echo $hasSQLite ? 'OK' : 'ERROR'; ?>
                        </span>
                    </div>
                    
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg border border-slate-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-green-100 text-green-600 flex items-center justify-center">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-900">Sessions</p>
                                <p class="text-sm text-slate-600">
                                    <?php 
                                    $sessionPath = ini_get('session.save_path');
                                    echo $sessionPath ?: ini_get('session.save_path') ?: 'Default';
                                    ?>
                                </p>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-full bg-green-100 text-green-800 text-xs font-bold">
                            <i class="fas fa-check-circle mr-1"></i>OK
                        </span>
                    </div>
                    
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg border border-slate-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center">
                                <i class="fas fa-lock"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-900">Directory Writable</p>
                                <p class="text-sm text-slate-600">
                                    <?php 
                                    $isWritable = is_writable(__DIR__ . '/../');
                                    echo $isWritable ? 'Yes' : 'No';
                                    ?>
                                </p>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-full <?php echo $isWritable ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> text-xs font-bold">
                            <i class="fas fa-<?php echo $isWritable ? 'check-circle' : 'times-circle'; ?> mr-1"></i>
                            <?php echo $isWritable ? 'OK' : 'ERROR'; ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-2xl p-8 mb-8">
                <h2 class="text-2xl font-bold text-slate-900 mb-6">
                    <i class="fas fa-rocket text-yellow-600 mr-2"></i>Getting Started
                </h2>
                
                <div class="space-y-4">
                    <div class="flex gap-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                            1
                        </div>
                        <div>
                            <p class="font-bold text-slate-900">Complete the Setup Wizard</p>
                            <p class="text-sm text-slate-600 mt-1">Create database tables, categories, admin account, and demo data</p>
                            <a href="<?php echo url('/setup.php'); ?>" class="text-emerald-600 hover:text-emerald-700 font-semibold text-sm mt-2 inline-flex items-center gap-1">
                                Start Setup <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div class="flex gap-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold">
                            2
                        </div>
                        <div>
                            <p class="font-bold text-slate-900">Login to Admin Panel</p>
                            <p class="text-sm text-slate-600 mt-1">Manage users, stores, products, and approvals</p>
                            <a href="<?php echo url('/admin/login.php'); ?>" class="text-blue-600 hover:text-blue-700 font-semibold text-sm mt-2 inline-flex items-center gap-1">
                                Go to Admin <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div class="flex gap-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center font-bold">
                            3
                        </div>
                        <div>
                            <p class="font-bold text-slate-900">Try the Platform</p>
                            <p class="text-sm text-slate-600 mt-1">Use demo credentials to explore features</p>
                            <a href="<?php echo url('/index.php'); ?>" class="text-purple-600 hover:text-purple-700 font-semibold text-sm mt-2 inline-flex items-center gap-1">
                                Go to Home <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-blue-50 border border-blue-200 rounded-2xl shadow p-8 mb-8">
                <h3 class="text-xl font-bold text-blue-900 mb-4">
                    <i class="fas fa-key mr-2"></i>Demo Credentials
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white rounded-lg p-4">
                        <p class="text-sm font-semibold text-slate-700 mb-2">Regular User</p>
                        <p class="text-xs text-slate-600 mb-1"><strong>Email:</strong> demo@dealscout.com</p>
                        <p class="text-xs text-slate-600"><strong>Password:</strong> demo123</p>
                    </div>
                    
                    <div class="bg-white rounded-lg p-4">
                        <p class="text-sm font-semibold text-slate-700 mb-2">Store Owner</p>
                        <p class="text-xs text-slate-600 mb-1"><strong>Email:</strong> owner@dealscout.com</p>
                        <p class="text-xs text-slate-600"><strong>Password:</strong> owner123</p>
                    </div>
                    
                    <div class="bg-white rounded-lg p-4">
                        <p class="text-sm font-semibold text-slate-700 mb-2">Admin</p>
                        <p class="text-xs text-slate-600 mb-1"><strong>Username:</strong> Set during setup</p>
                        <p class="text-xs text-slate-600"><strong>Password:</strong> Set during setup</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-secondary rounded-2xl shadow-lg p-8 text-white">
                <h3 class="text-xl font-bold mb-4">
                    <i class="fas fa-book mr-2"></i>Documentation
                </h3>
                <p class="text-slate-300 mb-4">For detailed information about the platform, features, and troubleshooting, see the <strong>README.md</strong> file.</p>
                <div class="flex flex-wrap gap-2">
                    <a href="https://localhost/Smriti/README.md" target="_blank" class="px-4 py-2 bg-white text-slate-900 rounded-lg font-semibold hover:bg-slate-100 transition text-sm">
                        <i class="fas fa-external-link-alt mr-1"></i>Read Documentation
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
