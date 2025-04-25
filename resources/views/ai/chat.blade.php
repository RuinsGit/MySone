@extends('layouts.app')

@section('title', 'SoneAI - Yapay Zeka Sohbet')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/atom-one-dark.min.css">

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
  max-width: calc(100% - 60px);
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
  margin-top: 16px;
  border-radius: var(--border-radius);
  overflow: hidden;
  background: #282c34;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.08);
  transition: all 0.3s ease;
  transform: translateZ(0);
  backface-visibility: hidden;
}

.code-block:hover {
  box-shadow: 0 12px 28px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(255, 255, 255, 0.1);
  transform: translateY(-3px) translateZ(0);
}

.code-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: rgba(0, 0, 0, 0.4);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  font-size: 13px;
  font-weight: 600;
  color: var(--text-muted);
  letter-spacing: 0.5px;
}

.code-content {
  max-height: 400px;
  overflow-y: auto;
  position: relative;
}

.code-content::-webkit-scrollbar {
  width: 5px;
  height: 5px;
}

.code-content::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.2);
}

.code-content::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 5px;
}

.code-content::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.2);
}

.code-content pre {
  margin: 0;
  padding: 16px;
  background: transparent !important;
}

.code-content code {
  font-family: 'Fira Code', 'JetBrains Mono', 'Courier New', monospace;
  font-size: 14px;
  line-height: 1.5;
}

.code-footer {
  display: flex;
  justify-content: flex-end;
  padding: 10px 16px;
  background: rgba(0, 0, 0, 0.3);
  border-top: 1px solid rgba(255, 255, 255, 0.07);
}

.code-button {
  background: rgba(79, 70, 229, 0.2);
  color: var(--primary-light);
  border: 1px solid rgba(79, 70, 229, 0.3);
  border-radius: 6px;
  padding: 8px 14px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 6px;
}

.code-button:hover {
  background: rgba(79, 70, 229, 0.3);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
}

.code-button:active {
  transform: translateY(0);
}

.code-button i {
  font-size: 14px;
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
  .sidebar {
    display: none;
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
                <button id="fullscreen-toggle" class="p-2 rounded-full hover:bg-gray-100 mr-2" aria-label="Tam Ekran">
                    <i class="fas fa-expand"></i>
                </button>
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
                    width: 90%;
                   height: 90%;
                   !important;
                   ">
                </div>
                <div class="message-sender-name">SoneAI</div>
                <div class="message-content">
                    <p>Merhaba! Ben SoneAI. Size nasıl yardımcı olabilirim?</p>
                </div>
            </div>
            
            <!-- Thinking animation placeholder - Mesaj akışının içinde -->
            <div id="ai-thinking" class="message message-ai ai-thinking-wrapper" style="display: none;">
                <div class="message-avatar ai-avatar-pulse">
                    <img src="{{ asset('images/sone.png') }}" alt="SoneAI Logo" 
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
<!-- Diller için ek paketler -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/languages/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/languages/php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/languages/python.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/languages/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/languages/html.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/languages/sql.min.js"></script>
<script>
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
        let visitorName = '{{ session('visitor_name') }}' || localStorage.getItem('visitor_name') || '';
        
        // Eğer session'da isim varsa, localStorage'a da kaydedelim
        if ('{{ session('visitor_name') }}') {
            localStorage.setItem('visitor_name', '{{ session('visitor_name') }}');
        }
        
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
            
            // Kullanıcı adı veya AI adı ekleyerek görüntüle
            const nameEl = document.createElement('div');
            nameEl.className = 'message-sender-name';
            nameEl.textContent = sender === 'ai' ? 'SoneAI' : (visitorName || '{{ session('visitor_name') }}');
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
                                voiceStatus.textContent = 'SoneAI yanıtlıyor...';
                                
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
                        voiceMessage.innerHTML += '<strong>SoneAI:</strong> ' + finalResponse + '<br>';
                        
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
                        voiceMessage.innerHTML += '<strong>SoneAI:</strong> Kod yanıtı oluşturdum. Görüntülemek için popup\'ı kapatın.<br>';
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
                
                voiceStatus.textContent = 'SoneAI konuşuyor...';
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
</script>
@endsection 