<?php
include '../partials/db.php';

header('Content-Type: application/json');

$search = $_GET['search'] ?? '';
$exactMatch = $_GET['exact_match'] ?? 0;

if (empty($search)) {
    echo json_encode([]);
    exit;
}

$query = "SELECT id, ime, prezime, clanski_broj FROM klijenti WHERE ";
$params = [];

if ($exactMatch) {
    $query .= "clanski_broj = ?";
    $params[] = $search;
} else {
    $query .= "(ime LIKE ? OR prezime LIKE ? OR clanski_broj LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params = [$searchTerm, $searchTerm, $searchTerm];
}

$query .= " LIMIT 10";

$stmt = $conn->prepare($query);
if (count($params) === 1) {
    $stmt->bind_param("s", $params[0]);
} else {
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

echo json_encode($result->fetch_all(MYSQLI_ASSOC));

$stmt->close();
$conn->close();
?>