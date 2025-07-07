<?php
include '../partials/header.php';
include '../partials/db.php';

// Provera i učitavanje klijenta i porudžbine
$klijentId = $_GET['klijent_id'] ?? 0;
$porudzbinaId = $_GET['porudzbina_id'] ?? 0;
$klijent = null;
$porudzbina = null;
$klijentValid = false;
$trenutniMesecCV = 0;

// Generisanje broja porudžbine
if (!$porudzbinaId) {
    $trenutniDatum = new DateTime();
    $godina = $trenutniDatum->format('y');
    $mesec = $trenutniDatum->format('m');
    $dan = $trenutniDatum->format('d');
    
    $stmt = $conn->prepare("SELECT COUNT(*) as broj FROM porudzbine WHERE DATE(datum) = CURDATE()");
    $stmt->execute();
    $result = $stmt->get_result();
    $brojPorudzbina = $result->fetch_assoc()['broj'] + 1;
    
    $brojPorudzbine = sprintf("%s%s%s-%04d", $godina, $dan, $mesec, $brojPorudzbina);
} else {
    // Učitaj postojeću porudžbinu
    $stmt = $conn->prepare("SELECT p.*, k.id as klijent_id, k.ime, k.prezime, k.clanski_broj 
                           FROM porudzbine p 
                           JOIN klijenti k ON p.klijent_id = k.id 
                           WHERE p.id = ?");
    $stmt->bind_param("i", $porudzbinaId);
    $stmt->execute();
    $result = $stmt->get_result();
    $porudzbina = $result->fetch_assoc();
    $stmt->close();
    
    if ($porudzbina) {
        $klijentId = $porudzbina['klijent_id'];
        $klijent = [
            'id' => $porudzbina['klijent_id'],
            'ime' => $porudzbina['ime'],
            'prezime' => $porudzbina['prezime'],
            'clanski_broj' => $porudzbina['clanski_broj']
        ];
        $klijentValid = true;
        $brojPorudzbine = $porudzbina['broj_porudzbine'];
        
        // Učitaj stavke porudžbine
        $stmt = $conn->prepare("SELECT sp.*, pr.sifra, pr.naziv, pr.cena, pr.cv 
                              FROM stavke_porudzbine sp
                              JOIN proizvodi pr ON sp.proizvod_id = pr.id
                              WHERE sp.porudzbina_id = ?");
        $stmt->bind_param("i", $porudzbinaId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stavkePorudzbine = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
} 

if ($klijentId && !$porudzbinaId) {
    $stmt = $conn->prepare("SELECT id, ime, prezime, clanski_broj FROM klijenti WHERE id = ?");
    $stmt->bind_param("i", $klijentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $klijent = $result->fetch_assoc();
    $stmt->close();
    
    if ($klijent) {
        $klijentValid = true;
        
        // Izračunaj ukupno CV za tekući mesec
        $trenutniMesec = date('Y-m');
        $stmt = $conn->prepare("SELECT COALESCE(SUM(ukupno_cv), 0) as ukupno_cv 
                               FROM porudzbine 
                               WHERE klijent_id = ? 
                               AND DATE_FORMAT(datum, '%Y-%m') = ?");
        $stmt->bind_param("is", $klijentId, $trenutniMesec);
        $stmt->execute();
        $result = $stmt->get_result();
        $trenutniMesecCV = $result->fetch_assoc()['ukupno_cv'] ?? 0;
        $stmt->close();
    }
}

// Dohvati sve aktivne popuste
$popusti = $conn->query("
    SELECT id, naziv, opis, uslovi 
    FROM popusti_pokloni 
    WHERE aktivan = 1 AND JSON_EXTRACT(uslovi, '$.tip') IN ('kolicinski_popust', 'poklon_na_cv')
    ORDER BY JSON_EXTRACT(uslovi, '$.min_cv') ASC
")->fetch_all(MYSQLI_ASSOC);

// Pronađi primenjive popuste
$primenjiviPopusti = [];
foreach ($popusti as $popust) {
    $uslovi = json_decode($popust['uslovi'], true);
    if ($uslovi['tip'] === 'kolicinski_popust' && $trenutniMesecCV >= $uslovi['min_cv']) {
        $primenjiviPopusti[] = [
            'id' => $popust['id'],
            'naziv' => $popust['naziv'],
            'procenat_popusta' => $uslovi['procenat_popusta']
        ];
    }
}

// Proveri da li postoji poklon za tekući CV
$odabraniPoklon = null;
foreach ($popusti as $popust) {
    $uslovi = json_decode($popust['uslovi'], true);
    if ($uslovi['tip'] === 'poklon_na_cv' && $trenutniMesecCV >= $uslovi['cv_kolicina']) {
        $odabraniPoklon = [
            'id' => $popust['id'],
            'naziv' => $popust['naziv'],
            'proizvodi' => $uslovi['poklon_proizvodi']
        ];
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $porudzbinaId ? 'Izmena porudžbine' : 'Nova porudžbina' ?></title>
    <link href="../css/tailwind.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
<div class="max-w-4xl mx-auto py-8 px-4">
    <h1 class="text-2xl font-bold mb-6">
        <?= $porudzbinaId ? 'Izmena porudžbine #' . $brojPorudzbine : 'Nova porudžbina #' . $brojPorudzbine ?>
    </h1>

    <!-- Prikaz trenutnog stanja CV - samo ako je klijent učitan -->
    <?php if ($klijentValid): ?>
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h3 class="font-medium text-blue-800">Lojalnost klijenta</h3>
                <p class="text-sm text-blue-600">
                    Ukupno CV u <?= date('F Y') ?>: <span class="font-bold"><?= number_format($trenutniMesecCV, 3, ',', '.') ?></span>
                </p>
            </div>
            <?php if (!empty($primenjiviPopusti)): ?>
                <div class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm">
                    <i class="fas fa-tag mr-1"></i>
                    Aktivni popust: <?= $primenjiviPopusti[0]['procenat_popusta'] ?>%
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <form id="porudzbinaForm" class="bg-white rounded-lg shadow p-6">
        <input type="hidden" id="porudzbinaId" name="porudzbina_id" value="<?= $porudzbinaId ?>">
        <input type="hidden" id="klijentId" name="klijent_id" value="<?= $klijent['id'] ?? '' ?>">
        <input type="hidden" id="brojPorudzbine" name="broj_porudzbine" value="<?= $brojPorudzbine ?>">
        <input type="hidden" id="primenjiviPopusti" name="primenjivi_popusti" value="<?= htmlspecialchars(json_encode($primenjiviPopusti)) ?>">
        <input type="hidden" id="odabraniPoklonId" name="odabrani_poklon_id" value="<?= $odabraniPoklon['id'] ?? '' ?>">

        <!-- Odabir klijenta -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Klijent *</label>
            <?php if ($klijentValid): ?>
                <div class="p-3 bg-gray-100 rounded-md mb-2">
                    <p class="font-medium"><?= htmlspecialchars($klijent['ime'] . ' ' . $klijent['prezime']) ?></p>
                    <p>Članski broj: <?= htmlspecialchars($klijent['clanski_broj']) ?></p>
                </div>
            <?php else: ?>
                <div class="flex mb-2">
                    <input type="text" id="klijentPretraga" placeholder="Pretraži po imenu, prezimenu ili članskom broju" 
                           class="flex-grow px-3 py-2 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="button" id="pretraziKlijenta" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-r-md">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <div id="klijentRezultati" class="hidden mt-2 border border-gray-300 rounded-md p-2 max-h-40 overflow-y-auto"></div>
                <div id="klijentGreska" class="text-red-600 text-sm mt-1 hidden">Morate odabrati validnog klijenta</div>
            <?php endif; ?>
        </div>

        <!-- Tabela proizvoda -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Proizvodi *</label>
            <div class="flex mb-2">
                <input type="text" id="proizvodPretraga" placeholder="Pretraži proizvode" 
                       class="flex-grow px-3 py-2 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                       <?= !$klijentValid ? 'disabled' : '' ?>>
                <button type="button" id="pretraziProizvod" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-r-md" 
                        <?= !$klijentValid ? 'disabled' : '' ?>>
                    <i class="fas fa-search"></i>
                </button>
            </div>
            <div id="proizvodRezultati" class="hidden border border-gray-300 rounded-md p-2 max-h-40 overflow-y-auto mb-4"></div>
            
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2">Šifra</th>
                        <th class="px-4 py-2">Naziv</th>
                        <th class="px-4 py-2">Cena</th>
                        <th class="px-4 py-2">CV</th>
                        <th class="px-4 py-2">Količina</th>
                        <th class="px-4 py-2">Ukupno</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody id="stavkePorudzbine">
                    <!-- Dinamički će se popuniti JavaScript-om -->
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50">
                        <td colspan="5" class="px-4 py-2 text-right font-bold">Ukupno:</td>
                        <td id="ukupnoCena" class="px-4 py-2">0,00 RSD</td>
                        <td id="ukupnoCV" class="px-4 py-2">0,00 CV</td>
                    </tr>
                    <?php if (!empty($primenjiviPopusti)): ?>
                    <tr class="bg-yellow-50">
                        <td colspan="5" class="px-4 py-2 text-right font-bold">Popust (<?= $primenjiviPopusti[0]['procenat_popusta'] ?>%):</td>
                        <td id="iznosPopusta" class="px-4 py-2">-0,00 RSD</td>
                        <td class="px-4 py-2"></td>
                    </tr>
                    <tr class="bg-green-50">
                        <td colspan="5" class="px-4 py-2 text-right font-bold">Za uplatu:</td>
                        <td id="zaUplatu" class="px-4 py-2 font-bold">0,00 RSD</td>
                        <td class="px-4 py-2"></td>
                    </tr>
                    <?php endif; ?>
                </tfoot>
            </table>
        </div>

        <!-- Sekcija za poklone -->
        <?php if ($odabraniPoklon): ?>
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="font-medium text-blue-800">Dostupan poklon</h3>
                    <p class="text-sm text-blue-600">
                        <?= htmlspecialchars($odabraniPoklon['naziv']) ?> - 
                        Proizvodi: <?= htmlspecialchars(implode(', ', $odabraniPoklon['proizvodi'])) ?>
                    </p>
                </div>
                <button type="button" id="dodajPoklonBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1 rounded text-sm">
                    <i class="fas fa-gift mr-1"></i> Dodaj poklon
                </button>
            </div>
        </div>
        <?php endif; ?>

        <div class="flex justify-end space-x-3">
            <a href="index.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">Otkaži</a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded" 
                    <?= !$klijentValid ? 'disabled' : '' ?>>
                <?= $porudzbinaId ? 'Sačuvaj izmene' : 'Sačuvaj porudžbinu' ?>
            </button>
        </div>
    </form>
</div>

<script>
// Globalni niz proizvoda
let proizvodi = <?= isset($stavkePorudzbine) ? json_encode(array_map(function($item) {
    return [
        'id' => $item['proizvod_id'],
        'sifra' => $item['sifra'],
        'naziv' => $item['naziv'],
        'cena' => (float)$item['cena_po_jedinici'],
        'cv' => (float)$item['cv_po_jedinici'],
        'kolicina' => (int)$item['kolicina']
    ];
}, $stavkePorudzbine)) : '[]' ?>;

let odabraniPoklon = null;

// Funkcija za formatiranje brojeva sa decimalnim zarezom
function formatirajBroj(broj, decimala = 2) {
    return broj.toFixed(decimala).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

// Funkcija za odabir klijenta
function odaberiKlijenta(klijent) {
    document.getElementById('klijentId').value = klijent.id;
    document.getElementById('klijentPretraga').value = `${klijent.ime} ${klijent.prezime} (${klijent.clanski_broj})`;
    document.getElementById('klijentRezultati').classList.add('hidden');
    document.getElementById('klijentGreska').classList.add('hidden');
    
    // Omogući unos proizvoda
    document.getElementById('proizvodPretraga').disabled = false;
    document.getElementById('pretraziProizvod').disabled = false;
    
    // Osveži stranicu da učita CV podatke za klijenta
    window.location.href = `dodaj.php?klijent_id=${klijent.id}`;
}

// Funkcija za pretragu klijenata
function pretraziKlijente() {
    const pretraga = document.getElementById('klijentPretraga').value.trim();
    if (pretraga.length < 1) {
        document.getElementById('klijentRezultati').classList.add('hidden');
        return;
    }

    fetch(`../api/pretraga_klijenata.php?search=${encodeURIComponent(pretraga)}`)
        .then(response => response.json())
        .then(data => {
            const rezultati = document.getElementById('klijentRezultati');
            rezultati.innerHTML = '';
            
            if (data.length > 0) {
                rezultati.classList.remove('hidden');
                
                data.forEach(klijent => {
                    const div = document.createElement('div');
                    div.className = 'p-2 hover:bg-gray-100 cursor-pointer';
                    div.innerHTML = `
                        <div class="font-medium">${klijent.ime} ${klijent.prezime}</div>
                        <div class="text-sm text-gray-600">Članski broj: ${klijent.clanski_broj}</div>
                    `;
                    div.addEventListener('click', () => odaberiKlijenta(klijent));
                    rezultati.appendChild(div);
                });
            } else {
                rezultati.classList.add('hidden');
                document.getElementById('klijentGreska').textContent = 'Nema pronađenih klijenata';
                document.getElementById('klijentGreska').classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Greška pri pretrazi:', error);
            document.getElementById('klijentGreska').textContent = 'Došlo je do greške pri pretrazi';
            document.getElementById('klijentGreska').classList.remove('hidden');
        });
}

// Funkcija za pretragu proizvoda
function pretraziProizvode() {
    const pretraga = document.getElementById('proizvodPretraga').value.trim();
    if (pretraga.length < 1) {
        document.getElementById('proizvodRezultati').classList.add('hidden');
        return;
    }

    fetch(`../api/pretraga_proizvoda.php?search=${encodeURIComponent(pretraga)}`)
        .then(response => response.json())
        .then(data => {
            const rezultati = document.getElementById('proizvodRezultati');
            rezultati.innerHTML = '';
            
            if (data.length > 0) {
                rezultati.classList.remove('hidden');
                
                data.forEach(proizvod => {
                    const div = document.createElement('div');
                    div.className = 'p-2 hover:bg-gray-100 cursor-pointer flex justify-between items-center';
                    div.innerHTML = `
                        <div>
                            <div class="font-medium">${proizvod.sifra} - ${proizvod.naziv}</div>
                            <div class="text-sm text-gray-600">${formatirajBroj(proizvod.cena, 2)} RSD | CV: ${formatirajBroj(proizvod.cv, 3)}</div>
                        </div>
                        <button type="button" class="dodajProizvodBtn bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm" 
                                data-id="${proizvod.id}" data-sifra="${proizvod.sifra}" data-naziv="${proizvod.naziv}" 
                                data-cena="${proizvod.cena}" data-cv="${proizvod.cv}">
                            <i class="fas fa-plus"></i> Dodaj
                        </button>
                    `;
                    rezultati.appendChild(div);
                });

                // Dodaj event listenere za dugmad
                document.querySelectorAll('.dodajProizvodBtn').forEach(button => {
                    button.addEventListener('click', function() {
                        dodajProizvod({
                            id: parseInt(this.dataset.id),
                            sifra: this.dataset.sifra,
                            naziv: this.dataset.naziv,
                            cena: parseFloat(this.dataset.cena),
                            cv: parseFloat(this.dataset.cv),
                            kolicina: 1
                        });
                        document.getElementById('proizvodPretraga').value = '';
                        document.getElementById('proizvodRezultati').classList.add('hidden');
                    });
                });
            } else {
                rezultati.classList.add('hidden');
            }
        })
        .catch(error => {
            console.error('Greška pri pretrazi proizvoda:', error);
        });
}

// Funkcija za dodavanje proizvoda
function dodajProizvod(proizvod) {
    // Proveri da li proizvod već postoji
    const postojiIndex = proizvodi.findIndex(p => p.id === proizvod.id);
    
    if (postojiIndex >= 0) {
        // Ako postoji, samo povećaj količinu
        proizvodi[postojiIndex].kolicina += 1;
    } else {
        // Ako ne postoji, dodaj novi proizvod
        proizvodi.push(proizvod);
    }
    
    osveziTabelu();
}

// Funkcija za osvežavanje tabele
function osveziTabelu() {
    const stavkePorudzbine = document.getElementById('stavkePorudzbine');
    if (!stavkePorudzbine) return;
    
    let ukupnoCena = 0;
    let ukupnoCV = 0;

    stavkePorudzbine.innerHTML = '';
    proizvodi.forEach(proizvod => {
        const ukupnaCena = proizvod.cena * proizvod.kolicina;
        const ukupnoCVBodova = proizvod.cv * proizvod.kolicina;
        
        ukupnoCena += ukupnaCena;
        ukupnoCV += ukupnoCVBodova;

        const red = document.createElement('tr');
        red.className = 'hover:bg-gray-50';
        red.innerHTML = `
            <td class="px-4 py-2">${proizvod.sifra}</td>
            <td class="px-4 py-2">${proizvod.naziv}</td>
            <td class="px-4 py-2">${formatirajBroj(proizvod.cena, 2)} RSD</td>
            <td class="px-4 py-2">${formatirajBroj(proizvod.cv, 3)}</td>
            <td class="px-4 py-2">
                <input type="number" min="1" value="${proizvod.kolicina}" 
                       class="kolicinaInput w-20 px-2 py-1 border rounded" 
                       data-id="${proizvod.id}">
            </td>
            <td class="px-4 py-2">${formatirajBroj(ukupnaCena, 2)} RSD</td>
            <td class="px-4 py-2">
                <button type="button" class="obrisiProizvod text-red-600 hover:text-red-800" data-id="${proizvod.id}">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        stavkePorudzbine.appendChild(red);
    });

    document.getElementById('ukupnoCena').textContent = formatirajBroj(ukupnoCena, 2) + ' RSD';
    document.getElementById('ukupnoCV').textContent = formatirajBroj(ukupnoCV, 3);

    // Ažuriranje popusta ako postoje primenjivi popusti
    const popusti = JSON.parse(document.getElementById('primenjiviPopusti').value);
    if (popusti && popusti.length > 0) {
        const iznosPopusta = ukupnoCena * (popusti[0].procenat_popusta / 100);
        const zaUplatu = ukupnoCena - iznosPopusta;
        
        document.getElementById('iznosPopusta').textContent = formatirajBroj(iznosPopusta, 2) + ' RSD';
        document.getElementById('zaUplatu').textContent = formatirajBroj(zaUplatu, 2) + ' RSD';
    }

    // Dodaj event listenere
    document.querySelectorAll('.kolicinaInput').forEach(input => {
        input.addEventListener('change', function() {
            const id = parseInt(this.dataset.id);
            const kolicina = parseInt(this.value);
            proizvodi = proizvodi.map(p => 
                p.id === id ? {...p, kolicina: kolicina} : p
            );
            osveziTabelu();
        });
    });

    document.querySelectorAll('.obrisiProizvod').forEach(button => {
        button.addEventListener('click', function() {
            const id = parseInt(this.dataset.id);
            proizvodi = proizvodi.filter(p => p.id !== id);
            osveziTabelu();
        });
    });
}

// Inicijalizacija
document.addEventListener('DOMContentLoaded', function() {
    // Event listener za Enter taster kod pretrage klijenata
    document.getElementById('klijentPretraga')?.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            pretraziKlijente();
        }
    });

    // Event listener za dugme pretrage klijenata
    document.getElementById('pretraziKlijenta')?.addEventListener('click', pretraziKlijente);

    // Event listener za Enter taster kod pretrage proizvoda
    document.getElementById('proizvodPretraga')?.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            pretraziProizvode();
        }
    });

    // Event listener za dugme pretrage proizvoda
    document.getElementById('pretraziProizvod')?.addEventListener('click', pretraziProizvode);

    // Event listener za klik van pretrage klijenata
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#klijentPretraga') && !e.target.closest('#klijentRezultati')) {
            document.getElementById('klijentRezultati').classList.add('hidden');
        }
        
        if (!e.target.closest('#proizvodPretraga') && !e.target.closest('#proizvodRezultati')) {
            document.getElementById('proizvodRezultati').classList.add('hidden');
        }
    });

    // Čuvanje porudžbine
    document.getElementById('porudzbinaForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (proizvodi.length === 0) {
            alert('Morate dodati barem jedan proizvod u porudžbinu');
            return;
        }

        const formData = new FormData(this);
        formData.append('broj_porudzbine', document.getElementById('brojPorudzbine').value);
        formData.append('proizvodi', JSON.stringify(proizvodi));
        
        fetch('../api/sacuvaj_porudzbinu.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                window.location.href = 'index.php?success=1&id=' + data.id;
            } else {
                alert('Došlo je do greške: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Greška:', error);
            alert('Došlo je do greške prilikom čuvanja');
        });
    });

    // Inicijalno osvežavanje tabele
    if (proizvodi.length > 0) {
        osveziTabelu();
    }
});
</script>

<?php include '../partials/footer.php'; ?>