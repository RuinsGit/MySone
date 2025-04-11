<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\AI\Core\CodeConsciousness;
use App\AI\Core\CodeRelationAnalyzer;
use App\AI\Core\CodeCategoryDetector;
use App\AI\Core\CodeUsagePredictorService;
use Illuminate\Support\Facades\Log;

class CodeConsciousnessCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'code:consciousness 
                            {action=status : Yapılacak işlem: status, activate, deactivate, think, analyze, categorize}
                            {--limit=50 : İşlem sınırı}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kod bilinç sistemini yönet ve bilgi al';

    /**
     * The code consciousness system instance.
     */
    protected $consciousness;
    protected $relationAnalyzer;
    protected $categoryDetector;
    protected $usagePredictor;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        CodeConsciousness $consciousness,
        CodeRelationAnalyzer $relationAnalyzer,
        CodeCategoryDetector $categoryDetector,
        CodeUsagePredictorService $usagePredictor
    ) {
        parent::__construct();
        
        $this->consciousness = $consciousness;
        $this->relationAnalyzer = $relationAnalyzer;
        $this->categoryDetector = $categoryDetector;
        $this->usagePredictor = $usagePredictor;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $action = $this->argument('action');
        $limit = $this->option('limit');
        
        switch ($action) {
            case 'status':
                $this->showStatus();
                break;
                
            case 'activate':
                $this->activateConsciousness();
                break;
                
            case 'deactivate':
                $this->deactivateConsciousness();
                break;
                
            case 'think':
                $this->startThinking();
                break;
                
            case 'analyze':
                $this->analyzeCodeRelations($limit);
                break;
                
            case 'categorize':
                $this->categorizeAllCodes($limit);
                break;
                
            case 'effectiveness':
                $this->analyzeEffectiveness($limit);
                break;
                
            default:
                $this->error("Bilinmeyen eylem: $action");
                $this->info("Kullanılabilir eylemler: status, activate, deactivate, think, analyze, categorize, effectiveness");
                return 1;
        }
        
        return 0;
    }
    
    /**
     * Bilinç sistemi durumunu göster
     */
    protected function showStatus()
    {
        $status = $this->consciousness->getStatus();
        
        $this->info('Kod Bilinç Sistemi Durumu:');
        $this->info('-------------------------');
        
        $this->info('Aktivasyon: ' . ($status['is_active'] ? 'Aktif' : 'İnaktif'));
        $this->info('Bilinç Seviyesi: ' . $status['consciousness_level']);
        $this->info('Öğrenme Hızı: ' . $status['learning_rate']);
        $this->info('Son Düşünme: ' . $status['last_thinking']);
        $this->info('İlişki Sayısı: ' . $status['code_relations_count']);
        $this->info('Kategori Sayısı: ' . $status['code_categories_count']);
        $this->info('Kullanım Kalıbı Sayısı: ' . $status['usage_patterns_count']);
    }
    
    /**
     * Bilinç sistemini aktifleştir
     */
    protected function activateConsciousness()
    {
        $this->info('Kod Bilinç Sistemi aktifleştiriliyor...');
        
        $result = $this->consciousness->activate();
        
        if ($result['success']) {
            $this->info('Kod Bilinç Sistemi başarıyla aktifleştirildi.');
            $this->info('Bilinç Seviyesi: ' . $result['consciousness_level']);
        } else {
            $this->error('Kod Bilinç Sistemi aktifleştirilemedi.');
        }
    }
    
    /**
     * Bilinç sistemini deaktifleştir
     */
    protected function deactivateConsciousness()
    {
        $this->info('Kod Bilinç Sistemi devre dışı bırakılıyor...');
        
        $result = $this->consciousness->deactivate();
        
        if ($result['success']) {
            $this->info('Kod Bilinç Sistemi başarıyla devre dışı bırakıldı.');
        } else {
            $this->error('Kod Bilinç Sistemi devre dışı bırakılamadı.');
        }
    }
    
    /**
     * Düşünme sürecini başlat
     */
    protected function startThinking()
    {
        $this->info('Bilinç sistemi düşünme sürecini başlatıyor...');
        
        $result = $this->consciousness->think();
        
        if ($result) {
            $this->info('Düşünme süreci tamamlandı.');
            
            // Güncellenmiş durumu göster
            $this->showStatus();
        } else {
            $this->error('Düşünme süreci başlatılamadı. Sistem inaktif olabilir.');
        }
    }
    
    /**
     * Kod ilişkilerini analiz et
     */
    protected function analyzeCodeRelations($limit)
    {
        $this->info("Kod ilişkileri analiz ediliyor (limit: $limit)...");
        
        $relations = $this->relationAnalyzer->analyzeAllCodeRelations($limit);
        
        $this->info('İlişki analizi tamamlandı.');
        $this->info('Toplam İlişki Sayısı: ' . count($relations));
        
        // İlişki tiplerini topla
        $relationTypes = [
            'similarity' => 0,
            'dependency' => 0,
            'complement' => 0,
            'alternative' => 0
        ];
        
        foreach ($relations as $relation) {
            foreach ($relation['relations'] as $rel) {
                $relationTypes[$rel['type']]++;
            }
        }
        
        $this->info('İlişki Tipleri:');
        foreach ($relationTypes as $type => $count) {
            $this->info("- $type: $count");
        }
    }
    
    /**
     * Tüm kodları kategorize et
     */
    protected function categorizeAllCodes($limit)
    {
        $this->info("Kodlar kategorize ediliyor (limit: $limit)...");
        
        $count = $this->categoryDetector->categorizeAllCodes($limit);
        
        $this->info('Kategorizasyon tamamlandı.');
        $this->info("Toplam $count kod kategorize edildi.");
    }
    
    /**
     * Kod etkinlik skorlarını analiz et
     */
    protected function analyzeEffectiveness($limit)
    {
        $this->info("Kod etkinlik skorları analiz ediliyor (limit: $limit)...");
        
        $count = $this->usagePredictor->analyzeEffectivenessScores(30, $limit);
        
        $this->info('Etkinlik skoru analizi tamamlandı.');
        $this->info("Toplam $count kod analiz edildi.");
    }
} 