<?php

// ASUMSI: Fungsi-fungsi helper seperti timeAgo(), base_url(), currentUser(), 
// dan itemStatusToBadge() tersedia melalui core/Functions.php.

// Data yang diekstrak dari $data (dikirim oleh HomeController)
$user = $data['user'] ?? ['name' => 'Pengguna', 'id' => 0]; 
$userId = $user['id'] ?? 0;
$userName = $user['name'] ?? 'Pengguna';

// Statistik
$stats = $data['stats'] ?? [
    'total_lost' => 0,
    'total_found' => 0,
    'total_returned' => 0, 
];

// Feed Items: INI PENTING UNTUK LAPORAN ANDA
$newly_found = $data['newly_found'] ?? [];
$newly_lost = $data['newly_lost'] ?? [];
$my_pending_reports = $data['my_pending_reports'] ?? []; 
$urgent_items = $data['urgent_items'] ?? []; // Data Bantuan Mendesak dari Controller
$topLocations = $data['topLocations'] ?? [];
$recentItems = array_merge($newly_found, $newly_lost);

/**
 * Helper untuk menampilkan badge status (Jika belum ada di core/Functions.php)
 */
if (!function_exists('itemStatusToBadge')) {
    function itemStatusToBadge(string $status): array {
        switch (strtolower($status)) {
            case 'open':
                return ['label' => 'AKTIF', 'color' => 'bg-primary-900/50 text-primary-300'];
            case 'process':
                return ['label' => 'DIPROSES', 'color' => 'bg-amber-600/50 text-amber-300'];
            case 'closed':
                return ['label' => 'SELESAI', 'color' => 'bg-green-600/50 text-green-300'];
            default:
                return ['label' => 'BARU', 'color' => 'bg-slate-600/50 text-slate-300'];
        }
    }
}
?>

<section class="gradient-mesh min-h-[85vh] flex items-center relative">
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-primary-400/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-sky-400/20 rounded-full blur-3xl"></div>
    </div>
    
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-20 relative z-10 max-w-7xl">
        <div class="max-w-4xl mx-auto text-center">
            
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-sm font-medium mb-6 animate-fade-in">
                <span class="w-2 h-2 bg-primary-500 rounded-full animate-pulse"></span>
                Platform Resmi Universitas Lampung
            </div>
            
            <h1 class="text-4xl md:text-6xl lg:text-7xl font-extrabold text-slate-900 dark:text-white mb-8 leading-tight">
                Temukan Barang Hilang
                <span class="gradient-text block">di Kampus</span>
            </h1>
            
            <p class="text-lg md:text-xl text-slate-600 dark:text-slate-400 mb-10 max-w-2xl mx-auto leading-relaxed">
                Platform digital untuk membantu civitas akademika Universitas Lampung dalam melaporkan dan menemukan barang hilang dengan mudah, cepat, dan aman.
            </p>
            
            <form action="<?= base_url('index.php?page=items') ?>" method="GET" class="max-w-2xl mx-auto mb-8">
                <input type="hidden" name="page" value="items">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none">
                        <svg class="w-6 h-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input 
                        type="text" 
                        name="query" 
                        placeholder="Cari barang hilang atau ditemukan..." 
                        class="w-full pl-16 pr-6 py-5 text-lg bg-white dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 rounded-2xl focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 text-slate-900 dark:text-white placeholder-slate-400 shadow-lg hover:shadow-xl transition-all"
                    >
                </div>
                <button type="submit" class="mt-4 w-full sm:w-auto px-8 py-3.5 gradient-primary text-white rounded-xl font-semibold shadow-lg shadow-primary-500/30 hover:shadow-xl hover:shadow-primary-500/40 transition-all">
                    Cari Sekarang
                </button>
            </form>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <a href="<?= base_url('index.php?page=items&action=create_lost') ?>" class="inline-flex items-center justify-center gap-2 px-8 py-4 gradient-primary text-white rounded-2xl font-semibold shadow-lg shadow-primary-500/30 hover:shadow-xl hover:-translate-y-1 transition-all w-full sm:w-auto">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Laporkan Kehilangan
                </a>
                <a href="<?= base_url('index.php?page=items') ?>" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-2xl font-semibold border-2 border-slate-200 dark:border-slate-700 hover:border-primary-500 hover:text-primary-600 dark:hover:text-primary-400 hover:-translate-y-1 transition-all shadow-md w-full sm:w-auto">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Cari Barang
                </a>
            </div>
        </div>
    </div>
