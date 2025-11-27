# 🌸 蓝莲花 XSS 在线平台源码 - 地表最强。

<div align="center">

![Version](https://img.shields.io/badge/version-2.0.8-blue.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![Docker](https://img.shields.io/badge/Docker-Ready-brightgreen.svg)

**专业的XSS漏洞检测与Web安全测试平台**

[🌐 官网](https://xss.li) |  | [Telegram技术合作](https://t.me/HackhubTeam)

<img width="2215" height="1637" alt="xss4" src="https://github.com/user-attachments/assets/b6da1ecd-2882-47ce-99c1-6785b9868561" />
<img width="2311" height="1732" alt="xss3" src="https://github.com/user-attachments/assets/dad0af89-8320-4c15-865a-c01ccbe5813e" />
<img width="2061" height="1731" alt="xss2" src="https://github.com/user-attachments/assets/d80d6b27-cd18-44bd-9b6f-f44cf444d954" />
<img width="2140" height="1806" alt="xss1" src="https://github.com/user-attachments/assets/99e43cee-d103-47c5-93d2-4a055fb74b6d" />

</div>

---

## ✨ 功能特性

技术服务合作联系：Hackhub.org

### 🎯 核心功能

- **7大Payload测试功能**
  - 🍪 Cookie窃取测试
  - ⌨️ 键盘记录测试
  - 📝 表单劫持测试
  - 🌍 GPS定位追踪
  - 🎣 钓鱼页面测试
  - 🖼️ 摄像头远程拍照
  - 🖥️ 浏览器指纹采集

- **实时监控系统**
  - 📊 数据看板统计
  - 📋 详细日志分析
  - 🔍 高级搜索过滤
  - 📥 数据导出功能

- **智能管理**
  - ✏️ Payload代码编辑器
  - 📁 分类标签管理
  - 🔗 一键生成测试链接
  - 📚 丰富的模板库（58+）

- **XSS知识库**
  - 📖 基础教程
  - 🛠️ Payload编写指南
  - 🎯 绕过技巧大全
  - 🛡️ 防御策略

### 🔒 安全特性

- ✅ 政府网站自动过滤
- ✅ IP黑名单机制
- ✅ Payload安全限制
- ✅ 数据加密存储
- ✅ CSRF防护
- ✅ SQL注入防护

---

## 🚀 快速开始

### 方式一：Docker一键部署（推荐）

#### 前置要求
- Docker 20.10+
- Docker Compose 2.0+

#### 部署步骤

```bash
# 1. 克隆项目
git clone https://github.com/your-org/xss-platform.git
cd xss-platform

# 2. 复制环境配置
cp .env.example .env

# 3. 修改配置（可选）
nano .env  # 或使用其他编辑器

# 4. 一键部署
bash deploy.sh
```

等待部署完成后，访问 `http://localhost` 即可使用！

#### 快捷命令

```bash
# 启动服务

docker-compose up -d

# 停止服务
docker-compose stop

# 重启服务
docker-compose restart

# 查看日志
docker-compose logs -f

# 停止并删除容器
docker-compose down

# 启动带phpMyAdmin
docker-compose --profile tools up -d
```

### 方式二：传统部署

#### 环境要求
- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+
- Nginx / Apache
- PHP扩展：PDO, PDO_MySQL, mbstring, json

#### 部署步骤

```bash
# 1. 下载项目
git clone https://github.com/your-org/xss-platform.git
cd xss-platform

# 2. 配置环境变量
cp .env.example .env
# 编辑.env文件，修改数据库配置

# 3. 导入数据库
mysql -u root -p < docker/mysql/init.sql

# 4. 配置Web服务器
# 将nginx.conf或Apache配置复制到相应目录

# 5. 设置权限
chmod -R 755 data myjs jstemplates
chown -R www-data:www-data data myjs jstemplates

# 6. 访问安装页面
# 浏览器打开: http://你的域名/install.php
```

---

## 📖 使用说明

### 默认账号

- **用户名**: admin
- **密码**: Admin@123

> ⚠️ 首次登录后请立即修改密码！

### 基本流程

1. **登录系统**  
   访问平台并使用默认账号登录

2. **生成Payload**  
   进入「Payload测试」页面，选择测试类型，点击生成

3. **执行测试**  
   将生成的Payload代码注入到目标网站

4. **查看结果**  
   进入「数据日志」页面查看回传数据

---

## 🛠️ 配置说明

### 环境变量

主要配置项（.env文件）：

```env
# 数据库配置
DB_HOST=localhost
DB_NAME=xss_platform
DB_USER=root
DB_PASS=your_password

# 应用配置
APP_NAME=蓝莲花XSS在线平台
APP_URL=https://xss.li
SESSION_TIMEOUT=3600

# 安全配置
INSTALL_PASSWORD=xss2024
IP_WHITELIST_ENABLED=false
RATE_LIMIT_ENABLED=true
```

完整配置请参考 `.env.example`

### Docker配置

#### 自定义端口

编辑 `.env` 文件：

```env
WEB_PORT=8080        # Web端口
WEB_SSL_PORT=8443    # HTTPS端口
```

#### 启用Redis

```bash
docker-compose --profile full up -d
```

#### 启用phpMyAdmin

```bash
docker-compose --profile tools up -d
```

访问 `http://localhost:8080`

---

## 📊 系统架构

```
xss-platform/
├── api/                    # API接口
├── data/                   # 数据目录
│   └── backups/           # 备份文件
├── docker/                # Docker配置
│   ├── nginx/            # Nginx配置
│   ├── php/              # PHP配置
│   ├── mysql/            # MySQL初始化
│   └── supervisor/       # Supervisor配置
├── includes/             # 公共组件
├── jstemplates/          # JS模板
├── myjs/                 # 用户上传
├── static/               # 静态资源
├── wiki/                 # 知识库
├── .env.example          # 环境配置模板
├── config.php            # 配置文件
├── docker-compose.yml    # Docker编排
├── Dockerfile           # Docker镜像
└── deploy.sh            # 一键部署脚本
```

---

## 🔧 高级功能

### 数据备份

```bash
# 进入MySQL容器
docker exec -it xss_mysql bash

# 备份数据库
mysqldump -u root -p xss_platform > /var/lib/mysql/backup_$(date +%Y%m%d).sql

# 退出容器
exit

# 复制备份到宿主机
docker cp xss_mysql:/var/lib/mysql/backup_20241123.sql ./
```

### 数据恢复

```bash
# 复制备份到容器
docker cp backup_20241123.sql xss_mysql:/var/lib/mysql/

# 进入容器
docker exec -it xss_mysql bash

# 恢复数据库
mysql -u root -p xss_platform < /var/lib/mysql/backup_20241123.sql
```

### 性能优化

#### PHP-FPM调优

编辑 `docker/php/www.conf`:

```ini
pm.max_children = 50        # 最大子进程数
pm.start_servers = 10       # 启动时进程数
pm.min_spare_servers = 5    # 最小空闲进程
pm.max_spare_servers = 35   # 最大空闲进程
```

#### MySQL调优

编辑 `docker-compose.yml`:

```yaml
command: 
  - --max_connections=500
  - --innodb_buffer_pool_size=1G
  - --query_cache_size=64M
```

---

## 🐛 故障排查

### 常见问题

#### 1. 数据库连接失败

```bash
# 检查MySQL容器状态
docker ps -a | grep xss_mysql

# 查看MySQL日志
docker logs xss_mysql

# 重启MySQL容器
docker restart xss_mysql
```

#### 2. 权限问题

```bash
# 修复文件权限
chmod -R 755 data myjs jstemplates
chown -R www-data:www-data data myjs jstemplates
```

#### 3. 端口被占用

```bash
# 查看端口占用
netstat -tunlp | grep :80

# 修改端口（编辑.env）
WEB_PORT=8080
```

#### 4. 查看详细日志

```bash
# 查看所有服务日志
docker-compose logs -f

# 查看特定服务日志
docker-compose logs -f web
docker-compose logs -f mysql
```

---

## 🤝 贡献指南

欢迎提交Issue和Pull Request！

### 开发环境搭建

```bash
# 克隆项目
git clone https://github.com/your-org/xss-platform.git
cd xss-platform

# 启动开发环境
docker-compose up -d

# 查看日志
docker-compose logs -f
```

### 代码规范

- PHP代码遵循PSR-12规范
- JavaScript使用ES6+语法
- 提交信息遵循[Conventional Commits](https://www.conventionalcommits.org/)

---

## 📄 许可证

本项目采用 [MIT License](LICENSE) 开源协议

---

## ⚠️ 免责声明

**本平台仅供安全研究和授权测试使用**

严禁将本工具用于：
- ❌ 未经授权的渗透测试
- ❌ 攻击政府、教育、医疗等敏感网站
- ❌ 窃取他人隐私数据
- ❌ 任何违反法律法规的行为

使用本平台造成的任何法律后果由使用者自行承担，平台提供方不承担任何责任。

---

## 📞 联系我们

- 🌐 **官方网站**: https://xss.li
- 💬 **Telegram群组**: https://t.me/hackhub7
- 📧 **商务合作**: 通过Telegram联系
- 🔗 **技术服务**: https://hackhub.org/contact-us.html

---

## 🌟 致谢

感谢所有使用和支持蓝莲花XSS平台的安全研究人员！

如果这个项目对您有帮助，请给我们一个⭐Star！

---

<div align="center">

**© 2024 蓝莲花安全团队 | Blue Lotus Security Team**

Made with ❤️ by Security Researchers

</div>



