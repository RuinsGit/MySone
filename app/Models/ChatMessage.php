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
} 