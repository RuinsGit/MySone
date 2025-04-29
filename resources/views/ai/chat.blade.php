@extends('layouts.app')

@section('title', 'SoneAI - Yapay Zeka Sohbet')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<!-- Son sürüm ve daha iyi tema ile değiştiriyorum -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/vs2015.min.css">

<style>
:root {
  --primary-color: #4f46e5;
  --primary-dark: #3c36b0;
  --primary-light: #6366f1;
  --secondary-color: #ec4899;
  --bg-dark: #111827;
  --bg-medium: #1f2937;
  --bg-light: #374151;
  --text-light: #f9fafb;
  --text-muted: #9ca3af;
  --success: #10b981;
  --warning: #f59e0b;
  --error: #ef4444;
  --border-radius: 12px;
  --transition-speed: 0.3s;
  --glow-color: rgba(79, 70, 229, 0.4);
}

.app-container {
  display: flex;
  height: 100vh;
  height: calc(var(--vh, 1vh) * 100);
  background: var(--bg-dark);
  color: var(--text-light);
  font-family: 'Inter', 'Segoe UI', Roboto, sans-serif;
  overflow: hidden;
  position: relative;
}

/* Sidebar Styles */
.sidebar {
  width: 280px;
  background: var(--bg-medium);
  border-right: 1px solid rgba(255, 255, 255, 0.07);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  transition: transform var(--transition-speed);
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
}

.sidebar-header {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.sidebar-logo {
  display: flex;
  align-items: center;
  gap: 10px;
}

.sidebar-logo img {
  filter: drop-shadow(0 0 8px var(--glow-color));
  transition: all 0.5s ease;
}

.sidebar-logo:hover img {
  transform: scale(1.05);
  filter: drop-shadow(0 0 12px var(--glow-color));
}

.logo-text {
  font-size: 1.4rem;
  font-weight: 700;
  background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  text-shadow: 0 0 30px var(--glow-color);
}

.model-selector-container {
  position: relative;
}

.model-selector {
  width: 100%;
  padding: 12px 16px;
  background: var(--bg-light);
  color: var(--text-light);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: var(--border-radius);
  appearance: none;
  font-size: 14px;
  cursor: pointer;
  transition: all var(--transition-speed);
  backdrop-filter: blur(10px);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.model-selector:hover, .model-selector:focus {
  border-color: var(--primary-light);
  box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
}

.model-selector:after {
  content: '\f107';
  font-family: 'Font Awesome 5 Free';
  font-weight: 900;
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
}

.sidebar-options {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.sidebar-option {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 0;
}

.sidebar-option span {
  font-size: 14px;
  font-weight: 500;
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
  background-color: var(--bg-light);
  transition: .4s;
  border-radius: 34px;
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
  background-color: var(--primary-color);
  box-shadow: 0 0 10px var(--glow-color);
}

input:checked + .toggle-slider:before {
  transform: translateX(26px);
}

.sidebar-select {
  width: 100%;
  padding: 10px;
  background: var(--bg-light);
  color: var(--text-light);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: var(--border-radius);
  font-size: 14px;
  transition: all 0.3s;
}

.sidebar-select:focus {
  border-color: var(--primary-light);
  box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
}

/* Main Content */
.main-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: var(--bg-dark);
  background-image: 
    radial-gradient(circle at 25% 25%, rgba(79, 70, 229, 0.05) 0%, transparent 50%),
    radial-gradient(circle at 75% 75%, rgba(236, 72, 153, 0.05) 0%, transparent 50%);
  overflow: hidden;
  position: relative;
}

/* Chat Header */
.chat-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 24px;
  background: rgba(31, 41, 55, 0.8);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  z-index: 10;
}

.chat-header-title {
  display: flex;
  align-items: center;
  gap: 12px;
}

.chat-header-title h1 {
  font-size: 1.2rem;
  font-weight: 700;
  background: linear-gradient(45deg, var(--text-light), var(--primary-light));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.user-dropdown {
  position: relative;
  display: inline-block;
}

.user-dropdown-toggle {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 24px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  cursor: pointer;
  transition: all 0.3s ease;
}

.user-dropdown-toggle:hover {
  background: rgba(255, 255, 255, 0.1);
}

.user-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: var(--primary-color);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  color: white;
  font-size: 14px;
}

.user-dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 8px;
  background: var(--bg-medium);
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.1);
  min-width: 160px;
  z-index: 1000;
  overflow: hidden;
  opacity: 0;
  transform: translateY(-10px);
  visibility: hidden;
  transition: all 0.3s ease;
}

.user-dropdown-menu.show {
  opacity: 1;
  transform: translateY(0);
  visibility: visible;
}

.user-dropdown-item {
  padding: 12px 16px;
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--text-light);
  font-size: 14px;
  transition: all 0.2s;
  cursor: pointer;
  text-decoration: none;
}

.user-dropdown-item:hover {
  background: rgba(255, 255, 255, 0.05);
}

.user-dropdown-item.logout {
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  color: var(--error);
}

.user-dropdown-item i {
  font-size: 16px;
  opacity: 0.8;
}

.chat-logo {
  display: flex;
  align-items: center;
  justify-content: center;
}

.chat-logo img {
  filter: drop-shadow(0 0 5px var(--glow-color));
  transition: all 0.3s ease;
}

