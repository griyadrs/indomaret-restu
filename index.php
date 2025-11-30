<?php
global $pdo;
require_once 'config.php';

// --- CEK SESI PENGGUNA ---
// Kalau pengguna sudah login (punya session user_id), jangan biarkan dia di halaman login.
// Langsung lempar ke halaman API atau Dashboard.
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

// --- PROSES BACKEND LOGIN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Cari pengguna berdasarkan username di database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Jika pengguna ditemukan dan password cocok (setelah diverifikasi hash-nya)
    if ($user && password_verify($password, $user['password'])) {
        // Simpan data penting ke dalam sesi
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['first_name'] = $user['first_name'];

        // Arahkan ke dashboard utama
        header("Location: dashboard.php");
        exit;
    } else {
        // Jika salah, beri pesan error
        $error = "Username atau Password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login POS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0d1b2a, #1b4332, #000000);
            background-size: 400% 400%;
            animation: gradientFlow 12s ease infinite;
            height: 100vh;
            display: flex;
            align-items: center;
        }

        @keyframes gradientFlow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .login-card {
            border: none;
            border-radius: 15px;
            padding: 35px;
        }

        .login-title {
            font-weight: 600;
            color: #333;
        }

        .input-group-text {
            background-color: #f0f0f0;
        }

        .form-control {
            background-color: #fafafa;
            border-radius: 10px;
        }

        .btn-login {
            background-color: #28a745;
            border: none;
            border-radius: 10px;
            font-weight: 600;
        }

        .btn-login:hover {
            background-color: #218838;
        }

        .logo-pos {
            width: 70px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">

            <div class="card login-card shadow-lg">
                <div class="card-body text-center">

                    <h3 class="login-title mb-4">Login Kasir</h3>

                    <?php if($error): ?>
                        <div class="alert alert-danger text-center">
                            <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">

                        <div class="mb-3 text-start">
                            <label class="form-label fw-semibold">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" name="username" class="form-control" required placeholder="Masukkan username">
                            </div>
                        </div>

                        <div class="mb-3 text-start">
                            <label class="form-label fw-semibold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" class="form-control" required placeholder="Masukkan password">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-login text-white w-100 mt-3 py-2">
                            Masuk
                        </button>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</body>
</html>
