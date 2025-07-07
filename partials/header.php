<?php
session_start();

// Provera za poruke o uspehu/grešci
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;

// Brišemo poruke nakon što ih prikažemo
unset($_SESSION['success']);
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vision - Sistem za lojalnost</title>
<script>
    tailwind.config = {
        theme: {
            extend: {},
        },
        corePlugins: {
            preflight: false,
        }
    }
</script>
<script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
        }
        body {
            display: flex;
            flex-direction: column;
        }
        .content {
            flex: 1 0 auto;
        }
        footer {
            flex-shrink: 0;
        }
    </style>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen">
    <nav class="bg-blue-600 text-white shadow-lg">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div class="flex space-x-4">
                    <a href="/vision/index.php" class="flex items-center space-x-2">
                        <!-- VAŠ LOGO - zamenite putanju i stilove po potrebi -->
                        <img src="/vision/assets/images/logo.png" alt="Vision Logo" class="h-10 object-contain">
                        
                        <!-- Opciono: Tekstualni naziv ako želite pored logoa -->
                        <!-- <span class="text-xl font-bold hidden md:block">Vision</span> -->
                    </a>
                </div>
                <div class="hidden md:flex items-center space-x-6">
                    <a href="/vision/klijenti/index.php" class="hover:bg-blue-700 px-3 py-2 rounded transition duration-300">
                        <i class="fas fa-users mr-2"></i>Klijenti
                    </a>
                    <a href="/vision/proizvodi/index.php" class="hover:bg-blue-700 px-3 py-2 rounded transition duration-300">
                        <i class="fas fa-boxes mr-2"></i>Proizvodi
                    </a>
                    <a href="/vision/porudzbine/index.php" class="hover:bg-blue-700 px-3 py-2 rounded transition duration-300">
                        <i class="fas fa-shopping-cart mr-2"></i>Porudžbine
                    </a>
                    <a href="/vision/popusti/index.php" class="hover:bg-blue-700 px-3 py-2 rounded transition duration-300">
                        <i class="fas fa-tags mr-2"></i>Popusti
                    </a>
                    <a href="/vision/statistika/index.php" class="hover:bg-blue-700 px-3 py-2 rounded transition duration-300">
                        <i class="fas fa-chart-line mr-2"></i>Statistika
                    </a>
                </div>
                <div class="md:hidden">
                    <button class="mobile-menu-button">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile menu -->
    <div class="mobile-menu hidden md:hidden bg-blue-700">
        <a href="/vision/klijenti/index.php" class="block px-4 py-2 text-white hover:bg-blue-600"><i class="fas fa-users mr-2"></i>Klijenti</a>
        <a href="/vision/proizvodi/index.php" class="block px-4 py-2 text-white hover:bg-blue-600"><i class="fas fa-boxes mr-2"></i>Proizvodi</a>
        <a href="/vision/porudzbine/index.php" class="block px-4 py-2 text-white hover:bg-blue-600"><i class="fas fa-shopping-cart mr-2"></i>Porudžbine</a>
        <a href="/vision/popusti/index.php" class="block px-4 py-2 text-white hover:bg-blue-600"><i class="fas fa-tags mr-2"></i>Popusti</a>
        <a href="/vision/statistika/index.php" class="block px-4 py-2 text-white hover:bg-blue-600"><i class="fas fa-chart-line mr-2"></i>Statistika</a>
    </div>

    <div class="container mx-auto px-4 py-6 flex-grow">
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $success; ?></span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6 text-green-500" role="button" onclick="this.parentElement.parentElement.style.display='none';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                </span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error; ?></span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6 text-red-500" role="button" onclick="this.parentElement.parentElement.style.display='none';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                </span>
            </div>
        <?php endif; ?>