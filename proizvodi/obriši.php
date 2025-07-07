<?php
header('Content-Type: application/json');
include '../partials/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Nevažeći metod']);
    exit;
}

$id = (int)$_GET['id'] ?? 0;

// Provera da li proizvod ima povezane stavke
$check = $conn->query("SELECT COUNT(*) as count FROM stavke_porudzbine WHERE proizvod_id = $id");
$row = $check->fetch_assoc();

if ($row['count'] > 0) {
    echo json_encode(['success' => false, 'error' => 'Proizvod se koristi u porudžbinama i ne može biti obrisan']);
    exit;
}

// Brisanje proizvoda
$stmt = $conn->prepare("DELETE FROM proizvodi WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}

$stmt->close();
$conn->close();
?>