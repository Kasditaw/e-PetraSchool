# Panduan Deployment e-PetraSchool1

Dokumen ini berisi panduan langkah-langkah untuk men-deploy aplikasi e-PetraSchool1 ke shared hosting (cPanel/Plesk).

---

## Persyaratan Hosting

| Komponen | Minimum |
|----------|---------|
| PHP | 7.4+ (disarankan 8.0+) |
| MySQL | 5.7+ / MariaDB 10.3+ |
| Apache | 2.4+ dengan `mod_rewrite` aktif |
| PHP Extension | `pdo_mysql`, `mbstring`, `json`, `gd` |
| Disk Space | ± 100 MB (tanpa uploads) |

---

## Langkah-Langkah Deployment

### 1. Buat Database di cPanel

1. Login ke **cPanel** → **MySQL Databases**
2. Buat database baru (contoh: `username_petraschool`)
3. Buat user database baru dengan password kuat
4. Assign user ke database dengan hak akses **ALL PRIVILEGES**

### 2. Import Struktur Database

1. Buka **phpMyAdmin** di cPanel
2. Pilih database yang baru dibuat
3. Klik tab **Import**
4. Upload file `database/database.sql`
5. Klik **Go** / **Import**

### 3. Konfigurasi Environment

1. Buka file `config/env.php`
2. Ubah pengaturan sesuai hosting:

```php
return [
    'APP_ENV'     => 'production',        // WAJIB diubah ke 'production'
    'APP_URL'     => '/',                  // '/' jika di root domain, '/subfolder/' jika di subfolder
    'FORCE_HTTPS' => true,                 // true jika hosting memiliki SSL
    'DB_HOST'     => 'localhost',           // biasanya 'localhost' di shared hosting
    'DB_NAME'     => 'username_petraschool', // nama database dari cPanel
    'DB_USER'     => 'username_petrauser',   // user database dari cPanel
    'DB_PASS'     => 'password_kuat_anda',   // password database
    'DB_CHARSET'  => 'utf8mb4',
];
```

### 4. Upload File ke Hosting

**File yang HARUS di-upload:**
```
.htaccess
index.php
login.php
logout.php
forgot-password.php
backup.php
config/              (semua file termasuk env.php yang sudah diisi)
includes/            (semua file)
modules/             (semua file dan subfolder)
assets/css/
assets/js/
assets/images/
assets/uploads/      (buat folder kosong: guru/, inventaris/, foto_profil/)
database/.htaccess   (proteksi folder, file SQL tidak perlu di-upload)
```

**File yang JANGAN di-upload:**
```
scratch/                 ← 265+ file debug/import (BERBAHAYA)
register_users.php       ← Bisa menghapus seluruh database
login_backup.php         ← File backup development
scratch_guru_mapel.php   ← File scratch development
database/*.sql           ← File SQL (sudah di-import manual)
database/*.php           ← File migrasi (hanya untuk development)
```

### 5. Aktifkan HTTPS (Opsional tapi Disarankan)

Jika hosting memiliki SSL (Let's Encrypt / SSL gratis):

1. Buka file `.htaccess` di root
2. **Uncomment** baris berikut (hapus tanda `#`):
```apache
RewriteEngine On
RewriteCond %{HTTPS} !=on
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```
3. Pastikan `FORCE_HTTPS` di `env.php` diset `true`

### 6. Set Permissions

Di cPanel File Manager, set permissions:
```
config/env.php      → 600 (hanya owner yang bisa baca)
assets/uploads/     → 755
error.log           → 644 (jika ada)
```

---

## Checklist Keamanan Pre-Launch

- [ ] `APP_ENV` diset ke `production` di `config/env.php`
- [ ] Password database kuat (minimal 12 karakter, kombinasi huruf/angka/simbol)
- [ ] `register_users.php` **tidak** ada di hosting
- [ ] Folder `scratch/` **tidak** ada di hosting
- [ ] Folder `database/` hanya berisi `.htaccess` (tanpa file `.sql` dan `.php`)
- [ ] `.htaccess` root berfungsi (coba akses `domain.com/config/db.php` → harus 403 Forbidden)
- [ ] HTTPS aktif dan berfungsi
- [ ] Login & navigasi berfungsi normal
- [ ] Upload file (foto profil, dokumen RPP) berfungsi
- [ ] Ganti password default semua akun demo (`admin123` → password kuat)

---

## Troubleshooting

### Error 500 (Internal Server Error)
- Periksa apakah `mod_rewrite` aktif di hosting
- Periksa `error.log` di root folder aplikasi
- Coba disable `.htaccess` sementara untuk identifikasi masalah

### Koneksi Database Gagal
- Pastikan kredensial di `config/env.php` sesuai dengan cPanel
- Di shared hosting, DB_HOST biasanya `localhost`
- Pastikan user database sudah di-assign ke database dengan benar

### CSS/JS Tidak Muncul
- Periksa `APP_URL` di `config/env.php` — harus sesuai path di hosting
- Jika aplikasi di root domain: `'APP_URL' => '/'`
- Jika di subfolder: `'APP_URL' => '/nama-subfolder/'`

### Session / Login Bermasalah
- Pastikan folder `tmp` PHP writable
- Di cPanel, periksa **PHP Configuration** → `session.save_path`
