# myUnila Lost & Found

## 👥 Daftar Anggota Kelompok 20

| No | Nama | NPM |
|----|------|-----|
| 1  | Firman Farel Richardo | 2315061099 |
| 2  | Muhammad Robbani Narsam | 2315061029 |
| 3  | Ananda Fahmuzna Fauzi | 2315061009 |
| 4  | Mutiara Khairunnisa Zulkifli | 2315061060 |

---

## 📖 Judul & Summary Project

**myUnila Lost & Found** adalah aplikasi web berbasis Community & Organization Management untuk mengelola sistem kehilangan dan penemuan barang di lingkungan Universitas Lampung.

### Summary
Aplikasi ini memfasilitasi mahasiswa dan civitas akademika Unila untuk:
- Melaporkan barang yang hilang dengan detail lengkap (foto, lokasi, tanggal)
- Melaporkan barang temuan yang ditemukan di area kampus
- Mencari dan mencocokkan barang hilang dengan barang temuan
- Berkomunikasi melalui sistem komentar untuk koordinasi pengembalian barang
- Mengelola akun dengan sistem role (Admin dan User)

### Teknologi yang Digunakan
- **Frontend**: HTML5, CSS3 (Bootstrap), JavaScript Native
- **Backend**: PHP Native (tanpa framework)
- **Database**: MySQL
- **Version Control**: Git & GitHub

### Fitur Utama
1. **User Management**: Registrasi, login, logout, manajemen role (Admin/User)
2. **Laporan Barang Hilang**: CRUD laporan kehilangan dengan upload foto
3. **Laporan Barang Temuan**: CRUD laporan penemuan dengan upload foto
4. **Pencarian & Filter**: Cari berdasarkan kategori, lokasi, tanggal, status
5. **Sistem Komentar**: Komunikasi antar pengguna pada setiap laporan
6. **Dashboard Admin**: Kelola laporan, kelola user, statistik

---

## 🚀 Cara Menjalankan Aplikasi

### Prasyarat
- XAMPP atau Laragon (PHP 7.4+ dan MySQL)
- Web Browser (Chrome, Firefox, Edge)
- Git

### Langkah Instalasi

**1. Clone Repository**
```bash
git clone https://github.com/firmanfarelrichardo/TUBES_PRK_PEMWEB_2025.git
cd TUBES_PRK_PEMWEB_2025/kelompok/kelompok_20
```

**2. Setup Database**
- Jalankan XAMPP/Laragon dan aktifkan Apache + MySQL
- Buka phpMyAdmin di browser: `http://localhost/phpmyadmin`
- Buat database baru dengan nama: `myunila_lostfound`
- Import file SQL: klik database → Import → pilih file `database/myunila_lostfound.sql` → Go

**3. Konfigurasi Koneksi Database**
- Buka file `config/database.php`
- Sesuaikan kredensial database jika diperlukan:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'myunila_lostfound');
```

**4. Jalankan Aplikasi**
- Pastikan Apache dan MySQL sudah running
- Buka browser dan akses: `http://localhost/TUBES_PRK_PEMWEB_2025/kelompok/kelompok_20`
- Atau jika menggunakan Laragon: `http://localhost/kelompok/kelompok_20`

### Troubleshooting
- Jika error koneksi database: Cek kredensial di `config/database.php`
- Jika gambar tidak muncul: Pastikan folder `uploads/` memiliki permission write
- Jika halaman blank: Cek error di `php_error.log` atau aktifkan `display_errors` di php.ini