</section>

    <section class="py-12 bg-white dark:bg-slate-800 border-y border-slate-200 dark:border-slate-700/50">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8">
                
                <div class="text-center p-6 glass-card rounded-2xl bento-shadow group hover:-translate-y-1 transition-all duration-300">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-red-500 to-rose-600 rounded-2xl flex items-center justify-center shadow-lg shadow-red-500/30">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <h3 class="text-4xl font-bold text-slate-900 dark:text-white mb-1">
                        <?= number_format($stats['total_lost'] ?? 0) ?>
                    </h3>
                    <p class="text-red-600 dark:text-red-400 font-medium">Laporan Hilang Aktif</p>
                </div>
                
                <div class="text-center p-6 glass-card rounded-2xl bento-shadow group hover:-translate-y-1 transition-all duration-300">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center shadow-lg shadow-green-500/30">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-4xl font-bold text-slate-900 dark:text-white mb-1">
                        <?= number_format($stats['total_found'] ?? 0) ?>
                    </h3>
                    <p class="text-green-600 dark:text-green-400 font-medium">Laporan Ditemukan</p>
                </div>
                
                <div class="text-center p-6 glass-card rounded-2xl bento-shadow group hover:-translate-y-1 transition-all duration-300">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg shadow-cyan-500/30">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h3 class="text-4xl font-bold text-slate-900 dark:text-white mb-1">
                        <?= number_format($stats['total_returned'] ?? 0) ?>
                    </h3>
                    <p class="text-cyan-600 dark:text-cyan-400 font-medium">Berhasil Dikembalikan</p>
                </div>
            </div>
        </div>
    </section>
    
    <section class="py-12 px-4 sm:px-6 lg:px-8 bg-slate-50 dark:bg-slate-900">
        <div class="container mx-auto max-w-7xl">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
                <div class="mb-4 md:mb-0">
                    <h2 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                        <span class="text-primary-500">📍</span>
                        Peta Hotspot Kehilangan
                    </h2>
                    <p class="text-slate-600 dark:text-slate-400 text-sm md:text-base">
                        Analisis lokasi dengan laporan hilang aktif terbanyak dalam 30 hari terakhir.
                    </p>
                </div>
                <span class="text-sm text-slate-500 dark:text-slate-400">Data real-time: <span id="map-timestamp" class="font-semibold text-primary-500">Loading...</span></span>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                
                <div class="lg:col-span-3 glass-card rounded-2xl overflow-hidden bento-shadow">
                    <div id="unila-hotspot-map" class="w-full h-[300px] md:h-[500px] lg:h-[550px]"></div> 
                </div>
                
                <div class="lg:col-span-1 space-y-4">
                    
                    <div class="glass-card rounded-xl p-5 bento-shadow">
                        <h4 class="font-semibold text-slate-900 dark:text-white mb-4">Legenda Hotspot</h4>
                        <div class="space-y-3 text-sm text-slate-600 dark:text-slate-400">
                            <div class="flex items-center gap-3"><div class="w-4 h-4 bg-red-500 rounded-full"></div><p>Hotspot Tinggi (> 10 Laporan)</p></div>
                            <div class="flex items-center gap-3"><div class="w-4 h-4 bg-amber-500 rounded-full"></div><p>Hotspot Sedang (5 - 9 Laporan)</p></div>
                            <div class="flex items-center gap-3"><div class="w-4 h-4 bg-blue-500 rounded-full"></div><p>Hotspot Rendah (3 - 4 Laporan)</p></div>
                            <div class="flex items-center gap-3"><div class="w-4 h-4 bg-emerald-500 rounded-full"></div><p>Sangat Rendah (< 3 Laporan)</p></div>
                        </div>
                    </div>
                    
                    <div class="glass-card rounded-xl p-5 bento-shadow">
                        <h4 class="font-semibold text-slate-900 dark:text-white mb-4">Top Lokasi Aktif</h4>
                        <div class="space-y-2">
                            <?php if (!empty($topLocations)): ?>
                                <?php foreach (array_slice($topLocations, 0, 5) as $location): ?>
                                    <a href="<?= base_url('index.php?page=items&action=search&location=' . urlencode($location['name'])) ?>"
                                       class="flex items-center justify-between p-3 bg-slate-100 dark:bg-slate-800 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-all group">
                                        <span class="font-medium text-sm text-slate-900 dark:text-white truncate pr-2"><?= htmlspecialchars($location['name']) ?></span>
                                        <span class="text-xs px-2 py-1 bg-red-100 dark:bg-red-600/30 text-red-700 dark:text-red-300 rounded-full font-semibold whitespace-nowrap"><?= $location['report_count'] ?> Laporan</span>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-gray-400 text-sm">Tidak ada hotspot aktif.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="px-4 sm:px-6 lg:px-8 pt-12 pb-16 bg-white dark:bg-slate-900">
        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-2">
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                    <span class="w-1 h-8 bg-primary-500 rounded-full"></span>
                    Laporan Terbaru Aktif
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-6">
                    <?php 
                    $recentItemsForDisplay = array_slice($recentItems, 0, 6);
                    ?>

                    <?php if (!empty($recentItemsForDisplay)): ?>
                        <?php foreach ($recentItemsForDisplay as $item): ?>
                            <article class="glass-card rounded-xl overflow-hidden bento-shadow transition-all duration-300 group hover:shadow-lg hover:-translate-y-1">
                                
                                <div class="relative w-full h-40 bg-slate-200 dark:bg-slate-700 overflow-hidden">
                                    <?php if (!empty($item['image_path'])): ?>
                                        <img src="<?= base_url($item['image_path']) ?>" 
                                            alt="<?= htmlspecialchars($item['title']) ?>" 
                                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"/>
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-4xl text-slate-500">📦</div>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    $is_found = $item['type'] === 'found';
                                    $tag_color = $is_found ? 'bg-gradient-to-r from-green-500 to-emerald-600' : 'bg-gradient-to-r from-red-500 to-rose-600'; 
                                    $tag_label = $is_found ? 'DITEMUKAN' : 'HILANG';
                                    ?>
                                    <span class="absolute top-2 right-2 px-3 py-1 <?= $tag_color ?> text-white text-xs font-bold rounded-full shadow-lg backdrop-blur-sm">
                                        <?= $tag_label ?>
                                    </span>
                                </div>
                                
                                <div class="p-4">
                                    <h3 class="font-semibold text-slate-900 dark:text-white mb-2 line-clamp-1 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors"><?= htmlspecialchars($item['title']) ?></h3>
                                    <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span><?= timeAgo($item['created_at']) ?></span>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="col-span-3 text-center text-slate-400">Belum ada laporan barang terbaru.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

