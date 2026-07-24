<?php
require_once __DIR__ . '/includes/header.php';

$db = new Database();
$db->query("SELECT * FROM categories WHERE parent_id IS NULL");
$categories = $db->resultSet();

$userLat = isset($_COOKIE['user_lat']) ? (float)$_COOKIE['user_lat'] : 27.7172;
$userLng = isset($_COOKIE['user_lng']) ? (float)$_COOKIE['user_lng'] : 85.3240;

$db = new Database();
$db->query("SELECT product_id, store_id, price, distance, last_updated, product_name, store_name, image_url
            FROM (
                SELECT l.id as listing_id, l.product_id, l.store_id, l.price, l.last_updated,
                       p.name as product_name, p.image_url, s.name as store_name,
                       (6371 * acos(cos(radians(:lat)) * cos(radians(s.latitude)) * cos(radians(s.longitude) - radians(:lng)) + sin(radians(:lat)) * sin(radians(s.latitude)))) AS distance
                FROM listings l
                JOIN products p ON l.product_id = p.id
                JOIN stores s ON l.store_id = s.id
                WHERE l.status = 'approved' AND s.status = 'approved'
            ) t1
            WHERE distance < 20
              AND t1.listing_id = (
                  SELECT l3.id FROM listings l3
                  JOIN products p3 ON l3.product_id = p3.id
                  JOIN stores s3 ON l3.store_id = s3.id
                  WHERE LOWER(p3.name) = LOWER(t1.product_name)
                    AND l3.status = 'approved'
                    AND s3.status = 'approved'
                  ORDER BY l3.price ASC, l3.last_updated DESC, l3.id ASC
                  LIMIT 1
              )
            ORDER BY last_updated DESC
            LIMIT 4");
$db->bind(':lat', $userLat);
$db->bind(':lng', $userLng);
$trendingDeals = $db->resultSet();
?>

<section class="relative w-full bg-white flex flex-col lg:flex-row items-stretch min-h-[600px] xl:min-h-[700px] border-b border-gray-100">
    
    <div class="w-full lg:w-1/2 xl:w-1/2 flex items-center justify-center p-8 md:p-16 lg:p-24 z-10 bg-white">
        <div class="max-w-2xl mx-auto lg:mx-0 w-full animate-slideInUp">
            <h1 class="text-6xl md:text-7xl lg:text-[80px] font-black text-primary leading-[1.05] tracking-tight mb-8">
                Find better<br/>deals, <br/>
                <span class="text-secondary">faster<span class="text-[#FEC450]">.</span></span>
            </h1>

            <p class="text-lg md:text-xl text-slate-600 mb-12 max-w-lg leading-relaxed font-medium">
                We help you stop overpaying for everyday products. Compare real-time prices from nearby stores, discover hidden deals, and save money instantly.
            </p>

            <div>
                <a href="<?php echo url('search.php'); ?>" class="bg-secondary hover:shadow-lg text-white px-5 py-2.5 rounded-lg text-sm font-semibold smooth-transition transform hover:scale-105 active:scale-95 shadow-md no-underline-fx inline-block">
                    Start Searching
                </a>
            </div>
        </div>
    </div>
    
    <div class="w-full lg:w-1/2 xl:w-1/2 bg-white flex items-center justify-center p-2 md:p-4 lg:p-6 relative min-h-[400px] lg:min-h-full overflow-hidden">
        <img src="<?php echo url('program.svg'); ?>" alt="Shopping Deals Illustration" class="max-w-[95%] max-h-[550px] lg:max-h-[680px] w-auto h-auto object-contain z-10 animate-fadeIn">
        <div class="absolute top-1/4 right-1/4 w-80 h-80 bg-secondary/8 rounded-full filter blur-3xl"></div>
        <div class="absolute bottom-1/4 left-1/4 w-80 h-80 bg-secondary/8 rounded-full filter blur-3xl"></div>
    </div>
</section>

<section class="py-12 bg-white border-b border-gray-100 relative z-20 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center divide-y md:divide-y-0 md:divide-x divide-gray-100">
            <div class="py-4">
                <p class="text-4xl font-black text-primary mb-1">0+</p>
                <p class="text-neutral font-bold uppercase tracking-widest text-xs">Verified Stores</p>
            </div>
            <div class="py-4">
                <p class="text-4xl font-black text-secondary mb-1">0K+</p>
                <p class="text-neutral font-bold uppercase tracking-widest text-xs">Products Listed</p>
            </div>
            <div class="py-4">
                <p class="text-4xl font-black text-secondary mb-1">0K+</p>
                <p class="text-neutral font-bold uppercase tracking-widest text-xs">Saved Yearly</p>
            </div>
        </div>
    </div>
</section>

<section class="py-24 bg-white border-t border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col items-center text-center mb-16 relative">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-red-100 text-red-600 text-xs font-bold mb-4 uppercase tracking-wider border border-red-200">
                <i class="fa-solid fa-fire"></i> Hot Right Now
            </div>
            <h2 class="text-4xl md:text-5xl font-black text-primary tracking-tight">Trending Local Deals</h2>
            <div class="w-full flex flex-col md:flex-row justify-center items-center relative mt-4">
                <p class="text-neutral text-lg">Freshly approved prices from stores near you</p>
                <div class="md:absolute md:right-0 mt-4 md:mt-0">
                    <a href="<?php echo url('search.php'); ?>" class="border-2 border-secondary text-secondary hover:bg-secondary hover:text-white font-bold py-2.5 px-5 rounded-lg text-sm smooth-transition shadow-sm no-underline-fx inline-block">
                        Explore all deals
                    </a>
                </div>
            </div>
        </div>

        <div id="trendingDealsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <?php if(count($trendingDeals) > 0): ?>
                <?php foreach($trendingDeals as $deal): ?>
                <a href="<?php echo url('product.php?id=' . $deal->product_id); ?>" class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 card-hover group flex flex-col h-full relative overflow-hidden block">
                    <div class="hidden"></div>
                    <div class="w-full h-48 bg-gray-50 rounded-xl mb-5 flex items-center justify-center p-6 relative z-10 overflow-hidden">
                        <span class="absolute top-3 left-3 z-20 bg-[#FEC450] text-[#0f172a] text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-md shadow-sm">
                            <i class="fa-solid fa-fire mr-1"></i> Hot Deal
                        </span>
                        <?php 
                            $dealImg = $deal->image_url ?? 'https://via.placeholder.com/150';
                            $fullDealImg = (strpos($dealImg, 'http') === 0 || strpos($dealImg, '/Smriti/') === 0) ? $dealImg : url($dealImg);
                        ?>
                        <img src="<?php echo htmlspecialchars($fullDealImg); ?>" alt="<?php echo htmlspecialchars($deal->product_name); ?>" class="max-h-full max-w-full object-contain mix-blend-multiply group-hover:scale-110 smooth-transition">
                    </div>
                    <div class="flex-grow flex flex-col relative z-10">
                        <h3 class="font-bold text-lg text-primary line-clamp-2 mb-2 group-hover:text-secondary smooth-transition"><?php echo htmlspecialchars($deal->product_name); ?></h3>
                        <p class="text-sm text-neutral flex items-center gap-2 mb-5 font-medium"><i class="fa-solid fa-store w-4 text-center text-gray-400"></i> <?php echo htmlspecialchars($deal->store_name); ?></p>
                        
                        <div class="mt-auto border-t border-gray-100 pt-5 flex items-end justify-between">
                            <div>
                                <p class="text-xs text-neutral font-semibold mb-1 uppercase tracking-wider">Starting at</p>
                                <p class="text-2xl font-black text-secondary">Rs. <?php echo number_format($deal->price); ?></p>
                            </div>
                            <span class="text-xs font-bold text-secondary flex items-center gap-1.5 bg-secondary/10 px-3 py-1.5 rounded-full"><i class="fa-solid fa-location-dot"></i> <?php 
                                $dist = (float)$deal->distance;
                                echo ($dist < 0.1) ? '< 0.1' : number_format($dist, 1);
                            ?> km</span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-1 md:col-span-2 lg:col-span-4 bg-gray-50 rounded-2xl border border-dashed border-gray-300 p-12 text-center">
                    <i class="fa-solid fa-store-slash text-4xl text-gray-300 mb-4 block"></i>
                    <h3 class="text-xl font-bold text-gray-700 mb-1">No deals nearby</h3>
                    <p class="text-gray-500">Be the first store to list your products in this area!</p>
                </div>
            <?php endif; ?>
        </div>
        

    </div>
</section>

<section class="py-24 bg-gradient-to-b from-gray-50 to-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col items-center text-center mb-16 relative">
            <h2 class="text-4xl md:text-5xl font-black text-primary tracking-tight">Browse by Category</h2>
            <div class="w-full flex flex-col md:flex-row justify-center items-center relative mt-4">
                <p class="text-neutral text-lg">Start your search from our most popular selections</p>
                <div class="md:absolute md:right-0 mt-4 md:mt-0">
                    <a href="<?php echo url('search.php'); ?>" class="border-2 border-secondary text-secondary hover:bg-secondary hover:text-white font-bold py-2.5 px-5 rounded-lg text-sm smooth-transition shadow-sm no-underline-fx inline-block">
                        View all categories
                    </a>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <?php foreach($categories as $category): ?>
            <a href="<?php echo url('search.php?category=' . $category->id); ?>" class="group bg-white rounded-2xl p-8 card-hover border border-gray-100 flex flex-col items-start relative overflow-hidden">
                <div class="w-16 h-16 bg-secondary rounded-xl flex items-center justify-center text-3xl text-primary mb-6 group-hover:bg-secondary group-hover:text-white smooth-transition">
                    <?php 
                        $icon = 'fa-tag'; 
                        if(stripos($category->name, 'electronic') !== false) $icon = 'fa-laptop';
                        if(stripos($category->name, 'grocer') !== false) $icon = 'fa-basket-shopping';
                        if(stripos($category->name, 'fashion') !== false) $icon = 'fa-shirt';
                    ?>
                    <i class="fa-solid <?php echo $icon; ?>"></i>
                </div>
                <h3 class="font-bold text-lg text-primary mb-1 group-hover:text-secondary smooth-transition"><?php echo htmlspecialchars($category->name); ?></h3>
                <span class="text-sm text-neutral font-medium opacity-0 group-hover:opacity-100 smooth-transition translate-y-1 group-hover:translate-y-0 inline-flex items-center gap-1">Explore <i class="fa-solid fa-angle-right text-xs"></i></span>
                <div class="absolute -bottom-4 -right-4 w-24 h-24 bg-gray-50 rounded-full group-hover:scale-150 smooth-transition -z-10"></div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="how-it-works" class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-20">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-100 text-accent text-xs font-bold mb-4 uppercase tracking-wider border border-blue-200">
                <i class="fa-solid fa-lightbulb"></i> Easy Process
            </div>
            <h2 class="text-4xl md:text-5xl font-black text-primary tracking-tight mb-4">Smarter Shopping Workflow</h2>
            <p class="text-neutral text-lg max-w-2xl mx-auto leading-relaxed">Skip the hassle of store-hopping. Find what you need, at the right price, right around the corner.</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-12 relative">
            <div class="hidden md:block absolute top-12 left-[15%] right-[15%] h-0.5 bg-slate-200 z-0"></div>

            <div class="flex flex-col items-center text-center relative z-10">
                <div class="w-24 h-24 bg-white border-2 border-secondary/20 rounded-2xl flex items-center justify-center text-4xl mb-8 shadow-md card-hover relative">
                    <i class="fa-solid fa-magnifying-glass text-secondary"></i>
                    <div class="absolute -top-4 -right-4 w-10 h-10 bg-slate-800 text-white rounded-full flex items-center justify-center text-lg font-bold shadow-md border-2 border-white">1</div>
                </div>
                <h3 class="text-2xl font-bold text-primary mb-3">Discover</h3>
                <p class="text-neutral leading-relaxed text-base">Type what you need. We'll show you matching products across all verified local vendors instantly.</p>
            </div>
            
            <div class="flex flex-col items-center text-center relative z-10">
                <div class="w-24 h-24 bg-white border-2 border-secondary/20 rounded-2xl flex items-center justify-center text-4xl mb-8 shadow-md card-hover relative">
                    <i class="fa-solid fa-tag text-secondary"></i>
                    <div class="absolute -top-4 -right-4 w-10 h-10 bg-slate-800 text-white rounded-full flex items-center justify-center text-lg font-bold shadow-md border-2 border-white">2</div>
                </div>
                <h3 class="text-2xl font-bold text-primary mb-3">Compare</h3>
                <p class="text-neutral leading-relaxed text-base">Evaluate prices, verify stock levels, and spot the "Best Deal" badge for unbeatable savings.</p>
            </div>

            <div class="flex flex-col items-center text-center relative z-10">
                <div class="w-24 h-24 bg-white border-2 border-secondary/20 rounded-2xl flex items-center justify-center text-4xl mb-8 shadow-md card-hover relative">
                    <i class="fa-solid fa-location-arrow text-secondary"></i>
                    <div class="absolute -top-4 -right-4 w-10 h-10 bg-slate-800 text-white rounded-full flex items-center justify-center text-lg font-bold shadow-md border-2 border-white">3</div>
                </div>
                <h3 class="text-2xl font-bold text-primary mb-3">Navigate</h3>
                <p class="text-neutral leading-relaxed text-base">Get accurate distances and seamless Google Maps directions directly to the store's front door.</p>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const categorySelect = document.getElementById('indexCategorySelect');
    const trendingContainer = document.getElementById('trendingDealsContainer');

    categorySelect.addEventListener('change', (e) => {
        const catId = e.target.value;
        const url = new URL('<?php echo url("api/search.php"); ?>', window.location.origin);
        if (catId) {
            url.searchParams.append('category', catId);
        }
        
        trendingContainer.innerHTML = '<div class="col-span-full py-10 text-center"><i class="fa-solid fa-spinner fa-spin text-3xl text-primary mb-2"></i><p>Loading...</p></div>';
        
        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' && data.count > 0) {
                    let html = '';
                    const deals = data.data.slice(0, 4); // Only show top 4
                    deals.forEach(deal => {
                        const productUrl = '<?php echo url("product.php?id="); ?>' + deal.product_id;
                        html += `
                        <a href="${productUrl}" class="bg-white rounded-2xl shadow-minimal border border-gray-100 p-5 hover:shadow-floating hover:-translate-y-1 transition-all duration-300 group flex flex-col h-full relative overflow-hidden block">
                            <div class="hidden"></div>
                            <div class="w-full h-40 bg-bgGray rounded-xl mb-4 flex items-center justify-center p-4 relative z-10">
                                <img src="${deal.image_url || 'https://via.placeholder.com/150'}" alt="${deal.product_name}" class="max-h-full max-w-full object-contain mix-blend-multiply group-hover:scale-110 transition-transform duration-500">
                            </div>
                            <div class="flex-grow flex flex-col relative z-10">
                                <h3 class="font-bold text-lg text-primary line-clamp-2 mb-1 group-hover:text-secondary transition-colors">${deal.product_name}</h3>
                                <p class="text-sm text-neutral flex items-center gap-1.5 mb-4"><i class="fa-solid fa-store w-4 text-center"></i> ${deal.store_name}</p>
                                
                                <div class="mt-auto border-t border-gray-100 pt-4 flex items-end justify-between">
                                    <div>
                                        <p class="text-xs text-neutral font-medium mb-0.5">Current Price</p>
                                        <p class="text-xl font-black text-primary">Rs. ${parseFloat(deal.price).toLocaleString()}</p>
                                    </div>
                                    <span class="text-xs font-bold text-secondary flex items-center gap-1 bg-secondary/10 px-2.5 py-1.5 rounded-full"><i class="fa-solid fa-location-dot"></i> ${parseFloat(deal.distance).toFixed(1)} km</span>
                                </div>
                            </div>
                        </a>
                        `;
                    });
                    trendingContainer.innerHTML = html;
                } else {
                    trendingContainer.innerHTML = `
                        <div class="col-span-1 md:col-span-2 lg:col-span-4 bg-gray-50 rounded-2xl border border-dashed border-gray-300 p-12 text-center">
                            <i class="fa-solid fa-store-slash text-4xl text-gray-300 mb-4 block"></i>
                            <h3 class="text-xl font-bold text-gray-700 mb-1">No deals found</h3>
                            <p class="text-gray-500">There are no approved deals in this category right now.</p>
                        </div>
                    `;
                }
            })
            .catch(err => {
                console.error(err);
                trendingContainer.innerHTML = '<div class="col-span-full py-10 text-center text-red-500">Failed to load deals.</div>';
            });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
