<script>
    // Yükleme sonrası highlight.js'yi başlat
    document.addEventListener('DOMContentLoaded', function() {
        hljs.configure({
            languages: ['javascript', 'typescript', 'php', 'css', 'html', 'json'],
            ignoreUnescapedHTML: true
        });
        hljs.highlightAll();

        // Kullanıcı konum izni ve kontrolü
        let userLocation = {
            latitude: null,
            longitude: null,
            accuracy: null,
            timestamp: null
        };

        // Konum izni iste
        function requestLocationPermission() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    // Başarı durumunda
                    function(position) {
                        userLocation = {
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude,
                            accuracy: position.coords.accuracy,
                            timestamp: position.timestamp
                        };
                        // Konum bilgilerini localStorage'a kaydet
                        localStorage.setItem('user_location', JSON.stringify(userLocation));
                        console.log('✓ Konum bilgileri alındı:', userLocation);
                        
                        // Konum bilgilerini sunucuya da gönder (oturum doğru şekilde güncellenmesi için)
                        updateServerWithLocation(userLocation);
                    },
                    // Hata durumunda
                    function(error) {
                        let errorMessage = '';
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                errorMessage = "Konum izni reddedildi."; 
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMessage = "Konum bilgisi kullanılamıyor."; 
                                break;
                            case error.TIMEOUT:
                                errorMessage = "Konum bilgisi alınırken zaman aşımı."; 
                                break;
                            case error.UNKNOWN_ERROR:
                                errorMessage = "Bilinmeyen bir hata oluştu."; 
                                break;
                        }
                        console.warn('Konum izni hatası:', errorMessage, error);
                        // Sadece hata kodunu kaydet
                        localStorage.setItem('location_error', error.code);
                        localStorage.setItem('location_error_message', errorMessage);
                    },
                    // Seçenekler
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 600000 // 10 dakika
                    }
                );
            } else {
                console.warn('Tarayıcınız konum desteği sunmuyor.');
                localStorage.setItem('location_error', 'UNSUPPORTED');
            }
        }
        
        // Konum bilgilerini sunucuya gönder
        function updateServerWithLocation(locationData) {
            try {
                if (!locationData || !locationData.latitude || !locationData.longitude) {
                    console.warn('Güncellenecek konum bilgisi yok veya eksik!');
                    return;
                }
                
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                // Konum bilgilerini API'ye gönder
                fetch('/api/update-location', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        location: locationData,
                        visitor_id: '{{ session('visitor_id') }}'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('✓ Konum bilgileri sunucuya kaydedildi');
                    } else {
                        console.warn('! Konum bilgileri sunucuya kaydedilemedi:', data.message);
                    }
                })
                .catch(error => {
                    console.error('! Konum güncelleme hatası:', error);
                });
            } catch (error) {
                console.error('! Konum güncelleme işlemi sırasında hata:', error);
            }
        }

        // Sayfa yüklendiğinde konum izni iste
        requestLocationPermission();

        // Bildirim sesi değişkenleri
        let notificationSound = new Audio('{{ asset('music/Ivory.mp3') }}');
        let isNotificationMuted = localStorage.getItem('notification_muted') === 'true';

        // Mobil Menü işlevselliği
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        
        // Hamburger menü tıklama olayı
        if (mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', function() {
                toggleSidebar();
            });
        }
        
        // Overlay tıklama olayı
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                toggleSidebar();
            });
        }
        
        // Sidebar göster/gizle
        function toggleSidebar() {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
            
            // Scrollu kilitlemek/açmak için
            if (sidebar.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }
    });

    // Hızlı erişim için global değişken
    let fullscreenToggleBtn;
    
    // Sayfa yüklenmesi sırasında global değişkeni ayarla
    window.onload = function() {
        console.log('Sayfa tam olarak yüklendi (window.onload)');
        fullscreenToggleBtn = document.getElementById('fullscreen-toggle');
        console.log('Global fullscreenToggleBtn:', fullscreenToggleBtn);
        
        if (fullscreenToggleBtn) {
            fullscreenToggleBtn.onclick = function() {
                console.log('window.onload - Tam ekran butonuna tıklandı');
                toggleFullScreenGlobal();
                return false;
            };
        }
    };
    
    // Global erişim için tam ekran fonksiyonu
    function toggleFullScreenGlobal() {
        console.log('toggleFullScreenGlobal çağrıldı');
        try {
            if (!document.fullscreenElement &&
                !document.mozFullScreenElement &&
                !document.webkitFullscreenElement &&
                !document.msFullscreenElement) {
                
                console.log('Tam ekran moduna geçiliyor (global)');
                if (document.documentElement.requestFullscreen) {
                    document.documentElement.requestFullscreen();
                } else if (document.documentElement.mozRequestFullScreen) {
                    document.documentElement.mozRequestFullScreen();
                } else if (document.documentElement.webkitRequestFullscreen) {
                    document.documentElement.webkitRequestFullscreen();
                } else if (document.documentElement.msRequestFullscreen) {
                    document.documentElement.msRequestFullscreen();
                }
                
                // İkonu değiştir
                if (fullscreenToggleBtn) {
                    fullscreenToggleBtn.querySelector('i').classList.remove('fa-expand');
                    fullscreenToggleBtn.querySelector('i').classList.add('fa-compress');
                }
            } else {
                console.log('Tam ekran modundan çıkılıyor (global)');
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.mozCancelFullScreen) {
                    document.mozCancelFullScreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
                
                // İkonu değiştir
                if (fullscreenToggleBtn) {
                    fullscreenToggleBtn.querySelector('i').classList.remove('fa-compress');
                    fullscreenToggleBtn.querySelector('i').classList.add('fa-expand');
                }
            }
        } catch (error) {
            console.error('Tam ekran işlemi sırasında hata oluştu (global):', error);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        console.log('Sayfa yüklendi, DOM hazır');
        
        // DOM elemanlarını seç
        const messageInput = document.getElementById('message-input');
        const sendMessageBtn = document.getElementById('send-message');
        const messagesContainer = document.getElementById('messages');
        const aiThinking = document.getElementById('ai-thinking');
        
        // Bildirim sesi için değişkenler
        let notificationSound = new Audio('{{ asset('music/Ivory.mp3') }}');
        let isNotificationMuted = localStorage.getItem('notification_muted') === 'true';
        
        // Bildirim ses kontrolü butonunu ekleme
        const soundToggleBtn = document.createElement('button');
        soundToggleBtn.id = 'sound-toggle';
        soundToggleBtn.className = 'sound-toggle-btn';
        soundToggleBtn.innerHTML = isNotificationMuted 
            ? '<i class="fas fa-volume-mute"></i>' 
            : '<i class="fas fa-volume-up"></i>';
        soundToggleBtn.title = isNotificationMuted 
            ? 'Bildirim sesini aç' 
            : 'Bildirim sesini kapat';
        
        // Bildirim ses kontrolü butonunu Sidebar'a ekle
        const sidebarOptions = document.querySelector('.sidebar-options');
        if (sidebarOptions) {
            // Yeni bir sidebar-option div'i oluştur
            const soundToggleOption = document.createElement('div');
            soundToggleOption.className = 'sidebar-option';
            
            // Etiket ekle
            const soundLabel = document.createElement('span');
            soundLabel.textContent = 'Bildirim Sesi';
            
            // Etiket ve butonu div'e ekle
            soundToggleOption.appendChild(soundLabel);
            soundToggleOption.appendChild(soundToggleBtn);
            
            // Oluşturulan div'i sidebar-options'a ekle
            sidebarOptions.appendChild(soundToggleOption);
        }
        
        // Bildirim ses kontrolü butonu tıklama olayı
        soundToggleBtn.addEventListener('click', function() {
            isNotificationMuted = !isNotificationMuted;
            localStorage.setItem('notification_muted', isNotificationMuted);
            
            if (isNotificationMuted) {
                this.innerHTML = '<i class="fas fa-volume-mute"></i>';
                this.title = 'Bildirim sesini aç';
            } else {
                this.innerHTML = '<i class="fas fa-volume-up"></i>';
                this.title = 'Bildirim sesini kapat';
                // Buton tıklamasını test etmek için bildirim sesini çal
                playNotificationSound();
            }
        });
        
        // Bildirim sesini çal
        function playNotificationSound() {
            if (!isNotificationMuted) {
                notificationSound.volume = 0.5; // Ses seviyesini %50 yap
                notificationSound.play().catch(error => {
                    console.error('Bildirim sesi çalınamadı:', error);
                });
            }
        }
        
        const inputContainer = document.querySelector('.input-container');
        const chatMessagesContainer = document.querySelector('.chat-messages-container');
        const fullscreenToggle = document.getElementById('fullscreen-toggle');
        
        console.log('Tam ekran butonu element:', fullscreenToggle);
        
        // Masaüstü kontrolleri
        const creativeToggle = document.getElementById('creative-toggle');
        const codingToggle = document.getElementById('coding-toggle');
        const codeLanguage = document.getElementById('code-language');
        const languageSettings = document.getElementById('language-settings');
        const modelSelector = document.getElementById('model-selector');
        
   
        
        // Kullanıcı adını localStorage'a kaydet
        let visitorName = ''; // Temiz başla

        // Eğer session'da visitor_name varsa ve cookie değerlerini içermiyorsa kullan
        const sessionVisitorName = '{{ session('visitor_name') }}';
        if (sessionVisitorName && !sessionVisitorName.includes('=') && !sessionVisitorName.includes(';')) {
            visitorName = sessionVisitorName;
            localStorage.setItem('visitor_name', sessionVisitorName);
        } 
        // Eğer localStorage'da varsa ve cookie değerlerini içermiyorsa kullan
        else if (localStorage.getItem('visitor_name') && 
                !localStorage.getItem('visitor_name').includes('=') && 
                !localStorage.getItem('visitor_name').includes(';')) {
            visitorName = localStorage.getItem('visitor_name');
        } 
        // Hiçbiri yoksa varsayılan kullan
        else {
            visitorName = 'Kullanıcı';
            // Geçersiz değerleri temizle
            localStorage.removeItem('visitor_name');
        }
        
        // Kullanıcı adı kontrolü
        const needsName = {{ $initialState['needs_name'] ? 'true' : 'false' }};
        let nameRequested = false;
        
        // İlk yükleme sırasında kullanıcı adı isteme
        if (needsName && !nameRequested) {
            setTimeout(() => {
                addMessage("Merhaba! Ben Lizz. Sana nasıl hitap etmemi istersin?", 'ai');
                nameRequested = true;
                messageInput.placeholder = "Adınızı yazın...";
                messageInput.focus();
            }, 1000);
        }
        
      
        
        // Mobil cihazlar için viewport yüksekliği ayarı
        function setVhVariable() {
            let vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        }
        
        // Uygulama durumu
        let isCreativeMode = localStorage.getItem('creative_mode') === 'true';
        let isCodingMode = localStorage.getItem('coding_mode') === 'true';
        let selectedModel = localStorage.getItem('selected_model') || 'gemini';
        let chatHistory = JSON.parse(localStorage.getItem('chat_history')) || [];
        
        // Depolanan ayarları yükle
        function loadSettings() {
            // Masaüstü
            if (creativeToggle) creativeToggle.checked = isCreativeMode;
            if (codingToggle) codingToggle.checked = isCodingMode;
            if (modelSelector) modelSelector.value = selectedModel;
            if (languageSettings) {
                languageSettings.style.display = isCodingMode ? 'block' : 'none';
            }
            
            updateModelDisplay();
        }
        
        // YENİ: Sadece model göstergesini güncelle
        function updateModelDisplay() {
            const modelNameElement = document.getElementById('model-name');
            if (modelNameElement) {
                modelNameElement.textContent = selectedModel === 'soneai' ? 'LizzAI Basic' : 'LizzAI Turbo';
            }
        }
        
      
        
        // Mesaj gönder
        async function sendMessage(message, isFirstMessage = false) {
            if (!message.trim()) return;
            
            // Kullanıcı mesajını ekle
            addMessage(message, 'user');
            messageInput.value = '';
            
            // Kullanıcı mesajını geçmişe ekle
            addToChatHistory(message, 'user');
            
            // Yanıt bekleniyor
            showThinking();
            
            try {
                // CSRF token
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                // Chat ID
                const chatId = localStorage.getItem('current_chat_id') || null;
                
                // Mobil cihazlar artık kullanılmıyor, sadece varsayılan değeri ata
                // Dil seçimi (sadece masaüstü)
                const language = codeLanguage ? codeLanguage.value : 'javascript';
                
                // Konum bilgilerini al
                const locationData = localStorage.getItem('user_location');
                
                // İstek verisi
                const requestData = {
                    message: message.trim(),
                    chat_id: chatId,
                    creative_mode: isCreativeMode,
                    coding_mode: isCodingMode,
                    preferred_language: language,
                    model: selectedModel,
                    is_first_message: isFirstMessage,
                    chat_history: chatHistory, // Sohbet geçmişini API'ye gönder
                    visitor_name: visitorName, // Kullanıcı adını da gönder
                    location: locationData ? JSON.parse(locationData) : null // Konum bilgilerini gönder
                };
                
                // API isteği gönder
                const response = await fetch('/api/ai/process', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify(requestData)
                });
                
                if (response.ok) {
                    const data = await response.json();
                    
                    // Thinking animasyonunu kaldır
                    hideThinking();
                    
                    // İsim kaydedildiyse, input placeholder'ı güncelle ve localStorage'a kaydet
                    if (data.name_saved) {
                        messageInput.placeholder = "Mesajınızı yazın...";
                        messageInput.focus();
                        visitorName = message.trim();
                        localStorage.setItem('visitor_name', visitorName);
                    }
                    
                    // Chat ID'yi kaydet
                    if (data.chat_id) {
                        localStorage.setItem('current_chat_id', data.chat_id);
                    }
                    
                    // Yanıtı işle
                    let finalResponse = data.response;
                    
                    // Kod yanıtı kontrolü
                    if (data.is_code_response) {
                        // Mesajı göster
                        addMessage(finalResponse, 'ai', data.code, data.language);
                        // AI yanıtını geçmişe ekle
                        addToChatHistory(finalResponse, 'ai');
                    } else {
                        // Normal yanıt
                        addMessage(finalResponse, 'ai');
                        // AI yanıtını geçmişe ekle
                        addToChatHistory(finalResponse, 'ai');
                    }
                    
                    // Mesajlar alanına otomatik kaydır
                    scrollToBottom();
                    
                } else {
                    hideThinking();
                    const errorData = await response.json();
                    const errorMessage = errorData.error || "Yanıt alınamadı. Lütfen tekrar deneyin.";
                    addMessage(errorMessage, 'ai');
                }
            } catch (error) {
                console.error('Hata:', error);
                hideThinking();
                addMessage("Üzgünüm, bir hata oluştu. Lütfen tekrar deneyin.", 'ai');
            }
        }
        
        // Mesaj ekle
        function addMessage(message, sender, codeContent = null, codeLanguage = 'javascript') {
            // Mesaj içeriğini kontrol et
            if (!message) {
                message = sender === 'user' ? 
                    'Mesaj gönderilirken bir sorun oluştu.' : 
                    'Yanıt alınamadı. Lütfen tekrar deneyin.';
            }
            
            // Jsonsa parse et
            if (typeof message === 'string' && 
               (message.trim().startsWith('{') && message.trim().endsWith('}'))) {
                try {
                    const jsonObj = JSON.parse(message);
                    if (jsonObj.success && jsonObj.response) {
                        message = jsonObj.response;
                    }
                } catch (e) {}
            }
            
            // Mesaj tür kontrolü
            if (typeof message === 'object') {
                if (message.response) {
                    message = message.response;
                } else if (message.message) {
                    message = message.message;
                } else {
                    try {
                        message = JSON.stringify(message);
                    } catch (e) {
                        message = "Mesaj içeriği gösterilemiyor.";
                    }
                }
            }
            
            // Mesaj container'ı oluştur
            const messageEl = document.createElement('div');
            messageEl.className = `message message-${sender}`;
            
            // Avatar oluştur
            const avatarEl = document.createElement('div');
            avatarEl.className = 'message-avatar';

            // AI mesajları için SoneAI logosu, kullanıcı mesajları için kullanıcı avatarı
            if (sender === 'ai') {
                avatarEl.innerHTML = `<img src="{{ asset('images/sone.png') }}" alt="LizzAI Logo" 
                        style="background-size:cover;
                        background-position: center;
                        background-repeat: no-repeat;
                        border-radius: 50%;
                        width: 28px;
                        height: 28px;
                        !important;
                        ">`;
                
                // AI mesajı geldiğinde bildirim sesini çal
                playNotificationSound();
            } else {
                // Kullanıcı avatarı - Google'dan gelen avatar varsa kullan, yoksa baş harfini göster
                @auth
                const userAvatar = "{{ auth()->user()->avatar }}";
                const sessionAvatar = "{{ session('user_avatar') }}";
                
                if (userAvatar && userAvatar.trim() !== "") {
                    avatarEl.innerHTML = `<img src="${userAvatar}" alt="{{ auth()->user()->name }}" 
                        style="background-size:cover;
                        background-position: center;
                        background-repeat: no-repeat;
                        border-radius: 50%;
                        width: 28px;
                        height: 28px;
                        object-fit: cover;
                        !important;">`;
                } else if (sessionAvatar && sessionAvatar.trim() !== "") {
                    avatarEl.innerHTML = `<img src="${sessionAvatar}" alt="{{ auth()->user()->name }}" 
                        style="background-size:cover;
                        background-position: center;
                        background-repeat: no-repeat;
                        border-radius: 50%;
                        width: 28px;
                        height: 28px;
                        object-fit: cover;
                        !important;">`;
                } else {
                    avatarEl.innerHTML = `<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background-color:#4e73df;color:white;font-weight:bold;">
                        {{ substr(auth()->user()->name, 0, 1) }}</div>`;
                }
                @else
                avatarEl.innerHTML = `<i class="fas fa-user"></i>`;
                @endauth
            }

            messageEl.appendChild(avatarEl);
            
            // Kullanıcı adı veya AI adı ekleyerek görüntüle
            const nameEl = document.createElement('div');
            nameEl.className = 'message-sender-name';

            // Kullanıcı adı kontrolü - çerez değeri kontrolü
            let displayName = '';
            if (sender === 'ai') {
                displayName = 'Lizz';
            } else {
                // Kullanıcı adı için çeşitli kaynakları kontrol et ve cookie değerlerini içerenleri filtrele
                if (visitorName && !visitorName.includes('=') && !visitorName.includes(';')) {
                    displayName = visitorName;
                } else if ('{{ auth()->check() ? auth()->user()->name : "" }}' && !'{{ auth()->check() ? auth()->user()->name : "" }}'.includes('=')) {
                    displayName = '{{ auth()->check() ? auth()->user()->name : "Kullanıcı" }}';
                } else {
                    displayName = 'Kullanıcı';
                }
            }

            nameEl.textContent = displayName;
            messageEl.appendChild(nameEl);
            
            // Mesaj içeriği
            const contentEl = document.createElement('div');
            contentEl.className = 'message-content';
            
            // HTML etiketleri olmadan sadece text içeriği olarak düzenle
            let processedMessage = String(message);
            
            // GIF URL'lerini tanımlamak için regex
            const tenorRegex = /(https:\/\/media\.tenor\.com\/[^\s]+\.gif)/g;
            const giphyRegex = /(https:\/\/media[0-9]?\.giphy\.com\/[^\s]+\.gif)/g;
            
            // Önce Giphy URL'lerini temizle (tamamen kaldır veya mesaj ile değiştir)
            processedMessage = processedMessage.replace(giphyRegex, '');
            
            // Tenor GIF URL'lerini görsel olarak ekle
            if (tenorRegex.test(processedMessage)) {
                // Tenor URL'lerini görsel olarak değiştir
                processedMessage = processedMessage.replace(
                    tenorRegex, 
                    '<img src="$1" alt="GIF" class="tenor-gif" loading="lazy">'
                );
                
                // GIF bağlantılarından sonra fazladan satır sonlarını temizle
                processedMessage = processedMessage.replace(/(<img[^>]+>)\s*\n+\s*/g, '$1');
            }
            
            // Mesaj içeriğini ekle
            contentEl.innerHTML = `<p>${processedMessage}</p>`;
            
            // Kod içeriği varsa ekle
            if (sender === 'ai' && codeContent) {
                const codeBlock = createCodeBlock(codeContent, codeLanguage);
                contentEl.appendChild(codeBlock);
            }
            
            messageEl.appendChild(contentEl);
            messagesContainer.appendChild(messageEl);
            
            // Daktilo efekti (sadece AI mesajları için)
            if (sender === 'ai') {
                typewriterEffect(contentEl.querySelector('p'), processedMessage);
            }
            
            // Scroll to bottom
            scrollToBottom();
        }
        
        // Bildirim sesini çal
        function playNotificationSound() {
            if (!isNotificationMuted) {
                notificationSound.volume = 0.5; // Ses seviyesini %50 yap
                notificationSound.play().catch(error => {
                    console.error('Bildirim sesi çalınamadı:', error);
                });
            }
        }
        
        // Kod bloğu oluştur
        function createCodeBlock(code, language) {
            const codeBlock = document.createElement('div');
            codeBlock.className = 'code-block';
            
            // Kod başlığı
            const codeHeader = document.createElement('div');
            codeHeader.className = 'code-header';
            codeHeader.innerHTML = `
                <span>${language.charAt(0).toUpperCase() + language.slice(1)}</span>
                <span class="language-badge">${language}</span>
            `;
            codeBlock.appendChild(codeHeader);
            
            // Kod içeriği
            const codeContent = document.createElement('div');
            codeContent.className = 'code-content';
            
            const pre = document.createElement('pre');
            const codeEl = document.createElement('code');
            codeEl.className = `language-${language}`;
            
            // Kodu biçimlendir (indentation ve formatting)
            let formattedCode = code;
            try {
                // Basit bir kod biçimlendirme
                formattedCode = formatCode(code, language);
            } catch (e) {
                console.warn('Kod biçimlendirme hatası:', e);
                formattedCode = code;
            }
            
            codeEl.textContent = formattedCode;
            
            pre.appendChild(codeEl);
            codeContent.appendChild(pre);
            codeBlock.appendChild(codeContent);
            
            // Kod alt kısmı
            const codeFooter = document.createElement('div');
            codeFooter.className = 'code-footer';
            
            const copyBtn = document.createElement('button');
            copyBtn.className = 'code-button';
            copyBtn.innerHTML = '<i class="far fa-copy mr-1"></i> Kopyala';
            copyBtn.addEventListener('click', () => {
                navigator.clipboard.writeText(code);
                copyBtn.innerHTML = '<i class="fas fa-check mr-1"></i> Kopyalandı!';
                setTimeout(() => {
                    copyBtn.innerHTML = '<i class="far fa-copy mr-1"></i> Kopyala';
                }, 2000);
            });
            
            codeFooter.appendChild(copyBtn);
            codeBlock.appendChild(codeFooter);
            
            // Highlight.js ile sözdizimi renklendirme
            setTimeout(() => {
                if (codeEl) {
                    hljs.highlightElement(codeEl);
                }
            }, 10);
            
            return codeBlock;
        }
        
        // Kod formatlama yardımcı fonksiyonu
        function formatCode(code, language) {
            // Temel kod biçimlendirme
            if (!code || typeof code !== 'string') return code;
            
            // Satır başındaki ve sonundaki boşlukları temizle
            let formattedCode = code.trim();
            
            // Bazı diller için özel formatlamalar yapılabilir
            switch (language.toLowerCase()) {
                case 'javascript':
                case 'js':
                case 'typescript':
                case 'ts':
                    // JavaScript/TypeScript kodunu düzgün formatlama
                    formattedCode = formatJavaScript(formattedCode);
                    break;
                    
                case 'html':
                case 'xml':
                    // HTML için daha iyi görüntü
                    formattedCode = formattedCode.replace(/></g, '>\n<');
                    break;
                    
                case 'json':
                    // JSON için biçimlendirme
                    try {
                        const jsonObj = JSON.parse(formattedCode);
                        formattedCode = JSON.stringify(jsonObj, null, 2);
                    } catch (e) {
                        // Geçersiz JSON, olduğu gibi bırak
                    }
                    break;
                    
                case 'css':
                case 'scss':
                case 'sass':
                    // CSS formatlaması
                    formattedCode = formatCSS(formattedCode);
                    break;
                    
                case 'php':
                    // PHP formatlaması
                    formattedCode = formatPHP(formattedCode);
                    break;
            }
            
            return formattedCode;
        }
        
        // JavaScript kodunu düzgün formatlama
        function formatJavaScript(code) {
            if (!code) return code;
            
            // Kod bir satırda ise düzenleme yap
            if (!code.includes('\n')) {
                // Noktalı virgülleri satır sonlarına çevir
                code = code.replace(/;/g, ';\n');
                
                // Süslü parantezleri düzenle
                code = code.replace(/{/g, ' {\n').replace(/}/g, '\n}');
                
                // Yorum satırlarını düzenle
                code = code.replace(/\/\//g, '\n//');
                
                // function ve if gibi anahtar kelimeleri düzenle
                code = code.replace(/function\s+/g, '\nfunction ');
                code = code.replace(/if\s*\(/g, '\nif (');
                code = code.replace(/else\s*{/g, '\nelse {');
                
                // Gereksiz boş satırları temizle
                code = code.replace(/\n\s*\n/g, '\n');
            }
            
            // Uzun satırları böl
            let lines = code.split('\n');
            
            for (let i = 0; i < lines.length; i++) {
                const line = lines[i];
                
                // Eğer satır çok uzunsa ve özel işaretler içeriyorsa böl
                if (line.length > 80) {
                    if (line.includes('&&') || line.includes('||')) {
                        lines[i] = line.replace(/\s*(&&|\|\|)\s*/g, '\n    $1 ');
                    } else if (line.includes('.') && !line.startsWith('//')) {
                        lines[i] = line.replace(/\.\s*(?=[a-zA-Z])/g, '.\n    ');
                    }
                }
            }
            
            return lines.join('\n');
        }
        
        // CSS kodu formatla
        function formatCSS(code) {
            if (!code) return code;
            
            // Tek satır CSS'i çoklu satıra dönüştür
            code = code.replace(/\s*{\s*/g, ' {\n    ');
            code = code.replace(/;\s*/g, ';\n    ');
            code = code.replace(/\s*}\s*/g, '\n}\n');
            
            // Fazla satırları temizle
            code = code.replace(/\n\s*\n/g, '\n');
            
            return code;
        }
        
        // PHP kodu formatla
        function formatPHP(code) {
            if (!code) return code;
            
            // Basit PHP formatlaması
            code = code.replace(/\s*{\s*/g, ' {\n    ');
            code = code.replace(/;\s*/g, ';\n');
            code = code.replace(/\s*}\s*/g, '\n}\n');
            
            return code;
        }
        
        // Yazı daktilo efekti
        function typewriterEffect(element, text) {
            if (!element) return;
            
            // Önceki içeriği temizle
            element.innerHTML = '';
            
            // GIF içeriyor mu kontrol et
            if (text.includes('<img src="https://media.tenor.com/')) {
                // GIF içeriyorsa daktilo efekti uygulamadan direkt göster
                element.innerHTML = text;
                return;
            }
            
            // HTML içeriğini işle
            const htmlContent = text;
            
            let i = 0;
            const speed = 20; // Hız (ms)
            
            // Karakterleri tek tek ekle
            function typeNextChar() {
                if (i < htmlContent.length) {
                    // Şimdiki metni al
                    let currentText = htmlContent.substring(0, i + 1);
                    
                    element.innerHTML = currentText;
                    i++;
                    
                    setTimeout(typeNextChar, speed);
                    scrollToBottom();
                }
            }
            
            typeNextChar();
        }
        
        // Aşağı kaydır
        function scrollToBottom() {
            setTimeout(() => {
                if (chatMessagesContainer) {
                    chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
                }
            }, 10);
        }
        
        // Düşünme animasyonu göster
        function showThinking() {
            if (aiThinking) {
                // AI thinking animasyonunu göster
                aiThinking.style.display = 'flex';
                
                // Mesaj alanının en sonuna ekle (yeni mesajlar thinking'in altında görünecek)
                if (messagesContainer && messagesContainer.contains(aiThinking)) {
                    messagesContainer.appendChild(aiThinking);
                }
                
                // Mobil görünümde ekstra stil
                if (window.innerWidth <= 767) {
                    aiThinking.style.marginTop = '15px';
                    aiThinking.style.marginBottom = '15px';
                    aiThinking.style.clear = 'both';
                }
                
                // Aşağı kaydır
                scrollToBottom();
            }
        }
        
        // Düşünme animasyonu gizle
        function hideThinking() {
            if (aiThinking) {
                aiThinking.style.display = 'none';
            }
        }
        
        // Mesaj gönderme event listener'ı
        if (sendMessageBtn) {
            sendMessageBtn.addEventListener('click', function() {
                const message = messageInput.value.trim();
                if (message) {
                    // İlk mesaj kontrolü (isim sorgusu için)
                    const isFirstMessage = needsName && nameRequested && !localStorage.getItem('current_chat_id');
                    sendMessage(message, isFirstMessage);
                    messageInput.blur(); // Mobil klavyeyi kapat
                }
            });
        }
        
        // Enter tuşu ile göndermeyi etkinleştir
        if (messageInput) {
            messageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    const message = messageInput.value.trim();
                    if (message) {
                        // İlk mesaj kontrolü (isim sorgusu için)
                        const isFirstMessage = needsName && nameRequested && !localStorage.getItem('current_chat_id');
                        sendMessage(message, isFirstMessage);
                        if (window.innerWidth <= 767) {
                            messageInput.blur(); // Mobil klavyeyi kapat
                        }
                    }
                }
            });
        }
        
        // Masaüstü ayarları
        if (creativeToggle) {
            creativeToggle.addEventListener('change', function() {
                syncSettings('creative', this.checked);
            });
        }
        
        if (codingToggle) {
            codingToggle.addEventListener('change', function() {
                syncSettings('coding', this.checked);
            });
        }
        
        if (modelSelector) {
            modelSelector.addEventListener('change', function() {
                syncSettings('model', this.value);
                showModelNotification();
            });
        }
        
        if (codeLanguage) {
            codeLanguage.addEventListener('change', function() {
                syncSettings('language', this.value);
            });
        }
        
        function showModelNotification() {
            // Bildirim göster
            const notification = document.createElement('div');
            notification.className = 'message';
            notification.style.textAlign = 'center';
            notification.style.maxWidth = '100%';
            notification.style.margin = '1rem 0';
            notification.innerHTML = `
                <div style="display: inline-block; background-color:rgb(61, 63, 65); padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.9rem;">
                    <i class="fas fa-info-circle mr-2"></i>
                    ${selectedModel === 'soneai' ? 'LizzAI Basic' : 'LizzAI Turbo'} modeli aktif
                </div>
            `;
            
            messagesContainer.appendChild(notification);
            scrollToBottom();
            
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transition = 'opacity 0.5s';
                
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 500);
            }, 3000);
        }
        
        // Viewport yüksekliğini ayarla
        setVhVariable();
        window.addEventListener('resize', setVhVariable);
        
        // Yönlendirme değişikliğinde de yüksekliği güncelle
        window.addEventListener('orientationchange', function() {
            setTimeout(setVhVariable, 200);
        });
        
        // Varsayılan ayarları yükle
        loadSettings();

        // Safari metin rengini düzeltme fonksiyonu
        function fixSafariColors() {
            const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
            
            if (isSafari || isIOS) {
                document.documentElement.classList.add('safari-browser');
                
                // Mevcut mesajları düzelt
                const fixMessages = function() {
                    const userMessages = document.querySelectorAll('.message-user .message-content');
                    userMessages.forEach(msg => {
                        msg.style.setProperty('color', 'white', 'important');
                        const paragraphs = msg.querySelectorAll('p');
                        paragraphs.forEach(p => p.style.setProperty('color', 'white', 'important'));
                    });
                };
                
                // İlk yükleme için düzelt
                fixMessages();
                
                // Mesaj eklendiğinde düzelt
                const observer = new MutationObserver(mutations => {
                    mutations.forEach(mutation => {
                        if (mutation.type === 'childList' && mutation.addedNodes.length) {
                            fixMessages();
                        }
                    });
                });
                
                observer.observe(messagesContainer, { childList: true, subtree: true });
            }
        }
        
        // Safari renk düzeltmesini uygula
        fixSafariColors();
        
        // Mesaj ekleme fonksiyonunda da renklerin düzgün görünmesini sağla
        const originalAddMessage = addMessage;
        addMessage = function(message, sender, codeContent = null, codeLanguage = 'javascript') {
            originalAddMessage(message, sender, codeContent, codeLanguage);
            
            // Safari için ek düzeltme
            if (/^((?!chrome|android).)*safari/i.test(navigator.userAgent) || /iPad|iPhone|iPod/.test(navigator.userAgent)) {
                if (sender === 'user') {
                    setTimeout(() => {
                        const lastMessage = messagesContainer.lastElementChild;
                        if (lastMessage && lastMessage.classList.contains('message-user')) {
                            const content = lastMessage.querySelector('.message-content');
                            if (content) {
                                content.style.setProperty('color', 'white', 'important');
                                const paragraphs = content.querySelectorAll('p');
                                paragraphs.forEach(p => p.style.setProperty('color', 'white', 'important'));
                            }
                        }
                    }, 10);
                }
            }
        };

        // Mesaj geçmişine yeni bir mesajı ekle
        function addToChatHistory(message, sender) {
            // Geçmişi maksimum 10 mesajla sınırla
            if (chatHistory.length >= 20) {
                chatHistory.shift(); // En eski mesajı çıkar
            }
            
            // Yeni mesajı ekle
            chatHistory.push({
                sender: sender,
                content: message,
                timestamp: new Date().toISOString()
            });
            
            // Geçmişi local storage'a kaydet
            localStorage.setItem('chat_history', JSON.stringify(chatHistory));
        }

        // Mesaj geçmişini temizle
        function clearChatHistory() {
            chatHistory = [];
            localStorage.removeItem('chat_history');
        }

        // Ayarları senkronize tutma
        function syncSettings(key, value) {
            if (key === 'creative') {
                isCreativeMode = value;
                localStorage.setItem('creative_mode', value);
                if (creativeToggle) creativeToggle.checked = value;
            } 
            else if (key === 'coding') {
                isCodingMode = value;
                localStorage.setItem('coding_mode', value);
                if (codingToggle) codingToggle.checked = value;
                
                // Dil seçim alanlarını göster/gizle
                if (languageSettings) {
                    languageSettings.style.display = value ? 'block' : 'none';
                }
            }
            else if (key === 'model') {
                selectedModel = value;
                localStorage.setItem('selected_model', value);
                if (modelSelector) modelSelector.value = value;
                updateModelDisplay();
            }
            else if (key === 'language') {
                if (codeLanguage) codeLanguage.value = value;
            }
        }
        
        // Yeni chat başlat
        function startNewChat() {
            localStorage.removeItem('current_chat_id');
            clearChatHistory();
            messagesContainer.innerHTML = '';
            addMessage("Merhaba! Ben Lizz. Size nasıl yardımcı olabilirim?", 'ai');
        }

        // Yeni chat butonu masaüstü
        document.getElementById('new-chat-btn').addEventListener('click', function() {
            startNewChat();
        });
        
        // Tam ekran modu değişkenini tanımla
        let isFullScreen = false;
        
        // Tam ekran butonuna tıklama olayı ekle
        if (fullscreenToggle) {
            console.log('Tam ekran butonu bulundu, event listener ekleniyor');
            try {
                fullscreenToggle.addEventListener('click', function(e) {
                    console.log('Tam ekran butonuna tıklandı (event listener)');
                    e.preventDefault();
                    toggleFullScreen();
                });
                
                // Alternatif olarak onclick özelliğini de ekleyelim
                fullscreenToggle.onclick = function() {
                    console.log('Tam ekran butonuna tıklandı (onclick)');
                    toggleFullScreen();
                    return false;
                };
                
                console.log('Event listener başarıyla eklendi');
            } catch (error) {
                console.error('Event listener eklenirken hata oluştu:', error);
            }
        } else {
            console.error('Tam ekran butonu bulunamadı!');
        }
        
        // Tam ekran modunu açıp kapatan fonksiyon
        function toggleFullScreen() {
            console.log('toggleFullScreen çağrıldı, mevcut durum:', isFullScreen);
            try {
                if (!isFullScreen) {
                    // Tam ekran moduna geç
                    console.log('Tam ekran moduna geçiliyor...');
                    if (document.documentElement.requestFullscreen) {
                        document.documentElement.requestFullscreen();
                    } else if (document.documentElement.mozRequestFullScreen) { // Firefox
                        document.documentElement.mozRequestFullScreen();
                    } else if (document.documentElement.webkitRequestFullscreen) { // Chrome, Safari, Opera
                        document.documentElement.webkitRequestFullscreen();
                    } else if (document.documentElement.msRequestFullscreen) { // IE/Edge
                        document.documentElement.msRequestFullscreen();
                    }
                    console.log('Tam ekran modu etkinleştirildi');
                    
                    // İkon değiştir
                    fullscreenToggle.querySelector('i').classList.remove('fa-expand');
                    fullscreenToggle.querySelector('i').classList.add('fa-compress');
                    isFullScreen = true;
                } else {
                    // Tam ekran modundan çık
                    console.log('Tam ekran modundan çıkılıyor...');
                    if (document.exitFullscreen) {
                        document.exitFullscreen();
                    } else if (document.mozCancelFullScreen) { // Firefox
                        document.mozCancelFullScreen();
                    } else if (document.webkitExitFullscreen) { // Chrome, Safari, Opera
                        document.webkitExitFullscreen();
                    } else if (document.msExitFullscreen) { // IE/Edge
                        document.msExitFullscreen();
                    }
                    console.log('Tam ekran modundan çıkıldı');
                    
                    // İkon değiştir
                    fullscreenToggle.querySelector('i').classList.remove('fa-compress');
                    fullscreenToggle.querySelector('i').classList.add('fa-expand');
                    isFullScreen = false;
                }
            } catch (error) {
                console.error('Tam ekran işlemi sırasında hata oluştu:', error);
            }
        }
        
        // Tam ekran durumu değiştiğinde çalışacak olay dinleyicisi
        document.addEventListener('fullscreenchange', updateFullscreenButtonIcon);
        document.addEventListener('webkitfullscreenchange', updateFullscreenButtonIcon);
        document.addEventListener('mozfullscreenchange', updateFullscreenButtonIcon);
        document.addEventListener('MSFullscreenChange', updateFullscreenButtonIcon);
        
        // Tam ekran durumuna göre buton ikonunu güncelle
        function updateFullscreenButtonIcon() {
            if (document.fullscreenElement || 
                document.webkitFullscreenElement || 
                document.mozFullScreenElement ||
                document.msFullscreenElement) {
                // Tam ekran modunda
                fullscreenToggle.querySelector('i').classList.remove('fa-expand');
                fullscreenToggle.querySelector('i').classList.add('fa-compress');
                isFullScreen = true;
            } else {
                // Normal modda
                fullscreenToggle.querySelector('i').classList.remove('fa-compress');
                fullscreenToggle.querySelector('i').classList.add('fa-expand');
                isFullScreen = false;
            }
        }

        // Sesli sohbet elemanları
        const voicePopup = document.getElementById('voice-popup');
        const voiceOverlay = document.getElementById('voice-overlay');
        const voiceInputBtn = document.getElementById('voice-input-btn');
        const closeVoice = document.getElementById('close-voice');
        const voiceMicBtn = document.getElementById('voice-mic-btn');
        const voiceVisualizer = document.getElementById('voice-visualizer');
        const voiceStatus = document.getElementById('voice-status');
        
        // Ses kayıt değişkenleri
        let mediaRecorder;
        let audioChunks = [];
        let isRecording = false;
        let stream;
        
        // Popup'ı görünür/gizli yap
        function toggleVoicePopup() {
            voicePopup.classList.toggle('active');
            
            // Overlay'i göster/gizle
            voiceOverlay.classList.toggle('active');
            
            // Popup aktifse
            if (voicePopup.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
                voiceStatus.textContent = 'Mikrofona tıklayarak konuşmaya başlayabilirsiniz...';
                
                // Popup'ın başlığını değiştir
                document.querySelector('.voice-title').textContent = 'Sesli Sohbet Modu';
                
                // Görsel arayüzü sesli sohbet moduna uyarla
                voicePopup.classList.add('voice-chat-mode');
                
                // Sürekli konuşma modu düğmesini göster
                if (voiceContinuousBtn) {
                    voiceContinuousBtn.style.display = 'block';
                }
                
                // Sohbet geçmişi alanını temizle ve görünür yap
                if (voiceConversation) {
                    voiceConversation.style.display = 'block';
                    voiceMessage.innerHTML = '<div class="voice-chat-welcome">Mikrofon düğmesine tıklayarak konuşmaya başlayabilirsiniz</div>';
                }
                
                // Mikrofona eriş
                requestMicrophoneAccess();
            } else {
                document.body.style.overflow = 'auto';
                
                // Kayıtta ise durdur
                if (isRecording) {
                    stopRecording();
                }
                
                // Sesli yanıtı durdur
                stopAllAudio();
                
                // Popup'ın görsel ayarlarını sıfırla
                voicePopup.classList.remove('voice-chat-mode');
                
                // Sürekli konuşma modu düğmesini gizle
                if (voiceContinuousBtn) {
                    voiceContinuousBtn.style.display = 'none';
                }
                
                // Mikrofonu kapat - Pop-up kapalıyken mikrofon erişimini tamamen kapat
                if (stream) {
                    const tracks = stream.getTracks();
                    tracks.forEach(track => track.stop());
                    stream = null;
                }
                
                // Kayıt değişkenlerini sıfırla
                isRecording = false;
                audioChunks = [];
                mediaRecorder = null;
            }
        }
        
        // Mikrofon erişimi iste
        async function requestMicrophoneAccess() {
            try {
                // Daha kapsamlı ses ayarları ile istek yap
                stream = await navigator.mediaDevices.getUserMedia({
                    audio: {
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true,
                        sampleRate: 48000
                    }
                });
                voiceStatus.textContent = 'Mikrofona tıklayarak konuşmaya başlayabilirsiniz.';
            } catch (err) {
                console.error('Mikrofon erişim hatası:', err);
                voiceStatus.textContent = 'Mikrofon erişimine izin verilmedi. Lütfen tarayıcı izinlerini kontrol edin.';
            }
        }
        
        // Kayıt başlat
        function startRecording() {
            if (!stream) {
                requestMicrophoneAccess().then(() => {
                    if (stream) startRecording();
                });
                return;
            }
            
            try {
                audioChunks = [];
                
                // Tarayıcı uyumluluğu için desteklenen MIME tiplerini kontrol et
                let mimeType = 'audio/webm';
                if (MediaRecorder.isTypeSupported('audio/webm;codecs=opus')) {
                    mimeType = 'audio/webm;codecs=opus';
                } else if (MediaRecorder.isTypeSupported('audio/ogg;codecs=opus')) {
                    mimeType = 'audio/ogg;codecs=opus';
                } else if (MediaRecorder.isTypeSupported('audio/mp4;codecs=mp4a')) {
                    mimeType = 'audio/mp4;codecs=mp4a';
                }
                
                console.log('Kullanılan MIME tipi:', mimeType);
                
                // MediaRecorder ile kayıt başlat
                mediaRecorder = new MediaRecorder(stream, {
                    mimeType: mimeType,
                    audioBitsPerSecond: 128000
                });
                
                mediaRecorder.addEventListener('dataavailable', event => {
                    if (event.data.size > 0) {
                        audioChunks.push(event.data);
                    }
                });
                
                mediaRecorder.addEventListener('stop', processRecording);
                mediaRecorder.addEventListener('error', (event) => {
                    console.error('MediaRecorder hatası:', event.error);
                    voiceStatus.textContent = 'Ses kaydı sırasında bir hata oluştu: ' + event.error.message;
                    stopRecording();
                });
                
                // Kayıt başlat (100ms zamanlayıcı ile veri topla)
                mediaRecorder.start(100);
                isRecording = true;
                
                // UI güncelle
                voiceMicBtn.classList.add('recording');
                voiceMicBtn.innerHTML = '<i class="fas fa-stop"></i>';
                voiceVisualizer.classList.add('recording');
                voiceStatus.textContent = 'Konuşuyorsunuz... Tamamlandığında durdurmak için tıklayın.';
                voiceInputBtn.classList.add('recording');
                
                // 30 saniye sonra otomatik olarak durdur
                setTimeout(() => {
                    if (isRecording) {
                        stopRecording();
                        voiceStatus.textContent = 'Maksimum kayıt süresi aşıldı (30 saniye).';
                    }
                }, 30000);
                
            } catch (err) {
                console.error('Kayıt başlatma hatası:', err);
                voiceStatus.textContent = 'Kayıt başlatılamadı: ' + err.message;
            }
        }
        
        // Kayıt durdur
        function stopRecording() {
            try {
                if (mediaRecorder && isRecording) {
                    mediaRecorder.stop();
                    isRecording = false;
                    
                    // UI güncelle
                    voiceMicBtn.classList.remove('recording');
                    voiceMicBtn.innerHTML = '<i class="fas fa-microphone"></i>';
                    voiceVisualizer.classList.remove('recording');
                    voiceStatus.textContent = 'Ses işleniyor...';
                    voiceInputBtn.classList.remove('recording');
                }
            } catch (err) {
                console.error('Kayıt durdurma hatası:', err);
                voiceStatus.textContent = 'Kayıt durdurulurken bir hata oluştu: ' + err.message;
                
                // İşlemi temizle
                isRecording = false;
                voiceMicBtn.classList.remove('recording');
                voiceMicBtn.innerHTML = '<i class="fas fa-microphone"></i>';
                voiceVisualizer.classList.remove('recording');
                voiceInputBtn.classList.remove('recording');
            }
        }
        
        // Ses kaydını işle
        async function processRecording() {
            if (audioChunks.length === 0) {
                voiceStatus.textContent = 'Ses kaydedilemedi. Lütfen tekrar deneyin.';
                return;
            }
            
            try {
                // Ses verilerini bir Blob nesnesi olarak birleştir (MIME türünü otomatik algıla)
                const mimeType = mediaRecorder.mimeType || 'audio/webm';
                const audioBlob = new Blob(audioChunks, { type: mimeType });
                
                console.log('Ses Blob oluşturuldu:', {
                    size: audioBlob.size,
                    type: audioBlob.type,
                    chunks: audioChunks.length
                });
                
                // Ses blobu çok küçükse uyarı ver
                if (audioBlob.size < 1000) {
                    voiceStatus.textContent = 'Ses kaydı çok kısa veya boş. Lütfen tekrar deneyin.';
                    return;
                }
                
                // Base64'e dönüştür
                const reader = new FileReader();
                reader.readAsDataURL(audioBlob);
                
                reader.onloadend = async function() {
                    const base64Audio = reader.result;
                    
                    // Speech-to-Text API'sine gönder
                    try {
                        voiceStatus.textContent = 'Sesli mesaj işleniyor...';
                        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        
                        const response = await fetch('/api/speech/to-text', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify({ audio: base64Audio })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success && data.text) {
                            // Metni input alanına ekle
                            messageInput.value = data.text;
                            
                            // Popup'ı kapat
                            toggleVoicePopup();
                            
                            // Metinde bir şey var mı kontrol et
                            if (data.text.trim().length > 0) {
                                // AI'dan yanıt al ve yanıtı sesli okut
                                sendMessage(data.text);
                                
                                // Focus input
                                messageInput.focus();
                            } else {
                                // Boş metin
                                voiceStatus.textContent = 'Tanınan metin boş. Lütfen tekrar deneyin.';
                            }
                        } else {
                            // API yanıt hatası
                            voiceStatus.textContent = 'Ses tanıma başarısız oldu: ' + (data.error || 'Bilinmeyen hata');
                            
                            // Detaylı hata bilgisini konsola yazdır
                            console.error('Speech-to-Text API hatası:', data);
                            
                            // Ses verisini yedek olarak sunucuya kaydet
                            saveAudioForAnalysis(base64Audio);
                        }
                    } catch (error) {
                        console.error('Speech-to-Text API isteği hatası:', error);
                        voiceStatus.textContent = 'Ses tanıma sırasında bir hata oluştu. Lütfen tekrar deneyin.';
                        
                        // Ses verisini yedek olarak sunucuya kaydet
                        saveAudioForAnalysis(base64Audio);
                    }
                };
                
                reader.onerror = function(error) {
                    console.error('Base64 dönüşüm hatası:', error);
                    voiceStatus.textContent = 'Ses verisi işlenemedi. Lütfen tekrar deneyin.';
                };
            } catch (error) {
                console.error('Ses işleme hatası:', error);
                voiceStatus.textContent = 'Ses işlenirken bir hata oluştu: ' + error.message;
            }
        }
        
        // Sorun teşhisi için ses verisini kaydet
        async function saveAudioForAnalysis(base64Audio) {
            try {
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                await fetch('/api/speech/save-audio', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({ audio: base64Audio })
                });
                
                console.log('Ses verisi analiz için kaydedildi');
            } catch (error) {
                console.error('Ses kaydetme hatası:', error);
            }
        }
        
        // AI yanıtını seslendir
        async function speakAIResponse(text) {
            try {
                if (!text || text.trim().length === 0) {
                    console.error('Seslendirilecek metin boş');
                    return;
                }
                
                // Tenor GIF URL'lerini temizle
                text = text.replace(/https:\/\/media\.tenor\.com\/[^\s]+\.gif/g, '');
                
                // Eğer sesli sohbet modu aktif değilse normal şekilde devam et
                if (!voicePopup.classList.contains('active')) {
                    voiceStatus.textContent = 'AI yanıtı seslendiriliyor...';
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    
                    const response = await fetch('/api/speech/to-speech', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify({ text: text })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success && data.audioContent) {
                        // Base64 ses içeriğini çal
                        try {
                            const audio = new Audio(`data:audio/mp3;base64,${data.audioContent}`);
                            
                            // Ses çalma olaylarını izle
                            audio.addEventListener('play', () => {
                                console.log('Ses çalmaya başladı');
                            });
                            
                            audio.addEventListener('ended', () => {
                                console.log('Ses çalma tamamlandı');
                            });
                            
                            audio.addEventListener('error', (e) => {
                                console.error('Ses çalma hatası:', e);
                            });
                            
                            await audio.play();
                        } catch (playError) {
                            console.error('Ses çalma hatası:', playError);
                        }
                    } else {
                        console.error('Text-to-Speech API hatası:', data);
                    }
                }
            } catch (error) {
                console.error('Text-to-Speech hatası:', error);
            }
        }
        
        // Event listener'ları ekle
        if (voiceInputBtn) {
            voiceInputBtn.addEventListener('click', toggleVoicePopup);
        }
        
        if (closeVoice) {
            closeVoice.addEventListener('click', toggleVoicePopup);
        }
        
        if (voiceOverlay) {
            voiceOverlay.addEventListener('click', toggleVoicePopup);
        }
        
        if (voiceMicBtn) {
            voiceMicBtn.addEventListener('click', function() {
                if (isRecording) {
                    stopRecording();
                } else {
                    startRecording();
                }
            });
        }
        
        // Orijinal sendMessage fonksiyonunu genişlet
        const originalSendMessage = sendMessage;
        sendMessage = async function(message, isFirstMessage = false) {
            if (!message.trim()) return;
            
            // Eğer sesli sohbet modu aktifse, varsayılan davranışı engelle
            if (voicePopup.classList.contains('active') && voicePopup.classList.contains('voice-chat-mode')) {
      
                return;
            }
            
            // Normal sohbet akışı
            // Kullanıcı mesajını ekle
            addMessage(message, 'user');
            messageInput.value = '';
            
            // Kullanıcı mesajını geçmişe ekle
            addToChatHistory(message, 'user');
            
            // Yanıt bekleniyor
            showThinking();
            
            try {
                // CSRF token
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                // Chat ID
                const chatId = localStorage.getItem('current_chat_id') || null;
                
                // Mobil cihazlar artık kullanılmıyor, sadece varsayılan değeri ata
                // Dil seçimi (sadece masaüstü)
                const language = codeLanguage ? codeLanguage.value : 'javascript';
                
                // Konum bilgilerini al
                const locationData = localStorage.getItem('user_location');
                
                // İstek verisi
                const requestData = {
                    message: message.trim(),
                    chat_id: chatId,
                    creative_mode: isCreativeMode,
                    coding_mode: isCodingMode,
                    preferred_language: language,
                    model: selectedModel,
                    is_first_message: isFirstMessage,
                    chat_history: chatHistory, // Sohbet geçmişini API'ye gönder
                    visitor_name: visitorName, // Kullanıcı adını da gönder
                    location: locationData ? JSON.parse(locationData) : null // Konum bilgilerini gönder
                };
                
                // API isteği gönder
                const response = await fetch('/api/ai/process', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify(requestData)
                });
                
                if (response.ok) {
                    const data = await response.json();
                    
                    // Thinking animasyonunu kaldır
                    hideThinking();
                    
                    // İsim kaydedildiyse, input placeholder'ı güncelle ve localStorage'a kaydet
                    if (data.name_saved) {
                        messageInput.placeholder = "Mesajınızı yazın...";
                        messageInput.focus();
                        visitorName = message.trim();
                        localStorage.setItem('visitor_name', visitorName);
                    }
                    
                    // Chat ID'yi kaydet
                    if (data.chat_id) {
                        localStorage.setItem('current_chat_id', data.chat_id);
                    }
                    
                    // Yanıtı işle
                    let finalResponse = data.response;
                    
                    // Kod yanıtı kontrolü
                    if (data.is_code_response) {
                        // Mesajı göster
                        addMessage(finalResponse, 'ai', data.code, data.language);
                        // AI yanıtını geçmişe ekle
                        addToChatHistory(finalResponse, 'ai');
                    } else {
                        // Normal yanıt
                        addMessage(finalResponse, 'ai');
                        // AI yanıtını geçmişe ekle
                        addToChatHistory(finalResponse, 'ai');
                    }
                    
                    // Mesajlar alanına otomatik kaydır
                    scrollToBottom();
                    
                } else {
                    hideThinking();
                    const errorData = await response.json();
                    const errorMessage = errorData.error || "Yanıt alınamadı. Lütfen tekrar deneyin.";
                    addMessage(errorMessage, 'ai');
                }
            } catch (error) {
                console.error('Hata:', error);
                hideThinking();
                addMessage("Üzgünüm, bir hata oluştu. Lütfen tekrar deneyin.", 'ai');
            }
        };
        
        // Klavye kısayolu: 'M' tuşu
        document.addEventListener('keydown', function(e) {
            // Alt+M veya Ctrl+M tuşuna basıldığında sesli asistanı aç/kapat
            if ((e.altKey || e.ctrlKey) && e.key === 'm') {
                e.preventDefault();
                toggleVoicePopup();
            }
            
            // ESC tuşuna basıldığında sesli asistanı kapat
            if (e.key === 'Escape' && voicePopup.classList.contains('active')) {
                toggleVoicePopup();
            }
        });

        // Sesli sohbet ekran elemanları
        const voiceConversation = document.getElementById('voice-conversation');
        const voiceMessage = document.getElementById('voice-message');
        const voiceContinuousBtn = document.getElementById('voice-continuous-btn');
        
        // Sesli sohbet ayarları
        let isContinuousMode = true; // Sürekli konuşma modu varsayılan olarak açık
        let isAISpeaking = false; // AI'ın konuşma durumu
        let conversationTimeout; // Konuşma aralığı için zamanlayıcı
        
        // AI yanıtını seslendirmeyi bekleyen fonksiyon
        let waitingForAIResponse = false;
        
        // Sürekli konuşma modu düğmesini ayarla
        if (voiceContinuousBtn) {
            voiceContinuousBtn.addEventListener('click', function() {
                isContinuousMode = !isContinuousMode;
                this.classList.toggle('active', isContinuousMode);
                
                if (isContinuousMode) {
                    voiceStatus.textContent = 'Sürekli konuşma modu açık. Mikrofona tıklayarak başlayın.';
                } else {
                    voiceStatus.textContent = 'Mikrofona tıklayarak tek seferlik konuşabilirsiniz.';
                }
            });
        }
        
        // Mikrofon erişimi iste
        async function requestMicrophoneAccess() {
            try {
                // Daha kapsamlı ses ayarları ile istek yap
                stream = await navigator.mediaDevices.getUserMedia({
                    audio: {
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true,
                        sampleRate: 48000
                    }
                });
                voiceStatus.textContent = 'Mikrofona tıklayarak konuşmaya başlayabilirsiniz.';
            } catch (err) {
                console.error('Mikrofon erişim hatası:', err);
                voiceStatus.textContent = 'Mikrofon erişimine izin verilmedi. Lütfen tarayıcı izinlerini kontrol edin.';
            }
        }
        
        // Kayıt başlat
        function startRecording() {
            if (!stream) {
                requestMicrophoneAccess().then(() => {
                    if (stream) startRecording();
                });
                return;
            }
            
            try {
                // AI konuşuyorsa durdur
                if (isAISpeaking) {
                    stopAllAudio();
                }
                
                audioChunks = [];
                
                // Tarayıcı uyumluluğu için desteklenen MIME tiplerini kontrol et
                let mimeType = 'audio/webm';
                if (MediaRecorder.isTypeSupported('audio/webm;codecs=opus')) {
                    mimeType = 'audio/webm;codecs=opus';
                } else if (MediaRecorder.isTypeSupported('audio/ogg;codecs=opus')) {
                    mimeType = 'audio/ogg;codecs=opus';
                } else if (MediaRecorder.isTypeSupported('audio/mp4;codecs=mp4a')) {
                    mimeType = 'audio/mp4;codecs=mp4a';
                }
                
                console.log('Kullanılan MIME tipi:', mimeType);
                
                // MediaRecorder ile kayıt başlat
                mediaRecorder = new MediaRecorder(stream, {
                    mimeType: mimeType,
                    audioBitsPerSecond: 128000
                });
                
                mediaRecorder.addEventListener('dataavailable', event => {
                    if (event.data.size > 0) {
                        audioChunks.push(event.data);
                    }
                });
                
                mediaRecorder.addEventListener('stop', processVoiceChatRecording);
                mediaRecorder.addEventListener('error', (event) => {
                    console.error('MediaRecorder hatası:', event.error);
                    voiceStatus.textContent = 'Ses kaydı sırasında bir hata oluştu: ' + event.error.message;
                    stopRecording();
                });
                
                // Kayıt başlat (100ms zamanlayıcı ile veri topla)
                mediaRecorder.start(100);
                isRecording = true;
                
                // UI güncelle
                voiceMicBtn.classList.add('recording');
                voiceMicBtn.innerHTML = '<i class="fas fa-stop"></i>';
                voiceVisualizer.classList.add('recording');
                voiceStatus.textContent = 'Konuşuyorsunuz... Tamamlandığında durdurmak için tıklayın.';
                voiceInputBtn.classList.add('recording');
                
                // Uzun sessizlikte otomatik durdurma (7 saniye)
                conversationTimeout = setTimeout(() => {
                    if (isRecording) {
                        stopRecording();
                        voiceStatus.textContent = 'Sessiz kaldınız. Yanıtlanıyor...';
                    }
                }, 7000);
                
                // 30 saniye sonra otomatik olarak durdur
                setTimeout(() => {
                    if (isRecording) {
                        stopRecording();
                        voiceStatus.textContent = 'Maksimum kayıt süresi aşıldı (30 saniye).';
                    }
                }, 30000);
                
            } catch (err) {
                console.error('Kayıt başlatma hatası:', err);
                voiceStatus.textContent = 'Kayıt başlatılamadı: ' + err.message;
            }
        }
        
        // Tüm ses çalmalarını durdur
        function stopAllAudio() {
            // AI sesini durdur ve konuşma durumunu sıfırla
            isAISpeaking = false;
            
            // Mevcut tüm audio elementlerini durdur
            document.querySelectorAll('audio').forEach(audio => {
                audio.pause();
                audio.currentTime = 0;
            });
            
            // Görsel efektleri kaldır
            voiceVisualizer.classList.remove('ai-speaking');
        }
        
        // Ses kaydını işle (Sesli sohbet için)
        async function processVoiceChatRecording() {
            // Sessizlik zamanlayıcısını iptal et
            clearTimeout(conversationTimeout);
            
            if (audioChunks.length === 0) {
                voiceStatus.textContent = 'Ses kaydedilemedi. Lütfen tekrar deneyin.';
                return;
            }
            
            try {
                // Ses verilerini bir Blob nesnesi olarak birleştir (MIME türünü otomatik algıla)
                const mimeType = mediaRecorder.mimeType || 'audio/webm';
                const audioBlob = new Blob(audioChunks, { type: mimeType });
                
                console.log('Ses Blob oluşturuldu:', {
                    size: audioBlob.size,
                    type: audioBlob.type,
                    chunks: audioChunks.length
                });
                
                // Ses blobu çok küçükse uyarı ver
                if (audioBlob.size < 1000) {
                    voiceStatus.textContent = 'Ses kaydı çok kısa veya boş. Lütfen tekrar deneyin.';
                    return;
                }
                
                // Base64'e dönüştür
                const reader = new FileReader();
                reader.readAsDataURL(audioBlob);
                
                reader.onloadend = async function() {
                    const base64Audio = reader.result;
                    
                    // Speech-to-Text API'sine gönder
                    try {
                        voiceStatus.textContent = 'Mesajınız işleniyor...';
                        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        
                        const response = await fetch('/api/speech/to-text', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify({ audio: base64Audio })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success && data.text) {
                            // Metinde bir şey var mı kontrol et
                            if (data.text.trim().length > 0) {
                                // Kullanıcının konuşmasını popup mesaj alanında göster (opsiyonel)
                                voiceConversation.style.display = 'block';
                                voiceMessage.innerHTML = '<strong>Siz:</strong> ' + data.text + '<br>';
                                
                                // AI yanıt vermeden önce bekleme durumuna geç
                                waitingForAIResponse = true;
                                voiceStatus.textContent = 'Lizz yanıtlıyor...';
                                
                                // AI'dan yanıt al ve seslendirerek yanıtla
                                getVoiceChatResponse(data.text);
                            } else {
                                // Boş metin
                                voiceStatus.textContent = 'Konuşmanız anlaşılamadı. Lütfen tekrar deneyin.';
                                
                                // Sürekli konuşma modunda yeni kayda otomatik başla
                                if (isContinuousMode) {
                                    setTimeout(() => {
                                        if (!isRecording && !isAISpeaking) {
                                            startRecording();
                                        }
                                    }, 1000);
                                }
                            }
                        } else {
                            // API yanıt hatası
                            voiceStatus.textContent = 'Ses tanıma başarısız oldu: ' + (data.error || 'Bilinmeyen hata');
                            
                            // Detaylı hata bilgisini konsola yazdır
                            console.error('Speech-to-Text API hatası:', data);
                            
                            // Ses verisini yedek olarak sunucuya kaydet
                            saveAudioForAnalysis(base64Audio);
                            
                            // Sürekli konuşma modunda yeni kayda otomatik başla
                            if (isContinuousMode) {
                                setTimeout(() => {
                                    if (!isRecording && !isAISpeaking) {
                                        startRecording();
                                    }
                                }, 2000);
                            }
                        }
                    } catch (error) {
                        console.error('Speech-to-Text API isteği hatası:', error);
                        voiceStatus.textContent = 'Ses tanıma sırasında bir hata oluştu. Lütfen tekrar deneyin.';
                        
                        // Ses verisini yedek olarak sunucuya kaydet
                        saveAudioForAnalysis(base64Audio);
                        
                        // Sürekli konuşma modunda yeni kayda otomatik başla
                        if (isContinuousMode) {
                            setTimeout(() => {
                                if (!isRecording && !isAISpeaking) {
                                    startRecording();
                                }
                            }, 2000);
                        }
                    }
                };
            } catch (error) {
                console.error('Ses işleme hatası:', error);
                voiceStatus.textContent = 'Ses işlenirken bir hata oluştu: ' + error.message;
                
                // Sürekli konuşma modunda yeni kayda otomatik başla
                if (isContinuousMode) {
                    setTimeout(() => {
                        if (!isRecording && !isAISpeaking) {
                            startRecording();
                        }
                    }, 2000);
                }
            }
        }
        
        // AI yanıtını sesli sohbet için al
        async function getVoiceChatResponse(text) {
            try {
                // CSRF token
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                // Chat ID
                const chatId = localStorage.getItem('current_chat_id') || null;
                
                // İstek verisi
                const requestData = {
                    message: text.trim(),
                    chat_id: chatId,
                    creative_mode: isCreativeMode,
                    coding_mode: isCodingMode,
                    model: selectedModel,
                    is_first_message: false,
                    chat_history: chatHistory,
                    visitor_name: visitorName
                };
                
                // API isteği gönder
                const response = await fetch('/api/ai/process', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify(requestData)
                });
                
                if (response.ok) {
                    const data = await response.json();
                    
        
                    if (data.chat_id) {
                        localStorage.setItem('current_chat_id', data.chat_id);
                    }
                    
                 
                    let finalResponse = data.response;
                    

                    addToChatHistory(text, 'user');
                    addToChatHistory(finalResponse, 'ai');
                    
                 
                    if (!data.is_code_response) {
                        // AI'nin yanıtını popup mesaj alanında göster
                        voiceConversation.style.display = 'block';
                        voiceMessage.innerHTML += '<strong>LizzAI:</strong> ' + finalResponse + '<br>';
                        
                        // AI konuşmaya başlıyor - görsel efekt
                        voiceVisualizer.classList.add('ai-speaking');
                        isAISpeaking = true;
                        
                        // Tenor GIF URL'lerini temizle
                        const cleanedResponse = finalResponse.replace(/https:\/\/media\.tenor\.com\/[^\s]+\.gif/g, '');
                        
                        // AI yanıtını seslendir
                        await speakVoiceChatResponse(cleanedResponse);
                    } else {
                        // Kod yanıtı için özel mesaj
                        voiceConversation.style.display = 'block';
                        voiceMessage.innerHTML += '<strong>LizzAI:</strong> Kod yanıtı oluşturdum. Görüntülemek için popup\'ı kapatın.<br>';
                        voiceStatus.textContent = 'Kod yanıtı oluşturuldu. Görmek için popup\'ı kapatabilirsiniz.';
                        
                        // Ana sohbet ekranına kod yanıtını ekle
                        addMessage(finalResponse, 'ai', data.code, data.language);
                        
                        // Sürekli konuşma modunda yeni kayda otomatik başla
                        if (isContinuousMode) {
                            setTimeout(() => {
                                if (!isRecording && !isAISpeaking) {
                                    startRecording();
                                }
                            }, 2000);
                        }
                    }
                } else {
                    const errorData = await response.json();
                    const errorMessage = errorData.error || "Yanıt alınamadı. Lütfen tekrar deneyin.";
                    
                    voiceStatus.textContent = 'AI yanıt hatası: ' + errorMessage;
                    
                    // Sürekli konuşma modunda yeni kayda otomatik başla
                    if (isContinuousMode) {
                        setTimeout(() => {
                            if (!isRecording && !isAISpeaking) {
                                startRecording();
                            }
                        }, 2000);
                    }
                }
            } catch (error) {
                console.error('AI yanıt hatası:', error);
                voiceStatus.textContent = 'AI yanıt hatası: ' + error.message;
                
                // Sürekli konuşma modunda yeni kayda otomatik başla
                if (isContinuousMode) {
                    setTimeout(() => {
                        if (!isRecording && !isAISpeaking) {
                            startRecording();
                        }
                    }, 2000);
                }
            } finally {
                waitingForAIResponse = false;
            }
        }
        
        // AI yanıtını seslendir (Sesli sohbet için)
        async function speakVoiceChatResponse(text) {
            try {
                if (!text || text.trim().length === 0) {
                    console.error('Seslendirilecek metin boş');
                    isAISpeaking = false;
                    voiceVisualizer.classList.remove('ai-speaking');
                    
                    // Sürekli konuşma modunda yeni kayda otomatik başla
                    if (isContinuousMode) {
                        setTimeout(() => {
                            if (!isRecording && !isAISpeaking) {
                                startRecording();
                            }
                        }, 1000);
                    }
                    return;
                }
                
                voiceStatus.textContent = 'Lizz konuşuyor...';
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                const response = await fetch('/api/speech/to-speech', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({ text: text })
                });
                
                const data = await response.json();
                
                if (data.success && data.audioContent) {
                    // Base64 ses içeriğini çal
                    try {
                        const audio = new Audio(`data:audio/mp3;base64,${data.audioContent}`);
                        
                        // Ses çalma olaylarını izle
                        audio.addEventListener('play', () => {
                            console.log('Ses çalmaya başladı');
                            isAISpeaking = true;
                            voiceVisualizer.classList.add('ai-speaking');
                        });
                        
                        audio.addEventListener('ended', () => {
                            console.log('Ses çalma tamamlandı');
                            isAISpeaking = false;
                            voiceVisualizer.classList.remove('ai-speaking');
                            
                            // Sürekli konuşma modunda yeni kayda otomatik başla
                            if (isContinuousMode) {
                                setTimeout(() => {
                                    if (!isRecording && !isAISpeaking) {
                                        startRecording();
                                        voiceStatus.textContent = 'Konuşuyorsunuz...';
                                    }
                                }, 1000);
                            } else {
                                voiceStatus.textContent = 'Mikrofona tıklayarak yeni bir soru sorabilirsiniz.';
                            }
                        });
                        
                        audio.addEventListener('error', (e) => {
                            console.error('Ses çalma hatası:', e);
                            isAISpeaking = false;
                            voiceVisualizer.classList.remove('ai-speaking');
                            
                            // Sürekli konuşma modunda yeni kayda otomatik başla
                            if (isContinuousMode) {
                                setTimeout(() => {
                                    if (!isRecording && !isAISpeaking) {
                                        startRecording();
                                    }
                                }, 1000);
                            }
                        });
                        
                        await audio.play();
                    } catch (playError) {
                        console.error('Ses çalma hatası:', playError);
                        isAISpeaking = false;
                        voiceVisualizer.classList.remove('ai-speaking');
                        
                        // Sürekli konuşma modunda yeni kayda otomatik başla
                        if (isContinuousMode) {
                            setTimeout(() => {
                                if (!isRecording && !isAISpeaking) {
                                    startRecording();
                                }
                            }, 1000);
                        }
                    }
                } else {
                    console.error('Text-to-Speech API hatası:', data);
                    isAISpeaking = false;
                    voiceVisualizer.classList.remove('ai-speaking');
                    
                    // Sürekli konuşma modunda yeni kayda otomatik başla
                    if (isContinuousMode) {
                        setTimeout(() => {
                            if (!isRecording && !isAISpeaking) {
                                startRecording();
                            }
                        }, 1000);
                    }
                }
            } catch (error) {
                console.error('Text-to-Speech hatası:', error);
                isAISpeaking = false;
                voiceVisualizer.classList.remove('ai-speaking');
                
                // Sürekli konuşma modunda yeni kayda otomatik başla
                if (isContinuousMode) {
                    setTimeout(() => {
                        if (!isRecording && !isAISpeaking) {
                            startRecording();
                        }
                    }, 1000);
                }
            }
        }
        
        // Sessizlik algılamak için ses kayıt işlemi
        function setupVoiceActivity() {
            if (!stream) return;
            
            try {
                // AudioContext oluştur
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const analyser = audioContext.createAnalyser();
                const microphone = audioContext.createMediaStreamSource(stream);
                const javascriptNode = audioContext.createScriptProcessor(2048, 1, 1);
                
                analyser.smoothingTimeConstant = 0.8;
                analyser.fftSize = 1024;
                
                microphone.connect(analyser);
                analyser.connect(javascriptNode);
                javascriptNode.connect(audioContext.destination);
                
                javascriptNode.onaudioprocess = function() {
                    const array = new Uint8Array(analyser.frequencyBinCount);
                    analyser.getByteFrequencyData(array);
                    let values = 0;
                    
                    for (let i = 0; i < array.length; i++) {
                        values += (array[i]);
                    }
                    
                    const average = values / array.length;
                    console.log('Audio level:', average);
                    
                    // 5 saniye sessiz kalırsa kayıt otomatik durdurulsun
                    if (average < 5 && isRecording) {
                        silenceCounter++;
                        if (silenceCounter > 50) { // ~5 saniye
                            stopRecording();
                            voiceStatus.textContent = 'Sessizlik algılandı. Yanıtlanıyor...';
                        }
                    } else {
                        silenceCounter = 0;
                    }
                };
            } catch (e) {
                console.error('Ses analiz hatası:', e);
            }
        }
        
        // Sesli sohbet popup'ını aç/kapat
        function toggleVoicePopup() {
            voicePopup.classList.toggle('active');
            voiceOverlay.classList.toggle('active');
            
            if (voicePopup.classList.contains('active')) {
                // Mikrofon erişimi iste
                requestMicrophoneAccess().then(() => {
                    // Konuşma alanını temizle
                    voiceConversation.style.display = 'none';
                    voiceMessage.innerHTML = '';
                    
                    // Sürekli konuşma modu aktifse otomatik başlat
                    if (isContinuousMode) {
                        setTimeout(() => {
                            startRecording();
                        }, 1000);
                    }
                });
            } else {
                // Sesi durdur
                if (isRecording) {
                    stopRecording();
                }
                
                // AI konuşuyorsa durdur
                if (isAISpeaking) {
                    stopAllAudio();
                }
                
                // Stream'i kapat
                if (stream) {
                    try {
                        stream.getTracks().forEach(track => track.stop());
                        stream = null;
                    } catch (error) {
                        console.error('Stream kapatma hatası:', error);
                    }
                }
            }
        }

        function processVoiceResponse(text) {
            // Sohbet geçmişine AI yanıtını ekle
            if (voiceConversation && voiceMessage) {
                // Eğer bu ilk mesaj değilse, yeni mesaj ekle
                if (voiceMessage.innerHTML.indexOf('voice-chat-welcome') === -1) {
                    voiceMessage.innerHTML += '<div class="voice-message-item ai-message"><strong>AI:</strong> ' + text + '</div>';
                } else {
                    // İlk mesajsa, hoş geldin mesajını değiştir
                    voiceMessage.innerHTML = '<div class="voice-message-item ai-message"><strong>AI:</strong> ' + text + '</div>';
                }
                
                // Otomatik kaydırma
                voiceConversation.scrollTop = voiceConversation.scrollHeight;
            }
            
            // Sesli sohbet modunda sesli yanıt ver
            speakAIResponseInChatMode(text);
        }

        // Sesli sohbet modunda AI yanıtını seslendir
        async function speakAIResponseInChatMode(text) {
            try {
                if (!text || text.trim().length === 0) return;
                
                // Tenor GIF URL'lerini temizle
                text = text.replace(/https:\/\/media\.tenor\.com\/[^\s]+\.gif/g, '');
                
                voiceStatus.textContent = 'AI yanıtlanıyor...';
                
                // Vizualizasyonu yanıt verme moduna geçir
                const visualizer = document.getElementById('voice-visualizer');
                if (visualizer) {
                    visualizer.classList.add('ai-speaking');
                }
                
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                const response = await fetch('/api/speech/to-speech', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({ text: text })
                });
                
                const data = await response.json();
                
                if (data.success && data.audioContent) {
                    // Tüm mevcut ses oynatmalarını durdur
                    stopAllAudio();
                    
                    // Base64 ses içeriğini çal
                    try {
                        currentAudio = new Audio(`data:audio/mp3;base64,${data.audioContent}`);
                        
                        // Ses çalma olaylarını izle
                        currentAudio.addEventListener('play', () => {
                            console.log('AI konuşuyor...');
                            voiceStatus.textContent = 'AI konuşuyor...';
                        });
                        
                        currentAudio.addEventListener('ended', () => {
                            console.log('AI konuşması tamamlandı');
                            voiceStatus.textContent = 'Konuşmak için mikrofona tıklayın';
                            
                            // AI konuşması bitince vizualizasyonu normal hale getir
                            if (visualizer) {
                                visualizer.classList.remove('ai-speaking');
                            }
                            
                            // Sürekli konuşma modundaysa otomatik kayda başla
                            if (isContinuousMode) {
                                setTimeout(() => {
                                    // Kaydedilmiyor ve mikrofon açıksa kayda başla
                                    if (!isRecording && stream) {
                                        startRecording();
                                    }
                                }, 1000); // 1 saniye sonra kayda başla
                            }
                        });
                        
                        currentAudio.addEventListener('error', (e) => {
                            console.error('Ses çalma hatası:', e);
                            voiceStatus.textContent = 'Ses çalma hatası. Tekrar deneyin.';
                            
                            if (visualizer) {
                                visualizer.classList.remove('ai-speaking');
                            }
                        });
                        
                        await currentAudio.play();
                    } catch (playError) {
                        console.error('Ses çalma hatası:', playError);
                        voiceStatus.textContent = 'Ses çalma hatası. Tekrar deneyin.';
                        
                        if (visualizer) {
                            visualizer.classList.remove('ai-speaking');
                        }
                    }
                } else {
                    console.error('Text-to-Speech API hatası:', data);
                    voiceStatus.textContent = 'Ses oluşturma hatası. Tekrar deneyin.';
                    
                    if (visualizer) {
                        visualizer.classList.remove('ai-speaking');
                    }
                }
            } catch (error) {
                console.error('Text-to-Speech hatası:', error);
                voiceStatus.textContent = 'Ses oluşturma hatası. Tekrar deneyin.';
                
                if (visualizer) {
                    visualizer.classList.remove('ai-speaking');
                }
            }
        }

        // Tüm ses oynatmalarını durdur
        function stopAllAudio() {
            if (currentAudio) {
                currentAudio.pause();
                currentAudio.currentTime = 0;
                currentAudio = null;
            }
        }

        // ... existing code ...

        // Orijinal getAIResponse fonksiyonunu değiştir
        async function getAIResponse(text, userContext = '') {
            try {
                loading = true;
                stopAllAudio(); // Önceki sesleri durdur
                
                // Sesli sohbet modundaysa mesajı görsel olarak ekle
                if (voicePopup.classList.contains('active') && voicePopup.classList.contains('voice-chat-mode')) {
                    if (voiceMessage) {
                        // Eğer hoş geldin mesajı varsa onu kaldır
                        if (voiceMessage.innerHTML.indexOf('voice-chat-welcome') !== -1) {
                            voiceMessage.innerHTML = '';
                        }
                        
                        // Kullanıcı mesajını ekle
                        voiceMessage.innerHTML += '<div class="voice-message-item user-message"><strong>Siz:</strong> ' + text + '</div>';
                        
                        // Otomatik kaydırma
                        if (voiceConversation) {
                            voiceConversation.scrollTop = voiceConversation.scrollHeight;
                        }
                    }
                    
                    voiceStatus.textContent = 'AI yanıt hazırlanıyor...';
                }
                
                // ... existing code ...

                // Eğer sesli sohbet modundaysa, sadece yanıtı seslendir
                if (voicePopup.classList.contains('active') && voicePopup.classList.contains('voice-chat-mode')) {
                    processVoiceResponse(assistantMessage);
                } else {
                    // Normal sohbet akışı
                    // ... existing code ...
                }
                
            } catch (error) {
                console.error('AI yanıtı alınamadı:', error);
                
                // Sesli sohbet modunda hata mesajı ver
                if (voicePopup.classList.contains('active') && voicePopup.classList.contains('voice-chat-mode')) {
                    voiceStatus.textContent = 'Hata oluştu. Tekrar deneyin.';
                }
                
                // ... existing code ...
            } finally {
                loading = false;
            }
        }

        // ... existing code ...

        // Speech-to-Text sonuçlarını işle
        async function processSTTResult(result) {
            if (result && result.transcript) {
                const transcript = result.transcript;
                
                // Sesli chat modunda
                if (voicePopup.classList.contains('active') && voicePopup.classList.contains('voice-chat-mode')) {
                    voiceStatus.textContent = 'Yanıt bekleniyor...';
                    await getAIResponse(transcript);
                } else {
                    // Sesli girişi metin kutusuna doldur
                    messageInput.value = transcript;
                    
                    // Otomatik gönder
                    document.querySelector('.ai-submit-btn').click();
                }
            }
        }

        // ... existing code ...
    });

    // User dropdown toggle
    document.addEventListener('DOMContentLoaded', function() {
        const userDropdownToggle = document.getElementById('userDropdownToggle');
        const userDropdownMenu = document.getElementById('userDropdownMenu');
        
        if (userDropdownToggle && userDropdownMenu) {
            userDropdownToggle.addEventListener('click', function(e) {
                userDropdownMenu.classList.toggle('show');
                e.stopPropagation();
            });
            
            document.addEventListener('click', function(e) {
                if (!userDropdownToggle.contains(e.target) && !userDropdownMenu.contains(e.target)) {
                    userDropdownMenu.classList.remove('show');
                }
            });
        }

        // Kullanıcı profil popup'ını oluştur
        function createProfilePopup() {
            // Daha önce varsa kaldır
            const existingModal = document.getElementById('userProfileModal');
            if (existingModal) existingModal.remove();

            // Yeni modal oluştur
            const modal = document.createElement('div');
            modal.id = 'userProfileModal';
            modal.className = 'fixed inset-0 z-50 flex items-center justify-center';
            modal.style.backgroundColor = 'rgba(0, 0, 0, 0.7)';
            modal.style.backdropFilter = 'blur(5px)';
            modal.style.display = 'none';
            modal.style.position = 'fixed';
            modal.style.top = '0';
            modal.style.left = '0';
            modal.style.width = '100vw';
            modal.style.height = '100vh';
            modal.style.zIndex = '9999';
            modal.style.alignItems = 'center';
            modal.style.justifyContent = 'center';

            // Modal içeriği
            const modalContent = document.createElement('div');
            modalContent.className = 'relative bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6';
            modalContent.style.backgroundColor = '#1f2937';
            modalContent.style.borderRadius = '12px';
            modalContent.style.boxShadow = '0 25px 50px -12px rgba(0, 0, 0, 0.25)';
            modalContent.style.maxWidth = '450px';
            modalContent.style.width = '90%';
            modalContent.style.position = 'relative';
            modalContent.style.padding = '24px';
            modalContent.style.color = '#f9fafb';
            modalContent.style.overflow = 'auto';
            modalContent.style.maxHeight = '85vh';

            // Kapatma butonu
            const closeButton = document.createElement('button');
            closeButton.className = 'absolute top-4 right-4 text-gray-400 hover:text-white';
            closeButton.style.position = 'absolute';
            closeButton.style.top = '16px';
            closeButton.style.right = '16px';
            closeButton.style.color = '#9ca3af';
            closeButton.style.cursor = 'pointer';
            closeButton.style.transition = 'color 0.3s';
            closeButton.innerHTML = '<i class="fas fa-times"></i>';
            closeButton.addEventListener('click', () => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            });
            closeButton.addEventListener('mouseover', () => {
                closeButton.style.color = '#f9fafb';
            });
            closeButton.addEventListener('mouseout', () => {
                closeButton.style.color = '#9ca3af';
            });

            // Modal başlığı
            const title = document.createElement('h2');
            title.className = 'text-xl font-semibold mb-6 text-indigo-400';
            title.style.fontSize = '1.25rem';
            title.style.fontWeight = '600';
            title.style.marginBottom = '24px';
            title.style.color = '#6366f1';
            title.textContent = 'Kullanıcı Profili';

            // Kullanıcı bilgileri
            const userInfo = document.createElement('div');
            userInfo.className = 'flex flex-col items-center mb-6';
            userInfo.style.display = 'flex';
            userInfo.style.flexDirection = 'column';
            userInfo.style.alignItems = 'center';
            userInfo.style.marginBottom = '24px';

            // Kullanıcı avatarı
            const avatar = document.createElement('div');
            avatar.className = 'w-24 h-24 rounded-full bg-indigo-500 flex items-center justify-center text-3xl font-bold text-white mb-4';
            avatar.style.width = '96px';
            avatar.style.height = '96px';
            avatar.style.borderRadius = '50%';
            avatar.style.display = 'flex';
            avatar.style.alignItems = 'center';
            avatar.style.justifyContent = 'center';
            avatar.style.fontSize = '3rem';
            avatar.style.fontWeight = '700';
            avatar.style.color = 'white';
            avatar.style.marginBottom = '16px';
            avatar.style.background = 'linear-gradient(135deg, #4f46e5 0%, #ec4899 100%)';
            
            // Kullanıcı adı
            const userName = document.createElement('h3');
            userName.className = 'text-lg font-medium';
            userName.style.fontSize = '1.25rem';
            userName.style.fontWeight = '500';
            userName.style.color = '#f9fafb';
            
            // Kullanıcı emaili
            const userEmail = document.createElement('p');
            userEmail.className = 'text-gray-400 mt-1';
            userEmail.style.color = '#9ca3af';
            userEmail.style.marginTop = '4px';
            userEmail.style.fontSize = '0.875rem';

            // Hesap bilgileri bölümü
            const accountInfo = document.createElement('div');
            accountInfo.className = 'w-full mt-6';
            accountInfo.style.width = '100%';
            accountInfo.style.marginTop = '24px';

            // Hesap bilgileri başlığı
            const accountTitle = document.createElement('h4');
            accountTitle.className = 'text-sm font-medium text-indigo-300 uppercase tracking-wider mb-4';
            accountTitle.style.fontSize = '0.875rem';
            accountTitle.style.fontWeight = '500';
            accountTitle.style.color = '#a5b4fc';
            accountTitle.style.textTransform = 'uppercase';
            accountTitle.style.letterSpacing = '0.05em';
            accountTitle.style.marginBottom = '16px';
            accountTitle.textContent = 'HESAP BİLGİLERİ';

            // Hesap bilgilerini görüntülemek için tablo veya liste
            const infoList = document.createElement('div');
            infoList.className = 'space-y-3';
            infoList.style.display = 'flex';
            infoList.style.flexDirection = 'column';
            infoList.style.gap = '12px';

            // Kayıt tarihi bilgisi
            const regDate = document.createElement('div');
            regDate.className = 'flex justify-between';
            regDate.style.display = 'flex';
            regDate.style.justifyContent = 'space-between';
            regDate.style.alignItems = 'center';
            
            const regDateLabel = document.createElement('span');
            regDateLabel.className = 'text-gray-400';
            regDateLabel.style.color = '#9ca3af';
            regDateLabel.textContent = 'Kayıt Tarihi';
            
            const regDateValue = document.createElement('span');
            regDateValue.className = 'text-white';
            regDateValue.style.color = '#f9fafb';
            regDateValue.id = 'userRegDate';
            
            regDate.appendChild(regDateLabel);
            regDate.appendChild(regDateValue);
            
            // Kullanıcı ID bilgisi
            const userId = document.createElement('div');
            userId.className = 'flex justify-between';
            userId.style.display = 'flex';
            userId.style.justifyContent = 'space-between';
            userId.style.alignItems = 'center';
            
            const userIdLabel = document.createElement('span');
            userIdLabel.className = 'text-gray-400';
            userIdLabel.style.color = '#9ca3af';
            userIdLabel.textContent = 'Kullanıcı ID';
            
            const userIdValue = document.createElement('span');
            userIdValue.className = 'text-white';
            userIdValue.style.color = '#f9fafb';
            userIdValue.id = 'userId';
            
            userId.appendChild(userIdLabel);
            userId.appendChild(userIdValue);

            // İstatistikler bölümü
            const statsInfo = document.createElement('div');
            statsInfo.className = 'w-full mt-6';
            statsInfo.style.width = '100%';
            statsInfo.style.marginTop = '24px';

            // İstatistikler başlığı
            const statsTitle = document.createElement('h4');
            statsTitle.className = 'text-sm font-medium text-indigo-300 uppercase tracking-wider mb-4';
            statsTitle.style.fontSize = '0.875rem';
            statsTitle.style.fontWeight = '500';
            statsTitle.style.color = '#a5b4fc';
            statsTitle.style.textTransform = 'uppercase';
            statsTitle.style.letterSpacing = '0.05em';
            statsTitle.style.marginBottom = '16px';
            statsTitle.textContent = 'İSTATİSTİKLER';

            // İstatistik bilgilerini görüntülemek için tablo veya liste
            const statsList = document.createElement('div');
            statsList.className = 'space-y-3';
            statsList.style.display = 'flex';
            statsList.style.flexDirection = 'column';
            statsList.style.gap = '12px';

            // Toplam sohbet bilgisi
            const totalChats = document.createElement('div');
            totalChats.className = 'flex justify-between';
            totalChats.style.display = 'flex';
            totalChats.style.justifyContent = 'space-between';
            totalChats.style.alignItems = 'center';
            
            const totalChatsLabel = document.createElement('span');
            totalChatsLabel.className = 'text-gray-400';
            totalChatsLabel.style.color = '#9ca3af';
            totalChatsLabel.textContent = 'Toplam Sohbet';
            
            const totalChatsValue = document.createElement('span');
            totalChatsValue.className = 'text-white';
            totalChatsValue.style.color = '#f9fafb';
            totalChatsValue.id = 'userTotalChats';
            
            totalChats.appendChild(totalChatsLabel);
            totalChats.appendChild(totalChatsValue);
            
            // Toplam mesaj bilgisi
            const totalMessages = document.createElement('div');
            totalMessages.className = 'flex justify-between';
            totalMessages.style.display = 'flex';
            totalMessages.style.justifyContent = 'space-between';
            totalMessages.style.alignItems = 'center';
            
            const totalMessagesLabel = document.createElement('span');
            totalMessagesLabel.className = 'text-gray-400';
            totalMessagesLabel.style.color = '#9ca3af';
            totalMessagesLabel.textContent = 'Toplam Mesaj';
            
            const totalMessagesValue = document.createElement('span');
            totalMessagesValue.className = 'text-white';
            totalMessagesValue.style.color = '#f9fafb';
            totalMessagesValue.id = 'userTotalMessages';
            
            totalMessages.appendChild(totalMessagesLabel);
            totalMessages.appendChild(totalMessagesValue);

            // Çıkış yap butonu
            const logoutButton = document.createElement('button');
            logoutButton.className = 'mt-8 w-full py-2 px-4 bg-red-600 hover:bg-red-700 text-white rounded transition duration-200';
            logoutButton.style.marginTop = '32px';
            logoutButton.style.width = '100%';
            logoutButton.style.padding = '8px 16px';
            logoutButton.style.backgroundColor = '#dc2626';
            logoutButton.style.color = 'white';
            logoutButton.style.borderRadius = '6px';
            logoutButton.style.border = 'none';
            logoutButton.style.cursor = 'pointer';
            logoutButton.style.transition = 'background-color 0.2s';
            logoutButton.innerHTML = '<i class="fas fa-sign-out-alt mr-2"></i> Çıkış Yap';
            
            logoutButton.addEventListener('mouseover', () => {
                logoutButton.style.backgroundColor = '#b91c1c';
            });
            
            logoutButton.addEventListener('mouseout', () => {
                logoutButton.style.backgroundColor = '#dc2626';
            });
            
            logoutButton.addEventListener('click', () => {
                // Çıkış yapma form submit işlemi
                document.querySelector('.user-dropdown-menu form').submit();
            });

            // Tüm bileşenleri bir araya getirme
            infoList.appendChild(regDate);
            infoList.appendChild(userId);
            accountInfo.appendChild(accountTitle);
            accountInfo.appendChild(infoList);
            
            statsList.appendChild(totalChats);
            statsList.appendChild(totalMessages);
            statsInfo.appendChild(statsTitle);
            statsInfo.appendChild(statsList);
            
            userInfo.appendChild(avatar);
            userInfo.appendChild(userName);
            userInfo.appendChild(userEmail);
            
            modalContent.appendChild(closeButton);
            modalContent.appendChild(title);
            modalContent.appendChild(userInfo);
            modalContent.appendChild(accountInfo);
            modalContent.appendChild(statsInfo);
            modalContent.appendChild(logoutButton);
            
            modal.appendChild(modalContent);
            document.body.appendChild(modal);
            
            return modal;
        }

        // Kullanıcı profil popup'ını oluştur ve kullanıcı verilerini ekle
        if (userDropdownToggle) {
            const profileModal = createProfilePopup();
            
            // Kullanıcı dropdown'ına tıklandığında profil popup'ı göster
            userDropdownToggle.addEventListener('click', function(e) {
                // Avatar ve kullanıcı adı bilgisini al
                const avatarElem = userDropdownToggle.querySelector('.user-avatar');
                const avatarText = avatarElem ? avatarElem.textContent.trim() : 'R';
                const userName = userDropdownToggle.querySelector('span') ? 
                                 userDropdownToggle.querySelector('span').textContent.trim() : 'Kullanıcı';
                
                // Modal içindeki öğeleri seç
                const modalAvatar = document.querySelector('#userProfileModal .w-24');
                const modalUserName = document.querySelector('#userProfileModal h3');
                const modalUserEmail = document.querySelector('#userProfileModal p');
                
                // Bilgileri doldur
                if (modalAvatar) {
                    @auth
                    if ("{{ auth()->check() && auth()->user()->avatar }}") {
                        // Google avatarı varsa
                        modalAvatar.innerHTML = `<img src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}" 
                            style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
                    } else {
                        // Avatar yoksa baş harf
                        modalAvatar.textContent = avatarText;
                    }
                    @else
                    modalAvatar.textContent = avatarText;
                    @endauth
                }
                
                if (modalUserName) modalUserName.textContent = userName;
                
                // Email ve diğer bilgileri ekle
                @auth
                if (modalUserEmail) modalUserEmail.textContent = "{{ auth()->user()->email }}";
                
                const regDate = document.getElementById('userRegDate');
                const userId = document.getElementById('userId');
                const totalChats = document.getElementById('userTotalChats');
                const totalMessages = document.getElementById('userTotalMessages');
                
                if (regDate) regDate.textContent = "{{ auth()->user()->created_at ? auth()->user()->created_at->format('d.m.Y') : '25.04.2025' }}";
                if (userId) userId.textContent = "{{ auth()->id() ?? '1' }}";
                if (totalChats) totalChats.textContent = "2";
                if (totalMessages) totalMessages.textContent = "12";
                @endauth
                
                // Popup'ı göster
                profileModal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                
                // Dışarı tıklandığında kapat
                profileModal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        this.style.display = 'none';
                        document.body.style.overflow = 'auto';
                    }
                });
                
                e.stopPropagation();
            });
        }
    });

  
</script>