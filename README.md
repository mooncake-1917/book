# 实用技术知识库（book）

一个基于 **Markdown** 与 **PDF** 的轻量级资料分发网站。登录后可在浏览器中阅读 Markdown 文档、在线预览/下载 PDF、按关键词搜索全文，支持邮箱注册（Resend 验证 + 管理员审核）、站内私信，并可选接入 Redis。

## ✨ 功能特性

- 📄 Markdown 文档渲染（Parsedown，已开启 HTML 转义防 XSS）
- 📕 PDF 在线预览与下载（经登录保护代理流式输出）
- 🔍 全文搜索（Markdown 内容）与文件名搜索（PDF）
- 📤 文件上传（.md / .txt / .pdf，类型与大小校验）
- 📧 邮箱注册：Resend 发送验证邮件，管理员后台审核通过后方可登录
- 💬 站内私信：会话列表、未读提醒、回复，可选邮件通知
- 🧱 Redis 支持：会话存储、登录限流、私信未读缓存（未安装 phpredis 时自动回退）
- 🌙 亮色 / 暗色主题切换
- 📱 响应式布局，适配移动端
- 🔐 登录认证、CSRF 防护、路径穿越防护、会话加固

## 🗂 目录结构

```text
book/
├── nginx.conf.example     # Nginx 配置示例
├── .htaccess              # Apache 配置（使用 Nginx 时可忽略）
├── config.sample.php      # 配置示例（复制为 config.php）
├── database.sql           # 全新安装初始化脚本（v2）
├── database_migration_v2.sql  # v1 -> v2 增量迁移脚本
├── download.php           # 登录保护的 PDF 下载/预览代理
├── index.php              # Markdown 文档阅读入口
├── files.php              # PDF 文件中心
├── login.php              # 登录（用户名或邮箱）
├── register.php           # 邮箱注册
├── verify.php             # 邮箱验证链接处理
├── admin.php              # 管理员审核用户
├── messages.php           # 站内私信
├── logout.php             # 退出登录
├── upload.php             # 文件上传
├── MARKDOWN/              # Markdown 内容目录（按分类建子目录）
├── PDFS/                  # PDF 内容目录（按分类建子目录）
├── RELESEAS/              # 搜索页面
│   ├── search.php
│   └── search-file.php
├── TOOLS/                 # 工具与安全函数
│   ├── security.php
│   ├── redis.php
│   ├── mail.php
│   ├── messaging.php
│   ├── Parsedown.php
│   ├── GET_ITEMS.php
│   ├── GET_MD.php
│   └── GET_SEARCH.php
├── STATIC/                # 静态资源（CSS/JS/字体）
└── PDFObject/             # 前端 PDF 嵌入库
```

## 🧰 环境要求

- PHP 7.4+（推荐 8.x），需 `curl`、`mysqli`；可选 `redis`（phpredis 扩展）
- MySQL 5.7+ / MariaDB 10.3+
- **Nginx 1.18+**（推荐）或 Apache 2.4+
- PHP-FPM（Nginx 环境）
- Resend 账号（https://resend.com）用于注册验证邮件
- Redis（可选，未安装时自动回退到文件会话）

## 🚀 安装部署

1. **获取代码**

   ```bash
   git clone https://github.com/moondish/book.git
   ```

2. **配置**

   ```bash
   cp config.sample.php config.php
   ```

   编辑 `config.php`，填写数据库、`SITE_URL`、Resend API Key、Redis 等信息。

3. **初始化数据库**

   全新安装：

   ```bash
   mysql -u root -p < database.sql
   ```

   已有 v1 数据库：

   ```bash
   mysql -u root -p book < database_migration_v2.sql
   ```

4. **创建管理员账号**

   生成密码哈希：

   ```bash
   php -r "echo password_hash('你的密码', PASSWORD_DEFAULT), PHP_EOL;"
   ```

   执行（替换邮箱和哈希）：

   ```sql
   INSERT INTO users (name, email, password, role, status, email_verified_at)
   VALUES ('admin', 'admin@your-domain.com', '<PASTE_HASH_HERE>', 'admin', 'active', NOW());
   ```

5. **目录权限**

   确保 PHP-FPM 运行用户对 `MARKDOWN/`、`PDFS/` 有读写权限：

   ```bash
   chown -R www-data:www-data MARKDOWN PDFS   # 用户视实际运行用户而定
   chmod -R 755 MARKDOWN PDFS
   ```

6. **配置 Web 服务器**

   **Nginx（推荐）**：参考 `nginx.conf.example`，替换域名、`root` 与 PHP-FPM socket 后，重载 Nginx：

   ```bash
   nginx -t && systemctl reload nginx
   ```

   > 注意：`.htaccess` 是 Apache 配置，在 Nginx 下不生效，所有安全规则与路由都已写在 `nginx.conf.example` 中。

   **Apache**：确保站点允许读取 `.htaccess`（`AllowOverride All`），仓库内已提供对应配置。

7. **访问站点**，未登录会自动跳转到登录页。

> ⚠️ `config.php` 已加入 `.gitignore`，不要提交到版本库。

## 📚 使用说明

### 注册 / 审核流程

1. 用户在注册页填写用户名、邮箱、密码；
2. 系统通过 Resend 发送验证邮件，用户点击链接完成邮箱验证；
3. 管理员在 `admin.php` 看到待审核用户，点击「通过」后用户即可登录。

### 私信

- 登录后点击右下角「私信」进入私信页；
- 可查看会话列表、未读数量，输入对方用户名发送纯文字私信；
- 若配置了 Resend，收件人会收到邮件通知。

### 添加 Markdown / PDF

在 `MARKDOWN/`、`PDFS/` 下按分类创建子目录放入文件；或登录后进入「上传文件」上传。

### 搜索

顶部搜索框：文档页搜索 Markdown 全文，文件中心搜索 PDF 文件名。

## 🧱 Redis 说明

- 安装 phpredis 扩展并配置 `REDIS_HOST` 等项后自动启用；
- 用于：会话存储、登录失败限流（全局计数）、私信未读缓存；
- 未安装或连接失败时自动回退到文件会话 / 数据库查询，不影响站点运行。

## 🔐 安全说明

- 数据库密码存放在 `config.php`（已 gitignore），请勿写回源码；
- 注册邮箱验证令牌一次性使用，密码使用 `password_hash`；
- 上传功能要求登录并使用 CSRF Token；
- 所有文件访问均经过路径穿越校验；
- Markdown 渲染已转义原始 HTML；PDF 经 `download.php` 登录保护后输出；
- Nginx 下 `MARKDOWN/`、`PDFS/`、`TOOLS/`（除 AJAX 接口）与敏感文件均禁止直接访问；
- 建议启用 HTTPS。

## 📄 许可证

本项目使用 GPL-3.0 许可证，详见 [LICENSE](LICENSE)。PDFObject 使用其自身 MIT 许可证（`PDFObject/LICENSE.md`）。
