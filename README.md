# Program Pendaftaran Pasien Rumah Sakit (PHP + MySQL)

## Cara menjalankan (XAMPP / Laragon)
1. Salin folder `rs-pendaftaran` ke `htdocs` (XAMPP) atau `www` (Laragon).
2. Buka phpMyAdmin, tab Import, pilih `database.sql`, lalu jalankan.
3. Cek `includes/init.php`: sesuaikan DB_USER dan DB_PASS jika berbeda.
4. Buka `http://localhost/rs-pendaftaran/install.php` sekali untuk membuat akun awal, lalu HAPUS `install.php`.
5. Buka `http://localhost/rs-pendaftaran/login.php`.

Akun awal: `admin` / `admin123` dan `petugas` / `petugas123` (segera ganti).

## Alur
Login petugas -> Pasien baru (atau cari pasien lama) -> Daftar kunjungan (poli, dokter, tanggal, penjamin) -> Nomor antrean dan tiket cetak -> Kelola status antrean di halaman Antrean.

## Struktur
- database.sql  : tabel users, poli, dokter, pasien, kunjungan + data poli/dokter awal
- includes/     : init.php (koneksi, session, CSRF), header.php, footer.php
- login.php, logout.php, install.php
- index.php (antrean hari ini), pasien.php, pasien_baru.php, daftar.php, tiket.php
