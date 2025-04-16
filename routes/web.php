<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AIController as AdminAIController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\AIChatController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ManageController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\AIController;
use App\Http\Controllers\ConsciousnessController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    if (auth()->guard('admin')->check()) {
            return redirect()->route('back.pages.index');
        }
        return redirect()->route('admin.login');
});

Route::prefix('admin')->group(function () {
    Route::get('/', function () {
        if (auth()->guard('admin')->check()) {
            return redirect()->route('back.pages.index');
        }
        return redirect()->route('admin.login');
    });

    Route::get('login', [AdminController::class, 'showLoginForm'])->name('admin.login')->middleware('guest:admin');
    Route::post('login', [AdminController::class, 'login'])->name('handle-login');

    Route::middleware('auth:admin')->group(function () {
        Route::get('dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

        Route::get('profile', function () {
            return view('back.admin.profile');
        })->name('admin.profile');

        Route::post('logout', [AdminController::class, 'logout'])->name('admin.logout');

        // Kullanıcı istatistikleri için rotalar
        Route::prefix('user-stats')->name('admin.user-stats.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\UserStatsController::class, 'index'])->name('index');
            Route::get('/ip/{ip}', [App\Http\Controllers\Admin\UserStatsController::class, 'showIpDetails'])->name('ip-details');
            Route::get('/visitor/{visitorId}', [App\Http\Controllers\Admin\UserStatsController::class, 'showVisitorDetails'])->name('visitor-details');
        });

        // AI Modelleri için rotalar
        Route::prefix('ai')->name('admin.ai.')->group(function () {
            Route::get('/', [AdminAIController::class, 'index'])->name('index');
            Route::get('/create', [AdminAIController::class, 'create'])->name('create');
            Route::post('/', [AdminAIController::class, 'store'])->name('store');
            Route::get('/{aiModel}', [AdminAIController::class, 'show'])->name('show');
            Route::get('/{aiModel}/edit', [AdminAIController::class, 'edit'])->name('edit');
            Route::put('/{aiModel}', [AdminAIController::class, 'update'])->name('update');
            Route::delete('/{aiModel}', [AdminAIController::class, 'destroy'])->name('destroy');
            Route::post('/{aiModel}/train', [AdminAIController::class, 'train'])->name('train');
            Route::post('/{aiModel}/generate-response', [AdminAIController::class, 'generateResponse'])->name('generate-response');
        });

        // AI Sohbet için rotalar
        Route::prefix('chat')->name('back.chat.')->group(function () {
            Route::get('/', [AIChatController::class, 'index'])->name('index');
            Route::get('/create', [AIChatController::class, 'create'])->name('create');
            Route::post('/', [AIChatController::class, 'store'])->name('store');
            Route::get('/{conversation}', [AIChatController::class, 'show'])->name('show');
            Route::post('/{conversation}/send', [AIChatController::class, 'sendMessage'])->name('send-message');
        });

        // Sayfalar için rotalar
        Route::prefix('pages')->name('back.pages.')->group(function () {
            Route::get('/', [PageController::class, 'index'])->name('index');
            Route::get('/create', [PageController::class, 'create'])->name('create');
            Route::post('/', [PageController::class, 'store'])->name('store');
            Route::get('/{page}/edit', [PageController::class, 'edit'])->name('edit');
            Route::put('/{page}', [PageController::class, 'update'])->name('update');
            Route::delete('/{page}', [PageController::class, 'destroy'])->name('destroy');
        });
    });
});

Route::get('/', [ChatController::class, 'index'])->name('chat');

