// ============================================
// UNILA LOST & FOUND - REAL-TIME MAP SYSTEM
// ============================================

console.log('🗺️ UNILA Lost & Found Map System initialized');

// Global variables
window.mapInstance = null;
window.hotspotsLayer = null;
window.itemsLayer = null;
window.currentData = null;
window.isMapInitialized = false;
let tileLayerInstance = null;

// Configuration
const CONFIG = {
    apiEndpoint: 'index.php?page=home&action=mapData',
    center: [-5.3630, 105.2440],
    zoom: 16,
    updateInterval: 30000,
    mapHeight: {
        desktop: '550px',
        mobile: '400px'
    }
};

// Icon definitions
const ICONS = {
    lost: {
        html: (category) => `
            <div class="relative">
                <div class="w-10 h-10 bg-red-500 rounded-full flex items-center justify-center shadow-lg border-2 border-white">
                    <span class="text-white text-lg">${getCategoryIcon(category)}</span>
                </div>
                <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-red-700 rounded-full"></div>
            </div>
        `,
        color: '#ef4444'
    },
    found: {
        html: (category) => `
            <div class="relative">
                <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center shadow-lg border-2 border-white">
                    <span class="text-white text-lg">${getCategoryIcon(category)}</span>
                </div>
                <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-700 rounded-full"></div>
            </div>
        `,
        color: '#10b981'
    }
};

// Category icons mapping
function getCategoryIcon(category) {
    const icons = {
        'Elektronik': '💻',
        'Dokumen': '📄',
        'Pakaian': '👕',
        'Aksesoris': '💎',
        'Kunci': '🔑',
        'Tas & Dompet': '🎒',
        'Buku & Alat Tulis': '📚',
        'Kendaraan': '🚗',
        'Lainnya': '📦'
    };
    return icons[category] || '📦';
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initializing map system...');
    
    const mapContainer = document.getElementById('unila-hotspot-map');
    if (!mapContainer) {
        showMapError('Elemen peta tidak ditemukan. Pastikan ada elemen dengan id="unila-hotspot-map"');
        return;
    }
    
    adjustMapHeight();
    
    if (typeof L === 'undefined') {
        showMapError('Leaflet library gagal dimuat. Periksa koneksi internet atau script Leaflet.');
        return;
    }
    
    initLeafletMap();
    loadMapData();
    setInterval(loadMapData, CONFIG.updateInterval);
    setupEventListeners();
    
    console.log('✅ Map system initialized');
});

// ==================== CORE FUNCTIONS ====================

function initLeafletMap() {
    try {
        console.log('🌍 Creating Leaflet map...');
        
        if (window.mapInstance) return;

        window.mapInstance = L.map('unila-hotspot-map', {
            center: CONFIG.center,
            zoom: CONFIG.zoom,
            zoomControl: true,
            preferCanvas: true
        });
        
        console.log('✅ Map instance created');
        
        tileLayerInstance = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19
        }).addTo(window.mapInstance);
        
        console.log('✅ Tile layer added');
        
        window.hotspotsLayer = L.layerGroup().addTo(window.mapInstance);
        window.itemsLayer = L.layerGroup().addTo(window.mapInstance);
        
        addUnilaMarker();
        
        window.isMapInitialized = true;
        console.log('✅ Leaflet map fully initialized');
        
        window.mapInstance.invalidateSize(true);
        
    } catch (error) {
        console.error('❌ Error initializing map:', error);
        showMapError('Gagal menginisialisasi peta: ' + error.message);
    }
}

async function loadMapData() {
    try {
        console.log('📡 Loading map data from API...');
        
        updateTimestamp('Memuat data...');
        
        const response = await fetch(CONFIG.apiEndpoint + '&_=' + Date.now());
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        console.log('📊 API response:', data);
        
        if (data.success) {
            window.currentData = data;
            updateMap(data);
            updateUI(data);
            console.log(`✅ Data loaded: ${data.items?.length || 0} items, ${data.hotspots?.length || 0} hotspots`);
        } else {
            console.error('❌ API returned success: false. Data tidak valid.');
            showDataError('Data dari server tidak valid. ' + (data.error || ''));
        }
        
    } catch (error) {
        console.error('❌ Error loading map data:', error);
        showDataError('Gagal memuat data terbaru. Periksa koneksi API/server. ' + error.message);
    }
}

