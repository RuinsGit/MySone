<?php

namespace App\AI\Core\CodeConsciousness;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\AICodeSnippet;

class CodeRelationAnalyzer
{
    private $relations = [];
    private $keywordMapping = [];
    
    public function __construct()
    {
        $this->loadRelations();
        $this->initializeKeywordMapping();
    }
    
    /**
     * Önceden kaydedilmiş ilişkileri yükle
     */
    private function loadRelations()
    {
        $this->relations = Cache::get('code_relations', []);
    }
    
    /**
     * Kod analizi için anahtar kelime eşlemelerini hazırla
     */
    private function initializeKeywordMapping()
    {
        // HTML için anahtar kelimeler
        $this->keywordMapping['html'] = [
            'form' => ['input', 'button', 'select', 'textarea', 'label', 'fieldset', 'method'],
            'table' => ['tr', 'td', 'th', 'thead', 'tbody', 'caption', 'colspan'],
            'semantic' => ['header', 'footer', 'nav', 'main', 'article', 'section', 'aside'],
            'media' => ['img', 'video', 'audio', 'source', 'picture', 'canvas'],
            'meta' => ['head', 'meta', 'title', 'link', 'script', 'style', 'charset']
        ];
        
        // CSS için anahtar kelimeler
        $this->keywordMapping['css'] = [
            'layout' => ['display', 'position', 'float', 'clear', 'flex', 'grid', 'margin', 'padding'],
            'visual' => ['color', 'background', 'border', 'box-shadow', 'text-shadow', 'opacity'],
            'animation' => ['animation', 'transition', 'transform', 'keyframes', 'animation-delay'],
            'responsive' => ['media', 'min-width', 'max-width', 'viewport', 'responsive'],
            'typography' => ['font', 'text', 'line-height', 'letter-spacing', 'word-spacing', 'font-family']
        ];
        
        // JavaScript için anahtar kelimeler
        $this->keywordMapping['javascript'] = [
            'function' => ['function', 'return', 'var', 'let', 'const', 'arrow', '=>'],
            'dom' => ['document', 'getElementById', 'querySelector', 'addEventListener', 'innerHTML'],
            'control' => ['if', 'else', 'for', 'while', 'switch', 'case', 'break'],
            'async' => ['promise', 'async', 'await', 'then', 'catch', 'fetch', 'setTimeout'],
            'object' => ['class', 'object', 'this', 'prototype', 'new', 'constructor', 'extends']
        ];
        
        // PHP için anahtar kelimeler
        $this->keywordMapping['php'] = [
            'function' => ['function', 'return', '$', 'global', 'static', 'use'],
            'oop' => ['class', 'public', 'private', 'protected', 'extends', 'implements', 'interface'],
            'control' => ['if', 'else', 'foreach', 'for', 'while', 'switch', 'case'],
            'array' => ['array', 'array_map', 'array_filter', 'array_reduce', 'implode', 'explode'],
            'database' => ['mysql', 'pdo', 'query', 'fetch', 'select', 'insert', 'update']
        ];
    }
    
