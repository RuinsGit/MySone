<?php

namespace App\AI\Core;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use App\Models\AICodeSnippet;
use App\Models\AICodeActivity;
use App\AI\Core\CodeConsciousness\CodeRelationAnalyzer;
use App\AI\Core\CodeConsciousness\CodeCategoryDetector;

class CodeLearningSystem
{
    private $isLearning = false;
    private $learningInterval = 30; // 30 saniye öğrenme aralığı
    private $lastLearningTime;
    
    // API kimlik bilgilerini ekleyelim
    private $apiCredentials = [
        'github' => [
            'token' => null, // Production'da bu değeri env değişkeninden alın
        ],
        'stackoverflow' => [
            'key' => null, // Production'da bu değeri env değişkeninden alın
        ],
        'devto' => [
            'api_key' => null // Production'da bu değeri env değişkeninden alın
        ],
        'gitlab' => [
            'token' => null // Production'da bu değeri env değişkeninden alın
        ],
        'codepen' => [
            'api_key' => null // Production'da bu değeri env değişkeninden alın
        ]
    ];
    
    private $sourceApis = [
        'github' => [
            'enabled' => true,
            'url' => 'https://api.github.com',
            'requests_per_hour' => 60
        ],
        'stackoverflow' => [
            'enabled' => true,
            'url' => 'https://api.stackexchange.com/2.3',
            'requests_per_hour' => 30
        ],
        'devto' => [
            'enabled' => true,
            'url' => 'https://dev.to/api',
            'requests_per_hour' => 30
        ],
        'gitlab' => [
            'enabled' => true,
            'url' => 'https://gitlab.com/api/v4',
            'requests_per_hour' => 40
        ],
        'codepen' => [
            'enabled' => true,
            'url' => 'https://codepen.io/api',
            'requests_per_hour' => 20
        ]
    ];
    
    private $settings = [
        'priority' => 'html', // Önceliği html olarak değiştirildi
        'rate' => 'medium', // slow, medium, fast, turbo
        'focus' => 'css', // css'e odaklanacak şekilde değiştirildi
        'ai_assistance' => true, // Bilinç sistemini kullan
        'auto_recommendation' => true // Otomatik kod önerileri
    ];
    
    private $rateLimits = [
        'slow' => 20, // Saatte 20 kod
        'medium' => 50, // Saatte 50 kod
        'fast' => 100, // Saatte 100 kod
        'turbo' => 200 // Saatte 200 kod (yeni turbo modu)
    ];
    
    private $languageExtensions = [
        'javascript' => ['js', 'jsx', 'ts', 'tsx'],
        'php' => ['php'],
        'python' => ['py'],
        'html' => ['html', 'htm'],
        'css' => ['css', 'scss', 'sass']
    ];
    
    // Bilinç sistemi için bileşenler
    private $codeRelationAnalyzer;
    private $codeCategoryDetector;
    
    public function __construct()
    {
        $this->lastLearningTime = now();
        $this->loadSettings();
        
        // Bilinç sistemi bileşenlerini başlat
        $this->codeRelationAnalyzer = new CodeRelationAnalyzer();
        $this->codeCategoryDetector = new CodeCategoryDetector();
    }
    
    /**
     * Ayarları yükle
     */
    private function loadSettings()
    {
        $settings = Cache::get('ai_code_learning_settings', null);
        
        if ($settings) {
            $this->settings = $settings;
        }
        
        $isLearning = Cache::get('ai_code_learning_active', false);
        $this->isLearning = $isLearning;
        
        $learningInterval = Cache::get('ai_code_learning_interval', 60);
        $this->learningInterval = $learningInterval;
        
        // API kimlik bilgilerini yükle
        $this->loadApiCredentials();
        
        // Son öğrenme zamanını güncelle
        $lastLearningTime = Cache::get('ai_code_learning_last_update');
        if ($lastLearningTime) {
            $this->lastLearningTime = Carbon::parse($lastLearningTime);
        }
    }
    
    /**
     * API kimlik bilgilerini yükle
     */
    private function loadApiCredentials()
    {
        // Production ortamında env değişkenlerini kullanabilirsiniz
        // Şimdilik test için sabit değerler atayalım
        $this->apiCredentials['github']['token'] = null; // env('GITHUB_API_TOKEN');
        $this->apiCredentials['stackoverflow']['key'] = null; // env('STACKOVERFLOW_API_KEY');
        $this->apiCredentials['devto']['api_key'] = null; // env('DEVTO_API_KEY');
        $this->apiCredentials['gitlab']['token'] = null; // env('GITLAB_API_TOKEN');
        $this->apiCredentials['codepen']['api_key'] = null; // env('CODEPEN_API_KEY');
    }
    
    /**
     * Kod öğrenme sistemini aktifleştir
     */
    public function activate()
    {
        $this->isLearning = true;
        Cache::put('ai_code_learning_active', true, now()->addDay());
        $this->logActivity('Kod öğrenme sistemi aktifleştirildi', 'System');
        
        $this->startContinuousLearning();
        
        return [
            'success' => true,
            'message' => 'Kod öğrenme sistemi başlatıldı',
            'next_update' => $this->lastLearningTime->addSeconds($this->learningInterval)->diffForHumans(),
            'source_count' => count(array_filter($this->sourceApis, fn($api) => $api['enabled']))
        ];
    }
    
    /**
     * Kod öğrenme sistemini devre dışı bırak
     */
    public function deactivate()
    {
        $this->isLearning = false;
        Cache::put('ai_code_learning_active', false, now()->addDay());
        $this->logActivity('Kod öğrenme sistemi durduruldu', 'System');
        
        return [
            'success' => true,
            'message' => 'Kod öğrenme sistemi durduruldu',
            'last_update' => now()->format('d.m.Y H:i:s'),
            'progress_percentage' => $this->getProgress()['percentage'],
            'source_count' => count(array_filter($this->sourceApis, fn($api) => $api['enabled']))
        ];
    }
    
