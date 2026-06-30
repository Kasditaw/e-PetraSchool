<?php
// c:\Users\SD Kristen Petra 1\Sistem SD Kristen Petra 1\register_users.php

// Connect to MySQL server
$host = 'localhost';
$user = 'root';
$pass = ''; // Default XAMPP password is empty
$db   = 'db_petraschool';
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

echo "<!DOCTYPE html>
<html>
<head>
    <title>Inisialisasi Database - SD Kristen Petra 1</title>
    <link href='https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css'>
    <style>
        body { background-color: #050A18; color: #fff; font-family: 'Outfit', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
        .card { background: rgba(18, 28, 54, 0.6); padding: 40px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.08); max-width: 600px; box-shadow: 0 8px 32px rgba(0,0,0,0.5); width: 100%; }
        h3 { color: #D4AF37; margin-top: 0; font-size: 24px; font-weight: 700; border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding-bottom: 15px; }
        p { color: #94A3B8; line-height: 1.6; }
        ul { color: #94A3B8; padding-left: 20px; line-height: 1.8; }
        code { background: rgba(212, 175, 55, 0.15); color: #D4AF37; padding: 2px 6px; border-radius: 4px; font-family: monospace; }
        .btn { background: linear-gradient(135deg, #D4AF37 0%, #F3CD48 100%); color: #000; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; margin-top: 20px; box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4); }
        .alert-error { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #F87171; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class='card'>";

try {
    // 1. Connect to MySQL Server first
    $pdo = new PDO("mysql:host=$host;charset=$charset", $user, $pass, $options);
    
    // 2. Drop existing database if any, and create clean one
    $pdo->exec("DROP DATABASE IF EXISTS `$db`");
    $pdo->exec("CREATE DATABASE `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db`");
    
    // 3. Read database.sql file
    $sql_path = __DIR__ . '/database/database.sql';
    if (file_exists($sql_path)) {
        $sql = file_get_contents($sql_path);
        
        // Remove comments
        $sql = preg_replace('/--.*\n/', '', $sql);
        
        // Split queries by semicolon
        $queries = explode(';', $sql);
        
        $count = 0;
        foreach ($queries as $query) {
            $query = trim($query);
            if ($query !== '') {
                $pdo->exec($query);
                $count++;
            }
        }
        
        echo "<h3><i class='fa-solid fa-circle-check me-2'></i> Inisialisasi Database Sukses!</h3>";
        echo "<p>Database <code>db_petraschool</code> berhasil dibuat ulang secara bersih, dan sebanyak <strong>$count</strong> kueri berhasil dieksekusi.</p>";
        echo "<p>Empat akun demo berikut telah didaftarkan dan siap digunakan untuk masuk log:</p>";
        echo "<ul>
                <li><strong>Super Admin</strong>: Username <code>superadmin</code>, Password <code>admin123</code></li>
                <li><strong>Admin Sekolah</strong>: Username <code>admin</code>, Password <code>admin123</code></li>
                <li><strong>Guru</strong>: Username <code>guru</code>, Password <code>admin123</code></li>
                <li><strong>Kepala Sekolah</strong>: Username <code>kepsek</code>, Password <code>admin123</code></li>
              </ul>";
        echo "<p class='small text-secondary'>* Semua kata sandi di atas telah disandikan menggunakan enkripsi aman <code>bcrypt</code>.</p>";
        echo "<a href='login.php' class='btn'>Masuk Halaman Login <i class='fa-solid fa-right-to-bracket'></i></a>";
    } else {
        echo "<div class='alert-error'><i class='fa-solid fa-circle-xmark me-2'></i> Error: Berkas <code>database.sql</code> tidak ditemukan di folder database.</div>";
    }
} catch (Exception $e) {
    echo "<div class='alert-error'><i class='fa-solid fa-circle-xmark me-2'></i> Koneksi Gagal: " . $e->getMessage() . "</div>";
    echo "<p>Pastikan layanan Apache dan MySQL di control panel XAMPP Anda sudah aktif.</p>";
}

echo "</div>
</body>
</html>";
?>