<section class="py-20 bg-white dark:bg-slate-800/50">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                Kategori Barang Paling Dicari
            </h2>
            <p class="text-slate-600 dark:text-slate-400 max-w-3xl mx-auto">
                Lihat jenis-jenis barang yang paling sering dilaporkan hilang atau ditemukan di sekitar kampus.
            </p>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            
            <div class="bg-slate-50 dark:bg-slate-800 p-6 rounded-2xl text-center group hover:bg-primary-50 dark:hover:bg-primary-900 transition-colors duration-300 bento-shadow">
                <div class="w-16 h-16 mx-auto mb-4 bg-primary-100 dark:bg-primary-700/50 rounded-full flex items-center justify-center group-hover:bg-primary-500 transition-colors">
                    <svg class="w-8 h-8 text-primary-600 dark:text-primary-300 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="font-bold text-lg text-slate-900 dark:text-white mb-1">
                    Elektronik
                </h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    Laptop, Ponsel, Tablet.
                </p>
            </div>
            
            <div class="bg-slate-50 dark:bg-slate-800 p-6 rounded-2xl text-center group hover:bg-primary-50 dark:hover:bg-primary-900 transition-colors duration-300 bento-shadow">
                <div class="w-16 h-16 mx-auto mb-4 bg-primary-100 dark:bg-primary-700/50 rounded-full flex items-center justify-center group-hover:bg-primary-500 transition-colors">
                    <svg class="w-8 h-8 text-primary-600 dark:text-primary-300 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="font-bold text-lg text-slate-900 dark:text-white mb-1">
                    Dokumen
                </h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    KTM, KTP, SIM, Ijazah.
                </p>
            </div>
            
            <div class="bg-slate-50 dark:bg-slate-800 p-6 rounded-2xl text-center group hover:bg-primary-50 dark:hover:bg-primary-900 transition-colors duration-300 bento-shadow">
                <div class="w-16 h-16 mx-auto mb-4 bg-primary-100 dark:bg-primary-700/50 rounded-full flex items-center justify-center group-hover:bg-primary-500 transition-colors">
                    <svg class="w-8 h-8 text-primary-600 dark:text-primary-300 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h.01M3 7h.01M3 3h.01M21 7h.01M21 3h.01M17 7h.01M17 3h.01M3 21h.01M3 17h.01M7 21h.01M7 17h.01M21 21h.01M21 17h.01M17 21h.01M17 17h.01M12 12V3M12 12l-4 4m4-4l4 4"/>
                    </svg>
                </div>
                <h3 class="font-bold text-lg text-slate-900 dark:text-white mb-1">
                    Aksesoris
                </h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    Kacamata, Jam, Tas.
                </p>
            </div>
            
            <div class="bg-slate-50 dark:bg-slate-800 p-6 rounded-2xl text-center group hover:bg-primary-50 dark:hover:bg-primary-900 transition-colors duration-300 bento-shadow">
                <div class="w-16 h-16 mx-auto mb-4 bg-primary-100 dark:bg-primary-700/50 rounded-full flex items-center justify-center group-hover:bg-primary-500 transition-colors">
                    <svg class="w-8 h-8 text-primary-600 dark:text-primary-300 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5s3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <h3 class="font-bold text-lg text-slate-900 dark:text-white mb-1">
                    Kuliah
                </h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    Buku, Catatan, Alat Tulis.
                </p>
            </div>
            
        </div>
        
        <div class="text-center mt-12">
            <a 
                href="<?= base_url('index.php?page=items') ?>" 
                class="inline-flex items-center gap-2 px-8 py-3.5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-xl font-semibold hover:bg-primary-600 dark:hover:bg-primary-500 hover:text-white transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5"
            >
                Lihat Semua Kategori
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>
    </div>
