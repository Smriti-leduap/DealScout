<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    redirect('/admin/login.php');
}

$db = new Database();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = sanitize($_POST['name'] ?? '');
    $brand = sanitize($_POST['brand'] ?? '');
    $category_id = $_POST['category_id'] ?? '';
    $description = sanitize($_POST['description'] ?? '');
    
    if (empty($name) || empty($category_id)) {
        $error = 'Name and category are required';
    } else {
        $db->query("INSERT INTO products (name, brand, category_id, description) 
                   VALUES (:name, :brand, :category_id, :description)");
        $db->bind(':name', $name);
        $db->bind(':brand', $brand);
        $db->bind(':category_id', $category_id);
        $db->bind(':description', $description);
        $db->execute();
        
        $message = 'Product added successfully!';
    }
}

$db->query("SELECT p.*, c.name as category_name FROM products p 
           LEFT JOIN categories c ON p.category_id = c.id 
           ORDER BY p.created_at DESC");
$products = $db->resultSet();

$db->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name");
$categories = $db->resultSet();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - DealScout Admin</title>
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
                <h1 class="text-xl font-bold text-slate-900">Manage Products</h1>
            </div>
            <a href="<?php echo url('/api/logout.php'); ?>" class="text-red-600 hover:text-red-800 font-medium">
                <i class="fas fa-sign-out-alt mr-2"></i>Logout
            </a>
        </div>
    </nav>
    
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-bold text-slate-900 mb-6">
                <i class="fas fa-plus-circle text-emerald-600 mr-2"></i>Add New Product
            </h2>
            
            <?php if ($message): ?>
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">
                    <i class="fas fa-check-circle mr-2"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Product Name *</label>
                    <input type="text" name="name" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none" placeholder="e.g., iPhone 13">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Brand</label>
                    <input type="text" name="brand" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none" placeholder="e.g., Apple">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Category *</label>
                    <select name="category_id" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat->id; ?>"><?php echo htmlspecialchars($cat->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Description</label>
                    <input type="text" name="description" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none" placeholder="Product description">
                </div>
                
                <div class="md:col-span-2">
                    <button type="submit" name="add_product" value="1" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded-lg transition">
                        <i class="fas fa-plus mr-2"></i>Add Product
                    </button>
                </div>
            </form>
        </div>
        
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-slate-100 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Product Name</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Brand</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Category</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Added</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php foreach ($products as $product): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-semibold text-slate-900"><?php echo htmlspecialchars($product->name); ?></td>
                            <td class="px-6 py-4 text-slate-600"><?php echo htmlspecialchars($product->brand ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 text-slate-600"><?php echo htmlspecialchars($product->category_name ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 text-sm text-slate-600"><?php echo date('M d, Y', strtotime($product->created_at)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
