# 🚀 部署指南

本文档详细说明蓝莲花XSS平台的各种部署方式。

---

## 📋 目录

- [Docker部署（推荐）](#docker部署推荐)
- [Docker Compose部署](#docker-compose部署)
- [传统LAMP/LNMP部署](#传统lamplnmp部署)
- [云平台部署](#云平台部署)
- [生产环境优化](#生产环境优化)

---

## 🐳 Docker部署（推荐）

### 快速开始

```bash
# 1. 克隆项目
git clone https://github.com/your-org/xss-platform.git
cd xss-platform

# 2. 一键部署
bash deploy.sh
```

### 手动部署

```bash
# 1. 创建环境配置
cp .env.example .env

# 2. 修改配置
nano .env  # 修改数据库密码等配置

# 3. 启动容器
docker-compose up -d

# 4. 查看状态
docker-compose ps

# 5. 查看日志
docker-compose logs -f
```

### 访问平台

打开浏览器访问：`http://服务器IP`

默认账号：
- 用户名：admin
- 密码：Admin@123

---

## 📦 Docker Compose部署

### 完整配置示例

```yaml
version: '3.8'

services:
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: your_password
      MYSQL_DATABASE: xss_platform
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - xss_network

  web:
    build: .
    ports:
      - "80:80"
      - "443:443"
    depends_on:
      - mysql
    environment:
      - DB_HOST=mysql
      - DB_NAME=xss_platform
      - DB_USER=root
      - DB_PASS=your_password
    networks:
      - xss_network

volumes:
  mysql_data:

networks:
  xss_network:
```

### 启动服务

```bash
docker-compose up -d
```

### 常用命令

```bash
# 查看运行状态
docker-compose ps

# 查看日志
docker-compose logs -f

# 重启服务
docker-compose restart

# 停止服务
docker-compose stop

# 删除容器（保留数据）
docker-compose down

# 删除容器和数据
docker-compose down -v
```

---

## 🖥️ 传统LAMP/LNMP部署

### 环境要求

- **操作系统**：Ubuntu 20.04 / CentOS 7+
- **PHP**: 7.4+
- **MySQL**: 5.7+ / MariaDB 10.3+
- **Web服务器**: Nginx 1.18+ / Apache 2.4+

### PHP扩展

必需扩展：
- pdo
- pdo_mysql
- mysqli
- mbstring
- json
- session

可选扩展：
- opcache（推荐，提升性能）
- redis（用于Session存储）

### 部署步骤

#### 1. 安装PHP和扩展

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install php7.4-fpm php7.4-mysql php7.4-mbstring php7.4-json php7.4-opcache
```

**CentOS/RHEL:**
```bash
sudo yum install php php-fpm php-mysql php-mbstring php-json php-opcache
```

#### 2. 安装MySQL

**Ubuntu/Debian:**
```bash
sudo apt install mysql-server
sudo mysql_secure_installation
```

**CentOS/RHEL:**
```bash
sudo yum install mysql-server
sudo systemctl start mysqld
sudo mysql_secure_installation
```

#### 3. 创建数据库

```bash
mysql -u root -p

CREATE DATABASE xss_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'xssuser'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON xss_platform.* TO 'xssuser'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### 4. 导入数据库

```bash
mysql -u xssuser -p xss_platform < docker/mysql/init.sql
```

#### 5. 下载项目

```bash
cd /var/www
git clone https://github.com/your-org/xss-platform.git
cd xss-platform
```

#### 6. 配置环境变量

```bash
cp .env.example .env
nano .env  # 修改配置
```

#### 7. 设置权限

```bash
sudo chown -R www-data:www-data /var/www/xss-platform
sudo chmod -R 755 /var/www/xss-platform
sudo chmod -R 777 /var/www/xss-platform/data
sudo chmod -R 777 /var/www/xss-platform/myjs
sudo chmod -R 777 /var/www/xss-platform/jstemplates
```

#### 8. 配置Nginx

创建配置文件：`/etc/nginx/sites-available/xss-platform`

```nginx
server {
    listen 80;
    server_name xss.li www.xss.li;
    root /var/www/xss-platform;
    index index.php index.html;

    # 日志
    access_log /var/log/nginx/xss_access.log;
    error_log /var/log/nginx/xss_error.log;

    # PHP处理
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # 伪静态
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # 静态资源缓存
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # 安全配置
    location ~ /\.env {
        deny all;
    }
    
    location /data/ {
        deny all;
    }
}
```

启用配置：

```bash
sudo ln -s /etc/nginx/sites-available/xss-platform /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

#### 9. 访问安装页面

访问：`http://你的域名/install.php`

---

## ☁️ 云平台部署

### AWS EC2

```bash
# 1. 安装Docker
sudo yum update -y
sudo yum install docker -y
sudo service docker start
sudo usermod -a -G docker ec2-user

# 2. 安装Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/download/v2.20.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# 3. 部署
git clone https://github.com/your-org/xss-platform.git
cd xss-platform
bash deploy.sh
```

### 阿里云ECS

```bash
# 1. 安装Docker
curl -fsSL https://get.docker.com | bash -s docker --mirror Aliyun

# 2. 启动Docker
systemctl start docker
systemctl enable docker

# 3. 安装Docker Compose
curl -L https://get.daocloud.io/docker/compose/releases/download/v2.20.0/docker-compose-`uname -s`-`uname -m` > /usr/local/bin/docker-compose
chmod +x /usr/local/bin/docker-compose

# 4. 部署
git clone https://github.com/your-org/xss-platform.git
cd xss-platform
bash deploy.sh
```

### 腾讯云CVM

同阿里云ECS部署步骤。

---

## 🔧 生产环境优化

### 1. 启用HTTPS

#### 使用Let's Encrypt

```bash
# 安装Certbot
sudo apt install certbot python3-certbot-nginx

# 获取证书
sudo certbot --nginx -d xss.li -d www.xss.li

# 自动续期
sudo crontab -e
# 添加：0 0 1 * * certbot renew --quiet
```

#### Docker环境启用HTTPS

在`docker-compose.yml`中添加：

```yaml
services:
  web:
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./ssl:/etc/nginx/ssl:ro
```

### 2. 数据库优化

编辑`docker-compose.yml`:

```yaml
services:
  mysql:
    command:
      - --max_connections=500
      - --innodb_buffer_pool_size=2G
      - --query_cache_size=128M
      - --slow_query_log=1
      - --slow_query_log_file=/var/log/mysql/slow.log
      - --long_query_time=2
```

### 3. PHP性能优化

编辑`docker/php/php.ini`:

```ini
; OPcache优化
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000

; 资源限制
memory_limit=512M
max_execution_time=300
```

### 4. Nginx优化

编辑`docker/nginx/default.conf`:

```nginx
# Worker进程
worker_processes auto;
worker_connections 4096;

# Gzip压缩
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_comp_level 6;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;

# 缓存设置
proxy_cache_path /var/cache/nginx levels=1:2 keys_zone=my_cache:10m max_size=1g inactive=60m;
```

### 5. 定时备份

创建备份脚本：`/root/backup.sh`

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backup"

# 备份数据库
docker exec xss_mysql mysqldump -u root -p${DB_PASS} xss_platform > ${BACKUP_DIR}/db_${DATE}.sql

# 备份文件
tar -czf ${BACKUP_DIR}/files_${DATE}.tar.gz /var/www/xss-platform/data /var/www/xss-platform/myjs

# 删除7天前的备份
find ${BACKUP_DIR} -name "*.sql" -mtime +7 -delete
find ${BACKUP_DIR} -name "*.tar.gz" -mtime +7 -delete
```

添加定时任务：

```bash
sudo crontab -e
# 每天凌晨2点备份
0 2 * * * /root/backup.sh
```

### 6. 监控告警

#### 安装Prometheus + Grafana

```yaml
# 在docker-compose.yml中添加
  prometheus:
    image: prom/prometheus:latest
    volumes:
      - ./prometheus.yml:/etc/prometheus/prometheus.yml
    ports:
      - "9090:9090"

  grafana:
    image: grafana/grafana:latest
    ports:
      - "3000:3000"
    environment:
      - GF_SECURITY_ADMIN_PASSWORD=admin
```

---

## 🔒 安全加固

### 1. 防火墙配置

```bash
# UFW（Ubuntu）
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# Firewalld（CentOS）
sudo firewall-cmd --permanent --add-service=ssh
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

### 2. Fail2ban配置

```bash
# 安装
sudo apt install fail2ban

# 配置
sudo nano /etc/fail2ban/jail.local
```

```ini
[nginx-http-auth]
enabled = true
port = http,https
logpath = /var/log/nginx/error.log
maxretry = 3
bantime = 3600
```

### 3. 修改默认密码

首次登录后立即修改：
- 管理员密码
- 数据库密码
- .env中的安装密码

---

## ❓ 常见问题

### Q: 如何更新版本？

```bash
# Docker部署
cd xss-platform
git pull
docker-compose down
docker-compose build
docker-compose up -d

# 传统部署
cd /var/www/xss-platform
git pull
sudo systemctl reload php7.4-fpm
sudo systemctl reload nginx
```

### Q: 如何重置管理员密码？

```bash
# 进入MySQL
docker exec -it xss_mysql mysql -u root -p

# 重置密码
USE xss_platform;
UPDATE users SET password='$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username='admin';
```

新密码为：Admin@123

### Q: 如何迁移数据？

```bash
# 1. 备份旧服务器
docker exec xss_mysql mysqldump -u root -p xss_platform > backup.sql
tar -czf data_backup.tar.gz data/ myjs/ jstemplates/

# 2. 传输到新服务器
scp backup.sql user@new-server:/path/
scp data_backup.tar.gz user@new-server:/path/

# 3. 在新服务器恢复
docker exec -i xss_mysql mysql -u root -p xss_platform < backup.sql
tar -xzf data_backup.tar.gz
```

---

## 📞 获取帮助

- 📚 [查看完整文档](README.md)
- 💬 [加入Telegram群组](https://t.me/hackhub7)
- 🐛 [提交Issue](https://github.com/your-org/xss-platform/issues)

---

**祝部署顺利！** 🎉
