@extends('layouts.app')

@section('title', 'SoneAI - Yapay Zeka Sohbet')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
    /* AI yazıyor animasyonu */
    .ai-thinking {
        display: inline-flex;
        align-items: center;
        margin-left: 5px;
        padding: 2px 8px;
        border-radius: 10px;
        background-color: #3b82f680;
    }
    
    .ai-thinking span {
        display: inline-block;
        width: 5px;
        height: 5px;
        margin: 0 2px;
        border-radius: 50%;
        background-color: white;
        animation: ai-typing 1.4s infinite ease-in-out both;
    }
    
    .ai-thinking span:nth-child(1) {
        animation-delay: -0.32s;
    }
    
    .ai-thinking span:nth-child(2) {
        animation-delay: -0.16s;
    }
    
    @keyframes ai-typing {
        0%, 80%, 100% { transform: scale(0); }
        40% { transform: scale(1); }
    }
    
    /* Sone düşünüyor animasyonu */
    .sone-thinking {
        position: relative;
        background: #f0f7ff;
        padding: 12px 16px;
        border-radius: 16px;
        border-left: 4px solid #4285f4;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        margin: 10px 0;
        font-weight: 500;
        color: #3b82f6;
        max-width: 70%;
        display: none;
        animation: pulse 2s infinite;
    }
    
    .sone-thinking:before {
        content: '';
        position: absolute;
        left: -10px;
        top: 12px;
        width: 20px;
        height: 20px;
        background: #4285f4;
        border-radius: 50%;
    }
    
    .sone-thinking .dots {
        display: inline-flex;
        margin-left: 8px;
    }
    
    .sone-thinking .dots span {
        width: 6px;
        height: 6px;
        margin: 0 2px;
        background-color: #3b82f6;
        border-radius: 50%;
        display: inline-block;
        animation: bounce 1.5s infinite ease-in-out;
    }
    
    .sone-thinking .dots span:nth-child(1) { animation-delay: 0s; }
    .sone-thinking .dots span:nth-child(2) { animation-delay: 0.2s; }
    .sone-thinking .dots span:nth-child(3) { animation-delay: 0.4s; }
    
    @keyframes bounce {
        0%, 80%, 100% { transform: translateY(0); }
        40% { transform: translateY(-8px); }
    }
    
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(66, 133, 244, 0.4); }
        70% { box-shadow: 0 0 0 6px rgba(66, 133, 244, 0); }
        100% { box-shadow: 0 0 0 0 rgba(66, 133, 244, 0); }
    }
    
    /* Kodlama modu için stiller */
    .chat-container {
        transition: all 0.3s ease;
    }
    
    .chat-layout {
        display: flex;
        flex-direction: row;
        gap: 16px;
        height: 600px;
    }
    
    .chat-section {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        padding: 1.5rem;
        height: 100%;
    }
    
    .code-section {
        flex: 1;
        display: none;
        flex-direction: column;
        border-radius: 0.5rem;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        height: 100%;
        background: #1e1e1e;
        border: 1px solid #252525;
    }
    
    .coding-mode .code-section {
        display: flex;
    }
    
    .code-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 16px;
        background: #1e1e1e;
        color: #e0e0e0;
        border-bottom: 1px solid #3b3b3b;
    }
    
    .code-header .language-selector {
        background: #333;
        border: none;
        color: #fff;
        padding: 4px 8px;
        border-radius: 4px;
    }
    
    .code-content {
        flex: 1;
        padding: 16px;
        background: #1e1e1e;
        color: #d4d4d4;
        font-family: 'Consolas', 'Monaco', monospace;
        overflow-y: auto;
        font-size: 14px;
        line-height: 1.5;
    }
    
    .code-content pre {
        margin: 0;
        white-space: pre-wrap;
        color: #dcddde;
        font-family: Consolas, "Courier New", monospace;
    }
    
    .code-footer {
        display: flex;
        justify-content: flex-end;
        padding: 12px 16px;
        background: #1e1e1e;
        border-top: 1px solid #3b3b3b;
    }
    
    .code-footer button {
        background: #0e639c;
        border: none;
        color: white;
        padding: 6px 12px;
        border-radius: 4px;
        margin-left: 8px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 500;
    }
    
    .code-footer button:hover {
        background: #1177bb;
    }
