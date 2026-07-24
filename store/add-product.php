<?php
require_once __DIR__ . '/../includes/header.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'store_owner') {
    redirect('index.php');
}

$db = new Database();

$db->query("SELECT id, status, latitude, longitude FROM stores WHERE owner_id = :owner_id LIMIT 1");
$db->bind(':owner_id', $_SESSION['user_id']);
$store = $db->single();

if (!$store || $store->status !== 'approved') {
    redirect('store/dashboard.php');
}

$db->query("SELECT * FROM categories ORDER BY CASE WHEN LOWER(name) = 'others' THEN 1 ELSE 0 END, name ASC");
$categories = $db->resultSet();
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<div class="bg-gray-50 flex-grow py-8 border-t border-gray-200">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 bg-white p-8 rounded-xl shadow border border-gray-100">
        
        <div class="mb-6 border-b border-gray-200 pb-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-900"><i class="fa-solid fa-plus-circle text-primary"></i> Add New Product</h1>
            <a href="dashboard.php" class="text-gray-500 hover:text-primary font-medium text-sm">&larr; Back to Dashboard</a>
        </div>

        <div id="alertBox" class="hidden mb-4 p-4 rounded-md text-sm"></div>

        <form id="addProductForm" class="space-y-6" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="store_id" value="<?php echo $store->id; ?>">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                <input type="text" name="product_name" id="productName" required placeholder="e.g., Organic Milk, Fresh Tomatoes..." class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5 font-semibold">
                <p class="text-xs text-gray-500 mt-1"><i class="fa-solid fa-info-circle"></i> Enter the name of the product you want to list</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Product Category *</label>
                <select name="category_name" id="categoryName" required class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5 font-semibold">
                    <option value="" disabled selected>Select a Category...</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat->name); ?>"><?php echo htmlspecialchars($cat->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Brand Name (Optional)</label>
                <input type="text" name="brand" id="brand" placeholder="e.g., Nestlé, Coca-Cola..." class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Product Image *</label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center bg-gray-50 hover:bg-gray-100 transition cursor-pointer" id="imageDropZone">
                    <input type="file" name="product_image" id="productImage" accept="image/*" required hidden>
                    <div id="imagePreviewContainer" class="hidden">
                        <img id="imagePreview" src="" alt="Preview" class="max-h-40 mx-auto mb-3 rounded-lg border border-gray-200">
                        <p class="text-sm text-gray-500">Click to change image</p>
                    </div>
                    <div id="imageUploadPrompt">
                        <i class="fa-solid fa-cloud-arrow-up text-3xl text-gray-400 mb-2"></i>
                        <p class="text-gray-600 font-medium">Drag and drop your image here</p>
                        <p class="text-xs text-gray-500 mt-1">or click to browse (JPG, PNG, GIF - max 5MB)</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-gray-100 pt-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Price (NPR) *</label>
                    <div class="relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">Rs.</span>
                        </div>
                        <input type="number" step="0.01" name="price" id="price" required min="0.01" class="focus:ring-primary focus:border-primary block w-full pl-10 pr-12 border-gray-300 rounded-md py-3 bg-gray-50 font-bold border" placeholder="0.00">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Stock Available *</label>
                    <div class="relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-boxes-stacked text-gray-400"></i>
                        </div>
                        <input type="number" name="stock" id="stock" required min="0" value="1" class="focus:ring-primary focus:border-primary block w-full pl-10 border-gray-300 rounded-md py-3 bg-gray-50 font-bold border" placeholder="e.g. 50">
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">Store Location</label>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-blue-800"><i class="fa-solid fa-info-circle mr-2"></i> This product will be available at your store location</p>
                </div>
                <input type="hidden" name="latitude" value="<?php echo $store->latitude; ?>">
                <input type="hidden" name="longitude" value="<?php echo $store->longitude; ?>">
                <div id="storeMap" class="h-48 w-full bg-gray-100 rounded-lg shadow-sm border border-gray-300 z-0"></div>
            </div>

            <div class="pt-4 border-t border-gray-100">
                <button type="submit" id="submitBtn" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-bold text-white bg-primary hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors uppercase">
                    <i class="fa-solid fa-check-circle mr-2"></i> Submit for Admin Approval
                </button>
                <p class="text-xs text-gray-500 text-center mt-2">Your product will be reviewed and approved by our admin team before appearing in the store</p>
            </div>
        </form>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const storeLat = <?php echo $store->latitude; ?>;
    const storeLng = <?php echo $store->longitude; ?>;
    
    setTimeout(() => {
        const map = L.map('storeMap').setView([storeLat, storeLng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        
        L.marker([storeLat, storeLng], {
            draggable: false
        }).addTo(map).bindPopup('<b>Your Store Location</b>');
    }, 200);

    const imageDropZone = document.getElementById('imageDropZone');
    const productImage = document.getElementById('productImage');
    const imagePreview = document.getElementById('imagePreview');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');
    const imageUploadPrompt = document.getElementById('imageUploadPrompt');

    imageDropZone.addEventListener('click', () => productImage.click());

    imageDropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        imageDropZone.classList.add('bg-blue-50', 'border-blue-400');
    });

    imageDropZone.addEventListener('dragleave', () => {
        imageDropZone.classList.remove('bg-blue-50', 'border-blue-400');
    });

    imageDropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        imageDropZone.classList.remove('bg-blue-50', 'border-blue-400');
        const files = e.dataTransfer.files;
        if(files.length > 0) {
            productImage.files = files;
            previewImage();
        }
    });

    productImage.addEventListener('change', previewImage);

    function previewImage() {
        const file = productImage.files[0];
        if(file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                imagePreview.src = e.target.result;
                imagePreviewContainer.classList.remove('hidden');
                imageUploadPrompt.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        }
    }

    document.getElementById('addProductForm')?.addEventListener('submit', (e) => {
        e.preventDefault();
        const btn = document.getElementById('submitBtn');
        const alert = document.getElementById('alertBox');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Submitting...';
        
       fetch('<?php echo url("api/add-product.php"); ?>', {
        method: 'POST',
        body: new FormData(e.target)
    })
        .then(res => res.json())
        .then(data => {
            alert.classList.remove('hidden');
            if(data.status === 'success') {
                alert.className = 'mb-4 p-4 rounded-md text-sm bg-green-50 text-green-800';
                alert.innerHTML = '<i class="fa-solid fa-check-circle mr-2"></i> ' + data.message;
                e.target.reset();
                imagePreviewContainer.classList.add('hidden');
                imageUploadPrompt.classList.remove('hidden');
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 1500);
            } else {
                alert.className = 'mb-4 p-4 rounded-md text-sm bg-red-50 text-red-800';
                alert.innerHTML = '<i class="fa-solid fa-triangle-exclamation mr-2"></i> ' + data.message;
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check-circle mr-2"></i> Submit for Admin Approval';
            }
        })
        .catch(err => {
            console.error(err);
            alert.className = 'mb-4 p-4 rounded-md text-sm bg-red-50 text-red-800';
            alert.innerHTML = '<i class="fa-solid fa-triangle-exclamation mr-2"></i> Error submitting product. Please try again.';
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check-circle mr-2"></i> Submit for Admin Approval';
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
