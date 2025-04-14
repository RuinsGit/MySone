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
            $enhancedPrompt = "Sen bir Türkçe dil asistanısın ve Türkçe olarak cevap vermelisin. Cevabını kesinlikle ve mutlaka Türkçe dilde oluştur, başka dil kullanma. Sorunun cevabını bilmiyorsan, 'Üzgünüm, bu konuda yeterli bilgim yok' diye yanıtla. Soru: {$prompt}";
            
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
     * @return array İşlem sonucu ['success' => bool, 'response' => string]
     */
    public function generateResponse($message, $isCreative = false, $isCodingRequest = false)
    {
        try {
            // API anahtarı kontrolü
            if (!$this->hasValidApiKey()) {
                return [
                    'success' => false,
                    'error' => 'API anahtarı bulunamadı'
                ];
            }
            
            // Kullanıcı mesajını ve istek tipine göre ek talimatları hazırla
            $enhancedPrompt = "Soruya kesinlikle Türkçe olarak yanıt ver. Eğer cevabı bilmiyorsan, uydurma ve şöyle de: 'Bu konuda kesin bilgim yok, ancak araştırıp size daha doğru bilgi verebilirim.'";
            $codePrompt = "İstenilen kodlama görevini gerçekleştir ve yanıtı SADECE Türkçe olarak oluştur. Soruda istenen dilde hatasız, eksiksiz ve çalışan kod üret. Kodun tüm bölümlerini detaylı Türkçe açıklamalarla ve yorumlarla açıkla. Eğer tam olarak ne istediğini anlayamazsan, Türkçe olarak daha fazla bilgi iste.";

            // İstek tipine göre ek talimatları ekle
            $finalPrompt = $isCodingRequest 
                ? $message . "\n\n" . $codePrompt 
                : $message . "\n\n" . $enhancedPrompt;

            // Model seçimi ve ayarlar
            $model = "gemini-pro";
            
            // Ayarları hazırla
            $options = [
                'temperature' => $isCreative ? 0.8 : 0.1,
                'maxOutputTokens' => $isCreative ? 2048 : 1024,
                'topP' => 0.9,
                'topK' => 40,
            ];
            
            // İsteği gönder
            $result = $this->generateContent($finalPrompt, $options);
            
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