<?php

namespace App\AI\Core;

use App\Models\AICodeSnippet;
use App\Models\AICodeActivity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CodeUsagePredictorService
{
    // Kullanım analitiği için eşikler
    const POPULAR_USAGE_THRESHOLD = 5; // Popüler kod eşiği
    const TRENDING_DAYS = 7; // Son 7 günlük trend
    const EFFECTIVENESS_THRESHOLD = 0.7; // En az %70 etkinliği olan kodlar
    
    // Kod kullanım bağlamları
    const CONTEXT_WEB_FRONTEND = 'web_frontend';
    const CONTEXT_WEB_BACKEND = 'web_backend';
    const CONTEXT_MOBILE = 'mobile';
    const CONTEXT_DESKTOP = 'desktop';
    const CONTEXT_DATA_ANALYTICS = 'data_analytics';
    
    // Kullanım zamanı
    private $timeBasedRecommendations = [
        'morning' => ['layout', 'structure', 'planning'],
        'afternoon' => ['implementation', 'functionality', 'components'],
        'evening' => ['debugging', 'optimization', 'styling']
    ];
    
    /**
     * En uygun kodları öner
     * 
     * @param string $language Programlama dili
     * @param string $category Kategori (opsiyonel)
     * @param string $context Kullanım bağlamı (opsiyonel)
     * @param int $limit Sonuç limiti
     * @return array Önerilen kodlar listesi
     */
    public function suggestCodes($language, $category = null, $context = null, $limit = 5)
    {
        Log::info("Kod önerisi isteniyor: $language, $category, $context");
        
        $query = AICodeSnippet::where('language', $language);
        
        if ($category) {
            // Ana kategori veya alt kategori olarak arama
            $query->where(function($q) use ($category) {
                $q->where('category', $category)
                  ->orWhereJsonContains('tags', $category);
            });
        }
        
        // Gün içindeki zamana göre kod seçimi
        $currentTime = Carbon::now();
        $hour = $currentTime->hour;
        
        $timeContext = $this->getTimeContext($hour);
        $relevantCategories = $this->timeBasedRecommendations[$timeContext] ?? [];
        
        if (!empty($relevantCategories)) {
            $query->where(function($q) use ($relevantCategories) {
                foreach ($relevantCategories as $cat) {
                    $q->orWhere('category', 'like', "%$cat%")
                      ->orWhereJsonContains('tags', $cat);
                }
            });
        }
        
        // Kullanım bağlamına göre filtrele
        if ($context) {
            $query->where(function($q) use ($context) {
                $q->whereHas('activities', function($q) use ($context) {
                    $q->where('usage_context', $context);
                });
            });
        }
        
        // Popülerlik ve kullanım etkinliğine göre sırala
        $query->orderBy('usage_count', 'desc')
              ->orderBy('confidence_score', 'desc')
              ->orderBy('id', 'desc');
              
        // Sonuçları getir
        $suggestions = $query->limit($limit)->get();
        
        // Sonuçlar için analitik bilgisi ekle
        $suggestionsWithAnalytics = [];
        foreach ($suggestions as $code) {
            $suggestionsWithAnalytics[] = [
                'code' => $code,
                'analytics' => $this->getCodeAnalytics($code),
                'usage_recommendation' => $this->generateUsageRecommendation($code)
            ];
        }
        
        return $suggestionsWithAnalytics;
    }
    
    /**
     * Şu anki saat dilimini al
     */
    private function getTimeContext($hour)
    {
        if ($hour >= 5 && $hour < 12) {
            return 'morning';
        } elseif ($hour >= 12 && $hour < 18) {
            return 'afternoon';
        } else {
            return 'evening';
        }
    }
    
    /**
     * Kod analitik bilgilerini getir
     */
    private function getCodeAnalytics(AICodeSnippet $code)
    {
        // Kod kullanım sıklığı
        $usageFrequency = $code->usage_count;
        
        // Son kullanım zamanı
        $lastUsage = $code->last_used_at 
            ? Carbon::parse($code->last_used_at)->diffForHumans() 
            : 'Hiç kullanılmadı';
        
        // Son 7 günlük trend
        $weeklyUsage = AICodeActivity::where('code_snippet_id', $code->id)
            ->where('created_at', '>=', Carbon::now()->subDays(self::TRENDING_DAYS))
            ->count();
        
        // Etkinlik skoru
        $effectiveness = $code->activities()
            ->where('effectiveness_score', '>=', self::EFFECTIVENESS_THRESHOLD)
            ->avg('effectiveness_score') ?? 0;
            
        return [
            'usage_frequency' => $usageFrequency,
            'last_usage' => $lastUsage,
            'weekly_trend' => $weeklyUsage,
            'effectiveness' => round($effectiveness * 100) . '%',
            'is_popular' => $usageFrequency >= self::POPULAR_USAGE_THRESHOLD,
            'is_trending' => $weeklyUsage > ($usageFrequency / 4), // Son hafta toplam kullanımın en az 1/4'ü kadarsa trend kabul et
        ];
    }
    
    /**
     * Kod için kullanım tavsiyesi oluştur
     */
    private function generateUsageRecommendation(AICodeSnippet $code)
    {
        $category = $code->category;
        $language = $code->language;
        $tags = $code->tags ?? [];
        
        $recommendation = [];
        
        // Kategori temelli tavsiyeler
        switch ($category) {
            case 'layout':
                $recommendation[] = "Bu kodu sayfa yapısını oluştururken kullanabilirsiniz.";
                break;
                
            case 'component':
                $recommendation[] = "Bu kodu tekrar kullanılabilir UI bileşeni olarak kullanabilirsiniz.";
                break;
                
            case 'animation':
                $recommendation[] = "Bu kodu kullanıcı deneyimini iyileştirmek için arayüz animasyonlarında kullanabilirsiniz.";
                break;
                
            case 'function':
                $recommendation[] = "Bu kodu veri işleme ve dönüştürme işlemlerinde kullanabilirsiniz.";
                break;
                
            case 'api':
                $recommendation[] = "Bu kodu harici servislerle iletişim kurarken kullanabilirsiniz.";
                break;
                
            case 'responsive':
                $recommendation[] = "Bu kodu mobil ve tablet uyumlu tasarımlar için kullanabilirsiniz.";
                break;
        }
        
        // Dil temelli tavsiyeler
        switch ($language) {
            case 'html':
                $recommendation[] = "Web sayfasının yapısını oluştururken kullanın.";
                break;
                
            case 'css':
                $recommendation[] = "Görsel stillemeler ve düzen için kullanın.";
                break;
                
            case 'javascript':
                $recommendation[] = "Sayfa etkileşimleri ve dinamik içerik için kullanın.";
                break;
                
            case 'php':
                $recommendation[] = "Sunucu taraflı işlemler için kullanın.";
                break;
        }
        
        // Etiketlere bağlı tavsiyeler
        if (is_array($tags)) {
            if (in_array('form', $tags)) {
                $recommendation[] = "Kullanıcı veri girişi formlarında kullanılabilir.";
            }
            
            if (in_array('animation', $tags)) {
                $recommendation[] = "Dikkat çekmek istediğiniz öğelerde kullanılabilir.";
            }
            
            if (in_array('responsive', $tags)) {
                $recommendation[] = "Farklı ekran boyutlarına uyumlu tasarım için kullanılabilir.";
            }
        }
        
        // En ideal kullanım zamanı
        $idealTime = $this->predictIdealUsageTime($code);
        if ($idealTime) {
            $recommendation[] = "En ideal kullanım zamanı: $idealTime";
        }
        
        // Birlikte kullanılacak kodlar
        $companionCodes = $this->findCompanionCodes($code);
        if (!empty($companionCodes)) {
            $recommendation[] = "Bu kodla birlikte şu kodları da kullanabilirsiniz: " . implode(', ', $companionCodes);
        }
        
        return $recommendation;
    }
    
    /**
     * Kod için en ideal kullanım zamanını tahmin et
     */
    private function predictIdealUsageTime(AICodeSnippet $code)
    {
        $category = $code->category;
        
        // Sabah için: Düzen, planlama, yapısal kodlar
        if (in_array($category, ['layout', 'structure'])) {
            return 'Sabah saatleri (planlama ve yapılandırma için)';
        }
        
        // Öğleden sonra için: Uygulama, işlevsel kodlar
        if (in_array($category, ['function', 'component', 'api'])) {
            return 'Öğleden sonra (uygulama geliştirme ve test için)';
        }
        
        // Akşam için: İyileştirme, düzeltme, stillleme
        if (in_array($category, ['animation', 'responsive', 'utility'])) {
            return 'Akşam saatleri (iyileştirme ve düzenleme için)';
        }
        
        return null;
    }
    
    /**
     * Bu kodla birlikte kullanılacak diğer kodları bul
     */
    private function findCompanionCodes(AICodeSnippet $code)
    {
        $companions = [];
        
        // Kategori ve dil kombinasyonuna göre birleştirilebilecek kodlar
        switch ($code->language) {
            case 'html':
                // HTML ile CSS ve JavaScript genellikle birlikte kullanılır
                $companions[] = 'CSS';
                $companions[] = 'JavaScript';
                break;
                
            case 'css':
                // CSS ile HTML birlikte kullanılır
                $companions[] = 'HTML';
                break;
                
            case 'javascript':
                // JavaScript ile HTML genellikle birlikte kullanılır
                $companions[] = 'HTML';
                
                // API çağrıları, Ajax ve form işlemlerine özel tavsiyeler
                if ($code->category === 'api') {
                    $companions[] = 'API istek işleyicileri';
                }
                
                if ($code->category === 'form') {
                    $companions[] = 'Form doğrulama kodu';
                }
                break;
                
            case 'php':
                // PHP için veritabanı, API veya form işleme kodları
                if ($code->category === 'database') {
                    $companions[] = 'Veritabanı işleme sınıfları';
                }
                
                if ($code->category === 'api') {
                    $companions[] = 'API yanıt formatlayıcıları';
                }
                break;
        }
        
        return $companions;
    }
    
    /**
     * Trende göre etkinlik skorlarını analiz eder
     */
    public function analyzeEffectivenessScores($days = 30, $limit = 100)
    {
        Log::info("Kod etkinlik skorları analiz ediliyor (son $days gün, limit: $limit)");
        
        $activities = AICodeActivity::where('created_at', '>=', Carbon::now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
            
        $codeEffectiveness = [];
        
        foreach ($activities as $activity) {
            $codeId = $activity->code_snippet_id;
            
            if (!isset($codeEffectiveness[$codeId])) {
                $codeEffectiveness[$codeId] = [
                    'total_score' => 0,
                    'count' => 0,
                    'contexts' => [],
                    'languages' => []
                ];
            }
            
            $codeEffectiveness[$codeId]['total_score'] += $activity->effectiveness_score ?? 0;
            $codeEffectiveness[$codeId]['count']++;
            
            // Kullanım bağlamlarını topla
            if ($activity->usage_context) {
                if (!isset($codeEffectiveness[$codeId]['contexts'][$activity->usage_context])) {
                    $codeEffectiveness[$codeId]['contexts'][$activity->usage_context] = 0;
                }
                $codeEffectiveness[$codeId]['contexts'][$activity->usage_context]++;
            }
            
            // İlgili dilleri topla
            if ($activity->related_languages && is_array($activity->related_languages)) {
                foreach ($activity->related_languages as $lang) {
                    if (!isset($codeEffectiveness[$codeId]['languages'][$lang])) {
                        $codeEffectiveness[$codeId]['languages'][$lang] = 0;
                    }
                    $codeEffectiveness[$codeId]['languages'][$lang]++;
                }
            }
        }
        
        // Sonuçları işle ve kodları güncelle
        foreach ($codeEffectiveness as $codeId => $data) {
            if ($data['count'] > 0) {
                $avgScore = $data['total_score'] / $data['count'];
                
                try {
                    $code = AICodeSnippet::find($codeId);
                    if ($code) {
                        // En sık kullanılan bağlamı bul
                        $mostUsedContext = !empty($data['contexts']) 
                            ? array_search(max($data['contexts']), $data['contexts']) 
                            : null;
                            
                        // En sık birlikte kullanılan dili bul
                        $mostRelatedLanguage = !empty($data['languages']) 
                            ? array_search(max($data['languages']), $data['languages']) 
                            : null;
                        
                        // Kodu güncelle
                        $code->effectiveness_score = $avgScore;
                        $code->most_used_context = $mostUsedContext;
                        $code->most_related_language = $mostRelatedLanguage;
                        $code->save();
                    }
                } catch (\Exception $e) {
                    Log::error('Kod etkinlik skoru güncellenemedi: ' . $e->getMessage());
                }
            }
        }
        
        Log::info("Kod etkinlik skorları analizi tamamlandı. " . count($codeEffectiveness) . " kod analiz edildi.");
        
        return count($codeEffectiveness);
    }
    
    /**
     * Etkin kod akışları önerir (birbirini tamamlayan kodlar)
     */
    public function suggestCodeFlow($startCategory, $language, $steps = 3)
    {
        $flow = [];
        $currentCategory = $startCategory;
        
        // Kodlama akışındaki adımlar
        $flowSteps = [
            'layout' => 'component',
            'component' => 'animation',
            'animation' => 'responsive',
            'responsive' => 'function',
            'function' => 'api',
            'api' => 'database',
            'database' => 'utility',
            'utility' => 'layout',
            'form' => 'function'
        ];
        
        // Her adım için en uygun kodu bul
        for ($i = 0; $i < $steps; $i++) {
            // Mevcut kategori için en iyi kodu bul
            $code = AICodeSnippet::where('language', $language)
                ->where('category', $currentCategory)
                ->orderBy('usage_count', 'desc')
                ->orderBy('effectiveness_score', 'desc')
                ->first();
                
            if ($code) {
                $flow[] = [
                    'step' => $i + 1,
                    'category' => $currentCategory,
                    'code' => $code,
                    'description' => $this->generateStepDescription($currentCategory, $i)
                ];
                
                // Bir sonraki kategoriye geç
                $currentCategory = $flowSteps[$currentCategory] ?? array_rand($flowSteps);
            } else {
                // Kategori için kod bulunamadıysa farklı bir kategori dene
                $currentCategory = array_rand($flowSteps);
                $i--; // Bu adımı tekrar dene
            }
        }
        
        return $flow;
    }
    
    /**
     * Akış adımı açıklaması oluştur
     */
    private function generateStepDescription($category, $stepIndex)
    {
        $descriptions = [
            'layout' => [
                'Sayfanın temel düzenini oluşturun',
                'Bölümleri yerleştirin',
                'Genel sayfa yapısını kurun'
            ],
            'component' => [
                'UI bileşenlerini ekleyin',
                'Yeniden kullanılabilir parçaları oluşturun',
                'Kullanıcı arayüzü öğelerini yerleştirin'
            ],
            'animation' => [
                'Kullanıcı deneyimini zenginleştirmek için animasyon ekleyin',
                'Geçiş efektleriyle sayfayı canlandırın',
                'Dikkat çekmek istediğiniz öğelere hareket katın'
            ],
            'responsive' => [
                'Farklı ekran boyutlarına uyumlu hale getirin',
                'Mobil görünümü optimize edin',
                'Duyarlı tasarım uygulamaları ekleyin'
            ],
            'function' => [
                'İşlevsel kod yapıları ekleyin',
                'Veri işleme mantığını oluşturun',
                'Uygulama davranışlarını kodlayın'
            ],
            'api' => [
                'Harici servislerle iletişim kurun',
                'API isteklerini yapılandırın',
                'Veri alışverişi sağlayın'
            ],
            'database' => [
                'Veritabanı işlemlerini ekleyin',
                'Veri depolama mekanizmalarını oluşturun',
                'Veri kalıcılığını sağlayın'
            ],
            'utility' => [
                'Yardımcı fonksiyonlar ekleyin',
                'Genel amaçlı kodları yerleştirin',
                'Destek sınıflarını oluşturun'
            ],
            'form' => [
                'Kullanıcı veri girişi formlarını ekleyin',
                'Form doğrulama mekanizmalarını kurun',
                'Kullanıcı girdilerini işleyin'
            ]
        ];
        
        if (isset($descriptions[$category])) {
            $options = $descriptions[$category];
            return $options[$stepIndex % count($options)];
        }
        
        return "Adım " . ($stepIndex + 1) . ": " . ucfirst($category) . " öğelerini ekleyin";
    }
} 