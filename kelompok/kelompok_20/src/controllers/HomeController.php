<?php
declare(strict_types=1);

final class HomeController
{
    private Item $itemModel;

    public function __construct()
    {
        require_once __DIR__ . '/../config/database.php';
        require_once __DIR__ . '/../models/Item.php';
        
        if (file_exists(__DIR__ . '/../core/Functions.php')) {
            require_once __DIR__ . '/../core/Functions.php';
        } else {
            if (!function_exists('currentUser')) {
                function currentUser() { return ['id' => 0, 'name' => 'Guest']; }
            }
        }
        
        $this->itemModel = new Item();
    }

    public function index(): void
    {
        $pageTitle = 'Beranda - myUnila Lost & Found';
        
        $user = currentUser();
        $userId = $user['id'] ?? 0; 

        $stats = [
            'total_lost' => $this->itemModel->countByType('lost'),
            'total_found' => $this->itemModel->countByType('found'),
            'total_returned' => $this->itemModel->countByStatus('closed'),
            'total_open' => $this->itemModel->countByStatus('open'),
            'total_process' => $this->itemModel->countByStatus('process'),
            'total_items' => $this->itemModel->countAll(),
        ];

        $newly_found = $this->itemModel->getRecentByType('found', 3);
        $newly_lost = $this->itemModel->getRecentByType('lost', 3); 
        
        $my_pending_reports = $this->itemModel->getPendingReportsByUserId($userId, 3);
        $urgent_items = $this->itemModel->getUrgentLostItems(3);
        
        $topLocations = $this->getHotspotLocations();
        $topCategories = $this->getTopCategories();
        $baseUrl = $this->getBaseUrl();
        
        // --- DATA KHUSUS PETA BARU (Laporan Kehilangan untuk Marker Clustering) ---
        // Asumsi ItemModel memiliki getAllForMap() yang bisa difilter
        $lostReportsForMap = $this->itemModel->getAllForMap(['type' => 'lost', 'status' => 'open', 'limit' => 100]);
        // Format ulang data agar hanya berisi informasi penting untuk marker JS
        $lostReportsData = [];
        foreach ($lostReportsForMap as $item) {
            if (!empty($item['latitude']) && !empty($item['longitude'])) {
                $lostReportsData[] = [
                    'id' => (int)$item['id'],
                    'title' => $item['title'],
                    'lat' => (float)$item['latitude'],
                    'lng' => (float)$item['longitude'],
                    'location_name' => $item['location_name'] ?? 'Tidak diketahui',
                    'type' => $item['type']
                ];
            }
        }
        // --------------------------------------------------------------------------
        
        $data = [
            'stats' => $stats,
            'newly_found' => $newly_found,
            'newly_lost' => $newly_lost,
            'my_pending_reports' => $my_pending_reports,
            'urgent_items' => $urgent_items,
            'topLocations' => $topLocations,
            'topCategories' => $topCategories,
            'baseUrl' => $baseUrl,
            'user' => $user,
            'userId' => $userId,
            'lost_reports_data' => $lostReportsData // Data Map Baru
        ];
        
        require_once __DIR__ . '/../views/home/index.php';
    }
    
