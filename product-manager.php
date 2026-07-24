<?php
require_once __DIR__ . '/includes/header.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if ($_SESSION['user_role'] !== 'store_owner') {
    redirect('index.php');
}

$db = new Database();

$db->query("SELECT id, name, status FROM stores WHERE owner_id = :uid AND status = 'approved' ORDER BY created_at DESC");
$db->bind(':uid', $_SESSION['user_id']);
$stores = $db->resultSet();

if (count($stores) === 0) {
    redirect('search.php'); 
}

$selected_store_id = isset($_GET['store']) ? (int)$_GET['store'] : $stores[0]->id;

$db->query("SELECT id FROM stores WHERE id = :sid AND owner_id = :uid");
$db->bind(':sid', $selected_store_id);
$db->bind(':uid', $_SESSION['user_id']);
if (!$db->single()) {
    $selected_store_id = $stores[0]->id;
}

$db->query("SELECT id, name FROM categories ORDER BY CASE WHEN LOWER(name) = 'others' THEN 1 ELSE 0 END, name");
$categories = $db->resultSet();

$db->query("SELECT p.id, p.name, p.brand, c.name as category_name, p.approval_status, p.created_at, 
                   p.rejection_reason, COUNT(l.id) as listing_count
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN listings l ON p.id = l.product_id
            WHERE p.created_by = :uid AND p.is_custom = TRUE
            GROUP BY p.id
            ORDER BY p.created_at DESC");
$db->bind(':uid', $_SESSION['user_id']);
$my_products = $db->resultSet();

$pending_count = 0;
$approved_count = 0;
foreach ($my_products as $prod) {
    if ($prod->approval_status === 'pending') $pending_count++;
    elseif ($prod->approval_status === 'approved') $approved_count++;
}

$csrf = generateCSRFToken();
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 py-12">
    <div class="max-w-6xl mx-auto px-4">
        
        <div class="mb-8">
            <h1 class="text-4xl font-black text-primary mb-2">My Products</h1>
            <p class="text-slate-600 text-lg">Create, manage, and monitor your custom products</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-600 text-sm font-semibold uppercase tracking-widest">Total Products</p>
                        <p class="text-4xl font-black text-primary mt-2"><?php echo count($my_products); ?></p>
                    </div>
                    <div class="w-16 h-16 bg-primary/10 rounded-xl flex items-center justify-center text-2xl text-primary">
                        <i class="fa-solid fa-box"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-600 text-sm font-semibold uppercase tracking-widest">Approved</p>
                        <p class="text-4xl font-black text-green-500 mt-2"><?php echo $approved_count; ?></p>
                    </div>
                    <div class="w-16 h-16 bg-green-100 rounded-xl flex items-center justify-center text-2xl text-green-500">
                        <i class="fa-solid fa-check-circle"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-600 text-sm font-semibold uppercase tracking-widest">Pending</p>
                        <p class="text-4xl font-black text-yellow-500 mt-2"><?php echo $pending_count; ?></p>
                    </div>
                    <div class="w-16 h-16 bg-yellow-100 rounded-xl flex items-center justify-center text-2xl text-yellow-500">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 sticky top-20">
                    <h2 class="text-2xl font-bold text-primary mb-6">Add New Product</h2>
                    
                    <form id="addProductForm" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Product Name *</label>
                            <input type="text" name="product_name" required maxlength="100" placeholder="e.g., Premium Pasta 500g"
                                   class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-secondary focus:border-transparent outline-none smooth-transition">
                            <p class="text-xs text-slate-500 mt-1">3-100 characters</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Brand</label>
                            <input type="text" name="brand" maxlength="50" placeholder="e.g., Your Brand"
                                   class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-secondary focus:border-transparent outline-none smooth-transition">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Category *</label>
                            <select name="category_name" required class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-secondary focus:border-transparent outline-none smooth-transition bg-white">
                                <option value="" disabled selected>Select a Category...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat->name); ?>"><?php echo htmlspecialchars($cat->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                            <textarea name="description" maxlength="500" rows="3" placeholder="Brief product description..."
                                      class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-secondary focus:border-transparent outline-none smooth-transition resize-none"></textarea>
                            <p class="text-xs text-slate-500 mt-1">Max 500 characters</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Product Image *</label>
                            <div id="imageDropZone" class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-secondary hover:bg-secondary/5 smooth-transition">
                                <input type="file" name="product_image" id="productImage" accept="image/jpeg,image/png,image/webp" required hidden>
                                <div id="imagePreviewArea" class="space-y-2">
                                    <i class="fa-solid fa-image text-3xl text-gray-400"></i>
                                    <p class="text-sm font-semibold text-gray-700">Click to upload or drag & drop</p>
                                    <p class="text-xs text-gray-500">PNG, JPG, WebP (Max 5MB)</p>
                                </div>
                            </div>
                            <div id="imageError" class="hidden mt-2 text-red-500 text-sm"></div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Price (per unit) *</label>
                            <div class="relative">
                                <span class="absolute left-3 top-3 text-gray-500 font-semibold">Rs</span>
                                <input type="number" name="price" required min="0.01" step="0.01" placeholder="0.00"
                                       class="w-full pl-8 pr-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-secondary focus:border-transparent outline-none smooth-transition">
                            </div>
                        </div>

                        <?php if (count($stores) > 1): ?>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Store *</label>
                            <select name="store_id" required
                                    class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-secondary focus:border-transparent outline-none smooth-transition appearance-none">
                                <?php foreach ($stores as $store): ?>
                                    <option value="<?php echo $store->id; ?>"><?php echo htmlspecialchars($store->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php else: ?>
                        <input type="hidden" name="store_id" value="<?php echo $stores[0]->id; ?>">
                        <?php endif; ?>

                        <button type="submit" class="w-full bg-secondary text-white font-bold py-3 px-6 rounded-lg smooth-transition shadow-lg hover:shadow-xl active:scale-95 mt-6">
                            <i class="fa-solid fa-plus mr-2"></i> Add Product
                        </button>

                        <p class="text-xs text-slate-500 mt-4">After submission, your product will be reviewed by our admin team and appear in search results once approved.</p>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    
                    <div class="px-8 py-6 border-b border-gray-100 bg-slate-50">
                        <h3 class="text-xl font-bold text-gray-900">Your Custom Products</h3>
                        <p class="text-sm text-gray-600 mt-1">Manage and monitor all your custom products</p>
                    </div>

                    <div class="divide-y divide-gray-100">
                        <?php if (count($my_products) === 0): ?>
                        <div class="px-8 py-12 text-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fa-solid fa-box text-2xl text-gray-400"></i>
                            </div>
                            <p class="text-gray-600 font-semibold">No custom products yet</p>
                            <p class="text-gray-500 text-sm mt-1">Create your first product using the form on the left</p>
                        </div>
                        <?php else: ?>
                            <?php foreach ($my_products as $product): ?>
                            <div class="px-8 py-6 hover:bg-gray-50 smooth-transition">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1">
                                        <div class="flex items-start gap-3">
                                            <div>
                                                <h4 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($product->name); ?></h4>
                                                <p class="text-sm text-gray-600 mt-1">
                                                    <span class="text-slate-500">Category:</span> <?php echo htmlspecialchars($product->category_name ?: 'N/A'); ?>
                                                </p>
                                                <?php if ($product->brand && $product->brand !== 'Custom'): ?>
                                                <p class="text-sm text-gray-600">
                                                    <span class="text-slate-500">Brand:</span> <?php echo htmlspecialchars($product->brand); ?>
                                                </p>
                                                <?php endif; ?>
                                                <p class="text-sm text-gray-600">
                                                    <span class="text-slate-500">Listings:</span> <?php echo $product->listing_count; ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-3">
                                        <?php if ($product->approval_status === 'approved'): ?>
                                        <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-green-100 text-green-700 text-xs font-bold rounded-full">
                                            <i class="fa-solid fa-check-circle"></i> Approved
                                        </span>
                                        <?php elseif ($product->approval_status === 'pending'): ?>
                                        <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-yellow-100 text-yellow-700 text-xs font-bold rounded-full">
                                            <i class="fa-solid fa-clock"></i> Pending
                                        </span>
                                        <?php elseif ($product->approval_status === 'rejected'): ?>
                                        <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-red-100 text-red-700 text-xs font-bold rounded-full">
                                            <i class="fa-solid fa-times-circle"></i> Rejected
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if ($product->approval_status === 'rejected' && !empty($product->rejection_reason)): ?>
                                <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                                    <i class="fa-solid fa-exclamation-circle mr-2"></i>
                                    <strong>Rejection Reason:</strong> <?php echo htmlspecialchars($product->rejection_reason); ?>
                                </div>
                                <?php endif; ?>

                                <p class="text-xs text-gray-500 mt-3">
                                    <i class="fa-solid fa-calendar-days mr-1"></i>
                                    Created: <?php echo date('M d, Y', strtotime($product->created_at)); ?>
                                </p>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-12 bg-blue-50 rounded-xl border border-secondary/20 p-8">
            <h3 class="text-lg font-bold text-primary mb-4">
                <i class="fa-solid fa-lightbulb mr-2"></i> Tips for Product Approval
            </h3>
            <ul class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
                <li class="flex gap-3">
                    <span class="text-secondary font-bold">•</span>
                    Use clear, descriptive product names (e.g., "Premium Basmati Rice 5kg" instead of "Rice")
                </li>
                <li class="flex gap-3">
                    <span class="text-secondary font-bold">•</span>
                    Upload high-quality product images (clear photos taken in good lighting)
                </li>
                <li class="flex gap-3">
                    <span class="text-secondary font-bold">•</span>
                    Include accurate brand and category information for better searchability
                </li>
                <li class="flex gap-3">
                    <span class="text-secondary font-bold">•</span>
                    Set competitive prices based on current market rates
                </li>
            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('addProductForm');
    const imageInput = document.getElementById('productImage');
    const dropZone = document.getElementById('imageDropZone');
    const imagePreviewArea = document.getElementById('imagePreviewArea');
    const imageError = document.getElementById('imageError');

    dropZone.addEventListener('click', () => imageInput.click());
    
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('border-secondary', 'bg-secondary/5');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('border-secondary', 'bg-secondary/5');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-secondary', 'bg-secondary/5');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            imageInput.files = files;
            handleImageSelect();
        }
    });

    imageInput.addEventListener('change', handleImageSelect);

    function handleImageSelect() {
        const file = imageInput.files[0];
        imageError.classList.add('hidden');
        imageError.textContent = '';

        if (!file) return;

        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            imageError.textContent = 'Invalid image format. Allowed: JPEG, PNG, WebP';
            imageError.classList.remove('hidden');
            imageInput.value = '';
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            imageError.textContent = 'Image size must not exceed 5MB';
            imageError.classList.remove('hidden');
            imageInput.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreviewArea.innerHTML = `
                <img src="${e.target.result}" class="w-full h-40 object-cover rounded-lg">
                <p class="text-xs text-gray-500 mt-2"><i class="fa-solid fa-check-circle text-green-500 mr-1"></i>Image selected</p>
            `;
        };
        reader.readAsDataURL(file);
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(form);

        try {
            const response = await fetch('<?php echo url('api/add-custom-product.php'); ?>', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.status === 'success') {
                alert('Product created successfully! It will appear in search results once admin approves it.');
                form.reset();
                imagePreviewArea.innerHTML = `
                    <i class="fa-solid fa-image text-3xl text-gray-400"></i>
                    <p class="text-sm font-semibold text-gray-700">Click to upload or drag & drop</p>
                    <p class="text-xs text-gray-500">PNG, JPG, WebP (Max 5MB)</p>
                `;
                setTimeout(() => window.location.reload(), 1500);
            } else {
                alert('Error: ' + (data.message || 'Failed to create product'));
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