function updateMap(data) {
    if (!window.mapInstance) {
        console.error('Cannot update map: map instance not available');
        return;
    }
    
    console.log('🔄 Updating map with new data...');
    
    if (window.hotspotsLayer) {
        window.hotspotsLayer.clearLayers();
        console.log('✅ Cleared hotspots layer');
    }
    
    if (window.itemsLayer) {
        window.itemsLayer.clearLayers();
        console.log('✅ Cleared items layer');
    }
    
    if (data.hotspots && Array.isArray(data.hotspots) && data.hotspots.length > 0) {
        console.log(`📍 Adding ${data.hotspots.length} hotspots`);
        data.hotspots.forEach(hotspot => addHotspotArea(hotspot));
    } else {
        console.log('⚠️ No hotspots data available');
    }
    
    if (data.items && Array.isArray(data.items) && data.items.length > 0) {
        console.log(`📍 Adding ${data.items.length} item markers`);
        data.items.forEach(item => addItemMarker(item));
        
        if (data.items.length < 50) {
            setTimeout(() => fitMapToMarkers(data.items), 500);
        }
    } else {
        console.log('⚠️ No items data available');
    }
    
    addUnilaMarker();
    
    console.log('✅ Map update complete');
}

function addUnilaMarker() {
    if (!window.mapInstance) return;
    
    const existingMarkers = document.querySelectorAll('.unila-marker');
    if (existingMarkers.length > 0) return;
    
    console.log('🏛️ Adding UNILA marker');
    
    const unilaIcon = L.divIcon({
        html: `
            <div class="unila-marker">
                <div class="w-12 h-12 bg-white dark:bg-slate-800 rounded-full flex items-center justify-center shadow-xl border-3 border-primary-500">
                    <span class="text-xl">🏛️</span>
                </div>
                <div class="absolute -bottom-8 left-1/2 transform -translate-x-1/2 whitespace-nowrap 
                            text-xs font-bold px-3 py-1 rounded-full bg-slate-900 text-white">
                    UNILA
                </div>
            </div>
        `,
        className: 'unila-marker',
        iconSize: [48, 60],
        iconAnchor: [24, 60]
    });
    
    L.marker(CONFIG.center, { icon: unilaIcon })
        .addTo(window.mapInstance)
        .bindPopup(`
            <div class="p-3">
                <h3 class="font-bold text-lg text-slate-900 dark:text-white mb-1">🏛️ Universitas Lampung</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">Kampus utama</p>
                <p class="text-xs text-slate-500 dark:text-slate-500">Jl. Prof. Dr. Sumantri Brojonegoro No.1</p>
            </div>
        `);
}

function addHotspotArea(hotspot) {
    if (!window.mapInstance || !hotspot.lat || !hotspot.lng) {
        console.warn('Skipping hotspot: invalid data', hotspot);
        return;
    }
    
    const radius = Math.min((hotspot.lost_count || 0) * 15, 120);
    
    console.log(`🔴 Adding hotspot: ${hotspot.name} (${hotspot.report_count} reports, radius: ${radius}m)`);
    
    const circle = L.circle([hotspot.lat, hotspot.lng], {
        color: hotspot.color || '#ef4444',
        fillColor: hotspot.color || '#ef4444',
        fillOpacity: 0.2,
        radius: radius,
        weight: 2
    }).addTo(window.hotspotsLayer);
    
    const popupContent = createHotspotPopup(hotspot);
    
    circle.bindPopup(popupContent, {
        maxWidth: 350,
        className: 'hotspot-popup'
    });
    
    circle.on('mouseover', function() {
        this.setStyle({ fillOpacity: 0.3, weight: 3 });
        this.openPopup();
    });
    
    circle.on('mouseout', function() {
        this.setStyle({ fillOpacity: 0.2, weight: 2 });
    });
    
    circle.on('click', function() {
        const url = `index.php?page=items&action=search&type=lost&location=${encodeURIComponent(hotspot.name)}`;
        window.location.href = url;
    });
}

