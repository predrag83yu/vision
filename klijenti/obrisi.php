<?php
header('Content-Type: application/json');
include '../partials/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Nevažeći metod']);
    exit;
}

$id = (int)$_GET['id'] ?? 0;

// Provera da li klijent ima porudžbine
$check = $conn->query("SELECT COUNT(*) as count FROM porudzbine WHERE klijent_id = $id");
$row = $check->fetch_assoc();

if ($row['count'] > 0) {
    echo json_encode(['success' => false, 'error' => 'Klijent ima porudžbine i ne može biti obrisan']);
    exit;
}

// Brisanje klijenta
$stmt = $conn->prepare("DELETE FROM klijenti WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}

$stmt->close();
$conn->close();
?>