<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\AI\Core\CodeConsciousness;
use App\AI\Core\CodeRelationAnalyzer;
use App\AI\Core\CodeCategoryDetector;
use App\AI\Core\CodeUsagePredictorService;
use App\Models\AICodeSnippet;
use App\Models\AICodeActivity;

class AICodeConsciousnessController extends Controller
{
    protected $consciousness;
    protected $relationAnalyzer;
    protected $categoryDetector;
    protected $usagePredictor;
    
    /**
     * Controller oluştur
     */
    public function __construct(
        CodeConsciousness $consciousness,
        CodeRelationAnalyzer $relationAnalyzer,
        CodeCategoryDetector $categoryDetector,
        CodeUsagePredictorService $usagePredictor
    ) {
        $this->consciousness = $consciousness;
        $this->relationAnalyzer = $relationAnalyzer;
        $this->categoryDetector = $categoryDetector;
        $this->usagePredictor = $usagePredictor;
    }
    
    /**
     * Bilinç sisteminin durumunu göster
     */
    public function status()
    {
        $status = $this->consciousness->getStatus();
        
        // İstatistikler
        $totalCodes = AICodeSnippet::count();
        $categorizedCodes = AICodeSnippet::whereNotNull('category')->count();
        $taggedCodes = AICodeSnippet::whereNotNull('tags')->where('tags', '!=', '[]')->count();
        $usedCodes = AICodeSnippet::where('usage_count', '>', 0)->count();
        
        // Dillere göre kod sayıları
        $languageStats = AICodeSnippet::selectRaw('language, COUNT(*) as count')
            ->groupBy('language')
            ->get()
            ->pluck('count', 'language')
            ->toArray();
            
        // Kategorilere göre kod sayıları
        $categoryStats = AICodeSnippet::selectRaw('category, COUNT(*) as count')
            ->whereNotNull('category')
            ->groupBy('category')
            ->get()
            ->pluck('count', 'category')
            ->toArray();
            
        return response()->json([
            'consciousness_status' => $status,
            'stats' => [
                'total_codes' => $totalCodes,
                'categorized_codes' => $categorizedCodes,
                'tagged_codes' => $taggedCodes,
                'used_codes' => $usedCodes,
                'language_stats' => $languageStats,
                'category_stats' => $categoryStats
            ]
        ]);
    }
    