function createHotspotPopup(hotspot) {
    let lostItems = (hotspot.items || []).filter(item => item.type === 'lost');
    let itemsList = '';
    
    if (lostItems.length > 0) {
        itemsList = lostItems.slice(0, 5).map(item => `
            <div class="flex items-center justify-between py-2 px-3 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors cursor-pointer"
                 onclick="window.location.href='index.php?page=items&action=show&id=${item.id}';">
                <div class="flex items-center gap-2 flex-1 min-w-0">
                    <span class="text-sm text-red-500">
                        ❓
                    </span>
                    <span class="text-sm font-medium truncate text-slate-800 dark:text-slate-200" title="${item.title}">${item.title}</span>
                </div>
                <span class="text-xs text-red-500 font-bold ml-2 whitespace-nowrap">${item.time_ago || 'Baru'}</span> 
            </div>
        `).join('');
    } else {
        itemsList = '<p class="text-sm text-slate-500 dark:text-slate-400 text-center py-3">Belum ada laporan kehilangan terbaru di sini.</p>';
    }

    const locationTypeIcon = getLocationTypeIcon(hotspot.type);
    const lostCount = hotspot.lost_count || 0;
    const foundCount = hotspot.found_count || 0;

    return `
        <div class="p-4 min-w-[280px] max-w-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="min-w-0 flex-1">
                    <h4 class="font-bold text-lg text-slate-900 dark:text-white truncate" title="${hotspot.name}">${hotspot.name}</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1 mt-1">
                        ${locationTypeIcon}
                        ${getLocationTypeName(hotspot.type)}
                    </p>
                </div>
                <span class="px-3 py-1 rounded-full text-sm font-bold whitespace-nowrap ml-2" 
                      style="background-color: ${hotspot.color}20; color: ${hotspot.color};">
                    ${lostCount} Kehilangan
                </span>
            </div>
            
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div class="bg-red-50 dark:bg-red-900/20 p-3 rounded-lg">
                    <p class="text-xs text-red-600 dark:text-red-400 mb-1">Total Hilang</p>
                    <p class="font-bold text-xl text-red-700 dark:text-red-300">${lostCount}</p>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 p-3 rounded-lg">
                    <p class="text-xs text-green-600 dark:text-green-400 mb-1">Total Ditemukan</p>
                    <p class="font-bold text-xl text-green-700 dark:text-green-300">${foundCount}</p>
                </div>
            </div>
            
            <div class="mb-4">
                <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Laporan Kehilangan Terbaru:</p>
                <div class="max-h-40 overflow-y-auto pr-2">
                    ${itemsList}
                </div>
            </div>
            
            <div class="flex gap-2">
                <a href="index.php?page=items&action=search&type=lost&location=${encodeURIComponent(hotspot.name)}"
                    class="flex-1 text-center px-3 py-2 bg-red-500 hover:bg-red-600 
                            text-white rounded-lg text-sm font-medium transition-colors"
                    onclick="event.stopPropagation();">
                    Lihat Semua Hilang
                </a>
                <a href="index.php?page=items&action=create&location=${encodeURIComponent(hotspot.name)}"
                    class="flex-1 text-center px-3 py-2 bg-slate-200 dark:bg-slate-700 
                            hover:bg-slate-300 dark:hover:bg-slate-600 
                            text-slate-800 dark:text-slate-200 rounded-lg text-sm font-medium transition-colors"
                    onclick="event.stopPropagation();">
                    Lapor di Sini
                </a>
            </div>
        </div>
    `;
}

