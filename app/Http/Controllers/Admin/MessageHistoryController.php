<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChatMessage;
use App\Models\Chat;
use Illuminate\Support\Facades\DB;

class MessageHistoryController extends Controller
{
    /**
     * Mesaj geçmişi ana sayfası
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $filterBy = $request->input('filter_by', 'all');
        $visitorId = $request->input('visitor_id', '');
        $userName = $request->input('user_name', '');
        
        \Log::info("Mesaj geçmişi sayfası açılıyor", [
            'search' => $search,
            'filter_by' => $filterBy,
            'visitor_id' => $visitorId,
            'user_name' => $userName
        ]);
        
        // Önce veritabanından tüm ziyaretçileri al
        $allVisitors = DB::table('visitor_names')
            ->select('visitor_id', 'name', 'ip_address')
            ->orderBy('name')
            ->get();
            
        // İsme göre gruplamak için boş bir dizi oluştur
        $groupedVisitors = [];
        
        // Her ziyaretçiyi ismine göre grupla
        foreach ($allVisitors as $visitor) {
            $name = $visitor->name ?? 'İsimsiz Ziyaretçi';
            
            if (!isset($groupedVisitors[$name])) {
                // Eğer isim ilk kez görüldüyse, yeni bir grup oluştur
                $groupedVisitors[$name] = [
                    'name' => $name,
                    'first_visitor' => $visitor,  // İlk ziyaretçiyi sakla
                    'visitors' => [$visitor],     // Tüm ziyaretçileri sakla
                    'count' => 1,                 // Ziyaretçi sayısını tut
                    'visitor_ids' => [$visitor->visitor_id]  // Tüm visitor ID'leri sakla
                ];
            } else {
                // Aynı isimli ziyaretçi varsa, mevcut gruba ekle
                $groupedVisitors[$name]['visitors'][] = $visitor;
                $groupedVisitors[$name]['count']++;
                $groupedVisitors[$name]['visitor_ids'][] = $visitor->visitor_id;
            }
        }
        
        // Sorgu oluştur
        $query = ChatMessage::query();
        
        // Arama kriteri varsa uygula
        if (!empty($search)) {
            $query->where('content', 'LIKE', "%{$search}%");
        }
        
        // Filtre uygula
        if ($filterBy == 'user') {
            $query->where('sender', 'user');
        } elseif ($filterBy == 'ai') {
            $query->where('sender', 'ai');
        }
        
        // Kullanıcı adına göre filtre uygula (öncelikli)
        if (!empty($userName) && isset($groupedVisitors[$userName])) {
            // Bu isme sahip tüm ziyaretçi ID'lerini al
            $visitorIds = $groupedVisitors[$userName]['visitor_ids'];
            \Log::info("Kullanıcı adına göre filtreleme yapılıyor", [
                'user_name' => $userName,
                'visitor_ids' => $visitorIds
            ]);
            
            // Birden fazla visitor ID için OR koşulu oluştur
            $query->where(function($q) use ($visitorIds) {
                foreach ($visitorIds as $vId) {
                    $q->orWhereRaw('JSON_EXTRACT(metadata, "$.visitor_id") = ?', ['"' . $vId . '"'])
                      ->orWhere('metadata', 'LIKE', '%"visitor_id":"' . $vId . '"%')
                      ->orWhere('metadata', 'LIKE', '%"visitor_id": "' . $vId . '"%');
                }
            });
        }
        // Belirli bir ziyaretçinin mesajlarını filtrele
        elseif (!empty($visitorId)) {
            \Log::info("Visitor ID ile mesaj filtreleme yapılıyor", ['visitor_id' => $visitorId]);
            
            // Çoklu sorgulama stratejisi kullan
            $query->where(function($q) use ($visitorId) {
                // JSON_EXTRACT ile arama
                $q->whereRaw('JSON_EXTRACT(metadata, "$.visitor_id") = ?', ['"' . $visitorId . '"'])
                // VEYA metin içinde arama 
                ->orWhere('metadata', 'LIKE', '%"visitor_id":"' . $visitorId . '"%')
                // VEYA basit bir dizi içinde arama
                ->orWhere('metadata', 'LIKE', '%"visitor_id": "' . $visitorId . '"%')
                // VEYA chat modelinde context içinde saklıysa
                ->orWhereHas('chat', function($subQ) use ($visitorId) {
                    $subQ->whereRaw('JSON_EXTRACT(context, "$.visitor_id") = ?', ['"' . $visitorId . '"'])
                    ->orWhere('context', 'LIKE', '%"visitor_id":"' . $visitorId . '"%');
                });
            });
            
            \Log::info("Visitor ID filtrelemesi uygulandı");
        }
        
        // Sonuçları en yeniden eskiye doğru sırala
        $query->orderBy('created_at', 'desc');
        
        // Bulunan toplam mesaj sayısını logla
        $totalCount = $query->count();
        \Log::info("Bulunan toplam mesaj sayısı", ['count' => $totalCount]);
        
        // Paginate uygula
        $messages = $query->paginate(25);
        
        // Her mesaj için ziyaretçi adını belirle
        foreach ($messages as $message) {
            $message->visitor_name = $this->getVisitorName($message);
        }
            
        return view('admin.message_history.index', compact('messages', 'search', 'filterBy', 'visitorId', 'userName', 'groupedVisitors'));
    }
    
    /**
     * Ziyaretçi listesini göster
     */
    public function visitors()
    {
        // Önce veritabanından tüm ziyaretçileri al
        $allVisitors = DB::table('visitor_names')
            ->select('visitor_id', 'name', 'ip_address', 'created_at')
            ->orderBy('name')
            ->get();
        
        // İsme göre gruplamak için boş bir dizi oluştur
        $groupedVisitors = [];
        
        // Her ziyaretçiyi ismine göre grupla
        foreach ($allVisitors as $visitor) {
            $name = $visitor->name ?? 'İsimsiz Ziyaretçi';
            
            if (!isset($groupedVisitors[$name])) {
                // Eğer isim ilk kez görüldüyse, yeni bir grup oluştur
                $groupedVisitors[$name] = [
                    'name' => $name,
                    'first_visitor' => $visitor,  // İlk ziyaretçiyi sakla
                    'visitors' => [$visitor],     // Tüm ziyaretçileri sakla
                    'count' => 1                  // Ziyaretçi sayısını tut
                ];
            } else {
                // Aynı isimli ziyaretçi varsa, mevcut gruba ekle
                $groupedVisitors[$name]['visitors'][] = $visitor;
                $groupedVisitors[$name]['count']++;
            }
        }
        
        // İsimlerin sayısına göre sırala (a-z)
        ksort($groupedVisitors);
            
        return view('admin.message_history.visitors', compact('groupedVisitors'));
    }
    
