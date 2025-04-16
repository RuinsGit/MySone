<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChatMessage;
use App\Models\Chat;
use Illuminate\Support\Facades\DB;

class UserStatsController extends Controller
{
    /**
     * Kullanıcı istatistikleri ana sayfası
     */
    public function index()
    {
        // IP adreslerine göre istatistikleri al
        $ipStats = ChatMessage::select('ip_address', DB::raw('COUNT(*) as message_count'))
            ->whereNotNull('ip_address')
            ->groupBy('ip_address')
            ->orderBy('message_count', 'desc')
            ->limit(100)
            ->get();
            
        // Ziyaretçi ID'lerine göre istatistikleri al - hem JSON_EXTRACT hem LIKE kullanalım
        $visitorStats = DB::table('chat_messages')
            ->select(
                DB::raw('JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.visitor_id")) as visitor_id'), 
                DB::raw('MIN(ip_address) as ip_address'),
                DB::raw('COUNT(*) as message_count')
            )
            ->whereNotNull('metadata')
            ->whereRaw('JSON_EXTRACT(metadata, "$.visitor_id") IS NOT NULL')
            ->groupBy('visitor_id')
            ->orderBy('message_count', 'desc')
            ->limit(50)
            ->get();
            
        // Eğer visitor_id bulunamadıysa LIKE ile deneyelim
        if ($visitorStats->isEmpty()) {
            \Log::info('JSON_EXTRACT ile visitor_id bulunamadı, LIKE ile deneniyor');
            
            $visitorStats = DB::table('chat_messages')
                ->select(
                    DB::raw('SUBSTRING_INDEX(SUBSTRING_INDEX(metadata, \'"visitor_id":"\', -1), \'"\', 1) as visitor_id'),
                    DB::raw('MIN(ip_address) as ip_address'),
                    DB::raw('COUNT(*) as message_count')
                )
                ->where('metadata', 'LIKE', '%"visitor_id"%')
                ->groupBy('visitor_id')
                ->orderBy('message_count', 'desc')
                ->limit(50)
                ->get();
        }
        
        \Log::info('Ziyaretçi istatistikleri alındı', [
            'visitor_count' => $visitorStats->count(),
            'ip_count' => $ipStats->count(),
        ]);
            
        // Gün başına mesaj sayısı
        $dailyMessageStats = ChatMessage::select(
                DB::raw('DATE(created_at) as date'), 
                DB::raw('COUNT(*) as message_count')
            )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();
            
        // Cihaz bilgilerine göre istatistikler (tarayıcı, işletim sistemi vs)
        $deviceStats = $this->generateDeviceStats();
            
        return view('admin.user_stats.index', compact('ipStats', 'visitorStats', 'dailyMessageStats', 'deviceStats'));
    }
    
    /**
     * Belirli bir IP adresine ait mesajları görüntüle
     */
    public function showIpDetails($ip)
    {
        // IP adresine ait tüm mesajları al
        $messages = ChatMessage::where('ip_address', $ip)
            ->orderBy('created_at', 'desc')
            ->paginate(50);
            
        // IP adresine ait benzersiz ziyaretçi ID'lerini bul
        $visitorIds = ChatMessage::where('ip_address', $ip)
            ->select(DB::raw('JSON_EXTRACT(metadata, "$.visitor_id") as visitor_id'))
            ->whereNotNull('metadata->visitor_id')
            ->distinct()
            ->pluck('visitor_id')
            ->toArray();
            
        // IP adresine ait istatistikler
        $stats = [
            'total_messages' => ChatMessage::where('ip_address', $ip)->count(),
            'first_message' => ChatMessage::where('ip_address', $ip)->orderBy('created_at', 'asc')->first(),
            'last_message' => ChatMessage::where('ip_address', $ip)->orderBy('created_at', 'desc')->first(),
            'user_messages' => ChatMessage::where('ip_address', $ip)->where('sender', 'user')->count(),
            'ai_messages' => ChatMessage::where('ip_address', $ip)->where('sender', 'ai')->count(),
            'unique_visitors' => count($visitorIds)
        ];
        
        // Cihaz bilgisi
        $deviceInfo = null;
        $latestMessage = ChatMessage::where('ip_address', $ip)
            ->whereNotNull('device_info')
            ->orderBy('created_at', 'desc')
            ->first();
            
        if ($latestMessage && !empty($latestMessage->device_info)) {
            $deviceInfo = json_decode($latestMessage->device_info, true);
        }
        
        return view('admin.user_stats.ip_details', compact('messages', 'ip', 'stats', 'deviceInfo', 'visitorIds'));
    }
    
