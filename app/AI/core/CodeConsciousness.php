<?php

namespace App\AI\Core;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\AICodeSnippet;
use App\Models\AICodeActivity;

class CodeConsciousness
{
    // Bilinç sistemi durumu
    private $isActive = false;
    private $consciousnessLevel = 0.0; // 0.0 - 1.0 arası bilinç seviyesi
    private $learningRate = 0.05;
    private $lastThinkingTime;
    
    // Kod ilişkileri ve kategorizasyon için hafıza
    private $codeRelations = [];
    private $codeCategories = [];
    private $usagePatterns = [];
    
    // Gerçek zamanlı kod işleme için değişkenler
    private $currentlyProcessingCode = null;
    private $processedCodeIds = [];
    private $processingStatus = [];
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->lastThinkingTime = now();
        $this->loadConsciousnessState();
    }
    
    /**
     * Önceki bilinç durumunu yükle
     */
    private function loadConsciousnessState()
    {
        $isActive = Cache::get('code_consciousness_active', false);
        $this->isActive = $isActive;
        
        $consciousnessLevel = Cache::get('code_consciousness_level', 0.0);
        $this->consciousnessLevel = $consciousnessLevel;
        
        $learningRate = Cache::get('code_consciousness_learning_rate', 0.05);
        $this->learningRate = $learningRate;
        
        // İlişkileri ve kategorileri yükle
        $codeRelations = Cache::get('code_consciousness_relations', []);
        $this->codeRelations = $codeRelations;
        
        $codeCategories = Cache::get('code_consciousness_categories', []);
        $this->codeCategories = $codeCategories;
        
        $usagePatterns = Cache::get('code_consciousness_usage_patterns', []);
        $this->usagePatterns = $usagePatterns;
        
        // Gerçek zamanlı işleme durumunu yükle
        $this->processedCodeIds = Cache::get('code_consciousness_processed_codes', []);
        $this->processingStatus = Cache::get('code_consciousness_processing_status', []);
    }
    
    /**
     * Bilinç sistemini etkinleştir
     */
    public function activate()
    {
        $this->isActive = true;
        Cache::put('code_consciousness_active', true, now()->addDay());
        Log::info('Kod bilinç sistemi aktifleştirildi');
        
        return [
            'success' => true,
            'message' => 'Kod bilinç sistemi başlatıldı',
            'consciousness_level' => $this->consciousnessLevel
        ];
    }
    
    /**
     * Bilinç sistemini devre dışı bırak
     */
    public function deactivate()
    {
        $this->isActive = false;
        Cache::put('code_consciousness_active', false, now()->addDay());
        Log::info('Kod bilinç sistemi devre dışı bırakıldı');
        
        return [
            'success' => true,
            'message' => 'Kod bilinç sistemi durduruldu'
        ];
    }
    
    /**
     * Bilinç seviyesini güncelle
     */
    public function updateConsciousnessLevel()
    {
        // Kod veritabanındaki toplam kod sayısına göre bilinç seviyesini ölçeklendirme
        $totalCodes = AICodeSnippet::count();
        $newLevel = min(1.0, $totalCodes / 1000); // 1000 kod = %100 bilinç seviyesi
        
        // Öğrenme hızına göre kademeli olarak artır
        $this->consciousnessLevel = $this->consciousnessLevel + 
            ($newLevel - $this->consciousnessLevel) * $this->learningRate;
        
        Cache::put('code_consciousness_level', $this->consciousnessLevel, now()->addMonth());
        
        return $this->consciousnessLevel;
    }
    
    /**
     * Düşünme işlemini başlat (kodları analiz et)
     */
    public function think()
    {
        if (!$this->isActive) {
            Log::warning('Kod bilinç sistemi inaktif, düşünme işlemi gerçekleştirilemiyor.');
            return false;
        }
        
        Log::info('Kod bilinç sistemi düşünmeye başladı');
        
        try {
            // Her defasında bilinç seviyesini güncelle
            $newLevel = $this->updateConsciousnessLevel();
            Log::info('Bilinç seviyesi güncellendi: ' . $newLevel);
            
            // Kod ilişkilerini analiz et
            $this->analyzeCodeRelations();
            Log::info('Kod ilişkileri analiz edildi.');
            
            // Kod kategorilerini öğren
            $this->learnCodeCategories();
            Log::info('Kod kategorileri öğrenildi.');
            
            // Kullanım kalıplarını öğren
            $this->learnUsagePatterns();
            Log::info('Kullanım kalıpları öğrenildi.');
            
            // Son düşünme zamanını güncelle
            $this->lastThinkingTime = now();
            
            // Önbellekteki son düşünme zamanını güncelle
            Cache::put('code_consciousness_last_thinking', now(), now()->addDay());
            
            Log::info('Kod bilinç sistemi düşünme işlemi tamamlandı. Bilinç seviyesi: ' . $this->consciousnessLevel);
            
            return true;
        }
        catch (\Exception $e) {
            Log::error('Düşünme işlemi sırasında hata oluştu: ' . $e->getMessage(), [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
    
    /**
     * Birer birer kod işleme (tek kod düşünme)
     * @return array|null İşlenen kod bilgileri veya işlenecek kod yoksa null
     */
    public function thinkSingleCode()
    {
        if (!$this->isActive) {
            Log::warning('Kod bilinç sistemi inaktif, düşünme işlemi gerçekleştirilemiyor.');
            return null;
        }
        
        // İşlenmemiş bir kod bul
        $code = $this->findUnprocessedCode();
        
        if (!$code) {
            Log::info('İşlenecek yeni kod bulunamadı.');
            return null;
        }
        
        Log::info('Kod bilinç sistemi tek kod işlemeye başladı: Kod ID #' . $code->id);
        $this->currentlyProcessingCode = $code->id;
        
        // İşleme durumunu güncelle
        $this->updateProcessingStatus($code->id, 'processing', 'Kod işleniyor...');
        
        try {
            // Bilinç seviyesini güncelle
            $this->updateConsciousnessLevel();
            
            // Kod kategorisini analiz et
            $categoryDetector = new CodeCategoryDetector();
            $categoryInfo = $categoryDetector->detectCategory($code);
            
            // Kodu güncelle
            $code->category = $categoryInfo['main_category'];
            $code->tags = array_merge(
                $categoryInfo['sub_categories'] ?? [], 
                explode(' ', $categoryInfo['purpose'] ?? ''),
                $categoryInfo['use_cases'] ?? []
            );
            $code->confidence_score = $categoryInfo['confidence'] ?? 0.5;
            $code->save();
            
            // Kod ilişkilerini analiz et
            $relationAnalyzer = new CodeRelationAnalyzer();
            $relatedCodes = AICodeSnippet::where('id', '!=', $code->id)
                ->where('language', $code->language)
                ->orderBy('id', 'desc')
                ->limit(5)
                ->get();
                
            $relations = [];
            foreach ($relatedCodes as $relatedCode) {
                $relation = $relationAnalyzer->analyzeRelation($code, $relatedCode);
                if (!empty($relation['relations'])) {
                    $relations[] = $relation;
                }
            }
            
            // İşlenen kod listesine ekle
            $this->processedCodeIds[] = $code->id;
            Cache::put('code_consciousness_processed_codes', $this->processedCodeIds, now()->addDay());
            
            // İşleme durumunu güncelle
            $this->updateProcessingStatus($code->id, 'completed', 'Kod başarıyla işlendi', [
                'category' => $code->category,
                'tags' => $code->tags,
                'confidence_score' => $code->confidence_score,
                'relations_count' => count($relations)
            ]);
            
            // Aktivite kaydı oluştur
            AICodeActivity::create([
                'activity_type' => 'Learning',
                'description' => 'Kod bilinç sistemi tarafından analiz edildi',
                'timestamp' => now(),
                'code_id' => $code->id,
                'effectiveness_score' => $categoryInfo['confidence'] ?? 0.5
            ]);
            
            Log::info('Kod bilinç sistemi tek kod işlemeyi tamamladı: Kod ID #' . $code->id);
            
            return [
                'code_id' => $code->id,
                'language' => $code->language,
                'category' => $code->category,
                'tags' => $code->tags,
                'confidence_score' => $code->confidence_score,
                'relations' => $relations
            ];
        }
        catch (\Exception $e) {
            Log::error('Kod işleme sırasında hata oluştu: ' . $e->getMessage(), [
                'code_id' => $code->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // İşleme durumunu güncelle - hata durumu
            $this->updateProcessingStatus($code->id, 'error', 'Kod işleme hatası: ' . $e->getMessage());
            
            return [
                'code_id' => $code->id,
                'error' => $e->getMessage()
            ];
        }
        finally {
            $this->currentlyProcessingCode = null;
        }
    }
    
    /**
     * İşlenmemiş bir kod bul
     */
    private function findUnprocessedCode()
    {
        return AICodeSnippet::whereNotIn('id', $this->processedCodeIds)
            ->orderBy('id', 'desc')
            ->first();
    }
    
    /**
     * İşleme durumunu güncelle
     */
    private function updateProcessingStatus($codeId, $status, $message, $data = [])
    {
        $this->processingStatus[$codeId] = [
            'status' => $status,
            'message' => $message,
            'timestamp' => now()->toDateTimeString(),
            'data' => $data
        ];
        
        Cache::put('code_consciousness_processing_status', $this->processingStatus, now()->addDay());
    }
    
    /**
     * İşlenmiş kodları resetle (tüm kodları yeniden işlemek için)
     */
    public function resetProcessedCodes()
    {
        $this->processedCodeIds = [];
        $this->processingStatus = [];
        Cache::put('code_consciousness_processed_codes', [], now()->addDay());
        Cache::put('code_consciousness_processing_status', [], now()->addDay());
        
        Log::info('İşlenmiş kod listesi sıfırlandı. Tüm kodlar yeniden işlenecek.');
        
        return [
            'success' => true,
            'message' => 'İşlenmiş kod listesi sıfırlandı. Tüm kodlar yeniden işlenecek.'
        ];
    }
    
    /**
     * Belirli sayıda kodu işle
     * @param int $count İşlenecek kod sayısı
     * @return array İşlenen kodların bilgileri
     */
    public function processBatchCodes($count = 5)
    {
        $results = [];
        
        for ($i = 0; $i < $count; $i++) {
            $result = $this->thinkSingleCode();
            if ($result) {
                $results[] = $result;
            } else {
                break; // İşlenecek kod kalmadı
            }
        }
        
        return [
            'processed_count' => count($results),
            'results' => $results
        ];
    }
    
    /**
     * İşleme durumunu getir
     */
    public function getProcessingStatus()
    {
        return [
            'total_codes' => AICodeSnippet::count(),
            'processed_codes' => count($this->processedCodeIds),
            'currently_processing' => $this->currentlyProcessingCode,
            'status_details' => $this->processingStatus
        ];
    }
    
    /**
     * Kod ilişkilerini analiz et
     */
    private function analyzeCodeRelations()
    {
        // Burada kodların birbirleriyle olan ilişkilerini analiz edip öğreneceğiz
        // Şimdilik temel bir yapı oluşturuldu, daha sonra geliştirilecek
    }
    
    /**
     * Kod kategorilerini öğren
     */
    private function learnCodeCategories()
    {
        // Burada kodların kategorilerini öğreneceğiz
        // Şimdilik temel bir yapı oluşturuldu, daha sonra geliştirilecek
    }
    
    /**
     * Kullanım kalıplarını öğren
     */
    private function learnUsagePatterns()
    {
        // Burada kodların ne zaman ve nasıl kullanıldığına dair kalıpları öğreneceğiz
        // Şimdilik temel bir yapı oluşturuldu, daha sonra geliştirilecek
    }
    
    /**
     * Bilinç durumunu al
     */
    public function getStatus()
    {
        return [
            'is_active' => $this->isActive,
            'consciousness_level' => round($this->consciousnessLevel * 10), // 0-10 arası değer
            'learning_rate' => $this->learningRate,
            'last_thinking_time' => $this->lastThinkingTime,
            'code_relations_count' => count($this->codeRelations),
            'code_categories_count' => count($this->codeCategories),
            'usage_patterns_count' => count($this->usagePatterns),
            'processed_codes_count' => count($this->processedCodeIds),
            'total_codes' => AICodeSnippet::count()
        ];
    }
} 