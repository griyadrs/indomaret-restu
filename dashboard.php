<?php
require_once 'config.php';

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_role = $_SESSION['role'] ?? 'cashier'; // Asumsi ada role di session, jika tidak default cashier
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - POS System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Note: Terapkan Perinsip DRY pada navbar-->
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="#"><i class="bi bi-speedometer2"></i> POS System</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="pos.php"><i class="bi bi-shop"></i> Kasir / POS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="inventory.php"><i class="bi bi-box-seam"></i> Stok Produk</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="vouchers.php"><i class="bi bi-ticket-perforated"></i> Voucher</a>
                </li>
            </ul>
            <div class="d-flex text-white align-items-center">
                <span class="me-3">Halo, <strong><?= htmlspecialchars($_SESSION['first_name'] ?? 'User') ?></strong></span>
                <a href="logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="container">
    <h2 class="mb-4">Ringkasan Hari Ini <small class="text-muted fs-6"><?= date('d F Y') ?></small></h2>

    <!-- Cards Stats -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Omzet -->
        <div class="col-md-4">
            <div class="card text-white bg-primary shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Omzet Penjualan</h6>
                            <h2 class="mt-2 mb-0" id="statRevenue">Rp 0</h2>
                        </div>
                        <i class="bi bi-cash-coin fs-1 opacity-50"></i>
                    </div>
                    <small>Total bersih setelah diskon</small>
                </div>
            </div>
        </div>

        <!-- Card 2: Transaksi -->
        <div class="col-md-4">
            <div class="card text-white bg-success shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Jumlah Transaksi</h6>
                            <h2 class="mt-2 mb-0" id="statTx">0</h2>
                        </div>
                        <i class="bi bi-receipt fs-1 opacity-50"></i>
                    </div>
                    <small>Transaksi berhasil hari ini</small>
                </div>
            </div>
        </div>

        <!-- Card 3: Item Terjual -->
        <div class="col-md-4">
            <div class="card text-white bg-warning shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Item Terjual</h6>
                            <h2 class="mt-2 mb-0" id="statItem">0</h2>
                        </div>
                        <i class="bi bi-bag-check fs-1 opacity-50"></i>
                    </div>
                    <small>Total qty produk keluar</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions Table -->
    <div class="card shadow">
        <div class="card-header bg-white">
            <h5 class="mb-0">5 Transaksi Terakhir</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead class="table-light">
                    <tr>
                        <th>ID TRX</th>
                        <th>Waktu</th>
                        <th>Kasir</th>
                        <th>Pelanggan</th>
                        <th class="text-end">Total Belanja</th>
                        <th class="text-end">Diskon</th>
                        <th class="text-end">Total Bayar</th>
                    </tr>
                    </thead>
                    <tbody id="recentTxBody">
                    <tr><td colspan="7" class="text-center">Memuat data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    $(document).ready(function() {
        loadDashboardStats();

        // Auto refresh setiap 30 detik
        setInterval(loadDashboardStats, 30000);
    });

    function loadDashboardStats() {
        $.get('api.php?action=get_dashboard_stats', function(res) {
            if(res.status === 'success') {
                // Update Cards
                $('#statRevenue').text('Rp ' + parseInt(res.data.revenue).toLocaleString());
                $('#statTx').text(res.data.total_transactions);
                $('#statItem').text(res.data.items_sold);

                // Update Table
                let html = '';
                if(res.data.recent_transactions.length === 0) {
                    html = '<tr><td colspan="7" class="text-center text-muted">Belum ada transaksi hari ini.</td></tr>';
                } else {
                    res.data.recent_transactions.forEach(tx => {
                        let subtotal = parseInt(tx.subtotal);
                        let discount = parseInt(tx.discount_amount);
                        let final = subtotal - discount;
                        let customer = tx.customer_name ? tx.customer_name : '<span class="text-muted">-</span>';

                        html += `
                            <tr>
                                <td>#${tx.id}</td>
                                <td>${tx.created_at}</td>
                                <td>${tx.username}</td>
                                <td>${customer}</td>
                                <td class="text-end">Rp ${subtotal.toLocaleString()}</td>
                                <td class="text-end text-danger">-Rp ${discount.toLocaleString()}</td>
                                <td class="text-end fw-bold text-success">Rp ${final.toLocaleString()}</td>
                            </tr>
                            `;
                    });
                }
                $('#recentTxBody').html(html);
            }
        }, 'json');
    }
</script>
</body>
</html>