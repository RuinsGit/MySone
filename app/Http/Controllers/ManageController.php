<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\AI\Learn\LearningSystem;
use App\AI\Core\CategoryManager;
use App\AI\Core\WordRelations;
use App\Models\AIData;
use Illuminate\Support\Facades\DB;

class ManageController extends Controller
{
    /**
     * Yönetim paneli ana sayfasını göster
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('ai.manage');
    }
    
    /**
     * Öğrenme işlemini başlat
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function startLearningProcess(Request $request)
    {
        try {
            // Parametreleri doğrula
            $request->validate([
                'word_limit' => 'nullable|integer|min:1|max:1000',
                'manual_words' => 'nullable|array'
            ]);
            
            // Öğrenme sistemini yükle
            $learningSystem = $this->loadLearningSystem();
            
            // Kelime limiti
            $wordLimit = $request->input('word_limit', 50);
            
            // Manuel kelimeler varsa ekle
            $manualWords = $request->input('manual_words', []);
            if (!empty($manualWords)) {
                $learningSystem->addManualWords($manualWords);
            }
            
            // Öğrenme işlemini başlat
            $result = $learningSystem->startLearning($wordLimit);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'learned_count' => $result['learned'],
                        'total_words' => $result['total'],
                        'duration' => $result['duration'],
                        'errors' => $result['errors']
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Öğrenme başlatma hatası: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Öğrenme başlatma hatası: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Öğrenme durumunu getir
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLearningStatus()
    {
        try {
            // Öğrenme sistemini yükle
            $learningSystem = $this->loadLearningSystem();
            
            // Durumu al
            $status = $learningSystem->getLearningStatus();
            
            return response()->json([
                'success' => true,
                'data' => $status
            ]);
        } catch (\Exception $e) {
            Log::error('Öğrenme durumu alma hatası: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Öğrenme durumu alma hatası: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Öğrenme sistemi istatistiklerini getir
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLearningSystemStats()
    {
        try {
            // CategoryManager ve WordRelations istatistiklerini al
            $categoryStats = app(CategoryManager::class)->getStats();
            $relationStats = app(WordRelations::class)->getStats();
            
            // Temel kelime ilişki sayıları
            $synonymPairs = $relationStats['synonym_pairs'] ?? 0;
            $antonymPairs = $relationStats['antonym_pairs'] ?? 0;
            $associationPairs = $relationStats['association_pairs'] ?? 0;
            $totalRelations = $synonymPairs + $antonymPairs + $associationPairs;
            
            // Kategorileri düzenle
            $topCategories = [];
            if (isset($categoryStats['top_categories'])) {
                $totalCategoryWords = 0;
                foreach ($categoryStats['top_categories'] as $category) {
                    $totalCategoryWords += $category['usage_count'];
                }
                
                foreach ($categoryStats['top_categories'] as $category) {
                    $percent = $totalCategoryWords > 0 ? round(($category['usage_count'] / $totalCategoryWords) * 100, 1) : 0;
                    $topCategories[] = [
                        'category' => $category['name'],
                        'count' => $category['usage_count'],
                        'percent' => $percent
                    ];
                }
            }
            
            // En çok ilişkili kelimeleri al
            $topRelatedWords = [];
            $words = AIData::orderBy('frequency', 'desc')->take(10)->get();
            
            foreach ($words as $word) {
                // İlişki sayısını hesapla
                $relations = json_decode($word->related_words ?? '[]', true);
                $relationCount = count($relations);
                $strongestRelation = '';
                
                if (!empty($relations)) {
                    // En güçlü ilişkiyi bul
                    $maxStrength = 0;
                    foreach ($relations as $relation => $info) {
                        if (isset($info['strength']) && $info['strength'] > $maxStrength) {
                            $maxStrength = $info['strength'];
                            $strongestRelation = $relation;
                        }
                    }
                }
                
                $topRelatedWords[] = [
                    'word' => $word->word,
                    'relation_count' => $relationCount,
                    'strongest_relation' => $strongestRelation
                ];
            }
            
            // Kelime türleri dağılımını hesapla
            $wordTypes = [];
            
            // Kategori bazlı kelime sayıları
            $categories = AIData::select('category', DB::raw('count(*) as count'))
                ->groupBy('category')
                ->orderBy('count', 'desc')
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->limit(10)
                ->get();
            
            $totalWords = AIData::count();
            
            foreach ($categories as $category) {
                $percent = $totalWords > 0 ? round(($category->count / $totalWords) * 100, 1) : 0;
                $wordTypes[] = [
                    'type' => $category->category,
                    'count' => $category->count,
                    'percent' => $percent
                ];
            }
            
            // Veritabanı boyutu hesapla (MB cinsinden)
            $dbSize = '≈' . round(AIData::count() * 0.005, 2) . ' MB';
            
            // İstatistik verileri
            $stats = [
                'total_words' => $totalWords,
                'total_categories' => $categoryStats['total_categories'] ?? 0,
                'total_relations' => $totalRelations,
                'db_size' => $dbSize,
                'word_types' => $wordTypes,
                'top_related_words' => $topRelatedWords,
                'top_categories' => $topCategories,
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('İstatistik alma hatası: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'İstatistik alma hatası: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Belirli bir kelimeyi öğren
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function learnWord(Request $request)
    {
        try {
            // Parametreleri doğrula
            $request->validate([
                'word' => 'required|string|min:2|max:100'
            ]);
            
            // Öğrenme sistemini yükle
            $learningSystem = $this->loadLearningSystem();
            
            // Kelimeyi öğren
            $result = $learningSystem->learnWord($request->input('word'));
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'word' => $request->input('word'),
                        'metadata' => $result['metadata']
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Kelime öğrenme hatası: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Kelime öğrenme hatası: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Öğrenme sistemini temizle
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearLearningSystem(Request $request)
    {
        try {
            // Güvenlik kontrolü
            if ($request->input('confirm') !== 'yes') {
                return response()->json([
                    'success' => false,
                    'message' => 'İşlemi onaylamanız gerekiyor.'
                ]);
            }
            
            // Tabloları temizle
            AIData::truncate();
            
            return response()->json([
                'success' => true,
                'message' => 'Öğrenme sistemi veritabanı temizlendi.'
            ]);
        } catch (\Exception $e) {
            Log::error('Veritabanı temizleme hatası: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Veritabanı temizleme hatası: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Belirli bir kelime için akıllı cümleler oluştur
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateSmartSentences(Request $request)
    {
        try {
            // Parametreleri doğrula
            $request->validate([
                'word' => 'required|string|min:2|max:50',
                'count' => 'nullable|integer|min:1|max:10',
                'save' => 'nullable|boolean'
            ]);
            
            $word = trim($request->input('word'));
            $count = $request->input('count', 3);
            $save = $request->input('save', true);
            
            // WordRelations sınıfını başlat
            $wordRelations = app(WordRelations::class);
            
            // Kelime öğrenilmiş mi kontrol et
            $wordExists = AIData::where('word', $word)->exists();
            if (!$wordExists) {
                // Kelime öğrenilmemişse, sisteme öğret
                $learningSystem = $this->loadLearningSystem();
                if (!$learningSystem) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Öğrenme sistemi başlatılamadı'
                    ], 500);
                }
                
                $result = $learningSystem->learnWord($word);
                if (!$result['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Kelime öğrenilemedi: ' . $result['message']
                    ], 400);
                }
                
                Log::info("$word kelimesi öğrenildi, şimdi akıllı cümleler oluşturulacak");
            }
            
            // Akıllı cümleler oluştur
            $sentences = $wordRelations->generateSmartSentences($word, $save, $count);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'word' => $word,
                    'sentences' => $sentences,
                    'count' => count($sentences)
                ],
                'message' => count($sentences) > 0 
                    ? $word . ' kelimesi için ' . count($sentences) . ' cümle oluşturuldu' 
                    : $word . ' kelimesi için cümle oluşturulamadı'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Akıllı cümle oluşturma hatası: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Akıllı cümle oluşturma hatası: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Öğrenme sistemini yükle
     * 
     * @return LearningSystem
     */
    private function loadLearningSystem()
    {
        try {
            // IoC container'dan sistemleri yükle
            $categoryManager = app(CategoryManager::class);
            $wordRelations = app(WordRelations::class);
            
            // LearningSystem nesnesini oluştur
            $learningSystem = app(LearningSystem::class);
            
            return $learningSystem;
        } catch (\Exception $e) {
            Log::error('Öğrenme sistemi yükleme hatası: ' . $e->getMessage());
            throw new \Exception('Öğrenme sistemi yüklenemedi: ' . $e->getMessage());
        }
    }
    
