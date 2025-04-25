<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\GeminiApiService;

class SpeechController extends Controller
{
    protected $geminiService;
    
    /**
     * Constructor
     */
    public function __construct(GeminiApiService $geminiService = null)
    {
        $this->geminiService = $geminiService ?? app(GeminiApiService::class);
    }
    
    /**
     * Konuşmayı metne dönüştür (Speech-to-Text)
     */
    public function convertSpeechToText(Request $request)
    {
        try {
            // Ses dosyası alınır
            $audioData = $request->input('audio');
            
            if (!$audioData) {
                return response()->json([
                    'success' => false,
                    'error' => 'Ses verisi bulunamadı.'
                ], 400);
            }
            
            // Base64 kısmını çıkar ve dekode et
            $base64Data = '';
            if (strpos($audioData, 'data:audio/webm;base64,') !== false) {
                $base64Data = str_replace('data:audio/webm;base64,', '', $audioData);
            } elseif (strpos($audioData, 'data:audio/wav;base64,') !== false) {
                $base64Data = str_replace('data:audio/wav;base64,', '', $audioData);
            } elseif (strpos($audioData, 'data:audio/ogg;base64,') !== false) {
                $base64Data = str_replace('data:audio/ogg;base64,', '', $audioData);
            } elseif (strpos($audioData, 'data:audio/mp3;base64,') !== false) {
                $base64Data = str_replace('data:audio/mp3;base64,', '', $audioData);
            } elseif (strpos($audioData, 'data:audio/') !== false) {
                // Herhangi bir audio formatı
                $base64Data = substr($audioData, strpos($audioData, 'base64,') + 7);
            } else {
                // Ham base64 veri
                $base64Data = $audioData;
            }
            
            // Format algılama için veri türünü belirle
            $audioFormat = 'WEBM_OPUS'; // varsayılan
            if (strpos($audioData, 'data:audio/wav') !== false) {
                $audioFormat = 'LINEAR16';
            } elseif (strpos($audioData, 'data:audio/ogg') !== false) {
                $audioFormat = 'OGG_OPUS';
            } elseif (strpos($audioData, 'data:audio/mp3') !== false) {
                $audioFormat = 'MP3';
            }
            
            $decodedAudio = base64_decode($base64Data);
            $tempFileName = 'temp_' . uniqid();
            $extension = 'webm';
            
            // Dosya uzantısını formatına göre belirle
            if ($audioFormat === 'LINEAR16') {
                $extension = 'wav';
            } elseif ($audioFormat === 'OGG_OPUS') {
                $extension = 'ogg';
            } elseif ($audioFormat === 'MP3') {
                $extension = 'mp3';
            }
            
            $tempFilePath = storage_path('app/' . $tempFileName . '.' . $extension);
            file_put_contents($tempFilePath, $decodedAudio);
            
            // Dosyanın boyutunu kontrol et ve log kaydet
            $fileSize = filesize($tempFilePath);
            Log::info('Ses dosyası hazırlandı', [
                'file_size' => $fileSize,
                'format' => $audioFormat,
                'extension' => $extension
            ]);
            
            // Dosya boş mu kontrol et
            if ($fileSize <= 0) {
                unlink($tempFilePath);
                return response()->json([
                    'success' => false,
                    'error' => 'Ses verisi boş veya geçersiz.'
                ], 400);
            }
            
            // Google Cloud Speech-to-Text API anahtarı
            $apiKey = env('GOOGLE_SPEECH_TO_TEXT_API_KEY', 'AIzaSyByvVdGoNPyIm47FpDjB87TdASxOJKW6qg');
            
            // API isteği
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Goog-Api-Key' => $apiKey,
            ])->post('https://speech.googleapis.com/v1/speech:recognize', [
                'config' => [
                    'encoding' => $audioFormat,
                    'sampleRateHertz' => 48000,
                    'languageCode' => 'tr-TR',
                    'enableAutomaticPunctuation' => true,
                    'model' => 'default',
                    'useEnhanced' => true,
                    'profanityFilter' => false
                ],
                'audio' => [
                    'content' => base64_encode(file_get_contents($tempFilePath))
                ]
            ]);
            