    /**
     * Belirli bir ziyaretçi ID'sine ait mesajları görüntüle
     */
    public function showVisitorDetails($visitorId)
    {
        // Ziyaretçi ID'sini çift tırnaklar içinde arıyoruz çünkü JSON'da bu şekilde saklanıyor
        $quotedVisitorId = '"' . $visitorId . '"';
        
        // İlk yöntem: JSON_EXTRACT kullanarak
        $messagesQuery1 = ChatMessage::whereRaw('JSON_EXTRACT(metadata, "$.visitor_id") = ?', [$quotedVisitorId]);
        
        // İkinci yöntem: JSON_CONTAINS kullanarak
        $messagesQuery2 = ChatMessage::whereRaw('JSON_CONTAINS(metadata, ?, "$.visitor_id")', [$quotedVisitorId]);
        
        // Üçüncü yöntem: LIKE operatörü kullanarak (not optimal ama bazen çalışabilir)
        $messagesQuery3 = ChatMessage::where('metadata', 'LIKE', '%"visitor_id":"' . $visitorId . '"%');
        
        // Log için bazı bilgileri yazdıralım
        \Log::info('Ziyaretçi ID araması yapılıyor', [
            'visitor_id' => $visitorId,
            'quoted_visitor_id' => $quotedVisitorId,
            'count_method1' => $messagesQuery1->count(),
            'count_method2' => $messagesQuery2->count(),
            'count_method3' => $messagesQuery3->count()
        ]);
        
        // En iyi sonucu veren metodu seçelim
        if ($messagesQuery1->count() > 0) {
            $messages = $messagesQuery1->orderBy('created_at', 'desc')->paginate(50);
            $queryMethod = 'JSON_EXTRACT';
        } elseif ($messagesQuery2->count() > 0) {
            $messages = $messagesQuery2->orderBy('created_at', 'desc')->paginate(50);
            $queryMethod = 'JSON_CONTAINS';
        } else {
            $messages = $messagesQuery3->orderBy('created_at', 'desc')->paginate(50);
            $queryMethod = 'LIKE';
        }
        
        // Log bilgisi
        \Log::info("Ziyaretçi mesajları bulundu ($queryMethod metodu)", [
            'visitor_id' => $visitorId,
            'message_count' => $messages->count()
        ]);
        
        // IP adreslerini seçilen metoda göre alalım
        if ($queryMethod === 'JSON_EXTRACT') {
            $ipAddresses = ChatMessage::whereRaw('JSON_EXTRACT(metadata, "$.visitor_id") = ?', [$quotedVisitorId])
                ->select('ip_address')
                ->distinct()
                ->pluck('ip_address')
                ->toArray();
        } elseif ($queryMethod === 'JSON_CONTAINS') {
            $ipAddresses = ChatMessage::whereRaw('JSON_CONTAINS(metadata, ?, "$.visitor_id")', [$quotedVisitorId])
                ->select('ip_address')
                ->distinct()
                ->pluck('ip_address')
                ->toArray();
        } else {
            $ipAddresses = ChatMessage::where('metadata', 'LIKE', '%"visitor_id":"' . $visitorId . '"%')
                ->select('ip_address')
                ->distinct()
                ->pluck('ip_address')
                ->toArray();
        }
            
        // Ziyaretçi istatistikleri
        $stats = [
            'total_messages' => $messages->total(),
            'first_message' => $messagesQuery1->orderBy('created_at', 'asc')->first(),
            'last_message' => $messagesQuery1->orderBy('created_at', 'desc')->first(),
            'user_messages' => $messagesQuery1->where('sender', 'user')->count(),
            'ai_messages' => $messagesQuery1->where('sender', 'ai')->count(),
            'ip_count' => count($ipAddresses)
        ];
        
        // Cihaz bilgisi
        $deviceInfo = null;
        $latestMessage = $messagesQuery1->whereNotNull('device_info')
            ->orderBy('created_at', 'desc')
            ->first();
            
        if ($latestMessage && !empty($latestMessage->device_info)) {
            $deviceInfo = json_decode($latestMessage->device_info, true);
        }
        
        \Log::info('Cihaz bilgisi kontrol ediliyor', [
            'visitor_id' => $visitorId,
            'device_info_found' => !is_null($deviceInfo),
            'latest_message_found' => !is_null($latestMessage)
        ]);
        
        return view('admin.user_stats.visitor_details', compact('messages', 'visitorId', 'stats', 'deviceInfo', 'ipAddresses'));
    }
    
    /**
     * Cihaz istatistiklerini oluştur
     */
    private function generateDeviceStats()
    {
        $stats = [
            'browsers' => [],
            'operating_systems' => [],
            'device_types' => []
        ];
        
        // Benzersiz IP adresleri için son mesajları al
        $latestMessages = DB::table('chat_messages')
            ->select('ip_address', DB::raw('MAX(id) as id'))
            ->whereNotNull('ip_address')
            ->whereNotNull('device_info')
            ->groupBy('ip_address')
            ->get();
            
        $messageIds = $latestMessages->pluck('id')->toArray();
        
        if (empty($messageIds)) {
            return $stats;
        }
        
        $messages = ChatMessage::whereIn('id', $messageIds)->get();
        
        foreach ($messages as $message) {
            if (empty($message->device_info)) continue;
            
            try {
                $deviceInfo = json_decode($message->device_info, true);
                
                if (isset($deviceInfo['browser'])) {
                    $browser = $deviceInfo['browser'];
                    $stats['browsers'][$browser] = ($stats['browsers'][$browser] ?? 0) + 1;
                }
                
                if (isset($deviceInfo['os'])) {
                    $os = $deviceInfo['os'];
                    $stats['operating_systems'][$os] = ($stats['operating_systems'][$os] ?? 0) + 1;
                }
                
                if (isset($deviceInfo['device_type'])) {
                    $deviceType = $deviceInfo['device_type'];
                    $stats['device_types'][$deviceType] = ($stats['device_types'][$deviceType] ?? 0) + 1;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        // Her kategoriyi sırala
        arsort($stats['browsers']);
        arsort($stats['operating_systems']);
        arsort($stats['device_types']);
        
        return $stats;
    }
} 