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

<div class="bg-slate-900 text-white min-h-screen">

    <section class="gradient-mesh min-h-[85vh] flex items-center relative">
        <div class="absolute inset-0 opacity-40 overflow-hidden">
            <div class="absolute -top-40 -right-40 w-80 h-80 bg-primary-400/20 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-sky-400/20 rounded-full blur-3xl"></div>
        </div>
        
        <div class="container mx-auto px-4 py-20 relative z-10">
            <div class="max-w-4xl mx-auto text-center">
                
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary-900/30 text-primary-300 text-sm font-medium mb-6 animate-fade-in">
                    <span class="w-2 h-2 bg-primary-500 rounded-full animate-pulse"></span>
                    Platform Resmi Universitas Lampung
                </div>
                
                <h1 class="text-4xl md:text-6xl lg:text-7xl font-extrabold text-white mb-8 leading-tight md:leading-relaxed">
                    Temukan Barang Hilang
                    <span class="gradient-text block">di Kampus</span>
                </h1>
                
                <p class="text-lg md:text-xl text-slate-400 mb-10 max-w-2xl mx-auto leading-relaxed">
                    Platform digital untuk membantu civitas akademika Universitas Lampung dalam melaporkan dan menemukan barang hilang dengan mudah, cepat, dan aman.
                </p>
                
                <form action="<?= base_url('index.php?page=items') ?>" method="GET" class="max-w-2xl mx-auto mb-8">
                    <input type="hidden" name="page" value="items">
                    <input type="hidden" name="action" value="search">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none">
                            <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            name="query" 
                            placeholder="Cari barang hilang atau ditemukan..." 
                            class="w-full pl-16 pr-6 py-5 text-lg bg-slate-800 border-2 border-slate-700 rounded-2xl focus:outline-none focus:border-primary-500 text-white placeholder-slate-400 shadow-lg transition-all"
                        >
                    </div>
                </form>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="<?= base_url('index.php?page=items&action=create_lost') ?>" class="inline-flex items-center justify-center gap-2 px-8 py-4 gradient-primary text-white rounded-2xl font-semibold shadow-lg shadow-primary-500/30 hover:shadow-xl hover:-translate-y-1 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Laporkan Kehilangan
                    </a>
                    <a href="<?= base_url('index.php?page=items') ?>" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-slate-800 text-slate-200 rounded-2xl font-semibold border-2 border-slate-700 hover:border-primary-500 hover:text-primary-400 hover:-translate-y-1 transition-all shadow-md">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Cari Barang
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="py-12 bg-slate-800 border-y border-slate-700/50">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                
                <div class="text-center p-6 bg-slate-900 rounded-xl shadow-xl border border-slate-700 group hover:-translate-y-1 transition-all duration-300">
                    <div class="w-16 h-16 mx-auto mb-4 bg-red-600/20 rounded-2xl flex items-center justify-center shadow-lg text-red-500">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <h3 class="text-4xl font-bold text-white mb-1">
                        <?= number_format($stats['total_lost'] ?? 0) ?>
                    </h3>
                    <p class="text-red-400 font-medium">Laporan Hilang Aktif</p>
                </div>
                
                <div class="text-center p-6 bg-slate-900 rounded-xl shadow-xl border border-slate-700 group hover:-translate-y-1 transition-all duration-300">
                    <div class="w-16 h-16 mx-auto mb-4 bg-green-600/20 rounded-2xl flex items-center justify-center shadow-lg text-green-400">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-4xl font-bold text-white mb-1">
                        <?= number_format($stats['total_found'] ?? 0) ?>
                    </h3>
                    <p class="text-green-400 font-medium">Laporan Ditemukan</p>
                </div>
                
                <div class="text-center p-6 bg-slate-900 rounded-xl shadow-xl border border-slate-700 group hover:-translate-y-1 transition-all duration-300">
                    <div class="w-16 h-16 mx-auto mb-4 bg-primary-600/20 rounded-2xl flex items-center justify-center shadow-lg text-primary-400">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h3 class="text-4xl font-bold text-white mb-1">
                        <?= number_format($stats['total_returned'] ?? 0) ?>
                    </h3>
                    <p class="text-primary-400 font-medium">Berhasil Dikembalikan</p>
                </div>
            </div>
        </div>
    </section>
    
    <section class="py-12 px-4 sm:px-6 lg:px-8 bg-slate-900">
        <div class="container mx-auto max-w-7xl">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
                <div class="mb-4 md:mb-0">
                    <h2 class="text-3xl font-bold text-white mb-2 border-l-4 border-primary-500 pl-3">
                        📍 Peta Hotspot Kehilangan
                    </h2>
                    <p class="text-slate-400">
                        Analisis lokasi dengan laporan hilang aktif terbanyak dalam 30 hari terakhir.
                    </p>
                </div>
                <span class="text-sm text-slate-500">Data real-time: <span id="map-timestamp">Loading...</span></span>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                
                <div class="lg:col-span-3 bg-slate-800 rounded-2xl overflow-hidden shadow-2xl">
                    <div id="unila-hotspot-map" class="w-full" style="height: 550px;"></div> 
                </div>
                
                <div class="lg:col-span-1 space-y-6">
                    
                    <div class="bg-slate-800 rounded-xl p-5 shadow-lg border border-slate-700">
                        <h4 class="font-semibold text-white mb-4">Legenda Hotspot</h4>
                        <div class="space-y-3 text-sm text-slate-400">
                            <div class="flex items-center gap-3"><div class="w-4 h-4 bg-red-500 rounded-full"></div><p>Hotspot Tinggi (> 10 Laporan)</p></div>
                            <div class="flex items-center gap-3"><div class="w-4 h-4 bg-amber-500 rounded-full"></div><p>Hotspot Sedang (5 - 9 Laporan)</p></div>
                            <div class="flex items-center gap-3"><div class="w-4 h-4 bg-blue-500 rounded-full"></div><p>Hotspot Rendah (3 - 4 Laporan)</p></div>
                            <div class="flex items-center gap-3"><div class="w-4 h-4 bg-emerald-500 rounded-full"></div><p>Sangat Rendah (< 3 Laporan)</p></div>
                        </div>
                    </div>
                    
                    <div class="bg-slate-800 rounded-xl p-5 shadow-lg border border-slate-700">
                        <h4 class="font-semibold text-white mb-4">Top Lokasi Aktif</h4>
                        <div class="space-y-3">
                            <?php if (!empty($topLocations)): ?>
                                <?php foreach (array_slice($topLocations, 0, 5) as $location): ?>
                                    <a href="<?= base_url('index.php?page=items&action=search&location=' . urlencode($location['name'])) ?>"
                                       class="flex items-center justify-between p-3 bg-slate-900 rounded-lg hover:bg-slate-700 transition-colors">
                                        <span class="font-medium text-sm text-white"><?= htmlspecialchars($location['name']) ?></span>
                                        <span class="text-xs px-2 py-1 bg-red-600/30 text-red-300 rounded-full"><?= $location['report_count'] ?> Laporan</span>
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

    <section class="px-4 sm:px-6 lg:px-8 pt-8 pb-16 bg-slate-900">
        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-2">
                <h2 class="text-2xl font-bold text-white mb-6 border-l-4 border-primary-500 pl-4">Laporan Terbaru Aktif</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php 
                    $recentItemsForDisplay = array_slice($recentItems, 0, 6);
                    ?>

                    <?php if (!empty($recentItemsForDisplay)): ?>
                        <?php foreach ($recentItemsForDisplay as $item): ?>
                            <article class="bg-slate-800 rounded-xl overflow-hidden shadow-xl border border-slate-700 transition-all duration-300 group hover:shadow-primary-500/20 hover:scale-[1.01]">
                                
                                <div class="relative w-full h-40 bg-slate-700 overflow-hidden">
                                    <?php if (!empty($item['image_path'])): ?>
                                        <img src="<?= base_url($item['image_path']) ?>" 
                                            alt="<?= htmlspecialchars($item['title']) ?>" 
                                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"/>
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-4xl text-slate-500">📦</div>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    $is_found = $item['type'] === 'found';
                                    $tag_color = $is_found ? 'bg-primary-600' : 'bg-red-600'; 
                                    $tag_label = $is_found ? 'DITEMUKAN' : 'HILANG';
                                    ?>
                                    <span class="absolute top-2 right-2 px-3 py-1 <?= $tag_color ?> text-white text-xs font-bold rounded-full shadow-md">
                                        <?= $tag_label ?>
                                    </span>
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
    </section>

</div>