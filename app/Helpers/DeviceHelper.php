<?php

namespace App\Helpers;

class DeviceHelper
{
    /**
     * Kullanıcının cihaz bilgilerini alma
     * 
     * @return array
     */
    public static function getUserDeviceInfo()
    {
        $userAgent = request()->header('User-Agent');
        $ip = self::getRealIpAddress();
        
        // Cihaz bilgilerini json formatında sakla
        $deviceInfo = json_encode([
            'user_agent' => $userAgent,
            'browser' => self::getBrowser($userAgent),
            'os' => self::getOS($userAgent),
            'device_type' => self::getDeviceType($userAgent)
        ]);
        
        return [
            'ip_address' => $ip,
            'device_info' => $deviceInfo
        ];
    }
    
    /**
     * Kullanıcının gerçek IP adresini alma
     * Proxy, load balancer veya CDN arkasında çalışırken gerçek IP'yi alır
     * 
     * @return string
     */
    public static function getRealIpAddress()
    {
        $request = request();
        
        // Cloudflare IP kontrolü
        if ($request->header('CF-Connecting-IP')) {
            return $request->header('CF-Connecting-IP');
        }
        
        // X-Forwarded-For kontrolü - birden fazla proxy varsa ilk IP gerçek kullanıcı IP'sidir
        if ($request->header('X-Forwarded-For')) {
            $ipList = explode(',', $request->header('X-Forwarded-For'));
            return trim($ipList[0]);
        }
        
        // Diğer yaygın proxy headerları
        $headers = [
            'X-Real-IP',
            'Client-IP',
            'X-Client-IP',
            'X-Cluster-Client-IP',
        ];
        
        foreach ($headers as $header) {
            if ($request->header($header)) {
                return $request->header($header);
            }
        }
        
        // Standart IP adresi
        return $request->ip();
    }
    
    /**
     * Tarayıcı bilgisini çıkarma
     */
    private static function getBrowser($userAgent)
    {
        $browser = "Bilinmeyen Tarayıcı";
        
        if (preg_match('/MSIE|Trident/i', $userAgent)) {
            $browser = 'Internet Explorer';
        } elseif (preg_match('/Edge/i', $userAgent)) {
            $browser = 'Microsoft Edge (Legacy)';
        } elseif (preg_match('/Edg/i', $userAgent)) {
            $browser = 'Microsoft Edge';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Mozilla Firefox';
        } elseif (preg_match('/OPR|Opera/i', $userAgent)) {
            $browser = 'Opera';
        } elseif (preg_match('/Chrome/i', $userAgent) && !preg_match('/Chromium/i', $userAgent)) {
            $browser = 'Google Chrome';
        } elseif (preg_match('/Safari/i', $userAgent) && !preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Safari';
        }
        
        return $browser;
    }
    
    /**
     * İşletim sistemi bilgisini çıkarma
     */
    private static function getOS($userAgent)
    {
        $os = "Bilinmeyen OS";
        
        if (preg_match('/windows|win32|win64/i', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $os = 'Mac OS';
        } elseif (preg_match('/android/i', $userAgent)) {
            $os = 'Android';
        } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            $os = 'iOS';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $os = 'Linux';
        }
        
        return $os;
    }
    
    /**
     * Cihaz tipini belirleme
     */
    private static function getDeviceType($userAgent)
    {
        $deviceType = "Masaüstü";
        
        if (preg_match('/iphone|android|webos|blackberry|ipad|ipod|blackberry|iemobile|opera mini/i', $userAgent)) {
            $deviceType = preg_match('/ipad|tablet/i', $userAgent) ? 'Tablet' : 'Mobil';
        }
        
        return $deviceType;
    }
} 