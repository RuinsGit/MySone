<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WELCOME</title>
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
        }
        
        .welcome-container {
            text-align: center;
            color: white;
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 500px;
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
        
        .username-container {
            margin-bottom: 2.5rem;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeIn 1s ease-out 0.8s forwards;
        }
        
        .username-input {
            width: 100%;
            max-width: 300px;
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 8px;
            border: 2px solid rgba(99, 102, 241, 0.3);
            background-color: rgba(30, 41, 59, 0.8);
            color: white;
            outline: none;
            transition: all 0.3s ease;
            text-align: center;
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.2);
        }
        
        .username-input:focus {
            border-color: #a855f7;
            box-shadow: 0 0 20px rgba(168, 85, 247, 0.4);
        }
        
        .username-input::placeholder {
            color: #9ca3af;
        }
        
        .username-button {
            margin-top: 10px;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 500;
            background: linear-gradient(45deg, #8b5cf6, #6366f1);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.3);
        }
        
        .username-button:hover {
            background: linear-gradient(45deg, #a855f7, #818cf8);
            transform: translateY(-2px);
            box-shadow: 0 0 20px rgba(168, 85, 247, 0.5);
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
        <div class="welcome-title">WELCOME TO SONE AI</div>
        
        @if(session()->has('visitor_name'))
            <div class="welcome-subtitle">HOŞ GELDİN, {{ strtoupper(session('visitor_name')) }}!</div>
            
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
            
            <script>
                // Sayfa yüklendiğinde otomatik yükleme başlat
                document.addEventListener('DOMContentLoaded', function() {
                    startLoadingAnimation();
                });
                
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
        @else
            <div class="welcome-subtitle">ENTER YOUR NAME TO START</div>
            
            <div class="username-container">
                <form id="usernameForm">
                    <input type="text" id="username" class="username-input" placeholder="Type your name here..." autocomplete="off" required>
                    <button type="submit" class="username-button">START</button>
                </form>
            </div>
            
            <div id="loading-section" class="hidden">
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
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const usernameForm = document.getElementById('usernameForm');
                    const usernameContainer = document.querySelector('.username-container');
                    const loadingSection = document.getElementById('loading-section');
                    
                    // Form gönderimi
                    usernameForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        
                        const username = document.getElementById('username').value.trim();
                        
                        if (username) {
                            // Kullanıcı adını kaydet - AJAX ile
                            saveUsername(username);
                            
                            // Form gizlensin, yükleme gösterilsin
                            usernameContainer.classList.add('hidden');
                            loadingSection.classList.remove('hidden');
                            
                            // Yükleme çubuğunu başlat
                            startLoadingAnimation();
                        }
                    });
                    
                    // Kullanıcı adını kaydet
                    function saveUsername(username) {
                        fetch('{{ route("save.username") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ username: username })
                        })
                        .then(response => response.json())
                        .then(data => {
                            console.log('Username saved:', data);
                        })
                        .catch(error => {
                            console.error('Error saving username:', error);
                        });
                    }
                    
                    // Yükleme animasyonu
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
                });
            </script>
        @endif
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