<?php
require '../partials/db.php';

$id = $_GET['id'] ?? 0;

if ($id > 0) {
    // Logičko brisanje
    $sql = "UPDATE popusti_pokloni SET aktivan = 0 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Uslov uspešno obrisan";
    } else {
        $_SESSION['error'] = "Greška pri brisanju: " . $conn->error;
    }
    
    $stmt->close();
}

header("Location: index.php");
exit;
?>