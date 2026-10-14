<?php
session_start();
require_once 'config/koneksi.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Menggunakan trim() agar tidak ada spasi tidak sengaja saat mengetik email
    $email    = trim(mysqli_real_escape_string($koneksi, $_POST['email']));
    $password = $_POST['password'];

    $query  = "SELECT * FROM users WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($koneksi, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // Verifikasi password (Mendukung password_verify atau teks biasa 'password123')
        if (password_verify($password, $user['password']) || $password === $user['password']) {
            $_SESSION['login']    = true;
            $_SESSION['id']       = $user['id'];
            $_SESSION['user_id']  = $user['id'];
            // Mengambil kolom 'name' sesuai struktur tabel di phpMyAdmin
            $_SESSION['username'] = !empty($user['name']) ? $user['name'] : $user['email'];
            
            // Ambil role tepat dari database (strtolower agar aman dari kapitalisasi)
            $role = strtolower(trim($user['role']));
            $_SESSION['role'] = $role;

            // Pengarahan halaman berdasarkan Role
            if ($role === 'owner') {
                header("Location: index.php"); // Diarahkan ke Dashboard Owner
                exit();
            } else {
                header("Location: index.php"); // Diarahkan ke Dashboard Admin
                exit();
            }
        } else {
            $error = "Sandi akses / password yang Anda masukkan salah!";
        }
    } else {
        $error = "Akun dengan email tersebut tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Executive Galaxy Command Center</title>
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            /* Latar Belakang Galaksi Kosmik Mewah */
            background: linear-gradient(rgba(0, 0, 5, 0.45), rgba(2, 2, 12, 0.65)), 
                        url('https://images.unsplash.com/photo-1543722530-d2c3201371e7?q=80&w=2500&auto=format&fit=crop') no-repeat center center fixed;
            background-size: cover;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Kartu Login Glassmorphism Hitam Emas */
        .card-login {
            background: rgba(15, 18, 28, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(234, 179, 8, 0.4);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.9), 0 0 30px rgba(234, 179, 8, 0.2);
            width: 100%;
            max-width: 420px;
            padding: 40px;
        }

        /* Input Kustom Kosmik */
        .form-control {
            background-color: rgba(10, 10, 15, 0.9);
            border: 1px solid rgba(234, 179, 8, 0.4);
            color: #f8fafc;
            border-radius: 12px;
            padding: 12px 15px;
        }
        .form-control:focus {
            background-color: rgba(10, 10, 15, 0.95);
            border-color: #fbbf24;
            color: #f8fafc;
            box-shadow: 0 0 15px rgba(251, 191, 36, 0.4);
        }

        /* Tombol Emas Bersinar Terang */
        .btn-gold-glow {
            background: linear-gradient(135deg, #fbbf24 0%, #d97706 100%);
            border: 1px solid #fef08a;
            border-radius: 12px;
            font-weight: 700;
            color: #000000;
            padding: 12px;
            box-shadow: 0 0 20px rgba(251, 191, 36, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.6);
            transition: all 0.3s ease;
        }
        .btn-gold-glow:hover {
            background: linear-gradient(135deg, #fcd34d 0%, #b45309 100%);
            color: #000000;
            box-shadow: 0 0 30px rgba(251, 191, 36, 0.9), inset 0 1px 0 rgba(255, 255, 255, 0.8);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<div class="card-login">
    <div class="text-center mb-4">
        <div class="mb-2 text-warning fs-1">
            <i class="fa-solid fa-crown"></i>
        </div>
        <h3 class="fw-bold text-white mb-1">Executive Access</h3>
        <p class="text-secondary small">Masuk ke pusat kendali galaksi kosmik</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger bg-dark border border-danger text-danger text-center small rounded-3 py-2 mb-3">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label class="form-label text-secondary small fw-bold">Email Enterprise</label>
            <div class="input-group">
                <span class="input-group-text bg-dark border-secondary text-warning"><i class="fa-solid fa-envelope"></i></span>
                <input type="email" name="email" class="form-control" placeholder="owner@bisnis.com" required>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label text-secondary small fw-bold">Sandi Akses</label>
            <div class="input-group">
                <span class="input-group-text bg-dark border-secondary text-warning"><i class="fa-solid fa-lock"></i></span>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
        </div>
        <button type="submit" class="btn btn-gold-glow w-100 fw-bold">
            <i class="fa-solid fa-right-to-bracket me-2"></i> Otorisasi Masuk
        </button>
    </form>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>