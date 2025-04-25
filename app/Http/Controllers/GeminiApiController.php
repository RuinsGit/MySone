<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GeminiApiService;
use Illuminate\Support\Facades\Log;

class GeminiApiController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiApiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Gemini API'ye direkt erişim sağlayan endpoint
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateContent(Request $request)
    {
        try {
            Log::info('Gelen istek (generate):', $request->all());
            
            // İstek parametrelerini al - JSON formatını veya form verilerini destekle
            $data = $request->json()->all() ?: $request->all();
            
            $prompt = $data['prompt'] ?? null;
            $options = $data['options'] ?? [];
            
            if (empty($prompt)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Prompt parametresi gereklidir.'
                ], 400);
            }
            
            // Gemini API'ye isteği gönder
            $result = $this->geminiService->generateContent($prompt, $options);
            
            // Yanıtı kontrolü
            if (!isset($result['success']) || !$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'API yanıtı işlenirken bir hata oluştu.'
                ], 500);
            }
            
            // Başarılı yanıtı döndür
            return response()->json([
                'success' => true,
                'data' => isset($result['text']) ? $result['text'] : $result
            ]);
            
        } catch (\Exception $e) {
            Log::error('Gemini API hatası: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'İstek işlenirken bir hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Kod talepleri için özel endpoint
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateCode(Request $request)
    {
        try {
            Log::info('Gelen istek (generate-code):', $request->all());
            
            // JSON verisini doğrudan al
            $content = $request->getContent();
            $data = json_decode($content, true);
            
            // JSON decode başarısız olursa, normal form verilerini dene
            if (json_last_error() !== JSON_ERROR_NONE) {
                $data = $request->all();
            }
            
            Log::info('İşlenen veri:', $data ?? []);
            
            $prompt = $data['prompt'] ?? null;
            $language = $data['language'] ?? 'javascript';
            
            if (empty($prompt)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Prompt parametresi gereklidir.'
                ], 400);
            }
            
            $result = $this->geminiService->generateCode($prompt, $language);
            
            if (!isset($result['success']) || !$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'API yanıtı işlenirken bir hata oluştu.'
                ], 500);
            }
            
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            Log::error('Gemini API kod üretme hatası: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'İstek işlenirken bir hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Sohbet yanıtı için endpoint
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateResponse(Request $request)
    {
        try {
            Log::info('Gelen istek (generate-response):', $request->all());
            
            // JSON verisini doğrudan al
            $content = $request->getContent();
            $data = json_decode($content, true);
            
            // JSON decode başarısız olursa, normal form verilerini dene
            if (json_last_error() !== JSON_ERROR_NONE) {
                $data = $request->all();
            }
            
            Log::info('İşlenen veri:', $data ?? []);
            
            $message = $data['message'] ?? null;
            $isCreative = $data['creative_mode'] ?? false;
            $isCodingRequest = $data['coding_mode'] ?? false;
            $chatHistory = $data['chat_history'] ?? [];
            
            if (empty($message)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message parametresi gereklidir.'
                ], 400);
            }
            
            $result = $this->geminiService->generateResponse($message, $isCreative, $isCodingRequest, $chatHistory);
            
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            Log::error('Gemini API yanıt üretme hatası: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'İstek işlenirken bir hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }
} 