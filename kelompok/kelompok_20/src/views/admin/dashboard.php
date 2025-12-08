<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Panel - myUnila Lost & Found</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
      rel="stylesheet"
    />

    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              unila: {
                dark: "#0f172a",
                primary: "#0ea5e9",
                secondary: "#3b82f6",
              },
            },
          },
        },
      };
    </script>
    <style>
      body {
        font-family: "Inter", sans-serif;
      }

      .active-nav {
        background-color: rgba(255, 255, 255, 0.1);
        border-left: 4px solid #0ea5e9;
        color: #38bdf8;
      }
    </style>
  </head>
  <body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
      <aside
        class="w-64 bg-slate-900 text-white flex-shrink-0 hidden md:flex flex-col shadow-xl"
      >
        <div class="p-6 flex items-center border-b border-gray-700">
          <img
            src="../../assets/images/iconlost&found.png"
            alt="Logo myUnila"
            class="w-10 h-10 rounded-full object-cover border-2 border-cyan-500 mr-3"
          />

          <div>
            <h1 class="text-lg font-bold tracking-wide">myUnila</h1>
            <p class="text-xs text-cyan-400 tracking-wider font-semibold">
              LOST & FOUND
            </p>
          </div>
        </div>

        <nav class="mt-6 flex-1 px-2 space-y-2">
          <a
            href="dashboard.php"
            id="nav-dashboard"
            class="active-nav group flex items-center px-4 py-3 text-sm font-medium rounded-r-md hover:bg-gray-800 hover:text-cyan-400 transition"
          >
            <i class="fa-solid fa-gauge mr-3 text-lg"></i>
            Dashboard
          </a>

          <a
            href="items.php"
            id="nav-items"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-r-md hover:bg-gray-800 hover:text-cyan-400 transition"
          >
            <i class="fa-solid fa-box-open mr-3 text-lg"></i>
            Data Barang
          </a>

          <a
            href="users.php"
            id="nav-users"
            class="group flex items-center px-4 py-3 text-sm font-medium rounded-r-md hover:bg-gray-800 hover:text-cyan-400 transition"
          >
            <i class="fa-solid fa-users mr-3 text-lg"></i>
            Data User
          </a>
        </nav>

        <div class="p-4 border-t border-gray-700 bg-slate-900">
          <a
            href="index.php?action=logout"
            class="flex items-center text-sm font-medium text-gray-400 hover:text-red-400 transition"
          >
            <i class="fa-solid fa-right-from-bracket mr-3"></i>
            Logout
          </a>
        </div>
      </aside>

      <div class="flex-1 flex flex-col overflow-hidden relative">
        <header class="bg-white shadow-sm z-10 border-b border-gray-200">
          <div class="flex items-center justify-between px-6 py-4">
            <button class="md:hidden text-gray-500 focus:outline-none">
              <i class="fa-solid fa-bars text-xl"></i>
            </button>
            <h2 class="text-xl font-bold text-slate-800" id="page-title">
              Dashboard Overview
            </h2>
            <div class="flex items-center space-x-4">
              <div class="relative cursor-pointer">
                <i
                  class="fa-regular fa-bell text-gray-500 text-xl hover:text-cyan-600 transition"
                ></i>
                <span
                  class="absolute top-0 right-0 block h-2 w-2 rounded-full ring-2 ring-white bg-red-500"
                ></span>
              </div>
              <div
                class="flex items-center gap-2 border-l pl-4 border-gray-200"
              >
                <div class="text-right hidden sm:block">
                  <p class="text-sm font-semibold text-slate-800">
                    Admin Unila
                  </p>
                  <p class="text-xs text-gray-500">Super Administrator</p>
                </div>
                <img
                  class="h-9 w-9 rounded-full object-cover border border-gray-300"
                  src="https://ui-avatars.com/api/?name=Admin+Unila&background=0f172a&color=0ea5e9"
                  alt="Admin Profile"
                />
              </div>
            </div>
          </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-6">
          <div id="section-dashboard" class="space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-cyan-500 flex items-center hover:shadow-md transition">
                <div class="p-3 rounded-full bg-cyan-50 text-cyan-600 mr-4">
                  <i class="fa-solid fa-box text-xl"></i>
                </div>
                <div>
                  <p class="text-gray-500 text-xs font-semibold uppercase">Total Postingan</p>
                  <p class="text-2xl font-bold text-slate-800"><?= $stats['total_items'] ?? 0 ?></p>
                </div>
              </div>

              <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-green-500 flex items-center hover:shadow-md transition">
                <div class="p-3 rounded-full bg-green-50 text-green-600 mr-4">
                  <i class="fa-solid fa-check-circle text-xl"></i>
                </div>
                <div>
                  <p class="text-gray-500 text-xs font-semibold uppercase">Barang Ditemukan</p>
                  <p class="text-2xl font-bold text-slate-800"><?= $stats['total_found'] ?? 0 ?></p>
                </div>
              </div>

              <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-purple-500 flex items-center hover:shadow-md transition">
                <div class="p-3 rounded-full bg-purple-50 text-purple-600 mr-4">
                  <i class="fa-solid fa-users text-xl"></i>
                </div>
                <div>
                  <p class="text-gray-500 text-xs font-semibold uppercase">Total User</p>
                  <p class="text-2xl font-bold text-slate-800"><?= $stats['total_users'] ?? 0 ?></p>
                </div>
              </div>

              <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-yellow-500 flex items-center hover:shadow-md transition">
                <div class="p-3 rounded-full bg-yellow-50 text-yellow-600 mr-4">
                  <i class="fa-solid fa-clock text-xl"></i>
                </div>
                <div>
                  <p class="text-gray-500 text-xs font-semibold uppercase">Menunggu Approval</p>
                  <p class="text-2xl font-bold text-slate-800">
                    <?= isset($pendingItems) ? count($pendingItems) : 0 ?>
                  </p>
                </div>
              </div>
            </div>

            <div
              class="rounded-xl shadow-lg p-8 text-white relative overflow-hidden"
            >
              <div
                class="absolute inset-0 bg-gradient-to-r from-cyan-600 to-blue-700"
              ></div>
              <div class="relative z-10 flex justify-between items-center">
                <div>
                  <h3 class="text-3xl font-bold mb-2">
                    Selamat Datang, Admin!
                  </h3>
                  <p class="text-cyan-100 max-w-xl">
                    Kelola data kehilangan dan penemuan barang di lingkungan
                    Universitas Lampung dengan mudah melalui panel ini.
                  </p>
                </div>
                <i
                  class="fa-solid fa-shield-halved text-8xl text-white opacity-10 hidden md:block absolute right-10 -bottom-6"
                ></i>
              </div>
            </div>

            <div class="mt-8">
                <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center">
                    <i class="fa-solid fa-clipboard-check mr-2 text-cyan-600"></i>
                    Moderasi Postingan (Menunggu Persetujuan)
                </h3>

                <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Info Barang</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Pelapor</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Kategori & Lokasi</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($pendingItems)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                            <div class="flex flex-col items-center justify-center">
                                                <i class="fa-regular fa-circle-check text-4xl text-green-500 mb-2"></i>
                                                <p>Tidak ada postingan pending. Semua aman!</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pendingItems as $item): ?>
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="h-12 w-12 bg-slate-100 rounded-lg flex-shrink-0 overflow-hidden border border-gray-200">
                                                    <?php if (!empty($item['image_path'])): ?>
                                                        <img src="<?= htmlspecialchars($item['image_path']) ?>" class="object-cover h-full w-full">
                                                    <?php else: ?>
                                                        <div class="flex items-center justify-center h-full text-slate-400">
                                                            <i class="fa-solid fa-image text-xl"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-bold text-slate-900"><?= htmlspecialchars($item['title']) ?></div>
                                                    <div class="text-xs text-gray-500 mt-1 line-clamp-1 max-w-[200px]"><?= htmlspecialchars($item['description']) ?></div>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium mt-1 <?= $item['type'] === 'lost' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' ?>">
                                                        <?= $item['type'] === 'lost' ? 'Kehilangan' : 'Ditemukan' ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-slate-900"><?= htmlspecialchars($item['user_name'] ?? 'Unknown') ?></div>
                                            <div class="text-xs text-gray-500"><?= date('d M Y, H:i', strtotime($item['created_at'])) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-slate-700"><?= htmlspecialchars($item['category_name']) ?></div>
                                            <div class="text-xs text-gray-500 flex items-center mt-1">
                                                <i class="fa-solid fa-location-dot mr-1 text-xs"></i>
                                                <?= htmlspecialchars($item['location_name']) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex justify-end gap-2">
                                                <a href="index.php?page=admin&action=approveItem&id=<?= $item['id'] ?>" 
                                                   onclick="return confirm('Apakah Anda yakin ingin menyetujui postingan ini agar tampil ke publik?')"
                                                   class="bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded-md text-xs font-semibold shadow-sm transition flex items-center">
                                                    <i class="fa-solid fa-check mr-1.5"></i> Terima
                                                </a>
                                                <a href="index.php?page=admin&action=rejectItem&id=<?= $item['id'] ?>" 
                                                   onclick="return confirm('Tolak postingan ini? Item akan ditandai sebagai rejected.')"
                                                   class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-md text-xs font-semibold shadow-sm transition flex items-center">
                                                    <i class="fa-solid fa-xmark mr-1.5"></i> Tolak
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

          </div>
        </main>
      </div>
    </div>

    <script src="../../assets/js/main.js"></script>
  </body>
</html>