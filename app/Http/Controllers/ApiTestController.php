<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiTestController extends Controller
{
    /**
     * Lizz API endpointlerini test etmek için bir test sayfası
     * 
     * @return \Illuminate\View\View
     */
    public function testPage()
    {
        return view('api.test');
    }
    
    /**
     * Lizz API'yi test etmek için basit bir endpoint
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testGeminiApi(Request $request)
    {
        try {
            $prompt = $request->input('prompt', 'Merhaba, nasılsın?');
            
            // Kendi API endpointimize istek gönderiyoruz
            $response = Http::post(url('/api/lizz/generate'), [
                'prompt' => $prompt,
                'options' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 1024,
                ]
            ]);
            
            $responseData = $response->json();
            
            return response()->json([
                'success' => true,
                'request' => [
                    'prompt' => $prompt
                ],
                'response' => $responseData,
            ]);
            
        } catch (\Exception $e) {
            Log::error('API Test hatası: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Test sırasında bir hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Kod üretme API'sini test etmek için endpoint
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testCodeGeneration(Request $request)
    {
        try {
            $prompt = $request->input('prompt', 'PHP ile kullanıcı girişi formu oluştur');
            $language = $request->input('language', 'php');
            
            $response = Http::post(url('/api/lizz/generate-code'), [
                'prompt' => $prompt,
                'language' => $language
            ]);
            
            $responseData = $response->json();
            
            return response()->json([
                'success' => true,
                'request' => [
                    'prompt' => $prompt,
                    'language' => $language
                ],
                'response' => $responseData,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Kod API Test hatası: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Test sırasında bir hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Sohbet yanıtı API'sini test etmek için endpoint
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testChatResponse(Request $request)
    {
        try {
            $message = $request->input('message', 'Merhaba, nasılsın?');
            $creativeMode = $request->input('creative_mode', false);
            $codingMode = $request->input('coding_mode', false);
            
            $response = Http::post(url('/api/lizz/generate-response'), [
                'message' => $message,
                'creative_mode' => $creativeMode,
                'coding_mode' => $codingMode,
                'chat_history' => []
            ]);
            
            $responseData = $response->json();
            
            return response()->json([
                'success' => true,
                'request' => [
                    'message' => $message,
                    'creative_mode' => $creativeMode,
                    'coding_mode' => $codingMode
                ],
                'response' => $responseData,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Sohbet API Test hatası: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Test sırasında bir hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }
} 