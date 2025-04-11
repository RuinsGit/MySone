<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\AI\Core\Brain;
use App\AI\Core\WordRelations;
use App\Models\AIData;
use App\Models\Chat;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Http;

class AIController extends Controller
{
    /**
     * Yapay zeka ile konuşma
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function chat(Request $request)
    {
        try {
            // Parametreleri doğrula
            $request->validate([
                'message' => 'required|string|max:1000',
                'chat_id' => 'nullable|integer'
            ]);
            
            // Brain nesnesini oluştur
            $brain = app(Brain::class);
            
            // Öğrenme sistemini al
            $learningSystem = $brain->getLearningSystem();
            
            // WordRelations sınıfını yükle
            $wordRelations = app(\App\AI\Core\WordRelations::class);
            
            // Kullanıcı mesajı
            $message = $request->input('message');
            
            // "Nedir" sorusu mu kontrol et
            $nedirResponse = $this->processNedirQuestion($message);
            if ($nedirResponse !== null) {
                // Sohbeti oluştur/bul
                $chatId = $request->input('chat_id');
                $chat = $this->getOrCreateChat($chatId, $message);
                
                // Kullanıcı mesajını kaydet
                ChatMessage::create([
                    'chat_id' => $chat->id,
                    'content' => $message,
                    'sender' => 'user'
                ]);
                
                // Yanıtı kelime ilişkileriyle zenginleştir
                $enhancedResponse = $this->enhanceResponseWithWordRelations($nedirResponse);
                
                // AI mesajını kaydet
                ChatMessage::create([
                    'chat_id' => $chat->id,
                    'content' => $enhancedResponse,
                    'sender' => 'ai'
                ]);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'chat_id' => $chat->id,
                        'response' => $enhancedResponse
                    ]
                ]);
            }
            
            // Sohbet kaydı
            $chat = $this->getOrCreateChat($request->input('chat_id'), $message);
            
            // Kullanıcı mesajını kaydet
            ChatMessage::create([
                'chat_id' => $chat->id,
                'content' => $message,
                'sender' => 'user'
            ]);
            
            // Mesajdaki yeni kelimeleri öğren
            if (strlen($message) > 10) {
                // Mesajı kelimelere ayır
                $words = preg_split('/\s+/', preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $message));
                
                foreach ($words as $word) {
                    if (strlen($word) >= 3 && !in_array(strtolower($word), ['için', 'gibi', 'daha', 'bile', 'kadar', 'nasıl', 'neden'])) {
                        // Kelime veritabanında var mı kontrol et
                        $exists = \App\Models\AIData::where('word', $word)->exists();
                        
                        // Eğer kelime veritabanında yoksa ve geçerli bir kelimeyse öğren
                        if (!$exists && $wordRelations->isValidWord($word)) {
                            try {
                                Log::info("API üzerinden yeni kelime öğreniliyor: " . $word);
                                $learningSystem->learnWord($word);
                            } catch (\Exception $e) {
                                Log::error("Kelime öğrenme hatası: " . $e->getMessage(), ['word' => $word]);
                            }
                        }
                    }
                }
            }
            
            // Yapay zeka yanıtını al
            $response = $brain->processInput($message);
            
            // Yanıtı kelime ilişkileriyle zenginleştir
            $enhancedResponse = $this->enhanceResponseWithWordRelations($response);
            
            // AI mesajını kaydet
            ChatMessage::create([
                'chat_id' => $chat->id,
                'content' => $enhancedResponse,
                'sender' => 'ai'
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'chat_id' => $chat->id,
                    'response' => $enhancedResponse
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('AI yanıt hatası: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'AI yanıt hatası: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Sohbeti oluştur veya bul
     *
     * @param int|null $chatId Sohbet ID
     * @param string $message Kullanıcı mesajı
     * @return \App\Models\Chat
     */
    private function getOrCreateChat($chatId, $message)
    {
        $chat = null;
        
        if ($chatId) {
            $chat = Chat::find($chatId);
        }
        
        if (!$chat) {
            // Yeni sohbet oluştur
            $chat = Chat::create([
                'user_id' => auth()->check() ? auth()->id() : null,
                'title' => substr($message, 0, 50),
                'status' => 'active'
            ]);
        }
        
        return $chat;
    }
    
