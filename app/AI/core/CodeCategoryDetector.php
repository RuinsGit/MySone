<?php

namespace App\AI\Core;

use App\Models\AICodeSnippet;
use Illuminate\Support\Facades\Log;

class CodeCategoryDetector
{
    // Ana kategoriler
    const CATEGORY_LAYOUT = 'layout'; // Sayfa düzeni ile ilgili kodlar
    const CATEGORY_COMPONENT = 'component'; // Yeniden kullanılabilir bileşenler
    const CATEGORY_FUNCTION = 'function'; // Fonksiyonlar
    const CATEGORY_CLASS = 'class'; // Sınıflar
    const CATEGORY_UTILITY = 'utility'; // Yardımcı kodlar
    const CATEGORY_ANIMATION = 'animation'; // Animasyonlar
    const CATEGORY_RESPONSIVE = 'responsive'; // Duyarlı tasarım
    const CATEGORY_FORM = 'form'; // Form işlemleri
    const CATEGORY_API = 'api'; // API istekleri
    const CATEGORY_DATABASE = 'database'; // Veritabanı işlemleri
    
    // Alt kategoriler
    private $subCategories = [
        'html' => [
            'structure' => ['header', 'footer', 'nav', 'main', 'section', 'article'],
            'form' => ['form', 'input', 'select', 'button', 'textarea'],
            'table' => ['table', 'tr', 'td', 'th'],
            'lists' => ['ul', 'ol', 'li'],
            'media' => ['img', 'video', 'audio'],
            'semantic' => ['header', 'footer', 'nav', 'article', 'section', 'aside']
        ],
        'css' => [
            'layout' => ['display', 'position', 'flex', 'grid', 'float'],
            'visual' => ['color', 'background', 'border', 'box-shadow', 'filter'],
            'typography' => ['font', 'text', 'letter-spacing', 'line-height'],
            'animation' => ['animation', 'transition', 'transform', 'keyframes'],
            'responsive' => ['media', '@media', 'responsive'],
            'framework' => ['bootstrap', 'tailwind', 'foundation', 'bulma']
        ],
        'javascript' => [
            'dom' => ['document', 'getElementById', 'querySelector', 'addEventListener'],
            'events' => ['click', 'mouseover', 'submit', 'change', 'load'],
            'ajax' => ['fetch', 'axios', 'XMLHttpRequest'],
            'utility' => ['map', 'filter', 'reduce', 'forEach', 'find'],
            'classes' => ['class', 'constructor', 'extends', 'new'],
            'framework' => ['react', 'vue', 'angular', 'svelte']
        ],
        'php' => [
            'functions' => ['function', 'return', 'echo', 'print'],
            'classes' => ['class', 'extends', 'implements', 'namespace'],
            'database' => ['mysql', 'query', 'select', 'insert', 'update', 'delete'],
            'framework' => ['laravel', 'symfony', 'codeigniter', 'wordpress'],
            'api' => ['json_encode', 'json_decode', 'curl', 'api']
        ]
    ];
    
    /**
     * Kod parçasının kategorisini tespit et
     * 
     * @param AICodeSnippet $code
     * @return array Kategori bilgileri
     */
    public function detectCategory(AICodeSnippet $code)
    {
        $language = $code->language;
        $codeContent = $code->code_content;
        
        // Ana kategori
        $mainCategory = $this->detectMainCategory($codeContent, $language);
        
        // Alt kategori
        $subCategories = $this->detectSubCategories($codeContent, $language);
        
        // Kod amacı
        $purpose = $this->detectPurpose($codeContent, $language, $mainCategory);
        
        // Zorluk seviyesi
        $complexity = $this->calculateComplexity($codeContent, $language);
        
        // Kullanım durumları
        $useCases = $this->identifyUseCases($code, $mainCategory, $subCategories);
        
        return [
            'main_category' => $mainCategory,
            'sub_categories' => $subCategories,
            'purpose' => $purpose,
            'complexity' => $complexity,
            'use_cases' => $useCases,
            'confidence' => $this->calculateConfidence($mainCategory, $subCategories, $complexity)
        ];
    }
    
    /**
     * Ana kategoriyi tespit et
     */
    private function detectMainCategory($code, $language)
    {
        // Dile özel ana kategori tespiti
        switch ($language) {
            case 'html':
                return $this->detectHtmlMainCategory($code);
            case 'css':
                return $this->detectCssMainCategory($code);
            case 'javascript':
                return $this->detectJsMainCategory($code);
            case 'php':
                return $this->detectPhpMainCategory($code);
            default:
                return 'unknown';
        }
    }
    