    public function mapData(): void
    {
        header('Content-Type: application/json');
        
        try {
            $hotspots = $this->itemModel->getHotspotLocationsEnhanced(15);
            $recentItems = $this->itemModel->getRecentItemsForMap(30);
            $topCategories = $this->itemModel->getTopCategoriesForMap(8);
            $mapStats = $this->itemModel->getMapStats();
            
            $formattedHotspots = [];
            foreach ($hotspots as $hotspot) {
                if (!empty($hotspot['latitude']) && !empty($hotspot['longitude'])) {
                    
                    $hotspotItems = [];
                    if (isset($hotspot['recent_items']) && is_array($hotspot['recent_items'])) {
                        foreach ($hotspot['recent_items'] as $item) {
                            $item['time_ago'] = $this->timeAgo($item['created_at']);
                            $hotspotItems[] = $item;
                        }
                    }
                    
                    $formattedHotspots[] = [
                        'id' => (int)$hotspot['id'],
                        'name' => $hotspot['name'],
                        'lat' => (float)$hotspot['latitude'],
                        'lng' => (float)$hotspot['longitude'],
                        'type' => $hotspot['location_type'] ?? 'building',
                        'report_count' => (int)$hotspot['report_count'],
                        'lost_count' => (int)$hotspot['lost_count'],
                        'found_count' => (int)$hotspot['found_count'],
                        'color' => $this->getColorByCount((int)$hotspot['lost_count']),
                        'items' => $hotspotItems,
                        'last_report_date' => $hotspot['last_report_date'] ?? null
                    ];
                }
            }
            
            $formattedItems = [];
            foreach ($recentItems as $item) {
                if (!empty($item['latitude']) && !empty($item['longitude'])) {
                    $formattedItems[] = [
                        'id' => (int)$item['id'],
                        'title' => $item['title'],
                        'description' => $item['description'],
                        'type' => $item['type'],
                        'status' => $item['status'],
                        'category' => $item['category_name'] ?? 'Lainnya',
                        'location' => $item['location_name'] ?? 'Tidak diketahui',
                        'lat' => (float)$item['latitude'],
                        'lng' => (float)$item['longitude'],
                        'location_type' => $item['location_type'] ?? 'building',
                        'created_at' => $item['created_at'],
                        'time_ago' => $this->timeAgo($item['created_at']),
                        'image_url' => !empty($item['image_path']) ? 
                            $this->getFullImageUrl($item['image_path']) : 
                            $this->getDefaultImageUrl(),
                        'reporter_name' => $item['reporter_name'] ?? 'Anonim'
                    ];
                }
            }
            
            $formattedCategories = [];
            foreach ($topCategories as $category) {
                $formattedCategories[] = [
                    'id' => (int)$category['id'],
                    'name' => $category['name'],
                    'item_count' => (int)$category['item_count'],
                    'lost_count' => (int)$category['lost_count'],
                    'found_count' => (int)$category['found_count']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'hotspots' => $formattedHotspots,
                'items' => $formattedItems,
                'categories' => $formattedCategories,
                'stats' => [
                    'total_hotspots' => count($formattedHotspots),
                    'total_items' => $mapStats['total_items'] ?? 0,
                    'total_lost' => $mapStats['total_lost'] ?? 0,
                    'total_found' => $mapStats['total_found'] ?? 0,
                    'total_active_items' => $mapStats['total_active_items'] ?? 0
                ],
                'center_lat' => -5.3630,
                'center_lng' => 105.2440,
                'timestamp' => date('H:i:s'),
                'last_updated' => date('Y-m-d H:i:s'),
                'api_version' => '1.1'
            ]);
            
        } catch (Exception $e) {
            error_log("MapData Error: " . $e->getMessage());
            
            echo json_encode([
                'success' => false,
                'error' => 'Gagal memuat data. ' . $e->getMessage(),
                'hotspots' => $this->getSampleHotspots(),
                'items' => $this->getSampleItems(),
                'categories' => $this->getSampleCategories(),
                'stats' => [
                    'total_hotspots' => 4,
                    'total_items' => 8,
                    'total_lost' => 5,
                    'total_found' => 3,
                    'total_active_items' => 8
                ],
                'center_lat' => -5.3630,
                'center_lng' => 105.2440,
                'timestamp' => 'Data contoh: ' . date('H:i:s'),
                'last_updated' => date('Y-m-d H:i:s')
            ]);
        }
        
        exit;
    }
    
