<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\ChatMessage;
use App\Models\Chat;

class CreateVisitorIDsTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:visitor_ids_table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tüm ziyaretçiler için visitor_id kayıtlarını oluştur';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Visitor ID tablo kontrolü ve mesaj onarımı başlatılıyor...');

        // 1. Visitor_names tablosunun varlığını kontrol et
        if (!Schema::hasTable('visitor_names')) {
            $this->error('visitor_names tablosu bulunamadı! Önce migration çalıştırın.');
            return 1;
        }

        // 2. Var olan chat_id'lerin listesini veritabanından al
        $this->info('Mevcut chat_id\'leri kontrol ediliyor...');
        $existingChatIds = Chat::pluck('id')->toArray();
        $this->info(count($existingChatIds) . ' adet chat kaydı bulundu.');

        // 3. Mesajlardaki chat_id'leri kontrol et ve eksik olanları oluştur
        $this->info('Chat mesajları kontrol ediliyor...');
        
        // Tüm benzersiz chat_id'leri bul
        $chatIds = ChatMessage::distinct('chat_id')->pluck('chat_id')->toArray();
        $this->info('Mesajlarda ' . count($chatIds) . ' adet farklı chat_id bulundu.');
        
        $chatIdsToCreate = array_diff($chatIds, $existingChatIds);
        
        if (count($chatIdsToCreate) > 0) {
            $this->info(count($chatIdsToCreate) . ' adet eksik chat kaydı oluşturulacak.');
            
            foreach ($chatIdsToCreate as $chatId) {
                // O chat_id'ye ait ilk mesajı bul
                $firstMessage = ChatMessage::where('chat_id', $chatId)
                    ->orderBy('created_at', 'asc')
                    ->first();
                
                if ($firstMessage) {
                    // Metadata'dan visitor_id'yi çıkar
                    $metadata = is_array($firstMessage->metadata) 
                        ? $firstMessage->metadata 
                        : json_decode($firstMessage->metadata, true);
                    
                    $visitorId = $metadata['visitor_id'] ?? null;
                    
                    // Yeni Chat kaydı oluştur
                    Chat::create([
                        'id' => $chatId,
                        'title' => substr($firstMessage->content, 0, 50),
                        'status' => 'active',
                        'context' => [
                            'visitor_id' => $visitorId,
                            'created_at' => $firstMessage->created_at
                        ],
                        'created_at' => $firstMessage->created_at,
                        'updated_at' => now()
                    ]);
                    
                    $this->info("Chat ID: $chatId için kayıt oluşturuldu.");
                }
            }
        } else {
            $this->info('Tüm chat kayıtları mevcut, eksik chat kaydı bulunmadı.');
        }

        // 4. Visitor_names tablosunu kontrol et ve eksik visitor_id'leri ekle
        $this->info('Ziyaretçi kayıtları kontrol ediliyor...');
        
        // Mesajlardan benzersiz visitor_id'leri topla
        $visitorIds = [];
        
        // Önce chat_messages tablosundaki verileri kontrol et
        $this->info('Chat mesajlarındaki visitor_id\'ler kontrol ediliyor...');
        
        ChatMessage::whereNotNull('metadata')->chunk(500, function ($messages) use (&$visitorIds) {
            foreach ($messages as $message) {
                $metadata = is_array($message->metadata) 
                    ? $message->metadata 
                    : json_decode($message->metadata, true);
                
                if (is_array($metadata) && isset($metadata['visitor_id'])) {
                    $visitorIds[$metadata['visitor_id']] = [
                        'ip_address' => $metadata['ip_address'] ?? null,
                        'device_info' => $metadata['device_info'] ?? null,
                        'name' => $metadata['visitor_name'] ?? 'İsimsiz Ziyaretçi',
                    ];
                }
            }
        });
        
        // Sonra chats tablosundaki context'leri kontrol et
        $this->info('Chat context\'lerindeki visitor_id\'ler kontrol ediliyor...');
        
        Chat::whereNotNull('context')->chunk(500, function ($chats) use (&$visitorIds) {
            foreach ($chats as $chat) {
                $context = is_array($chat->context) 
                    ? $chat->context 
                    : json_decode($chat->context, true);
                
                if (is_array($context) && isset($context['visitor_id'])) {
                    $visitorId = $context['visitor_id'];
                    if (!isset($visitorIds[$visitorId])) {
                        $visitorIds[$visitorId] = [
                            'ip_address' => null,
                            'device_info' => null,
                            'name' => 'İsimsiz Ziyaretçi',
                        ];
                    }
                }
            }
        });
        
        $this->info(count($visitorIds) . ' adet benzersiz visitor_id bulundu.');
        
        // Var olan visitor_id'leri al
        $existingVisitorIds = DB::table('visitor_names')->pluck('visitor_id')->toArray();
        $this->info(count($existingVisitorIds) . ' adet visitor_id kaydı zaten mevcut.');
        
        // Eksik visitor_id'leri bul ve oluştur
        $visitorIdsToCreate = array_diff(array_keys($visitorIds), $existingVisitorIds);
        
        if (count($visitorIdsToCreate) > 0) {
            $this->info(count($visitorIdsToCreate) . ' adet eksik visitor_id kaydı oluşturulacak.');
            
            foreach ($visitorIdsToCreate as $visitorId) {
                DB::table('visitor_names')->insert([
                    'visitor_id' => $visitorId,
                    'name' => $visitorIds[$visitorId]['name'],
                    'ip_address' => $visitorIds[$visitorId]['ip_address'],
                    'device_info' => $visitorIds[$visitorId]['device_info'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            
            $this->info('Eksik visitor_id kayıtları oluşturuldu.');
        } else {
            $this->info('Tüm visitor_id kayıtları mevcut, eksik visitor_id bulunmadı.');
        }
        
        $this->info('İşlem tamamlandı!');
        return 0;
    }
}
