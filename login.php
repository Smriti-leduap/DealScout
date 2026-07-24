<?php
require_once __DIR__ . '/includes/header.php';

if (isLoggedIn()) {
    redirect('index.php');
}
?>

<div class="bg-gradient-to-br from-slate-50 via-blue-50 to-slate-50 flex-grow py-16 flex items-center justify-center px-4 relative overflow-hidden">
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-0 right-0 w-96 h-96 bg-secondary/5 rounded-full filter blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-accent/5 rounded-full filter blur-3xl"></div>
    </div>

    <div class="max-w-md w-full bg-white rounded-3xl shadow-lg border border-gray-100/60 p-10 relative z-10">
        
        <div class="text-center mb-10">
            <div class="w-16 h-16 rounded-2xl bg-secondary text-white flex items-center justify-center text-2xl mx-auto mb-4 shadow-md">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <h2 class="text-3xl font-black text-primary">Welcome Back</h2>
            <p class="text-sm text-neutral mt-3">Access your saved stores and searches</p>
        </div>

        <div id="alertBox" class="hidden mb-6 p-4 rounded-xl text-sm border border-opacity-20 backdrop-blur-sm"></div>

        <form id="loginForm" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_GET['redirect'] ?? ''); ?>">
            
            <div>
                <label for="email" class="block text-sm font-semibold text-primary mb-2">Email Address</label>
                <input type="email" name="email" id="email" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50/50 focus:bg-white focus:border-secondary focus:ring-4 focus:ring-secondary/10 outline-none text-primary placeholder-gray-400 font-medium smooth-transition" placeholder="you@example.com" required>
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-primary mb-2">Password</label>
                <input type="password" name="password" id="password" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50/50 focus:bg-white focus:border-secondary focus:ring-4 focus:ring-secondary/10 outline-none text-primary placeholder-gray-400 font-medium smooth-transition" placeholder="••••••••" required>
            </div>

            <div class="flex items-center justify-between pt-2">
                <div class="flex items-center">
                    <input id="remember_me" name="remember_me" type="checkbox" class="w-5 h-5 rounded-lg border-2 border-gray-200 text-secondary focus:ring-secondary/50 focus:ring-4 cursor-pointer smooth-transition accent-secondary">
                    <label for="remember_me" class="ml-3 text-sm font-medium text-neutral cursor-pointer"> Remember me </label>
                </div>
                <div class="text-sm">
                    <a href="#" class="font-semibold text-secondary hover:text-primary smooth-transition"> Forgot password? </a>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" id="submitBtn" class="w-full bg-secondary hover:shadow-lg text-white font-bold py-3.5 px-4 rounded-xl smooth-transition flex items-center justify-center shadow-md active:scale-95">
                    Sign in
                </button>
            </div>
        </form>

        <div class="text-center mt-8 pt-6 border-t border-gray-100">
            <p class="text-sm text-neutral">
                Don't have an account? 
                <a href="<?php echo url('register.php'); ?>" class="font-bold text-secondary hover:text-primary smooth-transition ml-1">Signup</a>
            </p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const alertBox = document.getElementById('alertBox');
    const submitBtn = document.getElementById('submitBtn');

    loginForm.addEventListener('submit', (e) => {
        e.preventDefault();
        
        alertBox.classList.add('hidden');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Authenticating...';

        const formData = new FormData(loginForm);

       fetch('<?php echo url("api/login.php"); ?>', {
            method: 'POST',
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
                }, 1000);
            } else {
                throw new Error(data.message);
            }
        })
        .catch(err => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Sign in';
            alertBox.className = 'mb-4 p-4 rounded-md text-sm bg-red-50 text-red-800 border border-red-200';
            alertBox.innerHTML = '<i class="fa-solid fa-triangle-exclamation mr-2"></i> ' + (err.message || 'An error occurred during login.');
            alertBox.classList.remove('hidden');
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
