<?php
/**
 * 模板管理页面
 */
require_once 'config.php';
session_start();

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// 检查是否是管理员
$isAdmin = isAdmin();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="tu/xssicon.png">
    <link rel="shortcut icon" type="image/png" href="tu/xssicon.png">
    <link rel="apple-touch-icon" href="tu/xssicon.png">
    
    <title>模板管理 - <?php echo APP_NAME; ?></title>
    <link href="static/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="static/libs/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="static/css/style.css">
    <style>
        .search-box {
            max-width: 600px;
            margin: 2rem auto;
            position: relative;
        }
        
        .search-input {
            width: 100%;
            padding: 1rem 3rem 1rem 1.5rem;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--neon-green);
            box-shadow: 0 0 15px rgba(0, 255, 65, 0.2);
        }
        
        .search-icon {
            position: absolute;
            right: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-tertiary);
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
                <h1><i class="fas fa-file-code"></i> XSS模板库</h1>
                <div>
                    <a href="batch_import_templates.php" class="btn btn-primary me-2">
                        <i class="fas fa-database"></i> 批量导入
                    </a>
                    <a href="import_templates.php" class="btn btn-success">
                        <i class="fas fa-download"></i> 导入更多模板
                    </a>
                </div>
            </div>
            
            <!-- 搜索框 -->
            <div class="search-box">
                <input type="text" class="search-input" id="searchInput" placeholder="搜索模板名称或描述...">
                <i class="fas fa-search search-icon"></i>
            </div>
            
            <!-- 热门排行标题 -->
            <div class="alert alert-warning mb-4">
                <i class="fas fa-fire text-danger"></i> <strong>热门排行</strong> - 依据用户点击次数排序
            </div>
            
            <div class="row">
                <?php
                try {
                    $pdo = getDbConnection();
                    
                    // 检查并添加 click_count 字段（如果不存在）
                    try {
                        // 先检查字段是否存在
                        $checkColumn = $pdo->query("SHOW COLUMNS FROM templates LIKE 'click_count'");
                        if ($checkColumn->rowCount() == 0) {
                            // 字段不存在，添加它
                            $pdo->exec("ALTER TABLE templates ADD COLUMN click_count INT UNSIGNED DEFAULT 0");
                        }
                    } catch (PDOException $e) {
                        // 忽略错误，继续执行
                    }
                    
                    // 按点击次数降序排列
                    $stmt = $pdo->query("SELECT * FROM templates ORDER BY click_count DESC, created_at DESC");
                    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($templates) > 0) {
                        foreach ($templates as $template) {
                            $filename = htmlspecialchars($template['filename']);
                            $content = htmlspecialchars($template['content']);
                            
                            // 从文件名推断类型
                            $name = $filename;
                            $desc = '自定义XSS Payload模板';
                            
                            if (strpos($filename, 'cookie') !== false) {
                                $desc = 'Cookie窃取类Payload';
                            } elseif (strpos($filename, 'keylogger') !== false || strpos($filename, 'key') !== false) {
                                $desc = '键盘记录器类Payload';
                            } elseif (strpos($filename, 'dom') !== false) {
                                $desc = 'DOM窃取类Payload';
                            } elseif (strpos($filename, 'fingerprint') !== false) {
                                $desc = '浏览器指纹采集Payload';
                            } elseif (strpos($filename, 'geo') !== false) {
                                $desc = 'GPS定位窃取Payload';
                            } elseif (strpos($filename, 'form') !== false) {
                                $desc = '表单劫持类Payload';
                            } elseif (strpos($filename, 'alert') !== false || strpos($filename, 'basic') !== false) {
                                $desc = '基础测试类Payload';
                            } elseif (strpos($filename, 'polyglot') !== false) {
                                $desc = 'WAF绕过Polyglot Payload';
                            } elseif (strpos($filename, 'mobile') !== false) {
                                $desc = '移动端专用Payload';
                            } elseif (strpos($filename, 'stealth') !== false || strpos($filename, 'obfuscate') !== false) {
                                $desc = '隐蔽执行类Payload';
                            } elseif (strpos($filename, 'webrtc') !== false) {
                                $desc = 'WebRTC IP泄露Payload';
                            } elseif (strpos($filename, 'clipboard') !== false) {
                                $desc = '剪贴板劫持Payload';
                            } elseif (strpos($filename, 'svg') !== false || strpos($filename, 'animation') !== false) {
                                $desc = 'SVG/动画触发类Payload';
                            }
                            
                            $codePreview = mb_substr($content, 0, 200);
                            if (mb_strlen($content) > 200) {
                                $codePreview .= '...';
                            }
                            
                            $clickCount = isset($template['click_count']) ? intval($template['click_count']) : 0;
                            
                            echo "<div class='col-md-6 col-lg-4 mb-4 fade-in'>";
                            echo "<div class='card h-100 shadow-sm' onclick='viewTemplate({$template['id']})'";
                            echo " data-id='{$template['id']}' data-filename='{$filename}' data-content='" . htmlspecialchars($content, ENT_QUOTES) . "'";
                            echo " style='cursor:pointer;'>";
                            echo "<div class='card-body'>";
                            echo "<h5 class='card-title text-success'><i class='fas fa-code'></i> {$name}";
                            if ($clickCount > 0) {
                                echo " <span class='badge bg-danger'><i class='fas fa-fire'></i> {$clickCount}</span>";
                            }
                            echo "</h5>";
                            echo "<p class='card-text text-warning'><small><strong>{$desc}</strong></small></p>";
                            echo "<pre class='bg-light p-2 rounded' style='font-size:11px; max-height:120px; overflow:hidden;'>{$codePreview}</pre>";
                            echo "<div class='btn-group w-100' role='group'>";
                            echo "<button class='btn btn-sm btn-primary' onclick='copyTemplate(event, {$template['id']})'><i class='fas fa-copy'></i> 复制</button>";
                            echo "<button class='btn btn-sm btn-success' onclick=\"useTemplate('{$filename}'); event.stopPropagation();\"><i class='fas fa-check'></i> 使用</button>";
                            // 管理员显示删除按钮
                            if ($isAdmin) {
                                echo "<button class='btn btn-sm btn-danger' onclick='deleteTemplate(event, {$template['id']}, \"{$filename}\")' title='删除模板'><i class='fas fa-trash'></i></button>";
                            }
                            echo "</div>";
                            echo "</div>";
                            echo "</div>";
                            echo "</div>";
                        }
                    } else {
                        echo '<div class="col-12"><div class="alert alert-info text-center">';
                        echo '<h5><i class="fas fa-info-circle"></i> 暂无模板</h5>';
                        echo '<p>点击右上角"导入更多模板"按钮添加30个现代化XSS Payload</p>';
                        echo '<a href="import_templates.php" class="btn btn-success"><i class="fas fa-download"></i> 立即导入</a>';
                        echo '</div></div>';
                    }
                } catch (PDOException $e) {
                    echo '<div class="col-12"><div class="alert alert-danger">数据库错误: ' . htmlspecialchars($e->getMessage()) . '</div></div>';
                }
                ?>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="static/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- 全局通知系统 -->
    <script src="static/js/notification.js"></script>
    <script>
        function copyTemplate(event, templateId) {
            event.stopPropagation();
            
            const card = event.target.closest('.card');
            const content = card.getAttribute('data-content');
            
            navigator.clipboard.writeText(content).then(() => {
                alert('✅ 代码已复制到剪贴板！');
            }).catch(err => {
                const textarea = document.createElement('textarea');
                textarea.value = content;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                alert('✅ 代码已复制到剪贴板！');
            });
        }
        
        function viewTemplate(templateId) {
            // 增加点击次数
            incrementClickCount(templateId);
            
            const card = document.querySelector(`[data-id="${templateId}"]`);
            const filename = card.getAttribute('data-filename');
            const content = card.getAttribute('data-content');
            
            const modal = document.createElement('div');
            modal.className = 'modal fade show d-block';
            modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-file-code"></i> ${filename}</h5>
                            <button type="button" class="btn-close" onclick="this.closest('.modal').remove()"></button>
                        </div>
                        <div class="modal-body">
                            <pre class="bg-light p-3 rounded"><code>${content.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</code></pre>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-primary" onclick="navigator.clipboard.writeText(this.closest('.modal').querySelector('code').textContent); alert('已复制!');"><i class="fas fa-copy"></i> 复制</button>
                            <button class="btn btn-secondary" onclick="this.closest('.modal').remove()">关闭</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            modal.onclick = function(e) {
                if (e.target === modal) modal.remove();
            };
        }
        
        function useTemplate(filename) {
            if (confirm('将此模板应用到Payload管理中？')) {
                // 获取模板ID
                const card = document.querySelector(`[data-filename="${filename}"]`);
                if (card) {
                    const templateId = card.getAttribute('data-id');
                    incrementClickCount(templateId);
                }
                window.location.href = 'payloads.php?template=' + filename;
            }
        }
        
        // 增加点击次数
        async function incrementClickCount(templateId) {
            try {
                await fetch('api/template_click.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({template_id: templateId})
                });
            } catch (error) {
                console.error('更新点击次数失败:', error);
            }
        }
        
        // 删除模板（仅管理员）
        async function deleteTemplate(event, templateId, filename) {
            event.stopPropagation();
            
            if (!confirm(`确定要删除模板 "${filename}" 吗？\n\n此操作不可恢复！`)) {
                return;
            }
            
            try {
                const response = await fetch('api/template_delete.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({template_id: templateId})
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ ' + data.message);
                    // 移除卡片元素
                    const card = event.target.closest('.col-md-6');
                    if (card) {
                        card.style.transition = 'opacity 0.3s';
                        card.style.opacity = '0';
                        setTimeout(() => card.remove(), 300);
                    }
                } else {
                    alert('❌ ' + data.message);
                }
            } catch (error) {
                alert('❌ 删除失败: ' + error.message);
                console.error('删除模板失败:', error);
            }
        }
        
        // 搜索功能
        const searchInput = document.getElementById('searchInput');
        const templateCards = document.querySelectorAll('.col-md-6.col-lg-4');
        
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            
            templateCards.forEach(cardWrapper => {
                const card = cardWrapper.querySelector('.card');
                const filename = card.getAttribute('data-filename').toLowerCase();
                const content = card.getAttribute('data-content').toLowerCase();
                const title = card.querySelector('.card-title').textContent.toLowerCase();
                const desc = card.querySelector('.card-text').textContent.toLowerCase();
                
                if (searchTerm === '' || 
                    filename.includes(searchTerm) || 
                    content.includes(searchTerm) || 
                    title.includes(searchTerm) || 
                    desc.includes(searchTerm)) {
                    cardWrapper.style.display = 'block';
                    setTimeout(() => {
                        cardWrapper.style.opacity = '1';
                        cardWrapper.style.transform = 'translateY(0)';
                    }, 10);
                } else {
                    cardWrapper.style.opacity = '0';
                    cardWrapper.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        cardWrapper.style.display = 'none';
                    }, 300);
                }
            });
        });
        
        // 添加过渡效果
        templateCards.forEach(card => {
            card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        });
    </script>
</body>
</html>