// Yönetim Paneli Routes
Route::prefix('manage')->name('manage.')->group(function () {
    Route::get('/', [ManageController::class, 'index'])->name('index');
    Route::post('/', [ManageController::class, 'index'])->name('login');
    
    // Ayarlar ve eğitim
    Route::post('/update-settings', [ManageController::class, 'updateSettings']);
    Route::post('/train', [ManageController::class, 'trainModel']);
    Route::get('/train-status', [ManageController::class, 'getSystemStatus']);
    
    // Otomatik eğitim sistemi
    Route::post('/automated-learning', [ManageController::class, 'startAutomatedLearning']);
    
    // Yeni eğitim ve öğrenme sistemi
    Route::post('/training/start', [ManageController::class, 'startTrainingProcess']);
    Route::get('/training/status', [ManageController::class, 'getTrainingStatus']);
    Route::post('/learning/start', [ManageController::class, 'startLearningProcess']);
    Route::get('/learning/status', [ManageController::class, 'getLearningStatus']);
    Route::get('/learning/progress', [ManageController::class, 'getLearningProgress']);
    Route::get('/learning/stats', [ManageController::class, 'getLearningSystemStats']);
    
    // Kelime öğrenme
    Route::post('/word/learn', [ManageController::class, 'learnWord']);
    Route::get('/word/search', [ManageController::class, 'searchWord']);
    
    // Veritabanı bakımı
    Route::post('/learning/clear', [ManageController::class, 'clearLearningSystem']);
    Route::post('/maintenance/delete-recent', [ManageController::class, 'deleteRecentData']);
    Route::post('/maintenance/delete-old', [ManageController::class, 'deleteOldData']);
    Route::post('/maintenance/cleanup', [ManageController::class, 'cleanupData']);
    Route::post('/maintenance/optimize', [ManageController::class, 'optimizeDatabase']);
    Route::post('/maintenance/enhance-relations', [ManageController::class, 'enhanceWordRelations']);
});

// Kelime listesi sayfası için yeni rota
Route::get('/manage/words', [App\Http\Controllers\ManageController::class, 'getAllWords'])->name('manage.words');

// API rotaları
Route::prefix('api/ai')->group(function () {
    Route::post('/chat', [AIController::class, 'chat']);
    Route::get('/word/{word}', [AIController::class, 'getWordInfoByParam']);
    Route::get('/search', [AIController::class, 'searchWords']);
    Route::get('/status', [AIController::class, 'getStatus']);
    Route::get('/learning-status', [AIController::class, 'getLearningStatus']);
    Route::get('/chat/{chat_id}', [AIController::class, 'getChatHistory']);
    Route::get('/chats', [AIController::class, 'getUserChats']);
});

// APIController için API route
Route::post('/api/ai/process', [ChatController::class, 'sendMessage']);

// Arama API rotaları
Route::prefix('api/search')->group(function () {
    Route::get('/', [SearchController::class, 'search']);
    Route::get('/ai', [SearchController::class, 'aiSearch']);
});

// Arama sonuç sayfası rotası (HTML görünümü için)
Route::get('/search', [SearchController::class, 'search'])->name('search');

// Yönetim API Endpointleri
Route::prefix('api/manage')->group(function () {
    Route::post('/learning/start', [ManageController::class, 'startLearningProcess']);
    Route::get('/learning/status', [ManageController::class, 'getLearningStatus']);
    Route::get('/learning/stats', [ManageController::class, 'getLearningSystemStats']);
    Route::post('/learning/word', [ManageController::class, 'learnWord']);
    Route::post('/learning/clear', [ManageController::class, 'clearLearningSystem']);
    Route::post('/learning/generate-sentences', [ManageController::class, 'generateSmartSentences']);
    Route::post('/learning/auto-sentences', [ManageController::class, 'generateAutoSentences']);
});

// Bilinç Sistemi Rotaları
Route::prefix('consciousness')->group(function () {
    Route::get('/', [ConsciousnessController::class, 'index'])->name('consciousness.index');
    Route::post('/activate', [ConsciousnessController::class, 'activate'])->name('consciousness.activate');
    Route::post('/deactivate', [ConsciousnessController::class, 'deactivate'])->name('consciousness.deactivate');
    Route::post('/process-message', [ConsciousnessController::class, 'processMessage'])->name('consciousness.process-message');
    Route::post('/learning-rate', [ConsciousnessController::class, 'setLearningRate'])->name('consciousness.learning-rate');
    Route::post('/personality', [ConsciousnessController::class, 'updatePersonality'])->name('consciousness.personality');
    Route::post('/identity', [ConsciousnessController::class, 'updateSelfIdentity'])->name('consciousness.identity');
    Route::get('/metrics', [ConsciousnessController::class, 'getMetrics'])->name('consciousness.metrics');
});

// AI Kod Öğrenme Sayfası
Route::get('/ai/code-learning', [App\Http\Controllers\AICodeLearningController::class, 'showLearningInterface'])->name('ai.code-learning');

// AI Kod Bilinç Sistemi Sayfası
Route::get('/ai/code-consciousness', function() {
    return view('ai.consciousness.realtime-processing');
})->name('ai.code-consciousness');

