<?php

namespace App\AI\Core\CodeConsciousness;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\AICodeSnippet;

class CodeCategoryDetector
{
    private $categories = [];
    private $patterns = [];
    
    public function __construct()
    {
        $this->initializeCategories();
        $this->loadPatterns();
    }
    
    /**
     * Kategorileri başlat
     */
    private function initializeCategories()
    {
        // HTML kategorileri
        $this->categories['html'] = [
            'markup' => 'Yapısal HTML işaretlemesi',
            'form' => 'Form elemanları',
            'table' => 'Tablo yapısı',
            'list' => 'Liste yapısı',
            'semantic' => 'Semantik HTML',
            'component' => 'HTML bileşeni',
            'template' => 'HTML şablonu'
        ];
        
        // CSS kategorileri
        $this->categories['css'] = [
            'layout' => 'Düzen ve yerleşim',
            'visual' => 'Görsel stil',
            'animation' => 'Animasyon ve geçişler',
            'responsive' => 'Duyarlı tasarım',
            'typography' => 'Tipografi',
            'component' => 'Bileşen stili',
            'theme' => 'Tema veya renk şeması'
        ];
        
        // JavaScript kategorileri
        $this->categories['javascript'] = [
            'function' => 'İşlev tanımı',
            'class' => 'Sınıf tanımı',
            'dom' => 'DOM manipülasyonu',
            'event' => 'Olay işleyici',
            'async' => 'Asenkron işlem',
            'utility' => 'Yardımcı fonksiyonlar',
            'algorithm' => 'Algoritma'
        ];
        
        // PHP kategorileri
        $this->categories['php'] = [
            'function' => 'İşlev tanımı',
            'class' => 'Sınıf tanımı',
            'interface' => 'Arayüz tanımı',
            'trait' => 'Trait tanımı',
            'database' => 'Veritabanı işlemi',
            'validation' => 'Veri doğrulama',
            'api' => 'API işlemi'
        ];
    }
    
    /**
     * Kategori tespiti için regex desenleri yükle
     */
    private function loadPatterns()
    {
        // HTML desenleri
        $this->patterns['html'] = [
            'form' => '/<form|<input|<select|<textarea|<button|<label|<fieldset/i',
            'table' => '/<table|<tr|<td|<th|<thead|<tbody|<tfoot/i',
            'list' => '/<ul|<ol|<li|<dl|<dt|<dd/i',
            'semantic' => '/<header|<footer|<nav|<main|<article|<section|<aside/i',
            'component' => '/<div\s+class=|<section\s+class=|data-component/i',
            'template' => '/{{|{%|{#|@if|@foreach|@include|@extends|@section/i'
        ];
        
        // CSS desenleri
        $this->patterns['css'] = [
            'layout' => '/display:|position:|float:|grid-|flex-|margin:|padding:/i',
            'visual' => '/color:|background:|border:|box-shadow:|text-shadow:|opacity:/i',
            'animation' => '/@keyframes|animation:|transition:|transform:/i',
            'responsive' => '/@media|min-width|max-width/i',
            'typography' => '/font-|text-|line-height:|letter-spacing:/i',
            'component' => '/\.[\w-]+\s+\.[\w-]+|\.component-|\.widget-/i',
            'theme' => '/--[\w-]+:|var\(--[\w-]+\)|\.theme-|\.dark-mode|\.light-mode/i'
        ];
        
        // JavaScript desenleri
        $this->patterns['javascript'] = [
            'function' => '/function\s+\w+\s*\(|const\s+\w+\s*=\s*function|const\s+\w+\s*=\s*\([^\)]*\)\s*=>/i',
            'class' => '/class\s+\w+|extends\s+\w+|constructor\s*\(/i',
            'dom' => '/document\.|getElementById|querySelector|createElement|appendChild|innerHTML/i',
            'event' => '/addEventListener|onclick|onchange|onsubmit|onload|click\(|change\(/i',
            'async' => '/Promise\.|async\s+function|await\s+|\.then\(|\.catch\(/i',
            'utility' => '/function\s+is\w+|function\s+get\w+|function\s+format\w+|function\s+convert\w+/i',
            'algorithm' => '/for\s*\(|while\s*\(|if\s*\(|switch\s*\(|recursion|sort\(|map\(|filter\(|reduce\(/i'
        ];
        
        // PHP desenleri
        $this->patterns['php'] = [
            'function' => '/function\s+\w+\s*\(/i',
            'class' => '/class\s+\w+|extends\s+\w+|implements\s+\w+/i',
            'interface' => '/interface\s+\w+/i',
            'trait' => '/trait\s+\w+/i',
            'database' => '/PDO|mysqli|DB::|->query\(|->select\(|->where\(|->join\(/i',
            'validation' => '/->validate\(|filter_var|preg_match|is_\w+\(/i',
            'api' => '/->json\(|response\(|Request|->get\(|->post\(|curl_|file_get_contents\(/i'
        ];
    }
    
