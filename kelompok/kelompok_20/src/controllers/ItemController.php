<?php
declare(strict_types=1);

// Asumsi require_once ini berfungsi di lingkungan Anda
require_once __DIR__ . '/../models/Item.php';
// Asumsi functions global (isLoggedIn, currentUser, flash, redirect, clean, uploadImage, deleteImage, isAdmin) dimuat di index.php atau core/Functions.php
require_once __DIR__ . '/../models/Notification.php';

final class ItemController
{
    private Item $itemModel;
    private Notification $notificationModel;
    private string $uploadDir;
    private int $itemsPerPage = 12;

    public function __construct()
    {
        // PENTING: Asumsi Item model memerlukan require_once database.php
        if (file_exists(__DIR__ . '/../config/database.php')) {
            require_once __DIR__ . '/../config/database.php';
        }
        
        $this->itemModel = new Item();
        $this->notificationModel = new Notification();
        $this->uploadDir = __DIR__ . '/../assets/uploads/items/';
    }

    public function index(): void
    {
        // Asumsi clean() dimuat dan berfungsi
        $filters = [
            'type'          => clean($_GET['type'] ?? ''),
            'category_id'   => !empty($_GET['category']) ? (int) $_GET['category'] : null,
            'location_id'   => !empty($_GET['location']) ? (int) $_GET['location'] : null,
            'keyword'       => clean($_GET['q'] ?? ''),
            'status'        => clean($_GET['status'] ?? 'open') // Default status active
        ];

        if (!empty($_GET['start_date'])) {
            $filters['start_date'] = clean($_GET['start_date']);
        }
        if (!empty($_GET['end_date'])) {
            $filters['end_date'] = clean($_GET['end_date']);
        }

        $sort = clean($_GET['sort'] ?? 'newest');
        if (!in_array($sort, ['newest', 'oldest'], true)) {
            $sort = 'newest';
        }
        $filters['sort'] = $sort;

        $filters = array_filter($filters, fn($v) => $v !== null && $v !== '');

        $totalItems = $this->itemModel->countAllFiltered($filters);
        $totalPages = (int) ceil($totalItems / $this->itemsPerPage);

        $currentPage = (int) ($_GET['page'] ?? 1);
        if ($currentPage < 1) {
            $currentPage = 1;
        }
        // Perbaikan: Hindari pembagian nol saat $totalItems = 0
        if ($totalPages > 0 && $currentPage > $totalPages) {
            $currentPage = $totalPages;
        } elseif ($totalPages === 0 && $currentPage !== 1) {
            $currentPage = 1;
        }

        $offset = ($currentPage - 1) * $this->itemsPerPage;

        $filters['limit'] = $this->itemsPerPage;
        $filters['offset'] = $offset;

        $items = $this->itemModel->getAll($filters);
        $categories = $this->itemModel->getCategories();
        $locations = $this->itemModel->getLocations();

        $pagination = [
            'current_page' => $currentPage,
            'total_pages'  => $totalPages,
            'total_items'  => $totalItems,
            'per_page'     => $this->itemsPerPage,
            'has_prev'     => $currentPage > 1,
            'has_next'     => $currentPage < $totalPages
        ];

        $pageTitle = 'Daftar Barang - myUnila Lost & Found';

        require_once __DIR__ . '/../views/items/index.php';
    }

    
    public function matches(): void
    {
        // Asumsi fungsi global flash() dan redirect() dimuat
        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            flash('message', 'Item tidak valid.', 'error');
            redirect('index.php?page=items');
            return;
        }

        $item = $this->itemModel->getById($id);

        if (!$item) {
            flash('message', 'Item tidak ditemukan.', 'error');
            redirect('index.php?page=items');
            return;
        }

        $limit = (int) ($_GET['limit'] ?? 5);
        if ($limit < 1) $limit = 5;
        if ($limit > 20) $limit = 20;

        $matches = $this->itemModel->findMatches($item, $limit);

        $matchData = [
            'target_item'   => $item,
            'matches'       => $matches,
            'matches_count' => count($matches),
            'opposite_type' => ($item['type'] === 'lost') ? 'found' : 'lost'
        ];

        $pageTitle = 'Kecocokan untuk: ' . $item['title'] . ' - myUnila Lost & Found';

