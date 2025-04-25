<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\VisitorName;
use App\Models\Chat;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Http;
use App\Models\User;

class UserStatsController extends Controller
{
    /**
     * Kullanıcı istatistiklerini göster
     */
    public function index()
    {
        // Tüm ziyaretçi kayıtlarını al
        $visitors = DB::table('visitor_names')
            ->select('id', 'visitor_id', 'name', 'ip_address', 'device_info', 'created_at', 'updated_at')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Her ziyaretçi için istatistikleri topla
        foreach ($visitors as $visitor) {
            // Device info JSON verilerini çöz
            $visitor->device_info_decoded = json_decode($visitor->device_info, true);
            
            // Her ziyaretçinin mesaj sayısını al
            $visitor->message_count = DB::table('chat_messages')
                ->whereRaw('JSON_CONTAINS(metadata, ?, "$.visitor_id")', [$visitor->visitor_id])
                ->orWhereRaw('metadata LIKE ?', ['%"visitor_id":"' . $visitor->visitor_id . '"%'])
                ->count();
                
            // Son aktif olduğu zamanı al
            $lastMessage = DB::table('chat_messages')
                ->whereRaw('JSON_CONTAINS(metadata, ?, "$.visitor_id")', [$visitor->visitor_id])
                ->orWhereRaw('metadata LIKE ?', ['%"visitor_id":"' . $visitor->visitor_id . '"%'])
                ->orderBy('created_at', 'desc')
                ->first();
                
            $visitor->last_active = $lastMessage ? $lastMessage->created_at : $visitor->updated_at;
            
            // IP adresi bilgilerini göster
            $visitor->ip_location = $this->getIpDetails($visitor->ip_address);
        }
        
        return view('admin.user_stats.index', compact('visitors'));
    }
    
    /**
     * IP detaylarını göster
     */
    public function showIpDetails($ip)
    {
        // IP adresi hakkında detaylı bilgi al
        $ipDetails = $this->getIpDetails($ip);
        
        // Bu IP adresini kullanan tüm ziyaretçileri bul
        $visitors = DB::table('visitor_names')
            ->where('ip_address', $ip)
            ->get();
            
        // Bu IP'den gelen tüm mesajları al
        $messages = DB::table('chat_messages')
            ->where('ip_address', $ip)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return view('admin.user_stats.ip_details', compact('ip', 'ipDetails', 'visitors', 'messages'));
    }
    
    /**
     * Visitor ID'sini temizler (çerez formatı ve tırnak işaretlerini kaldırır)
     * 
     * @param string $visitorId
     * @return string
     */
    private function cleanVisitorId($visitorId) 
    {
        // Özel karakterleri temizle
        $cleanId = preg_replace('/[; ].*$/', '', $visitorId);
        
        // Tırnak işaretlerini temizle
        $cleanId = str_replace('"', '', $cleanId);
        $cleanId = str_replace("'", "", $cleanId);
        
        // Boşlukları temizle
        $cleanId = trim($cleanId);
        
        return $cleanId;
    }
    
