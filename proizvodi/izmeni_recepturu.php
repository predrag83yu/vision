<?php
include '../partials/header.php';
include '../partials/db.php';

$set_id = (int)$_GET['id'];

// Dohvatanje osnovnih podataka o setu
$sql = "SELECT * FROM proizvodi WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $set_id);
$stmt->execute();
$result = $stmt->get_result();
$set = $result->fetch_assoc();

if (!$set || !$set['je_set']) {
    $_SESSION['error'] = "Nevažeći set proizvoda.";
    header("Location: index.php");
    exit();
}

// Obrada forme za dodavanje novog sastojka
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dodaj_sastojak'])) {
    $proizvod_id = (int)$_POST['proizvod_id'];
    $kolicina = (int)$_POST['kolicina'];
    
    // Provera da li sastojak već postoji u recepturi
    $check_sql = "SELECT * FROM recepti_setova WHERE set_id = ? AND proizvod_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $set_id, $proizvod_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $_SESSION['error'] = "Ovaj proizvod je već dodat u recepturu!";
    } else {
        // Dodavanje novog sastojka
        $insert_sql = "INSERT INTO recepti_setova (set_id, proizvod_id, kolicina) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iii", $set_id, $proizvod_id, $kolicina);
        
        if ($insert_stmt->execute()) {
            $_SESSION['success'] = "Sastojak je uspešno dodat u recepturu.";
        } else {
            $_SESSION['error'] = "Greška pri dodavanju sastojka: " . $conn->error;
        }
    }
    
    header("Location: izmeni_recepturu.php?id=$set_id");
    exit();
}

// Obrada brisanja sastojka
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['obrisi_sastojak'])) {
    $sastojak_id = (int)$_POST['sastojak_id'];
    
    $delete_sql = "DELETE FROM recepti_setova WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $sastojak_id);
    
    if ($delete_stmt->execute()) {
        $_SESSION['success'] = "Sastojak je uspešno uklonjen iz recepture.";
    } else {
        $_SESSION['error'] = "Greška pri brisanju sastojka: " . $conn->error;
    }
    
    header("Location: izmeni_recepturu.php?id=$set_id");
    exit();
}

// Dohvatanje trenutne recepture
$receptura_sql = "SELECT r.id, p.sifra, p.naziv, r.kolicina 
                 FROM recepti_setova r
                 JOIN proizvodi p ON r.proizvod_id = p.id
                 WHERE r.set_id = ?
                 ORDER BY p.naziv";
$receptura_stmt = $conn->prepare($receptura_sql);
$receptura_stmt->bind_param("i", $set_id);
$receptura_stmt->execute();
$receptura = $receptura_stmt->get_result();
?>

<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Izmena recepture za: <?= htmlspecialchars($set['naziv']) ?></h1>
        <a href="detalji.php?id=<?= $set_id ?>" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">
            <i class="fas fa-arrow-left mr-2"></i> Nazad
        </a>
    </div>

    <?php include '../partials/messages.php'; ?>

    <!-- Forma za dodavanje novog sastojka -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Dodaj novi sastojak</h2>
        <form method="post" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Proizvod</label>
                <select name="proizvod_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">Izaberi proizvod</option>
                    <?php
                    $proizvodi_sql = "SELECT id, sifra, naziv FROM proizvodi WHERE id != ? AND je_set = 0 ORDER BY naziv";
                    $proizvodi_stmt = $conn->prepare($proizvodi_sql);
                    $proizvodi_stmt->bind_param("i", $set_id);
                    $proizvodi_stmt->execute();
                    $proizvodi = $proizvodi_stmt->get_result();
                    
                    while ($proizvod = $proizvodi->fetch_assoc()): ?>
                        <option value="<?= $proizvod['id'] ?>">
                            <?= htmlspecialchars($proizvod['sifra']) ?> - <?= htmlspecialchars($proizvod['naziv']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Količina</label>
                <input type="number" name="kolicina" min="1" value="1" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            
            <div class="flex items-end">
                <button type="submit" name="dodaj_sastojak" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded h-[42px] w-full">
                    <i class="fas fa-plus mr-2"></i> Dodaj
                </button>
            </div>
        </form>
    </div>

    <!-- Tabela sa postojećim sastojcima -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <h2 class="text-xl font-semibold p-6">Trenutna receptura</h2>
        
        <?php if ($receptura->num_rows > 0): ?>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left">Šifra</th>
                        <th class="px-6 py-3 text-left">Naziv</th>
                        <th class="px-6 py-3 text-left">Količina</th>
                        <th class="px-6 py-3 text-left">Akcije</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($sastojak = $receptura->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4"><?= htmlspecialchars($sastojak['sifra']) ?></td>
                        <td class="px-6 py-4"><?= htmlspecialchars($sastojak['naziv']) ?></td>
                        <td class="px-6 py-4"><?= (int)$sastojak['kolicina'] ?></td>
                        <td class="px-6 py-4">
                            <form method="post" class="inline">
                                <input type="hidden" name="sastojak_id" value="<?= $sastojak['id'] ?>">
                                <button type="submit" name="obrisi_sastojak" 
                                        class="text-red-600 hover:text-red-800" 
                                        onclick="return confirm('Da li ste sigurni da želite da uklonite ovaj sastojak?')">
                                    <i class="fas fa-trash"></i> Ukloni
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="p-6 text-gray-500">Nema sastojaka u recepturi.</div>
        <?php endif; ?>
    </div>
</div>

<?php 
$conn->close();
include '../partials/footer.php';
?>