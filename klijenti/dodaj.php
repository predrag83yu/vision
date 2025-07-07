<?php
include '../partials/header.php';
include '../partials/db.php';

// Generisanje novog članskog broja
$sql = "SELECT MAX(CAST(SUBSTRING(clanski_broj, 5) AS UNSIGNED)) AS max_broj FROM klijenti";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$novi_broj = str_pad(($row['max_broj'] + 1), 6, '0', STR_PAD_LEFT);
$clanski_broj = "381-" . $novi_broj;

// Inicijalizacija promenljivih za sponzora
$sponzor_clanski_broj = 'VISION';
$sponzor_ime_prezime = 'VISION (root sponzor)';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ime = $conn->real_escape_string($_POST['ime']);
    $prezime = $conn->real_escape_string($_POST['prezime']);
    $adresa = $conn->real_escape_string($_POST['adresa']);
    $email = $conn->real_escape_string($_POST['email']);
    $telefon = $conn->real_escape_string($_POST['telefon']);
    $sponzor_clanski_broj = $conn->real_escape_string($_POST['sponzor_clanski_broj']);
    
    // Provera da li sponzor postoji (osim ako nije VISION)
    if ($sponzor_clanski_broj !== 'VISION' && !empty($sponzor_clanski_broj)) {
        $check_sql = "SELECT ime, prezime FROM klijenti WHERE clanski_broj = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $sponzor_clanski_broj);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $_SESSION['error'] = "Sponzor mora biti postojeći član (unesite ispravan članski broj) ili VISION";
            header("Location: dodaj.php");
            exit();
        } else {
            $sponzor = $result->fetch_assoc();
            $sponzor_ime_prezime = $sponzor['ime'] . ' ' . $sponzor['prezime'];
        }
    }

    $sql = "INSERT INTO klijenti (ime, prezime, adresa, email, telefon, clanski_broj, sponzor) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss", $ime, $prezime, $adresa, $email, $telefon, $clanski_broj, $sponzor_clanski_broj);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Klijent je uspešno dodat. Članski broj: $clanski_broj";
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['error'] = "Greška pri dodavanju klijenta: " . $conn->error;
    }
}
?>

<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Dodaj novog klijenta</h1>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <form method="post" class="bg-white rounded-lg shadow p-6">
        <!-- Članski broj na vrhu -->
        <div class="mb-6 bg-blue-50 p-4 rounded-lg">
            <label for="clanski_broj" class="block text-sm font-medium text-blue-800 mb-1">Članski broj</label>
            <input type="text" id="clanski_broj" value="<?= $clanski_broj ?>" readonly 
                   class="w-full px-3 py-2 border border-blue-200 rounded-md bg-blue-100 text-blue-800 font-bold">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="ime" class="block text-sm font-medium text-gray-700 mb-1">Ime *</label>
                <input type="text" id="ime" name="ime" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label for="prezime" class="block text-sm font-medium text-gray-700 mb-1">Prezime *</label>
                <input type="text" id="prezime" name="prezime" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div class="md:col-span-2">
                <label for="adresa" class="block text-sm font-medium text-gray-700 mb-1">Adresa</label>
                <input type="text" id="adresa" name="adresa" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label for="telefon" class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                <input type="tel" id="telefon" name="telefon" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <!-- Poboljšano polje za sponzora -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Sponzor</label>
                <input type="hidden" id="sponzor_clanski_broj" name="sponzor_clanski_broj" value="<?= htmlspecialchars($sponzor_clanski_broj) ?>">
                
                <div class="flex items-center space-x-2">
                    <div class="flex-1">
                        <div class="relative">
                            <input type="text" id="sponzor_pretraga" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Unesite članski broj sponzora ili VISION"
                                   value="<?= htmlspecialchars($sponzor_clanski_broj) ?>">
                            <button type="button" id="sponzorPretragaBtn" class="absolute right-2 top-2 text-gray-500 hover:text-blue-600">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <div id="sponzorRezultati" class="hidden absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto"></div>
                    </div>
                    <div id="sponzorInfo" class="flex-1 p-2 bg-gray-100 rounded-md min-h-[42px]">
                        <?= htmlspecialchars($sponzor_ime_prezime) ?>
                    </div>
                </div>
                <small class="text-gray-500">Unesite "VISION" za root sponzora ili članski broj postojećeg člana</small>
            </div>
        </div>
        
        <div class="mt-6 flex justify-end space-x-3">
            <a href="index.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded transition duration-300">
                <i class="fas fa-times mr-2"></i> Otkaži
            </a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition duration-300">
                <i class="fas fa-save mr-2"></i> Sačuvaj
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sponzorInput = document.getElementById('sponzor_pretraga');
    const sponzorClanskiBrojInput = document.getElementById('sponzor_clanski_broj');
    const sponzorInfo = document.getElementById('sponzorInfo');
    const sponzorPretragaBtn = document.getElementById('sponzorPretragaBtn');
    const sponzorRezultati = document.getElementById('sponzorRezultati');
    
    // Funkcija za pretragu sponzora
    function pretraziSponzore() {
        const pretraga = sponzorInput.value.trim();
        
        // Ako je VISION, ne treba pretraga
        if (pretraga.toUpperCase() === 'VISION') {
            sponzorClanskiBrojInput.value = 'VISION';
            sponzorInfo.textContent = 'VISION (root sponzor)';
            sponzorRezultati.classList.add('hidden');
            return;
        }
        
        if (pretraga.length < 2) {
            sponzorRezultati.classList.add('hidden');
            return;
        }
        
        fetch(`../api/pretraga_klijenata.php?search=${encodeURIComponent(pretraga)}`)
            .then(response => response.json())
            .then(data => {
                sponzorRezultati.innerHTML = '';
                
                if (data.length > 0) {
                    data.forEach(klijent => {
                        const option = document.createElement('div');
                        option.className = 'p-2 hover:bg-gray-100 cursor-pointer';
                        option.textContent = `${klijent.clanski_broj} - ${klijent.ime} ${klijent.prezime}`;
                        option.addEventListener('click', function() {
                            sponzorInput.value = klijent.clanski_broj;
                            sponzorClanskiBrojInput.value = klijent.clanski_broj;
                            sponzorInfo.textContent = `${klijent.ime} ${klijent.prezime}`;
                            sponzorRezultati.classList.add('hidden');
                        });
                        sponzorRezultati.appendChild(option);
                    });
                    sponzorRezultati.classList.remove('hidden');
                } else {
                    const noResults = document.createElement('div');
                    noResults.className = 'p-2 text-gray-500';
                    noResults.textContent = 'Nema rezultata';
                    sponzorRezultati.appendChild(noResults);
                    sponzorRezultati.classList.remove('hidden');
                }
            });
    }
    
    // Event listeneri
    sponzorPretragaBtn.addEventListener('click', pretraziSponzore);
    sponzorInput.addEventListener('input', function() {
        if (this.value.toUpperCase() === 'VISION') {
            sponzorClanskiBrojInput.value = 'VISION';
            sponzorInfo.textContent = 'VISION (root sponzor)';
            sponzorRezultati.classList.add('hidden');
        }
    });
    sponzorInput.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            pretraziSponzore();
        }
    });
    
    // Sakrij rezultate kada se klikne negde drugde
    document.addEventListener('click', function(e) {
        if (!sponzorRezultati.contains(e.target) && 
            e.target !== sponzorInput && 
            e.target !== sponzorPretragaBtn) {
            sponzorRezultati.classList.add('hidden');
        }
    });
});
</script>

<?php include '../partials/footer.php'; ?>