    /**
     * Display visitor details
     *
     * @param string $visitorId
     * @return \Illuminate\View\View
     */
    public function showVisitorDetails($visitorId)
    {
        try {
            \Log::info("Ziyaretçi detayları görüntüleniyor: " . $visitorId);
            
            // Ziyaretçi ID'sini temizle
            $cleanedVisitorId = $this->cleanVisitorId($visitorId);
            
            // "deleted" içeren ID'leri google_common_id'ye yönlendir
            if ($cleanedVisitorId === 'deleted' || 
                strpos($cleanedVisitorId, 'deleted') !== false ||
                strpos($cleanedVisitorId, 'visitor_id=deleted') !== false) {
                \Log::info("Silinmiş visitor_id tespit edildi, Google ortak kimliğine yönlendiriliyor");
                return redirect()->route('admin.user-stats.visitor-details', 'google_common_id');
            }
            
            // Google kullanıcılarının ortak ziyaretçi ID'si kontrolü
            if ($cleanedVisitorId === 'google_common_id') {
                \Log::info("Google kullanıcıları için detaylar görüntüleniyor");
                
                // Google hesabıyla giriş yapmış tüm kullanıcıları getir
                $googleUsers = User::whereNotNull('google_id')->get();
                
                // Bu kullanıcıların tüm mesajlarını bul - deleted ID'lerdekileri de dahil et
                $messages = DB::table('chat_messages')
                    ->where(function($query) {
                        $query->where('visitor_id', 'google_common_id')
                              ->orWhere('visitor_id', 'like', 'google_%')
                              ->orWhere('visitor_id', 'deleted')
                              ->orWhere('visitor_id', 'like', '%deleted%');
                    })
                    ->orderBy('created_at', 'desc')
                    ->get();
                
                // Bu kullanıcıların sohbetlerini bul - deleted ID'lerdekileri de dahil et
                $chats = DB::table('chats')
                    ->where(function($query) {
                        $query->where('visitor_id', 'google_common_id')
                              ->orWhere('visitor_id', 'like', 'google_%')
                              ->orWhere('visitor_id', 'deleted')
                              ->orWhere('visitor_id', 'like', '%deleted%');
                    })
                    ->orderBy('updated_at', 'desc')
                    ->get();
                
                // Google visitor kaydını da göster
                $visitor = DB::table('visitor_names')
                    ->where('visitor_id', 'google_common_id')
                    ->first();
                
                // Google visitor kaydı yoksa oluştur
                if (!$visitor && count($googleUsers) > 0) {
                    // İlk Google kullanıcısının bilgilerini kullan
                    $firstUser = $googleUsers->first();
                    $visitor = DB::table('visitor_names')->insert([
                        'visitor_id' => 'google_common_id',
                        'name' => 'Google Kullanıcıları',
                        'avatar' => $firstUser->avatar,
                        'ip_address' => request()->ip(),
                        'user_id' => $firstUser->id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    // Yeni oluşturulan kaydı al
                    $visitor = DB::table('visitor_names')
                        ->where('visitor_id', 'google_common_id')
                        ->first();
                }
                
                return view('admin.user_stats.visitor_details', [
                    'isGoogleUser' => true,
                    'googleUsers' => $googleUsers,
                    'messages' => $messages,
                    'chats' => $chats,
                    'visitor' => $visitor
                ]);
            } else {
                // Normal ziyaretçi (Google kullanıcısı olmayan)
                $visitor = DB::table('visitor_names')
                    ->where('visitor_id', $cleanedVisitorId)
                    ->first();
                
                if (!$visitor) {
                    \Log::warning("Ziyaretçi bulunamadı: " . $cleanedVisitorId);
                    return redirect()->route('admin.user-stats.index')->with('error', 'Ziyaretçi bulunamadı.');
                }
                
                // Ziyaretçinin mesajlarını bul
                $messages = DB::table('chat_messages')
                    ->where('visitor_id', $cleanedVisitorId)
                    ->orderBy('created_at', 'desc')
                    ->get();
                
                // Ziyaretçinin sohbetlerini bul
                $chats = DB::table('chats')
                    ->where('visitor_id', $cleanedVisitorId)
                    ->orderBy('updated_at', 'desc')
                    ->get();
                
                return view('admin.user_stats.visitor_details', [
                    'visitor' => $visitor,
                    'messages' => $messages,
                    'chats' => $chats
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Ziyaretçi detayları görüntülenirken hata: ' . $e->getMessage());
            return redirect()->route('admin.user-stats.index')->with('error', 'Ziyaretçi detayları görüntülenirken bir hata oluştu: ' . $e->getMessage());
        }
    }
    
    /**
     * IP adresi hakkında ayrıntılı bilgi al
     */
    private function getIpDetails($ip)
    {
        try {
            // Eğer localhost veya özel IP ise boş sonuç döndür
            if (in_array($ip, ['127.0.0.1', 'localhost', '::1']) || 
                strpos($ip, '192.168.') === 0 || 
                strpos($ip, '10.') === 0) {
                return [
                    'country' => 'Yerel IP',
                    'region' => 'Yerel Ağ',
                    'city' => 'Yerel',
                    'isp' => 'Yerel Bağlantı',
                    'org' => 'Yerel Organizasyon',
                    'isVpn' => false,
                    'isProxy' => false,
                    'isHosting' => false
                ];
            }
            
            // IP-API kullanarak detayları al (ücretsiz servis)
            $response = Http::get("http://ip-api.com/json/{$ip}?fields=status,message,country,regionName,city,isp,org,mobile,proxy,hosting");
            
            if ($response->successful() && $response['status'] === 'success') {
                $data = $response->json();
                
                return [
                    'country' => $data['country'] ?? 'Bilinmiyor',
                    'region' => $data['regionName'] ?? 'Bilinmiyor',
                    'city' => $data['city'] ?? 'Bilinmiyor',
                    'isp' => $data['isp'] ?? 'Bilinmiyor',
                    'org' => $data['org'] ?? 'Bilinmiyor',
                    'isVpn' => $data['proxy'] ?? false,
                    'isProxy' => $data['proxy'] ?? false,
                    'isHosting' => $data['hosting'] ?? false,
                    'isMobile' => $data['mobile'] ?? false
                ];
            }
            
            return [
                'country' => 'Bilgi Yok',
                'region' => 'Bilgi Yok',
                'city' => 'Bilgi Yok',
                'isp' => 'Bilgi Yok',
                'org' => 'Bilgi Yok',
                'isVpn' => false,
                'isProxy' => false,
                'isHosting' => false
            ];
        } catch (\Exception $e) {
            \Log::error('IP detayları alınırken hata: ' . $e->getMessage());
            
            return [
                'country' => 'Hata',
                'region' => 'Hata',
                'city' => 'Hata',
                'isp' => 'Hata',
                'org' => 'Hata',
                'isVpn' => false,
                'isProxy' => false,
                'isHosting' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
