# 🤝 贡献指南

感谢您对蓝莲花XSS平台的关注！我们欢迎所有形式的贡献。

---

## 📋 贡献方式

您可以通过以下方式为项目做贡献：

- 🐛 报告Bug
- 💡 提出新功能建议
- 📝 改进文档
- 🔧 提交代码修复
- ✨ 开发新功能
- 🌍 翻译文档
- ⭐ Star项目并分享

---

## 🐛 报告Bug

### 提交前检查

1. 搜索[现有Issues](https://github.com/your-org/xss-platform/issues)，避免重复
2. 使用最新版本测试
3. 准备详细的复现步骤

### Bug报告模板

```markdown
**环境信息**
- 操作系统：Ubuntu 20.04
- Docker版本：20.10.21
- 浏览器：Chrome 119

**问题描述**
简要描述遇到的问题

**复现步骤**
1. 进入xxx页面
2. 点击xxx按钮
3. 观察到xxx错误

**预期行为**
应该显示xxx

**实际行为**
实际显示xxx

**截图**
如有必要，添加截图

**错误日志**
```
粘贴相关日志
```
**补充信息**
其他相关信息
```

---

## 💡 功能建议

### 提交建议

1. 确保功能符合项目定位
2. 详细描述功能场景
3. 说明实现思路（可选）

### 功能建议模板

```markdown
**功能描述**
我希望能够...

**使用场景**
在xxx情况下，这个功能可以帮助...

**实现思路**（可选）
可以通过xxx方式实现...

**其他说明**
...
```

---

## 🔧 代码贡献

### 开发流程

```bash
# 1. Fork项目
点击GitHub页面右上角的Fork按钮

# 2. 克隆仓库
git clone https://github.com/your-username/xss-platform.git
cd xss-platform

# 3. 创建开发分支
git checkout -b feature/your-feature-name

# 4. 进行开发
# 编写代码、测试、提交

# 5. 提交更改
git add .
git commit -m "feat: add your feature"

# 6. 推送到GitHub
git push origin feature/your-feature-name

# 7. 创建Pull Request
在GitHub上创建PR
```

### 代码规范

#### PHP代码

- 遵循[PSR-12](https://www.php-fig.org/psr/psr-12/)编码规范
- 使用4个空格缩进
- 变量命名使用小驼峰：`$userName`
- 函数命名使用小驼峰：`getUserName()`
- 类名使用大驼峰：`UserManager`

示例：
```php
<?php

class UserManager
{
    private $database;
    
    public function getUserById($userId)
    {
        $stmt = $this->database->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
}
```

#### JavaScript代码

- 使用ES6+语法
- 使用2个空格缩进
- 使用const/let，避免var
- 函数命名使用小驼峰
- 常量使用大写下划线

示例：
```javascript
const API_URL = '/api/users';

async function fetchUserData(userId) {
  try {
    const response = await fetch(`${API_URL}/${userId}`);
    const data = await response.json();
    return data;
  } catch (error) {
    console.error('获取用户数据失败:', error);
    throw error;
  }
}
```

#### CSS代码

- 使用2个空格缩进
- 类名使用小写字母和短横线
- 按字母顺序排列属性

示例：
```css
.user-card {
  background-color: #fff;
  border-radius: 8px;
  padding: 16px;
}
```

### 提交信息规范

遵循[Conventional Commits](https://www.conventionalcommits.org/)规范：

```
<type>(<scope>): <subject>

<body>

<footer>
```

**类型（type）：**
- `feat`: 新功能
- `fix`: Bug修复
- `docs`: 文档更新
- `style`: 代码格式调整
- `refactor`: 代码重构
- `perf`: 性能优化
- `test`: 测试相关
- `chore`: 构建/工具变动

**示例：**
```
feat(payload): 添加DOM劫持测试功能

- 实现DOM劫持核心逻辑
- 添加前端测试界面
- 更新文档

Closes #123
```

---

## 🧪 测试要求

### 运行测试

```bash
# 启动测试环境
docker-compose up -d

# 运行测试
docker-compose exec web php tests/run-tests.php
```

### 测试覆盖

- 新功能必须包含测试
- 修复Bug需添加回归测试
- 保持测试覆盖率>80%

---

## 📝 文档贡献

### 文档类型

- README.md - 项目概述
- DEPLOY.md - 部署指南
- CHANGELOG.md - 更新日志
- Wiki - 知识库文档
- API文档 - 接口说明

### 文档规范

- 使用简洁明了的语言
- 提供代码示例
- 添加必要的截图
- 保持格式统一

---

## 🌍 翻译贡献

我们欢迎将文档翻译成其他语言！

### 翻译流程

1. 复制英文文档
2. 创建语言目录（如`docs/ja/`）
3. 翻译内容
4. 提交PR

### 当前支持语言

- 🇨🇳 简体中文（默认）
- 🇺🇸 English
- 🇯🇵 日本語（计划中）
- 🇰🇷 한국어（计划中）

---

## ✅ Pull Request检查清单

提交PR前，请确认：

- [ ] 代码遵循项目规范
- [ ] 通过所有测试
- [ ] 更新相关文档
- [ ] 提交信息规范
- [ ] 没有合并冲突
- [ ] 代码已充分注释
- [ ] 功能已本地测试

---

## 🎖️ 贡献者名单

感谢以下贡献者（按贡献时间排序）：

<!-- 这里会自动生成贡献者列表 -->

---

## 📞 联系我们

- 💬 Telegram: https://t.me/hackhub7
- 📧 Email: 通过Telegram联系
- 🐛 Issues: https://github.com/your-org/xss-platform/issues

---

## 📄 许可证

通过贡献代码，您同意您的贡献将在[MIT License](LICENSE)下授权。

---

**再次感谢您的贡献！** ❤️
