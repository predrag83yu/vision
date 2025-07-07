<?php
include '../partials/header.php';
include '../partials/db.php';

// Setovanje srpske lokalizacije
setlocale(LC_ALL, 'sr_RS.UTF-8', 'sr_RS@latin', 'sr_RS');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Konverzija unosa sa decimalnim zarezom
    $_POST['cena'] = str_replace('.', '', $_POST['cena']);
    $_POST['cena'] = str_replace(',', '.', $_POST['cena']);
    
    $_POST['cv'] = str_replace('.', '', $_POST['cv']);
    $_POST['cv'] = str_replace(',', '.', $_POST['cv']);

    $sifra = $conn->real_escape_string($_POST['sifra']);
    $naziv = $conn->real_escape_string($_POST['naziv']);
    $cena = (float)$_POST['cena'];
    $cv = (float)$_POST['cv'];
    $je_set = isset($_POST['je_set']) ? 1 : 0;

    $errors = [];
    if (empty($sifra)) $errors[] = "Šifra je obavezno polje.";
    if (empty($naziv)) $errors[] = "Naziv je obavezno polje.";

    if (empty($errors)) {
        $conn->begin_transaction();
        
        try {
            // Dodavanje proizvoda
            $sql = "INSERT INTO proizvodi (sifra, naziv, cena, cv, je_set) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssddi", $sifra, $naziv, $cena, $cv, $je_set);
            $stmt->execute();
            $proizvod_id = $conn->insert_id;

            // Ako je set, dodajemo u recepturu
            if ($je_set && !empty($_POST['sastojci'])) {
                $sastojci = json_decode($_POST['sastojci'], true);
                
                if (json_last_error() === JSON_ERROR_NONE && is_array($sastojci)) {
                    foreach ($sastojci as $sastojak) {
                        $sql = "INSERT INTO recepti_setova (set_id, proizvod_id, kolicina) VALUES (?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("iii", $proizvod_id, $sastojak['proizvod_id'], $sastojak['kolicina']);
                        $stmt->execute();
                    }
                }
            }

            $conn->commit();
            $_SESSION['success'] = "Proizvod je uspešno dodat.";
            header("Location: index.php");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Došlo je do greške: " . $e->getMessage();
        }
    }
}

function formatBroj($broj, $decimale = 2) {
    return number_format($broj, $decimale, ',', '.');
}
?>