    /**
     * Kodun kategorisini tespit et
     * 
     * @param string $code Kod içeriği
     * @param string $language Programlama dili
     * @return array|null Kategori bilgisi
     */
    public function detectCategory($code, $language)
    {
        try {
            // Dil için kategori ve desen tanımları var mı kontrol et
            if (!isset($this->categories[$language]) || !isset($this->patterns[$language])) {
                return null;
            }
            
            $detectedCategories = [];
            $patternMatches = [];
            
            // Dil için tanımlı tüm desenleri kontrol et
            foreach ($this->patterns[$language] as $category => $pattern) {
                // Desene uygunluk kontrolü
                $matches = preg_match_all($pattern, $code, $matchResult);
                
                if ($matches > 0) {
                    $detectedCategories[$category] = $matches;
                    $patternMatches[$category] = $matchResult[0];
                }
            }
            
            // Hiç kategori tespit edilmediyse
            if (empty($detectedCategories)) {
                // Varsayılan kategori atama
                switch ($language) {
                    case 'html':
                        return ['category' => 'markup', 'confidence' => 0.5];
                    case 'css':
                        return ['category' => 'visual', 'confidence' => 0.5];
                    case 'javascript':
                    case 'php':
                        return ['category' => 'snippet', 'confidence' => 0.5];
                    default:
                        return null;
                }
            }
            
            // En çok eşleşen kategoriyi bul
            arsort($detectedCategories);
            $topCategory = key($detectedCategories);
            $matchCount = current($detectedCategories);
            
            // Eşleşme sayısına göre güven seviyesi hesapla
            $confidence = min(0.95, 0.5 + ($matchCount / 10));
            
            // Özel durumlar ve tahminler için düzeltmeler
            if ($language === 'css' && $topCategory === 'layout' && preg_match('/@media/i', $code)) {
                $topCategory = 'responsive';
                $confidence = 0.85;
            }
            
            if ($language === 'html' && $topCategory === 'component' && strlen($code) < 100) {
                $topCategory = 'markup';
                $confidence = 0.7;
            }
            
            // Karmaşık kodlar için daha yüksek güven seviyesi
            $lineCount = substr_count($code, "\n") + 1;
            if ($lineCount > 20) {
                $confidence = min(0.98, $confidence + 0.1);
            }
            
            // Makine öğrenimi için veri seti güncellemesi (eğer benzer kodlar varsa)
            $this->updateLearningData($language, $topCategory, $patternMatches[$topCategory] ?? []);
            
            return [
                'category' => $topCategory,
                'confidence' => $confidence,
                'description' => $this->categories[$language][$topCategory] ?? 'Kod parçası'
            ];
        } catch (\Exception $e) {
            Log::error('Kod kategori tespit hatası: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Makine öğrenimi için veri seti güncelleme
     */
    private function updateLearningData($language, $category, $patterns)
    {
        try {
            // Kategori istatistiklerini cache'den al
            $categoryStats = Cache::get('code_category_stats', []);
            
            // Dil için istatistikler yoksa oluştur
            if (!isset($categoryStats[$language])) {
                $categoryStats[$language] = [];
            }
            
            // Kategori için istatistikler yoksa oluştur
            if (!isset($categoryStats[$language][$category])) {
                $categoryStats[$language][$category] = [
                    'count' => 0,
                    'patterns' => []
                ];
            }
            
            // Kategori sayısını artır
            $categoryStats[$language][$category]['count']++;
            
            // Desenleri güncelle
            foreach ($patterns as $pattern) {
                $patternKey = md5($pattern);
                
                if (!isset($categoryStats[$language][$category]['patterns'][$patternKey])) {
                    $categoryStats[$language][$category]['patterns'][$patternKey] = [
                        'pattern' => substr($pattern, 0, 100), // Çok uzun desenleri kırp
                        'count' => 1
                    ];
                } else {
                    $categoryStats[$language][$category]['patterns'][$patternKey]['count']++;
                }
            }
            
            // İstatistikleri cache'e kaydet (1 hafta süreyle)
            Cache::put('code_category_stats', $categoryStats, now()->addWeek());
        } catch (\Exception $e) {
            Log::warning('Kategori öğrenme verisi güncellenemedi: ' . $e->getMessage());
        }
    }
    
    /**
     * Toplanan kategori istatistiklerini getir
     */
    public function getCategoryStatistics()
    {
        $categoryStats = Cache::get('code_category_stats', []);
        $statistics = [];
        
        foreach ($categoryStats as $language => $categories) {
            $statistics[$language] = [];
            
            foreach ($categories as $category => $data) {
                $statistics[$language][$category] = $data['count'];
            }
        }
        
        return $statistics;
    }
    
    /**
     * Benzer kod kategorileri arasında ilişki istatistiklerini getir
     */
    public function getCategoryRelations()
    {
        // Kategori ilişkilerini hesapla
        $snippets = AICodeSnippet::select(['id', 'language', 'category', 'tags'])
            ->take(1000)
            ->get();
            
        $relations = [];
        
        foreach ($snippets as $snippet) {
            $language = $snippet->language;
            $category = $snippet->category;
            
            if (!isset($relations[$language])) {
                $relations[$language] = [];
            }
            
            if (!isset($relations[$language][$category])) {
                $relations[$language][$category] = [
                    'count' => 0,
                    'related' => []
                ];
            }
            
            $relations[$language][$category]['count']++;
            
            // Etiketlerle ilişkileri analiz et
            if (!empty($snippet->tags) && is_array($snippet->tags)) {
                foreach ($snippet->tags as $tag) {
                    if (!isset($relations[$language][$category]['related'][$tag])) {
                        $relations[$language][$category]['related'][$tag] = 1;
                    } else {
                        $relations[$language][$category]['related'][$tag]++;
                    }
                }
            }
        }
        
        return $relations;
    }
    
    /**
     * Yeni bir kategori ekle veya mevcut bir kategoriyi güncelle
     */
    public function updateCategory($language, $category, $description, $pattern)
    {
        // Dil için kategori tanımları yoksa oluştur
        if (!isset($this->categories[$language])) {
            $this->categories[$language] = [];
        }
        
        // Kategoriyi güncelle veya ekle
        $this->categories[$language][$category] = $description;
        
        // Dil için desen tanımları yoksa oluştur
        if (!isset($this->patterns[$language])) {
            $this->patterns[$language] = [];
        }
        
        // Deseni güncelle veya ekle
        $this->patterns[$language][$category] = $pattern;
        
        // Değişiklikleri cache'e kaydet
        $this->saveCategories();
        
        return [
            'success' => true,
            'message' => "$language dilinde $category kategorisi güncellendi"
        ];
    }
    
    /**
     * Kategorileri cache'e kaydet
     */
    private function saveCategories()
    {
        Cache::put('code_categories', $this->categories, now()->addMonth());
        Cache::put('code_patterns', $this->patterns, now()->addMonth());
    }
} 