<?php 

    // --- VARIABEL PENDUKUNG UI ---
    $is_found = $item['type'] === 'found';
    $is_claimable = $item['status'] === 'open';
    
    // Logic untuk menentukan warna status 
    $status_color = [
        'open' => 'bg-yellow-600',
        'process' => 'bg-indigo-600',
        'closed' => 'bg-green-600'
    ][$item['status']] ?? 'bg-gray-500';
    
    // Blur Logic (CSS Class): Aktif jika ditemukan DAN Safe-Claim aktif
    $blur_class = ($is_found && $item['is_safe_claim']) ? 'photo-blurred' : ''; 

    // --- VARIABEL DARI MODEL (AMAN DIGUNAKAN) ---
    $reporterName = $item['user_name']; 
    $locationName = $item['location_name']; 
    $categoryName = $item['category_name'];
    $reporterWA = $item['user_phone']; 
?>

<style>
   
    .photo-blurred {
        filter: blur(15px); 
        transition: filter 0.5s ease;
    }
</style>

<div class="bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        
        <div class="bg-white shadow-2xl rounded-xl overflow-hidden p-6 lg:p-10 border-t-4 border-indigo-600">
            <h1 class="text-4xl font-extrabold text-gray-900 mb-2">
                <?= htmlspecialchars($item['title']); ?>
            </h1>
            <p class="text-lg font-semibold text-<?= $is_found ? 'green' : 'red'; ?>-600 mb-6 border-b pb-3">
                Laporan Barang <?= $is_found ? 'DITEMUKAN' : 'HILANG'; ?>
            </p>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="lg:col-span-2 space-y-4">
                    <div class="relative bg-gray-100 rounded-xl overflow-hidden shadow-lg h-96">
                        <img id="item-photo" src="/assets/uploads/items/<?= $item['image_path'] ?? 'placeholder.jpg'; ?>" alt="Foto Barang" 
                             class="w-full h-full object-cover transition duration-300 <?= $blur_class; ?>">
                        
                        <span class="absolute top-4 left-4 px-4 py-2 text-white text-sm font-bold rounded-full <?= $status_color; ?> shadow-md uppercase">
                            STATUS: <?= $item['status']; ?>
                        </span>
                        
                        <?php if ($is_claimable && $is_found): ?>
                            <button id="claim-button" 
                                    data-safe-claim="<?= $item['is_safe_claim'] ? 'true' : 'false'; ?>"
                                    data-item-id="<?= $item['id']; ?>"
                                    class="absolute bottom-4 right-4 px-8 py-3 bg-indigo-600 text-white font-bold text-lg rounded-xl shadow-2xl hover:bg-indigo-700 transition transform hover:scale-105">
                                Saya Pemiliknya
                            </button>
                        <?php elseif ($item['status'] === 'closed'): ?>
                            <span class="absolute bottom-4 right-4 text-sm font-bold text-green-700 bg-green-100 p-2 rounded-lg">
                                Barang Sudah Kembali
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-indigo-50 p-6 rounded-lg border border-indigo-200">
                        <h2 class="text-xl font-bold text-indigo-700 mb-3">Detail Penting</h2>
                        <p class="text-sm border-b py-1"><span class="font-medium text-gray-700">Kategori:</span> <?= $categoryName; ?></p>
                        <p class="text-sm border-b py-1"><span class="font-medium text-gray-700">Lokasi Kejadian:</span> <?= $locationName; ?></p>
                        <p class="text-sm border-b py-1"><span class="font-medium text-gray-700">Dilaporkan Oleh:</span> <?= $reporterName; ?></p>
                        <p class="text-sm py-1"><span class="font-medium text-gray-700">Tgl. Kejadian:</span> <?= formatDate($item['incident_date']); ?></p>
                    </div>

                    <div>
                        <h2 class="text-xl font-bold text-gray-800 mb-2">Ciri-ciri Lengkap</h2>
                        <p class="text-gray-600 leading-relaxed text-sm"><?= nl2br(htmlspecialchars($item['description'])); ?></p>
                    </div>
                </div>
            </div>
        </div>


        <div class="mt-12 p-6 bg-white shadow-xl rounded-xl">
            <div class="lg:max-w-4xl">
                <h2 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-2">Diskusi & Klarifikasi</h2>

                <?php if (isLoggedIn()): ?>
                    <div class="mb-8 p-4 bg-gray-50 rounded-lg border border-gray-100">
                        <textarea placeholder="Tulis komentar atau pertanyaan (Anda harus login untuk berkomentar)..." rows="3" id="comment-input"
                                  class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        <button id="submit-comment" data-item-id="<?= $item['id']; ?>" class="mt-3 px-6 py-2 bg-indigo-500 text-white font-semibold rounded-md hover:bg-indigo-600 transition">
                            Kirim Komentar
                        </button>
                    </div>
                <?php else: ?>
                    <div class="mb-8 p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-yellow-800 font-medium">
                        Silakan <a href="index.php?page=auth&action=login" class="text-yellow-700 underline">login</a> untuk meninggalkan komentar.
                    </div>
                <?php endif; ?>

                <div id="comments-list" class="space-y-4">
                    <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm">
                        <p class="font-semibold text-sm text-gray-800">Nama User <span class="text-xs font-normal text-gray-500 ml-2"><?= timeAgo(date('Y-m-d H:i:s', strtotime('-1 hour'))); ?></span></p>
                        <p class="text-gray-700 text-sm mt-1">Apakah di dalam tas ada buku cetak kimia?</p>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<?php if ($is_claimable && $is_found): ?>
