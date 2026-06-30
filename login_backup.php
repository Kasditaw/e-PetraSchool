<?php
// c:\Users\SD Kristen Petra 1\Sistem SD Kristen Petra 1\login.php

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/captcha.php';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    header("Location: modules/dashboard/index.php");
    exit;
}

$error = '';
$username = '';
$role_input = '';

// Generate CAPTCHA if not already set
$captcha_question = get_captcha_question();

// Process Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role_input = $_POST['role'] ?? '';
    $captcha_input = trim($_POST['captcha'] ?? '');
    $remember_me = isset($_POST['remember_me']);

    // Check CAPTCHA
    if (!validate_captcha($captcha_input)) {
        $error = 'Captcha salah! Silakan coba lagi.';
        // Regenerate CAPTCHA
        $captcha_question = generate_captcha();
    } else {
        try {
            // Find user in database with their role
            $stmt = $pdo->prepare("SELECT u.*, r.nama_role FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Verify if selected role matches database role
                if ($user['nama_role'] !== $role_input) {
                    $error = 'Username/Password cocok, tetapi Hak Akses (Role) terpilih salah.';
                    write_audit_log("Gagal login: Salah memilih role '$role_input' untuk user '$username'.", null, $username);
                } else {
                    // Successful login
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['nama'] = $user['nama'];
                    $_SESSION['role'] = $user['nama_role'];
                    $_SESSION['role_id'] = $user['role_id'];

                    // Handle Remember Me Cookie
                    if ($remember_me) {
                        $token = bin2hex(random_bytes(32));
                        // Save token to database
                        $update_stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                        $update_stmt->execute([$token, $user['id']]);
                        
                        // Set cookie for 30 days
                        setcookie('petra_remember', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
                    }

                    // Regenerate session ID for security
                    session_regenerate_id(true);

                    // Log activity
                    write_audit_log("Melakukan login ke dalam sistem.");

                    // Redirect to dashboard
                    header("Location: modules/dashboard/index.php");
                    exit;
                }
            } else {
                $error = 'Username atau Password salah!';
                write_audit_log("Gagal login: Username/Password tidak cocok untuk user '$username'.", null, $username);
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
        
        // Regenerate CAPTCHA on any submit attempt for security
        $captcha_question = generate_captcha();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Manajemen Sekolah SD Kristen Petra 1</title>
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
                    <div class="brand-icon mx-auto mb-3" style="width: 50px; height: 50px; border-radius: 12px; font-size: 1.75rem;">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <h4 class="text-white fw-bold mb-0">SD KRISTEN PETRA 1</h4>
                    <p class="text-gold text-uppercase small font-monospace tracking-wide mb-0" style="font-size: 10px; letter-spacing: 2px;">SISTEM MANAJEMEN SEKOLAH</p>
                </div>

                <div class="glass-card">
                    <h5 class="text-center text-white mb-4 fw-semibold"><i class="fa-solid fa-lock me-2 text-gold"></i> Masuk Akun</h5>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger border-0 text-white bg-danger bg-opacity-25 small mb-4 py-2.5" role="alert">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="login.php">
                        <!-- CSRF Token -->
                        <?php csrf_input(); ?>

                        <div class="mb-3">
                            <label for="username" class="form-label form-label-custom">Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark-select border-secondary text-secondary"><i class="fa-solid fa-user"></i></span>
                                <input type="text" name="username" id="username" class="form-control form-control-custom" placeholder="Masukkan username" value="<?php echo htmlspecialchars($username); ?>" required autocomplete="off">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label form-label-custom">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark-select border-secondary text-secondary"><i class="fa-solid fa-key"></i></span>
                                <input type="password" name="password" id="password" class="form-control form-control-custom" placeholder="Masukkan password" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="role" class="form-label form-label-custom">Hak Akses (Role)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark-select border-secondary text-secondary"><i class="fa-solid fa-shield"></i></span>
                                <select name="role" id="role" class="form-select form-control-custom bg-dark-select text-white" style="cursor: pointer;" required>
                                    <option value="" disabled <?php echo empty($role_input) ? 'selected' : ''; ?>>Pilih Role...</option>
                                    <option value="Super Admin" <?php echo $role_input === 'Super Admin' ? 'selected' : ''; ?>>Super Admin</option>
                                    <option value="Admin" <?php echo $role_input === 'Admin' ? 'selected' : ''; ?>>Admin Sekolah</option>
                                    <option value="Guru" <?php echo $role_input === 'Guru' ? 'selected' : ''; ?>>Guru / Wali Kelas</option>
                                    <option value="Kepala Sekolah" <?php echo $role_input === 'Kepala Sekolah' ? 'selected' : ''; ?>>Kepala Sekolah</option>
                                </select>
                            </div>
                        </div>

                        <div class="row align-items-center mb-3">
                            <div class="col-6">
                                <label class="form-label form-label-custom mb-2">CAPTCHA: <span class="text-gold fw-bold font-monospace"><?php echo $captcha_question; ?></span></label>
                            </div>
                            <div class="col-6">
                                <input type="number" name="captcha" id="captcha" class="form-control form-control-custom py-1" placeholder="Hasilnya" required autocomplete="off">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input bg-dark-select border-secondary" type="checkbox" name="remember_me" id="remember_me" style="cursor: pointer;">
                                <label class="form-check-label small text-secondary" for="remember_me" style="cursor: pointer; user-select: none;">
                                    Remember Me
                                </label>
                            </div>
                            <a href="forgot-password.php" class="small text-gold text-decoration-none hover-underline">Lupa Password?</a>
                        </div>

                        <button type="submit" class="btn btn-gold w-100 py-2.5 mb-2 d-flex justify-content-center align-items-center gap-2">
                            <span>Masuk Sistem</span> <i class="fa-solid fa-right-to-bracket"></i>
                        </button>
                    </form>
                </div>
                
                <div class="text-center text-secondary small" style="font-size: 11px;">
                    <span>&copy; <?php echo date('Y'); ?> SD Kristen Petra 1. Semua Hak Dilindungi.</span>
                </div>

            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
