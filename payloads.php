<?php
/**
 * Payload管理页面
 */
require_once 'config.php';
session_start();

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payload管理 - <?php echo APP_NAME; ?></title>
    <link href="static/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="static/libs/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="static/css/style.css">
    <!-- CodeMirror 使用 CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.min.css">
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/javascript/javascript.min.js"></script>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
                <h1><i class="fas fa-code"></i> Payload管理</h1>
                <button class="btn btn-success" onclick="createNew()">
                    <i class="fas fa-plus"></i> 新建Payload
                </button>
            </div>
            
            <div class="card shadow fade-in mb-4">
                <div class="card-header">
                    <h5 class="m-0"><i class="fas fa-folder"></i> Payload列表</h5>
                </div>
                <div class="card-body">
                    <div class="row" id="payloadList">
                        <div class="col-12 text-center py-5">
                            <div class="spinner-border text-success" role="status">
                                <span class="visually-hidden">加载中...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow fade-in" id="editorContainer" style="display:none;">
                <div class="card-header">
                    <h5 class="m-0"><i class="fas fa-edit"></i> 编辑: <span id="currentFilename">-</span></h5>
                </div>
                <div class="card-body">
                    <div class="editor-container mb-3">
                        <textarea id="codeEditor"></textarea>
                    </div>
                    
                    <div class="btn-group mb-3" role="group">
                        <button class="btn btn-success" onclick="savePayload()">
                            <i class="fas fa-save"></i> 保存
                        </button>
                        <button class="btn btn-danger" onclick="deletePayload()">
                            <i class="fas fa-trash"></i> 删除
                        </button>
                        <button class="btn btn-secondary" onclick="closeEditor()">
                            <i class="fas fa-times"></i> 取消
                        </button>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6 class="alert-heading"><i class="fas fa-link"></i> 访问地址</h6>
                        <div class="input-group">
                            <input type="text" class="form-control" id="payloadUrl" readonly onclick="this.select()">
                            <button class="btn btn-outline-secondary" onclick="copyUrl()">
                                <i class="fas fa-copy"></i> 复制
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="static/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- 全局通知系统 -->
    <script src="static/js/notification.js"></script>
    
    <script>
        let editor;
        let currentFile = null;
        let currentPayloadId = null;
        
        // 初始化CodeMirror
        document.addEventListener('DOMContentLoaded', function() {
            editor = CodeMirror.fromTextArea(document.getElementById('codeEditor'), {
                mode: 'javascript',
                lineNumbers: true,
                theme: 'default',
                indentUnit: 4,
                indentWithTabs: false
            });
            
            // 检查是否有模板参数
            const urlParams = new URLSearchParams(window.location.search);
            const templateName = urlParams.get('template');
            
            if (templateName) {
                // 从数据库加载模板
                loadTemplateFromDB(templateName);
            }
        });
        
        // 从数据库加载模板
        async function loadTemplateFromDB(filename) {
            try {
                console.log('正在加载模板:', filename);
                const response = await fetch('api/templates_get.php?filename=' + encodeURIComponent(filename));
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                console.log('模板加载响应:', data);
                
                if (data.success && data.template) {
                    // 设置当前文件名（移除.js后缀如果存在）
                    const baseFilename = filename.replace(/\.js$/, '');
                    currentFile = baseFilename + '.js';
                    currentPayloadId = null; // 清空，因为这是新文件
                    
                    document.getElementById('currentFilename').textContent = currentFile;
                    document.getElementById('editorContainer').style.display = 'block';
                    
                    // 使用setTimeout确保容器已显示，然后设置内容并刷新编辑器
                    setTimeout(() => {
                        editor.setValue(data.template.content);
                        editor.refresh(); // 刷新CodeMirror以正确显示内容
                    }, 10);
                    
                    document.getElementById('payloadUrl').value = `<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/payloads/'; ?>${currentFile}`;
                    
                    console.log('模板已加载到编辑器，准备自动保存...');
                    
                    // 显示提示，并自动保存
                    showNotification('✅ 模板已加载，正在保存...', 'info');
                    
                    // 自动保存
                    setTimeout(() => {
                        savePayload();
                    }, 500);
                } else {
                    showNotification('⚠️ ' + (data.message || '模板不存在'), 'error');
                }
            } catch (error) {
                console.error('加载模板失败:', error);
                showNotification('❌ 加载模板失败：' + error.message, 'error');
            }
        }
        
        async function loadPayloads() {
            try {
                const response = await fetch('api/payloads.php');
                const data = await response.json();
                
                if (!data.success) {
                    alert('加载失败: ' + (data.message || '未知错误'));
                    return;
                }
                
                const listDiv = document.getElementById('payloadList');
                if (data.payloads && data.payloads.length > 0) {
                    listDiv.innerHTML = data.payloads.map(payload => `
                        <div class="col-md-4 col-lg-3 mb-3">
                            <div class="card h-100 payload-item" onclick="loadPayload(${payload.id})" style="cursor:pointer;" data-id="${payload.id}">
                                <div class="card-body">
                                    <h6><i class="fas fa-file-code"></i> ${payload.filename}</h6>
                                    <small class="text-muted">${new Date(payload.updated_at).toLocaleString('zh-CN')}</small>
                                    <br>
                                    <small class="text-warning">大小: ${(payload.size / 1024).toFixed(2)} KB</small>
                                </div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    listDiv.innerHTML = '<div class="col-12 text-center py-5"><p class="text-muted">暂无Payload文件，点击“新建Payload”开始创建</p></div>';
                }
            } catch (error) {
                console.error('加载失败:', error);
                alert('加载失败，请刷新页面重试');
            }
        }
        
        async function loadPayload(payloadId) {
            try {
                const response = await fetch('api/payloads.php', {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: payloadId})
                });
                const data = await response.json();
                
                if (!data.success) {
                    alert('加载失败: ' + (data.message || '未知错误'));
                    return;
                }
                
                const payload = data.payload;
                currentFile = payload.filename;
                currentPayloadId = payload.id;
                
                document.getElementById('currentFilename').textContent = payload.filename;
                document.getElementById('editorContainer').style.display = 'block';
                
                // 使用setTimeout确保容器已显示，然后设置内容并刷新编辑器
                setTimeout(() => {
                    editor.setValue(payload.content || '');
                    editor.refresh();
                }, 10);
                
                document.getElementById('payloadUrl').value = `<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/payloads/'; ?>${payload.filename}`;
                
                // 高亮当前选中项
                document.querySelectorAll('.payload-item').forEach(item => {
                    item.classList.remove('active');
                    if (item.getAttribute('data-id') == payloadId) {
                        item.classList.add('active');
                    }
                });
            } catch (error) {
                console.error('加载失败:', error);
                alert('加载失败，请重试');
            }
        }
        
        function createNew() {
            // 创建美化的弹窗
            const modal = document.createElement('div');
            modal.id = 'createPayloadModal';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                backdrop-filter: blur(5px);
            `;
            
            modal.innerHTML = `
                <div style="
                    background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%);
                    border: 1px solid #00ff41;
                    box-shadow: 0 0 30px rgba(0, 255, 65, 0.3);
                    border-radius: 4px;
                    padding: 0;
                    width: 90%;
                    max-width: 500px;
                    font-family: 'Microsoft YaHei', sans-serif;
                    overflow: hidden;
                ">
                    <!-- 头部 -->
                    <div style="
                        background: rgba(0, 255, 65, 0.1);
                        border-bottom: 1px solid rgba(0, 255, 65, 0.3);
                        padding: 20px 25px;
                    ">
                        <h5 style="
                            margin: 0;
                            color: #00ff41;
                            font-size: 1.2rem;
                            font-weight: 600;
                            letter-spacing: 1px;
                        "><i class="fas fa-plus-circle"></i> 新建Payload</h5>
                    </div>
                    
                    <!-- 内容 -->
                    <div style="padding: 25px;">
                        <label style="
                            display: block;
                            color: #888;
                            font-size: 0.875rem;
                            margin-bottom: 10px;
                            text-transform: uppercase;
                            letter-spacing: 0.5px;
                        ">文件名</label>
                        <input type="text" id="newPayloadFilename" placeholder="例如: my-payload" style="
                            width: 100%;
                            padding: 12px 15px;
                            background: #1a1a1a;
                            border: 1px solid #333;
                            border-radius: 2px;
                            color: #e0e0e0;
                            font-size: 0.95rem;
                            font-family: 'Roboto Mono', monospace;
                            transition: all 0.3s ease;
                        " onfocus="this.style.borderColor='#00ff41'; this.style.boxShadow='0 0 10px rgba(0, 255, 65, 0.2)';" onblur="this.style.borderColor='#333'; this.style.boxShadow='none';">
                        <small style="
                            display: block;
                            color: #666;
                            margin-top: 8px;
                            font-size: 0.8rem;
                        ">• 自动添加 .js 后缀</small>
                    </div>
                    
                    <!-- 底部按钮 -->
                    <div style="
                        background: rgba(0, 0, 0, 0.3);
                        border-top: 1px solid rgba(0, 255, 65, 0.2);
                        padding: 15px 25px;
                        display: flex;
                        gap: 10px;
                        justify-content: flex-end;
                    ">
                        <button onclick="closeCreateModal()" style="
                            background: rgba(80, 80, 80, 0.6);
                            border: 1px solid rgba(100, 100, 100, 0.8);
                            color: #e0e0e0;
                            padding: 10px 25px;
                            border-radius: 2px;
                            cursor: pointer;
                            font-size: 0.875rem;
                            font-family: 'Microsoft YaHei', sans-serif;
                            transition: all 0.3s ease;
                            backdrop-filter: blur(5px);
                        " onmouseover="this.style.background='rgba(100, 100, 100, 0.8)';" onmouseout="this.style.background='rgba(80, 80, 80, 0.6)';">
                            <i class="fas fa-times"></i> 取消
                        </button>
                        <button onclick="confirmCreate()" style="
                            background: rgba(0, 255, 65, 0.15);
                            border: 1px solid rgba(0, 255, 65, 0.5);
                            color: #00ff41;
                            padding: 10px 25px;
                            border-radius: 2px;
                            cursor: pointer;
                            font-size: 0.875rem;
                            font-family: 'Microsoft YaHei', sans-serif;
                            transition: all 0.3s ease;
                            backdrop-filter: blur(5px);
                            box-shadow: 0 0 15px rgba(0, 255, 65, 0.2);
                        " onmouseover="this.style.background='rgba(0, 255, 65, 0.25)'; this.style.boxShadow='0 0 25px rgba(0, 255, 65, 0.4)';" onmouseout="this.style.background='rgba(0, 255, 65, 0.15)'; this.style.boxShadow='0 0 15px rgba(0, 255, 65, 0.2)';">
                            <i class="fas fa-check"></i> 确定
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // 自动聚焦输入框
            setTimeout(() => {
                document.getElementById('newPayloadFilename').focus();
            }, 100);
            
            // 点击背景关闭
            modal.onclick = function(e) {
                if (e.target === modal) closeCreateModal();
            };
            
            // 回车键确认
            document.getElementById('newPayloadFilename').onkeypress = function(e) {
                if (e.key === 'Enter') confirmCreate();
            };
        }
        
        function closeCreateModal() {
            const modal = document.getElementById('createPayloadModal');
            if (modal) modal.remove();
        }
        
        function confirmCreate() {
            const filename = document.getElementById('newPayloadFilename').value.trim();
            if (!filename) {
                document.getElementById('newPayloadFilename').style.borderColor = '#ff3b3b';
                return;
            }
            
            const fullname = filename.endsWith('.js') ? filename : filename + '.js';
            currentFile = fullname;
            currentPayloadId = null;
            document.getElementById('currentFilename').textContent = fullname;
            document.getElementById('editorContainer').style.display = 'block';
            
            setTimeout(() => {
                editor.setValue('// 新建Payload\n');
                editor.refresh();
            }, 10);
            
            document.getElementById('payloadUrl').value = `<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/payloads/'; ?>${fullname}`;
            closeCreateModal();
        }
        
        async function savePayload() {
            if (!currentFile) {
                showNotification('❌ 请先选择或创建文件', 'error');
                return;
            }
            
            const content = editor.getValue();
            const formData = new FormData();
            formData.append('filename', currentFile);
            formData.append('content', content);
            
            try {
                console.log('正在保存Payload:', currentFile);
                const response = await fetch('api/payloads.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                console.log('保存响应:', data);
                
                if (data.success) {
                    showNotification('✅ 保存成功！', 'success');
                    // 刷新Payload列表，但不刷新整个页面
                    await loadPayloads();
                    // 如果是从模板创建的，滚动到编辑器
                    document.getElementById('editorContainer').scrollIntoView({ behavior: 'smooth', block: 'start' });
                } else {
                    // 显示详细错误信息
                    let errorMsg = '保存失败: ' + data.message;
                    if (data.threats && data.threats.length > 0) {
                        errorMsg += '\n检测到的问题：\n• ' + data.threats.join('\n• ');
                    }
                    if (data.hint) {
                        errorMsg += '\n提示：' + data.hint;
                    }
                    showNotification('❌ ' + errorMsg, 'error');
                }
            } catch (error) {
                console.error('保存失败:', error);
                showNotification('❌ 保存失败：' + error.message, 'error');
            }
        }
        
        // 显示通知
        function showNotification(message, type = 'info') {
            // 移除旧通知
            const oldNotification = document.getElementById('payloadNotification');
            if (oldNotification) oldNotification.remove();
            
            const colors = {
                'success': '#00ff41',
                'error': '#ff3b3b',
                'info': '#00d4ff',
                'warning': '#ffa500'
            };
            
            const notification = document.createElement('div');
            notification.id = 'payloadNotification';
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: rgba(0, 0, 0, 0.95);
                border: 2px solid ${colors[type]};
                color: ${colors[type]};
                padding: 15px 25px;
                border-radius: 8px;
                font-family: 'Microsoft YaHei', sans-serif;
                font-size: 0.9rem;
                z-index: 10000;
                box-shadow: 0 0 30px ${colors[type]}40;
                animation: slideIn 0.3s ease;
                max-width: 400px;
                white-space: pre-line;
            `;
            
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}" style="font-size: 1.2rem;"></i>
                    <span>${message}</span>
                </div>
            `;
            
            // 添加动画
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(400px); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(400px); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
            
            document.body.appendChild(notification);
            
            // 点击关闭
            notification.onclick = () => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            };
            
            // 自动关闭（错误信息显示更长时间）
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => notification.remove(), 300);
                }
            }, type === 'error' ? 8000 : 3000);
        }
        
        async function deletePayload() {
            if (!currentPayloadId) return;
            
            if (!confirm(`确定要删除 ${currentFile} 吗？`)) return;
            
            try {
                const response = await fetch('api/payloads.php', {
                    method: 'DELETE',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: currentPayloadId})
                });
                
                const data = await response.json();
                if (data.success) {
                    alert('删除成功');
                    closeEditor();
                    loadPayloads();
                } else {
                    alert('删除失败: ' + (data.message || '未知错误'));
                }
            } catch (error) {
                console.error('删除失败:', error);
                alert('删除失败，请重试');
            }
        }
        
        function closeEditor() {
            document.getElementById('editorContainer').style.display = 'none';
            currentFile = null;
            currentPayloadId = null;
            document.querySelectorAll('.payload-item').forEach(item => item.classList.remove('active'));
        }
        
        function copyUrl() {
            const input = document.getElementById('payloadUrl');
            input.select();
            document.execCommand('copy');
            alert('链接已复制到剪贴板');
        }
        
        // 页面加载时获取列表
        loadPayloads();
    </script>
</body>
</html>
