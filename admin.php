<?php
/**
 * 管理后台首页
 */
require_once 'config.php';
session_start();

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if (!checkIpWhitelist()) {
    http_response_code(403);
    die('IP地址 ' . $_SERVER['REMOTE_ADDR'] . ' 不在白名单中');
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="tu/xssicon.png">
    <link rel="shortcut icon" type="image/png" href="tu/xssicon.png">
    <link rel="apple-touch-icon" href="tu/xssicon.png">
    
    <title>管理后台 - <?php echo APP_NAME; ?></title>
    <link href="static/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="static/libs/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="static/css/style.css">
    <!-- Chart.js 使用 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
                <h1><i class="fas fa-tachometer-alt"></i> 管理后台</h1>
            </div>
            
            <!-- 统计卡片 - 科幻趋势图 -->
            <div class="row mb-3">
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm h-100 stats-chart-card">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <small class="text-muted d-block">总请求数</small>
                                    <h3 class="mb-0 text-success" id="totalLogs">0</h3>
                                </div>
                                <i class="fas fa-database fa-2x text-success" style="opacity:0.3;"></i>
                            </div>
                            <div style="height:50px;">
                                <canvas id="chartTotal"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm h-100 stats-chart-card">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <small class="text-muted d-block">今日请求</small>
                                    <h3 class="mb-0 text-info" id="todayLogs">0</h3>
                                </div>
                                <i class="fas fa-chart-line fa-2x text-info" style="opacity:0.3;"></i>
                            </div>
                            <div style="height:50px;">
                                <canvas id="chartToday"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm h-100 stats-chart-card">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <small class="text-muted d-block">唯一IP</small>
                                    <h3 class="mb-0 text-warning" id="uniqueIps">0</h3>
                                </div>
                                <i class="fas fa-network-wired fa-2x text-warning" style="opacity:0.3;"></i>
                            </div>
                            <div style="height:50px;">
                                <canvas id="chartIps"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 最近活动 -->
            <div class="card shadow fade-in">
                <div class="card-header">
                    <h5 class="m-0"><i class="fas fa-clock"></i> 最近活动</h5>
                </div>
                <div class="card-body">
                    <div id="recentLogs" class="table-responsive"></div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="static/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- 全局通知系统 -->
    <script src="static/js/notification.js"></script>
    <script>
        // ========== 立即执行的调试信息 ==========
        console.log('%c========================================', 'color: #00ff41; font-weight: bold;');
        console.log('%c🚀 XSS后台管理系统 - 加载中...', 'color: #00ff41; font-size: 16px; font-weight: bold;');
        console.log('%c========================================', 'color: #00ff41; font-weight: bold;');
        console.log('📅 加载时间:', new Date().toLocaleString());
        console.log('🌐 页面URL:', window.location.href);
        
        // 趋势图配置
        let chartTotal, chartToday, chartIps;
        let totalData = [];
        let todayData = [];
        let ipsData = [];
        
        const AUTO_REFRESH_INTERVAL = 5000; // 5秒自动刷新
        
        // 初始化
        document.addEventListener('DOMContentLoaded', function() {
            console.log('%c========================================', 'color: #00ff41; font-weight: bold;');
            console.log('%c✅ DOM加载完成 - 开始初始化', 'color: #4CAF50; font-size: 14px; font-weight: bold;');
            console.log('%c========================================', 'color: #00ff41; font-weight: bold;');
            console.log('🚀 后台管理系统初始化');
            
            console.log('📊 初始化图表...');
            initCharts();
            
            console.log('📈 首次加载统计数据...');
            loadStats();
            
            // 静默自动刷新
            console.log('⏱️ 设置定时器: 每', AUTO_REFRESH_INTERVAL / 1000, '秒刷新一次');
            const refreshTimer = setInterval(() => {
                console.log('%c🔄 [定时器触发] 静默刷新数据...', 'color: #2196F3; font-weight: bold;');
                loadStats();
            }, AUTO_REFRESH_INTERVAL);
            
            console.log('✅ 定时器已设置, ID:', refreshTimer);
            
            // 检查是否是首次登录，如果是则显示法律声明
            const legalNoticeShown = localStorage.getItem('legalNoticeShown');
            if (!legalNoticeShown) {
                console.log('ℹ️ 首次访问，将显示法律声明');
                setTimeout(() => {
                    showLegalNotice();
                }, 1000); // 延迟1秒显示
            } else {
                console.log('ℹ️ 法律声明已显示过，跳过');
            }
            
            console.log('%c========================================', 'color: #00ff41; font-weight: bold;');
            console.log('%c🎉 初始化完成! 系统运行中...', 'color: #4CAF50; font-size: 14px; font-weight: bold;');
            console.log('%c========================================', 'color: #00ff41; font-weight: bold;');
        });
        function initCharts() {
            const chartConfig = (data, color) => ({
                type: 'line',
                data: {
                    labels: Array(20).fill(''),
                    datasets: [{
                        data: data,
                        borderColor: color,
                        backgroundColor: color.replace('1)', '0.1)'),
                        borderWidth: 2,
                        tension: 0.4,
                        pointRadius: 0,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { display: false },
                        y: { display: false }
                    },
                    interaction: { mode: 'index', intersect: false }
                }
            });
            
            // 初始化数据
            for (let i = 0; i < 20; i++) {
                totalData.push(0);
                todayData.push(0);
                ipsData.push(0);
            }
            
            chartTotal = new Chart(document.getElementById('chartTotal'), 
                chartConfig(totalData, 'rgba(0, 255, 65, 1)'));
            chartToday = new Chart(document.getElementById('chartToday'), 
                chartConfig(todayData, 'rgba(0, 212, 255, 1)'));
            chartIps = new Chart(document.getElementById('chartIps'), 
                chartConfig(ipsData, 'rgba(255, 193, 7, 1)'));
        }
        
        // 加载统计数据
        async function loadStats() {
            try {
                console.log('📊 正在加载统计数据...');
                const response = await fetch('api/logs_stats.php');
                
                console.log('统计API响应状态:', response.status, response.statusText);
                
                if (!response.ok) {
                    const text = await response.text();
                    console.error('统计API错误响应:', text);
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                console.log('📈 统计数据:', data);
                
                const currentLogCount = data.total_logs || 0;
                
                document.getElementById('totalLogs').textContent = currentLogCount;
                document.getElementById('todayLogs').textContent = data.today_logs || 0;
                document.getElementById('uniqueIps').textContent = data.unique_ips || 0;
                
                // 更新趋势图
                totalData.shift();
                totalData.push(data.total_logs || 0);
                chartTotal.data.datasets[0].data = totalData;
                chartTotal.update('none');
                
                todayData.shift();
                todayData.push(data.today_logs || 0);
                chartToday.data.datasets[0].data = todayData;
                chartToday.update('none');
                
                ipsData.shift();
                ipsData.push(data.unique_ips || 0);
                chartIps.data.datasets[0].data = ipsData;
                chartIps.update('none');
                
                // 加载最近日志
                loadRecentLogs();
            } catch (error) {
                console.error('加载统计数据失败:', error);
                // 显示错误提示
                document.getElementById('totalLogs').textContent = '错误';
                document.getElementById('todayLogs').textContent = '错误';
                document.getElementById('uniqueIps').textContent = '错误';
            }
        }
        
        // 加载最近日志
        async function loadRecentLogs() {
            try {
                console.log('正在加载最近日志...');
                const response = await fetch('api/logs.php?page=1&per_page=10');
                
                console.log('日志API响应状态:', response.status, response.statusText);
                
                if (!response.ok) {
                    const text = await response.text();
                    console.error('日志API错误响应:', text);
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                console.log('日志数据:', data);
                
                const recentDiv = document.getElementById('recentLogs');
                if (data.logs && data.logs.length > 0) {
                    recentDiv.innerHTML = `
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>时间</th>
                                    <th>IP地址</th>
                                    <th>请求方法</th>
                                    <th>来源</th>
                                    <th>用户</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.logs.map(log => {
                                    const referer = log.referer || '-';
                                    const isLink = referer !== '-' && (referer.startsWith('http://') || referer.startsWith('https://'));
                                    const displayReferer = referer.length > 40 ? referer.substring(0, 40) + '...' : referer;
                                    const refererDisplay = isLink 
                                        ? `<a href="${referer}" target="_blank" class="text-warning" title="${referer}">${displayReferer}</a>` 
                                        : `<span title="${referer}">${displayReferer}</span>`;
                                    
                                    // 获取用户名，如果没有则显示"系统"
                                    const username = log.username || '系统';
                                    
                                    return `
                                    <tr>
                                        <td>${log.created_at || '-'}</td>
                                        <td><span class="badge bg-secondary">${log.ip || '-'}</span></td>
                                        <td><span class="badge bg-primary">${log.method || '-'}</span></td>
                                        <td>${refererDisplay}</td>
                                        <td><span class="badge bg-success">${username}</span></td>
                                        <td>
                                            <div class="text-end">
                                                <a href="logs.php" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> 查看更多
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    `;
                                }).join('')}
                            </tbody>
                        </table>
                    `;
                } else {
                    recentDiv.innerHTML = '<p class="text-center text-muted">暂无最近活动</p>';
                }
            } catch (error) {
                console.error('加载最近日志失败:', error);
                const recentDiv = document.getElementById('recentLogs');
                recentDiv.innerHTML = `<p class="text-center text-danger">加载失败: ${error.message}</p>`;
            }
        }
        
        // 显示法律声明弹窗
        function showLegalNotice() {
            const modal = document.createElement('div');
            modal.id = 'legalNoticeModal';
            
            // 添加样式（包含滚动条美化）
            const style = document.createElement('style');
            style.textContent = `
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes scanLine {
                    0% { transform: translateX(-100%); }
                    100% { transform: translateX(100%); }
                }
                /* 美化滚动条 */
                .legal-scroll::-webkit-scrollbar {
                    width: 8px;
                }
                .legal-scroll::-webkit-scrollbar-track {
                    background: rgba(255, 255, 255, 0.05);
                    border-radius: 4px;
                }
                .legal-scroll::-webkit-scrollbar-thumb {
                    background: rgba(0, 255, 65, 0.3);
                    border-radius: 4px;
                    transition: all 0.3s ease;
                }
                .legal-scroll::-webkit-scrollbar-thumb:hover {
                    background: rgba(0, 255, 65, 0.5);
                    box-shadow: 0 0 10px rgba(0, 255, 65, 0.3);
                }
                /* Firefox 滚动条 */
                .legal-scroll {
                    scrollbar-width: thin;
                    scrollbar-color: rgba(0, 255, 65, 0.3) rgba(255, 255, 255, 0.05);
                }
            `;
            document.head.appendChild(style);
            
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.95);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                animation: fadeIn 0.3s ease;
                backdrop-filter: blur(10px);
            `;
            
            modal.innerHTML = `
                <div style="
                    background: #0a0a0a;
                    border: 1px solid #00ff41;
                    box-shadow: 0 0 50px rgba(0, 255, 65, 0.2), inset 0 0 30px rgba(0, 255, 65, 0.05);
                    max-width: 700px;
                    width: 92%;
                    max-height: 85vh;
                    overflow: hidden;
                    font-family: 'Roboto Mono', 'Courier New', monospace;
                ">
                    <!-- 顶部扫描线效果 -->
                    <div style="
                        position: relative;
                        background: linear-gradient(180deg, rgba(0, 255, 65, 0.15) 0%, rgba(0, 0, 0, 0) 100%);
                        padding: 25px 30px;
                        border-bottom: 1px solid rgba(0, 255, 65, 0.3);
                    ">
                        <div style="
                            position: absolute;
                            top: 0;
                            left: 0;
                            right: 0;
                            height: 2px;
                            background: linear-gradient(90deg, transparent, #00ff41, transparent);
                            animation: scanLine 3s linear infinite;
                        "></div>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="
                                width: 50px;
                                height: 50px;
                                background: rgba(0, 255, 65, 0.1);
                                border: 2px solid #00ff41;
                                border-radius: 50%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                box-shadow: 0 0 20px rgba(0, 255, 65, 0.3);
                            ">
                                <i class="fas fa-shield-alt" style="color: #00ff41; font-size: 24px;"></i>
                            </div>
                            <div>
                                <h2 style="
                                    margin: 0;
                                    color: #00ff41;
                                    font-size: 1.4rem;
                                    font-weight: 600;
                                    letter-spacing: 2px;
                                    text-transform: uppercase;
                                    text-shadow: 0 0 10px rgba(0, 255, 65, 0.5);
                                ">SECURITY NOTICE</h2>
                                <p style="
                                    margin: 5px 0 0 0;
                                    color: #888;
                                    font-size: 0.75rem;
                                    letter-spacing: 1px;
                                ">法律声明与使用协议</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 内容滚动区域 -->
                    <div style="
                        padding: 30px;
                        color: #e0e0e0;
                        line-height: 1.9;
                        font-size: 14px;
                        max-height: 50vh;
                        overflow-y: auto;
                    " class="legal-scroll">
                        <!-- 重要提示框 -->
                        <div style="
                            background: rgba(255, 59, 59, 0.08);
                            border: 1px solid #ff3b3b;
                            padding: 20px;
                            margin-bottom: 25px;
                            position: relative;
                        ">
                            <div style="
                                position: absolute;
                                top: -1px;
                                left: -1px;
                                width: 4px;
                                height: 40px;
                                background: #ff3b3b;
                                box-shadow: 0 0 10px #ff3b3b;
                            "></div>
                            <div style="display: flex; align-items: flex-start; gap: 12px;">
                                <i class="fas fa-exclamation-circle" style="color: #ff3b3b; font-size: 20px; margin-top: 2px;"></i>
                                <div>
                                    <p style="margin: 0 0 10px 0; color: #ff3b3b; font-weight: 600; font-size: 15px; text-transform: uppercase; letter-spacing: 1px;">重要安全警告</p>
                                    <p style="margin: 0; color: #c0c0c0; line-height: 1.7;">本XSS平台仅供<strong style="color: #00ff41;">授权安全测试</strong>使用。任何未经授权的渗透测试行为均属<strong style="color: #ff3b3b;">违法行为</strong>，将承担相应法律责任。</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 禁止行为 -->
                        <div style="margin-bottom: 25px;">
                            <h3 style="
                                color: #ff3b3b;
                                font-size: 13px;
                                text-transform: uppercase;
                                letter-spacing: 2px;
                                margin: 0 0 15px 0;
                                padding-bottom: 10px;
                                border-bottom: 1px solid rgba(255, 59, 59, 0.3);
                            "><i class="fas fa-ban"></i> PROHIBITED ACTIVITIES</h3>
                            <div style="display: grid; gap: 12px;">
                                <div style="display: flex; gap: 12px; padding: 12px; background: rgba(255, 255, 255, 0.02); border-left: 3px solid #ff3b3b;">
                                    <i class="fas fa-times" style="color: #ff3b3b; margin-top: 3px;"></i>
                                    <span><strong style="color: #00ff41;">政府机构</strong>及其下属网站、系统、平台的任何形式渗透测试</span>
                                </div>
                                <div style="display: flex; gap: 12px; padding: 12px; background: rgba(255, 255, 255, 0.02); border-left: 3px solid #ff3b3b;">
                                    <i class="fas fa-times" style="color: #ff3b3b; margin-top: 3px;"></i>
                                    <span><strong style="color: #00ff41;">企业公司</strong>、商业组织的生产环境、办公系统等未授权测试</span>
                                </div>
                                <div style="display: flex; gap: 12px; padding: 12px; background: rgba(255, 255, 255, 0.02); border-left: 3px solid #ff3b3b;">
                                    <i class="fas fa-times" style="color: #ff3b3b; margin-top: 3px;"></i>
                                    <span><strong style="color: #00ff41;">教育机构</strong>、医疗系统、金融平台等关键基础设施</span>
                                </div>
                                <div style="display: flex; gap: 12px; padding: 12px; background: rgba(255, 255, 255, 0.02); border-left: 3px solid #ff3b3b;">
                                    <i class="fas fa-times" style="color: #ff3b3b; margin-top: 3px;"></i>
                                    <span>任何<strong style="color: #00ff41;">未获得明确书面授权</strong>的第三方网站或系统</span>
                                </div>
                                <div style="display: flex; gap: 12px; padding: 12px; background: rgba(255, 255, 255, 0.02); border-left: 3px solid #ff3b3b;">
                                    <i class="fas fa-times" style="color: #ff3b3b; margin-top: 3px;"></i>
                                    <span>利用本平台进行<strong style="color: #ff3b3b;">恶意攻击、数据窃取、勒索</strong>等犯罪活动</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 合法用途 -->
                        <div style="margin-bottom: 25px;">
                            <h3 style="
                                color: #00ff41;
                                font-size: 13px;
                                text-transform: uppercase;
                                letter-spacing: 2px;
                                margin: 0 0 15px 0;
                                padding-bottom: 10px;
                                border-bottom: 1px solid rgba(0, 255, 65, 0.3);
                            "><i class="fas fa-check-circle"></i> LEGITIMATE USE CASES</h3>
                            <div style="display: grid; gap: 12px;">
                                <div style="display: flex; gap: 12px; padding: 12px; background: rgba(0, 255, 65, 0.03); border-left: 3px solid #00ff41;">
                                    <i class="fas fa-check" style="color: #00ff41; margin-top: 3px;"></i>
                                    <span>已获得<strong style="color: #00ff41;">正式授权书/授权函</strong>的安全测试项目</span>
                                </div>
                                <div style="display: flex; gap: 12px; padding: 12px; background: rgba(0, 255, 65, 0.03); border-left: 3px solid #00ff41;">
                                    <i class="fas fa-check" style="color: #00ff41; margin-top: 3px;"></i>
                                    <span>个人/团队<strong style="color: #00ff41;">自有项目</strong>的安全评估与漏洞研究</span>
                                </div>
                                <div style="display: flex; gap: 12px; padding: 12px; background: rgba(0, 255, 65, 0.03); border-left: 3px solid #00ff41;">
                                    <i class="fas fa-check" style="color: #00ff41; margin-top: 3px;"></i>
                                    <span>网络安全<strong style="color: #00ff41;">教育培训、学术研究</strong>等非商业用途</span>
                                </div>
                                <div style="display: flex; gap: 12px; padding: 12px; background: rgba(0, 255, 65, 0.03); border-left: 3px solid #00ff41;">
                                    <i class="fas fa-check" style="color: #00ff41; margin-top: 3px;"></i>
                                    <span>符合当地法律法规的<strong style="color: #00ff41;">合法渗透测试</strong>项目</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 法律条款 -->
                        <div style="
                            background: rgba(255, 170, 0, 0.05);
                            border: 1px solid rgba(255, 170, 0, 0.3);
                            padding: 20px;
                            margin-bottom: 20px;
                        ">
                            <h3 style="
                                color: #ffaa00;
                                font-size: 13px;
                                text-transform: uppercase;
                                letter-spacing: 2px;
                                margin: 0 0 12px 0;
                            "><i class="fas fa-gavel"></i> LEGAL DISCLAIMER</h3>
                            <div style="color: #b0b0b0; font-size: 13px; line-height: 1.8;">
                                <p style="margin: 0 0 10px 0;">• 使用本平台即表示您已完全理解并同意遵守上述所有条款</p>
                                <p style="margin: 0 0 10px 0;">• 违反规定造成的一切法律后果由<strong style="color: #ffaa00;">使用者本人承担</strong></p>
                                <p style="margin: 0;">• 本声明适用于<strong style="color: #ffaa00;">国际网络安全法律</strong>及您所在地区的相关法规</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 底部操作栏 -->
                    <div style="
                        background: rgba(0, 0, 0, 0.5);
                        padding: 20px 30px;
                        border-top: 1px solid rgba(0, 255, 65, 0.2);
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    ">
                        <div style="display: flex; align-items: center; gap: 10px; color: #888; font-size: 12px;">
                            <i class="fas fa-clock"></i>
                            <span id="timerText">请仔细阅读 (<span id="countdown">5</span>s)</span>
                        </div>
                        <button id="agreeBtn" disabled style="
                            background: #333;
                            border: 1px solid #555;
                            color: #666;
                            padding: 12px 35px;
                            cursor: not-allowed;
                            font-family: 'Roboto Mono', monospace;
                            font-size: 13px;
                            font-weight: 600;
                            text-transform: uppercase;
                            letter-spacing: 2px;
                            transition: all 0.3s ease;
                            position: relative;
                            overflow: hidden;
                        ">
                            <span id="btnText">等待中...</span>
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // 5秒倒计时
            let countdown = 5;
            const countdownEl = modal.querySelector('#countdown');
            const timerTextEl = modal.querySelector('#timerText');
            const agreeBtn = modal.querySelector('#agreeBtn');
            const btnText = modal.querySelector('#btnText');
            
            const timer = setInterval(() => {
                countdown--;
                countdownEl.textContent = countdown;
                
                if (countdown <= 0) {
                    clearInterval(timer);
                    timerTextEl.innerHTML = '<i class="fas fa-check-circle" style="color: #00ff41;"></i> 已完成阅读';
                    agreeBtn.disabled = false;
                    agreeBtn.style.cssText = `
                        background: rgba(0, 255, 65, 0.15);
                        border: 1px solid #00ff41;
                        color: #00ff41;
                        padding: 12px 35px;
                        cursor: pointer;
                        font-family: 'Roboto Mono', monospace;
                        font-size: 13px;
                        font-weight: 600;
                        text-transform: uppercase;
                        letter-spacing: 2px;
                        transition: all 0.3s ease;
                        box-shadow: 0 0 20px rgba(0, 255, 65, 0.2);
                    `;
                    btnText.textContent = 'I AGREE';
                    
                    agreeBtn.onmouseover = function() {
                        this.style.background = 'rgba(0, 255, 65, 0.25)';
                        this.style.boxShadow = '0 0 30px rgba(0, 255, 65, 0.4)';
                    };
                    agreeBtn.onmouseout = function() {
                        this.style.background = 'rgba(0, 255, 65, 0.15)';
                        this.style.boxShadow = '0 0 20px rgba(0, 255, 65, 0.2)';
                    };
                    
                    agreeBtn.onclick = function() {
                        closeLegalNotice();
                    };
                }
            }, 1000);
        }
        
        // 关闭法律声明弹窗
        function closeLegalNotice() {
            const modal = document.getElementById('legalNoticeModal');
            if (modal) modal.remove();
            // 标记为已显示
            localStorage.setItem('legalNoticeShown', 'true');
        }
        
        // 初始化
        document.addEventListener('DOMContentLoaded', function() {
            console.log('%c========================================', 'color: #00ff41; font-weight: bold;');
            console.log('%c✅ DOM加载完成 - 开始初始化', 'color: #4CAF50; font-size: 14px; font-weight: bold;');
            console.log('%c========================================', 'color: #00ff41; font-weight: bold;');
            console.log('🚀 后台管理系统初始化');
            console.log('⏰ 自动刷新间隔:', AUTO_REFRESH_INTERVAL / 1000, '秒');
            console.log('🔊 音频提示: 启用');
            console.log('🔔 新日志通知: 启用');
            
            // 用户首次与页面交互时初始化音频(符合浏览器策略)
            document.addEventListener('click', function initAudio() {
                initAudioContext();
                document.removeEventListener('click', initAudio);
            }, { once: true });
            
            console.log('📊 初始化图表...');
            initCharts();
            
            console.log('📈 首次加载统计数据...');
            loadStats();
            
            // 静默自动刷新
            console.log('⏱️ 设置定时器: 每', AUTO_REFRESH_INTERVAL / 1000, '秒刷新一次');
            const refreshTimer = setInterval(() => {
                console.log('%c🔄 [定时器触发] 静默刷新数据...', 'color: #2196F3; font-weight: bold;');
                loadStats();
            }, AUTO_REFRESH_INTERVAL);
            
            console.log('✅ 定时器已设置, ID:', refreshTimer);
            
            // 检查是否是首次登录，如果是则显示法律声明
            const legalNoticeShown = localStorage.getItem('legalNoticeShown');
            if (!legalNoticeShown) {
                console.log('ℹ️ 首次访问，将显示法律声明');
                setTimeout(() => {
                    showLegalNotice();
                }, 1000); // 延迟1秒显示
            } else {
                console.log('ℹ️ 法律声明已显示过，跳过');
            }
            
            console.log('%c========================================', 'color: #00ff41; font-weight: bold;');
            console.log('%c🎉 初始化完成! 系统运行中...', 'color: #4CAF50; font-size: 14px; font-weight: bold;');
            console.log('%c========================================', 'color: #00ff41; font-weight: bold;');
        });
    </script>
</body>
</html>
