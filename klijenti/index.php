<?php 
include '../partials/header.php';
include '../partials/db.php';

// Sortiranje
$sort = $_GET['sort'] ?? 'prezime';
$order = $_GET['order'] ?? 'asc';
$validSortColumns = ['ime', 'prezime', 'email', 'telefon', 'clanski_broj', 'sponzor'];
$sort = in_array($sort, $validSortColumns) ? $sort : 'prezime';
$order = $order === 'asc' ? 'asc' : 'desc';

// Pretraga
$search = $_GET['search'] ?? '';
$where = '';
$params = [];
$types = '';

if (!empty($search)) {
    $where = "WHERE ime LIKE ? OR prezime LIKE ? OR clanski_broj LIKE ? OR sponzor LIKE ?";
    $searchTerm = "%$search%";
    $params = array_fill(0, 4, $searchTerm);
    $types = 'ssss';
}

// SQL upit sa sortiranjem
$sql = "SELECT * FROM klijenti $where ORDER BY $sort $order";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Klijenti</h1>
    <a href="dodaj.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
        <i class="fas fa-plus mr-2"></i>Dodaj klijenta
    </a>
</div>

<!-- Forma za pretragu -->
<form method="get" action="" class="mb-6">
    <div class="flex">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
               placeholder="Pretraži po imenu, prezimenu, članskom broju ili sponzoru" 
               class="flex-grow px-4 py-2 border border-gray-300 rounded-l focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-r">
            <i class="fas fa-search mr-2"></i>Pretraži
        </button>
    </div>
    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
    <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>">
</form>

<!-- Tabela sa klijentima -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <a href="?sort=ime&order=<?= $sort === 'ime' && $order === 'asc' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>">
                            Ime <?= $sort === 'ime' ? ($order === 'asc' ? '↑' : '↓') : '' ?>
                        </a>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <a href="?sort=prezime&order=<?= $sort === 'prezime' && $order === 'asc' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>">
                            Prezime <?= $sort === 'prezime' ? ($order === 'asc' ? '↑' : '↓') : '' ?>
                        </a>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <a href="?sort=email&order=<?= $sort === 'email' && $order === 'asc' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>">
                            Email <?= $sort === 'email' ? ($order === 'asc' ? '↑' : '↓') : '' ?>
                        </a>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <a href="?sort=telefon&order=<?= $sort === 'telefon' && $order === 'asc' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>">
                            Telefon <?= $sort === 'telefon' ? ($order === 'asc' ? '↑' : '↓') : '' ?>
                        </a>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <a href="?sort=clanski_broj&order=<?= $sort === 'clanski_broj' && $order === 'asc' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>">
                            Članski broj <?= $sort === 'clanski_broj' ? ($order === 'asc' ? '↑' : '↓') : '' ?>
                        </a>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <a href="?sort=sponzor&order=<?= $sort === 'sponzor' && $order === 'asc' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>">
                            Sponzor <?= $sort === 'sponzor' ? ($order === 'asc' ? '↑' : '↓') : '' ?>
                        </a>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Akcije</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($row['ime']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($row['prezime']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($row['email']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($row['telefon']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($row['clanski_broj']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($row['sponzor']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap space-x-2">
    <a href="detalji.php?id=<?= $row['id'] ?>" class="text-blue-600 hover:text-blue-900">
        <i class="fas fa-eye"></i> Pogledaj
    </a>
</td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            Nema pronađenih klijenata. <?= !empty($search) ? 'Pokušajte sa drugim terminom pretrage.' : '' ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Da li ste sigurni da želite da obrišete ovog klijenta?')) {
        fetch(`obrisi.php?id=${id}`, { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Došlo je do greške: ' + data.error);
                }
            });
    }
}
</script>

<?php 
$stmt->close();
$conn->close();
include '../partials/footer.php'; 
?>