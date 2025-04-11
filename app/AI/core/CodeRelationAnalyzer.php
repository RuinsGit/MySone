<?php

namespace App\AI\Core;

use App\Models\AICodeSnippet;
use App\Models\AICodeActivity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CodeRelationAnalyzer
{
    // İlişki tipleri
    const RELATION_DEPENDENCY = 'dependency'; // Bir kod diğerine bağımlı
    const RELATION_SIMILARITY = 'similarity'; // Kodlar benzer
    const RELATION_COMPLEMENT = 'complement'; // Kodlar birbirini tamamlıyor
    const RELATION_ALTERNATIVE = 'alternative'; // Kodlar birbirinin alternatifi
    
    // İlişki gücü eşik değerleri
    const THRESHOLD_LOW = 0.3;
    const THRESHOLD_MEDIUM = 0.6;
    const THRESHOLD_HIGH = 0.8;
    
    /**
     * İki kod arasındaki ilişkiyi analiz et
     * 
     * @param AICodeSnippet $codeA
     * @param AICodeSnippet $codeB
     * @return array İlişki bilgileri
     */
    public function analyzeRelation(AICodeSnippet $codeA, AICodeSnippet $codeB)
    {
        $relations = [];
        
        // Benzerlik ilişkisi
        $similarityScore = $this->calculateSimilarityScore($codeA, $codeB);
        if ($similarityScore > self::THRESHOLD_LOW) {
            $relations[] = [
                'type' => self::RELATION_SIMILARITY,
                'score' => $similarityScore,
                'description' => $this->describeSimilarityRelation($codeA, $codeB, $similarityScore)
            ];
        }
        
        // Bağımlılık ilişkisi
        $dependencyScore = $this->calculateDependencyScore($codeA, $codeB);
        if ($dependencyScore > self::THRESHOLD_LOW) {
            $relations[] = [
                'type' => self::RELATION_DEPENDENCY,
                'score' => $dependencyScore,
                'description' => $this->describeDependencyRelation($codeA, $codeB, $dependencyScore)
            ];
        }
        
        // Tamamlayıcı ilişki
        $complementScore = $this->calculateComplementScore($codeA, $codeB);
        if ($complementScore > self::THRESHOLD_LOW) {
            $relations[] = [
                'type' => self::RELATION_COMPLEMENT,
                'score' => $complementScore,
                'description' => $this->describeComplementRelation($codeA, $codeB, $complementScore)
            ];
        }
        
        // Alternatif ilişki
        $alternativeScore = $this->calculateAlternativeScore($codeA, $codeB);
        if ($alternativeScore > self::THRESHOLD_LOW) {
            $relations[] = [
                'type' => self::RELATION_ALTERNATIVE,
                'score' => $alternativeScore,
                'description' => $this->describeAlternativeRelation($codeA, $codeB, $alternativeScore)
            ];
        }
        
        return [
            'code_a_id' => $codeA->id,
            'code_b_id' => $codeB->id,
            'relations' => $relations
        ];
    }
    
    /**
     * Benzerlik skorunu hesapla
     */
    private function calculateSimilarityScore(AICodeSnippet $codeA, AICodeSnippet $codeB)
    {
        $score = 0.0;
        
        // Aynı dil ve kategori ise başlangıç benzerlik puanı
        if ($codeA->language === $codeB->language) {
            $score += 0.3;
        }
        
        if ($codeA->category === $codeB->category) {
            $score += 0.2;
        }
        
        // Kod içeriği benzerliği
        $codeASanitized = preg_replace('/\s+/', ' ', strtolower($codeA->code_content));
        $codeBSanitized = preg_replace('/\s+/', ' ', strtolower($codeB->code_content));
        
        // Daha gelişmiş metin benzerliği analizleri burada yapılabilir
        // Şimdilik basit kelime eşleşmesi yapıyoruz
        $wordsA = explode(' ', $codeASanitized);
        $wordsB = explode(' ', $codeBSanitized);
        
        $commonWords = array_intersect($wordsA, $wordsB);
        $similarityRatio = count($commonWords) / max(count($wordsA), count($wordsB));
        
        $score += $similarityRatio * 0.5;
        
        return min(1.0, $score);
    }
    
    /**
     * Bağımlılık skorunu hesapla
     */
    private function calculateDependencyScore(AICodeSnippet $codeA, AICodeSnippet $codeB)
    {
        // Bu metodda, bir kodun diğerine ne kadar bağımlı olduğunu hesaplıyoruz
        // HTML ve CSS gibi dillerde, HTML'in CSS'e bağımlılığı yüksektir
        
        $score = 0.0;
        
        // HTML-CSS ilişkisi
        if (($codeA->language === 'html' && $codeB->language === 'css') ||
            ($codeB->language === 'html' && $codeA->language === 'css')) {
            $score += 0.7;
        }
        
        // JavaScript-HTML ilişkisi
        if (($codeA->language === 'javascript' && $codeB->language === 'html') ||
            ($codeB->language === 'javascript' && $codeA->language === 'html')) {
            $score += 0.6;
        }
        
        // JavaScript-CSS ilişkisi
        if (($codeA->language === 'javascript' && $codeB->language === 'css') ||
            ($codeB->language === 'javascript' && $codeA->language === 'css')) {
            $score += 0.4;
        }
        
        // Kod içeriğine göre bağımlılık analizi
        // Örneğin, HTML içinde CSS sınıf referansı var mı?
        if ($codeA->language === 'html' && $codeB->language === 'css') {
            // CSS class/id isimleri HTML'de geçiyor mu kontrol et
            $cssSelectors = $this->extractCssSelectors($codeB->code_content);
            foreach ($cssSelectors as $selector) {
                if (strpos($codeA->code_content, $selector) !== false) {
                    $score += 0.05; // Her eşleşme için skoru artır
                }
            }
        }
        
        return min(1.0, $score);
    }
    
    /**
     * Tamamlayıcı skor hesapla
     */
    private function calculateComplementScore(AICodeSnippet $codeA, AICodeSnippet $codeB)
    {
        // İki kodun birbirini ne kadar tamamladığını hesapla
        $score = 0.0;
        
        // İki kod aynı dilde ise
        if ($codeA->language === $codeB->language) {
            // Aynı dilde farklı kategoriler tamamlayıcı olabilir
            if ($codeA->category !== $codeB->category) {
                $score += 0.4;
            }
            
            // Bir kod function diğeri class ise
            if (($codeA->category === 'function' && $codeB->category === 'class') ||
                ($codeB->category === 'function' && $codeA->category === 'class')) {
                $score += 0.3;
            }
        }
        
        // Farklı dillerde, uyumlu kategoriler
        if ($codeA->language !== $codeB->language) {
            if ($codeA->category === $codeB->category) {
                $score += 0.2;
            }
        }
        
        return min(1.0, $score);
    }
    
    /**
     * Alternatif skor hesapla
     */
    private function calculateAlternativeScore(AICodeSnippet $codeA, AICodeSnippet $codeB)
    {
        // İki kodun birbirine alternatif olup olmadığını hesapla
        $score = 0.0;
        
        // Aynı kategoride farklı diller alternatif olabilir
        if ($codeA->language !== $codeB->language && $codeA->category === $codeB->category) {
            $score += 0.6;
        }
        
        // Aynı kategoride aynı dil, çok benzer olmayan kodlar alternatif olabilir
        if ($codeA->language === $codeB->language && $codeA->category === $codeB->category) {
            $similarityScore = $this->calculateSimilarityScore($codeA, $codeB);
            // Orta düzeyde benzerlik alternatif olabilir, çok benzerse değil
            if ($similarityScore > 0.3 && $similarityScore < 0.7) {
                $score += 0.4;
            }
        }
        
        return min(1.0, $score);
    }
    
    /**
     * CSS seçicilerini çıkar
     */
    private function extractCssSelectors($cssCode)
    {
        $selectors = [];
        // Basit CSS sınıf ve ID seçicilerini bul
        preg_match_all('/(\.[\w-]+|#[\w-]+)/', $cssCode, $matches);
        if (isset($matches[1])) {
            $selectors = array_merge($selectors, $matches[1]);
        }
        
        return $selectors;
    }
    
    /**
     * Benzerlik ilişkisini açıkla
     */
    private function describeSimilarityRelation($codeA, $codeB, $score)
    {
        if ($score > self::THRESHOLD_HIGH) {
            return "Bu kodlar neredeyse aynı veya çok benzer.";
        } elseif ($score > self::THRESHOLD_MEDIUM) {
            return "Bu kodlar oldukça benzer, aynı amaç için kullanılabilir.";
        } else {
            return "Bu kodlar bazı benzerlikler gösteriyor.";
        }
    }
    
    /**
     * Bağımlılık ilişkisini açıkla
     */
    private function describeDependencyRelation($codeA, $codeB, $score)
    {
        if ($codeA->language === 'html' && $codeB->language === 'css') {
            return "HTML kodu bu CSS stillerine bağımlıdır.";
        } elseif ($codeB->language === 'html' && $codeA->language === 'css') {
            return "CSS stilleri bu HTML yapısına uygulanabilir.";
        } elseif ($codeA->language === 'javascript' && $codeB->language === 'html') {
            return "JavaScript kodu bu HTML elemanlarıyla etkileşime geçebilir.";
        } elseif ($codeB->language === 'javascript' && $codeA->language === 'html') {
            return "HTML yapısı bu JavaScript kodunu kullanabilir.";
        } else {
            return "Bu kodlar arasında bir bağımlılık ilişkisi var.";
        }
    }
    
    /**
     * Tamamlayıcı ilişkiyi açıkla
     */
    private function describeComplementRelation($codeA, $codeB, $score)
    {
        return "Bu kodlar birbirini tamamlıyor ve birlikte kullanıldığında daha etkili olabilirler.";
    }
    
    /**
     * Alternatif ilişkiyi açıkla
     */
    private function describeAlternativeRelation($codeA, $codeB, $score)
    {
        return "Bu kodlar birbirlerine alternatif olarak kullanılabilir.";
    }
    
    /**
     * Tüm kodlar için ilişkileri analiz et ve cache'le
     */
    public function analyzeAllCodeRelations($limit = 100)
    {
        Log::info('Tüm kod ilişkileri analiz ediliyor...');
        
        // En son eklenen ve en çok kullanılan kodlarla başla
        $codes = AICodeSnippet::orderBy('id', 'desc')
            ->orderBy('usage_count', 'desc')
            ->limit($limit)
            ->get();
        
        $relations = [];
        $count = 0;
        
        foreach ($codes as $codeA) {
            foreach ($codes as $codeB) {
                // Kendisiyle ilişkisini analiz etme
                if ($codeA->id === $codeB->id) {
                    continue;
                }
                
                $relation = $this->analyzeRelation($codeA, $codeB);
                if (!empty($relation['relations'])) {
                    $relations[] = $relation;
                    $count++;
                }
                
                // 1000 ilişki analiz ettikten sonra dur
                if ($count >= 1000) {
                    break 2;
                }
            }
        }
        
        Log::info("Kod ilişki analizi tamamlandı. $count ilişki bulundu.");
        
        return $relations;
    }
} 