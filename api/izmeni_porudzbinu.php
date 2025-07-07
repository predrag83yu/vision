<?php
header('Content-Type: application/json');
include '../partials/db.php';

// Provera da li je zahtev POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Samo POST zahtevi su dozvoljeni']);
    exit;
}

// Čitanje podataka iz tela zahteva
$input = json_decode(file_get_contents('php://input'), true);

// Validacija podataka
if (!isset($input['porudzbina_id']) || !is_numeric($input['porudzbina_id'])) {
    echo json_encode(['success' => false, 'error' => 'Nevažeći ID porudžbine']);
    exit;
}

if (!isset($input['klijent_id']) || !is_numeric($input['klijent_id'])) {
    echo json_encode(['success' => false, 'error' => 'Nevažeći ID klijenta']);
    exit;
}

if (!isset($input['stavke']) || !is_array($input['stavke']) || count($input['stavke']) === 0) {
    echo json_encode(['success' => false, 'error' => 'Morate dodati barem jedan proizvod']);
    exit;
}

$porudzbinaId = (int)$input['porudzbina_id'];
$klijentId = (int)$input['klijent_id'];
$stavke = $input['stavke'];

// Počni transakciju
$conn->begin_transaction();

try {
    // 1. Proveri da li porudžbina postoji i pripada klijentu
    $stmt = $conn->prepare("SELECT id FROM porudzbine WHERE id = ? AND klijent_id = ?");
    $stmt->bind_param("ii", $porudzbinaId, $klijentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Porudžbina nije pronađena ili ne pripada klijentu');
    }
    $stmt->close();

    // 2. Obriši sve postojeće stavke za ovu porudžbinu
    $stmt = $conn->prepare("DELETE FROM stavke_porudzbine WHERE porudzbina_id = ?");
    $stmt->bind_param("i", $porudzbinaId);
    $stmt->execute();
    $stmt->close();

    // 3. Dodaj nove stavke i izračunaj ukupno CV
    $ukupnoCV = 0;
    
    foreach ($stavke as $stavka) {
        $proizvodId = (int)$stavka['id'];
        $kolicina = (int)$stavka['kolicina'];
        $cena = (float)$stavka['cena'];
        $cv = (float)$stavka['cv'];
        
        $ukupnoCV += ($cv * $kolicina);

        $stmt = $conn->prepare("INSERT INTO stavke_porudzbine 
                               (porudzbina_id, proizvod_id, kolicina, cena_po_jedinici, cv_po_jedinici) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiidd", $porudzbinaId, $proizvodId, $kolicina, $cena, $cv);
        $stmt->execute();
        $stmt->close();
    }

    // 4. Ažuriraj ukupno CV u porudžbini
    $stmt = $conn->prepare("UPDATE porudzbine SET ukupno_cv = ? WHERE id = ?");
    $stmt->bind_param("di", $ukupnoCV, $porudzbinaId);
    $stmt->execute();
    $stmt->close();

    // 5. Ažuriraj istoriju lojalnosti za klijenta
    $trenutniMesecGodina = date('Y-m');
    $stmt = $conn->prepare("SELECT id FROM istorija_lojalnosti 
                           WHERE klijent_id = ? AND mesec_godina = ?");
    $stmt->bind_param("is", $klijentId, $trenutniMesecGodina);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Ažuriraj postojeći unos
        $stmt = $conn->prepare("UPDATE istorija_lojalnosti 
                              SET ukupno_cv_mesec = ukupno_cv_mesec + ? 
                              WHERE klijent_id = ? AND mesec_godina = ?");
        $stmt->bind_param("dis", $ukupnoCV, $klijentId, $trenutniMesecGodina);
    } else {
        // Kreiraj novi unos
        $stmt = $conn->prepare("INSERT INTO istorija_lojalnosti 
                              (klijent_id, mesec_godina, ukupno_cv_mesec) 
                              VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $klijentId, $trenutniMesecGodina, $ukupnoCV);
    }
    $stmt->execute();
    $stmt->close();

    // Potvrdi transakciju
    $conn->commit();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Poništi transakciju u slučaju greške
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>