    /**
     * Bilinç sistemini aktifleştir
     */
    public function activate(Request $request)
    {
        try {
            // Kodunuzun çalıştığından emin olmak için log ekleyelim
            Log::info('Bilinç sistemi aktivasyon isteği alındı');
            
            $consciousness = resolve(CodeConsciousness::class);
            $result = $consciousness->activate();
            
            Log::info('Bilinç sistemi aktivasyon sonucu', $result);
            
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Bilinç sistemi aktivasyon hatası: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Bilinç sistemini devre dışı bırak
     */
    public function deactivate()
    {
        $result = $this->consciousness->deactivate();
        return response()->json($result);
    }
    
    /**
     * Düşünme işlemini başlat
     */
    public function think()
    {
        try {
            Log::info('Bilinç sistemi düşünme isteği alındı');
            
            $consciousness = resolve(CodeConsciousness::class);
            $result = $consciousness->think();
            
            Log::info('Bilinç sistemi düşünme sonucu: ' . ($result ? 'Başarılı' : 'Başarısız'));
            
            return response()->json([
                'success' => (bool)$result,
                'message' => $result ? 'Düşünme işlemi başarıyla tamamlandı' : 'Düşünme işlemi başarısız oldu'
            ]);
        } catch (\Exception $e) {
            Log::error('Düşünme işlemi hatası: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Tüm kodlar için ilişki analizi yap
     */
    public function analyzeRelations(Request $request)
    {
        $limit = $request->input('limit', 50);
        $relations = $this->relationAnalyzer->analyzeAllCodeRelations($limit);
        
        return response()->json([
            'success' => true,
            'message' => 'İlişki analizi tamamlandı',
            'relations_count' => count($relations),
            'relations' => $relations
        ]);
    }
    
    /**
     * Kategorilere ayır
     */
    public function categorize(Request $request)
    {
        $limit = $request->input('limit', 50);
        $count = $this->categoryDetector->categorizeAllCodes($limit);
        
        return response()->json([
            'success' => true,
            'message' => 'Kategorizasyon tamamlandı',
            'categorized_count' => $count
        ]);
    }
    
    /**
     * Etkinlik skorlarını analiz et
     */
    public function analyzeEffectiveness(Request $request)
    {
        $days = $request->input('days', 30);
        $limit = $request->input('limit', 100);
        
        $count = $this->usagePredictor->analyzeEffectivenessScores($days, $limit);
        
        return response()->json([
            'success' => true,
            'message' => 'Etkinlik analizi tamamlandı',
            'analyzed_count' => $count
        ]);
    }
    
    /**
     * Kod önerileri al
     */
    public function suggestCodes(Request $request)
    {
        $language = $request->input('language', 'html');
        $category = $request->input('category');
        $context = $request->input('context');
        $limit = $request->input('limit', 5);
        
        $suggestions = $this->usagePredictor->suggestCodes($language, $category, $context, $limit);
        
        return response()->json([
            'success' => true,
            'suggestions_count' => count($suggestions),
            'suggestions' => $suggestions
        ]);
    }
    
    /**
     * Kod akışı öner
     */
    public function suggestCodeFlow(Request $request)
    {
        $startCategory = $request->input('start_category', 'layout');
        $language = $request->input('language', 'html');
        $steps = $request->input('steps', 3);
        
        $flow = $this->usagePredictor->suggestCodeFlow($startCategory, $language, $steps);
        
        return response()->json([
            'success' => true,
            'steps_count' => count($flow),
            'flow' => $flow
        ]);
    }
    
    /**
     * İki kod arasındaki ilişkiyi analiz et
     */
    public function analyzeCodeRelationship(Request $request)
    {
        $codeAId = $request->input('code_a_id');
        $codeBId = $request->input('code_b_id');
        
        $codeA = AICodeSnippet::findOrFail($codeAId);
        $codeB = AICodeSnippet::findOrFail($codeBId);
        
        $relation = $this->relationAnalyzer->analyzeRelation($codeA, $codeB);
        
        return response()->json([
            'success' => true,
            'relation' => $relation
        ]);
    }
    
    /**
     * Kod kategorisini tespit et ve güncelle
     */
    public function detectCategory(Request $request)
    {
        $codeId = $request->input('code_id');
        $code = AICodeSnippet::findOrFail($codeId);
        
        $categoryInfo = $this->categoryDetector->detectCategory($code);
        
        // Kodu güncelle
        $code->category = $categoryInfo['main_category'];
        $code->tags = array_merge(
            $categoryInfo['sub_categories'], 
            explode(' ', $categoryInfo['purpose']),
            $categoryInfo['use_cases']
        );
        $code->confidence_score = $categoryInfo['confidence'];
        $code->save();
        
        return response()->json([
            'success' => true,
            'code_id' => $code->id,
            'category_info' => $categoryInfo
        ]);
    }
    
    /**
     * Kod kullanımını kaydet
     */
    public function recordCodeUsage(Request $request)
    {
        $codeId = $request->input('code_id');
        $context = $request->input('context');
        $relatedLanguages = $request->input('related_languages', []);
        $effectivenessScore = $request->input('effectiveness_score', 0.5);
        
        $code = AICodeSnippet::findOrFail($codeId);
        
        // Aktivite kaydı
        $activity = new AICodeActivity();
        $activity->code_snippet_id = $codeId;
        $activity->usage_context = $context;
        $activity->related_languages = $relatedLanguages;
        $activity->effectiveness_score = $effectivenessScore;
        $activity->save();
        
        // Kullanım istatistiklerini güncelle
        $code->usage_count = $code->usage_count + 1;
        $code->last_used_at = now();
        $code->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Kod kullanımı kaydedildi',
            'activity_id' => $activity->id
        ]);
    }
    
    /**
     * İki kodu birleştirerek yeni kod oluştur
     */
    public function combineCodes(Request $request)
    {
        $codeAId = $request->input('code_a_id');
        $codeBId = $request->input('code_b_id');
        
        $codeA = AICodeSnippet::findOrFail($codeAId);
        $codeB = AICodeSnippet::findOrFail($codeBId);
        
        // İki kodu birleştir - basit bir örnek
        $combinedContent = "/* Aşağıdaki kod {$codeA->id} ve {$codeB->id} kodlarının birleştirilmesiyle oluşturulmuştur */\n\n";
        $combinedContent .= "/* Kod A: */\n" . $codeA->code_content . "\n\n";
        $combinedContent .= "/* Kod B: */\n" . $codeB->code_content;
        
        // İki kodun dili aynıysa o dili kullan, değilse çoklu dil olarak işaretle
        $language = ($codeA->language === $codeB->language) ? $codeA->language : 'multi';
        
        // Etiketleri birleştir
        $tags = array_unique(array_merge($codeA->tags ?? [], $codeB->tags ?? []));
        
        // Yeni kod oluştur
        $newCode = new AICodeSnippet();
        $newCode->code_content = $combinedContent;
        $newCode->language = $language;
        $newCode->category = 'combined';
        $newCode->tags = $tags;
        $newCode->source = 'combination';
        $newCode->confidence_score = 0.8;
        $newCode->save();
        
        // Yeni kod üzerinden kategori tespiti yap
        $categoryInfo = $this->categoryDetector->detectCategory($newCode);
        
        // Kategori bilgisini güncelle
        $newCode->category = $categoryInfo['main_category'];
        $newCode->tags = array_unique(array_merge(
            $tags,
            $categoryInfo['sub_categories'],
            $categoryInfo['use_cases']
        ));
        $newCode->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Kodlar başarıyla birleştirildi',
            'new_code_id' => $newCode->id,
            'new_code' => $newCode
        ]);
    }
    
    /**
     * Bilinç sistemi bildirimleri
     */
    public function getNotifications()
    {
        // Son 24 saatte kategorilendirilen kodlar
        $categorizedCodes = AICodeSnippet::whereNotNull('category')
            ->where('updated_at', '>=', now()->subDay())
            ->count();
            
        // Son 24 saatte kaydedilen aktiviteler
        $newActivities = AICodeActivity::where('created_at', '>=', now()->subDay())
            ->count();
            
        // Son 24 saatte eklenen kodlar
        $newCodes = AICodeSnippet::where('created_at', '>=', now()->subDay())
            ->count();
            
        // Bildirimler
        $notifications = [];
        
        if ($categorizedCodes > 0) {
            $notifications[] = "Son 24 saatte $categorizedCodes kod kategorize edildi.";
        }
        
        if ($newActivities > 0) {
            $notifications[] = "Son 24 saatte $newActivities kod kullanım aktivitesi kaydedildi.";
        }
        
        if ($newCodes > 0) {
            $notifications[] = "Son 24 saatte $newCodes yeni kod öğrenildi.";
        }
        
        // Bilinç seviyesi bildirimi
        $status = $this->consciousness->getStatus();
        $consciousnessLevel = $status['consciousness_level'];
        
        $notifications[] = "Şu anki bilinç seviyesi: $consciousnessLevel";
        
        return response()->json([
            'success' => true,
            'notifications_count' => count($notifications),
            'notifications' => $notifications
        ]);
    }
    
    /**
     * Tek kod işleme
     * Bilinç sistemi ile tek bir kod işler ve sonucu döndürür
     */
    public function processSingleCode()
    {
        // Bilinç sistemi aktif mi kontrol et
        $status = $this->consciousness->getStatus();
        if (!$status['is_active']) {
            return response()->json([
                'success' => false,
                'message' => 'Kod bilinç sistemi aktif değil. Önce aktifleştirin.'
            ], 400);
        }
        
        // Tek kod işleme
        $result = $this->consciousness->thinkSingleCode();
        
        if ($result === null) {
            return response()->json([
                'success' => false,
                'message' => 'İşlenecek kod bulunamadı. Tüm kodlar işlenmiş olabilir.'
            ]);
        }
        
        // Kod hata aldıysa
        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => 'Kod işlenirken hata oluştu',
                'error' => $result['error'],
                'code_id' => $result['code_id']
            ], 500);
        }
        
        // Başarılı işleme
        return response()->json([
            'success' => true,
            'message' => 'Kod başarıyla işlendi',
            'code' => $result
        ]);
    }
    
    /**
     * Belirli sayıda kod işle
     */
    public function processBatchCodes(Request $request)
    {
        $count = $request->input('count', 5);
        
        // Maximum limit
        if ($count > 50) {
            $count = 50;
        }
        
        // Bilinç sistemi aktif mi kontrol et
        $status = $this->consciousness->getStatus();
        if (!$status['is_active']) {
            return response()->json([
                'success' => false,
                'message' => 'Kod bilinç sistemi aktif değil. Önce aktifleştirin.'
            ], 400);
        }
        
        // Toplu kod işleme
        $result = $this->consciousness->processBatchCodes($count);
        
        return response()->json([
            'success' => true,
            'message' => $result['processed_count'] . ' kod işlendi',
            'results' => $result
        ]);
    }
    
    /**
     * İşleme durumunu getir
     */
    public function getProcessingStatus()
    {
        $status = $this->consciousness->getProcessingStatus();
        $consciousnessStatus = $this->consciousness->getStatus();
        
        return response()->json([
            'success' => true,
            'consciousness_status' => $consciousnessStatus,
            'processing_status' => $status
        ]);
    }
    
    /**
     * İşlenmiş tüm kodları resetle
     */
    public function resetProcessedCodes()
    {
        $result = $this->consciousness->resetProcessedCodes();
        
        return response()->json([
            'success' => $result['success'],
            'message' => $result['message']
        ]);
    }
    
    /**
     * İşlenen son kodları getir
     */
    public function getRecentProcessedCodes(Request $request)
    {
        $limit = $request->input('limit', 10);
        
        // İşlenmiş kodların durumunu al
        $status = $this->consciousness->getProcessingStatus();
        
        // En son işlenen kod ID'leri
        $processedCodeIds = array_keys($status['status_details']);
        
        // Sırala (en son işlenenler önce)
        rsort($processedCodeIds);
        
        // Limiti uygula
        $recentCodeIds = array_slice($processedCodeIds, 0, $limit);
        
        // Kod detaylarını getir
        $codes = AICodeSnippet::whereIn('id', $recentCodeIds)->get();
        
        // İşleme durumlarını ekle
        $codesWithStatus = $codes->map(function($code) use ($status) {
            $codeStatus = $status['status_details'][$code->id] ?? null;
            return [
                'code' => $code,
                'processing_status' => $codeStatus
            ];
        });
        
        return response()->json([
            'success' => true,
            'processed_codes_count' => $status['processed_codes'],
            'total_codes_count' => $status['total_codes'],
            'recently_processed' => $codesWithStatus
        ]);
    }
    
    /**
     * Düz metin test endpoint
     */
    public function test()
    {
        return response()->json([
            'success' => true,
            'message' => 'AICodeConsciousnessController çalışıyor!'
        ]);
    }
} 