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
$required = ['reset_period', 'standard_discount', 'loyalty_percent'];
foreach ($required as $field) {
    if (!isset($input[$field]) || !is_numeric($input[$field])) {
        echo json_encode(['success' => false, 'error' => 'Nevažeći podaci za: ' . $field]);
        exit;
    }
}

try {
    // Čuvanje podešavanja u bazu
    $stmt = $conn->prepare("INSERT INTO sistemska_podesavanja 
                           (naziv, vrednost) VALUES 
                           ('reset_period', ?),
                           ('standard_discount', ?),
                           ('loyalty_percent', ?)
                           ON DUPLICATE KEY UPDATE vrednost = VALUES(vrednost)");
    
    $stmt->bind_param("iii", 
        $input['reset_period'],
        $input['standard_discount'],
        $input['loyalty_percent']
    );
    
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>