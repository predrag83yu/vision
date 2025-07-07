<?php
include '../partials/header.php';
include '../partials/db.php';

$id = (int)$_GET['id'];

// Dohvatanje osnovnih podataka
$sql = "SELECT * FROM proizvodi WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$proizvod = $result->fetch_assoc();

// Dohvatanje recepture
$sql = "SELECT p.sifra, p.naziv, r.kolicina 
        FROM recepti_setova r
        JOIN proizvodi p ON r.proizvod_id = p.id
        WHERE r.set_id = ?
        ORDER BY p.naziv";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$receptura = $stmt->get_result();
?>

<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Receptura za: <?= htmlspecialchars($proizvod['naziv']) ?></h1>
        <a href="izmeni.php?id=<?= $id ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
            <i class="fas fa-edit mr-2"></i> Izmeni recepturu
        </a>
    </div>
    
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left">Šifra</th>
                    <th class="px-6 py-3 text-left">Naziv</th>
                    <th class="px-6 py-3 text-left">Količina</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($sastojak = $receptura->fetch_assoc()): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4"><?= htmlspecialchars($sastojak['sifra']) ?></td>
                    <td class="px-6 py-4"><?= htmlspecialchars($sastojak['naziv']) ?></td>
                    <td class="px-6 py-4"><?= (int)$sastojak['kolicina'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
$conn->close();
include '../partials/footer.php';
?>