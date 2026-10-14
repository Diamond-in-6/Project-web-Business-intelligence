<?php
session_start();
require_once 'config/koneksi.php';

// Proteksi session login
if ((!isset($_SESSION['login']) || $_SESSION['login'] !== true) && !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';

// Proses Simpan Pengeluaran
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_expense'])) {
    $description = mysqli_real_escape_string($koneksi, $_POST['description']);
    $amount      = (float)$_POST['amount'];
    $expense_date= date('Y-m-d H:i:s');
    $user_id     = isset($_SESSION['id']) ? $_SESSION['id'] : 1;

    $query = "INSERT INTO expenses (id, description, amount, expense_date) VALUES ($user_id, '$description', $amount, '$expense_date')";
    
    if (mysqli_query($koneksi, $query)) {
        $message = "<div class='alert alert-success bg-dark border border-warning text-warning shadow-sm rounded-3 mb-4'>
                        <i class='fa-solid fa-circle-check me-2'></i> <strong>Berhasil!</strong> Pengeluaran operasional berhasil dicatat ke sistem.
                    </div>";
    } else {
        $message = "<div class='alert alert-danger bg-dark border border-danger text-danger shadow-sm rounded-3 mb-4'>Gagal menyimpan pengeluaran: " . mysqli_error($koneksi) . "</div>";
    }
}

// Proses Hapus Pengeluaran (Opsional jika ada fitur hapus)
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($koneksi, "DELETE FROM expenses WHERE id = $id");
    header("Location: expenses.php");
    exit();
}

// Ambil Riwayat Pengeluaran
$expenses = mysqli_query($koneksi, "SELECT * FROM expenses ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Dashboard - Pengeluaran</title>
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            /* Latar Belakang Galaksi Andromeda Spiral yang Megah & Indah */
            background: linear-gradient(rgba(0, 0, 5, 0.45), rgba(2, 2, 12, 0.65)), 
                        url('https://images.unsplash.com/photo-1543722530-d2c3201371e7?q=80&w=2500&auto=format&fit=crop') no-repeat center center fixed;
            background-size: cover;
            color: #e2e8f0;
            min-height: 100vh;
        }

        /* Navbar Kosmik Mewah Emas */
        .navbar-gold {
            background: rgba(10, 10, 15, 0.85);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-bottom: 1px solid rgba(234, 179, 8, 0.35);
        }
        .navbar-brand {
            color: #fbbf24 !important;
            text-shadow: 0 0 12px rgba(251, 191, 36, 0.6);
        }

        /* Kartu Panel Emas Glassmorphism */
        .card-gold {
            background: rgba(15, 18, 28, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(234, 179, 8, 0.35);
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.85), 0 0 25px rgba(234, 179, 8, 0.15);
        }

        /* Form Control Elegan */
        .form-control {
            background-color: rgba(10, 10, 15, 0.9);
            border: 1px solid rgba(234, 179, 8, 0.4);
            color: #f8fafc;
            border-radius: 10px;
        }
        .form-control:focus {
            background-color: rgba(10, 10, 15, 0.95);
            border-color: #fbbf24;
            color: #f8fafc;
            box-shadow: 0 0 15px rgba(251, 191, 36, 0.4);
        }

        /* Tombol Emas Bersinar Terang (Glowing Gold Button) */
        .btn-gold-glow {
            background: linear-gradient(135deg, #fbbf24 0%, #d97706 100%);
            border: 1px solid #fef08a;
            border-radius: 10px;
            font-weight: 700;
            color: #000000;
            box-shadow: 0 0 20px rgba(251, 191, 36, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.6);
            transition: all 0.3s ease;
        }
        .btn-gold-glow:hover {
            background: linear-gradient(135deg, #fcd34d 0%, #b45309 100%);
            color: #000000;
            box-shadow: 0 0 30px rgba(251, 191, 36, 0.9), inset 0 1px 0 rgba(255, 255, 255, 0.8);
            transform: translateY(-2px);
        }

        /* Tabel Kustom Hitam Emas */
        .table-gold {
            color: #f8fafc;
            vertical-align: middle;
        }
        .table-gold th {
            background-color: #0a0a0f !important;
            color: #fbbf24;
            font-weight: 600;
            border-bottom: 1px solid rgba(234, 179, 8, 0.4);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }
        .table-gold tbody tr {
            background-color: rgba(18, 18, 25, 0.6);
            transition: background-color 0.2s ease;
        }
        .table-gold tbody tr:hover {
            background-color: rgba(30, 30, 42, 0.8);
        }
        .table-gold td {
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            color: #e2e8f0;
        }
    </style>
</head>
<body>

<!-- Navbar Utama -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-gold py-3 shadow-lg">
    <div class="container">
        <span class="navbar-brand mb-0 h1 fw-bold">
            <i class="fa-solid fa-crown me-2 text-warning"></i>Executive Dashboard - Pengeluaran
        </span>
        <div>
            <a href="index.php" class="btn btn-outline-warning btn-sm me-2 rounded-pill px-3 fw-bold">Kembali ke Dashboard</a>
            <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">Logout</a>
        </div>
    </div>
</nav>

<!-- Main Container -->
<div class="container mb-5 py-4">
    <h2 class="fw-bold mb-4 text-white d-flex align-items-center gap-2">
        <i class="fa-solid fa-coins text-warning"></i> Catat Pengeluaran Operasional
    </h2>

    <?php echo $message; ?>

    <div class="row g-4">
        <!-- Form Input Pengeluaran (Sisi Kiri) -->
        <div class="col-md-4">
            <div class="card card-gold p-4">
                <h5 class="fw-bold mb-3 text-warning">Input Biaya Operasional</h5>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Deskripsi Pengeluaran</label>
                        <input type="text" name="description" class="form-control" placeholder="Contoh: Bayar Listrik, Gaji Karyawan" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-secondary small fw-bold">Nominal (Rp)</label>
                        <input type="number" name="amount" class="form-control" placeholder="150000" min="0" required>
                    </div>
                    <button type="submit" name="submit_expense" class="btn btn-gold-glow w-100 py-2">
                        <i class="fa-solid fa-paper-plane me-1"></i> Simpan Pengeluaran
                    </button>
                </form>
            </div>
        </div>

        <!-- Riwayat Pengeluaran (Sisi Kanan) -->
        <div class="col-md-8">
            <div class="card card-gold p-4">
                <h5 class="fw-bold mb-3 text-warning">Riwayat Biaya Operasional</h5>
                <div class="table-responsive">
                    <table class="table table-gold align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Deskripsi</th>
                                <th>Nominal</th>
                                <th>Tanggal Input</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($expenses && mysqli_num_rows($expenses) > 0): ?>
                                <?php $no = 1; while ($row = mysqli_fetch_assoc($expenses)): ?>
                                    <tr>
                                        <td class="text-warning fw-bold">#<?= $no++; ?></td>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($row['description']); ?></td>
                                        <td class="text-warning fw-bold">Rp <?= number_format($row['amount'], 0, ',', '.'); ?></td>
                                        <td class="text-secondary small"><?= date('d M Y, H:i', strtotime($row['expense_date'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-secondary py-4">Belum ada data pengeluaran operasional tercatat.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>