<?php
// c:\Users\SD Kristen Petra 1\Sistem SD Kristen Petra 1\login.php

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/csrf.php';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    header("Location: modules/dashboard/index.php");
    exit;
}

$error = '';
if (isset($_GET['timeout'])) {
    $error = 'Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan masuk kembali.';
}
$username = '';

// ── Brute-Force Rate Limiting ────────────────────────────────────────────────
// Lockout after 5 failed attempts for 15 minutes
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_SECONDS', 15 * 60);

$login_attempts    = $_SESSION['login_attempts'] ?? 0;
$last_attempt_time = $_SESSION['last_attempt_time'] ?? 0;

// Check if currently locked out
if ($login_attempts >= LOGIN_MAX_ATTEMPTS) {
    $elapsed = time() - $last_attempt_time;
    if ($elapsed < LOGIN_LOCKOUT_SECONDS) {
        $remaining = ceil((LOGIN_LOCKOUT_SECONDS - $elapsed) / 60);
        $error = "Terlalu banyak percobaan login gagal. Akun dikunci selama $remaining menit lagi. Coba lagi nanti.";
    } else {
        // Lockout period over — reset counters
        $_SESSION['login_attempts'] = 0;
        $login_attempts = 0;
    }
}

// Process Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {

    // ── CSRF Validation ──────────────────────────────────────────────────────
    check_csrf_request();

    $username    = trim($_POST['username'] ?? '');
    $password    = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);

    // ── Input Length Validation ──────────────────────────────────────────────
    if (strlen($username) > 50 || strlen($password) > 128) {
        $error = 'Input tidak valid.';
    } elseif (empty($username) || empty($password)) {
        $error = 'Username dan Password tidak boleh kosong.';
    } else {
        try {
            // Find user in database with their role
            $stmt = $pdo->prepare("SELECT u.*, r.nama_role FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // ── Reset login attempt counter on success ────────────────
                $_SESSION['login_attempts'] = 0;
                unset($_SESSION['last_attempt_time']);

                if (isset($user['status_aktif']) && $user['status_aktif'] === 'Tidak Aktif') {
                    $error = 'Akun Anda telah dinonaktifkan oleh administrator!';
                    write_audit_log("Gagal login: Percobaan masuk log oleh akun nonaktif '$username'.", null, $username);
                } else {
                    // Successful login
                    $_SESSION['user_id']  = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['nama']     = $user['nama'];
                    $_SESSION['role']     = $user['nama_role'];
                    $_SESSION['role_id']  = $user['role_id'];
                    $_SESSION['guru_id']  = $user['guru_id'] ?? null;

                    // ── Handle Remember Me Cookie ─────────────────────────
                    if ($remember_me) {
                        $token = bin2hex(random_bytes(32));
                        $update_stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                        $update_stmt->execute([$token, $user['id']]);

                        // Detect HTTPS for Secure flag
                        $is_secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                                     || (($_SERVER['SERVER_PORT'] ?? 80) == 443);
                        setcookie('petra_remember', $token, time() + (30 * 24 * 60 * 60), '/', '', $is_secure, true);
                    }

                    // Regenerate session ID for security
                    session_regenerate_id(true);

                    // Regenerate CSRF token to prevent token fixation
                    unset($_SESSION['csrf_token']);

                    // Log activity
                    write_audit_log("Melakukan login ke dalam sistem.");

                    // Redirect to dashboard
                    header("Location: modules/dashboard/index.php");
                    exit;
                }
            } else {
                // ── Track failed attempts ─────────────────────────────────
                $_SESSION['login_attempts'] = ($login_attempts + 1);
                $_SESSION['last_attempt_time'] = time();
                $attempts_left = LOGIN_MAX_ATTEMPTS - $_SESSION['login_attempts'];

                if ($attempts_left > 0) {
                    $error = 'Username atau Password salah! (' . $attempts_left . ' percobaan tersisa)';
                } else {
                    $error = 'Terlalu banyak percobaan login gagal. Akun dikunci selama ' . (LOGIN_LOCKOUT_SECONDS / 60) . ' menit.';
                }
                write_audit_log("Gagal login: Username/Password tidak cocok untuk user '$username'.", null, $username);
            }
        } catch (Exception $e) {
            // Log technical detail but don't expose it to the user
            error_log("Login error: " . $e->getMessage());
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
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
    <!-- Custom CSS (General app variables, overridden below for isolated design) -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        body.login-page {
            background: #ebf1f5 !important;
            font-family: 'Outfit', sans-serif !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-height: 100vh !important;
            margin: 0 !important;
            color: #1e293b !important;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
            padding: 15px;
        }

        .login-card {
            background: #ffffff !important;
            border-radius: 16px !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04), 0 1px 8px rgba(0, 0, 0, 0.02) !important;
            padding: 2.5rem 2rem !important;
            border: none !important;
            position: relative;
            transition: none !important;
            transform: none !important;
        }

        .login-card:hover {
            transform: none !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04), 0 1px 8px rgba(0, 0, 0, 0.02) !important;
            border: none !important;
        }

        .login-logo-circle {
            width: 60px;
            height: 60px;
            background-color: #2563eb !important;
            border-radius: 50% !important;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem auto;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .login-logo-circle i {
            color: #ffffff !important;
            font-size: 1.75rem !important;
        }

        .login-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.25rem;
            text-align: center;
        }

        .login-subtitle {
            font-size: 0.85rem;
            color: #475569;
            font-weight: 500;
            margin-bottom: 0.15rem;
            text-align: center;
        }

        .login-meta {
            font-size: 0.75rem;
            color: #94a3b8;
            font-weight: 500;
            margin-bottom: 1.75rem;
            text-align: center;
        }

        .login-form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.375rem;
            display: block;
        }

        .login-input-group {
            display: flex;
            align-items: center;
            background-color: #eff6ff !important;
            border: 1px solid #dbeafe !important;
            border-radius: 8px !important;
            padding: 0 14px !important;
            margin-bottom: 1.25rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            height: 46px;
        }

        .login-input-group:focus-within {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important;
            background-color: #eff6ff !important;
        }

        .login-input-group i {
            color: #94a3b8;
            font-size: 0.95rem;
            margin-right: 12px;
            width: 16px;
            text-align: center;
        }

        .login-input-field {
            border: none !important;
            background: transparent !important;
            color: #1e293b !important;
            width: 100% !important;
            outline: none !important;
            font-size: 0.9rem !important;
            padding: 0 !important;
            height: 100%;
        }

        .login-input-field::placeholder {
            color: #94a3b8 !important;
            opacity: 0.8;
        }

        .login-checkbox-label {
            font-size: 0.85rem;
            color: #64748b;
            user-select: none;
            cursor: pointer;
        }

        .login-checkbox {
            border: 1px solid #cbd5e1 !important;
            border-radius: 4px !important;
            width: 16px;
            height: 16px;
            cursor: pointer;
            background-color: #ffffff !important;
        }

        .login-link {
            color: #2563eb !important;
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none !important;
        }

        .login-link:hover {
            text-decoration: underline !important;
        }

        .login-btn-submit {
            background: #2563eb !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            border: none !important;
            border-radius: 8px !important;
            padding: 0.75rem 1rem !important;
            width: 100% !important;
            font-size: 0.95rem !important;
            cursor: pointer !important;
            transition: background-color 0.2s, transform 0.1s !important;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.15) !important;
            height: 46px;
        }

        .login-btn-submit:hover {
            background: #1d4ed8 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 6px 15px rgba(37, 99, 235, 0.25) !important;
        }

        .login-btn-submit:active {
            transform: translateY(0) !important;
        }

        .login-divider {
            border-top: 1px solid #e2e8f0 !important;
            margin: 1.5rem 0 1rem 0 !important;
        }

        .demo-title {
            text-align: center;
            font-size: 0.75rem;
            color: #94a3b8;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .demo-btn-group {
            display: flex;
            justify-content: center;
            gap: 8px;
        }

        .demo-btn {
            background: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 6px !important;
            color: #64748b !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            padding: 4px 10px !important;
            cursor: pointer !important;
            transition: all 0.2s !important;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02) !important;
        }

        .demo-btn:hover {
            background: #f1f5f9 !important;
            color: #0f172a !important;
            border-color: #cbd5e1 !important;
        }

        .login-alert {
            border-radius: 8px !important;
            padding: 10px 14px !important;
            font-size: 0.85rem !important;
            margin-bottom: 1.25rem !important;
            border: none !important;
        }
    </style>
