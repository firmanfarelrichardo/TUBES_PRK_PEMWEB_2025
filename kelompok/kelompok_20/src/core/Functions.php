<?php

declare(strict_types=1);

/**
 * PENTING: Fungsi ini akan bertindak sebagai router/url creator yang memilih jalur.
 * - Jika path adalah aset (misal: 'assets/...'), ia akan menghilangkan /src/.
 * - Jika path adalah routing (misal: 'index.php?page=...'), ia akan mempertahankan /src/ atau path folder yang diperlukan.
 */
function base_url(string $path = ''): string
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    
    // 1. Tentukan BASE PATH (ASET)
    $assetBase = dirname($scriptName);
    if (str_ends_with($assetBase, '/src') || str_ends_with($assetBase, '\src')) {
        $assetBase = dirname($assetBase);
    }
    $assetUrl = $protocol . '://' . $host . rtrim($assetBase, '/');
    
    // 2. Tentukan ROUTING PATH (Mempertahankan /src/)
    $routeBase = dirname($scriptName);
    $routeUrl = $protocol . '://' . $host . rtrim($routeBase, '/');
    
    // 3. Logika Pemilihan
    $path = ltrim($path, '/');

    // Jika path mengarah ke aset statis (gambar, js, css, dll.)
    if (str_starts_with($path, 'assets/') || str_ends_with($path, '.js') || str_ends_with($path, '.css') || str_ends_with($path, '.png') || str_ends_with($path, '.svg') || str_ends_with($path, '.jpg')) {
        return $assetUrl . '/' . $path;
    }
    
    // Jika path mengarah ke routing (index.php?page=...)
    return $routeUrl . '/' . $path;
}

function redirect(string $path): void
{
    if (!headers_sent()) {
        header('Location: ' . base_url($path));
        exit;
    } else {
        echo "<script>window.location.href = '" . base_url($path) . "';</script>";
        exit;
    }
}

function flash(string $name, string $message = '', string $type = 'success'): ?array
{
    if (!isset($_SESSION)) { session_start(); }
    
    if (!empty($message)) {
        if (!isset($_SESSION['flash'])) { $_SESSION['flash'] = []; }
        $_SESSION['flash'][$name] = ['message' => $message, 'type' => $type];
        return null;
    }
    
    if (isset($_SESSION['flash'][$name])) {
        $flash = $_SESSION['flash'][$name];
        unset($_SESSION['flash'][$name]);
        return $flash;
    }
    
    return null;
}

function dd(...$data): void
{
    echo '<pre style="background: #1e293b; color: #e2e8f0; padding: 20px; border-radius: 8px; margin: 20px; font-family: monospace;">';
    foreach ($data as $item) { var_dump($item); echo "\n"; }
    echo '</pre>';
    die;
}

function clean(?string $data): string
{
    if ($data === null || $data === '') return '';
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function isLoggedIn(): bool
{
    if (!isset($_SESSION)) { session_start(); }
    return isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0;
}

function currentUser(): ?array
{
    if (!isset($_SESSION)) { session_start(); }
    if (!isset($_SESSION['user']) || !isset($_SESSION['user_id'])) { return null; }
    return array_merge($_SESSION['user'], ['id' => (int)$_SESSION['user_id']]);
}

function isAdmin(): bool
{
    if (!isset($_SESSION)) { session_start(); }
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
}

function formatDate(string $date): string
{
    $timestamp = strtotime($date);
    if ($timestamp === false) { return 'Tanggal Tidak Valid'; }
    $months = [ 1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember' ];
    $day = date('d', $timestamp);
    $monthIndex = (int)date('m', $timestamp);
    $month = $months[$monthIndex];
    $year = date('Y', $timestamp);
    return "{$day} {$month} {$year}";
}

function uploadImage(array $file, string $targetDir, string $category = 'IMG', int $userId = 0): string|false
{
    if ($file['error'] !== UPLOAD_ERR_OK || empty($file['tmp_name'])) { return false; }
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $maxSize = 2 * 1024 * 1024;
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true) || $file['size'] > $maxSize) { return false; }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) { error_log("finfo_open failed."); return false; } 
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mimeType, $allowedMimes, true)) { return false; }

    if (!is_dir($targetDir)) { 
        if (!mkdir($targetDir, 0755, true)) {
            error_log("Failed to create upload directory: " . $targetDir);
            return false;
        }
    }

    $categoryClean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $category));
    $userIdFormatted = sprintf('U%03d', $userId);
    $timestamp = date('Ymd_His');
    $randomStr = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    
    $newFilename = sprintf('%s_%s_%s_%s.%s', $categoryClean, $userIdFormatted, $timestamp, $randomStr, $extension);
    $targetPath = rtrim($targetDir, '/') . '/' . $newFilename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        error_log("Failed to move uploaded file to: " . $targetPath);
        return false;
    }

    // Mengembalikan jalur RELATIF agar bisa diakses oleh base_url() (aset)
    return 'assets/uploads/items/' . $newFilename; 
}

function deleteImage(string $filename, string $targetDir): bool
{
    if (empty($filename) || strpos($filename, 'default') !== false) {
        return false;
    }

    $justFilename = basename($filename);
    $filePath = rtrim($targetDir, '/') . '/' . $justFilename;

    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    
    return false;
}

function timeAgo(string $datetime): string
{
    try {
        $now = new DateTime();
        $past = new DateTime($datetime);
        $diff = $now->diff($past);

        if ($diff->y > 0) { return $diff->y . ' tahun lalu'; }
        if ($diff->m > 0) { return $diff->m . ' bulan lalu'; }
        if ($diff->d > 0) { return $diff->d . ' hari lalu'; }
        if ($diff->h > 0) { return $diff->h . ' jam lalu'; }
        if ($diff->i > 0) { return $diff->i . ' menit lalu'; }

        return 'Baru saja';
    } catch (\Exception $e) {
        return 'Waktu tidak valid';
    }
}

function truncate(string $text, int $length = 100): string
{
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}