function addItemMarker(item) {
    if (!window.mapInstance || !item.lat || !item.lng) {
        console.warn('Skipping item marker: invalid data', item);
        return;
    }
    
    const iconType = item.type === 'lost' ? ICONS.lost : ICONS.found;
    
    console.log(`📍 Adding item marker: ${item.title} (${item.type})`);
    
    const customIcon = L.divIcon({
        html: iconType.html(item.category),
        className: 'item-marker',
        iconSize: [40, 40],
        iconAnchor: [20, 40]
    });
    
    const marker = L.marker([item.lat, item.lng], {
        icon: customIcon,
        title: item.title,
        riseOnHover: true
    }).addTo(window.itemsLayer);
    
    const popupContent = createItemPopup(item);
    
    marker.bindPopup(popupContent, {
        maxWidth: 350,
        className: 'item-popup'
    });
    
    marker.on('mouseover', function() {
        this.openPopup();
    });
    
    marker.on('click', function() {
        window.mapInstance.setView([item.lat, item.lng], 18);
    });
}

function createItemPopup(item) {
    const typeBadge = item.type === 'lost' ? 
        '<span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-bold rounded-full">HILANG</span>' :
        '<span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-bold rounded-full">DITEMUKAN</span>';
    
    const statusBadge = item.status === 'open' ?
        '<span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">Aktif</span>' :
        item.status === 'process' ?
        '<span class="px-2 py-1 bg-amber-100 text-amber-800 text-xs rounded-full">Diproses</span>' :
        '<span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs rounded-full">Selesai</span>';
    
    const imageHtml = item.image_url ? `
        <div class="w-full h-40 mb-3 rounded-lg overflow-hidden bg-slate-100 dark:bg-slate-800">
            <img src="${item.image_url}" 
                    alt="${item.title}" 
                    class="w-full h-full object-cover hover:scale-105 transition-transform duration-300"
                    onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=\"w-full h-full flex items-center justify-center\"><span class=\"text-4xl text-slate-400\">${getCategoryIcon(item.category)}</span></div>';">
        </div>
    ` : `
        <div class="w-full h-32 mb-3 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
            <span class="text-4xl text-slate-400">${getCategoryIcon(item.category)}</span>
        </div>
    `;
    
    return `
        <div class="p-0 min-w-[300px] max-w-sm">
            ${imageHtml}
            <div class="p-4">
                <div class="flex items-start justify-between mb-3">
                    <div class="min-w-0 flex-1">
                        <h4 class="font-bold text-lg text-slate-900 dark:text-white truncate" title="${item.title}">${item.title}</h4>
                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                            ${typeBadge}
                            ${statusBadge}
                        </div>
                    </div>
                </div>
                
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-3 line-clamp-2" title="${item.description || 'Tidak ada deskripsi'}">
                    ${item.description || 'Tidak ada deskripsi'}
                </p>
                
                <div class="space-y-2 mb-4">
                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-slate-500 dark:text-slate-400">📍</span>
                        <span class="text-slate-700 dark:text-slate-300 truncate">${item.location}</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-slate-500 dark:text-slate-400">📅</span>
                        <span class="text-slate-700 dark:text-slate-300">${item.time_ago}</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-slate-500 dark:text-slate-400">🏷️</span>
                        <span class="text-slate-700 dark:text-slate-300">${item.category}</span>
                    </div>
                    ${item.reporter_name ? `
                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-slate-500 dark:text-slate-400">👤</span>
                        <span class="text-slate-700 dark:text-slate-300">${item.reporter_name}</span>
                    </div>
                    ` : ''}
                </div>
                
                <div class="flex gap-2">
                    <a href="index.php?page=items&action=show&id=${item.id}"
                        class="flex-1 text-center px-3 py-2 bg-primary-500 hover:bg-primary-600 
                                text-white rounded-lg text-sm font-medium transition-colors"
                        onclick="event.stopPropagation();">
                        Lihat Detail
                    </a>
                    ${item.type === 'found' ? `
                    <a href="index.php?page=claims&action=create&item_id=${item.id}"
                        class="flex-1 text-center px-3 py-2 bg-emerald-500 hover:bg-emerald-600 
                                text-white rounded-lg text-sm font-medium transition-colors"
                        onclick="event.stopPropagation();">
                        Klaim Barang
                    </a>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
}

