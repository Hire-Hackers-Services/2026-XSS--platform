# Payload用户隔离功能说明

## 功能概述

现在Payload管理已升级为用户隔离模式，每个用户只能看到和管理自己的Payload文件，而不会看到其他用户的Payload。

## 主要改动

### 1. 数据库结构变化
- `payloads`表新增`user_id`字段
- 移除`filename`的UNIQUE约束
- 添加复合索引`(user_id, filename)`
- 添加外键约束关联到`users`表

### 2. API变化
**新增GET方法**：获取当前用户的Payload列表
```
GET /api/payloads.php
返回：{success: true, payloads: [...]}
```

**新增PUT方法**：获取单个Payload详情
```
PUT /api/payloads.php
参数：{id: payloadId}
返回：{success: true, payload: {...}}
```

**修改POST方法**：保存时自动关联当前用户
```
POST /api/payloads.php
参数：filename, content
功能：自动关联user_id，同用户同文件名会更新而非报错
```

**修改DELETE方法**：删除时验证权限
```
DELETE /api/payloads.php
参数：{id: payloadId}
功能：只能删除属于自己的Payload
```

### 3. 前端变化
- 从数据库加载Payload列表（而非扫描文件目录）
- 显示文件大小和更新时间
- 通过ID而非文件名操作Payload

## 数据库升级步骤

### 方式一：全新安装
直接导入`database.sql`，已包含新结构

### 方式二：升级现有数据库
执行迁移脚本：
```bash
mysql -u root -p xss_platform < migrate_payloads.sql
```

**注意**：现有Payload会自动关联到user_id=1（通常是管理员账号）

## 模板库与Payload的区别

### Templates（模板库）
- 存储在`templates`表
- 由**管理员**通过后台导入
- **所有用户**都可以看到和使用
- 用于提供通用的XSS Payload模板
- 用户可以从模板创建自己的Payload

### Payloads（用户Payload）
- 存储在`payloads`表
- 每个**用户私有**
- 只能看到和管理自己的Payload
- 用于个人项目和测试

## 工作流程

1. **用户查看模板库** → templates.php
2. **选择模板并使用** → 跳转到payloads.php并自动加载模板内容
3. **修改并保存** → 保存为用户自己的Payload
4. **管理自己的Payload** → 只能看到自己创建的文件

## 安全特性

✅ 用户隔离：每个用户只能访问自己的Payload
✅ 权限验证：所有API都验证user_id
✅ 数据库约束：外键确保数据一致性
✅ 级联删除：用户删除时自动删除其Payload

## 注意事项

1. 执行迁移脚本前请**备份数据库**
2. 现有Payload会自动归属user_id=1
3. 不同用户可以有同名的Payload文件
4. 删除用户会级联删除其所有Payload
