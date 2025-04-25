<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AIController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\GeminiApiController;
use App\Http\Controllers\AICodeLearningController;
use App\Http\Controllers\AICodeConsciousnessController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// AI Routes
Route::prefix('ai')->group(function () {
    Route::post('/process', [AIController::class, 'processInput']);
    Route::get('/status', [AIController::class, 'getStatus']);
    Route::get('/word-relations', [AIController::class, 'getWordRelations']);
    Route::post('/generate-sentence', [AIController::class, 'generateSentence']);
});

// Mobil Chat API Rotaları
Route::prefix('chat')->group(function () {
    Route::post('/send-message', [ChatController::class, 'sendMessage']);
    Route::get('/history/{chatId?}', [ChatController::class, 'getChatHistory']);
    Route::post('/create', [ChatController::class, 'createChat']);
    Route::get('/list', [ChatController::class, 'getChats']);
    Route::delete('/delete/{chatId}', [ChatController::class, 'deleteChat']);
});

// Lizz API Rotaları
Route::prefix('lizz')->group(function () {
    Route::post('/generate', [GeminiApiController::class, 'generateContent']);
    Route::post('/generate-code', [GeminiApiController::class, 'generateCode']);
    Route::post('/generate-response', [GeminiApiController::class, 'generateResponse']);
});

// AI Kod Öğrenme API Rotaları
Route::group(['prefix' => 'ai/code-learning'], function () {
    Route::get('/status', [AICodeLearningController::class, 'getStatus']);
    Route::post('/start', [AICodeLearningController::class, 'startLearning']);
    Route::post('/stop', [AICodeLearningController::class, 'stopLearning']);
    Route::post('/settings', [AICodeLearningController::class, 'updateSettings']);
    Route::post('/add-code', [AICodeLearningController::class, 'addCode']);
    Route::get('/codes', [AICodeLearningController::class, 'listCodes']);
    Route::get('/activities', [AICodeLearningController::class, 'listActivities']);
    Route::get('/example', [AICodeLearningController::class, 'getCodeExample']);
    Route::get('/search', [AICodeLearningController::class, 'searchCode']);
    Route::post('/reset-cache', [AICodeLearningController::class, 'resetCache']);
    
    // Yeni eklenen HTML/CSS rotaları
    Route::get('/html-css-examples', [AICodeLearningController::class, 'getHtmlCssExamples']);
    Route::post('/add-html-css', [AICodeLearningController::class, 'addHtmlCssCode']);
    Route::post('/use-code', [AICodeLearningController::class, 'useCode']);
    Route::get('/usage-stats', [AICodeLearningController::class, 'getUsageStats']);
    Route::post('/similar-codes', [AICodeLearningController::class, 'findSimilarCodes']);
    Route::get('/featured-codes', [AICodeLearningController::class, 'getFeaturedCodes']);
});

/*
|--------------------------------------------------------------------------
| Code Consciousness API Routes
|--------------------------------------------------------------------------
|
| Kod bilinç sistemi için API rotaları. Bu sistem, kodların kategorilerini,
| ilişkilerini analiz eder ve en uygun kod kullanımını önerir.
|
*/

// Kod Bilinç Sistemi
Route::prefix('ai/code-consciousness')->group(function () {
    // Sistem durumu
    Route::get('/status', [AICodeConsciousnessController::class, 'status']);
    
    // Sistem kontrolü
    Route::post('/activate', [AICodeConsciousnessController::class, 'activate']);
    Route::post('/deactivate', [AICodeConsciousnessController::class, 'deactivate']);
    Route::post('/think', [AICodeConsciousnessController::class, 'think']);
    
    // Kod analizi
    Route::post('/analyze-relations', [AICodeConsciousnessController::class, 'analyzeRelations']);
    Route::post('/categorize', [AICodeConsciousnessController::class, 'categorize']);
    Route::post('/analyze-effectiveness', [AICodeConsciousnessController::class, 'analyzeEffectiveness']);
    
    // Kod önerileri
    Route::get('/suggest-codes', [AICodeConsciousnessController::class, 'suggestCodes']);
    Route::get('/suggest-code-flow', [AICodeConsciousnessController::class, 'suggestCodeFlow']);
    
    // Kod ilişkileri
    Route::post('/analyze-relationship', [AICodeConsciousnessController::class, 'analyzeCodeRelationship']);
    Route::post('/detect-category', [AICodeConsciousnessController::class, 'detectCategory']);
    
    // Kod kullanımı ve birleştirme
    Route::post('/record-usage', [AICodeConsciousnessController::class, 'recordCodeUsage']);
    Route::post('/combine-codes', [AICodeConsciousnessController::class, 'combineCodes']);
    
    // Bildirimler
    Route::get('/notifications', [AICodeConsciousnessController::class, 'getNotifications']);
});