        require_once __DIR__ . '/../views/items/matches.php';
    }

    public function show(): void
    {
        // Asumsi fungsi global flash() dan redirect() dimuat
        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            flash('message', 'Item tidak valid.', 'error');
            redirect('index.php?page=items');
            return;
        }

        $item = $this->itemModel->getById($id);

        if (!$item) {
            flash('message', 'Item tidak ditemukan.', 'error');
            redirect('index.php?page=items');
            return;
        }

        // Asumsi fungsi global isLoggedIn() dimuat
        $isLoggedIn = isLoggedIn();
        $currentUserId = currentUser()['id'] ?? 0; // Menggunakan currentUser() yang lebih aman
        $isOwner = $isLoggedIn && (int) $currentUserId === (int) $item['user_id'];

        // Asumsi Comment model sudah didefinisikan
        require_once __DIR__ . '/../models/Comment.php';
        $commentModel = new Comment();
        $comments = $commentModel->getByItemId($id);

        $pageTitle = $item['title'] . ' - myUnila Lost & Found';

        require_once __DIR__ . '/../views/items/show.php';
    }

    // --- FUNGSI CREATE BARU (Untuk memisahkan Lost dan Found) ---

    public function createLost(): void
    {
        $this->handleCreateView('lost');
    }

    public function createFound(): void
    {
        $this->handleCreateView('found');
    }
    
    // Fungsi umum untuk menampilkan View Create
    private function handleCreateView(string $type): void
    {
        if (!isLoggedIn()) {
            flash('message', 'Silakan login untuk membuat laporan.', 'error');
            redirect('index.php?page=auth&action=login');
            return;
        }

        $categories = $this->itemModel->getCategories();
        $locations = $this->itemModel->getLocations();

        $pageTitle = ($type === 'lost' ? 'Lapor Kehilangan' : 'Lapor Temuan') . ' - myUnila Lost & Found';
        
        $itemType = $type; // Untuk dikirim ke View

        require_once __DIR__ . '/../views/items/create.php';
    }

    // Fungsi create() yang lama akan diganti dengan store()
    public function create(): void
    {
        // Default redirect ke pilih tipe, atau ke createLost()
        $this->handleCreateView('lost');
    }

    // --- FUNGSI STORE (PENCEGAHAN ERROR LENGKAP) ---

    public function store(): void
    {
        if (!isLoggedIn()) {
            flash('message', 'Silakan login untuk membuat laporan.', 'error');
            redirect('index.php?page=auth&action=login');
            return;
        }

        $user = currentUser();
        $userId = $user['id'] ?? 0;
        
        // Asumsi fungsi global clean() dimuat
        $title          = clean($_POST['title'] ?? '');
        $description    = clean($_POST['description'] ?? '');
        $type           = clean($_POST['type'] ?? '');
        $categoryId     = (int) ($_POST['category_id'] ?? 0);
        $locationId     = (int) ($_POST['location_id'] ?? 0);
        $incidentDate   = clean($_POST['incident_date'] ?? '');
        $isSafeClaim    = isset($_POST['is_safe_claim']) ? 1 : 0;
        $securityQ      = clean($_POST['security_question'] ?? '');
        $securityA      = clean($_POST['security_answer'] ?? '');
        
        // 1. Validasi Dasar
        $errors = $this->validateItem($title, $description, $type, $categoryId, $locationId, $incidentDate);

        // 2. Validasi Khusus Safe Claim
        if ($isSafeClaim && (empty($securityQ) || empty($securityA))) {
            $errors[] = 'Pertanyaan dan jawaban keamanan wajib diisi jika Safe Claim diaktifkan.';
        }
        
        // 3. Validasi Gambar (Wajib untuk Found Item)
        if ($type === 'found' && empty($_FILES['image']['name'])) {
             $errors[] = 'Untuk laporan Temuan, gambar barang wajib diunggah.';
        }


        // Jika ada Error Validasi
        if (!empty($errors)) {
            // Simpan input POST ke session untuk redisplay di form (jika ada helper input lama)
            // (Asumsi Anda punya helper untuk input lama)
            
            flash('message', implode('<br>', $errors), 'error');
            redirect('index.php?page=items&action=create' . ($type ? '_' . $type : '')); // Redirect ke form yang benar
            return;
        }

        // --- Proses Upload Gambar ---
        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            // Dapatkan nama kategori untuk penamaan file yang lebih baik
            $categories = $this->itemModel->getCategories();
            $categoryName = 'ITEM';
            foreach ($categories as $cat) {
                if ((int)$cat['id'] === $categoryId) {
                    $categoryName = $cat['name'];
                    break;
                }
            }
            
            // Asumsi fungsi global uploadImage() dimuat dan melakukan validasi ukuran/tipe
            $uploadResult = uploadImage($_FILES['image'], $this->uploadDir, $categoryName, $userId);
            
            if ($uploadResult === false) {
                // Asumsi uploadImage mengembalikan false jika gagal
                flash('message', 'Gagal upload gambar. Pastikan format JPG/PNG/WEBP dan ukuran maksimal 2MB.', 'error');
                redirect('index.php?page=items&action=create' . ($type ? '_' . $type : '')); 
                return;
            }
            $imagePath = $uploadResult;
        }

        // --- Persiapan Data dan Penyimpanan ---
        
        // Hashing Jawaban Keamanan (PENCEGAHAN ERROR 3: Keamanan Jawaban)
        $hashedSecurityA = $isSafeClaim ? password_hash(strtolower($securityA), PASSWORD_DEFAULT) : null;

        $data = [
            'user_id'           => $userId,
            'category_id'       => $categoryId,
            'location_id'       => $locationId,
            'title'             => $title,
            'description'       => $description,
            'type'              => $type,
            'incident_date'     => $incidentDate,
            'image_path'        => $imagePath,
            'status'            => 'open',
            'is_safe_claim'     => $isSafeClaim,
            'security_question' => $isSafeClaim ? $securityQ : null,
            // PENTING: Gunakan hash saat menyimpan jawaban
            'security_answer'   => $hashedSecurityA
        ];

        $itemId = $this->itemModel->create($data); // Asumsi create() mengembalikan ID atau false

        if (!$itemId) {
            // Jika penyimpanan gagal, hapus gambar yang baru diupload (PENCEGAHAN ERROR 4)
            if ($imagePath && file_exists($this->uploadDir . basename($imagePath))) {
                unlink($this->uploadDir . basename($imagePath));
            }
            
            flash('message', 'Gagal membuat laporan. Silakan coba lagi.', 'error');
            redirect('index.php?page=items&action=create' . ($type ? '_' . $type : ''));
            return;
        }

        // Buat notifikasi laporan baru
        require_once __DIR__ . '/../models/Notification.php';
        $notifModel = new Notification();
        $notifModel->create(
            $_SESSION['user_id'],
            'Laporan Dibuat',
            'Laporan Anda "' . $title . '" berhasil dibuat.',
            'index.php?page=items&action=show&id=' . $itemId,
            'item_created'
        );

        flash('message', 'Laporan berhasil dibuat!', 'success');
        redirect('index.php?page=items&action=show&id=' . $itemId);
    }
    
    // --- FUNGSI UPDATE (PENCEGAHAN ERROR/KEAMANAN) ---
    
    public function update(): void
    {
        if (!isLoggedIn()) {
            flash('message', 'Silakan login terlebih dahulu.', 'error');
            redirect('index.php?page=auth&action=login');
            return;
        }

        $id = (int) ($_POST['id'] ?? 0);
        // ... (Validasi ID dan Kepemilikan) ...

        $item = $this->itemModel->getById($id);

        if (!$item) {
            flash('message', 'Item tidak ditemukan.', 'error');
            redirect('index.php?page=items');
            return;
        }

        if (!$this->isOwnerOrAdmin($item)) {
            flash('message', 'Anda tidak memiliki akses untuk mengedit item ini.', 'error');
            redirect('index.php?page=items');
            return;
        }

        $title          = clean($_POST['title'] ?? '');
        $description    = clean($_POST['description'] ?? '');
        $type           = clean($_POST['type'] ?? '');
        $categoryId     = (int) ($_POST['category_id'] ?? 0);
        $locationId     = (int) ($_POST['location_id'] ?? 0);
        $incidentDate   = clean($_POST['incident_date'] ?? '');
        $isSafeClaim    = isset($_POST['is_safe_claim']) ? 1 : 0;
        $securityQ      = clean($_POST['security_question'] ?? '');
        $securityA      = clean($_POST['security_answer'] ?? ''); // Ini bisa jadi jawaban baru atau hash lama

        $errors = $this->validateItem($title, $description, $type, $categoryId, $locationId, $incidentDate);

        if ($isSafeClaim && (empty($securityQ) || empty($securityA))) {
            // Jika Safe Claim diaktifkan, pertanyaan dan jawaban harus ada.
            // Karena jawaban bisa berupa hash lama, kita cek jika jawabannya hash atau string kosong
            if (empty($securityQ) || (empty($securityA) && empty($item['security_answer']))) {
                 $errors[] = 'Pertanyaan dan jawaban keamanan wajib diisi jika Safe Claim diaktifkan.';
            }
        }
        
        if (!empty($errors)) {
            flash('message', implode('<br>', $errors), 'error');
            redirect('index.php?page=items&action=edit&id=' . $id);
            return;
        }

        $imagePath = $item['image_path'];

        // --- Proses Upload Gambar saat Update ---
        if (!empty($_FILES['image']['name'])) {
            $categories = $this->itemModel->getCategories();
            $categoryName = 'ITEM';
            foreach ($categories as $cat) {
                if ((int)$cat['id'] === $categoryId) {
                    $categoryName = $cat['name'];
                    break;
                }
            }
            
            $uploadResult = uploadImage($_FILES['image'], $this->uploadDir, $categoryName, $item['user_id']); // Gunakan user_id pemilik
            
            if ($uploadResult === false) {
                flash('message', 'Gagal upload gambar baru.', 'error');
                redirect('index.php?page=items&action=edit&id=' . $id);
                return;
            }

            // Hapus gambar lama jika ada (Asumsi fungsi global deleteImage dimuat)
            if (!empty($item['image_path'])) {
                deleteImage($item['image_path'], $this->uploadDir);
            }

            $imagePath = $uploadResult;
        }

        // --- Persiapan Data Update ---
        
        // Hashing Jawaban Keamanan (PENCEGAHAN ERROR/KEAMANAN)
        $securityAnswerToSave = $item['security_answer'];
        if ($isSafeClaim) {
             if (!empty($securityA)) {
                // Jika user mengisi jawaban baru, hash jawaban baru tersebut
                 $securityAnswerToSave = password_hash(strtolower($securityA), PASSWORD_DEFAULT);
             } 
             // Jika $securityA kosong, biarkan $item['security_answer'] (hash lama) yang tersimpan
        } else {
            // Jika Safe Claim dimatikan, hapus jawaban dan pertanyaan
            $securityQ = null;
            $securityAnswerToSave = null;
        }

        $data = [
            'category_id'       => $categoryId,
            'location_id'       => $locationId,
            'title'             => $title,
            'description'       => $description,
            'type'              => $type,
            'incident_date'     => $incidentDate,
            'image_path'        => $imagePath,
            'is_safe_claim'     => $isSafeClaim,
            'security_question' => $securityQ,
            'security_answer'   => $securityAnswerToSave // Gunakan hash yang sudah diproses
        ];

        $updated = $this->itemModel->update($id, $data); // Asumsi ItemModel::update() menerima hash

        if (!$updated) {
            flash('message', 'Gagal mengupdate laporan.', 'error');
            redirect('index.php?page=items&action=edit&id=' . $id);
            return;
        }

        flash('message', 'Laporan berhasil diupdate!', 'success');
        redirect('index.php?page=items&action=show&id=' . $id);
    }
    
    // --- FUNGSI LAINNYA (Tetap) ---

    public function edit(): void
    {
        if (!isLoggedIn()) {
            flash('message', 'Silakan login terlebih dahulu.', 'error');
            redirect('index.php?page=auth&action=login');
            return;
        }

        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            flash('message', 'Item tidak valid.', 'error');
            redirect('index.php?page=items');
            return;
        }

        $item = $this->itemModel->getById($id);

        if (!$item) {
            flash('message', 'Item tidak ditemukan.', 'error');
            redirect('index.php?page=items');
            return;
        }

        if (!$this->isOwnerOrAdmin($item)) {
            flash('message', 'Anda tidak memiliki akses untuk mengedit item ini.', 'error');
            redirect('index.php?page=items');
            return;
        }

        $categories = $this->itemModel->getCategories();
        $locations = $this->itemModel->getLocations();

        $pageTitle = 'Edit Laporan - myUnila Lost & Found';

        require_once __DIR__ . '/../views/items/edit.php';
    }


    public function delete(): void
    {
        if (!isLoggedIn()) {
            flash('message', 'Silakan login terlebih dahulu.', 'error');
            redirect('index.php?page=auth&action=login');
            return;
        }

        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

        if ($id <= 0) {
            flash('message', 'Item tidak valid.', 'error');
            redirect('index.php?page=items');
            return;
        }

        $item = $this->itemModel->getById($id);

        if (!$item) {
            flash('message', 'Item tidak ditemukan.', 'error');
            redirect('index.php?page=items');
            return;
        }

        if (!$this->isOwnerOrAdmin($item)) {
            flash('message', 'Anda tidak memiliki akses untuk menghapus item ini.', 'error');
            redirect('index.php?page=items');
            return;
        }

        $deleted = $this->itemModel->delete($id);

        if (!$deleted) {
            flash('message', 'Gagal menghapus laporan.', 'error');
            redirect('index.php?page=items&action=my');
            return;
        }
        
        // PENTING: Hapus file gambar setelah delete berhasil
        if (!empty($item['image_path'])) {
            deleteImage($item['image_path'], $this->uploadDir);
        }

        flash('message', 'Laporan berhasil dihapus.', 'success');
        redirect('index.php?page=items&action=my');
    }

    public function myItems(): void
    {
        if (!isLoggedIn()) {
            flash('message', 'Silakan login terlebih dahulu.', 'error');
            redirect('index.php?page=auth&action=login');
            return;
        }

        $items = $this->itemModel->getByUserId(currentUser()['id'] ?? 0); // Menggunakan currentUser()

        $pageTitle = 'Laporan Saya - myUnila Lost & Found';

        require_once __DIR__ . '/../views/items/my_items.php';
    }

    private function validateItem(
        string $title,
        string $description,
        string $type,
        int $categoryId,
        int $locationId,
        string $incidentDate
    ): array {
        $errors = [];

        if (empty($title) || strlen($title) < 5) {
            $errors[] = 'Judul minimal 5 karakter.';
        }

        if (empty($description) || strlen($description) < 20) {
            $errors[] = 'Deskripsi minimal 20 karakter.';
        }

        if (!in_array($type, ['lost', 'found'], true)) {
            $errors[] = 'Tipe laporan tidak valid.';
        }

        if ($categoryId <= 0) {
            $errors[] = 'Pilih kategori yang valid.';
        }

        if ($locationId <= 0) {
            $errors[] = 'Pilih lokasi yang valid.';
        }

        if (empty($incidentDate)) {
            $errors[] = 'Tanggal kejadian wajib diisi.';
        } elseif (strtotime($incidentDate) > time()) {
            $errors[] = 'Tanggal kejadian tidak boleh di masa depan.';
        }

        return $errors;
    }

    private function isOwnerOrAdmin(array $item): bool
    {
        $currentUserId = currentUser()['id'] ?? 0; // Menggunakan currentUser()
        $isOwner = (int) $item['user_id'] === (int) $currentUserId;

        // Asumsi fungsi global isAdmin() dimuat
        return $isOwner || isAdmin();
    }
}

    /**
     * Mencari item yang cocok dan memberi notifikasi ke pemilik item tersebut
     * Ketika ada laporan baru, cek apakah ada item dengan tipe berlawanan (lost vs found)
     * yang cocok berdasarkan kategori dan lokasi
     */
    private function notifyMatchingItems(int $newItemId, string $type, string $title, int $categoryId, int $locationId): void
    {
        // Ambil data item yang baru dibuat
        $newItem = $this->itemModel->getById($newItemId);
        if (!$newItem) {
            return;
        }
        
        // Cari item yang cocok menggunakan findMatches
        $matches = $this->itemModel->findMatches($newItem, 5);
        
        if (empty($matches)) {
            return;
        }

        $typeLabel = $type === 'lost' ? 'kehilangan' : 'penemuan';
        $oppositeLabel = $oppositeType === 'lost' ? 'kehilangan' : 'penemuan';
        
        // Notifikasi ke pemilik item yang cocok (tanpa duplikasi)
        $notifiedUsers = [];
        foreach ($matches as $match) {
            $matchOwnerId = (int) $match['user_id'];
            
            // Skip jika user yang sama atau sudah dinotifikasi
            if ($matchOwnerId === $_SESSION['user_id'] || in_array($matchOwnerId, $notifiedUsers, true)) {
                continue;
            }
            
            $this->notificationModel->create(
                $matchOwnerId,
                '🔍 Ada Barang yang Cocok!',
                "Laporan {$typeLabel} baru \"{$title}\" mungkin cocok dengan laporan {$oppositeLabel} Anda. Cek sekarang!",
                "index.php?page=items&action=show&id={$newItemId}",
                'item_match'
            );
            
            $notifiedUsers[] = $matchOwnerId;
        }
    }
}
