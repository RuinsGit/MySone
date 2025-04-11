<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\AI\Core\CodeConsciousness;
use App\AI\Core\CodeRelationAnalyzer;
use App\AI\Core\CodeCategoryDetector;
use App\AI\Core\CodeUsagePredictorService;

class AIServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Kod Bilinç Sistemi
        $this->app->singleton(CodeConsciousness::class, function ($app) {
            return new CodeConsciousness();
        });
        
        // Kod İlişki Analizi
        $this->app->singleton(CodeRelationAnalyzer::class, function ($app) {
            return new CodeRelationAnalyzer();
        });
        
        // Kod Kategori Tespiti
        $this->app->singleton(CodeCategoryDetector::class, function ($app) {
            return new CodeCategoryDetector();
        });
        
        // Kod Kullanımı Tahmini
        $this->app->singleton(CodeUsagePredictorService::class, function ($app) {
            return new CodeUsagePredictorService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
} 