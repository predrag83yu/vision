<?php
require '../partials/header.php';
require '../partials/db.php';

// Učitavanje proizvoda za izbor
$proizvodi = [];
$proizvodi_result = $conn->query("SELECT id, naziv FROM proizvodi");
if ($proizvodi_result) {
    while ($pro = $proizvodi_result->fetch_assoc()) {
        $proizvodi[$pro['id']] = $pro['naziv'];
    }
}

// Ako je izmena, učitaj postojeći popust
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT * FROM popusti_pokloni WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $popust = $result->fetch_assoc();
    
    if ($popust) {
        $uslov = json_decode($popust['uslovi'], true);
        $popust_data = json_decode($popust['popust'], true);
    }
}
?>

<div class="content">
    <h1 class="text-2xl font-bold mb-6"><?= isset($popust) ? 'Izmeni' : 'Dodaj novi' ?> popust/poklon</h1>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="sacuvaj.php" method="post">
            <?php if (isset($popust)): ?>
                <input type="hidden" name="id" value="<?= $popust['id'] ?>">
            <?php endif; ?>
            
            <div class="grid grid-cols-1 gap-6">
                <!-- Osnovne informacije -->
                <div>
                    <label class="block text-gray-700 mb-2">Naziv*</label>
                    <input type="text" name="naziv" required 
                           value="<?= $popust['naziv'] ?? '' ?>" 
                           class="w-full px-3 py-2 border rounded">
                </div>

                <div>
                    <label class="block text-gray-700 mb-2">Opis</label>
                    <textarea name="opis" class="w-full px-3 py-2 border rounded" rows="3"><?= $popust['opis'] ?? '' ?></textarea>
                </div>

                <!-- Tip uslova -->
                <div>
                    <label class="block text-gray-700 mb-2">Tip uslova*</label>
                    <select name="tip_uslova" id="tipUslova" required class="w-full px-3 py-2 border rounded">
                        <option value="">-- Izaberite tip uslova --</option>
                        <option value="poklon_na_cv" <?= ($uslov['tip'] ?? '') === 'poklon_na_cv' ? 'selected' : '' ?>>Poklon na n CV</option>
                        <option value="kolicinski_popust" <?= ($uslov['tip'] ?? '') === 'kolicinski_popust' ? 'selected' : '' ?>>Količinski popust</option>
                        <option value="lojalnost" <?= ($uslov['tip'] ?? '') === 'lojalnost' ? 'selected' : '' ?>>Lojalnost</option>
                        <option value="buster" <?= ($uslov['tip'] ?? '') === 'buster' ? 'selected' : '' ?>>Buster</option>
                    </select>
                </div>

                <!-- Dinamički deo za uslove -->
                <div id="uslovContainer">
                    <?php if (isset($uslov)): ?>
                        <?php if ($uslov['tip'] === 'poklon_na_cv'): ?>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-gray-700 mb-2">Vrsta poklona*</label>
                                    <select name="vrsta_poklona" required class="w-full px-3 py-2 border rounded">
                                        <option value="jednokratno" <?= $uslov['vrsta'] === 'jednokratno' ? 'selected' : '' ?>>Jednokratno</option>
                                        <option value="za_svakih" <?= $uslov['vrsta'] === 'za_svakih' ? 'selected' : '' ?>>Za svakih n CV</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">Broj CV poena*</label>
                                    <input type="number" name="cv_kolicina" required 
                                           value="<?= $uslov['cv_kolicina'] ?? '' ?>" 
                                           class="w-full px-3 py-2 border rounded" min="1">
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">Poklon proizvod/i*</label>
                                    <select name="poklon_proizvodi[]" multiple required 
                                            class="w-full px-3 py-2 border rounded">
                                        <?php foreach($proizvodi as $id => $naziv): ?>
                                            <option value="<?= $id ?>" <?= in_array($id, $uslov['poklon_proizvodi'] ?? []) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($naziv) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="text-sm text-gray-500 mt-1">Držite Ctrl za odabir više proizvoda</p>
                                </div>
                            </div>
                        <?php elseif ($uslov['tip'] === 'kolicinski_popust'): ?>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-gray-700 mb-2">Minimalni zbir CV*</label>
                                    <input type="number" name="min_cv" required 
                                           value="<?= $uslov['min_cv'] ?? '' ?>" 
                                           class="w-full px-3 py-2 border rounded" min="1">
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">Procenat popusta*</label>
                                    <div class="flex items-center">
                                        <input type="number" name="procenat_popusta" required 
                                               value="<?= $uslov['procenat_popusta'] ?? '' ?>" 
                                               class="w-20 px-3 py-2 border rounded" min="1" max="100">
                                        <span class="ml-2">%</span>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($uslov['tip'] === 'lojalnost'): ?>
                            <div>
                                <label class="block text-gray-700 mb-2">Procenat povraćaja CV*</label>
                                <div class="flex items-center">
                                    <input type="number" name="procenat_povracaja" required 
                                           value="<?= $uslov['procenat_povracaja'] ?? '' ?>" 
                                           class="w-20 px-3 py-2 border rounded" min="1" max="100">
                                    <span class="ml-2">%</span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">Koliko % od kupljenih CV klijent dobija nazad</p>
                            </div>
                        <?php elseif ($uslov['tip'] === 'buster'): ?>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-gray-700 mb-2">Broj CV poena*</label>
                                    <input type="number" name="cv_kolicina" required 
                                           value="<?= $uslov['cv_kolicina'] ?? '' ?>" 
                                           class="w-full px-3 py-2 border rounded" min="1">
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">Procenat povraćaja*</label>
                                    <div class="flex items-center">
                                        <input type="number" name="procenat_povracaja" required 
                                               value="<?= $uslov['procenat_povracaja'] ?? '' ?>" 
                                               class="w-20 px-3 py-2 border rounded" min="1" max="100">
                                        <span class="ml-2">%</span>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-1">Koliko % od CV poena može da iskoristi za poklon proizvode</p>
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">Poklon proizvod/i*</label>
                                    <select name="poklon_proizvodi[]" multiple required 
                                            class="w-full px-3 py-2 border rounded">
                                        <?php foreach($proizvodi as $id => $naziv): ?>
                                            <option value="<?= $id ?>" <?= in_array($id, $uslov['poklon_proizvodi'] ?? []) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($naziv) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="text-sm text-gray-500 mt-1">Držite Ctrl za odabir više proizvoda</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <a href="index.php" class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400">
                    <i class="fas fa-times mr-2"></i> Otkaži
                </a>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i> Sačuvaj
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tipUslova = document.getElementById('tipUslova');
    const uslovContainer = document.getElementById('uslovContainer');

    tipUslova.addEventListener('change', function() {
        const tip = this.value;
        let html = '';

        switch(tip) {
            case 'poklon_na_cv':
                html = `
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Vrsta poklona*</label>
                            <select name="vrsta_poklona" required class="w-full px-3 py-2 border rounded">
                                <option value="jednokratno">Jednokratno</option>
                                <option value="za_svakih">Za svakih n CV</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Broj CV poena*</label>
                            <input type="number" name="cv_kolicina" required 
                                   class="w-full px-3 py-2 border rounded" min="1">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Poklon proizvod/i*</label>
                            <select name="poklon_proizvodi[]" multiple required 
                                    class="w-full px-3 py-2 border rounded">
                                <?php foreach($proizvodi as $id => $naziv): ?>
                                    <option value="<?= $id ?>"><?= htmlspecialchars($naziv) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-sm text-gray-500 mt-1">Držite Ctrl za odabir više proizvoda</p>
                        </div>
                    </div>
                `;
                break;

            case 'kolicinski_popust':
                html = `
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Minimalni zbir CV*</label>
                            <input type="number" name="min_cv" required 
                                   class="w-full px-3 py-2 border rounded" min="1">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Procenat popusta*</label>
                            <div class="flex items-center">
                                <input type="number" name="procenat_popusta" required 
                                       class="w-20 px-3 py-2 border rounded" min="1" max="100">
                                <span class="ml-2">%</span>
                            </div>
                        </div>
                    </div>
                `;
                break;

            case 'lojalnost':
                html = `
                    <div>
                        <label class="block text-gray-700 mb-2">Procenat povraćaja CV*</label>
                        <div class="flex items-center">
                            <input type="number" name="procenat_povracaja" required 
                                   class="w-20 px-3 py-2 border rounded" min="1" max="100">
                            <span class="ml-2">%</span>
                        </div>
                        <p class="text-sm text-gray-500 mt-1">Koliko % od kupljenih CV klijent dobija nazad</p>
                    </div>
                `;
                break;

            case 'buster':
                html = `
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Broj CV poena*</label>
                            <input type="number" name="cv_kolicina" required 
                                   class="w-full px-3 py-2 border rounded" min="1">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Procenat povraćaja*</label>
                            <div class="flex items-center">
                                <input type="number" name="procenat_povracaja" required 
                                       class="w-20 px-3 py-2 border rounded" min="1" max="100">
                                <span class="ml-2">%</span>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">Koliko % od CV poena može da iskoristi za poklon proizvode</p>
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Poklon proizvod/i*</label>
                            <select name="poklon_proizvodi[]" multiple required 
                                    class="w-full px-3 py-2 border rounded">
                                <?php foreach($proizvodi as $id => $naziv): ?>
                                    <option value="<?= $id ?>"><?= htmlspecialchars($naziv) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-sm text-gray-500 mt-1">Držite Ctrl za odabir više proizvoda</p>
                        </div>
                    </div>
                `;
                break;
        }

        uslovContainer.innerHTML = html;
    });
});
</script>

<?php require '../partials/footer.php'; ?>