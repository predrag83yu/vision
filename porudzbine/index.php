<?php 
include '../partials/header.php';
include '../partials/db.php';

$porudzbine = $conn->query("
    SELECT p.id, p.datum, k.ime, k.prezime, k.clanski_broj, p.ukupno_cv,
           (SELECT SUM(sp.cena_po_jedinici * sp.kolicina) 
            FROM stavke_porudzbine sp 
            WHERE sp.porudzbina_id = p.id) as ukupno_rsd
    FROM porudzbine p
    JOIN klijenti k ON p.klijent_id = k.id
    ORDER BY p.datum DESC
");
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Porudžbine</h1>
    <a href="dodaj.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
        <i class="fas fa-plus mr-2"></i>Nova porudžbina
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3">Datum</th>
                    <th class="px-6 py-3">Klijent</th>
                    <th class="px-6 py-3">Članski broj</th>
                    <th class="px-6 py-3">Ukupno CV</th>
                    <th class="px-6 py-3">Ukupno RSD</th>
                    <th class="px-6 py-3">Akcije</th>
                </tr>
            </thead>
            <tbody>
                <?php while($porudzbina = $porudzbine->fetch_assoc()): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4"><?= date('d.m.Y H:i', strtotime($porudzbina['datum'])) ?></td>
                    <td class="px-6 py-4"><?= htmlspecialchars($porudzbina['ime'] . ' ' . $porudzbina['prezime']) ?></td>
                    <td class="px-6 py-4"><?= htmlspecialchars($porudzbina['clanski_broj']) ?></td>
                    <td class="px-6 py-4"><?= number_format($porudzbina['ukupno_cv'], 3, ',', '.') ?></td>
                    <td class="px-6 py-4"><?= number_format($porudzbina['ukupno_rsd'], 2, ',', '.') ?> RSD</td>
                    <td class="px-6 py-4">
                        <a href="detalji.php?id=<?= $porudzbina['id'] ?>" class="text-blue-600 hover:text-blue-900">
                            <i class="fas fa-eye mr-1"></i> Detalji
                        </a>
                    </td>
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