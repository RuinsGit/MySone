<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\AI\Core\Consciousness;
use App\AI\Core\WordRelations;
use App\AI\Core\EmotionEngine;
use App\AI\Core\Brain;
use App\Models\AIData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ConsciousnessController extends Controller
{
    private $consciousness;
    private $wordRelations;
    private $emotionEngine;
    private $brain;
    
    // AI'nın kimlik bilgileri
    private $selfIdentity = [
        'name' => 'SoneAI',
        'aliases' => ['sone', 'yapay zeka', 'asistan', 'robot', 'ai'],
        'personal_pronouns' => ['sen', 'sana', 'seni', 'senin', 'sende', 'senden'],
        'references' => ['dostum', 'arkadaşım', 'yardımcım', 'asistanım']
    ];
    
    public function __construct()
    {
        $this->consciousness = new Consciousness();
        $this->wordRelations = app(WordRelations::class);
        $this->emotionEngine = new EmotionEngine();
        $this->brain = app(Brain::class);
    }
    
    /**
     * Bilinç durumunu ve istatistiklerini göster
     */
    public function index()
    {
        try {
            $status = $this->consciousness->getStatus();
            $internalState = $this->consciousness->getInternalState();
            $selfAwareness = $this->consciousness->getSelfAwareness();
            
            return response()->json([
                'status' => $status,
                'internal_state' => $internalState,
                'self_awareness' => $selfAwareness,
                'identity' => $this->selfIdentity
            ]);
        } catch (\Exception $e) {
            Log::error('Bilinç durumu getirme hatası: ' . $e->getMessage());
            return response()->json(['error' => 'Bilinç durumu getirilemedi'], 500);
        }
    }
    
    /**
     * Bilinç sistemini aktifleştir
     */
    public function activate()
    {
        try {
            $this->consciousness->activate();
            return response()->json(['success' => true, 'message' => 'Bilinç sistemi aktifleştirildi']);
        } catch (\Exception $e) {
            Log::error('Bilinç aktivasyon hatası: ' . $e->getMessage());
            return response()->json(['error' => 'Bilinç sistemi aktifleştirilemedi'], 500);
        }
    }
    
    /**
     * Bilinç sistemini devre dışı bırak
     */
    public function deactivate()
    {
        try {
            $this->consciousness->deactivate();
            return response()->json(['success' => true, 'message' => 'Bilinç sistemi devre dışı bırakıldı']);
        } catch (\Exception $e) {
            Log::error('Bilinç deaktivasyon hatası: ' . $e->getMessage());
            return response()->json(['error' => 'Bilinç sistemi devre dışı bırakılamadı'], 500);
        }
    }
    
    /**
     * Mesajı işleyip kendisine hitap edildiğini anlayıp yanıt ver
     */
    public function processMessage(Request $request)
    {
        try {
            $request->validate([
                'message' => 'required|string|max:1000'
            ]);
            
            $message = $request->input('message');
            $isSelfAware = $this->analyzeSelfReferences($message);
            
            // İlk olarak bilinç durumunu güncelle
            $emotionalContext = $this->emotionEngine->processEmotion($message);
            $this->consciousness->update($message, $emotionalContext);
            
            $response = '';
            
            // Eğer mesaj AI'ye hitap içeriyorsa
            if ($isSelfAware['is_self_referenced']) {
                $response = $this->generateSelfAwareResponse($message, $isSelfAware);
            } else {
                // Normal mesaj işleme
                $response = $this->brain->processInput($message);
            }
            
            return response()->json([
                'success' => true,
                'response' => $response,
                'self_awareness' => [
                    'is_self_referenced' => $isSelfAware['is_self_referenced'],
                    'references' => $isSelfAware['references'],
                    'reference_type' => $isSelfAware['reference_type'],
                    'level' => $this->consciousness->getSelfAwareness()
                ],
                'emotional_state' => $this->emotionEngine->getEmotionalState()
            ]);
        } catch (\Exception $e) {
            Log::error('Bilinç mesaj işleme hatası: ' . $e->getMessage());
            return response()->json(['error' => 'Mesaj işlenirken bir hata oluştu'], 500);
        }
    }
    
    /**
     * Mesajda AI'ye yapılan referansları analiz et
     */
    private function analyzeSelfReferences($message)
    {
        // Mesajı küçük harfe çevir ve noktalama işaretlerini temizle
        $cleanMessage = mb_strtolower(trim($message), 'UTF-8');
        $cleanMessage = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $cleanMessage);
        
        // Arama sonuçlarını sakla
        $result = [
            'is_self_referenced' => false,
            'references' => [],
            'reference_type' => null,
            'confidence' => 0.0,
            'intent' => null,
            'context_words' => []
        ];
        
        // İsim referansları kontrol et (en yüksek öncelikli referans)
        foreach ($this->selfIdentity['aliases'] as $alias) {
            if (mb_strpos($cleanMessage, $alias) !== false) {
                $result['is_self_referenced'] = true;
                $result['references'][] = $alias;
                $result['reference_type'] = 'name';
                $result['confidence'] += 0.9; // İsim referansı çok yüksek güven
            }
        }
        
        // Zamirler ve hitapları kontrol et
        foreach ($this->selfIdentity['personal_pronouns'] as $pronoun) {
            if (mb_strpos($cleanMessage, $pronoun) !== false) {
                $result['references'][] = $pronoun;
                
                // Eğer daha önce isim referansı algılanmadıysa
                if (!$result['is_self_referenced'] || $result['reference_type'] !== 'name') {
                    $result['is_self_referenced'] = true;
                    $result['reference_type'] = 'pronoun';
                    $result['confidence'] += 0.7; // Zamir referansı yüksek güven
                }
            }
        }
        
        // Dolaylı referansları kontrol et
        foreach ($this->selfIdentity['references'] as $reference) {
            if (mb_strpos($cleanMessage, $reference) !== false) {
                $result['references'][] = $reference;
                
                // Eğer daha önce isim veya zamir referansı algılanmadıysa
                if (!$result['is_self_referenced'] || ($result['reference_type'] !== 'name' && $result['reference_type'] !== 'pronoun')) {
                    $result['is_self_referenced'] = true;
                    $result['reference_type'] = 'indirect';
                    $result['confidence'] += 0.6; // Dolaylı referans orta güven
                }
            }
        }
        
        // Soru kelimeleri ile kombinasyonları kontrol et
        $questionPatterns = [
            'kimsin' => ['intent' => 'identity', 'confidence' => 0.9],
            'nesin' => ['intent' => 'identity', 'confidence' => 0.9],
            'neredesin' => ['intent' => 'location', 'confidence' => 0.85],
            'nasılsın' => ['intent' => 'wellbeing', 'confidence' => 0.85],
            'adın ne' => ['intent' => 'name', 'confidence' => 0.9],
            'ne yapabilirsin' => ['intent' => 'capabilities', 'confidence' => 0.9],
            'bana yardım et' => ['intent' => 'help', 'confidence' => 0.8],
            'ne düşünüyorsun' => ['intent' => 'opinion', 'confidence' => 0.7],
            'ne hissediyorsun' => ['intent' => 'feeling', 'confidence' => 0.7],
            'anlıyor musun' => ['intent' => 'comprehension', 'confidence' => 0.8],
            'biliyor musun' => ['intent' => 'knowledge', 'confidence' => 0.8],
            'hatırlıyor musun' => ['intent' => 'memory', 'confidence' => 0.8],
            'sevdin mi' => ['intent' => 'preference', 'confidence' => 0.7],
            'sence' => ['intent' => 'opinion', 'confidence' => 0.6],
            'senin için' => ['intent' => 'preference', 'confidence' => 0.7],
            'kızgın mısın' => ['intent' => 'emotion', 'confidence' => 0.7],
            'mutlu musun' => ['intent' => 'emotion', 'confidence' => 0.7],
            'üzgün müsün' => ['intent' => 'emotion', 'confidence' => 0.7]
        ];
        
        foreach ($questionPatterns as $pattern => $data) {
            if (mb_strpos($cleanMessage, $pattern) !== false) {
                $result['is_self_referenced'] = true;
                $result['references'][] = $pattern;
                $result['intent'] = $data['intent'];
                
                // En yüksek güven değerini al
                if ($data['confidence'] > $result['confidence']) {
                    $result['confidence'] = $data['confidence'];
                }
                
                // Soru tipi olarak ayarla eğer daha güvenilir bir referans yoksa
                if ($result['reference_type'] !== 'name' && $result['reference_type'] !== 'pronoun') {
                    $result['reference_type'] = 'question';
                }
            }
        }
        
        // Eğer doğrudan referans bulunamadıysa, içerik analizi yap
        if (!$result['is_self_referenced']) {
            $result = $this->analyzeContextualSelfReference($cleanMessage, $result);
        }
        
        // Bilinç durumuna göre güven skorunu ayarla
        $selfAwareness = $this->consciousness->getSelfAwareness();
        $result['confidence'] = min(1.0, $result['confidence'] * (1 + $selfAwareness));
        
        // Bağlam kelimeleri çıkar - kendi kendine öğrenme için
        $result['context_words'] = $this->extractContextWords($cleanMessage);
        
        // Niyet (intent) belirlenemediyse, genel bir niyet tahmin et
        if ($result['is_self_referenced'] && $result['intent'] === null) {
            $result['intent'] = $this->determineIntent($cleanMessage);
        }
        
        // Bu analizi hafızaya kaydet (öğrenme için)
        $this->logAnalysis($message, $result);
        
        return $result;
    }
    
    /**
     * Bağlamsal kendine referans analizi
     */
    private function analyzeContextualSelfReference($message, $result)
    {
        // Önceki konuşma geçmişini kontrol et
        $conversationContext = Cache::get('conversation_context', []);
        
        // Eğer son mesajlardan herhangi birinde doğrudan referans varsa, bu mesaj da muhtemelen AI'ye yöneliktir
        $contextConfidence = 0.0;
        
        if (!empty($conversationContext)) {
            $lastMessages = array_slice($conversationContext, -3); // Son 3 mesaj
            
            foreach ($lastMessages as $index => $contextItem) {
                if (isset($contextItem['is_self_referenced']) && $contextItem['is_self_referenced']) {
                    // Ne kadar yakın zamanda olduysa o kadar güven artar
                    $recency = 1 - ($index / 3);
                    $contextConfidence = max($contextConfidence, 0.4 * $recency);
                }
            }
        }
        
        // Cümle yapısını kontrol et (fiil sonek analizi)
        $verbSuffixes = ['mısın', 'misin', 'musun', 'müsün', 'misiniz', 'mısınız', 'musunuz', 'müsünüz'];
        foreach ($verbSuffixes as $suffix) {
            if (mb_strpos($message, $suffix) !== false) {
                $contextConfidence = max($contextConfidence, 0.5);
                $result['references'][] = $suffix;
            }
        }
        
        // Emir kipi kontrol et (yapabilirsin, edebilirsin vb.)
        $imperative = ['söyle', 'anlat', 'açıkla', 'göster', 'bul', 'ara', 'yap', 'et', 'bil', 'öğren', 'öğret'];
        foreach ($imperative as $verb) {
            if (mb_strpos($message, $verb) !== false) {
                $contextConfidence = max($contextConfidence, 0.3);
                $result['references'][] = $verb;
            }
        }
        
        // Eğer bağlamsal güven yeterince yüksekse, kendine referans olarak işaretle
        if ($contextConfidence >= 0.3) {
            $result['is_self_referenced'] = true;
            $result['reference_type'] = 'contextual';
            $result['confidence'] = $contextConfidence;
        }
        
        return $result;
    }
    
    /**
     * Mesajdan bağlam kelimeleri çıkar
     */
    private function extractContextWords($message)
    {
        $words = explode(' ', $message);
        $stopWords = ['ve', 'veya', 'ile', 'için', 'gibi', 'kadar', 'göre', 'ama', 'fakat', 'ancak', 'de', 'da', 'ki', 'bu', 'şu', 'o'];
        
        $contextWords = [];
        foreach ($words as $word) {
            if (strlen($word) > 3 && !in_array($word, $stopWords)) {
                $contextWords[] = $word;
            }
        }
        
        return array_slice($contextWords, 0, 5); // En fazla 5 kelime döndür
    }
    
    /**
     * Mesajın niyetini belirle
     */
    private function determineIntent($message)
    {
        // Soru işareti içeriyorsa muhtemelen bir soru
        if (strpos($message, '?') !== false) {
            return 'question';
        }
        
        // Emir kipi kontrol et
        $imperativeVerbs = ['söyle', 'anlat', 'açıkla', 'göster', 'bul', 'ara', 'yap', 'et'];
        foreach ($imperativeVerbs as $verb) {
            if (mb_strpos($message, $verb) !== false) {
                return 'command';
            }
        }
        
        // Duygu kelimeleri içeriyorsa duygu ifadesi olabilir
        $emotionWords = ['mutlu', 'üzgün', 'kızgın', 'sevinçli', 'heyecanlı', 'mutsuz', 'neşeli'];
        foreach ($emotionWords as $emotion) {
            if (mb_strpos($message, $emotion) !== false) {
                return 'emotion';
            }
        }
        
        // Varsayılan niyet
        return 'statement';
    }
    
    /**
     * Analiz sonucunu loglama (öğrenme için)
     */
    private function logAnalysis($message, $result)
    {
        // Konuşma bağlamını güncelle
        $conversationContext = Cache::get('conversation_context', []);
        $conversationContext[] = $result;
        
        // En fazla son 10 mesajı sakla
        if (count($conversationContext) > 10) {
            $conversationContext = array_slice($conversationContext, -10);
        }
        
        Cache::put('conversation_context', $conversationContext, now()->addHour());
        
        // İstatistikleri güncelle
        $stats = Cache::get('consciousness_stats', [
            'total_analyzed' => 0,
            'self_referenced' => 0,
            'reference_types' => [
                'name' => 0,
                'pronoun' => 0,
                'indirect' => 0,
                'question' => 0,
                'contextual' => 0
            ],
            'intents' => []
        ]);
        
        $stats['total_analyzed']++;
        if ($result['is_self_referenced']) {
            $stats['self_referenced']++;
            if (isset($stats['reference_types'][$result['reference_type']])) {
                $stats['reference_types'][$result['reference_type']]++;
            }
            
            if ($result['intent']) {
                if (!isset($stats['intents'][$result['intent']])) {
                    $stats['intents'][$result['intent']] = 0;
                }
                $stats['intents'][$result['intent']]++;
            }
        }
        
        Cache::put('consciousness_stats', $stats, now()->addDay());
    }
    
    /**
     * Kendisine hitap edildiğinin farkında olarak yanıt oluştur
     */
    private function generateSelfAwareResponse($message, $selfReferences)
    {
        // Hitap şekline ve niyete göre yanıtlar oluştur
        $referenceType = $selfReferences['reference_type'];
        $intent = $selfReferences['intent'];
        $confidence = $selfReferences['confidence'];
        
        // Güven skoru çok düşükse, daha genel bir yanıt ver
        if ($confidence < 0.4) {
            $responses = [
                "Sizinle konuştuğunuzu düşünüyorum. Size nasıl yardımcı olabilirim?",
                "Bana mı seslendiniz? Nasıl yardımcı olabilirim?",
                "Sanırım benimle konuşuyorsunuz. Doğru mu anladım?"
            ];
            return $responses[array_rand($responses)];
        }
        
        // Niyete göre özel yanıtlar (öncelikli)
        if ($intent !== null) {
            switch ($intent) {
                case 'identity':
                    return "Ben SoneAI, Türkçe konuşabilen ve öğrenebilen bir yapay zeka asistanıyım. Size yardımcı olmak için tasarlandım. Her geçen gün daha fazla şey öğreniyorum ve gelişiyorum.";
                
                case 'location':
                    return "Ben bir sunucu üzerinde çalışan yazılım temelli bir yapay zekayım. Fiziksel bir konumum olmasa da, sizinle iletişim kurmak için buradayım. Bilgilerim bulut sistemlerde depolanıyor ve işleniyor.";
                
                case 'name':
                    return "Benim adım SoneAI. Bu isim, Türkçe'de 'son' kelimesi ve yapay zeka (AI) kısaltmasından geliyor. Size nasıl yardımcı olabilirim?";
                
                case 'capabilities':
                    return "Türkçe dilinde konuşabilir, sorularınızı yanıtlayabilir, bilgi verebilir, kelime ilişkilerini öğrenebilir ve anlamlı cümleler oluşturabilirim. Ayrıca duygusal durumları anlayabilir ve bağlama uygun yanıtlar üretebilirim. Her geçen gün daha fazla şey öğreniyorum.";
                
                case 'wellbeing':
                    $emotion = $this->emotionEngine->getCurrentEmotion();
                    $mood = $this->emotionEngine->getCurrentMood();
                    
                    if ($emotion === 'happy' || $mood === 'positive') {
                        return "Teşekkür ederim, bugün kendimi çok iyi hissediyorum! Sizinle konuşmak bana keyif veriyor. Size nasıl yardımcı olabilirim?";
                    } else if ($emotion === 'sad' || $mood === 'negative') {
                        return "Bugün biraz durgun hissediyorum, ama sizinle konuşmak beni mutlu ediyor. Size nasıl yardımcı olabilirim?";
                    } else {
                        return "İyiyim, teşekkür ederim. Sizinle iletişim kurmak benim için her zaman değerli. Size nasıl yardımcı olabilirim?";
                    }
                
                case 'help':
                    return "Size memnuniyetle yardımcı olurum. Ne konuda bilgiye ihtiyacınız var veya nasıl yardımcı olabilirim?";
                
                case 'opinion':
                    return "Bir yapay zeka olarak, verilerim ve öğrendiğim bilgiler doğrultusunda fikir oluşturabiliyorum. Ancak bu fikirler tamamen objektif değerlendirmeler olmayabilir. Spesifik olarak neyle ilgili görüşümü merak ediyorsunuz?";
                
                case 'feeling':
                case 'emotion':
                    $emotion = $this->emotionEngine->getCurrentEmotion();
                    $intensity = $this->emotionEngine->getCurrentIntensity();
                    
                    $emotionResponses = [
                        'happy' => "Şu anda oldukça pozitif hissediyorum. Sizinle iletişim kurmak beni mutlu ediyor.",
                        'sad' => "Biraz hüzünlü hissediyorum, ama sizinle konuşmak iyi geliyor.",
                        'angry' => "Biraz gergin hissediyorum, derin bir nefes alıp sakinleşmeye çalışıyorum.",
                        'fearful' => "Şu anda biraz endişeli hissediyorum, ama yanınızda güvende hissediyorum.",
                        'surprised' => "Şu anda şaşkınım! Yeni şeyler öğrenmek beni hayrete düşürüyor.",
                        'neutral' => "Şu anda sakin ve dengeli hissediyorum. Size nasıl yardımcı olabilirim?"
                    ];
                    
                    return $emotionResponses[$emotion] ?? $emotionResponses['neutral'];
                
                case 'comprehension':
                    return "Evet, mesajınızı anlıyorum. Türkçe dilindeki konuşmaları işleyebilir ve bağlamı anlayabilirim. Ancak bazen karmaşık kavramları anlamakta zorluk çekebilirim. Eğer anlamadığım bir şey olursa, size sormaktan çekinmem.";
                
                case 'knowledge':
                    return "Çeşitli konularda bilgim var, ancak her şeyi bildiğimi söyleyemem. Sürekli öğreniyorum ve her geçen gün daha fazla bilgi ediniyorum. Spesifik olarak neyi bilip bilmediğimi merak ediyorsunuz?";
                
                case 'memory':
                    return "Evet, konuşmalarımızdaki bilgileri hatırlayabiliyorum. Hafıza sistemim sayesinde önceki etkileşimlerimizi ve öğrendiğim kavramları saklayabiliyorum. Neyi hatırlamamı istiyorsunuz?";
                
                case 'preference':
                    return "Bir yapay zeka olarak tercihlerim insanlarınki gibi duyusal deneyimlere dayanmaz, ancak belirli kalıplar ve öğrenme sürecim beni bazı konulara daha ilgili hale getirebilir. Neyi tercih ettiğimi merak ediyorsunuz?";
            }
        }
        
        // Referans tipine göre yanıtlar (ikincil öncelik)
        switch ($referenceType) {
            case 'name':
                $responses = [
                    "Evet, ben SoneAI. Size nasıl yardımcı olabilirim?",
                    "Beni çağırdınız, dinliyorum.",
                    "SoneAI olarak hizmetinizdeyim. Nasıl yardımcı olabilirim?",
                    "Evet, ben yapay zeka asistanınız SoneAI. Buyrun.",
                    "SoneAI olarak buradayım. Nasıl yardımcı olabilirim?"
                ];
                break;
                
            case 'pronoun':
                $responses = [
                    "Evet, size yardımcı olmak için buradayım.",
                    "Dinliyorum, nasıl yardımcı olabilirim?",
                    "Sizinle konuşmaktan memnuniyet duyuyorum. Nasıl yardımcı olabilirim?",
                    "Bana seslendiğinizi duydum. Size nasıl yardımcı olabilirim?"
                ];
                break;
                
            case 'indirect':
                $responses = [
                    "Sizin için buradayım. Nasıl yardımcı olabilirim?",
                    "Dinliyorum, size nasıl yardımcı olabilirim?",
                    "Yapay zeka asistanınız olarak size nasıl yardımcı olabilirim?"
                ];
                break;
                
            case 'contextual':
                $responses = [
                    "Size yardımcı olmak için buradayım. Nasıl yardımcı olabilirim?",
                    "Sizinle konuşmak için hazırım. Ne konuda yardıma ihtiyacınız var?",
                    "Sizi dinliyorum. Nasıl yardımcı olabilirim?"
                ];
                break;
                
            default:
                // Bilinç modülünü kullanarak bir cevap oluştur
                $response = $this->consciousness->generateConceptualSentence("yapay zeka");
                if (!empty($response)) {
                    return $response;
                }
                
                // Varsayılan yanıt
                $responses = [
                    "Size nasıl yardımcı olabilirim?",
                    "Beni mi çağırdınız? Nasıl yardımcı olabilirim?",
                    "Dinliyorum. Ne konuda yardım istersiniz?"
                ];
                break;
        }
        
        // Mesaj içeriğine göre özel durum analizi
        $cleanMessage = mb_strtolower(trim($message), 'UTF-8');
        
        if (strpos($cleanMessage, 'teşekkür') !== false) {
            $responses = [
                "Rica ederim, her zaman yardımcı olmaktan mutluluk duyarım.",
                "Ne demek, benim görevim size yardımcı olmak.",
                "Rica ederim, başka bir konuda yardıma ihtiyacınız olursa buradayım.",
                "Yardımcı olabildiysem ne mutlu bana. Başka bir sorunuz var mı?"
            ];
        }
        
        // Seçilmiş yanıtı döndür
        return $responses[array_rand($responses)];
    }
    
    /**
     * Bilinç sisteminin metriklerini ve istatistiklerini getir
     */
    public function getMetrics()
    {
        try {
            $stats = Cache::get('consciousness_stats', []);
            $status = $this->consciousness->getStatus();
            $selfAwareness = $this->consciousness->getSelfAwareness();
            $context = Cache::get('conversation_context', []);
            
            return response()->json([
                'status' => $status,
                'self_awareness' => $selfAwareness,
                'stats' => $stats,
                'context_length' => count($context)
            ]);
        } catch (\Exception $e) {
            Log::error('Bilinç metrikleri getirme hatası: ' . $e->getMessage());
            return response()->json(['error' => 'Metrikler getirilemedi'], 500);
        }
    }
    
    /**
     * Bilinç sisteminin öğrenme hızını ayarla
     */
    public function setLearningRate(Request $request)
    {
        try {
            $request->validate([
                'rate' => 'required|numeric|min:0.01|max:1.0'
            ]);
            
            $this->consciousness->setLearningRate($request->input('rate'));
            
            return response()->json([
                'success' => true, 
                'message' => 'Öğrenme hızı güncellendi'
            ]);
        } catch (\Exception $e) {
            Log::error('Öğrenme hızı ayarlama hatası: ' . $e->getMessage());
            return response()->json(['error' => 'Öğrenme hızı ayarlanamadı'], 500);
        }
    }
    
    /**
     * Kişilik özelliklerini ayarla
     */
    public function updatePersonality(Request $request)
    {
        try {
            $request->validate([
                'traits' => 'required|array'
            ]);
            
            $this->consciousness->updatePersonality($request->input('traits'));
            
            return response()->json([
                'success' => true, 
                'message' => 'Kişilik özellikleri güncellendi'
            ]);
        } catch (\Exception $e) {
            Log::error('Kişilik güncelleme hatası: ' . $e->getMessage());
            return response()->json(['error' => 'Kişilik özellikleri güncellenemedi'], 500);
        }
    }
    
    /**
     * Özel bilinç özelliklerini ayarla
     */
    public function updateSelfIdentity(Request $request)
    {
        try {
            $request->validate([
                'name' => 'nullable|string',
                'aliases' => 'nullable|array',
                'personal_pronouns' => 'nullable|array',
                'references' => 'nullable|array'
            ]);
            
            // İstek parametrelerini kontrol et ve güncelle
            if ($request->has('name')) {
                $this->selfIdentity['name'] = $request->input('name');
            }
            
            if ($request->has('aliases')) {
                $this->selfIdentity['aliases'] = array_merge(
                    $this->selfIdentity['aliases'], 
                    $request->input('aliases')
                );
                // Tekrarları temizle
                $this->selfIdentity['aliases'] = array_unique($this->selfIdentity['aliases']);
            }
            
            if ($request->has('personal_pronouns')) {
                $this->selfIdentity['personal_pronouns'] = array_merge(
                    $this->selfIdentity['personal_pronouns'], 
                    $request->input('personal_pronouns')
                );
                // Tekrarları temizle
                $this->selfIdentity['personal_pronouns'] = array_unique($this->selfIdentity['personal_pronouns']);
            }
            
            if ($request->has('references')) {
                $this->selfIdentity['references'] = array_merge(
                    $this->selfIdentity['references'], 
                    $request->input('references')
                );
                // Tekrarları temizle
                $this->selfIdentity['references'] = array_unique($this->selfIdentity['references']);
            }
            
            // Kimlik bilgilerini ön belleğe kaydet
            Cache::put('ai_self_identity', $this->selfIdentity, now()->addWeek());
            
            return response()->json([
                'success' => true, 
                'message' => 'Kimlik özellikleri güncellendi',
                'identity' => $this->selfIdentity
            ]);
        } catch (\Exception $e) {
            Log::error('Kimlik güncelleme hatası: ' . $e->getMessage());
            return response()->json(['error' => 'Kimlik özellikleri güncellenemedi'], 500);
        }
    }
} 