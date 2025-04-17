@extends('layouts.app')

@section('title', 'SoneAI - Yapay Zeka Sohbet')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/atom-one-dark.min.css">
<style>
    :root {
        --ai-primary:rgb(64, 26, 233);
        --ai-primary-light:rgb(10, 76, 161);
        --ai-primary-dark:rgb(17, 46, 85);
        --ai-secondary: #5a9676;
        --ai-accent: #b07d48;
        --ai-dark: #2c3036;
        --ai-light: #f5f7fa;
        --ai-message-bg: #edf2fa;
        --ai-user-message-bg: #eef6f2;
        --ai-code-bg: #282c34;
        --ai-sidebar-bg: #2c3036;
        --ai-sidebar-text: #e1e1e1;
        --ai-sidebar-hover: #383d45;
        --ai-editor-bg: #1e1e1e;
        --ai-border-color: #e2e8f0;
        --ai-text-dark: #404550;
        --ai-text-light: #6e7281;
        --mobile-max-width: 768px;
    }
    
    body {
        background-color: #f5f7fa;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        color: var(--ai-text-dark);
        -webkit-text-size-adjust: 100%; /* Safari için metin boyutu ayarlaması */
    }
    
    /* ===== Medya Sorguları ===== */
    @media (min-width: 768px) {
        .app-container {
            flex-direction: row !important;
            max-width: 1200px;
            margin: 2rem auto;
            height: calc(100vh - 4rem) !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            overflow: hidden;
        }
        
        /* Sol sidebar stilleri */
        .sidebar {
            display: flex;
            flex-direction: column;
            width: 280px;
            background-color: var(--ai-sidebar-bg);
            border-right: 1px solid #e2e8f0;
            border-radius: 12px 0 0 12px;
            color: var(--ai-sidebar-text);
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
            font-size: 1.25rem;
            font-weight: bold;
            color: var(--ai-primary);
        }
        
        .sidebar-logo i {
            font-size: 1.5rem;
        }
        
        .model-selector-container {
            margin-top: 1rem;
        }
        
        .model-selector {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background-color: white;
            font-size: 0.9rem;
            outline: none;
        }
        
        .sidebar-options {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }
        
        .sidebar-option {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .sidebar-select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin-top: 0.5rem;
            background-color: white;
            font-size: 0.9rem;
            outline: none;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 44px;
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
            background-color: #9aa0a8;
            transition: .3s;
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
            transition: .3s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: var(--ai-primary);
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(20px);
        }
        
        /* Ana içerik alanı stilleri */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: #ffffff;
            height: 100%;
            max-height: 100vh;
            overflow: hidden;
        }
        
        .chat-header {
            border-radius: 0;
            padding: 1.25rem 2rem;
            background-color: #ffffff;
            border-bottom: 1px solid var(--ai-border-color);
        }
        
        .chat-header-title h1 {
            font-size: 1.5rem;
        }
        
        .chat-logo {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background-color: rgb(26 33 44 / 95%);
        }
        
        .input-container {
            position: relative;
            bottom: auto;
            left: auto;
            right: auto;
            border-radius: 0;
            border-top: 1px solid var(--ai-border-color);
            padding: 0.6rem 1.5rem;
            box-shadow: none;
            margin-top: auto;
            background-color: #ffffff;
        }
        
        .chat-messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            padding-bottom: 1rem;
            background-color: var(--ai-light);
        }
        
        .message {
            max-width: 70%;
        }
        
        .input-wrapper {
            max-width: none;
            height: 42px;
            margin: 0;
            border-radius: 22px;
            padding: 0.35rem 0.7rem;
            width: auto;
            background-color: #edf0f5;
            border: 1px solid #dde4ee;
        }
        
        .input-wrapper:focus-within {
            box-shadow: 0 1px 5px rgba(72, 114, 176, 0.15);
            border-color: #d6e0f0;
            background-color: #eef2f9;
        }
        
        .message-input {
            font-size: 0.92rem;
            padding: 0.35rem 0.6rem;
            width: auto;
            max-width: calc(100% - 40px);
            height: 26px;
            color: var(--ai-text-dark);
        }
        
        .message-input::placeholder {
            color: var(--ai-text-light);
        }
        
        .send-button {
            width: 32px;
            height: 32px;
            min-width: 32px;
            font-size: 0.85rem;
            background-color: var(--ai-primary);
        }
        
        .send-button:hover {
            background-color: var(--ai-primary-light);
        }
        
        .code-block {
            margin: 1.5rem 0;
        }
        
        .code-header {
            padding: 0.75rem 1.25rem;
        }
        
        .code-footer {
            padding: 0.75rem 1.25rem;
        }
        
        .code-button {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        
        /* Mobil ayarlar panelini masaüstünde gizle */
        #toggle-settings, .settings-panel, .settings-overlay {
            display: none;
        }
    }
    
    /* ===== Mobil Düzen ===== */
    @media (max-width: 767px) {
        :root {
            --safe-area-inset-bottom: env(safe-area-inset-bottom, 0);
            --mobile-app-bg: #1e1f27;
            --mobile-card-bg: #27293a;
            --mobile-input-bg: #33364c;
            --mobile-highlight: rgba(88, 132, 201, 0.8);
            --mobile-text: #e2e8f6;
            --mobile-text-light: #abb0c7;
            --mobile-header-height: 60px;
            --mobile-footer-height: 74px;
            --glass-blur: 10px;
        }
        
        body {
            background-color: var(--mobile-app-bg);
            color: var(--mobile-text);
        }
        
        .app-container {
            height: 100vh !important;
            max-height: 100vh !important;
            margin: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            background-color: var(--mobile-app-bg);
            color: var(--mobile-text);
        }
        
        .sidebar {
            display: none;
        }
        
        .main-content {
            width: 100%;
            height: 100vh;
            height: calc(var(--vh, 1vh) * 100);
            border-radius: 0;
            overflow: hidden;
            background-color: var(--mobile-app-bg) !important;
            display: flex;
            flex-direction: column;
        }
        
        /* Header */
        .chat-header {
            padding: 0 1rem;
            position: sticky;
            top: 0;
            background-color: rgba(30, 31, 39, 0.85);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            z-index: 30;
            height: var(--mobile-header-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.07);
        }
        
        .chat-header-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .chat-header-title h1 {
            font-size: 1.25rem;
            font-weight: 600;
            background: linear-gradient(90deg, #759bef, #6884f1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
            margin: 0;
        }
        
        .chat-logo {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: rgb(26 33 44 / 95%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            box-shadow: 0 3px 10px rgba(88, 130, 239, 0.35);
            position: relative;
            overflow: hidden;
        }
        
        .chat-logo::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 65% 35%, rgba(255, 255, 255, 0.2) 0%, transparent 70%);
        }
        
        .model-badge {
            margin-left: 0.75rem;
            padding: 0.35rem 0.75rem;
            background: linear-gradient(to right, rgba(87, 130, 239, 0.15), rgba(102, 95, 245, 0.2));
            color: #759bef;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
            border: 1px solid rgba(88, 130, 239, 0.25);
        }
        
        .model-badge i {
            font-size: 0.75rem;
        }
        
        #toggle-settings {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(87, 130, 239, 0.15), rgba(102, 95, 245, 0.2));
            color: #759bef;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(88, 130, 239, 0.25);
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        
        #toggle-settings:active {
            transform: scale(0.95);
            background: linear-gradient(135deg, rgba(87, 130, 239, 0.2), rgba(102, 95, 245, 0.25));
        }
        
        /* Mesaj Alanı */
        .chat-messages-container {
            flex: 1;
            padding: 0.75rem;
            background-color: var(--mobile-app-bg);
            overflow-y: auto;
            padding-bottom: 1rem;
            -webkit-overflow-scrolling: touch;
        }
        
        .message {
            max-width: 85%;
            margin-bottom: 1rem;
            animation: none;
            position: relative;
            transform: none;
            opacity: 1;
        }
        
        .message-ai {
            margin-right: auto;
            margin-left: 0;
            animation: messageFadeInLeft 0.3s ease forwards;
            opacity: 0;
            transform: translateX(-10px);
        }
        
        .message-user {
            margin-left: auto;
            margin-right: 0;
            animation: messageFadeInRight 0.3s ease forwards;
            opacity: 0;
            transform: translateX(10px);
        }
        
        .message-content {
            padding: 0.8rem 1rem;
            border-radius: 18px;
            line-height: 1.5;
            font-size: 0.95rem;
            box-shadow: none;
            position: relative;
        }
        
        .message-ai .message-content {
            background-color: var(--mobile-card-bg);
            color: var(--mobile-text);
            border-bottom-left-radius: 5px;
        }
        
        .message-user .message-content {
            background: linear-gradient(135deg, #054640, #054640);
            color: white !important;
            border-bottom-right-radius: 5px;
        }
        
        .message-user .message-content p {
            color: white !important;
        }
        
        .message-content p {
            margin: 0;
            padding: 0;
        }
        
        .message-avatar {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            margin-bottom: 6px;
            color: white;
            box-shadow: none;
        }
        
        .message-ai .message-avatar {
            background: linear-gradient(135deg, #5782ef, #665ff5);
            margin-right: auto;
        }
        
        .message-user .message-avatar {
            background: linear-gradient(135deg, #054640, #054640);
            margin-left: auto;
        }
        
        /* Giriş Alanı */
        .input-container {
            position: sticky;
            bottom: 0;
            padding: 0.9rem 0.75rem;
            padding-bottom: calc(0.9rem + var(--safe-area-inset-bottom));
            background-color: rgba(30, 31, 39, 0.95);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            z-index: 30;
            border-top: 1px solid rgba(255, 255, 255, 0.07);
            height: var(--mobile-footer-height);
            box-sizing: border-box;
            display: flex;
            align-items: center;
        }
        
        .input-wrapper {
            height: 48px;
            margin: 0;
            background: linear-gradient(90deg, rgba(41, 45, 62, 0.9), rgba(45, 50, 80, 0.85), rgba(41, 45, 62, 0.9));
            background-size: 200% 100%;
            animation: gradientFlow 8s linear infinite;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 25px;
            display: flex;
            align-items: center;
            padding: 0 0.75rem;
            width: 100%;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            overflow: hidden;
            position: relative;
        }
        
        .input-wrapper::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent,
                rgba(87, 132, 255, 0.1),
                rgba(87, 132, 255, 0.2),
                rgba(87, 132, 255, 0.1),
                transparent
            );
            animation: lightSweep 5s infinite ease-in-out;
            pointer-events: none;
        }
        
        .input-wrapper:focus-within {
            background: linear-gradient(90deg, rgba(45, 50, 80, 0.95), rgba(50, 60, 95, 0.9), rgba(45, 50, 80, 0.95));
            background-size: 200% 100%;
            border-color: rgba(87, 132, 255, 0.5);
            box-shadow: 0 2px 20px rgba(87, 132, 255, 0.2);
            transform: translateY(-2px);
        }
        
        .input-wrapper:focus-within::before {
            animation: lightSweep 3s infinite ease-in-out;
        }
        
        .message-input {
            flex: 1;
            border: none;
            background: transparent;
            color: rgba(255, 255, 255, 0.9);
            padding: 0.6rem;
            height: 100%;
            font-size: 0.95rem;
            width: calc(100% - 48px);
        }
        
        .message-input::placeholder {
            color: rgba(180, 185, 210, 0.6);
            transition: all 0.3s ease;
        }
        
        .message-input:focus {
            outline: none;
        }
        
        .message-input:focus::placeholder {
            color: rgba(180, 185, 210, 0.4);
            transform: translateX(5px);
        }
        
        .send-button {
            width: 40px;
            height: 40px;
            min-width: 40px;
            background: linear-gradient(135deg, #5782ef, #665ff5);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
            box-shadow: 0 3px 8px rgba(88, 130, 239, 0.35);
            margin-left: 0.5rem;
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
        }
        
        .send-button::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, rgba(255, 255, 255, 0.2) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .send-button:hover {
            transform: scale(1.05);
            background: linear-gradient(135deg, #6691f1, #7771f7);
        }
        
        .send-button:active {
            transform: scale(0.95);
            box-shadow: 0 2px 5px rgba(88, 130, 239, 0.3);
        }
        
        .send-button:active::after {
            opacity: 1;
        }
        
        @keyframes gradientFlow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        @keyframes lightSweep {
            0% { left: -100%; }
            50% { left: 100%; }
            100% { left: 100%; }
        }
        
        /* Düşünme Animasyonu */
        .ai-thinking {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 0.9rem 1.2rem;
            background-color: var(--mobile-card-bg);
            border-radius: 18px;
            border-bottom-left-radius: 5px;
            margin-bottom: 1rem;
            max-width: 150px;
            box-shadow: none;
        }
        
        .ai-thinking span {
            width: 7px;
            height: 7px;
            background: linear-gradient(135deg, #5782ef, #665ff5);
            border-radius: 50%;
            display: inline-block;
            animation: pulseThinking 1.5s infinite ease-in-out both;
        }
        
        .ai-thinking span:nth-child(1) { animation-delay: -0.3s; }
        .ai-thinking span:nth-child(2) { animation-delay: -0.15s; }
        .ai-thinking span:nth-child(3) { animation-delay: 0s; }
        
        /* Settings Panel */
        .settings-panel {
            background-color: var(--mobile-card-bg);
            border-top-left-radius: 20px;
            border-bottom-left-radius: 20px;
            padding: 1.5rem;
        }
        
        .settings-header {
            margin-bottom: 2rem;
        }
        
        .settings-title {
            font-weight: 600;
            color: var(--mobile-text);
        }
        
        .settings-close {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(87, 130, 239, 0.15);
            color: #759bef;
            border: 1px solid rgba(88, 130, 239, 0.25);
        }
        
        .settings-section {
            background-color: rgba(39, 41, 58, 0.5);
            border-radius: 16px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }
        
        .settings-section-title {
            color: var(--mobile-text);
            margin-bottom: 1.25rem;
        }
        
        .settings-option-label {
            color: var(--mobile-text);
        }
        
        .settings-select {
            background-color: var(--mobile-input-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--mobile-text);
            border-radius: 12px;
        }
        
        /* Kod blokları */
        .code-block {
            border-radius: 14px;
            margin: 0.75rem 0;
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow: hidden;
        }
        
        .code-header {
            padding: 0.75rem 1rem;
            background-color: rgba(33, 37, 43, 0.95);
            font-size: 0.85rem;
        }
        
        .code-content {
            padding: 1rem;
            font-size: 0.85rem;
        }
        
        .code-footer {
            padding: 0.75rem 1rem;
            background-color: rgba(33, 37, 43, 0.95);
        }
        
        .code-button {
            background: linear-gradient(135deg, #5782ef, #665ff5);
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
        }
        
        .hljs {
            background-color: rgba(40, 44, 52, 0.95) !important;
            padding: 0.75rem;
            font-size: 0.85rem;
            border-radius: 8px;
        }
        
        /* Animasyonlar */
        @keyframes messageFadeInLeft {
            0% { opacity: 0; transform: translateX(-10px); }
            100% { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes messageFadeInRight {
            0% { opacity: 0; transform: translateX(10px); }
            100% { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes pulseThinking {
            0%, 80%, 100% { transform: scale(0.8); opacity: 0.6; }
            40% { transform: scale(1.2); opacity: 1; }
        }
    }
    
    /* iPhone X ve üstü için güvenli alan desteği */
    @supports (padding: max(0px)) {
        @media (max-width: 767px) {
            .input-container {
                padding-bottom: max(0.6rem, env(safe-area-inset-bottom));
                padding-left: max(0.75rem, env(safe-area-inset-left));
                padding-right: max(0.75rem, env(safe-area-inset-right));
            }
        }
    }
    
    .app-container {
        display: flex;
        flex-direction: column;
        height: 100vh;
        max-height: 100vh;
        width: 100%;
        overflow: hidden;
        position: relative;
    }
    
    .chat-header {
        background-color: white;
        padding: 1rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        z-index: 10;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .chat-header-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .chat-header-title h1 {
        font-size: 1.25rem;
        font-weight: bold;
        margin: 0;
    }
    
    .chat-logo {
        width: 30px;
        height: 30px;
        background-color: rgb(26 33 44 / 95%);

        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
    }
    
    .chat-controls {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    /* ===== Mesaj Alanı ===== */
    .chat-messages-container {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
        scroll-behavior: smooth;
        padding-bottom: 1.5rem;
        background-color: var(--ai-light);
        -webkit-overflow-scrolling: touch;
    }
    
    .message {
        max-width: 85%;
        margin-bottom: 1rem;
        animation: fadeIn 0.3s ease-in-out;
        position: relative;
        transform-origin: left center;
        opacity: 0;
        animation: messageFadeIn 0.3s ease-out forwards;
    }
    
    .message-ai {
        margin-right: auto;
        margin-left: 0;
    }
    
    .message-user {
        margin-left: auto;
        margin-right: 0;
        transform-origin: right center;
    }
    
    .message-content {
        padding: 0.75rem 1rem;
        border-radius: 18px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05), 0 2px 8px rgba(0, 0, 0, 0.02);
        position: relative;
        overflow: hidden;
        line-height: 1.5;
        font-size: 0.97rem;
    }
    
    .message-ai .message-content {
        background-color: var(--ai-message-bg);
        color: var(--ai-text-dark);
        border-bottom-left-radius: 5px;
    }
    
    .message-user .message-content {
        background: linear-gradient(135deg, #054640, #054640);
        color: white !important;
        border-bottom-right-radius: 5px;
    }
    
    .message-user .message-content p {
        color: white !important;
    }
    
    .message-content p {
        margin: 0;
        padding: 0;
    }
    
    .message-avatar {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        margin-bottom: 4px;
        color: white;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }
    
    .message-ai .message-avatar {
        background-color: var(--ai-primary);
        background-image: linear-gradient(135deg, var(--ai-primary), var(--ai-primary-dark));
        margin-right: auto;
    }
    
    .message-user .message-avatar {
        background-color: var(--ai-secondary);
        background-image: linear-gradient(135deg, var(--ai-secondary), #4d7f63);
        margin-left: auto;
    }
    
    /* ===== Giriş Alanı ===== */
    .input-container {
        position: relative;
        bottom: auto;
        left: auto;
        right: auto;
        padding: 0.6rem 1rem;
        background-color: #ffffff;
        box-shadow: 0 -1px 10px rgba(0, 0, 0, 0.05);
        z-index: 10;
        border-top: 1px solid var(--ai-border-color);
        margin-top: auto;
        transition: all 0.3s ease;
    }
    
    .input-wrapper {
        display: flex;
        align-items: center;
        background-color: #edf2fa;
        border-radius: 24px;
        padding: 0.45rem 0.8rem;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        max-width: 700px;
        margin: 0 auto;
        border: 1px solid #dde4ee;
        width: auto;
        height: 48px;
        transition: all 0.3s ease;
    }
    
    .input-wrapper:focus-within {
        box-shadow: 0 3px 15px rgba(72, 114, 176, 0.15);
        border-color: #bfd1f3;
        background-color: #f0f5ff;
        transform: translateY(-2px);
    }
    
    .message-input {
        flex: 1;
        border: none;
        background: transparent;
        padding: 0.45rem 0.6rem;
        outline: none;
        font-size: 0.98rem;
        line-height: 1.4;
        color: var(--ai-text-dark);
        width: auto;
        max-width: calc(100% - 50px);
        height: 28px;
    }
    
    .message-input::placeholder {
        color: #8e98a9;
        opacity: 0.85;
    }
    
    .send-button {
        width: 38px;
        height: 38px;
        min-width: 38px;
        border-radius: 50%;
        background-color: var(--ai-primary);
        background-image: linear-gradient(135deg, var(--ai-primary-light), var(--ai-primary));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-left: 0.4rem;
        font-size: 0.9rem;
        box-shadow: 0 2px 8px rgba(72, 114, 176, 0.25);
    }
    
    .send-button:hover {
        background-image: linear-gradient(135deg, var(--ai-primary), var(--ai-primary-dark));
        transform: scale(1.05);
        box-shadow: 0 3px 12px rgba(72, 114, 176, 0.35);
    }
    
    .send-button:active {
        transform: scale(0.95);
    }
    
    /* ===== Kod Düzeni ===== */
    .code-block {
        background-color: var(--ai-code-bg);
        border-radius: 8px;
        margin: 0.75rem 0;
        position: relative;
        overflow: hidden;
    }
    
    .code-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 1rem;
        background-color: #21252b;
        color: #e0e0e0;
        font-size: 0.8rem;
    }
    
    .code-content {
        padding: 1rem;
        overflow-x: auto;
        font-family: 'Fira Code', 'Consolas', monospace;
        font-size: 0.9rem;
    }
    
    .code-content pre {
        margin: 0;
        color: #abb2bf;
    }
    
    .code-footer {
        display: flex;
        justify-content: flex-end;
        padding: 0.5rem;
        background-color: #21252b;
        gap: 0.5rem;
    }
    
    .code-button {
        background-color: var(--ai-primary);
        color: white;
        border: none;
        border-radius: 4px;
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
        cursor: pointer;
    }
    
    .code-button:hover {
        background-color: var(--ai-primary-light);
    }
    
    /* ===== Düşünme Animasyonu ===== */
    .ai-thinking {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 0.6rem 1.1rem;
        background-color: var(--ai-message-bg);
        border-radius: 18px;
        margin-bottom: 1rem;
        animation: pulse 1.5s infinite;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05), 0 2px 8px rgba(0, 0, 0, 0.02);
        border-bottom-left-radius: 5px;
        max-width: 120px;
        opacity: 0;
        transform: translateY(10px);
        animation: thinkingAppear 0.3s forwards;
    }
    
    .ai-thinking span {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: var(--ai-primary);
        opacity: 0.7;
        display: inline-block;
        animation: typing 1.4s infinite ease-in-out both;
    }
    
    .ai-thinking span:nth-child(1) { animation-delay: -0.32s; }
    .ai-thinking span:nth-child(2) { animation-delay: -0.16s; }
    
    @keyframes typing {
        0%, 80%, 100% { transform: scale(0.7); opacity: 0.4; }
        40% { transform: scale(1); opacity: 1; }
    }
    
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(66, 133, 244, 0.2); }
        70% { box-shadow: 0 0 0 5px rgba(66, 133, 244, 0); }
        100% { box-shadow: 0 0 0 0 rgba(66, 133, 244, 0); }
    }
    
    @keyframes messageFadeIn {
        0% { opacity: 0; transform: translateY(10px) scale(0.98); }
        100% { opacity: 1; transform: translateY(0) scale(1); }
    }
    
    @keyframes thinkingAppear {
        0% { opacity: 0; transform: translateY(10px); }
        100% { opacity: 1; transform: translateY(0); }
    }
    
    /* ===== Ayarlar Paneli ===== */
    .settings-panel {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        width: 85%;
        max-width: 320px;
        background-color: #ffffff;
        box-shadow: -5px 0 25px rgba(0, 0, 0, 0.15);
        z-index: 100;
        transform: translateX(100%);
        transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        padding: 1.25rem 1.25rem;
        display: flex;
        flex-direction: column;
        overflow-y: auto;
        border-top-left-radius: 16px;
        border-bottom-left-radius: 16px;
    }
    
    .settings-panel.active {
        transform: translateX(0);
    }
    
    .settings-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.6);
        z-index: 99;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease;
        backdrop-filter: blur(2px);
        -webkit-backdrop-filter: blur(2px);
    }
    
    .settings-overlay.active {
        opacity: 1;
        visibility: visible;
    }
    
    .settings-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    
    .settings-title {
        font-weight: 600;
        font-size: 1.25rem;
        color: var(--ai-text-dark);
    }
    
    .settings-close {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background-color: #f0f4f9;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
        color: var(--ai-text-dark);
        transition: all 0.2s ease;
    }
    
    .settings-close:active {
        background-color: #e4ebf5;
        transform: scale(0.95);
    }
    
    .settings-section {
        margin-bottom: 1.75rem;
        background-color: #f7f9fc;
        padding: 1rem 1.25rem;
        border-radius: 12px;
    }
    
    .settings-section-title {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: var(--ai-text-dark);
    }
    
    .settings-option {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    
    .settings-option:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    
    .settings-option-label {
        font-size: 0.95rem;
        color: var(--ai-text-dark);
    }
    
    .settings-switch {
        position: relative;
        display: inline-block;
        width: 46px;
        height: 24px;
    }
    
    .settings-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .switch-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #c1c9d6;
        transition: .3s;
        border-radius: 24px;
    }
    
    .switch-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .3s;
        border-radius: 50%;
    }
    
    input:checked + .switch-slider {
        background-color: var(--ai-primary);
    }
    
    input:checked + .switch-slider:before {
        transform: translateX(22px);
    }
    
    .settings-select {
        background-color: white;
        border: 1px solid #dde4ee;
        padding: 0.7rem 0.75rem;
        border-radius: 10px;
        width: 100%;
        font-size: 0.95rem;
        outline: none;
        margin-top: 0.5rem;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 16px;
        padding-right: 2.5rem;
    }
    
    /* Mobil uyumlu kod özellikleri */
    .hljs {
        padding: 1em;
        border-radius: 8px;
        font-size: 0.9em;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    /* Görünürlük kontrolleri */
    .no-scroll {
        overflow: hidden;
    }
    
    /* Koyu tema ince ayarlar */
    @media (prefers-color-scheme: dark) {
        :root {
            --ai-primary:rgb(215, 92, 240);
            --ai-primary-light:rgb(10, 67, 160);
            --ai-primary-dark:rgb(15, 36, 70);
            --ai-secondary: #5a9676;
            --ai-accent: #b07d48;
            --ai-message-bg: #2a3547;
            --ai-user-message-bg: #054640;
            --ai-light: #1a212c;
            --ai-dark: #111827;
            --ai-text-dark: #e5e7eb;
            --ai-text-light: #9ca3af;
            --ai-border-color: #374151;
            --ai-sidebar-bg: #151c28;
            --ai-sidebar-text: #e1e1e1;
        }
        
        body {
            background-color: var(--ai-dark);
            color: var(--ai-text-dark);
        }
        
        .app-container {
            background-color: var(--ai-dark);
        }
        
        .sidebar {
            background-color: var(--ai-sidebar-bg) !important;
            border-right: 1px solid #2d3748 !important;
        }
        
        .sidebar-header {
            border-bottom: 1px solid #2d3748 !important;
        }
        
        .sidebar-option {
            color: var(--ai-text-dark);
        }
        
        .model-selector, .sidebar-select {
            background-color: rgba(45, 55, 72, 0.7) !important;
            border: 1px solid #4b5563 !important;
            color: var(--ai-text-dark) !important;
        }
        
        .main-content {
            background-color: var(--ai-light) !important;
        }
        
        .chat-header {
            background-color: rgba(26, 33, 44, 0.95) !important;
            border-bottom: 1px solid var(--ai-border-color) !important;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .chat-header h1 {
            color: var(--ai-text-dark) !important;
        }
        
        .chat-messages-container {
            background-color: var(--ai-light) !important;
        }
        
        .message-ai .message-content {
            background-color: var(--ai-message-bg) !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2), 0 2px 6px rgba(0, 0, 0, 0.1) !important;
        }
        
        .message-user .message-content {
            background-color: var(--ai-user-message-bg) !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2), 0 2px 6px rgba(0, 0, 0, 0.1) !important;
        }
        
        .ai-thinking {
            background-color: var(--ai-message-bg) !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2), 0 2px 6px rgba(0, 0, 0, 0.1) !important;
        }
        
        .input-container {
            background-color: rgba(26, 33, 44, 0.95) !important;
            border-top: 1px solid #2d3748 !important;
            box-shadow: 0 -1px 10px rgba(0, 0, 0, 0.2) !important;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .input-wrapper {
            background-color: rgba(45, 55, 72, 0.7) !important;
            border: 1px solid #3d4a61 !important;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2) !important;
        }
        
        .input-wrapper:focus-within {
            background-color: rgba(51, 62, 82, 0.8) !important;
            border-color: rgba(88, 132, 201, 0.5) !important;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.3) !important;
        }
        
        .message-input {
            color: var(--ai-text-dark) !important;
        }
        
        .message-input::placeholder {
            color: rgba(156, 163, 175, 0.8) !important;
        }
        
        .settings-panel {
            background-color: var(--ai-light) !important;
            box-shadow: -5px 0 25px rgba(0, 0, 0, 0.3) !important;
        }
        
        .settings-title, .settings-section-title, .settings-option-label {
            color: var(--ai-text-dark) !important;
        }
        
        .settings-close {
            background-color: rgba(45, 55, 72, 0.7) !important;
            color: var(--ai-text-dark) !important;
        }
        
        .settings-section {
            background-color: rgba(26, 32, 44, 0.5) !important;
        }
        
        .settings-option {
            border-color: rgba(255, 255, 255, 0.1) !important;
        }
        
        .settings-select {
            background-color: rgba(45, 55, 72, 0.7) !important;
            border: 1px solid #3d4a61 !important;
            color: var(--ai-text-dark) !important;
        }
        
        #toggle-settings {
            background-color: rgba(45, 55, 72, 0.7) !important;
            color: var(--ai-text-dark) !important;
        }
    }

    /* Model seçici ve mesaj alanı geliştirmeleri */
    .model-selector {
        width: 100%;
        padding: 0.75rem;
        border-radius: 10px;
        font-size: 0.95rem;
        outline: none;
        transition: all 0.2s ease;
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 16px;
        padding-right: 2.5rem;
    }

    .model-selector:hover, .model-selector:focus {
        border-color: var(--ai-primary);
        box-shadow: 0 0 0 2px rgba(72, 114, 176, 0.1);
    }

    /* Mesaj alanı geliştirmeleri */
    .input-container {
        position: relative;
        bottom: auto;
        left: auto;
        right: auto;
        padding: 0.6rem 1rem;
        background-color: white;
        box-shadow: 0 -1px 10px rgba(0, 0, 0, 0.05);
        z-index: 10;
        border-top: 1px solid var(--ai-border-color);
        margin-top: auto;
        transition: all 0.3s ease;
    }

    .input-wrapper {
        display: flex;
        align-items: center;
        background-color: #edf2fa;
        border-radius: 24px;
        padding: 0.45rem 0.8rem;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        max-width: 700px;
        margin: 0 auto;
        border: 1px solid #dde4ee;
        width: auto;
        height: 48px;
        transition: all 0.3s ease;
    }

    .input-wrapper:focus-within {
        box-shadow: 0 3px 15px rgba(72, 114, 176, 0.15);
        border-color: #bfd1f3;
        background-color: #f0f5ff;
        transform: translateY(-2px);
    }

    .message-input {
        flex: 1;
        border: none;
        background: transparent;
        padding: 0.45rem 0.6rem;
        outline: none;
        font-size: 0.98rem;
        line-height: 1.4;
        color: var(--ai-text-dark);
        width: auto;
        max-width: calc(100% - 50px);
        height: 28px;
    }

    .message-input::placeholder {
        color: #8e98a9;
        opacity: 0.85;
    }

    .send-button {
        width: 38px;
        height: 38px;
        min-width: 38px;
        border-radius: 50%;
        background-color: var(--ai-primary);
        background-image: linear-gradient(135deg, var(--ai-primary-light), var(--ai-primary));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-left: 0.4rem;
        font-size: 0.9rem;
        box-shadow: 0 2px 8px rgba(72, 114, 176, 0.25);
    }

    .send-button:hover {
        background-image: linear-gradient(135deg, var(--ai-primary), var(--ai-primary-dark));
        transform: scale(1.05);
        box-shadow: 0 3px 12px rgba(72, 114, 176, 0.35);
    }

    .send-button:active {
        transform: scale(0.95);
    }

    @media (min-width: 768px) {
        .input-container {
            padding: 0.75rem 1.5rem;
            box-shadow: none;
        }
        
        .input-wrapper {
            max-width: none;
        }
    }

    /* iPhone X ve daha yeni cihazlar için safe area desteği */
    @supports (padding: max(0px)) {
        @media (max-width: 767px) {
            :root {
                --safe-area-inset-bottom: env(safe-area-inset-bottom, 20px);
            }
            
            .app-container {
                padding-top: env(safe-area-inset-top);
                padding-bottom: var(--safe-area-inset-bottom);
            }
            
            .input-container {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                padding-bottom: max(1.5rem, var(--safe-area-inset-bottom));
                padding-left: max(1rem, env(safe-area-inset-left));
                padding-right: max(1rem, env(safe-area-inset-right));
                z-index: 100;
            }
            
            .chat-messages-container {
                margin-bottom: calc(var(--mobile-footer-height) + var(--safe-area-inset-bottom));
                padding-bottom: 2rem;
            }
        }
    }

    @media (max-width: 767px) {
        .input-container {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 0.8rem 0.75rem;
            background-color: rgba(30, 31, 39, 0.95);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            z-index: 30;
            border-top: 1px solid rgba(255, 255, 255, 0.07);
            box-sizing: border-box;
            display: flex;
            align-items: center;
            border-radius: 20px 20px 0 0;
            box-shadow: 0 -5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            margin-bottom: 0;
        }
        
        .input-container.keyboard-visible {
            transform: translateY(0);
        }
        
        .chat-messages-container {
            margin-bottom: calc(var(--mobile-footer-height) + var(--safe-area-inset-bottom, 20px));
            padding-bottom: 2.5rem;
            -webkit-overflow-scrolling: touch;
        }
        
        /* Büyük ekranlı telefonlar için ayarlar */
        @media (min-height: 800px) {
            .input-container {
                padding-bottom: max(1.2rem, var(--safe-area-inset-bottom));
            }
            
            .chat-messages-container {
                margin-bottom: calc(var(--mobile-footer-height) + 15px + var(--safe-area-inset-bottom, 20px));
            }
        }
    }
    
    /* Safari için ek düzeltmeler */
    @media not all and (min-resolution:.001dpcm) {
        @supports (-webkit-appearance:none) {
            .message-user .message-content {
                color: white !important;
            }
            
            .message-user .message-content p {
                color: white !important;
            }
            
            @media (max-width: 767px) {
                .message-user .message-content {
                    background-color: #054640 !important;
                    background-image: none !important;
                }
            }
        }
    }
    
    /* iOS Safari için ek düzeltmeler */
    @supports (-webkit-touch-callout: none) {
        .message-user .message-content {
            color: white !important;
        }
        
        .message-user .message-content p {
            color: white !important;
        }
        
        @media (max-width: 767px) {
            .message-user .message-content {
                background-color: #054640 !important;
                background-image: none !important;
            }
        }
    }

    /* Mobil Safari için düzeltmeler */
    @media (max-width: 767px) {
        @supports (-webkit-touch-callout: none) {
            .app-container {
                height: -webkit-fill-available !important;
            }
            
            .chat-messages-container {
                margin-bottom: calc(var(--mobile-footer-height) + 20px);
                position: relative;
                z-index: 20;
            }
            
            .input-container {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                padding-bottom: max(0.9rem, env(safe-area-inset-bottom, 20px));
                background-color: rgba(30, 31, 39, 0.95);
                -webkit-backdrop-filter: blur(var(--glass-blur));
                z-index: 40;
            }
            
            /* Safari için mesaj içeriği düzeltmeleri */
            .message-content {
                -webkit-box-sizing: border-box;
                box-sizing: border-box;
                width: auto;
            }
            
            /* Safari için animasyonları düzeltmeler */
            .message-ai {
                -webkit-animation: messageFadeInLeft 0.3s ease forwards;
            }
            
            .message-user {
                -webkit-animation: messageFadeInRight 0.3s ease forwards;
            }
            
            /* Kullanıcı mesajları metin rengi düzeltmesi */
            .message-user .message-content {
                color: white !important;
            }
            
            .message-user .message-content p {
                color: white !important;
            }
            
            /* Mesaj arka plan rengi düzeltmesi */
            .message-user .message-content {
                background-color: #054640 !important;
                background-image: linear-gradient(135deg, #054640, #054640) !important;
            }
            
            /* Güvenli alan iyileştirmeleri */
            .input-container {
                padding-bottom: max(0.9rem, env(safe-area-inset-bottom, 20px)) !important;
            }
            
            /* İçerik düzgün görüntüleme için ekstra önlem */
            body.safari-browser .message-user .message-content,
            body.safari-browser .message-user .message-content p {
                color: white !important;
            }
        }
    }
</style>
@endsection

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="app-container">
    <!-- Sol Sidebar - Büyük ekranlarda görünür -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo" >
                <img src="{{ asset('images/sone.png') }}" alt="SoneAI Logo" 
                   style="background-size:cover;
                   background-position: center;
                   background-repeat: no-repeat;
                   border-radius: 50%;
                   width: 35px;
                   height: 35px;
                   !important;
                   ">
                <span class="logo-text" style="color:rgb(255, 255, 255);">SoneAI</span>
            </div>
            
            <div class="model-selector-container">
                <select id="model-selector" class="model-selector">
                    <option value="soneai">SoneAI</option>
                    <option value="gemini" selected>SoneAI Turbo</option>
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
        </div>
    </div>

    <!-- Sağ Alan - Ana İçerik -->
    <div class="main-content">
        <!-- Header -->
        <header class="chat-header">
            <div class="chat-header-title">
                <div class="chat-logo" style="width: 35px; height: 35px;">
                   <img src="{{ asset('images/sone.png') }}" alt="SoneAI Logo" 
                   style="background-size:cover;
                   background-position: center;
                   background-repeat: no-repeat;
                   border-radius: 50%;
                   width: 35px;
                   height: 35px;
                   !important;
                   ">
                </div>
                <h1>SoneAI</h1>
                <div class="ml-4 px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">
                    <span id="model-name">SoneAI Turbo</span>
                </div>
            </div>
            <div class="chat-controls">
                <button id="toggle-settings" class="p-2 rounded-full hover:bg-gray-100" aria-label="Ayarlar">
                    <i class="fas fa-cog"></i>
                </button>
            </div>
        </header>

        <!-- Messages -->
        <div id="messages" class="chat-messages-container">
            <!-- AI Message -->
            <div class="message message-ai">
                <div class="message-avatar">
                <img src="{{ asset('images/sone.png') }}" alt="SoneAI Logo" 
                   style="background-size:cover;
                   background-position: center;
                   background-repeat: no-repeat;
                   border-radius: 50%;
                   width: 28px;
                   height: 28px;
                   !important;
                   ">
                </div>
                <div class="message-content">
                    <p>Merhaba! Ben SoneAI. Size nasıl yardımcı olabilirim?</p>
                </div>
            </div>
            
            <!-- Thinking animation placeholder -->
            <div id="ai-thinking" class="ai-thinking" style="display: none;">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>

        <!-- Input Area -->
        <div class="input-container">
            <div class="input-wrapper">
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
                <option value="soneai">SoneAI</option>
                <option value="gemini" selected>SoneAI Turbo</option>
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
            </select>
        </div>
    </div>
    
    <div id="settings-overlay" class="settings-overlay"></div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
<!-- Diller için ek paketler -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/languages/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/languages/php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/languages/python.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/languages/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/languages/html.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/languages/sql.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // DOM elemanlarını seç
        const messageInput = document.getElementById('message-input');
        const sendMessageBtn = document.getElementById('send-message');
        const messagesContainer = document.getElementById('messages');
        const aiThinking = document.getElementById('ai-thinking');
        const settingsPanel = document.getElementById('settings-panel');
        const settingsOverlay = document.getElementById('settings-overlay');
        const toggleSettings = document.getElementById('toggle-settings');
        const closeSettings = document.getElementById('close-settings');
        const inputContainer = document.querySelector('.input-container');
        const chatMessagesContainer = document.querySelector('.chat-messages-container');
        
        // Masaüstü kontrolleri
        const creativeToggle = document.getElementById('creative-toggle');
        const codingToggle = document.getElementById('coding-toggle');
        const codeLanguage = document.getElementById('code-language');
        const languageSettings = document.getElementById('language-settings');
        const modelSelector = document.getElementById('model-selector');
        
        // Mobil kontrolleri
        const mobileCreativeToggle = document.getElementById('mobile-creative-toggle');
        const mobileCodingToggle = document.getElementById('mobile-coding-toggle');
        const mobileCodeLanguage = document.getElementById('mobile-code-language');
        const mobileLangSettings = document.getElementById('mobile-language-settings');
        const mobileModelSelector = document.getElementById('mobile-model-selector');
        
        // Kullanıcı adı kontrolü
        const needsName = {{ $initialState['needs_name'] ? 'true' : 'false' }};
        let nameRequested = false;
        
        // İlk yükleme sırasında kullanıcı adı isteme
        if (needsName && !nameRequested) {
            setTimeout(() => {
                addMessage("Merhaba! Ben SoneAI. Sana nasıl hitap etmemi istersin?", 'ai');
                nameRequested = true;
                messageInput.placeholder = "Adınızı yazın...";
                messageInput.focus();
            }, 1000);
        }
        
        // Mobil cihazlar için klavye olayları
        function setupMobileKeyboardEvents() {
            if (window.innerWidth <= 767) {
                // Klavye açıldığında
                messageInput.addEventListener('focus', function() {
                    setTimeout(function() {
                        inputContainer.classList.add('keyboard-visible');
                        scrollToBottom();
                    }, 300);
                });
                
                // Klavye kapandığında
                messageInput.addEventListener('blur', function() {
                    setTimeout(function() {
                        inputContainer.classList.remove('keyboard-visible');
                    }, 100);
                });
            }
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
            
            // Mobil
            if (mobileCreativeToggle) mobileCreativeToggle.checked = isCreativeMode;
            if (mobileCodingToggle) mobileCodingToggle.checked = isCodingMode;
            if (mobileModelSelector) mobileModelSelector.value = selectedModel;
            if (mobileLangSettings) {
                mobileLangSettings.style.display = isCodingMode ? 'block' : 'none';
            }
            
            updateModelDisplay();
        }
        
        // Ayarlar panelini aç/kapat
        function toggleSettingsPanel() {
            settingsPanel.classList.toggle('active');
            settingsOverlay.classList.toggle('active');
            document.body.classList.toggle('no-scroll');
        }
        
        // Model göstergesini güncelle
        function updateModelDisplay() {
            const modelNameElement = document.getElementById('model-name');
            if (modelNameElement) {
                modelNameElement.textContent = selectedModel === 'soneai' ? 'SoneAI' : 'SoneAI Turbo';
            }
        }
        
        // Ayarları senkronize tutma
        function syncSettings(key, value) {
            if (key === 'creative') {
                isCreativeMode = value;
                localStorage.setItem('creative_mode', value);
                if (creativeToggle) creativeToggle.checked = value;
                if (mobileCreativeToggle) mobileCreativeToggle.checked = value;
            } 
            else if (key === 'coding') {
                isCodingMode = value;
                localStorage.setItem('coding_mode', value);
                if (codingToggle) codingToggle.checked = value;
                if (mobileCodingToggle) mobileCodingToggle.checked = value;
                
                // Dil seçim alanlarını göster/gizle
                if (languageSettings) {
                    languageSettings.style.display = value ? 'block' : 'none';
                }
                if (mobileLangSettings) {
                    mobileLangSettings.style.display = value ? 'block' : 'none';
                }
            }
            else if (key === 'model') {
                selectedModel = value;
                localStorage.setItem('selected_model', value);
                if (modelSelector) modelSelector.value = value;
                if (mobileModelSelector) mobileModelSelector.value = value;
                updateModelDisplay();
            }
            else if (key === 'language') {
                if (codeLanguage) codeLanguage.value = value;
                if (mobileCodeLanguage) mobileCodeLanguage.value = value;
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
                
                // Dil seçimi (mobil veya masaüstü)
                const language = codeLanguage ? codeLanguage.value : 
                               (mobileCodeLanguage ? mobileCodeLanguage.value : 'javascript');
                
                // İstek verisi
                const requestData = {
                    message: message.trim(),
                    chat_id: chatId,
                    creative_mode: isCreativeMode,
                    coding_mode: isCodingMode,
                    preferred_language: language,
                    model: selectedModel,
                    is_first_message: isFirstMessage,
                    chat_history: chatHistory // Sohbet geçmişini API'ye gönder
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
                    
                    // İsim kaydedildiyse, input placeholder'ı güncelle
                    if (data.name_saved) {
                        messageInput.placeholder = "Mesajınızı yazın...";
                        messageInput.focus();
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

            // AI mesajları için SoneAI logosu, kullanıcı mesajları için kullanıcı ikonu
            if (sender === 'ai') {
                avatarEl.innerHTML = `<img src="{{ asset('images/sone.png') }}" alt="SoneAI Logo" 
                        style="background-size:cover;
                        background-position: center;
                        background-repeat: no-repeat;
                        border-radius: 50%;
                        width: 28px;
                        height: 28px;
                        !important;
                        ">`;
            } else {
                avatarEl.innerHTML = `<i class="fas fa-user"></i>`;
            }

            messageEl.appendChild(avatarEl);
            
            // Mesaj içeriği
            const contentEl = document.createElement('div');
            contentEl.className = 'message-content';
            
            // Satır sonlarını <br> etiketlerine dönüştür
            let processedMessage = String(message).replace(/\n/g, '<br>');
            
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
        
        // Kod bloğu oluştur
        function createCodeBlock(code, language) {
            const codeBlock = document.createElement('div');
            codeBlock.className = 'code-block';
            
            // Kod başlığı
            const codeHeader = document.createElement('div');
            codeHeader.className = 'code-header';
            codeHeader.innerHTML = `
                <span>${language.charAt(0).toUpperCase() + language.slice(1)}</span>
            `;
            codeBlock.appendChild(codeHeader);
            
            // Kod içeriği
            const codeContent = document.createElement('div');
            codeContent.className = 'code-content';
            
            const pre = document.createElement('pre');
            const codeEl = document.createElement('code');
            codeEl.className = `language-${language}`;
            codeEl.textContent = code;
            
            pre.appendChild(codeEl);
            codeContent.appendChild(pre);
            codeBlock.appendChild(codeContent);
            
            // Kod alt kısmı
            const codeFooter = document.createElement('div');
            codeFooter.className = 'code-footer';
            
            const copyBtn = document.createElement('button');
            copyBtn.className = 'code-button';
            copyBtn.textContent = 'Kopyala';
            copyBtn.addEventListener('click', () => {
                navigator.clipboard.writeText(code);
                copyBtn.textContent = 'Kopyalandı!';
                setTimeout(() => {
                    copyBtn.textContent = 'Kopyala';
                }, 2000);
            });
            
            codeFooter.appendChild(copyBtn);
            codeBlock.appendChild(codeFooter);
            
            // Highlight.js ile sözdizimi renklendirme
            setTimeout(() => {
                if (codeEl) {
                    hljs.highlightElement(codeEl);
                }
            }, 0);
            
            return codeBlock;
        }
        
        // Yazı daktilo efekti
        function typewriterEffect(element, text) {
            if (!element) return;
            
            // Önceki içeriği temizle
            element.innerHTML = '';
            
            // HTML'i temp div'e yerleştir
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = text;
            
            // Saf metin
            const plainText = tempDiv.textContent || tempDiv.innerText || '';
            
            let i = 0;
            const speed = 20; // Hız (ms)
            
            // <br> pozisyonlarını bul
            const brPositions = [];
            let tempText = text;
            let pos = -1;
            
            while ((pos = tempText.indexOf('<br>', pos + 1)) !== -1) {
                brPositions.push(pos);
            }
            
            // Karakterleri tek tek ekle
            function typeNextChar() {
                if (i < plainText.length) {
                    // Şimdiki metni al
                    let currentText = plainText.substring(0, i + 1);
                    
                    // <br> etiketlerini yeniden ekle
                    brPositions.forEach(pos => {
                        if (currentText.length >= pos - 3*brPositions.indexOf(pos)) {
                            const insertPos = pos - 3*brPositions.indexOf(pos);
                            currentText = currentText.substring(0, insertPos) + '<br>' + currentText.substring(insertPos);
                        }
                    });
                    
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
                aiThinking.style.display = 'inline-flex';
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
        
        // Ayarlar paneli
        if (toggleSettings) toggleSettings.addEventListener('click', toggleSettingsPanel);
        if (closeSettings) closeSettings.addEventListener('click', toggleSettingsPanel);
        if (settingsOverlay) settingsOverlay.addEventListener('click', toggleSettingsPanel);
        
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
        
        // Mobil ayarları
        if (mobileCreativeToggle) {
            mobileCreativeToggle.addEventListener('change', function() {
                syncSettings('creative', this.checked);
            });
        }
        
        if (mobileCodingToggle) {
            mobileCodingToggle.addEventListener('change', function() {
                syncSettings('coding', this.checked);
            });
        }
        
        if (mobileModelSelector) {
            mobileModelSelector.addEventListener('change', function() {
                syncSettings('model', this.value);
                showModelNotification();
            });
        }
        
        if (mobileCodeLanguage) {
            mobileCodeLanguage.addEventListener('change', function() {
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
                    ${selectedModel === 'soneai' ? 'SoneAI' : 'SoneAI Turbo'} modeli aktif
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
        
        // Mobil klavye görünürlüğünü takip et
        setupMobileKeyboardEvents();
        
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

        // Yeni chat başlat
        function startNewChat() {
            localStorage.removeItem('current_chat_id');
            clearChatHistory();
            messagesContainer.innerHTML = '';
            addMessage("Merhaba! Ben SoneAI. Size nasıl yardımcı olabilirim?", 'ai');
        }

        // Mobil yeni chat butonu
        document.getElementById('mobile-new-chat-btn').addEventListener('click', function() {
            startNewChat();
            toggleSettingsPanel();
        });

        // Yeni chat butonu masaüstü
        document.getElementById('new-chat-btn').addEventListener('click', function() {
            startNewChat();
        });
    });
</script>
@endsection 