    /**
     * "Nedir" kalıbındaki soruları işle ve web araştırması yap
     *
     * @param string $message Kullanıcı mesajı
     * @return string|null Yanıt veya null
     */
    private function processNedirQuestion($message)
    {
        // Özet modu bayrağı
        $summaryMode = preg_match('/\b(kısalt|özetle|özet|kısa|açıkla)\b/i', $message);
        
        // "Nedir" kalıbını kontrol et - daha esnek pattern
        if (preg_match('/(?:.*?)(\b\w+\b)(?:\s+nedir)(?:\?)?$/i', $message, $matches) || 
            preg_match('/(?:.*?)(\b\w+(?:\s+\w+){0,3}\b)(?:\s+ned[iı]r)(?:\?)?$/i', $message, $matches) ||
            preg_match('/^(?:.*?\s+)?(.+?)(?:\s+ned[iı]r)?(?:\?)?$/i', $message, $matches)) {
            
            $term = trim($matches[1]);
            
            // Filtrele: Soruda "peki", "şimdi", "o zaman" gibi gereksiz kelimeleri temizle
            $term = preg_replace('/^(peki|şimdi|yani|acaba|o zaman|hadi|ama|fakat)\s+/i', '', $term);
            
            // Ayrıca "kısalt", "özetle" gibi komut kelimelerini de temizle
            $term = preg_replace('/\b(kısalt|özetle|özet|kısa|açıkla)\b/i', '', $term);
            
            // Başta ve sondaki boşlukları temizle
            $term = trim($term);
            
            // Soru işaretini temizle
            $term = str_replace('?', '', $term);
            
            // Minimum uzunluk kontrolü
            if (strlen($term) < 2) {
                return null;
            }
            
            Log::info("Web araştırması yapılıyor: $term" . ($summaryMode ? " (Özet mod)" : ""));
            
            try {
                // İlk olarak veritabanımızda kontrol et
                $aiData = \App\Models\AIData::where('word', $term)->first();
                
                // Eğer veritabanında varsa, öncelikle bu bilgiyi kullan
                if ($aiData) {
                    Log::info("'$term' veritabanında bulundu, mevcut bilgiyi kullanıyoruz");
                    
                    $definitions = json_decode($aiData->metadata, true)['definitions'] ?? [];
                    $desc = !empty($definitions) ? implode(' ', array_slice($definitions, 0, 2)) : $aiData->sentence;
                    
                    if (!empty($desc)) {
                        return "$term: $desc";
                    }
                }
                
                // Web araştırması yap
                $searchResults = $this->searchWeb($term);
                
                if (empty($searchResults)) {
                    return "Üzgünüm, '$term' hakkında bilgi bulamadım. Başka bir şekilde ifade etmeyi deneyebilir misiniz?";
                }
                
                // Sonuçları parçalara ayır ve düzenle (özet modu bayrağını ilet)
                $formattedContent = $this->formatSearchResults($term, $searchResults, $summaryMode);
                
                // Kelimeyi öğrenmeye çalış
                try {
                    $brain = app(\App\AI\Core\Brain::class);
                    $learningSystem = $brain->getLearningSystem();
                    $learningSystem->learnWord($term);
                    Log::info("'$term' kelimesi otomatik olarak öğrenilmeye başlandı");
                } catch (\Exception $e) {
                    Log::error("'$term' kelimesini öğrenirken hata: " . $e->getMessage());
                }
                
                return $formattedContent;
            } catch (\Exception $e) {
                Log::error("Web araştırması hatası: " . $e->getMessage());
                return "Bu konu hakkında araştırma yaparken bir sorun oluştu. Lütfen tekrar deneyin.";
            }
        }
        
        return null;
    }
    