</style>
@endsection

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h1 class="text-3xl font-bold text-gray-800">SoneAI</h1>
            <p class="text-gray-600">Yapay Zeka Asistanı</p>
        </div>

        <!-- Chat and Code Layout -->
        <div id="chat-container" class="chat-layout">
            <!-- Chat Section -->
            <div class="chat-section">
                <!-- Messages Area -->
                <div id="messages" class="flex-1 overflow-y-auto mb-4 space-y-4">
                    <!-- AI Message -->
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center">
                                <i class="fas fa-robot text-white"></i>
                            </div>
                        </div>
                        <div class="ml-3 bg-blue-100 rounded-lg p-3 max-w-[70%]">
                            <p class="text-gray-800">Merhaba! Ben SoneAI. Size nasıl yardımcı olabilirim?</p>
                        </div>
                    </div>
                    
                    <!-- Sone düşünüyor animasyonu (kaldırılacak) -->
                    <div id="sone-thinking" class="sone-thinking" style="display: none;">
                        <span>Yaziyor</span>
                        <div class="dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>

                <!-- Input Area -->
                <div class="border-t pt-4">
                    <form id="chat-form" class="flex space-x-4">
                        <input type="text" 
                               id="message-input" 
                               class="flex-1 border rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500"
                               placeholder="Mesajınızı yazın...">
                        <button type="submit" 
                                class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 focus:outline-none">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Code Section -->
            <div id="code-section" class="code-section">
                <div class="code-header">
                    <span>Kod Editörü</span>
                    <select class="language-selector" id="code-language" disabled>
                        <option value="javascript">JavaScript</option>
                        <option value="php">PHP</option>
                        <option value="python">Python</option>
                        <option value="html">HTML</option>
                        <option value="css">CSS</option>
                        <option value="sql">SQL</option>
                    </select>
                </div>
                <div class="code-content">
                    <pre id="code-content" contenteditable="false">// Buraya kod yanıtları alabilirsiniz</pre>
                </div>
                <div class="code-footer">
                    <button id="clear-code">Temizle</button>
                    <button id="copy-code">Kopyala</button>
                </div>
            </div>
        </div>

        <!-- Status Bar -->
        <div class="bg-white rounded-lg shadow-md p-4 mt-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-gray-600">Sistem Aktif</span>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center">
                        <span class="mr-3 text-sm font-medium text-gray-700">Yaratıcı Mod</span>
                        <button type="button" id="creative-toggle" class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm font-medium rounded-md">
                            <span id="button-text">Kapalı</span>
                        </button>
                    </div>
                    <div class="flex items-center">
                        <span class="mr-3 text-sm font-medium text-gray-700">Kodlama Modu</span>
                        <button type="button" id="coding-toggle" class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm font-medium rounded-md">
                            <span id="coding-button-text">Kapalı</span>
                        </button>
                    </div>
                    <div class="text-gray-500 text-sm">
                        <span id="typing-indicator" class="hidden">
                            <span class="ai-thinking">
                                <span></span>
                                <span></span>
                                <span></span>
                            </span>
                            <span class="ml-1">AI yanıtlıyor...</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chatForm = document.getElementById('chat-form');
        const messageInput = document.getElementById('message-input');
        const messagesContainer = document.getElementById('messages');
        const typingIndicator = document.getElementById('typing-indicator');
        const soneThinking = document.getElementById('sone-thinking');
        const creativeToggle = document.getElementById('creative-toggle');
        const buttonText = document.getElementById('button-text');
        const codingToggle = document.getElementById('coding-toggle');
        const codingButtonText = document.getElementById('coding-button-text');
        const chatContainer = document.getElementById('chat-container');
        const codeSection = document.getElementById('code-section');
        const codeContent = document.getElementById('code-content');
        const codeLanguage = document.getElementById('code-language');
        const clearCodeBtn = document.getElementById('clear-code');
        const copyCodeBtn = document.getElementById('copy-code');
        
        // Sayfa yüklendiğinde Creative Mode ve Kodlama Modu durumlarını kontrol et
        let isCreativeMode = localStorage.getItem('creative_mode') === 'true';
        let isCodingMode = localStorage.getItem('coding_mode') === 'true';
        
        if (isCreativeMode) {
            buttonText.textContent = 'Açık';
            creativeToggle.classList.remove('bg-gray-200');
            creativeToggle.classList.add('bg-green-500', 'text-white');
        }
        
        if (isCodingMode) {
            codingButtonText.textContent = 'Açık';
            codingToggle.classList.remove('bg-gray-200');
            codingToggle.classList.add('bg-green-500', 'text-white');
            toggleCodingMode(true);
        }
        
        // Mesaj göndermeden önce form kontrolü
        if (chatForm) {
            chatForm.addEventListener('submit', handleSubmit);
        } else {
            console.error('Chat form bulunamadı!');
        }
        
        // Yaratıcı mod ve Kodlama Modu değişikliklerini sakla
        if (creativeToggle) {
            creativeToggle.addEventListener('click', function() {
                isCreativeMode = !isCreativeMode;
                localStorage.setItem('creative_mode', isCreativeMode);
                
                if (isCreativeMode) {
                    buttonText.textContent = 'Açık';
                    this.classList.remove('bg-gray-200', 'hover:bg-gray-300');
                    this.classList.add('bg-green-500', 'hover:bg-green-600', 'text-white');
                } else {
                    buttonText.textContent = 'Kapalı';
                    this.classList.remove('bg-green-500', 'hover:bg-green-600', 'text-white');
                    this.classList.add('bg-gray-200', 'hover:bg-gray-300', 'text-gray-800');
                }
            });
        } else {
            console.error('Creative toggle bulunamadı!');
        }
        
        if (codingToggle) {
            codingToggle.addEventListener('click', function() {
                isCodingMode = !isCodingMode;
                localStorage.setItem('coding_mode', isCodingMode);
                
                if (isCodingMode) {
                    codingButtonText.textContent = 'Açık';
                    this.classList.remove('bg-gray-200', 'hover:bg-gray-300');
                    this.classList.add('bg-green-500', 'hover:bg-green-600', 'text-white');
                } else {
                    codingButtonText.textContent = 'Kapalı';
                    this.classList.remove('bg-green-500', 'hover:bg-green-600', 'text-white');
                    this.classList.add('bg-gray-200', 'hover:bg-gray-300', 'text-gray-800');
                }
                
                // Kodlama modunu aç/kapat
                toggleCodingMode(isCodingMode);
            });
        } else {
            console.error('Coding toggle bulunamadı!');
        }
        
        // Kodlama modunu aç/kapat
        function toggleCodingMode(isActive) {
            if (isActive) {
                chatContainer.classList.add('coding-mode');
            } else {
                chatContainer.classList.remove('coding-mode');
            }
        }
        
        // Kod paneli işlemleri
        if (clearCodeBtn) {
            clearCodeBtn.addEventListener('click', function() {
                codeContent.textContent = '';
            });
        }
        
        if (copyCodeBtn) {
            copyCodeBtn.addEventListener('click', function() {
                navigator.clipboard.writeText(codeContent.textContent)
                    .then(() => {
                        // Kopyalama başarılı olduğunda geçici bildirim göster
                        const originalText = this.textContent;
                        this.textContent = 'Kopyalandı!';
                        setTimeout(() => {
                            this.textContent = originalText;
                        }, 2000);
                    })
                    .catch(err => {
                        console.error('Kopyalama hatası:', err);
                    });
            });
        }
        
        async function handleSubmit(e) {
            e.preventDefault();
            
            if (!messageInput) {
                console.error('Mesaj input alanı bulunamadı!');
                return;
            }
            
            const message = messageInput.value.trim();
            if (!message) return;

            // Kullanıcı mesajını ekle
            addMessage(message, 'user');
            messageInput.value = '';

            // Sone düşünüyor animasyonunu göster
            showThinkingAnimation();

            // Timeout ekle - yanıt alınamazsa 15 saniye sonra hata göster
            const timeout = setTimeout(() => {
                hideThinkingAnimation();
                if (typingIndicator) {
                    typingIndicator.classList.add('hidden');
                }
                addMessage('Yanıt alınamadı. Lütfen tekrar deneyin.', 'ai');
            }, 15000);

            try {
                // CSRF token kontrolü
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                if (!csrfToken) {
                    throw new Error('CSRF token bulunamadı!');
                }
                
                const response = await fetch('/api/ai/process', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.content
                    },
                    body: JSON.stringify({ 
                        message, 
                        creative_mode: isCreativeMode,
                        coding_mode: isCodingMode,
                        preferred_language: codeLanguage.value
                    })
                });
                
                // Timeout'u temizle
                clearTimeout(timeout);

                // Sone düşünüyor animasyonunu gizle
                hideThinkingAnimation();

                // HTTP yanıt durumunu kontrol et
                if (!response.ok) {
                    throw new Error(`Sunucu hatası: ${response.status} - ${response.statusText}`);
                }

                // HTTP yanıtı göstermek yerine JSON olarak ayrıştır
                const data = await response.json();
                
                // Typing göstergesini gizle
                if (typingIndicator) {
                    typingIndicator.classList.add('hidden');
                }

                if (data.success) {
                    // JSON yanıtını doğrudan kullan
                    const aiResponse = data.response || "Yanıt alınamadı.";
                    
                    // Temizlenmiş yanıtı al
                    let cleanedResponse = cleanResponseText(aiResponse);
                    
                    // AI yanıtını ekle - temizlenmiş metin olarak
                    addMessage(cleanedResponse, 'ai');
                    
                    // Kodlama modu açıksa ve kod yanıtı varsa
                    if (isCodingMode && data.code) {
                        // Kod dilini tespit et
                        const detectedLanguage = detectLanguage(data.code);
                        
                        // Tespit edilen dile göre select değerini güncelle
                        codeLanguage.value = detectedLanguage;
                        
                        // Syntax highlighting uygula
                        const highlightedCode = highlightCode(data.code, detectedLanguage);
                        
                        // Kod paneline ekle
                        codeContent.textContent = highlightedCode;
                    } else if (isCodingMode) {
                        // Eğer kod yanıtı yoksa ama kodlama modu açıksa, bir kod çıkarma işlemi yap
                        try {
                            // Yanıtta bir kod bloğu var mı kontrol et (markdown code block)
                            const codeBlockMatch = cleanedResponse.match(/```([a-zA-Z]*)\n([\s\S]*?)```/);
                            if (codeBlockMatch) {
                                const language = codeBlockMatch[1].toLowerCase() || 'javascript';
                                const code = codeBlockMatch[2];
                                
                                // Dil uygunsa select değerini güncelle
                                const options = Array.from(codeLanguage.options);
                                const option = options.find(opt => opt.value === language);
                                if (option) {
                                    codeLanguage.value = language;
                                }
                                
                                // Kod paneline ekle
                                codeContent.textContent = code;
                                
                                // Yanıttan kod bloğunu kaldır
                                cleanedResponse = cleanedResponse.replace(/```[a-zA-Z]*\n[\s\S]*?```/, '**Kod panelde gösteriliyor**');
                                
                                // Güncellenmiş yanıtı ekle
                                updateLastMessage(cleanedResponse);
                            }
                        } catch (e) {
                            console.error('Kod çıkarma hatası:', e);
                        }
                    }
                } else if (data.error) {
                    // Sunucudan dönen özel hata mesajı
                    addMessage(data.error, 'ai');
                } else {
                    // Genel hata durumu
                    addMessage(data.response || 'Üzgünüm, bir hata oluştu.', 'ai');
                }
            } catch (error) {
                // Timeout'u temizle
                clearTimeout(timeout);
                
                // Sone düşünüyor animasyonunu gizle
                hideThinkingAnimation();
                
                // Hata detaylarını logla
                console.error('Hata:', error);
                
                // Typing göstergesini gizle
                if (typingIndicator) {
                    typingIndicator.classList.add('hidden');
                }
                
                // Kullanıcıya hata mesajı göster
                addMessage('Bağlantı hatası oluştu. Lütfen internet bağlantınızı kontrol edin ve tekrar deneyin.', 'ai');
            }
        }

        function addMessage(message, sender) {
            if (!messagesContainer) {
                console.error('Mesaj konteyneri bulunamadı!');
                return;
            }
            
            // Mesaj kontrolü
            if (!message) {
                message = sender === 'user' ? 
                    'Mesaj gönderilirken bir sorun oluştu.' : 
                    'Yanıt alınamadı. Lütfen tekrar deneyin.';
            }
            
            // Mesaj işleme
            let messageText = '';
            
            try {
                // HTTP başlıkları içeriyor mu kontrol et (daha katı kontrol)
                if (typeof message === 'string' && 
                    (message.includes('HTTP/1.') || 
                     message.includes('Cache-Control:') || 
                     message.includes('Content-Type:') || 
                     message.includes('Date:'))) {
                    
                    console.log("HTTP başlıkları algılandı, temizleniyor...");
                    
                    // JSON başlangıcını bul
                    const jsonStart = message.indexOf('{');
                    if (jsonStart > -1) {
                        try {
                            // JSON kısmını çıkar ve parse et
                            const jsonText = message.substring(jsonStart);
                            const jsonData = JSON.parse(jsonText);
                            
                            // JSON yanıtından mesajı çıkart
                            if (jsonData.response) {
                                messageText = jsonData.response;
                            } else {
                                // Başka alanları da kontrol et
                                messageText = jsonData.message || jsonData.error || "Yanıt işlenemedi.";
                            }
                        } catch (jsonError) {
                            console.error("JSON parse hatası:", jsonError);
                            // JSON parse edilemezse, HTTP başlıklarını atlayarak içeriği göster
                            const messageLines = message.split('\n');
                            // HTTP başlıklarını atla (genelde ilk 5 satır)
                            if (messageLines.length > 5) {
                                messageText = messageLines.slice(5).join('\n').trim();
                            } else {
                                messageText = message;
                            }
                        }
                    } else {
                        // JSON bulunamadı, HTTP başlıklarını atlayarak içeriği göster
                        const messageLines = message.split('\n');
                        if (messageLines.length > 5) {
                            messageText = messageLines.slice(5).join('\n').trim();
                        } else {
                            messageText = message;
                        }
                    }
                }
                // String olarak JSON mu kontrol et
                else if (typeof message === 'string' && 
                         (message.trim().startsWith('{') || message.trim().startsWith('['))) {
                    try {
                        // JSON olarak parse et
                        const jsonData = JSON.parse(message);
                        
                        // JSON yapısını kontrol et
                        if (jsonData.response) {
                            messageText = jsonData.response;
                        } else if (jsonData.original && jsonData.original.response) {
                            messageText = jsonData.original.response;
                        } else if (jsonData.message) {
                            messageText = jsonData.message;
                        } else {
                            // Diğer olası alanları kontrol et ve varsayılan mesaja dön
                            messageText = "Yanıt içeriği alınamadı.";
                        }
                    } catch (jsonError) {
                        console.error("JSON parse hatası:", jsonError);
                        messageText = message;
                    }
                }
                // Obje olarak mı geldi?
                else if (typeof message === 'object') {
                    // Doğrudan obje olarak gelmişse
                    if (message.response) {
                        messageText = message.response;
                    } else if (message.message) {
                        messageText = message.message;
                    } else {
                        try {
                            messageText = JSON.stringify(message);
                        } catch (e) {
                            messageText = "Mesaj içeriği gösterilemiyor.";
                        }
                    }
                } else {
                    // Normal metin
                    messageText = message;
                }
                
                // Fazla boşlukları temizle
                if (typeof messageText === 'string') {
                    messageText = messageText.trim();
                }
                
                // Eğer hala JSON string gibi görünüyorsa son bir kontrol daha yap
                if (typeof messageText === 'string' && 
                   (messageText.trim().startsWith('{') || messageText.trim().startsWith('['))) {
                    try {
                        const finalJson = JSON.parse(messageText);
                        if (finalJson.response) {
                            messageText = finalJson.response;
                        }
                    } catch (e) {
                        // Hata olursa mevcut metni koru
                    }
                }
            } catch (e) {
                console.error('Mesaj işleme hatası:', e);
                messageText = message;
            }
            
            console.log("Son mesaj:", messageText);
            
            // İkinci rastgele cümleyi kaldır (varsa)
            if (typeof messageText === 'string' && messageText.includes('Farklı bir açıdan bakarsak')) {
                const parts = messageText.split('Farklı bir açıdan bakarsak');
                messageText = parts[0].trim();
            }
            
            // Mesaj metninde satır sonlarını <br> etiketlerine dönüştür
            messageText = String(messageText).replace(/\n/g, '<br>');
            
            // XSS koruması için basit bir metin temizleme (HTML entity dönüşümü)
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            // <br> etiketlerini koruyarak HTML'i escape et
            let safeText = escapeHtml(messageText).replace(/&lt;br&gt;/g, '<br>');
            
            const messageDiv = document.createElement('div');
            messageDiv.className = 'flex items-start ' + (sender === 'user' ? 'justify-end' : '');
            
            const icon = sender === 'user' ? 'user' : 'robot';
            const bgColor = sender === 'user' ? 'bg-green-100' : 'bg-blue-100';
            const iconBg = sender === 'user' ? 'bg-green-500' : 'bg-blue-500';
            
            messageDiv.innerHTML = `
                <div class="flex items-start ${sender === 'user' ? 'flex-row-reverse' : ''}">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 rounded-full ${iconBg} flex items-center justify-center">
                            <i class="fas fa-${icon} text-white"></i>
                        </div>
                    </div>
                    <div class="${sender === 'user' ? 'mr-3' : 'ml-3'} ${bgColor} rounded-lg p-3 max-w-[70%]">
                        <p class="text-gray-800">${safeText}</p>
                    </div>
                </div>
            `;
            
            messagesContainer.appendChild(messageDiv);
            
            // Scroll to bottom
            setTimeout(() => {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }, 10);
        }

        // "Sone düşünüyor" animasyonu için özel fonksiyon
        function showThinkingAnimation() {
            if (!messagesContainer) return;
            
            // Önceki thinking animasyonunu kaldır (varsa)
            const existingThinking = document.getElementById('ai-thinking-message');
            if (existingThinking) {
                messagesContainer.removeChild(existingThinking);
            }
            
            // Yeni thinking animasyonu oluştur
            const thinkingDiv = document.createElement('div');
            thinkingDiv.id = 'ai-thinking-message';
            thinkingDiv.className = 'flex items-start';
            
            thinkingDiv.innerHTML = `
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center">
                            <i class="fas fa-robot text-white"></i>
                        </div>
                    </div>
                    <div class="ml-3 sone-thinking" style="display: block; margin: 0;">
                        <span>Sone düşünüyor</span>
                        <div class="dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>
            `;
            
            messagesContainer.appendChild(thinkingDiv);
            
            // Scroll to bottom
            setTimeout(() => {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }, 10);
        }

        // "Sone düşünüyor" animasyonunu kaldırmak için fonksiyon
        function hideThinkingAnimation() {
            const thinkingElement = document.getElementById('ai-thinking-message');
            if (thinkingElement && thinkingElement.parentNode) {
                thinkingElement.parentNode.removeChild(thinkingElement);
            }
        }

        function cleanResponseText(text) {
            if (!text) return "Yanıt alınamadı.";
            
            console.log("Temizlenmemiş yanıt:", text);
            
            // HTTP başlıklarını kontrol et
            if (typeof text === 'string' && text.match(/HTTP\/[0-9.]+\s+\d+\s+OK/i)) {
                console.log("HTTP başlıkları tespit edildi");
                
                // Gerçek içeriği bulmak için JSON ya da son satıra bak
                let contentMatch = text.match(/\{.*\}/s);
                if (contentMatch) {
                    // JSON içeriği
                    try {
                        const jsonData = JSON.parse(contentMatch[0]);
                        if (jsonData.response) {
                            text = jsonData.response;
                        } else if (jsonData.message) {
                            text = jsonData.message;
                        }
                    } catch (e) {
                        // JSON içeriği değilse, HTTP başlıklarından sonraki son satırı al
                        let lines = text.split('\n');
                        for (let i = lines.length - 1; i >= 0; i--) {
                            if (lines[i].trim().length > 0 && !lines[i].includes(':')) {
                                text = lines[i].trim();
                                break;
                            }
                        }
                    }
                } else {
                    // JSON bulunamadıysa, başlıkları atla
                    const lastLine = text.split('\n').pop().trim();
                    // Eğer son satır boş değilse kullan
                    if (lastLine && lastLine.length > 2 && !lastLine.includes(':')) {
                        text = lastLine;
                    }
                }
            }
            
            // String olarak JSON mu kontrol et
            if (typeof text === 'string' && text.trim().match(/^\{.*\}$/s)) {
                try {
                    const jsonData = JSON.parse(text);
                    if (jsonData.response) {
                        text = jsonData.response;
                    } else if (jsonData.message) {
                        text = jsonData.message;
                    }
                } catch (e) {
                    // JSON parse edilemedi, bu durumda metni olduğu gibi kullan
                }
            }
            
            // Object olduğunda
            if (typeof text === 'object') {
                if (text.response) {
                    text = text.response;
                } else if (text.message) {
                    text = text.message;
                } else {
                    text = JSON.stringify(text);
                }
            }

            // Duygu durumunu kaldır (happy X.X gibi formatlarda)
            if (typeof text === 'string') {
                text = text.replace(/\s+happy\s+[0-9.]+$/, '');
                text = text.replace(/\s+sad\s+[0-9.]+$/, '');
                text = text.replace(/\s+neutral\s+[0-9.]+$/, '');
                text = text.replace(/\s+curious\s+[0-9.]+$/, '');
            }
            
            // Fazla boşlukları temizle
            if (typeof text === 'string') {
                text = text.trim();
            }
            
            console.log("Temizlenmiş yanıt:", text);
            return text;
        }

        // AI'dan gelen kod için syntax highlighting işlevi
        function highlightCode(code, language) {
            // Basit bir syntax highlighting - prodüksiyon için bir kütüphane kullanmak daha iyi olur
            if (!code) return '';
            
            // Kod başlangıcında dil yorumunu kaldır
            code = code.replace(/\/\/ Dil: [a-zA-Z]+\n/, '');
            
            // Burada Prism.js veya Highlight.js gibi bir kütüphane entegre edilebilir
            return code;
        }
        
        // AI'dan gelen kodun dil bilgisini tespit et
        function detectLanguage(code) {
            // Kodun başında bir dil belirtimi var mı kontrol et
            const langMatch = code.match(/\/\/ Dil: ([a-zA-Z]+)/);
            if (langMatch && langMatch[1]) {
                const detectedLang = langMatch[1].toLowerCase();
                
                // Select opsiyonlarında bu dil var mı kontrol et
                const options = Array.from(codeLanguage.options);
                const option = options.find(opt => opt.value === detectedLang);
                
                if (option) {
                    return detectedLang;
                }
            }
            
            // Tespit edilemezse varsayılan JavaScript
            return 'javascript';
        }

        // Son mesajı güncelle
        function updateLastMessage(newText) {
            if (!messagesContainer) return;
            
            // Son mesajı bul
            const messages = messagesContainer.querySelectorAll('.flex.items-start');
            if (messages.length === 0) return;
            
            const lastMessage = messages[messages.length - 1];
            
            // AI mesajı olduğundan emin ol
            if (lastMessage.innerHTML.includes('fa-robot')) {
                // Mesaj içeriğini güncelle
                const messageContent = lastMessage.querySelector('p');
                if (messageContent) {
                    // XSS koruması için metin işleme
                    const div = document.createElement('div');
                    div.textContent = newText;
                    const safeText = div.innerHTML.replace(/\n/g, '<br>');
                    
                    messageContent.innerHTML = safeText;
                }
            }
        }
    });
</script>
@endsection 