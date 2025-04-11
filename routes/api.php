<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AIController;

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

// AI Kod Öğrenme API Rotaları
Route::group(['prefix' => 'ai/code-learning'], function () {
    Route::get('/status', 'App\Http\Controllers\AICodeLearningController@getStatus');
    Route::post('/start', 'App\Http\Controllers\AICodeLearningController@startLearning');
    Route::post('/stop', 'App\Http\Controllers\AICodeLearningController@stopLearning');
    Route::post('/settings', 'App\Http\Controllers\AICodeLearningController@updateSettings');
    Route::post('/add-code', 'App\Http\Controllers\AICodeLearningController@addCode');
    Route::get('/codes', 'App\Http\Controllers\AICodeLearningController@listCodes');
    Route::get('/activities', 'App\Http\Controllers\AICodeLearningController@listActivities');
    Route::get('/example', 'App\Http\Controllers\AICodeLearningController@getCodeExample');
    Route::get('/search', 'App\Http\Controllers\AICodeLearningController@searchCode');
    Route::post('/reset-cache', 'App\Http\Controllers\AICodeLearningController@resetCache');
    
    // Yeni eklenen HTML/CSS rotaları
    Route::get('/html-css-examples', 'App\Http\Controllers\AICodeLearningController@getHtmlCssExamples');
    Route::post('/add-html-css', 'App\Http\Controllers\AICodeLearningController@addHtmlCssCode');
    Route::post('/use-code', 'App\Http\Controllers\AICodeLearningController@useCode');
    Route::get('/usage-stats', 'App\Http\Controllers\AICodeLearningController@getUsageStats');
    Route::post('/similar-codes', 'App\Http\Controllers\AICodeLearningController@findSimilarCodes');
    Route::get('/featured-codes', 'App\Http\Controllers\AICodeLearningController@getFeaturedCodes');
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
    Route::get('/status', 'AICodeConsciousnessController@status');
    
    // Sistem kontrolü
    Route::post('/activate', 'AICodeConsciousnessController@activate');
    Route::post('/deactivate', 'AICodeConsciousnessController@deactivate');
    Route::post('/think', 'AICodeConsciousnessController@think');
    
    // Kod analizi
    Route::post('/analyze-relations', 'AICodeConsciousnessController@analyzeRelations');
    Route::post('/categorize', 'AICodeConsciousnessController@categorize');
    Route::post('/analyze-effectiveness', 'AICodeConsciousnessController@analyzeEffectiveness');
    
    // Kod önerileri
    Route::get('/suggest-codes', 'AICodeConsciousnessController@suggestCodes');
    Route::get('/suggest-code-flow', 'AICodeConsciousnessController@suggestCodeFlow');
    
    // Kod ilişkileri
    Route::post('/analyze-relationship', 'AICodeConsciousnessController@analyzeCodeRelationship');
    Route::post('/detect-category', 'AICodeConsciousnessController@detectCategory');
    
    // Kod kullanımı ve birleştirme
    Route::post('/record-usage', 'AICodeConsciousnessController@recordCodeUsage');
    Route::post('/combine-codes', 'AICodeConsciousnessController@combineCodes');
    
    // Bildirimler
    Route::get('/notifications', 'AICodeConsciousnessController@getNotifications');
});
