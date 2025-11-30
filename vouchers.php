<?php
require_once 'config.php';

// --- KEAMANAN HALAMAN ---
// Cek Login: Jika user belum punya sesi maka tolak aksesnya.
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
    <title>Manajemen Voucher - POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="#"><i class="bi bi-ticket-perforated"></i> POS System</a>
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
                    <a class="nav-link" href="inventory.php"><i class="bi bi-box-seam"></i> Stok Produk</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="vouchers.php"><i class="bi bi-ticket-perforated"></i> Voucher</a>
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
            <h5 class="mb-0">Daftar Voucher</h5>
            <button class="btn btn-primary" onclick="openAddModal()">
                <i class="bi bi-plus-lg"></i> Tambah Voucher
            </button>
        </div>
        <div class="card-body">

            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchInput" class="form-control" placeholder="Cari Kode atau Nama...">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle">
                    <thead class="table-light">
                    <tr>
                        <th class="cursor-pointer" onclick="handleSort('id')">
                            Kode (ID) <i class="bi bi-sort-alpha-down sort-icon" id="icon-id"></i>
                        </th>
                        <th class="cursor-pointer" onclick="handleSort('name')">
                            Nama Voucher <i class="bi bi-arrow-down-up sort-icon" id="icon-name"></i>
                        </th>
                        <th class="cursor-pointer" onclick="handleSort('amount')">
                            Diskon (%) <i class="bi bi-arrow-down-up sort-icon" id="icon-amount"></i>
                        </th>
                        <th class="cursor-pointer" onclick="handleSort('max_discount')">
                            Max (Rp) <i class="bi bi-arrow-down-up sort-icon" id="icon-max_discount"></i>
                        </th>
                        <th class="cursor-pointer" onclick="handleSort('expiry_date')">
                            Berlaku Sampai <i class="bi bi-arrow-down-up sort-icon" id="icon-expiry_date"></i>
                        </th>
                        <th>Produk Terkait</th>
                        <th class="cursor-pointer" onclick="handleSort('status')">
                            Status <i class="bi bi-arrow-down-up sort-icon" id="icon-status"></i>
                        </th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody id="voucherTableBody">
                    <tr><td colspan="8" class="text-center">Memuat...</td></tr>
                    </tbody>
                </table>
            </div>

            <nav class="mt-3">
                <ul class="pagination justify-content-center" id="pagination">
                </ul>
            </nav>
        </div>
    </div>
</div>

