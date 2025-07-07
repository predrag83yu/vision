<?php
include '../partials/header.php';
include '../partials/db.php';

$id = (int)$_GET['id'];

// Dohvatanje osnovnih podataka o porudžbini
$porudzbina = $conn->query("
    SELECT p.*, k.ime, k.prezime, k.clanski_broj
    FROM porudzbine p
    JOIN klijenti k ON p.klijent_id = k.id
    WHERE p.id = $id
")->fetch_assoc();

if (!$porudzbina) {
    header("Location: index.php?error=Porudžbina nije pronađena");
    exit;
}

// Dohvatanje stavki
$stavke = $conn->query("
    SELECT sp.*, pr.sifra, pr.naziv
    FROM stavke_porudzbine sp
    JOIN proizvodi pr ON sp.proizvod_id = pr.id
    WHERE sp.porudzbina_id = $id
");
?>

<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Porudžbina #<?= $id ?></h1>
        <a href="index.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">
            <i class="fas fa-arrow-left mr-2"></i> Nazad
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <h3 class="font-semibold">Klijent:</h3>
                <p><?= htmlspecialchars($porudzbina['ime'] . ' ' . $porudzbina['prezime']) ?></p>
                <p>Članski broj: <?= htmlspecialchars($porudzbina['clanski_broj']) ?></p>
            </div>
            <div>
                <h3 class="font-semibold">Datum:</h3>
                <p><?= date('d.m.Y H:i', strtotime($porudzbina['datum'])) ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3">Šifra</th>
                    <th class="px-6 py-3">Naziv</th>
                    <th class="px-6 py-3">Cena</th>
                    <th class="px-6 py-3">CV</th>
                    <th class="px-6 py-3">Količina</th>
                    <th class="px-6 py-3">Ukupno</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $ukupnoCena = 0;
                $ukupnoCV = 0;
                while($stavka = $stavke->fetch_assoc()): 
                    $ukupnaCena = $stavka['cena_po_jedinici'] * $stavka['kolicina'];
                    $ukupnoCV += $stavka['cv_po_jedinici'] * $stavka['kolicina'];
                    $ukupnoCena += $ukupnaCena;
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4"><?= htmlspecialchars($stavka['sifra']) ?></td>
                    <td class="px-6 py-4"><?= htmlspecialchars($stavka['naziv']) ?></td>
                    <td class="px-6 py-4"><?= number_format($stavka['cena_po_jedinici'], 2, ',', '.') ?> RSD</td>
                    <td class="px-6 py-4"><?= number_format($stavka['cv_po_jedinici'], 3, ',', '.') ?></td>
                    <td class="px-6 py-4"><?= $stavka['kolicina'] ?></td>
                    <td class="px-6 py-4"><?= number_format($ukupnaCena, 2, ',', '.') ?> RSD</td>
                </tr>
                <?php endwhile; ?>
                <tr class="bg-gray-50 font-semibold">
                    <td colspan="5" class="px-6 py-4 text-right">Ukupno:</td>
                    <td class="px-6 py-4"><?= number_format($ukupnoCena, 2, ',', '.') ?> RSD</td>
                </tr>
                <tr class="bg-gray-50 font-semibold">
                    <td colspan="5" class="px-6 py-4 text-right">Ukupno CV bodova:</td>
                    <td class="px-6 py-4"><?= number_format($ukupnoCV, 3, ',', '.') ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Dugmadi na dnu stranice -->
    <div class="flex justify-end space-x-3 bg-white p-4 rounded-lg shadow">
        <button onclick="window.print()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
            <i class="fas fa-print mr-2"></i> Štampaj
        </button>
        <a href="dodaj.php?porudzbina_id=<?= $id ?>&klijent_id=<?= $porudzbina['klijent_id'] ?>" 
           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
            <i class="fas fa-edit mr-2"></i> Izmeni
        </a>
        <button onclick="potvrdiBrisanje()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
            <i class="fas fa-trash mr-2"></i> Obriši
        </button>
    </div>
</div>

<!-- CSS za štampu -->
<style>
@media print {
    body * {
        visibility: hidden;
    }
    .print-section, .print-section * {
        visibility: visible;
    }
    .print-section {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    .no-print {
        display: none !important;
    }
    .bg-gray-50 {
        background-color: #f9fafb !important;
    }
}
</style>

<script>
async function potvrdiBrisanje() {
    try {
        if (!confirm('Da li ste sigurni da želite da obrišete ovu porudžbinu?\n\nOva akcija je nepovratna!')) {
            return;
        }
        
        if (!confirm('STOP!\n\nPotvrdite brisanje porudžbine.\n\nPoslednja šansa da otkažete.')) {
            return;
        }

        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Brisanje...';
        btn.disabled = true;

        // Proveri putanju - bitno!
        const response = await fetch('/vision/api/obrisi_porudzbinu.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                porudzbina_id: <?= $id ?>
            })
        });

        // Proveri da li je odgovor JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            throw new Error(`Server returned: ${text.substring(0, 100)}...`);
        }

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Došlo je do greške na serveru');
        }

        window.location.href = 'index.php?success=Porudžbina je uspešno obrisana';

    } catch (error) {
        console.error('Greška:', error);
        alert('Došlo je do greške: ' + error.message);
        
        // Vrati dugme u prvobitno stanje
        const btn = event.target;
        if (btn) {
            btn.innerHTML = '<i class="fas fa-trash mr-2"></i> Obriši';
            btn.disabled = false;
        }
    }
}
</script>

<?php 
$conn->close();
include '../partials/footer.php'; 
?>