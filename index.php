<?php
// 1. Inisialisasi/Memulai Session
session_start();

// 2. PROTEKSI HALAMAN (Session Guard)
if ((!isset($_SESSION['login']) || $_SESSION['login'] !== true) && !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 3. Hubungkan ke database
require_once 'config/koneksi.php';

// 4. Ambil data user dari session
$role = $_SESSION['role'] ?? 'admin';
$name = $_SESSION['username'] ?? $_SESSION['name'] ?? 'User';

// 5. QUERY KHUSUS OWNER (Executive View)
if ($role == 'owner') {
    // Total Omset Penjualan
    $q_omset = mysqli_query($koneksi, "SELECT SUM(total_amount) AS total FROM sales");
    $d_omset = mysqli_fetch_assoc($q_omset);
    $total_omset = $d_omset['total'] ?? 0;

    // Total Profit Murni
    $q_profit = mysqli_query($koneksi, "SELECT SUM(total_profit) AS total FROM sales");
    $d_profit = mysqli_fetch_assoc($q_profit);
    $total_profit = $d_profit['total'] ?? 0;

    // Total Pengeluaran
    $q_expense = mysqli_query($koneksi, "SELECT SUM(amount) AS total FROM expenses");
    $d_expense = mysqli_fetch_assoc($q_expense);
    $total_expense = $d_expense['total'] ?? 0;

    // Net Profit (Laba Bersih)
    $net_profit = $total_profit - $total_expense;

    // Fitur Filter & Pencarian Riwayat Transaksi
    $search_keyword = isset($_GET['keyword']) ? mysqli_real_escape_string($koneksi, $_GET['keyword']) : '';
    $filter_date    = isset($_GET['tanggal']) ? mysqli_real_escape_string($koneksi, $_GET['tanggal']) : '';

    $where_clause = "WHERE 1=1";
    if (!empty($search_keyword)) {
        $where_clause .= " AND (p.name LIKE '%$search_keyword%' OR s.invoice_number LIKE '%$search_keyword%')";
    }
    if (!empty($filter_date)) {
        $where_clause .= " AND DATE(s.transaction_date) = '$filter_date'";
    }

    // Query Detail Transaksi Masuk (Joined dengan Produk & User)
    $q_transaksi = mysqli_query($koneksi, "
        SELECT s.invoice_number, s.transaction_date, p.name AS product_name, sd.quantity, sd.subtotal, u.name AS kasir_name
        FROM sales s
        JOIN sale_details sd ON s.id = sd.sale_id
        JOIN products p ON sd.product_id = p.id
        LEFT JOIN users u ON s.user_id = u.id
        $where_clause
        ORDER BY s.transaction_date DESC
    ");

    // Query Produk Paling Banyak Terjual (Top Selling Products)
    $q_top_products = mysqli_query($koneksi, "
        SELECT p.name AS product_name, SUM(sd.quantity) AS total_terjual, SUM(sd.subtotal) AS total_pendapatan
        FROM sale_details sd
        JOIN products p ON sd.product_id = p.id
        GROUP BY sd.product_id
        ORDER BY total_terjual DESC
        LIMIT 5
    ");

    // Query Grafik Tren
    $q_chart = mysqli_query($koneksi, "
        SELECT DATE_FORMAT(transaction_date, '%d %b') AS tanggal, 
               SUM(total_amount) AS omset, 
               SUM(total_profit) AS profit 
        FROM sales 
        GROUP BY DATE_FORMAT(transaction_date, '%d %b'), DATE(transaction_date)
        ORDER BY DATE(transaction_date) ASC LIMIT 7
    ");

    $chart_labels = [];
    $chart_omset  = [];
    $chart_profit = [];

    if ($q_chart) {
        while ($row = mysqli_fetch_assoc($q_chart)) {
            $chart_labels[] = $row['tanggal'];
            $chart_omset[]  = $row['omset'];
            $chart_profit[] = $row['profit'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Dashboard - Owner Command Center</title>
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(rgba(0, 0, 5, 0.45), rgba(2, 2, 12, 0.65)), 
                        url('https://images.unsplash.com/photo-1543722530-d2c3201371e7?q=80&w=2500&auto=format&fit=crop') no-repeat center center fixed;
            background-size: cover;
            color: #e2e8f0;
            min-height: 100vh;
        }

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

        .card-gold {
            background: rgba(15, 18, 28, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(234, 179, 8, 0.35);
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.85), 0 0 25px rgba(234, 179, 8, 0.15);
        }

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
            box-shadow: 0 0 30px rgba(251, 191, 36, 0.9);
            transform: translateY(-2px);
        }

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

    <!-- Navbar Header -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-gold py-3 shadow-lg">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
                <i class="fa-solid fa-crown text-warning"></i> Executive Command Center
            </a>
            <div class="d-flex align-items-center text-white">
                <span class="me-3 text-secondary small">Halo, <strong class="text-warning"><?= htmlspecialchars($name); ?></strong> (<span class="text-info"><?= strtoupper($role); ?></span>)</span>
                <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container my-5">

        <?php if ($role == 'owner'): ?>
            <!-- ================= HANYA TAMPIL UNTUK OWNER ================= -->
            <h3 class="mb-4 fw-bold text-white d-flex align-items-center gap-2">
                <i class="fa-solid fa-chart-line text-warning"></i> Ringkasan Kinerja Bisnis & Pemantauan
            </h3>
            
            <!-- Kartu Statistik -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card card-gold p-4 h-100">
                        <div class="text-secondary small fw-bold mb-1">Total Omset Penjualan</div>
                        <h4 class="fw-bold text-white mb-0">Rp <?= number_format($total_omset, 0, ',', '.'); ?></h4>
                        <div class="text-warning small mt-2"><i class="fa-solid fa-arrow-trend-up me-1"></i> Akumulasi kotor</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-gold p-4 h-100">
                        <div class="text-secondary small fw-bold mb-1">Total Profit Murni</div>
                        <h4 class="fw-bold text-success mb-0">Rp <?= number_format($total_profit, 0, ',', '.'); ?></h4>
                        <div class="text-success small mt-2"><i class="fa-solid fa-circle-check me-1"></i> Keuntungan produk</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-gold p-4 h-100">
                        <div class="text-secondary small fw-bold mb-1">Total Pengeluaran</div>
                        <h4 class="fw-bold text-danger mb-0">Rp <?= number_format($total_expense, 0, ',', '.'); ?></h4>
                        <div class="text-danger small mt-2"><i class="fa-solid fa-wallet me-1"></i> Beban operasional</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-gold p-4 h-100">
                        <div class="text-secondary small fw-bold mb-1">Net Profit (Laba Bersih)</div>
                        <h4 class="fw-bold text-warning mb-0">Rp <?= number_format($net_profit, 0, ',', '.'); ?></h4>
                        <div class="text-warning small mt-2"><i class="fa-solid fa-star me-1"></i> Hasil akhir bersih</div>
                    </div>
                </div>
            </div>

            <!-- Grafik Tren -->
            <div class="card card-gold p-4 mb-4">
                <h5 class="fw-bold mb-3 text-warning d-flex align-items-center gap-2">
                    <i class="fa-solid fa-chart-area"></i> Grafik Tren Omset & Profit
                </h5>
                <canvas id="businessChart" height="90"></canvas>
            </div>

            <!-- SISI PEMANTAUAN: Produk Terlaris & Filter Transaksi -->
            <div class="row g-4 mb-4">
                <!-- Produk Paling Banyak Terjual -->
                <div class="col-md-5">
                    <div class="card card-gold p-4 h-100">
                        <h5 class="fw-bold mb-3 text-warning d-flex align-items-center gap-2">
                            <i class="fa-solid fa-fire"></i> Produk Paling Banyak Terjual
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-gold align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Nama Produk</th>
                                        <th class="text-center">Terjual</th>
                                        <th>Total Pendapatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($q_top_products && mysqli_num_rows($q_top_products) > 0): ?>
                                        <?php while ($tp = mysqli_fetch_assoc($q_top_products)): ?>
                                            <tr>
                                                <td class="fw-bold text-dark"><?= htmlspecialchars($tp['product_name']); ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-dark border border-warning text-warning px-2 py-1">
                                                        <?= $tp['total_terjual']; ?> Unit
                                                    </span>
                                                </td>
                                                <td class="text-warning">Rp <?= number_format($tp['total_pendapatan'], 0, ',', '.'); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-secondary py-3">Belum ada data produk terjual.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Form Filter & Pencarian Riwayat -->
                <div class="col-md-7">
                    <div class="card card-gold p-4 h-100">
                        <h5 class="fw-bold mb-3 text-warning d-flex align-items-center gap-2">
                            <i class="fa-solid fa-filter"></i> Filter & Cari Riwayat Transaksi
                        </h5>
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label text-secondary small fw-bold">Cari Nama Barang / Invoice</label>
                                <input type="text" name="keyword" class="form-control" placeholder="Contoh: Beras, INV-..." value="<?= htmlspecialchars($search_keyword); ?>">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label text-secondary small fw-bold">Filter Tanggal</label>
                                <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($filter_date); ?>">
                            </div>
                            <div class="col-12 d-flex gap-2">
                                <button type="submit" class="btn btn-gold-glow px-4 py-2">
                                    <i class="fa-solid fa-magnifying-glass me-1"></i> Cari Data
                                </button>
                                <a href="index.php" class="btn btn-outline-warning px-3 py-2">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tabel Detail Log Transaksi Masuk -->
            <div class="card card-gold p-4">
                <h5 class="fw-bold mb-3 text-warning d-flex align-items-center gap-2">
                    <i class="fa-solid fa-receipt"></i> Log Detail Penjualan Barang Masuk
                </h5>
                <div class="table-responsive">
                    <table class="table table-gold align-middle mb-0">
                        <thead>
                            <tr>
                                <th>No. Invoice</th>
                                <th>Tanggal & Waktu</th>
                                <th>Nama Barang</th>
                                <th class="text-center">Qty</th>
                                <th>Subtotal</th>
                                <th>Kasir / Petugas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($q_transaksi && mysqli_num_rows($q_transaksi) > 0): ?>
                                <?php while ($trx = mysqli_fetch_assoc($q_transaksi)): ?>
                                    <tr>
                                        <td class="text-warning fw-bold"><?= htmlspecialchars($trx['invoice_number']); ?></td>
                                        <td class="text-secondary small"><?= date('d M Y, H:i', strtotime($trx['transaction_date'])); ?></td>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($trx['product_name']); ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-dark border border-warning text-warning">
                                                <?= $trx['quantity']; ?> Unit
                                            </span>
                                        </td>
                                        <td class="text-warning fw-bold">Rp <?= number_format($trx['subtotal'], 0, ',', '.'); ?></td>
                                        <td><span class="text-info"><?= htmlspecialchars($trx['kasir_name'] ?? 'Admin'); ?></span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-secondary py-4">Tidak ada riwayat transaksi yang cocok dengan pencarian.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <script>
                const ctx = document.getElementById('businessChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?= json_encode($chart_labels); ?>,
                        datasets: [
                            {
                                label: 'Omset (Rp)',
                                data: <?= json_encode($chart_omset); ?>,
                                borderColor: '#fbbf24',
                                backgroundColor: 'rgba(251, 191, 36, 0.1)',
                                fill: true,
                                tension: 0.3,
                                borderWidth: 3
                            },
                            {
                                label: 'Profit (Rp)',
                                data: <?= json_encode($chart_profit); ?>,
                                borderColor: '#34d399',
                                backgroundColor: 'rgba(52, 211, 153, 0.1)',
                                fill: true,
                                tension: 0.3,
                                borderWidth: 3
                            }
                        ]
                    },
                    options: { 
                        responsive: true,
                        plugins: {
                            legend: { labels: { color: '#e2e8f0', font: { family: 'Inter' } } }
                        },
                        scales: {
                            x: { grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: '#94a3b8' } },
                            y: { grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: '#94a3b8' } }
                        }
                    }
                });
            </script>

        <?php else: ?>
            <!-- ================= HANYA TAMPIL UNTUK ADMIN ================= -->
            <div class="text-center mb-5">
                <h2 class="fw-bold text-white display-6 mb-2">Panel Operasional Kosmik</h2>
                <p class="text-secondary">Pusat kendali enterprise dengan visualisasi galaksi spiral dan taburan bintang tanpa batas.</p>
            </div>

            <div class="row g-4 justify-content-center">
                <div class="col-md-4">
                    <div class="card card-gold p-4 text-center h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="mb-3 text-warning fs-1"><i class="fa-solid fa-boxes-stacked"></i></div>
                            <h5 class="fw-bold text-dark mb-2">Kelola Produk & Stok</h5>
                            <p class="text-secondary small mb-4">Tambah, edit, atau update inventaris logistik barang.</p>
                        </div>
                        <a href="products.php" class="btn btn-gold-glow py-2 w-100">
                            <i class="fa-solid fa-arrow-right-to-bracket me-2"></i> Kelola Barang
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-gold p-4 text-center h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="mb-3 text-warning fs-1"><i class="fa-solid fa-cash-register"></i></div>
                            <h5 class="fw-bold text-dark mb-2">Input Transaksi Penjualan</h5>
                            <p class="text-secondary small mb-4">Catat transaksi kasir pelanggan baru secara instan.</p>
                        </div>
                        <a href="kasir.php" class="btn btn-gold-glow py-2 w-100">
                            <i class="fa-solid fa-cart-shopping me-2"></i> Buka Kasir
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-gold p-4 text-center h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="mb-3 text-warning fs-1"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                            <h5 class="fw-bold text-dark mb-2">Catat Pengeluaran</h5>
                            <p class="text-secondary small mb-4">Input biaya operasional & tagihan bisnis harian.</p>
                        </div>
                        <a href="expenses.php" class="btn btn-gold-glow py-2 w-100">
                            <i class="fa-solid fa-wallet me-2"></i> Input Pengeluaran
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>