// AI Kod Öğrenme API Rotaları
Route::prefix('api/ai/code-learning')->group(function () {
    Route::get('/status', [App\Http\Controllers\AICodeLearningController::class, 'getStatus']);
    Route::post('/start', [App\Http\Controllers\AICodeLearningController::class, 'startLearning']);
    Route::post('/stop', [App\Http\Controllers\AICodeLearningController::class, 'stopLearning']);
    Route::post('/settings', [App\Http\Controllers\AICodeLearningController::class, 'updateSettings']);
    Route::post('/add-code', [App\Http\Controllers\AICodeLearningController::class, 'addCode']);
    Route::get('/code-example', [App\Http\Controllers\AICodeLearningController::class, 'getCodeExample']);
    Route::get('/search-code', [App\Http\Controllers\AICodeLearningController::class, 'searchCode']);
    Route::post('/reset-cache', [App\Http\Controllers\AICodeLearningController::class, 'resetCache']);
    Route::post('/force-learning', [App\Http\Controllers\AICodeLearningController::class, 'forceLearning']);
    Route::get('/consciousness-status', [App\Http\Controllers\AICodeLearningController::class, 'getConsciousnessStatus']);
    Route::post('/consciousness-think', [App\Http\Controllers\AICodeLearningController::class, 'triggerConsciousnessThinking']);
    Route::post('/consciousness-toggle', [App\Http\Controllers\AICodeLearningController::class, 'toggleConsciousness']);
    
    // Yeni eklenen özellikler için rotalar
    Route::post('/recommendations', [App\Http\Controllers\AICodeLearningController::class, 'getCodeRecommendations']);
    Route::get('/system-analysis', [App\Http\Controllers\AICodeLearningController::class, 'analyzeSystemPerformance']);
    Route::get('/languages', [App\Http\Controllers\AICodeLearningController::class, 'getLanguageStats']);
    Route::get('/categories', [App\Http\Controllers\AICodeLearningController::class, 'getCategoryStats']);
});

// AI Kod Bilinç Sistemi API Rotaları
Route::prefix('api/ai/code-consciousness')->group(function () {
    Route::get('/test', [App\Http\Controllers\AICodeConsciousnessController::class, 'test']);
    Route::get('/status', [App\Http\Controllers\AICodeConsciousnessController::class, 'status']);
    Route::post('/activate', [App\Http\Controllers\AICodeConsciousnessController::class, 'activate']);
    Route::post('/deactivate', [App\Http\Controllers\AICodeConsciousnessController::class, 'deactivate']);
    Route::post('/think', [App\Http\Controllers\AICodeConsciousnessController::class, 'think']);
    Route::get('/notifications', [App\Http\Controllers\AICodeConsciousnessController::class, 'getNotifications']);
    
    // Gerçek zamanlı kod işleme rotaları
    Route::post('/process-single', [App\Http\Controllers\AICodeConsciousnessController::class, 'processSingleCode']);
    Route::post('/process-batch', [App\Http\Controllers\AICodeConsciousnessController::class, 'processBatchCodes']);
    Route::get('/processing-status', [App\Http\Controllers\AICodeConsciousnessController::class, 'getProcessingStatus']);
    Route::post('/reset-processed', [App\Http\Controllers\AICodeConsciousnessController::class, 'resetProcessedCodes']);
    Route::get('/recent-processed', [App\Http\Controllers\AICodeConsciousnessController::class, 'getRecentProcessedCodes']);
    
    // Kod ilişki ve kategori analizi rotaları
    Route::post('/analyze-relations', [App\Http\Controllers\AICodeConsciousnessController::class, 'analyzeRelations']);
    Route::post('/categorize', [App\Http\Controllers\AICodeConsciousnessController::class, 'categorize']);
    Route::post('/analyze-effectiveness', [App\Http\Controllers\AICodeConsciousnessController::class, 'analyzeEffectiveness']);
    Route::get('/suggest-codes', [App\Http\Controllers\AICodeConsciousnessController::class, 'suggestCodes']);
    Route::get('/suggest-flow', [App\Http\Controllers\AICodeConsciousnessController::class, 'suggestCodeFlow']);
});

// AI rotaları
Route::prefix('ai')->group(function () {
    Route::get('/chat', [AIController::class, 'chat'])->name('ai.chat');
    Route::post('/active-user', [AIController::class, 'updateActiveUser'])->name('ai.active-user');
    Route::get('/active-users', [AIController::class, 'getActiveUsers'])->name('ai.active-users');
    Route::get('/active-users-admin', [AIController::class, 'showActiveUsersAdmin'])->name('ai.active-users-admin');
});
