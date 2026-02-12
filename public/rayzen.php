<?php
/**
 * ReadyCRM - Rayzen AI (Chat Interface)
 * Version: 2.0 (Matched with image.png)
 * Font: YekanBakh
 * Layout: Full RTL, Fixed Sidebar, Non-scrolling Empty State
 */

session_start();

// --- بارگذاری فایل‌های سیستمی ---
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/database.php';
require_once __DIR__ . '/../private/auth.php';
require_once __DIR__ . '/../private/functions.php';

// بررسی دسترسی
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
if (!hasPermission('view_dashboard')) {
    header('Location: dashboard.php');
    exit;
}

// تنظیمات کاربر
$csrf = generateCSRFToken();
$me = getCurrentUser();
$fullName = trim(($me['first_name'] ?? '') . ' ' . ($me['last_name'] ?? ''));
if ($fullName === '') $fullName = ($_SESSION['user_name'] ?? 'کاربر');
$userEmail = $me['email'] ?? 'user@example.com';
$userAvatar = $me['avatar'] ?? ''; 
$initial = mb_substr($fullName, 0, 1);

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>چت با رایزن | Rayzen AI</title>
    <link rel="shortcut icon" href="/assets/icons/ai/rayzen-logo.svg" type="image/svg+xml">
    
    <style>
        /* ================= FONTS ================= */
        @font-face {
            font-family: 'YekanBakh';
            src: url('../assets/YekanBakhFaNum-VF.ttf') format('truetype-variations');
            font-weight: 100 900;
            font-display: swap;
        }

        /* ================= VARIABLES ================= */
        :root {
            --c-brand: #00b0a4;
            --c-brand-light: #e6f7f6; /* Very light brand bg */
            --c-brand-text: #008f85;
            
            --c-black: #0e0e0e;
            --c-text-main: #1f2937;
            --c-text-sub: #6b7280;
            --c-border: #f0f0f0; /* Very light border */
            --c-bg-body: #fdfdfd;
            --c-bg-card: #ffffff;
            
            --radius-xl: 24px;
            --radius-lg: 16px;
            --radius-md: 12px;
            
            --sidebar-width: 280px;
            --header-height: 80px;
        }

        /* ================= RESET & BASE ================= */
        * { box-sizing: border-box; outline: none; -webkit-tap-highlight-color: transparent; }
        
        body, html {
            margin: 0; padding: 0;
            height: 100%; width: 100%;
            font-family: 'YekanBakh', sans-serif;
            background-color: var(--c-bg-body);
            color: var(--c-text-main);
            overflow: hidden; /* Prevent body scroll */
        }

        a { text-decoration: none; color: inherit; }
        button { border: none; background: none; font-family: inherit; cursor: pointer; padding: 0; }
        img { display: block; max-width: 100%; }

        /* ================= LAYOUT ================= */
        .rz-layout {
            display: flex;
            height: 100vh;
            padding: 12px;
            gap: 12px;
        }

        /* ================= SIDEBAR (RIGHT) ================= */
        .rz-sidebar {
            width: var(--sidebar-width);
            background: var(--c-bg-card);
            border: 1px solid var(--c-border);
            border-radius: var(--radius-xl);
            display: flex;
            flex-direction: column;
            padding: 20px 16px;
            flex-shrink: 0;
        }

        /* Workspace Header */
        .rz-ws-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 20px; padding: 0 4px;
        }
        .rz-ws-info { display: flex; align-items: center; gap: 10px; }
        .rz-ws-icon {
            width: 30px; height: 30px; border: 1px solid #eee; border-radius: 8px;
            display: grid; place-items: center; font-weight: 700; font-size: 14px; color: #333;
        }
        .rz-ws-title { font-weight: 700; font-size: 13px; color: var(--c-black); }

        /* Navigation */
        .rz-nav-list { display: flex; flex-direction: column; gap: 4px; margin-bottom: 20px; }
        
        .rz-nav-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 12px;
            border-radius: var(--radius-md);
            color: var(--c-text-sub);
            font-size: 13px; font-weight: 500;
            transition: all 0.2s; cursor: pointer;
        }
        .rz-nav-item:hover { background: #f8f9fa; color: var(--c-black); }
        
        .rz-nav-item.active {
            background: var(--c-brand-light);
            color: var(--c-brand-text);
            font-weight: 700;
        }

        .rz-nav-left { display: flex; align-items: center; gap: 10px; }
        .rz-nav-icon { width: 20px; height: 20px; display: flex; justify-content: center; align-items: center; }
        .rz-nav-icon img { width: 100%; height: 100%; object-fit: contain; }

        .rz-badge {
            background: #000; color: #fff; font-size: 9px; padding: 2px 6px; border-radius: 4px;
        }

        /* History */
        .rz-history-sec { flex: 1; overflow: hidden; display: flex; flex-direction: column; margin-top: 10px; }
        .rz-hist-head {
            display: flex; align-items: center; gap: 8px;
            font-size: 12px; font-weight: 700; color: #9ca3af; margin-bottom: 10px; padding: 0 4px;
        }
        .rz-hist-list {
            flex: 1; overflow-y: auto;
            padding-left: 4px; /* Space for scrollbar */
        }
        .rz-hist-list::-webkit-scrollbar { width: 4px; }
        .rz-hist-list::-webkit-scrollbar-thumb { background: #eee; border-radius: 4px; }

        .rz-hist-item {
            padding: 8px 12px; font-size: 12px; color: var(--c-text-sub);
            border-radius: 8px; cursor: pointer;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            transition: 0.2s; margin-bottom: 2px;
        }
        .rz-hist-item:hover { background: #f9fafb; color: var(--c-black); }
        .rz-hist-item.active { color: var(--c-brand); background: var(--c-brand-light); }

        /* Sidebar Footer */
        .rz-side-foot { margin-top: auto; padding-top: 15px; border-top: 1px solid var(--c-border); }
        
        .rz-foot-menu { display: flex; flex-direction: column; gap: 2px; margin-bottom: 10px; }
        .rz-foot-item {
            display: flex; align-items: center; justify-content: flex-end; gap: 8px;
            padding: 6px 4px; font-size: 12px; color: var(--c-text-sub); cursor: pointer;
        }
        .rz-foot-item:hover { color: var(--c-black); }
        .rz-foot-item img { width: 16px; opacity: 0.7; }

        .rz-profile {
            display: flex; align-items: center; justify-content: space-between;
            padding: 8px; border-radius: 12px; cursor: pointer; transition: 0.2s;
        }
        .rz-profile:hover { background: #f3f4f6; }
        .rz-prof-info { display: flex; align-items: center; gap: 8px; text-align: left; } /* LTR name usually */
        .rz-prof-img {
            width: 36px; height: 36px; border-radius: 50%; background: #eee;
            display: grid; place-items: center; font-weight: 700; color: #777; overflow: hidden;
        }
        .rz-prof-text { display: flex; flex-direction: column; align-items: flex-end; }
        .rz-p-name { font-size: 12px; font-weight: 700; color: var(--c-black); }
        .rz-p-mail { font-size: 10px; color: #999; }

        /* ================= MAIN AREA (LEFT) ================= */
        .rz-main {
            flex: 1;
            display: flex; flex-direction: column;
            position: relative;
            background: transparent; /* Main bg is body bg */
            overflow: hidden;
        }

        /* Header */
        .rz-header {
            height: var(--header-height);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 20px;
            flex-shrink: 0;
        }

        .rz-head-right { display: flex; flex-direction: column; }
        .rz-head-title { font-size: 18px; font-weight: 900; margin: 0; color: var(--c-black); }
        .rz-head-sub { font-size: 11px; color: #9ca3af; margin-top: 4px; }

        .rz-head-left { display: flex; align-items: center; gap: 10px; }

        .rz-act-btn {
            width: 38px; height: 38px; border-radius: 50%;
            border: 1px solid #eee; background: #fff;
            display: grid; place-items: center; cursor: pointer;
            transition: 0.2s;
        }
        .rz-act-btn:hover { border-color: #ccc; transform: translateY(-1px); }
        .rz-act-btn img { width: 18px; opacity: 0.7; }

        .rz-btn-back {
            height: 38px; padding: 0 20px;
            background: var(--c-black); color: #fff;
            border-radius: 99px;
            font-size: 12px; font-weight: 700;
            display: flex; align-items: center; gap: 8px;
            transition: 0.2s;
        }
        .rz-btn-back:hover { opacity: 0.9; }

        /* Chat Content */
        .rz-chat-area {
            flex: 1;
            overflow-y: auto;
            position: relative;
            display: flex; flex-direction: column;
            /* Padding bottom for input space */
            padding-bottom: 160px; 
        }

        /* Empty State */
        .rz-empty-state {
            width: 100%; height: 100%;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            /* Shift up slightly to accomodate bottom input */
            padding-bottom: 40px; 
        }

        .rz-spin-img {
            width: 80px; height: 80px; margin-bottom: 20px;
            animation: spin 15s linear infinite;
        }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        .rz-welcome-h { font-size: 20px; font-weight: 900; color: var(--c-black); margin: 0 0 8px 0; }
        .rz-welcome-p { font-size: 13px; color: var(--c-text-sub); margin: 0 0 20px 0; text-align: center; }
        .rz-powered { font-size: 9px; color: #e5e7eb; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 30px; }

        /* Compact Grid for Empty State */
        .rz-suggest-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;
            width: 100%; max-width: 800px; padding: 0 20px;
        }

        .rz-card {
            background: #fff; border: 1px solid #f3f4f6; border-radius: 16px;
            padding: 16px; cursor: pointer; transition: all 0.2s;
            display: flex; flex-direction: column; gap: 8px;
            text-align: right; /* Icons are LTR in image but text is aligned */
        }
        /* Fix for RTL Icon alignment if needed, based on image image.png icons are on top-right in RTL (start) */
        
        .rz-card:hover { border-color: var(--c-brand); transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.03); }

        .rz-card-head { display: flex; justify-content: space-between; align-items: flex-start; }
        .rz-c-icon {
            width: 32px; height: 32px; border-radius: 8px; background: #f9fafb;
            display: grid; place-items: center;
        }
        .rz-c-icon img { width: 18px; }
        
        .rz-c-title { font-size: 13px; font-weight: 700; color: var(--c-text-main); }
        .rz-c-desc { font-size: 11px; color: #9ca3af; line-height: 1.4; }

        /* Messages */
        .rz-msg-container {
            padding: 20px 10% 0 10%;
            display: flex; flex-direction: column; gap: 20px;
        }
        .rz-row { display: flex; gap: 14px; align-items: flex-start; }
        .rz-row.user { flex-direction: row-reverse; }
        
        .rz-bubble {
            max-width: 70%; padding: 12px 16px; border-radius: 18px;
            font-size: 13.5px; line-height: 1.7; position: relative;
        }
        .rz-bubble.ai { background: #fff; color: var(--c-text-main); border: 1px solid #eee; border-top-right-radius: 4px; }
        .rz-bubble.user { background: var(--c-black); color: #fff; border-top-left-radius: 4px; }

        /* ================= INPUT AREA ================= */
        .rz-input-area {
            position: absolute; bottom: 20px; left: 20px; right: 20px;
            z-index: 100;
        }
        
        .rz-input-box {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05);
            overflow: hidden;
            max-width: 900px;
            margin: 0 auto;
            display: flex; flex-direction: column;
            transition: border-color 0.2s;
        }
        .rz-input-box:focus-within { border-color: var(--c-brand); }

        /* Blue Alert Strip */
        .rz-alert-strip {
            background: #e0f7f6; color: var(--c-brand-text);
            padding: 8px 16px; font-size: 11px; font-weight: 700;
            display: flex; align-items: center; justify-content: space-between;
        }
        .rz-alert-content { display: flex; align-items: center; gap: 6px; }

        /* Textarea Zone */
        .rz-input-main { padding: 12px 16px; }
        .rz-textarea {
            width: 100%; border: none; resize: none;
            font-family: inherit; font-size: 13px;
            max-height: 140px; min-height: 24px; background: transparent;
            margin-bottom: 12px;
        }
        .rz-textarea::placeholder { color: #9ca3af; }

        /* Toolbar */
        .rz-toolbar { display: flex; align-items: center; justify-content: space-between; }
        
        /* RTL: Right side contains Actions (Gemini, Attach), Left side contains Send */
        .rz-tools-right { display: flex; align-items: center; gap: 8px; }
        .rz-tools-left { display: flex; align-items: center; }

        .rz-btn-gemini {
            display: flex; align-items: center; gap: 6px;
            padding: 5px 12px; border-radius: 99px;
            border: 1px solid var(--c-brand); color: var(--c-brand);
            background: #fff; font-size: 11px; font-weight: 700; transition: 0.2s;
        }
        .rz-btn-gemini:hover { background: var(--c-brand-light); }

        .rz-tool-icon {
            width: 28px; height: 28px; display: grid; place-items: center;
            color: #9ca3af; transition: 0.2s; cursor: pointer; border-radius: 6px;
        }
        .rz-tool-icon:hover { color: var(--c-text-main); background: #f9fafb; }
        .rz-tool-icon img { width: 16px; opacity: 0.6; }

        .rz-send-btn {
            width: 32px; height: 32px; display: grid; place-items: center;
            color: var(--c-brand); cursor: pointer; transition: transform 0.2s;
        }
        .rz-send-btn img { width: 20px; }
        .rz-send-btn:disabled { opacity: 0.4; cursor: default; }
        .rz-send-btn:not(:disabled):hover { transform: scale(1.1); }

        /* Responsive */
        @media (max-width: 1024px) {
            .rz-sidebar { display: none; }
            .rz-layout { padding: 0; gap: 0; }
            .rz-header { padding: 0 16px; }
            .rz-suggest-grid { grid-template-columns: repeat(2, 1fr); }
            .rz-input-area { left: 10px; right: 10px; bottom: 10px; }
        }
        @media (max-width: 600px) {
            .rz-suggest-grid { grid-template-columns: 1fr; }
            .rz-head-sub { display: none; }
        }

    </style>
</head>
<body>

<div class="rz-layout">
    
    <!-- MAIN CONTENT (Left in RTL Logic -> Flex order) -->
    <main class="rz-main">
        <!-- Header -->
        <header class="rz-header">
            <div class="rz-head-left"> <!-- Action Buttons -->
                <a href="dashboard.php" class="rz-btn-back">
                    بازگشت به داشبورد
                </a>
                <button class="rz-act-btn" title="راهنما">
                    <img src="assets/icons/ai/help-circle.svg">
                </button>
                <button class="rz-act-btn" title="جوایز">
                    <img src="assets/icons/ai/gift.svg">
                </button>
                <button class="rz-act-btn" title="تنظیمات">
                    <img src="assets/icons/ai/wrench-01.svg">
                </button>
            </div>

            <div class="rz-head-right"> <!-- Title -->
                <h1 class="rz-head-title">چت با رایزن</h1>
                <div class="rz-head-sub">من برای آسان سازی و مشاوره در کارهات کنارت هستم، خیالت راحت رئیس!</div>
            </div>
        </header>

        <!-- Chat Area -->
        <div class="rz-chat-area" id="rzChatArea">
            
            <!-- Empty State -->
            <div class="rz-empty-state" id="rzEmptyState">
                <img src="assets/icons/ai/AI-Voice.png" class="rz-spin-img" alt="AI">
                
                <h2 class="rz-welcome-h">به هوش مصنوعی رایزن خوش اومدی</h2>
                <p class="rz-welcome-p">یک کار شروع کن و به من بسپار تا من بقیه کارها رو برات انجام بدم.</p>
                
                <div class="rz-powered">POWERED BY READYSTUDIO</div>

                <div class="rz-suggest-grid">
                    <!-- Cards (3 cols x 2 rows) -->
                    <div class="rz-card" onclick="promptCard('Write Copy for an ad campaign')">
                        <div class="rz-card-head">
                            <div class="rz-c-icon"><img src="assets/icons/ai/document-validation.svg"></div>
                            <div style="flex:1"></div>
                        </div>
                        <div class="rz-c-title">Write Copy</div>
                        <div class="rz-c-desc">Create compelling text for ads, emails, and more.</div>
                    </div>

                    <div class="rz-card" onclick="promptCard('Generate an image concept')">
                        <div class="rz-card-head">
                            <div class="rz-c-icon"><img src="assets/icons/ai/image-02.svg"></div>
                            <div style="flex:1"></div>
                        </div>
                        <div class="rz-c-title">Image Generation</div>
                        <div class="rz-c-desc">Design custom visuals with AI.</div>
                    </div>

                    <div class="rz-card" onclick="promptCard('Research market trends')">
                        <div class="rz-card-head">
                            <div class="rz-c-icon"><img src="assets/icons/ai/ai-search.svg"></div>
                            <div style="flex:1"></div>
                        </div>
                        <div class="rz-c-title">Research</div>
                        <div class="rz-c-desc">Quickly gather and summarize info.</div>
                    </div>

                    <div class="rz-card" onclick="promptCard('Generate a blog article')">
                        <div class="rz-card-head">
                            <div class="rz-c-icon"><img src="assets/icons/ai/license.svg"></div>
                            <div style="flex:1"></div>
                        </div>
                        <div class="rz-c-title">Generate Article</div>
                        <div class="rz-c-desc">Write articles on any topic instantly.</div>
                    </div>

                    <div class="rz-card" onclick="promptCard('Analyze this data')">
                        <div class="rz-card-head">
                            <div class="rz-c-icon"><img src="assets/icons/ai/pie-chart.svg"></div>
                            <div style="flex:1"></div>
                        </div>
                        <div class="rz-c-title">Data Analytics</div>
                        <div class="rz-c-desc">Analyze data with AI-driven insights.</div>
                    </div>

                    <div class="rz-card" onclick="promptCard('Generate Python code')">
                        <div class="rz-card-head">
                            <div class="rz-c-icon"><img src="assets/icons/ai/code.svg"></div>
                            <div style="flex:1"></div>
                        </div>
                        <div class="rz-c-title">Generate Code</div>
                        <div class="rz-c-desc">Produce accurate code fast.</div>
                    </div>
                </div>
            </div>

            <!-- Message Container (Initially Empty) -->
            <div class="rz-msg-container" id="rzMsgContainer" style="display:none;"></div>
        </div>

        <!-- Input Area (Fixed Bottom) -->
        <div class="rz-input-area">
            <div class="rz-input-box">
                <!-- Alert Strip -->
                <div class="rz-alert-strip" id="rzInputAlert">
                    <div class="rz-alert-content">
                        <img src="assets/icons/ai/help-circle.svg" style="width:14px; filter: invert(48%) sepia(79%) saturate(2476%) hue-rotate(156deg) brightness(96%) contrast(101%);">
                        <span>با انتخاب یک ویژگی، رسیدن به هدف برای شما آسان‌تر خواهد شد.</span>
                    </div>
                    <div style="cursor:pointer; opacity:0.6" onclick="document.getElementById('rzInputAlert').style.display='none'">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </div>
                </div>

                <div class="rz-input-main">
                    <textarea id="rzInput" class="rz-textarea" placeholder="پیام خود را بنویسید..." rows="1"></textarea>
                    
                    <div class="rz-toolbar">
                        <!-- Right: Actions -->
                        <div class="rz-tools-right">
                            <button class="rz-btn-gemini">
                                پاسخ با جمنای
                                <img src="assets/icons/ai/flash.svg" style="width:12px; filter: invert(48%) sepia(79%) saturate(2476%) hue-rotate(156deg) brightness(96%) contrast(101%);">
                            </button>
                            <button class="rz-tool-icon" title="Upload Image">
                                <img src="assets/icons/ai/image-02.svg">
                            </button>
                            <button class="rz-tool-icon" title="Attach Link">
                                <img src="assets/icons/ai/link-01.svg">
                            </button>
                            <button class="rz-tool-icon" title="Search">
                                <img src="assets/icons/ai/ai-search.svg">
                            </button>
                        </div>
                        
                        <!-- Left: Send Button -->
                        <div class="rz-tools-left">
                            <button id="rzSendBtn" class="rz-send-btn" disabled>
                                <img src="assets/icons/ai/sent.svg" alt="Send">
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- SIDEBAR (Right in RTL) -->
    <aside class="rz-sidebar">
        <!-- Workspace -->
        <div class="rz-ws-header">
            <div class="rz-ws-info">
                <div class="rz-ws-icon">P</div>
                <div class="rz-ws-title">فضای کاری من</div>
            </div>
            <div style="width:14px; opacity:0.4; cursor:pointer">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
            </div>
        </div>

        <!-- Menu -->
        <div class="rz-nav-list">
            <div class="rz-nav-item active" id="btnNewChat">
                <div class="rz-nav-left">
                    <div class="rz-nav-icon"><img src="assets/icons/ai/rayzen-logo.svg" alt="AI"></div>
                    <span>چت با هوش مصنوعی</span>
                </div>
            </div>

            <div class="rz-nav-item">
                <div class="rz-nav-left">
                    <div class="rz-nav-icon"><img src="assets/icons/ai/zsh.svg" alt="Project"></div>
                    <span>پروژه ها</span>
                </div>
            </div>

            <div class="rz-nav-item">
                <div class="rz-nav-left">
                    <div class="rz-nav-icon"><img src="assets/icons/ai/document-validation.svg" alt="Docs"></div>
                    <span>فایل ها و مستندات</span>
                </div>
            </div>

            <div class="rz-nav-item">
                <div class="rz-nav-left">
                    <div class="rz-nav-icon"><img src="assets/icons/ai/link-01.svg" alt="Shared"></div>
                    <span>اشتراک گذاری شده</span>
                </div>
            </div>

            <div class="rz-nav-item">
                <div class="rz-nav-left">
                    <div class="rz-nav-icon"><img src="assets/icons/ai/image-02.svg" alt="Samples"></div>
                    <span>نمونه ها</span>
                </div>
                <span class="rz-badge">آزمایشی</span>
            </div>
        </div>

        <!-- History -->
        <div class="rz-history-sec">
            <div class="rz-hist-head">
                <img src="assets/icons/ai/pie-chart.svg" style="width:14px; opacity:0.6">
                تاریخچه
            </div>
            <div class="rz-hist-list" id="rzHistoryList">
                <div class="rz-hist-item">در حال بارگذاری...</div>
            </div>
        </div>

        <!-- Footer Profile -->
        <div class="rz-side-foot">
            <div class="rz-foot-menu">
                <div class="rz-foot-item">
                    بازخورد و پیشنهاد
                    <img src="assets/icons/ai/help-circle.svg">
                </div>
                <div class="rz-foot-item">
                    دعوت از دوستان
                    <img src="assets/icons/ai/gift.svg">
                </div>
                <div class="rz-foot-item">
                    تنظیمات
                    <img src="assets/icons/ai/wrench-01.svg">
                </div>
            </div>

            <div class="rz-profile">
                <div style="width:12px; opacity:0.4"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg></div>
                <div class="rz-prof-info">
                    <div class="rz-prof-text">
                        <span class="rz-p-name"><?php echo htmlspecialchars($fullName); ?></span>
                        <span class="rz-p-mail"><?php echo htmlspecialchars($userEmail); ?></span>
                    </div>
                    <div class="rz-prof-img">
                        <?php if($userAvatar): ?>
                            <img src="<?php echo htmlspecialchars($userAvatar); ?>" style="width:100%;height:100%;border-radius:50%">
                        <?php else: ?>
                            <?php echo $initial; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </aside>

</div>

<!-- ================= JAVASCRIPT LOGIC ================= -->
<script>
(() => {
    "use strict";

    // Config
    const CSRF = <?php echo json_encode($csrf); ?>;
    const STORE_ENDPOINT = 'rayzen_store.php';
    const AI_ENDPOINT = 'chatbot_api.php';
    
    // State
    let conversations = [];
    let activeConvId = null;
    let messages = [];
    let isLoading = false;

    // Elements
    const elInput = document.getElementById('rzInput');
    const elSendBtn = document.getElementById('rzSendBtn');
    const elChatArea = document.getElementById('rzChatArea');
    const elEmptyState = document.getElementById('rzEmptyState');
    const elMsgContainer = document.getElementById('rzMsgContainer');
    const elHistoryList = document.getElementById('rzHistoryList');
    const elNewChatBtn = document.getElementById('btnNewChat');

    // Global prompt helper
    window.promptCard = (text) => {
        elInput.value = text;
        elInput.focus();
        adjustInputHeight();
        elSendBtn.disabled = false;
    };

    // Utils
    const escapeHtml = (text) => String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
    const safeJson = async (r) => { try { return await r.json(); } catch(e) { return null; } };
    const scrollToBottom = () => { setTimeout(() => { elChatArea.scrollTop = elChatArea.scrollHeight; }, 50); };
    const adjustInputHeight = () => {
        elInput.style.height = 'auto';
        elInput.style.height = Math.min(elInput.scrollHeight, 140) + 'px';
    };

    // API Wrapper
    const storeApi = async (action, data = {}) => {
        const formData = new URLSearchParams();
        formData.append('csrf', CSRF);
        formData.append('action', action);
        for (const key in data) formData.append(key, data[key]);
        const r = await fetch(STORE_ENDPOINT, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:formData });
        return await safeJson(r);
    };

    // UI Rendering
    const renderConversations = async () => {
        const res = await storeApi('list_conversations');
        conversations = (res && res.success) ? res.conversations : [];
        
        elHistoryList.innerHTML = '';
        conversations.forEach(c => {
            const div = document.createElement('div');
            div.className = `rz-hist-item ${c.id === activeConvId ? 'active' : ''}`;
            div.innerText = c.title || 'مکالمه جدید';
            div.onclick = () => switchConversation(c.id);
            elHistoryList.appendChild(div);
        });
    };

    const renderMessages = () => {
        elMsgContainer.innerHTML = '';

        if (messages.length === 0) {
            elEmptyState.style.display = 'flex';
            elMsgContainer.style.display = 'none';
        } else {
            elEmptyState.style.display = 'none';
            elMsgContainer.style.display = 'flex';
            
            messages.forEach(m => {
                const row = document.createElement('div');
                row.className = `rz-row ${m.role}`;
                
                const bubble = document.createElement('div');
                bubble.className = `rz-bubble ${m.role}`;
                bubble.innerHTML = escapeHtml(m.content).replace(/\n/g, '<br>');
                
                row.appendChild(bubble);
                elMsgContainer.appendChild(row);
            });
            scrollToBottom();
        }
    };

    // Actions
    const switchConversation = async (id) => {
        activeConvId = id;
        const res = await storeApi('get_messages', { id });
        messages = (res && res.success) ? res.messages : [];
        renderMessages();
        renderConversations();
    };

    const createNewConversation = async () => {
        const res = await storeApi('create_conversation', { title: 'مکالمه جدید' });
        if (res && res.success) {
            conversations.unshift(res.conversation);
            await switchConversation(res.conversation.id);
        }
    };

    const sendMessage = async () => {
        const text = elInput.value.trim();
        if (!text || isLoading) return;

        if (!activeConvId) await createNewConversation();

        // UI Update
        elInput.value = '';
        adjustInputHeight();
        elSendBtn.disabled = true;
        
        messages.push({ role: 'user', content: text });
        renderMessages();
        await storeApi('add_message', { id: activeConvId, role: 'user', content: text });

        isLoading = true;

        try {
            // AI Call
            const historyForAI = messages.map(m => ({ role: m.role==='ai'?'assistant':'user', content: m.content }));
            const payload = new URLSearchParams();
            payload.append('action', 'chat');
            payload.append('message', text);
            payload.append('history', JSON.stringify(historyForAI.slice(-6)));
            payload.append('csrf', CSRF);

            const r = await fetch(AI_ENDPOINT, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: payload });
            const d = await safeJson(r);
            const reply = (d && d.success) ? d.reply : 'خطا در دریافت پاسخ.';

            messages.push({ role: 'ai', content: reply });
            renderMessages();
            await storeApi('add_message', { id: activeConvId, role: 'ai', content: reply });

        } catch (e) {
            messages.push({ role: 'ai', content: 'خطا در ارتباط با سرور.' });
            renderMessages();
        } finally {
            isLoading = false;
        }
    };

    // Listeners
    elInput.addEventListener('input', function() {
        adjustInputHeight();
        elSendBtn.disabled = this.value.trim().length === 0;
    });
    
    elInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });

    elSendBtn.addEventListener('click', sendMessage);
    elNewChatBtn.addEventListener('click', createNewConversation);

    // Init
    (async () => {
        await renderConversations();
        if (conversations.length > 0) {
            // Usually stay on empty state until clicked, but user preference can vary.
            // keeping it empty initially for the fresh feel
            elEmptyState.style.display = 'flex';
        }
    })();

})();
</script>

</body>
</html>
