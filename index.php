<?php include 'partials/header.php'; ?>

<h1 class="text-3xl font-bold mb-6">Dobrodošli u Vision</h1>
<p class="mb-8 text-gray-700">Sistem za upravljanje lojalnošću klijenata. Izaberite modul sa kojim želite da radite:</p>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <!-- Kartica za Klijente -->
    <a href="klijenti/index.php" class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition duration-300">
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div class="bg-blue-100 p-3 rounded-full mr-4">
                    <i class="fas fa-users text-blue-600 text-2xl"></i>
                </div>
                <h2 class="text-xl font-semibold">Klijenti</h2>
            </div>
            <p class="text-gray-600">Upravljanje klijentima i članskim kartama.</p>
        </div>
    </a>

    <!-- Kartica za Proizvode -->
    <a href="proizvodi/index.php" class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition duration-300">
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div class="bg-green-100 p-3 rounded-full mr-4">
                    <i class="fas fa-boxes text-green-600 text-2xl"></i>
                </div>
                <h2 class="text-xl font-semibold">Proizvodi</h2>
            </div>
            <p class="text-gray-600">Upravljanje proizvodima i uslugama.</p>
        </div>
    </a>

    <!-- Kartica za Porudžbine -->
    <a href="porudzbine/index.php" class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition duration-300">
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div class="bg-yellow-100 p-3 rounded-full mr-4">
                    <i class="fas fa-shopping-cart text-yellow-600 text-2xl"></i>
                </div>
                <h2 class="text-xl font-semibold">Porudžbine</h2>
            </div>
            <p class="text-gray-600">Pregled i upravljanje porudžbinama.</p>
        </div>
    </a>

    <!-- Kartica za Popuste -->
    <a href="popusti/index.php" class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition duration-300">
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div class="bg-purple-100 p-3 rounded-full mr-4">
                    <i class="fas fa-tags text-purple-600 text-2xl"></i>
                </div>
                <h2 class="text-xl font-semibold">Popusti i pokloni</h2>
            </div>
            <p class="text-gray-600">Upravljanje popustima i poklon programima.</p>
        </div>
    </a>

    <!-- Kartica za Statistiku -->
    <a href="statistika/index.php" class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition duration-300">
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div class="bg-red-100 p-3 rounded-full mr-4">
                    <i class="fas fa-chart-line text-red-600 text-2xl"></i>
                </div>
                <h2 class="text-xl font-semibold">Statistika</h2>
            </div>
            <p class="text-gray-600">Analiza i izveštaji o poslovanju.</p>
        </div>
    </a>
</div>

<?php include 'partials/footer.php'; ?>