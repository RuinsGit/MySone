<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LocationController extends Controller
{
    /**
     * Kullanıcının konum bilgilerini güncelle
     */
    public function updateLocation(Request $request)
    {
        try {
            $location = $request->input('location');
            $visitorId = $request->input('visitor_id');
            
            if (empty($location) || empty($visitorId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Konum veya ziyaretçi ID eksik'
                ]);
            }
            
            Log::info('Konum güncelleme isteği alındı', [
                'visitor_id' => $visitorId,
                'location' => $location
            ]);
            
            // Kullanıcı girişli ise users tablosunu güncelle
            if (auth()->check()) {
                $user = auth()->user();
                $user->latitude = $location['latitude'];
                $user->longitude = $location['longitude'];
                $user->location_info = json_encode($location);
                $user->save();
                
                Log::info('Kullanıcı konum bilgileri güncellendi', [
                    'user_id' => $user->id,
                    'latitude' => $location['latitude'],
                    'longitude' => $location['longitude']
                ]);
            }
            
            // Visitor_names tablosunu güncelle
            $updated = DB::table('visitor_names')
                ->where('visitor_id', $visitorId)
                ->update([
                    'latitude' => $location['latitude'],
                    'longitude' => $location['longitude'],
                    'location_info' => json_encode($location),
                    'updated_at' => now()
                ]);
            
            // Eğer kayıt yoksa oluştur
            if ($updated === 0) {
                // Kullanıcı adını almaya çalış
                $visitorName = session('visitor_name') ?? 'Ziyaretçi';
                
                DB::table('visitor_names')->insert([
                    'visitor_id' => $visitorId,
                    'name' => $visitorName,
                    'latitude' => $location['latitude'],
                    'longitude' => $location['longitude'],
                    'location_info' => json_encode($location),
                    'ip_address' => $request->ip(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                Log::info('Yeni ziyaretçi konum kaydı oluşturuldu', [
                    'visitor_id' => $visitorId,
                    'latitude' => $location['latitude'],
                    'longitude' => $location['longitude']
                ]);
            } else {
                Log::info('Ziyaretçi konum bilgileri güncellendi', [
                    'visitor_id' => $visitorId,
                    'latitude' => $location['latitude'],
                    'longitude' => $location['longitude']
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Konum bilgileri başarıyla güncellendi'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Konum güncellemesi sırasında hata', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Konum güncellenirken bir hata oluştu: ' . $e->getMessage()
            ]);
        }
    }
} 