.chat-controls {
  display: flex;
  gap: 8px;
}

.chat-controls button {
  background: transparent;
  color: var(--text-light);
  border: none;
  transition: all 0.2s;
}

.chat-controls button:hover {
  color: var(--primary-light);
  transform: scale(1.1);
}

/* Messages Container */
.chat-messages-container {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 24px;
  scroll-behavior: smooth;
}

.chat-messages-container::-webkit-scrollbar {
  width: 6px;
}

.chat-messages-container::-webkit-scrollbar-track {
  background: transparent;
}

.chat-messages-container::-webkit-scrollbar-thumb {
  background: var(--bg-light);
  border-radius: 10px;
}

.chat-messages-container::-webkit-scrollbar-thumb:hover {
  background: var(--primary-light);
}

/* YENİ MESAJ STİLLERİ */
.message {
  display: flex;
  gap: 16px;
  max-width: 90%;
  animation: messageSlideIn 0.4s cubic-bezier(0.215, 0.610, 0.355, 1.000);
  position: relative;
  margin-bottom: 28px;
}

@keyframes messageSlideIn {
  0% { 
    opacity: 0; 
    transform: translateY(20px) scale(0.95); 
    filter: blur(5px);
  }
  100% { 
    opacity: 1; 
    transform: translateY(0) scale(1); 
    filter: blur(0);
  }
}

.message-ai {
  align-self: flex-start;
}

.message-user {
  align-self: flex-end;
  flex-direction: row-reverse;
}

.message-avatar {
  width: 42px;
  height: 42px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg-light);
  color: var(--text-light);
  font-size: 16px;
  flex-shrink: 0;
  position: relative;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.05);
  transition: transform 0.3s ease, border-radius 0.3s ease;
}

.message-avatar:hover {
  transform: scale(1.05) translateY(-2px);
  border-radius: 20px;
}

.message-user .message-avatar {
  background: linear-gradient(135deg, var(--primary-color) 0%, #764ba2 100%);
  box-shadow: 0 4px 15px rgba(79, 70, 229, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.07);
}

/* .message-ai .message-avatar {
  background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%);
  box-shadow: 0 4px 15px rgba(0, 198, 255, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.07);
} */

.message-ai .message-avatar:before {
  content: '';
  position: absolute;
  width: 100%;
  height: 100%;
  background: radial-gradient(circle at center, rgba(255, 255, 255, 0.15) 0%, transparent 70%);
  z-index: 1;
}

.message-sender-name {
  position: absolute;
  top: -20px;
  font-size: 13px;
  font-weight: 600;
  background: linear-gradient(to right, #fff, #ccc);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  letter-spacing: 0.5px;
  opacity: 0.9;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
  white-space: nowrap;
}

.message-ai .message-sender-name {
  left: 52px;
  background: linear-gradient(to right, #00c6ff, #0072ff);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.message-user .message-sender-name {
  right: 52px;
  background: linear-gradient(to right, var(--primary-color), #764ba2);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.message-content {
  padding: 16px 20px;
  border-radius: 18px;
  font-size: 15px;
  line-height: 1.6;
  max-width: calc(90% - 0px);
  position: relative;
  z-index: 1;
  letter-spacing: 0.2px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.message-content:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 28px rgba(0, 0, 0, 0.15);
}

.message-ai .message-content {
  background: rgba(31, 41, 55, 0.85);
  border-top-left-radius: 4px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(255, 255, 255, 0.07) inset;
  backdrop-filter: blur(12px);
  border-left: 2px solid rgba(31, 41, 55, 0.85);
}

.message-ai .message-content:before {
  content: '';
  position: absolute;
  width: 12px;
  height: 12px;
  background: rgba(31, 41, 55, 0.85);
  top: 20px;
  left: -6px;
  transform: rotate(45deg);
  border-bottom: 2px solid rgba(31, 41, 55, 0.85);
  border-left: 2px solid rgba(31, 41, 55, 0.85);
  z-index: -1;
}

.message-user .message-content {
  background: linear-gradient(135deg, rgba(79, 70, 229, 0.9) 0%, rgba(118, 75, 162, 0.9) 100%);
  border-top-right-radius: 4px;
  color: white;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
  backdrop-filter: blur(12px);
}

.message-user .message-content:before {
  content: '';
  position: absolute;
  width: 12px;
  height: 12px;
  background: linear-gradient(135deg, rgba(79, 70, 229, 0.9) 0%, rgba(118, 75, 162, 0.9) 100%);
  top: 20px;
  right: -6px;
  transform: rotate(45deg);
  z-index: -1;
}

.message-content p {
  margin: 0;
  position: relative;
}

.message-ai .message-content p:after {
  content: '';
  position: absolute;
  bottom: -8px;
  left: 0;
  width: 30px;
  height: 2px;
  background: linear-gradient(to right,rgb(255, 0, 200), transparent);
  border-radius: 2px;
}

/* YENİ MUHTESEM LOADING ANİMASYON */
.ai-thinking-wrapper {
  margin-top: 5px;
  margin-bottom: 20px;
  animation: pulseIn 0.5s ease-out;
  align-self: flex-start;
  position: relative;
  max-width: 200px;
  z-index: 5; /* Üstte görünmesi için */
}

@keyframes pulseIn {
  0% { 
    opacity: 0; 
    transform: scale(0.8);
  }
  70% { 
    opacity: 1; 
    transform: scale(1.05);
  }
  100% { 
    opacity: 1; 
    transform: scale(1);
  }
}

.ai-avatar-pulse {
  position: relative;
  animation: floatAnimation 3s infinite ease-in-out;
}

@keyframes floatAnimation {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-6px); }
}

.ai-avatar-pulse:after {
  content: '';
  position: absolute;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
  background: rgba(0, 198, 255, 0.3);
  border-radius: 16px;
  z-index: -1;
  animation: rippleEffect 3s infinite;
}

@keyframes rippleEffect {
  0% {
    opacity: 0.8;
    transform: scale(1);
  }
  50% {
    opacity: 0;
    transform: scale(1.8);
  }
  100% {
    opacity: 0;
    transform: scale(2.5);
  }
}

.ai-avatar-pulse img {
  animation: glowPulse 2s infinite alternate;
  transform-origin: center;
}

@keyframes glowPulse {
  0% {
    filter: drop-shadow(0 0 5px rgba(0, 198, 255, 0.5));
    transform: scale(1);
  }
  100% {
    filter: drop-shadow(0 0 12px rgba(0, 198, 255, 0.8));
    transform: scale(1.1);
  }
}

.ai-thinking-content {
  padding: 16px;
  min-width: 100px;
  background: rgba(31, 41, 55, 0.85);
  border-radius: 18px;
  border-top-left-radius: 4px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0, 198, 255, 0.2) inset;
  margin-top: 4px;
  backdrop-filter: blur(12px);
  border-left: 2px solid rgba(0, 198, 255, 0.7);
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  transform-origin: left center;
  animation: pulseContent 2s infinite alternate ease-in-out;
}

@keyframes pulseContent {
  0% {
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0, 198, 255, 0.2) inset, 0 0 0 rgba(0, 198, 255, 0.2);
  }
  100% {
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0, 198, 255, 0.2) inset, 0 0 20px rgba(0, 198, 255, 0.4);
  }
}

