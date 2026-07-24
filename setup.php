<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$step = $_GET['step'] ?? 1;
$message = '';
$error = '';
$success = false;

function isDatabaseInitialized() {
    try {
        $db = new Database();
        $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
        $result = $db->single();
        return $result !== null;
    } catch (Exception $e) {
        return false;
    }
}

if ($step == 1 && $_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db = new Database();
        $dbh = $db->getDbh();
        
        $sqlStatements = [
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                location TEXT,
                role TEXT DEFAULT 'user',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                status TEXT DEFAULT 'active'
            )",
            
            "CREATE TABLE IF NOT EXISTS stores (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                address TEXT NOT NULL,
                latitude REAL NOT NULL,
                longitude REAL NOT NULL,
                phone TEXT,
                opening_hours TEXT,
                owner_id INTEGER,
                status TEXT DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_updated DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS admins (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                role TEXT DEFAULT 'moderator',
                last_login DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                parent_id INTEGER NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                brand TEXT,
                category_id INTEGER,
                description TEXT,
                image_url TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS listings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                store_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                price REAL NOT NULL,
                stock INTEGER DEFAULT 1,
                latitude REAL,
                longitude REAL,
                status TEXT DEFAULT 'pending',
                last_updated DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(store_id, product_id),
                FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            )",
            
            "CREATE TABLE IF NOT EXISTS search_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                query TEXT NOT NULL,
                result_count INTEGER DEFAULT 0,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS admin_action_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                admin_id INTEGER NOT NULL,
                action TEXT NOT NULL,
                details TEXT,
                ip_address TEXT,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (admin_id) REFERENCES admins(id)
            )",
            
            "CREATE TABLE IF NOT EXISTS approval_requests (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                type TEXT NOT NULL,
                item_id INTEGER NOT NULL,
                requested_by INTEGER,
                status TEXT DEFAULT 'pending',
                reviewed_by INTEGER,
                review_date DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (reviewed_by) REFERENCES admins(id) ON DELETE SET NULL
            )"
        ];
        
        foreach ($sqlStatements as $sql) {
            $dbh->exec($sql);
        }
        
        $success = true;
        $message = "✓ Database tables created successfully!";
    } catch (Exception $e) {
        $error = "✗ Error creating tables: " . $e->getMessage();
    }
}

if ($step == 2 && $_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db = new Database();
        
        $categories = [
            'Electronics' => ['Phones', 'Laptops', 'Tablets', 'Accessories'],
            'Home & Living' => ['Furniture', 'Decor', 'Kitchen', 'Bedding'],
            'Fashion' => ['Men', 'Women', 'Kids', 'Accessories'],
            'Food & Grocery' => ['Snacks', 'Beverages', 'Dairy', 'Fresh Produce'],
            'Books & Media' => ['Books', 'Magazines', 'DVDs', 'Music']
        ];
        
        $categoryCount = 0;
        
        foreach ($categories as $parent => $children) {
            $db->query("INSERT OR IGNORE INTO categories (name) VALUES (:name)");
            $db->bind(':name', $parent);
            $db->execute();
            
            $categoryCount++;
            
            $db->query("SELECT id FROM categories WHERE name = :name LIMIT 1");
            $db->bind(':name', $parent);
            $parentCat = $db->single();
            
            if ($parentCat) {
                foreach ($children as $child) {
                    $db->query("INSERT OR IGNORE INTO categories (name, parent_id) VALUES (:name, :parent_id)");
                    $db->bind(':name', $child);
                    $db->bind(':parent_id', $parentCat->id);
                    $db->execute();
                    $categoryCount++;
                }
            }
        }
        
        $success = true;
        $message = "✓ $categoryCount categories and subcategories created!";
    } catch (Exception $e) {
        $error = "✗ Error seeding categories: " . $e->getMessage();
    }
}

if ($step == 3 && $_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $username = sanitize($_POST['admin_username'] ?? '');
        $password = $_POST['admin_password'] ?? '';
        $confirm_password = $_POST['admin_confirm_password'] ?? '';
        
        if (empty($username) || empty($password) || empty($confirm_password)) {
            $error = "✗ All fields are required!";
        } elseif ($password !== $confirm_password) {
            $error = "✗ Passwords do not match!";
        } elseif (strlen($password) < 6) {
            $error = "✗ Password must be at least 6 characters!";
        } else {
            $db = new Database();
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            
            $db->query("INSERT INTO admins (username, password, role) VALUES (:username, :password, :role)");
            $db->bind(':username', $username);
            $db->bind(':password', $hashedPassword);
            $db->bind(':role', 'super_admin');
            $db->execute();
            
            $success = true;
            $message = "✓ Admin account created! Username: <strong>$username</strong>";
        }
    } catch (Exception $e) {
        $error = "✗ Error creating admin: " . $e->getMessage();
    }
}

