<?php

require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - DealScout</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 min-h-screen flex items-center justify-center px-4">
    <div class="max-w-md w-full text-center">
        <div class="mb-8">
            <div class="w-24 h-24 rounded-full bg-secondary text-white flex items-center justify-center text-5xl mx-auto shadow-lg">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
        </div>
        
        <h1 class="text-6xl font-black text-white mb-2">404</h1>
        <h2 class="text-2xl font-bold text-white mb-4">Page Not Found</h2>
        <p class="text-slate-300 mb-8">Sorry, the page you're looking for doesn't exist or has been moved.</p>
        
        <div class="flex flex-col gap-3">
            <a href="<?php echo url('/index.php'); ?>" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-lg transition">
                <i class="fas fa-home mr-2"></i>Go Home
            </a>
            <a href="javascript:history.back()" class="w-full bg-slate-700 hover:bg-slate-600 text-white font-bold py-3 px-4 rounded-lg transition">
                <i class="fas fa-arrow-left mr-2"></i>Go Back
            </a>
        </div>
        
        <div class="mt-12 text-slate-500 text-sm">
            <p>DealScout © 2024</p>
        </div>
    </div>
</body>
</html>
