<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="admin.php">
            <i class="fas fa-terminal"></i> <?php echo APP_NAME; ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="admin.php"><i class="fas fa-home"></i> 首页</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logs.php"><i class="fas fa-list"></i> 日志</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="payloads.php"><i class="fas fa-code"></i> Payload</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="payload-test.php"><i class="fas fa-vial"></i> Payload测试</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="templates.php"><i class="fas fa-file-code"></i> 模板</a>
                </li>
                <li class="nav-item admin-only">
                    <a class="nav-link" href="users.php"><i class="fas fa-users"></i> 用户管理</a>
                </li>
                <li class="nav-item admin-only">
                    <a class="nav-link" href="database_upgrade.php"><i class="fas fa-database"></i> 数据库升级</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="settings.php"><i class="fas fa-cog"></i> 设置</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="https://hackhub.org/contact-us.html" target="_blank"><i class="fas fa-headset"></i> 技术支持</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> 退出</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- 注入用户角色信息到前端 -->
<script>
    window.currentUser = {
        role: '<?php echo isset($_SESSION['role']) ? $_SESSION['role'] : 'user'; ?>',
        isAdmin: <?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 'true' : 'false'; ?>
    };
    
    // 根据用户角色显示/隐藏管理员专属元素
    document.addEventListener('DOMContentLoaded', function() {
        if (!window.currentUser.isAdmin) {
            // 隐藏所有 admin-only 类的元素
            document.querySelectorAll('.admin-only').forEach(function(element) {
                element.style.display = 'none';
            });
        }
    });
</script>
