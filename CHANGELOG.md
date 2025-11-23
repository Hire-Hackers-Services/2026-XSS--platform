# 更新日志 / Changelog

本文档记录蓝莲花XSS在线平台的所有重要更改。

---

## [2.0.8] - 2024-11-23

### 🎉 重大更新 - GitHub一键部署版本

#### ✨ 新增功能
- **Docker支持**: 完整的Docker和Docker Compose支持
- **环境变量配置**: 支持.env文件配置，更安全更灵活
- **一键部署脚本**: deploy.sh自动化部署脚本
- **GitHub Actions**: CI/CD自动构建和测试
- **健康检查端点**: /health接口用于容器健康检查
- **数据库自动初始化**: init.sql自动创建表结构
- **完整文档**: README.md, DEPLOY.md, LICENSE等

#### 🔧 改进
- 配置文件重构，支持环境变量优先级
- 优化Docker镜像大小（使用Alpine Linux）
- 添加Nginx、PHP-FPM、MySQL等完整配置
- 增强安全性配置（HTTP安全头、权限控制等）
- 改进日志管理和错误处理

#### 📦 新增文件
- `.env.example` - 环境配置模板
- `Dockerfile` - Docker镜像构建文件
- `docker-compose.yml` - Docker编排配置
- `deploy.sh` - 一键部署脚本
- `.gitignore` - Git忽略文件配置
- `README.md` - 项目说明文档
- `DEPLOY.md` - 详细部署指南
- `LICENSE` - MIT开源协议
- `.github/workflows/docker-build.yml` - GitHub Actions配置
- `docker/nginx/default.conf` - Nginx配置
- `docker/php/php.ini` - PHP配置
- `docker/php/www.conf` - PHP-FPM配置
- `docker/mysql/init.sql` - 数据库初始化SQL
- `docker/supervisor/supervisord.conf` - Supervisor配置

#### 🐛 修复
- 修复config.php环境变量加载问题
- 优化数据库连接池配置
- 修复文件权限问题

---

## [2.0.7] - 2024-11-20

### ✨ 新增功能
- 添加sitemap.php网站地图页面
- 优化index.html首页UI设计
- 核心功能演示SVG插图美化

### 🔧 改进
- 优化导航栏配色方案
- 改进登录注册按钮布局
- 增强赛博朋克风格效果

---

## [2.0.6] - 2024-11-15

### ✨ 新增功能
- Payload测试平台7大功能完善
- XSS知识库内容扩充
- 用户管理系统增强

### 🔧 改进
- 优化日志查询性能
- 改进数据导出功能
- 增强安全防护机制

### 🐛 修复
- 修复Cookie窃取功能bug
- 修复键盘记录兼容性问题
- 优化GPS定位准确性

---

## [2.0.5] - 2024-11-10

### ✨ 新增功能
- 新增IP黑名单管理
- 添加登录失败限制
- 实现临时IP封禁功能

### 🔧 改进
- 优化数据库索引
- 改进Session管理
- 增强CSRF防护

---

## [2.0.4] - 2024-11-05

### ✨ 新增功能
- 新增模板库系统（58+模板）
- 添加Payload分类管理
- 实现代码编辑器高亮

### 🔧 改进
- 优化前端性能
- 改进响应式布局
- 增强浏览器兼容性

---

## [2.0.3] - 2024-10-30

### ✨ 新增功能
- 政府网站自动过滤
- 敏感数据脱敏处理
- 审计日志功能

### 🐛 修复
- 修复XSS过滤器绕过问题
- 修复SQL注入漏洞
- 优化错误处理机制

---

## [2.0.2] - 2024-10-25

### ✨ 新增功能
- 多用户系统支持
- 角色权限管理
- 用户封禁功能

### 🔧 改进
- 优化数据库结构
- 改进API接口设计
- 增强数据加密

---

## [2.0.1] - 2024-10-20

### ✨ 新增功能
- 实时数据监控面板
- 数据统计图表
- 导出功能增强

### 🐛 修复
- 修复日志查询bug
- 优化内存使用
- 修复时区问题

---

## [2.0.0] - 2024-10-15

### 🎉 重大版本更新

#### ✨ 核心功能
- 全新UI设计（赛博朋克风格）
- 7大Payload测试功能
- 完整的XSS知识库
- 智能模板库系统

#### 🔒 安全增强
- 完善的权限控制
- 数据加密存储
- CSRF防护
- SQL注入防护

#### 📊 数据分析
- 实时监控面板
- 详细日志分析
- 数据可视化

---

## [1.0.0] - 2024-09-01

### 🎉 首次发布

#### ✨ 基础功能
- Cookie窃取功能
- 基础日志记录
- 简单的Payload管理
- 用户登录系统

---

## 版本规范说明

本项目遵循[语义化版本](https://semver.org/lang/zh-CN/)规范：

- **主版本号**：不兼容的API修改
- **次版本号**：向下兼容的功能性新增
- **修订号**：向下兼容的问题修正

### 图例说明
- 🎉 重大更新
- ✨ 新增功能
- 🔧 改进优化
- 🐛 Bug修复
- 🔒 安全更新
- 📦 依赖更新
- 📚 文档更新
- ⚠️ 废弃功能
- 🗑️ 移除功能

---

## 升级指南

### 从2.0.7升级到2.0.8

```bash
# 1. 备份数据
docker exec xss_mysql mysqldump -u root -p xss_platform > backup_20241123.sql

# 2. 拉取最新代码
git pull

# 3. 重新构建镜像
docker-compose down
docker-compose build
docker-compose up -d

# 4. 检查服务状态
docker-compose ps
docker-compose logs -f
```

### 从1.x升级到2.0

> ⚠️ **重要**：2.0版本包含重大更改，建议全新安装

如需从1.x升级：
1. 导出所有数据
2. 按照2.0部署文档全新安装
3. 手动迁移数据

---

## 未来计划

### v2.1.0 (计划中)
- [ ] 支持Kubernetes部署
- [ ] 添加Prometheus监控集成
- [ ] 实现分布式部署支持
- [ ] 增强API接口文档

### v2.2.0 (计划中)
- [ ] 机器学习检测异常流量
- [ ] 自动化渗透测试报告
- [ ] 集成第三方通知（钉钉、企微等）
- [ ] 移动端APP

---

## 反馈与贡献

欢迎通过以下方式参与项目：

- 🐛 [提交Bug报告](https://github.com/your-org/xss-platform/issues)
- 💡 [提出新功能建议](https://github.com/your-org/xss-platform/discussions)
- 🔧 [提交Pull Request](https://github.com/your-org/xss-platform/pulls)
- 💬 [加入Telegram群组](https://t.me/hackhub7)

---

**感谢所有贡献者！** ❤️
