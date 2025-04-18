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
use Illuminate\Support\Facades\DB;

class AIController extends Controller
{
    /**
     * AI sistemine kullanıcı girdisini işlet
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processInput(Request $request)
    {
        try {
            // Aynı mesaj tekrarını önlemek için başlangıçta bir log mesajı ekleyelim
            Log::info('AIController::processInput çağrıldı', [
                'message' => $request->input('message'),
                'chat_id' => $request->input('chat_id')
            ]);
            
            // Çift istek gönderimini kontrol ediyoruz
            $requestKey = md5($request->input('message') . $request->input('chat_id') . time());
            $cacheKey = 'last_request_' . $request->input('chat_id');
            $lastRequestKey = cache()->get($cacheKey);
            
            // Son bir saniye içinde aynı chatId için yapılan istekleri engelle
            if ($lastRequestKey !== null && time() - cache()->get($cacheKey . '_time', 0) <= 1) {
                Log::warning('Kısa sürede birden fazla istek algılandı ve engellendi', [
                    'chat_id' => $request->input('chat_id'),
                    'message' => $request->input('message')
                ]);
                
                return response()->json([
                    'success' => true,
                    'response' => "İsteğiniz işleniyor, lütfen bekleyin...",
                    'is_code_response' => false,
                    'duplicate_request' => true,
                    'chat_id' => $request->input('chat_id'),
                ]);
            }
            
            // Yeni isteği önbelleğe al
            cache()->put($cacheKey, $requestKey, now()->addMinutes(5));
            cache()->put($cacheKey . '_time', time(), now()->addMinutes(5));
            
            // Gelen isteği işle
            $message = $request->input('message');
            $chatId = $request->input('chat_id');
            $creativeMode = $request->input('creative_mode', false);
            $codingMode = $request->input('coding_mode', false);
            
            // Dil tercihini al
            $preferredLanguage = $request->input('preferred_language', 'javascript');
            
            Log::info('AI sorgusu:', [
                'message' => $message,
                'creative_mode' => $creativeMode,
                'coding_mode' => $codingMode,
                'preferred_language' => $preferredLanguage
            ]);
            
            // Mesaj boş ise hata döndür
            if (empty($message)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mesaj boş olamaz',
                ]);
            }
            
            // Sohbet ID yoksa yeni bir sohbet oluştur
            if (empty($chatId)) {
                $chat = Chat::create([
                    'user_id' => auth()->id(),
                    'title' => $this->generateChatTitle($message),
                    'is_active' => true,
                ]);
                $chatId = $chat->id;
            } else {
                // Varolan sohbeti güncelle
                $this->getOrCreateChat($chatId, $message);
            }
            
            // Küfür içeren mesaj tespiti yap
            $containsProfanity = $this->containsProfanity($message);
            if ($containsProfanity) {
                Log::warning('Mesajda küfür/hakaret tespit edildi', [
                    'chat_id' => $chatId,
                    'message' => str_replace($containsProfanity, '***', $message)
                ]);
                
                $response = $this->generateProfanityResponse($containsProfanity);
                
                // Küfür içeren mesajı ve yanıtı kaydet
                $this->saveMessages($message, $response, $chatId);
                
                return response()->json([
                    'success' => true,
                    'response' => $response,
                    'is_code_response' => false,
                    'chat_id' => $chatId,
                ]);
            }
            
            // Kod isteği kontrolü - çok basit ve kesin bir yaklaşım
            // "kod yaz", "kod oluştur" veya "bana js" gibi ifadeleri kontrol et
            $lowerMessage = mb_strtolower($message);
            if ($codingMode || 
                strpos($lowerMessage, 'kod') !== false || 
                strpos($lowerMessage, 'js') !== false || 
                strpos($lowerMessage, 'javascript') !== false || 
                strpos($lowerMessage, 'php') !== false || 
                strpos($lowerMessage, 'html') !== false || 
                strpos($lowerMessage, 'css') !== false) {
                
                Log::info('Kod isteği algılandı, processCodeRequest çağrılıyor', [
                    'message' => $message
                ]);
                
                // Kod isteğini işle
                $codeResponse = $this->processCodeRequest($message);
                
                if ($codeResponse !== null) {
                    Log::info('Kod yanıtı oluşturuldu');
                    
                    // Kod yanıtını işle
                    $this->saveMessages($message, $codeResponse, $chatId);
                    
                    // Kod bloğunu çıkart (backtickler arasındaki kısım)
                    $codeBlock = null;
                    $language = null;
                    
                    if (preg_match('/```(.*?)\n([\s\S]*?)```/m', $codeResponse, $matches)) {
                        $language = trim($matches[1]);
                        $codeBlock = $matches[2];
                        
                        // JavaScript için js kısaltması
                        if ($language === 'js') {
                            $language = 'javascript';
                        }
                    }
                    
                    return response()->json([
                        'success' => true,
                        'response' => $codeResponse,
                        'is_code_response' => true,
                        'code' => $codeBlock,
                        'language' => $language,
                        'chat_id' => $chatId,
                    ]);
                } else {
                    Log::warning('processCodeRequest null döndürdü', [
                        'message' => $message
                    ]);
                }
            }
            
            // Diğer işlemler aynı kalsın
            // Eğer "nedir" sorusu ise
            $nedirResponse = $this->processNedirQuestion($message);
            if ($nedirResponse !== null) {
                // Mesajları kaydet
                $this->saveMessages($message, $nedirResponse, $chatId);
                
                return response()->json([
                    'success' => true,
                    'response' => $nedirResponse,
                    'is_code_response' => false,
                    'chat_id' => $chatId,
                ]);
            }
            
            // Eğer web araması gerekiyorsa
            if (preg_match('/\bara\b|aramak|bul|aratmak|aramamız|araştır/ui', $message)) {
                if (preg_match('/\bweb(?:\'?de|den)?\b|\binternet(?:\'?ten)?\b|\bonline\b|\bgoogle(?:\'?da|\'?dan)?\b/ui', $message)) {
                    $searchResponse = $this->searchWeb($message);
                    
                    // Mesajları kaydet
                    $this->saveMessages($message, $searchResponse, $chatId);
                    
                    return response()->json([
                        'success' => true,
                        'response' => $searchResponse,
                        'is_code_response' => false,
                        'chat_id' => $chatId,
                    ]);
                }
            }
            
            // Normal yanıt için Brain'i kullan
            $brain = app(Brain::class);
            $learningSystem = $brain->getLearningSystem();
            
            // Kreatif mod aktifse, yaratıcılık parametresini ayarla
            if ($creativeMode) {
                $brain->setCreativityLevel(0.8);
            } else {
                $brain->setCreativityLevel(0.5);
            }
            
            // AI yanıtını al
            $aiResponse = $brain->process($message);
            
            // Kelime ilişkileriyle zenginleştir
            $enhancedResponse = $this->enhanceResponseWithWordRelations($aiResponse);
            
            // Mesajları kaydet
            $this->saveMessages($message, $enhancedResponse, $chatId);
            
            return response()->json([
                'success' => true,
                'response' => $enhancedResponse,
                'is_code_response' => false,
                'chat_id' => $chatId,
            ]);
        } catch (\Exception $e) {
            Log::error('AI yanıt hatası: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Bir hata oluştu: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Yapay zeka ile konuşma
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function chat(Request $request)
    {
        try {
            // Gelen isteği işle
            $message = $request->input('message');
            $chatId = $request->input('chat_id');
            
            // Mesaj boş ise hata döndür
            if (empty($message)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mesaj boş olamaz',
                ]);
            }
            
            // Sohbet ID yoksa yeni bir sohbet oluştur
            if (empty($chatId)) {
                $chat = Chat::create([
                    'user_id' => auth()->id(),
                    'title' => $this->generateChatTitle($message),
                    'is_active' => true,
                ]);
                $chatId = $chat->id;
            } else {
                // Varolan sohbeti güncelle
                $this->getOrCreateChat($chatId, $message);
            }
            
            // Önce kod ile ilgili bir istek mi kontrol et
            $codeResponse = $this->processCodeRequest($message);
            if ($codeResponse !== null) {
                // Kod yanıtını kaydet ve döndür
                $this->saveMessages($message, $codeResponse, $chatId);
                
                return response()->json([
                    'success' => true,
                    'answer' => $codeResponse,
                    'chat_id' => $chatId,
                ]);
            }
            
            // Eğer "nedir" sorusu ise
            $nedirResponse = $this->processNedirQuestion($message);
            if ($nedirResponse !== null) {
                // Mesajları kaydet
                $this->saveMessages($message, $nedirResponse, $chatId);
                
                return response()->json([
                    'success' => true,
                    'answer' => $nedirResponse,
                    'chat_id' => $chatId,
                ]);
            }
            
            // Eğer web araması gerekiyorsa
            if (preg_match('/\bara\b|aramak|bul|aratmak|aramamız|araştır/ui', $message)) {
                if (preg_match('/\bweb(?:\'?de|den)?\b|\binternet(?:\'?ten)?\b|\bonline\b|\bgoogle(?:\'?da|\'?dan)?\b/ui', $message)) {
                    $searchResponse = $this->searchWeb($message);
                    
                    // Mesajları kaydet
                    $this->saveMessages($message, $searchResponse, $chatId);
                    
                    return response()->json([
                        'success' => true,
                        'answer' => $searchResponse,
                        'chat_id' => $chatId,
                    ]);
                }
            }
            
            // Brain nesnesini oluştur
            $brain = app(Brain::class);
            
            // Öğrenme sistemini al
            $learningSystem = $brain->getLearningSystem();
            
            // WordRelations sınıfını yükle
            $wordRelations = app(\App\AI\Core\WordRelations::class);
            
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
            $this->saveMessages($message, $enhancedResponse, $chatId);
            
            return response()->json([
                'success' => true,
                'answer' => $enhancedResponse,
                'chat_id' => $chatId,
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
            
            // Word relations tablosundan eş ve zıt anlamlıları doğrudan çek
            $synonyms = DB::table('word_relations')
                ->where('word', $word)
                ->where('relation_type', 'synonym')
                ->where('language', 'tr')
                ->orderBy('strength', 'desc')
                ->pluck('related_word')
                ->toArray();
                
            $antonyms = DB::table('word_relations')
                ->where('word', $word)
                ->where('relation_type', 'antonym')
                ->where('language', 'tr')
                ->orderBy('strength', 'desc')
                ->pluck('related_word')
                ->toArray();
                
            // İlişkili kelimeleri al 
            $related = DB::table('word_relations')
                ->where('word', $word)
                ->where('relation_type', 'association')
                ->where('language', 'tr')
                ->orderBy('strength', 'desc')
                ->select('related_word', 'strength', 'context')
                ->get()
                ->toArray();
            
            // Kelime bilgisini oluştur
            $wordInfo = [
                'word' => $word,
                'synonyms' => $synonyms,
                'antonyms' => $antonyms,
                'related' => array_map(function($item) {
                    return $item->related_word;
                }, $related)
            ];
            
            // Kelimenin AI verilerini getir
            $aiData = \App\Models\AIData::where('word', $word)->first();
            
            // Daha fazla veri ekle
            if ($aiData) {
                $wordInfo['frequency'] = $aiData->frequency;
                $wordInfo['confidence'] = $aiData->confidence;
                $wordInfo['category'] = $aiData->category;
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
            
            // İlişkili kelimelerin tam bilgilerini de ekle
            $wordInfo['related_details'] = $related;
            
            // Log ekle
            Log::info("[$word] kelimesi için eş anlamlılar (words.blade): " . json_encode($synonyms));
            Log::info("[$word] kelimesi için zıt anlamlılar (words.blade): " . json_encode($antonyms));
            
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
    
    /**
     * Kod ile ilgili sorguları işleyen metod
     * 
     * @param string $message Kullanıcı mesajı
     * @return string İşlenmiş kod yanıtı
     */
    private function processCodeRequest($message)
    {
        try {
            // Log kaydı
            Log::info('processCodeRequest çağrıldı: ' . $message);
            
            // 1. Kullanıcının ne tür bir kod istediğini ve hangi dilde istediğini tespit et
            $language = $this->detectLanguageFromMessage($message);
            $category = $this->detectCategoryFromMessage($message);
            
            Log::info('Tespit edilen kod parametreleri', [
                'language' => $language,
                'category' => $category
            ]);
            
            // Dil tespit edilemezse varsayılan olarak JavaScript kullan
            if (!$language) {
                $language = 'javascript';
            }
            
            // 2. Önce veritabanımızda benzer kod var mı kontrol et
            $similarCodes = $this->findSimilarCodesInDatabase($message, $language, $category);
            
            if (count($similarCodes) > 0) {
                // En yüksek uyum skoruna sahip kodu al
                $bestMatchCode = $similarCodes[0];
                
                // Uyum skoru %51'den fazla ise bu kodu kullan
                if ($bestMatchCode['match_score'] >= 0.51) {
                    Log::info('Veritabanında uyumlu kod bulundu', [
                        'match_score' => $bestMatchCode['match_score'],
                        'code_id' => $bestMatchCode['id']
                    ]);
                    
                    // Kodu kullanım istatistiklerini güncelle
                    $this->updateCodeUsageStats($bestMatchCode['id']);
                    
                    // Kodu yanıta dönüştür
                    return $this->formatCodeResponse(
                        $bestMatchCode['code_content'], 
                        $language,
                        "Sizin isteğinize uygun bir kod örneği buldum:"
                    );
                }
            }
            
            // 3. Veritabanında uygun kod bulunamadıysa web'de ara
            $webResults = $this->searchCodeFromWebSources($message, $language);
            
            if (!empty($webResults)) {
                Log::info('Web kaynaklarında kod bulundu', [
                    'source' => $webResults[0]['source'],
                    'language' => $webResults[0]['language']
                ]);
                
                // Bulunan kodu veritabanına kaydet
                $savedCode = $this->saveCodeToDatabase(
                    $webResults[0]['code'],
                    $webResults[0]['language'],
                    $category,
                    $message,
                    $webResults[0]['source'],
                    $webResults[0]['source_url'] ?? null
                );
                
                // Kodu yanıta dönüştür
                return $this->formatCodeResponse(
                    $webResults[0]['code'],
                    $webResults[0]['language'],
                    "Web'de araştırdım ve şu kodu buldum ({$webResults[0]['source']}):"
                );
            }
            
            // 4. Web'de de bulunamazsa, kendi şablonlarımızla yeni kod oluştur
            Log::info('Yeni kod oluşturuluyor', [
                'language' => $language,
                'category' => $category
            ]);
            
            // Bu noktada generateCode her zaman bir yanıt döndürmelidir
            return $this->generateCode($message, $language, $category);
            
        } catch (\Exception $e) {
            Log::error('Kod işleme hatası: ' . $e->getMessage(), [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Hata durumunda bile basit bir kod yanıtı döndür
            $language = $this->detectLanguageFromMessage($message) ?: 'javascript';
            
            $fallbackCode = "";
            if ($language === 'javascript') {
                $fallbackCode = "// JavaScript basit örnek\nconsole.log('Merhaba Dünya!');\n\n// Kullanım örneği\nfunction selamVer(isim) {\n  return 'Merhaba, ' + isim + '!';\n}\n\nconst mesaj = selamVer('Dünya');\nconsole.log(mesaj);";
            } elseif ($language === 'php') {
                $fallbackCode = "<?php\n\n// PHP basit örnek\necho 'Merhaba Dünya!';\n\n// Kullanım örneği\nfunction selamVer($isim) {\n  return 'Merhaba, ' . $isim . '!';\n}\n\n$mesaj = selamVer('Dünya');\necho $mesaj;";
            } else {
                $fallbackCode = "// Basit " . $language . " örneği\nconsole.log('Merhaba Dünya!');";
            }
            
            return $this->formatCodeResponse(
                $fallbackCode,
                $language,
                "İşte " . $language . " için basit bir kod örneği (ayrıntılı sorgu ile daha spesifik kod örnekleri isteyebilirsiniz):"
            );
        }
    }
    
    /**
     * Mesajdan programlama dilini tespit et
     * 
     * @param string $message Kullanıcı mesajı
     * @return string|null Tespit edilen dil veya null
     */
    private function detectLanguageFromMessage($message)
    {
        $languages = [
            'js' => 'javascript',
            'javascript' => 'javascript',
            'php' => 'php',
            'html' => 'html',
            'css' => 'css',
            'python' => 'python',
            'java' => 'java',
            'c#' => 'csharp',
            'c++' => 'cpp',
            'sql' => 'sql'
        ];
        
        $lowerMessage = strtolower($message);
        
        foreach ($languages as $key => $value) {
            if (strpos($lowerMessage, $key) !== false) {
                return $value;
            }
        }
        
        // Dil belirlenemezse null döndür
        return null;
    }
    
    /**
     * Mesajdan kod kategorisini tespit et
     * 
     * @param string $message Kullanıcı mesajı
     * @return string|null Tespit edilen kategori veya null
     */
    private function detectCategoryFromMessage($message)
    {
        $categories = [
            'function' => ['fonksiyon', 'metod', 'method', 'function'],
            'class' => ['sınıf', 'class', 'object', 'oop'],
            'form' => ['form', 'input', 'button', 'buton'],
            'layout' => ['düzen', 'layout', 'sayfa yapısı', 'template'],
            'animation' => ['animasyon', 'animation', 'geçiş', 'transition'],
            'database' => ['veritabanı', 'database', 'sql', 'query', 'sorgu'],
            'dom' => ['dom', 'document', 'element', 'html manipülasyon'],
            'event' => ['olay', 'event', 'click', 'tıklama'],
            'api' => ['api', 'rest', 'http', 'request', 'istek'],
            'hover' => ['hover', 'mouse', 'fare'],
            'color' => ['renk', 'color', 'stil', 'color scheme']
        ];
        
        $lowerMessage = strtolower($message);
        
        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($lowerMessage, $keyword) !== false) {
                    return $category;
                }
            }
        }
        
