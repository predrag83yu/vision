<?php
include '../partials/header.php';
include '../partials/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Nevažeći ID proizvoda.";
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];

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

// Dohvatanje recepture ako je set
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obrada forme za izmenu
    $_POST['cena'] = str_replace('.', '', $_POST['cena']);
    $_POST['cena'] = str_replace(',', '.', $_POST['cena']);
    
    $_POST['cv'] = str_replace('.', '', $_POST['cv']);
    $_POST['cv'] = str_replace(',', '.', $_POST['cv']);

    $sifra = $conn->real_escape_string($_POST['sifra']);
    $naziv = $conn->real_escape_string($_POST['naziv']);
    $cena = (float)$_POST['cena'];
    $cv = (float)$_POST['cv'];
    $je_set = isset($_POST['je_set']) ? 1 : 0;

    $sql = "UPDATE proizvodi SET 
            sifra = ?, 
            naziv = ?, 
            cena = ?, 
            cv = ?, 
            je_set = ?
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssddii", $sifra, $naziv, $cena, $cv, $je_set, $id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Proizvod je uspešno ažuriran.";
        header("Location: detalji.php?id=$id");
        exit();
    } else {
        $_SESSION['error'] = "Greška pri ažuriranju: " . $stmt->error;
    }
}
?>

<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Izmeni proizvod</h1>
        <div class="flex space-x-2">
            <a href="detalji.php?id=<?= $id ?>" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">
                <i class="fas fa-arrow-left mr-2"></i> Nazad
            </a>
            <?php if ($proizvod['je_set']): ?>
            <a href="izmeni_recepturu.php?id=<?= $id ?>" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded">
    <i class="fas fa-list-ol mr-2"></i> Receptura
</a>
            <?php endif; ?>
        </div>
    </div>

    <form method="post" class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 gap-6">
            <div>
                <label for="sifra" class="block text-sm font-medium text-gray-700 mb-1">Šifra proizvoda *</label>
                <input type="text" id="sifra" name="sifra" required value="<?= htmlspecialchars($proizvod['sifra']) ?>" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            
            <div>
                <label for="naziv" class="block text-sm font-medium text-gray-700 mb-1">Naziv proizvoda *</label>
                <input type="text" id="naziv" name="naziv" required value="<?= htmlspecialchars($proizvod['naziv']) ?>" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="cena" class="block text-sm font-medium text-gray-700 mb-1">Cena (RSD) *</label>
                    <input type="text" id="cena" name="cena" required 
                           value="<?= number_format($proizvod['cena'], 2, ',', '.') ?>" 
                           pattern="[0-9]+([,][0-9]{1,2})?" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label for="cv" class="block text-sm font-medium text-gray-700 mb-1">Bodovi (CV) *</label>
                    <input type="text" id="cv" name="cv" required 
                           value="<?= number_format($proizvod['cv'], 3, ',', '.') ?>" 
                           pattern="[0-9]+([,][0-9]{1,3})?" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
            </div>
            
            <div class="flex items-center">
                <input type="checkbox" id="je_set" name="je_set" value="1" <?= $proizvod['je_set'] ? 'checked' : '' ?> 
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="je_set" class="ml-2 block text-sm text-gray-700">Proizvod je set</label>
            </div>
        </div>
        
        <div class="mt-6 flex justify-end space-x-3">
            <a href="detalji.php?id=<?= $id ?>" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">
                Otkaži
            </a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                Sačuvaj izmene
            </button>
        </div>
    </form>

    <?php if ($proizvod['je_set'] && !empty($receptura)): ?>
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-3">Receptura seta:</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Šifra</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Naziv</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Količina</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($receptura as $sastojak): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($sastojak['sifra']) ?></td>
                        <td class="px-6 py-4"><?= htmlspecialchars($sastojak['naziv']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= (int)$sastojak['kolicina'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Provera unosa za cenu
    document.getElementById('cena').addEventListener('blur', function() {
        let value = this.value.replace(/\./g, '').replace(',', '.');
        if(isNaN(value)) {
            this.value = '<?= number_format($proizvod['cena'], 2, ',', '.') ?>';
        }
    });
    
    // Provera unosa za CV
    document.getElementById('cv').addEventListener('blur', function() {
        let value = this.value.replace(/\./g, '').replace(',', '.');
        if(isNaN(value)) {
            this.value = '<?= number_format($proizvod['cv'], 3, ',', '.') ?>';
        }
    });
});
</script>

<?php 
$conn->close();
include '../partials/footer.php'; 
?>