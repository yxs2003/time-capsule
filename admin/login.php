<?php
/* admin/login.php - 带退出功能和7天免登录 (修复文字颜色看不清的问题) */
session_start();

// 1. 引入数据库 (必须放在最前，因为自动登录需要查库)
if (file_exists('../includes/db.php')) {
    require_once '../includes/db.php';
} else {
    die("错误：找不到数据库配置文件 includes/db.php");
}

// ==========================================
// A. 处理退出登录 (Logout Logic)
// ==========================================
if (isset($_GET['logout'])) {
    // 1. 清除 Session
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();

    // 2. 清除免登录 Cookie
    setcookie('admin_remember', '', time() - 3600, '/');

    // 3. 刷新页面，去掉 URL 参数，防止刷新时重复触发
    header("Location: login.php");
    exit;
}

// ==========================================
// B. 处理自动登录 (Auto Login via Cookie)
// ==========================================
if (!isset($_SESSION['admin']) && isset($_COOKIE['admin_remember'])) {
    // Cookie 格式: "userID:Signature"
    list($uid, $token) = explode(':', $_COOKIE['admin_remember']);
    
    if ($uid && $token) {
        try {
            // 查出用户信息
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ? LIMIT 1");
            $stmt->execute([$uid]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // 重新计算签名：md5(用户名 + 密码哈希 + 混淆字符串)
                // 这样如果用户改了密码，旧 Cookie 就会自动失效，很安全
                $calc_token = md5($user['username'] . $user['password'] . 'time_capsule_salt');
                
                if ($calc_token === $token) {
                    // 验证通过，自动登录
                    $_SESSION['admin'] = true;
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_name'] = $user['username'];
                }
            }
        } catch (Exception $e) {
            // 忽略错误，让用户重新登录
        }
    }
}

// ==========================================
// C. 检测登录状态
// ==========================================
// 如果已经登录（无论是刚才自动登录的，还是之前登录的），直接进后台
if(isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
    header("Location: index.php");
    exit;
}

$error = '';

// ==========================================
// D. 处理表单提交 (POST Login)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']); // 是否勾选

    if (empty($username) || empty($password)) {
        $error = '请输入用户名和密码';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password, $admin['password'])) {
                // --- 1. 设置 Session ---
                $_SESSION['admin'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['username'];
                
                // --- 2. 设置免登录 Cookie (如果勾选) ---
                if ($remember) {
                    // 生成签名：md5(用户名 + 密码哈希 + 混淆字符串)
                    $token = md5($admin['username'] . $admin['password'] . 'time_capsule_salt');
                    $cookie_val = $admin['id'] . ':' . $token;
                    // 有效期 7 天
                    setcookie('admin_remember', $cookie_val, time() + (7 * 86400), '/', '', false, true);
                }

                header("Location: index.php");
                exit;
            } else {
                $error = '用户名或密码错误';
            }
        } catch (Exception $e) {
            $error = '数据库连接错误';
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
        body {
            display: flex; justify-content: center; align-items: center;
            height: 100vh; background-color: #2d2d2d; margin: 0;
        }
        .login-box {
            width: 320px; padding: 30px;
            background: #fff; 
            border: 4px solid #000;
            box-shadow: 10px 10px 0 #000; 
            text-align: center;
            /* 修复：强制设置文字颜色为深色，覆盖全局的白色设置 */
            color: #333 !important; 
        }
        .error-msg {
            background: #ffe0e0; color: #d32f2f; font-size: 12px;
            padding: 10px; margin-bottom: 15px; border: 2px solid #000; font-weight: bold;
        }
        /* 自定义复选框样式 */
        .checkbox-wrapper {
            display: flex; align-items: center; justify-content: flex-start;
            margin-bottom: 20px; font-size: 12px; cursor: pointer;
            color: #333; /* 确保复选框文字也是深色 */
        }
        .checkbox-wrapper input { margin-right: 8px; cursor: pointer; }
        
        /* 强制输入框样式，防止继承全局的白色文字 */
        .login-box input.pixel-input {
            color: #333 !important;
            background: #fff !important;
            border-color: #000 !important;
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
            
            <div style="margin-bottom:15px; text-align:left;">
                <label style="font-size:10px; font-weight:bold;">PASSWORD</label>
                <input type="password" name="password" class="pixel-input" required>
            </div>

            <label class="checkbox-wrapper">
                <input type="checkbox" name="remember">
                <span>Remember me for 7 days</span>
            </label>

            <button type="submit" class="pixel-btn blue" style="width:100%;">LOGIN</button>
        </form>
        
        <div style="margin-top:20px;">
            <a href="../index.php" style="font-size:10px; color:#666; text-decoration:underline;">← BACK TO HOME</a>
        </div>
    </div>
</body>
</html>