function fitMapToMarkers(items) {
    if (!window.mapInstance || !items || items.length === 0) return;
    
    try {
        console.log('🔍 Fitting map to markers...');
        
        const bounds = L.latLngBounds(items.map(item => [item.lat, item.lng]));
        bounds.extend(CONFIG.center);
        
        window.mapInstance.fitBounds(bounds, {
            padding: [50, 50],
            maxZoom: 17,
            animate: true
        });
        
        console.log('✅ Map fitted to markers');
        
    } catch (error) {
        console.warn('Could not fit bounds:', error);
    }
}

function updateUI(data) {
    updateTimestamp(data.timestamp || new Date().toLocaleTimeString());
    updateStats(data.stats);
    updateCategories(data.categories);
    updateTopLocations(data.hotspots);
}

function updateTimestamp(time) {
    const timestampEl = document.getElementById('map-timestamp');
    if (timestampEl) {
        timestampEl.textContent = time;
        timestampEl.classList.remove('text-red-500');
        timestampEl.classList.add('text-primary-400');
    }
}

function updateStats(stats) {
    const statsContainer = document.getElementById('map-stats');
    if (!statsContainer || !stats) return;
    
    statsContainer.innerHTML = `
        <div class="grid grid-cols-2 gap-3">
            <div class="bg-slate-800/50 p-3 rounded-lg">
                <p class="text-xs text-slate-400">Total Laporan</p>
                <p class="text-xl font-bold text-white">${stats.total_items || stats.total_active_items || 0}</p>
            </div>
            <div class="bg-red-500/20 p-3 rounded-lg">
                <p class="text-xs text-red-400">Barang Hilang</p>
                <p class="text-xl font-bold text-white">${stats.total_lost || 0}</p>
            </div>
            <div class="bg-green-500/20 p-3 rounded-lg">
                <p class="text-xs text-green-400">Barang Ditemukan</p>
                <p class="text-xl font-bold text-white">${stats.total_found || 0}</p>
            </div>
            <div class="bg-blue-500/20 p-3 rounded-lg">
                <p class="text-xs text-blue-400">Hotspot Area</p>
                <p class="text-xl font-bold text-white">${stats.total_hotspots || 0}</p>
            </div>
        </div>
    `;
}

function updateCategories(categories) {
    const categoriesContainer = document.getElementById('map-categories');
    if (!categoriesContainer || !categories) return;
    
    if (!Array.isArray(categories) || categories.length === 0) {
        categoriesContainer.innerHTML = `
            <div class="text-center py-4">
                <div class="text-slate-400 text-lg mb-2">📦</div>
                <p class="text-gray-400 text-sm">Belum ada data kategori</p>
            </div>
        `;
        return;
    }
    
    categoriesContainer.innerHTML = categories.map(cat => `
        <a href="index.php?page=items&action=search&category_id=${cat.id}"
            class="flex items-center justify-between p-3 bg-slate-800/50 hover:bg-slate-700/50 rounded-lg transition-colors group"
            onclick="filterByCategory(${cat.id})">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-primary-500/20 flex items-center justify-center">
                    <span class="text-primary-400">${getCategoryIcon(cat.name)}</span>
                </div>
                <span class="font-medium text-sm text-white group-hover:text-primary-400 transition truncate">
                    ${cat.name}
                </span>
            </div>
            <span class="text-xs px-2 py-1 bg-slate-700 text-slate-300 rounded-full whitespace-nowrap">
                ${cat.item_count || 0}
            </span>
        </a>
    `).join('');
}