.ai-thinking-content:before {
  content: '';
  position: absolute;
  width: 12px;
  height: 12px;
  background: rgba(31, 41, 55, 0.85);
  top: 20px;
  left: -6px;
  transform: rotate(45deg);
  border-bottom: 2px solid rgba(0, 198, 255, 0.7);
  border-left: 2px solid rgba(0, 198, 255, 0.7);
  z-index: -1;
}

.ai-thinking-dots {
  display: flex;
  align-items: center;
  gap: 8px;
  position: relative;
}

.ai-thinking-dots span {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  display: inline-block;
  position: relative;
  animation: particleJump 1.5s infinite;
}

.ai-thinking-dots span:nth-child(1) {
  background: linear-gradient(to right, #00c6ff, #0072ff);
  animation-delay: -0.32s;
  box-shadow: 0 0 15px rgba(0, 198, 255, 0.5);
}

.ai-thinking-dots span:nth-child(2) {
  background: linear-gradient(to right, #0072ff, #764ba2);
  animation-delay: -0.16s;
  box-shadow: 0 0 15px rgba(0, 114, 255, 0.5);
}

.ai-thinking-dots span:nth-child(3) {
  background: linear-gradient(to right, #764ba2, #ec4899);
  box-shadow: 0 0 15px rgba(236, 72, 153, 0.5);
}

.ai-thinking-dots:after {
  content: '';
  position: absolute;
  bottom: -8px;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(to right, #00c6ff, #0072ff, transparent);
  animation: lineExpand 2s infinite alternate;
}

@keyframes lineExpand {
  0% { width: 20%; opacity: 0.3; }
  100% { width: 100%; opacity: 0.6; }
}

@keyframes particleJump {
  0%, 10%, 30%, 50%, 100% { 
    transform: translate3d(0, 0, 0) scale(0.8);
  }
  20% { 
    transform: translate3d(0, -10px, 0) scale(1.0);
  }
  40% { 
    transform: translate3d(0, -5px, 0) scale(0.9);
  }
}

/* Code Block Styles */
.code-block {
  margin: 20px 0;
  border-radius: 8px;
  overflow: hidden;
  background: #1e1e1e;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
  width: 100%;
  font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
}

.code-header {
  background: #343541;
  padding: 10px 15px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: #e9e9e9;
  font-size: 14px;
  font-weight: 500;
  border-bottom: 1px solid #44444f;
}

/* Kod içeriği için yeni stiller - renklendirme sorununu çözmek için */
.code-content {
  position: relative;
  max-height: none; /* Yükseklik sınırını kaldır */
  background: #1e1e1e;
}

.code-content pre {
  margin: 0;
  padding: 0 !important;
  background: transparent !important;
  overflow-x: hidden;
  overflow-y: auto;
  max-height: 600px; /* Dikey kaydırma için maksimum yükseklik */
}

.code-content code {
  padding: 16px !important;
  display: block;
  overflow-x: auto;
  white-space: pre !important;
  word-wrap: normal !important; 
  word-break: normal !important;
  tab-size: 4;
  font-size: 14px;
  line-height: 1.6;
  font-family: 'Consolas', 'Monaco', 'Menlo', 'Courier New', monospace !important;
  background: #1e1e1e !important;
  color: #d4d4d4 !important; /* VS Code varsayılan metin rengi */
}

.code-footer {
  background: #343541;
  padding: 8px 15px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  border-top: 1px solid #44444f;
}

.code-button {
  background: transparent;
  color: #e9e9e9;
  border: 1px solid #555;
  border-radius: 4px;
  padding: 5px 12px;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.2s;
}

.code-button:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: #888;
}

/* Highlight.js'nin stillerinin önceliğini arttır */
.hljs-keyword,
.hljs-built_in,
.hljs-type,
.hljs-literal,
.hljs-number,
.hljs-operator,
.hljs-tag {
  color: #569cd6 !important; /* Mavi */
}

.hljs-string,
.hljs-regexp,
.hljs-addition,
.hljs-attribute,
.hljs-meta .hljs-string {
  color: #ce9178 !important; /* Turuncu */
}

.hljs-function,
.hljs-title.function_ {
  color: #dcdcaa !important; /* Sarı */
}

.hljs-comment,
.hljs-quote {
  color: #6a9955 !important; /* Yeşil */
  font-style: italic;
}

.hljs-doctag,
.hljs-meta,
.hljs-meta .hljs-keyword {
  color: #ff7b72 !important;
}

.hljs-variable,
.hljs-template-variable {
  color: #bd63c5 !important; /* Mor */
}

.hljs-attr,
.hljs-property {
  color: #9cdcfe !important; /* Açık mavi */
}

.hljs-name,
.hljs-title,
.hljs-title.class_ {
  color: #4ec9b0 !important; /* Mint yeşili */
}

.hljs-section,
.hljs-selector-class {
  color: #ff7b72 !important;
}

/* Dil etiketini göster */
.language-badge {
  font-size: 12px;
  color: #6a9955;
  opacity: 0.8;
}

/* Input Container */
.input-container {
  padding: 16px 24px;
  background: rgba(31, 41, 55, 0.8);
  backdrop-filter: blur(10px);
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  display: flex;
  align-items: center;
  position: relative;
  z-index: 10;
}

.input-wrapper {
  display: flex;
  align-items: center;
  width: 100%;
  background: var(--bg-light);
  border-radius: var(--border-radius);
  padding: 8px 16px;
  transition: all 0.3s;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.input-wrapper:focus-within {
  box-shadow: 0 0 0 2px var(--primary-color), 0 0 20px rgba(79, 70, 229, 0.4);
  border-color: var(--primary-color);
}

.message-input {
  flex: 1;
  background: transparent;
  border: none;
  padding: 8px 0;
  color: var(--text-light);
  font-size: 15px;
  outline: none;
  width: auto !important;
  min-width: 0;
}

.message-input::placeholder {
  color: var(--text-muted);
}

.input-buttons {
  display: flex;
  align-items: center;
  gap: 10px;
}

.send-button {
  background: transparent;
  color: var(--primary-light);
  border: none;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s;
  flex-shrink: 0;
}

.send-button:hover {
  color: var(--text-light);
  background: var(--primary-color);
  transform: scale(1.1);
}

.voice-input-btn, .send-button {
  min-width: 40px;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
  margin: 0 5px;
}

.voice-input-btn {
  background: rgba(247, 217, 248, 0.23);
  color: var(--primary-color);
  border: 1px solid rgba(79, 70, 229, 0.3);
  transition: all 0.3s;
  font-size: 1.2rem;
  box-shadow: 0 0 10px rgba(79, 70, 229, 0.2);
  margin-left: 0;
  margin-right: 30px;
}

.send-button {
  background: rgba(16, 20, 223, 0.23);
  color: var(--primary-color);
  border: 1px solid rgba(79, 70, 229, 0.3);
  box-shadow: 0 0 10px rgba(79, 70, 229, 0.2);
  /* border: none; */
  transition: all 0.3s;
  margin-right: 0;
  margin-left: 10px;
}

.voice-input-btn:hover {
  color: white;
  background: var(--primary-color);
  transform: scale(1.1);
  box-shadow: 0 0 15px rgba(79, 70, 229, 0.4);
}

.send-button:hover {
  color: var(--text-light);
  background: var(--primary-color);
  transform: scale(1.1);
}

.voice-input-btn.recording {
  color: white;
  background: var(--error);
  animation: pulse 1.5s infinite;
  box-shadow: 0 0 15px rgba(239, 68, 68, 0.5);
}

/* Settings Panel */
.settings-panel {
  position: fixed;
  right: -350px;
  top: 0;
  width: 320px;
  height: 100%;
  background: var(--bg-medium);
  z-index: 1000;
  transition: right var(--transition-speed);
  box-shadow: -5px 0 30px rgba(0, 0, 0, 0.5);
  overflow-y: auto;
  border-left: 1px solid rgba(255, 255, 255, 0.05);
}

.settings-panel.active {
  right: 0;
}

.settings-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(3px);
  z-index: 999;
  opacity: 0;
  visibility: hidden;
  transition: opacity var(--transition-speed);
}

.settings-overlay.active {
  opacity: 1;
  visibility: visible;
}

.settings-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.settings-title {
  font-size: 1.2rem;
  font-weight: 600;
  background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.settings-close {
  background: transparent;
  border: none;
  color: var(--text-muted);
  font-size: 18px;
  cursor: pointer;
  transition: color 0.2s;
}

.settings-close:hover {
  color: var(--text-light);
}

.settings-section {
  padding: 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.settings-section-title {
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 16px;
  color: var(--text-light);
}

.settings-option {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.settings-option-label {
  font-size: 14px;
  color: var(--text-muted);
}

.settings-select {
  width: 100%;
  padding: 12px;
  background: var(--bg-light);
  color: var(--text-light);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: var(--border-radius);
  appearance: none;
  margin-top: 10px;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s;
}

.settings-select:focus {
  border-color: var(--primary-light);
  box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
}

.settings-switch {
  position: relative;
  display: inline-block;
  width: 50px;
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
  background-color: var(--bg-light);
  transition: .4s;
  border-radius: 34px;
}

.switch-slider:before {
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

input:checked + .switch-slider {
  background-color: var(--primary-color);
  box-shadow: 0 0 10px var(--glow-color);
}

input:checked + .switch-slider:before {
  transform: translateX(26px);
}

/* Responsive Styles */
@media (max-width: 767px) {
    .app-container {
        position: relative;
        overflow-x: hidden;
        background: var(--bg-dark); /* Ana arka plan rengini tekrar belirt */
    }
    
    /* Menü açık olduğunda ana içeriğin pozisyonunu ayarla */
    .main-content {
        transition: all 0.3s ease;
        position: relative;
        background: var(--bg-dark); /* Ana içerik arka plan rengini belirt */
        z-index: 0; /* Ana içerik z-index değeri */
    }
    
    .sidebar {
        position: fixed;
        top: 0;
        left: -280px;
        height: 100%;
        z-index: 1001;
        transition: all 0.3s ease;
        width: 280px;
        background: var(--bg-medium);
        display: block !important;
        overflow-y: auto;
        transform: none;
        visibility: visible; /* Her zaman görünür ancak dışarda */
    }
    
    .sidebar.active {
        left: 0;
        right: auto;
        box-shadow: 5px 0 20px rgba(0, 0, 0, 0.3);
    }

    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999; /* Sidebar'dan düşük, diğer içerikten yüksek */
        opacity: 0;
        visibility: hidden; /* Başlangıçta gizli */
        transition: all 0.3s ease;
        pointer-events: none; /* Başlangıçta tıklanamaz */
    }
    
    .sidebar-overlay.active {
        opacity: 1;
        visibility: visible;
        pointer-events: auto; /* Aktif olduğunda tıklanabilir */
    }

    /* Kaydırma sorununu önlemek için */
    .sidebar.active .sidebar-header,
    .sidebar.active .sidebar-options {
        pointer-events: auto;
    }

    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        opacity: 0;
        transition: opacity 0.3s ease;
        pointer-events: auto;
    }

    /* Menü hamburger butonu stil düzenlemeleri */
    .menu-toggle-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background: transparent;
        border: none;
        color: var(--text-light);
        font-size: 20px;
        cursor: pointer;
        transition: all 0.3s ease;
        padding: 0;
        border-radius: 50%;
        z-index: 1002;
    }
    
    .menu-toggle-btn:hover {
        background: rgba(255, 255, 255, 0.1);
        color: var(--primary-light);
    }
    
    .message {
        max-width: 100%;
    }
    
    .input-container.keyboard-visible {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
    }
    
    .chat-header-title h1 {
        font-size: 1.1rem;
    }
    
    .message-content {
        font-size: 14px;
    }
    
    .ai-thinking-wrapper {
        position: relative;
        bottom: auto;
        margin: 10px 0;
        padding: 0 16px;
        width: auto;
        clear: both;
    }
}

/* Dark mode specific styles for code highlighting */
.hljs {
  background: #282c34 !important;
}

/* Animation for Logo */
@keyframes pulse {
  0% {
    filter: drop-shadow(0 0 5px var(--glow-color));
  }
  50% {
    filter: drop-shadow(0 0 15px var(--glow-color));
  }
  100% {
    filter: drop-shadow(0 0 5px var(--glow-color));
  }
}

.sidebar-logo img, .chat-logo img {
  animation: pulse 3s infinite;
}

/* Gradient Button Styles */
button.gradient-btn {
  background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
  color: white;
  border: none;
  padding: 10px 16px;
  border-radius: var(--border-radius);
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

button.gradient-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
}

/* Glass Morphism Effect */
.glass-card {
  background: rgba(31, 41, 55, 0.7);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: var(--border-radius);
  padding: 20px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

/* Safari specific fixes */
.safari-browser .message-user .message-content {
  color: white !important;
}
.safari-browser .message-user .message-content p {
  color: white !important;
}

/* Tenor GIF stilleri */
.tenor-gif {
    max-width: 100%;
    max-height: 250px;
    border-radius: 8px;
    margin: 8px 0;
    display: block;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border: 1px solid rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.tenor-gif:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.message-content p img.tenor-gif {
    margin: 10px auto;
}

/* Mobil cihazlar için daha küçük GIF'ler */
@media (max-width: 576px) {
    .tenor-gif {
        max-height: 180px;
    }
}

/* Sesli Sohbet Popup Stilleri */
.voice-popup {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) scale(0.9);
  width: 90%;
  max-width: 500px;
  background: rgba(31, 41, 55, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 16px;
  z-index: 1000;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
  padding: 24px;
  opacity: 0;
  visibility: hidden;
  transition: all 0.3s ease;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.voice-popup.active {
  opacity: 1;
  visibility: visible;
  transform: translate(-50%, -50%) scale(1);
}

.voice-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(3px);
  z-index: 999;
  opacity: 0;
  visibility: hidden;
  transition: opacity var(--transition-speed);
}

.voice-overlay.active {
  opacity: 1;
  visibility: visible;
}

.voice-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  padding-bottom: 10px;
}

.voice-title {
  font-size: 1.2rem;
  font-weight: 600;
  background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.voice-close {
  background: transparent;
  border: none;
  color: var(--text-muted);
  font-size: 18px;
  cursor: pointer;
  transition: color 0.2s;
  width: 35px;
  height: 35px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
}

.voice-close:hover {
  color: var(--text-light);
  background: rgba(255, 255, 255, 0.1);
}

.voice-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 24px;
}

.voice-visualizer {
  width: 200px;
  height: 200px;
  border-radius: 50%;
  background: linear-gradient(135deg, rgba(79, 70, 229, 0.2) 0%, rgba(236, 72, 153, 0.2) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  box-shadow: 0 0 30px rgba(79, 70, 229, 0.3);
  overflow: hidden;
  transition: all 0.3s ease;
}

.voice-visualizer.recording {
  background: linear-gradient(135deg, rgba(236, 72, 153, 0.3) 0%, rgba(79, 70, 229, 0.3) 100%);
  box-shadow: 0 0 40px rgba(236, 72, 153, 0.4);
}

.voice-waves {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.voice-visualizer.recording .voice-waves {
  opacity: 1;
}

.voice-waves span {
  display: inline-block;
  width: 5px;
  margin: 0 2px;
  background: rgba(255, 255, 255, 0.5);
  height: 10px;
  border-radius: 3px;
  animation: waveform 1s infinite ease-in-out;
}

.voice-waves span:nth-child(2n) {
  animation-delay: 0.2s;
}

.voice-waves span:nth-child(3n) {
  animation-delay: 0.4s;
}

.voice-waves span:nth-child(4n) {
  animation-delay: 0.6s;
}

.voice-waves span:nth-child(5n) {
  animation-delay: 0.8s;
}

@keyframes waveform {
  0%, 100% {
    height: 10px;
  }
  50% {
    height: 80px;
  }
}

.voice-mic-btn {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  border: none;
  background: var(--primary-color);
  color: white;
  font-size: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 20px rgba(79, 70, 229, 0.5);
  position: relative;
  z-index: 2;
}

.voice-mic-btn:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 25px rgba(79, 70, 229, 0.7);
}

.voice-mic-btn:active {
  transform: scale(0.98);
}

.voice-mic-btn.recording {
  background: var(--error);
  animation: pulse 1.5s infinite;
  box-shadow: 0 4px 20px rgba(239, 68, 68, 0.5);
}

@keyframes pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.5);
  }
  70% {
    box-shadow: 0 0 0 15px rgba(239, 68, 68, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
  }
}

.voice-status {
  font-size: 16px;
  color: var(--text-light);
  text-align: center;
  max-width: 300px;
  min-height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.voice-input-btn {
  position: absolute;
  right: 60px;
  background: rgba(79, 70, 229, 0.1);
  color: var(--primary-color);
  border: 1px solid rgba(79, 70, 229, 0.3);
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s;
  font-size: 1.2rem;
  box-shadow: 0 0 10px rgba(79, 70, 229, 0.2);
  z-index: 2;
}

.voice-input-btn:hover {
  color: white;
  background: var(--primary-color);
  transform: scale(1.1);
  box-shadow: 0 0 15px rgba(79, 70, 229, 0.4);
}

.voice-input-btn:active {
  transform: scale(0.95);
}

.voice-input-btn.recording {
  color: white;
  background: var(--error);
  animation: pulse 1.5s infinite;
  box-shadow: 0 0 15px rgba(239, 68, 68, 0.5);
}

@media (max-width: 767px) {
  .voice-popup {
    width: 95%;
    padding: 16px;
  }
  
  .voice-visualizer {
    width: 150px;
    height: 150px;
  }
  
  .voice-mic-btn {
    width: 60px;
    height: 60px;
    font-size: 20px;
  }
}

/* Sesli sohbet popup ek stilleri */
.voice-conversation {
  width: 100%;
  margin-top: 10px;
  padding: 10px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 10px;
  max-height: 120px;
  overflow-y: auto;
  display: none;
}

.voice-message {
  font-size: 14px;
  color: var(--text-light);
  line-height: 1.4;
  opacity: 0.9;
}

.voice-controls {
  display: flex;
  gap: 10px;
  margin-top: 10px;
  width: 100%;
  justify-content: center;
}

.voice-control-btn {
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 8px 12px;
  font-size: 13px;
  color: var(--text-light);
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 6px;
}

.voice-control-btn:hover {
  background: rgba(255, 255, 255, 0.15);
  transform: translateY(-2px);
}

.voice-control-btn.active {
  background: var(--primary-light);
  border-color: var(--primary-light);
  box-shadow: 0 0 10px rgba(79, 70, 229, 0.4);
}

.voice-control-btn i {
  font-size: 14px;
}

/* Konuşma animasyonu */
@keyframes pulseSpeaking {
  0% {
    transform: scale(1);
    box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.5);
  }
  50% {
    transform: scale(1.05);
    box-shadow: 0 0 10px 5px rgba(99, 102, 241, 0.5);
  }
  100% {
    transform: scale(1);
    box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.5);
  }
}

.ai-speaking {
  animation: pulseSpeaking 1.5s infinite;
  background: linear-gradient(135deg, var(--primary-color) 0%, rgba(236, 72, 153, 0.8) 100%);
}

.voice-chat-mode {
    height: 80vh;
    display: flex;
    flex-direction: column;
}

.voice-chat-mode .voice-popup-content {
    display: flex;
    flex-direction: column;
    flex: 1;
    overflow: auto;
}

#voice-visualizer.ai-speaking {
    background: linear-gradient(90deg, #4776E6, #8E54E9);
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); opacity: 0.8; }
    50% { transform: scale(1.05); opacity: 1; }
    100% { transform: scale(1); opacity: 0.8; }
}

#voice-conversation {
    flex: 1;
    overflow-y: auto;
    padding: 15px;
    background-color: rgba(0,0,0,0.03);
    border-radius: 10px;
    margin-bottom: 15px;
    font-size: 14px;
    line-height: 1.6;
}