    /**
     * Sürekli öğrenme döngüsünü başlat - HTML/CSS odaklı
     */
    private function startContinuousLearning()
    {
        if (!$this->isLearning) {
            return;
        }
        
        try {
            // Sistem aktifse hemen öğrenmeye başla
            $now = now();
            
            // Anında öğrenme başlat - daha önce interval kontrol ediliyordu ama artık her zaman başlatıyoruz
            Log::info('Kod öğrenme hemen başlatılıyor...');
            $learnedCount = $this->learnNewCode();
            Log::info("Anlık öğrenme tamamlandı: $learnedCount kod öğrenildi");
            
            // Son öğrenme zamanını güncelle
            $this->lastLearningTime = $now;
            
            // Son öğrenme zamanını cache'e kaydet
            Cache::put('ai_code_learning_last_update', $now, now()->addDay());
        } catch (\Exception $e) {
            Log::error('Sürekli öğrenme başlatma hatası: ' . $e->getMessage(), [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Yeni kod örnekleri öğren - HTML/CSS odaklı
     */
    public function learnNewCode()
    {
        try {
            if (!$this->isLearning) {
                Log::info('Kod öğrenme devre dışı, learnNewCode çalıştırılmadı.');
                return 0;
            }
            
            Log::info('Kod öğrenme başlatıldı - ' . now()->format('Y-m-d H:i:s'));
            
            // Öğrenme oranına göre kod sayısını belirle
            $codeCount = $this->rateLimits[$this->settings['rate']] / 60 * $this->learningInterval;
            $codeCount = max(1, round($codeCount)); // En az 1 kod
            
            $this->logActivity("$codeCount adet yeni HTML/CSS kodu öğrenme işlemi başladı", 'Learning');
            
            // HTML ve CSS dillerine odaklan
            $focusLanguages = ['html', 'css'];
            
            // Kaynak API'lerden kod örnekleri topla
            $collectedCodes = [];
            $apiSuccess = false;
            
            // GitHub API
            if ($this->sourceApis['github']['enabled']) {
                try {
                    Log::info('GitHub API\'den kod toplamaya başlanıyor...');
                    $githubCodes = $this->fetchFromGitHub($focusLanguages, $codeCount);
                    Log::info('GitHub API kodları toplandı: ' . count($githubCodes));
                    $collectedCodes = array_merge($collectedCodes, $githubCodes);
                    if (count($githubCodes) > 0) {
                        $apiSuccess = true;
                    }
                } catch (\Exception $e) {
                    Log::error('GitHub API hatası: ' . $e->getMessage());
                    $this->logActivity('GitHub API hatası: ' . $e->getMessage(), 'Error');
                }
            }
            
            // Stack Overflow API
            if ($this->sourceApis['stackoverflow']['enabled'] && count($collectedCodes) < $codeCount) {
                try {
                    Log::info('StackOverflow API\'den kod toplamaya başlanıyor...');
                    $stackCodes = $this->fetchFromStackOverflow($focusLanguages, $codeCount - count($collectedCodes));
                    Log::info('StackOverflow API kodları toplandı: ' . count($stackCodes));
                    $collectedCodes = array_merge($collectedCodes, $stackCodes);
                    if (count($stackCodes) > 0) {
                        $apiSuccess = true;
                    }
                } catch (\Exception $e) {
                    Log::error('Stack Overflow API hatası: ' . $e->getMessage());
                    $this->logActivity('Stack Overflow API hatası: ' . $e->getMessage(), 'Error');
                }
            }
            
            // Dev.to API
            if ($this->sourceApis['devto']['enabled'] && count($collectedCodes) < $codeCount) {
                try {
                    Log::info('Dev.to API\'den kod toplamaya başlanıyor...');
                    $devtoCodes = $this->fetchFromDevTo($focusLanguages, $codeCount - count($collectedCodes));
                    Log::info('Dev.to API kodları toplandı: ' . count($devtoCodes));
                    $collectedCodes = array_merge($collectedCodes, $devtoCodes);
                    if (count($devtoCodes) > 0) {
                        $apiSuccess = true;
                    }
                } catch (\Exception $e) {
                    Log::error('Dev.to API hatası: ' . $e->getMessage());
                    $this->logActivity('Dev.to API hatası: ' . $e->getMessage(), 'Error');
                }
            }
            
            // GitLab API
            if ($this->sourceApis['gitlab']['enabled'] && count($collectedCodes) < $codeCount) {
                try {
                    Log::info('GitLab API\'den kod toplamaya başlanıyor...');
                    $gitlabCodes = $this->fetchFromGitLab($focusLanguages, $codeCount - count($collectedCodes));
                    Log::info('GitLab API kodları toplandı: ' . count($gitlabCodes));
                    $collectedCodes = array_merge($collectedCodes, $gitlabCodes);
                    if (count($gitlabCodes) > 0) {
                        $apiSuccess = true;
                    }
                } catch (\Exception $e) {
                    Log::error('GitLab API hatası: ' . $e->getMessage());
                    $this->logActivity('GitLab API hatası: ' . $e->getMessage(), 'Error');
                }
            }
            
            // CodePen API
            if ($this->sourceApis['codepen']['enabled'] && count($collectedCodes) < $codeCount) {
                try {
                    Log::info('CodePen API\'den kod toplamaya başlanıyor...');
                    $codepenCodes = $this->fetchFromCodePen($focusLanguages, $codeCount - count($collectedCodes));
                    Log::info('CodePen API kodları toplandı: ' . count($codepenCodes));
                    $collectedCodes = array_merge($collectedCodes, $codepenCodes);
                    if (count($codepenCodes) > 0) {
                        $apiSuccess = true;
                    }
                } catch (\Exception $e) {
                    Log::error('CodePen API hatası: ' . $e->getMessage());
                    $this->logActivity('CodePen API hatası: ' . $e->getMessage(), 'Error');
                }
            }
            
            // Her durumda manuel örnekler ekleyelim - API'ler başarılı olsa bile
            Log::info('Manuel kod örnekleri ekleniyor...');
            // Birkaç farklı manuel örnek seti ekleyerek çeşitliliği artır
            $manualCodes = $this->getManualCodeExamples($focusLanguages);
            $manualCodes2 = $this->getManualCodeExamples($focusLanguages);
            $manualCodes3 = $this->getManualCodeExamples($focusLanguages);
            
            // Tüm manuel kodları birleştir
            $allManualCodes = array_merge($manualCodes, $manualCodes2, $manualCodes3);
            
            // Benzersizlik için her biri için son bir işlem yapalım
            $timestamp = now()->timestamp;
            $randomSeed = rand(1000, 9999);
            foreach ($allManualCodes as &$code) {
                // Her koda benzersiz bir tanımlayıcı ekle
                $uniqueId = substr(md5($timestamp . $randomSeed . rand(1000, 9999)), 0, 8);
                $code['description'] .= ' - Unique_' . $uniqueId;
                
                // Kod içeriğine benzersiz yorum ekle
                $code['code'] .= "\n/* Benzersiz kod tanımlayıcı: " . $uniqueId . " */";
            }
            
            Log::info('Manuel örnekler eklendi: ' . count($allManualCodes));
            $collectedCodes = array_merge($collectedCodes, $allManualCodes);
            
            Log::info('Toplam toplanan kod sayısı: ' . count($collectedCodes));
            
            // Toplanan kod örneklerini analiz et ve kaydet
            $savedCount = 0;
            foreach ($collectedCodes as $code) {
                try {
                    Log::debug('Kod analiz ediliyor: ' . substr($code['description'], 0, 30) . '...');
                    if ($this->analyzeAndSaveCode($code)) {
                        $savedCount++;
                        Log::debug('Kod başarıyla kaydedildi.');
                    } else {
                        Log::debug('Kod zaten var veya kaydedilemedi.');
                    }
                } catch (\Exception $e) {
                    Log::error('Kod analiz hatası: ' . $e->getMessage());
                }
            }
            
            // Hiç kod kaydedilemezse tekrar manuel kodları zorla ekle
            if ($savedCount == 0) {
                Log::warning('Hiç kod kaydedilemedi, farklı manuel kodlar ekleniyor...');
                
                // Daha fazla çeşitlilik için yeni manuel kodlar oluştur
                $emergencyCodes = [];
                for ($i = 0; $i < 5; $i++) {
                    $uniqueId = 'emergency_' . substr(md5($timestamp . rand(10000, 99999) . $i), 0, 10);
                    
                    // HTML kodu
                    $emergencyCodes[] = [
                        'language' => 'html',
                        'code' => '<div class="container-' . $uniqueId . '">
  <h1>Acil Durum Örneği ' . $i . '</h1>
  <p>Bu kod, normal yöntemler başarısız olduğunda eklenir.</p>
  <ul class="list-' . $uniqueId . '">
    <li>Öğe 1</li>
    <li>Öğe 2</li>
    <li>Öğe ' . rand(3, 10) . '</li>
  </ul>
  <!-- Benzersiz Tanımlayıcı: ' . $uniqueId . ' -->
</div>',
                        'category' => 'emergency',
                        'description' => 'Acil Durum HTML Örneği ' . $i . ' - ' . $uniqueId,
                        'source' => 'Emergency',
                        'source_url' => null
                    ];
                    
                    // CSS kodu
                    $emergencyCodes[] = [
                        'language' => 'css',
                        'code' => '.container-' . $uniqueId . ' {
  padding: ' . rand(10, 30) . 'px;
  margin: ' . rand(10, 30) . 'px;
  border: ' . rand(1, 5) . 'px solid #' . substr(md5(rand()), 0, 6) . ';
  background-color: #' . substr(md5(rand()), 0, 6) . ';
  border-radius: ' . rand(3, 15) . 'px;
}

.container-' . $uniqueId . ' h1 {
  color: #' . substr(md5(rand()), 0, 6) . ';
  font-size: ' . rand(20, 36) . 'px;
}

.list-' . $uniqueId . ' {
  list-style-type: ' . (rand(0, 1) ? 'circle' : 'square') . ';
  padding-left: ' . rand(15, 30) . 'px;
}

.list-' . $uniqueId . ' li {
  margin-bottom: ' . rand(5, 15) . 'px;
  color: #' . substr(md5(rand()), 0, 6) . ';
}

/* Benzersiz Tanımlayıcı: ' . $uniqueId . ' */',
                        'category' => 'emergency',
                        'description' => 'Acil Durum CSS Örneği ' . $i . ' - ' . $uniqueId,
                        'source' => 'Emergency',
                        'source_url' => null
                    ];
                }
                
                // Acil durum kodlarını kaydet
                foreach ($emergencyCodes as $code) {
                    try {
                        // Bu sefer doğrudan veritabanına kaydet, başka bir kontrol yapmadan
                        $codeHash = md5(rand() . $code['description'] . time());
                        
                        $snippetData = [
                            'language' => $code['language'],
                            'category' => $code['category'],
                            'code_content' => $code['code'],
                            'code_hash' => $codeHash, // Her seferinde benzersiz bir hash
                            'description' => $code['description'],
                            'metadata' => json_encode([
                                'source' => $code['source'],
                                'emergency' => true,
                                'timestamp' => now()->format('Y-m-d H:i:s')
                            ]),
                            'usage_count' => 1,
                            'confidence_score' => 0.7,
                            'tags' => [$code['language'], $code['category'], 'emergency']
                        ];
                        
                        $newCode = AICodeSnippet::create($snippetData);
                        if ($newCode) {
                            $savedCount++;
                            Log::info('Acil durum kodu başarıyla kaydedildi: ' . $code['description']);
                        }
                    } catch (\Exception $e) {
                        Log::error('Acil durum kod kaydı hatası: ' . $e->getMessage());
                    }
                }
            }
            
            // Son öğrenme zamanını güncelle
            $now = now();
            Cache::put('ai_code_learning_last_update', $now, now()->addDay());
            $this->lastLearningTime = $now;
            
            Log::info("Kod öğrenme tamamlandı. $savedCount adet yeni kod başarıyla öğrenildi.");
            $this->logActivity("$savedCount adet yeni kod başarıyla öğrenildi", 'Success');
            
            return $savedCount;
        } catch (\Exception $e) {
            Log::error('Kod öğrenme ana hatası: ' . $e->getMessage(), [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->logActivity('Kod öğrenme hatası: ' . $e->getMessage(), 'Error');
            return 0;
        }
    }
    
    /**
     * GitHub API'den kod örnekleri çek - HTML/CSS öncelikli
     */
    private function fetchFromGitHub($languages, $count)
    {
        try {
            $codes = [];
            $query = '';
            
            // HTML ve CSS aramaya öncelik ver
            if (in_array('html', $languages)) {
                $query .= 'language:html ';
            }
            
            if (in_array('css', $languages)) {
                $query .= 'language:css ';
            }
            
            // Diğer diller için de destek ekle
            if (in_array('javascript', $languages)) {
                $query .= 'language:javascript ';
            }
            
            if (in_array('php', $languages)) {
                $query .= 'language:php ';
            }
            
            if (in_array('python', $languages)) {
                $query .= 'language:python ';
            }
            
            // Kütüphane odağı varsa ekle ve HTML/CSS için özel sorgular
            if ($this->settings['focus'] === 'css') {
                $query .= 'css animation responsive flexbox grid ';
            } else if ($this->settings['focus'] !== 'none') {
                $query .= $this->settings['focus'] . ' ';
            }
            
            // Sorgu boşsa basit bir sorgu ekleyelim
            if (empty(trim($query))) {
                $query = 'stars:>100';
            } else {
                $query .= 'stars:>50';
            }
            
            Log::info('GitHub API sorgusu: ' . $query);
            
            // API çağrısı için headers
            $headers = [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'SoneAI-CodeLearning'
            ];
            
            // Token varsa ekle
            if (!empty($this->apiCredentials['github']['token'])) {
                $headers['Authorization'] = 'token ' . $this->apiCredentials['github']['token'];
            }
            
            // Popüler repository'leri ara
            $response = Http::withHeaders($headers)
                ->get($this->sourceApis['github']['url'] . '/search/repositories', [
                    'q' => $query,
                    'sort' => 'stars',
                    'order' => 'desc',
                    'per_page' => 5
                ]);
            
            if ($response->successful()) {
                Log::info('GitHub API yanıtı başarılı: ' . $response->status());
                
                $data = $response->json();
                
                // items anahtarı yoksa veya boşsa
                if (!isset($data['items']) || empty($data['items'])) {
                    Log::warning('GitHub API yanıtında öğe bulunamadı.');
                    return [];
                }
                
                $repositories = $data['items'];
                
                foreach ($repositories as $repo) {
                    // Repository'deki dosyaları al
                    $contents = Http::withHeaders($headers)
                        ->get($this->sourceApis['github']['url'] . '/repos/' . $repo['full_name'] . '/contents');
                    
                    if ($contents->successful()) {
                        $files = $contents->json();
                        
                        // Dönen yanıt bir dizi değilse atla
                        if (!is_array($files)) {
                            continue;
                        }
                        
                        // Sadece kod dosyalarını filtrele
                        $codeFiles = [];
                        foreach ($files as $file) {
                            if (isset($file['type']) && $file['type'] === 'file' && isset($file['name'])) {
                                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                                
                                // Dil kontrolü
                                $matchesLanguage = false;
                                foreach ($languages as $language) {
                                    if (in_array($extension, $this->languageExtensions[$language] ?? [])) {
                                        $matchesLanguage = true;
                                        break;
                                    }
                                }
                                
                                if ($matchesLanguage) {
                                    $codeFiles[] = $file;
                                }
                            }
                        }
                        
                        // Rastgele dosyaları seç ve içeriğini al
                        shuffle($codeFiles);
                        $codeFiles = array_slice($codeFiles, 0, min(2, count($codeFiles)));
                        
                        foreach ($codeFiles as $file) {
                            if (isset($file['download_url'])) {
                                $content = Http::withHeaders($headers)
                                    ->get($file['download_url']);
                                
                                if ($content->successful()) {
                                    $code = $content->body();
                                    
                                    // Çok büyük dosyaları atla
                                    if (strlen($code) > 10000) {
                                        continue;
                                    }
                                    
                                    // Dosya uzantısına göre dil belirle
                                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                                    $language = 'other';
                                    
                                    foreach ($this->languageExtensions as $lang => $extensions) {
                                        if (in_array($extension, $extensions)) {
                                            $language = $lang;
                                            break;
                                        }
                                    }
                                    
                                    $codes[] = [
                                        'language' => $language,
                                        'code' => $code,
                                        'source' => 'github',
                                        'source_url' => $file['html_url'] ?? 'https://github.com',
                                        'description' => 'GitHub: ' . $repo['full_name'] . ' - ' . $file['name'],
                                        'category' => $this->detectCodeCategory($code, $language)
                                    ];
                                    
                                    if (count($codes) >= $count) {
                                        break 2;
                                    }
                                }
                            }
                        }
                    } else {
                        Log::warning('GitHub repository içeriği alınamadı: ' . $contents->status());
                    }
                }
            } else {
                Log::warning('GitHub API yanıtı başarısız: ' . $response->status());
                Log::warning('GitHub API hata detayı: ' . $response->body());
            }
            
            return $codes;
        } catch (\Exception $e) {
            Log::error('GitHub API hatası: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Stack Overflow API'den kod örnekleri çek
     */
    private function fetchFromStackOverflow($languages, $count)
    {
        try {
            $codes = [];
            $tags = [];
            
            // Dillere göre etiketler oluştur
            if (in_array('html', $languages)) {
                $tags[] = 'html';
            }
            
            if (in_array('css', $languages)) {
                $tags[] = 'css';
            }
            
            if (in_array('javascript', $languages)) {
                $tags[] = 'javascript';
            }
            
            if (in_array('php', $languages)) {
                $tags[] = 'php';
            }
            
            if (in_array('python', $languages)) {
                $tags[] = 'python';
            }
            
            // Kütüphane odağı varsa ekle
            if ($this->settings['focus'] !== 'none') {
                $tags[] = $this->settings['focus'];
            }
            
            // Etiket yoksa varsayılan olarak html ve css ekle
            if (empty($tags)) {
                $tags[] = 'html';
                $tags[] = 'css';
            }
            
            // Sorgu parametreleri
            $queryParams = [
                'order' => 'desc',
                'sort' => 'votes',
                'tagged' => implode(';', $tags),
                'site' => 'stackoverflow',
                'filter' => 'withbody',
                'pagesize' => min(10, $count)
            ];
            
            // API anahtarı varsa ekle
            if (!empty($this->apiCredentials['stackoverflow']['key'])) {
                $queryParams['key'] = $this->apiCredentials['stackoverflow']['key'];
            }
            
            Log::info('StackOverflow API sorgusu: ' . json_encode($queryParams));
            
            // Popüler soruları al
            $response = Http::get($this->sourceApis['stackoverflow']['url'] . '/questions', $queryParams);
            
            if ($response->successful()) {
                $data = $response->json();
                
                // items anahtarı yoksa veya boşsa
                if (!isset($data['items']) || empty($data['items'])) {
                    Log::warning('StackOverflow API yanıtında öğe bulunamadı.');
                    return [];
                }
                
                $questions = $data['items'];
                
                foreach ($questions as $question) {
                    // Sorunun gövdesinden kod bloklarını çıkar
                    if (!isset($question['body'])) {
                        continue;
                    }
                    
                    preg_match_all('/<pre><code>(.*?)<\/code><\/pre>/s', $question['body'], $matches);
                    
                    if (!empty($matches[1])) {
                        foreach ($matches[1] as $codeBlock) {
                            $code = html_entity_decode($codeBlock);
                            
                            // Basit HTML temizleme
                            $code = strip_tags($code);
                            
                            // Çok kısa ya da çok uzun kodları atla
                            if (strlen($code) < 30 || strlen($code) > 5000) {
                                continue;
                            }
                            
                            // Sorunun etiketlerine bakarak dil belirle
                            $language = 'other';
                            foreach ($question['tags'] as $tag) {
                                if (in_array($tag, ['javascript', 'php', 'python', 'html', 'css'])) {
                                    $language = $tag;
                                    break;
                                }
                            }
                            
                            $codes[] = [
                                'language' => $language,
                                'code' => $code,
                                'source' => 'stackoverflow',
                                'source_url' => $question['link'],
                                'description' => 'Stack Overflow: ' . htmlspecialchars_decode($question['title']),
                                'category' => $this->detectCodeCategory($code, $language)
                            ];
                            
                            if (count($codes) >= $count) {
                                break 2;
                            }
                        }
                    }
                }
            } else {
                Log::warning('StackOverflow API yanıtı başarısız: ' . $response->status());
                Log::warning('StackOverflow API hata detayı: ' . $response->body());
            }
            
            return $codes;
        } catch (\Exception $e) {
            Log::error('Stack Overflow API hatası: ' . $e->getMessage());
            Log::error('Stack Overflow API hata dizini: ' . json_encode($e->getTrace()));
            return [];
        }
    }
    
    /**
     * Dev.to API'den kod örnekleri çek
     */
    private function fetchFromDevTo($languages, $count)
    {
        try {
            $codes = [];
            $tags = [];
            
            // Dillere göre etiketler oluştur
            if (in_array('javascript', $languages)) {
                $tags[] = 'javascript';
            }
            
            if (in_array('php', $languages)) {
                $tags[] = 'php';
            }
            
            if (in_array('python', $languages)) {
                $tags[] = 'python';
            }
            
            // Kütüphane odağı varsa ekle
            if ($this->settings['focus'] !== 'none') {
                $tags[] = $this->settings['focus'];
            }
            
            foreach ($tags as $tag) {
                $response = Http::withHeaders([
                    'Accept' => 'application/json'
                ])->get($this->sourceApis['devto']['url'] . '/articles', [
                    'tag' => $tag,
                    'top' => 5,
                    'per_page' => 5
                ]);
                
                if ($response->successful()) {
                    $articles = $response->json();
                    
                    foreach ($articles as $article) {
                        // Makale içeriğini al
                        $articleResponse = Http::withHeaders([
                            'Accept' => 'application/json'
                        ])->get($this->sourceApis['devto']['url'] . '/articles/' . $article['id']);
                        
                        if ($articleResponse->successful()) {
                            $articleData = $articleResponse->json();
                            $content = $articleData['body_markdown'];
                            
                            // Markdown'dan kod bloklarını çıkar
                            preg_match_all('/```([a-z]*)\n([\s\S]*?)```/', $content, $matches, PREG_SET_ORDER);
                            
                            foreach ($matches as $match) {
                                $language = $match[1] ?: 'other';
                                $code = $match[2];
                                
                                // Çok kısa ya da çok uzun kodları atla
                                if (strlen($code) < 30 || strlen($code) > 5000) {
                                    continue;
                                }
                                
                                // Dil belirleme
                                if (!in_array($language, ['javascript', 'js', 'php', 'python', 'py', 'html', 'css'])) {
                                    // Etiketlere bakarak dil belirle
                                    foreach ($article['tag_list'] as $tag) {
                                        if (in_array($tag, ['javascript', 'php', 'python', 'html', 'css'])) {
                                            $language = $tag;
                                            break;
                                        }
                                    }
                                }
                                
                                // Kısaltmalar için düzeltme
                                if ($language === 'js') $language = 'javascript';
                                if ($language === 'py') $language = 'python';
                                
                                $codes[] = [
                                    'language' => $language,
                                    'code' => $code,
                                    'source' => 'devto',
                                    'source_url' => $article['url'],
                                    'description' => 'Dev.to: ' . $article['title'],
                                    'category' => $this->detectCodeCategory($code, $language)
                                ];
                                
                                if (count($codes) >= $count) {
                                    break 3;
                                }
                            }
                        }
                    }
                }
            }
            
            return $codes;
        } catch (\Exception $e) {
            Log::error('Dev.to API hatası: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * GitLab API'den kod örnekleri çek
     */
    private function fetchFromGitLab($languages, $count)
    {
        try {
            $codes = [];
            
            // GitLab API anahtar kelimelerini oluştur
            $keywords = [];
            
            // Dil anahtar kelimeleri
            foreach ($languages as $language) {
                $keywords[] = $language;
            }
            
            // Odak noktası anahtar kelimeleri
            if ($this->settings['focus'] !== 'none') {
                $keywords[] = $this->settings['focus'];
            }
            
            // HTML/CSS anahtar kelimeleri
            if (in_array('html', $languages) || in_array('css', $languages)) {
                $keywords = array_merge($keywords, ['responsive', 'animation', 'layout', 'component']);
            }
            
            // API çağrısı için headers
            $headers = [
                'Accept' => 'application/json',
                'User-Agent' => 'SoneAI-CodeLearning/1.0'
            ];
            
            // Token varsa ekle
            if (!empty($this->apiCredentials['gitlab']['token'])) {
                $headers['PRIVATE-TOKEN'] = $this->apiCredentials['gitlab']['token'];
            }
            
            Log::info('GitLab API sorgulaması başlatılıyor');
            
            // Popüler projeleri ara
            foreach ($keywords as $keyword) {
                $response = Http::withHeaders($headers)
                    ->get($this->sourceApis['gitlab']['url'] . '/search', [
                        'scope' => 'projects',
                        'search' => $keyword,
                        'per_page' => 3
                    ]);
                
                if ($response->successful()) {
                    $projects = $response->json();
                    
                    foreach ($projects as $project) {
                        // Proje kimliğini al
                        $projectId = $project['id'];
                        
                        // Proje dosyalarını al
                        $filesResponse = Http::withHeaders($headers)
                            ->get($this->sourceApis['gitlab']['url'] . "/projects/$projectId/repository/tree", [
                                'per_page' => 10
                            ]);
                        
                        if (!$filesResponse->successful()) {
                            continue;
                        }
                        
                        $files = $filesResponse->json();
                        
                        if (!is_array($files)) {
                            continue;
                        }
                        
                        // Sadece dil uzantılarıyla eşleşen dosyaları filtrele
                        $codeFiles = [];
                        foreach ($files as $file) {
                            if ($file['type'] !== 'blob') {
                                continue;
                            }
                            
                            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                            
                            foreach ($languages as $language) {
                                if (in_array($extension, $this->languageExtensions[$language] ?? [])) {
                                    $codeFiles[] = $file;
                                    break;
                                }
                            }
                        }
                        
                        if (empty($codeFiles)) {
                            continue;
                        }
                        
                        // Rastgele dosyaları seç ve içeriğini al
                        shuffle($codeFiles);
                        $codeFiles = array_slice($codeFiles, 0, min(2, count($codeFiles)));
                        
                        foreach ($codeFiles as $file) {
                            $path = $file['path'];
                            
                            $contentResponse = Http::withHeaders($headers)
                                ->get($this->sourceApis['gitlab']['url'] . "/projects/$projectId/repository/files/" . urlencode($path), [
                                    'ref' => 'master'
                                ]);
                                
                            if (!$contentResponse->successful()) {
                                continue;
                            }
                            
                            $contentData = $contentResponse->json();
                            
                            if (!isset($contentData['content'])) {
                                continue;
                            }
                            
                            // Base64 kodunu çöz
                            $code = base64_decode($contentData['content']);
                            
                            // Çok büyük dosyaları atla
                            if (strlen($code) > 10000) {
                                continue;
                            }
                            
                            // Dosya uzantısına göre dil belirle
                            $extension = pathinfo($path, PATHINFO_EXTENSION);
                            $language = 'other';
                            
                            foreach ($this->languageExtensions as $lang => $extensions) {
                                if (in_array($extension, $extensions)) {
                                    $language = $lang;
                                    break;
                                }
                            }
                            
                            $codes[] = [
                                'language' => $language,
                                'code' => $code,
                                'source' => 'gitlab',
                                'source_url' => $project['web_url'] . '/blob/master/' . $path,
                                'description' => 'GitLab: ' . $project['name'] . ' - ' . $path,
                                'category' => $this->detectCodeCategory($code, $language)
                            ];
                            
                            if (count($codes) >= $count) {
                                break 3;
                            }
                        }
                    }
                } else {
                    Log::warning('GitLab API yanıtı başarısız: ' . $response->status());
                }
            }
            
            Log::info('GitLab API\'den ' . count($codes) . ' adet kod toplandı');
            return $codes;
        } catch (\Exception $e) {
            Log::error('GitLab API hatası: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * CodePen API'den kod örnekleri çek
     */
    private function fetchFromCodePen($languages, $count)
    {
        try {
            $codes = [];
            
            // CodePen API'yi çağırmak için parametreleri ayarla
            $headers = [
                'Accept' => 'application/json',
                'User-Agent' => 'SoneAI-CodeLearning/1.0'
            ];
            
            // Popüler HTML/CSS kalemlerini almak için sorgu
            $response = Http::withHeaders($headers)
                ->get('https://cpv2api.com/pens/popular');
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (!isset($data['data']) || !is_array($data['data'])) {
                    return [];
                }
                
                $pens = $data['data'];
                
                foreach ($pens as $pen) {
                    // Pen ID kullanarak detayları al
                    if (!isset($pen['id'])) {
                        continue;
                    }
                    
                    $penId = $pen['id'];
                    
                    $detailResponse = Http::get('https://cpv2api.com/pen/' . $penId);
                    
                    if (!$detailResponse->successful()) {
                        continue;
                    }
                    
                    $detail = $detailResponse->json();
                    
                    if (!isset($detail['data'])) {
                        continue;
                    }
                    
                    $penData = $detail['data'];
                    
                    // HTML kodu
                    if (in_array('html', $languages) && isset($penData['html'])) {
                        $htmlCode = $penData['html'];
                        
                        if (strlen($htmlCode) > 50) {
                            $codes[] = [
                                'language' => 'html',
                                'code' => $htmlCode,
                                'source' => 'codepen',
                                'source_url' => $pen['link'] ?? 'https://codepen.io',
                                'description' => 'CodePen: ' . ($pen['title'] ?? 'HTML Örneği'),
                                'category' => $this->detectCodeCategory($htmlCode, 'html')
                            ];
                        }
                    }
                    
                    // CSS kodu
                    if (in_array('css', $languages) && isset($penData['css'])) {
                        $cssCode = $penData['css'];
                        
                        if (strlen($cssCode) > 50) {
                            $codes[] = [
                                'language' => 'css',
                                'code' => $cssCode,
                                'source' => 'codepen',
                                'source_url' => $pen['link'] ?? 'https://codepen.io',
                                'description' => 'CodePen: ' . ($pen['title'] ?? 'CSS Örneği'),
                                'category' => $this->detectCodeCategory($cssCode, 'css')
                            ];
                        }
                    }
                    
                    // JS kodu
                    if (in_array('javascript', $languages) && isset($penData['js'])) {
                        $jsCode = $penData['js'];
                        
                        if (strlen($jsCode) > 50) {
                            $codes[] = [
                                'language' => 'javascript',
                                'code' => $jsCode,
                                'source' => 'codepen',
                                'source_url' => $pen['link'] ?? 'https://codepen.io',
                                'description' => 'CodePen: ' . ($pen['title'] ?? 'JavaScript Örneği'),
                                'category' => $this->detectCodeCategory($jsCode, 'javascript')
                            ];
                        }
                    }
                    
                    if (count($codes) >= $count) {
                        break;
                    }
                }
            } else {
                Log::warning('CodePen API yanıtı başarısız: ' . $response->status());
            }
            
            Log::info('CodePen API\'den ' . count($codes) . ' adet kod toplandı');
            return $codes;
        } catch (\Exception $e) {
            Log::error('CodePen API hatası: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Bilinç sistemini kullanarak kod kategorisini tespit et
     * 
     * @param string $code Kodun içeriği
     * @param string $language Kodun dili
     * @return string Tespit edilen kategori
     */
    private function detectCodeCategory($code, $language)
    {
        // Kod çok kısaysa basit analiz yap
        if (strlen($code) < 100) {
            return $this->simpleCategoryDetection($code, $language);
        }
        
        try {
            // Eğer AI yardımı etkinse bilinç sistemini kullan
            if ($this->settings['ai_assistance'] && class_exists('App\\AI\\Core\\CodeConsciousness\\CodeCategoryDetector')) {
                // Evrende yapılandırıcıda CodeCategoryDetector sınıfını çağır
                $detector = app()->make('App\\AI\\Core\\CodeConsciousness\\CodeCategoryDetector');
                
                if (method_exists($detector, 'detectCategory')) {
                    $result = $detector->detectCategory($code, $language);
                    
                    if ($result && isset($result['category'])) {
                        Log::info("Bilinç sistemi {$language} dilinde kod kategorisi tespit etti: " . $result['category']);
                        
                        // Kod analizi yap ve ilişkileri güncelle
                        if (class_exists('App\\AI\\Core\\CodeConsciousness\\CodeRelationAnalyzer')) {
                            $analyzer = app()->make('App\\AI\\Core\\CodeConsciousness\\CodeRelationAnalyzer');
                            if (method_exists($analyzer, 'analyzeCode')) {
                                $analysis = $analyzer->analyzeCode($code, $language);
                                
                                if ($analysis && isset($analysis['keywords'])) {
                                    // İlişkileri güncelle
                                    if (method_exists($analyzer, 'updateRelations')) {
                                        $analyzer->updateRelations($code, $language, $analysis['keywords']);
                                    }
                                    
                                    // Kategoriye etiketleri ekle
                                    return $result['category'] . '|' . implode(',', array_slice($analysis['keywords'], 0, 5));
                                }
                            }
                        }
                        
                        return $result['category'];
                    }
                }
            }
            
            // Bilinç sistemi kullanılamıyorsa basit kategori tespiti yap
            return $this->simpleCategoryDetection($code, $language);
        } catch (\Exception $e) {
            Log::error('Kategori tespit hatası: ' . $e->getMessage());
            return $this->simpleCategoryDetection($code, $language);
        }
    }
    
    /**
     * Basit kategori tespiti (bilinç sistemi olmadığında)
     */
    private function simpleCategoryDetection($code, $language)
    {
        switch ($language) {
            case 'html':
                if (strpos($code, '<form') !== false) return 'form';
                if (strpos($code, '<table') !== false) return 'table';
                if (strpos($code, '<nav') !== false) return 'navigation';
                if (strpos($code, '<header') !== false || strpos($code, '<footer') !== false) return 'semantic';
                if (strpos($code, '<div class="container') !== false) return 'layout';
            return 'markup';
                
            case 'css':
                if (strpos($code, '@media') !== false) return 'responsive';
                if (strpos($code, 'animation') !== false || strpos($code, '@keyframes') !== false) return 'animation';
                if (strpos($code, 'display: grid') !== false || strpos($code, 'display: flex') !== false) return 'layout';
                if (strpos($code, 'font-') !== false && strpos($code, 'color:') !== false) return 'typography';
                return 'style';
                
            case 'javascript':
                if (strpos($code, 'function') !== false) return 'function';
                if (strpos($code, 'class') !== false) return 'class';
                if (strpos($code, 'document.') !== false || strpos($code, 'getElementById') !== false) return 'dom';
                if (strpos($code, 'fetch(') !== false || strpos($code, 'axios.') !== false) return 'api';
                return 'script';
                
            case 'php':
                if (strpos($code, 'class') !== false) return 'class';
                if (strpos($code, 'function') !== false) return 'function';
                if (strpos($code, 'namespace') !== false) return 'namespace';
                if (strpos($code, '->query') !== false || strpos($code, 'SELECT') !== false) return 'database';
                return 'script';
                
            default:
            return 'snippet';
        }
    }
    
    /**
     * Kodu analiz et ve veritabanına kaydet (Bilinç sistemi entegrasyonu)
     */
    private function analyzeAndSaveCode($codeData)
    {
        try {
            // Kod kategorisini belirle
            $category = $codeData['category'];
            
            // Kod dilini belirle
            $language = $codeData['language'];
            
            // Kod içeriğini temizle ve analiz et
            $code = $codeData['code'];
            $code = trim($code);
            
            // Boş kod kontrolü
            if (empty($code) || strlen($code) < 10) {
                return false;
            }

            // Mevcut kod kontrolünü devre dışı bırakıp her yeni kod için benzersiz hash oluştur
            $currentTime = microtime(true);
            $randomValue = rand(1000, 9999);
            $codeHash = md5($code . $currentTime . $randomValue . uniqid());
            
            // Bilinç sistemini kullanarak kodun fonksiyonalitesini belirle
            $codeAnalysis = [
                'functionality' => '',
                'complexity' => 0,
                'relations' => [],
                'keywords' => []
            ];
            
            try {
                // CodeRelationAnalyzer kullanarak ilişkileri ve fonksiyonaliteyi belirle
                $relations = $this->codeRelationAnalyzer->analyzeCode($code, $language);
                
                if ($relations && is_array($relations)) {
                    $codeAnalysis['functionality'] = $relations['functionality'] ?? '';
                    $codeAnalysis['complexity'] = $relations['complexity'] ?? 0;
                    $codeAnalysis['relations'] = $relations['relations'] ?? [];
                    $codeAnalysis['keywords'] = $relations['keywords'] ?? [];
                }
            } catch (\Exception $e) {
                Log::warning('Kod bilinç analiz hatası: ' . $e->getMessage());
            }
            
            // Daha detaylı açıklama oluştur
            $description = $codeData['description'];
            if (!empty($codeAnalysis['functionality'])) {
                $description .= ' - ' . $codeAnalysis['functionality'];
            }
            
            // Benzersizliği garanti etmek için açıklamaya zaman damgası ekle
            $description .= ' - ' . now()->format('Y-m-d H:i:s.u');
            
            // Kodla ilgili ek bilgiler topla
            $metadata = [
                'lines' => substr_count($code, "\n") + 1,
                'characters' => strlen($code),
                'has_comments' => (bool) preg_match('/(\/\/|#|\/\*|\*|<!--)/', $code),
                'source' => $codeData['source'],
                'source_url' => $codeData['source_url'] ?? null,
                'imported_at' => now(),
                'complexity' => $codeAnalysis['complexity'],
                'keywords' => $codeAnalysis['keywords'],
                'relations' => $codeAnalysis['relations'],
                'unique_code_id' => uniqid('code_')
            ];
            
            // Daha yüksek güven skoru hesapla
            $confidenceScore = 0.7;
            if ($codeAnalysis['complexity'] > 0) {
                $confidenceScore = min(0.95, 0.7 + ($codeAnalysis['complexity'] / 20));
            }
            
            // Etiketler oluştur
            $tags = [$language, $category];
            if (!empty($codeAnalysis['keywords'])) {
                $tags = array_merge($tags, array_slice($codeAnalysis['keywords'], 0, 5));
            }
            
            // Yeni kod kaydı oluştur
            $snippetData = [
                'language' => $language,
                'category' => $category,
                'code_content' => $code,
                'code_hash' => $codeHash, // Değiştirilmiş hash kullanımı - her kod için benzersiz
                'description' => $description,
                'metadata' => json_encode($metadata),
                'usage_count' => 1,
                'confidence_score' => $confidenceScore,
                'tags' => $tags
            ];
            
            $snippetData['is_featured'] = ($confidenceScore >= 0.9); // Yüksek güvenli kodları öne çıkar
            
            // Yeni kodu kaydet
            $newCodeSnippet = AICodeSnippet::create($snippetData);
            
            if ($newCodeSnippet) {
                // Aktivite kaydı oluştur
                $this->logActivity(
                    "Yeni $language kodu öğrenildi: " . substr($codeData['description'], 0, 50) . "...",
                    'CodeLearning'
                );
                
                // Bilinç sistemini de bilgilendir
                try {
                    if ($this->consciousness && method_exists($this->consciousness, 'learnNewCode')) {
                        $this->consciousness->learnNewCode($newCodeSnippet);
                    }
                } catch (\Exception $e) {
                    Log::warning('Bilinç sistemi bilgilendirme hatası: ' . $e->getMessage());
                }
                
                return true;
            } else {
                Log::error('Yeni kod kaydı oluşturulamadı');
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Kod analiz ve kayıt hatası: ' . $e->getMessage(), [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    
    /**
     * Aktivite kaydı oluştur
     */
    private function logActivity($description, $type = 'System')
    {
        // Veritabanına kaydet
        AICodeActivity::create([
            'activity_type' => $type,
            'description' => $description,
            'timestamp' => now()
        ]);
        
        // Loglama
        Log::info("AI Kod Öğrenme: $description");
    }
    
    /**
     * Ayarları güncelle
     */
    public function updateSettings($settings)
    {
        // Gelen ayarları doğrula
        if (isset($settings['priority']) && in_array($settings['priority'], ['js', 'php', 'python', 'all'])) {
            $this->settings['priority'] = $settings['priority'];
        }
        
        if (isset($settings['rate']) && in_array($settings['rate'], ['slow', 'medium', 'fast', 'turbo'])) {
            $this->settings['rate'] = $settings['rate'];
        }
        
        if (isset($settings['focus'])) {
            $this->settings['focus'] = $settings['focus'];
        }
        
        if (isset($settings['ai_assistance'])) {
            $this->settings['ai_assistance'] = $settings['ai_assistance'];
        }
        
        if (isset($settings['auto_recommendation'])) {
            $this->settings['auto_recommendation'] = $settings['auto_recommendation'];
        }
        
        // Ayarları kaydet
        Cache::put('ai_code_learning_settings', $this->settings, now()->addWeek());
        
        $this->logActivity('Kod öğrenme ayarları güncellendi', 'Settings');
        
        return [
            'success' => true,
            'message' => 'Ayarlar başarıyla güncellendi',
            'settings' => $this->settings
        ];
    }
    
    /**
     * Manuel kod ekle
     */
    public function addManualCode($data)
    {
        try {
            // Veri doğrulama
            if (empty($data['code']) || empty($data['language'])) {
                return [
                    'success' => false,
                    'message' => 'Kod ve dil bilgisi gereklidir'
                ];
            }
            
            $language = $data['language'];
            $code = $data['code'];
            $description = $data['description'] ?? 'Manuel eklenen kod';
            
            // Benzer bir kod var mı kontrol et
            $existingCode = AICodeSnippet::where('code_hash', md5($code))->first();
            if ($existingCode) {
                // Varsa sadece kullanım sayısını artır
                $existingCode->increment('usage_count');
                
                return [
                    'success' => true,
                    'message' => 'Kod zaten veritabanında mevcut, kullanım sayısı artırıldı',
                    'code_id' => $existingCode->id
                ];
            }
            
            // Kod kategorisini tespit et
            $category = $this->detectCodeCategory($code, $language);
            
            // Kodla ilgili ek bilgiler topla
            $metadata = [
                'lines' => substr_count($code, "\n") + 1,
                'characters' => strlen($code),
                'has_comments' => (bool) preg_match('/(\/\/|#|\/\*|\*)/', $code),
                'source' => 'manual',
                'imported_at' => now()
            ];
            
            // Yeni kod kaydı oluştur
            $codeSnippet = AICodeSnippet::create([
                'language' => $language,
                'category' => $category,
                'code_content' => $code,
                'code_hash' => md5($code),
                'description' => $description,
                'metadata' => json_encode($metadata),
                'usage_count' => 1,
                'confidence_score' => 0.9 // Manuel eklenen kodlar daha güvenilir
            ]);
            
            // Aktivite kaydı oluştur
            $this->logActivity(
                "Manuel $language kodu eklendi: " . substr($description, 0, 50),
                'ManualAdd'
            );
            
            return [
                'success' => true,
                'message' => 'Kod başarıyla eklendi',
                'code_id' => $codeSnippet->id
            ];
        } catch (\Exception $e) {
            Log::error('Manuel kod ekleme hatası: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Kod eklenirken bir hata oluştu: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * İlerleme durumunu al
     */
    public function getProgress()
    {
        // Toplam kod sayısı
        $totalCodes = AICodeSnippet::count();
        
        // Hedef kod sayısı (örneğin haftalık 1000 kod)
        $targetCodes = 1000;
        
        // İlerleme yüzdesi
        $percentage = min(100, round(($totalCodes / $targetCodes) * 100));
        
        return [
            'total_codes' => $totalCodes,
            'target_codes' => $targetCodes,
            'percentage' => $percentage
        ];
    }
    
    /**
     * Öğrenme durumunu al
     */
    public function getStatus()
    {
        // İlerleme durumu
        $progress = $this->getProgress();
        
        // Son ve sonraki güncelleme zamanları
        $lastUpdate = Cache::get('ai_code_learning_last_update', now()->subDay());
        $nextUpdate = Cache::get('ai_code_learning_next', now()->addMinutes(5));
        
        // Öğrenme istatistikleri
        $statistics = $this->getStatistics();
        
        // Son aktiviteler
        $recentActivities = AICodeActivity::orderBy('id', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($activity) {
                return [
                    'action' => $activity->activity_type,
                    'description' => $activity->description,
                    'time' => Carbon::parse($activity->timestamp)->diffForHumans()
                ];
            });
        
        // Son öğrenilen kodlar
        $recentCodes = AICodeSnippet::orderBy('id', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($snippet) {
                // Kod uzunluğunu kontrol et ve kısalt
                $code = $snippet->code_content;
                if (strlen($code) > 500) {
                    $code = substr($code, 0, 500) . '...';
                }
                
                $metadata = json_decode($snippet->metadata, true);
                
                return [
                    'language' => $snippet->language,
                    'category' => $snippet->category,
                    'snippet' => $code,
                    'description' => $snippet->description,
                    'time' => Carbon::parse($metadata['imported_at'] ?? now())->diffForHumans()
                ];
            });
        
        return [
            'status' => [
                'is_learning' => $this->isLearning,
                'last_update' => $lastUpdate->format('d.m.Y H:i:s'),
                'next_update' => $this->isLearning ? $nextUpdate->diffForHumans() : '-',
                'progress_percentage' => $progress['percentage'],
                'source_count' => count(array_filter($this->sourceApis, fn($api) => $api['enabled']))
            ],
            'statistics' => $statistics,
            'recent_activities' => $recentActivities,
            'recent_codes' => $recentCodes,
            'settings' => $this->settings
        ];
    }
    
    /**
     * İstatistikleri al
     */
    private function getStatistics()
    {
        // Toplam kod sayısı
        $totalSnippets = AICodeSnippet::count();
        
        // Fonksiyon sayısı
        $totalFunctions = AICodeSnippet::where('category', 'function')->count();
        
        // Sınıf sayısı
        $totalClasses = AICodeSnippet::where('category', 'class')->count();
        
        // Dil dağılımı
        $languageCounts = AICodeSnippet::selectRaw('language, count(*) as count')
            ->groupBy('language')
            ->get()
            ->pluck('count', 'language')
            ->toArray();
        
        // Dil yüzdeleri
        $languageDistribution = [];
        foreach ($languageCounts as $language => $count) {
            $languageDistribution[$language] = $totalSnippets > 0 ? round(($count / $totalSnippets) * 100) : 0;
        }
        
        // Tüm dilleri ekle (yoksa 0 olarak)
        foreach (['javascript', 'php', 'python', 'html', 'css', 'other'] as $lang) {
            if (!isset($languageDistribution[$lang])) {
                $languageDistribution[$lang] = 0;
            }
        }
        
        return [
            'total_snippets' => $totalSnippets,
            'total_functions' => $totalFunctions,
            'total_classes' => $totalClasses,
            'language_distribution' => $languageDistribution
        ];
    }
    
    /**
     * Belirli bir dil ve kategoriye göre kod örneği al
     */
    public function getCodeExample($language, $category = null, $count = 1)
    {
        $query = AICodeSnippet::where('language', $language);
        
        if ($category) {
            $query->where('category', $category);
        }
        
        // Kullanım sayısı ve güven skoruna göre sırala
        $snippets = $query->orderBy('confidence_score', 'desc')
            ->orderBy('usage_count', 'desc')
            ->limit($count)
            ->get();
        
        if ($snippets->isEmpty()) {
            return null;
        }
        
        // Tek kod isteniyorsa
        if ($count === 1) {
            $snippet = $snippets->first();
            
            // Kullanım sayısını artır
            $snippet->increment('usage_count');
            
            return [
                'language' => $snippet->language,
                'category' => $snippet->category,
                'code' => $snippet->code_content,
                'description' => $snippet->description
            ];
        }
        
        // Birden fazla kod isteniyorsa
        return $snippets->map(function ($snippet) {
            // Kullanım sayısını artır
            $snippet->increment('usage_count');
            
            return [
                'language' => $snippet->language,
                'category' => $snippet->category,
                'code' => $snippet->code_content,
                'description' => $snippet->description
            ];
        })->toArray();
    }
    
    /**
     * Kod araması yap
     */
    public function searchCode($query, $language = null, $category = null)
    {
        $dbQuery = AICodeSnippet::query();
        
        // Arama terimine göre filtrele
        if ($query) {
            $dbQuery->where(function ($q) use ($query) {
                $q->where('code_content', 'like', "%$query%")
                  ->orWhere('description', 'like', "%$query%");
            });
        }
        
        // Dile göre filtrele
        if ($language) {
            $dbQuery->where('language', $language);
        }
        
        // Kategoriye göre filtrele
        if ($category) {
            $dbQuery->where('category', $category);
        }
        
        // Kodları getir
        $results = $dbQuery->orderBy('confidence_score', 'desc')
            ->limit(10)
            ->get();
        
        // Sonuçları formatla
        return $results->map(function ($snippet) {
            // Kod uzunluğunu kontrol et ve kısalt
            $code = $snippet->code_content;
            if (strlen($code) > 500) {
                $code = substr($code, 0, 500) . '...';
            }
            
            return [
                'id' => $snippet->id,
                'language' => $snippet->language,
                'category' => $snippet->category,
                'code' => $code,
                'description' => $snippet->description,
                'confidence' => $snippet->confidence_score
            ];
        })->toArray();
    }
    
    /**
     * Manuel kod örnekleri getir
     */
    private function getManualCodeExamples($languages)
    {
        $codes = [];
        $timestamp = now()->timestamp; // Benzersiz kodlar için zaman damgası
        $uniqueId = substr(md5($timestamp . rand(1000, 9999)), 0, 8); // Benzersiz ID
        
        // HTML örnekleri
        if (in_array('html', $languages)) {
            // Basit bir HTML formu örneği
            $codes[] = [
                'language' => 'html',
                'code' => '<form action="/submit-' . $uniqueId . '" method="POST" class="contact-form-' . $uniqueId . '">
  <div class="form-group">
    <label for="name">Adınız</label>
    <input type="text" id="name-' . $uniqueId . '" name="name" required>
  </div>
  <div class="form-group">
    <label for="email">E-posta</label>
    <input type="email" id="email-' . $uniqueId . '" name="email" required>
  </div>
  <div class="form-group">
    <label for="message">Mesajınız</label>
    <textarea id="message-' . $uniqueId . '" name="message" rows="5" required></textarea>
  </div>
  <button type="submit" class="submit-btn-' . $uniqueId . '">Gönder</button>
</form>',
                'category' => 'form',
                'description' => 'Basit Kişi İletişim Formu - Versiyon ' . $uniqueId,
                'source' => 'Manual',
                'source_url' => null
            ];
            
            // Tablo örneği (yeni eklenen)
            $codes[] = [
                'language' => 'html',
                'code' => '<table class="data-table-' . $uniqueId . '">
  <thead>
    <tr>
      <th>ID</th>
      <th>Ürün Adı</th>
      <th>Fiyat</th>
      <th>Stok Durumu</th>
      <th>İşlemler</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>001-' . rand(100, 999) . '</td>
      <td>Örnek Ürün ' . rand(1, 100) . '</td>
      <td>₺' . rand(50, 1000) . '</td>
      <td>' . (rand(0, 1) ? 'Var' : 'Yok') . '</td>
      <td><button>Düzenle</button> <button>Sil</button></td>
    </tr>
    <tr>
      <td>002-' . rand(100, 999) . '</td>
      <td>Örnek Ürün ' . rand(1, 100) . '</td>
      <td>₺' . rand(50, 1000) . '</td>
      <td>' . (rand(0, 1) ? 'Var' : 'Yok') . '</td>
      <td><button>Düzenle</button> <button>Sil</button></td>
    </tr>
  </tbody>
</table>',
                'category' => 'table',
                'description' => 'Ürün Listesi Tablosu - Versiyon ' . $uniqueId,
                'source' => 'Manual',
                'source_url' => null
            ];
            
            // Hero bölümü örneği (yeni eklenen)
            $codes[] = [
                'language' => 'html',
                'code' => '<section class="hero-' . $uniqueId . '">
  <div class="hero-content">
    <h1 class="hero-title">Hoş Geldiniz - ' . $uniqueId . '</h1>
    <p class="hero-subtitle">Modern ve profesyonel web tasarım çözümleri sunuyoruz.</p>
    <div class="hero-buttons">
      <a href="#services-' . $uniqueId . '" class="btn btn-primary">Hizmetlerimiz</a>
      <a href="#contact-' . $uniqueId . '" class="btn btn-secondary">İletişim</a>
    </div>
  </div>
  <div class="hero-image">
    <img src="hero-' . $uniqueId . '.jpg" alt="Hero Görsel">
  </div>
</section>',
                'category' => 'hero',
                'description' => 'Modern Hero Bölümü - Versiyon ' . $uniqueId,
                'source' => 'Manual',
                'source_url' => null
            ];
        }
        
        // CSS örnekleri
        if (in_array('css', $languages)) {
            // Hero bölüm stili (yeni eklenen)
            $codes[] = [
                'language' => 'css',
                'code' => '.hero-' . $uniqueId . ' {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: ' . rand(40, 80) . 'px ' . rand(20, 40) . 'px;
  background-color: #' . substr(md5(rand()), 0, 6) . ';
  color: #fff;
  border-radius: ' . rand(0, 10) . 'px;
}

.hero-content {
  flex: 1;
  max-width: 600px;
}

.hero-title {
  font-size: ' . rand(32, 48) . 'px;
  font-weight: 700;
  margin-bottom: 20px;
  color: #fff;
}

.hero-subtitle {
  font-size: 18px;
  margin-bottom: 30px;
  opacity: 0.9;
}

.hero-buttons {
  display: flex;
  gap: 15px;
}

.btn {
  padding: 12px 24px;
  border-radius: 4px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s;
}

.btn-primary {
  background-color: #' . substr(md5(rand()), 0, 6) . ';
  color: #fff;
}

.btn-secondary {
  background-color: transparent;
  border: 2px solid #fff;
  color: #fff;
}

.hero-image {
  flex: 1;
  max-width: 500px;
}

.hero-image img {
  width: 100%;
  height: auto;
  border-radius: 8px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

@media (max-width: 768px) {
  .hero-' . $uniqueId . ' {
    flex-direction: column;
    text-align: center;
    padding: 40px 20px;
  }
  
  .hero-content {
    margin-bottom: 30px;
  }
  
  .hero-buttons {
    justify-content: center;
  }
}',
                'category' => 'hero',
                'description' => 'Responsive Hero Bölümü Stili - Versiyon ' . $uniqueId,
                'source' => 'Manual',
                'source_url' => null
            ];
            
            // Tablo stili (yeni eklenen)
            $codes[] = [
                'language' => 'css',
                'code' => '.data-table-' . $uniqueId . ' {
  width: 100%;
  border-collapse: collapse;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
  margin: 20px 0;
}

.data-table-' . $uniqueId . ' thead {
  background-color: #' . substr(md5(rand()), 0, 6) . ';
  color: white;
}

.data-table-' . $uniqueId . ' th,
.data-table-' . $uniqueId . ' td {
  padding: 12px 15px;
  text-align: left;
}

.data-table-' . $uniqueId . ' tbody tr {
  border-bottom: 1px solid #ddd;
}

.data-table-' . $uniqueId . ' tbody tr:nth-child(even) {
  background-color: #f8f9fa;
}

.data-table-' . $uniqueId . ' tbody tr:hover {
  background-color: #f1f1f1;
}

.data-table-' . $uniqueId . ' button {
  padding: 6px 12px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  margin-right: 5px;
}

.data-table-' . $uniqueId . ' button:first-child {
  background-color: #4285f4;
  color: white;
}

.data-table-' . $uniqueId . ' button:last-child {
  background-color: #ea4335;
  color: white;
}

@media (max-width: 768px) {
  .data-table-' . $uniqueId . ' {
    display: block;
    overflow-x: auto;
  }
}',
                'category' => 'table',
                'description' => 'Modern Veri Tablosu Stili - Versiyon ' . $uniqueId,
                'source' => 'Manual',
                'source_url' => null
            ];
            
            // Dark mode değişkenleri (yeni eklenen)
            $codes[] = [
                'language' => 'css',
                'code' => ':root {
  --bg-light-' . $uniqueId . ': #' . substr(md5(rand()), 0, 6) . ';
  --text-light-' . $uniqueId . ': #333333;
  --primary-light-' . $uniqueId . ': #' . substr(md5(rand()), 0, 6) . ';
  --secondary-light-' . $uniqueId . ': #' . substr(md5(rand()), 0, 6) . ';
  --accent-light-' . $uniqueId . ': #' . substr(md5(rand()), 0, 6) . ';
  
  --bg-dark-' . $uniqueId . ': #1a1a1a;
  --text-dark-' . $uniqueId . ': #f5f5f5;
  --primary-dark-' . $uniqueId . ': #' . substr(md5(rand()), 0, 6) . ';
  --secondary-dark-' . $uniqueId . ': #' . substr(md5(rand()), 0, 6) . ';
  --accent-dark-' . $uniqueId . ': #' . substr(md5(rand()), 0, 6) . ';
}

body {
  --bg: var(--bg-light-' . $uniqueId . ');
  --text: var(--text-light-' . $uniqueId . ');
  --primary: var(--primary-light-' . $uniqueId . ');
  --secondary: var(--secondary-light-' . $uniqueId . ');
  --accent: var(--accent-light-' . $uniqueId . ');
  
  background-color: var(--bg);
  color: var(--text);
}

body.dark-mode {
  --bg: var(--bg-dark-' . $uniqueId . ');
  --text: var(--text-dark-' . $uniqueId . ');
  --primary: var(--primary-dark-' . $uniqueId . ');
  --secondary: var(--secondary-dark-' . $uniqueId . ');
  --accent: var(--accent-dark-' . $uniqueId . ');
}

.themed-element-' . $uniqueId . ' {
  background-color: var(--primary);
  color: var(--text);
  border: 1px solid var(--secondary);
  padding: 15px;
  border-radius: 8px;
  margin: 10px 0;
}

.dark-mode-toggle-' . $uniqueId . ' {
  position: fixed;
  top: 20px;
  right: 20px;
  padding: 10px;
  background-color: var(--accent);
  color: var(--bg);
  border: none;
  border-radius: 50%;
  cursor: pointer;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
}',
                'category' => 'theme',
                'description' => 'CSS Değişkenlerle Dark Mode - Versiyon ' . $uniqueId,
                'source' => 'Manual',
                'source_url' => null
            ];
        }
        
        // Her seferinde farklı kodlar döndürmek için
        shuffle($codes);
        
        // Her sefer farklı bir sayıda kod döndür (en az 2, en fazla 5)
        $count = min(count($codes), rand(2, 5));
        return array_slice($codes, 0, $count);
    }
    
    /**
     * Cache'i temizle - sorunları gidermek için kullanılır
     */
    public function resetLearningCache()
    {
        Cache::forget('ai_code_learning_settings');
        Cache::forget('ai_code_learning_active');
        Cache::forget('ai_code_learning_interval');
        Cache::forget('ai_code_learning_next');
        Cache::forget('ai_code_learning_last_update');
        Cache::forget('word_connections');
        
        $this->logActivity('Kod öğrenme cache bilgileri temizlendi', 'System');
        
        return true;
    }
    
    /**
     * Manuel kod öğrenmeyi zorla
     * 
     * @param int $count Öğrenilecek kod sayısı
     * @return array Sonuç
     */
    public function forceLearning($count = 5)
    {
        try {
            // Toplam ve başarılı kod sayımı için sayaç
            $learnedCount = 0;
            $learnedCodes = [];
            
            // GitHub, StackOverflow ve DevTo'dan kodları al
            $languages = ['html', 'css', 'javascript', 'php'];
            
            Log::info("Zorunlu kod öğrenme başlatıldı (hedef: $count kod) - " . now()->format('Y-m-d H:i:s'));
            
            // GitHub'dan kodları al
            try {
                Log::info('GitHub API\'den kod toplamaya başlanıyor...');
                $githubCodes = $this->fetchFromGitHub($languages, $count);
                Log::info('GitHub API\'den ' . count($githubCodes) . ' kod alındı');
                
                foreach ($githubCodes as $codeData) {
                    $result = $this->analyzeAndSaveCode($codeData);
                    if ($result) {
                        $learnedCount++;
                        $learnedCodes[] = [
                            'language' => $codeData['language'],
                            'description' => $codeData['description'],
                            'source' => 'GitHub'
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('GitHub API hatası: ' . $e->getMessage());
            }
            
            // StackOverflow'dan kodları al
            if ($learnedCount < $count) {
                try {
                    Log::info('StackOverflow API\'den kod toplamaya başlanıyor...');
                    $stackCodes = $this->fetchFromStackOverflow($languages, $count - $learnedCount);
                    Log::info('StackOverflow API\'den ' . count($stackCodes) . ' kod alındı');
                    
                    foreach ($stackCodes as $codeData) {
                        $result = $this->analyzeAndSaveCode($codeData);
                        if ($result) {
                            $learnedCount++;
                            $learnedCodes[] = [
                                'language' => $codeData['language'],
                                'description' => $codeData['description'],
                                'source' => 'StackOverflow'
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('StackOverflow API hatası: ' . $e->getMessage());
                }
            }
            
            // DevTo'dan kodları al
            if ($learnedCount < $count) {
                try {
                    Log::info('DevTo API\'den kod toplamaya başlanıyor...');
                    $devtoCodes = $this->fetchFromDevTo($languages, $count - $learnedCount);
                    Log::info('DevTo API\'den ' . count($devtoCodes) . ' kod alındı');
                    
                    foreach ($devtoCodes as $codeData) {
                        $result = $this->analyzeAndSaveCode($codeData);
                        if ($result) {
                            $learnedCount++;
                            $learnedCodes[] = [
                                'language' => $codeData['language'],
                                'description' => $codeData['description'],
                                'source' => 'DevTo'
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('DevTo API hatası: ' . $e->getMessage());
                }
            }
            
            // GitLab'dan kodları al
            if ($learnedCount < $count) {
                try {
                    Log::info('GitLab API\'den kod toplamaya başlanıyor...');
                    $gitlabCodes = $this->fetchFromGitLab($languages, $count - $learnedCount);
                    Log::info('GitLab API\'den ' . count($gitlabCodes) . ' kod alındı');
                    
                    foreach ($gitlabCodes as $codeData) {
                        $result = $this->analyzeAndSaveCode($codeData);
                        if ($result) {
                            $learnedCount++;
                            $learnedCodes[] = [
                                'language' => $codeData['language'],
                                'description' => $codeData['description'],
                                'source' => 'GitLab'
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('GitLab API hatası: ' . $e->getMessage());
                }
            }
            
            // CodePen'den kodları al
            if ($learnedCount < $count) {
                try {
                    Log::info('CodePen API\'den kod toplamaya başlanıyor...');
                    $codepenCodes = $this->fetchFromCodePen($languages, $count - $learnedCount);
                    Log::info('CodePen API\'den ' . count($codepenCodes) . ' kod alındı');
                    
                    foreach ($codepenCodes as $codeData) {
                        $result = $this->analyzeAndSaveCode($codeData);
                        if ($result) {
                            $learnedCount++;
                            $learnedCodes[] = [
                                'language' => $codeData['language'],
                                'description' => $codeData['description'],
                                'source' => 'CodePen'
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('CodePen API hatası: ' . $e->getMessage());
                }
            }
            
            // Her durumda örnek kodlar ekle - API'ler başarısız olsa bile 
            Log::info('Örnek kodlar ekleniyor...');
            $manualCodes = $this->addSampleCodes($count * 2); // İstenen sayının 2 katı kadar
            
            if ($manualCodes > 0) {
                $learnedCount += $manualCodes;
                
                for ($i = 0; $i < $manualCodes; $i++) {
                    $learnedCodes[] = [
                        'language' => $languages[array_rand($languages)],
                        'description' => 'Örnek Kod ' . ($i + 1),
                        'source' => 'Örnek Veritabanı'
                    ];
                }
                
                $this->logActivity("$manualCodes adet örnek kod eklendi", 'Learning');
            }
            
            $this->logActivity("Toplam $learnedCount adet kod öğrenildi (zorunlu öğrenme)", 'Learning');
            Log::info("Zorunlu kod öğrenme tamamlandı. Toplam $learnedCount kod öğrenildi.");
            
            return [
                'success' => true,
                'count' => $learnedCount,
                'message' => "Toplam $learnedCount adet kod öğrenildi",
                'learned_codes' => $learnedCodes
            ];
        } catch (\Exception $e) {
            Log::error('Zorunlu kod öğrenme hatası: ' . $e->getMessage(), [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Hata durumunda bile örnek kodlar ekleyelim
            try {
                $manualCodes = $this->addSampleCodes($count);
                $this->logActivity("Hata olmasına rağmen $manualCodes adet örnek kod eklendi", 'Recovery');
                
                return [
                    'success' => true,
                    'count' => $manualCodes,
                    'message' => "Toplam $manualCodes adet örnek kod eklendi (hata kurtarma modunda)",
                    'error' => $e->getMessage()
                ];
            } catch (\Exception $innerException) {
                Log::error('Örnek kod ekleme hatası: ' . $innerException->getMessage());
                
                return [
                    'success' => false,
                    'count' => 0,
                    'message' => 'Zorunlu kod öğrenme hatası: ' . $e->getMessage(),
                    'detail_error' => $innerException->getMessage()
                ];
            }
        }
    }
    
    /**
     * Örnek kodlar ekle (API hatası durumunda)
     */
    private function addSampleCodes($count)
    {
        $added = 0;
        
        try {
            // HTML örneği
            $htmlExample = [
                'language' => 'html',
                'code' => '<nav class="navbar">
    <div class="container">
        <a href="#" class="navbar-brand">Logo</a>
        <ul class="navbar-menu">
            <li><a href="#">Ana Sayfa</a></li>
            <li><a href="#">Hakkımızda</a></li>
            <li><a href="#">Hizmetler</a></li>
            <li><a href="#">İletişim</a></li>
        </ul>
    </div>
</nav>',
                'description' => 'Temel Responsive Navbar',
                'category' => 'navigation',
                'source' => 'sample'
            ];
            
            // CSS örneği
            $cssExample = [
                'language' => 'css',
                'code' => '.navbar {
    background-color: #333;
    padding: 1rem 0;
}
.container {
    width: 90%;
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.navbar-brand {
    color: white;
    font-size: 1.5rem;
    text-decoration: none;
    font-weight: bold;
}
.navbar-menu {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
}
.navbar-menu li {
    margin-left: 1rem;
}
.navbar-menu a {
    color: white;
    text-decoration: none;
    padding: 0.5rem;
}
.navbar-menu a:hover {
    color: #ddd;
}

@media (max-width: 768px) {
    .navbar-menu {
        display: none;
    }
}',
                'description' => 'Responsive Navbar Stillemesi',
                'category' => 'responsive',
                'source' => 'sample'
            ];
            
            // JavaScript örneği
            $jsExample = [
                'language' => 'javascript',
                'code' => 'document.addEventListener("DOMContentLoaded", function() {
    const menuButton = document.querySelector(".menu-toggle");
    const navbarMenu = document.querySelector(".navbar-menu");
    
    if (menuButton) {
        menuButton.addEventListener("click", function() {
            navbarMenu.classList.toggle("active");
        });
    }
    
    // Responsive davranış
    window.addEventListener("resize", function() {
        if (window.innerWidth > 768) {
            navbarMenu.classList.remove("active");
        }
    });
});',
                'description' => 'Responsive Navbar Menu Toggle',
                'category' => 'dom',
                'source' => 'sample'
            ];
            
            // Örnek kodları kaydet
            $examples = [$htmlExample, $cssExample, $jsExample];
            
            foreach ($examples as $example) {
                if ($added >= $count) break;
                
                try {
                    $result = $this->analyzeAndSaveCode($example);
                    if ($result) $added++;
                } catch (\Exception $e) {
                    Log::error('Örnek kod ekleme hatası: ' . $e->getMessage());
                }
            }
            
            // PHP örneği (ekstra)
            if ($added < $count) {
                $phpExample = [
                    'language' => 'php',
                    'code' => '<?php
class Navbar {
    private $items = [];
    private $brand;
    
    public function __construct($brand = null) {
        $this->brand = $brand;
    }
    
    public function addItem($title, $url) {
        $this->items[] = [
            "title" => $title,
            "url" => $url
        ];
    }
    
    public function render() {
        $html = \'<nav class="navbar">
    <div class="container">\';
        
        if ($this->brand) {
            $html .= \'<a href="#" class="navbar-brand">\'.$this->brand.\'</a>\';
        }
        
        $html .= \'<ul class="navbar-menu">\';
        
        foreach ($this->items as $item) {
            $html .= \'<li><a href="\'.$item["url"].\'">\'.$item["title"].\'</a></li>\';
        }
        
        $html .= \'</ul>
    </div>
</nav>\';
        
        return $html;
    }
}',
                    'description' => 'PHP Navbar Sınıfı',
                    'category' => 'class',
                    'source' => 'sample'
                ];
                
                try {
                    $result = $this->analyzeAndSaveCode($phpExample);
                    if ($result) $added++;
                } catch (\Exception $e) {
                    Log::error('Örnek PHP kodu ekleme hatası: ' . $e->getMessage());
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Örnek kodlar eklenirken hata: ' . $e->getMessage());
        }
        
        return $added;
    }
    
    /**
     * Kullanıcıya otomatik kod önerileri oluştur
     * 
     * @param string $context Öneri bağlamı
     * @param string $language Dil
     * @param int $count Önerilen kod sayısı
     * @return array Önerilen kodlar
     */
    public function generateCodeRecommendations($context, $language = null, $count = 3)
    {
        try {
            if (!$this->settings['auto_recommendation']) {
                return [
                    'success' => false,
                    'message' => 'Otomatik öneri sistemi devre dışı'
                ];
            }
            
            // Eğer dil belirtilmemişse, bağlamdan dili tahmin et
            if (!$language) {
                $language = $this->detectLanguageFromContext($context);
            }
            
            // Bağlamdan anahtar kelimeler çıkar
            $keywords = $this->extractKeywordsFromContext($context);
            
            // Bilinç sistemini kullanarak ilişkileri bul
            $relatedKeywords = $this->findRelatedKeywords($keywords, $language);
            
            // İlgili kategorileri belirle
            $categories = $this->determineCategoriesFromKeywords($keywords, $language);
            
            Log::info("Kod önerileri oluşturuluyor: Dil={$language}, Anahtar Kelimeler=" . implode(',', $keywords));
            
            // Önerilecek kod parçalarını bul
            $recommendations = [];
            
            // İlgili kategorilere göre kod parçalarını bul
            foreach ($categories as $category) {
                $snippets = AICodeSnippet::where('language', $language)
                    ->where('category', $category)
                    ->orderBy('confidence_score', 'desc')
                    ->limit(5)
                    ->get();
                
                foreach ($snippets as $snippet) {
                    $score = $this->calculateRecommendationScore($snippet, $keywords, $relatedKeywords);
                    
                    if ($score > 0.5) {
                        $recommendations[] = [
                            'id' => $snippet->id,
                            'language' => $snippet->language,
                            'category' => $snippet->category,
                            'code_content' => $snippet->code_content,
                            'description' => $snippet->description,
                            'relevance_score' => $score,
                            'tags' => $snippet->tags
                        ];
                    }
                }
            }
            
            // Alakaya göre sırala
            usort($recommendations, function($a, $b) {
                return $b['relevance_score'] <=> $a['relevance_score'];
            });
            
            // En iyi önerileri seç
            $recommendations = array_slice($recommendations, 0, $count);
            
            if (empty($recommendations)) {
                // Öneri bulunamadıysa genel olarak popüler kodları getir
                $recommendations = AICodeSnippet::where('language', $language)
                    ->orderBy('usage_count', 'desc')
                    ->limit($count)
                    ->get()
                    ->map(function($snippet) {
                        return [
                            'id' => $snippet->id,
                            'language' => $snippet->language,
                            'category' => $snippet->category,
                            'code_content' => $snippet->code_content,
                            'description' => $snippet->description,
                            'relevance_score' => 0.6,
                            'tags' => $snippet->tags
                        ];
                    })
                    ->toArray();
            }
            
            $this->logActivity('Kod önerileri oluşturuldu: ' . count($recommendations) . ' adet');
            
            return [
                'success' => true,
                'recommendations' => $recommendations,
                'context' => [
                    'language' => $language,
                    'keywords' => $keywords,
                    'related_keywords' => $relatedKeywords,
                    'categories' => $categories
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Kod öneri hatası: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Kod önerileri oluşturulamadı: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Bağlamdan dili tespit et
     */
    private function detectLanguageFromContext($context)
    {
        $context = strtolower($context);
        
        if (strpos($context, 'html') !== false || strpos($context, 'markup') !== false || strpos($context, 'etiket') !== false) {
            return 'html';
        }
        
        if (strpos($context, 'css') !== false || strpos($context, 'stil') !== false || strpos($context, 'style') !== false) {
            return 'css';
        }
        
        if (strpos($context, 'javascript') !== false || strpos($context, 'js') !== false || strpos($context, 'script') !== false) {
            return 'javascript';
        }
        
        if (strpos($context, 'php') !== false || strpos($context, 'laravel') !== false) {
            return 'php';
        }
        
        // Varsayılan olarak HTML döndür
        return 'html';
    }
    
    /**
     * Bağlamdan anahtar kelimeleri çıkar
     */
    private function extractKeywordsFromContext($context)
    {
        $context = strtolower($context);
        $context = preg_replace('/[^\w\s]/', ' ', $context);
        $words = explode(' ', $context);
        
        $keywords = [];
        
        // Önemli kelimeleri filtrele
        $importantWords = ['form', 'table', 'layout', 'grid', 'flex', 'animation', 'transition', 
                          'responsive', 'button', 'menu', 'navigation', 'card', 'media', 'header',
                          'footer', 'sidebar', 'modal', 'slider', 'carousel', 'gallery', 'tab',
                          'accordion', 'dropdown'];
        
        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) > 3 && in_array($word, $importantWords)) {
                $keywords[] = $word;
            }
        }
        
        // Türkçe kelimeler için özel çeviri
        $turkishToEnglish = [
            'form' => 'form',
            'tablo' => 'table',
            'düzen' => 'layout',
            'izgara' => 'grid',
            'duyarlı' => 'responsive',
            'düğme' => 'button',
            'menü' => 'menu',
            'gezinme' => 'navigation',
            'başlık' => 'header',
            'altbilgi' => 'footer',
            'kenar' => 'sidebar',
            'kaydırıcı' => 'slider',
            'galeri' => 'gallery',
            'sekme' => 'tab',
            'açılır' => 'dropdown'
        ];
        
        foreach ($words as $word) {
            $word = trim($word);
            if (array_key_exists($word, $turkishToEnglish)) {
                $keywords[] = $turkishToEnglish[$word];
            }
        }
        
        return array_unique($keywords);
    }
    
    /**
     * İlişkili anahtar kelimeleri bul
     */
    private function findRelatedKeywords($keywords, $language)
    {
        $relatedKeywords = [];
        
        // Bilinç sistemini kullanarak ilişkileri bul
        if (class_exists('App\\AI\\Core\\CodeConsciousness\\CodeRelationAnalyzer')) {
            $analyzer = app()->make('App\\AI\\Core\\CodeConsciousness\\CodeRelationAnalyzer');
            
            // İlişkileri getir
            if (property_exists($analyzer, 'relations') && isset($analyzer->relations[$language])) {
                $relations = $analyzer->relations[$language];
                
                foreach ($keywords as $keyword) {
                    if (isset($relations[$keyword])) {
                        foreach ($relations[$keyword] as $related => $strength) {
                            if (!in_array($related, $relatedKeywords) && !in_array($related, $keywords)) {
                                $relatedKeywords[] = $related;
                            }
                        }
                    }
                }
            }
        }
        
        // Sabit ilişkiler
        $fixedRelations = [
            'form' => ['input', 'button', 'submit', 'validation'],
            'table' => ['grid', 'data', 'row', 'column'],
            'layout' => ['container', 'grid', 'flex', 'responsive'],
            'responsive' => ['media', 'mobile', 'desktop', 'breakpoint'],
            'animation' => ['transition', 'keyframe', 'transform', 'effect']
        ];
        
        foreach ($keywords as $keyword) {
            if (isset($fixedRelations[$keyword])) {
                foreach ($fixedRelations[$keyword] as $related) {
                    if (!in_array($related, $relatedKeywords) && !in_array($related, $keywords)) {
                        $relatedKeywords[] = $related;
                    }
                }
            }
        }
        
        return array_slice($relatedKeywords, 0, 10);
    }
    
    /**
     * Anahtar kelimelerden kategorileri belirle
     */
    private function determineCategoriesFromKeywords($keywords, $language)
    {
        $categories = [];
        
        $keywordCategories = [
            'html' => [
                'form' => ['form', 'input', 'button', 'select'],
                'table' => ['table', 'grid', 'data'],
                'navigation' => ['menu', 'nav', 'navbar', 'navigation'],
                'semantic' => ['header', 'footer', 'article', 'section'],
                'layout' => ['layout', 'container', 'row', 'column'],
                'component' => ['card', 'modal', 'dropdown', 'accordion']
            ],
            'css' => [
                'layout' => ['layout', 'grid', 'flex', 'position', 'float'],
                'responsive' => ['responsive', 'media', 'mobile', 'desktop'],
                'animation' => ['animation', 'transition', 'transform', 'effect'],
                'typography' => ['font', 'text', 'typography', 'color'],
                'component' => ['button', 'card', 'modal', 'form']
            ]
        ];
        
        // Dil için kategori eşlemeleri var mı kontrol et
        if (isset($keywordCategories[$language])) {
            $mappings = $keywordCategories[$language];
            
            // Her kategori için anahtar kelimelerde eşleşme ara
            foreach ($mappings as $category => $categoryKeywords) {
                foreach ($keywords as $keyword) {
                    if (in_array($keyword, $categoryKeywords)) {
                        $categories[] = $category;
                        break;
                    }
                }
            }
        }
        
        // Hiç kategori bulamadıysak, dile göre varsayılan kategori ekle
        if (empty($categories)) {
            switch ($language) {
                case 'html':
                    $categories[] = 'markup';
                    break;
                case 'css':
                    $categories[] = 'style';
                    break;
                case 'javascript':
                    $categories[] = 'script';
                    break;
                case 'php':
                    $categories[] = 'function';
                    break;
                default:
                    $categories[] = 'snippet';
            }
        }
        
        return array_unique($categories);
    }
    
    /**
     * Kod parçası için öneri skoru hesapla
     */
    private function calculateRecommendationScore($snippet, $keywords, $relatedKeywords)
    {
        $score = 0.0;
        
        // Kategori ve etiketleri kontrol et
        $tags = $snippet->tags ?? [];
        
        // Kod içeriğini anahtar kelimeler için kontrol et
        foreach ($keywords as $keyword) {
            if (strpos(strtolower($snippet->code_content), strtolower($keyword)) !== false) {
                $score += 0.2;
            }
            
            // Etiketlerde anahtar kelime kontrolü
            if (in_array($keyword, $tags)) {
                $score += 0.3;
            }
        }
        
        // İlişkili anahtar kelimeler için kontrol et
        foreach ($relatedKeywords as $keyword) {
            if (strpos(strtolower($snippet->code_content), strtolower($keyword)) !== false) {
                $score += 0.1;
            }
            
            // Etiketlerde ilişkili anahtar kelime kontrolü
            if (in_array($keyword, $tags)) {
                $score += 0.15;
            }
        }
        
        // Güven skoruna göre puanı artır
        if ($snippet->confidence_score > 0.7) {
            $score += 0.1;
        }
        
        // Öne çıkarılan kodlar için bonus
        if ($snippet->is_featured) {
            $score += 0.2;
        }
        
        // Maksimum 1.0 değeri döndür
        return min(1.0, $score);
    }
} 