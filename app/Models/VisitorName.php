<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorName extends Model
{
    use HasFactory;

    protected $fillable = [
        'visitor_id',
        'name',
        'avatar',
        'user_id',
        'ip_address',
        'device_info'
    ];

    /**
     * Ziyaretçi adını kaydet veya güncelle
     *
     * @param string $visitorId
     * @param string $name
     * @param string|null $ipAddress
     * @param string|null $deviceInfo
     * @param string|null $avatar
     * @param int|null $userId
     * @return VisitorName
     */
    public static function saveVisitorName(string $visitorId, string $name, ?string $ipAddress = null, ?string $deviceInfo = null, ?string $avatar = null, ?int $userId = null)
    {
        $data = [
            'name' => $name,
            'ip_address' => $ipAddress,
            'device_info' => $deviceInfo
        ];
        
        if ($avatar) {
            $data['avatar'] = $avatar;
        }
        
        if ($userId) {
            $data['user_id'] = $userId;
        }
        
        return self::updateOrCreate(
            ['visitor_id' => $visitorId],
            $data
        );
    }

    /**
     * Ziyaretçi adını getir
     *
     * @param string $visitorId
     * @return string|null
     */
    public static function getVisitorName(string $visitorId)
    {
        $visitor = self::where('visitor_id', $visitorId)->first();
        return $visitor ? $visitor->name : null;
    }
    
    /**
     * Bu ziyaretçiye ait kullanıcı bilgisi
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 