    /**
     * HTML ana kategorisini tespit et
     */
    private function detectHtmlMainCategory($code)
    {
        if (preg_match('/<form\b/i', $code)) {
            return self::CATEGORY_FORM;
        } elseif (preg_match('/<(nav|header|footer|aside|main|section)\b/i', $code)) {
            return self::CATEGORY_LAYOUT;
        } elseif (preg_match('/<(div|span)\s+class="[^"]*component/i', $code)) {
            return self::CATEGORY_COMPONENT;
        } elseif (preg_match('/<(div|section)\s+class="[^"]*container/i', $code)) {
            return self::CATEGORY_LAYOUT;
        } elseif (preg_match('/<(table|tr|td|th)\b/i', $code)) {
            return self::CATEGORY_COMPONENT;
        } elseif (preg_match('/class="[^"]*responsive/i', $code)) {
            return self::CATEGORY_RESPONSIVE;
        } else {
            // İçerik analizi ile kategori tespiti
            $componentPattern = '/<[a-z-]+\s+class="[^"]*(?:card|button|modal|dropdown|navbar|tab)/i';
            if (preg_match($componentPattern, $code)) {
                return self::CATEGORY_COMPONENT;
            }
            
            return self::CATEGORY_LAYOUT;
        }
    }
    
    /**
     * CSS ana kategorisini tespit et
     */
    private function detectCssMainCategory($code)
    {
        if (preg_match('/@keyframes|animation:|transition:|transform:/i', $code)) {
            return self::CATEGORY_ANIMATION;
        } elseif (preg_match('/@media|responsive/i', $code)) {
            return self::CATEGORY_RESPONSIVE;
        } elseif (preg_match('/flex:|display\s*:\s*flex|grid:|display\s*:\s*grid/i', $code)) {
            return self::CATEGORY_LAYOUT;
        } elseif (preg_match('/\.(btn|button|card|modal|component|dropdown)/i', $code)) {
            return self::CATEGORY_COMPONENT;
        } elseif (preg_match('/\.form|input|select|textarea|button/i', $code)) {
            return self::CATEGORY_FORM;
        } else {
            return self::CATEGORY_UTILITY;
        }
    }
    
    /**
     * JavaScript ana kategorisini tespit et
     */
    private function detectJsMainCategory($code)
    {
        if (preg_match('/class\s+\w+/i', $code)) {
            return self::CATEGORY_CLASS;
        } elseif (preg_match('/function\s+\w+/i', $code)) {
            return self::CATEGORY_FUNCTION;
        } elseif (preg_match('/fetch\(|axios\.|XMLHttpRequest/i', $code)) {
            return self::CATEGORY_API;
        } elseif (preg_match('/querySelector|getElementById|addEventListener/i', $code)) {
            return self::CATEGORY_COMPONENT;
        } elseif (preg_match('/animation|keyframes|transition|transform/i', $code)) {
            return self::CATEGORY_ANIMATION;
        } elseif (preg_match('/form|submit|input|validation/i', $code)) {
            return self::CATEGORY_FORM;
        } else {
            return self::CATEGORY_UTILITY;
        }
    }
    
    /**
     * PHP ana kategorisini tespit et
     */
    private function detectPhpMainCategory($code)
    {
        if (preg_match('/class\s+\w+/i', $code)) {
            return self::CATEGORY_CLASS;
        } elseif (preg_match('/function\s+\w+/i', $code)) {
            return self::CATEGORY_FUNCTION;
        } elseif (preg_match('/SELECT|INSERT|UPDATE|DELETE|FROM|JOIN/i', $code)) {
            return self::CATEGORY_DATABASE;
        } elseif (preg_match('/json_encode|json_decode|curl_|api/i', $code)) {
            return self::CATEGORY_API;
        } elseif (preg_match('/<form|$_POST|$_GET|validate|input/i', $code)) {
            return self::CATEGORY_FORM;
        } else {
            return self::CATEGORY_UTILITY;
        }
    }
    
