<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Stok - POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .cursor-pointer { cursor: pointer; user-select: none; }
        .cursor-pointer:hover { background-color: #f8f9fa; }
        .sort-icon { font-size: 0.8em; margin-left: 5px; color: #6c757d; }
        .active-sort { color: #0d6efd; font-weight: bold; }
    </style>
</head>
<body class="bg-light">

<!-- Note: Terapkan Perinsip DRY pada navbar-->
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="#"><i class="bi bi-box-seam"></i> POS System</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="pos.php"><i class="bi bi-shop"></i> Kasir / POS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="inventory.php"><i class="bi bi-box-seam"></i> Stok Produk</a>
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
    <div class="card shadow">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Produk</h5>
            <button class="btn btn-primary" onclick="openAddModal()">
                <i class="bi bi-plus-lg"></i> Tambah Produk
            </button>
        </div>
        <div class="card-body">
            <!-- Search Bar -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchInput" class="form-control" placeholder="Cari nama produk...">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle">
                    <thead class="table-light">
                    <tr>
                        <th width="6%" class="cursor-pointer" onclick="handleSort('id')">
                            ID <i class="bi bi-sort-down sort-icon" id="icon-id"></i>
                        </th>
                        <th class="cursor-pointer" onclick="handleSort('name')">
                            Nama Produk <i class="bi bi-arrow-down-up sort-icon" id="icon-name"></i>
                        </th>
                        <th width="20%" class="cursor-pointer" onclick="handleSort('price')">
                            Harga (Rp) <i class="bi bi-arrow-down-up sort-icon" id="icon-price"></i>
                        </th>
                        <th width="15%" class="cursor-pointer" onclick="handleSort('stock')">
                            Stok <i class="bi bi-arrow-down-up sort-icon" id="icon-stock"></i>
                        </th>
                        <th width="15%">Aksi</th>
                    </tr>
                    </thead>
                    <tbody id="productTableBody">
                    <!-- Data dimuat via AJAX -->
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <nav class="mt-3">
                <ul class="pagination justify-content-center" id="pagination">
                    <!-- Tombol Paginasi via JS -->
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Modal Tambah/Edit Produk -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Tambah Produk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="productForm">
                    <input type="hidden" id="prodId" name="id">
                    <input type="hidden" id="actionType" name="action" value="add_product">

                    <div class="mb-3">
                        <label class="form-label">Nama Produk</label>
                        <input type="text" class="form-control" id="prodName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga Jual</label>
                        <input type="number" class="form-control" id="prodPrice" name="price" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stok Awal / Saat Ini</label>
                        <input type="number" class="form-control" id="prodStock" name="stock" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="saveProduct()">Simpan</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    let currentPage = 1;
    let searchKeyword = '';
    let currentSort = 'id';
    let currentOrder = 'DESC';

    $(document).ready(function() {
        loadProducts();

        // Event Listener Pencarian (Debounce sederhana)
        let timeout;
        $('#searchInput').on('keyup', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                searchKeyword = $(this).val();
                currentPage = 1; // Reset ke halaman 1 saat mencari
                loadProducts();
            }, 500);
        });
    });

    // --- Sorting Logic ---
    function handleSort(column) {
        // Jika kolom yang sama diklik, balik urutannya (ASC <-> DESC)
        if (currentSort === column) {
            currentOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
        } else {
            currentSort = column;
            currentOrder = 'ASC'; // Kolom baru default ASC
        }
        updateSortUI();
        loadProducts();
    }

    function updateSortUI() {
        // Reset semua icon
        $('.sort-icon').attr('class', 'bi bi-arrow-down-up sort-icon').removeClass('active-sort');

        // Set icon aktif
        let iconClass = currentOrder === 'ASC' ? 'bi-sort-up' : 'bi-sort-down';
        // Khusus kolom alfabet (nama), gunakan alpha-down/up agar lebih intuitif (opsional, disini pakai sort standar)
        if(currentSort === 'name') iconClass = currentOrder === 'ASC' ? 'bi-sort-alpha-down' : 'bi-sort-alpha-down-alt';
        if(currentSort === 'price' || currentSort === 'stock' || currentSort === 'id') iconClass = currentOrder === 'ASC' ? 'bi-sort-numeric-down' : 'bi-sort-numeric-down-alt';

        $(`#icon-${currentSort}`).attr('class', `bi ${iconClass} sort-icon active-sort`);
    }

    // --- Load Data ---
    function loadProducts() {
        $.get('api.php', {
            action: 'get_products',
            page: currentPage,
            search: searchKeyword,
            sort_by: currentSort,
            order: currentOrder
        }, function(res) {
            if(res.status === 'success') {
                renderTable(res.data);
                renderPagination(res.pagination);
                updateSortUI(); // Pastikan UI sinkron saat load awal
            }
        }, 'json');
    }

    function renderTable(products) {
        let html = '';
        if(products.length === 0) {
            html = '<tr><td colspan="5" class="text-center text-muted">Tidak ada data produk ditemukan.</td></tr>';
        } else {
            products.forEach(p => {
                html += `
                    <tr>
                        <td class="text-center">${p.id}</td>
                        <td>${p.name}</td>
                        <td>Rp ${parseInt(p.price).toLocaleString()}</td>
                        <td>
                            <span class="badge ${p.stock < 10 ? 'bg-danger' : 'bg-success'}">
                                ${p.stock}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-warning me-1" onclick="openEditModal(${p.id}, '${p.name}', ${p.price}, ${p.stock})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteProduct(${p.id}, '${p.name}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>`;
            });
        }
        $('#productTableBody').html(html);
    }

    function renderPagination(meta) {
        let html = '';
        let totalPages = meta.total_pages;
        let current = meta.current_page;

        // Tombol Previous
        html += `<li class="page-item ${current === 1 ? 'disabled' : ''}">
                        <button class="page-link" onclick="changePage(${current - 1})">Prev</button>
                     </li>`;

        // Angka Halaman
        // Logic sederhana: tampilkan semua page jika sedikit, atau batasi jika banyak (disini simplified)
        let startPage = Math.max(1, current - 2);
        let endPage = Math.min(totalPages, current + 2);

        for (let i = startPage; i <= endPage; i++) {
            html += `<li class="page-item ${i === current ? 'active' : ''}">
                            <button class="page-link" onclick="changePage(${i})">${i}</button>
                         </li>`;
        }

        // Tombol Next
        html += `<li class="page-item ${current === totalPages || totalPages === 0 ? 'disabled' : ''}">
                        <button class="page-link" onclick="changePage(${current + 1})">Next</button>
                     </li>`;

        $('#pagination').html(html);
    }

    function changePage(page) {
        currentPage = page;
        loadProducts();
    }

    // --- CRUD Modal Functions ---
    function openAddModal() {
        $('#productForm')[0].reset();
        $('#prodId').val('');
        $('#actionType').val('add_product');
        $('#modalTitle').text('Tambah Produk Baru');
        new bootstrap.Modal(document.getElementById('productModal')).show();
    }

    function openEditModal(id, name, price, stock) {
        $('#prodId').val(id);
        $('#prodName').val(name);
        $('#prodPrice').val(price);
        $('#prodStock').val(stock);
        $('#actionType').val('update_product');
        $('#modalTitle').text('Edit Produk');
        new bootstrap.Modal(document.getElementById('productModal')).show();
    }

    function saveProduct() {
        const formData = $('#productForm').serialize();

        $.post('api.php', formData, function(res) {
            if(res.status === 'success') {
                Swal.fire('Berhasil', 'Data produk disimpan', 'success');
                $('#productModal').modal('hide');
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
                loadProducts();
            } else {
                Swal.fire('Gagal', res.message, 'error');
            }
        }, 'json');
    }

    function deleteProduct(id, name) {
        Swal.fire({
            title: 'Hapus Produk?',
            text: `Anda akan menghapus "${name}".`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('api.php', {action: 'delete_product', id: id}, function(res) {
                    if(res.status === 'success') {
                        Swal.fire('Terhapus!', 'Produk telah dihapus.', 'success');
                        loadProducts();
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                }, 'json');
            }
        });
    }
</script>
</body>
</html>