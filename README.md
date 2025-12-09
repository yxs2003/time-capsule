-----

# ⏳ Time Capsule (时光胶囊)

> “请忘记这封寄给未来的信件，让我们在未来一起回忆现在这个时刻。”

**Time Capsule** 是一个基于 PHP + MySQL 构建的“慢递邮局”系统。它拥有独特的复古像素（Pixel Art）风格 UI，允许用户写一封寄给未来的信。信件会在指定的时间通过邮件发送给收件人，并支持在“时光广场”匿名公开展示、评论和点赞。

[![网站首页](https://s41.ax1x.com/2025/12/10/pZuwUcF.jpg)](https://imgchr.com/i/pZuwUcF)

## ✨ 功能特性 (Features)

### 🎨 前台功能

  * **像素风视觉体验**：全站采用复古像素风格，集成 Zpix 字体，拥有细腻的 CSS 动画（胶囊悬浮、打开动画、噪点背景）。
  * **寄送胶囊**：用户可设定收信邮箱、发送时间（未来）、信件内容，并选择是否公开。
  * **时光广场**：展示公开的信件，支持 **点赞** 和 **评论** 互动。
  * **精美信纸页**：`letter.php` 包含 SVG 生成的蚀刻风格邮戳（指南针、羽毛笔、土星等），随随机生成，极具仪式感。
  * **响应式设计**：完美适配移动端和桌面端（修复了移动端信纸与按钮的布局）。

#### 发件
  
[![发件](https://s41.ax1x.com/2025/12/10/pZuwN1U.jpg)](https://imgchr.com/i/pZuwN1U)

#### 评论广场

[![评论广场](https://s41.ax1x.com/2025/12/10/pZuwB7R.jpg)](https://imgchr.com/i/pZuwB7R)

#### 评论广场

[![评论广场](https://s41.ax1x.com/2025/12/10/pZuwtpT.jpg)](https://imgchr.com/i/pZuwtpT)

#### 评论广场

![评论广场](https://youke2.picui.cn/s1/2025/12/10/693857e641935.jpg)


### 🛡️ 后台管理 (Admin Console)

  * **仪表盘**：可视化管理信件队列、审核评论、系统设置。
  * **内容审核**：
      * **广场审核**：用户申请公开的信件需管理员批准后才会显示。
      * **评论审核**：防止垃圾评论，支持一键通过或删除。
  * **SMTP 配置**：后台直接配置邮件服务器（Host, Port, User, Pass），无需修改代码。
  * **PHPMailer 一键安装**：内置自动化脚本 (`setup_mailer.php`)，可从后台直接下载并配置 PHPMailer 库。
  * **系统设置**：在线修改网站标题、描述、关于我们、页脚信息等。
  
[![pZuwrA1.png](https://s41.ax1x.com/2025/12/10/pZuwrA1.png)](https://imgchr.com/i/pZuwrA1)

[![pZuwstx.png](https://s41.ax1x.com/2025/12/10/pZuwstx.png)](https://imgchr.com/i/pZuwstx)

[![pZuw0B9.png](https://s41.ax1x.com/2025/12/10/pZuw0B9.png)](https://imgchr.com/i/pZuw0B9)

[![pZuwwnJ.png](https://s41.ax1x.com/2025/12/10/pZuwwnJ.png)](https://imgchr.com/i/pZuwwnJ)

[![pZuwaX4.png](https://s41.ax1x.com/2025/12/10/pZuwaX4.png)](https://imgchr.com/i/pZuwaX4)

### ⚙️ 核心逻辑

  * **自动化投递**：基于 Cron Job 的邮件发送机制，精准投递到达时间的信件。
  * **安装向导**：内置 `install.php`，首次访问即可通过图形化界面完成数据库安装和管理员账号创建。

## 🛠️ 安装指南 (Installation)

### 1\. 环境要求

  * PHP 7.4+ (需开启 PDO\_MYSQL 扩展)
  * MySQL 5.7+
  * Web 服务器 (Apache/Nginx)

### 2\. 部署步骤

1.  将项目文件上传至服务器根目录或子目录。
2.  确保 `includes/` 目录具有写入权限（用于生成 `db.php` 和安装 `PHPMailer`）。
3.  浏览器访问 `http://your-domain.com/install.php`。
4.  根据提示输入数据库信息（Host, DB Name, User, Password）及管理员账号密码。
5.  安装成功后，系统会自动锁定安装程序（生成 `installed.lock`）。

### 3\. 配置邮件发送 (SMTP)

1.  访问后台 `http://your-domain.com/admin/` 并登录。
2.  进入 **SMTP** 选项卡。
3.  如果提示 "Not Installed"，点击下载按钮自动安装 PHPMailer。
4.  填写你的 SMTP 服务商信息（如 Gmail, Outlook, QQ邮箱等）并保存。
5.  使用下方的测试框发送一封测试邮件确保配置正确。

### 4\. 设置定时任务 (Cron Job)

为了让信件能在未来准时发出，你需要在服务器上设置定时任务。

**安全提示**：请打开 `cron.php` 文件，修改第 4 行的密钥：

```php
$secret_key = "YOUR_SECURE_KEY"; // 修改这个值为复杂的字符串
```

**Linux Crontab 设置 (建议每分钟或每5分钟执行一次):**

```bash
# 每5分钟检查一次是否有信件需要投递
*/5 * * * * curl -s "http://your-domain.com/cron.php?key=YOUR_SECURE_KEY" >/dev/null 2>&1
```

## 📂 目录结构 (Directory Structure)

```text
├── admin/               # 后台管理系统
│   ├── index.php        # 后台主控面板
│   ├── login.php        # 管理员登录
│   └── setup_mailer.php # PHPMailer 自动安装脚本
├── assets/              # 静态资源
│   ├── css/style.css    # 核心样式表
│   ├── js/app.js        # 前端交互逻辑
│   └── fonts/           # Zpix 字体文件
├── includes/            # 核心库
│   ├── db.php           # 数据库配置 (安装后生成)
│   └── functions.php    # 通用函数库
├── api/                 # 功能
│   ├── comment.php      # 评论功能
│   ├── get_comments.php # 获取广场评论
│   ├── like.php         # 广场喜欢
│   └── submit.php       # 邮件提交
├── install.php          # 安装向导
├── index.php            # 前台首页 & 广场
├── letter.php           # "A LETTER" 页面 (SVG 邮戳逻辑)
├── cron.php             # 定时任务脚本
├── comment.php          # 评论提交接口
└── README.md
```

## 📝 待办事项 / 开发计划

  - [×] 增加信件的富文本编辑支持
  - [×] 增加更多种类的复古邮票样式
  - [×] 用户个人中心（目前为匿名/邮箱检索模式）

## 📄 License

仅供交流学习，代码部分来自于Gemini，功能与UI为原创。

-----

**Created by shiguang** *Time will prove everything.*