            // Geçici dosyayı sil
            unlink($tempFilePath);
            
            if ($response->successful()) {
                $result = $response->json();
                Log::info('Speech-to-Text API yanıtı', [
                    'response' => $result
                ]);
                
                if (isset($result['results'][0]['alternatives'][0]['transcript'])) {
                    return response()->json([
                        'success' => true,
                        'text' => $result['results'][0]['alternatives'][0]['transcript']
                    ]);
                }
                
                return response()->json([
                    'success' => false,
                    'error' => 'Konuşma metne dönüştürülemedi.',
                    'details' => $result
                ]);
            }
            
            Log::error('Speech-to-Text API hatası', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'API yanıt hatası: ' . $response->status(),
                'details' => $response->json()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Speech-to-Text hatası: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'İşlem sırasında bir hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Metni konuşmaya dönüştür (Text-to-Speech)
     */
    public function convertTextToSpeech(Request $request)
    {
        try {
            // Metni al
            $text = $request->input('text');
            
            if (empty($text)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Metin boş olamaz.'
                ], 400);
            }
            
            // Text-to-Speech için maksimum karakter sınırı (Google API sınırı 5000)
            $maxChars = 4000;
            if (strlen($text) > $maxChars) {
                $text = substr($text, 0, $maxChars);
                Log::info('Metin kısaltıldı', ['original_length' => strlen($text), 'new_length' => $maxChars]);
            }
            
            // Metni temizle - GIF URL'lerini kaldır
            $text = preg_replace('/https:\/\/media\.tenor\.com\/[^\s]+\.gif/i', '', $text);
            
            // Emojileri SSML duygusal ifadelere dönüştür
            $text = $this->convertTextToEmotionalSpeech($text);
            
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);
            
            // Google Cloud Text-to-Speech API anahtarı
            $apiKey = env('GOOGLE_SPEECH_TO_TEXT_API_KEY', 'AIzaSyByvVdGoNPyIm47FpDjB87TdASxOJKW6qg');
            
            $isSsml = strpos($text, '<speak>') === 0;
            $inputType = $isSsml ? 'ssml' : 'text';
            
