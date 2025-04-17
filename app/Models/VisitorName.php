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
     * @return VisitorName
     */
    public static function saveVisitorName(string $visitorId, string $name, ?string $ipAddress = null, ?string $deviceInfo = null)
    {
        return self::updateOrCreate(
            ['visitor_id' => $visitorId],
            [
                'name' => $name,
                'ip_address' => $ipAddress,
                'device_info' => $deviceInfo
            ]
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
} 