<?php
require_once 'config/koneksi.php';

// Buat hash password yang valid dari PHP
$pass_hash = password_hash('password123', PASSWORD_BCRYPT);

// Update ke database
$query = "UPDATE users SET password = '$pass_hash'";
if (mysqli_query($koneksi, $query)) {
    echo "Password berhasil di-reset menjadi: <b>password123</b><br>";
    echo "<a href='login.php'>Klik di sini untuk Login</a>";
} else {
    echo "Gagal reset: " . mysqli_error($koneksi);
}
?>