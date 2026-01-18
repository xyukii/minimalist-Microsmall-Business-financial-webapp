`# Aplikasi Keuangan Multi-Pengguna (PHP + SQLite)

Aplikasi web sederhana untuk mencatat produk, penjualan, dan biaya dengan peran **admin** dan **user**. Dibangun dengan PHP, PDO SQLite, dan Bootstrap 5.

## Fitur
- Login dengan peran admin dan user.
- Admin membuat/mengelola pengguna (registrasi publik dinonaktifkan).
- Setiap pengguna memiliki tabel produk, penjualan, dan biaya sendiri (isolasi data).
- CRUD produk, penjualan, dan biaya; laporan laba rugi sederhana.
- Ekspor laporan ke PDF.
- Dukungan format Rupiah (tampilan dengan pemisah ribuan, input toleran titik/koma).

## Persyaratan
- PHP 8.x dengan ekstensi SQLite3 aktif.
- Web server (Apache/Nginx). Pada shared hosting, gunakan root `public_html`.

## Konfigurasi Lokal
1. Clone repo: `git clone ...`
2. Pastikan PHP CLI tersedia: `php -v`.
3. Jalankan server dev: `php -S localhost:8000 -t .`
4. Buka `http://localhost:8000/login.php`.
5. Admin default otomatis dibuat: username `admin`, password `admin123`. Segera ganti setelah login.

> Database SQLite disimpan di folder `data/` sebagai `app.db`. Tabel per pengguna dibuat otomatis saat akun dibuat.

## Struktur Penting
- `includes/` – koneksi DB, helper, auth.
- `user/` – panel pengguna (produk, penjualan, biaya, laporan).
- `admin/` – panel admin untuk kelola pengguna.
- `assets/` – CSS.
- `data/` – file SQLite (pastikan writable di hosting).

## Deploy ke Shared Hosting (Contoh : Hostinger)
1. **Siapkan berkas**: zip seluruh isi proyek.
2. **Upload & ekstrak** di `public_html` (atau root subdomain) via File Manager.
3. **Pastikan path DB** di `includes/db.php` menunjuk ke lokasi writable, misal `__DIR__ . '/../data/app.db'`. Folder `data/` harus ada.
4. **Permission**: folder `data/` 755 (atau 775 jika perlu), file PHP 644. Tambahkan `.htaccess` di `data/` dengan `Deny from all` untuk mencegah akses langsung (opsional tapi disarankan).
5. **Cek ekstensi SQLite3** aktif (biasanya default di Hostinger).
6. **Akses situs**: buka `/login.php`, login admin `admin/admin123`, buat user via panel admin.
7. **Amankan**: ganti password admin; pastikan `display_errors` off di produksi; lakukan backup berkala file `data/app.db`.

## Keamanan (dibundel)
- Session hardening: cookie `HttpOnly`, `Secure` (jika HTTPS), `SameSite=Lax`, regenerasi ID saat login, dan auto-logout setelah 30 menit idle.
- Proteksi database: `.htaccess` di folder `data/` untuk blok akses langsung.
- HTTPS + header: `.htaccess` di root memaksa HTTPS dan mengaktifkan header keamanan (X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy, CSP).
- Permission: file 644, folder 755; hindari 777.

## Tips Operasional
- **Backup**: unduh `data/app.db` secara berkala (atau gunakan backup hoster).
- **Update versi**: unggah file baru, jangan timpa folder `data/` kecuali ingin reset database.
- **HTTPS**: paksa HTTPS dengan rule di `.htaccess` jika memakai Apache.

## Lisensi
Gunakan secara bebas untuk kebutuhan Anda. Tidak ada garansi.
