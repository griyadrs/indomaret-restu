<?php
global $pdo;
require_once 'config.php';

// Mengatur agar output selalu berupa JSON
header('Content-Type: application/json');

// --- KEAMANAN DASAR ---
// Cek Login: Jika user belum punya sesi maka tolak aksesnya.
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Menentukan "action" apa yang diminta oleh frontend (bisa dari GET atau POST)
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ============================================================================
// FITUR KASIR (POS) - Bagian Front Office
// ============================================================================

// 1. CARI PRODUK
// Digunakan saat kasir mengetik nama barang atau scan barcode
if ($action === 'search_product') {
    $keyword = $_GET['keyword'] ?? '';
    try {
        // Cari produk berdasarkan Nama (mirip) ATAU ID (persis/barcode)
        // Hanya tampilkan jika stok masih ada
        $stmt = $pdo->prepare("SELECT * FROM products WHERE (name LIKE ? OR id = ?) AND stock > 0 LIMIT 10");
        $stmt->execute(["%$keyword%", $keyword]);
        $products = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'data' => $products]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// 2. CARI MEMBER
// Mencari data pelanggan berdasarkan nomor HP untuk poin/diskon member. NOTE: Versi saat ini 1.2 hanya bisa mencari user
elseif ($action === 'search_member') {
    $phone = $_POST['phone_number'] ?? '';
    try {
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE phone_number = ?");
        $stmt->execute([$phone]);
        $member = $stmt->fetch();

        if ($member) {
            echo json_encode(['status' => 'success', 'data' => $member]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Member tidak ditemukan']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// 3. BUKA SHIFT KASIR
// Mencatat modal awal di laci kasir (Cash Drawer) sebelum memulai shift
elseif ($action === 'open_shift') {
    $modal = $_POST['starting_cash'] ?? 0;
    $userId = $_SESSION['user_id'];

    try {
        $stmt = $pdo->prepare("INSERT INTO cashier_shifts (user_id, starting_cash, status) VALUES (?, ?, 'open')");
        $stmt->execute([$userId, $modal]);
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// 4. CEK VOUCHER
// Memvalidasi kode promo yang dimasukkan kasir
elseif ($action === 'check_voucher') {
    $code = strtoupper($_POST['code'] ?? ''); // ID voucher dianggap sebagai KODE
    $today = date('Y-m-d');

    try {
        // Cek tabel vouchers: harus ada, status aktif, dan belum kadaluarsa
        $stmt = $pdo->prepare("SELECT * FROM vouchers WHERE id = ? AND status = 'active' AND expiry_date >= ?");
        $stmt->execute([$code, $today]);
        $voucher = $stmt->fetch();

        if ($voucher) {
            // Cek apakah voucher ini dibatasi untuk produk tertentu saja?
            $stmtProd = $pdo->prepare("SELECT product_id FROM product_voucher WHERE voucher_id = ?");
            $stmtProd->execute([$code]);
            $allowedProducts = $stmtProd->fetchAll(PDO::FETCH_COLUMN);

            // Kirim data voucher & daftar produk yang boleh didiskon ke frontend
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'voucher' => $voucher,
                    'allowed_products' => $allowedProducts // Array kosong [] berarti voucher berlaku untuk SEMUA produk
                ]
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Kode voucher tidak valid atau kadaluarsa']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// 5. PROSES CHECKOUT (TRANSAKSI UTAMA)
// Menghani pembayaran, potong stok, hitung diskon final, dan simpan riwayat
elseif ($action === 'checkout') {
    $cart = json_decode($_POST['cart'], true); // Data keranjang dari JS
    $customerId = !empty($_POST['customer_id']) ? $_POST['customer_id'] : NULL;
    $voucherCode = !empty($_POST['voucher_code']) ? $_POST['voucher_code'] : NULL;
    $cashierId = $_SESSION['user_id'];

    if (empty($cart)) {
        echo json_encode(['status' => 'error', 'message' => 'Keranjang kosong']);
        exit;
    }

    try {
        // Mulai Transaksi Database
        // Jika ada error di tengah jalan, semua perubahan dibatalkan (Rollback)
        $pdo->beginTransaction();

        $discountAmount = 0;
        $totalTransaction = 0;

        // --- LOGIKA HITUNG DISKON DI SERVER (Agar aman dari manipulasi) ---
        if ($voucherCode) {
            $today = date('Y-m-d');
            $stmtVoucher = $pdo->prepare("SELECT * FROM vouchers WHERE id = ? AND status = 'active' AND expiry_date >= ?");
            $stmtVoucher->execute([$voucherCode, $today]);
            $voucherData = $stmtVoucher->fetch();

            if ($voucherData) {
                // Ambil daftar produk spesifik yang boleh didiskon voucher ini
                $stmtAllowed = $pdo->prepare("SELECT product_id FROM product_voucher WHERE voucher_id = ?");
                $stmtAllowed->execute([$voucherCode]);
                $allowedProductIds = $stmtAllowed->fetchAll(PDO::FETCH_COLUMN);
                $isGlobalVoucher = empty($allowedProductIds); // Jika list kosong, berarti berlaku global

                // Loop cart untuk hitung diskon per item yang valid
                $calculatedDiscount = 0;

                // Siapkan query harga untuk validasi
                $stmtGetPrice = $pdo->prepare("SELECT price, stock FROM products WHERE id = ?");

                foreach ($cart as $item) {
                    $stmtGetPrice->execute([$item['id']]);
                    $prodDB = $stmtGetPrice->fetch();
                    if ($prodDB) {
                        $lineTotal = $prodDB['price'] * $item['qty'];
                        $totalTransaction += $lineTotal;

                        // Cek syarat diskon: Voucher Global ATAU ID Produk ada di whitelist voucher
                        if ($isGlobalVoucher || in_array($item['id'], $allowedProductIds)) {
                            // amount desimal (0.20 = 20%)
                            $calculatedDiscount += $lineTotal * $voucherData['amount'];
                        }
                    }
                }

                // Cek Batas Maksimal Diskon (Cap)
                $maxDisc = (int)$voucherData['max_discount'];
                if ($maxDisc > 0 && $calculatedDiscount > $maxDisc) {
                    $discountAmount = $maxDisc;
                } else {
                    $discountAmount = $calculatedDiscount;
                }

            } else {
                // Voucher tidak valid/expired saat checkout berlangsung
                // Hitung total normal tanpa diskon
                $stmtGetPrice = $pdo->prepare("SELECT price FROM products WHERE id = ?");
                foreach ($cart as $item) {
                    $stmtGetPrice->execute([$item['id']]);
                    $p = $stmtGetPrice->fetch();
                    if($p) $totalTransaction += $p['price'] * $item['qty'];
                }
            }
        } else {
            // Transaksi Normal Tanpa Voucher
            $stmtGetPrice = $pdo->prepare("SELECT price FROM products WHERE id = ?");
            foreach ($cart as $item) {
                $stmtGetPrice->execute([$item['id']]);
                $p = $stmtGetPrice->fetch();
                if($p) $totalTransaction += $p['price'] * $item['qty'];
            }
        }

        // --- SIMPAN KE DATABASE ---

        // 1. Insert ke Tabel Transaksi (Header)
        $stmtCtx = $pdo->prepare("INSERT INTO transactions (cashier_id, customer_id, discount_amount) VALUES (?, ?, ?)");
        $stmtCtx->execute([$cashierId, $customerId, $discountAmount]);
        $transactionId = $pdo->lastInsertId();

        // 2. Insert Detail Transaksi & Update Stok Produk
        $stmtDetail = $pdo->prepare("INSERT INTO transaction_details (transaction_id, product_id, qty, price_at_transaction) VALUES (?, ?, ?, ?)");
        // Query stok memastikan stok tidak menjadi negatif
        $stmtStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
        $stmtGetPriceCtx = $pdo->prepare("SELECT price, stock FROM products WHERE id = ?");

        foreach ($cart as $item) {
            $stmtGetPriceCtx->execute([$item['id']]);
            $prodData = $stmtGetPriceCtx->fetch();

            // Validasi Stok Terakhir
            if (!$prodData || $prodData['stock'] < $item['qty']) {
                throw new Exception("Stok produk ID {$item['id']} tidak cukup.");
            }

            // Simpan Detail
            $stmtDetail->execute([$transactionId, $item['id'], $item['qty'], $prodData['price']]);

            // Potong Stok
            $stmtStock->execute([$item['qty'], $item['id'], $item['qty']]);
            if ($stmtStock->rowCount() == 0) {
                throw new Exception("Gagal update stok ID {$item['id']}. Stok berubah saat transaksi.");
            }
        }

        // 3. Update Poin Member (Loyalty Program)
        $finalTotal = $totalTransaction - $discountAmount;
        if ($customerId && $finalTotal > 0) {
            // Aturan: 1 poin tiap kelipatan 10.000
            $pointsEarned = floor($finalTotal / 10000);
            if ($pointsEarned > 0) {
                $stmtPoint = $pdo->prepare("UPDATE customers SET points = points + ? WHERE id = ?");
                $stmtPoint->execute([$pointsEarned, $customerId]);
            }
        }

        // Jika sampai sini aman, simpan permanen.
        $pdo->commit();
        echo json_encode(['status' => 'success', 'transaction_id' => $transactionId]);

    } catch (Exception $e) {
        // Ada error? Batalkan semua perubahan database.
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// ============================================================================
// DASHBOARD STATS (laporan singkat untuk kasir)
// ============================================================================
elseif ($action === 'get_dashboard_stats') {
    $today = date('Y-m-d');
    try {
        // Hitung total jumlah transaksi hari ini
        $stmtCtx = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE DATE(created_at) = ?");
        $stmtCtx->execute([$today]);
        $totalTx = $stmtCtx->fetchColumn();

        // Hitung total item terjual
        $stmtItem = $pdo->prepare("
            SELECT SUM(td.qty) FROM transaction_details td 
            JOIN transactions t ON td.transaction_id = t.id 
            WHERE DATE(t.created_at) = ?
        ");
        $stmtItem->execute([$today]);
        $totalItem = $stmtItem->fetchColumn() ?: 0;

        // Hitung omset kotor (sebelum diskon)
        $stmtGross = $pdo->prepare("
            SELECT SUM(td.price_at_transaction * td.qty) FROM transaction_details td 
            JOIN transactions t ON td.transaction_id = t.id 
            WHERE DATE(t.created_at) = ?
        ");
        $stmtGross->execute([$today]);
        $grossSales = $stmtGross->fetchColumn() ?: 0;

        // Hitung total diskon yang diberikan hari ini
        $stmtDisc = $pdo->prepare("SELECT SUM(discount_amount) FROM transactions WHERE DATE(created_at) = ?");
        $stmtDisc->execute([$today]);
        $totalDiscount = $stmtDisc->fetchColumn() ?: 0;

        // Omset bersih
        $netRevenue = $grossSales - $totalDiscount;

        // Ambil 5 transaksi terbaru untuk ditampilkan di tabel dashboard
        $stmtRecent = $pdo->query("
            SELECT t.id, t.created_at, t.discount_amount, u.username, c.name as customer_name,
            (SELECT SUM(price_at_transaction * qty) FROM transaction_details WHERE transaction_id = t.id) as subtotal
            FROM transactions t
            LEFT JOIN users u ON t.cashier_id = u.id
            LEFT JOIN customers c ON t.customer_id = c.id
            ORDER BY t.created_at DESC LIMIT 5
        ");
        $recentTx = $stmtRecent->fetchAll();

        echo json_encode([
            'status' => 'success',
            'data' => [
                'total_transactions' => $totalTx,
                'items_sold' => $totalItem,
                'revenue' => $netRevenue,
                'recent_transactions' => $recentTx
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// ============================================================================
// MANAJEMEN STOK (INVENTORY)
// ============================================================================
elseif ($action === 'get_products') {
    // Logika Pagination & Sorting
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 12; // Barang per halaman
    $offset = ($page - 1) * $limit;

    $search = $_GET['search'] ?? '';
    $stockOnly = isset($_GET['stock_only']) && $_GET['stock_only'] == 1;

    // Keamanan Sorting (Whitelist kolom)
    $sortBy = $_GET['sort_by'] ?? 'id';
    $sortOrder = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
    if (!in_array($sortBy, ['id', 'name', 'price', 'stock'])) $sortBy = 'id';

    try {
        // Membangun query dinamis berdasarkan filter
        $whereClauses = []; $params = [];
        if (!empty($search)) { $whereClauses[] = "(name LIKE ? OR id = ?)"; $params[] = "%$search%"; $params[] = $search; }
        if ($stockOnly) { $whereClauses[] = "stock > 0"; }
        $whereSql = count($whereClauses) > 0 ? " WHERE " . implode(" AND ", $whereClauses) : "";

        // Hitung total untuk pagination
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM products" . $whereSql);
        $stmtCount->execute($params);
        $totalItems = $stmtCount->fetchColumn();

        // Ambil data produk
        $stmt = $pdo->prepare("SELECT * FROM products" . $whereSql . " ORDER BY $sortBy $sortOrder LIMIT $limit OFFSET $offset");
        $stmt->execute($params);

        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(), 'pagination' => ['current_page' => $page, 'total_pages' => ceil($totalItems / $limit), 'total_items' => $totalItems]]);
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
}

// --- CRUD PRODUK ---

elseif ($action === 'add_product') {
    // Menambah produk baru ke database
    try {
        $stmt = $pdo->prepare("INSERT INTO products (name, price, stock) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['name'], $_POST['price'], $_POST['stock']]);
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
}
elseif ($action === 'update_product') {
    // Mengupdate data produk yang sudah ada
    try {
        $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, stock = ? WHERE id = ?");
        $stmt->execute([$_POST['name'], $_POST['price'], $_POST['stock'], $_POST['id']]);
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
}
elseif ($action === 'delete_product') {
    // Menghapus produk
    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        // Menangani error Foreign Key (jika produk sudah pernah terjual, tidak boleh dihapus)
        echo json_encode(['status' => 'error', 'message' => ($e->getCode() == 23000 ? 'Produk sudah ada di transaksi!' : $e->getMessage())]);
    }
}

// ============================================================================
// MANAJEMEN VOUCHER (PROMO)
// ============================================================================

// 11. GET ALL VOUCHERS
// Mengambil daftar voucher untuk tabel admin, lengkap dengan fitur cari & urutkan
elseif ($action === 'get_vouchers') {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $search = $_GET['search'] ?? '';
    $sortBy = $_GET['sort_by'] ?? 'expiry_date'; // Default: yang mau expired duluan
    $sortOrder = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

    // Whitelist kolom untuk keamanan sorting
    $allowedSorts = ['id', 'name', 'amount', 'max_discount', 'expiry_date', 'status'];
    if (!in_array($sortBy, $allowedSorts)) {
        $sortBy = 'expiry_date';
    }

    try {
        // Build Query Filter
        $whereClauses = [];
        $params = [];

        if (!empty($search)) {
            $whereClauses[] = "(id LIKE ? OR name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $whereSql = count($whereClauses) > 0 ? " WHERE " . implode(" AND ", $whereClauses) : "";

        // Hitung Total Data
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM vouchers" . $whereSql);
        $stmtCount->execute($params);
        $totalItems = $stmtCount->fetchColumn();
        $totalPages = ceil($totalItems / $limit);

        // Ambil Data + Subquery hitung jumlah produk terkait
        $sql = "SELECT v.*, (SELECT COUNT(*) FROM product_voucher WHERE voucher_id = v.id) as product_count 
                FROM vouchers v 
                $whereSql 
                ORDER BY $sortBy $sortOrder 
                LIMIT $limit OFFSET $offset";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $vouchers = $stmt->fetchAll();

        echo json_encode([
            'status' => 'success',
            'data' => $vouchers,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalItems
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// 12. ADD VOUCHER
// Membuat voucher baru beserta relasi produknya (jika ada)
elseif ($action === 'add_voucher') {
    $id = strtoupper($_POST['id'] ?? ''); // KODE Voucher (Primary Key)
    $name = $_POST['name'] ?? '';
    $amount = $_POST['amount'] ?? 0.00; // Decimal, 0.2 untuk 20%
    $max_discount = $_POST['max_discount'] ?? 0;
    $expiry_date = $_POST['expiry_date'] ?? date('Y-m-d');

    // List produk (Array JSON)
    $product_ids = isset($_POST['product_ids']) ? json_decode($_POST['product_ids'], true) : [];

    if (strlen($id) > 6 || strlen($id) < 1) {
        echo json_encode(['status' => 'error', 'message' => 'ID Voucher maksimal 6 karakter']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Insert Data Utama Voucher
        $stmt = $pdo->prepare("INSERT INTO vouchers (id, name, amount, max_discount, expiry_date, status) VALUES (?, ?, ?, ?, ?, 'active')");
        $stmt->execute([$id, $name, $amount, $max_discount, $expiry_date]);

        // Insert Relasi Produk (Looping array product_ids)
        if (!empty($product_ids) && is_array($product_ids)) {
            $stmtRel = $pdo->prepare("INSERT INTO product_voucher (product_id, voucher_id) VALUES (?, ?)");
            foreach ($product_ids as $pid) {
                $stmtRel->execute([$pid, $id]);
            }
        }

        $pdo->commit();
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        $pdo->rollBack();
        // Error 23000 = Duplicate Key (Kode voucher sudah terpakai)
        if ($e->getCode() == 23000) {
            echo json_encode(['status' => 'error', 'message' => 'ID Voucher sudah ada!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}

// 13. DELETE VOUCHER
// Menghapus voucher dan relasinya
elseif ($action === 'delete_voucher') {
    $id = $_POST['id'] ?? '';
    try {
        $pdo->beginTransaction();

        // Bersihkan dulu relasi di tabel product_voucher agar tidak error constraint
        $stmtRel = $pdo->prepare("DELETE FROM product_voucher WHERE voucher_id = ?");
        $stmtRel->execute([$id]);

        // Hapus voucher master
        $stmt = $pdo->prepare("DELETE FROM vouchers WHERE id = ?");
        $stmt->execute([$id]);

        $pdo->commit();
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}