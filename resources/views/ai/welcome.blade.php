<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LIZZ AI - Hoşgeldiniz</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background:rgb(22, 30, 41);
            font-family: 'Arial', sans-serif;
            overflow: hidden;
            box-sizing: border-box;
        }
        
        *, *:before, *:after {
            box-sizing: inherit;
        }
        
        .welcome-container {
            text-align: center;
            color: white;
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 600px;
            padding: 0 20px;
        }
        
        .welcome-title {
            font-size: 5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeIn 1s ease-out forwards;
            color: #d1d5db;
            text-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
        }
        
        .welcome-subtitle {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeIn 1s ease-out 0.5s forwards;
            color: #9ca3af;
        }
        
        .auth-tabs {
            display: flex;
            margin-bottom: 2rem;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeIn 1s ease-out 0.5s forwards;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.2);
            width: 100%;
            max-width: 300px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .auth-tab {
            flex: 1;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            background: rgba(30, 41, 59, 0.7);
            color: #d1d5db;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .auth-tab.active {
            background: linear-gradient(45deg, #8b5cf6, #6366f1);
            color: white;
        }
        
        .form-container {
            margin-bottom: 2.5rem;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeIn 1s ease-out 0.8s forwards;
            width: 100%;
            max-width: 300px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .auth-form {
            display: none;
        }
        
        .auth-form.active {
            display: block;
        }
        
        .google-login {
            margin-top: 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            width: 100%;
            max-width: 300px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .google-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 0;
            border-radius: 8px;
            background-color: white;
            color: #4285F4;
            border: 2px solid rgba(66, 133, 244, 0.3);
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            cursor: pointer;
            width: 100%;
            font-size: 14px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-decoration: none;
        }
        
        .google-button:hover {
            box-shadow: 0 5px 15px rgba(66, 133, 244, 0.4);
            transform: translateY(-3px);
        }
        
        .google-button:active {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(66, 133, 244, 0.3);
        }
        
        .guest-button {
            background: linear-gradient(45deg, #a855f7, #8b5cf6);
            color: white;
            border: none;
            box-shadow: 0 2px 10px rgba(168, 85, 247, 0.3);
        }
        
        .guest-button:hover {
            background: linear-gradient(45deg, #9333ea, #7c3aed);
            box-shadow: 0 5px 15px rgba(168, 85, 247, 0.5);
        }
        
        .guest-button:active {
            box-shadow: 0 3px 10px rgba(168, 85, 247, 0.4);
        }
        
        .form-input {
            width: 100%;
            padding: 12px 15px;
            font-size: 16px;
            border-radius: 8px;
            border: 2px solid rgba(99, 102, 241, 0.3);
            background-color: rgba(30, 41, 59, 0.8);
            color: white;
            outline: none;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            text-align: center;
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.2);
            margin-bottom: 15px;
            box-sizing: border-box;
        }
        
        .form-input:focus {
            border-color: #a855f7;
            box-shadow: 0 0 20px rgba(168, 85, 247, 0.4);
        }
        
        .form-input::placeholder {
            color: #9ca3af;
        }
        
        .form-button {
            margin-top: 10px;
            padding: 12px 0;
            font-size: 16px;
            font-weight: 500;
            background: linear-gradient(45deg, #8b5cf6, #6366f1);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.3);
            width: 100%;
            box-sizing: border-box;
        }
        
        .form-button:hover {
            background: linear-gradient(45deg, #a855f7, #818cf8);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(168, 85, 247, 0.5);
        }
        
        .form-button:active {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(168, 85, 247, 0.4);
        }
        
        .form-link {
            margin-top: 15px;
            color: #a855f7;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
            color: #9ca3af;
            width: 100%;
            max-width: 300px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .divider::before, .divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid rgba(99, 102, 241, 0.3);
        }
        
        .divider::before {
            margin-right: 10px;
        }
        
        .divider::after {
            margin-left: 10px;
        }

        .error-message {
            color: #ef4444;
            font-size: 14px;
            margin: 10px 0;
        }
        
        .loading-container {
            position: relative;
            width: 140px;
            height: 140px;
            margin: 0 auto;
            margin-bottom: 2.5rem;
            opacity: 0;
            transform: scale(0.9);
            animation: fadeIn 1s ease-out 1s forwards;
        }
        
        .loading-circle {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: transparent;
            border: 4px solid transparent;
            border-top: 4px solid #8b5cf6;
            animation: spinCircle 2s linear infinite;
        }
        
        .loading-circle-2 {
            width: 80%;
            height: 80%;
            top: 10%;
            left: 10%;
            border: 3px solid transparent;
            border-left: 3px solid #a855f7;
            animation-duration: 1.5s;
            animation-direction: reverse;
        }
        
        .loading-circle-3 {
            width: 60%;
            height: 60%;
            top: 20%;
            left: 20%;
            border: 2px solid transparent;
            border-right: 2px solid #c084fc;
            animation-duration: 2s;
        }
        
        .loading-bar-container {
            width: 300px;
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.2);
            opacity: 0;
            animation: fadeIn 1s ease-out 1.2s forwards;
        }
        
        .loading-bar {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 0%;
            border-radius: 4px;
            background: linear-gradient(90deg, #8b5cf6, #a855f7);
            box-shadow: 0 0 10px #8b5cf6;
        }
        
        .percentage {
            font-size: 1rem;
            color: #a855f7;
            margin-top: 15px;
            font-weight: 500;
            opacity: 0;
            animation: fadeIn 1s ease-out 1.4s forwards;
        }
        
        #particles-js {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }
        
        .glow-effect {
            position: absolute;
            width: 140%;
            height: 140%;
            top: -20%;
            left: -20%;
            background: radial-gradient(ellipse at center, rgba(168, 85, 247, 0.2) 0%, rgba(0, 0, 0, 0) 70%);
            pointer-events: none;
        }
        
        .hidden {
            display: none;
        }
        
        @keyframes fadeIn {
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        @keyframes spinCircle {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                opacity: 0.8;
            }
            50% {
                transform: scale(1.05);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <div id="particles-js"></div>
    
    <div class="welcome-container">
        <div class="welcome-title">LIZZ AI</div>
        
        <div id="welcome-content">
            <!-- İçerik JavaScript ile doldurulacak -->
        </div>
        
        <script>
            // Sayfa yüklendiğinde çalışacak fonksiyon
            document.addEventListener('DOMContentLoaded', function() {
                // Giriş yapmış kullanıcı var mı kontrol et
                const isLoggedIn = {{ auth()->check() ? 'true' : 'false' }};
                
                if (isLoggedIn) {
                    const userName = "{{ auth()->user() ? auth()->user()->name : 'Kullanıcı' }}";
                    showWelcomeScreen(userName);
                    return;
                }
                
                // Hata mesajlarını kontrol et
                const errorMessage = "{{ session('error') ?? '' }}";
                
                // Login/Register formlarını göster
                showAuthForms(errorMessage);
            });
            
            // Hoş geldin ekranını gösterme fonksiyonu
            function showWelcomeScreen(username) {
                const welcomeContent = document.getElementById('welcome-content');
                
                welcomeContent.innerHTML = `
                    <div class="welcome-subtitle">HOŞ GELDİN, ${username.toUpperCase()}!</div>
                    
                    <div id="loading-section">
                        <div class="loading-container">
                            <div class="loading-circle"></div>
                            <div class="loading-circle loading-circle-2"></div>
                            <div class="loading-circle loading-circle-3"></div>
                            <div class="glow-effect"></div>
                        </div>
                        
                        <div class="loading-bar-container">
                            <div class="loading-bar" id="loading-bar"></div>
                        </div>
                        <div class="percentage" id="percentage">0%</div>
                    </div>
                `;
                
                // Yükleme animasyonunu başlat
                startLoadingAnimation();
            }
            
            // Auth formları gösterme fonksiyonu
            function showAuthForms(errorMessage = '') {
                const welcomeContent = document.getElementById('welcome-content');
                
                welcomeContent.innerHTML = `
                    <div class="welcome-subtitle">YAPAY ZEKA ASISTANIMIZA HOŞGELDİNİZ</div>
                    
                    @if (session('error'))
                    <div class="error-message" style="margin-bottom: 15px; background-color: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 5px; border-left: 3px solid #ef4444;">
                        {{ session('error') }}
                    </div>
                    @endif
                    
                    <div class="auth-tabs">
                        <div class="auth-tab active" id="login-tab">Giriş</div>
                        <div class="auth-tab" id="register-tab">Kayıt</div>
                    </div>
                    
                    <div class="form-container">
                        ${errorMessage ? `<div class="error-message">${errorMessage}</div>` : ''}
                        
                        <!-- Google ile Giriş ve Misafir Olarak Giriş Butonları -->
                        <div class="google-login">
                            <a href="{{ route('google.login') }}" class="google-button">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 48 48">
                                    <path fill="#4285F4" d="M45.12 24.5c0-1.56-.14-3.06-.4-4.5H24v8.51h11.84c-.51 2.75-2.06 5.08-4.39 6.64v5.52h7.11c4.16-3.83 6.56-9.47 6.56-16.17z"/>
                                    <path fill="#34A853" d="M24 46c5.94 0 10.92-1.97 14.56-5.33l-7.11-5.52c-1.97 1.32-4.49 2.1-7.45 2.1-5.73 0-10.58-3.87-12.31-9.07H4.34v5.7C7.96 41.07 15.4 46 24 46z"/>
                                    <path fill="#FBBC05" d="M11.69 28.18C11.25 26.86 11 25.45 11 24s.25-2.86.69-4.18v-5.7H4.34C2.85 17.09 2 20.45 2 24c0 3.55.85 6.91 2.34 9.88l7.35-5.7z"/>
                                    <path fill="#EA4335" d="M24 10.75c3.23 0 6.13 1.11 8.41 3.29l6.31-6.31C34.91 4.18 29.93 2 24 2 15.4 2 7.96 6.93 4.34 14.12l7.35 5.7c1.73-5.2 6.58-9.07 12.31-9.07z"/>
                                </svg>
                                Google
                            </a>
                            <button id="guest-login-button" class="google-button guest-button">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                Misafir
                            </button>
                        </div>
                        
                        <div class="divider">veya</div>
                        
                        <!-- Giriş Formu -->
                        <div class="auth-form active" id="login-form">
                            <form method="POST" action="{{ route('login') }}">
                                @csrf
                                <input type="email" name="email" class="form-input" placeholder="E-posta Adresi" required value="{{ old('email') }}">
                                @error('email')
                                <div class="error-message">{{ $message }}</div>
                                @enderror
                                
                                <input type="password" name="password" class="form-input" placeholder="Şifre" required>
                                @error('password')
                                <div class="error-message">{{ $message }}</div>
                                @enderror
                                
                                <div style="margin: 15px 0;">
                                    <label style="color: #9ca3af; font-size: 14px;">
                                        <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                        Beni Hatırla
                                    </label>
                                </div>
                                
                                <button type="submit" class="form-button">Giriş Yap</button>
                            </form>
                            
                            <div class="form-link" id="show-register">Hesabınız yok mu? Kayıt olun</div>
                        </div>
                        
                        <!-- Kayıt Formu -->
                        <div class="auth-form" id="register-form">
                            <form method="POST" action="{{ route('register') }}">
                                @csrf
                                <input type="text" name="name" class="form-input" placeholder="İsim" required value="{{ old('name') }}">
                                @error('name')
                                <div class="error-message">{{ $message }}</div>
                                @enderror
                                
                                <input type="email" name="email" class="form-input" placeholder="E-posta Adresi" required value="{{ old('email') }}">
                                @error('email')
                                <div class="error-message">{{ $message }}</div>
                                @enderror
                                
                                <input type="password" name="password" class="form-input" placeholder="Şifre" required>
                                @error('password')
                                <div class="error-message">{{ $message }}</div>
                                @enderror
                                
                                <input type="password" name="password_confirmation" class="form-input" placeholder="Şifre Tekrarı" required>
                                
                                <button type="submit" class="form-button">Kayıt Ol</button>
                            </form>
                            
                            <div class="form-link" id="show-login">Zaten hesabınız var mı? Giriş yapın</div>
                        </div>
                    </div>
                `;
                
                // Tab değişimi eventleri
                document.getElementById('login-tab').addEventListener('click', function() {
                    document.getElementById('login-tab').classList.add('active');
                    document.getElementById('register-tab').classList.remove('active');
                    document.getElementById('login-form').classList.add('active');
                    document.getElementById('register-form').classList.remove('active');
                });
                
                document.getElementById('register-tab').addEventListener('click', function() {
                    document.getElementById('register-tab').classList.add('active');
                    document.getElementById('login-tab').classList.remove('active');
                    document.getElementById('register-form').classList.add('active');
                    document.getElementById('login-form').classList.remove('active');
                });
                
                document.getElementById('show-register').addEventListener('click', function() {
                    document.getElementById('register-tab').click();
                });
                
                document.getElementById('show-login').addEventListener('click', function() {
                    document.getElementById('login-tab').click();
                });
                
                // Misafir olarak giriş butonuna tıklama
                document.getElementById('guest-login-button').addEventListener('click', function() {
                    startGuestLogin();
                });
            }
            
            // Misafir olarak giriş fonksiyonu
            function startGuestLogin() {
                // Random isim oluştur
                const randomNames = [
                    "Misafir", "YeniKullanıcı", "Gezici", "Ziyaretçi", "Konuk", 
                    "GeçiciKullanıcı", "Anonim", "Gezgin", "MeraklıZiyaretçi", "Yolcu",
                    "Keşfedici", "HızlıGezgin", "MisafirDost", "YeniMisafir", "GeçerkenUğrayan"
                ];
                
                const randomName = randomNames[Math.floor(Math.random() * randomNames.length)] + Math.floor(Math.random() * 1000);
                
                // Misafir girişi için yükleme ekranını göster
                const welcomeContent = document.getElementById('welcome-content');
                
                welcomeContent.innerHTML = `
                    <div class="welcome-subtitle">HOŞ GELDİN, ${randomName}!</div>
                    
                    <div id="loading-section">
                        <div class="loading-container">
                            <div class="loading-circle"></div>
                            <div class="loading-circle loading-circle-2"></div>
                            <div class="loading-circle loading-circle-3"></div>
                            <div class="glow-effect"></div>
                        </div>
                        
                        <div class="loading-bar-container">
                            <div class="loading-bar" id="loading-bar"></div>
                        </div>
                        <div class="percentage" id="percentage">0%</div>
                    </div>
                `;
                
                // Yükleme animasyonunu başlat
                startLoadingAnimation();
                
                // Misafir bilgisini sakla
                localStorage.setItem('guestName', randomName);
                
                // Misafir olarak chat sayfasına yönlendir
                setTimeout(() => {
                    window.location.href = "{{ route('ai.chat') }}?guest=true&name=" + encodeURIComponent(randomName);
                }, 5000);
            }
            
            // Yükleme animasyonu fonksiyonu
            function startLoadingAnimation() {
                const loadingBar = document.getElementById('loading-bar');
                const percentageElement = document.getElementById('percentage');
                
                let currentPercentage = 0;
                const duration = 4900; // 5 saniyeden biraz az (yönlendirme için)
                const interval = 30; // Her 30ms'de bir güncelleme
                const steps = duration / interval;
                const increment = 100 / steps;
                
                const loadingInterval = setInterval(() => {
                    currentPercentage += increment;
                    
                    if (currentPercentage >= 100) {
                        currentPercentage = 100;
                        clearInterval(loadingInterval);
                        
                        // Chat sayfasına yönlendir
                        setTimeout(() => {
                            window.location.href = "{{ route('ai.chat') }}";
                        }, 500);
                    }
                    
                    const displayPercentage = Math.floor(currentPercentage);
                    loadingBar.style.width = `${currentPercentage}%`;
                    percentageElement.textContent = `${displayPercentage}%`;
                    
                }, interval);
            }
        </script>
    </div>
    
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>        
        // Parçacık efekti yapılandırması
        particlesJS('particles-js', {
            particles: {
                number: {
                    value: 120,
                    density: {
                        enable: true,
                        value_area: 800
                    }
                },
                color: {
                    value: ['#a855f7', '#8b5cf6', '#c084fc', '#f0abfc']
                },
                shape: {
                    type: "circle"
                },
                opacity: {
                    value: 0.6,
                    random: true,
                    anim: {
                        enable: true,
                        speed: 2,
                        opacity_min: 0.2,
                        sync: false
                    }
                },
                size: {
                    value: 3,
                    random: true,
                    anim: {
                        enable: true,
                        speed: 3,
                        size_min: 0.5,
                        sync: false
                    }
                },
                line_linked: {
                    enable: true,
                    distance: 150,
                    color: '#a855f7',
                    opacity: 0.4,
                    width: 1.5
                },
                move: {
                    enable: true,
                    speed: 2,
                    direction: "none",
                    random: true,
                    straight: false,
                    out_mode: "out",
                    bounce: false,
                    attract: {
                        enable: true,
                        rotateX: 600,
                        rotateY: 1200
                    }
                }
            },
            interactivity: {
                detect_on: "canvas",
                events: {
                    onhover: {
                        enable: true,
                        mode: "grab"
                    },
                    onclick: {
                        enable: true,
                        mode: "push"
                    },
                    resize: true
                },
                modes: {
                    grab: {
                        distance: 180,
                        line_linked: {
                            opacity: 1
                        }
                    },
                    push: {
                        particles_nb: 6
                    }
                }
            },
            retina_detect: true
        });
    </script>
</body>
</html> 