#voice-message {
    max-height: 300px;
}

#voice-message strong {
    color: #4776E6;
}

#voice-message strong:first-child {
    color: #2E8B57;
}

.voice-chat-welcome {
    text-align: center;
    color: #666;
    padding: 20px;
    font-style: italic;
}

#voice-continuous-btn {
    position: absolute;
    top: 15px;
    right: 60px;
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 50px;
    padding: 5px 15px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.3s;
}

#voice-continuous-btn.active {
    background: #4776E6;
    color: white;
    border-color: #4776E6;
}

.voice-footer {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px 0;
}

/* Sesli Sohbet Modu Stilleri */
.voice-chat-mode .voice-chat-container {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.voice-chat-mode .voice-chat-header {
    padding: 15px;
    background: var(--background-color);
    border-bottom: 1px solid rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.voice-chat-mode .voice-chat-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--font-color);
}

.voice-chat-mode .voice-chat-controls {
    display: flex;
    gap: 10px;
}

.voice-chat-mode .voice-chat-conversation {
    flex: 1;
    overflow-y: auto;
    padding: 15px;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.voice-chat-mode .voice-message-item {
    padding: 12px 15px;
    border-radius: 12px;
    max-width: 85%;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.voice-chat-mode .user-message {
    background: #e1f5fe;
    color: #0277bd;
    align-self: flex-end;
}

.voice-chat-mode .ai-message {
    background: #f5f5f5;
    color: #424242;
    align-self: flex-start;
}

.voice-chat-mode .voice-chat-controls-bottom {
    padding: 15px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--background-color);
    border-top: 1px solid rgba(0,0,0,0.1);
}

.voice-chat-mode .voice-status {
    font-size: 14px;
    color: #666;
}

.voice-chat-mode .voice-controls {
    display: flex;
    gap: 15px;
    align-items: center;
}

.voice-chat-mode .voice-btn {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.voice-chat-mode .mic-btn {
    background: var(--primary-color);
    color: white;
}

.voice-chat-mode .mic-btn.recording {
    background: #f44336;
    animation: pulse 1.5s infinite;
}

.voice-chat-mode .control-btn {
    background: #f5f5f5;
    color: #333;
    width: 40px;
    height: 40px;
}

.voice-chat-mode .control-btn.active {
    background: var(--primary-color);
    color: white;
}

#voice-visualizer {
    width: 250px;
    height: 40px;
    background: #f5f5f5;
    border-radius: 20px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
}

#voice-visualizer::before {
    content: '';
    position: absolute;
    width: 100%;
    height: 2px;
    background: #ccc;
}