<div class="modal fade" id="voucherModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Buat Voucher Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="voucherForm">
                    <input type="hidden" name="action" value="add_voucher">

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Kode (ID)</label>
                            <input type="text" class="form-control text-uppercase" name="id" id="inputVoucherId" maxlength="6" minlength="6" placeholder="Misal: VO-001" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Nama Voucher</label>
                            <input type="text" class="form-control" name="name" placeholder="Contoh: Promo Merdeka" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Persen Diskon (0.01 - 1.00)</label>
                            <input type="number" class="form-control" name="amount" step="0.01" min="0.01" max="1.00" placeholder="0.20 untuk 20%" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Maksimal Diskon (Rp)</label>
                            <input type="number" class="form-control" name="max_discount" value="0">
                            <div class="form-text">0 = Tanpa Batas</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Berlaku Sampai</label>
                        <input type="date" class="form-control" name="expiry_date" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Produk Khusus (Opsional)</label>
                        <div class="form-text mb-2">Pilih produk jika voucher hanya untuk barang tertentu. Biarkan kosong untuk Semua Produk. (Tahan Ctrl untuk pilih banyak)</div>

                        <select class="form-select" name="product_ids_select" id="productSelect" multiple size="5">
                        </select>

                        <input type="hidden" name="product_ids" id="productIdsJson">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="saveVoucher()">Simpan Voucher</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <script>
    // --- VARIABEL GLOBAL STATE ---
    // Menyimpan status halaman saat ini agar navigasi lancar
    let currentPage = 1;
    let searchKeyword = '';
    let currentSort = 'expiry_date'; // Default urut berdasarkan tanggal kadaluarsa
    let currentOrder = 'DESC';       // Default urut dari yang terbaru

    // --- SAAT HALAMAN SELESAI DIMUAT ---
    $(document).ready(function() {
        // Langsung muat data voucher
        loadVouchers();

        // Event Listener untuk Kolom Pencarian
        // Menggunakan teknik 'Debounce': Menunggu user selesai mengetik 500ms
        // baru request ke server, agar tidak membebani server setiap ketikan huruf.
        let timeout;
        $('#searchInput').on('keyup', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                searchKeyword = $(this).val();
                currentPage = 1; // Reset ke halaman 1 setiap pencarian baru
                loadVouchers();
            }, 500);
        });
    });

    // --- LOGIKA SORTING (MENGURUTKAN DATA) ---
    // Dipanggil saat header tabel diklik
    function handleSort(column) {
        if (currentSort === column) {
            // Jika kolom sama diklik lagi, balik urutannya (ASC <-> DESC)
            currentOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
        } else {
            // Jika kolom baru, default ke ASC (A-Z)
            currentSort = column;
            currentOrder = 'ASC';
        }
        updateSortUI(); // Update ikon panah
        loadVouchers(); // Refresh data
    }

    // Mengubah ikon panah di header tabel sesuai urutan aktif
    function updateSortUI() {
        // Reset semua icon ke default (panah atas-bawah netral)
        $('.sort-icon').attr('class', 'bi bi-arrow-down-up sort-icon').removeClass('active-sort');

        // Tentukan ikon berdasarkan ASC/DESC
        let iconClass = currentOrder === 'ASC' ? 'bi-sort-up' : 'bi-sort-down';

        // Kosmetik: Ikon khusus untuk kolom huruf (A-Z) vs angka (0-9)
        if(['id', 'name', 'status'].includes(currentSort)) {
            iconClass = currentOrder === 'ASC' ? 'bi-sort-alpha-down' : 'bi-sort-alpha-down-alt';
        }
        if(['amount', 'max_discount', 'expiry_date'].includes(currentSort)) {
            iconClass = currentOrder === 'ASC' ? 'bi-sort-numeric-down' : 'bi-sort-numeric-down-alt';
        }

        // Set icon pada kolom yang aktif
        $(`#icon-${currentSort}`).attr('class', `bi ${iconClass} sort-icon active-sort`);
    }

    // --- FUNGSI UTAMA: LOAD DATA DARI SERVER ---
    function loadVouchers() {
        // Melakukan request AJAX GET ke api.php
        $.get('api.php', {
            action: 'get_vouchers',
            page: currentPage,
            search: searchKeyword,
            sort_by: currentSort,
            order: currentOrder
        }, function(res) {
            if(res.status === 'success') {
                // Jika sukses, gambar ulang tabel dan tombol paginasi
                renderTable(res.data);
                renderPagination(res.pagination);
                updateSortUI();
            }
        }, 'json');
    }

    // Mengubah data JSON menjadi baris HTML Tabel
    function renderTable(vouchers) {
        let html = '';
        if(vouchers.length === 0) {
            html = '<tr><td colspan="8" class="text-center text-muted">Belum ada voucher ditemukan.</td></tr>';
        } else {
            vouchers.forEach(v => {
                // Format tampilan data agar enak dibaca
                let percent = parseFloat(v.amount) * 100;
                let maxDisc = parseInt(v.max_discount) > 0 ? 'Rp ' + parseInt(v.max_discount).toLocaleString() : 'No Limit';

                // Badge untuk Produk Terkait
                let products = v.product_count > 0 ? `<span class="badge bg-info">${v.product_count} Produk</span>` : '<span class="badge bg-success">Global (Semua)</span>';

                // Badge Status & Logika warna expired
                let statusClass = v.status === 'active' ? 'bg-success' : 'bg-secondary';
                let today = new Date().toISOString().split('T')[0];

                // Jika aktif tapi tanggal sudah lewat, warnanya jadi kuning
                if(v.expiry_date < today && v.status === 'active') statusClass = 'bg-warning text-dark';

                let statusBadge = `<span class="badge ${statusClass}">${v.status}</span>`;

                html += `
                    <tr>
                        <td class="fw-bold text-uppercase">${v.id}</td>
                        <td>${v.name}</td>
                        <td>${percent}%</td>
                        <td>${maxDisc}</td>
                        <td>${v.expiry_date}</td>
                        <td>${products}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <button class="btn btn-sm btn-danger" onclick="deleteVoucher('${v.id}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>`;
            });
        }
        $('#voucherTableBody').html(html);
    }

    // Membuat tombol Prev/Next dan nomor halaman
    function renderPagination(meta) {
        let html = '';
        let totalPages = meta.total_pages;
        let current = meta.current_page;

        // Tombol Previous
        html += `<li class="page-item ${current === 1 ? 'disabled' : ''}">
                        <button class="page-link" onclick="changePage(${current - 1})">Prev</button>
                     </li>`;

        // Logic membatasi tampilan nomor halaman (hanya tampilkan sekitar halaman aktif)
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

    // Fungsi ganti halaman
    function changePage(page) {
        currentPage = page;
        loadVouchers();
    }

    // --- FITUR CRUD (CREATE, READ, DELETE) ---

    // 1. Membuka Modal Tambah
    function openAddModal() {
        // Reset form agar kosong
        $('#voucherForm')[0].reset();

        // Ambil daftar produk dari API untuk mengisi Select Option
        $.get('api.php?action=get_products&limit=1000', function(res) {
            let html = '';
            if(res.status === 'success') {
                res.data.forEach(p => {
                    html += `<option value="${p.id}">${p.name} (Rp ${parseInt(p.price).toLocaleString()})</option>`;
                });
            }
            $('#productSelect').html(html);
            // Tampilkan Modal Bootstrap
            new bootstrap.Modal(document.getElementById('voucherModal')).show();
        }, 'json');
    }

    // 2. Menyimpan Voucher Baru
    function saveVoucher() {
        // VALIDASI FRONTEND: Panjang Kode ID Wajib 6 Karakter
        let idVal = $('#inputVoucherId').val().trim();
        if (idVal.length !== 6) {
            Swal.fire('Validasi Gagal', 'Kode Voucher wajib terdiri dari 6 karakter.', 'warning');
            return;
        }

        // Ambil produk yang dipilih dari elemen <select multiple>
        let selectedProducts = $('#productSelect').val();
        // Masukkan ke hidden input dalam bentuk JSON String agar bisa dikirim via POST
        $('#productIdsJson').val(JSON.stringify(selectedProducts));

        // Bungkus semua data form
        const formData = $('#voucherForm').serialize();

        // Kirim ke Server
        $.post('api.php', formData, function(res) {
            if(res.status === 'success') {
                Swal.fire('Berhasil', 'Voucher dibuat!', 'success');

                // Tutup modal dan bersihkan backdrop
                $('#voucherModal').modal('hide');
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');

                // Refresh tabel
                loadVouchers();
            } else {
                Swal.fire('Gagal', res.message, 'error');
            }
        }, 'json');
    }

    // 3. Menghapus Voucher
    function deleteVoucher(id) {
        // Tampilkan konfirmasi SweetAlert sebelum menghapus
        Swal.fire({
            title: 'Hapus Voucher?',
            text: "Voucher " + id + " akan dihapus permanen.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Jika user klik Ya, kirim request hapus
                $.post('api.php', {action: 'delete_voucher', id: id}, function(res) {
                    if(res.status === 'success') {
                        Swal.fire('Terhapus!', 'Voucher dihapus.', 'success');
                        loadVouchers();
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