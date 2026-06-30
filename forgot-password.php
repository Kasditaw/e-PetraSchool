<?php
// c:\Users\SD Kristen Petra 1\Sistem SD Kristen Petra 1\forgot-password.php

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/csrf.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ── CSRF Validation ─────────────────────────────────────────────────────
    check_csrf_request();

    $username = trim($_POST['username'] ?? '');

    // ── Input validation ─────────────────────────────────────────────────────
    if (strlen($username) > 50 || empty($username)) {
        $error = 'Input tidak valid.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // ── Generic message: Tidak membedakan username ada/tidak (mencegah username enumeration) ──
            // Jika sistem ini sudah memiliki email, kirim token reset di sini.
            // Untuk saat ini, hanya tampilkan pesan generik.
            $success = "Jika username Anda terdaftar, administrator akan menghubungi Anda untuk mereset password. Silakan hubungi admin sekolah.";

            if ($user) {
                write_audit_log("Permintaan reset password untuk user '$username'.", $user['id'], $username);
            }
        } catch (Exception $e) {
            error_log("Forgot password error: " . $e->getMessage());
            $error = "Terjadi kesalahan sistem. Silakan coba lagi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - SD Kristen Petra 1</title>
    <!-- Favicon -->
    <link rel="icon" href="https://img.icons8.com/color/48/school.png" type="image/x-icon">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100" style="background-color: var(--bg-main);">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-5">
                
                <div class="text-center mb-4">
                    <div class="brand-icon mx-auto mb-3" style="width: 50px; height: 50px; border-radius: 12px; font-size: 1.75rem; background-color: transparent !important; box-shadow: none !important; display: flex; align-items: center; justify-content: center;">
                        <img src="assets/images/logo.png" alt="Logo Petra" style="height: 50px; width: 50px; object-fit: contain;">
                    </div>
                    <h4 class="text-white fw-bold mb-0">SD KRISTEN PETRA 1</h4>
                    <p class="text-gold text-uppercase small font-monospace tracking-wide mb-0" style="font-size: 10px; letter-spacing: 2px;">SISTEM MANAJEMEN SEKOLAH</p>
                </div>

                <div class="glass-card">
                    <h5 class="text-center text-white mb-3 fw-semibold"><i class="fa-solid fa-key me-2 text-gold"></i> Lupa Password</h5>
                    <p class="text-center text-secondary small mb-4">Masukkan username Anda untuk mereset kata sandi Anda kembali ke sandi default demo.</p>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger border-0 text-white bg-danger bg-opacity-25 small mb-3 py-2" role="alert">
                            <i class="fa-solid fa-circle-xmark me-2"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success border-0 text-white bg-success bg-opacity-25 small mb-3 py-2" role="alert">
                            <i class="fa-solid fa-circle-check me-2"></i> <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="forgot-password.php">
                        <?php csrf_input(); ?>

                        <div class="mb-4">
                            <label for="username" class="form-label form-label-custom">Username Anda</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark-select border-secondary text-secondary"><i class="fa-solid fa-user"></i></span>
                                <input type="text" name="username" id="username" class="form-control form-control-custom" placeholder="Masukkan username" required autocomplete="off">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-gold w-100 py-2.5 mb-3 d-flex justify-content-center align-items-center gap-2">
                            <span>Kirim Reset Request</span> <i class="fa-solid fa-paper-plane"></i>
                        </button>
                        
                        <div class="text-center">
                            <a href="login.php" class="small text-secondary text-decoration-none hover-underline"><i class="fa-solid fa-chevron-left me-1"></i> Kembali ke Login</a>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

</body>
</html>
