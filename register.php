<?php
require_once __DIR__ . '/includes/header.php';

if (isLoggedIn()) {
    redirect('index.php');
}
?>

<div class="bg-white flex-grow flex items-center justify-center px-4 py-12 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-slate-50 via-white to-slate-50 pointer-events-none"></div>
    <div class="absolute top-0 right-0 w-full h-full pointer-events-none overflow-hidden">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-secondary/8 rounded-full filter blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-accent/8 rounded-full filter blur-3xl"></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-0 max-w-6xl w-full relative z-10 rounded-3xl overflow-hidden shadow-2xl border border-gray-100">
        <div class="hidden lg:flex flex-col justify-between bg-slate-900 p-12 relative overflow-hidden">
            <div class="absolute inset-0 pointer-events-none">
                <div class="absolute top-0 right-0 w-96 h-96 bg-white/5 rounded-full mix-blend-screen filter blur-3xl"></div>
                <div class="absolute bottom-0 left-0 w-96 h-96 bg-white/5 rounded-full mix-blend-screen filter blur-3xl"></div>
            </div>

            <div class="relative z-10">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 text-white text-sm font-semibold mb-6 border border-white/15 backdrop-blur-sm">
                    <i class="fa-solid fa-users text-secondary"></i> Active Community
                </div>
                <h2 class="text-4xl font-black text-white mb-6 leading-tight">Save Money, <span class="text-secondary">Compare Smart</span></h2>
                <p class="text-slate-300 text-lg leading-relaxed mb-12">Join our community and start finding the best deals from local stores around you. No shipping delays, no extra costs.</p>

                <div class="space-y-6">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-secondary flex-shrink-0 mt-1">
                            <i class="fa-solid fa-bolt"></i>
                        </div>
                        <div>
                            <h3 class="text-white font-bold mb-2">Real-time Prices</h3>
                            <p class="text-slate-400 text-sm">Updated prices from verified local stores instantly</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-secondary flex-shrink-0 mt-1">
                            <i class="fa-solid fa-map-location-dot"></i>
                        </div>
                        <div>
                            <h3 class="text-white font-bold mb-2">Near You</h3>
                            <p class="text-slate-400 text-sm">Find products at stores closest to your location</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-secondary flex-shrink-0 mt-1">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <div>
                            <h3 class="text-white font-bold mb-2">Save More</h3>
                            <p class="text-slate-400 text-sm">Compare prices and save up to 40% on purchases</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative z-10 pt-8 border-t border-white/10">
                <p class="text-slate-400 text-sm mb-3">Trusted by users worldwide</p>
                <div class="flex items-center gap-2">
                    <div class="flex -space-x-2">
                        <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-white text-xs font-bold border border-slate-700">A</div>
                        <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-white text-xs font-bold border border-slate-700">B</div>
                        <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-white text-xs font-bold border border-slate-700">C</div>
                    </div>
                    <span class="text-white font-semibold text-sm">+50K Users</span>
                </div>
            </div>
        </div>

        <div class="bg-white p-8 md:p-12 flex flex-col justify-center">
            <div class="mb-10">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-secondary/10 text-secondary text-xs font-bold mb-4 uppercase tracking-wider border border-secondary/20">
                    <i class="fa-solid fa-user-plus"></i> New Member
                </div>
                <h1 class="text-3xl md:text-4xl font-black text-primary mb-2">Create Account</h1>
                <p class="text-neutral text-base">Join DealScout and start saving today</p>
            </div>

            <div id="alertBox" class="hidden mb-6 p-4 rounded-xl text-sm border border-opacity-20 backdrop-blur-sm"></div>

            <form id="registerForm" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div>
                    <label for="name" class="block text-sm font-semibold text-primary mb-2">Full Name</label>
                    <input type="text" name="name" id="name" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50/50 focus:bg-white focus:border-secondary focus:ring-4 focus:ring-secondary/10 outline-none text-primary placeholder-gray-400 font-medium smooth-transition" placeholder="John Doe" required>
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold text-primary mb-2">Email Address</label>
                    <input type="email" name="email" id="email" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50/50 focus:bg-white focus:border-secondary focus:ring-4 focus:ring-secondary/10 outline-none text-primary placeholder-gray-400 font-medium smooth-transition" placeholder="you@example.com" required>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-sm font-semibold text-primary mb-2">Password</label>
                        <input type="password" name="password" id="password" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50/50 focus:bg-white focus:border-secondary focus:ring-4 focus:ring-secondary/10 outline-none text-primary placeholder-gray-400 font-medium smooth-transition" placeholder="••••••••" required minlength="6">
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-semibold text-primary mb-2">Confirm</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50/50 focus:bg-white focus:border-secondary focus:ring-4 focus:ring-secondary/10 outline-none text-primary placeholder-gray-400 font-medium smooth-transition" placeholder="••••••••" required minlength="6">
                    </div>
                </div>

                <div>
                    <label for="location" class="block text-sm font-semibold text-primary mb-2">Location / Address</label>
                    <input type="text" name="location" id="location" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50/50 focus:bg-white focus:border-secondary focus:ring-4 focus:ring-secondary/10 outline-none text-primary placeholder-gray-400 font-medium smooth-transition" placeholder="Your City or Address" required>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-primary mb-3">Account Type</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="relative flex border-2 border-gray-200 rounded-xl p-4 cursor-pointer hover:border-secondary hover:bg-green-50/50 smooth-transition group">
                            <input type="radio" name="role" value="user" checked class="sr-only peer" onclick="document.getElementById('role_consumer_label').classList.add('text-secondary')">
                            <div class="w-5 h-5 rounded-full border-2 border-gray-300 peer-checked:border-secondary peer-checked:bg-secondary flex items-center justify-center mt-0.5 flex-shrink-0">
                                <i class="fa-solid fa-check text-white text-xs opacity-0 peer-checked:opacity-100"></i>
                            </div>
                            <div class="ml-3 text-sm flex flex-col">
                                <span id="role_consumer_label" class="font-bold text-primary text-secondary"><i class="fa-solid fa-magnifying-glass mr-2"></i> Find Deals</span>
                                <span class="text-neutral text-xs mt-0.5">Compare prices</span>
                            </div>
                        </label>
                        <label class="relative flex border-2 border-gray-200 rounded-xl p-4 cursor-pointer hover:border-secondary hover:bg-green-50/50 smooth-transition group">
                            <input type="radio" name="role" value="store_owner" class="sr-only peer">
                            <div class="w-5 h-5 rounded-full border-2 border-gray-300 peer-checked:border-secondary peer-checked:bg-secondary flex items-center justify-center mt-0.5 flex-shrink-0">
                                <i class="fa-solid fa-check text-white text-xs opacity-0 peer-checked:opacity-100"></i>
                            </div>
                            <div class="ml-3 text-sm flex flex-col">
                                <span class="font-bold text-primary"><i class="fa-solid fa-shop mr-2"></i> Add Store</span>
                                <span class="text-neutral text-xs mt-0.5">List products</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" id="submitBtn" class="w-full bg-secondary hover:shadow-lg text-white font-bold py-3.5 px-4 rounded-xl smooth-transition flex items-center justify-center shadow-md active:scale-95 text-base">
                        Create Account
                    </button>
                </div>
                
                <p class="text-xs text-center text-neutral leading-relaxed">By signing up you agree to our <a href="#" class="text-primary hover:text-secondary font-semibold smooth-transition">Terms</a> and <a href="#" class="text-primary hover:text-secondary font-semibold smooth-transition">Privacy</a></p>
            </form>

            <div class="text-center mt-8 pt-6 border-t border-gray-100">
                <p class="text-sm text-neutral">
                    Already a member? 
                    <a href="<?php echo url('login.php'); ?>" class="font-bold text-secondary hover:text-primary smooth-transition ml-1">Login</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const registerForm = document.getElementById('registerForm');
    const alertBox = document.getElementById('alertBox');
    const submitBtn = document.getElementById('submitBtn');

    registerForm.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const pwd = document.getElementById('password').value;
        const confirm = document.getElementById('confirm_password').value;

        if (pwd !== confirm) {
            alertBox.className = 'mb-4 p-4 rounded-md text-sm bg-red-50 text-red-800 border border-red-200';
            alertBox.innerHTML = '<i class="fa-solid fa-triangle-exclamation mr-2"></i> Passwords do not match!';
            alertBox.classList.remove('hidden');
            return;
        }
        
        alertBox.classList.add('hidden');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Processing...';

        const formData = new FormData(registerForm);

       fetch('<?php echo url("api/register.php"); ?>', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                alertBox.className = 'mb-4 p-4 rounded-md text-sm bg-green-50 text-green-800 border border-green-200';
                alertBox.innerHTML = '<i class="fa-solid fa-check-circle mr-2"></i> ' + data.message;
                alertBox.classList.remove('hidden');
                
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1500);
            } else {
                throw new Error(data.message);
            }
        })
        .catch(err => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Create Account';
            alertBox.className = 'mb-4 p-4 rounded-md text-sm bg-red-50 text-red-800 border border-red-200';
            alertBox.innerHTML = '<i class="fa-solid fa-triangle-exclamation mr-2"></i> ' + (err.message || 'An error occurred during registration.');
            alertBox.classList.remove('hidden');
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
