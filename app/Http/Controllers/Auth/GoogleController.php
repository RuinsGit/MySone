<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Exception;

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
    public function handleGoogleCallback()
    {
        try {
            $user = Socialite::driver('google')->user();
            
            // Google ID ile kullanıcı var mı kontrol et
            $finduser = User::where('google_id', $user->id)->first();
            
            if ($finduser) {
                // Kullanıcı daha önce giriş yapmışsa, oturum aç
                Auth::login($finduser);
                
                // Doğrudan sohbet sayfasına yönlendir
                return redirect()->route('ai.chat');
            } else {
                // Email adresine göre kullanıcıyı kontrol et
                $existingUser = User::where('email', $user->email)->first();
                
                if ($existingUser) {
                    // Mevcut hesaba Google ID'yi ekle
                    $existingUser->google_id = $user->id;
                    $existingUser->avatar = $user->avatar;
                    $existingUser->save();
                    Auth::login($existingUser);
                    
                    // Doğrudan sohbet sayfasına yönlendir
                    return redirect()->route('ai.chat');
                } else {
                    // Yeni kullanıcı oluştur
                    $newUser = User::create([
                        'name' => $user->name,
                        'email' => $user->email,
                        'google_id' => $user->id,
                        'avatar' => $user->avatar,
                        'password' => bcrypt(rand(1000000, 9999999)), // Rastgele şifre
                    ]);
                    
                    Auth::login($newUser);
                    
                    // Doğrudan sohbet sayfasına yönlendir
                    return redirect()->route('ai.chat');
                }
            }
        } catch (Exception $e) {
            return redirect()->route('ai.welcome')->with('error', 'Google ile giriş yapılırken bir hata oluştu: ' . $e->getMessage());
        }
    }
}
