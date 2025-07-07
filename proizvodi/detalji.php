<?php
include '../partials/header.php';
include '../partials/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Nevažeći ID proizvoda.";
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];

// Dohvatanje osnovnih podataka o proizvodu
$sql = "SELECT * FROM proizvodi WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$proizvod = $result->fetch_assoc();

if (!$proizvod) {
    $_SESSION['error'] = "Proizvod nije pronađen.";
    header("Location: index.php");
    exit();
}

// Dohvatanje recepture samo ako je proizvod set
$receptura = [];
if ($proizvod['je_set']) {
    $sql = "SELECT p.id, p.sifra, p.naziv, r.kolicina 
            FROM recepti_setova r
            JOIN proizvodi p ON r.proizvod_id = p.id
            WHERE r.set_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $receptura = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<div class="max-w-4xl mx-auto">
    <!-- Zaglavlje i navigacija -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Detalji proizvoda</h1>
        <div class="flex space-x-2">
            <a href="index.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">
                <i class="fas fa-arrow-left mr-2"></i> Nazad
            </a>
            <?php if ($proizvod['je_set']): ?>
                <a href="izmeni_recepturu.php?id=<?= $id ?>" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded">
    <i class="fas fa-list-ol mr-2"></i> Receptura
</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Osnovne informacije o proizvodu -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Šifra proizvoda</label>
                <div class="w-full px-3 py-2 border border-gray-200 rounded-md bg-gray-50">
                    <?= htmlspecialchars($proizvod['sifra']) ?>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Naziv proizvoda</label>
                <div class="w-full px-3 py-2 border border-gray-200 rounded-md bg-gray-50">
                    <?= htmlspecialchars($proizvod['naziv']) ?>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cena (RSD)</label>
                <div class="w-full px-3 py-2 border border-gray-200 rounded-md bg-gray-50">
                    <?= number_format($proizvod['cena'], 2, ',', '.') ?> RSD
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bodovi (CV)</label>
                <div class="w-full px-3 py-2 border border-gray-200 rounded-md bg-gray-50">
                    <?= number_format($proizvod['cv'], 3, ',', '.') ?>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Set</label>
                <div class="w-full px-3 py-2 border border-gray-200 rounded-md bg-gray-50">
                    <?= $proizvod['je_set'] ? 'Da' : 'Ne' ?>
                </div>
            </div>
        </div>

        <!-- Prikaz recepture samo ako je proizvod set i ima sastojke -->
        <?php if ($proizvod['je_set'] && !empty($receptura)): ?>
        <div class="mt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-3">Sastojci seta:</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Šifra</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Naziv</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Količina</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Akcije</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($receptura as $sastojak): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($sastojak['sifra']) ?></td>
                            <td class="px-6 py-4"><?= htmlspecialchars($sastojak['naziv']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= (int)$sastojak['kolicina'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="detalji.php?id=<?= $sastojak['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Akcije -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-center space-x-4">
            <a href="izmeni.php?id=<?= $proizvod['id'] ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                <i class="fas fa-edit mr-2"></i> Izmeni
            </a>
            <button onclick="confirmDelete(<?= $proizvod['id'] ?>)" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
                <i class="fas fa-trash mr-2"></i> Obriši
            </button>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Da li ste sigurni da želite da obrišete ovaj proizvod?')) {
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