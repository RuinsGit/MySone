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
        // Tüm IP adreslerini ve mesaj sayılarını al
        $ipStats = \DB::table('chat_messages')
            ->select('ip_address', \DB::raw('count(*) as message_count'))
            ->whereNotNull('ip_address')
            ->groupBy('ip_address')
            ->orderBy('message_count', 'desc')
            ->limit(50)
            ->get();
        
        // visitor_id'ye göre mesaj sayılarını al
        $visitorStats = \DB::table('chat_messages')
            ->select(
                \DB::raw('JSON_EXTRACT(metadata, "$.visitor_id") as visitor_id'),
                \DB::raw('MIN(ip_address) as ip_address'),
                \DB::raw('COUNT(*) as message_count')
            )
            ->whereRaw('JSON_EXTRACT(metadata, "$.visitor_id") IS NOT NULL')
            ->groupBy('visitor_id')
            ->orderBy('message_count', 'desc')
            ->limit(50)
            ->get();
        
        // JSON_EXTRACT bulunmazsa, LIKE ile dene
        if ($visitorStats->isEmpty()) {
            \Log::info('JSON_EXTRACT ile visitor_id bulunamadı, LIKE ile deneniyor');
            
            $visitorStats = \DB::table('chat_messages')
                ->select(
                    \DB::raw('SUBSTRING_INDEX(SUBSTRING_INDEX(metadata, \'"visitor_id":"\', -1), \'"\', 1) as visitor_id'),
                    \DB::raw('MIN(ip_address) as ip_address'),
                    \DB::raw('COUNT(*) as message_count')
                )
                ->where('metadata', 'LIKE', '%"visitor_id"%')
                ->groupBy('visitor_id')
                ->orderBy('message_count', 'desc')
                ->limit(50)
                ->get();
        }
        
        // Ziyaretçi adlarını çek
        $visitorIds = $visitorStats->pluck('visitor_id')->map(function($id) {
            return str_replace('"', '', $id);
        })->toArray();
        
        $visitorNames = \DB::table('visitor_names')
            ->whereIn('visitor_id', $visitorIds)
            ->pluck('name', 'visitor_id')
            ->toArray();
        
        \Log::info('Ziyaretçi istatistikleri alındı', [
            'visitor_count' => $visitorStats->count(),
            'ip_count' => $ipStats->count(),
            'visitor_names_count' => count($visitorNames)
        ]);
        
        // Gün başına mesaj sayısı
        $dailyMessageStats = \App\Models\ChatMessage::select(
                \DB::raw('DATE(created_at) as date'), 
                \DB::raw('COUNT(*) as message_count')
            )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();
            
        // Cihaz bilgilerine göre istatistikler (tarayıcı, işletim sistemi vs)
        $deviceStats = $this->generateDeviceStats();
            
        return view('admin.user_stats.index', compact('ipStats', 'visitorStats', 'dailyMessageStats', 'deviceStats', 'visitorNames'));
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
        // Temiz ziyaretçi ID'si oluştur
        $cleanVisitorId = str_replace('"', '', $visitorId);
        
        // Sorgu metodu belirle (JSON_EXTRACT, JSON_CONTAINS veya LIKE)
        $queryMethod = 'JSON_EXTRACT';
        $quotedVisitorId = '"' . $cleanVisitorId . '"';

        // Ziyaretçiye ait mesajları, tırnakları temizleyerek sorgula
        if ($queryMethod === 'JSON_EXTRACT') {
            $messagesQuery = ChatMessage::whereRaw('JSON_EXTRACT(metadata, "$.visitor_id") = ?', [$quotedVisitorId]);
            $messagesQuery1 = clone $messagesQuery;
            $messages = $messagesQuery->orderBy('created_at', 'desc')->paginate(50);
        } elseif ($queryMethod === 'JSON_CONTAINS') {
            $messagesQuery = ChatMessage::whereRaw('JSON_CONTAINS(metadata, ?, "$.visitor_id")', [$quotedVisitorId]);
            $messagesQuery1 = clone $messagesQuery;
            $messages = $messagesQuery->orderBy('created_at', 'desc')->paginate(50);
        } else {
            $messagesQuery = ChatMessage::where('metadata', 'LIKE', '%"visitor_id":"' . $cleanVisitorId . '"%');
            $messagesQuery1 = clone $messagesQuery;
            $messages = $messagesQuery->orderBy('created_at', 'desc')->paginate(50);
        }
        
        // Ziyaretçinin bilgilerini çek
        $visitorInfo = DB::table('visitor_names')->where('visitor_id', $cleanVisitorId)->first();
        
        // Kullanılan IP adreslerini bul
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
            $ipAddresses = ChatMessage::where('metadata', 'LIKE', '%"visitor_id":"' . $cleanVisitorId . '"%')
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
            'ip_count' => count($ipAddresses),
            'visitor_name' => $visitorInfo->name ?? null
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
            'latest_message_found' => !is_null($latestMessage),
            'visitor_name' => $visitorInfo->name ?? 'Bulunamadı'
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