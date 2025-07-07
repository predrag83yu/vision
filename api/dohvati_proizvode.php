<?php
include '../partials/db.php';

header('Content-Type: application/json');

$ids = $_GET['ids'] ?? '';
$idsArray = explode(',', $ids);
$placeholders = implode(',', array_fill(0, count($idsArray), '?'));

$stmt = $conn->prepare("SELECT id, sifra, naziv, cena, cv FROM proizvodi WHERE id IN ($placeholders)");
$stmt->bind_param(str_repeat('i', count($idsArray)), ...$idsArray);
$stmt->execute();
$result = $stmt->get_result();

echo json_encode($result->fetch_all(MYSQLI_ASSOC));

$stmt->close();
$conn->close();
?>