function updateTopLocations(hotspots) {
    const locationsContainer = document.getElementById('top-locations');
    if (!locationsContainer || !hotspots) return;
    
    const topHotspots = Array.isArray(hotspots) ? hotspots.slice(0, 5) : [];
    
    if (topHotspots.length === 0) {
        locationsContainer.innerHTML = `
            <div class="text-center py-4">
                <div class="text-slate-400 text-lg mb-2">📍</div>
                <p class="text-gray-400 text-sm">Belum ada hotspot aktif</p>
            </div>
        `;
        return;
    }
    
    locationsContainer.innerHTML = topHotspots.map(hotspot => `
        <a href="index.php?page=items&action=search&location=${encodeURIComponent(hotspot.name)}"
            class="flex items-center justify-between p-3 bg-slate-900 rounded-lg hover:bg-slate-700 transition-colors group">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                     style="background-color: ${hotspot.color}20">
                    <div class="w-3 h-3 rounded-full" style="background-color: ${hotspot.color}"></div>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-medium text-sm text-white truncate" title="${hotspot.name}">${hotspot.name}</p>
                    <p class="text-xs text-slate-400">${hotspot.report_count || 0} laporan</p>
                </div>
            </div>
            <span class="text-xs text-slate-400 group-hover:text-primary-400 transition">→</span>
        </a>
    `).join('');
}

function getLocationTypeIcon(type) {
    const icons = {
        'building': '🏛️',
        'library': '📚',
        'canteen': '🍽️',
        'parking': '🅿️',
        'worship': '🕌',
        'sport': '⚽',
        'office': '🏢',
        'dormitory': '🏠',
        'other': '📍'
    };
    return icons[type] || '📍';
}

function getLocationTypeName(type) {
    const names = {
        'building': 'Gedung',
        'library': 'Perpustakaan',
        'canteen': 'Kantin',
        'parking': 'Parkiran',
        'worship': 'Tempat Ibadah',
        'sport': 'Area Olahraga',
        'office': 'Kantor',
        'dormitory': 'Asrama',
        'other': 'Lokasi Lain'
    };
    return names[type] || 'Lokasi';
}

function adjustMapHeight() {
    const mapElement = document.getElementById('unila-hotspot-map');
    if (!mapElement) return;
    
    const isMobile = window.innerWidth < 768;
    mapElement.style.height = isMobile ? CONFIG.mapHeight.mobile : CONFIG.mapHeight.desktop;
    
    console.log(`📏 Adjusted map height: ${isMobile ? 'mobile' : 'desktop'} (${mapElement.style.height})`);
    
    if (window.mapInstance) {
        setTimeout(() => {
            window.mapInstance.invalidateSize(true);
            console.log('🔄 Map size invalidated after resize');
        }, 100);
    }
}

function setupEventListeners() {
    window.addEventListener('resize', () => {
        adjustMapHeight();
    });
    
    document.querySelectorAll('[data-filter-type]').forEach(btn => {
        btn.addEventListener('click', function() {
            const type = this.dataset.filterType;
            filterByType(type, this);
        });
    });
    
    const refreshBtn = document.getElementById('refresh-map');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => {
            refreshBtn.innerHTML = `
                <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Memuat...
            `;
            loadMapData();
            setTimeout(() => {
                refreshBtn.innerHTML = `
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Refresh
                `;
            }, 1000);
        });
    }
    
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden && window.mapInstance) {
            console.log('🔍 Tab became visible, refreshing map');
            setTimeout(() => {
                window.mapInstance.invalidateSize(true);
                loadMapData();
            }, 300);
        }
    });
}

function filterByType(type, clickedButton = null) {
    if (!window.currentData) return;
    
    console.log(`🎚️ Filtering by type: ${type}`);
    
    if (clickedButton) {
        document.querySelectorAll('[data-filter-type]').forEach(btn => {
            btn.classList.remove('bg-primary-500', 'text-white');
            btn.classList.add('bg-white', 'dark:bg-slate-800', 'text-slate-700', 'dark:text-slate-300');
        });
        
        clickedButton.classList.remove('bg-white', 'dark:bg-slate-800', 'text-slate-700', 'dark:text-slate-300');
        clickedButton.classList.add('bg-primary-500', 'text-white');
    }
    
    if (type === 'all') {
        updateMap(window.currentData);
        console.log('✅ Showing all items');
    } else {
        const filteredData = {
            ...window.currentData,
            items: window.currentData.items.filter(item => item.type === type)
        };
        updateMap(filteredData);
        console.log(`✅ Showing ${filteredData.items.length} ${type} items`);
    }
}