    public function mapItems(): void
    {
        header('Content-Type: application/json');
        
        try {
            $type = $_GET['type'] ?? null;
            $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
            $locationId = isset($_GET['location_id']) ? (int)$_GET['location_id'] : null;
            
            $filters = [];
            if ($type && in_array($type, ['lost', 'found'])) {
                $filters['type'] = $type;
            }
            if ($categoryId) {
                $filters['category_id'] = $categoryId;
            }
            if ($locationId) {
                $filters['location_id'] = $locationId;
            }
            
            $items = $this->itemModel->getAllForMap($filters);
            
            $formattedItems = [];
            foreach ($items as $item) {
                if (!empty($item['latitude']) && !empty($item['longitude'])) {
                    $formattedItems[] = [
                        'id' => (int)$item['id'],
                        'title' => $item['title'],
                        'type' => $item['type'],
                        'status' => $item['status'],
                        'category' => $item['category_name'] ?? 'Lainnya',
                        'location' => $item['location_name'] ?? 'Tidak diketahui',
                        'lat' => (float)$item['latitude'],
                        'lng' => (float)$item['longitude'],
                        'created_at' => $item['created_at'],
                        'time_ago' => $this->timeAgo($item['created_at']),
                        'image_url' => !empty($item['image_path']) ? 
                            $this->getFullImageUrl($item['image_path']) : 
                            $this->getDefaultImageUrl()
                    ];
                }
            }
            
            echo json_encode([
                'success' => true,
                'items' => $formattedItems,
                'count' => count($formattedItems),
                'filters' => $filters,
                'timestamp' => date('H:i:s')
            ]);
            
        } catch (Exception $e) {
            error_log("MapItems Error: " . $e->getMessage());
            
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'items' => [],
                'count' => 0
            ]);
        }
        
        exit;
    }
    
    public function mapSearch(): void
    {
        header('Content-Type: application/json');
        
        try {
            $lat = isset($_GET['lat']) ? (float)$_GET['lat'] : -5.3630;
            $lng = isset($_GET['lng']) ? (float)$_GET['lng'] : 105.2440;
            $radius = isset($_GET['radius']) ? (float)$_GET['radius'] : 1.0; 
            $type = $_GET['type'] ?? null;
            $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
            
            $filters = [];
            if ($type && in_array($type, ['lost', 'found'])) {
                $filters['type'] = $type;
            }
            if ($categoryId) {
                $filters['category_id'] = $categoryId;
            }
            
            $items = $this->itemModel->searchByRadius($lat, $lng, $radius, $filters);
            
            $formattedItems = [];
            foreach ($items as $item) {
                $formattedItems[] = [
                    'id' => (int)$item['id'],
                    'title' => $item['title'],
                    'type' => $item['type'],
                    'category' => $item['category_name'] ?? 'Lainnya',
                    'location' => $item['location_name'] ?? 'Tidak diketahui',
                    'lat' => (float)$item['latitude'],
                    'lng' => (float)$item['longitude'],
                    'distance' => isset($item['distance']) ? round($item['distance'], 2) : 0,
                    'created_at' => $item['created_at'],
                    'time_ago' => $this->timeAgo($item['created_at'])
                ];
            }
            
            echo json_encode([
                'success' => true,
                'items' => $formattedItems,
                'count' => count($formattedItems),
                'search_center' => ['lat' => $lat, 'lng' => $lng],
                'radius_km' => $radius,
                'timestamp' => date('H:i:s')
            ]);
            
        } catch (Exception $e) {
            error_log("MapSearch Error: " . $e->getMessage());
            
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'items' => [],
                'count' => 0
            ]);
        }
        
        exit;
    }
    
    private function getHotspotLocations(): array
    {
        $locations = $this->itemModel->getHotspotLocations(); 
        $hotspots = [];
        
        foreach ($locations as $location) {
            $totalCount = $this->itemModel->countAllFiltered([
                'location_id' => $location['id'],
                'status' => 'open'
            ]);
            
            if ($totalCount > 0) {
                $hotspots[] = [
                    'id' => $location['id'],
                    'name' => $location['name'],
                    'latitude' => $location['latitude'],
                    'longitude' => $location['longitude'],
                    'location_type' => $location['location_type'] ?? 'building',
                    'report_count' => $totalCount,
                    'lost_count' => $this->itemModel->countAllFiltered([
                        'location_id' => $location['id'], 'type' => 'lost', 'status' => 'open'
                    ]),
                    'found_count' => $this->itemModel->countAllFiltered([
                        'location_id' => $location['id'], 'type' => 'found', 'status' => 'open'
                    ])
                ];
            }
        }
        
        usort($hotspots, function($a, $b) {
            return $b['report_count'] <=> $a['report_count'];
        });
        
        return array_slice($hotspots, 0, 10);
    }

    private function getTopCategories(): array
    {
        $categories = $this->itemModel->getCategories();
        $topCategories = [];
        
        foreach ($categories as $category) {
            $itemCount = $this->itemModel->countAllFiltered([
                'category_id' => $category['id'],
                'status' => 'open'
            ]);
            
            if ($itemCount > 0) {
                $topCategories[] = [
                    'id' => $category['id'],
                    'name' => $category['name'],
                    'item_count' => $itemCount
                ];
            }
        }
        
        usort($topCategories, function($a, $b) {
            return $b['item_count'] <=> $a['item_count'];
        });
        
        return array_slice($topCategories, 0, 6);
    }
    
    private function getColorByCount(int $count): string
    {
        if ($count >= 10) return '#b91c1c'; 
        if ($count >= 5) return '#dc2626';  
        if ($count >= 3) return '#ef4444'; 
        return '#f87171';
    }
    
    private function timeAgo(string $datetime): string
    {
        $time = strtotime($datetime);
        $diff = time() - $time;
        
        if ($diff < 60) return 'Baru saja';
        if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
        if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
        if ($diff < 604800) return floor($diff / 86400) . ' hari lalu';
        return date('d M Y', $time);
    }
    
    private function getFullImageUrl(string $path): string
    {
        $path = ltrim($path, '/');
        return $this->getBaseUrl() . $path;
    }
    
    private function getDefaultImageUrl(): string
    {
        return $this->getBaseUrl() . 'assets/images/default-item.jpg';
    }
    
    private function getBaseUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        
        $url = rtrim($protocol . '://' . $host . $base, '/') . '/';
        
        return $url . (substr($url, -1) !== '/' ? '/' : '');
    }
    
    private function getSampleHotspots(): array
    {
        $currentDateTime = date('Y-m-d H:i:s');
        $defaultColor = $this->getColorByCount(10); 
        
        return [
            [
                'id' => 6,
                'name' => 'Gedung B - Fakultas Teknik',
                'lat' => -5.3615,
                'lng' => 105.2470,
                'type' => 'building',
                'report_count' => 12,
                'lost_count' => 8,
                'found_count' => 4,
                'color' => $defaultColor,
                'items' => [
                    ['id' => 1, 'title' => 'Laptop Lenovo', 'type' => 'lost', 'category_name' => 'Elektronik', 'created_at' => $currentDateTime, 'time_ago' => $this->timeAgo($currentDateTime)],
                    ['id' => 2, 'title' => 'Kunci Motor', 'type' => 'found', 'category_name' => 'Kunci', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')), 'time_ago' => $this->timeAgo(date('Y-m-d H:i:s', strtotime('-1 day')))],
                    ['id' => 3, 'title' => 'Buku Kalkulus', 'type' => 'lost', 'category_name' => 'Buku & Alat Tulis', 'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')), 'time_ago' => $this->timeAgo(date('Y-m-d H:i:s', strtotime('-2 days')))]
                ]
            ],
            [
                'id' => 3,
                'name' => 'Perpustakaan Pusat',
                'lat' => -5.3640,
                'lng' => 105.2450,
                'type' => 'library',
                'report_count' => 8,
                'lost_count' => 5,
                'found_count' => 3,
                'color' => $this->getColorByCount(5),
                'items' => [
                    ['id' => 4, 'title' => 'Dompet Kulit', 'type' => 'lost', 'category_name' => 'Tas & Dompet', 'created_at' => $currentDateTime, 'time_ago' => $this->timeAgo($currentDateTime)],
                    ['id' => 5, 'title' => 'Kacamata Minus', 'type' => 'found', 'category_name' => 'Aksesoris', 'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours')), 'time_ago' => $this->timeAgo(date('Y-m-d H:i:s', strtotime('-3 hours')))]
                ]
            ],
            [
                'id' => 4,
                'name' => 'Kantin Terpadu',
                'lat' => -5.3625,
                'lng' => 105.2425,
                'type' => 'canteen',
                'report_count' => 6,
                'lost_count' => 4,
                'found_count' => 2,
                'color' => $this->getColorByCount(4),
                'items' => [
                    ['id' => 6, 'title' => 'Smartphone Xiaomi', 'type' => 'lost', 'category_name' => 'Elektronik', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')), 'time_ago' => $this->timeAgo(date('Y-m-d H:i:s', strtotime('-1 day')))],
                    ['id' => 7, 'title' => 'Jaket Hoodie', 'type' => 'found', 'category_name' => 'Pakaian', 'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')), 'time_ago' => $this->timeAgo(date('Y-m-d H:i:s', strtotime('-2 days')))]
                ]
            ],
            [
                'id' => 15,
                'name' => 'Parkiran Motor Pusat',
                'lat' => -5.3605,
                'lng' => 105.2395,
                'type' => 'parking',
                'report_count' => 4,
                'lost_count' => 3,
                'found_count' => 1,
                'color' => $this->getColorByCount(3),
                'items' => [
                    ['id' => 8, 'title' => 'Helm Bogo', 'type' => 'lost', 'category_name' => 'Aksesoris', 'created_at' => date('Y-m-d H:i:s', strtotime('-4 days')), 'time_ago' => $this->timeAgo(date('Y-m-d H:i:s', strtotime('-4 days')))]
                ]
            ]
        ];
    }
    
    private function getSampleItems(): array
    {
        $defaultUrl = $this->getDefaultImageUrl();
        $currentTime = date('Y-m-d H:i:s');
        
        return [
            [
                'id' => 1,
                'title' => 'Laptop Lenovo ThinkPad',
                'description' => 'Laptop warna hitam, ada stiker UNILA di samping, charger Lenovo original',
                'type' => 'lost',
                'status' => 'open',
                'category' => 'Elektronik',
                'location' => 'Gedung B - Fakultas Teknik',
                'lat' => -5.3615,
                'lng' => 105.2470,
                'created_at' => $currentTime,
                'time_ago' => $this->timeAgo($currentTime),
                'image_url' => $defaultUrl,
                'reporter_name' => 'John Doe'
            ],
            [
                'id' => 2,
                'title' => 'Kunci Motor Honda Beat',
                'description' => 'Kunci dengan gantungan karakter anime, ada stiker merah',
                'type' => 'found',
                'status' => 'open',
                'category' => 'Kunci',
                'location' => 'Gedung B - Fakultas Teknik',
                'lat' => -5.3615,
                'lng' => 105.2470,
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'time_ago' => $this->timeAgo(date('Y-m-d H:i:s', strtotime('-1 day'))),
                'image_url' => $defaultUrl,
                'reporter_name' => 'Jane Smith'
            ],
            [
                'id' => 3,
                'title' => 'Buku Kalkulus 2',
                'description' => 'Buku edisi 2020, ada tulisan nama "Budi" di halaman depan',
                'type' => 'lost',
                'status' => 'open',
                'category' => 'Buku & Alat Tulis',
                'location' => 'Perpustakaan Pusat',
                'lat' => -5.3640,
                'lng' => 105.2450,
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'time_ago' => $this->timeAgo(date('Y-m-d H:i:s', strtotime('-2 days'))),
                'image_url' => $defaultUrl,
                'reporter_name' => 'Budi Santoso'
            ]
        ];
    }
    
    private function getSampleCategories(): array
    {
        return [
            ['id' => 1, 'name' => 'Elektronik', 'item_count' => 8, 'lost_count' => 5, 'found_count' => 3],
            ['id' => 6, 'name' => 'Tas & Dompet', 'item_count' => 6, 'lost_count' => 4, 'found_count' => 2],
            ['id' => 7, 'name' => 'Buku & Alat Tulis', 'item_count' => 5, 'lost_count' => 3, 'found_count' => 2],
            ['id' => 5, 'name' => 'Kunci', 'item_count' => 4, 'lost_count' => 2, 'found_count' => 2],
            ['id' => 4, 'name' => 'Aksesoris', 'item_count' => 3, 'lost_count' => 2, 'found_count' => 1]
        ];
    }
    
    public function checkMapEndpoint(): void
    {
        header('Content-Type: application/json');
        
        $endpoints = [
            'mapData' => 'index.php?page=home&action=mapData',
            'mapItems' => 'index.php?page=home&action=mapItems',
            'mapSearch' => 'index.php?page=home&action=mapSearch'
        ];
        
        echo json_encode([
            'status' => 'ok',
            'message' => 'Map endpoints are working',
            'endpoints' => $endpoints,
            'timestamp' => date('Y-m-d H:i:s'),
            'server_info' => [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
            ]
        ]);
        exit;
    }
    
    public function mapStats(): void
    {
        header('Content-Type: application/json');
        
        try {
            $stats = $this->itemModel->getMapDashboardStats();
            
            echo json_encode([
                'success' => true,
                'stats' => $stats,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            error_log("MapStats Error: " . $e->getMessage());
            
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'stats' => [],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
        
        exit;
    }
}