<?php
session_start();

// --- PENGATURAN DATABASE ---
// Sesuaikan bagian ini dengan settingan di komputer/server
$hostname = 'localhost';
$database = 'indomaret';
$username = 'root';
$password = '';

try {
    // Mencoba menghubungkan PHP ke MySQL menggunakan PDO
    $pdo = new PDO("mysql:host=$hostname; dbname=$database; charset=utf8mb4", $username, $password);

    // Aktifkan mode error, jadi kalau ada SQL yang salah, program akan memberitahu (throw exception)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Atur agar data yang diambil otomatis berbentuk array asosiatif (nama kolom => nilai)
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Kalau koneksi gagal, matikan program dan tampilkan pesan errornya
    die("Gagal terhubung ke database: " . $e->getMessage());
}

/**
 * Fungsi Pembantu: Format Rupiah
 * Mengubah angka biasa (misal: 50000) menjadi format uang (Rp 50.000)
 * agar tampilan harga lebih enak dilihat mata.
 *
 * @param int $num Angka yang mau diformat
 * @return string
 */
function formatRupiah(int $num): string
{
    return "Rp " . number_format($num, 0, ',', '.');
}