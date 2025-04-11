<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\StartCodeLearning;
use App\Console\Commands\CodeConsciousnessCommand;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        StartCodeLearning::class,
        CodeConsciousnessCommand::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Veri toplama işlemini 30 dakikada bir çalıştır
        $schedule->command('ai:collect-data')
                 ->everyThirtyMinutes()
                 ->appendOutputTo(storage_path('logs/scheduler-collect.log'))
                 ->onFailure(function () {
                     \Log::error('ai:collect-data komutu çalıştırılamadı');
                 });
        
        // Bilinç sistemini 3 dakikada bir kontrol et
        $schedule->command('ai:consciousness --interval=180')
                 ->everyThreeMinutes()
                 ->appendOutputTo(storage_path('logs/scheduler-consciousness.log'))
                 ->onFailure(function () {
                     \Log::error('ai:consciousness komutu çalıştırılamadı');
                 });
        
        // Sürekli öğrenme işlemini 5 dakikada bir çalıştır
        $schedule->command('ai:learn --limit=50')
                 ->everyFiveMinutes()
                 ->appendOutputTo(storage_path('logs/scheduler-learn.log'))
                 ->onFailure(function () {
                     \Log::error('ai:learn komutu çalıştırılamadı');
                 });
        
        // Rasgele öğrenme işlemini 15 dakikada bir çalıştır
        $schedule->command('ai:learn --limit=20 --force')
                 ->everyFifteenMinutes()
                 ->appendOutputTo(storage_path('logs/scheduler-random-learn.log'))
                 ->onFailure(function () {
                     \Log::error('ai:learn --force komutu çalıştırılamadı');
                 });
        
        // Kelime ilişkilerini öğrenme işlemini 30 dakikada bir çalıştır
        $schedule->command('ai:learn-relations')
                 ->everyThirtyMinutes()
                 ->appendOutputTo(storage_path('logs/scheduler-relations.log'))
                 ->onFailure(function () {
                     \Log::error('ai:learn-relations komutu çalıştırılamadı');
                 });
                 
        // Her 10 dakikada bir otomatik cümle üretme işlemini çalıştır 
        $schedule->command('ai:generate-sentences --count=5')
                 ->everyTenMinutes()
                 ->appendOutputTo(storage_path('logs/scheduler-sentences.log'))
                 ->onFailure(function () {
                     \Log::error('ai:generate-sentences komutu çalıştırılamadı');
                 });
        
        // Saatlik veritabanı temizliği
        $schedule->command('ai:db-maintenance --mode=clean')
                 ->hourly()
                 ->appendOutputTo(storage_path('logs/db-maintenance.log'));
                 
        // Haftalık veritabanı optimizasyonu
        $schedule->command('ai:db-maintenance --mode=optimize')
                 ->weekly()
                 ->appendOutputTo(storage_path('logs/db-maintenance.log'));
        
        // Kategori sistemi güncelleme (5 dakikada bir çalıştır)
        $schedule->command('ai:init-categories')
                 ->everyFiveMinutes()
                 ->appendOutputTo(storage_path('logs/category-update.log'))
                 ->onFailure(function () {
                     \Log::error('ai:init-categories komutu çalıştırılamadı');
                 });

        // Kod öğrenme sistemi için kontrol zamanlaması - her 5 dakikada bir kod toplama sistemini çalıştırır
        $schedule->command('code:learn')->everyFiveMinutes();
        
        // Kod bilinç sistemi için zamanlanmış görevler
        $schedule->command('code:consciousness think')->hourly();
        $schedule->command('code:consciousness categorize --limit=20')->daily();
        $schedule->command('code:consciousness analyze --limit=30')->daily();
        $schedule->command('code:consciousness effectiveness --limit=50')->daily();

        // Sürekli öğrenme işlemini dakikada bir çalıştır
        $schedule->command('ai:learn --limit=50')
                 ->everyMinute()
                 ->appendOutputTo(storage_path('logs/scheduler-learn.log'))
                 ->onFailure(function () {
                     \Log::error('ai:learn komutu çalıştırılamadı');
                 });

        // Kod öğrenme sistemi için kontrol zamanlaması - her 30 saniyede bir kod toplama sistemini çalıştırır
        $schedule->command('code:learn')->everyThirtySeconds();
        
        // Kod bilinç sistemi için zamanlanmış görevler - her dakikada bir bilinç sistemini düşündür
        $schedule->command('code:consciousness think')->everyMinute();
        $schedule->command('code:consciousness categorize --limit=10')->hourly();
        $schedule->command('code:consciousness analyze --limit=15')->hourly();
        $schedule->command('code:consciousness effectiveness --limit=20')->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