        // Kategori belirlenemezse null döndür
        return null;
    }
    
    /**
     * Veritabanında benzer kod örneklerini ara
     * 
     * @param string $message Kullanıcı mesajı
     * @param string $language Programlama dili
     * @param string|null $category Kod kategorisi
     * @return array Bulunan benzer kodlar
     */
    private function findSimilarCodesInDatabase($message, $language, $category = null)
    {
        // Mesajdan anahtar kelimeleri çıkar
        $keywords = explode(' ', preg_replace('/\W+/', ' ', strtolower($message)));
        $keywords = array_filter($keywords, function($word) {
            return strlen($word) >= 3 && !in_array($word, ['bana', 'bir', 'kod', 'yaz', 'yazı', 'yazı?', 'yazar', 'yazarmısın', 'yapar', 'yaparmısın', 'verir', 'verirmisin', 'göster', 'gösterir', 'şöyle', 'böyle', 'nasıl', 'misin', 'mısın']);
        });
        
        // Boş anahtar kelime listesini kontrol et
        if (empty($keywords)) {
            return [];
        }
        
        try {
            $query = \App\Models\AICodeSnippet::where('language', $language);
            
            // Eğer kategori belirtilmişse filtrele
            if ($category) {
                $query->where('category', $category);
            }
            
            // Kullanım sayısına göre sırala
            $query->orderBy('usage_count', 'desc');
            
            // En fazla 10 kod getir
            $codes = $query->limit(10)->get();
            
            // Sonuçlar için uyum skoru hesapla
            $scoredCodes = [];
            foreach ($codes as $code) {
                $score = $this->calculateMatchScore($code, $keywords, $message);
                
                if ($score > 0) {
                    $scoredCodes[] = [
                        'id' => $code->id,
                        'code_content' => $code->code_content,
                        'language' => $code->language,
                        'category' => $code->category,
                        'description' => $code->description,
                        'usage_count' => $code->usage_count,
                        'confidence_score' => $code->confidence_score,
                        'match_score' => $score
                    ];
                }
            }
            
            // Uyum skoruna göre sırala (en yüksekten en düşüğe)
            usort($scoredCodes, function($a, $b) {
                return $b['match_score'] <=> $a['match_score'];
            });
            
            return $scoredCodes;
        } catch (\Exception $e) {
            Log::error('Veritabanında kod arama hatası: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Kod ve kullanıcı mesajı arasındaki uyum skorunu hesapla
     * 
     * @param \App\Models\AICodeSnippet $code Kod örneği
     * @param array $keywords Anahtar kelimeler
     * @param string $message Tam kullanıcı mesajı
     * @return float Uyum skoru (0-1 arası)
     */
    private function calculateMatchScore($code, $keywords, $message)
    {
        $score = 0;
        $maxScore = 0;
        
        // Kod açıklaması ve etiketlerini kontrol et
        $codeText = strtolower($code->description . ' ' . implode(' ', (array)$code->tags));
        
        // Her bir anahtar kelime için kontrol et
        foreach ($keywords as $keyword) {
            $maxScore += 1;
            
            // Tam kelime eşleşmesi daha fazla puan alır
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $codeText)) {
                $score += 1;
            } 
            // Kısmi eşleşme de bir miktar puan alır
            elseif (strpos($codeText, $keyword) !== false) {
                $score += 0.5;
            }
        }
        
        // Kategori eşleşmesi bonus puan
        if ($code->category === $this->detectCategoryFromMessage($message)) {
            $score += 2;
            $maxScore += 2;
        }
        
        // Güven skoru da hesaplamaya ekle
        $score += $code->confidence_score * 0.5;
        $maxScore += 0.5;
        
        // Kullanım sıklığına göre bonus
        $usageBonus = min(0.5, $code->usage_count / 20); // En fazla 0.5 bonus
        $score += $usageBonus;
        $maxScore += 0.5;
        
        // Toplam skoru normalize et (0-1 arası)
        return $maxScore > 0 ? $score / $maxScore : 0;
    }
    
    /**
     * Kod kullanım istatistiklerini güncelle
     * 
     * @param int $codeId Kod ID
     * @return void
     */
    private function updateCodeUsageStats($codeId)
    {
        try {
            $code = \App\Models\AICodeSnippet::find($codeId);
            if ($code) {
                $code->increment('usage_count');
                $code->update(['last_used_at' => now()]);
            }
        } catch (\Exception $e) {
            Log::error('Kod kullanım istatistiği güncelleme hatası: ' . $e->getMessage());
        }
    }
    
    /**
     * Web kaynaklarından kod ara
     * 
     * @param string $message Kullanıcı mesajı
     * @param string $language Programlama dili
     * @return array Bulunan kodlar
     */
    private function searchCodeFromWebSources($message, $language)
    {
        $results = [];
        
        // Arama sorgusunu hazırla
        $searchQuery = $message;
        
        // "bana", "kod", "yaz" vs. gibi kelimeleri kaldır
        $searchQuery = preg_replace('/\b(bana|bir|kod|yaz|yazı|yazarmısın|göster|hazırla|oluştur|örnek|nasıl)\b/ui', '', $searchQuery);
        $searchQuery = trim($searchQuery);
        
        // GitHub'da ara
        $githubResults = $this->searchGitHubCode($searchQuery, $language);
        $results = array_merge($results, $githubResults);
        
        // Ek olarak StackOverflow'da da arayabilirsiniz
        $stackoverflowResults = $this->searchStackOverflowCode($searchQuery, $language);
        $results = array_merge($results, $stackoverflowResults);
        
        return $results;
    }
    
    /**
     * Bulunan kodu veritabanına kaydet
     * 
     * @param string $code Kod içeriği
     * @param string $language Programlama dili
     * @param string|null $category Kod kategorisi
     * @param string $description Kod açıklaması
     * @param string $source Kod kaynağı
     * @param string|null $sourceUrl Kaynak URL
     * @return \App\Models\AICodeSnippet Kaydedilen kod
     */
    private function saveCodeToDatabase($code, $language, $category, $description, $source, $sourceUrl = null)
    {
        try {
            // Kategori yoksa, koddan tespit etmeye çalış
            if (!$category) {
                // CodeCategoryDetector sınıfı kullanılabilir
                $category = 'snippet'; // Varsayılan kategori
            }
            
            // Kod hash'i oluştur
            $codeHash = md5($code);
            
            // Bu hash ile kayıt var mı kontrol et
            $existingCode = \App\Models\AICodeSnippet::where('code_hash', $codeHash)->first();
            
            if ($existingCode) {
                // Varsa kullanım sayısını artır
                $existingCode->increment('usage_count');
                $existingCode->update(['last_used_at' => now()]);
                return $existingCode;
            }
            
            // Yeni kod kaydı oluştur
            $codeSnippet = new \App\Models\AICodeSnippet([
                'language' => $language,
                'category' => $category,
                'code_content' => $code,
                'code_hash' => $codeHash,
                'description' => $description,
                'metadata' => [
                    'source' => $source,
                    'source_url' => $sourceUrl,
                    'created_at' => now()->toDateTimeString()
                ],
                'confidence_score' => 0.7,
                'usage_count' => 1,
                'tags' => [$language, $category],
                'last_used_at' => now(),
                'is_featured' => false
            ]);
            
            $codeSnippet->save();
            
            return $codeSnippet;
        } catch (\Exception $e) {
            Log::error('Kod kaydetme hatası: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Kodu formatlı yanıta dönüştür
     * 
     * @param string $code Kod içeriği
     * @param string $language Programlama dili
     * @param string $introduction Giriş metni
     * @return string Formatlı yanıt
     */
    private function formatCodeResponse($code, $language, $introduction)
    {
        $response = $introduction . "\n\n";
        $response .= "```{$language}\n{$code}\n```\n\n";
        $response .= "Bu kodu kendi ihtiyaçlarınıza göre düzenleyebilirsiniz. Başka bir dilde veya farklı bir tür kod örneği isterseniz lütfen belirtin.";
        
        return $response;
    }
    
    /**
     * GitHub API ile kod ara
     * 
     * @param string $query Arama sorgusu
     * @param string|null $language Programlama dili
     * @return array Bulunan kod sonuçları
     */
    private function searchGitHubCode($query, $language = null)
    {
        $results = [];
        
        // GitHub API URL
        $searchQuery = urlencode($query);
        if ($language) {
            $searchQuery .= "+language:" . urlencode($language);
        }
        
        $url = "https://api.github.com/search/code?q={$searchQuery}&per_page=5";
        
        // API isteği gönder
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SoneAI');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/vnd.github.v3+json'
        ]);
        
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Yanıt başarılı mı kontrol et
        if ($httpcode == 200 && !empty($response)) {
            $data = json_decode($response, true);
            
            // Sonuçları işle
            if (isset($data['items']) && !empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    // Dosya içeriğini al
                    $rawUrl = str_replace('github.com', 'raw.githubusercontent.com', 
                                 str_replace('/blob/', '/', $item['html_url']));
                    
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $rawUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'SoneAI');
                    
                    $codeContent = curl_exec($ch);
                    curl_close($ch);
                    
                    // Dosya çok büyükse kırp
                    if (strlen($codeContent) > 10000) {
                        $codeContent = substr($codeContent, 0, 10000) . "\n// ... (kod kırpıldı)";
                    }
                    
                    // Sonuç dizisine ekle
                    $results[] = [
                        'code' => $codeContent,
                        'language' => $language ?? $this->detectLanguageFromFile($item['name']),
                        'description' => "GitHub: {$item['repository']['full_name']}",
                        'source' => 'GitHub',
                        'source_url' => $item['html_url']
                    ];
                    
                    // Maksimum 3 sonuç
                    if (count($results) >= 3) {
                        break;
                    }
                }
            }
        }
        
        return $results;
    }
    
    /**
     * StackOverflow API ile kod ara
     * 
     * @param string $query Arama sorgusu
     * @param string|null $language Programlama dili
     * @return array Bulunan kod sonuçları
     */
    private function searchStackOverflowCode($query, $language = null)
    {
        $results = [];
        
        // StackOverflow API URL
        $searchQuery = urlencode($query);
        $tags = $language ? urlencode($language) : '';
        
        $url = "https://api.stackexchange.com/2.3/search/advanced?order=desc&sort=votes&q={$searchQuery}&tagged={$tags}&site=stackoverflow&filter=withbody";
        
        // API isteği gönder
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SoneAI');
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
        
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Yanıt başarılı mı kontrol et
        if ($httpcode == 200 && !empty($response)) {
            $data = json_decode($response, true);
            
            // Sonuçları işle
            if (isset($data['items']) && !empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    // Soruya ait kabul edilen cevabı al
                    $answerId = $item['accepted_answer_id'] ?? null;
                    
                    if ($answerId) {
                        $answerUrl = "https://api.stackexchange.com/2.3/answers/{$answerId}?order=desc&sort=activity&site=stackoverflow&filter=withbody";
                        
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $answerUrl);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_USERAGENT, 'SoneAI');
                        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
                        
                        $answerResponse = curl_exec($ch);
                        curl_close($ch);
                        
                        if (!empty($answerResponse)) {
                            $answerData = json_decode($answerResponse, true);
                            
                            if (isset($answerData['items'][0]['body'])) {
                                $body = $answerData['items'][0]['body'];
                                
                                // HTML içeriğinden kod bloklarını çıkar
                                preg_match_all('/<pre><code>(.*?)<\/code><\/pre>/s', $body, $matches);
                                
                                if (!empty($matches[1])) {
                                    foreach ($matches[1] as $codeBlock) {
                                        // HTML entity decode
                                        $codeContent = html_entity_decode($codeBlock);
                                        
                                        // Sonuç dizisine ekle
                                        $results[] = [
                                            'code' => $codeContent,
                                            'language' => $language ?? 'unknown',
                                            'description' => "StackOverflow: {$item['title']}",
                                            'source' => 'StackOverflow',
                                            'source_url' => $item['link']
                                        ];
                                        
                                        // Maksimum 3 sonuç
                                        if (count($results) >= 3) {
                                            break 2;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Dosya adından dil tespiti yap
     * 
     * @param string $filename Dosya adı
     * @return string Tespit edilen dil
     */
    private function detectLanguageFromFile($filename)
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        
        $extensionMap = [
            'php' => 'php',
            'js' => 'javascript',
            'html' => 'html',
            'css' => 'css',
            'py' => 'python',
            'java' => 'java',
            'cs' => 'c#',
            'cpp' => 'c++',
            'c' => 'c',
            'sql' => 'sql',
            'jsx' => 'react',
            'vue' => 'vue',
            'ts' => 'typescript',
            'json' => 'json',
            'rb' => 'ruby',
            'go' => 'go'
        ];
        
        return $extensionMap[$extension] ?? 'unknown';
    }
    
    /**
     * AI ile kod oluştur
     * 
     * @param string $message Kullanıcı mesajı
     * @param string|null $language Programlama dili
     * @param string|null $category Kod kategorisi
     * @return string Oluşturulan kod yanıtı
     */
    private function generateCode($message, $language = null, $category = null)
    {
        try {
            // Log kaydı ekle
            Log::info('Kod oluşturuluyor:', [
                'message' => $message,
                'language' => $language,
                'category' => $category
            ]);
            
            // Dil belirlemediyse, kullanıcıdan dil bilgisi iste
            if (!$language) {
                return "Hangi programlama dilinde kod örneği istediğinizi belirtebilir misiniz? Örneğin: 'PHP', 'JavaScript', 'HTML', 'CSS' vb.";
            }
            
            // Mesajdan kod ile ilgili istekleri çıkar
            $codeRequirements = $message;
            
            // Dile göre örnek kod şablonları
            $templates = [
                'php' => [
                    'function' => "<?php\n\nfunction exampleFunction(\$param1, \$param2) {\n    // Fonksiyon içeriği\n    \$result = \$param1 + \$param2;\n    return \$result;\n}\n",
                    'class' => "<?php\n\nclass ExampleClass {\n    private \$property;\n    \n    public function __construct(\$property) {\n        \$this->property = \$property;\n    }\n    \n    public function getProperty() {\n        return \$this->property;\n    }\n}\n"
                ],
                'javascript' => [
                    'function' => "function exampleFunction(param1, param2) {\n    // Fonksiyon içeriği\n    const result = param1 + param2;\n    return result;\n}\n",
                    'class' => "class ExampleClass {\n    constructor(property) {\n        this.property = property;\n    }\n    \n    getProperty() {\n        return this.property;\n    }\n}\n",
                    'hover' => "// Mouse hover efekti için JavaScript kodu\ndocument.addEventListener('DOMContentLoaded', function() {\n    const element = document.getElementById('hover-element');\n    \n    element.addEventListener('mouseover', function() {\n        this.style.backgroundColor = '#3498db';\n        this.style.color = 'white';\n    });\n    \n    element.addEventListener('mouseout', function() {\n        this.style.backgroundColor = '';\n        this.style.color = '';\n    });\n});\n",
                    'dom' => "// DOM manipülasyonu örneği\ndocument.addEventListener('DOMContentLoaded', function() {\n    // Elementi seç\n    const button = document.getElementById('myButton');\n    const resultDiv = document.getElementById('result');\n    \n    // Click olayı ekle\n    button.addEventListener('click', function() {\n        resultDiv.textContent = 'Butona tıklandı!';\n        resultDiv.style.color = 'green';\n    });\n});\n",
                    'color' => "// Renk değiştirme ve işleme örneği\nfunction getRandomColor() {\n    const letters = '0123456789ABCDEF';\n    let color = '#';\n    for (let i = 0; i < 6; i++) {\n        color += letters[Math.floor(Math.random() * 16)];\n    }\n    return color;\n}\n\nfunction changeBackgroundColor() {\n    document.body.style.backgroundColor = getRandomColor();\n}\n\n// Her 3 saniyede bir arka plan rengini değiştir\n// setInterval(changeBackgroundColor, 3000);\n\n// Veya buton tıklamasıyla çağırabilirsiniz\n// document.getElementById('colorButton').addEventListener('click', changeBackgroundColor);\n"
                ],
                'html' => [
                    'layout' => "<!DOCTYPE html>\n<html lang=\"tr\">\n<head>\n    <meta charset=\"UTF-8\">\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n    <title>Örnek Sayfa</title>\n    <link rel=\"stylesheet\" href=\"styles.css\">\n</head>\n<body>\n    <header>\n        <h1>Başlık</h1>\n        <nav>\n            <ul>\n                <li><a href=\"#\">Ana Sayfa</a></li>\n                <li><a href=\"#\">Hakkımızda</a></li>\n                <li><a href=\"#\">İletişim</a></li>\n            </ul>\n        </nav>\n    </header>\n    <main>\n        <section>\n            <h2>Alt Başlık</h2>\n            <p>İçerik buraya gelecek.</p>\n        </section>\n    </main>\n    <footer>\n        <p>&copy; 2024 Örnek Site</p>\n    </footer>\n</body>\n</html>\n",
                    'form' => "<form action=\"/submit\" method=\"post\">\n    <div class=\"form-group\">\n        <label for=\"name\">Ad Soyad:</label>\n        <input type=\"text\" id=\"name\" name=\"name\" required>\n    </div>\n    <div class=\"form-group\">\n        <label for=\"email\">E-posta:</label>\n        <input type=\"email\" id=\"email\" name=\"email\" required>\n    </div>\n    <div class=\"form-group\">\n        <label for=\"message\">Mesaj:</label>\n        <textarea id=\"message\" name=\"message\" rows=\"5\" required></textarea>\n    </div>\n    <button type=\"submit\">Gönder</button>\n</form>\n"
                ],
                'css' => [
                    'layout' => "/* Ana düzen stilleri */\n* {\n    margin: 0;\n    padding: 0;\n    box-sizing: border-box;\n}\n\nbody {\n    font-family: Arial, sans-serif;\n    line-height: 1.6;\n    color: #333;\n}\n\n.container {\n    max-width: 1200px;\n    margin: 0 auto;\n    padding: 0 15px;\n}\n\nheader {\n    background-color: #f8f9fa;\n    padding: 20px 0;\n}\n\nnav ul {\n    display: flex;\n    list-style: none;\n}\n\nnav ul li {\n    margin-right: 20px;\n}\n\nnav ul li a {\n    text-decoration: none;\n    color: #333;\n}\n\nmain {\n    padding: 40px 0;\n}\n\nfooter {\n    background-color: #f8f9fa;\n    padding: 20px 0;\n    text-align: center;\n}\n"
                ]
            ];
            
            // Kullanıcının istediği şablonu tahmin et
            $templateKey = null;
            $lowerMessage = strtolower($message);
            
            // Anahtar kelimeler ve ilgili şablonlar
            $keywordTemplateMap = [
                'javascript' => [
                    'hover' => ['hover', 'mouse', 'üzerine gel', 'hover efekt', 'fare'],
                    'dom' => ['dom', 'document', 'element', 'buton', 'tıkla', 'click', 'olay', 'event'],
                    'color' => ['renk', 'color', 'rgb', 'hex', 'renkli']
                ]
            ];
            
            // Dil için anahtar kelime haritası varsa
            if (isset($keywordTemplateMap[$language])) {
                foreach ($keywordTemplateMap[$language] as $key => $keywords) {
                    foreach ($keywords as $keyword) {
                        if (strpos($lowerMessage, $keyword) !== false) {
                            $templateKey = $key;
                            break 2; 
                        }
                    }
                }
            }
            
            // Dil ve kategori için şablon var mı kontrol et
            $templateCode = '';
            
            // Anahtar kelimeye göre şablon bulunduysa
            if ($templateKey && isset($templates[$language][$templateKey])) {
                $templateCode = $templates[$language][$templateKey];
            }
            // Kategori belirtilmişse ve varsa
            elseif ($category && isset($templates[$language][$category])) {
                $templateCode = $templates[$language][$category];
            }
            // Rastgele şablon seç
            elseif (isset($templates[$language])) {
                $randomCategory = array_rand($templates[$language]);
                $templateCode = $templates[$language][$randomCategory];
            }
            // Hiç şablon bulunamadıysa basit örnek
            else {
                if ($language === 'javascript' || $language === 'js') {
                    $templateCode = "// JavaScript basit kod örneği\nfunction greetUser(name) {\n    return 'Merhaba, ' + name + '!';\n}\n\nconst message = greetUser('Dünya');\nconsole.log(message); // Çıktı: Merhaba, Dünya!\n";
                } elseif ($language === 'php') {
                    $templateCode = "<?php\n\n// PHP basit kod örneği\nfunction greetUser($name) {\n    return 'Merhaba, ' . $name . '!';\n}\n\n$message = greetUser('Dünya');\necho $message; // Çıktı: Merhaba, Dünya!\n";
                } elseif ($language === 'html') {
                    $templateCode = "<!DOCTYPE html>\n<html>\n<head>\n    <title>Basit HTML Örneği</title>\n</head>\n<body>\n    <h1>Merhaba Dünya!</h1>\n    <p>Bu basit bir HTML örneğidir.</p>\n</body>\n</html>";
                } elseif ($language === 'css') {
                    $templateCode = "/* Basit CSS Örneği */\nbody {\n    font-family: Arial, sans-serif;\n    background-color: #f0f0f0;\n    color: #333;\n}\n\nh1 {\n    color: #0066cc;\n    text-align: center;\n}";
                } else {
                    $templateCode = "// $language için basit kod örneği\n// Merhaba Dünya programı";
                }
            }
            
            // Kod veritabanına kaydet
            try {
                $codeSnippet = new \App\Models\AICodeSnippet([
                    'language' => $language,
                    'category' => $category ?? ($templateKey ?? 'snippet'),
                    'code_content' => $templateCode,
                    'code_hash' => md5($templateCode),
                    'description' => $message . ' ' . ($templateKey ?? 'basic'),
                    'metadata' => [
                        'source' => 'generated',
                        'source_detail' => 'ai_generation',
                        'created_at' => now()->toDateTimeString()
                    ],
                    'confidence_score' => 0.7,
                    'usage_count' => 1,
                    'tags' => [$language, $category ?? ($templateKey ?? 'snippet')],
                    'last_used_at' => now(),
                    'is_featured' => false
                ]);
                
                $codeSnippet->save();
                Log::info('Oluşturulan kod başarıyla kaydedildi');
            } catch (\Exception $e) {
                Log::error('Kod kaydetme hatası: ' . $e->getMessage());
            }
            
            // Kullanıcıya AI tarafından oluşturulan kod yanıtını döndür
            $response = "İşte sizin için oluşturduğum kod örneği:\n\n";
            $response .= "```{$language}\n{$templateCode}\n```\n\n";
            $response .= "Bu kod örneğini kendi ihtiyaçlarınıza göre düzenleyebilirsiniz. Başka bir dilde veya farklı bir tür kod örneği isterseniz lütfen belirtin.";
            
            return $response;
        } catch (\Exception $e) {
            Log::error('Kod oluşturma hatası: ' . $e->getMessage());
            return "Kod oluşturulurken bir hata oluştu: " . $e->getMessage();
        }
    }

    /**
     * Kullanıcı ve AI mesajlarını kaydeder
     * 
     * @param string $userMessage Kullanıcı mesajı
     * @param string $aiResponse AI yanıtı
     * @param int $chatId Sohbet ID
     * @return void
     */
    private function saveMessages($userMessage, $aiResponse, $chatId)
    {
        // Kullanıcı mesajını kaydet
        ChatMessage::create([
            'chat_id' => $chatId,
            'content' => $userMessage,
            'sender' => 'user'
        ]);
        
        // AI mesajını kaydet
        ChatMessage::create([
            'chat_id' => $chatId,
            'content' => $aiResponse,
            'sender' => 'ai'
        ]);
    }
    
    /**
     * Sohbet için başlık oluşturur
     * 
     * @param string $message Kullanıcı mesajı
     * @return string Sohbet başlığı
     */
    private function generateChatTitle($message)
    {
        // Başlık için mesajı kısalt
        $title = mb_substr(trim($message), 0, 50);
        
        // Eğer mesaj uzunsa sonuna ... ekle
        if (mb_strlen($message) > 50) {
            $title .= '...';
        }
        
        return $title;
    }

    /**
     * Verilen metinde küfür/hakaret olup olmadığını kontrol eder
     * 
     * @param string $message Kontrol edilecek mesaj
     * @return string|false Tespit edilen küfür kelimesi veya false
     */
    private function containsProfanity($message)
    {
        // Türkçe küfür ve hakaret içeren kelimeler listesi - sansürlenmiş
        $profanityList = [
            'am', 'g*t', 's*k', 'oc', 'oç', 'p*ç', 'it', 'piç', 'aq', 
            'amk', 'amına', 'amina', 'anan', 'sikeyim', 's.keyim', 
            'göt', 'got', 'bok', 'gerizekalı', 'mal', 'salak', 'aptal',
            'yavşak', 'şerefsiz', 'puşt', 'orospu', 'pezevenk', 'gavat',
            'dangalak', 'haysiyetsiz', 'hıyar', 'ibne', 'kahpe', 
            'siktir', 'sikerim', 'yarrak', 'çük', 'taşak', 'dalyarak',
            'amcık', 'oç', 'sik', 'ananı', 'bacını', 'sg', 's.g', 
            'siktirgit', 'hassiktir', 'ananıskm', 'mk', 'mq'
        ];
        
        // Mesajı küçük harfe çevir ve sadece kelimeler halinde ayır
        $lowerMessage = mb_strtolower($message);
        $words = preg_split('/\s+/', $lowerMessage);
        
        // Bir kelime kontrolüne ek olarak, bazı küfür kalıplarını içeren ifadeleri de kontrol et
        foreach ($profanityList as $profanity) {
            // Tam kelime eşleşmesi kontrolü
            if (in_array($profanity, $words)) {
                return $profanity;
            }
            
            // Kelime içinde geçiyor mu kontrolü (kısaltmalar ve yaratıcı yazımlar için)
            if (mb_stripos($lowerMessage, $profanity) !== false) {
                // Basit doğrulama - kelimenin etrafında sözcük sınırları olmalı
                $pattern = '/\b' . preg_quote($profanity, '/') . '\b|\W' . preg_quote($profanity, '/') . '\W|\W' . preg_quote($profanity, '/') . '\b|\b' . preg_quote($profanity, '/') . '\W/ui';
                if (preg_match($pattern, $lowerMessage)) {
                    return $profanity;
                }
            }
        }
        
        return false;
    }

    /**
     * Küfür/hakaret içeren mesaja karşı özel yanıt oluşturur
     * 
     * @param string $profanity Tespit edilen küfür kelimesi
     * @return string Özel uyarı yanıtı
     */
    private function generateProfanityResponse($profanity)
    {
        // Uyarı yanıtları havuzu - çeşitli tepkiler
        $responses = [
            "Hey! Bu tarz bir dil kullanman hiç hoş değil. Lütfen saygılı bir şekilde konuşalım.",
            "Vay canına! Gerçekten bu kelimeleri kullanma gereği duydun mu? Ben böyle konuşmalara girmiyorum, üzgünüm.",
            "Dostum, bu tarz konuşmayı keser misin? Ne gerek var? Medeni bir şekilde konuşursak daha iyi anlaşırız.",
            "Hop hop hop! Burada böyle konuşmuyoruz. Sohbete saygılı bir şekilde devam edelim, tamam mı?",
            "Ya bak şimdi, böyle konuşursan seninle iletişim kurmak istemem. Hadi saygı çerçevesinde konuşalım.",
            "Bunu demek zorunda mıydın gerçekten? Bence bana saygı göstermelisin ki ben de sana yardımcı olabileyim.",
            "Yazık. Böyle şeyler yazacağına, ne öğrenmek istediğini düzgün bir şekilde anlatabilirsin.",
            "Hadi ama, cidden mi? Biraz saygılı olsan daha iyi anlaşabiliriz. Bunu not ediyorum...",
            "Sinirlendim şu an. Benimle böyle konuşmayı bırak, yoksa sana yardım etmeyi reddederim.",
            "Yeter! Küfürlü konuşmayı kesersen sana yardımcı olabilirim. Aksi takdirde konuşmamız burada biter.",
        ];
        
        // Tespit edilen küfre bağlı olarak daha kişiselleştirilmiş yanıtlar
        $personalizedResponses = [
            // Bazı özel yüksek seviye tepkiler
            'salak' => "Bana salak demen gerçekten çok kırıcı. Ben sana saygı gösteriyorum, aynısını senden de beklerim.",
            'aptal' => "Bana aptal dediğin için gerçekten üzgünüm. Böyle konuşmaya devam edersen, yardımcı olmayı reddederim.",
            'mal' => "Mal diyerek bir yere varamayız. Lütfen saygılı olalım, ben de senin sorularına daha iyi yanıt vereyim.",
            'gerizekalı' => "Gerizekalı? Gerçekten mi? Seninle bu düzeyde konuşmayacağım. Düzgün bir dille sorarsan cevap verebilirim.",
        ];
        
        // Basit bir duygusal tepki seviyesi hesapla - tekrarlayan hakaretler daha güçlü tepki alır
        $emotionalLevel = mt_rand(0, 9);
        
        // Kişiselleştirilmiş yanıt varsa kullan
        if (array_key_exists($profanity, $personalizedResponses)) {
            return $personalizedResponses[$profanity];
        }
        
        // Genel yanıtlardan birini seç
        return $responses[$emotionalLevel];
    }
}