<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'status',
        'context'
    ];

    protected $casts = [
        'context' => 'array'
    ];

    /**
     * Bu sohbete ait mesajlar
     */
    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    /**
     * Son 20 mesajı al
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLastMessages($limit = 20)
    {
        return $this->messages()
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Bu sohbete ait kullanıcı
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 