    /**
     * POST metodu ile ziyaretçi mesajlarını görüntüle
     * URL sorunlarını önlemek için tasarlanmıştır
     */
    public function viewUser(Request $request)
    {
        $visitorId = $request->input('visitor_id');
        
        // Boş veya deleted olarak gelen visitor_id kontrolü
        if (empty($visitorId) || $visitorId === 'deleted') {
            return redirect()->route('admin.message-history.index')
                ->with('error', 'Geçersiz ziyaretçi ID\'si: ' . $visitorId);
        }

        // Özel karakterleri temizle
        $cleanVisitorId = $this->cleanVisitorId($visitorId);
        
        // Ziyaretçinin varlığını kontrol et
        if (!$this->checkIfVisitorExists($cleanVisitorId)) {
            return redirect()->route('admin.message-history.index')
                ->with('error', 'Belirtilen ID için ziyaretçi bulunamadı: ' . $cleanVisitorId);
        }
        
        return $this->userHistory($cleanVisitorId);
    }
    
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
     * Belirli bir kullanıcının mesaj geçmişini görüntüle
     */
    public function userHistory($visitorId)
    {
        if (empty($visitorId)) {
            return redirect()->route('admin.message-history.index')
                ->with('error', 'Geçersiz ziyaretçi ID\'si');
        }
        
        // Temizle: URL'den gelebilecek çerez formatındaki değeri temizle
        $visitorId = $this->cleanVisitorId($visitorId);
        
        // Debug için visitor ID'yi logla
        \Log::info("Ziyaretçi mesajları görüntüleniyor (temizlenmiş ID)", ['visitor_id' => $visitorId]);
        
        // Ziyaretçi bilgilerini al
        $visitor = DB::table('visitor_names')
            ->where('visitor_id', $visitorId)
            ->first();
            
        // Eğer ziyaretçi bilgisi yoksa ve mesajlarda da bulunamıyorsa hata ver
        if (!$visitor) {
            // Temizlenmiş ID ile bulunamadıysa, orijinal ID'ye tırnak işaretleri ekleyerek deneyelim
            $quotedVisitorId = '"' . $visitorId . '"';
            $visitor = DB::table('visitor_names')
                ->where('visitor_id', $quotedVisitorId)
                ->first();
                
            if (!$visitor) {
                $visitorExists = $this->checkIfVisitorExists($visitorId);
                
                if (!$visitorExists) {
                    // Ayrıca tırnaklı versiyonla da kontrol edelim
                    $visitorExists = $this->checkIfVisitorExists($quotedVisitorId);
                    
                    if ($visitorExists) {
                        $visitorId = $quotedVisitorId;
                    } else {
                        return redirect()->route('admin.message-history.index')
                            ->with('error', 'Belirtilen ID için ziyaretçi bulunamadı: ' . $visitorId);
                    }
                }
                
                // Eğer visitor_names tablosunda kaydı yoksa ama mesajlarda varsa, bir kayıt oluşturalım
                try {
                    // Ziyaretçiye ait bir mesaj bul (hem normal hem tırnaklı versiyonu dene)
                    $firstMessage = $this->findFirstMessageByVisitorId($visitorId);
                    
                    if (!$firstMessage && $visitorId != $quotedVisitorId) {
                        $firstMessage = $this->findFirstMessageByVisitorId($quotedVisitorId);
                        if ($firstMessage) {
                            $visitorId = $quotedVisitorId;
                        }
                    }
                    
                    if ($firstMessage) {
                        $metadata = is_array($firstMessage->metadata) 
                            ? $firstMessage->metadata 
                            : json_decode($firstMessage->metadata, true);
                        
                        $visitorName = $metadata['visitor_name'] ?? 'İsimsiz Ziyaretçi';
                        
                        // visitor_names tablosuna ekle
                        DB::table('visitor_names')->insert([
                            'visitor_id' => $visitorId,
                            'name' => $visitorName,
                            'ip_address' => $firstMessage->ip_address ?? '127.0.0.1',
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        
                        // Yeni oluşturulan kaydı al
                        $visitor = DB::table('visitor_names')
                            ->where('visitor_id', $visitorId)
                            ->first();
                    } else {
                        return redirect()->route('admin.message-history.index')
                            ->with('error', 'Belirtilen ID için mesaj bulunamadı: ' . $visitorId);
                    }
                } catch (\Exception $e) {
                    \Log::error('Eksik ziyaretçi kaydı oluşturma hatası: ' . $e->getMessage(), [
                        'visitor_id' => $visitorId,
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    return redirect()->route('admin.message-history.index')
                        ->with('error', 'Ziyaretçi kaydı oluşturulurken hata: ' . $e->getMessage());
                }
            }
        }
        
        // Önce bu ziyaretçinin tüm mesajlarını bulalım (sayfa için)
        try {
            \Log::info("Ziyaretçi ID ile mesaj sorgusu yapılıyor", ['visitor_id' => $visitorId]);
            
            // Tüm olası sorgu yöntemlerini deneyen çoklu yaklaşım
            $messagesQuery = $this->getMessagesByVisitorId($visitorId);
            
            // Bulunan mesaj sayısını logla
            $countBeforePagination = $messagesQuery->count();
            \Log::info("Bulunan toplam mesaj sayısı", ['count' => $countBeforePagination]);
            
            // Hiç mesaj bulunamadıysa ve ID'de tırnak yoksa, tırnaklı ID ile deneyelim
            if ($countBeforePagination == 0 && strpos($visitorId, '"') === false) {
                $quotedVisitorId = '"' . $visitorId . '"';
                $messagesQuery = $this->getMessagesByVisitorId($quotedVisitorId);
                $countBeforePagination = $messagesQuery->count();
                
                if ($countBeforePagination > 0) {
                    $visitorId = $quotedVisitorId;
                    \Log::info("Tırnaklı ID ile mesajlar bulundu", [
                        'quoted_id' => $quotedVisitorId,
                        'count' => $countBeforePagination
                    ]);
                }
            }
            
            if ($countBeforePagination == 0) {
                return redirect()->route('admin.message-history.index')
                    ->with('error', 'Bu ziyaretçi için mesaj bulunamadı: ' . $visitorId);
            }
            
            // Ziyaretçinin tüm mesajlarını al (sayfalanmış)
            $messages = clone $messagesQuery;
            $messages = $messages->paginate(25);
                
            // Sayfalanmamış tüm mesajlar (istatistikler için)
            $allMessages = clone $messagesQuery;
            $allMessages = $allMessages->get();
            
            \Log::info("Bulunan mesaj sayısı: {$allMessages->count()}");
            
            // Sohbet gruplarını al
            $chatIds = $allMessages->pluck('chat_id')->unique()->values()->toArray();
            $chats = Chat::whereIn('id', $chatIds)->get();
            
            \Log::info("Bulunan sohbet sayısı: {$chats->count()}");
            
            // Benzersiz sohbet IDleri için ilgili mesajları eşleştirelim
            foreach ($chats as $chat) {
                $chat->message_count = $allMessages->where('chat_id', $chat->id)->count();
            }
            
            // İstatistikler
            $stats = [
                'total_messages' => $allMessages->count(),
                'user_messages' => $allMessages->where('sender', 'user')->count(),
                'ai_messages' => $allMessages->where('sender', 'ai')->count(),
                'chats_count' => count($chatIds),
                'first_message' => $allMessages->sortBy('created_at')->first(),
                'last_message' => $allMessages->sortByDesc('created_at')->first()
            ];
            
            return view('admin.message_history.user_history', compact('messages', 'visitorId', 'visitor', 'chats', 'stats'));
        } catch (\Exception $e) {
            \Log::error('Kullanıcı mesaj geçmişi alınırken hata: ' . $e->getMessage(), [
                'visitor_id' => $visitorId,
                'exception' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.message-history.index')
                ->with('error', 'Mesaj geçmişi alınırken hata oluştu: ' . $e->getMessage());
        }
    }
    
    /**
     * Belirtilen ziyaretçi ID'sine ait ilk mesajı bul
     */
    private function findFirstMessageByVisitorId($visitorId)
    {
        try {
            // JSON_EXTRACT ile deneyelim
            $message = ChatMessage::whereRaw('JSON_EXTRACT(metadata, "$.visitor_id") = ?', [$visitorId])
                ->where('sender', 'user')
                ->orderBy('created_at', 'asc')
                ->first();
                
            if ($message) return $message;
            
            // LIKE ile deneyelim
            $message = ChatMessage::where('metadata', 'LIKE', '%"visitor_id":"' . str_replace('"', '\"', $visitorId) . '"%')
                ->orWhere('metadata', 'LIKE', '%"visitor_id": "' . str_replace('"', '\"', $visitorId) . '"%')
                ->where('sender', 'user')
                ->orderBy('created_at', 'asc')
                ->first();
                
            return $message;
        } catch (\Exception $e) {
            \Log::error('İlk mesaj aranırken hata: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Belirtilen ziyaretçi ID'sine ait tüm mesajları sorgula
     */
    private function getMessagesByVisitorId($visitorId)
    {
        return ChatMessage::where(function($query) use ($visitorId) {
            // JSON_EXTRACT ile arama
            $query->whereRaw('JSON_EXTRACT(metadata, "$.visitor_id") = ?', [$visitorId])
            // VEYA metin içinde arama
            ->orWhere('metadata', 'LIKE', '%"visitor_id":"' . str_replace('"', '\"', $visitorId) . '"%')
            // VEYA basit bir dizi içinde arama
            ->orWhere('metadata', 'LIKE', '%"visitor_id": "' . str_replace('"', '\"', $visitorId) . '"%')
            // VEYA chat modelinde context içinde saklıysa
            ->orWhereHas('chat', function($q) use ($visitorId) {
                $q->whereRaw('JSON_EXTRACT(context, "$.visitor_id") = ?', [$visitorId])
                ->orWhere('context', 'LIKE', '%"visitor_id":"' . str_replace('"', '\"', $visitorId) . '"%')
                ->orWhere('context', 'LIKE', '%"visitor_id": "' . str_replace('"', '\"', $visitorId) . '"%');
            });
        })
        ->orderBy('created_at', 'desc');
    }
    
    /**
     * Belirli bir sohbetin mesajlarını görüntüle
     */
    public function chatHistory($chatId)
    {
        $chat = Chat::findOrFail($chatId);
        
        // Chat tablosundan verileri al
        $messages = ChatMessage::where('chat_id', $chatId)
            ->orderBy('created_at', 'asc')
            ->get();
        
        // Ziyaretçi ID'sini bulmaya çalışalım
        $visitorId = null;
        
        // Önce sohbetin context alanında arayalım
        if ($chat->context) {
            $context = is_array($chat->context) ? $chat->context : json_decode($chat->context, true);
            if (is_array($context) && isset($context['visitor_id'])) {
                $visitorId = $context['visitor_id'];
            }
        }
        
        // Bulamazsak, ilk mesajın metadata'sında arayalım
        if (!$visitorId && $messages->count() > 0) {
            $firstMessage = $messages->where('sender', 'user')->first();
            if ($firstMessage) {
                $metadata = is_array($firstMessage->metadata) ? 
                    $firstMessage->metadata : 
                    json_decode($firstMessage->metadata, true);
                
                if (is_array($metadata) && isset($metadata['visitor_id'])) {
                    $visitorId = $metadata['visitor_id'];
                }
            }
        }
        
        // Her mesaj için ziyaretçi adını belirle
        foreach ($messages as $message) {
            $message->visitor_name = $this->getVisitorName($message);
        }
        
        // İstatistikler oluştur
        $stats = [
            'total_messages' => $messages->count(),
            'user_messages' => $messages->where('sender', 'user')->count(),
            'ai_messages' => $messages->where('sender', 'ai')->count(),
            'first_message' => $messages->first() ? $messages->first()->created_at : null,
            'last_message' => $messages->last() ? $messages->last()->created_at : null,
        ];
        
        // İstatistikleri hesapla
        $userPercentage = $stats['total_messages'] > 0 ? round(($stats['user_messages'] / $stats['total_messages']) * 100) : 0;
        $aiPercentage = 100 - $userPercentage;
        
        return view('admin.message_history.chat_history', compact('chat', 'messages', 'visitorId', 'stats', 'userPercentage', 'aiPercentage'));
    }
    
    /**
     * Mesajın ziyaretçi adını al
     */
    private function getVisitorName($message)
    {
        if ($message->sender !== 'user') {
            return null;
        }
        
        try {
            // Metadata JSON formatında mı veya dizi mi kontrol et
            $metadata = null;
            
            if (!empty($message->metadata)) {
                if (is_array($message->metadata)) {
                    $metadata = $message->metadata;
                } elseif (is_string($message->metadata)) {
                    $metadata = json_decode($message->metadata, true);
                }
            }
            
            // metadata boş veya geçersizse loglama yap
            if (empty($metadata)) {
                \Log::warning('Mesaj için metadata bilgisi bulunamadı', [
                    'message_id' => $message->id,
                    'chat_id' => $message->chat_id
                ]);
                return 'İsimsiz Ziyaretçi';
            }
            
            if (isset($metadata['visitor_id'])) {
                $visitorId = $metadata['visitor_id'];
                
                // visitor_names tablosunda arama yap
                $visitorName = DB::table('visitor_names')
                    ->where('visitor_id', $visitorId)
                    ->value('name');
                
                // Bulunamadıysa ama visitor_name metadata'da varsa onu kullan
                if (empty($visitorName) && isset($metadata['visitor_name'])) {
                    $visitorName = $metadata['visitor_name'];
                    
                    // Bu durumda visitor_names tablosuna kaydetmeyi dene
                    try {
                        DB::table('visitor_names')->updateOrInsert(
                            ['visitor_id' => $visitorId],
                            [
                                'name' => $visitorName,
                                'ip_address' => $message->ip_address,
                                'updated_at' => now(),
                                'created_at' => now()
                            ]
                        );
                    } catch (\Exception $e) {
                        \Log::error('İsimsiz ziyaretçi kaydedilirken hata: ' . $e->getMessage());
                    }
                }
                
                return $visitorName ?: 'İsimsiz Ziyaretçi';
            } else {
                \Log::warning('Mesaj metadatada visitor_id bulunamadı', [
                    'message_id' => $message->id,
                    'metadata' => $metadata
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Ziyaretçi adı alınırken hata: ' . $e->getMessage(), [
                'message_id' => $message->id,
                'exception' => $e
            ]);
        }
        
        return 'İsimsiz Ziyaretçi';
    }
    
    /**
     * Belirtilen visitor_id'nin mesajlarda olup olmadığını kontrol eder
     */
    private function checkIfVisitorExists($visitorId)
    {
        try {
            \Log::info("Ziyaretçi varlığı kontrol ediliyor", ['visitor_id' => $visitorId]);
            
            // Önce visitor_names tablosunda kontrol edelim (en kolay yöntem)
            $visitorExists = DB::table('visitor_names')
                ->where('visitor_id', $visitorId)
                ->exists();
                
            if ($visitorExists) {
                \Log::info("Ziyaretçi ID $visitorId visitor_names tablosunda bulundu");
                return true;
            }
            
            // JSON_EXTRACT ile mesajlarda kontrol edelim
            $existsJson = ChatMessage::whereRaw('JSON_EXTRACT(metadata, "$.visitor_id") = ?', ['"' . $visitorId . '"'])
                ->exists();
                
            if ($existsJson) {
                \Log::info("Ziyaretçi ID $visitorId mesajlarda JSON_EXTRACT ile bulundu");
                return true;
            }
            
            // LIKE operatörü ile mesajlarda kontrol edelim
            $existsLike = ChatMessage::where('metadata', 'LIKE', '%"visitor_id":"' . $visitorId . '"%')
                ->orWhere('metadata', 'LIKE', '%"visitor_id": "' . $visitorId . '"%')
                ->exists();
                
            if ($existsLike) {
                \Log::info("Ziyaretçi ID $visitorId mesajlarda LIKE operatörü ile bulundu");
                return true;
            }
            
            // Chat tablosundaki context'te kontrol edelim
            $existsChat = Chat::whereRaw('JSON_EXTRACT(context, "$.visitor_id") = ?', ['"' . $visitorId . '"'])
                ->orWhere('context', 'LIKE', '%"visitor_id":"' . $visitorId . '"%')
                ->exists();
                
            if ($existsChat) {
                \Log::info("Ziyaretçi ID $visitorId sohbet context'inde bulundu");
                return true;
            }
            
            // Son bir metod olarak DB->query() ile raw SQL sorgusu deneyelim
            try {
                $query = "SELECT EXISTS(SELECT 1 FROM chat_messages 
                    WHERE metadata LIKE '%\"visitor_id\":\"$visitorId\"%' 
                    OR metadata LIKE '%\"visitor_id\": \"$visitorId\"%' LIMIT 1) as found";
                
                $result = DB::select($query);
                
                if (isset($result[0]->found) && $result[0]->found == 1) {
                    \Log::info("Ziyaretçi ID $visitorId raw SQL sorgusu ile bulundu");
                    return true;
                }
            } catch (\Exception $sqlException) {
                \Log::error("Raw SQL sorgusu hatası: " . $sqlException->getMessage());
            }
            
            \Log::warning("Ziyaretçi ID $visitorId hiçbir yerde bulunamadı");
            return false;
        } catch (\Exception $e) {
            \Log::error('Ziyaretçi kontrolü yapılırken hata: ' . $e->getMessage());
            return false;
        }
    }
}
