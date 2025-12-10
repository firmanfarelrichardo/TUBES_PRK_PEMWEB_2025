<?php
// seed.php - Script untuk Reset & Isi Data Dummy

// 1. KONEKSI DATABASE (Sesuaikan credential jika beda)
// seed.php
$host = 'localhost';
$db   = 'tubes_test'; // Database yang baru kita buat
$user = 'root';       // Default Laragon
$pass = '';           // Default Laragon (KOSONG)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "✅ Koneksi Database Berhasil.\n";
} catch (\PDOException $e) {
    die("❌ Koneksi Gagal: " . $e->getMessage());
}

// 2. BERSIHKAN DATA LAMA (Reset)
try {
    // Matikan foreign key check biar bisa truncate
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    $pdo->exec("TRUNCATE TABLE claims");
    $pdo->exec("TRUNCATE TABLE items");
    $pdo->exec("TRUNCATE TABLE users");
    $pdo->exec("TRUNCATE TABLE categories");
    $pdo->exec("TRUNCATE TABLE locations");
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "✅ Data lama berhasil dibersihkan.\n";
} catch (Exception $e) {
    die("❌ Gagal Reset Data: " . $e->getMessage());
}

// 3. ISI MASTER DATA (Kategori & Lokasi)
$categories = ['Elektronik', 'Dokumen', 'Aksesoris', 'Pakaian', 'Kunci'];
foreach ($categories as $cat) {
    $stmt = $pdo->prepare("INSERT INTO categories (name, created_at) VALUES (?, NOW())");
    $stmt->execute([$cat]);
}
echo "✅ Kategori dummy dibuat.\n";

$locations = ['Gedung H Teknik', 'Gedung A Rektorat', 'Perpustakaan Pusat', 'GSG Unila', 'Kantin Teknik'];
foreach ($locations as $loc) {
    $stmt = $pdo->prepare("INSERT INTO locations (name, created_at) VALUES (?, NOW())");
    $stmt->execute([$loc]);
}
echo "✅ Lokasi dummy dibuat.\n";

// 4. ISI DATA USER (Admin & Mahasiswa)
// Password default: '12345' (Dihash)
$password = password_hash('12345', PASSWORD_DEFAULT);

$users = [
    ['Admin Unila', 'admin@unila.ac.id', '081234567890', 'admin', 'active'],
    ['Budi Santoso', 'budi@students.unila.ac.id', '08987654321', 'user', 'active'],
    ['Siti Aminah', 'siti@students.unila.ac.id', '08111222333', 'user', 'active'],
    ['Spammer Jahat', 'hacker@students.unila.ac.id', '000000000', 'user', 'banned'], // User status banned
];

foreach ($users as $u) {
    $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, role, status, password, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$u[0], $u[1], $u[2], $u[3], $u[4], $password]);
}
echo "✅ User dummy dibuat (Password semua: 12345).\n";

// Ambil ID User & Kategori buat relasi item
$userId = $pdo->query("SELECT id FROM users WHERE email='budi@students.unila.ac.id'")->fetchColumn();
$catId  = $pdo->query("SELECT id FROM categories LIMIT 1")->fetchColumn();
$locId  = $pdo->query("SELECT id FROM locations LIMIT 1")->fetchColumn();

// 5. ISI DATA BARANG (ITEMS) - Skenario Lengkap

$items = [
    // [Judul, Deskripsi, Tipe, Status]
    ['Laptop ASUS ROG', 'Hilang di Gedung H, stiker Apple', 'lost', 'pending'], // UNTUK TEST MODERASI
    ['Dompet Kulit Hitam', 'Isi KTM dan SIM C', 'lost', 'pending'],             // UNTUK TEST MODERASI
    ['Kunci Motor Vario', 'Gantungan boneka boba', 'found', 'open'],            // SUDAH TAYANG (Open)
    ['Tumblr Corkcicle', 'Warna putih, ketinggalan di perpus', 'lost', 'open'], // SUDAH TAYANG (Open)
    ['iPhone 13 Pro', 'Layar retak dikit', 'found', 'rejected'],                // DITOLAK ADMIN
    ['Jas Almamater', 'Ada nama di kerah', 'lost', 'closed'],                   // SUDAH SELESAI
];

$stmt = $pdo->prepare("INSERT INTO items (user_id, category_id, location_id, title, description, type, status, incident_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");

foreach ($items as $item) {
    $stmt->execute([$userId, $catId, $locId, $item[0], $item[1], $item[2], $item[3]]);
}

echo "✅ Item dummy dibuat (Pending, Open, Rejected, Closed).\n";
echo "🎉 SEEDING SELESAI! Silakan cek Dashboard Admin.\n";
?>