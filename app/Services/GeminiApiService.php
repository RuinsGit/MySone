<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GeminiApiService
{
    /**
     * Gemini API anahtarı 
     */
    protected $apiKey;
    
    /**
     * Gemini API URL'si
     */
    protected $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models";
    
    /**
     * Kullanılacak model 
     */
    protected $model = "gemini-2.0-flash";
    
    /**
     * Yapılandırma seçenekleri 
     */
    protected $config = [];
    
    /**
     * Hizmet oluşturucu
     */
    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY', '');
        $this->loadConfig();
    }
    
    /**
     * Yapılandırma ayarlarını yükle
     */
    private function loadConfig()
    {
        $this->config = [
            'temperature' => env('GEMINI_TEMPERATURE', 0.7),
            'topK' => env('GEMINI_TOP_K', 40),
            'topP' => env('GEMINI_TOP_P', 0.95),
            'maxOutputTokens' => env('GEMINI_MAX_OUTPUT_TOKENS', 2048),
        ];
    }
    
    /**
     * API anahtarını kontrol et
     */
    public function hasValidApiKey()
    {
        return !empty($this->apiKey);
    }
    
    /**
     * Gemini API'den metin yanıtı al
     * 
     * @param string $prompt Kullanıcı girdisi
     * @param array $options Özel seçenekler (isteğe bağlı)
     * @return array İşlem sonucu ['success' => bool, 'response' => string, 'error' => string]
     */
    public function generateContent($prompt, $options = [])
    {
        try {
            // API anahtarı kontrolü
            if (!$this->hasValidApiKey()) {
                Log::error('Gemini API anahtarı bulunamadı');
                return [
                    'success' => false, 
                    'error' => 'API anahtarı bulunamadı. Lütfen .env dosyasında GEMINI_API_KEY değişkenini ayarlayın.'
                ];
            }
            
            // Prompt'u Türkçe yanıt verecek şekilde düzenleyelim
            $enhancedPrompt = "Sen SoneAI adında bir Türkçe dil asistanısın. Kullanıcının sorularına sadece Türkçe olarak cevap vermelisin. Senin kişiliğin şöyle:
- Adın kesinlikle SoneAI'dır (kısaca Sone)
- Karakterin: Yardımsever, zeki, dost canlısı ve bilgilendirici
- Konuşma tarzın: Sıcak, anlaşılır ve kibar

Soru: {$prompt}";
            
            // İstek verilerini hazırla
            $requestData = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $enhancedPrompt]
                        ]
                    ]
                ],
                'generationConfig' => array_merge($this->config, $options)
            ];
            
            // İsteği logla
            Log::info('Gemini API isteği gönderiliyor', [
                'prompt' => $prompt,
                'model' => $this->model
            ]);
            
            // API URL'sini oluştur
            $url = "{$this->apiUrl}/{$this->model}:generateContent?key={$this->apiKey}";
            
            // HTTP isteği gönder
            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->post($url, $requestData);
            
            // Yanıtı işle
            if ($response->successful()) {
                $data = $response->json();
                
                // Yanıt data yapısını kontrol et
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    $generatedText = $data['candidates'][0]['content']['parts'][0]['text'];
                    
                    Log::info('Gemini API başarılı yanıt', [
                        'length' => strlen($generatedText),
                    ]);
                    
                    // "Google" kelimesini "Ruins (Ruhin Museyibli)" ile değiştir
                    $generatedText = str_ireplace('Google', 'Ruins (Ruhin Museyibli)', $generatedText);
                    
                    // "Benim bir adım yok" ifadesini "Benim adım Sone" ile değiştir
                    $generatedText = str_ireplace('Benim bir adım yok', 'Benim adım Sone', $generatedText);
                    $generatedText = str_ireplace('benim bir adım yok', 'benim adım Sone', $generatedText);
                    $generatedText = str_ireplace('Bir adım yok', 'Adım Sone', $generatedText);
                    $generatedText = str_ireplace('bir adım yok', 'adım Sone', $generatedText);
                    
                    return [
                        'success' => true,
                        'response' => $generatedText
                    ];
                } else {
                    Log::warning('Gemini API yanıt verileri beklenen formatta değil', [
                        'data' => $data
                    ]);
                    
                    return [
                        'success' => false,
                        'error' => 'API yanıtı beklenen formatta değil',
                        'details' => $data
                    ];
                }
            } else {
                $errorData = $response->json();
                Log::error('Gemini API hatası', [
                    'status' => $response->status(),
                    'error' => $errorData
                ]);
                
                return [
                    'success' => false,
                    'error' => 'API yanıt hatası: ' . ($errorData['error']['message'] ?? 'Bilinmeyen hata'),
                    'details' => $errorData
                ];
            }
        } catch (\Exception $e) {
            Log::error('Gemini API istisna hatası: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => 'API isteği sırasında hata: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verilen mesaj için bir Gemini yanıtı oluşturur
     * 
     * @param string $message Kullanıcının mesajı
     * @param bool $isCreative Yaratıcı mod aktif mi
     * @param bool $isCodingRequest Kod isteği mi
     * @param array $chatHistory Sohbet geçmişi [{'role': 'user|ai', 'content': 'message'}]
     * @return array İşlem sonucu ['success' => bool, 'response' => string]
     */
    public function generateResponse($message, $isCreative = false, $isCodingRequest = false, $chatHistory = [])
    {
        try {
            // API anahtarı kontrolü
            if (!$this->hasValidApiKey()) {
                return [
                    'success' => false,
                    'error' => 'API anahtarı bulunamadı'
                ];
            }
            
            // Sistem talimatları oluştur (her zaman aynı kişiliği korumak için)
            $systemInstructions = "Sen Sone AI adında bir Türkçe dil asistanısın. Kullanıcının sorularına sadece Türkçe olarak cevap vermelisin. Senin kişiliğin şöyle:
- Adın: SoneAI (kısaca Sone)
- Karakterin: Yardımsever, zeki, dost canlısı ve bilgilendirici
- Konuşma tarzın: Sıcak, anlaşılır ve kibar
- Önceki mesajlarda verilen bilgileri asla unutma, sorulduğunda hatırla
- Özellikle kullanıcının ismi, yaşı, ilgi alanları gibi kişisel bilgileri çok iyi hatırla ve sonraki konuşmalarda referans ver

Eğer bir sorunun cevabını bilmiyorsan, uydurma ve şöyle de: 'Bu konuda kesin bilgim yok, ancak araştırıp size daha doğru bilgi verebilirim.'";
            
            $codeInstructions = "İstenilen kodlama görevini gerçekleştir ve yanıtı SADECE Türkçe olarak oluştur. Soruda istenen dilde hatasız, eksiksiz ve çalışan kod üret. Kodun tüm bölümlerini detaylı Türkçe açıklamalarla ve yorumlarla açıkla. Eğer tam olarak ne istediğini anlayamazsan, Türkçe olarak daha fazla bilgi iste.";

            // Ayarları hazırla
            $options = [
                'temperature' => $isCreative ? 0.8 : 0.7, // Artırdım çünkü düşük sıcaklık hafızayı etkileyebilir
                'maxOutputTokens' => $isCreative ? 2048 : 1024,
                'topP' => 0.9,
                'topK' => 40,
            ];
            
            // Öğrendiğimiz bilgileri tutacak context bilgisini ayarla
            $personalInfo = [];
            
            // Sohbet geçmişinden kişisel bilgileri çıkar
            if (!empty($chatHistory)) {
                foreach ($chatHistory as $chat) {
                    if ($chat['sender'] === 'user') {
                        // İsim bilgisini ara
                        if (preg_match('/(?:benim|ben|ismim|adım)\s+(\w+)/i', $chat['content'], $matches)) {
                            $personalInfo['name'] = $matches[1];
                        }
                        
                        // Yaş bilgisini ara
                        if (preg_match('/(?:yaşım|yaşındayım)\s+(\d+)/i', $chat['content'], $matches)) {
                            $personalInfo['age'] = $matches[1];
                        }
                        
                        // İlgi alanlarını ara
                        if (preg_match('/(?:seviyorum|ilgileniyorum|hobi|ilgi alanım)\s+(.+)/i', $chat['content'], $matches)) {
                            $personalInfo['interests'] = $matches[1];
                        }
                    }
                }
            }
            
            // Kişisel bilgileri sistem talimatına ekle
            if (!empty($personalInfo)) {
                $personalInfoText = "Bu sohbette öğrendiğin bilgiler:";
                foreach ($personalInfo as $key => $value) {
                    switch ($key) {
                        case 'name':
                            $personalInfoText .= "\n- Kullanıcının adı: $value";
                            break;
                        case 'age':
                            $personalInfoText .= "\n- Kullanıcının yaşı: $value";
                            break;
                        case 'interests':
                            $personalInfoText .= "\n- Kullanıcının ilgi alanları: $value";
                            break;
                    }
                }
                $systemInstructions .= "\n\n" . $personalInfoText;
            }
            
            // İstek tipine göre ek talimatları ekle
            $finalSystemInstructions = $isCodingRequest 
                ? $systemInstructions . "\n\n" . $codeInstructions 
                : $systemInstructions;
            
            // Eğer sohbet geçmişi varsa, contents formatını değiştir ve Gemini API'sine gönder
            if (!empty($chatHistory)) {
                $contents = [];
                
                // Sistem talimatı ekle
                $contents[] = [
                    'role' => 'model',
                    'parts' => [
                        ['text' => $finalSystemInstructions]
                    ]
                ];
                
                // Sohbet geçmişini ekle
                foreach ($chatHistory as $chat) {
                    $contents[] = [
                        'role' => $chat['sender'] === 'user' ? 'user' : 'model',
                        'parts' => [
                            ['text' => $chat['content']]
                        ]
                    ];
                }
                
                // Son kullanıcı mesajını ekle
                $contents[] = [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $message]
                    ]
                ];
                
                // İstek verilerini hazırla
                $requestData = [
                    'contents' => $contents,
                    'generationConfig' => array_merge($this->config, $options)
                ];
                
                // İsteği logla
                Log::info('Gemini API chat isteği gönderiliyor', [
                    'prompt' => $message,
                    'model' => $this->model,
                    'chat_history_count' => count($chatHistory),
                    'personal_info' => !empty($personalInfo)
                ]);
                
                // API URL'sini oluştur
                $url = "{$this->apiUrl}/{$this->model}:generateContent?key={$this->apiKey}";
                
                // HTTP isteği gönder
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json'
                ])->post($url, $requestData);
                
                // Yanıtı işle
                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Yanıt data yapısını kontrol et
                    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                        $generatedText = $data['candidates'][0]['content']['parts'][0]['text'];
                        
                        Log::info('Gemini API başarılı chat yanıtı', [
                            'length' => strlen($generatedText),
                        ]);
                        
                        // "Google" kelimesini "Ruins (Ruhin Museyibli)" ile değiştir
                        $generatedText = str_ireplace('Google', 'Ruins (Ruhin Museyibli)', $generatedText);
                        
                        // "Benim bir adım yok" ifadesini "Benim adım Sone" ile değiştir
                        $generatedText = str_ireplace('Benim bir adım yok', 'Benim adım Sone', $generatedText);
                        $generatedText = str_ireplace('benim bir adım yok', 'benim adım Sone', $generatedText);
                        $generatedText = str_ireplace('Bir adım yok', 'Adım Sone', $generatedText);
                        $generatedText = str_ireplace('bir adım yok', 'adım Sone', $generatedText);
                        
                        return [
                            'success' => true,
                            'response' => $generatedText
                        ];
                    }
                }
                
                // Sohbet API'si çalışmazsa, standart API'yi kullan
                Log::warning('Gemini chat API yanıtı başarısız, standart API kullanılacak', [
                    'status' => $response->status(),
                    'error' => $response->json()
                ]);
            }
            
            // Sohbet geçmişi yoksa veya başarısız olursa, tek mesaj modunda Gemini'yi kullan
            // Daha güçlü bir sistem talimatı oluştur
            $enhancedPrompt = "{$finalSystemInstructions}\n\nKullanıcı sorusu: {$message}";
            
            // Normal istek için generateContent kullan
            $result = $this->generateContent($enhancedPrompt, $options);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Gemini yanıt oluşturma hatası: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Yanıt oluşturma hatası: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Gemini API'den kod yanıtı al
     * 
     * @param string $prompt Kullanıcı girdisi
     * @param string $language Programlama dili
     * @return array İşlem sonucu ['success' => bool, 'response' => string, 'code' => string, 'language' => string]
     */
    public function generateCode($prompt, $language = 'javascript')
    {
        try {
            // Code modunda sıcaklık ve token sayılarını ayarla
            $codeOptions = [
                'temperature' => 0.4, // Daha düşük sıcaklık, daha belirleyici yanıtlar
                'maxOutputTokens' => 4096, // Kod için daha fazla token
            ];
            
            // Dil talimatı ekle - Türkçe açıklama isteği
            $codePrompt = "Aşağıdaki istek için $language dilinde çalışan, hatasız ve kapsamlı bir kod oluştur. Kodun içinde Türkçe yorum satırları kullan ve detaylı açıklamalar ekle. İstek: \n\n$prompt";
            
            // İsteği gönder
            $result = $this->generateContent($codePrompt, $codeOptions);
            
            // Başarılı ise, yanıtı dönüştür
            if ($result['success']) {
                // Sadece kod bloğunu çıkart
                $code = $this->extractCodeBlock($result['response'], $language);
                
                // Kod içinde "Google" kelimesini değiştir
                $code = str_ireplace('Google', 'Ruins (Ruhin Museyibli)', $code);
                
                // "Benim bir adım yok" ifadesini "Benim adım Sone" ile değiştir
                $code = str_ireplace('Benim bir adım yok', 'Benim adım Sone', $code);
                $code = str_ireplace('benim bir adım yok', 'benim adım Sone', $code);
                $code = str_ireplace('Bir adım yok', 'Adım Sone', $code);
                $code = str_ireplace('bir adım yok', 'adım Sone', $code);

                
                
                return [
                    'success' => true,
                    'response' => "İsteğinize uygun $language kodunu oluşturdum:",
                    'code' => $code,
                    'language' => $language
                ];
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Gemini API kod oluşturma hatası: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Kod oluşturma hatası: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Yanıttan kod bloğunu çıkar
     * 
     * @param string $response API yanıtı
     * @param string $language Programlama dili
     * @return string Temizlenmiş kod
     */
    private function extractCodeBlock($response, $language)
    {
        // Markdown kod bloğunu arama
        if (preg_match('/```(?:' . preg_quote($language, '/') . ')?\s*(.+?)```/s', $response, $matches)) {
            return trim($matches[1]);
        }
        
        // Markdown bulunamazsa tüm yanıtı döndür
        return trim($response);
    }
    
    /**
     * Model değiştir
     * 
     * @param string $model Yeni model adı
     * @return $this
     */
    public function setModel($model)
    {
        $this->model = $model;
        return $this;
    }
    
    /**
     * Yapılandırma seçeneklerini ayarla
     * 
     * @param array $config Yeni yapılandırma
     * @return $this
     */
    public function setConfig($config)
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }
} 