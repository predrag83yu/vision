<?php
// Koristite apsolutnu putanju ili ispravnu relativnu
include __DIR__ . '/../partials/db.php';

header('Content-Type: application/json');

// Omogućite CORS ako je potrebno
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Proverite da li je konekcija uspostavljena
if (!$conn) {
    die(json_encode(['error' => 'Database connection failed']));
}

$search = $_GET['search'] ?? '';
$searchTerm = '%' . $search . '%';

try {
    $stmt = $conn->prepare("SELECT id, sifra, naziv, cena, cv FROM proizvodi 
                           WHERE aktivan = 1 AND (sifra LIKE ? OR naziv LIKE ?) 
                           ORDER BY naziv ASC LIMIT 10");
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    $proizvodi = [];
    while ($row = $result->fetch_assoc()) {
        $proizvodi[] = [
            'id' => $row['id'],
            'sifra' => $row['sifra'],
            'naziv' => $row['naziv'],
            'cena' => (float)$row['cena'],
            'cv' => (float)$row['cv']
        ];
    }

    echo json_encode($proizvodi);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>