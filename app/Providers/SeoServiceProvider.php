<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\SeoSetting;

class SeoServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Tüm view'lara SEO ayarlarını ekleyelim
        View::composer('*', function ($view) {
            try {
                $seoSettings = SeoSetting::global();
                $view->with('seoSettings', $seoSettings);
            } catch (\Exception $e) {
                // Veritabanı tablomuz henüz oluşturulmamış olabilir
                // Bu durumda boş bir obje oluşturalım
                $seoSettings = new \stdClass();
                $seoSettings->site_title = 'LIZZ AI';
                $seoSettings->default_title = 'Yapay Zeka Asistan';
                $seoSettings->title_separator = '|';
                $seoSettings->meta_description = '';
                $seoSettings->meta_keywords = '';
                $seoSettings->head_scripts = '';
                $seoSettings->body_start_scripts = '';
                $seoSettings->body_end_scripts = '';
                $seoSettings->google_analytics = '';
                $seoSettings->google_tag_manager = '';
                $seoSettings->noindex = false;
                $seoSettings->nofollow = false;
                $seoSettings->canonical_self = true;
                $view->with('seoSettings', $seoSettings);
            }
        });
    }
} 