</section>
<section class="py-20 bg-slate-50 dark:bg-slate-900/50">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                Laporan Terbaru
            </h2>
            <p class="text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
                Daftar barang hilang dan ditemukan terbaru di lingkungan Universitas Lampung
            </p>
        </div>
        
        <?php if (!empty($recentItems)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($recentItems as $item): ?>
                    <article class="bg-white dark:bg-slate-800 rounded-2xl overflow-hidden bento-shadow hover:shadow-xl transition-all duration-300 group">
                        
                        <div class="aspect-video bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-700 dark:to-slate-800 relative overflow-hidden">
                            <?php if (!empty($item['image_path'])): ?>
                                <?php 
                                // Check if image_path is external URL or local path
                                $imageSrc = (strpos($item['image_path'], 'http://') === 0 || strpos($item['image_path'], 'https://') === 0) 
                                    ? $item['image_path'] 
                                    : base_url('assets/uploads/items/' . $item['image_path']);
                                ?>
                                <img 
                                    src="<?= $imageSrc ?>" 
                                    alt="<?= htmlspecialchars($item['title']) ?>" 
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                    onerror="this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center\'><svg class=\'w-16 h-16 text-slate-400 dark:text-slate-600\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z\'/></svg></div>'"
                                >
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-16 h-16 text-slate-400 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="p-4">
                                    <h3 class="font-semibold text-lg text-white line-clamp-1 group-hover:text-primary-400 transition"><?= htmlspecialchars($item['title']) ?></h3>
                                    <div class="flex items-center text-xs text-slate-500 mt-1">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <?= htmlspecialchars($item['location_name'] ?? 'Lokasi') ?>
                                    </div>
                                    
                                    <p class="text-xs text-slate-500 mt-2">
                                        Dilaporkan <?= timeAgo($item['created_at']) ?>
                                    </p>
                                    
                            <a href="<?= base_url('index.php?page=items&action=show&id=' . $item['id']) ?>" 
                                class="mt-3 inline-flex items-center text-primary-400 text-sm font-bold hover:text-white transition-colors">
                                Lihat Detail →
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-3 bg-slate-800 rounded-xl p-8 text-center border border-slate-700">
                    <p class="text-gray-400">Belum ada laporan barang terbaru.</p>
                </div>
            <?php endif; ?>
            </div>
        </div>

            <div class="lg:col-span-1 space-y-8">
                
                <div>
                    <h3 class="text-xl font-bold text-white mb-4 border-l-4 border-primary-500 pl-3">
                        Status Laporan Saya
                    </h3>
                    <div class="bg-slate-800 rounded-xl shadow-lg p-5 space-y-3 border border-slate-700">
                        <?php if ($userId > 0 && !empty($my_pending_reports)): ?>
                            <?php foreach ($my_pending_reports as $report): ?>
                                <?php $badge = itemStatusToBadge($report['status']); ?>
                                <div class="p-3 rounded-lg border border-slate-700 hover:bg-slate-700 transition-colors">
                                    <div class="flex justify-between items-start">
                                        <span class="font-medium text-white truncate pr-2">
                                            <?= htmlspecialchars($report['title']) ?>
                                        </span>
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= $badge['color'] ?> flex-shrink-0">
                                            <?= $badge['label'] ?>
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1">
                                        <?= timeAgo($report['created_at']) ?> di <?= htmlspecialchars($report['location_name']) ?>
                                    </p>
                                    <a href="<?= base_url('index.php?page=item&action=show&id=' . $report['id']) ?>" class="text-xs text-primary-400 hover:text-white mt-2 inline-block">
                                        Lihat Detail Laporan →
                                    </a>
                                </div>
                            <?php endforeach; ?>
                            <div class="pt-2 text-center">
                                <a href="<?= base_url('index.php?page=myitems') ?>" class="text-sm font-semibold text-primary-400 hover:text-white">
                                    Lihat Semua Laporan Saya (<?= count($my_pending_reports) ?> Aktif)
                                </a>
                            </div>
                        <?php elseif ($userId > 0): ?>
                             <p class="text-gray-400 text-center py-3">🎉 Semua laporan Anda telah selesai atau tidak ada yang aktif.</p>
                        <?php else: ?>
                            <p class="text-gray-400 text-center py-3">Silakan <a href="<?= base_url('index.php?page=auth&action=login') ?>" class="text-primary-500 font-bold hover:underline">login</a> untuk melihat status laporan Anda.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-xl font-bold text-white mb-4 border-l-4 border-red-500 pl-3">
                        <span class="text-red-500">🚨</span> Bantuan Mendesak
                    </h3>
                    <div class="bg-slate-800 rounded-xl shadow-lg p-5 space-y-3 border border-slate-700">
                        <?php if (!empty($urgent_items)): ?>
                            <?php foreach ($urgent_items as $item): ?>
                                <div class="rounded-lg shadow-sm border border-red-700 hover:bg-red-900/20 duration-300">
                                    <a href="<?= base_url('index.php?page=items&action=show&id=' . $item['id']) ?>" class="flex p-3 items-center">
                                        <div class="w-12 h-12 flex-shrink-0 rounded-lg overflow-hidden mr-3 bg-red-900/50 flex items-center justify-center">
                                            <?php if (!empty($item['image_path'])): ?>
                                                <img src="<?= base_url($item['image_path']) ?>" class="w-full h-full object-cover filter blur-sm"/>
                                            <?php else: ?>
                                                <span class="text-2xl text-red-400">🚨</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow min-w-0">
                                            <p class="font-semibold text-white truncate"><?= htmlspecialchars($item['title']) ?></p>
                                            <p class="text-xs text-red-400 mt-0.5 font-medium">
                                                Hilang <?= timeAgo($item['created_at']) ?>
                                            </p>
                                        </div>
                                        <span class="text-sm text-primary-400 font-bold flex-shrink-0 ml-3">Bantu →</span>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-gray-400 text-center py-5">Tidak ada pencarian mendesak.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <section class="py-16 bg-slate-800 border-t border-slate-700">
        <div class="container mx-auto px-4 max-w-7xl">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">
                    Cara Kerja Platform
                </h2>
                <p class="text-slate-400 max-w-2xl mx-auto">
                    Tiga langkah mudah menjamin keamanan dan pengembalian barang.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                
                <div class="bg-slate-900 rounded-2xl p-8 text-center shadow-xl border border-slate-700 hover:border-primary-500 transition-all duration-300">
                    <div class="w-16 h-16 mx-auto mb-6 bg-primary-600/20 rounded-2xl flex items-center justify-center text-primary-400 font-bold text-2xl shadow-lg shadow-primary-500/30">1</div>
                    <h3 class="font-semibold text-xl text-white mb-3">Lapor atau Cari</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">Buat laporan barang hilang atau segera cari di database temuan kami.</p>
                </div>
                
                <div class="bg-slate-900 rounded-2xl p-8 text-center shadow-xl border border-slate-700 hover:border-primary-500 transition-all duration-300">
                    <div class="w-16 h-16 mx-auto mb-6 bg-primary-600/20 rounded-2xl flex items-center justify-center text-primary-400 font-bold text-2xl shadow-lg shadow-primary-500/30">2</div>
                    <h3 class="font-semibold text-xl text-white mb-3">Verifikasi Aman</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">Kami menggunakan data dan fitur Safe Claim untuk memastikan kepemilikan yang sah.</p>
                </div>
                
                <div class="bg-slate-900 rounded-2xl p-8 text-center shadow-xl border border-slate-700 hover:border-primary-500 transition-all duration-300">
                    <div class="w-16 h-16 mx-auto mb-6 bg-primary-600/20 rounded-2xl flex items-center justify-center text-primary-400 font-bold text-2xl shadow-lg shadow-primary-500/30">3</div>
                    <h3 class="font-semibold text-xl text-white mb-3">Barang Kembali</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">Koordinasi serah terima barang dengan aman dan lancar melalui platform.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-20 bg-slate-50 dark:bg-slate-900/50">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                Mengapa Menggunakan Lost & Found Unila?
            </h2>
            <p class="text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
                Platform resmi dan terpercaya untuk menemukan kembali barang berharga Anda.
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
            
            <div class="flex items-start p-6 bg-white dark:bg-slate-800 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                <div class="flex-shrink-0 w-12 h-12 mr-4 mt-1 bg-teal-500 rounded-full flex items-center justify-center shadow-md">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-1">
                        Proses Cepat & Efisien
                    </h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm">
                        Laporkan kehilangan atau temuan dalam hitungan menit. Proses pencarian dan verifikasi yang disederhanakan.
                    </p>
                </div>
            </div>
            
            <div class="flex items-start p-6 bg-white dark:bg-slate-800 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                <div class="flex-shrink-0 w-12 h-12 mr-4 mt-1 bg-amber-500 rounded-full flex items-center justify-center shadow-md">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-1">
                        Jangkauan Seluruh Kampus
                    </h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm">
                        Semua laporan terpusat, memastikan barang hilang di fakultas mana pun dapat ditemukan.
                    </p>
                </div>
            </div>
            
            <div class="flex items-start p-6 bg-white dark:bg-slate-800 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                <div class="flex-shrink-0 w-12 h-12 mr-4 mt-1 bg-red-500 rounded-full flex items-center justify-center shadow-md">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.047 12.007 12.007 0 00-2.396 6.385c.465 4.312 3.822 7.842 8.525 9.873.308.13.626.23.953.305v-12"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-1">
                        Verifikasi Keamanan
                    </h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm">
                        Kami memastikan barang hanya diserahkan kepada pemilik yang sah melalui proses verifikasi yang ketat.
                    </p>
                </div>
            </div>
            
            <div class="flex items-start p-6 bg-white dark:bg-slate-800 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                <div class="flex-shrink-0 w-12 h-12 mr-4 mt-1 bg-blue-500 rounded-full flex items-center justify-center shadow-md">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm-6-2h6m6 0v1h3v-1m-3-6h.01M18 10h.01M18 14h.01"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-1">
                        Mendukung Komunitas
                    </h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm">
                        Meningkatkan budaya jujur dan saling membantu di lingkungan civitas akademika Universitas Lampung.
                    </p>
                </div>
            </div>
            
        </div>
    </section>
