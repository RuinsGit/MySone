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
  display: flex;
  flex-direction: column;
  background: var(--bg-medium);
  border-right: 1px solid rgba(255, 255, 255, 0.05);
  padding: 20px;
  transition: transform var(--transition-speed) ease;
  overflow-y: auto;
  z-index: 100;
}

.sidebar-header {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding-bottom: 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  margin-bottom: 20px;
}

.sidebar-logo {
  display: flex;
  align-items: center;
  gap: 10px;
}

.sidebar-options {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.sidebar-option {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.sidebar-select {
  width: 100%;
  padding: 8px 12px;
  background: var(--bg-light);
  color: var(--text-light);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: var(--border-radius);
  font-size: 14px;
  margin-top: 8px;
}

/* Mobil hamburger menü stilleri */
.mobile-menu-toggle {
  display: none;
  background: transparent;
  border: none;
  color: var(--text-light);
  font-size: 1.5rem;
  cursor: pointer;
  margin-right: 16px;
  transition: all 0.3s ease;
}

.mobile-menu-toggle:hover {
  color: var(--primary-light);
  transform: scale(1.1);
}

.sidebar-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(3px);
  z-index: 90;
  display: none;
  opacity: 0;
  transition: opacity 0.3s ease;
}

/* Mobil görünüm için medya sorguları */
@media (max-width: 768px) {
  .mobile-menu-toggle {
    display: block;
  }

  .sidebar {
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    width: 270px;
    transform: translateX(-100%);
    z-index: 1000;
  }

  .sidebar.active {
    transform: translateX(0);
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
  }

  .sidebar-overlay.active {
    display: block;
    opacity: 1;
  }
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

/* Bildirim sesi butonu */
.sound-toggle-btn {
  background: transparent;
  border: none;
  color: var(--text-light);
  font-size: 1.2rem;
  cursor: pointer;
  width: 40px;
  height: 24px;
  border-radius: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  position: relative;
}

.sound-toggle-btn:hover {
  color: var(--primary-light);
  transform: scale(1.1);
}

.sound-toggle-btn:active {
  transform: scale(0.95);
}

/* Sidebar içindeki bildirim ses butonu için özel stil */
.sidebar-option .sound-toggle-btn {
  background-color: var(--bg-light);
  border: 1px solid rgba(255, 255, 255, 0.1);
  position: relative;
  height: 24px;
}

.sidebar-option .sound-toggle-btn:hover {
  border-color: var(--primary-light);
  box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
}

.sidebar-option .sound-toggle-btn[title*="kapat"] {
  background-color: var(--primary-color);
  box-shadow: 0 0 10px var(--glow-color);
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