</head>
<body class="login-page">

    <div class="login-container">
        <div class="login-card">
            <!-- Top Petra Logo circle -->
            <div class="login-logo-circle" style="background-color: transparent !important; box-shadow: none !important;">
                <img src="assets/images/logo.png" alt="Logo Petra" style="height: 60px; width: 60px; object-fit: contain;">
            </div>
            
            <!-- Branding titles -->
            <div class="login-title">e-PetraSchool</div>
            <div class="login-subtitle">Electronic Petra School Management</div>
            <div class="login-meta">SD Kristen Petra 1 &bull; T.A 2025/2026</div>

            <!-- Error Alerts -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger login-alert text-danger bg-danger bg-opacity-10" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="login.php">
                <!-- CSRF Token -->
                <?php csrf_input(); ?>

                <!-- Username input -->
                <div class="mb-3">
                    <label for="username" class="login-form-label">Username</label>
                    <div class="login-input-group">
                        <i class="fa-regular fa-user"></i>
                        <input type="text" name="username" id="username" class="login-input-field" placeholder="Masukkan username" value="<?php echo htmlspecialchars($username); ?>" required autocomplete="off">
                    </div>
                </div>

                <!-- Password input -->
                <div class="mb-3">
                    <label for="password" class="login-form-label">Password</label>
                    <div class="login-input-group">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="password" id="password" class="login-input-field" placeholder="Masukkan password" required>
                    </div>
                </div>

                <!-- Remember Me and Forgot Password links -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-center gap-2">
                        <input class="login-checkbox" type="checkbox" name="remember_me" id="remember_me">
                        <label class="login-checkbox-label" for="remember_me">Ingat saya</label>
                    </div>
                    <a href="forgot-password.php" class="login-link">Lupa password?</a>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="login-btn-submit">
                    Masuk ke Sistem
                </button>
            </form>

        </div>
    </div>

</body>
</html>
