<?php
$host     = "localhost";
$user     = "root";
$password = ""; // Default Laragon biasanya kosong
$database = "db_owner_dashboard";

$koneksi = mysqli_connect($host, $user, $password, $database);

// Cek apakah koneksi berhasil
if (!$koneksi) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}
?>