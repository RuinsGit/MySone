@extends('layouts.app')

@section('title', 'SoneAI - Yapay Zeka Sohbet')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<!-- Son sürüm ve daha iyi tema ile değiştiriyorum -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/vs2015.min.css">

@include('ai.includes.css.chatcss')

@endsection

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="app-container">
    <!-- Sol Sidebar - Büyük ekranlarda görünür -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo" >
                <img src="{{ asset('images/sone.png') }}" alt="LizzAI Logo" 
                   style="background-size:cover;
                   background-position: center;
                   background-repeat: no-repeat;
                   border-radius: 50%;
                   width: 35px;
                   height: 35px;
                   !important;
                   ">
                <span class="logo-text" style="color:rgb(255, 255, 255);">LizzAI</span>
            </div>
            
            <div class="model-selector-container">
                <select id="model-selector" class="model-selector">
                    <option value="soneai">LizzAI Basic</option>
                    <option value="gemini" selected>LizzAI Turbo</option>
                </select>
            </div>
        </div>
        
        <div class="sidebar-options">
            <div class="sidebar-option">
                <span>Yaratıcı Mod</span>
                <label class="toggle-switch">
                    <input type="checkbox" id="creative-toggle">
                    <span class="toggle-slider"></span>
                </label>
            </div>
            
            <div class="sidebar-option">
                <span>Kodlama Modu</span>
                <label class="toggle-switch">
                    <input type="checkbox" id="coding-toggle">
                    <span class="toggle-slider"></span>
                </label>
            </div>
            
            <div id="language-settings" class="sidebar-option" style="display: none;">
                <span>Kodlama Dili</span>
                <select id="code-language" class="sidebar-select">
                    <option value="javascript">JavaScript</option>
                    <option value="php">PHP</option>
                    <option value="python">Python</option>
                    <option value="html">HTML</option>
                    <option value="css">CSS</option>
                    <option value="sql">SQL</option>
                </select>
            </div>
            
            <div class="sidebar-option mt-6">
                <button id="new-chat-btn" class="gradient-btn w-full">
                    <i class="fas fa-plus mr-2"></i> Yeni Sohbet
                </button>
            </div>
        </div>
    </div>

    <!-- Sağ Alan - Ana İçerik -->
    <div class="main-content">
        <!-- Header -->
        <header class="chat-header">
            <div class="chat-header-title">
                <div class="chat-logo">
                    <img src="{{ asset('images/sone.png') }}" alt="LizzAI Logo" width="32" height="32">
                </div>
                <h1 id="chat-title">LizzAI</h1>
            </div>
            
            <div class="chat-controls">
                @auth
                <div class="user-dropdown">
                    <div class="user-dropdown-toggle" id="userDropdownToggle">
                        <div class="user-avatar">
                            @if(auth()->user()->avatar)
                                <img src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                            @else
                                {{ substr(auth()->user()->name, 0, 1) }}
                            @endif
                        </div>
                        <span>{{ auth()->user()->name }}</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="user-dropdown-menu" id="userDropdownMenu">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="user-dropdown-item logout">
                                <i class="fas fa-sign-out-alt"></i>
                                Çıkış Yap
                            </button>
                        </form>
                    </div>
                </div>
                @endauth
                
                <button id="menu-toggle" class="menu-toggle-btn">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </header>

        <!-- Messages -->
        <div id="messages" class="chat-messages-container">
            <!-- AI Message -->
            <div class="message message-ai">
                <div class="message-avatar">
                <img src="{{ asset('images/sone.png') }}" alt="LizzAI Logo" 
                   style="background-size:cover;
                   background-position: center;
                   background-repeat: no-repeat;
                   border-radius: 50%;
                    width: 90%;
                   height: 90%;
                   !important;
                   ">
                </div>
                <div class="message-sender-name">LizzAI</div>
                <div class="message-content">
                    <p>Merhaba! Ben LizzAI. Size nasıl yardımcı olabilirim?</p>
                </div>
            </div>
            
            <!-- Thinking animation placeholder - Mesaj akışının içinde -->
            <div id="ai-thinking" class="message message-ai ai-thinking-wrapper" style="display: none;">
                <div class="message-avatar ai-avatar-pulse">
                    <img src="{{ asset('images/sone.png') }}" alt="LizzAI Logo" 
                        style="background-size:cover;
                        background-position: center;
                        background-repeat: no-repeat;
                        border-radius: 50%;
                        width: 28   px;
                        height: 28px;
                        !important;">
                </div>
                <div class="ai-thinking-content">
                    <div class="ai-thinking-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="input-container">
            <div class="input-wrapper">
                <button id="voice-input-btn" class="voice-input-btn" type="button" title="Sesli Mesaj Gönder">
                    <i class="fas fa-microphone"></i>
                </button>
                <input type="text" 
                    id="message-input" 
                    class="message-input"
                    placeholder="Mesajınızı yazın...">
                <button id="send-message" class="send-button" type="button">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Settings Panel - Mobilde gösterilen ayarlar paneli -->
    <div id="settings-panel" class="settings-panel">
        <div class="settings-header">
            <div class="settings-title">Ayarlar</div>
            <button id="close-settings" class="settings-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="settings-section">
            <div class="settings-section-title">AI Modeli</div>
            <select id="mobile-model-selector" class="settings-select">
                <option value="soneai">LizzAI Basic</option>
                <option value="gemini" selected>LizzAI Turbo</option>
            </select>
        </div>
        
        <div class="settings-section">
            <div class="settings-section-title">Özellikler</div>
            
            <div class="settings-option">
                <div class="settings-option-label">Yaratıcı Mod</div>
                <label class="settings-switch">
                    <input type="checkbox" id="mobile-creative-toggle">
                    <span class="switch-slider"></span>
                </label>
            </div>
            
            <div class="settings-option">
                <div class="settings-option-label">Kodlama Modu</div>
                <label class="settings-switch">
                    <input type="checkbox" id="mobile-coding-toggle">
                    <span class="switch-slider"></span>
                </label>
            </div>
        </div>
        
        <div class="settings-section" id="mobile-language-settings" style="display: none;">
            <div class="settings-section-title">Kodlama Dili</div>
            <select id="mobile-code-language" class="settings-select">
                <option value="javascript">JavaScript</option>
                <option value="php">PHP</option>
                <option value="python">Python</option>
                <option value="html">HTML</option>
                <option value="css">CSS</option>
                <option value="sql">SQL</option>
                <option value="csharp">C#</option>
                <option value="java">Java</option>
                <option value="kotlin">Kotlin</option>
                <option value="swift">Swift</option>
                <option value="ruby">Ruby</option>
                <option value="go">Go</option>
                <option value="react">React</option>
                <option value="vue">Vue</option>
                <option value="angular">Angular</option>
                <option value="nodejs">Node.js</option>
                <option value="express">Express</option>
                <option value="django">Django</option>
                
                
            </select>
        </div>
        
        <div class="settings-section">
            <button id="mobile-new-chat-btn" class="gradient-btn w-full">
                <i class="fas fa-plus mr-2"></i> Yeni Sohbet
            </button>
        </div>
    </div>
    
    <div id="settings-overlay" class="settings-overlay"></div>

    <!-- Sesli Sohbet Popup -->
    <div id="voice-popup" class="voice-popup">
        <div class="voice-header">
            <div class="voice-title">Sesli Asistan</div>
            <button id="close-voice" class="voice-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="voice-content">
            <div id="voice-visualizer" class="voice-visualizer">
                <div class="voice-waves">
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <button id="voice-mic-btn" class="voice-mic-btn">
                    <i class="fas fa-microphone"></i>
                </button>
            </div>
            
            <div id="voice-status" class="voice-status">
                Mikrofona tıklayarak konuşmaya başlayabilirsiniz.
            </div>
            
            <div id="voice-conversation" class="voice-conversation">
                <div id="voice-message" class="voice-message">
                    <!-- Burada sesli sohbet mesajları görünecek -->
                </div>
            </div>
            
            <div class="voice-controls">
                <button id="voice-continuous-btn" class="voice-control-btn active">
                    <i class="fas fa-infinity"></i> Sürekli Konuşma
                </button>
            </div>
        </div>
    </div>

    <div id="voice-overlay" class="voice-overlay"></div>
</div>
@endsection

@section('scripts')
<!-- Highlight.js'in en son sürümünü yükle - tüm dil desteğiyle -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/typescript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/html.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/json.min.js"></script>

@include('ai.includes.js.chatjs')

@endsection 