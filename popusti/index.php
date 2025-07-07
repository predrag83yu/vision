<?php
require '../partials/header.php';
require '../partials/db.php';

$sql = "SELECT id, naziv, opis, uslovi FROM popusti_pokloni WHERE aktivan = 1 ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<div class="content">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Popusti i pokloni</h1>
        <a href="dodaj.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i> Dodaj novi
        </a>
    </div>

    <?php include '../partials/messages.php'; ?>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Naziv</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uslov</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Akcije</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): 
                            $uslov = json_decode($row['uslovi'], true);
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?= $row['id'] ?></td>
                            <td class="px-6 py-4">
                                <div class="font-medium"><?= htmlspecialchars($row['naziv']) ?></div>
                                <div class="text-gray-500"><?= htmlspecialchars($row['opis']) ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <?php switch($uslov['tip']):
                                    case 'poklon_na_cv': ?>
                                        <div class="font-medium">Poklon na <?= $uslov['cv_kolicina'] ?> CV</div>
                                        <div class="text-gray-500">
                                            <?= $uslov['vrsta'] === 'jednokratno' ? 'Jednokratno' : 'Za svakih '.$uslov['cv_kolicina'].' CV' ?>
                                        </div>
                                        <div class="text-gray-500">
                                            Proizvodi: <?= implode(', ', $uslov['poklon_proizvodi']) ?>
                                        </div>
                                        <?php break; ?>
                                    
                                    <?php case 'kolicinski_popust': ?>
                                        <div class="font-medium">Količinski popust</div>
                                        <div class="text-gray-500">
                                            Na <?= $uslov['min_cv'] ?> CV: <?= $uslov['procenat_popusta'] ?>% popusta
                                        </div>
                                        <?php break; ?>
                                    
                                    <?php case 'lojalnost': ?>
                                        <div class="font-medium">Lojalnost</div>
                                        <div class="text-gray-500">
                                            Povraćaj: <?= $uslov['procenat_povracaja'] ?>% od CV
                                        </div>
                                        <?php break; ?>
                                    
                                    <?php case 'buster': ?>
                                        <div class="font-medium">Buster</div>
                                        <div class="text-gray-500">
                                            Na <?= $uslov['cv_kolicina'] ?> CV: <?= $uslov['procenat_povracaja'] ?>% povraćaja
                                        </div>
                                        <div class="text-gray-500">
                                            Proizvodi: <?= implode(', ', $uslov['poklon_proizvodi']) ?>
                                        </div>
                                        <?php break; ?>
                                <?php endswitch; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="izmeni.php?id=<?= $row['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="obrisi.php?id=<?= $row['id'] ?>" class="text-red-600 hover:text-red-900" 
                                   onclick="return confirm('Da li ste sigurni?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">Nema dostupnih popusta</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$conn->close();
require '../partials/footer.php';
?>