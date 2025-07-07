<?php
header('Content-Type: application/json');

// Omogući prikaz grešaka za debug (obrišite ovo u produkciji)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Funkcija za standardni JSON odgovor
function jsonResponse($success, $message = '', $data = []) {
    http_response_code($success ? 200 : 400);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

try {
    // Proveri da li je POST zahtev
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Samo POST zahtevi su dozvoljeni');
    }

    // Uključi db konekciju
    require_once __DIR__ . '/../partials/db.php';
    
    // Proveri da li postoji konekcija
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Pročitaj input
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }

    // Validacija
    if (!isset($input['porudzbina_id']) || !is_numeric($input['porudzbina_id'])) {
        throw new Exception('Invalid order ID');
    }

    $porudzbinaId = (int)$input['porudzbina_id'];

    // Počni transakciju
    $conn->begin_transaction();

    // 1. Obriši stavke
    $stmt = $conn->prepare("DELETE FROM stavke_porudzbine WHERE porudzbina_id = ?");
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("i", $porudzbinaId);
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    $stmt->close();

    // 2. Obriši porudžbinu
    $stmt = $conn->prepare("DELETE FROM porudzbine WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("i", $porudzbinaId);
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('Order not found');
    }
    $stmt->close();

    $conn->commit();
    jsonResponse(true, 'Order deleted successfully');

} catch (Exception $e) {
    if (isset($conn) && $conn) {
        $conn->rollback();
    }
    jsonResponse(false, $e->getMessage());
}

// Zatvori konekciju ako postoji
if (isset($conn) && $conn) {
    $conn->close();
}
?>