<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DealScout | Nearby Price Comparison Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0f172a',     // Slate 900
                        secondary: '#10b981',   // Emerald 500
                        accent: '#3b82f6',      // Blue 500
                        neutral: '#64748b',     // Slate 500
                        bgLight: '#f8fafc',     // Slate 50
                        bgGray: '#f1f5f9',      // Slate 100
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                    },
                    boxShadow: {
                        'minimal': '0 4px 20px -2px rgba(0, 0, 0, 0.05)',
                        'floating': '0 20px 40px -10px rgba(16, 185, 129, 0.15)',
                        'glass': '0 8px 32px 0 rgba(0, 0, 0, 0.1)',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            -webkit-font-smoothing: antialiased; 
            --scroll-behavior: smooth;
        }
        
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes pulse-glow {
            0%, 100% { 
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            }
            50% {
                box-shadow: 0 0 0 10px rgba(16, 185, 129, 0);
            }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .animate-slideInDown { animation: slideInDown 0.5s ease-out; }
        .animate-slideInUp { animation: slideInUp 0.5s ease-out; }
        .animate-fadeIn { animation: fadeIn 0.5s ease-out; }
        .animate-pulse-glow { animation: pulse-glow 2s infinite; }
        .animate-float { animation: float 3s ease-in-out infinite; }
        
        .hero {
            background-image: linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(15, 23, 42, 0.85)), url('https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?auto=format&fit=crop&q=80&w=2070');
            background-size: cover;
            background-position: center;
        }
        
        .glassmorphism {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
        
        .smooth-transition { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        
        .btn-primary {
            background: #10b981;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(15, 23, 42, 0.4);
        }
        
        .btn-primary:active {
            transform: translateY(0px);
        }
        
        .card-hover {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px -10px rgba(16, 185, 129, 0.2);
        }
        
        input, select, textarea {
            transition: all 0.3s ease;
            border-color: #e2e8f0;
        }
        
        input:focus, select:focus, textarea:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        
        nav a:not(.no-underline-fx) {
            position: relative;
        }
        
        nav a:not(.no-underline-fx)::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: #10b981;
            transition: width 0.3s ease;
        }
        
        nav a:not(.no-underline-fx):hover::after {
            width: 100%;
        }
        
        .no-underline-fx, .no-underline-fx:hover {
            text-decoration: none !important;
        }
        
        .gradient-text {
            color: #10b981;
        }
        
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(16, 185, 129, 0.2);
            border-radius: 50%;
            border-top-color: #10b981;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .badge-pulse {
            animation: badge-pulse 2s infinite;
        }
        
        @keyframes badge-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
    </style>
</head>
<body class="bg-bgLight text-primary min-h-screen flex flex-col font-sans selection:bg-secondary selection:text-white">

<nav class="bg-white/95 backdrop-blur-xl border-b border-gray-100/50 sticky top-0 z-50 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20 relative">
            <div class="flex items-center flex-1">
                <a href="<?php echo url('index.php'); ?>" class="text-2xl font-extrabold tracking-tight text-primary flex items-center gap-2 group smooth-transition no-underline-fx">
                    <span class="w-10 h-10 rounded-xl bg-secondary text-white flex items-center justify-center text-lg shadow-md group-hover:shadow-lg group-hover:scale-110 smooth-transition">
                        <i class="fa-solid fa-layer-group"></i>
                    </span>
                    <span>Deal<span class="text-secondary">Scout</span></span>
                </a>
            </div>
            
            <div class="hidden sm:flex items-center justify-center space-x-1 absolute left-1/2 -translate-x-1/2">
                <a href="<?php echo url('index.php'); ?>" class="text-neutral hover:text-primary px-4 py-2 text-sm font-medium smooth-transition rounded-lg">Home</a>
                <a href="<?php echo url('search.php'); ?>" class="text-neutral hover:text-primary px-4 py-2 text-sm font-medium smooth-transition rounded-lg">Search</a>
                <a href="<?php echo url('index.php#how-it-works'); ?>" class="text-neutral hover:text-primary px-4 py-2 text-sm font-medium smooth-transition rounded-lg">How It Works</a>
            </div>
            
            <div class="hidden sm:flex items-center justify-end flex-1 space-x-1">
                <?php if(isLoggedIn()): ?>
                    <a href="<?php echo url('dashboard.php'); ?>" class="text-neutral hover:text-primary px-4 py-2 text-sm font-medium smooth-transition rounded-lg">Dashboard</a>
                    <a href="<?php echo url('api/logout.php'); ?>" class="bg-red-50 hover:bg-red-100 text-red-600 hover:shadow-lg px-5 py-2.5 rounded-lg text-sm font-semibold smooth-transition transform hover:scale-105 active:scale-95 shadow-md no-underline-fx border border-red-200/50">Logout</a>
                <?php else: ?>
                    <a href="<?php echo url('login.php'); ?>" class="bg-secondary hover:shadow-lg text-white px-5 py-2.5 rounded-lg text-sm font-semibold smooth-transition transform hover:scale-105 active:scale-95 shadow-md no-underline-fx">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<script>
    function updateUserLocation() {
        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                document.cookie = `user_lat=${lat}; path=/; max-age=86400; SameSite=Lax`;
                document.cookie = `user_lng=${lng}; path=/; max-age=86400; SameSite=Lax`;
                
                sessionStorage.setItem('user_lat', lat);
                sessionStorage.setItem('user_lng', lng);
                
                console.log("Location updated:", lat, lng);
            }, function(error) {
                console.warn("Location access denied or error:", error.message);
            });
        }
    }

    document.addEventListener('DOMContentLoaded', updateUserLocation);

    function getDirections(destLat, destLng) {
        let lat = sessionStorage.getItem('user_lat') || '27.7172';
        let lng = sessionStorage.getItem('user_lng') || '85.3240';
        
        const url = `https://www.google.com/maps/dir/?api=1&origin=${lat},${lng}&destination=${destLat},${destLng}`;
        window.open(url, '_blank');
    }
</script>

<main class="flex-grow">
