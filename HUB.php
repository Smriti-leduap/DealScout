<?php


require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DealScout - Navigation Hub</title>
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
                <span class="text-xl font-black text-slate-900">DealScout Hub</span>
            </div>
            <div class="flex items-center gap-4">
                <a href="<?php echo url('/index.php'); ?>" class="text-slate-600 hover:text-slate-900 font-medium">
                    <i class="fas fa-home mr-2"></i>Home
                </a>
            </div>
        </div>
    </nav>
    
    <div class="max-w-6xl mx-auto px-4 py-12">
        <div class="mb-12 text-center">
            <h1 class="text-5xl font-black text-slate-900 mb-4">Welcome to DealScout</h1>
            <p class="text-xl text-slate-600">Choose your entry point below to get started</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
            <a href="<?php echo url('/start.php'); ?>" class="group">
                <div class="bg-white rounded-lg shadow-sm hover:shadow-lg transition p-6 border-l-4 border-blue-500 h-full">
                    <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition">
                        <i class="fas fa-stethoscope"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">System Check</h3>
                    <p class="text-slate-600 text-sm">Verify system requirements and diagnostics</p>
                    <p class="text-xs text-slate-500 mt-4"><i class="fas fa-link mr-1"></i>start.php</p>
                </div>
            </a>
            
            <a href="<?php echo url('/setup.php'); ?>" class="group">
                <div class="bg-white rounded-lg shadow-sm hover:shadow-lg transition p-6 border-l-4 border-emerald-500 h-full">
                    <div class="w-12 h-12 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Setup Wizard</h3>
                    <p class="text-slate-600 text-sm">Initialize database and configure platform</p>
                    <p class="text-xs text-slate-500 mt-4"><i class="fas fa-link mr-1"></i>setup.php</p>
                </div>
            </a>
            
            <a href="<?php echo url('/GETTING_STARTED.php'); ?>" class="group">
                <div class="bg-white rounded-lg shadow-sm hover:shadow-lg transition p-6 border-l-4 border-yellow-500 h-full">
                    <div class="w-12 h-12 rounded-lg bg-yellow-100 text-yellow-600 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Getting Started</h3>
                    <p class="text-slate-600 text-sm">Complete guide and tutorials for all features</p>
                    <p class="text-xs text-slate-500 mt-4"><i class="fas fa-link mr-1"></i>GETTING_STARTED.php</p>
                </div>
            </a>
            
            <a href="<?php echo url('/index.php'); ?>" class="group">
                <div class="bg-white rounded-lg shadow-sm hover:shadow-lg transition p-6 border-l-4 border-purple-500 h-full">
                    <div class="w-12 h-12 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition">
                        <i class="fas fa-home"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Home Page</h3>
                    <p class="text-slate-600 text-sm">Main platform interface and dashboard</p>
                    <p class="text-xs text-slate-500 mt-4"><i class="fas fa-link mr-1"></i>index.php</p>
                </div>
            </a>
            
            <a href="<?php echo url('/login.php'); ?>" class="group">
                <div class="bg-white rounded-lg shadow-sm hover:shadow-lg transition p-6 border-l-4 border-cyan-500 h-full">
                    <div class="w-12 h-12 rounded-lg bg-cyan-100 text-cyan-600 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">User Login</h3>
                    <p class="text-slate-600 text-sm">Login as regular user or store owner</p>
                    <p class="text-xs text-slate-500 mt-4"><i class="fas fa-link mr-1"></i>login.php</p>
                </div>
            </a>
            
            <a href="<?php echo url('/admin/login.php'); ?>" class="group">
                <div class="bg-white rounded-lg shadow-sm hover:shadow-lg transition p-6 border-l-4 border-red-500 h-full">
                    <div class="w-12 h-12 rounded-lg bg-red-100 text-red-600 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Admin Panel</h3>
                    <p class="text-slate-600 text-sm">Access administration and management tools</p>
                    <p class="text-xs text-slate-500 mt-4"><i class="fas fa-link mr-1"></i>admin/login.php</p>
                </div>
            </a>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-8 mb-12">
            <h2 class="text-2xl font-bold text-slate-900 mb-6 flex items-center gap-2">
                <i class="fas fa-book text-emerald-600"></i>Documentation & References
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="p-4 bg-slate-50 rounded-lg">
                    <h3 class="font-bold text-slate-900 mb-2 flex items-center gap-2">
                        <i class="fas fa-file-alt text-blue-600"></i>README.md
                    </h3>
                    <p class="text-sm text-slate-600 mb-3">Complete platform documentation including features, database schema, API endpoints, and troubleshooting.</p>
                    <a href="#" class="text-blue-600 hover:text-blue-700 font-semibold text-sm">Read Documentation →</a>
                </div>
                
                <div class="p-4 bg-slate-50 rounded-lg">
                    <h3 class="font-bold text-slate-900 mb-2 flex items-center gap-2">
                        <i class="fas fa-tasks text-emerald-600"></i>SETUP_COMPLETE.md
                    </h3>
                    <p class="text-sm text-slate-600 mb-3">Summary of all files created, features implemented, quality checklist, and next steps after setup.</p>
                    <a href="#" class="text-emerald-600 hover:text-emerald-700 font-semibold text-sm">View Summary →</a>
                </div>
            </div>
        </div>
        
        <div class="bg-secondary rounded-lg p-8 mb-12 border border-emerald-200">
            <h2 class="text-2xl font-bold text-emerald-900 mb-4 flex items-center gap-2">
                <i class="fas fa-zap"></i>Quick Start (Recommended)
            </h2>
            <ol class="space-y-3 text-emerald-800">
                <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold text-sm">1</span>
                    <span><strong>Visit System Check:</strong> Go to <strong>start.php</strong> to verify your system</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold text-sm">2</span>
                    <span><strong>Run Setup:</strong> Go to <strong>setup.php</strong> and complete all 4 steps</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold text-sm">3</span>
                    <span><strong>Login to Admin:</strong> Go to <strong>admin/login.php</strong> with your credentials</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold text-sm">4</span>
                    <span><strong>Explore Platform:</strong> Test the home page and all features</span>
                </li>
            </ol>
        </div>
        
        <div class="bg-blue-50 rounded-lg p-8 border border-blue-200">
            <h2 class="text-2xl font-bold text-blue-900 mb-6 flex items-center gap-2">
                <i class="fas fa-key"></i>Test Credentials (After Setup)
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded p-4 border border-blue-200">
                    <p class="font-bold text-slate-900 mb-3 text-lg">👤 Regular User</p>
                    <div class="space-y-2 text-sm">
                        <div>
                            <p class="text-blue-700 font-semibold">Email:</p>
                            <p class="font-mono bg-slate-50 p-2 rounded">demo@dealscout.com</p>
                        </div>
                        <div>
                            <p class="text-blue-700 font-semibold">Password:</p>
                            <p class="font-mono bg-slate-50 p-2 rounded">demo123</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded p-4 border border-blue-200">
                    <p class="font-bold text-slate-900 mb-3 text-lg">🏪 Store Owner</p>
                    <div class="space-y-2 text-sm">
                        <div>
                            <p class="text-blue-700 font-semibold">Email:</p>
                            <p class="font-mono bg-slate-50 p-2 rounded">owner@dealscout.com</p>
                        </div>
                        <div>
                            <p class="text-blue-700 font-semibold">Password:</p>
                            <p class="font-mono bg-slate-50 p-2 rounded">owner123</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded p-4 border border-blue-200">
                    <p class="font-bold text-slate-900 mb-3 text-lg">👑 Admin</p>
                    <div class="space-y-2 text-sm">
                        <div>
                            <p class="text-blue-700 font-semibold">Username:</p>
                            <p class="font-mono bg-slate-50 p-2 rounded">Set during setup</p>
                        </div>
                        <div>
                            <p class="text-blue-700 font-semibold">Password:</p>
                            <p class="font-mono bg-slate-50 p-2 rounded">Set during setup</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-white border-t border-slate-200 mt-16">
        <div class="max-w-6xl mx-auto px-4 py-8 text-center text-slate-600">
            <p class="font-semibold mb-2">DealScout © 2024</p>
            <p>Nearby Price Comparison Platform | All Systems Operational ✓</p>
        </div>
    </div>
</body>
</html>
