<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'content',
        'sender',
        'metadata',
        'ip_address',
        'device_info',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Mesajın ait olduğu chat'i getir
     */
    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }
} 