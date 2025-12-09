<?php
/* admin/login.php - 真正的登录验证页 */
session_start();

// 1. 引入数据库连接 (由 install.php 生成)
// 确保路径正确，admin 目录的上一级是 includes
if (file_exists('../includes/db.php')) {
    require_once '../includes/db.php';
} else {
    die("错误：找不到数据库配置文件 includes/db.php，请先运行 install.php");
}

// 2. 如果已经登录，直接跳进后台，不要在登录页逗留
if(isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
    header("Location: index.php");
    exit;
}

$error = '';

// 3. 处理登录提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = '请输入用户名和密码';
    } else {
        try {
            // 4. 从数据库查找管理员
            // 对应 install.php 创建的 admins 表
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            // 5. 验证密码
            // install.php 使用了 password_hash 加密，所以这里必须用 password_verify
            if ($admin && password_verify($password, $admin['password'])) {
                // --- 登录成功 ---
                $_SESSION['admin'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['username'];
                
                // 跳转到刚才那个功能很全的 index.php
                header("Location: index.php");
                exit;
            } else {
                $error = '用户名或密码错误';
            }
        } catch (Exception $e) {
            $error = '数据库连接错误，请检查配置';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* 简单的居中布局 */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #2d2d2d;
            margin: 0;
        }
        .login-box {
            width: 320px;
            padding: 30px;
            background: #fff;
            border: 4px solid #000;
            box-shadow: 10px 10px 0 #000;
            text-align: center;
        }
        .error-msg {
            background: #ffe0e0;
            color: #d32f2f;
            font-size: 12px;
            padding: 10px;
            margin-bottom: 15px;
            border: 2px solid #000;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h3 style="margin-bottom:20px; border-bottom:2px dashed #000; padding-bottom:10px;">SYSTEM ACCESS</h3>
        
        <?php if($error): ?>
            <div class="error-msg">Error: <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div style="margin-bottom:15px; text-align:left;">
                <label style="font-size:10px; font-weight:bold;">USERNAME</label>
                <input type="text" name="username" class="pixel-input" required autofocus>
            </div>
            
            <div style="margin-bottom:20px; text-align:left;">
                <label style="font-size:10px; font-weight:bold;">PASSWORD</label>
                <input type="password" name="password" class="pixel-input" required>
            </div>

            <button type="submit" class="pixel-btn blue" style="width:100%;">LOGIN</button>
        </form>
        
        <div style="margin-top:20px;">
            <a href="../index.php" style="font-size:10px; color:#666; text-decoration:underline;">← BACK TO HOME</a>
        </div>
    </div>
</body>
</html>