            // API isteği
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Goog-Api-Key' => $apiKey,
            ])->post('https://texttospeech.googleapis.com/v1/text:synthesize', [
                'input' => [
                    $inputType => $text
                ],
                'voice' => [
                    'languageCode' => 'tr-TR',
                    'name' => 'tr-TR-Wavenet-C', // Gelişmiş kadın ses (Wavenet-C)
                    'ssmlGender' => 'FEMALE'
                ],
                'audioConfig' => [
                    'audioEncoding' => 'MP3',
                    'speakingRate' => 1.05, // Biraz daha doğal konuşma hızı
                    'pitch' => 0.2, // Hafif daha tiz kadın sesi
                    'effectsProfileId' => ['telephony-class-application']
                ]
            ]);
            
            if ($response->successful()) {
                $result = $response->json();
                
                if (isset($result['audioContent'])) {
                    return response()->json([
                        'success' => true,
                        'audioContent' => $result['audioContent']
                    ]);
                }
                
                Log::error('Text-to-Speech içerik hatası', [
                    'result' => $result
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Metin sese dönüştürülemedi.',
                    'details' => $result
                ]);
            }
            
            Log::error('Text-to-Speech API hatası', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'API yanıt hatası: ' . $response->status(),
                'details' => $response->json()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Text-to-Speech hatası: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'İşlem sırasında bir hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Metni duygusal konuşmaya dönüştür (emojileri duygusal ifadelere çevir)
     */
    private function convertTextToEmotionalSpeech($text)
    {
        // Emoji varlığını kontrol et
        $hasEmojis = preg_match('/[\x{1F600}-\x{1F64F}|\x{1F300}-\x{1F5FF}|\x{1F680}-\x{1F6FF}|\x{2600}-\x{26FF}|\x{2700}-\x{27BF}]/u', $text);
        
        if (!$hasEmojis) {
            // Emoji yoksa normal temizleme
            return $this->cleanTextForSpeech($text);
        }
        
        // SSML formatına çevir
        $ssmlText = "<speak>";
        
        // Metni cümlelere böl
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        foreach ($sentences as $sentence) {
            // Her cümlenin duygusunu belirle
            $emotion = $this->detectSentenceEmotion($sentence);
            
            // Cümleden emojileri temizle
            $cleanSentence = $this->cleanTextForSpeech($sentence);
            if (empty(trim($cleanSentence))) continue;
            
            // Duyguya göre SSML formatında ses özellikleri ekle
            switch ($emotion) {
                case 'happy':
                    $ssmlText .= "<prosody rate=\"1.15\" pitch=\"+1.5st\">{$cleanSentence}</prosody> ";
                    break;
                case 'sad':
                    $ssmlText .= "<prosody rate=\"0.9\" pitch=\"-1st\">{$cleanSentence}</prosody> ";
                    break;
                case 'excited':
                    $ssmlText .= "<prosody volume=\"loud\" rate=\"1.2\" pitch=\"+2st\">{$cleanSentence}</prosody> ";
                    break;
                case 'calm':
                    $ssmlText .= "<prosody rate=\"0.95\" pitch=\"+0st\">{$cleanSentence}</prosody> ";
                    break;
                case 'questioning':
                    $ssmlText .= "<prosody pitch=\"+1st\">{$cleanSentence}</prosody> ";
                    break;
                default:
                    $ssmlText .= $cleanSentence . " ";
            }
            
            // Cümle aralarında kısa duraklamalar ekle
            $ssmlText .= "<break time=\"300ms\"/>";
        }
        
        $ssmlText .= "</speak>";
        return $ssmlText;
    }
    
    /**
     * Cümledeki emojilere göre duygu durumunu tespit et
     */
    private function detectSentenceEmotion($sentence)
    {
        // Mutlu emojiler
        if (preg_match('/[\x{1F600}-\x{1F607}|\x{1F609}-\x{1F60A}|\x{1F642}|\x{1F60D}|\x{1F618}|\x{1F970}|\x{1F60E}]/u', $sentence)) {
            return 'happy';
        }
        
        // Üzgün emojiler
        if (preg_match('/[\x{1F614}-\x{1F616}|\x{1F61E}-\x{1F61F}|\x{1F62D}|\x{1F622}-\x{1F62A}]/u', $sentence)) {
            return 'sad';
        }
        
        // Heyecanlı emojiler
        if (preg_match('/[\x{1F601}-\x{1F603}|\x{1F604}|\x{1F606}|\x{1F639}|\x{1F606}|\x{1F929}|\x{1F973}]/u', $sentence)) {
            return 'excited';
        }
        
        // Sakin/normal emojiler
        if (preg_match('/[\x{1F642}|\x{1F60C}|\x{1F610}|\x{1F636}]/u', $sentence)) {
            return 'calm';
        }
        
        // Soru işareti veya soru emofileri
        if (preg_match('/[\x{1F615}|\x{1F914}|\?]/u', $sentence)) {
            return 'questioning';
        }
        
        // Metin içinde duygu belirten sözcükler
        if (preg_match('/(mutlu|seviyorum|harika|güzel|süper|çok iyi)/ui', $sentence)) {
            return 'happy';
        }
        
        if (preg_match('/(üzgün|üzüldüm|kötü|maalesef)/ui', $sentence)) {
            return 'sad';
        }
        
        // Varsayılan olarak normal ton
        return 'normal';
    }
    
    /**
     * Metni seslendirilecek hale getir (emojileri ve kodları temizle)
     */
    private function cleanTextForSpeech($text)
    {
        // Emoji Unicode karakterlerini temizle
        $text = preg_replace('/[\x{1F600}-\x{1F64F}|\x{1F300}-\x{1F5FF}|\x{1F680}-\x{1F6FF}|\x{2600}-\x{26FF}|\x{2700}-\x{27BF}]/u', '', $text);
        
        // :emoji_ismi: formatındaki emojileri temizle
        $text = preg_replace('/:[a-z0-9_]+:/i', '', $text);
        
        // HTML emoji kodlarını temizle (&amp; gibi)
        $text = preg_replace('/&[a-z0-9]+;/i', '', $text);
        
        // URL'leri temizle
        $text = preg_replace('/https?:\/\/\S+/i', '', $text);
        
        // Konuşmaya daha doğal bir his vermek için bazı semboller düzelt
        $replacements = [
            ':)' => '',
            ':(' => '',
            ';)' => '',
            '<3' => '',
            '❤️' => '',
            '💖' => '',
            '' => '',
            '💘' => '',
            '💙' => '',
            '💚' => '',
            '💛' => '',
            '💜' => '',
            '😊' => '',
            '👍' => '',
            '🙂' => '',
            '😃' => '',
            '😄' => '',
            '🤗' => '',
            '😍' => '',
            '💕' => '',
            '😎' => '',
            '🤔' => '',
            '🤷' => '',
            '🤷‍♂️' => '',
            '🤷‍♀️' => ''
            
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }
    
    /**
     * Kaydedilen ses verisini sunucuya kaydet
     */
    public function saveRecordedAudio(Request $request)
    {
        try {
            // Ses dosyası alınır
            $audioData = $request->input('audio');
            
            if (!$audioData) {
                return response()->json([
                    'success' => false,
                    'error' => 'Ses verisi bulunamadı.'
                ], 400);
            }
            
            // Base64 kısmını çıkar ve dekode et
            $base64Data = '';
            if (strpos($audioData, 'data:audio/webm;base64,') !== false) {
                $base64Data = str_replace('data:audio/webm;base64,', '', $audioData);
                $extension = 'webm';
            } elseif (strpos($audioData, 'data:audio/wav;base64,') !== false) {
                $base64Data = str_replace('data:audio/wav;base64,', '', $audioData);
                $extension = 'wav';
            } elseif (strpos($audioData, 'data:audio/ogg;base64,') !== false) {
                $base64Data = str_replace('data:audio/ogg;base64,', '', $audioData);
                $extension = 'ogg';
            } elseif (strpos($audioData, 'data:audio/mp3;base64,') !== false) {
                $base64Data = str_replace('data:audio/mp3;base64,', '', $audioData);
                $extension = 'mp3';
            } elseif (strpos($audioData, 'data:audio/') !== false) {
                // Herhangi bir audio formatı
                $base64Data = substr($audioData, strpos($audioData, 'base64,') + 7);
                $extension = 'webm'; // varsayılan
            } else {
                // Ham base64 veri
                $base64Data = $audioData;
                $extension = 'webm'; // varsayılan
            }
            
            $decodedAudio = base64_decode($base64Data);
            $fileName = 'audio_' . uniqid() . '.' . $extension;
            
            // Klasör var mı kontrol et, yoksa oluştur
            $directory = 'public/audio';
            if (!Storage::exists($directory)) {
                Storage::makeDirectory($directory);
            }
            
            // Dosyayı Storage'a kaydet
            Storage::put($directory . '/' . $fileName, $decodedAudio);
            
            Log::info('Ses dosyası kaydedildi', [
                'fileName' => $fileName,
                'size' => strlen($decodedAudio)
            ]);
            
            return response()->json([
                'success' => true,
                'fileName' => $fileName,
                'path' => Storage::url('audio/' . $fileName)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Ses kayıt hatası: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Ses kaydedilirken bir hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }
} 