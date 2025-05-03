<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SeoSetting;
use Illuminate\Support\Facades\File;

class SeoSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // SEO ayarlarını oluştur veya güncelle
        $seoSettings = SeoSetting::firstOrNew(['id' => 1]);
        
        // Icon dosyasını public klasörüne kopyala
        $sourcePath = public_path('images/sone.png');
        $iconDir = public_path('uploads/seo');
        $targetPath = $iconDir . '/favicon.png';
        
        // Klasör yoksa oluştur
        if (!File::exists($iconDir)) {
            File::makeDirectory($iconDir, 0755, true);
        }
        
        // Dosya varsa kopyala
        if (File::exists($sourcePath)) {
            File::copy($sourcePath, $targetPath);
        }
        
        // Ayarları güncelle
        $seoSettings->site_title = 'LIZZ AI';
        $seoSettings->default_title = 'Yapay Zeka Asistan';
        $seoSettings->title_separator = '|';
        $seoSettings->meta_description = 'LIZZ AI yapay zeka asistanınız ile sohbet edin, sorular sorun ve anında cevaplar alın.';
        $seoSettings->meta_keywords = 'yapay zeka, ai, sohbet, asistan, chatbot, LIZZ AI';
        $seoSettings->favicon = 'uploads/seo/favicon.png';
        $seoSettings->canonical_self = true;
        
        $seoSettings->save();
        
        // Root favicon.ico dosyası oluştur
        if (File::exists($sourcePath)) {
            File::copy($sourcePath, public_path('favicon.ico'));
        }
    }
} 