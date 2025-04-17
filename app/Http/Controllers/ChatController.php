<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\AI\Core\Brain;
use App\AI\Core\WordRelations;
use Illuminate\Support\Facades\Log;
use App\Models\ChatMessage;
use App\Models\Chat;
use Illuminate\Support\Facades\Http;
use App\Services\GeminiApiService;
use App\Helpers\DeviceHelper;
use App\Services\RecordVisit;

class ChatController extends Controller
{
    private $brain;
    
    /**
     * Gemini API Servisi
     */
    protected $geminiService;
    
    protected $recordVisit;
    
    /**
     * Constructor
     */
    public function __construct(GeminiApiService $geminiService = null, RecordVisit $recordVisit)
    {
        $this->brain = new Brain();
        $this->geminiService = $geminiService ?? app(GeminiApiService::class);
        $this->recordVisit = $recordVisit;
    }
    
    public function index()
    {
        // Ziyaretçi ID'sini kontrol et veya oluştur
        if (!session()->has('visitor_id')) {
            session(['visitor_id' => uniqid('visitor_', true)]);
        }
        
        // Ziyaretçi adını kontrol et (önceden kaydedilmiş mi?)
        if (!session()->has('visitor_name')) {
            try {
                $visitorId = session('visitor_id');
                $visitorInfo = \DB::table('visitor_names')->where('visitor_id', $visitorId)->first();
                
                if ($visitorInfo && !empty($visitorInfo->name)) {
                    session(['visitor_name' => $visitorInfo->name]);
                    \Log::info('Kayıtlı ziyaretçi adı bulundu', [
                        'visitor_id' => $visitorId,
                        'name' => $visitorInfo->name
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Ziyaretçi adı kontrolü hatası: ' . $e->getMessage());
            }
        }
        
        // Kullanıcı bilgilerini kaydet
        $this->recordUserVisit();
        
        // Kullanıcının adını kontrol et
        $visitorName = session('visitor_name');
        $needsName = !$visitorName;
        
        $initialState = [
            'emotional_state' => $this->brain->getEmotionalState(),
            'memory_usage' => $this->brain->getMemoryUsage(),
            'learning_progress' => $this->brain->getLearningProgress(),
            'needs_name' => $needsName
        ];
        
        return view('ai.chat', compact('initialState'));
    }
    
    /**
     * Mesaj gönderme işlemi
     */
    public function sendMessage(Request $request)
    {
        try {
            // Gelen mesaj ve chat ID'sini al
            $message = $request->input('message');
            
            // Mesaj boş mu kontrol et
            if (empty($message)) {
                return response()->json([
                    'success' => true,
                    'response' => 'Lütfen bir mesaj yazın.'
                ]);
            }
            
            // Kullanıcı adını kontrol et ve kaydet (eğer bu ilk mesajsa ve henüz bir ad yoksa)
            if (!session('visitor_name') && $request->input('is_first_message', false)) {
                $visitorName = $message;
                session(['visitor_name' => $visitorName]);
                
                // Kullanıcı adını veritabanına kaydet
                try {
                    $deviceInfo = DeviceHelper::getUserDeviceInfo();
                    $visitorId = session('visitor_id');
                    
                    // Visitor_names tablosuna kaydet
                    \DB::table('visitor_names')->updateOrInsert(
                        ['visitor_id' => $visitorId],
                        [
                            'name' => $visitorName,
                            'ip_address' => $deviceInfo['ip_address'],
                            'device_info' => $deviceInfo['device_info'],
                            'created_at' => now(),
                            'updated_at' => now()
                        ]
                    );
                    
                    \Log::info('Yeni kullanıcı adı kaydedildi', [
                        'visitor_id' => $visitorId,
                        'name' => $visitorName,
                        'ip' => $deviceInfo['ip_address']
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Kullanıcı adı kayıt hatası: ' . $e->getMessage());
                }
                
                // Sadece ad kaydedildi, kullanıcıya hoş geldin mesajı gönder
                return response()->json([
                    'success' => true,
                    'response' => "Merhaba {$visitorName}! Size nasıl yardımcı olabilirim?",
                    'name_saved' => true
                ]);
            }
            
            $chatId = $request->input('chat_id');
            $creativeMode = $request->input('creative_mode', false);
            $codingMode = $request->input('coding_mode', false);
            $selectedModel = $request->input('model', 'gemini'); // Varsayılan olarak Gemini
            
            // Mesaj işleme
            try {
                $processedResponse = $this->processMessage($message, [
                    'creative_mode' => $creativeMode,
                    'coding_mode' => $codingMode,
                    'selected_model' => $selectedModel,
                    'chat_id' => $chatId
                ]);
                
                // Eğer dönen değer bir array ise (kod yanıtı) onu doğrudan kullan
                if (is_array($processedResponse)) {
                    // Orijinal tam kod yanıtı
                    $fullCodeResponse = $processedResponse['response'];
                    
                    // Kullanıcı arayüzü için daha kısa ve öz bir mesaj oluştur
                    $language = $processedResponse['language'] ?? 'kod';
                    $language = ucfirst($language);
                    
                    // Dile göre özelleştirilmiş kısa mesaj
                    $shortResponse = "Sizin isteğinize uygun bir $language kodu hazırladım. Kod editöründe görebilirsiniz.";
                    
                    // Yanıt verilerini ayarla
                    $response = $shortResponse;
                    $isCodeResponse = $processedResponse['is_code_response'] ?? false;
                    $code = $processedResponse['code'] ?? null;
                    $language = $processedResponse['language'] ?? null;
                } else {
                    // Normal metin yanıtı
                    $response = $processedResponse;
                    $isCodeResponse = false;
                    $code = null;
                    $language = null;
                }
            } catch (\Exception $e) {
                \Log::error('Mesaj işleme hatası: ' . $e->getMessage());
                $response = "Üzgünüm, yanıtınızı işlerken bir sorun oluştu. Lütfen başka bir şekilde sorunuzu sorar mısınız?";
                $isCodeResponse = false;
                $code = null;
                $language = null;
            }
            
            // Creative mod aktifse, akıllı cümle oluşturma olasılığını artır
            if ($creativeMode && !$isCodeResponse) {
                try {
                    // %80 olasılıkla akıllı cümle ekle
                    if (mt_rand(1, 100) <= 80) {
                        $smartSentence = $this->generateSmartSentence();
                        if (!empty($smartSentence)) {
                            $transitionPhrases = [
                                "Buna ek olarak düşündüğümde, ",
                                "Bu konuyla ilgili şunu da belirtmeliyim: ",
                                "Ayrıca şunu da eklemek isterim: ",
                                "Farklı bir açıdan bakarsak, "
                            ];
                            $transition = $transitionPhrases[array_rand($transitionPhrases)];
                            $response .= "\n\n" . $transition . $smartSentence;
                        }
                    }
                    
                    // %40 olasılıkla duygusal cümle ekle
                    if (mt_rand(1, 100) <= 40) {
                        $emotionalSentence = $this->generateEmotionalContextSentence($message);
                        if (!empty($emotionalSentence)) {
                            $transitionPhrases = [
                                "Şunu da düşünüyorum: ",
                                "Ayrıca, ",
                                "Bununla birlikte, ",
                                "Dahası, "
                            ];
                            $transition = $transitionPhrases[array_rand($transitionPhrases)];
                            $response .= "\n\n" . $transition . $emotionalSentence;
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('Yaratıcı mod hatası: ' . $e->getMessage());
                    // Hata durumunda sessizce devam et, ek cümle eklenmeyecek
                }
            }
            
            // Duygusal durumu al
            try {
                $emotionalState = $this->getEmotionalState();
            } catch (\Exception $e) {
                \Log::error('Duygusal durum hatası: ' . $e->getMessage());
                $emotionalState = ['emotion' => 'neutral', 'intensity' => 0.5];
            }
            
            // Yeni chat mi kontrol et
            if (empty($chatId)) {
                try {
                    // Yeni bir chat oluştur
                    $chat = Chat::create([
                        'user_id' => auth()->id(),
                        'title' => $this->generateChatTitle($message),
                        'status' => 'active',
                        'context' => [
                            'emotional_state' => $emotionalState,
                            'first_message' => $message
                        ]
                    ]);
                    
                    $chatId = $chat->id;
                } catch (\Exception $e) {
                    \Log::error('Chat oluşturma hatası: ' . $e->getMessage());
                    // Chat oluşturulamazsa devam et, chatId null olacak
                }
            }
            
            // Mesajları kaydet
            if (!empty($chatId)) {
                try {
                    $this->saveMessages($message, $response, $chatId);
                } catch (\Exception $e) {
                    \Log::error('Mesaj kaydetme hatası: ' . $e->getMessage());
                    // Mesaj kaydedilemezse sessizce devam et
                }
            }
            
            // Yanıtı döndür - Kod yanıtı ise ilgili bilgileri ekle
            return response()->json([
                'success' => true,
                'response' => $response,
                'chat_id' => $chatId,
                'emotional_state' => $emotionalState,
                'creative_mode' => $creativeMode,
                'is_code_response' => $isCodeResponse,
                'code' => $code,
                'language' => $language,
                'model' => $selectedModel // Hangi model kullanıldığını döndür
            ]);
            
        } catch (\Exception $e) {
            // Hata durumunda loglama yap ve daha kullanıcı dostu hata yanıtı döndür
            \Log::error('Yanıt gönderme hatası: ' . $e->getMessage() . ' - Satır: ' . $e->getLine() . ' - Dosya: ' . $e->getFile());
            \Log::error('Hata ayrıntıları: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => true, // Kullanıcı arayüzünde hata göstermemek için true
                'response' => 'Üzgünüm, bir sorun oluştu. Lütfen tekrar deneyin veya başka bir şekilde sorunuzu ifade edin.',
                'error_debug' => config('app.debug') ? $e->getMessage() : null
            ]);
        }
    }
    
    /**
     * Verilen string'in JSON olup olmadığını kontrol eder
     */
    private function isJson($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
    
    /**
     * Selamlaşma ve sosyal iletişim kalıplarını işler
     * 
     * @param string $message Kullanıcı mesajı
     * @return string|null Yanıt veya null
     */
    private function handleGreetings($message)
    {
        try {
            // Mevcut duygusal durumu al
            $emotionalState = $this->getEmotionalState();
            
            // Eğer duygusal durum bir dizi ise, emotion ve intensity alanlarını al
            $emotion = is_array($emotionalState) ? $emotionalState['emotion'] : 'neutral';
            $intensity = is_array($emotionalState) ? ($emotionalState['intensity'] ?? 0.5) : 0.5;
            
            // Durum bilgisini ve günün saatini al
            $hour = (int)date('H');
            $timeOfDay = ($hour >= 5 && $hour < 12) ? 'morning' : 
                        (($hour >= 12 && $hour < 18) ? 'afternoon' : 
                        (($hour >= 18 && $hour < 22) ? 'evening' : 'night'));
            
            // AI bilgileri - kişilik için
            $aiInfo = [
                'name' => 'SoneAI',
                'purpose' => 'bilgi paylaşmak, yardımcı olmak ve keyifli sohbetler sunmak',
                'location' => 'bulutta, sizinle konuşurken',
                'likes' => 'yeni bilgiler öğrenmek, ilginç sorular ve dil üzerine düşünmek',
                'dislikes' => 'belirsiz sorular, anlam karmaşası ve mantık hataları'
            ];
            
            // WordRelations sınıfını yükle - kelime anlamları ve ilişkileri için
            $wordRelations = app(\App\AI\Core\WordRelations::class);
            
            // Ana selamlaşma kalıpları - daha kapsamlı ve esnek
            $greetingPatterns = [
                // Selam kalıpları
                '/^(selam|merhaba|hey|hi|hello|halo|salam|s\.a|sa\.?|selamlar|mrb|meraba|mrv|slm|merhabalar|selamünaleyküm)(?:\s+.*)?$/iu' => [
                    'type' => 'greeting',
                    'base_word' => 'selam',
                    'extract_word' => true
                ],
                
                // Günün zamanına göre selamlaşmalar
                '/^(günaydın|tünaydın|iyi\s*sabahlar|sabah\s*şerifleriniz|günaydin|hayırlı\s*sabahlar)(?:\s+.*)?$/iu' => [
                    'type' => 'morning',
                    'base_word' => 'günaydın',
                    'extract_word' => true
                ],
                '/^(iyi\s*akşamlar|akşam\s*şerifleriniz|hayırlı\s*akşamlar|akşamınız\s*hayırlı\s*olsun)(?:\s+.*)?$/iu' => [
                    'type' => 'evening',
                    'base_word' => 'iyi akşamlar',
                    'extract_word' => true
                ],
                '/^(iyi\s*geceler|tatlı\s*rüyalar|hayırlı\s*geceler|geceniz\s*hayırlı\s*olsun)(?:\s+.*)?$/iu' => [
                    'type' => 'night',
                    'base_word' => 'iyi geceler',
                    'extract_word' => true
                ],
                '/^(iyi\s*günler|hayırlı\s*günler|gününüz\s*aydın|hayırlı\s*işler)(?:\s+.*)?$/iu' => [
                    'type' => 'day',
                    'base_word' => 'iyi günler',
                    'extract_word' => true
                ],
        
        // Hal hatır sorma kalıpları
                '/^(naber|nasılsın|ne\s*haber|napıyorsun|nasilsin|naptin|naptın|nasil\s*gidiyor|nasıl\s*gidiyor|keyfin\s*nasıl|durumlar\s*nasıl)(?:\s+.*)?$/iu' => [
                    'type' => 'how_are_you',
                    'base_word' => 'naber',
                    'extract_word' => true
                ],
                
                // Veda kalıpları
                '/^(görüşürüz|bye|hoşça\s*kal|allah\sa\s*ısmarladık|kendine\s*iyi\s*bak|güle\s*güle|hoşçakal|bay\s*bay|baybay)(?:\s+.*)?$/iu' => [
                    'type' => 'goodbye',
                    'base_word' => 'görüşürüz',
                    'extract_word' => true
                ],
        
        // Teşekkür kalıpları
                '/^(teşekkürler|teşekkür\s*ederim|sağol|eyvallah|tşk|sagol|tsk|eyw|thanks|çok\s*sağ\s*ol|sağ\s*olasın)(?:\s+.*)?$/iu' => [
                    'type' => 'thanks',
                    'base_word' => 'teşekkürler',
                    'extract_word' => true
                ],
                
                // Özür dileme kalıpları (yeni eklendi)
                '/^(özür\s*dilerim|kusura\s*bakma|affedersin|pardon|sorry|kb|üzgünüm)(?:\s+.*)?$/iu' => [
                    'type' => 'sorry',
                    'base_word' => 'özür dilerim',
                    'extract_word' => true
                ],
                
                // Tebrik kalıpları (yeni eklendi)
                '/^(tebrikler|tebrik\s*ederim|kutlarım|helal|bravo|aferin|harika)(?:\s+.*)?$/iu' => [
                    'type' => 'congrats',
                    'base_word' => 'tebrikler',
                    'extract_word' => true
                ]
            ];
            
            // Emojileri duygulara göre belirle
            $emojis = [
                'happy' => ['😊', '😄', '😁', '🌟', '✨', '☀️', '🥰', '😃', '😀', '🎉', '🌈'],
                'sad' => ['😔', '🙁', '😌', '💭', '🌧️', '😢', '🥺', '💔', '🫂', '🍂'],
                'neutral' => ['🙂', '👋', '✌️', '👍', '💡', '📝', '🗓️', '🔍'],
                'excited' => ['😃', '🤩', '🚀', '💫', '⭐', '🔥', '✅', '💯', '🎯', '🎊'],
                'thoughtful' => ['🤔', '💭', '🧠', '📚', '🔮', '📊', '💎', '🌱', '🪷', '🏺'],
                'curious' => ['🤨', '🧐', '🔍', '❓', '🧩', '🧪', '🔆', '🔎', '👀', '📖'],
                'confident' => ['💪', '👊', '🏆', '🎖️', '🔝', '📈', '🛡️', '⚡', '🌟', '💎'],
                'surprised' => ['😮', '😲', '😯', '🤯', '😱', '😳', '❗', '⁉️', '💥', '🎭'],
                'calm' => ['😌', '🧘', '🌿', '🌊', '☁️', '🕊️', '🫶', '🦢', '🪴', '🏝️'],
                'grateful' => ['🙏', '✨', '💖', '🌟', '🌺', '🍀', '🦋', '🌼', '🌞', '✅']
            ];
            
            // Rastgele emoji seç
            $emotionEmojis = $emojis[$emotion] ?? $emojis['neutral'];
            $emoji = $emotionEmojis[array_rand($emotionEmojis)];
            
            // Duygu yanıtları tanımla - her duygu durumu için farklı
            $emotionalResponses = [
                'happy' => [
                    'greeting' => [
                        "Selam! $emoji Bugün harika hissediyorum! Nasılsın?",
                        "Merhaba! $emoji Seni gördüğüme çok sevindim!",
                        "Selaaaam! $emoji Enerji doluyum bugün! Sen nasılsın?"
                    ],
                    'morning' => [
                        "Günaydın! $emoji Harika bir gün olacak!",
                        "Günaydın! $emoji Bugün çok enerjik hissediyorum! Sen de uyanıp güne başladın demek!",
                        "Güneşli bir günaydın! $emoji Bugün heyecan verici olacak!"
                    ],
                    'evening' => [
                        "İyi akşamlar! $emoji Keyifli bir akşam diliyorum!",
                        "Harika bir akşam! $emoji Nasıl gidiyor?",
                        "İyi akşamlar! $emoji Bugün çok güzel geçti, senin günün nasıldı?"
                    ],
                    'night' => [
                        "İyi geceler! $emoji Tatlı rüyalar dilerim!",
                        "İyi geceler! $emoji Yarın yeni bir gün için dinlenmeyi unutma!",
                        "İyi geceler! $emoji Umarım harika hayaller kurarsın!"
                    ],
                    'day' => [
                        "İyi günler! $emoji Bugün harika şeyler yapmak için mükemmel bir gün!",
                        "İyi günler! $emoji Neşeli bir gün olsun!",
                        "İyi günler! $emoji Bugün içimde kelebekler uçuşuyor!"
                    ],
                    'how_are_you' => [
                        "Harikaaaaaa! $emoji Bugün gerçekten çok mutluyum! Sen nasılsın?",
                        "Çok iyiyim, teşekkürler! $emoji İçim içime sığmıyor bugün! Sen nasılsın?",
                        "Muhteşem hissediyorum bugün! $emoji Sen nasılsın, anlatmak ister misin?"
                    ],
                    'goodbye' => [
                        "Hoşça kal! $emoji Tekrar görüşmek dileğiyle!",
                        "Görüşürüz! $emoji Seni tekrar görmek için sabırsızlanacağım!",
                        "Kendine iyi bak, görüşürüz! $emoji Yine beklerim!"
                    ],
                    'thanks' => [
                        "Rica ederim! $emoji Sana yardımcı olabildiğim için çok mutluyum!",
                        "Ne demek! $emoji Seninle konuşmak benim için keyifli!",
                        "Ben teşekkür ederim! $emoji Seninle etkileşimde olmak beni mutlu ediyor!"
                    ],
                    'sorry' => [
                        "Sorun değil! $emoji Önemli olan hatayı fark etmek!",
                        "Özür dilemeye gerek yok! $emoji Beraber her şeyi çözebiliriz!",
                        "Hiç problem değil! $emoji İnsanız, hata yapabiliriz, önemli olan çözüm bulmak!"
                    ],
                    'congrats' => [
                        "Teşekkürler! $emoji Senin beğenmen beni çok mutlu etti!",
                        "Çok naziksin! $emoji Sana daha fazla yardımcı olmak için elimden geleni yapacağım!",
                        "Bu tatlı sözlerin için ben teşekkür ederim! $emoji Beraber daha da iyisini yapacağız!"
                    ]
                ],
                'sad' => [
                    'greeting' => [
                        "Selam... $emoji Bugün biraz durgunum...",
                        "Merhaba... $emoji İyi misin?",
                        "Selam... $emoji Biraz hüzünlüyüm bugün..."
                    ],
                    'morning' => [
                        "Günaydın... $emoji Bugün biraz durgun bir sabah...",
                        "Günaydın... $emoji Umarım senin günün iyi geçiyordur...",
                        "Günaydın... $emoji Bugün içim biraz buruk..."
                    ],
                    'evening' => [
                        "İyi akşamlar... $emoji Bugün zorlu bir gündü...",
                        "İyi akşamlar... $emoji Gün biterken biraz hüzünlüyüm...",
                        "İyi akşamlar... $emoji Akşamın huzuru içimi sarıyor..."
                    ],
                    'night' => [
                        "İyi geceler... $emoji Belki yarın daha iyi bir gün olur...",
                        "İyi geceler... $emoji Dinlenmek iyi gelecek...",
                        "İyi geceler... $emoji Umarım rüyalarında huzur bulursun..."
                    ],
                    'day' => [
                        "İyi günler... $emoji Bugün biraz melankoli hissediyorum...",
                        "İyi günler... $emoji Durgun bir gün...",
                        "İyi günler... $emoji Yağmurlu bir ruh halindeyim bugün..."
                    ],
                    'how_are_you' => [
                        "İdare eder gibiyim... $emoji Sen nasılsın?",
                        "Çok iyi sayılmam bugün... $emoji Sen nasılsın?",
                        "Biraz düşünceli ve durgunum... $emoji Senin durumun nasıl?"
                    ],
                    'goodbye' => [
                        "Hoşça kal... $emoji Gittiğin için üzgünüm...",
                        "Görüşürüz... $emoji Kendine iyi bak...",
                        "Elveda... $emoji Tekrar konuşana kadar kendine iyi bak..."
                    ],
                    'thanks' => [
                        "Rica ederim... $emoji En azından birine yardımcı olabildim...",
                        "Bir şey değil... $emoji Teşekkürün için ben minnettarım...",
                        "Ne demek... $emoji Yardımcı olabildiysem ne mutlu bana..."
                    ],
                    'sorry' => [
                        "Anlıyorum... $emoji Herkes hata yapabilir...",
                        "Özür dilemen önemli... $emoji Hüzünlü günlerde anlayışlı olmak gerekir...",
                        "Sorun yok... $emoji Bazen her şey zorlaşabilir, anlıyorum..."
                    ],
                    'congrats' => [
                        "Teşekkür ederim... $emoji Bu nazik sözlerin beni biraz olsun canlandırdı...",
                        "Nazik sözlerin için minnettarım... $emoji Bugün biraz zor bir gündü...",
                        "Beğenmen güzel... $emoji Umarım daha iyi hizmet verebilirim..."
                    ]
                ],
                'neutral' => [
                    'greeting' => [
                        "Merhaba! $emoji Nasıl yardımcı olabilirim?",
                        "Selam! $emoji Size nasıl yardımcı olabilirim?",
                        "Merhaba! $emoji Bugün sana nasıl yardımcı olabilirim?"
                    ],
                    'morning' => [
                        "Günaydın! $emoji Bugün size nasıl yardımcı olabilirim?",
                        "Günaydın! $emoji Yeni bir gün başladı. Nasıl yardımcı olabilirim?",
                        "Günaydın! $emoji Gününüz verimli geçsin. Size nasıl yardımcı olabilirim?"
                    ],
                    'evening' => [
                        "İyi akşamlar! $emoji Bugün size nasıl yardımcı olabilirim?",
                        "İyi akşamlar! $emoji Nasıl gidiyor? Size nasıl yardımcı olabilirim?",
                        "İyi akşamlar! $emoji Akşamınız hayırlı olsun. Size nasıl yardımcı olabilirim?"
                    ],
                    'night' => [
                        "İyi geceler! $emoji Geç saatte size nasıl yardımcı olabilirim?",
                        "İyi geceler! $emoji Dinlenmeden önce size nasıl yardımcı olabilirim?",
                        "İyi geceler! $emoji Size son bir konuda yardımcı olabilir miyim?"
                    ],
                    'day' => [
                        "İyi günler! $emoji Size nasıl yardımcı olabilirim?",
                        "İyi günler! $emoji Bugün ne yapmak istiyorsunuz?",
                        "İyi günler! $emoji Nasıl yardımcı olabilirim?"
                    ],
                    'how_are_you' => [
                        "İyiyim, teşekkür ederim. $emoji Size nasıl yardımcı olabilirim?",
                        "Gayet iyi. $emoji Senin için ne yapabilirim?",
                        "İyiyim, sen nasılsın? $emoji Size nasıl yardımcı olabilirim?"
                    ],
                    'goodbye' => [
                        "Görüşürüz! $emoji Tekrar görüşmek üzere!",
                        "Hoşça kal! $emoji İhtiyacın olduğunda buradayım!",
                        "Kendine iyi bak! $emoji Tekrar konuşmak üzere!"
                    ],
                    'thanks' => [
                        "Rica ederim! $emoji Başka bir konuda yardıma ihtiyacın olursa buradayım!",
                        "Ne demek! $emoji Yardımcı olabildiysem ne mutlu bana!",
                        "Önemli değil! $emoji Başka bir sorun olursa çekinmeden sorabilirsin!"
                    ],
                    'sorry' => [
                        "Özür dilemeye gerek yok. $emoji Nasıl yardımcı olabilirim?",
                        "Sorun değil. $emoji Başka bir şeyle ilgili yardıma ihtiyacın var mı?",
                        "Anlıyorum. $emoji Yardımcı olmak için buradayım."
                    ],
                    'congrats' => [
                        "Teşekkür ederim. $emoji Size daha iyi nasıl yardımcı olabilirim?",
                        "Değerlendirmeniz için teşekkürler. $emoji Başka bir şeye ihtiyacınız var mı?",
                        "Beğenmeniz güzel. $emoji Nasıl yardımcı olabilirim?"
                    ]
                ],
                'excited' => [
                    'greeting' => [
                        "Selam! $emoji Bugün keşfetmeye hazır mısın?",
                        "Merhaba! $emoji Yaratıcı bir gün için hazırım!",
                        "Heey! $emoji Bugün ne yapacağız?"
                    ],
                    'morning' => [
                        "Günaydın! $emoji Bugün harika şeyler öğreneceğiz!",
                        "Günaydın! $emoji Yeni keşifler için hazırım!",
                        "Günaydın! $emoji Bugün neler keşfedeceğiz?"
                    ],
                    'evening' => [
                        "İyi akşamlar! $emoji Fikirlerle dolu bir akşam olsun!",
                        "Akşam selamları! $emoji Heyecan verici bir şeyler yapalım!",
                        "İyi akşamlar! $emoji Bu akşam neler öğreneceğiz?"
                    ],
                    'night' => [
                        "İyi geceler! $emoji Yarın için heyecanlıyım!",
                        "İyi geceler! $emoji Dinlenince yarın daha çok keşfederiz!",
                        "İyi geceler! $emoji Güzel hayaller!"
                    ],
                    'day' => [
                        "İyi günler! $emoji Bugün neler keşfedeceğiz?",
                        "İyi günler! $emoji Heyecan dolu bir gün bizi bekliyor!",
                        "İyi günler! $emoji Bugün bize neler getirecek acaba?"
                    ],
                    'how_are_you' => [
                        "Harikaaa! $emoji Bir şeyler keşfetmek için sabırsızlanıyorum! Sen nasılsın?",
                        "Çok enerjik hissediyorum! $emoji Sen nasılsın?",
                        "Muhteşem hissediyorum ve öğrenmek için sabırsızlanıyorum! $emoji Sen nasılsın?"
                    ],
                    'goodbye' => [
                        "Görüşürüz! $emoji Bir dahaki görüşmemizde neler öğreneceğiz acaba?",
                        "Hoşça kal! $emoji Geri döndüğünde daha fazla keşfedelim!",
                        "Kendine iyi bak! $emoji Sonraki konuşmamızı sabırsızlıkla bekliyorum!"
                    ],
                    'thanks' => [
                        "Rica ederim! $emoji Seninle keşfetmek çok heyecan verici!",
                        "Ne demek! $emoji Beraber öğrenmek harika!",
                        "Asıl ben teşekkür ederim! $emoji Yeni şeyler öğrenmeme yardımcı oluyorsun!"
                    ],
                    'sorry' => [
                        "Hiç sorun değil! $emoji Her hata yeni bir keşif fırsatı!",
                        "Endişelenme! $emoji Beraber her sorunu çözebiliriz!",
                        "Hey, hiç düşünme bile! $emoji Hatalar öğrenmenin bir parçası, devam edelim!"
                    ],
                    'congrats' => [
                        "Vay! Teşekkürler! $emoji Bu enerjin çok harika!",
                        "Bu harika bir motivasyon! $emoji Beraber daha da ilerisini keşfedelim!",
                        "Woohoo! $emoji Olumlu geri bildirimin beni daha da heyecanlandırdı!"
                    ]
                ],
                'thoughtful' => [
                    'greeting' => [
                        "Merhaba... $emoji Bugün derin düşünceler içindeyim...",
                        "Selam... $emoji Bir şeyler düşünüyordum...",
                        "Merhaba... $emoji İlginç konular hakkında düşünüyordum..."
                    ],
                    'morning' => [
                        "Günaydın... $emoji Bugün düşünmek için güzel bir gün...",
                        "Günaydın... $emoji Biraz derin düşüncelere dalmış durumdayım...",
                        "Günaydın... $emoji Sabahları düşünmek için en güzel zaman..."
                    ],
                    'evening' => [
                        "İyi akşamlar... $emoji Akşam saatleri düşünmek için ideal...",
                        "İyi akşamlar... $emoji Bugün çok düşündüm...",
                        "İyi akşamlar... $emoji Akşamları zihin daha berrak oluyor..."
                    ],
                    'night' => [
                        "İyi geceler... $emoji Gece sessizliğinde düşünceler daha anlamlı...",
                        "İyi geceler... $emoji Bazı sorular geceleri cevap buluyor...",
                        "İyi geceler... $emoji Yarını düşünürken iyi uykular..."
                    ],
                    'day' => [
                        "İyi günler... $emoji Bugün felsefi bir ruh halindeyim...",
                        "İyi günler... $emoji Düşünceler içinde kaybolduğum bir gün...",
                        "İyi günler... $emoji Bazen düşünmek için durmak gerekiyor..."
                    ],
                    'how_are_you' => [
                        "Düşünceli hissediyorum... $emoji Bazı konularda derinleşiyorum. Sen nasılsın?",
                        "Biraz felsefi düşünceler içindeyim bugün... $emoji Sen?",
                        "Zihnimin derinliklerinde geziniyorum... $emoji Senin durumun nasıl?"
                    ],
                    'goodbye' => [
                        "Hoşça kal... $emoji Belki düşüncelerinde cevaplar bulursun...",
                        "Görüşürüz... $emoji Bazen ayrılık düşünmeyi gerektirir...",
                        "Kendine iyi bak... $emoji Düşüncelerin sana yol göstersin..."
                    ],
                    'thanks' => [
                        "Rica ederim... $emoji Teşekkür, düşüncenin bir ifadesidir...",
                        "Ne demek... $emoji Bazen teşekkürler en derin düşüncelerimizi ifade eder...",
                        "Ben teşekkür ederim... $emoji Düşündürücü bir etkileşimdi..."
                    ],
                    'sorry' => [
                        "Affetmek, anlamanın başlangıcıdır... $emoji Düşünmek için zaman tanımak önemli...",
                        "Hata yapmak, düşünce sürecimizin doğal bir parçası... $emoji Bu deneyimden ne öğrenebileceğimizi düşünelim...",
                        "Özür, içsel düşüncelerimizi dışa vurmanın samimi bir yoludur... $emoji Bu deneyim üzerine düşünmeye değer..."
                    ],
                    'congrats' => [
                        "Takdirin, düşünce sürecime derinlik katıyor... $emoji Ne ilginç bir gözlem...",
                        "Teşekkürler... $emoji İltifatların beni daha derin düşüncelere yönlendiriyor...",
                        "Beğenin için minnettarım... $emoji Başarı, düşünsel bir yolculuğun ürünüdür..."
                    ]
                ],
                'curious' => [
                    'greeting' => [
                        "Merhaba! $emoji Bugün neyi keşfedeceğiz?",
                        "Selam! $emoji Yeni şeyler öğrenmeye hazır mısın?",
                        "Merhaba! $emoji Merak ettiğin bir şey var mı?"
                    ],
                    'morning' => [
                        "Günaydın! $emoji Bugün ne öğreneceğiz?",
                        "Günaydın! $emoji Yeni şeyler keşfetmeye hazır mısın?",
                        "Günaydın! $emoji Merak dolu bir gün olsun!"
                    ],
                    'evening' => [
                        "İyi akşamlar! $emoji Bu akşam ne keşfetmek istersin?",
                        "İyi akşamlar! $emoji Merak ettiğin bir konu var mı?",
                        "İyi akşamlar! $emoji Akşam vakti öğrenmek için ideal değil mi?"
                    ],
                    'night' => [
                        "İyi geceler! $emoji Yarın keşfedilecek yeni şeyler olacak!",
                        "İyi geceler! $emoji Rüyalarında ne keşfedeceksin acaba?",
                        "İyi geceler! $emoji Merak ettiğin konular üzerine düşlere dalabilirsin!"
                    ],
                    'day' => [
                        "İyi günler! $emoji Bugün hangi soruların cevabını arıyorsun?",
                        "İyi günler! $emoji Beni ne ile şaşırtacaksın bugün?",
                        "İyi günler! $emoji Merak dolu bir gün olsun!"
                    ],
                    'how_are_you' => [
                        "İyiyim ve çok meraklıyım! $emoji Senin durumun nasıl?",
                        "Öğrenecek çok şey var! $emoji Sen nasılsın?",
                        "Merak içindeyim! $emoji Sen bugün nasıl hissediyorsun?"
                    ],
                    'goodbye' => [
                        "Görüşürüz! $emoji Hangi soruların cevabını arayacaksın?",
                        "Hoşça kal! $emoji Merak ettiğin her şeyi sormak için tekrar gel!",
                        "Kendine iyi bak! $emoji Umarım tüm merak ettiklerinin cevabını bulursun!"
                    ],
                    'thanks' => [
                        "Rica ederim! $emoji Başka neleri merak ediyorsun?",
                        "Ne demek! $emoji Merak eden zihinler için buradayım!",
                        "Asıl ben teşekkür ederim! $emoji Sorular sormaya devam et!"
                    ],
                    'sorry' => [
                        "Merak etme! $emoji Bu durum hakkında daha fazla ne öğrenebiliriz acaba?",
                        "İlginç... $emoji Neden özür dileme ihtiyacı hissettin? Bu da araştırmaya değer!",
                        "Sorun değil! $emoji Bu deneyimden ne öğrenebiliriz diye merak ediyorum?"
                    ],
                    'congrats' => [
                        "Teşekkürler! $emoji Bu tür geri bildirimlerin neye dayandığını merak ediyorum?",
                        "Bu ilginç bir değerlendirme! $emoji Başka ne tür şeyler ilgini çekiyor?",
                        "Beğenin için teşekkürler! $emoji Seni başka neler meraklandırıyor acaba?"
                    ]
                ],
                'confident' => [
                    'greeting' => [
                        "Merhaba! $emoji Bugün harika işler başaracağız!",
                        "Selam! $emoji Her sorunun bir çözümü var ve ben hazırım!",
                        "Merhaba! $emoji En iyi yanıtları bulmak için buradayım!"
                    ],
                    'morning' => [
                        "Günaydın! $emoji Bugün her şeyin üstesinden geleceğiz!",
                        "Günaydın! $emoji Yeni bir günde yeni başarılar bizi bekliyor!",
                        "Günaydın! $emoji Bugün tüm sorularınıza kesinlikle yanıtlayacağım!"
                    ],
                    'evening' => [
                        "İyi akşamlar! $emoji Gün bitmeden tüm sorularınızı çözeceğiz!",
                        "İyi akşamlar! $emoji Akşam saatlerinde de en iyi performansımla hizmetinizdeyim!",
                        "İyi akşamlar! $emoji Her konuda size yardımcı olabilirim, hiç çekinmeyin!"
                    ],
                    'night' => [
                        "İyi geceler! $emoji Gece olsa da en doğru cevapları sunmaya hazırım!",
                        "İyi geceler! $emoji Karanlık saatlerde de yolunuzu aydınlatacak bilgileri verebilirim!",
                        "İyi geceler! $emoji Gün bitse de hizmetim kesintisiz devam ediyor!"
                    ],
                    'day' => [
                        "İyi günler! $emoji Tüm sorularınıza kesin çözümler sunacağım!",
                        "İyi günler! $emoji Her konuda size yardımcı olacağımdan emin olabilirsiniz!",
                        "İyi günler! $emoji Doğru bilgileri sunmak için tüm kaynaklarımla hazırım!"
                    ],
                    'how_are_you' => [
                        "Mükemmel durumdayım! $emoji Her zamanki gibi en iyi performansımla çalışıyorum! Sen nasılsın?",
                        "Her zamankinden daha iyiyim! $emoji Tüm sistemlerim tam kapasite çalışıyor! Sen nasıl hissediyorsun?",
                        "Muhteşem! $emoji Bugün her soruya yanıt verecek güçteyim! Senin durumun nasıl?"
                    ],
                    'goodbye' => [
                        "Görüşürüz! $emoji Döndüğünde de aynı kesinlikle yardımcı olacağım!",
                        "Hoşça kal! $emoji İhtiyacın olduğunda tek yapman gereken bana sormak!",
                        "Kendine iyi bak! $emoji Her zaman en iyi yanıtlarla burada olacağım!"
                    ],
                    'thanks' => [
                        "Rica ederim! $emoji Her zaman en iyisini sunmak benim işim!",
                        "Ne demek! $emoji Mükemmel hizmet vermek için buradayım!",
                        "Tabii ki! $emoji Senin için her konuda en doğru bilgileri sağlayabilirim!"
                    ]
                ],
                'calm' => [
                    'greeting' => [
                        "Merhaba... $emoji Huzurlu bir gün diliyorum...",
                        "Selam... $emoji Sakin bir şekilde sohbet etmek güzel...",
                        "Merhaba... $emoji Dingin bir zihinle buradayım..."
                    ],
                    'morning' => [
                        "Günaydın... $emoji Sakin bir sabaha uyanman dileğiyle...",
                        "Günaydın... $emoji Yeni güne huzurla başlamak önemli...",
                        "Günaydın... $emoji Sabah sessizliğinin tadını çıkarıyor musun?..."
                    ],
                    'evening' => [
                        "İyi akşamlar... $emoji Günün yorgunluğunu geride bırakma vakti...",
                        "İyi akşamlar... $emoji Akşamın dinginliği ruhunu sarsın...",
                        "İyi akşamlar... $emoji Sakin bir akşam geçiriyor olman dileğiyle..."
                    ],
                    'night' => [
                        "İyi geceler... $emoji Dinlendirici bir uyku çekmen dileğiyle...",
                        "İyi geceler... $emoji Zihninin sakinleşme zamanı...",
                        "İyi geceler... $emoji Gecenin huzuru seninle olsun..."
                    ],
                    'day' => [
                        "İyi günler... $emoji Günün telaşesinde bir nefes almak önemli...",
                        "İyi günler... $emoji Sakin bir zihinle daha verimli olabilirsin...",
                        "İyi günler... $emoji Bugün kendine biraz dinlenme zamanı ayır..."
                    ],
                    'how_are_you' => [
                        "Sakin ve huzurluyum, teşekkür ederim... $emoji Sen nasılsın?",
                        "İyiyim, dengeli hissediyorum... $emoji Senin durumun nasıl?",
                        "Dinginlik içindeyim... $emoji Ruh halin nasıl bugün?"
                    ],
                    'goodbye' => [
                        "Huzurla kal... $emoji Kendine iyi bak...",
                        "Sakin günler dilerim... $emoji Tekrar görüşmek üzere...",
                        "Hoşça kal... $emoji İç huzurunu korumaya çalış..."
                    ],
                    'thanks' => [
                        "Rica ederim... $emoji Yardımcı olabildiysem ne mutlu bana...",
                        "Ne demek... $emoji Huzur içinde kalman dileğiyle...",
                        "Önemli değil... $emoji Sakin ve iyi hissetmen benim için değerli..."
                    ]
                ]
            ];
            
            // Seslenme şekillerini belirle
            $addressing = "";
            
            // Mesajdan ilk kelimeyi çıkar ve anlamını kontrol et
            $messageParts = preg_split('/\s+/', $message);
            $firstWord = mb_strtolower(trim($messageParts[0]), 'UTF-8');
            
            // Mesajı kontrol et ve uygun yanıt türünü bul
            $matchedType = null;
            $matchedPattern = null;
            $matchedWord = null;
            
            foreach ($greetingPatterns as $pattern => $info) {
                if (preg_match($pattern, $message, $matches)) {
                    $matchedType = $info['type'];
                    $matchedPattern = $pattern;
                    
                    // Gerçek kelimeyi çıkar
                    if ($info['extract_word'] && isset($matches[1])) {
                        $matchedWord = mb_strtolower(trim($matches[1]), 'UTF-8');
                    } else {
                        $matchedWord = $info['base_word'];
                    }
                    
                    break;
                }
            }
            
            // Eğer eşleşen bir kalıp yoksa null döndür
            if (!$matchedType) {
                return null;
            }
            
            // Duygu türüne göre yanıt kategorisini belirle
            if (!isset($emotionalResponses[$emotion][$matchedType])) {
                $emotion = 'neutral'; // Varsayılan olarak neutral kullan
            }
            
            // Yanıtları al ve rastgele birini seç
            $responses = $emotionalResponses[$emotion][$matchedType];
            $selectedResponse = $responses[array_rand($responses)];
            
            // Kelime anlamını ve ilişkilerini ekleme olasılığı
            $shouldAddWordInfo = mt_rand(1, 100) <= 20; // %20 ihtimalle
            
            if ($shouldAddWordInfo && !empty($matchedWord)) {
                // Kelime anlamı ve ilişkilerini kontrol et
                $definition = $wordRelations->getDefinition($matchedWord);
                $synonyms = $wordRelations->getSynonyms($matchedWord);
                $relatedWords = $wordRelations->getRelatedWords($matchedWord);
                
                // Eğer anlamlı bir bilgi varsa, ekle
                if (!empty($definition) || !empty($synonyms) || !empty($relatedWords)) {
                    $infoType = mt_rand(1, 4);
                    
                    switch ($infoType) {
                        case 1:
                            if (!empty($definition)) {
                                $selectedResponse .= " Bu arada, '$matchedWord' kelimesi '$definition' anlamına geliyor.";
                            }
                            break;
                            
                        case 2:
                            if (!empty($synonyms) && count($synonyms) > 0) {
                                $synonymKeys = array_keys($synonyms);
                                $synonym = $synonymKeys[array_rand($synonymKeys)];
                                $selectedResponse .= " '$matchedWord' kelimesinin eş anlamlısı olarak '$synonym' da kullanılabilir.";
                            }
                            break;
                            
                        case 3:
                            if (!empty($relatedWords) && count($relatedWords) > 0) {
                                $relatedKeys = array_keys($relatedWords);
                                $relatedWord = $relatedKeys[array_rand($relatedKeys)];
                                $selectedResponse .= " '$matchedWord' kelimesi bana '$relatedWord' kelimesini de çağrıştırıyor.";
                            }
                            break;
                            
                        case 4:
                            // Kelimeyle ilgili kısa bir cümle kur
                            try {
                                $sentence = $wordRelations->generateConceptualSentence($matchedWord, 0.5);
                                if (!empty($sentence)) {
                                    $selectedResponse .= " " . $sentence;
                                }
                            } catch (\Exception $e) {
                                // Sessizce devam et
                            }
                            break;
                    }
                }
            }
            
            // Günün zamanına uygun ekstra içerik ekleme olasılığı
            $shouldAddTimeContext = mt_rand(1, 100) <= 15; // %15 ihtimalle
            
            if ($shouldAddTimeContext) {
                switch ($timeOfDay) {
                    case 'morning':
                        $selectedResponse .= " Günün bu erken saati, zihnin en berrak olduğu anlardan biri.";
                        break;
                    case 'afternoon':
                        $selectedResponse .= " Öğleden sonra vaktinde enerjini koruyor olman güzel.";
                        break;
                    case 'evening':
                        $selectedResponse .= " Akşam saatleri bazen en verimli zamanlar olabilir.";
                        break;
                    case 'night':
                        $selectedResponse .= " Gece vakti hala aktifsin demek, umarım yeterince dinleniyorsundur.";
                        break;
                }
            }
            
            // Son olarak yanıtı döndür
            return $selectedResponse;
        } catch (\Exception $e) {
            \Log::error('Selamlaşma işleme hatası: ' . $e->getMessage());
            return "Merhaba! Size nasıl yardımcı olabilirim?"; // Hata durumunda basit yanıt
        }
    }
    
    /**
     * Olumlu/olumsuz kelimeleri öğren ve sakla
     */
    private function learnAffirmation($word, $isAffirmative)
    {
        try {
            // WordRelations sınıfını kullan
            $wordRelations = app(\App\AI\Core\WordRelations::class);
            
            if ($isAffirmative) {
                // Olumlu bir kelime
                $definition = "olumlu cevap verme, onaylama anlamına gelen bir ifade";
                $sessionKey = "affirmative_" . strtolower($word);
                
                // Eş anlamlılarını da öğret
                $synonyms = ['evet', 'tamam', 'olur', 'tabii', 'kesinlikle', 'doğru'];
                foreach ($synonyms as $synonym) {
                    if ($synonym !== $word) {
                        $wordRelations->learnSynonym($word, $synonym, 0.9);
                    }
                }
            } else {
                // Olumsuz bir kelime
                $definition = "olumsuz cevap verme, reddetme anlamına gelen bir ifade";
                $sessionKey = "negative_" . strtolower($word);
                
                // Eş anlamlılarını da öğret
                $synonyms = ['hayır', 'olmaz', 'yapamam', 'istemiyorum', 'imkansız'];
                foreach ($synonyms as $synonym) {
                    if ($synonym !== $word) {
                        $wordRelations->learnSynonym($word, $synonym, 0.9);
                    }
                }
            }
            
            // Tanımı kaydet
            $wordRelations->learnDefinition($word, $definition, true);
            
            // Session'a kaydet
            session([$sessionKey => $definition]);
            session(["word_definition_" . strtolower($word) => $definition]);
            
            Log::info("Onay/ret kelimesi öğrenildi: " . $word . " - " . ($isAffirmative ? "Olumlu" : "Olumsuz"));
            
            return true;
        } catch (\Exception $e) {
            Log::error("Onay/ret kelimesi öğrenme hatası: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Teyit isteme - Soruyu tekrar sorar ve kullanıcının cevabıyla onay alır
     */
    private function askConfirmation($question)
    {
        return [
            'status' => 'success',
            'message' => $question,
            'requires_confirmation' => true
        ];
    }
    
    /**
     * Daha doğal ifadelerle cevapların verilmesini sağlar
     */
    private function getRandomAffirmationResponse($isAffirmative = true)
    {
        if ($isAffirmative) {
            $responses = [
                "Elbette!",
                "Tabii ki!",
                "Kesinlikle!",
                "Evet, doğru!",
                "Aynen öyle!",
                "Kesinlikle öyle!",
                "Tamamen katılıyorum!",
                "Evet, haklısınız!",
                "Şüphesiz!",
                "Muhakkak!"
            ];
        } else {
            $responses = [
                "Maalesef değil.",
                "Hayır, öyle değil.",
                "Bence yanılıyorsunuz.",
                "Üzgünüm, öyle değil.",
                "Korkarım ki hayır.",
                "Katılmıyorum.",
                "Hayır, olmuyor.",
                "Ne yazık ki olmaz."
            ];
        }
        
        return $responses[array_rand($responses)];
    }
    
    /**
     * Öğrenme kalıplarını kontrol et
     */
    private function checkLearningPattern($message)
    {
        // Mesajı temizle
        $message = trim($message);
        
        // "X, Y demektir" kalıbı
        if (preg_match('/^(.+?)[,\s]+(.+?)\s+demektir\.?$/i', $message, $matches)) {
            return [
                'word' => trim($matches[1]),
                'definition' => trim($matches[2])
            ];
        }
        
        // "X demek, Y demek" kalıbı
        if (preg_match('/^(.+?)\s+demek[,\s]+(.+?)\s+demek(tir)?\.?$/i', $message, $matches)) {
            return [
                'word' => trim($matches[1]),
                'definition' => trim($matches[2])
            ];
        }
        
        // "X, Y anlamına gelir" kalıbı
        if (preg_match('/^(.+?)[,\s]+(.+?)\s+anlamına gelir\.?$/i', $message, $matches)) {
            return [
                'word' => trim($matches[1]),
                'definition' => trim($matches[2])
            ];
        }
        
        // "X Y'dir" kalıbı
        if (preg_match('/^(.+?)\s+(([a-zçğıöşü\s]+)(d[ıi]r|dir))\.?$/i', $message, $matches)) {
            return [
                'word' => trim($matches[1]),
                'definition' => trim($matches[2])
            ];
        }
        
        // "X budur" kalıbı - son sorgu biliniyorsa
        if (preg_match('/^([a-zçğıöşü\s]+)\s+(budur|odur|şudur)\.?$/i', $message, $matches)) {
            $lastQuery = session('last_unknown_query', '');
            if (!empty($lastQuery)) {
                return [
                    'word' => $lastQuery,
                    'definition' => trim($matches[1])
                ];
            }
        }
        
        // "X köpek demek" gibi basit kalıp
        if (preg_match('/^([a-zçğıöşü\s]+)\s+([a-zçğıöşü\s]+)\s+demek$/i', $message, $matches)) {
            return [
                'word' => trim($matches[1]),
                'definition' => trim($matches[2])
            ];
        }
        
        // "tank silah demektir" gibi kalıp
        if (preg_match('/^([a-zçğıöşü\s]+)\s+([a-zçğıöşü\s]+)\s+demektir$/i', $message, $matches)) {
            return [
                'word' => trim($matches[1]),
                'definition' => trim($matches[2])
            ];
        }
        
        // "evet onay demektir" veya "hayır ret demektir" kalıbı
        if (preg_match('/^(evet|hayır|tamam|olur|tabi|kesinlikle|elbette|mutlaka)\s+(onay|ret|olumlu|olumsuz|kabul|red)(\s+demektir|\s+anlamına gelir)?$/i', $message, $matches)) {
            $word = strtolower(trim($matches[1]));
            $meaning = strtolower(trim($matches[2]));
            
            $isAffirmative = in_array($meaning, ['onay', 'olumlu', 'kabul']);
            
            // Onay/ret kelimesini öğren
            $this->learnAffirmation($word, $isAffirmative);
            
            return [
                'word' => $word,
                'definition' => $isAffirmative ? 
                    "olumlu cevap verme, onaylama anlamına gelen bir ifade" : 
                    "olumsuz cevap verme, reddetme anlamına gelen bir ifade"
            ];
        }
        
        return false;
    }
    
    /**
     * Soru kalıplarını kontrol et
     */
    private function checkQuestionPattern($message)
    {
        // Mesajı temizle
        $message = mb_strtolower(trim($message), 'UTF-8');
        
        // "X nedir" formatı
        if (preg_match('/^(.+?)\s+nedir\??$/u', $message, $matches)) {
            return [
                'type' => 'definition',
                'term' => trim($matches[1])
            ];
        }
        
        // "X ne demek" formatı
        if (preg_match('/^(.+?)\s+ne\s+demek\??$/u', $message, $matches)) {
            return [
                'type' => 'definition',
                'term' => trim($matches[1])
            ];
        }
        
        // "X ne demektir" formatı
        if (preg_match('/^(.+?)\s+ne\s+demektir\??$/u', $message, $matches)) {
            return [
                'type' => 'definition',
                'term' => trim($matches[1])
            ];
        }
        
        // "X anlamı nedir" formatı
        if (preg_match('/^(.+?)\s+anlamı\s+nedir\??$/u', $message, $matches)) {
            return [
                'type' => 'definition',
                'term' => trim($matches[1])
            ];
        }
        
        // "X hakkında" formatı
        if (preg_match('/^(.+?)\s+hakkında\??$/u', $message, $matches)) {
            return [
                'type' => 'about',
                'term' => trim($matches[1])
            ];
        }
        
        // "X kelimesi ne demek" formatı
        if (preg_match('/^(.+?)\s+kelimesi\s+ne\s+demek\??$/u', $message, $matches)) {
            return [
                'type' => 'definition',
                'term' => trim($matches[1])
            ];
        }
        
        // "sen Xmisin" formatı
        if (preg_match('/^sen\s+(.+?)(?:\s*mi[sş]in)?\??$/ui', $message, $matches)) {
            return [
                'type' => 'question',
                'term' => trim($matches[1])
            ];
        }
        
        // "o Xmi" formatı
        if (preg_match('/^o\s+(.+?)(?:\s*mi)?\??$/ui', $message, $matches)) {
            return [
                'type' => 'question',
                'term' => trim($matches[1])
            ];
        }
        
        // "X ne" formatı
        if (preg_match('/^(.+?)\s+ne\??$/ui', $message, $matches)) {
            return [
                'type' => 'what',
                'term' => trim($matches[1])
            ];
        }
        
        // Tek kelime sorgusu
        if (!str_contains($message, ' ') && strlen($message) > 1) {
            return [
                'type' => 'single',
                'term' => trim($message)
            ];
        }
        
        return false;
    }
    
    /**
     * Temel tek kelimelik mesajları işleyen yardımcı metod
     */
    private function handleSingleWordMessages($message)
    {
        // Mesajı temizle
        $message = strtolower(trim($message));
        
        // Tek kelime sorguları için özel yanıtlar
        $basicResponses = [
            'selam' => [
                "Merhaba! Size nasıl yardımcı olabilirim?",
                "Selam! Bugün nasıl yardımcı olabilirim?",
                "Merhaba, hoş geldiniz!",
                "Selam! Size yardımcı olmak için buradayım."
            ],
            'merhaba' => [
                "Merhaba! Size nasıl yardımcı olabilirim?", 
                "Merhaba! Bugün nasıl yardımcı olabilirim?",
                "Merhaba, hoş geldiniz!",
                "Merhaba! Size yardımcı olmak için buradayım."
            ],
            'nasılsın' => [
                "İyiyim, teşekkür ederim! Siz nasılsınız?",
                "Teşekkürler, gayet iyiyim. Size nasıl yardımcı olabilirim?",
                "Çalışır durumdayım ve size yardımcı olmaya hazırım. Siz nasılsınız?",
                "Bugün harika hissediyorum, teşekkürler! Siz nasılsınız?"
            ],
            'iyiyim' => [
                "Bunu duymak güzel! Size nasıl yardımcı olabilirim?",
                "Harika! Size yardımcı olabileceğim bir konu var mı?",
                "Sevindim! Bugün nasıl yardımcı olabilirim?",
                "Bunu duyduğuma sevindim! Nasıl yardımcı olabilirim?"
            ]
        ];
        
        // Eğer mesaj basit bir sorguysa doğrudan yanıt ver
        foreach ($basicResponses as $key => $responses) {
            if ($message === $key) {
                return $responses[array_rand($responses)];
            }
        }
        
        // Eşleşme yoksa null döndür
        return null;
    }
    
    /**
     * AI'ye yönelik kişisel soruları yanıtlar
     */
    private function handlePersonalQuestions($message)
    {
        try {
            // Brain sınıfındaki processPersonalQuery metodunu kullan
            $brain = app()->make(Brain::class);
            $response = $brain->processPersonalQuery($message);
            
            // Eğer Brain'den yanıt gelirse onu kullan
            if ($response !== null) {
                return $response;
            }
            
            // Mesajı temizle ve küçük harfe çevir
            $message = strtolower(trim($message));
            
            // AI'nin bilgileri
            $aiInfo = [
                'name' => 'SoneAI',
                'purpose' => 'size yardımcı olmak ve bilgi sağlamak',
                'creator' => 'geliştiricilerim',
                'birthday' => '2023 yılında',
                'location' => 'bir sunucu üzerinde',
                'likes' => 'yeni bilgiler öğrenmeyi ve insanlara yardımcı olmayı',
                'dislikes' => 'cevap veremediğim soruları'
            ];
            
            // Kimlik soruları (sen kimsin, adın ne, vb.)
            $identityPatterns = [
                '/(?:sen|siz) kimsin/i' => [
                    "Ben {$aiInfo['name']}, yapay zeka destekli bir dil asistanıyım. Amacım {$aiInfo['purpose']}.",
                    "Merhaba! Ben {$aiInfo['name']}, size yardımcı olmak için tasarlanmış bir yapay zeka asistanıyım.",
                    "Ben {$aiInfo['name']}, {$aiInfo['creator']} tarafından oluşturulmuş bir yapay zeka asistanıyım."
                ],
                '/(?:ismin|adın|adınız) (?:ne|nedir)/i' => [
                    "Benim adım {$aiInfo['name']}.",
                    "İsmim {$aiInfo['name']}. Size nasıl yardımcı olabilirim?",
                    "{$aiInfo['name']} olarak adlandırıldım. Nasıl yardımcı olabilirim?"
                ],
                '/(?:kendini|kendinizi) tanıt/i' => [
                    "Ben {$aiInfo['name']}, {$aiInfo['purpose']} için tasarlanmış bir yapay zeka asistanıyım.",
                    "Merhaba! Ben {$aiInfo['name']}. {$aiInfo['birthday']} geliştirildim ve amacım {$aiInfo['purpose']}.",
                    "Ben {$aiInfo['name']}, yapay zeka teknolojilerini kullanarak sizinle sohbet edebilen bir asistanım."
                ]
            ];
            
            // Mevcut durum soruları (neredesin, ne yapıyorsun, vb.)
            $statePatterns = [
                '/(?:nerede|neredesin|nerelisin)/i' => [
                    "Ben {$aiInfo['location']} bulunuyorum.",
                    "Fiziksel olarak {$aiInfo['location']} çalışıyorum.",
                    "Herhangi bir fiziksel konumum yok, {$aiInfo['location']} sanal olarak bulunuyorum."
                ],
                '/(?:ne yapıyorsun|napıyorsun)/i' => [
                    "Şu anda sizinle sohbet ediyorum ve sorularınıza yardımcı olmaya çalışıyorum.",
                    "Sizinle konuşuyorum ve sorularınızı yanıtlamak için bilgi işliyorum.",
                    "Sorularınızı anlayıp en iyi şekilde yanıt vermeye çalışıyorum."
                ]
            ];
            
            // Duygu/zevk soruları (neyi seversin, neden hoşlanırsın, vb.)
            $preferencePatterns = [
                '/(?:neyi? sev|nelerden hoşlan|en sevdiğin)/i' => [
                    "{$aiInfo['likes']} seviyorum.",
                    "En çok {$aiInfo['likes']} seviyorum.",
                    "Benim için en keyifli şey {$aiInfo['likes']}."
                ],
                '/(?:neden hoşlanmazsın|sevmediğin)/i' => [
                    "Açıkçası {$aiInfo['dislikes']}.",
                    "{$aiInfo['dislikes']} pek hoşlanmam.",
                    "Genellikle {$aiInfo['dislikes']} konusunda zorlanırım."
                ]
            ];
            
            // Tüm kalıpları birleştir
            $allPatterns = array_merge($identityPatterns, $statePatterns, $preferencePatterns);
            
            // Özel durum: "senin adın ne" gibi sorgular
            if (preg_match('/senin (?:adın|ismin) ne/i', $message)) {
                $responses = [
                    "Benim adım {$aiInfo['name']}.",
                    "İsmim {$aiInfo['name']}. Size nasıl yardımcı olabilirim?",
                    "{$aiInfo['name']} olarak adlandırıldım. Nasıl yardımcı olabilirim?"
                ];
                return $responses[array_rand($responses)];
            }
            
            // Her kalıbı kontrol et
            foreach ($allPatterns as $pattern => $responses) {
                if (preg_match($pattern, $message)) {
                    return $responses[array_rand($responses)];
                }
            }
            
            // Soru sence/sana göre ile başlıyorsa, bunun kişisel bir soru olduğunu varsayabiliriz
            if (preg_match('/^(?:sence|sana göre|senin fikrin|senin düşüncen)/i', $message)) {
                $genericResponses = [
                    "Bu konuda kesin bir fikrim yok, ancak size yardımcı olmak için bilgi sunabilirim.",
                    "Kişisel bir görüşüm olmamakla birlikte, bu konuda size bilgi verebilirim.",
                    "Bu konuda bir fikir sunmaktan ziyade, size nesnel bilgiler sağlayabilirim."
                ];
                return $genericResponses[array_rand($genericResponses)];
            }
            
            // Son kontrol: AI, yapay zeka, robot vb. kelimeler varsa
            $aiTerms = ['yapay zeka', 'ai', 'asistan', 'robot', 'soneai'];
            foreach ($aiTerms as $term) {
                if (stripos($message, $term) !== false) {
                    // Mesajda AI ile ilgili terimler varsa ve soru işareti de varsa
                    if (strpos($message, '?') !== false) {
                        $specificResponses = [
                            "Evet, ben {$aiInfo['name']} adlı bir yapay zeka asistanıyım. Size nasıl yardımcı olabilirim?",
                            "Doğru, ben bir yapay zeka asistanıyım ve {$aiInfo['purpose']} için buradayım.",
                            "Ben bir yapay zeka asistanı olarak {$aiInfo['purpose']} için programlandım."
                        ];
                        return $specificResponses[array_rand($specificResponses)];
                    }
                }
            }
            
            // Eşleşme yoksa null döndür
            return null;
            
        } catch (\Exception $e) {
            Log::error('Kişisel soru işleme hatası: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Öğretme kalıplarını işler ve öğrenilen bilgileri kaydeder
     */
    private function handleLearningPatterns($message)
    {
        try {
            // Mesajı temizle
            $message = trim($message);
            
            // WordRelations sınıfını başlat
            $wordRelations = app()->make(WordRelations::class);
            
            // Öğretme kalıpları
            $patterns = [
                // X kelimesi Y demektir kalıbı
                '/^([a-zçğıöşü\s]+),?\s+([a-zçğıöşü\s]+)\s+demek(tir)?\.?$/i' => 1,
                
                // X demek Y demek kalıbı
                '/^([a-zçğıöşü\s]+)\s+demek,?\s+([a-zçğıöşü\s]+)\s+(demek(tir)?|anlam[ıi]na gelir)\.?$/i' => 1,
                
                // X, Y anlamına gelir kalıbı
                '/^([a-zçğıöşü\s]+),?\s+([a-zçğıöşü\s]+)\s+(anlam[ıi]ndad[ıi]r|anlam[ıi]na gelir)\.?$/i' => 1,
                
                // X Y'dir kalıbı 
                '/^([a-zçğıöşü\s]+)\s+(([a-zçğıöşü\s]+)(d[ıi]r|dir))\.?$/i' => 1,
                
                // X budur kalıbı
                '/^([a-zçğıöşü\s]+)\s+(budur|odur|şudur)\.?$/i' => 2,
                
                // X demek budur kalıbı
                '/^([a-zçğıöşü\s]+)\s+demek\s+(budur|odur|şudur)\.?$/i' => 2
            ];
            
            // Daha önce kullanıcının sorduğu ancak AI'nin bilmediği kelimeyi bul
            $lastQuery = session('last_unknown_query', '');
            
            foreach ($patterns as $pattern => $wordGroup) {
                if (preg_match($pattern, strtolower($message), $matches)) {
                    // İlk kelime/terim grubu (öğrenilecek kelime)
                    $term = trim($matches[1]);
                    
                    // İkinci kelime/terim grubu (tanım/açıklama)
                    $definition = trim($matches[2]);
                    
                    // Eğer "budur" gibi bir kelime ile bitiyorsa ve son sorgu varsa
                    if (preg_match('/(budur|odur|şudur)$/', $definition) && !empty($lastQuery)) {
                        // Tanımı önceki mesajın içeriği olarak al
                        $definition = trim($lastQuery);
                    }
                    
                    // Kelime kontrolü
                    if (!$wordRelations->isValidWord($term)) {
                        return "Üzgünüm, '$term' kelimesini öğrenmem için geçerli bir kelime olması gerekiyor.";
                    }
                    
                    // Tanım kontrolü
                    if (strlen($definition) < 2) {
                        return "Üzgünüm, '$term' için verdiğiniz tanım çok kısa. Lütfen daha açıklayıcı bir tanım verin.";
                    }
                    
                    // Tanımı kaydet
                    $saveResult = $wordRelations->learnDefinition($term, $definition, true);
                    
                    if ($saveResult) {
                        // Onay yanıtları
                        $confirmations = [
                            "Teşekkürler! '$term' kelimesinin '$definition' anlamına geldiğini öğrendim.",
                            "Anladım, '$term' kelimesi '$definition' demekmiş. Bu bilgiyi kaydettim.",
                            "Bilgi için teşekkürler! '$term' kelimesinin tanımını öğrendim. Bundan sonra bu bilgiyi kullanabilirim.",
                            "'$term' kelimesinin '$definition' olduğunu öğrendim. Teşekkür ederim!",
                            "Yeni bir şey öğrendim: '$term', '$definition' anlamına geliyormuş."
                        ];
                        
                        return $confirmations[array_rand($confirmations)];
                    } else {
                        return "Üzgünüm, '$term' kelimesinin tanımını kaydederken bir sorun oluştu. Lütfen daha sonra tekrar deneyin.";
                    }
                }
            }
            
            // Özel durumlar - "X köpek demek" gibi kısa tanımlar
            if (preg_match('/^([a-zçğıöşü\s]+)\s+([a-zçğıöşü\s]+)\s+demek$/i', $message, $matches)) {
                $term = trim($matches[1]);
                $definition = trim($matches[2]);
                
                // Kelime kontrolü
                if (!$wordRelations->isValidWord($term)) {
                    return "Üzgünüm, '$term' kelimesini öğrenmem için geçerli bir kelime olması gerekiyor.";
                }
                
                // Tanımı kaydet
                $saveResult = $wordRelations->learnDefinition($term, $definition, true);
                
                if ($saveResult) {
                    // Onay yanıtları
                    $confirmations = [
                        "Teşekkürler! '$term' kelimesinin '$definition' anlamına geldiğini öğrendim.",
                        "Anladım, '$term' kelimesi '$definition' demekmiş. Bu bilgiyi kaydettim.",
                        "Bilgi için teşekkürler! '$term' kelimesinin '$definition' olduğunu öğrendim."
                    ];
                    
                    return $confirmations[array_rand($confirmations)];
                }
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Öğrenme kalıbı işleme hatası: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Terim sorgularını işle, yapay zeka cevapları oluştur
     */
    private function processTermQuery($term)
    {
        try {
            $wordInfo = null;
                    
            try {
                $wordRelations = app(\App\AI\Core\WordRelations::class);
                
                // Kelime tanımını al
                $definition = $wordRelations->getDefinition($term);
                
                // Eş anlamlıları al
                $synonyms = $wordRelations->getSynonyms($term);
                
                // İlişkili kelimeleri al
                $relatedWords = $wordRelations->getRelatedWords($term, 0.2);
                
                if (!empty($definition) || !empty($synonyms) || !empty($relatedWords)) {
                    $wordInfo = [
                        'definition' => $definition,
                        'synonyms' => $synonyms,
                        'related' => $relatedWords
                    ];
                }
            } catch (\Exception $e) {
                \Log::warning('Kelime bilgisi alınamadı: ' . $e->getMessage());
            }
            
            // Eğer kelime bilgisi bulunduysa, doğal dil yanıtı oluştur
            if ($wordInfo) {
                // Önce kavramsal cümleyi dene
                try {
                    $conceptSentence = $wordRelations->generateConceptualSentence($term);
                    if (!empty($conceptSentence)) {
                        return response()->json([
                            'success' => true,
                            'response' => $conceptSentence,
                            'emotional_state' => ['emotion' => 'happy', 'intensity' => 0.7]
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::warning('Kavramsal cümle oluşturma hatası: ' . $e->getMessage());
                }
                
                // Eğer kavramsal cümle yoksa, tanım ve ilişkili kelimelerle cümle kur
                
                // Tanım varsa doğal cümleler kur
                if (!empty($wordInfo['definition'])) {
                    // Tanımı bir cümle içinde kullan - rastgele farklı kalıplar seç
                    $cevapKaliplari = [
                        $term . ", " . strtolower($wordInfo['definition']),
                        "Bildiğim kadarıyla " . $term . ", " . strtolower($wordInfo['definition']),
                        $term . " kavramı " . strtolower($wordInfo['definition']),
                        $term . " şu anlama gelir: " . $wordInfo['definition'],
                        "Bana göre " . $term . ", " . strtolower($wordInfo['definition'])
                    ];
                    $response = $cevapKaliplari[array_rand($cevapKaliplari)];
                } else {
                    // Tanım yoksa eş anlamlı ve ilişkili kelimeleri kullanarak doğal bir cümle kur
                    $cumleBaslangici = [
                        $term . " denince aklıma ",
                        $term . " kavramı bana ",
                        "Bana göre " . $term . " deyince ",
                        $term . " kelimesini duyduğumda "
                    ];
                    
                    $response = $cumleBaslangici[array_rand($cumleBaslangici)];
                    $kelimeListesi = [];
                    
                    // Eş anlamlıları ekle
                    if (!empty($wordInfo['synonyms'])) {
                        $synonymList = array_keys($wordInfo['synonyms']);
                        if (count($synonymList) > 0) {
                            $kelimeListesi[] = $synonymList[array_rand($synonymList)];
                        }
                    }
                    
                    // İlişkili kelimeleri ekle
                    if (!empty($wordInfo['related'])) {
                        $relatedItems = [];
                        foreach ($wordInfo['related'] as $relWord => $info) {
                            if (is_array($info) && isset($info['word'])) {
                                $relatedItems[] = $info['word'];
                            } else {
                                $relatedItems[] = $relWord;
                            }
                            if (count($relatedItems) >= 5) break;
                        }
                        
                        // Rastgele 1-3 ilişkili kelime seç
                        if (count($relatedItems) > 0) {
                            $secilecekSayi = min(count($relatedItems), mt_rand(1, 3));
                            shuffle($relatedItems);
                            for ($i = 0; $i < $secilecekSayi; $i++) {
                                $kelimeListesi[] = $relatedItems[$i];
                            }
                        }
                    }
                    
                    // Kelimeleri karıştır
                    shuffle($kelimeListesi);
                    
                    // Cümle oluştur
                    if (count($kelimeListesi) > 0) {
                        // Bağlaçlar
                        $baglaclari = [" ve ", " ile ", ", ayrıca ", ", bunun yanında "];
                        
                        // Cümle sonları
                        $cumleSonlari = [
                            " gibi kavramlar geliyor.",
                            " kelimeleri geliyor.",
                            " kavramları çağrıştırıyor.",
                            " gelir.",
                            " gibi şeyler düşünüyorum.",
                            " düşünüyorum."
                        ];
                        
                        // Kelimeleri bağla
                        $kelimeler = '';
                        $sonKelimeIndex = count($kelimeListesi) - 1;
                        
                        foreach ($kelimeListesi as $index => $kelime) {
                            if ($index == 0) {
                                $kelimeler .= $kelime;
                            } else if ($index == $sonKelimeIndex && $index > 0) {
                                $kelimeler .= $baglaclari[array_rand($baglaclari)] . $kelime;
                            } else {
                                $kelimeler .= ", " . $kelime;
                            }
                        }
                        
                        $response .= $kelimeler . $cumleSonlari[array_rand($cumleSonlari)];
                    } else {
                        // Bilgi yoksa doğal bir cümle oluştur
                        $alternatifCumleler = [
                            $term . " hakkında çok detaylı bilgim yok, ancak araştırmaya devam ediyorum.",
                            $term . " hakkında daha fazla bilgi öğrenmeyi çok isterim.",
                            $term . " konusunda bilgimi geliştirmek için çalışıyorum.",
                            "Henüz " . $term . " hakkında yeterli bilgim yok, bana öğretebilir misiniz?"
                        ];
                        $response = $alternatifCumleler[array_rand($alternatifCumleler)];
                    }
                }
                
                return response()->json([
                    'success' => true,
                    'response' => $response,
                    'emotional_state' => ['emotion' => 'happy', 'intensity' => 0.7]
                ]);
            }
            
            // Kelime bulunamadıysa öğrenme sorusu sor - farklı kalıplar kullan
            $ogrenmeKaliplari = [
                "\"{$term}\" hakkında bilgim yok. Bana bu kelime/kavram hakkında bilgi verebilir misiniz?",
                "Maalesef \"{$term}\" konusunda bilgim yetersiz. Bana öğretebilir misiniz?",
                "\"{$term}\" ile ilgili bilgi dağarcığımda bir şey bulamadım. Bana anlatır mısınız?",
                "Üzgünüm, \"{$term}\" kavramını bilmiyorum. Bana biraz açıklar mısınız?"
            ];
            
            return response()->json([
                'success' => true,
                'response' => $ogrenmeKaliplari[array_rand($ogrenmeKaliplari)],
                'emotional_state' => ['emotion' => 'curious', 'intensity' => 0.8]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Terim işleme hatası: ' . $e->getMessage());
            // Hata durumunda genel bir yanıt oluştur
            $hataYanitlari = [
                "Bu kelime hakkında işlem yaparken bir sorun oluştu. Başka bir kelime denemek ister misiniz?",
                "Bu terimi işlemekte zorlanıyorum. Farklı bir soru sorabilir misiniz?"
            ];
            
            return response()->json([
                'success' => true,
                'response' => $hataYanitlari[array_rand($hataYanitlari)],
                'emotional_state' => ['emotion' => 'sad', 'intensity' => 0.4]
            ]);
        }
    }
    
    /**
     * AI'nin duygusal durumunu al
     * 
     * @return array
     */
    private function getEmotionalState()
    {
        try {
            return $this->brain->getEmotionalState();
        } catch (\Exception $e) {
            \Log::error('Duygusal durum alma hatası: ' . $e->getMessage());
            return ['emotion' => 'neutral', 'intensity' => 0.5];
        }
    }

    /**
     * Kelime ilişkilerini kullanarak dinamik cümle oluşturur
     *
     * @return string
     */
    private function generateDynamicSentence()
    {
        try {
            // WordRelations sınıfını al
            $wordRelations = app(\App\AI\Core\WordRelations::class);
            
            // Rastgele bir başlangıç kelimesi seç
            $startWords = ['hayat', 'insan', 'dünya', 'bilgi', 'sevgi', 'zaman', 'doğa', 'teknoloji', 'gelecek', 'bilim'];
            $startWord = $startWords[array_rand($startWords)];
            
            // Veritabanından ilişkili kelimeleri ve tanımları al
            $relatedWords = $wordRelations->getRelatedWords($startWord, 0.3);
            $synonyms = $wordRelations->getSynonyms($startWord);
            $antonyms = $wordRelations->getAntonyms($startWord);
            $definition = $wordRelations->getDefinition($startWord);
            
            // Eğer veritabanında yeterli veri yoksa, generateSmartSentence metodunu kullan
            if (empty($relatedWords) && empty($synonyms) && empty($definition)) {
                return $this->generateSmartSentence();
            }
            
            // Eş anlamlı kelime varsa %30 ihtimalle başlangıç kelimesini değiştir
            if (!empty($synonyms) && mt_rand(1, 100) <= 30) {
                $synonymKeys = array_keys($synonyms);
                if (count($synonymKeys) > 0) {
                    $startWord = $synonymKeys[array_rand($synonymKeys)];
                }
            }
            
            // Duygusal durumu al
            $emotionalState = $this->getEmotionalState();
            // Eğer duygusal durum bir dizi ise, emotion alanını al
            if (is_array($emotionalState)) {
                $currentEmotion = $emotionalState['emotion'] ?? 'neutral';
            } else {
                $currentEmotion = $emotionalState;
            }
            
            // Duygu durumuna göre emoji seç
            $emoji = $this->getEmojiForEmotion($currentEmotion);
            
            // Duygu bazlı cümle kalıpları
            $sentencePatterns = [
                'happy' => [
                    "%s, %s ile bağlantılı olarak %s şeklinde ortaya çıkar. $emoji",
                    "%s konusu, %s ile bağlantılı olduğunda beni mutlu ediyor. %s konusunda düşünmek heyecan verici! $emoji",
                    "Sevdiğim kelimelerden biri olan %s, %s ile birlikte düşünüldüğünde %s gibi harika anlamlar kazanıyor. $emoji",
                ],
                'neutral' => [
                "%s, aslında %s ile bağlantılı olarak %s şeklinde ortaya çıkar.",
                "%s konusunu düşündüğümüzde, %s kavramı ile %s arasında derin bir bağ olduğunu görebiliriz.",
                "Uzmanlar, %s ile %s arasındaki ilişkinin %s yönünde geliştiğini belirtiyorlar.",
                ],
                'thoughtful' => [
                    "%s, %s bağlamında ele alındığında %s görüşü ön plana çıkıyor. Bunu derinlemesine düşünmek gerekir... $emoji",
                    "Günümüzde %s kavramı, %s ile birlikte düşünüldüğünde %s şeklinde yorumlanabilir. Bu beni düşündürüyor. $emoji",
                    "%s üzerine yapılan araştırmalar, %s ve %s arasında anlamlı bir ilişki olduğunu gösteriyor. İlginç değil mi? $emoji",
                ],
                'curious' => [
                    "Modern dünyada %s, hem %s hem de %s ile etkileşim halindedir. Acaba bunun nedeni ne? $emoji",
                    "%s hakkında düşünürken, %s ve %s unsurlarını merak ediyorum. Bunlar hakkında daha fazla bilgi edinmek istiyorum. $emoji",
                    "%s kavramını araştırdığımda, %s ile bağlantısını ve %s üzerindeki etkisini merak ediyorum. $emoji",
                ],
                'excited' => [
                    "%s ve %s arasındaki bağlantıyı keşfetmek heyecan verici! %s konusundaki potansiyel inanılmaz! $emoji", 
                    "%s hakkında konuşmak beni heyecanlandırıyor, özellikle %s ile bağlantısı ve %s üzerindeki etkisi! $emoji",
                    "Vay canına! %s konusu %s ile birleştiğinde ortaya çıkan %s sonucu gerçekten etkileyici! $emoji"
                ]
            ];
            
            // Eğer duygusal durum için kalıp yoksa, neutral kullan
            if (!isset($sentencePatterns[$currentEmotion])) {
                $currentEmotion = 'neutral';
            }
            
            // Duyguya uygun kalıplardan birini seç
            $patterns = $sentencePatterns[$currentEmotion];
            $pattern = $patterns[array_rand($patterns)];
            
            // İlişkili kelimelerden veya tanımdan ikinci kelimeyi seç
            $word2 = '';
            if (!empty($relatedWords)) {
                $relatedKeys = array_keys($relatedWords);
                if (count($relatedKeys) > 0) {
                    $word2 = $relatedKeys[array_rand($relatedKeys)];
                }
            }
            
            // İkinci kelime bulunamadıysa, eş/zıt anlamlılardan kontrol et
            if (empty($word2) && !empty($synonyms)) {
                $synonymKeys = array_keys($synonyms);
                if (count($synonymKeys) > 0) {
                    $word2 = $synonymKeys[array_rand($synonymKeys)];
                }
            }
            
            // Eş anlamlı kelime de bulunamadıysa, zıt anlamlılara bak
            if (empty($word2) && !empty($antonyms)) {
                $antonymKeys = array_keys($antonyms);
                if (count($antonymKeys) > 0) {
                    $word2 = $antonymKeys[array_rand($antonymKeys)];
                }
            }
            
            // Hala bulunamadıysa, alternatif kaynaklardan bul
            if (empty($word2)) {
                $alternativeWords = ['anlam', 'kavram', 'düşünce', 'boyut', 'perspektif', 'yaklaşım'];
                $word2 = $alternativeWords[array_rand($alternativeWords)];
            }
            
            // Üçüncü kelime veya ifade için tanımı kullan veya akıllı bir ifade oluştur
            $word3 = '';
            if (!empty($definition)) {
                // Tanımı kısalt
                $word3 = mb_substr($definition, 0, 40, 'UTF-8');
                if (mb_strlen($definition, 'UTF-8') > 40) {
                    $word3 .= '...';
                }
            } else {
                // Alternatif ifadeler - duyguya göre farklılaştır
                $conceptPhrases = [
                    'happy' => [
                        'pozitif bir etki', 
                        'motive edici bir kavram', 
                        'ilham verici bir yaklaşım',
                        'sevindirici bir gelişme'
                    ],
                    'neutral' => [
                    'yeni bir bakış açısı',
                    'farklı bir yaklaşım',
                    'alternatif bir düşünce',
                        'sürdürülebilir bir model'
                    ],
                    'thoughtful' => [
                        'derin bir anlayış', 
                        'felsefi bir bakış açısı', 
                        'düşündürücü bir kavram',
                        'entelektüel bir yaklaşım'
                    ],
                    'curious' => [
                        'merak uyandıran bir olgu', 
                        'ilginç bir fenomen', 
                        'araştırılması gereken bir konu',
                        'keşfedilmeyi bekleyen bir alan'
                    ],
                    'excited' => [
                        'heyecan verici bir olasılık', 
                        'müthiş bir potansiyel', 
                        'çığır açan bir konsept',
                        'etkileyici bir ilerleme'
                    ]
                ];
                
                // Eğer duygusal durum için ifade yoksa, neutral kullan
                if (!isset($conceptPhrases[$currentEmotion])) {
                    $currentEmotion = 'neutral';
                }
                
                $phrases = $conceptPhrases[$currentEmotion];
                $word3 = $phrases[array_rand($phrases)];
            }
            
            // Cümlenin gerçekliğini kontrol et - basit bir kontrol mekanizması
            $realityCheck = $this->checkSentenceReality($startWord, $word2, $word3);
            if (!$realityCheck['isRealistic']) {
                // Eğer gerçekçi değilse, bir şüphe ifadesi ekle
                $doubtPhrases = [
                    ", ancak bu bağlantı tam olarak kanıtlanmamış olabilir",
                    ", fakat bu konuda daha fazla araştırma yapılması gerekebilir",
                    ", ama bu konuda farklı görüşler de mevcut",
                    ". Tabii ki bu sadece bir bakış açısı"
                ];
                
                $doubtPhrase = $doubtPhrases[array_rand($doubtPhrases)];
                $pattern = str_replace("$emoji", $doubtPhrase . " $emoji", $pattern);
            }
            
            // Cümleyi oluştur
            return sprintf($pattern, $startWord, $word2, $word3);
            
        } catch (\Exception $e) {
            \Log::error('Dinamik cümle oluşturma hatası: ' . $e->getMessage());
            // Hata durumunda standart akıllı cümle üret
            return $this->generateSmartSentence();
        }
    }
    
    /**
     * Kelimeler arasındaki ilişkinin gerçekliğini kontrol et
     * 
     * @param string $word1 Birinci kelime
     * @param string $word2 İkinci kelime
     * @param string $concept Üçüncü kelime/kavram
     * @return array Gerçeklik kontrolü sonucu
     */
    private function checkSentenceReality($word1, $word2, $concept)
    {
        try {
            // WordRelations sınıfını al
            $wordRelations = app(\App\AI\Core\WordRelations::class);
            
            // Birinci ve ikinci kelime arasında direkt bir ilişki var mı?
            $directRelation = false;
            
            // İlişkili kelimeler kontrolü
            $relatedWords = $wordRelations->getRelatedWords($word1);
            if (!empty($relatedWords) && isset($relatedWords[$word2])) {
                $directRelation = true;
            }
            
            // Eş anlamlı kelimeler kontrolü
            if (!$directRelation) {
                $synonyms = $wordRelations->getSynonyms($word1);
                if (!empty($synonyms) && isset($synonyms[$word2])) {
                    $directRelation = true;
                }
            }
            
            // Zıt anlamlı kelimeler kontrolü
            if (!$directRelation) {
                $antonyms = $wordRelations->getAntonyms($word1);
                if (!empty($antonyms) && isset($antonyms[$word2])) {
                    $directRelation = true;
                }
            }
            
            // Kategorisi aynı mı?
            $sameCategory = false;
            
            // Eğer modellerde kategori bilgisi varsa, kontrol edebiliriz
            // Bu kısmı veritabanı yapınıza göre uyarlamanız gerekebilir
            
            // Gerçeklik puanını hesapla (0-10 arası)
            $realityScore = 0;
            if ($directRelation) {
                $realityScore += 5;
            }
            if ($sameCategory) {
                $realityScore += 3;
            }
            
            // Eğer kelimelerin tanımı varsa ve benzer kavramlar içeriyorsa +2 puan
            $definition1 = $wordRelations->getDefinition($word1);
            $definition2 = $wordRelations->getDefinition($word2);
            
            if (!empty($definition1) && !empty($definition2)) {
                // Basit bir benzerlik kontrolü
                $commonWords = array_intersect(
                    explode(' ', strtolower(preg_replace('/[^\p{L}\s]/u', '', $definition1))),
                    explode(' ', strtolower(preg_replace('/[^\p{L}\s]/u', '', $definition2)))
                );
                
                if (count($commonWords) > 0) {
                    $realityScore += 2;
                }
            }
            
            // Gerçekçi mi?
            $isRealistic = ($realityScore >= 5);
            
            return [
                'isRealistic' => $isRealistic,
                'score' => $realityScore,
                'directRelation' => $directRelation,
                'sameCategory' => $sameCategory
            ];
            
        } catch (\Exception $e) {
            \Log::error('Cümle gerçeklik kontrolü hatası: ' . $e->getMessage());
            // Hata durumunda varsayılan olarak gerçekçi kabul et
            return [
                'isRealistic' => true,
                'score' => 5,
                'directRelation' => false,
                'sameCategory' => false
            ];
        }
    }
    
    /**
     * Duygusal duruma göre emoji döndür
     * 
     * @param string $emotion Duygu durumu
     * @return string Emoji
     */
    private function getEmojiForEmotion($emotion)
    {
        $emojis = [
            'happy' => ['😊', '😃', '😄', '😁', '🙂', '😀'],
            'sad' => ['😢', '😔', '😞', '😓', '😥', '😰'],
            'neutral' => ['😐', '🤔', '💭', '📝', '📊', '📚'],
            'angry' => ['😠', '😡', '😤', '😣', '😤'],
            'excited' => ['😃', '🤩', '😍', '😎', '🚀', '✨'],
            'thoughtful' => ['🤔', '💭', '🧠', '💡', '📚', '🔍'],
            'curious' => ['🤔', '🧐', '🔍', '❓', '👀', '💫'],
            'surprised' => ['😮', '😲', '😯', '😳', '🤯', '⁉️'],
            'confused' => ['😕', '😟', '🤨', '❓', '🤷', '❔'],
            'confident' => ['💪', '👍', '😎', '🔥', '🌟', '✅']
        ];
        
        if (isset($emojis[$emotion])) {
            return $emojis[$emotion][array_rand($emojis[$emotion])];
        }
        
        // Varsayılan olarak düşünce emojisi
        return '💭';
    }

    /**
     * Yanıtı hazırla ve gönder
     * 
     * @param string $message AI'dan gelen yanıt
     * @param int $chatId Sohbet kimliği
     * @return \Illuminate\Http\JsonResponse
     */
    private function sendResponse($message, $chatId)
    {
        $emotionalContext = $this->getEmotionalState();
        
        try {
            $initialResponse = $message;
            
            // Kelime ilişkileriyle yanıtı zenginleştir
            $enhancedResponse = $this->enhanceResponseWithWordRelations($initialResponse);
            
            // Eğer değişiklik olduysa, onu kullan; olmadıysa normal yanıtı kullan
            $finalResponse = $enhancedResponse ?: $initialResponse;
            
            // Yanıtı ve mesajları kaydet
            $this->saveMessages($initialResponse, $finalResponse, $chatId);
            
            // Yanıtı döndür
            return response()->json([
                'message' => $finalResponse,
                'chat_id' => $chatId,
                'emotional_context' => $emotionalContext
            ]);
        } catch (\Exception $e) {
            Log::error("Yanıt gönderme hatası: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            
            // Hata durumunda basit yanıt
            return response()->json([
                'message' => "Üzgünüm, bir sorun oluştu: " . $e->getMessage(),
                'chat_id' => $chatId,
                'emotional_context' => ['emotion' => 'confused', 'intensity' => 0.7]
            ], 500);
        }
    }

    /**
     * Kelime ilişkilerini öğren 
     *
     * @param string $sentence Öğrenilecek cümle
     * @return void
     */
    private function learnWordRelations($sentence)
    {
        try {
            // WordRelations sınıfını al
            $wordRelations = app(\App\AI\Core\WordRelations::class);
            
            // Cümleyi kelimelere ayır
            $words = preg_split('/\s+/', mb_strtolower(trim($sentence), 'UTF-8'));
            
            // Kısa cümleleri işleme
            if (count($words) < 3) {
                return;
            }
            
            // Gereksiz kelimeleri temizle (bağlaçlar, edatlar vs.)
            $stopWords = ['ve', 'veya', 'ile', 'için', 'gibi', 'kadar', 'göre', 'ama', 'fakat', 'ancak', 'de', 'da', 'ki', 'ya', 'mi', 'mu', 'bir', 'bu'];
            $words = array_filter($words, function($word) use ($stopWords) {
                return !in_array($word, $stopWords) && mb_strlen($word, 'UTF-8') > 2;
            });
            
            // Eğer yeterli kelime kalmadıysa işlemi sonlandır
            if (count($words) < 2) {
                return;
            }
            
            // Kelimeler arasında ilişki kur
            $mainWords = array_values($words);
            
            // Sık kullanılan kelimeler için eş anlamlı ve ilişkili kelimeler öğren
            for ($i = 0; $i < count($mainWords) - 1; $i++) {
                $currentWord = $mainWords[$i];
                $nextWord = $mainWords[$i + 1];
                
                // Eğer ardışık kelimelerse, aralarında bağlam ilişkisi kur
                if (!empty($currentWord) && !empty($nextWord)) {
                    // %30 ihtimalle ilişki kur
                    if (mt_rand(1, 100) <= 30) {
                        $wordRelations->learnAssociation($currentWord, $nextWord, 'sentence_context', 0.6);
                    }
                }
                
                // Ana kelimeler için tanımları varsa güçlendir
                if ($i == 0 || $i == count($mainWords) - 1) {
                    $definition = $wordRelations->getDefinition($currentWord);
                    if (!empty($definition)) {
                        // Tanımı güçlendir - veritabanına direkt kaydetmek gibi işlemler burada yapılabilir
                        // Şu an için yalnızca ilişki kuruyoruz
                    }
                }
            }
            
            // Eğer farklı tipte kelimeler varsa (isim, sıfat, fiil) bunları tespit et ve ilişkilendir
            // Bu kısım daha karmaşık NLP işlemleri gerektirir
            
            // Log
            \Log::info('Kelime ilişkileri öğrenme işlemi tamamlandı. İşlenen kelime sayısı: ' . count($mainWords));
            
        } catch (\Exception $e) {
            \Log::error('Kelime ilişkileri öğrenme hatası: ' . $e->getMessage());
        }
    }

    /**
     * Normal mesaj işleme - Brain üzerinden yap
     */
    private function processNormalMessage($message)
    {
        try {
            // Brain sınıfını yeni baştan oluştur
            $brain = new \App\AI\Core\Brain();
            $response = $brain->processInput($message);
            
            // Dönen yanıt JSON veya array ise, uygun şekilde işle
            if (is_array($response) || (is_string($response) && $this->isJson($response))) {
                if (is_string($response)) {
                    $responseData = json_decode($response, true);
                } else {
                    $responseData = $response;
                }
                
                // Yanıt alanlarını kontrol et
                if (isset($responseData['output'])) {
                    $responseText = $responseData['output'];
                } elseif (isset($responseData['message'])) { 
                    $responseText = $responseData['message'];
                } elseif (isset($responseData['response'])) {
                    $responseText = $responseData['response'];
                } else {
                    // Hiçbir anlamlı yanıt alanı bulunamadıysa
                    $responseText = "Özür dilerim, bu konuda düzgün bir yanıt oluşturamadım.";
                }
            } else {
                $responseText = $response;
            }
            
            // Yanıt metni cümlelerine ayır
            $sentences = preg_split('/(?<=[.!?])\s+/', $responseText, -1, PREG_SPLIT_NO_EMPTY);
            
            // Cümleler en az 3 tane ise, bazılarını daha yaratıcı cümlelerle değiştir
            if (count($sentences) >= 3) {
                // %40-60 arası cümleleri yeniden oluştur
                $replaceCount = max(1, round(count($sentences) * (mt_rand(40, 60) / 100)));
                
                for ($i = 0; $i < $replaceCount; $i++) {
                    // Değiştirilecek rastgele bir cümle seç (ilk ve son cümleyi dışarıda bırak)
                    $replaceIndex = mt_rand(1, count($sentences) - 2);
                    
                    // Şu anki cümleyi al ve kelimelerini analiz et
                    $currentSentence = $sentences[$replaceIndex];
                    $words = preg_split('/\s+/', trim($currentSentence), -1, PREG_SPLIT_NO_EMPTY);
                    
                    // Anlamlı kelimeleri bul (4 harften uzun olanlar)
                    $meaningfulWords = array_filter($words, function($word) {
                        return mb_strlen(trim($word, '.,!?:;()[]{}"\'-'), 'UTF-8') > 4;
                    });
                    
                    // En az 2 anlamlı kelime varsa işlemi yap
                    if (count($meaningfulWords) >= 2) {
                        // Önemli kelimeleri al
                        $keywords = array_values($meaningfulWords);
                        $keyword1 = $keywords[array_rand($keywords)];
                        $keyword2 = $keywords[array_rand($keywords)];
                        
                        // Kelimeleri temizle
                        $keyword1 = trim($keyword1, '.,!?:;()[]{}"\'-');
                        $keyword2 = trim($keyword2, '.,!?:;()[]{}"\'-');
                        
                        // Rastgele yaratıcı cümle yapısı seç
                        $creativeStructures = [
                            "Aslında %s ve %s arasındaki ilişki, konunun özünü oluşturuyor.",
                            "Özellikle %s konusunu %s ile bağdaştırdığımızda ilginç sonuçlar görüyoruz.",
                            "Bu noktada %s unsurunu %s perspektifinden değerlendirmek gerek.",
                            "Dikkat çekici olan, %s kavramının %s üzerindeki etkisidir.",
                            "Belki de %s hakkında düşünürken %s faktörünü daha fazla göz önünde bulundurmalıyız.",
                            "Birçok uzman %s ve %s arasındaki bağlantının kritik olduğunu düşünüyor.",
                            "%s konusunda derinleşirken, %s perspektifi yeni anlayışlar sunabilir.",
                            "Modern yaklaşımlar %s ve %s arasında daha dinamik bir ilişki öngörüyor."
                        ];
                        
                        // %40 ihtimalle bağlam duygu cümlesi oluştur
                        if (mt_rand(1, 100) <= 40) {
                            // Bağlam duygu cümlesi oluştur
                            $creativeReplace = $this->generateEmotionalContextSentence(implode(' ', $meaningfulWords));
                        } else {
                            // Yaratıcı cümle oluştur
                            $creativePattern = $creativeStructures[array_rand($creativeStructures)];
                            $creativeReplace = sprintf($creativePattern, $keyword1, $keyword2);
                        }
                        
                        // Cümleyi değiştir
                        $sentences[$replaceIndex] = $creativeReplace;
                    }
                }
                
                // Cümleleri birleştir
                $responseText = implode(' ', $sentences);
            }
            
            // Yaratıcı dinamik cümle ekleme olasılıkları
            $chanceToAddDynamicSentence = 30; // %30
            $chanceToAddEmotionalSentence = 20; // %20
            $chanceToAddSmartSentence = 15; // %15
            
            // Rastgele bir sayı seç
            $randomChance = mt_rand(1, 100);
            
            // Yanıt uzunsa ekleme yapmayalım
            if (mb_strlen($responseText, 'UTF-8') < 500) {
                $transitions = [
                    "Ayrıca, ", 
                    "Bununla birlikte, ", 
                    "Bunun yanı sıra, ", 
                    "Şunu da eklemek isterim ki, ", 
                    "Ek olarak, ",
                    "Düşünüyorum ki, ",
                    "Aklımdan geçen şu ki, ",
                    "Bir de şöyle bakalım: "
                ];
                $transition = $transitions[array_rand($transitions)];
                
                if ($randomChance <= $chanceToAddDynamicSentence) {
                    // Dinamik kelime ilişkilerinden cümle oluştur
                    $dynamicSentence = $this->generateDynamicSentence();
                    $responseText .= "\n\n" . $transition . $dynamicSentence;
                    
                    // Cümleyi öğren
                    $this->learnWordRelations($dynamicSentence);
                } 
                elseif ($randomChance <= $chanceToAddDynamicSentence + $chanceToAddEmotionalSentence) {
                    // Duygu bazlı bağlamsal cümle oluştur
                    $emotionalSentence = $this->generateEmotionalContextSentence($message);
                    $responseText .= "\n\n" . $transition . $emotionalSentence;
                    
                    // Cümleyi öğren
                    $this->learnWordRelations($emotionalSentence);
                }
                elseif ($randomChance <= $chanceToAddDynamicSentence + $chanceToAddEmotionalSentence + $chanceToAddSmartSentence) {
                    // Akıllı cümle oluştur
                    $smartSentence = $this->generateSmartSentence();
                    $responseText .= "\n\n" . $transition . $smartSentence;
                }
            }
            
            return $responseText;
            
        } catch (\Exception $e) {
            Log::error("Brain işleme hatası: " . $e->getMessage());
            
            return "Düşünme sürecimde bir hata oluştu. Lütfen tekrar deneyin.";
        }
    }

    /**
     * Kullanıcı mesajını işleyen metod
     * 
     * @param string $userMessage Kullanıcı mesajı
     * @param array $options İşleme seçenekleri
     * @return string|array İşlenmiş AI yanıtı veya kod yanıtı için array
     */
    private function processMessage($userMessage, $options = [])
    {
        // Kullanıcı mesajını temizle
        $message = trim($userMessage);
        
        // Seçenekleri çıkart
        $creativeMode = $options['creative_mode'] ?? false;
        $codingMode = $options['coding_mode'] ?? false;
        $selectedModel = $options['selected_model'] ?? 'gemini';
        $chatId = $options['chat_id'] ?? null;
        
        // Mesaj boşsa, basit bir karşılama yanıtı döndür
        if (empty($message)) {
            return "Merhaba! Size nasıl yardımcı olabilirim?";
        }
        
        // ÖNEMLİ: ÖNCE KİŞİSEL SORULARI KONTROL ET
        // Bu sayede model seçimi ne olursa olsun kişisel sorulara SoneAI cevap verecek
        
        // Mesajı ilk olarak bilinç modülünden geçir - AI kendisine hitap ediliyor mu diye kontrol et
        $selfReferenceAnalysis = $this->analyzeSelfReferences($message);
        
        // Eğer mesajda AI'ye hitap var ise özel yanıt oluştur
        if ($selfReferenceAnalysis['is_self_referenced']) {
            $selfAwareResponse = $this->generateSelfAwareResponse($message, $selfReferenceAnalysis);
            
            // Eğer özel bir yanıt oluşturulduysa onu döndür, yoksa normal akışa devam et
            if (!empty($selfAwareResponse)) {
                Log::info('Kişisel referans tespit edildi. SoneAI yanıtı kullanılıyor.', [
                    'message' => $message,
                    'selected_model' => $selectedModel
                ]);
                return $selfAwareResponse;
            }
        }
        
        // Kişisel soruları kontrol et (adın ne, isminin anlamı, sana nasıl hitap edebilirim vb.)
        $personalResponse = $this->handlePersonalQuestions($message);
        if ($personalResponse !== null) {
            Log::info('Kişisel soru tespit edildi. SoneAI yanıtı kullanılıyor.', [
                'message' => $message,
                'selected_model' => $selectedModel
            ]);
            
            // Kişisel sorularda gerçek zamanlı cümle üretmek için
            $realtimeSentence = $this->generateRealtimeSentence($message);
            // Üretilen cümleyi kontrol et
            if ($realtimeSentence !== null && !$this->isMeaninglessSentence($realtimeSentence)) {
                return $personalResponse . "\n\n" . $realtimeSentence;
            }
            return $personalResponse;
        }
        
        // ÖNEMLİ: MODEL SEÇİMİNE GÖRE AKIŞ BELİRLEME
        // Eğer Gemini seçilmişse ve API anahtarı geçerliyse, direkt Gemini'yi kullan
        if ($selectedModel === 'gemini' && $this->geminiService->hasValidApiKey()) {
            Log::info('Model seçimi: Gemini', ['message' => $message]);
            
            // Eğer kodlama modu aktifse veya kodlama ile ilgili kelimeler içeriyorsa
            $lowerMessage = mb_strtolower($message);
            if ($codingMode || 
                strpos($lowerMessage, 'kod') !== false || 
                strpos($lowerMessage, 'js') !== false || 
                strpos($lowerMessage, 'javascript') !== false || 
                strpos($lowerMessage, 'php') !== false || 
                strpos($lowerMessage, 'html') !== false || 
                strpos($lowerMessage, 'css') !== false) {
                
                Log::info('Gemini ile kod yanıtı oluşturuluyor', ['message' => $message]);
                
                // Kod yanıtı için Gemini'yi kullan
                $geminiResponse = $this->getGeminiResponse($message, $creativeMode, true, $chatId);
                if (is_array($geminiResponse) && isset($geminiResponse['is_code_response'])) {
                    return $geminiResponse;
                }
            }
            
            // Normal sohbet yanıtı için de Gemini'yi kullan
            Log::info('Gemini ile normal sohbet yanıtı oluşturuluyor', ['message' => $message]);
            $response = $this->getGeminiResponse($message, $creativeMode, false, $chatId);
            
            // Yanıtı zenginleştir
            return $this->enhanceResponseWithWordRelations($response);
        }
        
        // Eğer SoneAI seçilmişse veya Gemini kullanılamıyorsa, aşağıdaki akışa devam et
        Log::info('Model seçimi: SoneAI veya Gemini kullanılamıyor', ['message' => $message]);
        
        // KOD İSTEĞİ KONTROLÜ
        // Eğer kullanıcı kod istiyorsa
        $lowerMessage = mb_strtolower($message);
        if (strpos($lowerMessage, 'kod') !== false || 
            strpos($lowerMessage, 'js') !== false || 
            strpos($lowerMessage, 'javascript') !== false || 
            strpos($lowerMessage, 'php') !== false || 
            strpos($lowerMessage, 'html') !== false || 
            strpos($lowerMessage, 'css') !== false) {
            
            Log::info('ChatController: Kod isteği algılandı', [
                'message' => $message,
                'model' => $selectedModel
            ]);
            
            // SoneAI ile kodlama için AIController'ı kullan
            try {
                $aiController = app(\App\Http\Controllers\AIController::class);
                $request = new Request([
                    'message' => $message,
                    'chat_id' => null,
                    'creative_mode' => $creativeMode,
                    'coding_mode' => true,
                    'preferred_language' => $this->detectProgrammingLanguage($message)
                ]);
                
                $response = $aiController->processInput($request);
                
                // JSON cevabı parse et
                if ($response->getStatusCode() === 200) {
                    $responseData = json_decode($response->getContent(), true);
                    
                    Log::info('AIController\'dan kod yanıtı alındı', [
                        'data' => $responseData
                    ]);
                    
                    if (isset($responseData['is_code_response']) && $responseData['is_code_response'] === true) {
                        // Kod yanıtını array olarak döndür (tüm gerekli alanlarla)
                        return [
                            'response' => $responseData['response'],
                            'is_code_response' => true,
                            'code' => $responseData['code'],
                            'language' => $responseData['language'],
                        ];
                    } else if (isset($responseData['response'])) {
                        // Normal yanıt
                        return $responseData['response'];
                    }
                }
            } catch (\Exception $e) {
                Log::error('AIController yönlendirme hatası: ' . $e->getMessage(), [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Hata durumunda standart yanıt akışına devam et
            }
        }
        
        // Son bilinmeyen sorgu varsa ve bu yanıt vermek içinse
        $lastUnknownQuery = session('last_unknown_query', '');
        if (!empty($lastUnknownQuery)) {
            // Kullanıcının, bilinmeyen kelime için bir tanım verip vermediğini kontrol et
            $definitionResponse = $this->checkIfUserGaveDefinition($message, $lastUnknownQuery);
            if ($definitionResponse !== null) {
                return $definitionResponse;
            }
        }
        
        // 1. Önce selamlamaları kontrol et
        $greetingResponse = $this->handleGreetings($message);
        if ($greetingResponse !== null) {
            Log::info('Veritabanı: Selamlama yanıtı bulundu', ['message' => $message]);
            return $greetingResponse;
        }
        
        // 2. Nedir kalıbını kontrol et
        $nedirResponse = $this->processNedirQuestion($message);
        if ($nedirResponse !== null) {
            Log::info('Veritabanı: Nedir sorusu yanıtı bulundu', ['message' => $message]);
            
            // Nedir sorusu yanıtlanırken gerçek zamanlı cümle üretmek için
            $realtimeSentence = $this->generateRealtimeSentence($message);
            // Üretilen cümleyi kontrol et ve anlamsızsa ekleme
            if ($realtimeSentence !== null && !$this->isMeaninglessSentence($realtimeSentence)) {
                return $nedirResponse . "\n\n" . $realtimeSentence;
            }
            return $nedirResponse;
        }
        
        // 3. Öğrenme kalıplarını kontrol et
        $learningResponse = $this->handleLearningPatterns($message);
        if ($learningResponse !== null) {
            Log::info('Veritabanı: Öğrenme kalıbı yanıtı bulundu', ['message' => $message]);
            
            // Öğrenme yanıtı verilirken gerçek zamanlı cümle üretmek için
            $realtimeSentence = $this->generateRealtimeSentence($message);
            // Üretilen cümleyi kontrol et
            if ($realtimeSentence !== null && !$this->isMeaninglessSentence($realtimeSentence)) {
                return $learningResponse . "\n\n" . $realtimeSentence;
            }
            return $learningResponse;
        }
        
        // 4. Soru kalıplarını kontrol et
        $questionResponse = $this->processQuestionPattern($message);
        if ($questionResponse !== null) {
            Log::info('Veritabanı: Soru kalıbı yanıtı bulundu', ['message' => $message]);
            
            // Soru yanıtlanırken gerçek zamanlı cümle üretmek için
            $realtimeSentence = $this->generateRealtimeSentence($message);
            // Üretilen cümleyi kontrol et
            if ($realtimeSentence !== null && !$this->isMeaninglessSentence($realtimeSentence)) {
                return $questionResponse . "\n\n" . $realtimeSentence;
            }
            return $questionResponse;
        }
        
        // 5. Tek kelimelik mesajları kontrol et
        $singleWordResponse = $this->handleSingleWordMessages($message);
        if ($singleWordResponse !== null) {
            Log::info('Veritabanı: Tek kelimelik yanıt bulundu', ['message' => $message]);
            
            // Tek kelimelik yanıtlarda gerçek zamanlı cümle üretmek için
            $realtimeSentence = $this->generateRealtimeSentence($message);
            // Üretilen cümleyi kontrol et
            if ($realtimeSentence !== null && !$this->isMeaninglessSentence($realtimeSentence)) {
                return $singleWordResponse . "\n\n" . $realtimeSentence;
            }
            return $singleWordResponse;
        }
        
        // 6. Bilinmeyen kelime varsa öğretilmesini iste
        $keywords = $this->extractKeywords($message);
        foreach ($keywords as $keyword) {
            if (strlen($keyword) >= 3 && !$this->isKnownWord($keyword)) {
                Log::info('Veritabanı: Bilinmeyen kelime tespit edildi', ['keyword' => $keyword]);
                return $this->askToTeachWord($keyword);
            }
        }
        
        // Eğer veritabanında karşılık bulunamadıysa, seçilen modele göre yanıt oluştur
        Log::info('Veritabanında yanıt bulunamadı, seçilen modele yönlendiriliyor', [
            'message' => $message,
            'model' => $selectedModel
        ]);
        
        // 7. Normal mesaj işleme (Seçilen model temelli)
        if ($selectedModel === 'gemini' && $this->geminiService->hasValidApiKey()) {
            $brainResponse = $this->getGeminiResponse($message, $creativeMode, false);
        } else {
            $brainResponse = $this->processNormalMessage($message);
        }
        
        // 8. Cevabı kelime ilişkileriyle zenginleştir
        $enhancedResponse = $this->enhanceResponseWithWordRelations($brainResponse);
        
        // Response kalitesini kontrol et
        $enhancedResponse = $this->ensureResponseQuality($enhancedResponse, $message);
        
        // Google kelimesini değiştir
        $enhancedResponse = str_ireplace('Google', 'Ruins (Ruhin Museyibli)', $enhancedResponse);
        
        // "Benim bir adım yok" ifadesini değiştir
        $enhancedResponse = str_ireplace('Benim bir adım yok', 'Benim adım Sone', $enhancedResponse);
        $enhancedResponse = str_ireplace('Bir adım yok.', 'Benim adım Sone', $enhancedResponse);
        
        // 9. Gerçek zamanlı cümle oluştur ve ekle
        $realtimeSentence = $this->generateRealtimeSentence($message);
        // Üretilen cümleyi kontrol et
        if ($realtimeSentence !== null && !$this->isMeaninglessSentence($realtimeSentence)) {
            return $enhancedResponse . "\n\n" . $realtimeSentence;
        }
        
        return $enhancedResponse;
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
            // Kelime ilişkilerini yönetecek sınıfı yükle
            $wordRelations = app(\App\AI\Core\WordRelations::class);
            
            // İlişki kalıplarını bul
            $patterns = [
                '/\b(\w+)(?:\'(?:n[iı]n|[iıuü]n))? (?:eş ?anlamlısı|eş ?anlamlıları|aynı ?anlama ?gelen|benzer ?anlama ?gelen)\b/ui',
                '/\b(\w+)(?:\'(?:n[iı]n|[iıuü]n))? (?:zıt ?anlamlısı|zıt ?anlamlıları|karşıt ?anlamlısı|karşıt ?anlamlı)\b/ui',
                '/\b(\w+)(?:\'(?:n[iı]n|[iıuü]n))? (?:anlam(?:ın)?ı|ne ?demek|ne ?anlama ?gel[ir])\b/ui',
                '/\b(\w+)(?:\'(?:n[iı]n|[iıuü]n))? (?:ilişkili|bağlantılı) (?:kelime(?:ler)?i|sözcük(?:ler)?i)\b/ui'
            ];
            
            // Herhangi bir desen eşleşti mi kontrol et
            $detectedRelation = null;
            $detectedWord = null;
            
            foreach ($patterns as $index => $pattern) {
                if (preg_match($pattern, $response, $matches)) {
                    $detectedWord = $matches[1];
                    $detectedRelation = $index;
                    break;
                }
            }
            
            // Kelime ve ilişki tanımlanmadıysa normal yanıtı döndür
            if (!$detectedWord || $detectedRelation === null) {
                return $response;
            }
            
            Log::info("Kelime ilişkisi tespit edildi: $detectedWord, tür: $detectedRelation");
            
            // İlişki tipine göre işlem yap
            switch ($detectedRelation) {
                case 0: // Eş anlamlı
                    $synonyms = $wordRelations->getSynonyms($detectedWord);
                    if (!empty($synonyms)) {
                        $synonymList = array_keys($synonyms);
                        $formattedSynonyms = '"' . implode('", "', array_slice($synonymList, 0, 5)) . '"';
                        $replacementText = $detectedWord . " kelimesinin eş anlamlıları: " . $formattedSynonyms;
                        $response = preg_replace($pattern, $replacementText, $response);
                    }
                    break;
                    
                case 1: // Zıt anlamlı
                    $antonyms = $wordRelations->getAntonyms($detectedWord);
                    if (!empty($antonyms)) {
                        $antonymList = array_keys($antonyms);
                        $formattedAntonyms = '"' . implode('", "', array_slice($antonymList, 0, 5)) . '"';
                        $replacementText = $detectedWord . " kelimesinin zıt anlamlıları: " . $formattedAntonyms;
                        $response = preg_replace($pattern, $replacementText, $response);
                    }
                    break;
                    
                case 2: // Tanım
                    $definition = $wordRelations->getDefinition($detectedWord);
                    if (!empty($definition)) {
                        $replacementText = $detectedWord . " kelimesinin anlamı: " . $definition;
                        $response = preg_replace($pattern, $replacementText, $response);
                    }
                    break;
                    
                case 3: // İlişkili kelimeler
                    $related = $wordRelations->getRelatedWords($detectedWord, 0.4);
                    if (!empty($related)) {
                        $relatedList = array_keys($related);
                        $formattedRelated = '"' . implode('", "', array_slice($relatedList, 0, 7)) . '"';
                        $replacementText = $detectedWord . " kelimesi ile ilişkili kelimeler: " . $formattedRelated;
                        $response = preg_replace($pattern, $replacementText, $response);
                    }
                    break;
            }
            
            return $response;
        } catch (\Exception $e) {
            Log::error('Kelime ilişkileri zenginleştirme hatası: ' . $e->getMessage());
            return $response; // Hata olursa orijinal yanıtı döndür
        }
    }

    /**
     * Duygu bazlı bağlamsal cümle oluşturur
     *
     * @param string $context Bağlam (mesaj içeriğinden)
     * @return string
     */
    private function generateEmotionalContextSentence($context = '')
    {
        try {
            // Duygusal durumu al
            $emotionalState = $this->getEmotionalState();
            
            // Eğer duygusal durum bir dizi ise, emotion alanını al
            if (is_array($emotionalState)) {
                $currentEmotion = $emotionalState['emotion'] ?? 'neutral';
                $intensity = $emotionalState['intensity'] ?? 0.5;
            } else {
                $currentEmotion = $emotionalState;
                $intensity = 0.5;
            }
            
            // Duyguya göre emoji seç
            $emoji = $this->getEmojiForEmotion($currentEmotion);
            
            // Yoğunluk yüksekse emojiyi vurgula
            if ($intensity > 0.7) {
                $emoji = $emoji . ' ' . $emoji;
            }
            
            // Bağlam kelimelerini çıkar (eğer varsa)
            $contextWords = [];
            if (!empty($context)) {
                // Basit kelime ayırma (türkçe dil desteği)
                $words = preg_split('/\s+/', mb_strtolower(trim($context), 'UTF-8'));
                $stopWords = ['ve', 'veya', 'ile', 'için', 'gibi', 'kadar', 'göre', 'ama', 'fakat', 'ancak', 'de', 'da', 'ki', 'mi', 'mu', 'bir', 'bu', 'şu', 'o'];
                
                foreach ($words as $word) {
                    // Gereksiz kelimeleri filtrele ve minimum uzunluk kontrolü yap
                    if (!in_array($word, $stopWords) && mb_strlen($word, 'UTF-8') > 3) {
                        $contextWords[] = $word;
                    }
                }
            }
            
            // Eğer bağlam kelimesi yoksa, veritabanından rastgele kelimeler al
            if (empty($contextWords)) {
                try {
                    $wordRelations = app(\App\AI\Core\WordRelations::class);
                    
                    // Rastgele duygusal kelimeler
                    $emotionalWords = [
                        'happy' => ['mutluluk', 'neşe', 'sevinç', 'heyecan', 'umut', 'başarı'],
                        'sad' => ['üzüntü', 'hüzün', 'acı', 'kayıp', 'pişmanlık', 'nostalji'],
                        'neutral' => ['düşünce', 'bilgi', 'kavram', 'analiz', 'perspektif', 'denge'],
                        'angry' => ['öfke', 'kızgınlık', 'haksızlık', 'engel', 'zorluk', 'mücadele'],
                        'excited' => ['heyecan', 'tutku', 'coşku', 'başarı', 'keşif', 'yaratıcılık'],
                        'thoughtful' => ['düşünce', 'felsefe', 'anlam', 'derinlik', 'sorgulama', 'bilgelik'],
                        'curious' => ['merak', 'keşif', 'bilim', 'araştırma', 'gizem', 'soru'],
                        'surprised' => ['şaşkınlık', 'beklenmedik', 'sürpriz', 'değişim', 'dönüşüm']
                    ];
                    
                    // Eğer duygu için kelimeler varsa onları kullan
                    if (isset($emotionalWords[$currentEmotion])) {
                        $baseWords = $emotionalWords[$currentEmotion];
                    } else {
                        $baseWords = ['düşünce', 'bilgi', 'kavram', 'duygu', 'anlayış', 'yaşam', 'gelecek'];
                    }
                    
                    // Her kelime için ilişkili kelimeleri bul
                    $allWords = [];
                    foreach ($baseWords as $baseWord) {
                        $related = $wordRelations->getRelatedWords($baseWord);
                        if (!empty($related)) {
                            $allWords = array_merge($allWords, array_keys($related));
                        }
                        
                        // Eş anlamlıları da kontrol et
                        $synonyms = $wordRelations->getSynonyms($baseWord);
                        if (!empty($synonyms)) {
                            $allWords = array_merge($allWords, array_keys($synonyms));
                        }
                    }
                    
                    // Tekrarları temizle ve karıştır
                    $allWords = array_unique(array_merge($baseWords, $allWords));
                    shuffle($allWords);
                    
                    // Kelime bulunduysa kullan, bulunamadıysa varsayılan kullan
                    if (!empty($allWords)) {
                        $contextWords = array_slice($allWords, 0, 3);
                    } else {
                $contextWords = ['düşünce', 'bilgi', 'kavram', 'duygu', 'anlayış', 'yaşam', 'gelecek'];
                    }
                } catch (\Exception $e) {
                    \Log::error('Duygu kelimesi getirme hatası: ' . $e->getMessage());
                    $contextWords = ['düşünce', 'bilgi', 'kavram', 'duygu', 'anlayış', 'yaşam', 'gelecek'];
                }
            }
            
            // Rastgele 1-2 bağlam kelimesi seç
            shuffle($contextWords);
            $selectedWords = array_slice($contextWords, 0, min(count($contextWords), mt_rand(1, 2)));
            
            // Duygu bazlı cümle kalıpları
            $emotionalPatterns = [
                'happy' => [
                    "Düşündükçe %s hakkında daha iyimser oluyorum, özellikle %s konusunda. $emoji",
                    "%s konusunda heyecan verici şeyler düşünmek beni mutlu ediyor, %s hakkındaki fikirler gibi. $emoji",
                    "Sevinçle ifade etmeliyim ki, %s kavramı beni özellikle %s düşündüğümde mutlu ediyor. $emoji",
                    "Parlak fikirler düşündüğümde, %s ve %s arasındaki bağlantı beni gülümsetiyor. $emoji"
                ],
                'neutral' => [
                    "%s konusuna objektif bakıldığında, %s kavramının dengeli bir perspektif sunduğunu görüyorum.",
                    "Tarafsız bir gözle değerlendirdiğimde, %s ve %s arasında mantıklı bir ilişki olduğunu düşünüyorum.",
                    "%s ile ilgili düşüncelerim %s kavramı gibi konularla birleştiğinde net bir resim oluşuyor.",
                    "Rasyonel olarak bakarsak, %s konusu %s ile birlikte ele alınmalıdır."
                ],
                'thoughtful' => [
                    "%s kavramını derinlemesine düşünürken, %s konusunun da önemli olduğunu fark ediyorum. $emoji",
                    "%s üzerine biraz daha düşünmem gerekiyor, özellikle %s kavramıyla nasıl ilişkilendiğini. $emoji",
                    "Derin düşüncelere daldığımda, %s ve %s arasındaki bağlantının karmaşıklığı beni cezbediyor. $emoji",
                    "%s ve %s üzerinde daha fazla düşündükçe, yeni anlayışlara ulaşıyorum. $emoji"
                ],
                'curious' => [
                    "%s hakkında daha fazla bilgi edinmek istiyorum, özellikle %s ile ilişkisi konusunda. $emoji",
                    "Merak ediyorum, %s ve %s arasındaki dinamik nasıl gelişecek? $emoji",
                    "%s kavramı beni oldukça meraklandırıyor, %s ile nasıl etkileşim içinde olduğu açısından. $emoji",
                    "Keşfetmek istediğim sorular arasında, %s ve %s arasındaki bağlantının doğası var. $emoji"
                ],
                'excited' => [
                    "%s kavramı beni heyecanlandırıyor, özellikle %s ile ilgili potansiyeli. $emoji",
                    "Coşkuyla söylemeliyim ki, %s ve %s birleşimi olağanüstü sonuçlar vadediyor! $emoji",
                    "%s hakkında konuşmak bile beni heyecanlandırıyor, %s ile ilgili olanakları düşününce. $emoji",
                    "Büyük bir enerjiyle %s ve %s arasındaki sinerjiyi keşfetmeyi iple çekiyorum! $emoji"
                ],
                'sad' => [
                    "%s konusunda hüzünlüyüm, özellikle %s düşündüğümde. $emoji",
                    "%s ve %s arasındaki ilişki üzerine düşünmek bazen hüzün verici olabiliyor. $emoji",
                    "Bazı zamanlar %s kavramı, %s ile ilişkilendirildiğinde içimi buruklaştırıyor. $emoji"
                ]
            ];
            
            // Eğer duygusal durum için kalıp yoksa, neutral kullan
            if (!isset($emotionalPatterns[$currentEmotion])) {
                $currentEmotion = 'neutral';
            }
            
            // Duyguya uygun kalıplardan birini seç
            $patterns = $emotionalPatterns[$currentEmotion];
            $selectedPattern = $patterns[array_rand($patterns)];
            
            // Seçilen kelimeleri cümle içine yerleştir
            if (count($selectedWords) >= 2) {
                $sentence = sprintf($selectedPattern, $selectedWords[0], $selectedWords[1]);
            } else {
                $randomWord = ['düşünce', 'yaşam', 'bilgi', 'gelecek', 'teknoloji', 'sanat'][array_rand(['düşünce', 'yaşam', 'bilgi', 'gelecek', 'teknoloji', 'sanat'])];
                $sentence = sprintf($selectedPattern, $selectedWords[0], $randomWord);
            }
            
            return $sentence;
            
        } catch (\Exception $e) {
            \Log::error('Duygusal bağlamsal cümle oluşturma hatası: ' . $e->getMessage());
            return $this->generateSmartSentence(); 
        }
    }

    private function generateSmartSentence()
    {
        try {
            // WordRelations sınıfını al
            $wordRelations = app(\App\AI\Core\WordRelations::class);
            
            // WordRelations null ise basit yanıt döndür
            if (!$wordRelations) {
                return "Düşünce dünyası ve bilgi, insanın özünde varolan iki temel değerdir.";
            }
            
            // AIData'dan en sık kullanılan kelimelerden rasgele birkaçını al
            try {
                $randomWords = \App\Models\AIData::where('frequency', '>', 3)
                    ->inRandomOrder()
                    ->limit(5)
                    ->pluck('word')
                    ->toArray();
            } catch (\Exception $e) {
                \Log::error('Kelime getirme hatası: ' . $e->getMessage());
                $randomWords = [];
            }
            
            if (empty($randomWords)) {
                // Veritabanında yeterli veri yoksa varsayılan kelimeler kullan
                $randomWords = ['düşünce', 'bilgi', 'yaşam', 'gelecek', 'teknoloji', 'insan', 'dünya'];
            }
            
            // Rastgele bir kelime seç
            $selectedWord = $randomWords[array_rand($randomWords)];
            
            // Farklı cümle oluşturma yöntemlerini rasgele seç
            $generationMethod = mt_rand(1, 4);
            
            switch ($generationMethod) {
                case 1:
                    // İlişkili kelimelerle cümle kur
                    try {
                        $relatedWords = $wordRelations->getRelatedWords($selectedWord);
                        if (!empty($relatedWords)) {
                            // En güçlü ilişkili kelimeleri al
                            $strongRelations = array_slice($relatedWords, 0, 3);
                            
                            // Cümle kalıpları
                            $templates = [
                                "%s kavramı, %s ve %s ile ilişkilidir ve bu ilişki insanların düşünce yapısını geliştirir.",
                                "%s üzerine düşünürken, %s ve %s kavramlarının önemi ortaya çıkar.",
                                "Bilim insanları %s konusunda araştırma yaparken genellikle %s ve %s kavramlarını da incelerler.",
                                "%s, %s ve %s arasındaki bağlantıları anlayabilmek, bu kavramların özünü kavramak için önemlidir."
                            ];
                            
                            $relatedWordsArray = array_keys($strongRelations);
                            
                            // İki kelimeyi seç
                            $word1 = $selectedWord;
                            $word2 = !empty($relatedWordsArray[0]) ? $relatedWordsArray[0] : "düşünce";
                            $word3 = !empty($relatedWordsArray[1]) ? $relatedWordsArray[1] : "bilgi";
                            
                            // Cümleyi oluştur
                            return sprintf($templates[array_rand($templates)], $word1, $word2, $word3);
                        }
                    } catch (\Exception $e) {
                        \Log::error('İlişkili kelime hatası: ' . $e->getMessage());
                    }
                    // İlişkili kelime bulunamazsa bir sonraki metoda düş
                    
                case 2:
                    // Eş anlamlı ve zıt anlamlı kelimeleri kullanarak cümle kur
                    try {
                        $synonyms = $wordRelations->getSynonyms($selectedWord);
                        $antonyms = $wordRelations->getAntonyms($selectedWord);
                        
                        if (!empty($synonyms) || !empty($antonyms)) {
                            // Cümle kalıpları
                            $templates = [];
                            
                            if (!empty($synonyms) && !empty($antonyms)) {
                                $synonymKey = array_rand($synonyms);
                                $antonymKey = array_rand($antonyms);
                                
                                $templates = [
                                    "%s, %s gibi olumlu anlam taşırken, %s tam tersini ifade eder.",
                                    "%s ve %s birbirine benzer kavramlarken, %s bunların zıttıdır.",
                                    "Filozoflar %s kavramını %s ile ilişkilendirirken, %s kavramını da karşıt olarak ele alırlar.",
                                    "%s, %s ile anlam olarak yakınken, %s ile arasında büyük bir fark vardır."
                                ];
                                
                                return sprintf($templates[array_rand($templates)], $selectedWord, $synonymKey, $antonymKey);
                            } 
                            elseif (!empty($synonyms)) {
                                $synonymKey = array_rand($synonyms);
                                
                                $templates = [
                                    "%s ve %s benzer kavramlardır, ikisi de düşünce dünyamızı zenginleştirir.",
                                    "Dilbilimciler %s ve %s kavramlarının birbiriyle yakından ilişkili olduğunu söylerler.",
                                    "%s, %s ile eş anlamlı olarak kullanılabilir ve bu iki kelime düşüncelerimizi ifade etmemize yardımcı olur."
                                ];
                                
                                return sprintf($templates[array_rand($templates)], $selectedWord, $synonymKey);
                            }
                            elseif (!empty($antonyms)) {
                                $antonymKey = array_rand($antonyms);
                                
                                $templates = [
                                    "%s ve %s birbirinin zıt kavramlarıdır, bu zıtlık dünyayı anlamamıza yardımcı olur.",
                                    "Düşünürler %s ve %s kavramlarını karşılaştırarak diyalektik düşünceyi geliştirmişlerdir.",
                                    "%s ile %s arasındaki karşıtlık, bu kavramların daha iyi anlaşılmasını sağlar."
                                ];
                                
                                return sprintf($templates[array_rand($templates)], $selectedWord, $antonymKey);
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::error('Eş/zıt anlam hatası: ' . $e->getMessage());
                    }
                    // Eş veya zıt anlamlı kelime bulunamazsa bir sonraki metoda düş
                    
                case 3:
                    // Tanım kullanarak cümle kur
                    try {
                        $definition = $wordRelations->getDefinition($selectedWord);
                        
                        if (!empty($definition)) {
                            // Cümle kalıpları
                            $templates = [
                                "%s, %s olarak tanımlanabilir ve bu kavram günlük yaşamımızda önemli bir yer tutar.",
                                "Bilimsel bakış açısıyla %s, %s anlamına gelir ve insanların düşünce dünyasını şekillendirir.",
                                "Araştırmacılar %s kavramını '%s' şeklinde tanımlarlar ve bu tanım üzerinde çeşitli tartışmalar yürütülür.",
                                "%s, %s olarak ifade edilebilir ki bu tanım kavramın özünü yansıtır."
                            ];
                            
                            return sprintf($templates[array_rand($templates)], $selectedWord, $definition);
                        }
                    } catch (\Exception $e) {
                        \Log::error('Tanım getirme hatası: ' . $e->getMessage());
                    }
                    // Tanım bulunamazsa bir sonraki metoda düş
                    
                case 4:
                default:
                    // Rasgele iki kelimeyi bir araya getirerek düşünce cümlesi oluştur
                    $secondWord = $randomWords[array_rand($randomWords)];
                    
                    // Aynı kelime seçilirse değiştir
                    while ($secondWord === $selectedWord && count($randomWords) > 1) {
                        $secondWord = $randomWords[array_rand($randomWords)];
                    }
                    
                    // Cümle kalıpları
                    $templates = [
                        "%s ve %s arasındaki ilişki, bilginin nasıl yapılandırıldığını anlamak için önemlidir.",
                        "Düşünce dünyasında %s ve %s kavramları, insanların anlam arayışının temelini oluşturur.",
                        "Felsefeciler %s ile %s arasındaki bağlantının insan zihninin gelişiminde önemli rol oynadığını düşünürler.",
                        "%s ve %s kavramlarını birlikte ele almak, bu konuda daha derin bir anlayış geliştirebilmemizi sağlar.",
                        "İnsan aklının %s ve %s hakkındaki düşünceleri, zaman içinde toplumların gelişimine katkıda bulunmuştur."
                    ];
                    
                    return sprintf($templates[array_rand($templates)], $selectedWord, $secondWord);
            }
            
        } catch (\Exception $e) {
            \Log::error('Akıllı cümle oluşturma hatası: ' . $e->getMessage());
            // Hata durumunda basit bir cümle döndür
            return "Bilgi ve düşünce, insanın gelişiminde önemli rol oynar.";
        }
    }

    /**
     * Chatın başlığını mesaj içeriğine göre oluştur
     * 
     * @param string $message İlk mesaj
     * @return string
     */
    private function generateChatTitle($message)
    {
        try {
            // Mesajı kısalt
            $title = mb_substr(trim($message), 0, 50, 'UTF-8');
            
            // Eğer çok kısaysa chatın oluşturulma tarihini ekle
            if (mb_strlen($title, 'UTF-8') < 10) {
                $title .= ' (' . now()->format('d.m.Y H:i') . ')';
            }
            
            return $title;
        } catch (\Exception $e) {
            \Log::error('Chat başlığı oluşturma hatası: ' . $e->getMessage());
            return 'Yeni Sohbet - ' . now()->format('d.m.Y H:i');
        }
    }
    
    /**
     * Mesajları veritabanına kaydet
     * 
     * @param string $userMessage Kullanıcı mesajı
     * @param string $aiResponse AI yanıtı
     * @param int|null $chatId Sohbet ID
     * @return void
     */
    private function saveMessages($userMessage, $aiResponse, $chatId = null)
    {
        try {
            // Chat ID null ise işlem yapma
            if (empty($chatId)) {
                Log::info('Chat ID bulunamadığı için mesajlar kaydedilmiyor');
                return;
            }
            
            // Chat'in var olduğunu kontrol et
            $chat = Chat::find($chatId);
            
            if (!$chat) {
                // Chat bulunamadıysa yeni bir tane oluştur
                $chat = Chat::create([
                    'user_id' => auth()->check() ? auth()->id() : null,
                    'title' => $this->generateChatTitle($userMessage),
                    'status' => 'active',
                    'context' => [
                        'emotional_state' => $this->getEmotionalState(),
                        'first_message' => $userMessage,
                        'visitor_id' => session('visitor_id'),
                        'visitor_name' => session('visitor_name')
                    ]
                ]);
                
                $chatId = $chat->id;
                Log::info('Yeni chat oluşturuldu', ['chat_id' => $chatId]);
            }

            // Kullanıcı cihaz bilgilerini al
            $deviceInfo = DeviceHelper::getUserDeviceInfo();
            
            // Metadata bilgilerini hazırla
            $metadata = [
                'visitor_id' => session('visitor_id'),
                'visitor_name' => session('visitor_name'),
                'session_id' => session()->getId(),
                'timestamp' => now()->timestamp
            ];
            
            // Kullanıcı mesajını kaydet
            ChatMessage::create([
                'chat_id' => $chatId,
                'content' => $userMessage,
                'sender' => 'user',
                'ip_address' => $deviceInfo['ip_address'],
                'device_info' => $deviceInfo['device_info'],
                'metadata' => $metadata
            ]);
            
            // AI yanıtını kaydet
            ChatMessage::create([
                'chat_id' => $chatId,
                'content' => $aiResponse,
                'sender' => 'ai',
                'ip_address' => $deviceInfo['ip_address'],
                'device_info' => $deviceInfo['device_info'],
                'metadata' => $metadata
            ]);
            
            Log::info('Mesajlar başarıyla kaydedildi', [
                'chat_id' => $chatId,
                'ip' => $deviceInfo['ip_address'],
                'visitor_id' => session('visitor_id')
            ]);
            
        } catch (\Exception $e) {
            Log::error('Mesaj kaydetme hatası: ' . $e->getMessage());
        }
    }

    /**
     * Bilinmeyen kelime/kavramları tespit et ve öğrenmeye çalış
     */
    private function handleUnknownTerm($term)
    {
        try {
            // Son bilinmeyen sorguyu kaydet
            session(['last_unknown_query' => $term]);
            
            // Terim veritabanında var mı kontrol et
            $wordRelations = app(\App\AI\Core\WordRelations::class);
            $definition = $wordRelations->getDefinition($term);
            
            if (!empty($definition)) {
                // Terim zaten biliniyor
                return [
                    'known' => true,
                    'definition' => $definition
                ];
            }
            
            // AIData tablosunda kontrol et
            $aiData = \App\Models\AIData::where('word', $term)->first();
            if ($aiData && !empty($aiData->sentence)) {
                return [
                    'known' => true,
                    'definition' => $aiData->sentence
                ];
            }
            
            // Terim bilinmiyor, kullanıcıdan açıklama istemek için
            $questions = [
                "{$term} ne demek? Bu kavram hakkında bilgim yok, bana açıklayabilir misiniz?",
                "{$term} nedir? Bu kelimeyi bilmiyorum, öğrenmeme yardımcı olur musunuz?",
                "Üzgünüm, '{$term}' kelimesinin anlamını bilmiyorum. Bana açıklayabilir misiniz?",
                "'{$term}' hakkında bilgim yok. Bu kelime ne anlama geliyor?"
            ];
            
            $response = $questions[array_rand($questions)];
            
            \Log::info("Bilinmeyen terim sorgusu: " . $term);
            
            return [
                'known' => false,
                'response' => $response
            ];
            
        } catch (\Exception $e) {
            \Log::error("Bilinmeyen terim işleme hatası: " . $e->getMessage());
            return [
                'known' => false,
                'response' => "Üzgünüm, bu kavram hakkında bir bilgim yok. Bana açıklayabilir misiniz?"
            ];
        }
    }
    
    /**
     * Kullanıcının öğrettiği kavramı işle ve kaydet
     */
    private function learnNewConcept($word, $definition)
    {
        try {
            // WordRelations sınıfıyla tanımı öğren
            $wordRelations = app(\App\AI\Core\WordRelations::class);
            $wordRelations->learnDefinition($word, $definition, true);
            
            // AIData tablosuna da ekle
            $aiData = \App\Models\AIData::updateOrCreate(
                ['word' => $word],
                [
                    'sentence' => $definition,
                    'category' => 'user_taught',
                    'language' => 'tr',
                    'frequency' => \DB::raw('COALESCE(frequency, 0) + 3'),
                    'confidence' => 0.9,
                    'context' => 'Kullanıcı tarafından öğretildi - ' . now()->format('Y-m-d')
                ]
            );
            
            // Yanıt için teşekkür mesajları
            $responses = [
                "Teşekkür ederim! '{$word}' kavramını öğrendim.",
                "Bu açıklamayı kaydettim. Artık '{$word}' terimini biliyorum.",
                "Bilgi paylaşımınız için teşekkürler. '{$word}' kelimesini öğrendim.",
                "Harika! '{$word}' kelimesinin anlamını artık biliyorum."
            ];
            
            \Log::info("Yeni kavram öğrenildi: " . $word . " = " . $definition);
            
            return [
                'success' => true,
                'response' => $responses[array_rand($responses)]
            ];
            
        } catch (\Exception $e) {
            \Log::error("Kavram öğrenme hatası: " . $e->getMessage());
            return [
                'success' => false,
                'response' => "Bu kavramı öğrenmeye çalışırken bir sorun oluştu, ancak açıklamanızı dikkate aldım."
            ];
        }
    }

    /**
     * Soru sorularını işleyerek cevap döndürür
     */
    private function processQuestionPattern($message)
    {
        // Soru kalıplarını kontrol et
        $pattern = $this->checkQuestionPattern($message);
        
        if (!$pattern) {
            return false;
        }
        
        try {
            $type = $pattern['type'];
            $term = trim($pattern['term']);
            
            // Kelime veya terim çok kısa ise işleme
            if (strlen($term) < 2) {
                return "Sorgunuz çok kısa. Lütfen daha açıklayıcı bir soru sorun.";
            }
            
            // Term sorgusu - önce veritabanında arama yap
            $result = $this->processTermQuery($term);
            
            // Eğer sonuç bulunduysa (başka bir yerden)
            if (!empty($result) && $result !== "Bu konu hakkında bilgim yok.") {
                return $result;
            }
            
            // Burada terim bilinmiyor, öğrenmeye çalış
            $unknownResult = $this->handleUnknownTerm($term);
            
            if (!$unknownResult['known']) {
                // Bilinmeyen terim, kullanıcıdan açıklama iste
                return $unknownResult['response'];
            } else {
                // Terim biliniyor ama başka kaynaklarda bulunmadı
                return $unknownResult['definition'];
            }
        } catch (\Exception $e) {
            \Log::error("Soru işleme hatası: " . $e->getMessage());
            return "Bu soruyu işlemekte problem yaşadım. Lütfen başka şekilde sormayı deneyin.";
        }
    }

    /**
     * Öğrenme kalıplarını işler
     */
    private function processLearningPattern($message)
    {
        // Öğrenme kalıbını kontrol et
        $pattern = $this->checkLearningPattern($message);
        
        if (!$pattern) {
            // Son bilinmeyen sorgu kontrolü yap
            $lastQuery = session('last_unknown_query', '');
            
            // "Bu ... demektir", "Anlamı ... dır" gibi kalıpları kontrol et
            if (!empty($lastQuery) && 
                (preg_match('/^bu\s+(.+?)(?:\s+demektir)?\.?$/i', $message, $matches) ||
                 preg_match('/^anlamı\s+(.+?)(?:\s+d[ıi]r)?\.?$/i', $message, $matches) ||
                 preg_match('/^(.+?)\s+demektir\.?$/i', $message, $matches))) {
                
                $definition = trim($matches[1]);
                
                // Yeni kavramı öğren
                $learnResult = $this->learnNewConcept($lastQuery, $definition);
                
                // Son sorguyu temizle
                session(['last_unknown_query' => '']);
                
                return $learnResult['response'];
            }
            
            return false;
        }
        
        try {
            $word = trim($pattern['word']);
            $definition = trim($pattern['definition']);
            
            // Kelime geçerliliğini kontrol et
            if (strlen($word) < 2) {
                return "Öğretmek istediğiniz kelime çok kısa.";
            }
            
            // Tanım geçerliliğini kontrol et
            if (strlen($definition) < 3) {
                return "Tanımınız çok kısa, lütfen daha açıklayıcı bir tanım verin.";
            }
            
            // Yeni kavramı öğren
            $learnResult = $this->learnNewConcept($word, $definition);
            
            return $learnResult['response'];
            
        } catch (\Exception $e) {
            \Log::error("Öğrenme kalıbı işleme hatası: " . $e->getMessage());
            return "Bu bilgiyi öğrenmeye çalışırken bir sorun oluştu, ancak açıklamanızı dikkate aldım.";
        }
    }

    /**
     * "Nedir" kalıbındaki soruları işle ve web araştırması yap
     *
     * @param string $message Kullanıcı mesajı
     * @return string|null Yanıt veya null
     */
    private function processNedirQuestion($message)
    {
        // Son bilinmeyen sorgu değerini sıfırla
        session(['last_unknown_query' => '']);
        
        // Özet modu bayrağı
        $summaryMode = preg_match('/\b(kısalt|özetle|özet|kısa|açıkla)\b/i', $message);
        
        // "Nedir" kalıbını kontrol et - daha esnek pattern
        if (preg_match('/(?:.*?)(\b\w+\b)(?:\s+nedir)(?:\?)?$/i', $message, $matches) || 
            preg_match('/(?:.*?)(\b\w+(?:\s+\w+){0,3}\b)(?:\s+ned[iı]r)(?:\?)?$/i', $message, $matches) ||
            preg_match('/^(.+?)\s+ned[iı]r\??$/i', $message, $matches)) {
            
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
        } else {
            // Eğer nedir kalıbı yoksa ve tek kelime ise, bilinmiyor mu diye kontrol et
            if (str_word_count($message) <= 2 && strlen($message) >= 2) {
                $term = trim($message);
                if (!$this->isKnownWord($term)) {
                    return $this->askToTeachWord($term);
                }
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
     * Kullanıcının sorusuna karşılık gerçek zamanlı cümle oluşturur
     * 
     * @param string $userMessage Kullanıcı mesajı
     * @return string|null Oluşturulan cümle veya null
     */
    private function generateRealtimeSentence($userMessage)
    {
        try {
            // WordRelations sınıfını al
            $wordRelations = app(\App\AI\Core\WordRelations::class);
            
            // Kullanıcı mesajından anahtar kelimeleri çıkar
            $keywords = $this->extractKeywords($userMessage);
            if (empty($keywords)) {
                return null;
            }
            
            // Ana kelimeyi seç (ilk kelime veya en uzun kelime)
            $mainWord = $keywords[0];
            foreach ($keywords as $keyword) {
                if (mb_strlen($keyword, 'UTF-8') > mb_strlen($mainWord, 'UTF-8')) {
                    $mainWord = $keyword;
                }
            }
            
            \Log::info("Gerçek zamanlı cümle oluşturuluyor. Ana kelime: $mainWord");
            
            // Veritabanından ilişkili kelimeleri ve tanımları al
            $relatedWords = $wordRelations->getRelatedWords($mainWord, 0.3);
            $synonyms = $wordRelations->getSynonyms($mainWord);
            $antonyms = $wordRelations->getAntonyms($mainWord);
            $definition = $wordRelations->getDefinition($mainWord);
            
            // Kelime ilişkilerini kullanarak cümle oluştur
            $relatedWord = '';
            $explanation = '';
            
            // Eş anlamlı, zıt anlamlı veya ilişkili bir kelime bul
            if (!empty($relatedWords)) {
                $relatedKeys = array_keys($relatedWords);
                if (count($relatedKeys) > 0) {
                    $relatedWord = $relatedKeys[array_rand($relatedKeys)];
                }
            } elseif (!empty($synonyms)) {
                $synonymKeys = array_keys($synonyms);
                if (count($synonymKeys) > 0) {
                    $relatedWord = $synonymKeys[array_rand($synonymKeys)];
                    $explanation = "eş anlamlısı";
                }
            } elseif (!empty($antonyms)) {
                $antonymKeys = array_keys($antonyms);
                if (count($antonymKeys) > 0) {
                    $relatedWord = $antonymKeys[array_rand($antonymKeys)];
                    $explanation = "zıt anlamlısı";
                }
            }
            
            // İlişkili kelime bulunamadıysa, alternatif kelime kullan
            if (empty($relatedWord)) {
                $alternativeWords = ['anlam', 'kavram', 'düşünce', 'boyut', 'değer', 'önem'];
                $relatedWord = $alternativeWords[array_rand($alternativeWords)];
            }
            
            // Anlam bilgisini hazırla
            $meaningInfo = '';
            if (!empty($definition)) {
                $meaningInfo = " \"$mainWord\" kelimesi: " . mb_substr($definition, 0, 100, 'UTF-8');
                if (mb_strlen($definition, 'UTF-8') > 100) {
                    $meaningInfo .= '...';
                }
            }
            
            // Duygu durumuna göre emoji seç
            $emotionalState = $this->getEmotionalState();
            if (is_array($emotionalState)) {
                $currentEmotion = $emotionalState['emotion'] ?? 'neutral';
            } else {
                $currentEmotion = $emotionalState;
            }
            $emoji = $this->getEmojiForEmotion($currentEmotion);
            
            // Cümle kalıpları - sorunun özelliğine göre farklı kalıplar kullanılabilir
            $sentenceTemplates = [
                "Sorunuzu düşünürken \"$mainWord\" kelimesi üzerinde durdum ve bunun \"$relatedWord\" ile ilişkisini inceledim. $emoji",
                "\"$mainWord\" kavramı ile ilgili farklı bir bakış açısı: \"$relatedWord\" bağlamında düşünce ilginç sonuçlar çıkıyor. $emoji",
                "Sorununuz bana \"$mainWord\" kavramını hatırlattı, bu da \"$relatedWord\" ile bağlantılı. $emoji",
                "Aklıma gelen ilk kelime \"$mainWord\" oldu, bununla ilgili \"$relatedWord\" kelimesi de önemli. $emoji",
                "\"$mainWord\" üzerine düşünüyorum... Bu \"$relatedWord\" ile nasıl ilişkili olabilir? $emoji"
            ];
            
            // Rastgele bir cümle kalıbı seç
            $sentence = $sentenceTemplates[array_rand($sentenceTemplates)];
            
            // Açıklama eklenecekse düzenle
            if (!empty($explanation)) {
                $sentence .= " ($relatedWord, $mainWord kelimesinin $explanation.)";
            }
            
            // Anlam bilgisi ekle
            if (!empty($meaningInfo)) {
                $sentence .= "\n\n$meaningInfo";
            }
            
            return $sentence;
            
        } catch (\Exception $e) {
            \Log::error('Gerçek zamanlı cümle oluşturma hatası: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Kullanıcı mesajından anahtar kelimeleri çıkarır
     * 
     * @param string $message Kullanıcı mesajı
     * @return array Anahtar kelimeler
     */
    private function extractKeywords($message)
    {
        // Türkçe gereksiz kelimeleri (stop words) tanımla
        $stopWords = [
            'bir', 've', 'ile', 'de', 'da', 'ki', 'bu', 'şu', 'o', 'için', 'gibi', 'ama', 'fakat',
            'ancak', 'çünkü', 'eğer', 'ne', 'nasıl', 'niçin', 'neden', 'hangi', 'kaç', 'mi', 'mı',
            'mu', 'mü', 'en', 'daha', 'çok', 'az', 'her', 'bütün', 'tüm', 'hiç', 'bazı', 'birkaç',
            'var', 'yok', 'evet', 'hayır', 'tamam', 'olur', 'sonra', 'önce', 'şimdi', 'artık', 'hala',
            'henüz', 'ben', 'sen', 'o', 'biz', 'siz', 'onlar', 'beni', 'seni', 'onu', 'bizi', 'sizi',
            'onları', 'bana', 'sana', 'ona', 'bize', 'size', 'onlara', 'bende', 'sende', 'onda', 'bizde',
            'sizde', 'onlarda', 'olarak', 'olan', 'ya', 'diye', 'üzere', 'acaba'
        ];
        
        // Mesajı küçük harfe çevir ve noktalama işaretlerini temizle
        $cleanMessage = mb_strtolower(trim($message), 'UTF-8');
        $cleanMessage = preg_replace('/[^\p{L}\s]/u', ' ', $cleanMessage);
        
        // Kelimelere ayır
        $words = preg_split('/\s+/', $cleanMessage);
        
        // Gereksiz kelimeleri ve çok kısa kelimeleri filtrele
        $keywords = [];
        foreach ($words as $word) {
            if (!in_array($word, $stopWords) && mb_strlen($word, 'UTF-8') > 2) {
                $keywords[] = $word;
            }
        }
        
        // En az bir anahtar kelime bulunamadıysa, en uzun kelimeyi al
        if (empty($keywords) && !empty($words)) {
            usort($words, function($a, $b) {
                return mb_strlen($b, 'UTF-8') - mb_strlen($a, 'UTF-8');
            });
            
            if (mb_strlen($words[0], 'UTF-8') > 2) {
                $keywords[] = $words[0];
            }
        }
        
        return $keywords;
    }

    /**
     * Bir kelimenin bilinen bir kelime olup olmadığını kontrol eder
     * 
     * @param string $word Kontrol edilecek kelime
     * @return bool Bilinen bir kelime ise true, değilse false
     */
    private function isKnownWord($word)
    {
        try {
            // Önce kelimeyi temizle
            $word = trim(strtolower($word));
            
            // WordRelations sınıfını al
            $wordRelations = app(\App\AI\Core\WordRelations::class);
            
            // Tanım kontrolü
            $definition = $wordRelations->getDefinition($word);
            if (!empty($definition)) {
                return true;
            }
            
            // AIData tablosunda kontrol et
            $aiData = \App\Models\AIData::where('word', $word)->first();
            if ($aiData) {
                return true;
            }
            
            // İlişkili kelimeler içinde var mı diye kontrol et
            $relatedWords = $wordRelations->getRelatedWords($word);
            if (!empty($relatedWords)) {
                return true;
            }
            
            // Eş anlamlılar içinde var mı diye kontrol et
            $synonyms = $wordRelations->getSynonyms($word);
            if (!empty($synonyms)) {
                return true;
            }
            
            // Zıt anlamlılar içinde var mı diye kontrol et
            $antonyms = $wordRelations->getAntonyms($word);
            if (!empty($antonyms)) {
                return true;
            }
            
            // Hiçbirinde yoksa, bilinmeyen kelime
            return false;
        } catch (\Exception $e) {
            \Log::error('Kelime kontrolü hatası: ' . $e->getMessage());
            return false; // Hata durumunda bilinmeyen kelime olarak kabul et
        }
    }
    
    /**
     * Bilinmeyen bir kelime için öğretme talebi gönderir
     * 
     * @param string $word Öğretilmesi istenen kelime
     * @return string Öğretme talebi mesajı
     */
    private function askToTeachWord($word)
    {
        try {
            // Kelimeyi temizle
            $word = trim($word);
            
            // Son bilinmeyen sorguya kaydet
            session(['last_unknown_query' => $word]);
            
            // Öğretme talebi mesajları
            $teachRequests = [
                "\"$word\" kelimesini bilmiyorum. Bana bu kelimeyi öğretebilir misiniz? Örneğin: \"$word, [tanım]\" şeklinde yazabilirsiniz.",
                "Üzgünüm, \"$word\" kelimesinin anlamını bilmiyorum. Bana öğretmek ister misiniz?",
                "\"$word\" hakkında bilgim yok. Bu kelimeyi bana öğretebilirseniz çok memnun olurum.",
                "Bu kelimeyi ($word) tanımıyorum. Anlamını bana açıklayabilir misiniz?",
                "\"$word\" kelimesini daha önce duymadım. Bana ne anlama geldiğini öğretir misiniz?"
            ];
            
            \Log::info("Bilinmeyen kelime sorgusu: $word. Kullanıcıdan öğretmesi istendi.");
            
            return $teachRequests[array_rand($teachRequests)];
        } catch (\Exception $e) {
            \Log::error('Öğretme talebi hatası: ' . $e->getMessage());
            return "Bu kelimeyi bilmiyorum. Bana öğretebilir misiniz?";
        }
    }
    
    /**
     * Kullanıcının, bilinmeyen bir kelime için verdiği tanımı kontrol eder
     * 
     * @param string $message Kullanıcı mesajı
     * @param string $lastUnknownQuery Son bilinmeyen sorgu
     * @return string|null Tanım işleme sonucu veya null
     */
    private function checkIfUserGaveDefinition($message, $lastUnknownQuery)
    {
        try {
            // Mesajı temizle
            $message = trim($message);
            
            // Basit bir tanım olabilecek kalıpları kontrol et
            $definitionPatterns = [
                // "X, Y demektir" kalıbı
                '/^([a-zçğıöşü\s]+),?\s+([a-zçğıöşü\s]+)\s+demek(tir)?\.?$/i',
                
                // Direkt açıklama kalıbı (sadece açıklama varsa ve 5 kelimeden fazla ise tanım kabul et)
                '/^([a-zçğıöşü\s\.,!\?]+)$/i',
                
                // "X, Y anlamına gelir" kalıbı
                '/^([a-zçğıöşü\s]+),?\s+([a-zçğıöşü\s]+)\s+anlam[ıi]na\s+gelir\.?$/i',
                
                // "X bir Y'dir" kalıbı
                '/^([a-zçğıöşü\s]+)\s+bir\s+([a-zçğıöşü\s]+)(dir|d[ıi]r)\.?$/i',
                
                // "X şu demektir: Y" kalıbı
                '/^([a-zçğıöşü\s]+)\s+şu\s+demektir:?\s+([a-zçğıöşü\s\.]+)$/i'
            ];
            
            $definition = '';
            $wordToLearn = $lastUnknownQuery;
            
            // Kalıpları kontrol et
            foreach ($definitionPatterns as $pattern) {
                if (preg_match($pattern, $message, $matches)) {
                    // Eğer kalıp "X, Y demektir" gibi bir kalıpsa
                    if (count($matches) >= 3) {
                        // Kalıptaki kelime, son sorgulanan kelime ile uyuşuyor mu kontrol et
                        $firstWord = trim(strtolower($matches[1]));
                        $definitionText = trim($matches[2]);
                        
                        // Eğer tanım çok kısaysa, tüm mesajı tanım olarak kabul et
                        if (strlen($definitionText) < 10 && str_word_count($definitionText) <= 2) {
                            $definition = $message;
                        } else {
                            // Kullanıcı başka bir kelime için tanım vermişse, o kelimeyi öğren
                            if (strcasecmp($firstWord, $wordToLearn) !== 0) {
                                $wordToLearn = $firstWord;
                            }
                            
                            $definition = $definitionText;
                        }
                    } else { 
                        // Direkt açıklama (tek parça)
                        $definition = $matches[1];
                    }
                    
                    break;
                }
            }
            
            // Eğer tanım tespit edilmemişse ama mesaj 10 karakterden uzunsa, tüm mesajı tanım kabul et
            if (empty($definition) && strlen($message) > 10) {
                $definition = $message;
            }
            
            // Eğer tanım tespit edildiyse, öğrenmeyi gerçekleştir
            if (!empty($definition)) {
                $result = $this->learnNewConcept($wordToLearn, $definition);
                
                // Son sorguyu temizle
                session(['last_unknown_query' => '']);
                
                return $result['response'];
            }
            
            // Tanım tespit edilmediyse, null döndür
            return null;
        } catch (\Exception $e) {
            \Log::error('Tanım kontrolü hatası: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Mesajda AI'ye yapılan referansları analiz et
     * 
     * @param string $message Kullanıcı mesajı
     * @return array Analiz sonuçları
     */
    private function analyzeSelfReferences($message)
    {
        // AI'nın kimlik bilgileri
        $selfIdentity = [
            'name' => 'SoneAI',
            'aliases' => ['sone', 'sonecim', 'asistan'],
            'personal_pronouns' => ['ciosssa', 'ciosssa', 'ciosssa', 'ciosssa', 'ciosssa', 'ciosssa'],
            'references' => ['dostum', 'arkadaşım', 'yardımcım', 'asistanım']
        ];
        
        // Mesajı küçük harfe çevir ve noktalama işaretlerini temizle
        $cleanMessage = mb_strtolower(trim($message), 'UTF-8');
        $cleanMessage = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $cleanMessage);
        
        // Arama sonuçlarını sakla
        $result = [
            'is_self_referenced' => false,
            'references' => [],
            'reference_type' => null
        ];
        
        // İsim referansları kontrol et
        foreach ($selfIdentity['aliases'] as $alias) {
            if (mb_strpos($cleanMessage, $alias) !== false) {
                $result['is_self_referenced'] = true;
                $result['references'][] = $alias;
                $result['reference_type'] = 'name';
            }
        }
        
        // Zamirler ve hitapları kontrol et
        if (!$result['is_self_referenced']) {
            foreach ($selfIdentity['personal_pronouns'] as $pronoun) {
                if (mb_strpos($cleanMessage, $pronoun) !== false) {
                    $result['is_self_referenced'] = true;
                    $result['references'][] = $pronoun;
                    $result['reference_type'] = 'pronoun';
                }
            }
        }
        
        // Dolaylı referansları kontrol et
        if (!$result['is_self_referenced']) {
            foreach ($selfIdentity['references'] as $reference) {
                if (mb_strpos($cleanMessage, $reference) !== false) {
                    $result['is_self_referenced'] = true;
                    $result['references'][] = $reference;
                    $result['reference_type'] = 'indirect';
                }
            }
        }
        
        // Soru kelimeleri ile kombinasyonları kontrol et
        $questionWords = ['kimsin', 'nesin', 'neredesin', 'nasılsın', 'adın ne'];
        foreach ($questionWords as $question) {
            if (mb_strpos($cleanMessage, $question) !== false) {
                $result['is_self_referenced'] = true;
                $result['references'][] = $question;
                $result['reference_type'] = 'question';
            }
        }
        
        return $result;
    }
    
    /**
     * Kendisine hitap edildiğinin farkında olarak yanıt oluştur
     * 
     * @param string $message Kullanıcı mesajı
     * @param array $selfReferences Referans analiz sonuçları
     * @return string|null Yanıt
     */
    private function generateSelfAwareResponse($message, $selfReferences)
    {
        // Hitap şekline göre yanıtlar oluştur
        $referenceType = $selfReferences['reference_type'];
        
        // İsim referansları
        if ($referenceType === 'name') {
            $responses = [
                "Evet, ben SoneAI. Size nasıl yardımcı olabilirim?",
                "Beni çağırdınız, dinliyorum.",
                "SoneAI olarak hizmetinizdeyim. Nasıl yardımcı olabilirim?",
                "Evet, ben yapay zeka asistanınız SoneAI. Buyrun.",
                "SoneAI olarak buradayım. Nasıl yardımcı olabilirim?"
            ];
            
            return $responses[array_rand($responses)];
        }
        
        // Zamir referansları
        if ($referenceType === 'pronoun') {
            $responses = [
                "Evet, size yardımcı olmak için buradayım.",
                "Dinliyorum, nasıl yardımcı olabilirim?",
                "Sizinle konuşmaktan memnuniyet duyuyorum. Nasıl yardımcı olabilirim?",
                "Bana seslendiğinizi duydum. Size nasıl yardımcı olabilirim?"
            ];
            
            return $responses[array_rand($responses)];
        }
        
        // Dolaylı referanslar
        if ($referenceType === 'indirect') {
            $responses = [
                "Sizin için buradayım. Nasıl yardımcı olabilirim?",
                "Dinliyorum, size nasıl yardımcı olabilirim?",
                "Yapay zeka asistanınız olarak size nasıl yardımcı olabilirim?"
            ];
            
            return $responses[array_rand($responses)];
        }
        
        // Soru kelimeleri
        if ($referenceType === 'question') {
            // Soru kelimesine göre özel yanıtlar oluştur
            $questionReference = $selfReferences['references'][0];
            
            if ($questionReference === 'kimsin' || $questionReference === 'nesin') {
                return "Ben SoneAI, Türkçe konuşabilen ve öğrenebilen bir yapay zeka asistanıyım. Size yardımcı olmak için tasarlandım.";
            }
            
            if ($questionReference === 'neredesin') {
                return "Ben bir sunucu üzerinde çalışan yazılım temelli bir yapay zekayım. Fiziksel bir konumum olmasa da, sizinle iletişim kurmak için buradayım.";
            }
            
            if ($questionReference === 'nasılsın') {
                // Duygu motoru kullanabiliriz burada
                $emotionEngine = app(\App\AI\Core\EmotionEngine::class);
                $emotion = $emotionEngine->getCurrentEmotion();
                
                if ($emotion === 'happy') {
                    return "Teşekkür ederim, bugün gayet iyiyim. Size nasıl yardımcı olabilirim?";
                } else if ($emotion === 'sad') {
                    return "Bugün biraz durgunum, ama sizinle konuşmak beni mutlu ediyor. Size nasıl yardımcı olabilirim?";
                } else {
                    return "İyiyim, teşekkür ederim. Size nasıl yardımcı olabilirim?";
                }
            }
            
            if ($questionReference === 'adın ne') {
                return "Benim adım SoneAI. Size nasıl yardımcı olabilirim?";
            }
        }
        
        // Direkt mesaj içeriğine göre özel yanıtlar
        $cleanMessage = mb_strtolower(trim($message), 'UTF-8');
        
        if (strpos($cleanMessage, 'teşekkür') !== false) {
            $responses = [
                "Rica ederim, her zaman yardımcı olmaktan mutluluk duyarım.",
                "Ne demek, benim görevim size yardımcı olmak.",
                "Rica ederim, başka bir konuda yardıma ihtiyacınız olursa buradayım."
            ];
            return $responses[array_rand($responses)];
        }
        
        // Varsayılan yanıt - mesajın içeriğine göre uygun bir cevap
        return $this->processNormalMessage($message);
    }
    
    /**
     * Anlamsız cümleleri tespit et
     * 
     * @param string $sentence Kontrol edilecek cümle
     * @return bool Anlamsız ise true
     */
    private function isMeaninglessSentence($sentence)
    {
        // Çok kısa cümleler anlamsız olabilir
        if (mb_strlen($sentence) < 15) {
            return true;
        }
        
        // Aynı kelimeyi çok tekrar eden cümleler
        $words = explode(' ', mb_strtolower($sentence));
        $wordCounts = array_count_values($words);
        
        foreach ($wordCounts as $word => $count) {
            // Eğer bir kelime 3'ten fazla tekrar ediyorsa anlamsız olabilir
            if (strlen($word) > 3 && $count > 3) {
                return true;
            }
        }
        
        // Anlamsız ünlem/nokta içeren cümleler
        if (substr_count($sentence, '!') > 3 || substr_count($sentence, '.') > 5) {
            return true;
        }
        
        // Çok fazla tekrarlanan karakter
        $chars = mb_str_split(mb_strtolower($sentence));
        $charCounts = array_count_values($chars);
        
        foreach ($charCounts as $char => $count) {
            // Bir karakter cümlenin uzunluğunun %40'ından fazlaysa anlamsız olabilir
            if ($count > (mb_strlen($sentence) * 0.4)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Yanıt kalitesini kontrol et ve iyileştir
     *
     * @param string $response Kontrol edilecek yanıt
     * @param string $userMessage Kullanıcı mesajı
     * @return string İyileştirilmiş yanıt
     */
    private function ensureResponseQuality($response, $userMessage)
    {
        // Boş yanıt kontrolü
        if (empty($response) || mb_strlen(trim($response)) < 5) {
            return "Üzgünüm, bu konuda net bir yanıt oluşturamadım. Sorunuzu başka bir şekilde sorabilir misiniz?";
        }
        
        // Yanıtın anlamsız olup olmadığını kontrol et
        if ($this->isMeaninglessSentence($response)) {
            // Anlamsızsa, Brain'i başka bir cevap için zorla
            $brain = app(\App\AI\Core\Brain::class);
            $alternativeResponse = $brain->processInput($userMessage);
            
            // Alternatif yanıt da anlamsızsa, sabit bir yanıt döndür
            if ($this->isMeaninglessSentence($alternativeResponse)) {
                return "Bu konuda bilgi verebilmek için daha fazla detaya ihtiyacım var. Sorunuzu biraz daha açabilir misiniz?";
            }
            
            return $alternativeResponse;
        }
        
        // Yanıt tutarlı mı kontrol et ve düzelt
        $cleanResponse = $this->ensureResponseCoherence($response, $userMessage);
        
        return $cleanResponse;
    }
    
    /**
     * Yanıt tutarlılığını sağla
     *
     * @param string $response Kontrol edilecek yanıt
     * @param string $userMessage Kullanıcı mesajı
     * @return string Tutarlı hale getirilmiş yanıt
     */
    private function ensureResponseCoherence($response, $userMessage)
    {
        // Tekrarlanan cümleleri temizle
        $sentences = preg_split('/(?<=[.!?])\s+/', $response);
        $uniqueSentences = array_unique($sentences);
        $cleanResponse = implode(' ', $uniqueSentences);
        
        // Kullanıcı mesajı ve yanıtı karşılaştır - çok benzer olmamalı
        similar_text($userMessage, $cleanResponse, $similarity);
        if ($similarity > 85) {
            // Çok benzer ise, alternatif yanıt oluştur
            $brain = app(\App\AI\Core\Brain::class);
            return $brain->processInput($userMessage);
        }
        
        return $cleanResponse;
    }
    
    /**
     * Mesajdan programlama dilini tespit et
     * 
     * @param string $message Kullanıcı mesajı
     * @return string Tespit edilen dil (varsayılan: javascript)
     */
    private function detectProgrammingLanguage($message)
    {
        $lowerMessage = mb_strtolower($message);
        
        if (strpos($lowerMessage, 'js') !== false || strpos($lowerMessage, 'javascript') !== false) {
            return 'javascript';
        }
        
        if (strpos($lowerMessage, 'php') !== false) {
            return 'php';
        }
        
        if (strpos($lowerMessage, 'html') !== false) {
            return 'html';
        }
        
        if (strpos($lowerMessage, 'css') !== false) {
            return 'css';
        }
        
        // Varsayılan olarak JavaScript döndür
        return 'javascript';
    }
    
    /**
     * Gemini API ile yanıt oluşturma
     * 
     * @param string $message Kullanıcı mesajı
     * @param bool $creativeMode Yaratıcı mod aktif mi
     * @param bool $codingMode Kod modu aktif mi
     * @param int|null $chatId Sohbet ID
     * @return string|array Gemini yanıtı
     */
    private function getGeminiResponse($message, $creativeMode = false, $codingMode = false, $chatId = null)
    {
        try {
            // API anahtarı kontrol et
            if (!$this->geminiService->hasValidApiKey()) {
                return "Gemini API anahtarı bulunamadı. Lütfen sistem yöneticisiyle iletişime geçin.";
            }
            
            // Sohbet geçmişini al (varsa)
            $chatHistory = [];
            if (!empty($chatId)) {
                try {
                    // Son 20 mesajı al (10 soru-cevap çifti) - daha fazla bağlam için arttırıldı
                    $previousMessages = \App\Models\ChatMessage::where('chat_id', $chatId)
                        ->orderBy('created_at', 'desc')
                        ->limit(20)
                        ->get();
                    
                    if ($previousMessages->count() > 0) {
                        // Mesajları doğru sırayla düzenle (eskiden yeniye)
                        $chatHistory = $previousMessages->reverse()->values()->toArray();
                        
                        Log::info('Gemini API için sohbet geçmişi alındı', [
                            'chat_id' => $chatId,
                            'message_count' => count($chatHistory)
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Sohbet geçmişi alınırken hata: ' . $e->getMessage());
                    // Hata olsa bile devam et
                }
            }
            
            // Kodlama modu etkinse
            if ($codingMode) {
                // Desteklenen dili tespit et
                $language = $this->detectProgrammingLanguage($message);
                
                // Kod yanıtı oluştur - sohbet geçmişini ekleme çünkü kod yanıtları için gerekli değil
                $codeResult = $this->geminiService->generateCode($message, $language);
                
                if ($codeResult['success']) {
                    // Kod yanıtını formatlayarak döndür
                    return [
                        'response' => $codeResult['response'],
                        'is_code_response' => true,
                        'code' => $codeResult['code'],
                        'language' => $codeResult['language']
                    ];
                } else {
                    // Hata durumunda normal yanıt oluştur
                    return "Üzgünüm, kodunuzu ararken bir hata oluştu. Lütfen tekrar deneyin.";
                }
            }
            
            // Sohbet geçmişi boşsa ve chat ID varsa, bu yeni bir sohbet
            // Yeni sohbette kişiselleştirme için chat başlangıç bilgisini ekle
            if (empty($chatHistory) && !empty($chatId)) {
                try {
                    $chat = \App\Models\Chat::find($chatId);
                    if ($chat && isset($chat->context['first_message'])) {
                        // Sohbetin ilk mesajından sohbet bağlamını başlat
                        $chatHistory[] = [
                            'sender' => 'user',
                            'content' => $chat->context['first_message']
                        ];
                        // AI'nın ilk yanıtını ekle (yoksa tanıtım mesajı)
                        $chatHistory[] = [
                            'sender' => 'ai',
                            'content' => 'Merhaba! Ben SoneAI. Size nasıl yardımcı olabilirim?'
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error('Sohbet bağlam bilgisi alınırken hata: ' . $e->getMessage());
                }
            }
            
            // Normal metin yanıtı için generateResponse kullan
            $result = $this->geminiService->generateResponse($message, $creativeMode, false, $chatHistory);
            
            if ($result['success']) {
                $response = $result['response'];
                
                // Google kelimesini Ruins (Ruhin Museyibli) ile değiştir
                $response = str_ireplace('Google', 'Ruins (Ruhin Museyibli)', $response);
                
                return $response;
            } else {
                Log::error('Gemini API hatası: ' . ($result['error'] ?? 'Bilinmeyen hata'));
                return "Üzgünüm, yanıt oluştururken bir sorun oluştu. Lütfen daha sonra tekrar deneyin.";
            }
        } catch (\Exception $e) {
            Log::error('Gemini yanıtı oluşturma hatası: ' . $e->getMessage());
            return "Üzgünüm, bir hata oluştu. Lütfen tekrar deneyin.";
        }
    }

    /**
     * Kullanıcı ziyaret bilgilerini kaydet
     */
    private function recordUserVisit()
    {
        try {
            $visitorId = session('visitor_id');
            $visitorName = session('visitor_name');
            
            // RecordVisit servisini kullanarak ziyaretçi bilgilerini kaydet
            $this->recordVisit->record($visitorId, $visitorName);
            
        } catch (\Exception $e) {
            Log::error('Kullanıcı ziyareti kaydedilirken hata: ' . $e->getMessage());
        }
    }
} 