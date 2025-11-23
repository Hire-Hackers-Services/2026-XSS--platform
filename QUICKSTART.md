# ⚡ 快速开始指南

5分钟快速部署蓝莲花XSS在线平台！

---

## 🎯 三步部署

### 步骤1：准备环境

确保已安装：
- ✅ Docker 20.10+
- ✅ Docker Compose 2.0+
- ✅ Git

检查版本：
```bash
docker --version
docker-compose --version
git --version
```

### 步骤2：下载项目

```bash
git clone https://github.com/your-org/xss-platform.git
cd xss-platform
```

### 步骤3：一键部署

```bash
bash deploy.sh
```

等待3-5分钟，部署完成！

---

## 🌐 访问平台

打开浏览器访问：**http://localhost**

### 默认账号
- **用户名**: `admin`
- **密码**: `Admin@123`

> ⚠️ **安全提示**：首次登录后立即修改密码！

---

## 📱 常用操作

### 查看服务状态
```bash
docker-compose ps
```

### 查看实时日志
```bash
docker-compose logs -f
```

### 重启服务
```bash
docker-compose restart
```

### 停止服务
```bash
docker-compose stop
```

### 删除所有数据
```bash
docker-compose down -v
```

---

## 🎨 功能快速导航

### 1️⃣ Payload测试

访问：http://localhost/payload-test.php

支持7种测试：
- Cookie窃取
- 键盘记录
- 表单劫持
- GPS定位
- 钓鱼页面
- 摄像头拍照
- 浏览器指纹

### 2️⃣ 查看日志

访问：http://localhost/logs.php

查看所有XSS回传数据

### 3️⃣ Payload管理

访问：http://localhost/payloads.php

管理您的XSS代码

### 4️⃣ XSS知识库

访问：http://localhost/wiki.html

学习XSS攻防知识

---

## 🔧 自定义配置

### 修改端口

编辑 `.env` 文件：
```env
WEB_PORT=8080
```

重启服务：
```bash
docker-compose down
docker-compose up -d
```

### 修改数据库密码

编辑 `.env` 文件：
```env
DB_PASS=your_new_password
```

重新部署：
```bash
docker-compose down -v
docker-compose up -d
```

---

## 📦 可选组件

### 启动phpMyAdmin

```bash
docker-compose --profile tools up -d
```

访问：http://localhost:8080

### 启动Redis缓存

```bash
docker-compose --profile full up -d
```

---

## ❓ 遇到问题？

### 端口被占用
```bash
# 查看占用情况
netstat -tulpn | grep :80

# 修改端口（编辑.env）
WEB_PORT=8080
```

### 容器无法启动
```bash
# 查看详细日志
docker-compose logs

# 重新构建
docker-compose build --no-cache
docker-compose up -d
```

### 数据库连接失败
```bash
# 检查MySQL容器
docker ps -a | grep mysql

# 重启MySQL
docker restart xss_mysql

# 查看MySQL日志
docker logs xss_mysql
```

---

## 📚 进阶学习

- 📖 [完整文档](README.md)
- 🚀 [部署指南](DEPLOY.md)
- 📝 [更新日志](CHANGELOG.md)
- 💬 [加入社区](https://t.me/hackhub7)

---

## 🎉 部署成功！

现在您可以：

1. ✅ 登录系统
2. ✅ 生成Payload
3. ✅ 测试XSS漏洞
4. ✅ 查看回传数据
5. ✅ 学习XSS知识

**祝您使用愉快！** 🚀

---

<div align="center">

**有问题？**  
[提交Issue](https://github.com/your-org/xss-platform/issues) | [加入Telegram](https://t.me/hackhub7)

</div>
