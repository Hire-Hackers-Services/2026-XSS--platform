/**
 * 代码雨背景效果 - 随机跳动风格
 */
(function() {
    // 创建canvas元素
    const canvas = document.createElement('canvas');
    canvas.id = 'matrix-bg';
    document.body.insertBefore(canvas, document.body.firstChild);
    
    const ctx = canvas.getContext('2d');
    
    // 设置canvas尺寸
    function resizeCanvas() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }
    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);
    
    // 字符集 - 代码字符、数字、符号
    const chars = '01ABCDEFGHIJKLMNOPQRSTUVWXYZ<>{}[]()=/\\|@#$%^&*';
    const charArray = chars.split('');
    
    // 跳动字符数组 - 随机分布
    const fontSize = 24; // 更大的字体
    const numChars = 1000; // 增加字符数量，从80增加到150
    const characters = [];
    
    // 初始化随机分布的字符
    for (let i = 0; i < numChars; i++) {
        characters.push({
            char: charArray[Math.floor(Math.random() * charArray.length)],
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height,
            opacity: Math.random() * 0.5 + 0.3 // 0.3-0.8 的透明度
        });
    }
    
    // 绘制函数
    function draw() {
        // 半透明黑色背景（创建淡入淡出效果）
        ctx.fillStyle = 'rgba(10, 10, 10, 0.1)';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        // 绘制每个字符
        characters.forEach(item => {
            // 设置文字样式 - 明显的灰色，带透明度变化
            ctx.fillStyle = `rgba(80, 80, 80, ${item.opacity})`;
            ctx.font = `${fontSize}px 'Courier New', monospace`;
            
            // 绘制字符
            ctx.fillText(item.char, item.x, item.y);
            
            // 随机跳动 - 位置和字符都会随机变化
            if (Math.random() > 0.95) {
                // 随机改变字符
                item.char = charArray[Math.floor(Math.random() * charArray.length)];
            }
            
            if (Math.random() > 0.92) {
                // 随机跳动位置（小范围）
                item.x += (Math.random() - 0.5) * 30;
                item.y += (Math.random() - 0.5) * 30;
                
                // 边界检测，超出屏幕则重新随机分布
                if (item.x < 0 || item.x > canvas.width || item.y < 0 || item.y > canvas.height) {
                    item.x = Math.random() * canvas.width;
                    item.y = Math.random() * canvas.height;
                }
            }
            
            // 透明度跳动
            if (Math.random() > 0.9) {
                item.opacity = Math.random() * 0.5 + 0.3;
            }
        });
    }
    
    // 动画循环
    setInterval(draw, 80);
})();
