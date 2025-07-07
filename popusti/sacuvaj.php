<?php
require '../partials/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $naziv = $conn->real_escape_string($_POST['naziv']);
    $opis = $conn->real_escape_string($_POST['opis'] ?? '');
    $tip_uslova = $conn->real_escape_string($_POST['tip_uslova']);

    // Priprema uslova u zavisnosti od tipa
    $uslov = ['tip' => $tip_uslova];

    switch($tip_uslova) {
        case 'poklon_na_cv':
            $uslov['vrsta'] = $_POST['vrsta_poklona'];
            $uslov['cv_kolicina'] = $_POST['cv_kolicina'];
            $uslov['poklon_proizvodi'] = $_POST['poklon_proizvodi'];
            break;

        case 'kolicinski_popust':
            $uslov['min_cv'] = $_POST['min_cv'];
            $uslov['procenat_popusta'] = $_POST['procenat_popusta'];
            break;

        case 'lojalnost':
            $uslov['procenat_povracaja'] = $_POST['procenat_povracaja'];
            break;

        case 'buster':
            $uslov['cv_kolicina'] = $_POST['cv_kolicina'];
            $uslov['procenat_povracaja'] = $_POST['procenat_povracaja'];
            $uslov['poklon_proizvodi'] = $_POST['poklon_proizvodi'];
            break;
    }

    $uslovi_json = json_encode($uslov);

    if ($id > 0) {
        // Ažuriranje postojećeg
        $sql = "UPDATE popusti_pokloni SET naziv=?, opis=?, uslovi=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $naziv, $opis, $uslovi_json, $id);
    } else {
        // Dodavanje novog
        $sql = "INSERT INTO popusti_pokloni (naziv, opis, uslovi, aktivan) VALUES (?, ?, ?, 1)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $naziv, $opis, $uslovi_json);
    }

    if ($stmt->execute()) {
        $_SESSION['success'] = "Uslov uspešno " . ($id > 0 ? "ažuriran" : "dodat");
    } else {
        $_SESSION['error'] = "Greška: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
}

header("Location: index.php");
exit;
?>