    /**
     * Alt kategorileri tespit et
     */
    private function detectSubCategories($code, $language)
    {
        $subCategories = [];
        
        if (!isset($this->subCategories[$language])) {
            return $subCategories;
        }
        
        foreach ($this->subCategories[$language] as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($code, $keyword) !== false) {
                    if (!in_array($category, $subCategories)) {
                        $subCategories[] = $category;
                    }
                }
            }
        }
        
        return $subCategories;
    }
    
    /**
     * Kod amacını tespit et
     */
    private function detectPurpose($code, $language, $mainCategory)
    {
        // Dil ve kategoriye göre amacı tespit et
        switch ($language) {
            case 'html':
                return $this->detectHtmlPurpose($code, $mainCategory);
            case 'css':
                return $this->detectCssPurpose($code, $mainCategory);
            case 'javascript':
                return $this->detectJsPurpose($code, $mainCategory);
            case 'php':
                return $this->detectPhpPurpose($code, $mainCategory);
            default:
                return "Genel amaçlı $language kodu";
        }
    }
    
    /**
     * HTML kodunun amacını tespit et
     */
    private function detectHtmlPurpose($code, $mainCategory)
    {
        if ($mainCategory === self::CATEGORY_FORM) {
            if (preg_match('/login|giriş/i', $code)) {
                return 'Kullanıcı giriş formu';
            } elseif (preg_match('/register|kayıt/i', $code)) {
                return 'Kullanıcı kayıt formu';
            } elseif (preg_match('/contact|iletişim/i', $code)) {
                return 'İletişim formu';
            } else {
                return 'Form yapısı';
            }
        } elseif ($mainCategory === self::CATEGORY_LAYOUT) {
            if (preg_match('/<nav/i', $code)) {
                return 'Navigasyon menüsü';
            } elseif (preg_match('/<header/i', $code)) {
                return 'Sayfa başlığı';
            } elseif (preg_match('/<footer/i', $code)) {
                return 'Sayfa altlığı';
            } elseif (preg_match('/<aside/i', $code)) {
                return 'Yan menü';
            } else {
                return 'Sayfa düzeni';
            }
        } elseif ($mainCategory === self::CATEGORY_COMPONENT) {
            if (preg_match('/card|kart/i', $code)) {
                return 'Kart bileşeni';
            } elseif (preg_match('/button|buton/i', $code)) {
                return 'Buton bileşeni';
            } elseif (preg_match('/modal|popup/i', $code)) {
                return 'Modal bileşeni';
            } elseif (preg_match('/table|tablo/i', $code)) {
                return 'Tablo bileşeni';
            } else {
                return 'UI bileşeni';
            }
        } else {
            return 'HTML yapısı';
        }
    }
    
    /**
     * CSS kodunun amacını tespit et
     */
    private function detectCssPurpose($code, $mainCategory)
    {
        if ($mainCategory === self::CATEGORY_ANIMATION) {
            if (preg_match('/@keyframes/i', $code)) {
                return 'Keyframe animasyonu';
            } elseif (preg_match('/transition/i', $code)) {
                return 'Geçiş efekti';
            } else {
                return 'Animasyon efekti';
            }
        } elseif ($mainCategory === self::CATEGORY_RESPONSIVE) {
            return 'Duyarlı (responsive) tasarım';
        } elseif ($mainCategory === self::CATEGORY_LAYOUT) {
            if (preg_match('/flex/i', $code)) {
                return 'Flexbox düzeni';
            } elseif (preg_match('/grid/i', $code)) {
                return 'Grid düzeni';
            } else {
                return 'Sayfa düzeni stilleri';
            }
        } elseif ($mainCategory === self::CATEGORY_COMPONENT) {
            if (preg_match('/button|btn/i', $code)) {
                return 'Buton stilleri';
            } elseif (preg_match('/card/i', $code)) {
                return 'Kart stilleri';
            } elseif (preg_match('/modal/i', $code)) {
                return 'Modal pencere stilleri';
            } else {
                return 'Bileşen stilleri';
            }
        } else {
            return 'CSS stilleri';
        }
    }
    
    /**
     * JavaScript kodunun amacını tespit et
     */
    private function detectJsPurpose($code, $mainCategory)
    {
        if ($mainCategory === self::CATEGORY_CLASS) {
            return 'JavaScript sınıfı';
        } elseif ($mainCategory === self::CATEGORY_FUNCTION) {
            if (preg_match('/validate|doğrula/i', $code)) {
                return 'Doğrulama fonksiyonu';
            } elseif (preg_match('/calculate|hesapla/i', $code)) {
                return 'Hesaplama fonksiyonu';
            } elseif (preg_match('/format|format/i', $code)) {
                return 'Formatlama fonksiyonu';
            } else {
                return 'JavaScript fonksiyonu';
            }
        } elseif ($mainCategory === self::CATEGORY_API) {
            return 'API istek kodu';
        } elseif ($mainCategory === self::CATEGORY_ANIMATION) {
            return 'JavaScript animasyonu';
        } elseif ($mainCategory === self::CATEGORY_FORM) {
            return 'Form işleme kodu';
        } else {
            return 'JavaScript kodu';
        }
    }
    
    /**
     * PHP kodunun amacını tespit et
     */
    private function detectPhpPurpose($code, $mainCategory)
    {
        if ($mainCategory === self::CATEGORY_CLASS) {
            if (preg_match('/Controller/i', $code)) {
                return 'Controller sınıfı';
            } elseif (preg_match('/Model/i', $code)) {
                return 'Model sınıfı';
            } else {
                return 'PHP sınıfı';
            }
        } elseif ($mainCategory === self::CATEGORY_FUNCTION) {
            return 'PHP fonksiyonu';
        } elseif ($mainCategory === self::CATEGORY_DATABASE) {
            if (preg_match('/SELECT/i', $code)) {
                return 'Veri sorgulama kodu';
            } elseif (preg_match('/INSERT/i', $code)) {
                return 'Veri ekleme kodu';
            } elseif (preg_match('/UPDATE/i', $code)) {
                return 'Veri güncelleme kodu';
            } elseif (preg_match('/DELETE/i', $code)) {
                return 'Veri silme kodu';
            } else {
                return 'Veritabanı işlem kodu';
            }
        } elseif ($mainCategory === self::CATEGORY_API) {
            return 'API işlem kodu';
        } else {
            return 'PHP kodu';
        }
    }
    
    /**
     * Kod karmaşıklığını hesapla
     */
    private function calculateComplexity($code, $language)
    {
        $complexity = 0;
        
        // Kod uzunluğu
        $lines = count(explode("\n", $code));
        $complexity += min(5, $lines / 20); // Maks 5 puan
        
        // İç içe yapılar
        $nestingLevel = 0;
        switch ($language) {
            case 'html':
                // HTML'de iç içe elemanları say
                $nestingLevel = max(0, substr_count($code, '<') - substr_count($code, '</')); 
                break;
            case 'css':
                // CSS'de iç içe seçicileri say
                $nestingLevel = substr_count($code, '{') - substr_count($code, '}');
                break;
            case 'javascript':
            case 'php':
                // Kaç adet süslü parantez var say
                $nestingLevel = substr_count($code, '{') - substr_count($code, '}');
                // İf, for, while gibi kontrol yapıları
                $nestingLevel += substr_count(strtolower($code), 'if ');
                $nestingLevel += substr_count(strtolower($code), 'for ');
                $nestingLevel += substr_count(strtolower($code), 'while ');
                $nestingLevel += substr_count(strtolower($code), 'foreach ');
                $nestingLevel += substr_count(strtolower($code), 'switch ');
                break;
        }
        
        $complexity += min(3, $nestingLevel / 3); // Maks 3 puan
        
        // Koşul ifadeleri
        $conditionCount = substr_count(strtolower($code), 'if ') + 
                         substr_count(strtolower($code), 'else ') +
                         substr_count(strtolower($code), 'switch ') +
                         substr_count(strtolower($code), 'case ');
        
        $complexity += min(2, $conditionCount / 5); // Maks 2 puan
        
        return min(10, $complexity) / 10; // 0-1 arasında normalize et
    }
    
    /**
     * Kullanım durumlarını belirle
     */
    private function identifyUseCases($code, $mainCategory, $subCategories)
    {
        $useCases = [];
        $language = $code->language;
        
        switch ($language) {
            case 'html':
                if ($mainCategory === self::CATEGORY_FORM) {
                    $useCases[] = 'Kullanıcı veri girişi';
                    $useCases[] = 'Form gönderimi';
                } elseif ($mainCategory === self::CATEGORY_LAYOUT) {
                    $useCases[] = 'Sayfa düzeni';
                    $useCases[] = 'Web sitesi yapısı';
                } elseif ($mainCategory === self::CATEGORY_COMPONENT) {
                    $useCases[] = 'Kullanıcı arayüz bileşeni';
                    $useCases[] = 'Yeniden kullanılabilir öğe';
                }
                break;
                
            case 'css':
                if ($mainCategory === self::CATEGORY_ANIMATION) {
                    $useCases[] = 'Kullanıcı deneyimini iyileştirme';
                    $useCases[] = 'Dikkat çekme';
                } elseif ($mainCategory === self::CATEGORY_RESPONSIVE) {
                    $useCases[] = 'Farklı ekran boyutlarına uyum';
                    $useCases[] = 'Mobil cihazlarda görüntüleme';
                } elseif ($mainCategory === self::CATEGORY_LAYOUT) {
                    $useCases[] = 'Sayfa öğelerini düzenleme';
                    $useCases[] = 'Görsel hiyerarşi oluşturma';
                }
                break;
                
            case 'javascript':
                if ($mainCategory === self::CATEGORY_FUNCTION) {
                    $useCases[] = 'Yeniden kullanılabilir işlev';
                    $useCases[] = 'Veri işleme';
                } elseif ($mainCategory === self::CATEGORY_CLASS) {
                    $useCases[] = 'Nesne yönelimli programlama';
                    $useCases[] = 'Karmaşık veri yapıları';
                } elseif ($mainCategory === self::CATEGORY_API) {
                    $useCases[] = 'Sunucu ile iletişim';
                    $useCases[] = 'Veri alma/gönderme';
                }
                break;
                
            case 'php':
                if ($mainCategory === self::CATEGORY_FUNCTION) {
                    $useCases[] = 'Sunucu taraflı işlev';
                    $useCases[] = 'Veri işleme';
                } elseif ($mainCategory === self::CATEGORY_CLASS) {
                    $useCases[] = 'Nesne yönelimli programlama';
                    $useCases[] = 'MVC mimarisi';
                } elseif ($mainCategory === self::CATEGORY_DATABASE) {
                    $useCases[] = 'Veri depolama';
                    $useCases[] = 'Veri sorgulama';
                }
                break;
        }
        
        // Alt kategorilere göre ek kullanım durumları
        foreach ($subCategories as $subCategory) {
            switch ($subCategory) {
                case 'form':
                    $useCases[] = 'Kullanıcı veri girişi';
                    break;
                case 'animation':
                    $useCases[] = 'Görsel geri bildirim';
                    break;
                case 'responsive':
                    $useCases[] = 'Farklı cihazlarda görüntüleme';
                    break;
                case 'framework':
                    $useCases[] = 'Framework tabanlı geliştirme';
                    break;
            }
        }
        
        // Benzersiz kullanım durumlarını döndür
        return array_unique($useCases);
    }
    
    /**
     * Kategori tespiti güvenilirliğini hesapla
     */
    private function calculateConfidence($mainCategory, $subCategories, $complexity)
    {
        $confidence = 0.5; // Başlangıç değeri
        
        // Ana kategori biliniyorsa güven artar
        if ($mainCategory !== 'unknown') {
            $confidence += 0.2;
        }
        
        // Alt kategoriler ne kadar fazlaysa o kadar güvenilir
        $confidence += min(0.2, count($subCategories) * 0.05);
        
        // Karmaşık kodlar için daha düşük güven
        $confidence -= $complexity * 0.2;
        
        return max(0.1, min(1.0, $confidence));
    }
    
    /**
     * Kodları kategorileştir ve güncelle
     */
    public function categorizeAllCodes($limit = 100)
    {
        Log::info('Kod kategorileri analiz ediliyor...');
        
        // Kategorisi güncellenmemiş kodları al
        $codes = AICodeSnippet::whereNull('tags')
            ->orWhere('tags', '=', '[]')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();
            
        $categorizedCount = 0;
        
        foreach ($codes as $code) {
            try {
                $categoryInfo = $this->detectCategory($code);
                
                // Ana kategoriyi güncelle
                $code->category = $categoryInfo['main_category'];
                
                // Alt kategorileri etiket olarak ekle
                $tags = $categoryInfo['sub_categories'];
                
                // Amacı da etiket olarak ekle
                $purpose = explode(' ', $categoryInfo['purpose']);
                $tags = array_merge($tags, $purpose);
                
                // Kullanım durumlarını da etiket olarak ekle
                $tags = array_merge($tags, $categoryInfo['use_cases']);
                
                // Benzersiz etiketleri ayarla
                $code->tags = array_unique($tags);
                
                // Güven skorunu güncelle
                $code->confidence_score = $categoryInfo['confidence'];
                
                // Kaydet
                $code->save();
                
                $categorizedCount++;
            } catch (\Exception $e) {
                Log::error('Kod kategorilendirme hatası: ' . $e->getMessage());
            }
        }
        
        Log::info("Kod kategori analizi tamamlandı. $categorizedCount kod kategorize edildi.");
        
        return $categorizedCount;
    }
} 