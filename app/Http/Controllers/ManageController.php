<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\AI\Learn\LearningSystem;
use App\AI\Core\CategoryManager;
use App\AI\Core\WordRelations;
use App\Models\AIData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

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
            
            // Son işlenen kelimeler, eş anlamlılar ve zıt anlamlılar
            $processedWords = $this->getLastProcessedWords(10);
            
            // Son duruma ekle
            $status['recent_words'] = $processedWords['words'];
            
            if (!empty($status['last_word'])) {
                // Son işlenen kelime için eş ve zıt anlamlıları al
                $wordRelations = app(\App\AI\Core\WordRelations::class);
                $status['last_processed_word'] = $status['last_word'];
                $status['last_synonyms'] = $wordRelations->getSynonyms($status['last_word']);
                $status['last_antonyms'] = $wordRelations->getAntonyms($status['last_word']);
                
                // Tanımı bul
                $definition = $wordRelations->getDefinition($status['last_word']);
                if (!empty($definition)) {
                    $status['last_definition'] = $definition;
                }
            }
            
            // İstatistikleri alalım
            try {
                $wordStats = $wordRelations->getStats();
                $status['synonym_count'] = $wordStats['synonym_pairs'];
                $status['antonym_count'] = $wordStats['antonym_pairs'];
                $status['total_words'] = \App\Models\AIData::count();
            } catch (\Exception $e) {
                // İstatistikler alınamazsa devam et
                Log::warning("İstatistikler alınamadı: " . $e->getMessage());
            }
            
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
     * Son işlenen kelimeleri ve ilişkilerini getir
     *
     * @param int $limit
     * @return array
     */
    private function getLastProcessedWords($limit = 10)
    {
        try {
            // WordRelations sınıfını başlat
            $wordRelations = app(\App\AI\Core\WordRelations::class);
            
            // Son işlenen kelimeleri al
            $latestWords = \App\Models\AIData::orderBy('updated_at', 'desc')
                ->limit($limit)
                ->get(['id', 'word', 'updated_at', 'metadata']);
            
            $result = [
                'words' => []
            ];
            
            foreach ($latestWords as $wordData) {
                // Her kelime için eş ve zıt anlamlıları al
                $synonyms = $wordRelations->getSynonyms($wordData->word);
                $antonyms = $wordRelations->getAntonyms($wordData->word);
                $definition = $wordRelations->getDefinition($wordData->word);
                
                // Tanım bulunamazsa alternatif kaynağı kontrol et
                if (empty($definition)) {
                    $metadata = json_decode($wordData->metadata, true);
                    if (!empty($metadata['definitions'][0])) {
                        $definition = $metadata['definitions'][0];
                    } else if (!empty($wordData->sentence)) {
                        $definition = $wordData->sentence;
                    }
                }
                
                // Kelime bilgilerini ekle
                $result['words'][] = [
                    'word' => $wordData->word,
                    'synonyms' => $synonyms,
                    'antonyms' => $antonyms,
                    'definition' => $definition,
                    'updated_at' => $wordData->updated_at->diffForHumans()
                ];
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Son işlenen kelimeleri getirirken hata: ' . $e->getMessage());
            return [
                'words' => []
            ];
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
            
            // WordRelations sınıfından istatistikleri al
            $wordRelations = app(\App\AI\Core\WordRelations::class);
            $stats = $wordRelations->getStats();
            
            // Eğer değerler boş geliyorsa varsayılan değerler ata
            if (!isset($progress['total_words'])) {
                $progress['total_words'] = \App\Models\AIData::count() ?? 0;
            }
            
            if (!isset($progress['synonym_count'])) {
                $progress['synonym_count'] = $stats['synonym_pairs'] ?? 0;
            }
            
            if (!isset($progress['antonym_count'])) {
                $progress['antonym_count'] = $stats['antonym_pairs'] ?? 0;
            }
            
            // Son işlenen kelimeler
            if (!isset($progress['recent_words'])) {
                $progress['recent_words'] = $this->getLastProcessedWords(10);
            }
            
            // Progress yapısını debug için logla
            Log::info("Öğrenme ilerleme bilgisi:", ['progress' => $progress]);
            
            return response()->json([
                'success' => true,
                'data' => $progress
            ]);
        } catch (\Exception $e) {
            Log::error('Öğrenme ilerleme bilgisi alma hatası: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Hata durumunda varsayılan değerlerle yanıt ver
            $defaultProgress = [
                'is_learning' => false,
                'total_words' => \App\Models\AIData::count() ?? 0,
                'synonym_count' => 0,
                'antonym_count' => 0,
                'recent_words' => []
            ];
            
            return response()->json([
                'success' => true,
                'data' => $defaultProgress,
                'error_message' => $e->getMessage()
            ]);
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
    
    /**
     * Kelimelerin eş anlamlı ve zıt anlamlı ilişkilerini geliştir
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function enhanceWordRelations(Request $request)
    {
        try {
            // PHP çalışma süresini artır - 5 dakika
            ini_set('max_execution_time', 300);
            
            // Parametreleri doğrula
            $request->validate([
                'limit' => 'nullable|integer|min:1|max:1000',
                'min_strength' => 'nullable|numeric|min:0|max:1',
                'confirm' => 'required|string'
            ]);
            
            // Güvenlik kontrolü
            if ($request->input('confirm') !== 'yes') {
                return response()->json([
                    'success' => false,
                    'message' => 'İşlemi onaylamanız gerekiyor.'
                ]);
            }
            
            $limit = $request->input('limit', 10); // Çok fazla kelime işleme sorununa karşı 200 yerine 10 olarak değiştirildi
            $minStrength = $request->input('min_strength', 0.5);
            
            // WordRelations ve AIDataCollectorService sınıflarını başlat
            $wordRelations = app(\App\AI\Core\WordRelations::class);
            $aiDataCollector = app(\App\Services\AIDataCollectorService::class);
            
            // İşlemek için kelimeleri al
            $words = \App\Models\AIData::select('word')
                ->orderBy('frequency', 'desc') 
                ->limit($limit)
                ->pluck('word')
                ->toArray();
                
            if (empty($words)) {
                return response()->json([
                    'success' => false,
                    'message' => 'İşlenecek kelime bulunamadı.'
                ]);
            }
            
            $stats = [
                'total_words' => count($words),
                'processed_words' => 0,
                'new_synonyms' => 0,
                'new_antonyms' => 0,
                'new_associations' => 0,
                'failed_words' => []
            ];
            
            // İşlenen kelimeleri ve ilişkilerini saklamak için değişkenler
            $processedWords = [];
            $processedSynonyms = [];
            $processedAntonyms = [];
            
            // Her kelime için eş ve zıt anlamlıları genişlet
            foreach ($words as $index => $word) {
                try {
                    // İlerleme durumunu log'a yaz
                    Log::info("Kelime işleniyor ({$index}/{$stats['total_words']}): $word");
                    
                    // İşlenen kelimeyi listeye ekle
                    $processedWords[] = $word;
                    
                    // Kelime için eş ve zıt anlamlı dizileri
                    $wordSynonyms = [];
                    $wordAntonyms = [];
                    
                    // Önce doğrudan TDK sorgusu yap
                    $tdkData = $this->collectWordFromTDK($word);
                    
                    Log::info("TDK'dan veri toplama sonucu: " . ($tdkData['success'] ? 'Başarılı' : 'Başarısız'));
                    Log::info("Bulunan eş anlamlı sayısı: " . count($tdkData['data']['synonyms']));
                    Log::info("Bulunan zıt anlamlı sayısı: " . count($tdkData['data']['antonyms']));
                    
                    if ($tdkData['success']) {
                        // Eş anlamlıları işle
                        if (!empty($tdkData['data']['synonyms'])) {
                            foreach ($tdkData['data']['synonyms'] as $synonym) {
                                if (strlen($synonym) < 2) continue; // Çok kısa kelimeleri atla
                                
                                try {
                                    // Veritabanına kaydet
                                    $inserted = $this->saveSynonymToDatabase($word, $synonym);
                                    
                                    if ($inserted) {
                                        $stats['new_synonyms']++;
                                        $wordSynonyms[] = $synonym;
                                        Log::info("Eş anlamlı kelime eklendi: $word -> $synonym");
                                    }
                                } catch (\Exception $e) {
                                    Log::error("Eş anlamlı kelime kaydedilirken hata oluştu: " . $e->getMessage());
                                }
                            }
                        }
                        
                        // Zıt anlamlıları işle
                        if (!empty($tdkData['data']['antonyms'])) {
                            foreach ($tdkData['data']['antonyms'] as $antonym) {
                                if (strlen($antonym) < 2) continue; // Çok kısa kelimeleri atla
                                
                                try {
                                    // Veritabanına kaydet
                                    $inserted = $this->saveAntonymToDatabase($word, $antonym);
                                    
                                    if ($inserted) {
                                        $stats['new_antonyms']++;
                                        $wordAntonyms[] = $antonym;
                                        Log::info("Zıt anlamlı kelime eklendi: $word -> $antonym");
                                    }
                                } catch (\Exception $e) {
                                    Log::error("Zıt anlamlı kelime kaydedilirken hata oluştu: " . $e->getMessage());
                                }
                            }
                        }
                        
                        // Tanımlar varsa kelimenin AIData kaydını güncelle
                        if (!empty($tdkData['data']['definition'])) {
                            // AIData tablosundaki kelime kaydını güncelle
                            try {
                                $aiData = \App\Models\AIData::where('word', $word)->first();
                                
                                if ($aiData) {
                                    // Tanımı güncelle
                                    if (empty($aiData->sentence)) {
                                        $aiData->sentence = $tdkData['data']['definition'];
                                    }
                                    
                                    // Metadata alanını güncelle
                                    $metadata = json_decode($aiData->metadata ?: '{}', true) ?: [];
                                    
                                    if (!isset($metadata['definitions'])) {
                                        $metadata['definitions'] = [];
                                    }
                                    
                                    // Tanımı metadata'ya ekle eğer yoksa
                                    if (!in_array($tdkData['data']['definition'], $metadata['definitions'])) {
                                        $metadata['definitions'][] = $tdkData['data']['definition'];
                                    }
                                    
                                    // İşlem zamanını ekle
                                    $metadata['tdk_enhanced'] = true;
                                    $metadata['last_enhanced'] = now()->toDateTimeString();
                                    
                                    $aiData->metadata = json_encode($metadata);
                                    $aiData->save();
                                }
                            } catch (\Exception $e) {
                                Log::error("AIData güncellenirken hata oluştu: " . $e->getMessage());
                            }
                        }
                        
                        // Tanımdan ilişkili kelimeleri çıkar
                        if (!empty($tdkData['data']['definition'])) {
                            // Tanımdan anahtar kelimeleri çıkar
                            $definition = $tdkData['data']['definition'];
                            $keywords = $this->extractKeywordsFromText($definition);
                            
                            // İlişkili kelimeleri öğren
                            foreach ($keywords as $keyword) {
                                if ($wordRelations->learnAssociation($word, $keyword, 'definition', 0.7)) {
                                    $stats['new_associations']++;
                                }
                            }
                            
                            // Tanımı da kaydet
                            $wordRelations->learnDefinition($word, $definition, true);
                        }
                        
                        // İşlenen kelime sayısını artır
                        $stats['processed_words']++;
                        
                        // Eş ve zıt anlamlı listelerine ekle
                        if (!empty($wordSynonyms)) {
                            $processedSynonyms[$word] = $wordSynonyms;
                        }
                        
                        if (!empty($wordAntonyms)) {
                            $processedAntonyms[$word] = $wordAntonyms;
                        }
                    } else {
                        // TDK başarısız olduysa alternatif veri toplamayı dene
                        $result = $aiDataCollector->collectWordData($word);
                        
                        if ($result['success']) {
                            $stats['processed_words']++;
                        } else {
                            $stats['failed_words'][] = $word;
                        }
                    }
                    
                    // CPU ve bellek kullanımı çok yoğunsa kısa bir bekleme ekle
                    if ($index % 3 == 0) {
                        usleep(300000); // 0.3 saniye bekle
                    }
                    
                } catch (\Exception $e) {
                    Log::error("Kelime ilişkileri geliştirme hatası ($word): " . $e->getMessage());
                    $stats['failed_words'][] = $word;
                }
            }
            
            // İlişkileri topla ve kaydet
            $wordRelations->collectAndLearnRelations();
            
            // İşlenen kelimeler ve ilişkileri sonuca ekle
            $stats['processed_word_list'] = $processedWords;
            $stats['processed_synonyms'] = $processedSynonyms;
            $stats['processed_antonyms'] = $processedAntonyms;
            
            return response()->json([
                'success' => true,
                'message' => "Kelime ilişkileri başarıyla geliştirildi. {$stats['processed_words']} kelime işlendi.",
                'stats' => $stats
            ]);
            
        } catch (\Exception $e) {
            Log::error('Kelime ilişkileri geliştirme hatası: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Kelime ilişkileri geliştirme hatası: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Eş anlamlı kelimeyi veritabanına kaydet
     * 
     * @param string $word Ana kelime
     * @param string $synonym Eş anlamlı kelime
     * @return bool Başarı durumu
     */
    private function saveSynonymToDatabase($word, $synonym)
    {
        try {
            // İki kelime aynı mı kontrol et
            if ($word === $synonym) {
                return false;
            }
            
            // İlk olarak word_relations tablosuna eş anlamlı ekle
            DB::table('word_relations')->updateOrInsert(
                [
                    'word' => $word,
                    'related_word' => $synonym,
                    'relation_type' => 'synonym',
                    'language' => 'tr'
                ],
                [
                    'strength' => 0.9,
                    'is_verified' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
            
            // Ters yönlü ilişkiyi de ekle
            DB::table('word_relations')->updateOrInsert(
                [
                    'word' => $synonym,
                    'related_word' => $word,
                    'relation_type' => 'synonym',
                    'language' => 'tr'
                ],
                [
                    'strength' => 0.9,
                    'is_verified' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
            
            // İkinci olarak AIData tablosundaki related_words alanını güncelle
            $aiData = \App\Models\AIData::where('word', $word)->first();
            
            if ($aiData) {
                $relatedWords = json_decode($aiData->related_words ?: '[]', true) ?: [];
                
                // Eş anlamlı kelime zaten var mı kontrol et
                $exists = false;
                foreach ($relatedWords as $relation) {
                    if (is_array($relation) && isset($relation['word']) && $relation['word'] === $synonym && isset($relation['type']) && $relation['type'] === 'synonym') {
                        $exists = true;
                        break;
                    } else if (is_string($relation) && $relation === $synonym) {
                        $exists = true;
                        break;
                    }
                }
                
                // Eş anlamlı kelimeyi ekle
                if (!$exists) {
                    $relatedWords[] = [
                        'word' => $synonym,
                        'type' => 'synonym',
                        'strength' => 0.9
                    ];
                    
                    $aiData->related_words = json_encode($relatedWords);
                    $aiData->save();
                }
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error("Eş anlamlı kelime veritabanına kaydedilemedi: " . $e->getMessage(), [
                'word' => $word,
                'synonym' => $synonym
            ]);
            return false;
        }
    }
    
    /**
     * Zıt anlamlı kelimeyi veritabanına kaydet
     * 
     * @param string $word Ana kelime
     * @param string $antonym Zıt anlamlı kelime
     * @return bool Başarı durumu
     */
    private function saveAntonymToDatabase($word, $antonym)
    {
        try {
            // İki kelime aynı mı kontrol et
            if ($word === $antonym) {
                return false;
            }
            
            // İlk olarak word_relations tablosuna zıt anlamlı ekle
            DB::table('word_relations')->updateOrInsert(
                [
                    'word' => $word,
                    'related_word' => $antonym,
                    'relation_type' => 'antonym',
                    'language' => 'tr'
                ],
                [
                    'strength' => 0.9,
                    'is_verified' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
            
            // Ters yönlü ilişkiyi de ekle
            DB::table('word_relations')->updateOrInsert(
                [
                    'word' => $antonym,
                    'related_word' => $word,
                    'relation_type' => 'antonym',
                    'language' => 'tr'
                ],
                [
                    'strength' => 0.9,
                    'is_verified' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
            
            // İkinci olarak AIData tablosundaki related_words alanını güncelle
            $aiData = \App\Models\AIData::where('word', $word)->first();
            
            if ($aiData) {
                $relatedWords = json_decode($aiData->related_words ?: '[]', true) ?: [];
                
                // Zıt anlamlı kelime zaten var mı kontrol et
                $exists = false;
                foreach ($relatedWords as $relation) {
                    if (is_array($relation) && isset($relation['word']) && $relation['word'] === $antonym && isset($relation['type']) && $relation['type'] === 'antonym') {
                        $exists = true;
                        break;
                    }
                }
                
                // Zıt anlamlı kelimeyi ekle
                if (!$exists) {
                    $relatedWords[] = [
                        'word' => $antonym,
                        'type' => 'antonym',
                        'strength' => 0.9
                    ];
                    
                    $aiData->related_words = json_encode($relatedWords);
                    $aiData->save();
                }
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error("Zıt anlamlı kelime veritabanına kaydedilemedi: " . $e->getMessage(), [
                'word' => $word,
                'antonym' => $antonym
            ]);
            return false;
        }
    }
    
    /**
     * TDK sözlüğünden kelime verilerini topla
     */
    private function collectWordFromTDK($word)
    {
        $result = [
            'success' => false,
            'data' => [
                'definition' => '',
                'synonyms' => [],
                'antonyms' => []
            ]
        ];
        
        try {
            // 1. TDK'dan veri topla
            // Timeout değeri ekle (5 saniye)
            $response = Http::timeout(5)->get('https://sozluk.gov.tr/gts', [
                'ara' => $word
            ]);
            
            Log::info("TDK'ya sorgu gönderildi: $word");
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Yanıt içeriğini logla
                Log::debug("TDK API yanıtı detayı: " . json_encode(array_slice((array)$data, 0, 1), JSON_UNESCAPED_UNICODE));
                
                // Veri doğru formatta mı kontrolü
                if (!empty($data) && is_array($data) && !empty($data[0])) {
                    $result['success'] = true;
                    
                    // Tanımları al
                    if (!empty($data[0]['anlamlarListe'])) {
                        $firstMeaning = $data[0]['anlamlarListe'][0]['anlam'] ?? '';
                        $result['data']['definition'] = $firstMeaning;
                        
                        // Tüm anlamları kontrol ederek eş anlamlı ve zıt anlamlı kontrolü
                        foreach ($data[0]['anlamlarListe'] as $anlam) {
                            $anlamText = $anlam['anlam'] ?? '';
                            $anlamHtml = $anlam['anlam_html'] ?? '';
                            
                            // Anlam metnini ve HTML versiyonunu logla
                            Log::debug("Anlam metni detayı:", ['text' => $anlamText, 'html' => $anlamHtml]);
                            
                            // Eş anlamlıları bul (Hem metin hem HTML'de ara)
                            $this->findSynonymsInText($anlamText, $result['data']['synonyms']);
                            if (!empty($anlamHtml)) {
                                $this->findSynonymsInText(strip_tags($anlamHtml), $result['data']['synonyms']);
                            }
                            
                            // Zıt anlamlıları bul (Hem metin hem HTML'de ara)
                            $this->findAntonymsInText($anlamText, $result['data']['antonyms']);
                            if (!empty($anlamHtml)) {
                                $this->findAntonymsInText(strip_tags($anlamHtml), $result['data']['antonyms']);
                            }
                            
                            // Özellikler listesini kontrol et - bazen burada eş/zıt anlamlılar olabilir
                            if (isset($anlam['ozelliklerListe']) && is_array($anlam['ozelliklerListe'])) {
                                foreach ($anlam['ozelliklerListe'] as $ozellik) {
                                    if (isset($ozellik['tam_adi'])) {
                                        // Zıt anlamlı veya karşıtı gibi özellikler
                                        if (strpos(mb_strtolower($ozellik['tam_adi'], 'UTF-8'), 'karş') !== false ||
                                            strpos(mb_strtolower($ozellik['tam_adi'], 'UTF-8'), 'zıt') !== false) {
                                            Log::debug("Özellik listesinde zıt anlamlı referansı: " . $ozellik['tam_adi']);
                                            
                                            // Tanımda bu özelliğe ait zıt anlamlı kavram olabilir
                                            $this->findAntonymsInText($anlamText, $result['data']['antonyms']);
                                        }
                                    }
                                }
                            }
                            
                            // Yönlendirme işaretini kontrol et (►)
                            if (strpos($anlamText, '►') !== false || strpos($anlamHtml, '►') !== false) {
                                // Bu bir yönlendirme, eş anlamlı olabilir
                                preg_match('/►\s*([^\.,;]+)/', $anlamText, $matches);
                                if (!empty($matches[1])) {
                                    $synonym = trim($matches[1]);
                                    if (!in_array($synonym, $result['data']['synonyms'])) {
                                        $result['data']['synonyms'][] = $synonym;
                                        Log::debug("Yönlendirme eş anlamlı bulundu: " . $synonym);
                                    }
                                }
                            }
                            
                            // Metinde tanımlayıcı virgüller varsa bunlar eş anlamlı olabilir
                            if (preg_match('/^([^,;\.]+),\s*([^,;\.]+)(?:,\s*([^,;\.]+))?(?:,\s*([^,;\.]+))?/u', $anlamText, $matches)) {
                                for ($i = 2; $i < count($matches); $i++) {
                                    if (!empty($matches[$i])) {
                                        $synonym = trim($matches[$i]);
                                        if (!in_array($synonym, $result['data']['synonyms']) && strlen($synonym) > 2) {
                                            $result['data']['synonyms'][] = $synonym;
                                            Log::debug("Tanım parçasından olası eş anlamlı: " . $synonym);
                                        }
                                    }
                                }
                            }
                            
                            // Örnekleri kontrol et
                            if (isset($anlam['orneklerListe']) && is_array($anlam['orneklerListe'])) {
                                foreach ($anlam['orneklerListe'] as $ornek) {
                                    if (isset($ornek['ornek'])) {
                                        $ornekText = $ornek['ornek'];
                                        $this->findSynonymsInText($ornekText, $result['data']['synonyms']);
                                        $this->findAntonymsInText($ornekText, $result['data']['antonyms']);
                                    }
                                }
                            }
                        }
                    }
                    
                    // Birleşik kelimeler içinde eş/zıt anlamlı kelimeler olabilir
                    if (!empty($data[0]['birlesikler'])) {
                        $birlesikler = explode(',', $data[0]['birlesikler']);
                        foreach ($birlesikler as $birlesik) {
                            $birlesik = trim($birlesik);
                            // Eş/zıt anlamlıyı içeren birleşik kelimeler olabilir
                            if (strpos($birlesik, 'eş') !== false || strpos($birlesik, 'aynı') !== false) {
                                Log::debug("Eş anlamlı içeren birleşik kelime: " . $birlesik);
                            }
                            if (strpos($birlesik, 'zıt') !== false || strpos($birlesik, 'karşıt') !== false) {
                                Log::debug("Zıt anlamlı içeren birleşik kelime: " . $birlesik);
                                
                                // Birleşik kelimeden zıt anlamlı çıkarma girişimi
                                if (preg_match('/([a-zçğıöşü]+)(?:zıt|karşıt|karşı)([a-zçğıöşü]+)/ui', $birlesik, $matches)) {
                                    if (!empty($matches[1]) && strlen($matches[1]) > 2 && !in_array($matches[1], $result['data']['antonyms'])) {
                                        $result['data']['antonyms'][] = $matches[1];
                                        Log::debug("Birleşik kelimeden zıt anlamlı bulundu: " . $matches[1]);
                                    }
                                    if (!empty($matches[2]) && strlen($matches[2]) > 2 && !in_array($matches[2], $result['data']['antonyms'])) {
                                        $result['data']['antonyms'][] = $matches[2];
                                        Log::debug("Birleşik kelimeden zıt anlamlı bulundu: " . $matches[2]);
                                    }
                                }
                            }
                        }
                    }
                    
                    // Özel alanları kontrol et
                    $possibleSynonymFields = ['es_anlamlilar', 'es_anlamlilari', 'esdeger', 'sinonim', 'sinonimler', 'sinonimleri'];
                    $possibleAntonymFields = ['zit_anlamlilar', 'zit_anlamlilari', 'karsilik', 'antonim', 'antonimler', 'antonimleri', 'karşıt_anlamlısı', 'karşıt_anlam'];
                    
                    // Log tüm alanları
                    Log::debug("TDK Kelime tüm alanları:", (array)$data[0]);
                    
                    // Eş anlamlılar için olası tüm alanları kontrol et
                    foreach ($possibleSynonymFields as $field) {
                        if (!empty($data[0][$field])) {
                            Log::debug("TDK yanıtında eş anlamlı alanı bulundu: " . $field . " = " . $data[0][$field]);
                            $synonyms = explode(',', $data[0][$field]);
                            foreach ($synonyms as $synonym) {
                                $synonym = trim($synonym);
                                if (!empty($synonym) && !in_array($synonym, $result['data']['synonyms'])) {
                                    $result['data']['synonyms'][] = $synonym;
                                }
                            }
                        }
                    }
                    
                    // Zıt anlamlılar için olası tüm alanları kontrol et
                    foreach ($possibleAntonymFields as $field) {
                        if (!empty($data[0][$field])) {
                            Log::debug("TDK yanıtında zıt anlamlı alanı bulundu: " . $field . " = " . $data[0][$field]);
                            $antonyms = explode(',', $data[0][$field]);
                            foreach ($antonyms as $antonym) {
                                $antonym = trim($antonym);
                                if (!empty($antonym) && !in_array($antonym, $result['data']['antonyms'])) {
                                    $result['data']['antonyms'][] = $antonym;
                                }
                            }
                        }
                    }
                    
                    // Ek olarak, TDK API'sinden atabilen "atasozu" ve "birlesikler" alanlarında da zıt anlamlı ipuçları olabilir 
                    if (!empty($data[0]['atasozu']) && is_array($data[0]['atasozu'])) {
                        foreach ($data[0]['atasozu'] as $atasozu) {
                            if (isset($atasozu['madde'])) {
                                // Atasözünde zıt anlam kalıpları ara
                                $this->findAntonymsInText($atasozu['madde'], $result['data']['antonyms']);
                            }
                        }
                    }
                    
                    // Tanımdan çıkarma (Detaylı analiz)
                    if (!empty($result['data']['definition'])) {
                        $definition = $result['data']['definition'];
                        
                        // Tanımda geçen çeşitli kalıpları kontrol et
                        $synonymPatterns = [
                            '/(\p{L}+)\s+(?:veya|ile|ya da)\s+(\p{L}+)/u',
                            '/(\p{L}+)\s+(?:denilen|denen|adı verilen)\s+(\p{L}+)/u',
                            '/(\p{L}+),\s*(\p{L}+)(?:,\s*(\p{L}+))?(?:,\s*(\p{L}+))?$/u'
                        ];
                        
                        foreach ($synonymPatterns as $pattern) {
                            if (preg_match_all($pattern, $definition, $matches)) {
                                for ($i = 1; $i < count($matches); $i++) {
                                    foreach ($matches[$i] as $match) {
                                        $match = trim($match);
                                        if (!empty($match) && strlen($match) > 2 && !in_array($match, $result['data']['synonyms'])) {
                                            $result['data']['synonyms'][] = $match;
                                            Log::debug("Tanımdan çıkarılan olası eş anlamlı: " . $match);
                                        }
                                    }
                                }
                            }
                        }
                        
                        // Tanımda zıt anlamlı ipuçları için ek kontrol
                        $antonymPatterns = [
                            '/(\p{L}+)\s+(?:değil|olmayan)/u',
                            '/(\p{L}+)\s+yerine\s+(\p{L}+)/u',
                            '/(?:zıt|karşıt) (?:anlamlı|olarak)\s+(\p{L}+)/u',
                            '/(?:tersine|aksine)\s+(\p{L}+)/u'
                        ];
                        
                        foreach ($antonymPatterns as $pattern) {
                            if (preg_match_all($pattern, $definition, $matches)) {
                                for ($i = 1; $i < count($matches); $i++) {
                                    foreach ($matches[$i] as $match) {
                                        $match = trim($match);
                                        if (!empty($match) && strlen($match) > 2 && !in_array($match, $result['data']['antonyms'])) {
                                            $result['data']['antonyms'][] = $match;
                                            Log::debug("Tanımdan çıkarılan olası zıt anlamlı: " . $match);
                                        }
                                    }
                                }
                            }
                        }
                        
                        // Çok kullanılan kalıpları tam olarak kontrol et
                        $hakikatPos = mb_stripos($definition, 'hakikat');
                        if ($hakikatPos !== false) {
                            $result['data']['synonyms'][] = 'hakikat';
                            Log::debug("Tanımda 'hakikat' kelimesi bulundu, eş anlamlı olarak eklendi");
                        }
                        
                        $gercePos = mb_stripos($definition, 'gerçeklik');
                        if ($gercePos !== false && $word !== 'gerçeklik') {
                            $result['data']['synonyms'][] = 'gerçeklik';
                            Log::debug("Tanımda 'gerçeklik' kelimesi bulundu, eş anlamlı olarak eklendi");
                        }
                        
                        $dogruPos = mb_stripos($definition, 'doğruluk');
                        if ($dogruPos !== false && $word !== 'doğruluk') {
                            $result['data']['synonyms'][] = 'doğruluk';
                            Log::debug("Tanımda 'doğruluk' kelimesi bulundu, eş anlamlı olarak eklendi");
                        }
                    }
                }
            }
            
            // 2. Eğer TDK'den yeterli veri alınamadıysa lokal veritabanı kontrolü yap
            if (empty($result['data']['synonyms']) || empty($result['data']['antonyms'])) {
                $this->findRelationsFromDatabase($word, $result);
            }
            
            // 3. Hala yeterli veri yoksa, alternatif yöntemlerle bul
            if (empty($result['data']['synonyms'])) {
                $this->findSynonymsAlternative($word, $result);
            }

            if (empty($result['data']['antonyms'])) {
                $this->findAntonymsAlternative($word, $result);
            }
            
            // Tekrar eden kelimeleri temizle
            $result['data']['synonyms'] = array_values(array_unique($result['data']['synonyms']));
            $result['data']['antonyms'] = array_values(array_unique($result['data']['antonyms']));
            
            // Kelimeden kendisini çıkar
            $result['data']['synonyms'] = array_diff($result['data']['synonyms'], [$word]);
            $result['data']['antonyms'] = array_diff($result['data']['antonyms'], [$word]);
            
            // Debug log ekle
            Log::info("Kelime ilişkileri toplama sonucu - $word: " . 
                count($result['data']['synonyms']) . " eş anlamlı, " . 
                count($result['data']['antonyms']) . " zıt anlamlı kelime bulundu."
            );
            
            // Eş ve zıt anlamlıları direkt loglayarak kontrol et
            if (!empty($result['data']['synonyms'])) {
                Log::info("$word eş anlamlıları: " . implode(", ", $result['data']['synonyms']));
            }
            
            if (!empty($result['data']['antonyms'])) {
                Log::info("$word zıt anlamlıları: " . implode(", ", $result['data']['antonyms']));
            }
            
            // Başarı durumunu güncelle - en azından tanım veya ilişki varsa başarılı say
            $result['success'] = !empty($result['data']['definition']) || 
                                !empty($result['data']['synonyms']) || 
                                !empty($result['data']['antonyms']);
            
            return $result;
        } catch (\Exception $e) {
            Log::error("TDK veri toplama hatası ($word): " . $e->getMessage());
            return $result;
        }
    }
    
    /**
     * Metinden eş anlamlıları bul ve çıkar
     * 
     * @param string $text Metin
     * @param array &$synonyms Eş anlamlılar listesi
     */
    private function findSynonymsInText($text, &$synonyms)
    {
        // Eş anlamlı kelimeler için yaygın kalıplar
        $patterns = [
            '/Eş anl\.\s*([^\.;,]+)/ui',          // "Eş anl." kalıbı
            '/eş anlamlısı:?\s*([^\.;,]+)/ui',    // "eş anlamlısı:" kalıbı
            '/eş ?anlamlı(?:lar|sı):?\s*([^\.;,]+)/ui', // "eş anlamlılar:" veya "eş anlamlısı:" kalıbı
            '/anlamdaşı:?\s*([^\.;,]+)/ui',       // "anlamdaşı:" kalıbı
            '/aynı anlama gelen:?\s*([^\.;,]+)/ui', // "aynı anlama gelen:" kalıbı
            '/aynı anlama (?:gelir|gelmektedir)/ui', // Cümlenin tamamı eş anlamlı ifade edebilir
            '/\bçın\b/ui',                         // "çın" kelimesi (gerçek için)
            '/\bciddi\b/ui',                       // "ciddi" kelimesi (gerçek için)
            '/\bhakikat\b/ui'                      // "hakikat" kelimesi (gerçek için)
        ];
        
        // TDK'daki "Eş anl." kalıbını özel olarak daha detaylı incele
        if (strpos($text, 'Eş anl.') !== false) {
            Log::debug("Eş anl. kalıbı bulundu: " . $text);
            preg_match('/Eş anl\.\s*([^\.]+)/ui', $text, $matches);
            
            if (!empty($matches[1])) {
                $words = explode(',', $matches[1]);
                foreach ($words as $word) {
                    $trimmed = trim($word);
                    if (strlen($trimmed) > 2 && !in_array($trimmed, $synonyms)) {
                        $synonyms[] = $trimmed;
                        Log::debug("Eş anl. kalıbından eş anlamlı bulundu: " . $trimmed);
                    }
                }
            }
        }
        
        // Diğer kalıpları kontrol et
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                if (!empty($matches[1])) {
                    $words = explode(',', $matches[1]);
                    foreach ($words as $word) {
                        $trimmed = trim($word);
                        if (strlen($trimmed) > 2 && !in_array($trimmed, $synonyms)) {
                            $synonyms[] = $trimmed;
                            Log::debug("Pattern ile eş anlamlı bulundu: " . $trimmed);
                        }
                    }
                } else if (preg_match('/\b(çın|ciddi|hakikat)\b/ui', $text, $simpleMatches)) {
                    // Basit kelime eşleşmesi
                    $trimmed = trim($simpleMatches[1]);
                    if (strlen($trimmed) > 2 && !in_array($trimmed, $synonyms)) {
                        $synonyms[] = $trimmed;
                        Log::debug("Basit eşleşme ile eş anlamlı bulundu: " . $trimmed);
                    }
                }
            }
        }
        
        // Tanım metin içinde eş anlamlı olabilecek kelimeler için yeni bir kontrol
        $definitionParts = explode(',', $text);
        if (count($definitionParts) > 1) {
            // Son parçalarda eş anlamlılar olabilir
            $lastParts = array_slice($definitionParts, 1);
            foreach ($lastParts as $part) {
                $cleanPart = trim($part);
                
                // Son parça kısa bir kelime veya kelime grubu olabilir
                if (mb_strlen($cleanPart) < 20 && mb_strlen($cleanPart) > 2 && !preg_match('/[.;:]/', $cleanPart)) {
                    // Kısa kelime veya kelime grubu, muhtemelen eş anlamlı
                    if (!in_array($cleanPart, $synonyms)) {
                        $synonyms[] = $cleanPart;
                        Log::debug("Tanım parçasından olası eş anlamlı: " . $cleanPart);
                    }
                }
            }
        }
    }
    
    /**
     * Metinden zıt anlamlıları bul ve çıkar
     * 
     * @param string $text Metin
     * @param array &$antonyms Zıt anlamlılar listesi
     */
    private function findAntonymsInText($text, &$antonyms)
    {
        // Zıt anlamlı kelimeler için yaygın kalıplar - Regex'i güçlendirelim
        $patterns = [
            '/Karş\.\s*([^\.;,]+)/ui',            // "Karş." kalıbı
            '/zıt anlamlısı:?\s*([^\.;,]+)/ui',   // "zıt anlamlısı:" kalıbı
            '/zıt ?anlamlı(?:lar|sı):?\s*([^\.;,]+)/ui', // "zıt anlamlılar:" veya "zıt anlamlısı:" kalıbı
            '/karşıt anlamlısı:?\s*([^\.;,]+)/ui', // "karşıt anlamlısı:" kalıbı
            '/karşıt anlama gelen:?\s*([^\.;,]+)/ui', // "karşıt anlama gelen:" kalıbı
            '/karşıtı:?\s*([^\.;,]+)/ui',        // "karşıtı:" kalıbı
            '/tersi:?\s*([^\.;,]+)/ui',          // "tersi:" kalıbı
            '/karşı anlamlısı:?\s*([^\.;,]+)/ui', // "karşı anlamlısı:" kalıbı
            '/\b(?:zıt|karşıt|karşı|ters)\b[^\.]*/ui',  // "zıt", "karşıt", "karşı" veya "ters" içeren herhangi bir ifade
            '/değil\b/ui',                        // "değil" kelimesi zıt anlam içerebilir
            '/aksine/ui'                         // "aksine" kelimesi zıt anlam içerebilir
        ];
        
        // TDK'daki "Karş." kalıbını özel olarak daha detaylı incele
        if (strpos($text, 'Karş.') !== false) {
            Log::debug("Karş. kalıbı bulundu: " . $text);
            preg_match('/Karş\.\s*([^\.]+)/ui', $text, $matches);
            
            if (!empty($matches[1])) {
                $words = explode(',', $matches[1]);
                foreach ($words as $word) {
                    $trimmed = trim($word);
                    if (strlen($trimmed) > 2 && !in_array($trimmed, $antonyms)) {
                        $antonyms[] = $trimmed;
                        Log::debug("Karş. kalıbından zıt anlamlı bulundu: " . $trimmed);
                    }
                }
            }
        }
        
        // Diğer kalıpları kontrol et
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                if (!empty($matches[1])) {
                    $words = explode(',', $matches[1]);
                    foreach ($words as $word) {
                        $trimmed = trim($word);
                        if (strlen($trimmed) > 2 && !in_array($trimmed, $antonyms)) {
                            $antonyms[] = $trimmed;
                            Log::debug("Pattern ile zıt anlamlı bulundu: " . $trimmed);
                        }
                    }
                } else if (preg_match('/\b((?:zıt|karşıt|karşı|ters)[^\s,\.;:]*)\b/ui', $text, $wordMatches)) {
                    // Kelime içinde "zıt", "karşıt" gibi parçalar olan kelimeleri çıkarma
                    $oppositeWord = $this->extractOppositeWordFromContext($text);
                    if ($oppositeWord && !in_array($oppositeWord, $antonyms)) {
                        $antonyms[] = $oppositeWord;
                        Log::debug("Bağlam analizi ile zıt anlamlı bulundu: " . $oppositeWord);
                    }
                }
            }
        }
        
        // "Karşıtı", "Zıddı" gibi ifadeler için ek kontrol
        $oppositePatterns = [
            '/([a-zçğıöşü]+)\'(?:ın|nin|nın|un|ün|nün|nın) (?:karşıtı|zıddı|tersi) ([a-zçğıöşü]+)/ui',
            '/([a-zçğıöşü]+) ile ([a-zçğıöşü]+) (?:zıt|karşıt) anlamlıdır/ui',
            '/([a-zçğıöşü]+) (?:kelimesinin|sözcüğünün) (?:zıt|karşıt|karşı) anlamlısı ([a-zçğıöşü]+)/ui',
            '/([a-zçğıöşü]+) değil, ([a-zçğıöşü]+)/ui',
            '/([a-zçğıöşü]+) yerine ([a-zçğıöşü]+)/ui'
        ];
        
        foreach ($oppositePatterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                for ($i = 0; $i < count($matches[0]); $i++) {
                    $word1 = trim($matches[1][$i]);
                    $word2 = trim($matches[2][$i]);
                    
                    if (strlen($word1) > 2 && !in_array($word1, $antonyms)) {
                        $antonyms[] = $word1;
                        Log::debug("Karşıtlık ifadesinden zıt anlamlı bulundu: " . $word1);
                    }
                    
                    if (strlen($word2) > 2 && !in_array($word2, $antonyms)) {
                        $antonyms[] = $word2;
                        Log::debug("Karşıtlık ifadesinden zıt anlamlı bulundu: " . $word2);
                    }
                }
            }
        }
        
        // Özellikler listesini kontrol et (mecaz, argo, terimi vb. içerebilir)
        if (strpos($text, 'karş.') !== false || strpos($text, 'zıt anl.') !== false) {
            // Metin içinde aşağı/yukarı yönlendirme varsa
            preg_match_all('/(↓|↑|→|zıt anl\.|karş\.)\s*([^\.;,]+)/ui', $text, $matches);
            
            if (!empty($matches[2])) {
                foreach ($matches[2] as $match) {
                    $trimmed = trim($match);
                    if (strlen($trimmed) > 2 && !in_array($trimmed, $antonyms)) {
                        $antonyms[] = $trimmed;
                        Log::debug("Yönlendirme işaretinden zıt anlamlı bulundu: " . $trimmed);
                    }
                }
            }
        }
    }
    
    /**
     * Metinden zıt anlamlı kelimeleri çıkarma bağlam analizi
     */
    private function extractOppositeWordFromContext($text)
    {
        // Tipik zıt anlamlı belirten kalıplar
        $contextPatterns = [
            '/(?:değil|olmayan) ([a-zçğıöşü]+)/ui',
            '/(?:zıt|karşıt|karşı) (?:olarak|anlamlı) ([a-zçğıöşü]+)/ui',
            '/(?:tersine|aksine) ([a-zçğıöşü]+)/ui'
        ];
        
        foreach ($contextPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                if (!empty($matches[1])) {
                    return trim($matches[1]);
                }
            }
        }
        
        return null;
    }
    
    /**
     * Veritabanından ilişkili kelimeleri bul
     */
    private function findRelationsFromDatabase($word, &$result)
    {
        try {
            // Eş anlamlıları bul
            $synonymRelations = DB::table('word_relations')
                ->where('word', $word)
                ->where('relation_type', 'synonym')
                ->get();
                
            foreach ($synonymRelations as $relation) {
                $result['data']['synonyms'][] = $relation->related_word;
            }
            
            // Zıt anlamlıları bul
            $antonymRelations = DB::table('word_relations')
                ->where('word', $word)
                ->where('relation_type', 'antonym')
                ->get();
                
            foreach ($antonymRelations as $relation) {
                $result['data']['antonyms'][] = $relation->related_word;
            }
            
            // Tanım yoksa veritabanından getir
            if (empty($result['data']['definition'])) {
                $definition = DB::table('word_definitions')
                    ->where('word', $word)
                    ->first();
                    
                if ($definition) {
                    $result['data']['definition'] = $definition->definition;
                }
            }
        } catch (\Exception $e) {
            Log::error("Veritabanından kelime ilişkisi bulma hatası ($word): " . $e->getMessage());
        }
    }
    
    /**
     * Alternatif yöntemlerle eş anlamlıları bul
     */
    private function findSynonymsAlternative($word, &$result)
    {
        // Türkçe'de yaygın bazı eş anlamlı kalıplar
        $commonSynonyms = [
            'güzel' => ['hoş', 'latif', 'şirin', 'alımlı', 'gösterişli'],
            'büyük' => ['iri', 'kocaman', 'devasa', 'geniş', 'yüce'],
            'küçük' => ['ufak', 'minik', 'minnacık', 'ince', 'dar'],
            'hızlı' => ['çabuk', 'süratli', 'tez', 'acil', 'çevik'],
            'yavaş' => ['ağır', 'aheste', 'telaşsız', 'sakin'],
            'iyi' => ['güzel', 'hoş', 'müsait', 'uygun', 'makbul'],
            'kötü' => ['fena', 'berbat', 'bozuk', 'çirkin', 'acı'],
            'zor' => ['güç', 'çetin', 'zahmetli', 'meşakkatli'],
            'kolay' => ['basit', 'zahmetsiz', 'yalın', 'anlaşılır'],
            'fazla' => ['çok', 'bol', 'gani', 'aşırı', 'artık'],
            'az' => ['eksik', 'yetersiz', 'kıt', 'nadir', 'ender']
        ];
        
        // Yaygın eş anlamlılardan bul
        if (isset($commonSynonyms[$word])) {
            $result['data']['synonyms'] = array_merge($result['data']['synonyms'], $commonSynonyms[$word]);
        }
        
        // Ters aramayı dene - onun eş anlamlısı bu kelimeyse bu kelimenin de eş anlamlısı o olmalı
        try {
            $reverseSynonyms = DB::table('word_relations')
                ->where('related_word', $word)
                ->where('relation_type', 'synonym')
                ->pluck('word')
                ->toArray();
                
            if (!empty($reverseSynonyms)) {
                $result['data']['synonyms'] = array_merge($result['data']['synonyms'], $reverseSynonyms);
            }
        } catch (\Exception $e) {
            Log::error("Ters eş anlamlı arama hatası ($word): " . $e->getMessage());
        }
    }
    
    /**
     * Alternatif yöntemlerle zıt anlamlıları bul
     */
    private function findAntonymsAlternative($word, &$result)
    {
        // Türkçe'de yaygın bazı zıt anlamlı kalıplar
        $commonAntonyms = [
            'güzel' => ['çirkin', 'kötü', 'berbat'],
            'büyük' => ['küçük', 'ufak', 'minik'],
            'küçük' => ['büyük', 'kocaman', 'iri'],
            'hızlı' => ['yavaş', 'ağır', 'aheste'],
            'yavaş' => ['hızlı', 'çabuk', 'süratli'],
            'iyi' => ['kötü', 'fena', 'berbat'],
            'kötü' => ['iyi', 'güzel', 'hoş'],
            'zor' => ['kolay', 'basit', 'düz'],
            'kolay' => ['zor', 'güç', 'çetin'],
            'fazla' => ['az', 'eksik', 'yetersiz'],
            'az' => ['fazla', 'çok', 'bol'],
            'açık' => ['kapalı', 'örtülü', 'gizli'],
            'kapalı' => ['açık', 'belli', 'ortada'],
            'soğuk' => ['sıcak', 'ılık', 'yakıcı'],
            'sıcak' => ['soğuk', 'buz gibi', 'serin'],
            'doğru' => ['yanlış', 'yalan', 'hatalı'],
            'yanlış' => ['doğru', 'gerçek', 'hakikat'],
            'gerçek' => ['sahte', 'yalan', 'hayal'],
            'sahte' => ['gerçek', 'hakiki', 'asıl'],
            'uzun' => ['kısa', 'dar', 'az'],
            'kısa' => ['uzun', 'geniş', 'büyük'],
            'geniş' => ['dar', 'sınırlı', 'küçük'],
            'dar' => ['geniş', 'büyük', 'ferah'],
            'siyah' => ['beyaz', 'ak', 'açık'],
            'beyaz' => ['siyah', 'kara', 'koyu'],
            'yaşlı' => ['genç', 'taze', 'körpe'],
            'genç' => ['yaşlı', 'ihtiyar', 'kocamış'],
            'temiz' => ['kirli', 'pis', 'bulaşık'],
            'kirli' => ['temiz', 'pak', 'arı'],
            'mutlu' => ['mutsuz', 'kederli', 'hüzünlü'],
            'mutsuz' => ['mutlu', 'neşeli', 'keyifli'],
            'eski' => ['yeni', 'taze', 'modern'],
            'yeni' => ['eski', 'kadim', 'antika'],
            'kolay' => ['zor', 'güç', 'karmaşık'],
            'zor' => ['kolay', 'basit', 'yalın'],
            'ileri' => ['geri', 'geride', 'arkada'],
            'geri' => ['ileri', 'önde', 'ileride'],
            'yazmak' => ['silmek', 'bozmak', 'çıkarmak'],
            'okumak' => ['yazmak', 'dinlemek', 'anlatmak'],
            'almak' => ['vermek', 'satmak', 'bırakmak'],
            'gelmek' => ['gitmek', 'uzaklaşmak', 'ayrılmak'],
            'sevmek' => ['nefret etmek', 'tiksinmek', 'sevmemek'],
            'yapmak' => ['bozmak', 'yıkmak', 'dağıtmak'],
            'gülmek' => ['ağlamak', 'üzülmek', 'sızlamak'],
            'ağlamak' => ['gülmek', 'sevinmek', 'kahkaha atmak'],
            'uyumak' => ['uyanmak', 'kalkmak', 'ayakta olmak'],
            'kaynaklamak' => ['kurutmak', 'susuz kalmak', 'bitirmek']
        ];
        
        // Yaygın zıt anlamlılardan bul
        if (isset($commonAntonyms[$word])) {
            $result['data']['antonyms'] = array_merge($result['data']['antonyms'], $commonAntonyms[$word]);
            Log::debug("Yaygın zıt anlamlılar sözlüğünden bulundu: " . implode(", ", $commonAntonyms[$word]));
        }
        
        // Ters aramayı dene - onun zıt anlamlısı bu kelimeyse bu kelimenin de zıt anlamlısı o olmalı
        try {
            $reverseAntonyms = DB::table('word_relations')
                ->where('related_word', $word)
                ->where('relation_type', 'antonym')
                ->pluck('word')
                ->toArray();
                
            if (!empty($reverseAntonyms)) {
                $result['data']['antonyms'] = array_merge($result['data']['antonyms'], $reverseAntonyms);
                Log::debug("Ters zıt anlamlı aramadan bulundu: " . implode(", ", $reverseAntonyms));
            }
        } catch (\Exception $e) {
            Log::error("Ters zıt anlamlı arama hatası ($word): " . $e->getMessage());
        }
        
        // Bazı genel morfolojik yapıları incele
        $prefixMap = [
            'bi' => ['sız', 'siz', 'suz', 'süz'],
            'bağ' => ['sız', 'siz', 'suz', 'süz'],
            'a' => ['sız', 'siz', 'suz', 'süz'],
            'sız' => ['lı', 'li', 'lu', 'lü'],
            'siz' => ['lı', 'li', 'lu', 'lü'],
            'suz' => ['lı', 'li', 'lu', 'lü'],
            'süz' => ['lı', 'li', 'lu', 'lü']
        ];
        
        // Sonsuzluk -> Son gibi yapılar
        foreach ($prefixMap as $prefix => $suffixes) {
            if (mb_strpos($word, $prefix) === 0) {
                $stem = mb_substr($word, mb_strlen($prefix));
                
                foreach ($suffixes as $suffix) {
                    $possibleAntonym = $stem . $suffix;
                    
                    // Veritabanında böyle bir kelime var mı diye kontrol et
                    $exists = DB::table('word_relations')
                        ->where('word', $possibleAntonym)
                        ->exists();
                        
                    if ($exists) {
                        $result['data']['antonyms'][] = $possibleAntonym;
                        Log::debug("Morfolojik analiz ile zıt anlamlı bulundu: " . $possibleAntonym);
                    }
                }
            }
        }
        
        // İcap -> İcapsız gibi yapıları kontrol et
        $suffixMap = [
            'lı' => ['sız', 'siz', 'suz', 'süz'],
            'li' => ['sız', 'siz', 'suz', 'süz'],
            'lu' => ['sız', 'siz', 'suz', 'süz'],
            'lü' => ['sız', 'siz', 'suz', 'süz'],
            'sız' => ['lı', 'li', 'lu', 'lü'],
            'siz' => ['lı', 'li', 'lu', 'lü'],
            'suz' => ['lı', 'li', 'lu', 'lü'],
            'süz' => ['lı', 'li', 'lu', 'lü'],
        ];
        
        foreach ($suffixMap as $suffix => $opposites) {
            if (mb_substr($word, -mb_strlen($suffix)) === $suffix) {
                $stem = mb_substr($word, 0, mb_strlen($word) - mb_strlen($suffix));
                
                foreach ($opposites as $oppositeSuffix) {
                    $possibleAntonym = $stem . $oppositeSuffix;
                    
                    // Veritabanında böyle bir kelime var mı diye kontrol et
                    $exists = DB::table('word_relations')
                        ->where('word', $possibleAntonym)
                        ->exists();
                        
                    if ($exists) {
                        $result['data']['antonyms'][] = $possibleAntonym;
                        Log::debug("Morfolojik ek analizi ile zıt anlamlı bulundu: " . $possibleAntonym);
                    }
                }
            }
        }
    }
    
    /**
     * Metinden anahtar kelimeleri çıkar
     */
    private function extractKeywordsFromText($text)
    {
        // Metin işleme
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        
        // Kelimelere ayır
        $words = preg_split('/\s+/', $text);
        
        // Stopwords - Türkçe yaygın kullanılan anlamsız kelimeler
        $stopwords = ['ve', 'veya', 'bir', 'ile', 'için', 'gibi', 'de', 'da', 'ki', 'bu', 'şu', 'o', 'ben', 'sen', 'biz', 'siz', 'onlar'];
        
        // Stopwords'leri ve çok kısa kelimeleri kaldır
        $filteredWords = [];
        foreach ($words as $word) {
            if (mb_strlen($word) > 2 && !in_array($word, $stopwords)) {
                $filteredWords[] = $word;
            }
        }
        
        // Tekrarlanan kelimeleri kaldır
        return array_unique($filteredWords);
    }
}
