<div id="section-items" class=" space-y-6">
            <div
              class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-100"
            >
              <div
                class="px-6 py-4 border-b border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-gray-50/50"
              >
                <h3 class="text-lg font-bold text-slate-800">
                  Daftar Postingan Barang
                </h3>
                <div
                  class="flex items-center bg-white border border-gray-200 rounded-lg px-3 py-2 w-full md:w-auto focus-within:ring-2 focus-within:ring-cyan-500"
                >
                  <i class="fa-solid fa-search text-gray-400 mr-2"></i>
                  <input
                    type="text"
                    placeholder="Cari barang..."
                    class="bg-transparent border-none focus:outline-none text-sm w-64 text-gray-700"
                  />
                </div>
              </div>

              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-slate-50">
                    <tr>
                      <th
                        class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"
                      >
                        Barang
                      </th>
                      <th
                        class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"
                      >
                        Pelapor
                      </th>
                      <th
                        class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"
                      >
                        Lokasi
                      </th>
                      <th
                        class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"
                      >
                        Status
                      </th>
                      <th
                        class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider"
                      >
                        Aksi
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr class="hover:bg-slate-50 transition">
                      <td class="px-6 py-4">
                        <div class="flex items-center">
                          <div
                            class="h-10 w-10 bg-slate-100 rounded-lg flex items-center justify-center text-slate-500"
                          >
                            <i class="fa-solid fa-laptop"></i>
                          </div>
                          <div class="ml-4">
                            <div class="text-sm font-semibold text-slate-900">
                              Laptop ASUS ROG
                            </div>
                            <div class="text-xs text-gray-500">
                              Elektronik • 2 jam lalu
                            </div>
                          </div>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-slate-900 font-medium">
                          Budi Santoso
                        </div>
                        <div class="text-xs text-gray-500">2115061001</div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span
                          class="px-2.5 py-1 text-xs font-medium rounded-md bg-slate-100 text-slate-700 border border-slate-200"
                        >
                          Gedung H Teknik
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span
                          class="px-2.5 py-1 text-xs font-bold rounded-full bg-red-100 text-red-600"
                        >
                          Hilang
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-right">
                        <button
                          data-admin-action="delete_item"
                          data-id="1"
                          data-confirm="Hapus postingan ini?"
                          class="text-red-500 hover:text-white hover:bg-red-500 border border-red-200 px-3 py-1.5 rounded-md transition text-xs font-medium shadow-sm"
                        >
                          <i class="fa-solid fa-trash-can mr-1"></i> Hapus
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

