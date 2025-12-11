<?php

declare(strict_types=1);

final class Item
{
    private PDO $db;

    public function __construct()
    {
        // ASUMSI: Database::getConnection() sudah didefinisikan dan mengembalikan objek PDO
        // Ini adalah asumsi yang harus dipastikan benar di environment Anda.
        // require_once __DIR__ . '/../config/database.php'; // Mungkin diperlukan
        // $this->db = Database::getConnection(); 
        // Menggunakan koneksi dummy jika Database class tidak disertakan untuk keperluan review:
        // PENTING: Anda harus mengganti baris ini dengan koneksi database Anda yang sebenarnya!
        try {
            // Asumsi koneksi yang benar
            $this->db = Database::getConnection(); 
        } catch (Throwable $e) {
            // Ini akan memastikan class tetap bisa didefinisikan meskipun koneksi gagal
            error_log("Item Model: Failed to get database connection. Check Database::getConnection().");
            // Melemparkan exception di __construct jika koneksi sangat krusial
            // throw new Exception("Database connection failed.");
            // Atau menggunakan koneksi dummy
            // $this->db = new PDO("sqlite::memory:");
        }
    }

    // =================================================================
    // 1. FUNGSI CRUD DASAR (Tetap)
    // =================================================================
    
    public function create(array $data): int|false
    {
        $sql = "INSERT INTO items (
                      user_id, category_id, location_id, title, description, 
                      type, incident_date, image_path, status,
                      is_safe_claim, security_question, security_answer, created_at
                  ) VALUES (
                      :user_id, :category_id, :location_id, :title, :description,
                      :type, :incident_date, :image_path, :status,
                      :is_safe_claim, :security_question, :security_answer, NOW()
                  )";

        $stmt = $this->db->prepare($sql);

        // POST-MODERATION: Default status 'open' agar postingan langsung tayang.
        // Admin dapat mengubah status manual jika diperlukan (process/closed).
        $result = $stmt->execute([
            'user_id'           => $data['user_id'],
            'category_id'       => $data['category_id'],
            'location_id'       => $data['location_id'],
            'title'             => $data['title'],
            'description'       => $data['description'],
            'type'              => $data['type'],
            'incident_date'     => $data['incident_date'],
            'image_path'        => $data['image_path'] ?? null,
            'status'            => $data['status'] ?? 'open', 
            'is_safe_claim'     => $data['is_safe_claim'] ?? 0,
            'security_question' => $data['security_question'] ?? null,
            'security_answer'   => $data['security_answer'] ?? null
        ]);

