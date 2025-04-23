<?php

namespace App\Helpers;

class DeviceHelper
{
    /**
     * Kullanıcının cihaz bilgilerini al
     * 
     * @return array
     */
    public static function getUserDeviceInfo()
    {
        $userAgent = request()->header('User-Agent');
        $ipAddress = request()->ip();
        
        // Tarayıcı ve işletim sistemi bilgilerini çıkart
        $deviceInfo = self::parseUserAgent($userAgent);
        
        return [
            'ip_address' => $ipAddress,
            'device_info' => json_encode($deviceInfo)
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
     * User Agent bilgisinden tarayıcı ve işletim sistemi bilgilerini çıkart
     * 
     * @param string $userAgent
     * @return array
     */
    private static function parseUserAgent($userAgent)
    {
        $browser = 'Unknown Browser';
        $platform = 'Unknown Platform';
        $version = '';
        
        // İşletim sistemi tespiti
        if (preg_match('/windows|win32/i', $userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $platform = 'Mac';
        } elseif (preg_match('/android/i', $userAgent)) {
            $platform = 'Android';
        } elseif (preg_match('/iphone/i', $userAgent)) {
            $platform = 'iPhone';
        } elseif (preg_match('/ipad/i', $userAgent)) {
            $platform = 'iPad';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $platform = 'Linux';
        }
        
        // Tarayıcı tespiti
        if (preg_match('/MSIE/i', $userAgent) || preg_match('/Trident/i', $userAgent)) {
            $browser = 'Internet Explorer';
            if (preg_match('/MSIE\s([0-9]+\.[0-9]+)/i', $userAgent, $matches)) {
                $version = $matches[1];
            } elseif (preg_match('/rv:([0-9]+\.[0-9]+)/i', $userAgent, $matches)) {
                $version = $matches[1];
            }
        } elseif (preg_match('/Edge/i', $userAgent)) {
            $browser = 'Microsoft Edge';
            preg_match('/Edge\/([0-9]+\.[0-9]+)/i', $userAgent, $matches);
            if (isset($matches[1])) $version = $matches[1];
        } elseif (preg_match('/Edg/i', $userAgent)) {
            $browser = 'Microsoft Edge (Chromium)';
            preg_match('/Edg\/([0-9]+\.[0-9]+)/i', $userAgent, $matches);
            if (isset($matches[1])) $version = $matches[1];
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Mozilla Firefox';
            preg_match('/Firefox\/([0-9]+\.[0-9]+)/i', $userAgent, $matches);
            if (isset($matches[1])) $version = $matches[1];
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Google Chrome';
            preg_match('/Chrome\/([0-9]+\.[0-9]+)/i', $userAgent, $matches);
            if (isset($matches[1])) $version = $matches[1];
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $browser = 'Safari';
            preg_match('/Version\/([0-9]+\.[0-9]+)/i', $userAgent, $matches);
            if (isset($matches[1])) $version = $matches[1];
        } elseif (preg_match('/Opera/i', $userAgent)) {
            $browser = 'Opera';
            preg_match('/Version\/([0-9]+\.[0-9]+)/i', $userAgent, $matches);
            if (isset($matches[1])) $version = $matches[1];
        }
        
        return [
            'browser' => $browser,
            'browser_version' => $version,
            'platform' => $platform,
            'user_agent' => $userAgent
        ];
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