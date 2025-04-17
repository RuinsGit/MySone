<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\DeviceHelper;

/**
 * RecordVisit Sınıfı
 * 
 * Bu sınıf, ziyaretçi bilgilerini kaydetmek ve ziyaretçilerle ilgili
 * istatistikleri almak için kullanılır. Ziyaretçilerin IP adresleri,
 * cihaz bilgileri ve adları saklanır. ChatController ve diğer kontrolcüler
 * tarafından ziyaretçilerin bilgilerini kaydetmek için kullanılır.
 */
class RecordVisit
{
    /**
     * Ziyaretçi bilgilerini kaydet
     * 
     * @param string $visitorId
     * @param string|null $visitorName
     * @return void
     */
    public function record($visitorId, $visitorName = null)
    {
        try {
            $deviceInfo = DeviceHelper::getUserDeviceInfo();
            $ipAddress = $deviceInfo['ip_address'];

            // Bilgileri loglayalım
            Log::info('Kullanıcı ziyareti kaydedildi', [
                'ip' => $ipAddress, 
                'visitor_id' => $visitorId,
                'visitor_name' => $visitorName,
                'device_info' => $deviceInfo['device_info']
            ]);
            
            // Ziyaretçi ID'si yoksa işlemi durdur
            if (empty($visitorId)) {
                Log::warning('Ziyaretçi ID\'si olmadan kayıt yapılamadı');
                return;
            }
            
            // Visitor_names tablosuna kayıt yap (isim varsa güncelleyerek, yoksa oluşturarak)
            try {
                // Visitor_names tablosu varsa, ziyaretçi bilgilerini güncelleyelim
                $data = [
                    'ip_address' => $ipAddress,
                    'device_info' => json_encode($deviceInfo['device_info']),
                    'updated_at' => now()
                ];
                
                // Kullanıcı adı varsa ekle
                if (!empty($visitorName)) {
                    $data['name'] = $visitorName;
                }
                
                // Kayıt varsa güncelle, yoksa oluştur (isim olmasa bile IP ve cihaz bilgilerini kaydediyoruz)
                $existingRecord = DB::table('visitor_names')->where('visitor_id', $visitorId)->first();
                
                if ($existingRecord) {
                    // Eğer mevcut bir kayıt varsa ve isim yoksa, mevcut ismi koru
                    if (empty($visitorName) && !empty($existingRecord->name)) {
                        // İsim yoksa ama kayıtlı isim varsa, session'a kaydet
                        session(['visitor_name' => $existingRecord->name]);
                        Log::info('Veritabanından alınan kullanıcı adı session\'a yüklendi', [
                            'visitor_id' => $visitorId,
                            'name' => $existingRecord->name
                        ]);
                    } else {
                        // İsim varsa güncelle
                        DB::table('visitor_names')->where('visitor_id', $visitorId)->update($data);
                    }
                } else {
                    // Yeni kayıt oluştur
                    $data['visitor_id'] = $visitorId;
                    $data['name'] = $visitorName ?? 'İsimsiz Ziyaretçi'; // İsim yoksa varsayılan değer
                    $data['created_at'] = now();
                    
                    DB::table('visitor_names')->insert($data);
                    
                    Log::info('Yeni ziyaretçi kaydı oluşturuldu', [
                        'visitor_id' => $visitorId, 
                        'name' => $data['name']
                    ]);
                }
            } catch (\Exception $e) {
                // Tablo yoksa sessizce devam et
                Log::error('Ziyaretçi bilgisi kaydedilemedi: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            Log::error('Ziyaretçi kaydı sırasında hata: ' . $e->getMessage());
        }
    }
    
    /**
     * Belirli bir visitor_id için tüm istatistikleri getirir
     *
     * @param string $visitorId Ziyaretçi ID'si
     * @return array|null İstatistikler veya hata durumunda null
     */
    public function getVisitorStats($visitorId)
    {
        try {
            $visitor = DB::table('visitor_names')
                ->where('visitor_id', $visitorId)
                ->first();
                
            if (!$visitor) {
                return null;
            }
            
            // Mesaj sayıları
            $messageCount = DB::table('chat_messages')
                ->whereRaw('JSON_EXTRACT(metadata, "$.visitor_id") = ?', ['"' . $visitorId . '"'])
                ->count();
                
            $userMessageCount = DB::table('chat_messages')
                ->whereRaw('JSON_EXTRACT(metadata, "$.visitor_id") = ?', ['"' . $visitorId . '"'])
                ->where('sender', 'user')
                ->count();
                
            $aiMessageCount = DB::table('chat_messages')
                ->whereRaw('JSON_EXTRACT(metadata, "$.visitor_id") = ?', ['"' . $visitorId . '"'])
                ->where('sender', 'ai')
                ->count();
                
            // İlk ve son mesaj
            $firstMessage = DB::table('chat_messages')
                ->whereRaw('JSON_EXTRACT(metadata, "$.visitor_id") = ?', ['"' . $visitorId . '"'])
                ->orderBy('created_at', 'asc')
                ->first();
                
            $lastMessage = DB::table('chat_messages')
                ->whereRaw('JSON_EXTRACT(metadata, "$.visitor_id") = ?', ['"' . $visitorId . '"'])
                ->orderBy('created_at', 'desc')
                ->first();
                
            return [
                'visitor' => $visitor,
                'message_count' => $messageCount,
                'user_message_count' => $userMessageCount,
                'ai_message_count' => $aiMessageCount,
                'first_message' => $firstMessage,
                'last_message' => $lastMessage,
                'first_message_time' => $firstMessage ? $firstMessage->created_at : null,
                'last_message_time' => $lastMessage ? $lastMessage->created_at : null,
            ];
        } catch (\Exception $e) {
            Log::error('Ziyaretçi istatistikleri alınırken hata: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Belirli bir visitor_id için mesajları getirir
     *
     * @param string $visitorId Ziyaretçi ID'si
     * @param int $perPage Sayfa başına kayıt sayısı
     * @return \Illuminate\Pagination\LengthAwarePaginator Mesajların sayfalanmış hali
     */
    public function getVisitorMessages($visitorId, $perPage = 20)
    {
        return DB::table('chat_messages')
            ->whereRaw('JSON_EXTRACT(metadata, "$.visitor_id") = ?', ['"' . $visitorId . '"'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
} 