# 实用技术知识库（book）

一个基于 **Markdown** 与 **PDF** 的轻量级资料分发网站。登录后可在浏览器中阅读 Markdown 文档、在线预览/下载 PDF、按关键词搜索全文，并支持登录用户上传资料。

## ✨ 功能特性

- 📄 Markdown 文档渲染（Parsedown，已开启 HTML 转义防 XSS）
- 📕 PDF 在线预览与下载（经登录保护代理流式输出）
- 🔍 全文搜索（Markdown 内容）与文件名搜索（PDF）
- 📤 文件上传（.md / .txt / .pdf，类型与大小校验）
- 🌙 亮色 / 暗色主题切换
- 📱 响应式布局，适配移动端
- 🔐 登录认证、CSRF 防护、路径穿越防护、会话加固

## 🗂 目录结构

```text
book/
├── .htaccess              # Apache 路由与安全规则
├── config.sample.php      # 数据库配置示例（复制为 config.php）
├── database.sql           # 数据库初始化脚本
├── download.php           # 登录保护的 PDF 下载/预览代理
├── index.php              # Markdown 文档阅读入口
├── files.php              # PDF 文件中心
├── login.php              # 登录页
├── logout.php             # 退出登录
├── upload.php             # 文件上传
├── MARKDOWN/              # Markdown 内容目录（按分类建子目录）
├── PDFS/                  # PDF 内容目录（按分类建子目录）
├── RELESEAS/              # 搜索页面
│   ├── search.php
│   └── search-file.php
├── TOOLS/                 # 工具与安全函数
│   ├── security.php
│   ├── Parsedown.php
│   ├── GET_ITEMS.php
│   ├── GET_MD.php
│   └── GET_SEARCH.php
├── STATIC/                # 静态资源（CSS/JS/字体）
│   ├── CSS/
│   ├── JS/
│   └── FONTS/
└── PDFObject/             # 前端 PDF 嵌入库
```

## 🧰 环境要求

- PHP 7.4+（推荐 8.x）
- MySQL 5.7+ / MariaDB 10.3+
- Apache 2.4+，启用 `mod_rewrite`、`mod_headers`
- 网站根目录允许读取 `.htaccess`（`AllowOverride All`）

## 🚀 安装部署

1. **获取代码**

   ```bash
   git clone https://github.com/moondish/book.git
   ```

2. **配置数据库**

   ```bash
   cp config.sample.php config.php
   ```

   编辑 `config.php`，填入数据库主机、账号、密码、库名。

3. **初始化数据库**

   ```bash
   mysql -u root -p < database.sql
   ```

4. **创建登录账号**

   用 PHP 生成密码哈希：

   ```bash
   php -r "echo password_hash('你的密码', PASSWORD_DEFAULT), PHP_EOL;"
   ```

   然后执行：

   ```sql
   INSERT INTO users (name, password, role) VALUES ('你的用户名', '上一步生成的哈希', 'admin');
   ```

5. **目录权限**

   确保 Web 服务器对 `MARKDOWN/`、`PDFS/` 有读写权限：

   ```bash
   chmod -R 755 MARKDOWN PDFS
   # 具体属主视运行环境（如 www-data / apache）调整
   ```

6. **访问站点**

   打开首页，未登录会自动跳转到登录页。

> ⚠️ 注意：`config.php` 已加入 `.gitignore`，不要提交到版本库。

## 📚 使用说明

### 添加 Markdown 文档

在 `MARKDOWN/` 下按分类创建子目录，把 `.md` 文件放入即可，左侧目录树会自动读取。

### 添加 PDF

在 `PDFS/` 下按分类创建子目录，放入 `.pdf` 文件；或登录后进入「文件上传」上传。

### 搜索

顶部搜索框：文档页搜索 Markdown 全文，文件中心搜索 PDF 文件名。

## 🔐 安全说明

- 数据库密码必须存放在 `config.php`（已 gitignore），请勿写回源码。
- 上传功能要求登录，并使用 CSRF Token。
- 所有文件访问均经过路径穿越校验（`secure_realpath`）。
- Markdown 渲染已转义原始 HTML；PDF 通过 `download.php` 登录保护后输出。
- 建议启用 HTTPS，并取消 `.htaccess` 中强制 HTTPS 的注释。

## 📄 许可证

本项目使用 GPL-3.0 许可证，详见 [LICENSE](LICENSE)。PDFObject 使用其自身 MIT 许可证（`PDFObject/LICENSE.md`）。
