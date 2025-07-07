<?php 
include '../partials/header.php';
include '../partials/db.php';

// Setovanje srpske lokalizacije
setlocale(LC_ALL, 'sr_RS.UTF-8', 'sr_RS@latin', 'sr_RS');

// Sortiranje
$sort = $_GET['sort'] ?? 'naziv';
$order = $_GET['order'] ?? 'asc';
$validSortColumns = ['sifra', 'naziv', 'cena', 'cv', 'je_set'];
$sort = in_array($sort, $validSortColumns) ? $sort : 'naziv';
$order = $order === 'asc' ? 'asc' : 'desc';

// Pretraga
$search = $_GET['search'] ?? '';
$where = '';
$params = [];
$types = '';

if (!empty($search)) {
    $where = "WHERE sifra LIKE ? OR naziv LIKE ?";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm];
    $types = 'ss';
}

// Formiranje SQL upita sa posebnim slučajem za sortiranje po šifri
$sql = "SELECT * FROM proizvodi $where ";
if ($sort === 'sifra') {
    $sql .= "ORDER BY CAST(sifra AS UNSIGNED) $order, sifra $order";
} else {
    $sql .= "ORDER BY $sort $order";
}

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

function formatBroj($broj, $decimale = 2) {
    return number_format($broj, $decimale, ',', '.');
}
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Proizvodi</h1>
    <a href="dodaj.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
        <i class="fas fa-plus mr-2"></i>Dodaj proizvod
    </a>
</div>

<form method="get" action="" class="mb-6">
    <div class="flex">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
               placeholder="Pretraži po šifri ili nazivu" 
               class="flex-grow px-4 py-2 border border-gray-300 rounded-l focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-r">
            <i class="fas fa-search mr-2"></i>Pretraži
        </button>
    </div>
    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
    <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>">
</form>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <a href="?sort=sifra&order=<?= $sort === 'sifra' && $order === 'asc' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>"
                           class="flex items-center">
                            Šifra 
                            <?php if ($sort === 'sifra'): ?>
                                <i class="fas fa-sort-<?= $order === 'asc' ? 'up' : 'down' ?> ml-1"></i>
                            <?php else: ?>
                                <i class="fas fa-sort ml-1 text-gray-400"></i>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <a href="?sort=naziv&order=<?= $sort === 'naziv' && $order === 'asc' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>"
                           class="flex items-center">
                            Naziv
                            <?php if ($sort === 'naziv'): ?>
                                <i class="fas fa-sort-<?= $order === 'asc' ? 'up' : 'down' ?> ml-1"></i>
                            <?php else: ?>
                                <i class="fas fa-sort ml-1 text-gray-400"></i>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <a href="?sort=cena&order=<?= $sort === 'cena' && $order === 'asc' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>"
                           class="flex items-center">
                            Cena
                            <?php if ($sort === 'cena'): ?>
                                <i class="fas fa-sort-<?= $order === 'asc' ? 'up' : 'down' ?> ml-1"></i>
                            <?php else: ?>
                                <i class="fas fa-sort ml-1 text-gray-400"></i>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <a href="?sort=cv&order=<?= $sort === 'cv' && $order === 'asc' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>"
                           class="flex items-center">
                            Bodovi (CV)
                            <?php if ($sort === 'cv'): ?>
                                <i class="fas fa-sort-<?= $order === 'asc' ? 'up' : 'down' ?> ml-1"></i>
                            <?php else: ?>
                                <i class="fas fa-sort ml-1 text-gray-400"></i>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <a href="?sort=je_set&order=<?= $sort === 'je_set' && $order === 'asc' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>"
                           class="flex items-center">
                            Set
                            <?php if ($sort === 'je_set'): ?>
                                <i class="fas fa-sort-<?= $order === 'asc' ? 'up' : 'down' ?> ml-1"></i>
                            <?php else: ?>
                                <i class="fas fa-sort ml-1 text-gray-400"></i>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Akcije</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($row['sifra']) ?></td>
                            <td class="px-6 py-4"><?= htmlspecialchars($row['naziv']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= formatBroj($row['cena']) ?> RSD</td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= formatBroj($row['cv'], 3) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($row['je_set']): ?>
                                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded">Da</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs font-medium rounded">Ne</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="detalji.php?id=<?= $row['id'] ?>" class="text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-eye mr-1"></i> Pogledaj
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            Nema pronađenih proizvoda. <?= !empty($search) ? 'Pokušajte sa drugim terminom pretrage.' : '' ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
$stmt->close();
$conn->close();
include '../partials/footer.php'; 
?>