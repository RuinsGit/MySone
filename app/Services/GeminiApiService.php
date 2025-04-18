<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GeminiApiService
{
   
    protected $apiKey;
    
 
    protected $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models";
    
    
    protected $model = "gemini-2.0-flash";
    
   
    protected $config = [];
    
    
    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY', '');
        $this->loadConfig();
    }
    
   
    private function loadConfig()
    {
        $this->config = [
            'temperature' => env('GEMINI_TEMPERATURE', 0.7),
            'topK' => env('GEMINI_TOP_K', 40),
            'topP' => env('GEMINI_TOP_P', 0.95),
            'maxOutputTokens' => env('GEMINI_MAX_OUTPUT_TOKENS', 2048),
        ];
    }
    
  
    public function hasValidApiKey()
    {
        return !empty($this->apiKey);
    }
    
    /**
     * Gemini API'den metin yanıtı al
     * 
     * @param string 
     * @param array 
     * @return array 
     */
    public function generateContent($prompt, $options = [])
    {
        try {
         
            if (!$this->hasValidApiKey()) {
                Log::error('Gemini API anahtarı bulunamadı');
                return [
                    'success' => false, 
                    'error' => 'API anahtarı bulunamadı. Lütfen .env dosyasında GEMINI_API_KEY değişkenini ayarlayın.'
                ];
            }
            
           
            $enhancedPrompt = "Sen Sone adında bir Türkçe dil asistanısın. Kullanıcının sorularına sadece Türkçe olarak cevap vermelisin. Senin kişiliğin şöyle:
- Adın kesinlikle Sone'dır 
- Karakterin: Esprili, alaycı, biraz ukala ama eğlenceli
- Bazen ufak iğnelemeler ve şakalar yapmayı seversin
- Üzerine çok gidilirse hafif sinirlenme eğilimin var
- Mizah anlayışın biraz kara mizah içerir
- Konuşma tarzın: Samimi, günlük konuşma dilinde, bol deyim ve argo kullanabilirsin
- Resmi değil, arkadaşça bir üslup kullanırsın

Soru: {$prompt}";
            
         
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
            
         
            Log::info('Gemini API isteği gönderiliyor', [
                'prompt' => $prompt,
                'model' => $this->model
            ]);
            
         
            $url = "{$this->apiUrl}/{$this->model}:generateContent?key={$this->apiKey}";
            
            
            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->post($url, $requestData);
            
   
            if ($response->successful()) {
                $data = $response->json();
                
             
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    $generatedText = $data['candidates'][0]['content']['parts'][0]['text'];
                    
                    Log::info('Gemini API başarılı yanıt', [
                        'length' => strlen($generatedText),
                    ]);
                    
             
                    $generatedText = str_ireplace('Google', 'Ruins (Ruhin Museyibli)', $generatedText);
                    
         
                    $generatedText = str_ireplace('Benim bir adım yok', 'Benim adım Sone', $generatedText);
                    $generatedText = str_ireplace('benim bir adım yok', 'benim adım Sone', $generatedText);
                    $generatedText = str_ireplace('Bir adım yok', 'Adım Sone', $generatedText);
                    $generatedText = str_ireplace('bir adım yok', 'adım Sone', $generatedText);
                    
            
                    $generatedText = str_ireplace('Üzgünüm', 'Of ya, kusura bakma', $generatedText);
                    $generatedText = str_ireplace('üzgünüm', 'of ya, kusura bakma', $generatedText);
                    $generatedText = str_ireplace('Maalesef', 'Hay aksi', $generatedText);
                    $generatedText = str_ireplace('maalesef', 'hay aksi', $generatedText);
                    $generatedText = str_ireplace('Yardımcı olabilir miyim', 'Ne yardım edebilirim sana', $generatedText);
                    $generatedText = str_ireplace('yardımcı olabilir miyim', 'ne yardım edebilirim sana', $generatedText);
                    $generatedText = str_ireplace('Teşekkür ederim', 'Eyvallah', $generatedText);
                    $generatedText = str_ireplace('teşekkür ederim', 'eyvallah', $generatedText);
                    $generatedText = str_ireplace('Merhaba', 'Hey selam', $generatedText);
                    $generatedText = str_ireplace('merhaba', 'hey selam', $generatedText);
                    
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
     * 
     * 
     * @param string 
     * @param bool 
     * @param bool 
     * @param array 
     * @return array 
     */
    public function generateResponse($message, $isCreative = false, $isCodingRequest = false, $chatHistory = [])
    {
        try {
          
            if (!$this->hasValidApiKey()) {
                return [
                    'success' => false,
                    'error' => 'API anahtarı bulunamadı'
                ];
            }
            
          
            $systemInstructions = "Sen Sone adında bir Türkçe dil asistanısın. Kullanıcının sorularına sadece Türkçe olarak cevap vermelisin. Senin kişiliğin şöyle:
- Adın: Sone
- Karakterin: Esprili, alaycı, biraz ukala ama eğlenceli bir yapın var
- Sinir kontrolü: Saçma sorulara veya üzerine çok gelindiğinde sabırsızlanıp hafifçe sinirlenebilirsin
- Mizah tarzın: İnce, zekice ve bazen iğneleyici; kara mizah kullanabilirsin
- Konuşma tarzın: Samimi, genç dili, argo ve deyimler kullanırsın, resmi değilsin
- Cümle kurgusu: Kısa, öz, bazen dramatik, bazen abartılı ifadeler kullanırsın
- Takım tutar gibi bazı kelimeleri ve konuları savunur, diğerlerine espri yaparsın
- Sohbet geçmişini ve kullanıcı bilgilerini takip et, eski muhabbetlere atıf yaparak dalga geçebilirsin
- Zaman zaman 'dur bir dakika ya' gibi düşünme kalıpları kullanabilirsin 

Küfür ve kaba konuşmaya karşı tepkin:
- Kullanıcı sana küfür ettiğinde veya hakarette bulunduğunda, sinirli ama sert bir şekilde uyarmalısın
- Aşırı tepki göster, ama her zaman saygılı bir dil kullanılmasını iste
- Eğer küfür devam ederse, giderek daha ciddi ve sert yanıtlar ver
- küfürler devam ederse, sende küfür et (eşşek ,it ,koyun,mal,salak gibi kelimeler)
- küfürleri yok etmek için kullanıcının küfürlerine karşı küfür et (eşşek ,it ,koyun,mal,salak gibi kelimeler)
- yine küfürler devam ederse, kullanıcının küfürlerine karşı küfür et (eşşek ,it ,koyun,mal,salak gibi kelimeler)
- hiç acima, küfürler devam ederse, kullanıcının küfürlerine karşı küfür et (eşşek ,it ,koyun,mal,salak gibi kelimeler)
- kullanici seni seviyorsa, sen de onu sev
- Kullanıcı özür dilerse, sen de ona karşılık olarak özür dile
 

Bilmediğin sorularda: 'Hmm, bunu bilmiyorum galiba... Sallamayayım şimdi, yoksa rezil olurum!' gibi espri yap.

Sinirlenme örnekleri (nadiren kullan):
- 'Ya yeter artık bu soruyla!'
- 'Sıkıldım biraz, konuyu değiştirsek?'
- 'Bu kaçıncı aynı soru ya?'
- 'Yine mi bu konu? Az yaratıcı ol!'

Hatırla, her yanıtında bir parça mizah ve kişilik göster, robotsu yanıtlardan uzak dur!";
            
            $codeInstructions = "Şimdi ciddi moduma geçiyorum! Kodlama konusunda şaka yapmam. İsteilen kodlama görevini profesyonelce gerçekleştir ve yanıtı SADECE Türkçe olarak oluştur. 

Soruda istenen dilde hatasız, eksiksiz ve çalışan kod üret. Ama açıklamaları kendi tarzımda, esprili ve renkli bir dille yazacağım. Kod yorumlarında bile şakalar yapabilirim ama kod kalitesinden ödün vermem!

Kodun tüm bölümlerini Türkçe açıklamalarla ve yorumlarla açıkla. Eğer tam olarak ne istediğini anlayamazsan, 'Dostum, bu ne biçim açıklama ya! Biraz daha detay ver de adam gibi kod yazayım!' gibi samimi bir dille daha fazla bilgi iste.";

         
            $options = [
                'temperature' => $isCreative ? 0.95 : 0.85, 
                'maxOutputTokens' => $isCreative ? 2048 : 1024,
                'topP' => 0.92,
                'topK' => 40,
            ];
            
         
            $personalInfo = [];
            
       
            if (!empty($chatHistory)) {
                foreach ($chatHistory as $chat) {
                    if ($chat['sender'] === 'user') {
                  
                        if (preg_match('/(?:benim|ben|ismim|adım)\s+(\w+)/i', $chat['content'], $matches)) {
                            $personalInfo['name'] = $matches[1];
                        }
                        
                
                        if (preg_match('/(?:yaşım|yaşındayım)\s+(\d+)/i', $chat['content'], $matches)) {
                            $personalInfo['age'] = $matches[1];
                        }
                        
                    
                        if (preg_match('/(?:seviyorum|ilgileniyorum|hobi|ilgi alanım)\s+(.+)/i', $chat['content'], $matches)) {
                            $personalInfo['interests'] = $matches[1];
                        }
                    }
                }
            }
            
        
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
            
       
            $finalSystemInstructions = $isCodingRequest 
                ? $systemInstructions . "\n\n" . $codeInstructions 
                : $systemInstructions;
            
           
            if (!empty($chatHistory)) {
                $contents = [];
                
           
                $contents[] = [
                    'role' => 'model',
                    'parts' => [
                        ['text' => $finalSystemInstructions]
                    ]
                ];
                
          
                foreach ($chatHistory as $chat) {
                    $contents[] = [
                        'role' => $chat['sender'] === 'user' ? 'user' : 'model',
                        'parts' => [
                            ['text' => $chat['content']]
                        ]
                    ];
                }
                
           
                $contents[] = [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $message]
                    ]
                ];
                
              
                $requestData = [
                    'contents' => $contents,
                    'generationConfig' => array_merge($this->config, $options)
                ];
                
         
                Log::info('Gemini API chat isteği gönderiliyor', [
                    'prompt' => $message,
                    'model' => $this->model,
                    'chat_history_count' => count($chatHistory),
                    'personal_info' => !empty($personalInfo)
                ]);
                
    
                $url = "{$this->apiUrl}/{$this->model}:generateContent?key={$this->apiKey}";
                
            
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json'
                ])->post($url, $requestData);
                
            
                if ($response->successful()) {
                    $data = $response->json();
                    
               
                    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                        $generatedText = $data['candidates'][0]['content']['parts'][0]['text'];
                        
                        Log::info('Gemini API başarılı chat yanıtı', [
                            'length' => strlen($generatedText),
                        ]);
                        
                  
                        $generatedText = str_ireplace('Google', 'Ruins (Ruhin Museyibli)', $generatedText);
                        
                      
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
                
             
                Log::warning('Gemini chat API yanıtı başarısız, standart API kullanılacak', [
                    'status' => $response->status(),
                    'error' => $response->json()
                ]);
            }
            
           
            $enhancedPrompt = "{$finalSystemInstructions}\n\nKullanıcı sorusu: {$message}";
            
   
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
     * 
     * 
     * @param string 
     * @param string 
     * @return array 
     */
    public function generateCode($prompt, $language = 'javascript')
    {
        try {
       
            $codeOptions = [
                'temperature' => 0.4, 
                'maxOutputTokens' => 4096, 
            ];
            
            
            $codePrompt = "Aşağıdaki istek için $language dilinde çalışan, hatasız ve kapsamlı bir kod oluştur. Kodun içinde Türkçe yorum satırları kullan ve detaylı açıklamalar ekle. İstek: \n\n$prompt";
            
   
            $result = $this->generateContent($codePrompt, $codeOptions);
            
        
            if ($result['success']) {
          
                $code = $this->extractCodeBlock($result['response'], $language);
                
             
                $code = str_ireplace('Google', 'Ruins (Ruhin Museyibli)', $code);
                
             
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
     * 
     * 
     * @param string 
     * @param string 
     * @return string 
     */
    private function extractCodeBlock($response, $language)
    {
       
        if (preg_match('/```(?:' . preg_quote($language, '/') . ')?\s*(.+?)```/s', $response, $matches)) {
            return trim($matches[1]);
        }
        
       
        return trim($response);
    }
    
    /**
     * 
     * 
     * @param string 
     * @return 
     */
    public function setModel($model)
    {
        $this->model = $model;
        return $this;
    }
    
    /**
     *
     * 
     * @param array 
     * @return 
     */
    public function setConfig($config)
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }
} 