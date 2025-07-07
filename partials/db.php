<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lojalnost";

// Kreiranje konekcije
$conn = new mysqli($servername, $username, $password, $dbname);

// Provera konekcije
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>