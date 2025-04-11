<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\AI\Core\CodeLearningSystem;
use App\AI\Core\CodeConsciousness;
use Illuminate\Support\Facades\Log;

class StartCodeLearning extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'code:learn {--stop : Kod öğrenme sistemini durdur} {--force : Hemen öğrenmeyi zorla} {--count=5 : Zorunlu öğrenme sayısı}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'HTML ve CSS kod öğrenme sistemini başlat/durdur';

    /**
     * The code learning system instance.
     */
    protected $codeLearningSystem;
    
    /**
     * The code consciousness system instance.
     */
    protected $codeConsciousness;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(CodeLearningSystem $codeLearningSystem, CodeConsciousness $codeConsciousness)
    {
        parent::__construct();
        $this->codeLearningSystem = $codeLearningSystem;
        $this->codeConsciousness = $codeConsciousness;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->option('stop')) {
            $this->stopLearning();
        } elseif ($this->option('force')) {
            $this->forceLearning();
        } else {
            $this->startLearning();
        }

        return 0;
    }

    /**
     * Kod öğrenme sistemini başlat
     */
    private function startLearning()
    {
        $this->info('HTML/CSS kod öğrenme sistemi başlatılıyor...');
        
        try {
            // Bilinç sistemini etkinleştir
            $this->activateConsciousness();
            
            // Sistem ayarlarını yapılandır
            $settings = [
                'priority' => 'html',
                'rate' => 'medium',
                'focus' => 'css'
            ];
            
            $this->codeLearningSystem->updateSettings($settings);
            $result = $this->codeLearningSystem->activate();
            
            if ($result['success']) {
                $this->info('✓ Kod öğrenme sistemi başarıyla başlatıldı!');
                $this->info('✓ Sonraki öğrenme: ' . $result['next_update']);
                $this->info('✓ Etkin kaynak sayısı: ' . $result['source_count']);
                
                // Hemen öğrenmeyi başlatalım
                $this->info('İlk kod öğrenme işlemi başlatılıyor...');
                $count = $this->codeLearningSystem->learnNewCode();
                
                if ($count > 0) {
                    $this->info("✓ $count adet yeni kod öğrenildi!");
                    
                    // Bilinç sistemine düşünme komutu ver
                    $this->thinkAboutCodes();
                } else {
                    $this->warn('! Kod öğrenilemedi veya yeni kod bulunamadı.');
                }
            } else {
                $this->error('✗ Sistem başlatılamadı: ' . ($result['message'] ?? 'Bilinmeyen hata'));
            }
        } catch (\Exception $e) {
            Log::error('Kod öğrenme başlatma hatası: ' . $e->getMessage());
            $this->error('✗ Hata: ' . $e->getMessage());
        }
    }

    /**
     * Kod öğrenme sistemini durdur
     */
    private function stopLearning()
    {
        $this->info('Kod öğrenme sistemi durduruluyor...');
        
        try {
            $result = $this->codeLearningSystem->deactivate();
            
            if ($result['success']) {
                $this->info('✓ Kod öğrenme sistemi başarıyla durduruldu!');
                $this->info('✓ Son güncelleme: ' . $result['last_update']);
                $this->info('✓ İlerleme: %' . $result['progress_percentage']);
                
                // Bilinç sistemi durumunu sor
                $consciousnessStatus = $this->codeConsciousness->getStatus();
                $this->info('ℹ Bilinç sistemi durumu: ' . ($consciousnessStatus['is_active'] ? 'Aktif' : 'Pasif'));
                $this->info('ℹ Bilinç seviyesi: ' . $consciousnessStatus['consciousness_level']);
            } else {
                $this->error('✗ Sistem durdurulamadı: ' . ($result['message'] ?? 'Bilinmeyen hata'));
            }
        } catch (\Exception $e) {
            Log::error('Kod öğrenme durdurma hatası: ' . $e->getMessage());
            $this->error('✗ Hata: ' . $e->getMessage());
        }
    }
    
    /**
     * Manuel kod öğrenmeyi zorla
     */
    private function forceLearning()
    {
        $count = (int)$this->option('count');
        $this->info("Manuel kod öğrenme başlatılıyor (hedef: $count kod)...");
        
        try {
            // Bilinç sistemini etkinleştir
            $this->activateConsciousness();
            
            // Öğrenmeyi zorla
            $result = $this->codeLearningSystem->forceLearning($count);
            
            if ($result['success']) {
                $this->info('✓ Manuel kod öğrenme tamamlandı!');
                $this->info("✓ Öğrenilen kod sayısı: {$result['count']}");
                
                // Bilinç sistemine düşünme komutu ver
                $this->thinkAboutCodes();
            } else {
                $this->error('✗ Manuel öğrenme başarısız: ' . ($result['message'] ?? 'Bilinmeyen hata'));
            }
        } catch (\Exception $e) {
            Log::error('Manuel kod öğrenme hatası: ' . $e->getMessage());
            $this->error('✗ Hata: ' . $e->getMessage());
        }
    }
    
    /**
     * Bilinç sistemini aktifleştir
     */
    private function activateConsciousness()
    {
        $this->info('Kod bilinç sistemi etkinleştiriliyor...');
        
        try {
            $result = $this->codeConsciousness->activate();
            
            if ($result['success']) {
                $this->info('✓ Kod bilinç sistemi başarıyla etkinleştirildi!');
                $this->info('✓ Bilinç seviyesi: ' . $result['consciousness_level']);
            } else {
                $this->warn('! Bilinç sistemi etkinleştirilemedi: ' . ($result['message'] ?? 'Bilinmeyen hata'));
            }
        } catch (\Exception $e) {
            Log::warning('Bilinç sistemi etkinleştirme hatası: ' . $e->getMessage());
            $this->warn('! Bilinç sistemi etkinleştirilemedi: ' . $e->getMessage());
        }
    }
    
    /**
     * Bilinç sistemine kodlar hakkında düşünme komutu ver
     */
    private function thinkAboutCodes()
    {
        $this->info('Bilinç sistemi kodlar üzerinde düşünüyor...');
        
        try {
            $result = $this->codeConsciousness->think();
            
            if ($result) {
                $status = $this->codeConsciousness->getStatus();
                $this->info('✓ Düşünme işlemi tamamlandı!');
                $this->info('✓ İlişki sayısı: ' . $status['code_relations_count']);
                $this->info('✓ Kategori sayısı: ' . $status['code_categories_count']);
            } else {
                $this->warn('! Düşünme işlemi gerçekleştirilemedi.');
            }
        } catch (\Exception $e) {
            Log::warning('Düşünme hatası: ' . $e->getMessage());
            $this->warn('! Düşünme hatası: ' . $e->getMessage());
        }
    }
} 