    /**
     * Kelimeleri ara
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchWord(Request $request)
    {
        try {
            // Parametreleri doğrula
            $request->validate([
                'query' => 'required|string|min:2|max:100'
            ]);
            
            // Arama yap
            $query = $request->input('query');
            $results = AIData::where('word', 'like', "%$query%")
                ->orWhere('sentence', 'like', "%$query%")
                ->orWhere('category', 'like', "%$query%")
                ->limit(20)
                ->get(['word', 'category', 'sentence as definition', 'related_words', 'created_at']);
            
            // Sonuçları formatla
            $formattedResults = $results->map(function($item) {
                $data = $item->toArray();
                if (isset($data['related_words']) && !empty($data['related_words'])) {
                    $data['relations'] = json_decode($data['related_words']);
                    unset($data['related_words']);
                }
                return $data;
            });
            
            return response()->json([
                'success' => true,
                'data' => $formattedResults
            ]);
        } catch (\Exception $e) {
            Log::error('Kelime arama hatası: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Kelime arama hatası: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Öğrenme ilerlemesini getir
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLearningProgress()
    {
        try {
            // Öğrenme sistemini yükle
            $learningSystem = $this->loadLearningSystem();
            
            // İlerleme bilgisini al
            $progress = $learningSystem->getProgress();
            
            return response()->json([
                'success' => true,
                'data' => $progress
            ]);
        } catch (\Exception $e) {
            Log::error('Öğrenme ilerleme bilgisi alma hatası: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Öğrenme ilerleme bilgisi alma hatası: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Öğrenme işlemini durdur
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stopLearningProcess()
    {
        try {
            $learningSystem = $this->loadLearningSystem();
            
            $result = $learningSystem->stopLearning();
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result['data'] ?? null
            ]);
        } catch (\Exception $e) {
            Log::error('Öğrenme işlemi durdurulurken hata: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Öğrenme işlemi durdurulurken bir hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Otomatik olarak kelimeler seçip akıllı cümleler oluştur
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateAutoSentences(Request $request)
    {
        try {
            // Parametreleri doğrula
            $request->validate([
                'count' => 'nullable|integer|min:1|max:50',
                'save' => 'nullable|string'
            ]);
            
            $count = $request->input('count', 10);
            $save = $request->input('save') === "1";
            
            // Debug için log
            Log::info("Otomatik cümle oluşturma başladı: count=$count, save=" . ($save ? 'true' : 'false'));
            
            // LearningSystem'ı yükle
            $learningSystem = $this->loadLearningSystem();
            if (!$learningSystem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Öğrenme sistemi başlatılamadı'
                ], 500);
            }
            
            // WordRelations sınıfını başlat
            $wordRelations = app(WordRelations::class);
            
            // Önce öğrenilmiş kelimeleri kontrol et
            $learnedWords = AIData::pluck('word')->toArray();
            
            if (count($learnedWords) > 0) {
                Log::info("Veritabanında " . count($learnedWords) . " öğrenilmiş kelime bulundu");
                
                // Önce mevcut kelimelerden bazılarını kullan
                $randomLearnedWords = array_slice($learnedWords, 0, min(intval($count/2), 5));
                
                // Kalan sayıda yeni kelime öğren
                $remainingCount = $count - count($randomLearnedWords);
                $newWords = $learningSystem->getAutomaticWordsToLearn($remainingCount);
                
                $words = array_merge($randomLearnedWords, $newWords);
            } else {
                // Hiç öğrenilmiş kelime yoksa tamamen yeni kelimeler seç
                $words = $learningSystem->getAutomaticWordsToLearn($count);
            }
            
            if (empty($words)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Öğrenilecek kelime bulunamadı'
                ]);
            }
            
            Log::info("Otomatik seçilen kelimeler: " . implode(", ", $words));
            
            $allSentences = [];
            $learnedWords = [];
            
            // Her kelime için cümle oluştur
            foreach ($words as $word) {
                try {
                    // Önce kelimeyi öğren (eğer öğrenilmemişse)
                    if (!AIData::where('word', $word)->exists()) {
                        $result = $learningSystem->learnWord($word);
                        if ($result['success']) {
                            $learnedWords[] = $word;
                            Log::info("$word kelimesi başarıyla öğrenildi");
                        } else {
                            Log::warning("Kelime öğrenme hatası: " . $result['message']);
                            continue;
                        }
                    }
                    
                    // Akıllı cümleler oluştur
                    $sentences = $wordRelations->generateSmartSentences($word, $save, 3);
                    
                    Log::info("$word kelimesi için " . count($sentences) . " cümle oluşturuldu");
                    
                    if (!empty($sentences)) {
                        $allSentences[$word] = $sentences;
                    }
                } catch (\Exception $e) {
                    Log::error("$word kelimesi işlenirken hata: " . $e->getMessage());
                }
            }
            
            if (empty($allSentences)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hiç cümle oluşturulamadı. Sistem kelime öğreniyor olabilir, lütfen biraz bekleyin ve tekrar deneyin.'
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => count($allSentences) . ' kelime için toplam ' . array_sum(array_map('count', $allSentences)) . ' cümle oluşturuldu',
                'data' => [
                    'words' => array_keys($allSentences),
                    'sentences' => $allSentences,
                    'newly_learned' => $learnedWords
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Otomatik cümle oluşturma hatası: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Otomatik cümle oluşturma hatası: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Tüm öğrenilen kelimeleri getir
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function getAllWords(Request $request)
    {
        try {
            // Filtreleme parametreleri
            $search = $request->input('search', '');
            $category = $request->input('category', '');
            $sort = $request->input('sort', 'word');
            $order = $request->input('order', 'asc');
            
            // Kelime sorgusunu oluştur
            $query = AIData::query();
            
            // Arama filtresi
            if (!empty($search)) {
                $query->where('word', 'like', "%{$search}%")
                    ->orWhere('sentence', 'like', "%{$search}%");
            }
            
            // Kategori filtresi
            if (!empty($category)) {
                $query->where('category', $category);
            }
            
            // Sıralama
            $query->orderBy($sort, $order);
            
            // Sayfalandırılmış sonuçları al
            $words = $query->paginate(20);
            
            // Benzersiz kategorileri al
            $categories = AIData::distinct()->pluck('category');
            
            return view('ai.words', [
                'words' => $words,
                'categories' => $categories,
                'search' => $search,
                'category' => $category,
                'sort' => $sort,
                'order' => $order
            ]);
        } catch (\Exception $e) {
            Log::error('Kelime listesi alma hatası: ' . $e->getMessage());
            
            return view('ai.words', [
                'error' => 'Kelime listesi alınırken bir hata oluştu: ' . $e->getMessage(),
                'words' => collect(),
                'categories' => collect()
            ]);
        }
    }
    
    /**
     * Son eklenen belirli sayıda veriyi sil
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteRecentData(Request $request)
    {
        try {
            // Parametreleri doğrula
            $request->validate([
                'count' => 'required|integer|min:1|max:100',
                'confirm' => 'required|string'
            ]);
            
            // Güvenlik kontrolü
            if ($request->input('confirm') !== 'yes') {
                return response()->json([
                    'success' => false,
                    'message' => 'İşlemi onaylamanız gerekiyor.'
                ]);
            }
            
            $count = $request->input('count', 20);
            
            // Son eklenen verileri bul
            $recentData = AIData::orderBy('created_at', 'desc')
                ->limit($count)
                ->get();
                
            // Verileri sil
            foreach ($recentData as $data) {
                $data->delete();
            }
            
            return response()->json([
                'success' => true,
                'message' => "Son eklenen $count veri başarıyla silindi.",
                'count' => $recentData->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Son verileri silme hatası: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Son verileri silme hatası: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Belirli bir tarihten önce eklenmiş verileri sil
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteOldData(Request $request)
    {
        try {
            // Parametreleri doğrula
            $request->validate([
                'days' => 'required|integer|min:1|max:365',
                'confirm' => 'required|string'
            ]);
            
            // Güvenlik kontrolü
            if ($request->input('confirm') !== 'yes') {
                return response()->json([
                    'success' => false,
                    'message' => 'İşlemi onaylamanız gerekiyor.'
                ]);
            }
            
            $days = $request->input('days', 30);
            $date = now()->subDays($days);
            
            // Belirli tarihten önce eklenmiş verileri sil
            $count = AIData::where('created_at', '<', $date)->count();
            AIData::where('created_at', '<', $date)->delete();
            
            return response()->json([
                'success' => true,
                'message' => "$days günden eski $count veri başarıyla silindi.",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            Log::error('Eski verileri silme hatası: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Eski verileri silme hatası: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Düşük kaliteli verileri temizle (örn. tekrarlanan veriler, kısa tanımlar)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cleanupData(Request $request)
    {
        try {
            // Parametreleri doğrula
            $request->validate([
                'confirm' => 'required|string'
            ]);
            
            // Güvenlik kontrolü
            if ($request->input('confirm') !== 'yes') {
                return response()->json([
                    'success' => false,
                    'message' => 'İşlemi onaylamanız gerekiyor.'
                ]);
            }
            
            // Düşük kaliteli verileri bul
            $deleted = 0;
            
            // 1. Çok kısa tanımlı verileri sil
            $shortDataCount = AIData::whereRaw('LENGTH(sentence) < 10')->count();
            AIData::whereRaw('LENGTH(sentence) < 10')->delete();
            $deleted += $shortDataCount;
            
            // 2. Tekrarlanan kelimeleri temizle (ilkini tut)
            $uniqueWords = [];
            $duplicates = [];
            
            $allWords = AIData::select('id', 'word')->get();
            
            foreach ($allWords as $data) {
                $word = strtolower(trim($data->word));
                
                if (in_array($word, $uniqueWords)) {
                    $duplicates[] = $data->id;
                } else {
                    $uniqueWords[] = $word;
                }
            }
            
            // Tekrar eden verileri sil
            if (!empty($duplicates)) {
                AIData::whereIn('id', $duplicates)->delete();
                $deleted += count($duplicates);
            }
            
            return response()->json([
                'success' => true,
                'message' => "Toplam $deleted düşük kaliteli veri temizlendi.",
                'details' => [
                    'short_data' => $shortDataCount,
                    'duplicates' => count($duplicates)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Veri temizleme hatası: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Veri temizleme hatası: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Veritabanını optimize et
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function optimizeDatabase(Request $request)
    {
        try {
            // Güvenlik kontrolü
            if ($request->input('confirm') !== 'yes') {
                return response()->json([
                    'success' => false,
                    'message' => 'İşlemi onaylamanız gerekiyor.'
                ]);
            }
            
            // 1. Kullanılmayan ilişkileri temizle
            $orphanedRelations = DB::table('word_relations')
                ->leftJoin('ai_data as w1', 'word_relations.word1', '=', 'w1.word')
                ->leftJoin('ai_data as w2', 'word_relations.word2', '=', 'w2.word')
                ->whereNull('w1.id')
                ->orWhereNull('w2.id')
                ->count();
                
            DB::table('word_relations')
                ->leftJoin('ai_data as w1', 'word_relations.word1', '=', 'w1.word')
                ->leftJoin('ai_data as w2', 'word_relations.word2', '=', 'w2.word')
                ->whereNull('w1.id')
                ->orWhereNull('w2.id')
                ->delete();
            
            // 2. Boş kategorileri temizle
            $emptyCategories = DB::table('word_categories')
                ->leftJoin('word_category_items', 'word_categories.id', '=', 'word_category_items.category_id')
                ->whereNull('word_category_items.id')
                ->count();
                
            DB::table('word_categories')
                ->leftJoin('word_category_items', 'word_categories.id', '=', 'word_category_items.category_id')
                ->whereNull('word_category_items.id')
                ->delete();
            
            // 3. Frekans ve geçerlilik değerlerini güncelle
            AIData::whereNull('frequency')->update(['frequency' => 1]);
            AIData::whereNull('confidence')->update(['confidence' => 0.7]);
            
            return response()->json([
                'success' => true,
                'message' => 'Veritabanı başarıyla optimize edildi.',
                'details' => [
                    'orphaned_relations' => $orphanedRelations,
                    'empty_categories' => $emptyCategories
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Veritabanı optimizasyon hatası: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Veritabanı optimizasyon hatası: ' . $e->getMessage()
            ], 500);
        }
    }
}