    /**
     * Web üzerinde arama yap
     *
     * @param string $query Arama sorgusu
     * @return array Arama sonuçları
     */
    private function searchWeb($query)
    {
        try {
            // Google arama sorgusu
            $encodedQuery = urlencode($query . " nedir tanım açıklama");
            
            // İlk olarak Wikipedia'da ara
            $wikipediaData = $this->searchWikipedia($query);
            
            // TDK sözlüğünde ara
            $tdkData = $this->searchTDK($query);
            
            // Google'da ara
            $googleResults = $this->searchGoogle($encodedQuery);
            
            // Tüm sonuçları birleştir
            $combinedResults = [
                'wikipedia' => $wikipediaData,
                'tdk' => $tdkData,
                'google' => $googleResults
            ];
            
            return $combinedResults;
            
        } catch (\Exception $e) {
            Log::error("Web araması hatası: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Wikipedia'da arama yap
     *
     * @param string $query Arama sorgusu
     * @return array Wikipedia verileri
     */
    private function searchWikipedia($query)
    {
        try {
            // Wikipedia API URL
            $encodedQuery = urlencode($query);
            $url = "https://tr.wikipedia.org/api/rest_v1/page/summary/" . $encodedQuery;
            
            $response = Http::timeout(5)->get($url);
            
            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'title' => $data['title'] ?? $query,
                    'extract' => $data['extract'] ?? '',
                    'url' => $data['content_urls']['desktop']['page'] ?? '',
                    'source' => 'Wikipedia'
                ];
            }
            
            return [];
            
        } catch (\Exception $e) {
            Log::error("Wikipedia araması hatası: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * TDK sözlüğünde arama yap
     *
     * @param string $query Arama sorgusu
     * @return array TDK verileri
     */
    private function searchTDK($query)
    {
        try {
            // TDK API URL
            $encodedQuery = urlencode($query);
            $url = "https://sozluk.gov.tr/gts?ara=" . $encodedQuery;
            
            $response = Http::timeout(5)->get($url);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data[0])) {
                    $result = [
                        'title' => $data[0]['madde'] ?? $query,
                        'meanings' => [],
                        'source' => 'TDK Sözlük'
                    ];
                    
                    if (isset($data[0]['anlamlarListe'])) {
                        foreach ($data[0]['anlamlarListe'] as $meaning) {
                            $result['meanings'][] = $meaning['anlam'];
                        }
                    }
                    
                    return $result;
                }
            }
            
            return [];
            
        } catch (\Exception $e) {
            Log::error("TDK araması hatası: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Google'da arama yap
     *
     * @param string $query Arama sorgusu
     * @return array Google sonuçları
     */
    private function searchGoogle($query)
    {
        try {
            // Google özel arama motoru API anahtarı ve kimliği
            $apiKey = env('GOOGLE_API_KEY', '');
            $cx = env('GOOGLE_SEARCH_CX', '');
            
            if (empty($apiKey) || empty($cx)) {
                return [];
            }
            
            // Google Custom Search API URL
            $url = "https://www.googleapis.com/customsearch/v1?key={$apiKey}&cx={$cx}&q={$query}&lr=lang_tr";
            
            $response = Http::timeout(5)->get($url);
            
            if ($response->successful()) {
                $data = $response->json();
                $results = [];
                
                if (isset($data['items'])) {
                    foreach ($data['items'] as $item) {
                        $results[] = [
                            'title' => $item['title'] ?? '',
                            'snippet' => $item['snippet'] ?? '',
                            'link' => $item['link'] ?? '',
                            'source' => 'Google'
                        ];
                    }
                }
                
                return $results;
            }
            
            return [];
            
        } catch (\Exception $e) {
            Log::error("Google araması hatası: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Arama sonuçlarını formatla ve düzenle
     *
     * @param string $term Aranan terim
     * @param array $results Arama sonuçları
     * @param bool $summaryMode Özet modu
     * @return string Düzenlenmiş içerik
     */
    private function formatSearchResults($term, $results, $summaryMode = false)
    {
        // Başlık
        $formattedContent = "$term hakkında " . ($summaryMode ? "özet" : "bilgiler") . ":\n\n";
        
        // Wikipedia ve TDK sonuçlarını önce kullanalım
        if (!empty($results['wikipedia']['extract'])) {
            $wikipediaExtract = $results['wikipedia']['extract'];
            
            // Özet modda Wikipedia içeriğini kısalt
            if ($summaryMode && strlen($wikipediaExtract) > 150) {
                // İlk cümleyi veya belirli bir kısmını al
                if (preg_match('/^(.{1,150}[.!?])\s/u', $wikipediaExtract, $matches)) {
                    $wikipediaExtract = $matches[1];
                } else {
                    $wikipediaExtract = substr($wikipediaExtract, 0, 150) . "...";
                }
            }
            
            $formattedContent .= "$wikipediaExtract\n\n";
        }
        
        if (!empty($results['tdk']['meanings'])) {
            if ($summaryMode) {
                // Özet modda sadece ilk tanımı ekle
                $formattedContent .= "TDK: " . $results['tdk']['meanings'][0] . "\n\n";
            } else {
                $formattedContent .= "TDK Sözlük tanımları:\n";
                foreach ($results['tdk']['meanings'] as $index => $meaning) {
                    // Özet modda en fazla 2 tanım göster
                    if ($summaryMode && $index >= 2) break;
                    $formattedContent .= ($index + 1) . ". $meaning\n";
                }
                $formattedContent .= "\n";
            }
        }
        
        // Google sonuçlarından faydalı bilgiler çıkar
        if (!empty($results['google']) && !$summaryMode) {
            // Özet modda Google sonuçlarını gösterme veya sadece en önemlisini göster
            // En fazla 3 Google sonucu kullan (özet modda sadece 1)
            $usedSnippets = [];
            $snippetCount = 0;
            $maxSnippets = $summaryMode ? 1 : 3;
            
            foreach ($results['google'] as $item) {
                $snippet = $item['snippet'] ?? '';
                
                // Benzersiz ve yeterince uzun snippet'leri kullan
                if (!empty($snippet) && strlen($snippet) > 40 && !in_array($snippet, $usedSnippets)) {
                    if (stripos($snippet, $term) !== false) {
                        $usedSnippets[] = $snippet;
                        $snippetCount++;
                        
                        if ($snippetCount >= $maxSnippets) {
                            break;
                        }
                    }
                }
            }
            
            // Topladığımız snippet'leri metne ekleyelim
            if (!empty($usedSnippets)) {
                $formattedContent .= "Diğer kaynaklar " . ($summaryMode ? "özeti" : "şunları söylüyor") . ":\n";
                foreach ($usedSnippets as $snippet) {
                    $formattedContent .= "• " . $snippet . "\n";
                }
            }
        }
        
        // Sonuç bulunamadıysa özel mesaj göster
        if (strlen($formattedContent) <= strlen("$term hakkında " . ($summaryMode ? "özet" : "bilgiler") . ":\n\n")) {
            return "Üzgünüm, '$term' hakkında spesifik bir bilgi bulamadım. Başka bir şekilde sormayı deneyebilir misiniz?";
        }
        
        // Kaynak bilgisini ekle
        if (!$summaryMode) {
            $formattedContent .= "\nBu bilgiler Wikipedia, TDK Sözlük ve diğer web kaynaklarından derlenmiştir.";
        }
        
        return $formattedContent;
    }
    
    /**
     * Yanıtı kelime ilişkileriyle zenginleştir
     * 
     * @param string $response Orijinal yanıt
     * @return string Zenginleştirilmiş yanıt
     */
    private function enhanceResponseWithWordRelations($response)
    {
        try {
            // Kelime ilişkileri sınıfını yükle
            $wordRelations = app(\App\AI\Core\WordRelations::class);
            
            // Yanıt zaten yeterince uzunsa veya %30 ihtimalle ek yapmıyoruz
            if (strlen($response) > 150 || mt_rand(1, 100) <= 30) {
                return $response;
            }
            
            // Yanıttaki önemli kelimeleri bul
            $words = preg_split('/\s+/', preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $response));
            $importantWords = [];
            
            foreach ($words as $word) {
                if (strlen($word) >= 3 && !in_array(strtolower($word), ['için', 'gibi', 'daha', 'bile', 'kadar', 'nasıl', 'neden'])) {
                    $importantWords[] = $word;
                }
            }
            
            // Önemli kelime yoksa orijinal yanıtı döndür
            if (empty($importantWords)) {
                return $response;
            }
            
            // Rasgele bir kelime seç
            $selectedWord = $importantWords[array_rand($importantWords)];
            
            // 50% ihtimalle eş anlamlı, 25% ihtimalle zıt anlamlı, 25% ihtimalle akıllı cümle
            $random = mt_rand(1, 100);
            
            if ($random <= 50) {
                // Eş anlamlılarla ilgili bilgi ekle
                $synonyms = $wordRelations->getSynonyms($selectedWord);
                
                if (!empty($synonyms)) {
                    $randomKey = array_rand($synonyms);
                    $synonym = $synonyms[$randomKey];
                    $additions = [
                        "Bu arada, '$selectedWord' kelimesinin eş anlamlısı '$synonym' kelimesidir.",
                        "'$selectedWord' ve '$synonym' benzer anlamlara sahiptir.",
                        "$selectedWord yerine $synonym da kullanılabilir."
                    ];
                    
                    $selectedAddition = $additions[array_rand($additions)];
                    
                    // Doğruluk kontrolü
                    $accuracy = $wordRelations->calculateSentenceAccuracy($selectedAddition, $selectedWord);
                    
                    if ($accuracy >= 0.6) {
                        Log::info("Eş anlamlı bilgi eklendi: $selectedAddition (Doğruluk: $accuracy)");
                        return $response . " " . $selectedAddition;
                    } else {
                        Log::info("Eş anlamlı bilgi doğruluk kontrolünden geçemedi: $selectedAddition (Doğruluk: $accuracy)");
                    }
                }
            } elseif ($random <= 75) {
                // Zıt anlamlılarla ilgili bilgi ekle
                $antonyms = $wordRelations->getAntonyms($selectedWord);
                
                if (!empty($antonyms)) {
                    $randomKey = array_rand($antonyms);
                    $antonym = $antonyms[$randomKey];
                    $additions = [
                        "Bu arada, '$selectedWord' kelimesinin zıt anlamlısı '$antonym' kelimesidir.",
                        "'$selectedWord' ve '$antonym' zıt anlamlara sahiptir.",
                        "$selectedWord kelimesinin tam tersi $antonym olarak ifade edilir."
                    ];
                    
                    $selectedAddition = $additions[array_rand($additions)];
                    
                    // Doğruluk kontrolü
                    $accuracy = $wordRelations->calculateSentenceAccuracy($selectedAddition, $selectedWord);
                    
                    if ($accuracy >= 0.6) {
                        Log::info("Zıt anlamlı bilgi eklendi: $selectedAddition (Doğruluk: $accuracy)");
                        return $response . " " . $selectedAddition;
                    } else {
                        Log::info("Zıt anlamlı bilgi doğruluk kontrolünden geçemedi: $selectedAddition (Doğruluk: $accuracy)");
                    }
                }
            } else {
                // Akıllı cümle üret - doğruluk kontrolü bu metod içinde yapılıyor
                try {
                    // Minimum doğruluk değeri 0.6 ile cümle üret
                    $sentences = $wordRelations->generateSmartSentences($selectedWord, true, 1, 0.6);
                    
                    if (!empty($sentences)) {
                        Log::info("Akıllı cümle eklendi: " . $sentences[0]);
                        return $response . " " . $sentences[0];
                    }
                } catch (\Exception $e) {
                    Log::error("Akıllı cümle üretme hatası: " . $e->getMessage());
                }
            }
            
            // Hiçbir ekleme yapılamadıysa orijinal yanıtı döndür
            return $response;
            
        } catch (\Exception $e) {
            Log::error("Yanıt zenginleştirme hatası: " . $e->getMessage());
            return $response; // Hata durumunda orijinal yanıtı döndür
        }
    }
    
    /**
     * Bir kelime hakkında bilgi al
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWordInfo(Request $request)
    {
        try {
            // Parametreleri doğrula
            $request->validate([
                'word' => 'required|string|min:2|max:100'
            ]);
            
            // Brain nesnesini oluştur
            $brain = app(Brain::class);
            
            // Kelime bilgisini al
            $wordInfo = $brain->getWordRelations($request->input('word'));
            
            return response()->json([
                'success' => true,
                'data' => $wordInfo
            ]);
        } catch (\Exception $e) {
            Log::error('Kelime bilgisi alma hatası: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Kelime bilgisi alma hatası: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * URL parametresiyle kelime bilgisi getir
     * 
     * @param string $word
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWordInfoByParam($word)
    {
        try {
            if (empty($word) || strlen($word) < 2 || strlen($word) > 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Geçersiz kelime parametresi'
                ], 400);
            }
            
            // Brain nesnesini oluştur
            $brain = app(Brain::class);
            
            // WordRelations sınıfını da doğrudan kullan
            $wordRelations = app(\App\AI\Core\WordRelations::class);
            
            // Brain üzerinden temel kelime ilişkilerini al
            $wordInfo = $brain->getWordRelations($word);
            
            // Kelimenin AI verilerini getir
            $aiData = \App\Models\AIData::where('word', $word)->first();
            
            // Daha fazla veri ekle
            if ($aiData) {
                $wordInfo['frequency'] = $aiData->frequency;
                $wordInfo['confidence'] = $aiData->confidence;
                $wordInfo['category'] = $aiData->category;
                $wordInfo['related_words'] = json_decode($aiData->related_words) ?: [];
                $wordInfo['examples'] = json_decode($aiData->usage_examples) ?: [];
                $wordInfo['metadata'] = json_decode($aiData->metadata) ?: [];
                $wordInfo['emotional_context'] = json_decode($aiData->emotional_context) ?: [];
                $wordInfo['created_at'] = $aiData->created_at->format('Y-m-d H:i:s');
                $wordInfo['updated_at'] = $aiData->updated_at->format('Y-m-d H:i:s');
            }
            
            // Tanımları getir
            $definitions = [];
            if (method_exists($wordRelations, 'getDefinitions')) {
                $definitions = $wordRelations->getDefinitions($word);
            } else {
                // Alternatif olarak, getDefinition metodunu deneyelim ve tek değer olarak alalım
                $singleDef = $wordRelations->getDefinition($word);
                if (!empty($singleDef)) {
                    $definitions = [$singleDef];
                }
            }
            $wordInfo['definitions'] = $definitions ?: [];
            
            // Örnekleri getir
            $examples = [];
            if (method_exists($wordRelations, 'getExamples')) {
                $examples = $wordRelations->getExamples($word);
            }
            $wordInfo['examples'] = $examples ?: [];
            
            // İlişkili kelimelerin düz listesini oluştur
            $relatedWordsFlat = [];
            if (!empty($wordInfo['related_words']) && is_array($wordInfo['related_words'])) {
                foreach ($wordInfo['related_words'] as $item) {
                    if (is_array($item) && isset($item['word'])) {
                        $relatedWordsFlat[] = $item['word'];
                    } elseif (is_string($item)) {
                        $relatedWordsFlat[] = $item;
                    }
                }
            }
            $wordInfo['related'] = $relatedWordsFlat;
            
            return response()->json([
                'success' => true,
                'data' => $wordInfo
            ]);
        } catch (\Exception $e) {
            Log::error('Kelime bilgisi alma hatası: ' . $e->getMessage(), [
                'word' => $word,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Kelime bilgisi alma hatası: ' . $e->getMessage()
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
            // Brain nesnesini oluştur
            $brain = app(Brain::class);
            
            // Öğrenme sistemi var mı kontrol et
            try {
                $learningSystem = $brain->getLearningSystem();
            } catch (\Exception $e) {
                Log::warning('Öğrenme sistemi bulunamadı veya erişilemedi: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Öğrenme sistemi başlatılmadı veya erişilemedi'
                ]);
            }
            
            if (!$learningSystem) {
                Log::warning('Öğrenme sistemi bulunamadı (null değer döndü)');
                return response()->json([
                    'success' => false,
                    'message' => 'Öğrenme sistemi başlatılmadı'
                ]);
            }
            
            try {
                // Öğrenme durumunu al
                $status = $brain->getLearningStatus();
                
                return response()->json([
                    'success' => true,
                    'data' => $status
                ]);
            } catch (\Exception $e) {
                Log::error('Öğrenme durumu alma hatası: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Öğrenme durumu alma hatası: ' . $e->getMessage()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Brain oluşturma hatası: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Sistem hatası: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Yapay zeka hakkında genel durum bilgisi
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAIStatus()
    {
        try {
            // Brain nesnesini oluştur
            $brain = app(Brain::class);
            
            // Durum bilgilerini al
            $status = [
                'words_learned' => AIData::count()
            ];
            
            // Her bir metot çağrısını ayrı bir try-catch bloğunda gerçekleştir
            try {
                $status['memory'] = $brain->getMemoryStatus();
            } catch (\Exception $e) {
                Log::warning('Bellek durumu alınamadı: ' . $e->getMessage());
                $status['memory'] = ['error' => 'Bellek durumu alınamadı'];
            }
            
            try {
                $status['emotion'] = $brain->getEmotionalState();
            } catch (\Exception $e) {
                Log::warning('Duygusal durum alınamadı: ' . $e->getMessage());
                $status['emotion'] = ['error' => 'Duygusal durum alınamadı'];
            }
            
            try {
                $status['learning'] = $brain->getLearningStatus();
            } catch (\Exception $e) {
                Log::warning('Öğrenme durumu alınamadı: ' . $e->getMessage());
                $status['learning'] = ['error' => 'Öğrenme durumu alınamadı'];
            }
            
            try {
                $status['consciousness'] = $brain->getConsciousnessState();
            } catch (\Exception $e) {
                Log::warning('Bilinç durumu alınamadı: ' . $e->getMessage());
                $status['consciousness'] = ['error' => 'Bilinç durumu alınamadı'];
            }
            
            return response()->json([
                'success' => true,
                'data' => $status
            ]);
        } catch (\Exception $e) {
            Log::error('AI durum bilgisi alma hatası: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'AI durum bilgisi alma hatası: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Kelime araması yap
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchWords(Request $request)
    {
        try {
            // Parametreleri doğrula
            $request->validate([
                'query' => 'required|string|min:2|max:100',
                'limit' => 'nullable|integer|min:1|max:100'
            ]);
            
            // Arama parametreleri
            $query = $request->input('query');
            $limit = $request->input('limit', 20);
            
            // Kelime araması yap
            $words = AIData::where('word', 'like', "%$query%")
                ->orWhere('sentence', 'like', "%$query%")
                ->orWhere('category', 'like', "%$query%")
                ->orderBy('frequency', 'desc')
                ->limit($limit)
                ->get(['word', 'category', 'frequency', 'confidence']);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'query' => $query,
                    'count' => $words->count(),
                    'words' => $words
                ]
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
     * Sohbet geçmişini getir
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChatHistory(Request $request)
    {
        try {
            // Parametreleri doğrula
            $request->validate([
                'chat_id' => 'required|integer'
            ]);
            
            // Sohbet kaydını al
            $chat = Chat::with(['messages' => function($query) {
                $query->orderBy('created_at', 'asc');
            }])->find($request->input('chat_id'));
            
            if (!$chat) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sohbet bulunamadı'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $chat
            ]);
        } catch (\Exception $e) {
            Log::error('Sohbet geçmişi alma hatası: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Sohbet geçmişi alma hatası: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Kullanıcı sohbetlerini listele
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserChats()
    {
        try {
            // Kullanıcı ID'sine göre sohbetleri getir
            $userId = auth()->check() ? auth()->id() : null;
            
            $chats = Chat::where('user_id', $userId)
                ->orderBy('updated_at', 'desc')
                ->get(['id', 'title', 'status', 'created_at', 'updated_at']);
            
            return response()->json([
                'success' => true,
                'data' => $chats
            ]);
        } catch (\Exception $e) {
            Log::error('Kullanıcı sohbetleri listeleme hatası: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Kullanıcı sohbetleri listeleme hatası: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Yapay zeka durumunu getir (getAIStatus'a yönlendir)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatus()
    {
        return $this->getAIStatus();
    }
}
