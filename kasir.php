<?php
session_start();
require_once 'config/koneksi.php';

// Proteksi session login
if ((!isset($_SESSION['login']) || $_SESSION['login'] !== true) && !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';

// Proses Transaksi Kasir
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_transaksi'])) {
    $product_id = (int)$_POST['product_id'];
    $qty        = (int)$_POST['qty'];

    // Ambil detail produk untuk hitung total & profit
    $q_prod = mysqli_query($koneksi, "SELECT * FROM products WHERE id = $product_id");
    $prod   = mysqli_fetch_assoc($q_prod);

    if ($prod) {
        if ($prod['stock'] >= $qty) {$hpp          = $prod['buy_price'];$price        = $prod['sell_price'];$total_amount = $price * $qty;
            $total_profit = ($price - $hpp) *$qty;
            $user_id      = isset($_SESSION['id']) ?$_SESSION['id'] : 1; 
            $trx_date     = date('Y-m-d H:i:s');
            
            // Membuat nomor invoice unik dengan tambahan angka acak (mt_rand)
            $invoice_no   = 'INV-' . date('YmdHis') . '-' . mt_rand(100, 999);

            // 1. Simpan Transaksi Penjualan Utama
            $q_insert = "INSERT INTO sales (invoice_number, user_id, total_amount, total_profit, transaction_date) 
                         VALUES ('$invoice_no', $user_id,$total_amount, $total_profit, '$trx_date')";
                     
            if (mysqli_query($koneksi,$q_insert)) {
                // Ambil ID dari transaksi sales yang baru saja masuk
                $sale_id = mysqli_insert_id($koneksi);

                // 2. Simpan detail produk ke tabel sale_details (Untuk Produk Paling Laris)
                $q_detail = "INSERT INTO sale_details (sale_id, product_id, quantity, subtotal) 
                             VALUES ($sale_id,$product_id, $qty,$total_amount)";
                mysqli_query($koneksi,$q_detail);

                // 3. Potong Stok Barang
                mysqli_query($koneksi, "UPDATE products SET stock = stock - $qty WHERE id =$product_id");

                $message = "<div class='alert alert-success bg-dark border border-warning text-warning shadow-sm rounded-3 mb-4'>
                                <i class='fa-solid fa-circle-check me-2'></i> <strong>Transaksi Berhasil!</strong> Stok barang berkurang $qty unit. Total: Rp " . number_format($total_amount, 0, ',', '.') . "
                            </div>";
            } else {
                $message = "<div class='alert alert-danger bg-dark border border-danger text-danger shadow-sm rounded-3 mb-4'>Gagal menyimpan transaksi: " . mysqli_error($koneksi) . "</div>";
            }
        } else {
            $message = "<div class='alert alert-danger bg-dark border border-danger text-danger shadow-sm rounded-3 mb-4'>Stok tidak cukup! Stok yang tersedia hanya " . $prod['stock'] . " unit.</div>";
        }
    }
}

// Ambil Daftar Produk yang Stoknya Ada
$products = mysqli_query($koneksi, "SELECT * FROM products WHERE stock > 0 ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Dashboard - Kasir</title>
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            /* Latar Belakang Galaksi Andromeda Spiral (Murni tanpa animasi jatuh) */
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

        /* Kartu Glassmorphism Emas Kosmik */
        .card-gold {
            background: rgba(15, 18, 28, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(234, 179, 8, 0.35);
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.85), 0 0 25px rgba(234, 179, 8, 0.15);
        }

        /* Input & Select Kustom */
        .form-control, .form-select {
            background-color: rgba(10, 10, 15, 0.9);
            border: 1px solid rgba(234, 179, 8, 0.4);
            color: #f8fafc;
            border-radius: 10px;
        }
        .form-control:focus, .form-select:focus {
            background-color: rgba(10, 10, 15, 0.95);
            border-color: #fbbf24;
            color: #f8fafc;
            box-shadow: 0 0 15px rgba(251, 191, 36, 0.4);
        }
        .form-select option {
            background-color: #0f172a;
            color: #f8fafc;
        }

        /* Tombol Emas Bersinar Terang */
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
    </style>
</head>
<body>

<!-- Navbar Utama -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-gold py-3 shadow-lg">
    <div class="container">
        <span class="navbar-brand mb-0 h1 fw-bold">
            <i class="fa-solid fa-star me-2 text-warning"></i>Executive Dashboard - Kasir
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
        <i class="fa-solid fa-cart-shopping text-warning"></i> Input Transaksi Penjualan
    </h2>

    <?php echo $message; ?>

    <div class="row g-4">
        <!-- Form Transaksi Kasir -->
        <div class="col-md-5">
            <div class="card card-gold p-4">
                <h5 class="card-title fw-bold mb-3 text-warning">Form Kasir Penjualan</h5>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Pilih Produk</label>
                        <select name="product_id" class="form-select" required>
                            <option value="">-- Pilih Barang --</option>
                            <?php if ($products && mysqli_num_rows($products) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($products)): ?>
                                    <option value="<?= $row['id']; ?>">
                                        <?= htmlspecialchars($row['name']); ?> (Rp <?= number_format($row['sell_price'], 0, ',', '.'); ?>) - Stok: <?=$row['stock']; ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Jumlah Beli (Qty)</label>
                        <input type="number" name="qty" class="form-control" value="1" min="1" required>
                    </div>
                    <button type="submit" name="submit_transaksi" class="btn btn-gold-glow w-100 fw-bold py-2 mt-2">
                        <i class="fa-solid fa-bolt me-1"></i> Proses Transaksi
                    </button>
                </form>
            </div>
        </div>

        <!-- Info / Tips -->
        <div class="col-md-7">
            <div class="card card-gold p-4 h-100">
                <h5 class="fw-bold mb-3 text-warning d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-info"></i> Petunjuk Penggunaan Kasir
                </h5>
                <ul class="text-secondary small mb-0 lh-lg">
                    <li class="mb-2">Pilih barang dari daftar inventaris yang tersedia di sistem.</li>
                    <li class="mb-2">Masukkan jumlah unit (`qty`) yang dibeli oleh pelanggan.</li>
                    <li class="mb-2">Sistem akan <strong class="text-warning">otomatis memotong stok</strong> produk di database secara instan.</li>
                    <li class="mb-2">Data penjualan produk akan otomatis masuk ke tabel Produk Paling Laris di dashboard Owner.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>