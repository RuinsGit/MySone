<?php

namespace App\Http\Controllers;

use App\Models\AICoding;
use App\Models\AICodingPattern;
use App\Models\AICodingSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AICodingController extends Controller
{
    /**
     * Kodlama isteğini işle
     */
    public function processCode(Request $request)
    {
        try {
            $request->validate([
                'message' => 'required|string',
                'language' => 'required|string',
                'session_id' => 'nullable|string'
            ]);

            $message = $request->input('message');
            $language = $request->input('language');
            $sessionId = $request->input('session_id');

            // Mesajı analiz et
            $analysis = $this->analyzeCodingRequest($message);

            // Yanıt ve kod oluştur
            $response = $this->generateCodingResponse($message, $language, $analysis);

            // Oturum bilgilerini güncelle
            if ($sessionId) {
                $this->updateSession($sessionId, $message, $response, $language);
            } else {
                $sessionId = $this->createSession($message, $response, $language);
            }

            return response()->json([
                'success' => true,
                'response' => $response['explanation'],
                'code' => $response['code'],
                'session_id' => $sessionId,
                'language' => $language
            ]);

        } catch (\Exception $e) {
            Log::error('Kodlama isteği işleme hatası: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Kod üretilirken bir hata oluştu'
            ], 500);
        }
    }

    /**
     * Kodlama isteğini analiz et
     */
    private function analyzeCodingRequest($message)
    {
        $analysis = [
            'type' => 'unknown',
            'keywords' => [],
            'context' => [],
            'complexity' => 0
        ];

        // İstek tipini belirle
        if (str_contains(strtolower($message), ['nasıl', 'örnek'])) {
            $analysis['type'] = 'example';
        } elseif (str_contains(strtolower($message), ['oluştur', 'yap', 'yaz'])) {
            $analysis['type'] = 'create';
        } elseif (str_contains(strtolower($message), ['düzelt', 'hata', 'debug'])) {
            $analysis['type'] = 'debug';
        } elseif (str_contains(strtolower($message), ['optimize', 'iyileştir'])) {
            $analysis['type'] = 'optimize';
        }

        // Anahtar kelimeleri çıkar
        $keywords = array_filter(explode(' ', strtolower($message)), function($word) {
            return strlen($word) > 3;
        });
        $analysis['keywords'] = array_values($keywords);

        // Karmaşıklık seviyesini belirle
        $analysis['complexity'] = $this->determineComplexity($message);

        return $analysis;
    }

    /**
     * Kodlama yanıtı oluştur
     */
    private function generateCodingResponse($message, $language, $analysis)
    {
        // AI Coding verilerini al
        $aiCoding = AICoding::where('language', $language)
            ->orderBy('usage_count', 'desc')
            ->get();

        // Kod pattern'lerini al
        $patterns = AICodingPattern::where('language', $language)
            ->orderBy('usage_count', 'desc')
            ->get();

        $response = [
            'explanation' => '',
            'code' => '',
            'pattern_id' => null
        ];

        // En uygun pattern'i bul
        $pattern = $this->findBestPattern($analysis, $patterns);
        if ($pattern) {
            $response['pattern_id'] = $pattern->id;
            $code = $this->generateCodeFromPattern($pattern, $analysis);
            $response['code'] = $code;
            $response['explanation'] = "İşte {$language} dilinde bir örnek kod:";
        } else {
            // Pattern bulunamazsa benzer kodları kullan
            $similarCode = $this->findSimilarCode($analysis, $aiCoding);
            if ($similarCode) {
                $response['code'] = $this->modifyExistingCode($similarCode, $analysis);
                $response['explanation'] = "İşte isteğinize uygun bir kod örneği:";
            } else {
                // Yeni kod oluştur
                $response['code'] = $this->generateNewCode($language, $analysis);
                $response['explanation'] = "İsteğinize göre yeni bir kod oluşturdum:";
            }
        }

        // Kodu veritabanına kaydet
        $this->saveGeneratedCode($response['code'], $language, $analysis);

        return $response;
    }

    /**
     * Oturum oluştur
     */
    private function createSession($message, $response, $language)
    {
        $session = AICodingSession::create([
            'session_id' => uniqid('code_'),
            'context' => $message,
            'conversation_history' => [
                [
                    'role' => 'user',
                    'content' => $message
                ],
                [
                    'role' => 'assistant',
                    'content' => $response['explanation'],
                    'code' => $response['code']
                ]
            ],
            'code_snippets' => [
                [
                    'code' => $response['code'],
                    'language' => $language,
                    'timestamp' => now()
                ]
            ],
            'active_language' => $language
        ]);

        return $session->session_id;
    }

    /**
     * Oturumu güncelle
     */
    private function updateSession($sessionId, $message, $response, $language)
    {
        $session = AICodingSession::where('session_id', $sessionId)->first();
        if (!$session) {
            return $this->createSession($message, $response, $language);
        }

        $history = $session->conversation_history;
        $snippets = $session->code_snippets;

        // Konuşma geçmişini güncelle
        $history[] = [
            'role' => 'user',
            'content' => $message
        ];
        $history[] = [
            'role' => 'assistant',
            'content' => $response['explanation'],
            'code' => $response['code']
        ];

        // Kod parçacıklarını güncelle
        $snippets[] = [
            'code' => $response['code'],
            'language' => $language,
            'timestamp' => now()
        ];

        $session->update([
            'conversation_history' => $history,
            'code_snippets' => $snippets,
            'active_language' => $language
        ]);

        return $session->session_id;
    }

    // Diğer yardımcı metodlar buraya gelecek...
    private function determineComplexity($message) { /* ... */ }
    private function findBestPattern($analysis, $patterns) { /* ... */ }
    private function generateCodeFromPattern($pattern, $analysis) { /* ... */ }
    private function findSimilarCode($analysis, $aiCoding) { /* ... */ }
    private function modifyExistingCode($code, $analysis) { /* ... */ }
    private function generateNewCode($language, $analysis) { /* ... */ }
    private function saveGeneratedCode($code, $language, $analysis) { /* ... */ }
} 