    /**
     * Kodu analiz et ve ilişkileri belirle
     * 
     * @param string $code Analiz edilecek kod
     * @param string $language Kodun dili
     * @return array Analiz sonuçları
     */
    public function analyzeCode($code, $language)
    {
        try {
            // Kodun satırlarını ayır
            $lines = explode("\n", $code);
            $lineCount = count($lines);
            
            // Kodun karmaşıklığını hesapla
            $complexity = $this->calculateComplexity($code, $language);
            
            // Kodu token'lara ayır
            $tokens = $this->tokenizeCode($code, $language);
            
            // Anahtar kelimeleri belirle
            $keywords = $this->extractKeywords($tokens, $language);
            
            // Kodun ne işe yaradığını tahmin et
            $functionality = $this->determineFunctionality($keywords, $language);
            
            // Diğer kod parçalarıyla ilişkileri belirle
            $relations = $this->findRelations($keywords, $language);
            
            // Analiz sonuçlarını döndür
            return [
                'functionality' => $functionality,
                'complexity' => $complexity,
                'keywords' => $keywords,
                'relations' => $relations,
                'line_count' => $lineCount
            ];
        } catch (\Exception $e) {
            Log::error('Kod analiz hatası (CodeRelationAnalyzer): ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Kodun karmaşıklığını hesapla
     */
    private function calculateComplexity($code, $language)
    {
        $complexity = 0;
        
        // Satır sayısına göre temel karmaşıklık
        $lineCount = substr_count($code, "\n") + 1;
        $complexity += min(5, $lineCount / 10);
        
        // İç içe yapılara göre karmaşıklık
        $nestingLevel = $this->calculateNestingLevel($code);
        $complexity += min(5, $nestingLevel);
        
        // Dile özgü karmaşıklık hesaplamaları
        switch ($language) {
            case 'html':
                $complexity += substr_count($code, '<') / 10;
                break;
                
            case 'css':
                $complexity += substr_count($code, '{') / 5;
                $complexity += substr_count($code, '@media') * 2;
                $complexity += substr_count($code, '@keyframes') * 2;
                break;
                
            case 'javascript':
                $complexity += substr_count($code, 'function') * 1.5;
                $complexity += substr_count($code, '=>') * 1.5;
                $complexity += substr_count($code, 'class') * 2;
                $complexity += substr_count($code, 'async') * 1.5;
                break;
                
            case 'php':
                $complexity += substr_count($code, 'function') * 1.5;
                $complexity += substr_count($code, 'class') * 2;
                $complexity += substr_count($code, 'extends') * 1.5;
                $complexity += substr_count($code, 'implements') * 1.5;
                break;
        }
        
        // Maksimum 10 karmaşıklık
        return min(10, $complexity);
    }
    
    /**
     * Kodun iç içe geçme seviyesini hesapla
     */
    private function calculateNestingLevel($code)
    {
        // Parantez sayısına göre iç içe geçme seviyesini hesapla
        $openBraces = substr_count($code, '{');
        $closeBraces = substr_count($code, '}');
        
        // Maksimum parantez derinliği
        $maxDepth = 0;
        $currentDepth = 0;
        
        for ($i = 0; $i < strlen($code); $i++) {
            if ($code[$i] === '{') {
                $currentDepth++;
                $maxDepth = max($maxDepth, $currentDepth);
            } elseif ($code[$i] === '}') {
                $currentDepth--;
            }
        }
        
        return $maxDepth;
    }
    
    /**
     * Kodu token'lara ayır
     */
    private function tokenizeCode($code, $language)
    {
        $tokens = [];
        
        // Boşlukları, noktalama işaretlerini temizle
        $cleanedCode = preg_replace('/[^\w\s]/', ' ', $code);
        $cleanedCode = preg_replace('/\s+/', ' ', $cleanedCode);
        
        // Kelimelere böl
        $words = explode(' ', $cleanedCode);
        
        // Boş kelimeleri filtrele
        $words = array_filter($words, function($word) {
            return !empty(trim($word));
        });
        
        return array_values($words);
    }
    
    /**
     * Koddan anahtar kelimeleri çıkar
     */
    private function extractKeywords($tokens, $language)
    {
        $keywords = [];
        
        // Eğer dil için keyword mapping varsa
        if (isset($this->keywordMapping[$language])) {
            $mappings = $this->keywordMapping[$language];
            
            // Her bir token'ı kontrol et
            foreach ($tokens as $token) {
                $token = strtolower(trim($token));
                if (empty($token)) continue;
                
                // Token'ı dil için tanımlı kategorilerle eşleştir
                foreach ($mappings as $category => $categoryKeywords) {
                    if (in_array($token, $categoryKeywords)) {
                        if (!in_array($category, $keywords)) {
                            $keywords[] = $category;
                        }
                        if (!in_array($token, $keywords)) {
                            $keywords[] = $token;
                        }
                    }
                }
            }
        }
        
        // Yeterli anahtar kelime yoksa token'lardan en yaygın olanları ekle
        if (count($keywords) < 3) {
            $tokenCounts = array_count_values($tokens);
            arsort($tokenCounts);
            
            foreach (array_keys($tokenCounts) as $token) {
                if (!in_array($token, $keywords) && strlen($token) > 3) {
                    $keywords[] = $token;
                    if (count($keywords) >= 5) break;
                }
            }
        }
        
        return array_slice($keywords, 0, 10); // En fazla 10 anahtar kelime
    }
    
    /**
     * Anahtar kelimelere göre kodun ne işe yaradığını tahmin et
     */
    private function determineFunctionality($keywords, $language)
    {
        // Dile göre özel fonksiyonlar
        switch ($language) {
            case 'html':
                if (in_array('form', $keywords)) {
                    return 'HTML form yapısı';
                } elseif (in_array('table', $keywords)) {
                    return 'HTML tablo yapısı';
                } elseif (in_array('semantic', $keywords)) {
                    return 'Semantik HTML yapısı';
                } elseif (in_array('media', $keywords)) {
                    return 'Medya içerikli HTML yapısı';
                }
                return 'HTML yapı elemanı';
                
            case 'css':
                if (in_array('animation', $keywords)) {
                    return 'CSS animasyon stillemesi';
                } elseif (in_array('layout', $keywords)) {
                    return 'CSS düzen stillemesi';
                } elseif (in_array('responsive', $keywords)) {
                    return 'Duyarlı CSS stillemesi';
                } elseif (in_array('typography', $keywords)) {
                    return 'CSS tipografi stillemesi';
                }
                return 'CSS stil tanımı';
                
            case 'javascript':
                if (in_array('function', $keywords)) {
                    return 'JavaScript fonksiyon tanımı';
                } elseif (in_array('dom', $keywords)) {
                    return 'JavaScript DOM manipülasyonu';
                } elseif (in_array('async', $keywords)) {
                    return 'Asenkron JavaScript kodu';
                } elseif (in_array('object', $keywords)) {
                    return 'JavaScript nesne/sınıf tanımı';
                }
                return 'JavaScript kod bloğu';
                
            case 'php':
                if (in_array('function', $keywords)) {
                    return 'PHP fonksiyon tanımı';
                } elseif (in_array('oop', $keywords)) {
                    return 'PHP nesne yönelimli yapı';
                } elseif (in_array('array', $keywords)) {
                    return 'PHP dizi işleme kodu';
                } elseif (in_array('database', $keywords)) {
                    return 'PHP veritabanı işlemi';
                }
                return 'PHP kod bloğu';
                
            default:
                return 'Kod parçası';
        }
    }
    
    /**
     * Anahtar kelimelere göre diğer kodlarla ilişkileri bul
     */
    private function findRelations($keywords, $language)
    {
        $relations = [];
        
        // Cache'te varsa mevcut ilişkileri kullan
        $existingRelations = $this->relations[$language] ?? [];
        
        // Anahtar kelimeleri kullanarak benzer kodları bul
        foreach ($keywords as $keyword) {
            // Bu anahtar kelime için ilişkiler var mı kontrol et
            if (isset($existingRelations[$keyword])) {
                foreach ($existingRelations[$keyword] as $relatedKeyword => $strength) {
                    if (!isset($relations[$relatedKeyword])) {
                        $relations[$relatedKeyword] = $strength;
                    } else {
                        $relations[$relatedKeyword] += $strength;
                    }
                }
            }
        }
        
        // Benzerlikleri güçlerine göre sırala
        arsort($relations);
        
        // En güçlü ilişkileri döndür (en fazla 5)
        return array_slice($relations, 0, 5, true);
    }
    
    /**
     * İlişkileri kaydet
     */
    public function saveRelations()
    {
        Cache::put('code_relations', $this->relations, now()->addWeek());
    }
    
    /**
     * Kod ilişkilerini güncelle
     * 
     * @param string $code Kod parçası
     * @param string $language Kodun dili
     * @param array $keywords Kodun anahtar kelimeleri
     */
    public function updateRelations($code, $language, $keywords)
    {
        if (!isset($this->relations[$language])) {
            $this->relations[$language] = [];
        }
        
        // Her anahtar kelime için ilişkileri güncelle
        foreach ($keywords as $keyword) {
            if (!isset($this->relations[$language][$keyword])) {
                $this->relations[$language][$keyword] = [];
            }
            
            // Diğer tüm anahtar kelimeler ile ilişki kur
            foreach ($keywords as $relatedKeyword) {
                if ($keyword !== $relatedKeyword) {
                    if (!isset($this->relations[$language][$keyword][$relatedKeyword])) {
                        $this->relations[$language][$keyword][$relatedKeyword] = 1;
                    } else {
                        $this->relations[$language][$keyword][$relatedKeyword] += 0.1;
                    }
                }
            }
        }
        
        // İlişkileri kaydet
        $this->saveRelations();
    }
} 