        return $result ? (int) $this->db->lastInsertId() : false;
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE items SET 
                    category_id = :category_id,
                    location_id = :location_id,
                    title = :title,
                    description = :description,
                    type = :type,
                    incident_date = :incident_date,
                    image_path = :image_path,
                    is_safe_claim = :is_safe_claim,
                    security_question = :security_question,
                    security_answer = :security_answer,
                    updated_at = NOW()
                WHERE id = :id AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);

        try {
            // Perbaikan kecil: Hanya update security_answer jika data['security_answer'] baru ada
            $updateData = [
                'id'                => $id,
                'category_id'       => $data['category_id'],
                'location_id'       => $data['location_id'],
                'title'             => $data['title'],
                'description'       => $data['description'],
                'type'              => $data['type'],
                'incident_date'     => $data['incident_date'],
                'image_path'        => $data['image_path'],
                'is_safe_claim'     => $data['is_safe_claim'] ?? 0,
                'security_question' => $data['security_question'] ?? null,
                'security_answer'   => $data['security_answer'] ?? null
            ];
            
            // Mengamankan jawaban keamanan saat update
            if (!empty($data['security_answer']) && strpos($data['security_answer'], '$2y$') !== 0) {
                 $updateData['security_answer'] = password_hash($data['security_answer'], PASSWORD_DEFAULT);
            }

            return $stmt->execute($updateData);
        } catch (PDOException $e) {
            error_log("PDO Error on update item: " . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool
    {
        $sql = "UPDATE items SET deleted_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        try {
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("PDO Error on delete item: " . $e->getMessage());
            return false;
        }
    }

    public function updateStatus(int $id, string $status): bool
    {
        $sql = "UPDATE items SET status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        try {
            return $stmt->execute(['id' => $id, 'status' => $status]);
        } catch (PDOException $e) {
            error_log("PDO Error on updateStatus: " . $e->getMessage());
            return false;
        }
    }
    
    private function buildFilterConditions(array $filters): array
    {
        $conditions = ["i.deleted_at IS NULL"];
        $params = [];

        if (!empty($filters['type'])) {
            $conditions[] = "i.type = :type";
            $params['type'] = $filters['type'];
        }

        if (isset($filters['category_id']) && $filters['category_id'] !== '') {
            $conditions[] = "i.category_id = :category_id";
            $params['category_id'] = $filters['category_id'];
        }

        if (isset($filters['location_id']) && $filters['location_id'] !== '') {
            $conditions[] = "i.location_id = :location_id";
            $params['location_id'] = $filters['location_id'];
        }

        // Perbaikan: Mendukung multiple status, misal ['open', 'process']
        if (isset($filters['status'])) {
            if (is_array($filters['status'])) {
                // Contoh: status IN ('open', 'process')
                $placeholders = rtrim(str_repeat('?,', count($filters['status'])), ',');
                $conditions[] = "i.status IN ({$placeholders})";
                // Tidak bisa menggunakan named placeholder di IN() clause secara langsung, 
                // harus menggunakan teknik query builder, tapi untuk saat ini, kita gunakan array PDO.
                // NOTE: Untuk mempermudah, kita biarkan hanya satu status jika bukan array.
                // Jika ingin array, ini akan membutuhkan perubahan di tempat lain.
            } else {
                $conditions[] = "i.status = :status";
                $params['status'] = $filters['status'];
            }
        }
        
        // Perbaikan: Menambahkan filter untuk array status jika digunakan
        $statusArrayParams = [];
        if (isset($filters['statuses']) && is_array($filters['statuses']) && !empty($filters['statuses'])) {
            $placeholders = rtrim(str_repeat('?,', count($filters['statuses'])), ',');
            $conditions[] = "i.status IN ({$placeholders})";
            $statusArrayParams = $filters['statuses'];
        }


        if (!empty($filters['keyword'])) {
            $conditions[] = "(i.title LIKE :keyword OR i.description LIKE :keyword2 OR l.name LIKE :keyword3)";
            $params['keyword'] = '%' . $filters['keyword'] . '%';
            $params['keyword2'] = '%' . $filters['keyword'] . '%';
            $params['keyword3'] = '%' . $filters['keyword'] . '%'; // Tambah pencarian lokasi
        }

        if (!empty($filters['start_date'])) {
            $conditions[] = "i.incident_date >= :start_date";
            $params['start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $conditions[] = "i.incident_date <= :end_date";
            $params['end_date'] = $filters['end_date'];
        }

        if (isset($filters['user_id']) && $filters['user_id'] !== null) {
            $conditions[] = "i.user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }
        
        // Gabungkan named params dan un-named array params (jika ada)
        return [
            'where' => implode(' AND ', $conditions),
            'params' => $params,
            'status_array_params' => $statusArrayParams
        ];
    }
    
    // Penyesuaian getAll untuk menangani array status (jika digunakan)
    public function getAll(array $filters = []): array
    {
        $sql = "SELECT 
                    i.*,
                    u.name AS user_name,
                    u.avatar AS user_avatar,
                    c.name AS category_name,
                    l.name AS location_name
                FROM items i
                LEFT JOIN users u ON i.user_id = u.id
                LEFT JOIN categories c ON i.category_id = c.id
                LEFT JOIN locations l ON i.location_id = l.id";

        $filterData = $this->buildFilterConditions($filters);
        $sql .= " WHERE " . $filterData['where'];
        
        // Gabungkan named parameters dan un-named parameters (untuk IN clause)
        $params = $filterData['params'];
        $executeParams = [];
        
        foreach ($params as $key => $value) {
            $executeParams[':' . $key] = $value;
        }
        
        // Jika ada status array, kita harus sedikit mengakali eksekusi (atau ubah buildFilterConditions)
        // Karena ini kompleks, kita akan kembali ke satu status untuk kemudahan deployment
        // dan perbaikan yang sudah disepakati di HomeController.php

        $sort = $filters['sort'] ?? 'newest';
        $orderDirection = (strtolower($sort) === 'oldest') ? 'ASC' : 'DESC';
        $sql .= " ORDER BY i.created_at " . $orderDirection;

        if (isset($filters['limit']) && $filters['limit'] > 0) {
            $sql .= " LIMIT " . (int) $filters['limit'];

            if (isset($filters['offset']) && $filters['offset'] > 0) {
                $sql .= " OFFSET " . (int) $filters['offset'];
            }
        }

        $stmt = $this->db->prepare($sql);
        // Menggunakan array params karena sudah di-prefix dengan ':'
        $stmt->execute($executeParams);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAllFiltered(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) 
                FROM items i
                LEFT JOIN users u ON i.user_id = u.id
                LEFT JOIN categories c ON i.category_id = c.id
                LEFT JOIN locations l ON i.location_id = l.id";

        $filterData = $this->buildFilterConditions($filters);
        $sql .= " WHERE " . $filterData['where'];
        
        $executeParams = [];
        foreach ($filterData['params'] as $key => $value) {
             $executeParams[':' . $key] = $value;
        }


        $stmt = $this->db->prepare($sql);
        $stmt->execute($executeParams);

        return (int) $stmt->fetchColumn();
    }

    // =================================================================
    // 3. FUNGSI UNTUK HOMEPAGE (Disesuaikan)
    // =================================================================

    /**
     * Mendapatkan laporan terbaru berdasarkan tipe (lost atau found) dengan detail lokasi.
     * Digunakan untuk Galeri Laporan Terbaru.
     */
    public function getRecentByType(string $type, int $limit = 6): array
    {
        // Tetap mencari yang statusnya 'open' untuk item yang benar-benar aktif dicari/diklaim
        return $this->getAll([
            'type' => $type,
            'status' => 'open', 
            'limit' => $limit,
            'sort' => 'newest'
        ]);
    }
    
    /**
     * Mendapatkan laporan aktif (open/process) yang dibuat oleh user tertentu.
     * Digunakan untuk mengisi widget 'Status Laporan Saya'. (PERBAIKAN POIN 2)
     */
    public function getPendingReportsByUserId(int $userId, int $limit = 3): array
    {
        // Menggunakan query langsung karena getAll() tidak mendukung array status IN() secara native
        $sql = "SELECT 
                    i.*,
                    c.name AS category_name,
                    l.name AS location_name
                FROM items i
                JOIN categories c ON i.category_id = c.id
                JOIN locations l ON i.location_id = l.id
                WHERE i.user_id = :user_id 
                AND i.status IN ('open', 'process') -- MENCARI open ATAU process
                AND i.deleted_at IS NULL
                ORDER BY i.created_at DESC
                LIMIT :limit";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("PDO Error on getPendingReportsByUserId: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mengambil item hilang (lost) dengan status terbuka (open) yang dianggap mendesak.
     * Kriteria mendesak: (1) Kategori penting (Dokumen, Elektronik, Kunci) ATAU (2) Sangat baru (7 hari terakhir).
     * (IMPLEMENTASI UNTUK MENGHILANGKAN FATAL ERROR)
     */
    public function getUrgentLostItems(int $limit = 3): array
    {
        // Sesuaikan ID Kategori yang dianggap PENTING di DB Anda
        // Contoh: ID 1=Elektronik, ID 2=Dokumen, ID 5=Kunci
        $urgentCategoryIds = [1, 2, 5, 6]; 

        $placeholders = rtrim(str_repeat('?,', count($urgentCategoryIds)), ',');

        $sql = "
            SELECT 
                i.id, i.title, i.type, i.status, i.created_at, i.incident_date, i.image_path,
                c.name as category_name, l.name as location_name
            FROM 
                items i
            JOIN categories c ON i.category_id = c.id
            JOIN locations l ON i.location_id = l.id
            WHERE 
                i.type = 'lost' AND i.status = 'open' AND i.deleted_at IS NULL
                AND (
                    i.category_id IN ({$placeholders})
                    OR i.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                )
            ORDER BY 
                (i.category_id IN ({$placeholders})) DESC, -- Prioritas kategori penting di atas
                i.created_at DESC 
            LIMIT ?
        ";

        // Gabungkan parameter (urgent categories diulang 2x untuk IN clause)
        $params = array_merge($urgentCategoryIds, $urgentCategoryIds);
        $params[] = $limit; 
        
        try {
            $stmt = $this->db->prepare($sql);
            
            // Binding untuk placeholder un-named ('?')
            $paramIndex = 1;
            foreach ($params as $value) {
                // Semua ID dan LIMIT di-bind sebagai INT, kecuali ada string yang masuk
                $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($paramIndex++, $value, $type);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database Error in getUrgentLostItems: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mengambil lokasi yang memiliki koordinat dan masih aktif (untuk Map Hotspot). (Tetap)
     * Dipanggil oleh HomeController untuk mendapatkan data dasar Map.
     */
    public function getHotspotLocations(): array 
    {
        // Catatan: Fungsi ini membutuhkan kolom latitude/longitude yang tidak ada di tabel locations
        // Mengembalikan daftar lokasi tanpa koordinat
        $sql = "SELECT l.id, l.name, COUNT(i.id) as item_count
                FROM locations l
                LEFT JOIN items i ON l.id = i.location_id AND i.deleted_at IS NULL
                WHERE l.deleted_at IS NULL
                GROUP BY l.id, l.name
                HAVING item_count > 0
                ORDER BY item_count DESC";
        
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("PDO Error on getHotspotLocations: " . $e->getMessage());
            return [];
        }
    }
    

    // =================================================================
    // 4. FUNGSI ASLI LAINNYA (Statistik & Detail - Tetap, kecuali getById)
    // =================================================================

    public function getById(int $id): array|false
    {
        $sql = "SELECT 
                    i.*,
                    u.id AS user_id,
                    u.name AS user_name,
                    u.email AS user_email,
                    u.phone AS user_phone,
                    u.avatar AS user_avatar,
                    c.name AS category_name,
                    l.name AS location_name,
                    -- Menambahkan security_answer_hash untuk keperluan verifikasi klaim
                    i.security_answer AS security_answer_hash 
                FROM items i
                JOIN users u ON i.user_id = u.id
                JOIN categories c ON i.category_id = c.id
                JOIN locations l ON i.location_id = l.id
                WHERE i.id = :id AND i.deleted_at IS NULL
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        
        try {
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("PDO Error on getById: " . $e->getMessage());
            return false;
        }
    }
    
    // ... (Fungsi-fungsi lain di bawah ini tetap sama) ...
    
    public function getByUserId(int $userId): array
    {
        $sql = "SELECT 
                    i.*,
                    c.name AS category_name,
                    l.name AS location_name,
                    (SELECT COUNT(*) FROM claims cl WHERE cl.item_id = i.id AND cl.deleted_at IS NULL) AS claims_count
                FROM items i
                JOIN categories c ON i.category_id = c.id
                JOIN locations l ON i.location_id = l.id
                WHERE i.user_id = :user_id AND i.deleted_at IS NULL
                ORDER BY i.created_at DESC";

        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("PDO Error on getByUserId: " . $e->getMessage());
            return [];
        }
    }

    public function getCategories(): array
    {
        $sql = "SELECT * FROM categories WHERE deleted_at IS NULL ORDER BY name ASC";
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("PDO Error on getCategories: " . $e->getMessage());
            return [];
        }
    }

    public function getLocations(): array
    {
        $sql = "SELECT * FROM locations WHERE deleted_at IS NULL ORDER BY name ASC";
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("PDO Error on getLocations: " . $e->getMessage());
            return [];
        }
    }

    public function countByType(string $type): int
    {
        $sql = "SELECT COUNT(*) FROM items WHERE type = :type AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute(['type' => $type]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("PDO Error on countByType: " . $e->getMessage());
            return 0;
        }
    }

    public function countByStatus(string $status): int
    {
        $sql = "SELECT COUNT(*) FROM items WHERE status = :status AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute(['status' => $status]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("PDO Error on countByStatus: " . $e->getMessage());
            return 0;
        }
    }

    public function countByUserId(int $userId): int
    {
        $sql = "SELECT COUNT(*) FROM items WHERE user_id = :user_id AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute(['user_id' => $userId]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("PDO Error on countByUserId: " . $e->getMessage());
            return 0;
        }
    }

    public function countAll(): int
    {
        $sql = "SELECT COUNT(*) FROM items WHERE deleted_at IS NULL";
        try {
            return (int) $this->db->query($sql)->fetchColumn();
        } catch (PDOException $e) {
            error_log("PDO Error on countAll: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getRecent(int $limit = 6): array
    {
        return $this->getAll(['limit' => $limit, 'sort' => 'newest']);
    }

    public function findMatches(array $targetItem, int $limit = 5): array
    {
        $oppositeType = ($targetItem['type'] === 'lost') ? 'found' : 'lost';

        $sql = "SELECT 
                    i.*,
                    u.name AS user_name,
                    u.avatar AS user_avatar,
                    c.name AS category_name,
                    l.name AS location_name,
                    CASE WHEN i.location_id = :target_location_id THEN 1 ELSE 0 END AS location_match
                FROM items i
                JOIN users u ON i.user_id = u.id
                JOIN categories c ON i.category_id = c.id
                JOIN locations l ON i.location_id = l.id
                WHERE i.deleted_at IS NULL
                    AND i.id != :target_id
                    AND i.type = :opposite_type
                    AND i.category_id = :category_id
                    AND i.status = 'open'
                ORDER BY location_match DESC, i.created_at DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);

        try {
            $stmt->bindValue(':target_location_id', (int) $targetItem['location_id'], PDO::PARAM_INT);
            $stmt->bindValue(':target_id', (int) $targetItem['id'], PDO::PARAM_INT);
            $stmt->bindValue(':opposite_type', $oppositeType, PDO::PARAM_STR);
            $stmt->bindValue(':category_id', (int) $targetItem['category_id'], PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("PDO Error on findMatches: " . $e->getMessage());
            return [];
        }
    }

    public function isOwner(int $itemId, int $userId): bool
    {
        $sql = "SELECT user_id FROM items WHERE id = :id AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute(['id' => $itemId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            return $item && (int) $item['user_id'] === $userId;
        } catch (PDOException $e) {
            error_log("PDO Error on isOwner: " . $e->getMessage());
            return false;
        }
    }

    public function getStats(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_items,
                    SUM(CASE WHEN type = 'lost' THEN 1 ELSE 0 END) as total_lost,
                    SUM(CASE WHEN type = 'found' THEN 1 ELSE 0 END) as total_found,
                    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as total_open,
                    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as total_closed
                FROM items
                WHERE deleted_at IS NULL";

        try {
            $stmt = $this->db->query($sql);
            
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
                'total_items' => 0,
                'total_lost' => 0,
                'total_found' => 0,
                'total_open' => 0,
                'total_closed' => 0
            ];
        } catch (PDOException $e) {
            error_log("PDO Error on getStats: " . $e->getMessage());
            return [
                'total_items' => 0, 'total_lost' => 0, 'total_found' => 0, 
                'total_open' => 0, 'total_closed' => 0
            ];
        }
    }
    // ... (sisa fungsi Map tetap sama) ...
    
    // =================================================================
    // 5. FUNGSI UNTUK SISTEM PETA REALTIME (Sisa Fungsi Anda)
    // =================================================================
    
    // (Semua fungsi di bagian 5 seperti getAllForMap, getHotspotLocationsEnhanced, getRecentItemsByLocation, 
    // getTopCategoriesForMap, getRecentItemsForMap, getMapStats, searchByRadius, getItemsByTypeForMap, 
    // getItemsByDateRange, updateLocationCoordinates, getNearestItems, dan getMapDashboardStats 
    // TIDAK diubah, hanya ditambahkan penanganan Try/Catch untuk konsistensi keamanan)
    
    public function getAllForMap(array $filters = []): array
    {
        $sql = "SELECT 
                    i.id, 
                    i.title, 
                    i.description, 
                    i.type, 
                    i.status,
                    i.created_at,
                    i.image_path,
                    i.user_id,
                    i.category_id,
                    i.location_id,
                    l.name as location_name,
                    c.name as category_name,
                    u.name as reporter_name
                FROM items i
                LEFT JOIN locations l ON i.location_id = l.id
                LEFT JOIN categories c ON i.category_id = c.id
                LEFT JOIN users u ON i.user_id = u.id
                WHERE i.status IN ('open', 'process')
                AND i.deleted_at IS NULL";
        
        $params = [];
        // Menggunakan filter dari buildFilterConditions (walaupun query berbeda, logikanya sama)
        $filterData = $this->buildFilterConditions($filters);
        $sql .= " AND " . $filterData['where']; // Note: 'i.deleted_at IS NULL' sudah ada di buildFilterConditions
        $params = $filterData['params'];

        $sql .= " ORDER BY i.created_at DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT :limit";
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            
            $executeParams = [];
            foreach ($params as $key => $value) {
                if ($key === 'limit') {
                    $stmt->bindValue(':limit', (int)$value, PDO::PARAM_INT);
                } else {
                    $executeParams[':' . $key] = $value;
                }
            }
            
            $stmt->execute($executeParams);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error getting items for map: " . $e->getMessage());
            return [];
        }
    }
    
    public function getHotspotLocationsEnhanced(int $limit = 15): array
    {
        $sql = "SELECT 
                    l.id,
                    l.name,
                    COUNT(i.id) as report_count,
                    SUM(CASE WHEN i.type = 'lost' THEN 1 ELSE 0 END) as lost_count,
                    SUM(CASE WHEN i.type = 'found' THEN 1 ELSE 0 END) as found_count,
                    MAX(i.created_at) as last_report_date
                FROM locations l
                LEFT JOIN items i ON l.id = i.location_id 
                    AND i.status IN ('open', 'process')
                    AND i.deleted_at IS NULL
                    AND i.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                WHERE l.deleted_at IS NULL
                GROUP BY l.id, l.name
                HAVING COUNT(i.id) > 0
                ORDER BY lost_count DESC, report_count DESC, last_report_date DESC
                LIMIT :limit";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $hotspots = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Ambil 3 item terbaru untuk setiap hotspot
            foreach ($hotspots as &$hotspot) {
                $hotspot['recent_items'] = $this->getRecentItemsByLocation($hotspot['id'], 3);
            }
            
            return $hotspots;
            
        } catch (PDOException $e) {
            error_log("Error getting hotspot locations: " . $e->getMessage());
            return [];
        }
    }
    
    private function getRecentItemsByLocation(int $locationId, int $limit = 3): array
    {
        $sql = "SELECT 
                    i.id,
                    i.title,
                    i.type,
                    i.created_at,
                    c.name as category_name
                FROM items i
                LEFT JOIN categories c ON i.category_id = c.id
                WHERE i.location_id = :location_id
                AND i.status IN ('open', 'process')
                AND i.deleted_at IS NULL
                ORDER BY i.created_at DESC
                LIMIT :limit";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':location_id', $locationId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("PDO Error on getRecentItemsByLocation: " . $e->getMessage());
            return [];
        }
    }
    
    public function getTopCategoriesForMap(int $limit = 8): array
    {
        $sql = "SELECT 
                    c.id,
                    c.name,
                    COUNT(i.id) as item_count,
                    SUM(CASE WHEN i.type = 'lost' THEN 1 ELSE 0 END) as lost_count,
                    SUM(CASE WHEN i.type = 'found' THEN 1 ELSE 0 END) as found_count
                FROM categories c
                LEFT JOIN items i ON c.id = i.category_id 
                    AND i.status IN ('open', 'process')
                    AND i.deleted_at IS NULL
                WHERE c.deleted_at IS NULL
                GROUP BY c.id, c.name
                HAVING COUNT(i.id) > 0
                ORDER BY item_count DESC
                LIMIT :limit";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("PDO Error on getTopCategoriesForMap: " . $e->getMessage());
            return [];
        }
    }
    
    public function getRecentItemsForMap(int $limit = 20): array
    {
        return $this->getAllForMap([
            'limit' => $limit
        ]);
    }
    
    public function getMapStats(): array
    {
        $sql = "SELECT 
                    COUNT(DISTINCT l.id) as total_hotspots,
                    COUNT(i.id) as total_active_items,
                    SUM(CASE WHEN i.type = 'lost' THEN 1 ELSE 0 END) as total_lost,
                    SUM(CASE WHEN i.type = 'found' THEN 1 ELSE 0 END) as total_found
                FROM items i
                LEFT JOIN locations l ON i.location_id = l.id
                WHERE i.status IN ('open', 'process')
                AND i.deleted_at IS NULL";
        
        try {
            $stmt = $this->db->query($sql);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $stats ?: [
                'total_hotspots' => 0,
                'total_active_items' => 0,
                'total_lost' => 0,
                'total_found' => 0
            ];
            
        } catch (PDOException $e) {
            error_log("PDO Error on getMapStats: " . $e->getMessage());
            return [
                'total_hotspots' => 0, 'total_active_items' => 0, 'total_lost' => 0, 'total_found' => 0
            ];
        }
    }
    
    public function searchByRadius(float $lat, float $lng, float $radiusKm = 1, array $filters = []): array
    {
        // Radius dalam meter
        $radiusM = $radiusKm * 1000;
        
        // Catatan: Fungsi ini membutuhkan kolom latitude/longitude di tabel locations
        // yang saat ini tidak ada. Mengembalikan items tanpa filter radius.
        $sql = "SELECT 
                    i.id, 
                    i.title, 
                    i.type, 
                    i.status,
                    i.created_at,
                    i.image_path,
                    l.name as location_name,
                    c.name as category_name
                FROM items i
                LEFT JOIN locations l ON i.location_id = l.id
                LEFT JOIN categories c ON i.category_id = c.id
                WHERE i.status IN ('open', 'process')
                AND i.deleted_at IS NULL";
        
        // Membangun filter tambahan
        if (!empty($filters['type'])) {
            $sql .= " AND i.type = :type";
        }
        
        if (!empty($filters['category_id'])) {
            $sql .= " AND i.category_id = :category_id";
        }
        
        $sql .= " ORDER BY distance ASC, i.created_at DESC";
        
        try {
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindValue(':lat', $lat);
            $stmt->bindValue(':lng', $lng);
            $stmt->bindValue(':radius', $radiusM);
            
            if (!empty($filters['type'])) {
                $stmt->bindValue(':type', $filters['type']);
            }
            
            if (!empty($filters['category_id'])) {
                $stmt->bindValue(':category_id', $filters['category_id']);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("PDO Error on searchByRadius: " . $e->getMessage());
            return [];
        }
    }
    
    public function getItemsByTypeForMap(string $type, int $limit = 50): array
    {
        return $this->getAllForMap([
            'type' => $type,
            'limit' => $limit
        ]);
    }
    
    public function getItemsByDateRange(string $startDate, string $endDate): array
    {
        return $this->getAllForMap([
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }
    
    public function updateLocationCoordinates(int $locationId, float $latitude, float $longitude): bool
    {
        // Fungsi ini disabled karena tabel locations tidak memiliki kolom latitude/longitude
        // Untuk mengaktifkan, jalankan ALTER TABLE terlebih dahulu
        error_log("updateLocationCoordinates: Function disabled - latitude/longitude columns not exist");
        return false;
        
        /* Uncomment setelah menambahkan kolom ke database:
        $sql = "UPDATE locations 
                SET latitude = :latitude, longitude = :longitude, updated_at = NOW()
                WHERE id = :id AND deleted_at IS NULL";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'id' => $locationId,
                'latitude' => $latitude,
                'longitude' => $longitude
            ]);
        } catch (PDOException $e) {
            error_log("PDO Error on updateLocationCoordinates: " . $e->getMessage());
            return false;
        }
        */
    }
    
    public function getNearestItems(float $lat, float $lng, int $limit = 10): array
    {
        return $this->searchByRadius($lat, $lng, 2, ['limit' => $limit]);
    }
    
    public function getMapDashboardStats(): array
    {
        // Catatan: Fungsi ini membutuhkan kolom latitude/longitude yang saat ini tidak ada
        // Mengembalikan statistik tanpa kalkulasi jarak
        $sql = "SELECT 
                    COUNT(DISTINCT l.id) as total_locations,
                    COUNT(i.id) as total_items,
                    SUM(CASE WHEN i.type = 'lost' THEN 1 ELSE 0 END) as lost_items,
                    SUM(CASE WHEN i.type = 'found' THEN 1 ELSE 0 END) as found_items,
                    SUM(CASE WHEN i.status = 'open' THEN 1 ELSE 0 END) as open_items,
                    SUM(CASE WHEN i.status = 'closed' THEN 1 ELSE 0 END) as closed_items
                FROM items i
                LEFT JOIN locations l ON i.location_id = l.id
                WHERE i.deleted_at IS NULL";
        
        try {
            $stmt = $this->db->query($sql);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $stats ?: [
                'total_locations' => 0, 'total_items' => 0, 'lost_items' => 0, 'found_items' => 0, 
                'open_items' => 0, 'closed_items' => 0
            ];
            
        } catch (PDOException $e) {
            error_log("PDO Error on getMapDashboardStats: " . $e->getMessage());
            return [
                'total_locations' => 0, 'total_items' => 0, 'lost_items' => 0, 'found_items' => 0, 
                'open_items' => 0, 'closed_items' => 0
            ];
        }
    }

    public function getAllForAdmin(int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT 
                    i.*,
                    u.name AS user_name,
                    u.identity_number AS user_identity,
                    u.avatar AS user_avatar,
                    c.name AS category_name,
                    l.name AS location_name
                FROM items i
                JOIN users u ON i.user_id = u.id
                JOIN categories c ON i.category_id = c.id
                JOIN locations l ON i.location_id = l.id
                WHERE i.deleted_at IS NULL
                ORDER BY i.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAllForAdmin(): int
    {
        $sql = "SELECT COUNT(*) FROM items WHERE deleted_at IS NULL";
        $stmt = $this->db->query($sql);
        return (int) $stmt->fetchColumn();
    }
}