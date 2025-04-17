<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'content',
        'sender',
        'ip_address',
        'device_info',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    /**
     * Bu mesajın ait olduğu sohbet
     */
    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }
    
    /**
     * Mesaj kaydetme helper fonksiyonu
     * 
     * @param string $chatId Sohbet ID
     * @param string $content Mesaj içeriği
     * @param string $sender Gönderen (user/ai)
     * @param array $options Ek seçenekler
     * @return ChatMessage
     */
    public static function saveMessage($chatId, $content, $sender = 'user', $options = [])
    {
        try {
            // Eğer device_info bir dizi ise, JSON'a dönüştür
            $deviceInfo = $options['device_info'] ?? request()->header('User-Agent');
            if (is_array($deviceInfo)) {
                $deviceInfo = json_encode($deviceInfo);
            }
            
            $data = [
                'chat_id' => $chatId,
                'content' => $content,
                'sender' => $sender,
                'ip_address' => $options['ip_address'] ?? request()->ip(),
                'device_info' => $deviceInfo,
                'metadata' => $options['metadata'] ?? []
            ];
            
            Log::info('Mesaj kaydediliyor (Helper)', [
                'chat_id' => $chatId,
                'sender' => $sender,
                'content_length' => mb_strlen($content)
            ]);
            
            return self::create($data);
        } catch (\Exception $e) {
            Log::error('Mesaj helper kayıt hatası: ' . $e->getMessage(), [
                'chat_id' => $chatId,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            throw $e;
        }
    }
} 