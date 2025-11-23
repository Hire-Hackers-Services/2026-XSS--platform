// 基础Alert弹窗 - 黑客仓库XSS平台模板
// 这是一个最简单的XSS测试payload，用于验证XSS漏洞是否存在

(function() {
    'use strict';
    
    // 显示测试信息
    alert('💻 黑客仓库XSS平台测试\n\nXSS漏洞验证成功！\n\n时间: ' + new Date().toLocaleString());
    
    // 在控制台输出信息
    console.log('========================================');
    console.log('💻 黑客仓库XSS平台 - XSS测试成功');
    console.log('========================================');
    console.log('当前页面: ' + window.location.href);
    console.log('User-Agent: ' + navigator.userAgent);
    console.log('执行时间: ' + new Date().toISOString());
    
    // 可选：在页面上显示提示
    try {
        var div = document.createElement('div');
        div.style.cssText = 'position:fixed;top:10px;right:10px;background:#28a745;color:white;padding:10px;border-radius:5px;z-index:9999;font-size:14px;';
        div.innerHTML = '💻 XSS测试成功！';
        document.body.appendChild(div);
        
        // 3秒后自动移除提示
        setTimeout(function() {
            if (div.parentNode) {
                div.parentNode.removeChild(div);
            }
        }, 3000);
    } catch (e) {
        console.log('无法添加页面提示:', e);
    }
    
})();