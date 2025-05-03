<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GoogleController extends Controller
{
    /**
     * Google SSO sayfasına yönlendir
     */
    public function redirectToGoogle()
    {
        try {
            return Socialite::driver('google')->redirect();
        } catch (Exception $e) {
            return redirect()->route('ai.welcome')->with('error', 'Google API yapılandırması eksik: ' . $e->getMessage());
        }
    }
    
    /**
     * Google'dan gelen kullanıcı bilgilerini işle
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            if (config('services.google.client_id') == null || config('services.google.client_secret') == null) {
                return redirect()->route('login')->with('error', 'Google API ayarları yapılandırılmamış.');
            }
            
            $googleUser = Socialite::driver('google')->user();
            $existingUser = User::where('google_id', $googleUser->id)->first();
            
            if ($existingUser) {
                // Kullanıcı zaten Google ID ile varsa, giriş yap ve Google'dan gelen adı güncelle
                $existingUser->name = $googleUser->name; // Google'dan gelen adı her zaman kullan
                $existingUser->avatar = $googleUser->avatar;
                $existingUser->save();
                
                Auth::login($existingUser);
                $this->updateVisitorInfo($request, $existingUser);
                Log::info('Google hesabıyla giriş yapıldı: ' . $existingUser->email);
                
                return redirect()->route('ai.chat.index');
            }
            
            // Google ID olmayan kullanıcıyı ara
            $existingEmail = User::where('email', $googleUser->email)->first();
            
            if ($existingEmail) {
                // Varolan hesabı Google ID ile güncelleyelim
                $existingEmail->google_id = $googleUser->id;
                $existingEmail->name = $googleUser->name; // Google'dan gelen adı her zaman kullan
                $existingEmail->avatar = $googleUser->avatar;
                $existingEmail->save();
                
                Auth::login($existingEmail);
                $this->updateVisitorInfo($request, $existingEmail);
                Log::info('Varolan hesap Google ile bağlandı: ' . $existingEmail->email);
                
                return redirect()->route('ai.chat.index');
            }
            
            // Yeni kullanıcı oluştur
            $newUser = User::create([
                'name' => $googleUser->name, // Google'dan gelen adı kullan
                'email' => $googleUser->email,
                'password' => Hash::make(Str::random(24)),
                'google_id' => $googleUser->id,
                'avatar' => $googleUser->avatar,
            ]);
            
            Auth::login($newUser);
            $this->updateVisitorInfo($request, $newUser);
            Log::info('Google ile yeni hesap oluşturuldu: ' . $newUser->email);
            
            return redirect()->route('ai.chat.index');
        } catch (\Exception $e) {
            Log::error('Google SSO hatası: ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'Google ile giriş sırasında bir hata oluştu. Lütfen tekrar deneyin.');
        }
    }

    /**
     * Google kullanıcısının ziyaretçi bilgilerini güncelle
     * 
     * @param $user Google'dan dönen kullanıcı bilgileri
     * @return void
     */
    private function updateVisitorInfo($request, $user)
    {
        try {
            // Her zaman Google'dan gelen adı kullan
            $visitorName = $user->name;
            
            // Ziyaretçi adını session'a kaydet
            session(['visitor_name' => $visitorName]);
            
            // Google'dan gelen adı veritabanındaki kullanıcı adı ile eşleştir
            $authUser = Auth::user();
            if ($authUser) {
                $authUser->name = $visitorName;
                
                // Avatar'ı da güncelle - Google'dan gelen avatar URL'sini kullan
                if (!empty($user->avatar)) {
                    $authUser->avatar = $user->avatar;
                    // Ayrıca session'a da kaydet
                    session(['user_avatar' => $user->avatar]);
                }
                
                $authUser->save();
                
                \Log::info('Kullanıcı bilgileri Google ile güncellendi', [
                    'user_id' => auth()->id(),
                    'google_name' => $visitorName,
                    'google_avatar' => $user->avatar
                ]);
                
                // Kullanıcının visitor_id'sini güncelle
                $authUser->visitor_id = 'google_user_' . $authUser->id;
                $authUser->save();
            }
            
            // Benzersiz visitor ID kullan - kullanıcı ID'si ile
            $visitorId = 'google_user_' . auth()->id();
            
            // Session'a kaydet
            session(['visitor_id' => $visitorId]);
            
            // Cihaz bilgilerini al
            $deviceInfo = app(\App\Helpers\DeviceHelper::class)->getUserDeviceInfo();
            
            // Visitor_names tablosuna kaydet
            \DB::table('visitor_names')->updateOrInsert(
                ['visitor_id' => $visitorId],
                [
                    'name' => $visitorName,
                    'avatar' => $user->avatar ?? null,
                    'ip_address' => $deviceInfo['ip_address'],
                    'device_info' => json_encode($deviceInfo['device_info']),
                    'user_id' => auth()->id(),
                    'updated_at' => now()
                ]
            );
            
            // Visitor_names tablosuna kaydedildiğini doğrula
            $visitorRecord = \DB::table('visitor_names')->where('visitor_id', $visitorId)->first();
            \Log::info('Visitor tablosu kaydı güncellendi', [
                'visitor_id' => $visitorId,
                'name' => $visitorRecord->name ?? 'Kayıt bulunamadı',
                'avatar' => $visitorRecord->avatar ?? 'Avatar bulunamadı'
            ]);
            
            \Log::info('Google kullanıcısının ziyaretçi bilgileri güncellendi', [
                'visitor_id' => $visitorId,
                'name' => $visitorName,
                'user_id' => auth()->id(),
                'google_id' => $user->google_id,
                'avatar' => $user->avatar
            ]);
        } catch (\Exception $e) {
            \Log::error('Google kullanıcısı ziyaretçi bilgileri güncelleme hatası: ' . $e->getMessage());
        }
    }
}
