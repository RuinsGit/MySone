<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SeoSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SeoController extends Controller
{
    /**
     * SEO ayarları sayfasını göster
     */
    public function index()
    {
        $seoSettings = SeoSetting::global();
        
        \Log::info("SEO ayarları sayfası açıldı", [
            'user_id' => auth()->id() ?? 'Konuk',
            'ip' => request()->ip()
        ]);
        
        return view('admin.seo.index', compact('seoSettings'));
    }
    
    /**
     * SEO ayarlarını güncelle
     */
    public function update(Request $request)
    {
        $request->validate([
            'site_title' => 'nullable|string|max:100',
            'default_title' => 'nullable|string|max:100',
            'title_separator' => 'nullable|string|max:20',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
            'head_scripts' => 'nullable|string',
            'body_start_scripts' => 'nullable|string',
            'body_end_scripts' => 'nullable|string',
            'google_analytics' => 'nullable|string',
            'google_tag_manager' => 'nullable|string',
            'robots_txt' => 'nullable|string',
            'google_verification' => 'nullable|string',
            'favicon' => 'nullable|image|mimes:ico,png,jpg,jpeg,svg|max:5120',
            'og_image' => 'nullable|image|mimes:png,jpg,jpeg|max:5120',
            'noindex' => 'boolean',
            'nofollow' => 'boolean',
            'canonical_self' => 'boolean',
        ]);
        
        try {
            $seoSettings = SeoSetting::global();
            
            // Dosya yüklemeleri için
            if ($request->hasFile('favicon')) {
                try {
                    if ($seoSettings->favicon) {
                        // Eğer eski dosya varsa ve public klasöründe ise sil
                        if (file_exists(public_path($seoSettings->favicon))) {
                            unlink(public_path($seoSettings->favicon));
                        }
                    }
                    
                    $faviconFile = $request->file('favicon');
                    $faviconExtension = $faviconFile->getClientOriginalExtension();
                    $faviconName = 'favicon_' . time() . '.' . $faviconExtension;
                    $faviconPath = 'uploads/seo/';
                    
                    // Dizin yoksa oluştur
                    if (!file_exists(public_path($faviconPath))) {
                        mkdir(public_path($faviconPath), 0777, true);
                    }
                    
                    // Dosyayı public klasörüne taşı
                    $faviconFile->move(public_path($faviconPath), $faviconName);
                    
                    // Veritabanına dosya yolunu kaydet (storage yerine doğrudan public yolu)
                    $seoSettings->favicon = $faviconPath . $faviconName;
                    
                    \Log::info('Favicon başarıyla public klasörüne yüklendi', [
                        'path' => $seoSettings->favicon
                    ]);
                    
                } catch (\Exception $e) {
                    Log::error('Favicon yüklenirken hata:', [
                        'error' => $e->getMessage(),
                        'file' => $request->file('favicon')->getClientOriginalName(),
                        'size' => $request->file('favicon')->getSize()
                    ]);
                    return redirect()->back()->with('error', 'Favicon yüklenirken bir hata oluştu: ' . $e->getMessage());
                }
            }
            
            if ($request->hasFile('og_image')) {
                try {
                    if ($seoSettings->og_image) {
                        // Eğer eski dosya varsa ve public klasöründe ise sil
                        if (file_exists(public_path($seoSettings->og_image))) {
                            unlink(public_path($seoSettings->og_image));
                        }
                    }
                    
                    $ogImageFile = $request->file('og_image');
                    $ogImageExtension = $ogImageFile->getClientOriginalExtension();
                    $ogImageName = 'og_image_' . time() . '.' . $ogImageExtension;
                    $ogImagePath = 'uploads/seo/';
                    
                    // Dizin yoksa oluştur
                    if (!file_exists(public_path($ogImagePath))) {
                        mkdir(public_path($ogImagePath), 0777, true);
                    }
                    
                    // Dosyayı public klasörüne taşı
                    $ogImageFile->move(public_path($ogImagePath), $ogImageName);
                    
                    // Veritabanına dosya yolunu kaydet (storage yerine doğrudan public yolu)
                    $seoSettings->og_image = $ogImagePath . $ogImageName;
                    
                    \Log::info('OG Image başarıyla public klasörüne yüklendi', [
                        'path' => $seoSettings->og_image
                    ]);
                    
                } catch (\Exception $e) {
                    Log::error('OG Image yüklenirken hata:', [
                        'error' => $e->getMessage(),
                        'file' => $request->file('og_image')->getClientOriginalName(),
                        'size' => $request->file('og_image')->getSize()
                    ]);
                    return redirect()->back()->with('error', 'Sosyal medya görseli yüklenirken bir hata oluştu: ' . $e->getMessage());
                }
            }
            
            // Diğer alanları güncelle
            $seoSettings->update($request->except(['favicon', 'og_image']));
            
            // Robots.txt dosyasını güncelle
            if ($request->filled('robots_txt')) {
                Storage::disk('public')->put('robots.txt', $request->robots_txt);
            }
            
            \Log::info("SEO ayarları güncellendi", [
                'user_id' => auth()->id() ?? 'Konuk',
                'ip' => request()->ip()
            ]);
            
            return redirect()->route('admin.seo.index')->with('success', 'SEO ayarları başarıyla güncellendi.');
        } catch (\Exception $e) {
            Log::error('SEO ayarları güncellenirken hata:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'SEO ayarları güncellenirken bir hata oluştu: ' . $e->getMessage());
        }
    }
    
    /**
     * Meta görünümü önizleme
     */
    public function preview()
    {
        $seoSettings = SeoSetting::global();
        return view('admin.seo.preview', compact('seoSettings'));
    }
    
    /**
     * Robots.txt dosyasını güncelle
     */
    public function updateRobots(Request $request)
    {
        $request->validate([
            'robots_content' => 'required|string'
        ]);
        
        try {
            Storage::disk('public')->put('robots.txt', $request->robots_content);
            
            // Ayarları da güncelle
            $seoSettings = SeoSetting::global();
            $seoSettings->robots_txt = $request->robots_content;
            $seoSettings->save();
            
            return redirect()->route('admin.seo.index')->with('success', 'Robots.txt dosyası başarıyla güncellendi.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Robots.txt dosyası güncellenirken bir hata oluştu: ' . $e->getMessage());
        }
    }
    
    /**
     * PHP dosya yükleme ayarlarını kontrol et
     */
    public function checkUploadSettings()
    {
        $settings = [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_file_uploads' => ini_get('max_file_uploads'),
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit')
        ];
        
        return view('admin.seo.upload-settings', compact('settings'));
    }
} 