<div id="claim-modal" class="fixed inset-0 bg-gray-900 bg-opacity-80 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-8 m-4">
        <h3 class="text-2xl font-bold text-indigo-600 mb-6">Proses Verifikasi Klaim</h3>
        
        <?php if ($item['is_safe_claim']): ?>
            <div id="safe-claim-prompt">
                <p class="mb-4 text-gray-700 text-center">🔐 **Verifikasi Rahasia Diaktifkan.** Jawab pertanyaan ini:</p>
                <p class="font-bold text-xl text-center mb-3 text-gray-800" id="secret-question-text">
                    "<?= htmlspecialchars($item['security_question']); ?>" 
                </p>
                <input type="text" id="claim-answer" placeholder="Masukkan Jawaban Kunci Anda (Case-Insensitive)" 
                       class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                <button id="submit-claim-answer" data-item-id="<?= $item['id']; ?>" class="mt-5 w-full py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition">
                    Verifikasi & Lanjutkan
                </button>
            </div>
            
            <div id="verification-result" class="hidden text-center mt-4 text-sm font-bold"></div>

        <?php endif; ?>

        <div id="contact-info" class="<?= $item['is_safe_claim'] ? 'hidden' : 'text-center'; ?>">
            <p class="text-green-600 font-bold text-xl mb-3">Akses Kontak Diberikan!</p>
            <p class="text-gray-700 mb-4">Silakan hubungi <?= $reporterName; ?> untuk negosiasi pengambilan.</p>
            <a href="https://wa.me/<?= $reporterWA; ?>" target="_blank" id="wa-link"
               class="w-full inline-block py-3 bg-green-500 text-white font-bold rounded-lg hover:bg-green-600 transition">
                Hubungi via WhatsApp (<?= $reporterWA; ?>)
            </a>
        </div>

        <button id="close-modal" class="mt-6 text-sm text-gray-500 hover:text-gray-700 block mx-auto font-medium">Tutup Jendela</button>
    </div>
</div>
<?php endif; ?>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        const claimButton = document.getElementById('claim-button');
        const claimModal = document.getElementById('claim-modal');
        const closeModal = document.getElementById('close-modal');
        const itemPhoto = document.getElementById('item-photo');
        
        // Elemen Safe Claim
        const isSafeClaimActive = claimButton ? claimButton.dataset.safeClaim === 'true' : false;
        const submitClaimAnswer = document.getElementById('submit-claim-answer');
        const safeClaimPrompt = document.getElementById('safe-claim-prompt');
        const contactInfo = document.getElementById('contact-info');
        const verificationResult = document.getElementById('verification-result');

        // --- A. Modal Show/Hide ---
        if (claimButton) {
            claimButton.addEventListener('click', () => {
                claimModal.classList.remove('hidden');
                claimModal.classList.add('flex');
            });
        }
        
        closeModal.addEventListener('click', () => {
            claimModal.classList.add('hidden');
            claimModal.classList.remove('flex');
        });

        // --- B. Logika Verifikasi Safe Claim (Integrasi Backend) ---
        if (submitClaimAnswer) {
            submitClaimAnswer.addEventListener('click', async () => {
                const answer = document.getElementById('claim-answer').value;
                const itemId = submitClaimAnswer.dataset.itemId;
                
                verificationResult.classList.remove('hidden');
                verificationResult.innerHTML = '<p class="text-indigo-600 font-bold">Memverifikasi Jawaban...</p>';
                
                // Fetch API Call to Backend (You MUST update this when the API endpoint is ready)
                // const verificationUrl = '/api/claim/verify'; // Asumsi URL API
                
                // --- SIMULASI BACKEND RESPONSE (HAPUS INI) ---
                const verificationSuccess = (answer.toLowerCase().trim() === 'aqua'); 
                if (verificationSuccess) {
                    verificationResult.innerHTML = '<p class="text-green-600 font-bold">Verifikasi Berhasil! Menampilkan Kontak...</p>';
                    safeClaimPrompt.classList.add('hidden'); 
                    contactInfo.classList.remove('hidden'); 
                    itemPhoto.classList.remove('photo-blurred'); 
                } else {
                    verificationResult.innerHTML = '<p class="text-red-600 font-bold">Jawaban Salah. Akses ditolak.</p>';
                }
                // --- AKHIR SIMULASI ---
            });
        }
        
        // --- C. Logic Komentar (Simulasi Kirim) ---
        const submitComment = document.getElementById('submit-comment');
        const commentInput = document.getElementById('comment-input');
        
        if (submitComment) {
            submitComment.addEventListener('click', () => {
                const commentText = commentInput.value.trim();
                if (commentText.length > 0) {
                    console.log('Kirim Komentar:', commentText);
                    alert('Komentar terkirim! (Simulasi)');
                    commentInput.value = '';
                }
            });
        }
    });
</script>