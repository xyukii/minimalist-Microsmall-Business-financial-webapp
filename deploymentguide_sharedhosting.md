# Panduan Deployment ke Shared Hosting (Hostinger)

Dokumen ini berisi panduan lengkap untuk melakukan deployment aplikasi ini ke shared hosting seperti Hostinger, Niagahoster, atau provider hosting shared lainnya.

## 📋 Persyaratan

### Kebutuhan Hosting
- **PHP Version**: 7.4 atau lebih tinggi
- **MySQL/MariaDB**: 5.7 atau lebih tinggi
- **Storage**: Minimal 100 MB
- **Extension PHP yang diperlukan**:
  - mysqli
  - pdo_mysql
  - session
  - gd (untuk PDF generation)
  - mbstring

### Yang Perlu Disiapkan
1. Akun hosting aktif (Hostinger/Niagahoster/dll)
2. Akses ke cPanel atau File Manager
3. Akses ke phpMyAdmin
4. File aplikasi (semua file dalam project ini)

## 🚀 Langkah-Langkah Deployment

### 1. Persiapan File

#### A. Download/Export Project
```bash
# Jika dari Git
git clone <repository-url>
cd websitemama

# Atau download sebagai ZIP dari GitHub
```

#### B. Compress Project
Buat file ZIP yang berisi semua file project:
- login.php
- logout.php
- register.php
- README.md
- Folder: admin/, assets/, data/, includes/, user/

**PENTING**: Pastikan file `includes/db.php` akan dikonfigurasi setelah upload.

### 2. Upload ke Hosting

#### Metode A: Menggunakan File Manager (Recommended)

1. **Login ke cPanel** hosting Anda
2. Buka **File Manager**
3. Navigasi ke folder `public_html` (atau `htdocs`/`www` tergantung provider)
4. **Upload file ZIP** project
5. **Extract** file ZIP di dalam `public_html`
6. **Pindahkan semua file** dari folder hasil extract ke `public_html` langsung
7. **Hapus** file ZIP dan folder kosong

#### Metode B: Menggunakan FTP

1. Download FTP Client (FileZilla recommended)
2. Koneksi ke hosting menggunakan kredensial FTP dari cPanel:
   - **Host**: ftp.yourdomain.com
   - **Username**: username dari cPanel
   - **Password**: password FTP
   - **Port**: 21
3. Upload semua file ke folder `public_html`

### 3. Setup Database

#### A. Buat Database MySQL

1. Login ke **cPanel**
2. Buka **MySQL Databases**
3. **Buat Database Baru**:
   - Nama: `usernamecp_financial` (sesuaikan dengan naming convention hosting)
   - Klik "Create Database"

4. **Buat User Database**:
   - Username: `usernamecp_dbuser`
   - Password: Gunakan password yang kuat
   - Klik "Create User"

5. **Assign User ke Database**:
   - Pilih user dan database yang baru dibuat
   - Klik "Add"
   - Berikan **ALL PRIVILEGES**
   - Klik "Make Changes"

#### B. Import Database Schema

1. Buka **phpMyAdmin** dari cPanel
2. Pilih database yang baru dibuat
3. Klik tab **"Import"**
4. Upload file SQL schema (jika ada) atau buat tabel manual:

```sql
-- Jalankan query ini di phpMyAdmin

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    sale_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT,
    amount DECIMAL(10,2) NOT NULL,
    expense_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### 4. Konfigurasi Database

Edit file **`includes/db.php`** menggunakan File Manager:

1. Buka File Manager
2. Navigasi ke `public_html/includes/db.php`
3. Klik kanan → **Edit** atau **Code Editor**
4. Update konfigurasi database:

```php
<?php
// Konfigurasi Database untuk Shared Hosting
$host = 'localhost';  // Biasanya localhost untuk shared hosting
$dbname = 'usernamecp_financial';  // Nama database yang dibuat
$username = 'usernamecp_dbuser';   // Username database
$password = 'password_database_anda';  // Password database

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
?>
```

5. **Save** file

### 5. Set Permissions (File Permissions)

Pastikan permission file yang benar:

```
Folders: 755 (rwxr-xr-x)
Files: 644 (rw-r--r--)
```

**Folder yang perlu write access**:
- `data/` → Set ke **755** atau **775**

Cara set permission di File Manager:
1. Klik kanan pada folder/file
2. Pilih **"Change Permissions"**
3. Set angka permission yang sesuai
4. Klik "Change Permissions"

### 6. Testing

1. Buka browser dan akses: `https://yourdomain.com`
2. Test halaman utama
3. Test halaman register: `https://yourdomain.com/register.php`
4. Test halaman login: `https://yourdomain.com/login.php`

#### Buat Admin User Pertama

Gunakan phpMyAdmin untuk insert admin user:

```sql
INSERT INTO users (username, password, email, role) 
VALUES (
    'admin', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- password: "password"
    'admin@yourdomain.com', 
    'admin'
);
```