function filterByCategory(categoryId) {
    if (!window.currentData) return;
    
    console.log(`🎚️ Filtering by category ID: ${categoryId}`);
    
    const filteredData = {
        ...window.currentData,
        items: window.currentData.items.filter(item => {
            return true;
        })
    };
    
    updateMap(filteredData);
    showNotification(`Memfilter berdasarkan kategori...`);
}

function showMapError(message) {
    const mapContainer = document.getElementById('unila-hotspot-map');
    if (!mapContainer) return;
    
    mapContainer.innerHTML = `
        <div class="h-full flex flex-col items-center justify-center bg-slate-800 text-white p-6 text-center rounded-2xl">
            <div class="text-6xl mb-4">🗺️</div>
            <h3 class="text-xl font-bold mb-2">Peta Tidak Dapat Dimuat</h3>
            <p class="text-slate-400 mb-4">${message}</p>
            <button onclick="window.location.reload()" 
                    class="px-6 py-2 bg-primary-500 hover:bg-primary-600 rounded-lg font-medium transition-colors mb-3">
                Muat Ulang Halaman
            </button>
        </div>
    `;
}

function showDataError(message) {
    const errorContainer = document.getElementById('map-error');
    if (!errorContainer) return;
    
    errorContainer.innerHTML = `
        <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-4 mb-4 animate-pulse">
            <div class="flex items-center gap-3">
                <span class="text-red-500 text-xl">⚠️</span>
                <div class="flex-1">
                    <p class="font-medium text-red-400">${message}</p>
                    <p class="text-sm text-red-500/80 mt-1">Sistem akan mencoba memuat ulang secara otomatis</p>
                </div>
                <button onclick="loadMapData()" class="text-red-500 hover:text-red-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>
            </div>
        </div>
    `;
    
    setTimeout(() => {
        if (errorContainer.innerHTML.includes('⚠️')) {
            errorContainer.innerHTML = '';
        }
    }, 10000);
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed bottom-4 right-4 z-[9999] px-4 py-3 rounded-lg shadow-lg max-w-sm ${
        type === 'error' ? 'bg-red-500 text-white' :
        type === 'warning' ? 'bg-amber-500 text-white' :
        type === 'success' ? 'bg-green-500 text-white' :
        'bg-primary-500 text-white'
    } animate-fade-in`;
    
    notification.innerHTML = `
        <div class="flex items-center gap-2">
            ${type === 'error' ? '❌' : type === 'warning' ? '⚠️' : type === 'success' ? '✅' : 'ℹ️'}
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateY(10px)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 5000);
}

// ==================== GLOBAL FUNCTIONS ====================

window.forceMapInvalidate = function() {
    if (window.mapInstance) {
        console.log('🔄 forceMapInvalidate() called');
        setTimeout(() => {
            window.mapInstance.invalidateSize(true);
            console.log('✅ Map size invalidated via forceMapInvalidate');
        }, 100);
    } else {
        console.warn('⚠️ forceMapInvalidate() called but mapInstance not available');
    }
};

window.zoomToLocation = function(lat, lng, zoom = 17) {
    if (window.mapInstance) {
        window.mapInstance.setView([lat, lng], zoom);
        showNotification(`Memperbesar ke lokasi`);
    }
};

window.refreshMapData = function() {
    loadMapData();
    showNotification('Memperbarui data peta...');
};

window.clearMapFilters = function() {
    if (window.currentData) {
        updateMap(window.currentData);
        document.querySelectorAll('[data-filter-type]').forEach(btn => {
            btn.classList.remove('bg-primary-500', 'text-white');
            btn.classList.add('bg-white', 'dark:bg-slate-800', 'text-slate-700', 'dark:text-slate-300');
        });
        const allBtn = document.querySelector('[data-filter-type="all"]');
        if (allBtn) {
            allBtn.classList.remove('bg-white', 'dark:bg-slate-800', 'text-slate-700', 'dark:text-slate-300');
            allBtn.classList.add('bg-primary-500', 'text-white');
        }
        showNotification('Filter direset');
    }
};

// ==================== INITIALIZATION ====================

console.log('📦 Map.js loaded successfully - Ready for UNILA Lost & Found!');