if ($step == 4 && $_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db = new Database();
        
        $userEmail = "demo@dealscout.com";
        $userName = "Demo User";
        $userPassword = password_hash("demo123", PASSWORD_BCRYPT);
        
        $db->query("INSERT OR IGNORE INTO users (name, email, password, role, location, status) 
                   VALUES (:name, :email, :password, :role, :location, :status)");
        $db->bind(':name', $userName);
        $db->bind(':email', $userEmail);
        $db->bind(':password', $userPassword);
        $db->bind(':role', 'user');
        $db->bind(':location', 'Kathmandu, Nepal');
        $db->bind(':status', 'active');
        $db->execute();
        
        $storeOwnerEmail = "owner@dealscout.com";
        $storeOwnerName = "Store Owner";
        $storeOwnerPassword = password_hash("owner123", PASSWORD_BCRYPT);
        
        $db->query("INSERT OR IGNORE INTO users (name, email, password, role, location, status) 
                   VALUES (:name, :email, :password, :role, :location, :status)");
        $db->bind(':name', $storeOwnerName);
        $db->bind(':email', $storeOwnerEmail);
        $db->bind(':password', $storeOwnerPassword);
        $db->bind(':role', 'store_owner');
        $db->bind(':location', 'Kathmandu, Nepal');
        $db->bind(':status', 'active');
        $db->execute();
        
        $db->query("SELECT id FROM users WHERE email = :email LIMIT 1");
        $db->bind(':email', $storeOwnerEmail);
        $owner = $db->single();
        
        if ($owner) {
            $stores = [
                [
                    'name' => 'ElectroHub Kathmandu',
                    'address' => 'Thamel, Kathmandu',
                    'latitude' => 27.7172,
                    'longitude' => 85.3240,
                    'phone' => '+977-1-4234567',
                    'opening_hours' => '9:00 AM - 9:00 PM'
                ],
                [
                    'name' => 'Fresh & Organic Market',
                    'address' => 'Putali Sadak, Kathmandu',
                    'latitude' => 27.7156,
                    'longitude' => 85.3255,
                    'phone' => '+977-1-4987654',
                    'opening_hours' => '8:00 AM - 10:00 PM'
                ]
            ];
            
            foreach ($stores as $store) {
                $db->query("INSERT INTO stores (name, address, latitude, longitude, phone, opening_hours, owner_id, status) 
                           VALUES (:name, :address, :latitude, :longitude, :phone, :opening_hours, :owner_id, :status)");
                $db->bind(':name', $store['name']);
                $db->bind(':address', $store['address']);
                $db->bind(':latitude', $store['latitude']);
                $db->bind(':longitude', $store['longitude']);
                $db->bind(':phone', $store['phone']);
                $db->bind(':opening_hours', $store['opening_hours']);
                $db->bind(':owner_id', $owner->id);
                $db->bind(':status', 'approved');
                $db->execute();
            }
        }
        
        $db->query("SELECT id FROM categories WHERE name = 'Electronics' LIMIT 1");
        $category = $db->single();
        
        if ($category) {
            $products = [
                ['name' => 'iPhone 13', 'brand' => 'Apple', 'description' => 'Latest iPhone with A15 Bionic chip'],
                ['name' => 'Samsung Galaxy S21', 'brand' => 'Samsung', 'description' => 'Flagship Android phone'],
                ['name' => 'MacBook Pro', 'brand' => 'Apple', 'description' => 'Powerful laptop for professionals']
            ];
            
            foreach ($products as $product) {
                $db->query("INSERT INTO products (name, brand, category_id, description) 
                           VALUES (:name, :brand, :category_id, :description)");
                $db->bind(':name', $product['name']);
                $db->bind(':brand', $product['brand']);
                $db->bind(':category_id', $category->id);
                $db->bind(':description', $product['description']);
                $db->execute();
            }
        }
        
        $success = true;
        $message = "✓ Demo data created! User: <strong>demo@dealscout.com / demo123</strong> | Owner: <strong>owner@dealscout.com / owner123</strong>";
    } catch (Exception $e) {
        $error = "✗ Error creating demo data: " . $e->getMessage();
    }
}

$dbReady = isDatabaseInitialized();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DealScout - Setup & Configuration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .step-active { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .step-inactive { background: #e5e7eb; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 min-h-screen">
    <div class="min-h-screen flex items-center justify-center px-4 py-8">
        <div class="w-full max-w-2xl">
            <div class="text-center mb-12">
                <div class="w-20 h-20 rounded-3xl bg-secondary text-white flex items-center justify-center text-4xl mx-auto mb-6 shadow-2xl">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h1 class="text-4xl font-black text-white mb-2">DealScout</h1>
                <p class="text-emerald-300 font-semibold">Nearby Price Comparison Platform</p>
                <p class="text-slate-400 text-sm mt-2">Complete Setup Wizard</p>
            </div>
            
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
                <div class="bg-secondary px-8 py-6">
                    <div class="flex justify-between items-center">
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                            <div class="flex flex-col items-center">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-white mb-2 step-<?php echo $i <= $step ? 'active' : 'inactive'; ?>">
                                    <?php echo $i <= $step ? '<i class="fas fa-check"></i>' : $i; ?>
                                </div>
                                <span class="text-xs font-semibold text-slate-600 text-center">
                                    <?php 
                                    $labels = ['Database', 'Categories', 'Admin', 'Demo Data'];
                                    echo $labels[$i-1];
                                    ?>
                                </span>
                            </div>
                            <?php if ($i < 4): ?>
                                <div class="flex-1 h-0.5 mx-2 mt-5 <?php echo $i < $step ? 'bg-emerald-500' : 'bg-slate-300'; ?>"></div>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="p-8">
                    <?php if ($success): ?>
                        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-800 fade-in">
                            <i class="fas fa-check-circle mr-2"></i> <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 fade-in">
                            <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($step == 1): ?>
                        <div class="fade-in">
                            <h2 class="text-2xl font-bold text-slate-900 mb-4">
                                <i class="fas fa-database text-emerald-600 mr-2"></i>Initialize Database
                            </h2>
                            <p class="text-slate-600 mb-6">Create all necessary database tables for the DealScout platform.</p>
                            
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                                <p class="text-sm text-blue-800"><i class="fas fa-info-circle mr-2"></i>This will create all required tables including users, stores, products, listings, and more.</p>
                            </div>
                            
                            <form method="POST" class="space-y-4">
                                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-lg transition">
                                    <i class="fas fa-play mr-2"></i>Create Database Tables
                                </button>
                            </form>
                        </div>
                    
                    <?php elseif ($step == 2): ?>
                        <div class="fade-in">
                            <h2 class="text-2xl font-bold text-slate-900 mb-4">
                                <i class="fas fa-tags text-emerald-600 mr-2"></i>Seed Categories
                            </h2>
                            <p class="text-slate-600 mb-6">Add product categories to the database.</p>
                            
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                                <p class="text-sm text-blue-800"><i class="fas fa-info-circle mr-2"></i>Categories include: Electronics, Home & Living, Fashion, Food & Grocery, and Books & Media with subcategories.</p>
                            </div>
                            
                            <form method="POST" class="space-y-4">
                                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-lg transition">
                                    <i class="fas fa-play mr-2"></i>Seed Categories
                                </button>
                            </form>
                        </div>
                    
                    <?php elseif ($step == 3): ?>
                        <div class="fade-in">
                            <h2 class="text-2xl font-bold text-slate-900 mb-4">
                                <i class="fas fa-user-shield text-emerald-600 mr-2"></i>Create Admin Account
                            </h2>
                            <p class="text-slate-600 mb-6">Set up your admin account to manage the platform.</p>
                            
                            <form method="POST" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Admin Username</label>
                                    <input type="text" name="admin_username" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none" placeholder="e.g., admin">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Password</label>
                                    <input type="password" name="admin_password" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none" placeholder="Min 6 characters">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Confirm Password</label>
                                    <input type="password" name="admin_confirm_password" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none" placeholder="Confirm password">
                                </div>
                                
                                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-lg transition">
                                    <i class="fas fa-play mr-2"></i>Create Admin Account
                                </button>
                            </form>
                        </div>
                    
                    <?php elseif ($step == 4): ?>
                        <div class="fade-in">
                            <h2 class="text-2xl font-bold text-slate-900 mb-4">
                                <i class="fas fa-database text-emerald-600 mr-2"></i>Create Demo Data
                            </h2>
                            <p class="text-slate-600 mb-6">Populate the database with demo users, stores, and products.</p>
                            
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                                <p class="text-sm text-blue-800 mb-3"><i class="fas fa-info-circle mr-2"></i>Demo accounts will be created:</p>
                                <ul class="text-sm text-blue-800 space-y-1 ml-4">
                                    <li>• User: <strong>demo@dealscout.com / demo123</strong></li>
                                    <li>• Store Owner: <strong>owner@dealscout.com / owner123</strong></li>
                                </ul>
                            </div>
                            
                            <form method="POST" class="space-y-4">
                                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-lg transition">
                                    <i class="fas fa-play mr-2"></i>Create Demo Data
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                                        <div class="flex gap-4 mt-8 pt-8 border-t border-slate-200">
                        <?php if ($step > 1): ?>
                            <a href="setup.php?step=<?php echo $step - 1; ?>" class="flex-1 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold py-2 px-4 rounded-lg transition text-center">
                                <i class="fas fa-arrow-left mr-2"></i>Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($step < 4 && $success): ?>
                            <a href="setup.php?step=<?php echo $step + 1; ?>" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded-lg transition text-center">
                                Next<i class="fas fa-arrow-right ml-2"></i>
                            </a>
                        <?php elseif ($step == 4 && $success): ?>
                            <a href="index.php" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition text-center">
                                <i class="fas fa-home mr-2"></i>Go to Home
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-8 text-slate-400 text-sm">
                <p>DealScout © 2024 | Setup & Configuration</p>
            </div>
        </div>
    </div>
</body>
</html>