**PENTING**: Segera login dan ganti password default!

## 🔒 Keamanan & Optimasi

### 1. File .htaccess

Buat file **`.htaccess`** di root folder (`public_html`) untuk keamanan:

```apache
# Disable directory browsing
Options -Indexes

# Protect includes directory
<FilesMatch "\.(php|inc)$">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
</FilesMatch>

# Allow only specific PHP files
<Files "login.php">
    Require all granted
</Files>
<Files "register.php">
    Require all granted
</Files>
<Files "logout.php">
    Require all granted
</Files>

# Protect configuration files
<Files "db.php">
    Require all denied
</Files>

# Enable error logging (disable display for production)
php_flag display_errors Off
php_flag log_errors On

# Security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>

# HTTPS redirect (jika sudah install SSL)
# RewriteEngine On
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 2. Install SSL Certificate

1. Login ke cPanel
2. Buka **SSL/TLS Status**
3. Pilih domain Anda
4. Klik **"Run AutoSSL"** (gratis dari Let's Encrypt)
5. Tunggu proses selesai
6. Enable HTTPS redirect di .htaccess

### 3. Security Checklist

- ✅ Ganti semua password default
- ✅ Aktifkan SSL/HTTPS
- ✅ Set file permissions yang benar
- ✅ Protect file konfigurasi
- ✅ Disable PHP error display di production
- ✅ Regular backup database dan file
- ✅ Update PHP ke versi terbaru yang stabil

## 🐛 Troubleshooting

### Error "Database connection failed"
- Periksa kredensial database di `includes/db.php`
- Pastikan database sudah dibuat
- Pastikan user sudah di-assign ke database dengan privileges yang benar
- Cek apakah hostname menggunakan 'localhost'

### Error "500 Internal Server Error"
- Periksa file permissions
- Cek error log di cPanel → Error Log
- Pastikan versi PHP kompatibel (7.4+)
- Periksa syntax error di PHP files

### Error "404 Not Found"
- Pastikan file berada di folder `public_html` langsung
- Periksa nama file (case-sensitive di Linux hosting)
- Cek .htaccess jika ada rewrite rules

### Session tidak berfungsi
- Periksa `session_start()` ada di setiap halaman yang membutuhkan
- Pastikan folder session writable (biasanya otomatis di shared hosting)
- Cek PHP session configuration di cPanel → Select PHP Version

### PDF tidak ter-generate
- Pastikan extension GD terinstall
- Cek PHP memory limit di cPanel
- Pastikan folder `data/` writable

## 📊 Backup & Maintenance

### Backup Regular

1. **Backup Files**:
   - cPanel → File Manager → Compress folder `public_html`
   - Download file ZIP

2. **Backup Database**:
   - phpMyAdmin → Export
   - Format: SQL
   - Download file .sql

3. **Automated Backup**:
   - Gunakan fitur cPanel Backup
   - Set schedule: Weekly atau Daily

### Update Aplikasi

1. Backup dulu file dan database
2. Upload file baru
3. Jangan overwrite `includes/db.php` (atau backup dulu)
4. Test aplikasi

## 📝 Catatan Penting Hostinger

### Hostinger Specific Settings

1. **Database Host**: Biasanya `localhost`, tapi bisa juga `mysql.hostinger.com` (cek di phpMyAdmin)
2. **PHP Version**: Pilih di cPanel → PHP Configuration → minimal 7.4
3. **Max Upload Size**: Default 128MB (bisa diubah di PHP Options)
4. **Execution Time**: Default 30s (bisa diubah jika perlu)

### File Manager Path
- Root web: `/home/username/public_html/`
- Logs: `/home/username/logs/`

### Support
- Live Chat: 24/7
- Knowledge Base: https://support.hostinger.com
- Email Support: support@hostinger.com

## ✅ Checklist Deployment

- [ ] File sudah terupload semua
- [ ] Database sudah dibuat
- [ ] User database sudah dibuat dan assigned
- [ ] Tabel database sudah dibuat
- [ ] File `includes/db.php` sudah dikonfigurasi
- [ ] File permissions sudah diset
- [ ] Admin user sudah dibuat
- [ ] Test login berhasil
- [ ] Test register berhasil
- [ ] Test fitur CRUD berhasil
- [ ] SSL sudah terinstall
- [ ] .htaccess sudah dibuat
- [ ] Backup pertama sudah dibuat

## 🎉 Selesai!

Aplikasi Anda sekarang sudah live di: `https://yourdomain.com`

Untuk pertanyaan atau masalah, silakan buat issue di repository GitHub atau hubungi support hosting Anda.

---

**Dibuat untuk**: Deployment aplikasi Financial Management Web App  
**Kompatibel dengan**: Hostinger, Niagahoster, Rumahweb, dan shared hosting lainnya  
**Terakhir diupdate**: Januari 2026
