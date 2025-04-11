@extends('layouts.app')

@section('title', 'SoneAI - Kod Öğrenme Sistemi')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
    .dashboard-card {
        background-color: #fff;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: all 0.3s;
    }
    
    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
    }
    
    .status-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
    }
    
    .status-active {
        background-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
    }
    
    .status-inactive {
        background-color: #ef4444;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
    }
    
    .status-learning {
        background-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        animation: pulse 2s infinite;
    }
    
    .learning-progress {
        height: 8px;
        border-radius: 4px;
        background-color: #e5e7eb;
        overflow: hidden;
    }
    
    .learning-progress-bar {
        height: 100%;
        background-color: #3b82f6;
        border-radius: 4px;
        transition: width 0.5s ease;
    }
    
    .code-snippet {
        background-color: #1e1e1e;
        color: #d4d4d4;
        border-radius: 0.5rem;
        padding: 1rem;
        font-family: 'Consolas', 'Monaco', monospace;
        overflow-x: auto;
        line-height: 1.5;
    }
    
    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4);
        }
        70% {
            box-shadow: 0 0 0 6px rgba(59, 130, 246, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);
        }
    }
</style>
@endsection

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">SoneAI Kod Öğrenme Sistemi</h1>
                    <p class="text-gray-600">Otomatik kod öğrenme ve bilgi toplama arayüzü</p>
                </div>
                <div>
                    <button id="start-learning" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg transition">
                        <i class="fas fa-play mr-2"></i>Öğrenmeyi Başlat
                    </button>
                    <button id="stop-learning" class="bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded-lg transition ml-2 hidden">
                        <i class="fas fa-stop mr-2"></i>Öğrenmeyi Durdur
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Öğrenme Durumu -->
            <div class="dashboard-card p-6">
                <h2 class="font-semibold text-lg text-gray-700 mb-4">Öğrenme Durumu</h2>
                <div class="flex items-center mb-4">
                    <span class="status-indicator status-inactive" id="learning-status-indicator"></span>
                    <span class="text-gray-700" id="learning-status-text">Beklemede</span>
                </div>
                <div class="text-xs text-gray-500">Son güncelleme: <span id="last-update">-</span></div>
            </div>
            
            <!-- Öğrenilen Kod Sayısı -->
            <div class="dashboard-card p-6">
                <h2 class="font-semibold text-lg text-gray-700 mb-4">Öğrenilen Kod İstatistikleri</h2>
                <div class="flex flex-col">
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Toplam Kod Snippet:</span>
                        <span class="font-semibold" id="total-snippets">0</span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">İşlevler:</span>
                        <span class="font-semibold" id="total-functions">0</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Sınıflar:</span>
                        <span class="font-semibold" id="total-classes">0</span>
                    </div>
                </div>
            </div>
            
            <!-- Dil Dağılımı -->
            <div class="dashboard-card p-6">
                <h2 class="font-semibold text-lg text-gray-700 mb-4">Dil Dağılımı</h2>
                <div class="space-y-2">
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-gray-600 text-sm">JavaScript</span>
                            <span class="text-gray-600 text-sm" id="js-percentage">0%</span>
                        </div>
                        <div class="learning-progress">
                            <div class="learning-progress-bar" id="js-progress" style="width: 0%; background-color: #f7df1e;"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-gray-600 text-sm">PHP</span>
                            <span class="text-gray-600 text-sm" id="php-percentage">0%</span>
                        </div>
                        <div class="learning-progress">
                            <div class="learning-progress-bar" id="php-progress" style="width: 0%; background-color: #8993be;"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-gray-600 text-sm">Python</span>
                            <span class="text-gray-600 text-sm" id="python-percentage">0%</span>
                        </div>
                        <div class="learning-progress">
                            <div class="learning-progress-bar" id="python-progress" style="width: 0%; background-color: #306998;"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Öğrenme İlerleme Durumu -->
            <div class="dashboard-card p-6">
                <h2 class="font-semibold text-lg text-gray-700 mb-4">Öğrenme İlerlemesi</h2>
                <div class="mb-4">
                    <div class="flex justify-between mb-1">
                        <span class="text-gray-600 text-sm">İlerleme</span>
                        <span class="text-gray-600 text-sm" id="learning-percentage">0%</span>
                    </div>
                    <div class="learning-progress">
                        <div class="learning-progress-bar" id="learning-progress-bar" style="width: 0%;"></div>
                    </div>
                </div>
                <div class="space-y-1">
                    <div class="flex justify-between">
                        <span class="text-gray-600 text-xs">Kaynak sayısı:</span>
                        <span class="text-gray-600 text-xs" id="source-count">0</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 text-xs">Sonraki güncelleme:</span>
                        <span class="text-gray-600 text-xs" id="next-update">-</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Learning Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Son Öğrenilen Kodlar -->
            <div class="dashboard-card p-6">
                <h2 class="font-semibold text-lg text-gray-700 mb-4">Son Öğrenilen Kodlar</h2>
                <div class="space-y-4" id="recent-codes">
                    <div class="bg-gray-100 rounded-lg p-4 text-center text-gray-500">
                        <p>Henüz öğrenilen kod yok</p>
                    </div>
                </div>
            </div>
            
            <!-- Öğrenme Aktiviteleri -->
            <div class="dashboard-card p-6">
                <h2 class="font-semibold text-lg text-gray-700 mb-4">Öğrenme Aktiviteleri</h2>
                <div class="space-y-2" id="learning-activities">
                    <div class="bg-gray-100 rounded-lg p-4 text-center text-gray-500">
                        <p>Henüz aktivite yok</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Ayarlar -->
        <div class="dashboard-card p-6 mb-6">
            <h2 class="font-semibold text-lg text-gray-700 mb-4">Öğrenme Ayarları</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Öğrenme Önceliği</label>
                    <select id="learning-priority" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="js">JavaScript</option>
                        <option value="php">PHP</option>
                        <option value="python">Python</option>
                        <option value="all">Tüm Diller</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Öğrenme Hızı</label>
                    <select id="learning-rate" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="slow">Yavaş (Saatte 20 kod)</option>
                        <option value="medium" selected>Orta (Saatte 50 kod)</option>
                        <option value="fast">Hızlı (Saatte 100 kod)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Özel Kütüphane Odağı</label>
                    <select id="library-focus" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="none">Odak Yok</option>
                        <option value="react">React.js</option>
                        <option value="laravel">Laravel</option>
                        <option value="django">Django</option>
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <button id="save-settings" class="bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded-lg transition">
                    <i class="fas fa-save mr-2"></i>Ayarları Kaydet
                </button>
            </div>
        </div>
        
        <!-- Manuel Kod Ekleme -->
        <div class="dashboard-card p-6">
            <h2 class="font-semibold text-lg text-gray-700 mb-4">Manuel Kod Ekleme</h2>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Programlama Dili</label>
                <select id="manual-language" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="javascript">JavaScript</option>
                    <option value="php">PHP</option>
                    <option value="python">Python</option>
                    <option value="html">HTML</option>
                    <option value="css">CSS</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Kod Snippeti</label>
                <textarea id="manual-code" rows="6" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 font-mono" placeholder="// Buraya kodu yapıştırın..."></textarea>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Açıklama</label>
                <input type="text" id="manual-description" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Bu kod ne işe yarıyor...">
            </div>
            <div>
                <button id="add-manual-code" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg transition">
                    <i class="fas fa-plus mr-2"></i>Kodu Ekle
                </button>
            </div>
        </div>
        
        <!-- Bilinç Sistemi Bölümü -->
        <div class="dashboard-card p-6 mb-6">
            <h2 class="font-semibold text-lg text-gray-700 mb-4">Bilinç Sistemi Durumu</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6 mb-4">
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Bilinç Seviyesi:</span>
                        <span class="font-semibold" id="consciousness-level">0</span>
                    </div>
                    <div class="learning-progress">
                        <div class="learning-progress-bar" id="consciousness-level-bar" style="width: 0%; background-color: #10b981;"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Kod İlişki Sayısı:</span>
                        <span class="font-semibold" id="relation-count">0</span>
                    </div>
                    <div class="learning-progress">
                        <div class="learning-progress-bar" id="relation-count-bar" style="width: 0%; background-color: #8b5cf6;"></div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center">
                    <span class="status-indicator status-inactive" id="consciousness-status-indicator"></span>
                    <span class="text-gray-700" id="consciousness-status-text">Beklemede</span>
                </div>
                <div>
                    <button id="toggle-consciousness" class="bg-purple-500 hover:bg-purple-600 text-white font-medium py-2 px-4 rounded-lg transition mr-2">
                        <i class="fas fa-power-off mr-2"></i>Etkinleştir
                    </button>
                    <button id="trigger-thinking" class="bg-indigo-500 hover:bg-indigo-600 text-white font-medium py-2 px-4 rounded-lg transition">
                        <i class="fas fa-brain mr-2"></i>Düşünmeyi Tetikle
                    </button>
                </div>
            </div>
            
            <div class="text-xs text-gray-500">Son düşünme zamanı: <span id="last-thinking-time">-</span></div>
        </div>
        
        <!-- Kod Önerileri Bölümü -->
        <div class="dashboard-card p-6 mb-6">
            <h2 class="font-semibold text-lg text-gray-700 mb-4">Kod Önerileri</h2>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Ne Tür Bir Kod Arıyorsunuz?</label>
                <div class="flex">
                    <input type="text" id="recommendation-query" class="flex-1 px-3 py-2 border border-gray-300 rounded-l-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Örn: Responsive bir form arayüzü...">
                    <select id="recommendation-language" class="w-40 px-3 py-2 border border-gray-300 border-l-0 shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="html">HTML</option>
                        <option value="css">CSS</option>
                        <option value="javascript">JavaScript</option>
                        <option value="php">PHP</option>
                    </select>
                    <button id="get-recommendations" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-r-md transition">
                        <i class="fas fa-search mr-2"></i>Öner
                    </button>
                </div>
            </div>
            
            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-700 font-medium">Öneri Bilgileri</span>
                    <span class="text-xs text-gray-500" id="recommendation-time">-</span>
                </div>
                <div class="flex flex-wrap gap-2" id="recommendation-tags">
                    <!-- Öneri etiketleri buraya gelecek -->
                    <span class="text-xs px-2 py-1 bg-gray-200 text-gray-700 rounded">Henüz öneri yok</span>
                </div>
            </div>
            
            <div class="space-y-4" id="recommendation-results">
                <div class="bg-gray-100 rounded-lg p-4 text-center text-gray-500">
                    <p>Öneri almak için yukarıdaki formu doldurun</p>
                </div>
            </div>
        </div>
        
        <!-- Sistem Performansı Bölümü -->
        <div class="dashboard-card p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="font-semibold text-lg text-gray-700">Sistem Performansı</h2>
                <button id="analyze-system" class="bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded-lg transition">
                    <i class="fas fa-chart-line mr-2"></i>Performans Analizi
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="rounded-lg bg-blue-50 p-4">
                    <div class="text-blue-800 font-semibold mb-1">Öğrenme Oranı</div>
                    <div class="text-3xl font-bold text-blue-600" id="learning-rate-value">-%</div>
                    <div class="text-xs text-blue-500 mt-1">Son 24 saatte: <span id="learning-last-24">0</span> kod</div>
                </div>
                
                <div class="rounded-lg bg-purple-50 p-4">
                    <div class="text-purple-800 font-semibold mb-1">Bilinç Entegrasyonu</div>
                    <div class="text-3xl font-bold text-purple-600" id="consciousness-integration-value">-%</div>
                    <div class="text-xs text-purple-500 mt-1">Seviye: <span id="consciousness-integration-level">-</span></div>
                </div>
                
                <div class="rounded-lg bg-green-50 p-4">
                    <div class="text-green-800 font-semibold mb-1">API Başarı Oranı</div>
                    <div class="text-3xl font-bold text-green-600" id="api-success-rate">-%</div>
                    <div class="text-xs text-green-500 mt-1">İstek: <span id="api-request-count">0</span> / Başarılı: <span id="api-success-count">0</span></div>
                </div>
                
                <div class="rounded-lg bg-yellow-50 p-4">
                    <div class="text-yellow-800 font-semibold mb-1">Sistem Sağlığı</div>
                    <div class="text-3xl font-bold text-yellow-600" id="system-health-value">-%</div>
                    <div class="text-xs text-yellow-500 mt-1">Çalışma süresi: <span id="system-uptime">0</span> saat</div>
                </div>
            </div>
            
            <div>
                <h3 class="font-semibold text-gray-700 mb-3">İyileştirme Önerileri</h3>
                <div id="system-recommendations" class="space-y-2">
                    <div class="flex items-center p-3 bg-gray-100 rounded-lg">
                        <span class="text-gray-500">Henüz analiz yapılmadı</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // DOM Elementleri
        const startLearningBtn = document.getElementById('start-learning');
        const stopLearningBtn = document.getElementById('stop-learning');
        const learningStatusIndicator = document.getElementById('learning-status-indicator');
        const learningStatusText = document.getElementById('learning-status-text');
        const lastUpdateEl = document.getElementById('last-update');
        const totalSnippetsEl = document.getElementById('total-snippets');
        const totalFunctionsEl = document.getElementById('total-functions');
        const totalClassesEl = document.getElementById('total-classes');
        const jsPercentageEl = document.getElementById('js-percentage');
        const jsProgressEl = document.getElementById('js-progress');
        const phpPercentageEl = document.getElementById('php-percentage');
        const phpProgressEl = document.getElementById('php-progress');
        const pythonPercentageEl = document.getElementById('python-percentage');
        const pythonProgressEl = document.getElementById('python-progress');
        const learningPercentageEl = document.getElementById('learning-percentage');
        const learningProgressBarEl = document.getElementById('learning-progress-bar');
        const sourceCountEl = document.getElementById('source-count');
        const nextUpdateEl = document.getElementById('next-update');
        const recentCodesEl = document.getElementById('recent-codes');
        const learningActivitiesEl = document.getElementById('learning-activities');
        const saveSettingsBtn = document.getElementById('save-settings');
        const addManualCodeBtn = document.getElementById('add-manual-code');
        
        // Yeni Bilinç Sistemi DOM Elementleri
        const consciousnessLevelEl = document.getElementById('consciousness-level');
        const consciousnessLevelBarEl = document.getElementById('consciousness-level-bar');
        const relationCountEl = document.getElementById('relation-count');
        const relationCountBarEl = document.getElementById('relation-count-bar');
        const consciousnessStatusIndicator = document.getElementById('consciousness-status-indicator');
        const consciousnessStatusText = document.getElementById('consciousness-status-text');
        const toggleConsciousnessBtn = document.getElementById('toggle-consciousness');
        const triggerThinkingBtn = document.getElementById('trigger-thinking');
        const lastThinkingTimeEl = document.getElementById('last-thinking-time');
        
        // CSRF Token
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            console.error('CSRF token bulunamadı!');
        }
        
        // Öğrenme durumunu kontrol et ve UI'ı güncelle
        async function checkLearningStatus() {
            try {
                const response = await fetch('/api/ai/code-learning/status', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.content
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`Sunucu hatası: ${response.status}`);
                }
                
                const data = await response.json();
                
                // Durum bilgisini güncelle
                updateStatusUI(data.status);
                
                // İstatistikleri güncelle
                updateStatisticsUI(data.statistics);
                
                // Son aktiviteleri güncelle
                updateRecentActivitiesUI(data.recent_activities);
                
                // Son kodları güncelle
                updateRecentCodesUI(data.recent_codes);
                
                // Bilinç sistemi durumunu kontrol et
                checkConsciousnessStatus();
                
                return data;
            } catch (error) {
                console.error('Durum kontrolü hatası:', error);
                return null;
            }
        }
        
        // Bilinç sistemi durumunu kontrol et
        async function checkConsciousnessStatus() {
            try {
                const response = await fetch('/api/ai/code-learning/consciousness-status', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.content
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`Sunucu hatası: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    // Bilinç durumu UI'ını güncelle
                    updateConsciousnessUI(data);
                }
                
                return data;
            } catch (error) {
                console.error('Bilinç durumu kontrolü hatası:', error);
                return null;
            }
        }
        
        // Bilinç sistemi durumuna göre UI'ı güncelle
        function updateConsciousnessUI(data) {
            // Bilinç seviyesi
            const level = data.consciousness_level || 0;
            consciousnessLevelEl.textContent = level;
            consciousnessLevelBarEl.style.width = `${level * 10}%`; // 0-10 arası
            
            // İlişki sayısı
            const relationsCount = data.code_relations_count || 0;
            relationCountEl.textContent = relationsCount;
            // Max 1000 ilişki için %100 göster
            relationCountBarEl.style.width = `${Math.min(100, (relationsCount / 10))}%`;
            
            // Durum göstergesi
            if (data.is_active) {
                consciousnessStatusIndicator.className = 'status-indicator status-active';
                consciousnessStatusText.textContent = 'Aktif';
                toggleConsciousnessBtn.innerHTML = '<i class="fas fa-power-off mr-2"></i>Devre Dışı Bırak';
                toggleConsciousnessBtn.classList.remove('bg-purple-500', 'hover:bg-purple-600');
                toggleConsciousnessBtn.classList.add('bg-red-500', 'hover:bg-red-600');
            } else {
                consciousnessStatusIndicator.className = 'status-indicator status-inactive';
                consciousnessStatusText.textContent = 'Devre Dışı';
                toggleConsciousnessBtn.innerHTML = '<i class="fas fa-power-off mr-2"></i>Etkinleştir';
                toggleConsciousnessBtn.classList.remove('bg-red-500', 'hover:bg-red-600');
                toggleConsciousnessBtn.classList.add('bg-purple-500', 'hover:bg-purple-600');
            }
            
            // Son düşünme zamanı
            if (data.last_thinking_time) {
                lastThinkingTimeEl.textContent = formatDate(new Date(data.last_thinking_time));
            } else {
                lastThinkingTimeEl.textContent = '-';
            }
        }
        
        // Bilinç sistemini aç/kapat
        async function toggleConsciousness() {
            try {
                // Mevcut durumu kontrol et
                const statusResponse = await fetch('/api/ai/code-learning/consciousness-status', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.content
                    }
                });
                
                if (!statusResponse.ok) {
                    throw new Error(`Sunucu hatası: ${statusResponse.status}`);
                }
                
                const statusData = await statusResponse.json();
                
                // Durumu tersine çevir
                const newState = !(statusData.is_active);
                
                const response = await fetch('/api/ai/code-learning/consciousness-toggle', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.content
                    },
                    body: JSON.stringify({
                        active: newState
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`Sunucu hatası: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    // UI'ı güncelle
                    checkConsciousnessStatus();
                    
                    // Bildirim göster
                    alert(data.message);
                } else {
                    alert(`İşlem başarısız: ${data.message}`);
                }
            } catch (error) {
                console.error('Bilinç sistemi durumu değiştirme hatası:', error);
                alert('Bilinç sistemi durumu değiştirilirken bir hata oluştu. Lütfen tekrar deneyin.');
            }
        }
        
        // Bilinç sistemi düşünme işlemini tetikle
        async function triggerThinking() {
            try {
                const response = await fetch('/api/ai/code-learning/consciousness-think', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.content
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`Sunucu hatası: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    // UI'ı güncelle
                    checkConsciousnessStatus();
                    
                    // Bildirim göster
                    alert('Düşünme işlemi tamamlandı!');
                } else {
                    alert(`Düşünme işlemi başarısız: ${data.message}`);
                }
            } catch (error) {
                console.error('Düşünme tetikleme hatası:', error);
                alert('Düşünme işlemi tetiklenirken bir hata oluştu. Lütfen tekrar deneyin.');
            }
        }
        
        // Durum UI'ını güncelle
        function updateStatusUI(status) {
            // Öğrenme durumu
            if (status.is_learning) {
                learningStatusIndicator.className = 'status-indicator status-learning';
                learningStatusText.textContent = 'Öğreniyor';
                startLearningBtn.classList.add('hidden');
                stopLearningBtn.classList.remove('hidden');
            } else {
                learningStatusIndicator.className = 'status-indicator status-inactive';
                learningStatusText.textContent = 'Beklemede';
                startLearningBtn.classList.remove('hidden');
                stopLearningBtn.classList.add('hidden');
            }
            
            // Son güncelleme
            lastUpdateEl.textContent = status.last_update;
            
            // Sonraki güncelleme
            nextUpdateEl.textContent = status.next_update;
            
            // İlerleme
            learningPercentageEl.textContent = `${status.progress_percentage}%`;
            learningProgressBarEl.style.width = `${status.progress_percentage}%`;
            
            // Kaynak sayısı
            sourceCountEl.textContent = status.source_count;
        }
        
        // İstatistik UI'ını güncelle
        function updateStatisticsUI(statistics) {
            // Toplam sayılar
            totalSnippetsEl.textContent = statistics.total_snippets;
            totalFunctionsEl.textContent = statistics.total_functions;
            totalClassesEl.textContent = statistics.total_classes;
            
            // Dil dağılımları
            jsPercentageEl.textContent = `${statistics.language_distribution.javascript}%`;
            jsProgressEl.style.width = `${statistics.language_distribution.javascript}%`;
            
            phpPercentageEl.textContent = `${statistics.language_distribution.php}%`;
            phpProgressEl.style.width = `${statistics.language_distribution.php}%`;
            
            pythonPercentageEl.textContent = `${statistics.language_distribution.python}%`;
            pythonProgressEl.style.width = `${statistics.language_distribution.python}%`;
        }
        
        // Son aktiviteleri güncelle
        function updateRecentActivitiesUI(activities) {
            if (!activities || activities.length === 0) {
                learningActivitiesEl.innerHTML = `
                    <div class="bg-gray-100 rounded-lg p-4 text-center text-gray-500">
                        <p>Henüz aktivite yok</p>
                    </div>
                `;
                return;
            }
            
            learningActivitiesEl.innerHTML = '';
            
            activities.forEach(activity => {
                const activityEl = document.createElement('div');
                activityEl.className = 'bg-blue-50 rounded-lg p-3 text-sm';
                activityEl.innerHTML = `
                    <div class="flex justify-between">
                        <span class="font-medium text-blue-600">${activity.action}</span>
                        <span class="text-gray-500 text-xs">${activity.time}</span>
                    </div>
                    <p class="text-gray-600 mt-1">${activity.description}</p>
                `;
                learningActivitiesEl.appendChild(activityEl);
            });
        }
        
        // Son kodları güncelle
        function updateRecentCodesUI(codes) {
            if (!codes || codes.length === 0) {
                recentCodesEl.innerHTML = `
                    <div class="bg-gray-100 rounded-lg p-4 text-center text-gray-500">
                        <p>Henüz öğrenilen kod yok</p>
                    </div>
                `;
                return;
            }
            
            recentCodesEl.innerHTML = '';
            
            codes.forEach(code => {
                const codeEl = document.createElement('div');
                codeEl.className = 'rounded-lg border border-gray-200 overflow-hidden';
                codeEl.innerHTML = `
                    <div class="bg-gray-100 px-4 py-2 flex justify-between items-center">
                        <div>
                            <span class="font-medium">${code.language}</span>
                            <span class="text-gray-500 text-xs ml-2">${code.time}</span>
                        </div>
                        <div>
                            <span class="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded">${code.category}</span>
                        </div>
                    </div>
                    <div class="code-snippet text-sm whitespace-pre">${code.snippet}</div>
                    <div class="bg-gray-50 px-4 py-2 text-xs text-gray-500">${code.description}</div>
                `;
                recentCodesEl.appendChild(codeEl);
            });
        }
        
        // Öğrenmeyi başlat
        async function startLearning() {
            try {
                // Ayarları al ve boş ise varsayılan değerler kullan
                const learningPriority = document.getElementById('learning-priority')?.value || 'html';
                const learningRate = document.getElementById('learning-rate')?.value || 'medium';
                const libraryFocus = document.getElementById('library-focus')?.value || 'css';
                
                const response = await fetch('/api/ai/code-learning/start', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.content
                    },
                    body: JSON.stringify({
                        priority: learningPriority,
                        rate: learningRate,
                        focus: libraryFocus
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`Sunucu hatası: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    // UI'ı güncelle
                    updateStatusUI({
                        is_learning: true,
                        last_update: 'Şimdi',
                        next_update: data.next_update,
                        progress_percentage: 0,
                        source_count: data.source_count
                    });
                    
                    // Bildirim göster
                    alert('Öğrenme başlatıldı!');
                } else {
                    alert(`Öğrenme başlatılamadı: ${data.message}`);
                }
            } catch (error) {
                console.error('Öğrenme başlatma hatası:', error);
                alert('Öğrenme başlatılırken bir hata oluştu. Lütfen tekrar deneyin.');
            }
        }
        
        // Öğrenmeyi durdur
        async function stopLearning() {
            try {
                const response = await fetch('/api/ai/code-learning/stop', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.content
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`Sunucu hatası: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    // UI'ı güncelle
                    updateStatusUI({
                        is_learning: false,
                        last_update: data.last_update,
                        next_update: '-',
                        progress_percentage: data.progress_percentage,
                        source_count: data.source_count
                    });
                    
                    // Bildirim göster
                    alert('Öğrenme durduruldu!');
                } else {
                    alert(`Öğrenme durdurulamadı: ${data.message}`);
                }
            } catch (error) {
                console.error('Öğrenme durdurma hatası:', error);
                alert('Öğrenme durdurulurken bir hata oluştu. Lütfen tekrar deneyin.');
            }
        }
        
        // Ayarları kaydet
        async function saveSettings() {
            try {
                const learningPriority = document.getElementById('learning-priority').value;
                const learningRate = document.getElementById('learning-rate').value;
                const libraryFocus = document.getElementById('library-focus').value;
                
                const response = await fetch('/api/ai/code-learning/settings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.content
                    },
                    body: JSON.stringify({
                        priority: learningPriority,
                        rate: learningRate,
                        focus: libraryFocus
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`Sunucu hatası: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Ayarlar kaydedildi!');
                } else {
                    alert(`Ayarlar kaydedilemedi: ${data.message}`);
                }
            } catch (error) {
                console.error('Ayar kaydetme hatası:', error);
                alert('Ayarlar kaydedilirken bir hata oluştu. Lütfen tekrar deneyin.');
            }
        }
        
        // Manuel kod ekle
        async function addManualCode() {
            try {
                const language = document.getElementById('manual-language').value;
                const code = document.getElementById('manual-code').value;
                const description = document.getElementById('manual-description').value;
                
                if (!code.trim()) {
                    alert('Lütfen bir kod girin!');
                    return;
                }
                
                const response = await fetch('/api/ai/code-learning/add-code', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.content
                    },
                    body: JSON.stringify({
                        language: language,
                        code: code,
                        description: description
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`Sunucu hatası: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    // Formu temizle
                    document.getElementById('manual-code').value = '';
                    document.getElementById('manual-description').value = '';
                    
                    // Bildirim göster
                    alert('Kod başarıyla eklendi!');
                    
                    // Durum kontrolü yap
                    checkLearningStatus();
                } else {
                    alert(`Kod eklenemedi: ${data.message}`);
                }
            } catch (error) {
                console.error('Kod ekleme hatası:', error);
                alert('Kod eklenirken bir hata oluştu. Lütfen tekrar deneyin.');
            }
        }
        
        // Kod önerileri al
        async function getRecommendations() {
            try {
                const query = document.getElementById('recommendation-query').value;
                const language = document.getElementById('recommendation-language').value;
                
                if (!query.trim()) {
                    alert('Lütfen bir arama sorgusu girin!');
                    return;
                }
                
                const response = await fetch('/api/ai/code-learning/recommendations', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.content
                    },
                    body: JSON.stringify({
                        context: query,
                        language: language,
                        count: 3
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`Sunucu hatası: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    // Öneri sonuçlarını göster
                    displayRecommendations(data);
                } else {
                    alert(`Öneri alınamadı: ${data.message}`);
                }
            } catch (error) {
                console.error('Öneri alma hatası:', error);
                alert('Öneri alınırken bir hata oluştu. Lütfen tekrar deneyin.');
            }
        }
        
        // Önerileri göster
        function displayRecommendations(data) {
            const recommendationResultsEl = document.getElementById('recommendation-results');
            const recommendationTagsEl = document.getElementById('recommendation-tags');
            const recommendationTimeEl = document.getElementById('recommendation-time');
            
            // Zaman bilgisini güncelle
            recommendationTimeEl.textContent = formatDate(new Date());
            
            // Etiketleri göster
            recommendationTagsEl.innerHTML = '';
            if (data.keywords && data.keywords.length > 0) {
                data.keywords.forEach(keyword => {
                    const tagEl = document.createElement('span');
                    tagEl.className = 'text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded mr-1 mb-1 inline-block';
                    tagEl.textContent = keyword;
                    recommendationTagsEl.appendChild(tagEl);
                });
            }
            
            // Öneri sonuçlarını göster
            recommendationResultsEl.innerHTML = '';
            
            if (!data.recommendations || data.recommendations.length === 0) {
                recommendationResultsEl.innerHTML = `
                    <div class="bg-gray-100 rounded-lg p-4 text-center text-gray-500">
                        <p>Aramanıza uygun kod önerisi bulunamadı</p>
                    </div>
                `;
                return;
            }
            
            data.recommendations.forEach(recommendation => {
                // Kod içeriğini güvenli bir şekilde HTML formatına dönüştür
                const safeCode = recommendation.code ? 
                    recommendation.code.replace(/</g, '&lt;').replace(/>/g, '&gt;') : 
                    '// Kod içeriği bulunamadı';
                
                const codeEl = document.createElement('div');
                codeEl.className = 'rounded-lg border border-gray-200 overflow-hidden mb-4';
                codeEl.innerHTML = `
                    <div class="bg-gray-100 px-4 py-2 flex justify-between items-center">
                        <div>
                            <span class="font-medium">${recommendation.language || 'Bilinmeyen Dil'}</span>
                            <span class="text-gray-500 text-xs ml-2">${recommendation.category || 'Genel'}</span>
                        </div>
                        <div>
                            <span class="text-xs px-2 py-1 bg-green-100 text-green-800 rounded">
                                Benzerlik: ${Math.round((recommendation.relevance_score || 0.6) * 100)}%
                            </span>
                        </div>
                    </div>
                    <pre class="code-snippet text-sm overflow-auto p-4 m-0 bg-gray-900 text-gray-100">${safeCode}</pre>
                    <div class="bg-gray-50 px-4 py-2 text-xs text-gray-700">${recommendation.description || 'Açıklama yok'}</div>
                    <div class="bg-gray-100 px-4 py-2 flex justify-end">
                        <button class="use-code-btn text-xs bg-blue-500 hover:bg-blue-600 text-white font-medium py-1 px-3 rounded transition" 
                                data-code-id="${recommendation.id || 0}">Kodu Kullan</button>
                    </div>
                `;
                recommendationResultsEl.appendChild(codeEl);
            });
            
            // Kod kullanma butonlarına event ekle
            document.querySelectorAll('.use-code-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const codeId = this.getAttribute('data-code-id');
                    useCode(codeId);
                });
            });
        }
        
        // Sistem performansını analiz et
        async function analyzeSystemPerformance() {
            try {
                const response = await fetch('/api/ai/code-learning/system-analysis', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.content
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`Sunucu hatası: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    // Performans verilerini göster
                    displayPerformanceData(data);
                } else {
                    alert(`Analiz başarısız: ${data.message}`);
                }
            } catch (error) {
                console.error('Performans analizi hatası:', error);
                alert('Performans analizi yapılırken bir hata oluştu. Lütfen tekrar deneyin.');
            }
        }
        
        // Performans verilerini göster
        function displayPerformanceData(data) {
            // Öğrenme oranı
            document.getElementById('learning-rate-value').textContent = data.learning_rate_percentage + '%';
            document.getElementById('learning-last-24').textContent = data.codes_learned_last_24h;
            
            // Bilinç entegrasyonu
            document.getElementById('consciousness-integration-value').textContent = data.consciousness_integration_percentage + '%';
            document.getElementById('consciousness-integration-level').textContent = data.consciousness_integration_level;
            
            // API başarı oranı
            document.getElementById('api-success-rate').textContent = data.api_success_rate_percentage + '%';
            document.getElementById('api-request-count').textContent = data.api_request_count;
            document.getElementById('api-success-count').textContent = data.api_success_count;
            
            // Sistem sağlığı
            document.getElementById('system-health-value').textContent = data.system_health_percentage + '%';
            document.getElementById('system-uptime').textContent = data.system_uptime_hours;
            
            // İyileştirme önerileri
            const recommendationsEl = document.getElementById('system-recommendations');
            recommendationsEl.innerHTML = '';
            
            if (data.recommendations && data.recommendations.length > 0) {
                data.recommendations.forEach(recommendation => {
                    const recEl = document.createElement('div');
                    recEl.className = 'flex items-center p-3 bg-gray-50 rounded-lg';
                    
                    // Öneri tipine göre renk belirle
                    let iconClass = 'text-gray-600';
                    if (recommendation.type === 'critical') {
                        iconClass = 'text-red-600';
                    } else if (recommendation.type === 'warning') {
                        iconClass = 'text-yellow-600';
                    } else if (recommendation.type === 'info') {
                        iconClass = 'text-blue-600';
                    } else if (recommendation.type === 'success') {
                        iconClass = 'text-green-600';
                    }
                    
                    recEl.innerHTML = `
                        <i class="fas fa-lightbulb mr-2 ${iconClass}"></i>
                        <span class="text-gray-700">${recommendation.message}</span>
                    `;
                    recommendationsEl.appendChild(recEl);
                });
            } else {
                recommendationsEl.innerHTML = `
                    <div class="flex items-center p-3 bg-green-50 rounded-lg">
                        <i class="fas fa-check-circle mr-2 text-green-600"></i>
                        <span class="text-green-800">Sistem optimal çalışıyor. Şu anda iyileştirme önerisi yok.</span>
                    </div>
                `;
            }
        }
        
        // Tarih formatlama yardımcı fonksiyonu
        function formatDate(date) {
            return date.toLocaleString('tr-TR', {
                year: 'numeric',
                month: 'numeric',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        // Kodu kullan
        async function useCode(codeId) {
            try {
                const response = await fetch('/api/ai/code-learning/use-code', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.content
                    },
                    body: JSON.stringify({
                        code_id: codeId
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`Sunucu hatası: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Kod başarıyla kopyalandı!');
                } else {
                    alert(`Kod kullanımı başarısız: ${data.message}`);
                }
            } catch (error) {
                console.error('Kod kullanma hatası:', error);
                alert('Kod kullanılırken bir hata oluştu. Lütfen tekrar deneyin.');
            }
        }
        
        // Event Listeners
        startLearningBtn.addEventListener('click', startLearning);
        stopLearningBtn.addEventListener('click', stopLearning);
        saveSettingsBtn.addEventListener('click', saveSettings);
        addManualCodeBtn.addEventListener('click', addManualCode);
        
        // Bilinç sistemi butonları
        toggleConsciousnessBtn.addEventListener('click', toggleConsciousness);
        triggerThinkingBtn.addEventListener('click', triggerThinking);
        
        // Kod önerileri butonu
        document.getElementById('get-recommendations').addEventListener('click', getRecommendations);
        
        // Sistem analizi butonu
        document.getElementById('analyze-system').addEventListener('click', analyzeSystemPerformance);
        
        // Sayfa yüklendiğinde kontrol et
        checkLearningStatus();
        
        // Her 60 saniyede bir durum kontrolü yap
        setInterval(checkLearningStatus, 60000);
    });
</script>
@endsection 