<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Dodaj novi proizvod</h1>
    
    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= $error ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" class="bg-white rounded-lg shadow p-6" id="proizvodForm">
        <div class="grid grid-cols-1 gap-6">
            <div>
                <label for="sifra" class="block text-sm font-medium text-gray-700 mb-1">Šifra proizvoda *</label>
                <input type="text" id="sifra" name="sifra" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            
            <div>
                <label for="naziv" class="block text-sm font-medium text-gray-700 mb-1">Naziv proizvoda *</label>
                <input type="text" id="naziv" name="naziv" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="cena" class="block text-sm font-medium text-gray-700 mb-1">Cena (RSD) *</label>
                    <input type="text" id="cena" name="cena" required 
                           pattern="[0-9]+([,][0-9]{1,2})?" 
                           title="Unesite broj sa decimalnim zarezom (npr. 1.234,56)"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label for="cv" class="block text-sm font-medium text-gray-700 mb-1">Bodovi (CV) *</label>
                    <input type="text" id="cv" name="cv" required 
                           pattern="[0-9]+([,][0-9]{1,3})?" 
                           title="Unesite broj sa decimalnim zarezom (npr. 12,345)"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
            </div>
            
            <div class="flex items-center">
                <input type="checkbox" id="je_set" name="je_set" value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="je_set" class="ml-2 block text-sm text-gray-700">Proizvod je set</label>
            </div>

            <!-- Sekcija za recepturu (prikazuje se samo ako je set) -->
            <div id="recepturaSection" class="hidden">
                <h3 class="text-lg font-medium text-gray-900 mb-3">Receptura seta:</h3>
                
                <div class="mb-4">
                    <div class="flex mb-2">
                        <input type="text" id="proizvodPretraga" placeholder="Pretraži proizvode" 
                               class="flex-grow px-3 py-2 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="button" id="pretraziProizvod" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-r-md">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <div id="proizvodRezultati" class="hidden border border-gray-300 rounded-md p-2 max-h-40 overflow-y-auto mb-4"></div>
                </div>
                
                <div id="sastojciContainer">
                    <!-- Sastojci će biti dinamički dodati ovde -->
                </div>
                
                <input type="hidden" id="sastojciInput" name="sastojci" value="">
            </div>
        </div>
        
        <div class="mt-6 flex justify-end space-x-3">
            <a href="index.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">Otkaži</a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Sačuvaj</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const jeSetCheckbox = document.getElementById('je_set');
    const recepturaSection = document.getElementById('recepturaSection');
    const sastojciContainer = document.getElementById('sastojciContainer');
    const sastojciInput = document.getElementById('sastojciInput');
    let sastojci = [];

    // Provera unosa za cenu i CV
    document.getElementById('cena').addEventListener('blur', formatirajBroj);
    document.getElementById('cv').addEventListener('blur', formatirajBroj);
    
    function formatirajBroj(e) {
        let value = e.target.value.replace(/\./g, '').replace(',', '.');
        if(isNaN(value)) {
            e.target.value = '';
        }
    }

    // Prikaz/skrivanje sekcije za recepturu
    jeSetCheckbox.addEventListener('change', function() {
        recepturaSection.classList.toggle('hidden', !this.checked);
        if (this.checked) {
            osveziPrikazSastojaka();
        }
    });

    // Pretraga proizvoda
    document.getElementById('pretraziProizvod').addEventListener('click', pretraziProizvode);
    document.getElementById('proizvodPretraga').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') pretraziProizvode();
    });

    function pretraziProizvode() {
        const pretraga = document.getElementById('proizvodPretraga').value.trim();
        if (pretraga.length < 2) return;
        
        fetch(`../api/pretraga_proizvoda.php?search=${encodeURIComponent(pretraga)}&exclude_sets=1`)
            .then(response => response.json())
            .then(data => {
                const rezultati = document.getElementById('proizvodRezultati');
                rezultati.innerHTML = '';
                
                if (data.length > 0) {
                    rezultati.classList.remove('hidden');
                    data.forEach(proizvod => {
                        const div = document.createElement('div');
                        div.className = 'p-2 hover:bg-gray-100 cursor-pointer flex justify-between';
                        div.innerHTML = `
                            <span>${proizvod.sifra} - ${proizvod.naziv}</span>
                            <button class="text-blue-600 hover:text-blue-800 text-sm" 
                                    onclick="dodajSastojak(${proizvod.id}, '${proizvod.sifra.replace("'", "\\'")}', '${proizvod.naziv.replace("'", "\\'")}')">
                                <i class="fas fa-plus"></i> Dodaj
                            </button>
                        `;
                        rezultati.appendChild(div);
                    });
                } else {
                    rezultati.classList.add('hidden');
                }
            });
    }

    // Dodavanje sastojka u recepturu
    window.dodajSastojak = function(id, sifra, naziv) {
        // Proveri da li je već dodat
        if (sastojci.some(s => s.proizvod_id == id)) {
            alert('Ovaj proizvod je već dodat u recepturu!');
            return;
        }

        const noviSastojak = {
            proizvod_id: id,
            sifra: sifra,
            naziv: naziv,
            kolicina: 1
        };
        sastojci.push(noviSastojak);
        osveziPrikazSastojaka();
        document.getElementById('proizvodPretraga').value = '';
        document.getElementById('proizvodRezultati').classList.add('hidden');
    }

    // Osvežavanje prikaza sastojaka
    function osveziPrikazSastojaka() {
        sastojciContainer.innerHTML = '';
        
        if (sastojci.length === 0) {
            sastojciContainer.innerHTML = '<p class="text-gray-500">Nema dodatih sastojaka</p>';
            sastojciInput.value = '';
            return;
        }

        const table = document.createElement('table');
        table.className = 'min-w-full divide-y divide-gray-200';
        table.innerHTML = `
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2">Šifra</th>
                    <th class="px-4 py-2">Naziv</th>
                    <th class="px-4 py-2">Količina</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody id="sastojciTabela"></tbody>
        `;
        sastojciContainer.appendChild(table);

        const tbody = document.getElementById('sastojciTabela');
        sastojci.forEach((sastojak, index) => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50';
            row.innerHTML = `
                <td class="px-4 py-2">${sastojak.sifra}</td>
                <td class="px-4 py-2">${sastojak.naziv}</td>
                <td class="px-4 py-2">
                    <input type="number" min="1" value="${sastojak.kolicina}" 
                           class="kolicinaInput w-20 px-2 py-1 border rounded" 
                           data-index="${index}">
                </td>
                <td class="px-4 py-2">
                    <button type="button" onclick="ukloniSastojak(${index})" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });

        // Dodaj event listenere za promenu količine
        document.querySelectorAll('.kolicinaInput').forEach(input => {
            input.addEventListener('change', function() {
                const index = parseInt(this.dataset.index);
                sastojci[index].kolicina = parseInt(this.value);
                osveziPrikazSastojaka();
            });
        });

        // Ažuriraj skriveni input sa JSON podacima
        sastojciInput.value = JSON.stringify(sastojci);
    }

    // Uklanjanje sastojka iz recepture
    window.ukloniSastojak = function(index) {
        sastojci.splice(index, 1);
        osveziPrikazSastojaka();
    }

    // Inicijalno osvežavanje
    osveziPrikazSastojaka();
});
</script>

<?php 
$conn->close();
include '../partials/footer.php'; 
?>