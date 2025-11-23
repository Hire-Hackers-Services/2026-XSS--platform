<?php
/**
 * 网站地图 - 展示所有可访问的页面
 */
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网站地图 - 蓝莲花XSS在线平台 | Sitemap</title>
    <meta name="description" content="蓝莲花XSS在线平台完整网站地图，包含所有功能页面导航：首页、Payload测试、XSS知识库、用户登录等，快速找到您需要的功能。">
    <meta name="keywords" content="网站地图,sitemap,XSS平台,导航,页面索引,蓝莲花">
    <meta name="robots" content="index, follow">
    
    <link rel="icon" type="image/png" href="tu/xssicon.png">
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
            font-size: 0.6rem;
            line-height: 1.2;
            color: var(--neon-green);
            text-shadow: 0 0 10px rgba(0, 255, 65, 0.5);
            margin-bottom: 20px;
            white-space: pre;
            font-family: monospace;
        }
        
        h1 {
            font-size: 2.5rem;
            background: linear-gradient(45deg, var(--neon-green), var(--neon-cyan), var(--neon-red));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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
        
        /* 分类标题 */
        .category-title {
            font-size: 1.8rem;
            color: var(--neon-cyan);
            margin: 40px 0 20px;
            padding-left: 15px;
            border-left: 4px solid var(--neon-cyan);
            text-shadow: 0 0 15px rgba(0, 255, 255, 0.5);
        }
        
        /* 链接网格 */
        .link-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        /* 链接卡片 */
        .link-card {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.95), rgba(13, 13, 13, 0.95));
            border: 2px solid var(--border-color);
            border-radius: 15px;
            padding: 25px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(0, 255, 65, 0.1);
            cursor: pointer;
            text-decoration: none;
            display: block;
        }
        
        .link-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(0, 255, 65, 0.1), transparent);
            transform: rotate(45deg);
            transition: all 0.5s;
        }
        
        .link-card:hover::before {
            left: 100%;
        }
        
        .link-card:hover {
            transform: translateY(-5px);
            border-color: var(--neon-green);
            box-shadow: 0 15px 40px rgba(0, 255, 65, 0.3), inset 0 0 20px rgba(0, 255, 65, 0.1);
        }
        
        .card-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            display: inline-block;
        }
        
        .link-card h3 {
            color: var(--neon-green);
            font-size: 1.4rem;
            margin-bottom: 10px;
            font-weight: bold;
        }
        
        .link-card p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .card-url {
            color: var(--neon-cyan);
            font-size: 0.85rem;
            font-family: 'Courier New', monospace;
            opacity: 0.8;
            word-break: break-all;
        }
        
        .card-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: bold;
            margin-top: 10px;
            border: 1px solid;
        }
        
        .badge-public {
            background: rgba(0, 255, 65, 0.15);
            color: var(--neon-green);
            border-color: var(--neon-green);
        }
        
        .badge-login {
            background: rgba(255, 170, 0, 0.15);
            color: var(--neon-orange);
            border-color: var(--neon-orange);
        }
        
        .badge-wiki {
            background: rgba(0, 212, 255, 0.15);
            color: var(--neon-cyan);
            border-color: var(--neon-cyan);
        }
        
        /* 返回主页按钮 */
        .back-home {
            text-align: center;
            margin: 50px 0 30px;
        }
        
        .back-btn {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, rgba(0, 255, 65, 0.2), rgba(0, 255, 65, 0.1));
            border: 2px solid var(--neon-green);
            border-radius: 10px;
            color: var(--neon-green);
            text-decoration: none;
            font-weight: bold;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 0 20px rgba(0, 255, 65, 0.2);
        }
        
        .back-btn:hover {
            background: rgba(0, 255, 65, 0.3);
            box-shadow: 0 0 30px rgba(0, 255, 65, 0.5);
            transform: translateY(-3px);
            color: var(--neon-green);
        }
        
        /* 页脚 */
        .footer {
            text-align: center;
            padding: 30px 20px;
            color: var(--text-secondary);
            font-size: 0.9rem;
            border-top: 1px solid rgba(0, 255, 65, 0.2);
            margin-top: 50px;
        }
        
        /* 响应式 */
        @media (max-width: 768px) {
            h1 { font-size: 1.8rem; }
            .category-title { font-size: 1.4rem; }
            .link-grid { grid-template-columns: 1fr; gap: 15px; }
            .link-card { padding: 20px; }
            .logo-ascii { font-size: 0.5rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 头部 -->
        <div class="header">
            <div class="logo-ascii">
 ____  _ _                           
/ ___|(_) |_ ___ _ __ ___   __ _ _ __  
\___ \| | __/ _ \ '_ ` _ \ / _` | '_ \ 
 ___) | | ||  __/ | | | | | (_| | |_) |
|____/|_|\__\___|_| |_| |_|\__,_| .__/ 
                                |_|    
            </div>
            <h1><i class="fas fa-sitemap"></i> 网站地图 Sitemap</h1>
            <p class="subtitle">快速导航 · 探索平台所有功能</p>
        </div>

        <!-- 主要页面 -->
        <h2 class="category-title"><i class="fas fa-home"></i> 主要页面</h2>
        <div class="link-grid">
            <a href="index.html" class="link-card">
                <div class="card-icon" style="color: var(--neon-green);">
                    <i class="fas fa-home"></i>
                </div>
                <h3>平台首页</h3>
                <p>蓝莲花XSS在线平台主页，了解平台功能特性、核心优势和最新动态</p>
                <div class="card-url">https://xss.li/</div>
                <span class="card-badge badge-public">公开访问</span>
            </a>
            
            <a href="login.php" class="link-card">
                <div class="card-icon" style="color: var(--neon-cyan);">
                    <i class="fas fa-sign-in-alt"></i>
                </div>
                <h3>用户登录</h3>
                <p>登录您的账户，开始使用XSS平台的完整功能</p>
                <div class="card-url">https://xss.li/login.php</div>
                <span class="card-badge badge-public">公开访问</span>
            </a>
            
            <a href="admin.php" class="link-card">
                <div class="card-icon" style="color: var(--neon-purple);">
                    <i class="fas fa-tachometer-alt"></i>
                </div>
                <h3>后台管理</h3>
                <p>XSS平台管理后台，查看日志、管理Payload、监控数据回传</p>
                <div class="card-url">https://xss.li/admin.php</div>
                <span class="card-badge badge-login">需要登录</span>
            </a>
        </div>

        <!-- 核心功能 -->
        <h2 class="category-title"><i class="fas fa-flask"></i> 核心功能</h2>
        <div class="link-grid">
            <a href="payload-test.php" class="link-card">
                <div class="card-icon" style="color: var(--neon-orange);">
                    <i class="fas fa-vial"></i>
                </div>
                <h3>Payload 测试平台</h3>
                <p>专业XSS Payload测试工具，支持Cookie窃取、键盘记录、表单劫持、GPS定位等7大功能</p>
                <div class="card-url">https://xss.li/payload-test.php</div>
                <span class="card-badge badge-login">需要登录使用</span>
            </a>
            
            <a href="logs.php" class="link-card">
                <div class="card-icon" style="color: var(--neon-red);">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h3>数据日志</h3>
                <p>实时查看XSS数据回传日志，包含Cookie、键盘记录、GPS位置等敏感信息</p>
                <div class="card-url">https://xss.li/logs.php</div>
                <span class="card-badge badge-login">需要登录</span>
            </a>
            
            <a href="payloads.php" class="link-card">
                <div class="card-icon" style="color: var(--neon-cyan);">
                    <i class="fas fa-code"></i>
                </div>
                <h3>Payload 管理</h3>
                <p>管理您的XSS Payload代码，支持创建、编辑、删除和快速复制</p>
                <div class="card-url">https://xss.li/payloads.php</div>
                <span class="card-badge badge-login">需要登录</span>
            </a>
            
            <a href="templates.php" class="link-card">
                <div class="card-icon" style="color: var(--neon-purple);">
                    <i class="fas fa-file-code"></i>
                </div>
                <h3>模板库</h3>
                <p>丰富的XSS模板库，提供多种场景的现成Payload代码，开箱即用</p>
                <div class="card-url">https://xss.li/templates.php</div>
                <span class="card-badge badge-login">需要登录</span>
            </a>
        </div>

        <!-- XSS知识库 -->
        <h2 class="category-title"><i class="fas fa-book"></i> XSS 知识库</h2>
        <div class="link-grid">
            <a href="wiki.html" class="link-card">
                <div class="card-icon" style="color: var(--neon-cyan);">
                    <i class="fas fa-book-open"></i>
                </div>
                <h3>XSS 知识库首页</h3>
                <p>全面的XSS攻击知识库，从基础概念到高级技巧，助您深入理解跨站脚本攻击</p>
                <div class="card-url">https://xss.li/wiki.html</div>
                <span class="card-badge badge-wiki">知识库</span>
            </a>
            
            <a href="wiki/xss-basics.html" class="link-card">
                <div class="card-icon" style="color: var(--neon-green);">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h3>XSS 基础入门</h3>
                <p>什么是XSS？反射型、存储型、DOM型XSS的区别和原理详解</p>
                <div class="card-url">https://xss.li/wiki/xss-basics.html</div>
                <span class="card-badge badge-wiki">知识库</span>
            </a>
            
            <a href="wiki/payload-guide.html" class="link-card">
                <div class="card-icon" style="color: var(--neon-orange);">
                    <i class="fas fa-code-branch"></i>
                </div>
                <h3>Payload 编写指南</h3>
                <p>详细的Payload编写教程，掌握各种XSS攻击载荷的构造技巧</p>
                <div class="card-url">https://xss.li/wiki/payload-guide.html</div>
                <span class="card-badge badge-wiki">知识库</span>
            </a>
            
            <a href="wiki/cookie-login-guide.html" class="link-card">
                <div class="card-icon" style="color: var(--neon-red);">
                    <i class="fas fa-cookie-bite"></i>
                </div>
                <h3>Cookie 登录教程</h3>
                <p>如何使用窃取的Cookie进行会话劫持，完整操作步骤演示</p>
                <div class="card-url">https://xss.li/wiki/cookie-login-guide.html</div>
                <span class="card-badge badge-wiki">知识库</span>
            </a>
            
            <a href="wiki/bypass-techniques.html" class="link-card">
                <div class="card-icon" style="color: var(--neon-purple);">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>绕过防护技巧</h3>
                <p>WAF绕过、CSP绕过、过滤器绕过等高级XSS攻击技术</p>
                <div class="card-url">https://xss.li/wiki/bypass-techniques.html</div>
                <span class="card-badge badge-wiki">知识库</span>
            </a>
            
            <a href="wiki/defense-strategies.html" class="link-card">
                <div class="card-icon" style="color: var(--neon-cyan);">
                    <i class="fas fa-lock"></i>
                </div>
                <h3>防御策略</h3>
                <p>XSS漏洞的有效防御方法，输入验证、输出编码、CSP配置等</p>
                <div class="card-url">https://xss.li/wiki/defense-strategies.html</div>
                <span class="card-badge badge-wiki">知识库</span>
            </a>
        </div>

        <!-- 系统管理 -->
        <h2 class="category-title"><i class="fas fa-cog"></i> 系统管理</h2>
        <div class="link-grid">
            <a href="users.php" class="link-card">
                <div class="card-icon" style="color: var(--neon-green);">
                    <i class="fas fa-users"></i>
                </div>
                <h3>用户管理</h3>
                <p>管理平台用户，查看用户信息、权限设置、封禁操作</p>
                <div class="card-url">https://xss.li/users.php</div>
                <span class="card-badge badge-login">管理员权限</span>
            </a>
            
            <a href="settings.php" class="link-card">
                <div class="card-icon" style="color: var(--neon-cyan);">
                    <i class="fas fa-sliders-h"></i>
                </div>
                <h3>系统设置</h3>
                <p>平台系统配置，个性化设置、安全选项、通知配置</p>
                <div class="card-url">https://xss.li/settings.php</div>
                <span class="card-badge badge-login">需要登录</span>
            </a>
        </div>

        <!-- 返回主页 -->
        <div class="back-home">
            <a href="index.html" class="back-btn">
                <i class="fas fa-arrow-left"></i> 返回主页
            </a>
        </div>

        <!-- 页脚 -->
        <div class="footer">
            <p>&copy; 2024 蓝莲花XSS在线平台 | Blue Lotus XSS Platform</p>
            <p style="margin-top: 10px; font-size: 0.85rem;">
                <i class="fas fa-shield-alt"></i> 仅供安全研究和授权测试使用 · 请勿用于非法用途
            </p>
        </div>
    </div>

    <!-- 添加浮动粒子效果 -->
    <script>
        // 生成浮动粒子
        for(let i = 0; i < 30; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 15 + 's';
            particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
            document.body.appendChild(particle);
        }
    </script>
</body>
</html>
