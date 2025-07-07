<?php
include __DIR__ . '/../partials/db.php';
header('Content-Type: application/json');

try {
    $porudzbinaId = $_POST['porudzbina_id'] ?? 0;
    $klijentId = $_POST['klijent_id'];
    $brojPorudzbine = $_POST['broj_porudzbine'];
    $proizvodi = json_decode($_POST['proizvodi'], true);
    $primenjiviPopusti = json_decode($_POST['primenjivi_popusti'] ?? '[]', true);
    $odabraniPoklonId = $_POST['odabrani_poklon_id'] ?? null;

    // Izračunavanje ukupnih vrednosti
    $ukupno = 0;
    $ukupnoCV = 0;
    
    foreach ($proizvodi as $proizvod) {
        $ukupno += $proizvod['cena'] * $proizvod['kolicina'];
        $ukupnoCV += $proizvod['cv'] * $proizvod['kolicina'];
    }

    // Primena popusta
    $popust = 0;
    if (!empty($primenjiviPopusti)) {
        $popust = $ukupno * ($primenjiviPopusti[0]['procenat_popusta'] / 100);
    }
    $zaUplatu = $ukupno - $popust;

    if ($porudzbinaId) {
        // Ažuriranje postojeće porudžbine
        $stmt = $conn->prepare("UPDATE porudzbine SET 
                              broj_porudzbine = ?, 
                              ukupno = ?, 
                              ukupno_cv = ?, 
                              popust = ?, 
                              za_uplatu = ? 
                              WHERE id = ?");
        $stmt->bind_param("sdddii", $brojPorudzbine, $ukupno, $ukupnoCV, $popust, $zaUplatu, $porudzbinaId);
    } else {
        // Kreiranje nove porudžbine
        $stmt = $conn->prepare("INSERT INTO porudzbine 
                              (broj_porudzbine, klijent_id, datum, ukupno, ukupno_cv, popust, za_uplatu) 
                              VALUES (?, ?, NOW(), ?, ?, ?, ?)");
        $stmt->bind_param("sidddd", $brojPorudzbine, $klijentId, $ukupno, $ukupnoCV, $popust, $zaUplatu);
    }
    $stmt->execute();
    
    if (!$porudzbinaId) {
        $porudzbinaId = $conn->insert_id;
    }

    // Čuvanje stavki porudžbine
    $conn->query("DELETE FROM stavke_porudzbine WHERE porudzbina_id = $porudzbinaId");
    
    $stmt = $conn->prepare("INSERT INTO stavke_porudzbine 
                          (porudzbina_id, proizvod_id, kolicina, cena_po_jedinici, cv_po_jedinici) 
                          VALUES (?, ?, ?, ?, ?)");
    
    foreach ($proizvodi as $proizvod) {
        $stmt->bind_param("iiidd", $porudzbinaId, $proizvod['id'], $proizvod['kolicina'], $proizvod['cena'], $proizvod['cv']);
        $stmt->execute();
    }

    // Ažuriranje poklona ako postoji
    if ($odabraniPoklonId) {
        $conn->query("UPDATE porudzbine SET poklon_id = $odabraniPoklonId WHERE id = $porudzbinaId");
    }

    echo json_encode(['success' => true, 'id' => $porudzbinaId]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>