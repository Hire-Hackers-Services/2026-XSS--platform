/**
 * 全局新日志通知系统
 * 在所有后台页面自动检测新日志并提示
 */

// 配置
const NOTIFICATION_CONFIG = {
    checkInterval: 5000, // 5秒检测一次
    soundFrequency: 800, // 提示音频率
    soundDuration: 0.5,  // 提示音时长
    notificationTimeout: 8000 // 通知显示时长
};

// 状态管理
let lastLogCount = 0;
let isFirstCheck = true;
let audioContext = null;
let notificationTimer = null;

// 初始化音频上下文
function initAudioContext() {
    if (!audioContext) {
        try {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
            console.log('🔊 音频上下文已初始化');
        } catch (error) {
            console.error('🔇 音频上下文初始化失败:', error);
        }
    }
}

// 播放提示音
function playNotificationSound() {
    try {
        if (!audioContext) {
            initAudioContext();
        }
        
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.value = NOTIFICATION_CONFIG.soundFrequency;
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + NOTIFICATION_CONFIG.soundDuration);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + NOTIFICATION_CONFIG.soundDuration);
        
        console.log('🔔 播放提示音');
    } catch (error) {
        console.error('🔇 音频播放失败:', error);
    }
}

// 显示新日志通知弹窗
function showNewLogNotification(newLogs) {
    console.log('📬 检测到新日志:', newLogs.length, '条');
    
    // 播放提示音
    playNotificationSound();
    
    // 创建通知弹窗
    const modal = document.createElement('div');
    modal.id = 'globalNotification';
    modal.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, rgba(0, 255, 65, 0.95) 0%, rgba(0, 212, 255, 0.95) 100%);
        border: 2px solid #00ff41;
        border-radius: 12px;
        padding: 20px 25px;
        max-width: 400px;
        z-index: 999999;
        box-shadow: 0 10px 40px rgba(0, 255, 65, 0.4), 0 0 20px rgba(0, 255, 65, 0.3);
        animation: slideInRight 0.5s ease, pulse 2s ease-in-out infinite;
        cursor: pointer;
    `;
    
    const logText = newLogs.length === 1 
        ? `新日志来自: ${newLogs[0].ip}` 
        : `${newLogs.length} 条新活动日志`;
    
    modal.innerHTML = `
        <div style="display: flex; align-items: center; gap: 15px;">
            <div style="
                width: 50px;
                height: 50px;
                background: rgba(0, 0, 0, 0.2);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
            ">🔔</div>
            <div style="flex: 1;">
                <div style="
                    color: #000;
                    font-weight: bold;
                    font-size: 16px;
                    margin-bottom: 5px;
                ">新活动检测!</div>
                <div style="
                    color: rgba(0, 0, 0, 0.8);
                    font-size: 14px;
                ">${logText}</div>
                <div style="
                    color: rgba(0, 0, 0, 0.6);
                    font-size: 12px;
                    margin-top: 5px;
                ">点击查看详情</div>
            </div>
            <div style="
                color: rgba(0, 0, 0, 0.5);
                font-size: 12px;
                cursor: pointer;
            " onclick="event.stopPropagation(); this.closest('div').parentElement.remove();">✕</div>
        </div>
    `;
    
    // 添加动画样式
    if (!document.getElementById('notificationStyles')) {
        const style = document.createElement('style');
        style.id = 'notificationStyles';
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes pulse {
                0%, 100% {
                    box-shadow: 0 10px 40px rgba(0, 255, 65, 0.4), 0 0 20px rgba(0, 255, 65, 0.3);
                }
                50% {
                    box-shadow: 0 10px 50px rgba(0, 255, 65, 0.6), 0 0 30px rgba(0, 255, 65, 0.5);
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(modal);
    
    // 点击跳转到日志页面
    modal.onclick = function() {
        window.location.href = '/logs.php';
    };
    
    // 自动关闭
    setTimeout(() => {
        if (modal.parentElement) {
            modal.style.animation = 'slideInRight 0.5s ease reverse';
            setTimeout(() => modal.remove(), 500);
        }
    }, NOTIFICATION_CONFIG.notificationTimeout);
}

// 检测新日志
async function checkForNewLogs() {
    try {
        const response = await fetch('/api/logs_stats.php');
        if (!response.ok) {
            console.error('📊 统计API错误:', response.status);
            return;
        }
        
        const data = await response.json();
        const currentLogCount = data.total_logs || 0;
        
        // 检测新日志（跳过首次检测）
        if (!isFirstCheck && currentLogCount > lastLogCount) {
            const newLogCount = currentLogCount - lastLogCount;
            console.log('🆕 检测到', newLogCount, '条新日志!');
            
            // 获取最新日志详情
            try {
                const logsResponse = await fetch(`/api/logs.php?page=1&per_page=${newLogCount}`);
                const logsData = await logsResponse.json();
                
                if (logsData.logs && logsData.logs.length > 0) {
                    showNewLogNotification(logsData.logs);
                }
            } catch (error) {
                console.error('❌ 获取日志详情失败:', error);
            }
        } else if (isFirstCheck) {
            console.log('🕵️ 首次检测，跳过通知');
            isFirstCheck = false;
        }
        
        lastLogCount = currentLogCount;
        
    } catch (error) {
        console.error('❌ 检测新日志失败:', error);
    }
}

// 启动通知系统
function startNotificationSystem() {
    console.log('🚀 全局通知系统启动');
    console.log('⏰ 检测间隔:', NOTIFICATION_CONFIG.checkInterval / 1000, '秒');
    
    // 首次检测
    checkForNewLogs();
    
    // 定时检测
    notificationTimer = setInterval(() => {
        console.log('🔄 [定时检测] 检查新日志...');
        checkForNewLogs();
    }, NOTIFICATION_CONFIG.checkInterval);
    
    console.log('✅ 定时器已设置, ID:', notificationTimer);
}

// 停止通知系统
function stopNotificationSystem() {
    if (notificationTimer) {
        clearInterval(notificationTimer);
        console.log('⏹️ 通知系统已停止');
    }
}

// 页面加载时自动初始化
document.addEventListener('DOMContentLoaded', function() {
    console.log('📡 全局通知系统 - 初始化');
    
    // 用户首次交互时初始化音频
    document.addEventListener('click', function initAudio() {
        initAudioContext();
        document.removeEventListener('click', initAudio);
    }, { once: true });
    
    // 启动通知系统
    startNotificationSystem();
});

// 页面卸载时清理
window.addEventListener('beforeunload', function() {
    stopNotificationSystem();
});
