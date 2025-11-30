<?php
global $pdo;
require_once 'config.php';

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['first_name'];

// Cek Shift Terbuka
$stmt = $pdo->prepare("SELECT id FROM cashier_shifts WHERE user_id = ? AND status = 'open' LIMIT 1");
$stmt->execute([$user_id]);
$currentShift = $stmt->fetch();
$shiftIsOpen = $currentShift ? true : false;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .product-card { cursor: pointer; transition: 0.2s; }
        .product-card:hover { transform: translateY(-2px); border-color: #0d6efd; }
        .cart-list { height: calc(100vh - 380px); overflow-y: auto; }
        .product-grid-container { height: calc(100vh - 200px); overflow-y: auto; overflow-x: hidden; }
        .total-section { background-color: #f8f9fa; border-top: 2px solid #dee2e6; }
    </style>
</head>
<body class="bg-light">

<!-- Note: Terapkan Perinsip DRY pada navbar-->
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="#"><i class="bi bi-shop"></i> POS System</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="pos.php"><i class="bi bi-shop"></i> Kasir / POS</a>
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

<div class="container-fluid">
    <div class="row">
        <!-- LEFT: Product Search & List -->
        <div class="col-md-7">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchProduct" class="form-control" placeholder="Scan Barcode / Cari Nama Produk..." autofocus>
                    </div>
                </div>
            </div>

            <!-- Product Grid -->
            <div class="product-grid-container px-2">
                <div id="productList" class="row g-3">
                    <div class="col-12 text-center mt-5">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="row mt-4 mb-3">
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <button class="btn btn-secondary" id="btnPrev" onclick="changePage(-1)" disabled><i class="bi bi-chevron-left"></i> Prev</button>
                        <span class="text-muted small" id="pageInfo">Halaman 1</span>
                        <button class="btn btn-secondary" id="btnNext" onclick="changePage(1)" disabled>Next <i class="bi bi-chevron-right"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: Cart & Checkout -->
        <div class="col-md-5">
            <div class="card shadow h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-cart"></i> Keranjang</h5>
                    <button class="btn btn-sm btn-outline-danger" onclick="clearCart()">Reset</button>
                </div>

                <!-- Member Section -->
                <div class="p-2 bg-light border-bottom">
                    <div class="input-group input-group-sm mb-1" id="memberSearchGroup">
                        <input type="text" id="memberPhone" class="form-control" placeholder="No. HP Member">
                        <button class="btn btn-outline-secondary" type="button" onclick="searchMember()">Cari</button>
                    </div>
                    <div id="memberInfo" class="d-none text-success small fw-bold d-flex justify-content-between">
                        <span><i class="bi bi-person-check"></i> <span id="memberName"></span></span>
                        <button class="btn btn-link btn-sm text-danger p-0" onclick="removeMember()">Hapus</button>
                    </div>
                </div>

                <!-- Cart Items -->
                <div class="card-body p-0 cart-list">
                    <table class="table table-striped mb-0">
                        <thead class="table-light sticky-top">
                        <tr>
                            <th>Produk</th>
                            <th width="20%">Qty</th>
                            <th class="text-end">Subtotal</th>
                            <th width="5%"></th>
                        </tr>
                        </thead>
                        <tbody id="cartTableBody"></tbody>
                    </table>
                </div>

                <!-- Footer Totals & Voucher -->
                <div class="card-footer bg-white border-top p-3">
                    <!-- Voucher Input -->
                    <div class="input-group mb-2">
                        <span class="input-group-text bg-light"><i class="bi bi-ticket-perforated"></i></span>
                        <input type="text" id="voucherCode" class="form-control" placeholder="Kode Voucher (6 Digit)">
                        <button class="btn btn-outline-primary" type="button" onclick="checkVoucher()">Gunakan</button>
                    </div>
                    <div id="voucherInfo" class="text-success small fw-bold mb-2 d-none">
                        <div class="d-flex justify-content-between">
                            <span>Voucher: <span id="voucherName"></span></span>
                            <a href="#" onclick="removeVoucher()" class="text-danger text-decoration-none">×</a>
                        </div>
                        <small class="text-muted d-block" id="voucherDesc"></small>
                    </div>

                    <!-- Summary -->
                    <div class="d-flex justify-content-between mb-1">
                        <span>Subtotal:</span>
                        <span class="fw-bold" id="subTotalDisplay">Rp 0</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 text-danger">
                        <span>Diskon:</span>
                        <span class="fw-bold" id="discountDisplay">-Rp 0</span>
                    </div>
                    <div class="d-flex justify-content-between h4 total-section pt-2">
                        <span>Total Bayar:</span>
                        <span class="fw-bold text-primary" id="grandTotal">Rp 0</span>
                    </div>

                    <div class="d-grid gap-2 mt-3">
                        <button class="btn btn-success btn-lg" onclick="processCheckout()" id="btnPay">
                            <i class="bi bi-cash"></i> BAYAR SEKARANG
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Buka Shift -->
<div class="modal fade" id="shiftModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Buka Shift Kasir</h5>
            </div>
            <div class="modal-body">
                <p>Halo <strong><?= $user_name ?></strong>, silakan input modal awal.</p>
                <div class="mb-3">
                    <label class="form-label">Modal Awal (Rp)</label>
                    <input type="number" id="startingCash" class="form-control" value="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary w-100" onclick="openShift()">Buka Shift</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    let cart = [];
    let currentMemberId = null;
    let currentPage = 1;
    let totalPages = 1;
    let searchKeyword = '';

    // --- LOGIKA VOUCHER BARU ---
    let activeVoucher = null;
    // activeVoucher structure: { voucher: {id, name, amount, max_discount}, allowed_products: [] }

    const shiftIsOpen = <?= $shiftIsOpen ? 'true' : 'false' ?>;

    $(document).ready(function() {
        if (!shiftIsOpen) {
            new bootstrap.Modal(document.getElementById('shiftModal')).show();
        }
        loadProducts();

        let timeout;
        $('#searchProduct').on('keyup', function(e) {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                searchKeyword = $(this).val();
                currentPage = 1;
                loadProducts();
            }, 300);
        });
    });

    // --- FUNGSI PRODUK & CART (Standard) ---
    function loadProducts() {
        $.get('api.php', {
            action: 'get_products',
            page: currentPage,
            search: searchKeyword,
            stock_only: 1
        }, function(res) {
            if(res.status === 'success') {
                renderProductList(res.data);
                totalPages = res.pagination.total_pages;
                $('#pageInfo').text(`Halaman ${res.pagination.current_page} dari ${totalPages || 1}`);
                $('#btnPrev').prop('disabled', res.pagination.current_page <= 1);
                $('#btnNext').prop('disabled', res.pagination.current_page >= totalPages || totalPages === 0);
            }
        }, 'json');
    }

    function renderProductList(products) {
        let html = '';
        if(products.length === 0) {
            html = '<div class="col-12 text-center text-muted mt-5"><p>Produk tidak ditemukan.</p></div>';
        } else {
            products.forEach(p => {
                html += `
                    <div class="col-md-4 col-sm-6">
                        <div class="card product-card h-100" onclick="addToCart(${p.id}, '${p.name}', ${p.price}, ${p.stock})">
                            <div class="card-body p-2 text-center">
                                <h6 class="card-title text-truncate">${p.name}</h6>
                                <p class="card-text fw-bold text-primary">Rp ${parseInt(p.price).toLocaleString()}</p>
                                <small class="text-muted">Stok: ${p.stock}</small>
                            </div>
                        </div>
                    </div>`;
            });
        }
        $('#productList').html(html);
    }

    function addToCart(id, name, price, stock) {
        let existing = cart.find(i => i.id === id);
        if(existing) {
            if(existing.qty < stock) {
                existing.qty++;
            } else {
                Swal.fire('Stok Habis', 'Maksimal stok tercapai', 'warning');
            }
        } else {
            cart.push({id, name, price, stock, qty: 1});
        }
        renderCart();
    }

    // --- UPDATE LOGIKA HITUNG CART ---
    function renderCart() {
        let html = '';
        let subtotal = 0;

        cart.forEach((item, index) => {
            let itemTotal = item.price * item.qty;
            subtotal += itemTotal;
            html += `
                <tr>
                    <td><small>${item.name}</small></td>
                    <td>
                        <input type="number" class="form-control form-control-sm" value="${item.qty}" min="1" max="${item.stock}" onchange="updateQty(${index}, this.value)">
                    </td>
                    <td class="text-end"><small>Rp ${itemTotal.toLocaleString()}</small></td>
                    <td><button class="btn btn-sm btn-link text-danger" onclick="removeFromCart(${index})"><i class="bi bi-trash"></i></button></td>
                </tr>`;
        });
        $('#cartTableBody').html(html);

        // --- HITUNG DISKON ESTIMASI ---
        let discountAmount = 0;
        if (activeVoucher) {
            let v = activeVoucher.voucher;
            let allowed = activeVoucher.allowed_products; // Array ID produk
            let isGlobal = (allowed.length === 0);
            let percent = parseFloat(v.amount); // Misal 0.20
            let calculatedDisc = 0;

            cart.forEach(item => {
                // Cek apakah produk ini dapat diskon
                if (isGlobal || allowed.includes(item.id.toString()) || allowed.includes(item.id)) {
                    calculatedDisc += (item.price * item.qty) * percent;
                }
            });

            // Cek Max Discount (Cap)
            let maxDisc = parseInt(v.max_discount);
            if (maxDisc > 0 && calculatedDisc > maxDisc) {
                discountAmount = maxDisc;
            } else {
                discountAmount = calculatedDisc;
            }
        }

        // Validasi akhir
        if (discountAmount > subtotal) discountAmount = subtotal;

        let grandTotal = subtotal - discountAmount;

        $('#subTotalDisplay').text('Rp ' + subtotal.toLocaleString());
        $('#discountDisplay').text('-Rp ' + parseInt(discountAmount).toLocaleString());
        $('#grandTotal').text('Rp ' + parseInt(grandTotal).toLocaleString());
    }

    function updateQty(index, val) {
        val = parseInt(val);
        if(val > cart[index].stock) cart[index].qty = cart[index].stock;
        else if (val < 1) cart[index].qty = 1;
        else cart[index].qty = val;
        renderCart();
    }

    function removeFromCart(index) {
        cart.splice(index, 1);
        renderCart();
    }

    function clearCart() {
        cart = [];
        removeVoucher();
        renderCart();
        removeMember();
    }

    // --- FUNGSI VOUCHER (UPDATE) ---
    function checkVoucher() {
        let code = $('#voucherCode').val();
        if (!code) return;

        $.post('api.php', {action: 'check_voucher', code: code}, function(res) {
            if (res.status === 'success') {
                // Simpan data voucher dari respon API
                activeVoucher = res.data;

                let v = activeVoucher.voucher;
                let percentText = (parseFloat(v.amount) * 100) + '%';
                let maxText = parseInt(v.max_discount) > 0 ? ` (Maks Rp ${parseInt(v.max_discount).toLocaleString()})` : '';
                let scopeText = res.data.allowed_products.length > 0 ? 'Produk Tertentu' : 'Semua Produk';

                $('#voucherInfo').removeClass('d-none');
                $('#voucherName').text(v.id + ' - ' + v.name);
                $('#voucherDesc').text(`Diskon ${percentText}${maxText} - ${scopeText}`);

                $('#voucherCode').val('');
                Swal.fire('Voucher Diterapkan', 'Diskon dihitung sesuai produk terkait', 'success');
                renderCart();
            } else {
                Swal.fire('Gagal', res.message, 'error');
            }
        }, 'json');
    }

    function removeVoucher() {
        activeVoucher = null;
        $('#voucherInfo').addClass('d-none');
        $('#voucherCode').val('');
        renderCart();
    }

    // --- MEMBER & CHECKOUT ---
    function searchMember() {
        let phone = $('#memberPhone').val();
        if(!phone) return;
        $.post('api.php', {action: 'search_member', phone_number: phone}, function(res) {
            if(res.status === 'success') {
                currentMemberId = res.data.id;
                $('#memberSearchGroup').addClass('d-none');
                $('#memberInfo').removeClass('d-none');
                $('#memberName').text(res.data.name);
                Swal.fire('Member Ditemukan', res.data.name, 'success');
            } else {
                Swal.fire('Gagal', 'Member tidak ditemukan', 'error');
            }
        }, 'json');
    }

    function removeMember() {
        currentMemberId = null;
        $('#memberSearchGroup').removeClass('d-none');
        $('#memberInfo').addClass('d-none');
        $('#memberPhone').val('');
    }

    function openShift() {
        let cash = $('#startingCash').val();
        $.post('api.php', {action: 'open_shift', starting_cash: cash}, function(res) {
            if(res.status === 'success') location.reload();
            else alert('Gagal: ' + res.message);
        });
    }

    function processCheckout() {
        if(cart.length === 0) {
            Swal.fire('Keranjang Kosong', 'Pilih produk dulu', 'warning');
            return;
        }

        $('#btnPay').prop('disabled', true).text('Memproses...');
        let voucherCodeToSend = activeVoucher ? activeVoucher.voucher.id : null;

        $.post('api.php', {
            action: 'checkout',
            cart: JSON.stringify(cart),
            customer_id: currentMemberId,
            voucher_code: voucherCodeToSend
        }, function(res) {
            if(res.status === 'success') {
                Swal.fire({
                    title: 'Transaksi Berhasil!',
                    text: 'ID: ' + res.transaction_id,
                    icon: 'success'
                }).then(() => {
                    clearCart();
                    loadProducts();
                    $('#btnPay').prop('disabled', false).html('<i class="bi bi-cash"></i> BAYAR SEKARANG');
                });
            } else {
                Swal.fire('Gagal', res.message, 'error');
                $('#btnPay').prop('disabled', false).html('<i class="bi bi-cash"></i> BAYAR SEKARANG');
            }
        }, 'json');
    }

    function changePage(delta) {
        let newPage = currentPage + delta;
        if(newPage > 0 && newPage <= totalPages) {
            currentPage = newPage;
            loadProducts();
        }
    }
</script>
</body>
</html>