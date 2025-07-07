<?php
include '../partials/header.php';
include '../partials/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Nevažeći ID klijenta.";
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];

// Dohvatanje podataka o klijentu
$sql = "SELECT * FROM klijenti WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Klijent nije pronađen.";
    header("Location: index.php");
    exit();
}

$klijent = $result->fetch_assoc();
$stmt->close();

// Dohvatanje podataka o sponzoru
$sponzor_ime_prezime = 'VISION (root sponzor)';
if ($klijent['sponzor'] !== 'VISION') {
    $sql = "SELECT ime, prezime FROM klijenti WHERE clanski_broj = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $klijent['sponzor']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $sponzor = $result->fetch_assoc();
        $sponzor_ime_prezime = $sponzor['ime'] . ' ' . $sponzor['prezime'];
    }
    $stmt->close();
}
?>

<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Detalji klijenta</h1>
        <a href="index.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">
            <i class="fas fa-arrow-left mr-2"></i> Nazad
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="mb-6 bg-blue-50 p-4 rounded-lg">
                <label class="block text-sm font-medium text-blue-800 mb-1">Članski broj</label>
                <div class="w-full px-3 py-2 border border-blue-200 rounded-md bg-blue-100 text-blue-800 font-bold">
                    <?= htmlspecialchars($klijent['clanski_broj']) ?>
                </div>
            </div>

            <div class="mb-6 bg-gray-50 p-4 rounded-lg">
                <label class="block text-sm font-medium text-gray-700 mb-1">Sponzor</label>
                <div class="w-full px-3 py-2 border border-gray-200 rounded-md bg-gray-100">
                    <?= htmlspecialchars($sponzor_ime_prezime) ?>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ime</label>
                <div class="w-full px-3 py-2 border border-gray-200 rounded-md bg-gray-50">
                    <?= htmlspecialchars($klijent['ime']) ?>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Prezime</label>
                <div class="w-full px-3 py-2 border border-gray-200 rounded-md bg-gray-50">
                    <?= htmlspecialchars($klijent['prezime']) ?>
                </div>
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Adresa</label>
                <div class="w-full px-3 py-2 border border-gray-200 rounded-md bg-gray-50">
                    <?= htmlspecialchars($klijent['adresa']) ?>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <div class="w-full px-3 py-2 border border-gray-200 rounded-md bg-gray-50">
                    <?= htmlspecialchars($klijent['email']) ?>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                <div class="w-full px-3 py-2 border border-gray-200 rounded-md bg-gray-50">
                    <?= htmlspecialchars($klijent['telefon']) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-center space-x-4">
            <a href="izmeni.php?id=<?= $klijent['id'] ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                <i class="fas fa-edit mr-2"></i> Izmeni
            </a>
            <a href="/vision/porudzbine/dodaj.php?klijent_id=<?= $klijent['id'] ?>" 
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                <i class="fas fa-shopping-cart mr-2"></i> Nova porudžbina
            </a>
            <button onclick="confirmDelete(<?= $klijent['id'] ?>)" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
                <i class="fas fa-trash mr-2"></i> Obriši
            </button>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Da li ste sigurni da želite da obrišete ovog klijenta?')) {
        fetch(`obrisi.php?id=${id}`, { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'index.php';
                } else {
                    alert('Došlo je do greške: ' + data.error);
                }
            });
    }
}
</script>

<?php 
$conn->close();
include '../partials/footer.php'; 
?>