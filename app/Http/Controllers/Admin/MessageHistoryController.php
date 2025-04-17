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
        
        \Log::info("Mesaj geçmişi sayfası açılıyor", [
            'search' => $search,
            'filter_by' => $filterBy,
            'visitor_id' => $visitorId
        ]);
        
        // Visitor_names tablosundan tüm ziyaretçileri al
        $visitors = DB::table('visitor_names')
            ->select('visitor_id', 'name', 'ip_address')
            ->orderBy('name')
            ->get();
            
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
        
        // Belirli bir ziyaretçinin mesajlarını filtrele
        if (!empty($visitorId)) {
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
        
        // Ziyaretçi adlarını isimle arama formunda kullanmak için al
        $visitorNames = DB::table('visitor_names')
            ->pluck('name', 'visitor_id')
            ->toArray();
            
        return view('admin.message_history.index', compact('messages', 'search', 'filterBy', 'visitorId', 'visitors', 'visitorNames'));
    }
    
    /**
     * Ziyaretçi listesini göster
     */
    public function visitors()
    {
        $visitors = DB::table('visitor_names')
            ->select('visitor_id', 'name', 'ip_address', 'created_at')
            ->orderBy('name')
            ->get();
            
        return view('admin.message_history.visitors', compact('visitors'));
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
        
        // Debug için visitor ID'yi logla
        \Log::info("Ziyaretçi mesajları görüntüleniyor", ['visitor_id' => $visitorId]);
        
        // Ziyaretçi bilgilerini al
        $visitor = DB::table('visitor_names')
            ->where('visitor_id', $visitorId)
            ->first();
            
        // Eğer ziyaretçi bilgisi yoksa ve mesajlarda da bulunamıyorsa hata ver
        if (!$visitor) {
            $visitorExists = $this->checkIfVisitorExists($visitorId);
            
            if (!$visitorExists) {
                return redirect()->route('admin.message-history.index')
                    ->with('error', 'Belirtilen ID için ziyaretçi bulunamadı');
            }
            
            // Eğer visitor_names tablosunda kaydı yoksa ama mesajlarda varsa, bir kayıt oluşturalım
            try {
                // Ziyaretçiye ait bir mesaj bul
                $firstMessage = ChatMessage::whereRaw('JSON_EXTRACT(metadata, "$.visitor_id") = ?', ['"' . $visitorId . '"'])
                    ->where('sender', 'user')
                    ->orderBy('created_at', 'asc')
                    ->first();
                
                if ($firstMessage) {
                    $metadata = is_array($firstMessage->metadata) 
                        ? $firstMessage->metadata 
                        : json_decode($firstMessage->metadata, true);
                    
                    $visitorName = $metadata['visitor_name'] ?? 'İsimsiz Ziyaretçi';
                    
                    // visitor_names tablosuna ekle
                    DB::table('visitor_names')->insert([
                        'visitor_id' => $visitorId,
                        'name' => $visitorName,
                        'ip_address' => $firstMessage->ip_address,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    // Yeni oluşturulan kaydı al
                    $visitor = DB::table('visitor_names')
                        ->where('visitor_id', $visitorId)
                        ->first();
                }
            } catch (\Exception $e) {
                \Log::error('Eksik ziyaretçi kaydı oluşturma hatası: ' . $e->getMessage());
            }
        }
        
        // Önce bu ziyaretçinin tüm mesajlarını bulalım (sayfa için)
        try {
            \Log::info("Ziyaretçi ID ile mesaj sorgusu yapılıyor", ['visitor_id' => $visitorId]);
            
            // Farklı sorgulama stratejileri deneyelim
            $queryMethod = 'multi';
            
            if ($queryMethod === 'JSON_EXTRACT') {
                // JSON_EXTRACT kullanarak sorgulama
                $messagesQuery = ChatMessage::whereRaw('JSON_EXTRACT(metadata, "$.visitor_id") = ?', ['"' . $visitorId . '"'])
                    ->orderBy('created_at', 'desc');
                
                \Log::info("JSON_EXTRACT metodu kullanıldı");
            } 
            elseif ($queryMethod === 'LIKE') {
                // LIKE kullanarak sorgulama (metadata metin ise)
                $messagesQuery = ChatMessage::where('metadata', 'LIKE', '%"visitor_id":"' . $visitorId . '"%')
                    ->orderBy('created_at', 'desc');
                
                \Log::info("LIKE metodu kullanıldı");
            }
            else {
                // Tüm olası sorgu yöntemlerini deneyen çoklu yaklaşım
                $messagesQuery = ChatMessage::where(function($query) use ($visitorId) {
                    // JSON_EXTRACT ile arama
                    $query->whereRaw('JSON_EXTRACT(metadata, "$.visitor_id") = ?', ['"' . $visitorId . '"'])
                    // VEYA metin içinde arama 
                    ->orWhere('metadata', 'LIKE', '%"visitor_id":"' . $visitorId . '"%')
                    // VEYA basit bir dizi içinde arama
                    ->orWhere('metadata', 'LIKE', '%"visitor_id": "' . $visitorId . '"%')
                    // VEYA chat modelinde context içinde saklıysa
                    ->orWhereHas('chat', function($q) use ($visitorId) {
                        $q->whereRaw('JSON_EXTRACT(context, "$.visitor_id") = ?', ['"' . $visitorId . '"'])
                        ->orWhere('context', 'LIKE', '%"visitor_id":"' . $visitorId . '"%');
                    });
                })
                ->orderBy('created_at', 'desc');
                
                \Log::info("Çoklu sorgulama metodu kullanıldı");
            }
            
            // Bulunan mesaj sayısını logla
            $countBeforePagination = $messagesQuery->count();
            \Log::info("Bulunan toplam mesaj sayısı", ['count' => $countBeforePagination]);
            
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
     * Belirli bir sohbetin mesajlarını görüntüle
     */
    public function chatHistory($chatId)
    {
        try {
            // Sohbet bilgilerini al
            $chat = Chat::findOrFail($chatId);
            
            \Log::info("Sohbet görüntüleniyor", ['chat_id' => $chatId]);
            
            // Sohbetin mesajlarını kronolojik sırayla al
            $messages = ChatMessage::where('chat_id', $chatId)
                ->orderBy('created_at', 'asc')
                ->get();
                
            \Log::info("Sohbet mesajları bulundu", ['count' => $messages->count()]);
                
            // Sohbeti başlatan ziyaretçi bilgisi
            $visitorId = null;
            $visitorName = null;
            
            // Önce context içinde visitor_id'yi kontrol edelim
            if ($chat->context && is_array($chat->context) && isset($chat->context['visitor_id'])) {
                $visitorId = $chat->context['visitor_id'];
                \Log::info("Sohbet context'inde visitor_id bulundu", ['visitor_id' => $visitorId]);
            } 
            // Json formattaysa decode edip bakalım
            elseif ($chat->context && is_string($chat->context)) {
                $context = json_decode($chat->context, true);
                if (is_array($context) && isset($context['visitor_id'])) {
                    $visitorId = $context['visitor_id'];
                    \Log::info("Sohbet context'i decode edilerek visitor_id bulundu", ['visitor_id' => $visitorId]);
                }
            }
            
            // Sohbet context'inde visitor_id yoksa, ilk mesajın metadata'sına bakalım
            if (empty($visitorId) && $messages->count() > 0) {
                $firstUserMessage = $messages->where('sender', 'user')->first();
                
                if ($firstUserMessage) {
                    $metadata = null;
                    
                    if (is_array($firstUserMessage->metadata)) {
                        $metadata = $firstUserMessage->metadata;
                    } elseif (is_string($firstUserMessage->metadata)) {
                        $metadata = json_decode($firstUserMessage->metadata, true);
                    }
                    
                    if (is_array($metadata) && isset($metadata['visitor_id'])) {
                        $visitorId = $metadata['visitor_id'];
                        \Log::info("İlk kullanıcı mesajında visitor_id bulundu", ['visitor_id' => $visitorId]);
                    }
                }
            }
            
            if ($visitorId) {
                $visitorInfo = DB::table('visitor_names')
                    ->where('visitor_id', $visitorId)
                    ->first();
                    
                if ($visitorInfo) {
                    $visitorName = $visitorInfo->name;
                    \Log::info("Ziyaretçi adı bulundu", ['name' => $visitorName]);
                } else {
                    \Log::warning("Visitor_id ($visitorId) için ziyaretçi kaydı bulunamadı");
                }
            } else {
                \Log::warning("Sohbet için visitor_id bulunamadı", ['chat_id' => $chatId]);
            }
            
            // İstatistikler
            $stats = [
                'total_messages' => $messages->count(),
                'user_messages' => $messages->where('sender', 'user')->count(),
                'ai_messages' => $messages->where('sender', 'ai')->count(),
                'first_message' => $messages->first() ? $messages->first()->created_at : null,
                'last_message' => $messages->last() ? $messages->last()->created_at : null,
            ];
            
            return view('admin.message_history.chat_history', compact('chat', 'messages', 'visitorId', 'visitorName', 'stats'));
        } catch (\Exception $e) {
            \Log::error('Sohbet görüntüleme hatası: ' . $e->getMessage(), [
                'chat_id' => $chatId,
                'exception' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.message-history.index')
                ->with('error', 'Sohbet görüntülenirken bir hata oluştu: ' . $e->getMessage());
        }
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