#voice-visualizer.recording::before {
    height: 100%;
    background: linear-gradient(90deg, rgba(244,67,54,0) 0%, rgba(244,67,54,0.3) 50%, rgba(244,67,54,0) 100%);
    animation: wave 1.5s ease-in-out infinite;
}

#voice-visualizer.ai-speaking::before {
    height: 100%;
    background: linear-gradient(90deg, rgba(33,150,243,0) 0%, rgba(33,150,243,0.3) 50%, rgba(33,150,243,0) 100%);
    animation: wave 1.5s ease-in-out infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

@keyframes wave {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
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

// Pre ve code elementlerinin stillerini düzenle
document.addEventListener('DOMContentLoaded', function() {
    // Stil ekle
    const styleElement = document.createElement('style');
    styleElement.textContent = `
        .code-content pre {
            max-height: none !important;
            overflow-x: hidden !important;
            white-space: pre !important;
        }
        
        .code-content code {
            white-space: pre !important;
            overflow-x: auto !important;
            word-wrap: normal !important;
            word-break: normal !important;
            tab-size: 4 !important;
        }
    `;
    document.head.appendChild(styleElement);
});
</style>
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
<script>
    // Yükleme sonrası highlight.js'yi başlat
    document.addEventListener('DOMContentLoaded', function() {
        hljs.configure({
            languages: ['javascript', 'typescript', 'php', 'css', 'html', 'json'],
            ignoreUnescapedHTML: true
        });
        hljs.highlightAll();
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
        const settingsPanel = document.getElementById('settings-panel');
        const settingsOverlay = document.getElementById('settings-overlay');
        const toggleSettings = document.getElementById('toggle-settings');
        const closeSettings = document.getElementById('close-settings');
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
        
        // Mobil kontrolleri
        const mobileCreativeToggle = document.getElementById('mobile-creative-toggle');
        const mobileCodingToggle = document.getElementById('mobile-coding-toggle');
        const mobileCodeLanguage = document.getElementById('mobile-code-language');
        const mobileLangSettings = document.getElementById('mobile-language-settings');
        const mobileModelSelector = document.getElementById('mobile-model-selector');
        
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
                modelNameElement.textContent = selectedModel === 'soneai' ? 'LizzAI Basic' : 'LizzAI Turbo';
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
                    chat_history: chatHistory, // Sohbet geçmişini API'ye gönder
                    visitor_name: visitorName // Kullanıcı adını da gönder
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
            } else {
                // Kullanıcı avatarı - Google'dan gelen avatar varsa kullan, yoksa baş harfini göster
                @auth
                if ("{{ auth()->check() && auth()->user()->avatar }}") {
                    avatarEl.innerHTML = `<img src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}" 
                        style="background-size:cover;
                        background-position: center;
                        background-repeat: no-repeat;
                        border-radius: 50%;
                        width: 28px;
                        height: 28px;
                        !important;">`;
                } else {
                    avatarEl.innerHTML = `<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;">
                        {{ auth()->check() ? substr(auth()->user()->name, 0, 1) : 'K' }}</div>`;
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
            addMessage("Merhaba! Ben Lizz. Size nasıl yardımcı olabilirim?", 'ai');
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
                // Sesli sohbet modunda işlem zaten voice modüllerinde hallediliyor
                // Bu durumda normal sohbet akışını işleme ve sadece sesli yanıt döndür
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
                    chat_history: chatHistory, // Sohbet geçmişini API'ye gönder
                    visitor_name: visitorName // Kullanıcı adını da gönder
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
                    
                    // Chat ID'yi kaydet
                    if (data.chat_id) {
                        localStorage.setItem('current_chat_id', data.chat_id);
                    }
                    
                    // Yanıtı işle
                    let finalResponse = data.response;
                    
                    // AI yanıtını geçmişe ekle
                    addToChatHistory(text, 'user');
                    addToChatHistory(finalResponse, 'ai');
                    
                    // Kod yanıtı değilse seslendir
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
                                 userDropdownToggle.querySelector('span').textContent.trim() : 'Ruhin';
                
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

    // Hamburger menü için gerekli elemanları seç
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    // Hamburger menü tıklama olayı
    if (menuToggle) {
        menuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            
            // Varolan tüm overlay'leri temizle
            const existingOverlays = document.querySelectorAll('.sidebar-overlay');
            existingOverlays.forEach(overlay => {
                if (overlay && overlay.parentNode) {
                    overlay.parentNode.removeChild(overlay);
                }
            });
            
            // Overlay oluştur
            const sidebarOverlay = document.createElement('div');
            sidebarOverlay.className = 'sidebar-overlay';
            document.body.appendChild(sidebarOverlay);
            
            // İlk animasyon gecikmesi için minimum süre
            setTimeout(() => {
                // Sidebar'ı aç/kapat
                sidebar.classList.toggle('active');
                
                // Overlay'i aktifleştir veya kapat
                if (sidebar.classList.contains('active')) {
                    sidebarOverlay.classList.add('active');
                    
                    // Arka plana tıklama olayı ekle
                    sidebarOverlay.addEventListener('click', function() {
                        sidebar.classList.remove('active');
                        sidebarOverlay.classList.remove('active');
                        
                        setTimeout(() => {
                            if (sidebarOverlay.parentNode) {
                                document.body.removeChild(sidebarOverlay);
                            }
                        }, 300);
                    });
                } else {
                    sidebarOverlay.classList.remove('active');
                    
                    setTimeout(() => {
                        if (sidebarOverlay.parentNode) {
                            document.body.removeChild(sidebarOverlay);
                        }
                    }, 300);
                }
            }, 10);
        });
        
        // Sidebar içindeki tüm elemanların tıklama olaylarını engelleme
        sidebar.addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        // Sayfa yüklendiğinde sidebar'ı gizle
        window.addEventListener('load', function() {
            if (window.innerWidth <= 767) {
                sidebar.classList.remove('active');
                
                // Tüm overlay'leri temizle
                const overlays = document.querySelectorAll('.sidebar-overlay');
                overlays.forEach(overlay => {
                    if (overlay && overlay.parentNode) {
                        overlay.parentNode.removeChild(overlay);
                    }
                });
            }
        });
        
        // Rezise olayında sidebar'ı kontrol et
        window.addEventListener('resize', function() {
            if (window.innerWidth <= 767) {
                if (!sidebar.classList.contains('active')) {
                    // Stil durumunu düzelt
                    sidebar.style.left = '-280px';
                    sidebar.style.right = 'auto';
                    
                    // Tüm overlay'leri temizle
                    const overlays = document.querySelectorAll('.sidebar-overlay');
                    overlays.forEach(overlay => {
                        if (overlay && overlay.parentNode) {
                            overlay.parentNode.removeChild(overlay);
                        }
                    });
                }
            }
        });
    }
</script>
@endsection 