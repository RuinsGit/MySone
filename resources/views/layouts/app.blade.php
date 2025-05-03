<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
    
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- SEO Meta Etiketleri -->
    <title>{{ isset($seoSettings) && $seoSettings->site_title ? $seoSettings->site_title : config('app.name') }} {{ isset($seoSettings) && $seoSettings->title_separator ? $seoSettings->title_separator : '|' }} @yield('title', isset($seoSettings) && $seoSettings->default_title ? $seoSettings->default_title : 'Yapay Zeka Asistan')</title>
    
    @if(isset($seoSettings))
        @if($seoSettings->meta_description)
            <meta name="description" content="{{ $seoSettings->meta_description }}">
        @endif
        
        @if($seoSettings->meta_keywords)
            <meta name="keywords" content="{{ $seoSettings->meta_keywords }}">
        @endif
        
        @if($seoSettings->google_verification)
            <meta name="google-site-verification" content="{{ $seoSettings->google_verification }}">
        @endif
        
        @if($seoSettings->canonical_self)
            <link rel="canonical" href="{{ url()->current() }}">
        @endif
        
        @if($seoSettings->noindex || $seoSettings->nofollow)
            <meta name="robots" content="{{ $seoSettings->noindex ? 'noindex' : 'index' }},{{ $seoSettings->nofollow ? 'nofollow' : 'follow' }}">
        @endif
        
        <!-- Open Graph / Facebook Meta Etiketleri -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:title" content="{{ $seoSettings->site_title ?? config('app.name') }}">
        @if($seoSettings->meta_description)
            <meta property="og:description" content="{{ $seoSettings->meta_description }}">
        @endif
        @if($seoSettings->og_image)
            <meta property="og:image" content="{{ asset($seoSettings->og_image) }}">
        @endif
        
        <!-- Twitter Meta Etiketleri -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:url" content="{{ url()->current() }}">
        <meta name="twitter:title" content="{{ $seoSettings->site_title ?? config('app.name') }}">
        @if($seoSettings->meta_description)
            <meta name="twitter:description" content="{{ $seoSettings->meta_description }}">
        @endif
        @if($seoSettings->og_image)
            <meta name="twitter:image" content="{{ asset($seoSettings->og_image) }}">
        @endif
        
        <!-- Favicon -->
        @if($seoSettings->favicon)
            <link rel="icon" href="{{ asset($seoSettings->favicon) }}" type="image/x-icon">
            <link rel="shortcut icon" href="{{ asset($seoSettings->favicon) }}" type="image/x-icon">
            <link rel="apple-touch-icon" href="{{ asset($seoSettings->favicon) }}">
        @else
            <link rel="icon" href="{{ asset('images/sone.png') }}" type="image/png">
            <link rel="shortcut icon" href="{{ asset('images/sone.png') }}" type="image/png">
            <link rel="apple-touch-icon" href="{{ asset('images/sone.png') }}">
        @endif
        
        <!-- Head Script Eklentileri -->
        @if($seoSettings->head_scripts)
            {!! $seoSettings->head_scripts !!}
        @endif
        
        <!-- Google Analytics -->
        @if($seoSettings->google_analytics)
            {!! $seoSettings->google_analytics !!}
        @endif
        
        <!-- Google Tag Manager -->
        @if($seoSettings->google_tag_manager)
            {!! $seoSettings->google_tag_manager !!}
        @endif
    @else
        <link rel="icon" href="{{ asset('images/sone.png') }}" type="image/png">
    @endif

    <!-- Fontlar -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- Custom Styles -->
    <style>
        :root {
            --bs-primary: #6366f1;
            --bs-primary-rgb: 99, 102, 241;
            --bs-secondary: #ec4899;
            --bs-secondary-rgb: 236, 72, 153;
            --bs-primary-hover: #4f46e5;
            --bs-body-transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        /* Dark mode specific styles */
        [data-bs-theme="dark"] {
            --bs-body-bg: #0f172a;
            --bs-body-color: #e2e8f0;
            --bs-border-color: #2d3748;
            --bs-card-bg: #1e293b;
            --bs-card-cap-bg: #1a2234;
            --bs-navbar-color: #e2e8f0;
            --bs-navbar-hover-color: #fff;
            --bs-navbar-active-color: #fff;
            --bs-chat-user-bg: #4f46e5;
            --bs-chat-ai-bg: #1e293b;
            --bs-chat-user-color: #fff;
            --bs-chat-ai-color: #e2e8f0;
            --bs-input-bg: #1a2234;
            --bs-input-color: #e2e8f0;
            --bs-input-placeholder-color: #94a3b8;
            --bs-input-border-color: #2d3748;
        }
        
        /* Light mode specific styles */
        [data-bs-theme="light"] {
            --bs-body-bg: #f8fafc;
            --bs-body-color: #334155;
            --bs-border-color: #e2e8f0;
            --bs-card-bg: #ffffff;
            --bs-card-cap-bg: #f1f5f9;
            --bs-navbar-color: #334155;
            --bs-navbar-hover-color: #111827;
            --bs-navbar-active-color: #111827;
            --bs-chat-user-bg: #6366f1;
            --bs-chat-ai-bg: #f1f5f9;
            --bs-chat-user-color: #fff;
            --bs-chat-ai-color: #334155;
            --bs-input-bg: #ffffff;
            --bs-input-color: #334155;
            --bs-input-placeholder-color: #94a3b8;
            --bs-input-border-color: #e2e8f0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            transition: var(--bs-body-transition);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            -webkit-text-size-adjust: 100%;
            overflow: hidden;
        }
        
        #app {
            height: 100vh;
            width: 100%;
            overflow: hidden;
            position: relative;
        }
        
        main {
            height: 100%;
            padding: 0 !important;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        
        /* Sidebar styles */
        .sidebar {
            background-color: var(--bs-card-bg);
            border-right: 1px solid var(--bs-border-color);
            transition: transform 0.3s ease, width 0.3s ease, background-color 0.3s ease;
            overflow-y: auto;
            height: 100%;
            z-index: 1030;
            position: relative;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-logo {
            display: flex;
            align-items: center;
            padding: 1.25rem 1rem;
            border-bottom: 1px solid var(--bs-border-color);
        }
        
        .sidebar-logo img {
            height: 40px;
            width: auto;
            margin-right: 0.75rem;
            filter: drop-shadow(0 0 3px rgba(var(--bs-primary-rgb), 0.5));
            transition: filter 0.3s ease;
        }
        
        .sidebar-logo:hover img {
            filter: drop-shadow(0 0 5px rgba(var(--bs-primary-rgb), 0.8));
        }
        
        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--bs-primary), var(--bs-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
        }
        
        .sidebar-options {
            padding: 1rem;
        }
        
        .sidebar-option {
            margin-bottom: 1rem;
        }
        
        .option-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--bs-navbar-color);
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--bs-input-bg);
            transition: .4s;
            border-radius: 24px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: var(--bs-primary);
            box-shadow: 0 0 10px rgba(var(--bs-primary-rgb), 0.5);
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }
        
        .sidebar-select {
            width: 100%;
            padding: 0.75rem;
            background-color: var(--bs-input-bg);
            color: var(--bs-input-color);
            border: 1px solid var(--bs-input-border-color);
            border-radius: 0.5rem;
            appearance: none;
            cursor: pointer;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        .sidebar-select:focus {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 2px rgba(var(--bs-primary-rgb), 0.25);
            outline: none;
        }
        
        /* Chat area styles */
        .chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
            transition: background-color 0.3s ease;
            background-color: var(--bs-body-bg);
            position: relative;
        }
        
        .chat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            background-color: var(--bs-card-bg);
            border-bottom: 1px solid var(--bs-border-color);
            transition: background-color 0.3s ease;
            z-index: 1020;
        }
        
        .chat-header-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .chat-header-title h1 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            color: var(--bs-navbar-color);
        }
        
        .chat-header-controls {
            display: flex;
            gap: 0.5rem;
        }
        
        .header-button {
            background: transparent;
            color: var(--bs-navbar-color);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s ease;
            cursor: pointer;
        }
        
        .header-button:hover {
            background-color: var(--bs-input-bg);
            color: var(--bs-navbar-hover-color);
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            scroll-behavior: smooth;
        }
        
        .message {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            animation: fadeIn 0.3s ease;
        }
        
        .message-ai {
            align-self: flex-start;
        }
        
        .message-user {
            align-self: flex-end;
            flex-direction: row-reverse;
        }
        
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--bs-card-bg);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .message-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .message-avatar i {
            font-size: 1.25rem;
            color: var(--bs-navbar-color);
        }
        
        .message-content {
            max-width: 70%;
            padding: 1rem;
            border-radius: 1rem;
            overflow-wrap: break-word;
            word-wrap: break-word;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .message-ai .message-content {
            background-color: var(--bs-chat-ai-bg);
            color: var(--bs-chat-ai-color);
            border-top-left-radius: 0.25rem;
        }
        
        .message-user .message-content {
            background-color: var(--bs-chat-user-bg);
            color: var(--bs-chat-user-color);
            border-top-right-radius: 0.25rem;
        }
        
        .input-container {
            padding: 1rem;
            background-color: var(--bs-card-bg);
            border-top: 1px solid var(--bs-border-color);
            transition: background-color 0.3s ease;
        }
        
        .input-wrapper {
            display: flex;
            align-items: center;
            background-color: var(--bs-input-bg);
            border: 1px solid var(--bs-input-border-color);
            border-radius: 1.5rem;
            overflow: hidden;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        .input-wrapper:focus-within {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 2px rgba(var(--bs-primary-rgb), 0.25);
        }
        
        .message-input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: none;
            background-color: transparent;
            color: var(--bs-input-color);
            font-size: 1rem;
        }
        
        .message-input::placeholder {
            color: var(--bs-input-placeholder-color);
        }
        
        .message-input:focus {
            outline: none;
        }
        
        .send-button {
            background: linear-gradient(45deg, var(--bs-primary), var(--bs-secondary));
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .send-button:hover {
            transform: scale(1.05);
            box-shadow: 0 0 10px rgba(var(--bs-primary-rgb), 0.5);
        }
        
        /* Theme Toggle */
        .theme-toggle {
            cursor: pointer;
            padding: 0.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
            color: var(--bs-navbar-color);
            margin-top: 1rem;
        }
        
        .theme-toggle:hover {
            background-color: var(--bs-input-bg);
        }
        
        /* Mobile styles */
        @media (max-width: 991.98px) {
            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                bottom: 0;
                width: 280px;
                transform: translateX(-100%);
                z-index: 1040;
            }
            
            .sidebar-open .sidebar {
                transform: translateX(0);
            }
            
            .sidebar-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1035;
                display: none;
            }
            
            .sidebar-open .sidebar-backdrop {
                display: block;
            }
            
            .message-content {
                max-width: 85%;
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Code block styles */
        .code-block {
            margin-top: 1rem;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .code-header {
            padding: 0.5rem 1rem;
            background-color: rgba(0, 0, 0, 0.4);
            color: #e2e8f0;
            font-family: monospace;
            font-size: 0.875rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .code-content {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .code-content pre {
            margin: 0;
            padding: 1rem;
        }
        
        .hljs {
            background: #282c34;
            color: #abb2bf;
        }
    </style>

    @yield('styles')
</head>
<body>
    @if(isset($seoSettings) && $seoSettings->body_start_scripts)
        {!! $seoSettings->body_start_scripts !!}
    @endif
    
    <div id="app">
        <!-- Sidebar Backdrop (Mobile) -->
        <div class="sidebar-backdrop"></div>
        
        <main class="d-flex">
            @if(isset($showSidebar) && $showSidebar)
            <!-- Sidebar -->
            <div class="sidebar" id="sidebar">
                <div class="sidebar-logo">
                    <img src="{{ asset('images/sone.png') }}" alt="LIZZ AI Logo">
                    <span class="logo-text">LIZZ AI</span>
                </div>
                
                <div class="sidebar-options">
                    <div class="sidebar-option">
                        <span class="option-label">AI Modeli</span>
                        <select class="sidebar-select" id="model-selector">
                            <option value="soneai">LIZZ AI Basic</option>
                            <option value="gemini" selected>LIZZ AI Turbo</option>
                        </select>
                    </div>
                    
                    <div class="sidebar-option">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="option-label">Yaratıcı Mod</span>
                            <label class="toggle-switch">
                                <input type="checkbox" id="creative-toggle">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="sidebar-option">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="option-label">Kodlama Modu</span>
                            <label class="toggle-switch">
                                <input type="checkbox" id="coding-toggle">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="sidebar-option" id="language-settings" style="display: none;">
                        <span class="option-label">Kodlama Dili</span>
                        <select class="sidebar-select" id="code-language">
                            <option value="javascript">JavaScript</option>
                            <option value="php">PHP</option>
                            <option value="python">Python</option>
                            <option value="html">HTML</option>
                            <option value="css">CSS</option>
                            <option value="sql">SQL</option>
                        </select>
                    </div>
                    
                    <div class="sidebar-option mt-4">
                        <button class="btn btn-primary w-100" id="new-chat-btn">
                            <i class="fas fa-plus me-2"></i> Yeni Sohbet
                        </button>
                    </div>
                    
                    <div class="sidebar-option mt-5 text-center">
                        <div class="theme-toggle" id="theme-toggle">
                            <i class="fas fa-sun fa-lg" id="theme-icon"></i>
                        </div>
                        <div class="small text-muted mt-1">Tema Değiştir</div>
                    </div>
                </div>
            </div>
            @endif
            
            @yield('content')
        </main>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap JS Bundle (Popper + Bootstrap) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Theme Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Theme Toggle
            const themeToggle = document.getElementById('theme-toggle');
            if (themeToggle) {
                const themeIcon = document.getElementById('theme-icon');
                const htmlElement = document.documentElement;
                
                // Check for saved theme preference or respect OS preference
                const savedTheme = localStorage.getItem('theme');
                if (savedTheme) {
                    htmlElement.setAttribute('data-bs-theme', savedTheme);
                    updateThemeIcon(savedTheme);
                } else {
                    // If no saved preference, check OS preference
                    const prefersDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    const theme = prefersDarkMode ? 'dark' : 'light';
                    htmlElement.setAttribute('data-bs-theme', theme);
                    updateThemeIcon(theme);
                }
                
                themeToggle.addEventListener('click', function() {
                    const currentTheme = htmlElement.getAttribute('data-bs-theme');
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                    
                    htmlElement.setAttribute('data-bs-theme', newTheme);
                    localStorage.setItem('theme', newTheme);
                    updateThemeIcon(newTheme);
                });
                
                function updateThemeIcon(theme) {
                    if (theme === 'dark') {
                        themeIcon.classList.remove('fa-moon');
                        themeIcon.classList.add('fa-sun');
                    } else {
                        themeIcon.classList.remove('fa-sun');
                        themeIcon.classList.add('fa-moon');
                    }
                }
            }
            
            // Mobile sidebar toggle
            const menuToggle = document.getElementById('menu-toggle');
            const appElement = document.getElementById('app');
            const sidebarBackdrop = document.querySelector('.sidebar-backdrop');
            
            if (menuToggle) {
                menuToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    appElement.classList.toggle('sidebar-open');
                });
            }
            
            if (sidebarBackdrop) {
                sidebarBackdrop.addEventListener('click', function() {
                    appElement.classList.remove('sidebar-open');
                });
            }
            
            // Handle viewport height for mobile browsers
            function setVhVariable() {
                let vh = window.innerHeight * 0.01;
                document.documentElement.style.setProperty('--vh', `${vh}px`);
            }
            
            setVhVariable();
            window.addEventListener('resize', setVhVariable);
            window.addEventListener('orientationchange', function() {
                setTimeout(setVhVariable, 200);
            });
        });
    </script>


    
    @yield('scripts')
    
    @if(isset($seoSettings) && $seoSettings->body_end_scripts)
        {!! $seoSettings->body_end_scripts !!}
    @endif
</body>
</html> 