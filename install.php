<?php
/*
 * Time Capsule Installer
 * Author: shiguang
 */
if (file_exists('installed.lock')) {
    die("系统已安装。如需重装，请删除 'installed.lock'。<a href='index.php'>返回</a>");
}

$msg = ''; $status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host']; $db_name = $_POST['db_name'];
    $db_user = $_POST['db_user']; $db_pass = $_POST['db_pass'];
    $admin_user = $_POST['admin_user']; $admin_pass = $_POST['admin_pass'];

    try {
        $dsn = "mysql:host=$db_host;charset=utf8mb4";
        $pdo = new PDO($dsn, $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $pdo->exec("USE `$db_name`");

        // 1. 信件表
        $pdo->exec("CREATE TABLE IF NOT EXISTS `capsules` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `email` varchar(255) NOT NULL,
            `title` varchar(255) NOT NULL,
            `content` text NOT NULL,
            `deliver_at` datetime NOT NULL,
            `is_public` tinyint(1) DEFAULT 0,
            `status` enum('pending','approved') DEFAULT 'pending',
            `email_status` enum('pending','sent','failed') DEFAULT 'pending',
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `likes_count` int(11) DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 2. 评论表 (升级：增加 parent_id 用于回复)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `comments` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `capsule_id` int(11) NOT NULL,
            `parent_id` int(11) DEFAULT 0,
            `nickname` varchar(50) NOT NULL,
            `email` varchar(255) NOT NULL,
            `content` varchar(1000) NOT NULL,
            `status` enum('pending','approved') DEFAULT 'pending',
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_capsule` (`capsule_id`),
            KEY `idx_parent` (`parent_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 3. 点赞表
        $pdo->exec("CREATE TABLE IF NOT EXISTS `likes` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `capsule_id` int(11) NOT NULL,
            `ip_address` varchar(45) NOT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_like` (`capsule_id`, `ip_address`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 4. 管理员表
        $pdo->exec("CREATE TABLE IF NOT EXISTS `admins` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL,
            `password` varchar(255) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 5. 设置表
        $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
            `key_name` varchar(50) NOT NULL,
            `value` text,
            PRIMARY KEY (`key_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 6. 默认数据
        $default_success = "<span>> SYSTEM: MESSAGE ENCRYPTED.</span>\n<span>> STATUS: UPLOAD SUCCESS.</span>\n<br>\n<span>\"请忘记这封寄给未来的信件，<br>让我们在未来一起回忆现在这个时刻。\"</span>";
        $default_letter = "亲爱的访客：\n\n很高兴你能来到这里。这是一个关于时间、等待和希望的地方。\n\n我们希望你能在这里种下一颗种子，期待它在未来发芽。\n\n—— shiguang";

        $pdo->exec("INSERT IGNORE INTO `settings` VALUES 
            ('site_title', 'Time Capsule'), 
            ('site_desc', '寄给未来的信'), 
            ('site_keywords', '时间胶囊,未来,信件'),
            ('site_favicon', 'https://cdn-icons-png.flaticon.com/512/3616/3616091.png'),
            ('site_about', '这是一个慢递邮局。\\n时间会证明一切。'),
            ('site_letter_content', " . $pdo->quote($default_letter) . "),
            ('site_footer', '&copy; 2025 Time Capsule. By shiguang.'),
            ('site_timezone', 'Asia/Shanghai'),
            ('site_success_msg', " . $pdo->quote($default_success) . "),
            ('app_version', 'v3.1 Rebirth'),
            ('smtp_host', ''), ('smtp_port', '465'), ('smtp_user', ''), ('smtp_pass', ''), ('smtp_secure', 'ssl')
        ");

        $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
        $stmt->execute([$admin_user, password_hash($admin_pass, PASSWORD_DEFAULT)]);

        $conf = "<?php
\$host='$db_host'; \$db='$db_name'; \$user='$db_user'; \$pass='$db_pass';
try{ \$pdo=new PDO(\"mysql:host=\$host;dbname=\$db;charset=utf8mb4\",\$user,\$pass); \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); }
catch(PDOException \$e){die(\"DB Error\");}
";
        file_put_contents('includes/db.php', $conf);
        file_put_contents('installed.lock', date('Y-m-d'));

        $msg = "安装成功！正在跳转..."; $status = 'success';
        echo "<script>setTimeout(()=>{window.location.href='admin/login.php'},2000)</script>";

    } catch (PDOException $e) {
        $msg = "错误: " . $e->getMessage(); $status = 'error';
    }
}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Setup</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body><div class="center-screen"><div class="install-container"><div class="pixel-box">
    <h1 style="text-align:center;">SETUP WIZARD</h1>
    <?php if($msg) echo "<div class='status-msg $status'>$msg</div>"; ?>
    <form method="POST">
        <label>DB Host</label><input type="text" name="db_host" class="pixel-input" value="127.0.0.1" required>
        <label>DB Name</label><input type="text" name="db_name" class="pixel-input" value="time_capsule" required>
        <label>DB User</label><input type="text" name="db_user" class="pixel-input" required>
        <label>DB Pass</label><input type="password" name="db_pass" class="pixel-input">
        <hr>
        <label>Admin User</label><input type="text" name="admin_user" class="pixel-input" required>
        <label>Admin Pass</label><input type="password" name="admin_pass" class="pixel-input" required>
        <button class="pixel-btn green" style="width:100%">INSTALL</button>
    </form>
</div></div></div></body></html>