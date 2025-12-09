<?php
/* Author: shiguang */
session_start();
if(!isset($_SESSION['admin'])) { header("Location: login.php"); exit; }
require_once '../includes/functions.php';

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'Reviews';
$msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';

// --- POST 处理 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 网站设置
    if (isset($_POST['save_site'])) {
        save_setting('site_title', $_POST['site_title']);
        save_setting('site_desc', $_POST['site_desc']);
        save_setting('site_keywords', $_POST['site_keywords']);
        save_setting('site_favicon', $_POST['site_favicon']);
        save_setting('site_timezone', $_POST['site_timezone']);
        header("Location: index.php?tab=Site&msg=系统设置已保存"); exit;
    }
    // 内容管理
    if (isset($_POST['save_content'])) {
        save_setting('site_about', $_POST['site_about']);
        save_setting('site_footer', $_POST['site_footer']);
        save_setting('site_success_msg', $_POST['site_success_msg']);
        header("Location: index.php?tab=Content&msg=内容已更新"); exit;
    }
    // 信件页设置
    if (isset($_POST['save_letter'])) {
        save_setting('site_letter_content', $_POST['site_letter_content']);
        header("Location: index.php?tab=Letter&msg=信件内容已更新"); exit;
    }
    // SMTP
    if (isset($_POST['save_smtp'])) {
        save_setting('smtp_host', $_POST['smtp_host']);
        save_setting('smtp_port', $_POST['smtp_port']);
        save_setting('smtp_user', $_POST['smtp_user']);
        save_setting('smtp_pass', $_POST['smtp_pass']);
        save_setting('smtp_secure', $_POST['smtp_secure']);
        header("Location: index.php?tab=SMTP&msg=SMTP配置已保存"); exit;
    }
    // Test SMTP
    if (isset($_POST['test_smtp'])) {
        try {
            $smtp = new SimpleSMTP();
            $smtp->send($_POST['test_email'], 'SMTP Test', 'Connection Successful!');
            header("Location: index.php?tab=SMTP&msg=测试成功"); exit;
        } catch (Exception $e) {
            $err = urlencode("失败: " . $e->getMessage());
            header("Location: index.php?tab=SMTP&msg=$err"); exit;
        }
    }
}

// --- GET 动作 ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // 广场审核
    if ($_GET['action'] == 'approve_capsule') {
        $pdo->prepare("UPDATE capsules SET status = 'approved' WHERE id = ?")->execute([$id]);
        header("Location: index.php?tab=Reviews&msg=信件已公开"); exit;
    }
    if ($_GET['action'] == 'delete_capsule') {
        $pdo->prepare("DELETE FROM capsules WHERE id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM comments WHERE capsule_id = ?")->execute([$id]);
        header("Location: index.php?tab=Reviews&msg=信件已删除"); exit;
    }
    // 评论审核
    if ($_GET['action'] == 'approve_comment') {
        $pdo->prepare("UPDATE comments SET status = 'approved' WHERE id = ?")->execute([$id]);
        header("Location: index.php?tab=Comments&msg=评论已通过"); exit;
    }
    if ($_GET['action'] == 'delete_comment') {
        $pdo->prepare("DELETE FROM comments WHERE id = ?")->execute([$id]);
        header("Location: index.php?tab=Comments&msg=评论已删除"); exit;
    }
    // 重发邮件
    if ($_GET['action'] == 'resend') {
        $stmt = $pdo->prepare("SELECT * FROM capsules WHERE id = ?");
        $stmt->execute([$id]); $letter = $stmt->fetch();
        if($letter) {
            try {
                $smtp = new SimpleSMTP();
                $smtp->send($letter['email'], $letter['title'], $letter['content']);
                $pdo->prepare("UPDATE capsules SET email_status = 'sent' WHERE id = ?")->execute([$id]);
                header("Location: index.php?tab=Queue&msg=Sent"); exit;
            } catch (Exception $e) {
                $pdo->prepare("UPDATE capsules SET email_status = 'failed' WHERE id = ?")->execute([$id]);
                header("Location: index.php?tab=Queue&msg=Fail"); exit;
            }
        }
    }
}

// 数据查询
$reviews = $pdo->query("SELECT * FROM capsules WHERE is_public=1 ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$comments_list = $pdo->query("SELECT c.*, l.id as letter_id FROM comments c LEFT JOIN capsules l ON c.capsule_id = l.id ORDER BY c.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$queue_stmt = $pdo->query("SELECT * FROM capsules ORDER BY deliver_at ASC LIMIT 50");
$queue = $queue_stmt->fetchAll(PDO::FETCH_ASSOC);

// 检测 PHPMailer
$has_phpmailer = file_exists(__DIR__ . '/../includes/PHPMailer/src/PHPMailer.php');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>
        function openTab(evt, tabName) {
            var i, x, tablinks;
            x = document.getElementsByClassName("tab-content");
            for (i = 0; i < x.length; i++) x[i].style.display = "none";
            tablinks = document.getElementsByClassName("tab-btn");
            for (i = 0; i < tablinks.length; i++) tablinks[i].className = tablinks[i].className.replace(" active", "");
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";
        }
        function showFullText(content) {
            document.getElementById('fullTextContent').innerText = content;
            document.getElementById('fullTextModal').style.display = 'flex';
        }
        window.onload = function() {
            if (window.history.replaceState) {
                var url = new URL(window.location.href);
                if(url.searchParams.get("msg")) {
                    url.searchParams.delete("msg");
                    window.history.replaceState(null, null, url);
                }
            }
        }
    </script>
</head>
<body class="admin-mode">
    <div class="admin-header">
        <span>ADMIN CONSOLE</span>
        <span style="font-size:10px; color:#666;">Server: <?= date('Y-m-d H:i') ?></span>
        <div><a href="../index.php" target="_blank">[ 预览前台 ]</a> <a href="login.php?logout=1" style="color:var(--red); font-weight:bold; margin-left:10px;">[ 退出 ]</a></div>
    </div>

    <div class="main-container">
        <?php if($msg): ?><div class="pixel-box" style="margin-bottom:20px; color:blue; text-align:center; border:2px solid blue; background:#e0e0ff;"><?= urldecode($msg) ?></div><?php endif; ?>

        <div class="tabs">
            <button class="tab-btn <?= $active_tab=='Reviews'?'active':'' ?>" onclick="openTab(event, 'Reviews')">广场管理</button>
            <button class="tab-btn <?= $active_tab=='Comments'?'active':'' ?>" onclick="openTab(event, 'Comments')">评论审核</button>
            <button class="tab-btn <?= $active_tab=='Queue'?'active':'' ?>" onclick="openTab(event, 'Queue')">信件队列</button>
            <button class="tab-btn <?= $active_tab=='Site'?'active':'' ?>" onclick="openTab(event, 'Site')">系统设置</button>
            <button class="tab-btn <?= $active_tab=='SMTP'?'active':'' ?>" onclick="openTab(event, 'SMTP')">SMTP</button>
            <button class="tab-btn <?= $active_tab=='Content'?'active':'' ?>" onclick="openTab(event, 'Content')">内容管理</button>
            <button class="tab-btn <?= $active_tab=='Letter'?'active':'' ?>" onclick="openTab(event, 'Letter')">信件页设置</button>
        </div>

        <div id="Reviews" class="tab-content pixel-box" style="display:<?= $active_tab=='Reviews'?'block':'none' ?>;">
            <h3>公开信件列表</h3>
            <table class="admin-table">
                <tr><th>ID</th><th>状态</th><th>邮箱</th><th>预览</th><th>时间</th><th>操作</th></tr>
                <?php foreach($reviews as $row): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><span class="badge <?= $row['status'] ?>"><?= strtoupper($row['status']) ?></span></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td onclick="showFullText('<?= htmlspecialchars(addslashes($row['content'])) ?>')" style="cursor:pointer; color:blue; text-decoration:underline;"><?= htmlspecialchars(mb_substr($row['content'],0,10)) ?>...</td>
                    <td><?= $row['created_at'] ?></td>
                    <td>
                        <?php if($row['status'] == 'pending'): ?>
                        <a href="?action=approve_capsule&id=<?= $row['id'] ?>" class="pixel-btn green sm">通过</a>
                        <?php endif; ?>
                        <a href="?action=delete_capsule&id=<?= $row['id'] ?>" class="pixel-btn red sm" onclick="return confirm('确定删除？')">删</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div id="Comments" class="tab-content pixel-box" style="display:<?= $active_tab=='Comments'?'block':'none' ?>;">
            <h3>评论管理</h3>
            <table class="admin-table">
                <tr><th>ID</th><th>信件ID</th><th>发布人</th><th>内容</th><th>状态</th><th>操作</th></tr>
                <?php foreach($comments_list as $c): ?>
                <tr>
                    <td><?= $c['id'] ?></td>
                    <td>#<?= $c['letter_id'] ?></td>
                    <td>
                        <div style="font-weight:bold;"><?= htmlspecialchars($c['nickname']) ?></div>
                        <div style="font-size:8px; color:#666;"><?= htmlspecialchars($c['email']) ?></div>
                    </td>
                    <td onclick="showFullText('<?= htmlspecialchars(addslashes($c['content'])) ?>')" style="cursor:pointer; color:blue;"><?= htmlspecialchars(mb_substr($c['content'],0,20)) ?>...</td>
                    <td><span class="badge <?= $c['status'] ?>"><?= $c['status'] ?></span></td>
                    <td>
                        <?php if($c['status'] == 'pending'): ?>
                            <a href="?action=approve_comment&id=<?= $c['id'] ?>&tab=Comments" class="pixel-btn green sm">√</a>
                        <?php endif; ?>
                        <a href="?action=delete_comment&id=<?= $c['id'] ?>&tab=Comments" class="pixel-btn red sm">X</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div id="Queue" class="tab-content pixel-box" style="display:<?= $active_tab=='Queue'?'block':'none' ?>;">
            <h3>信件队列</h3>
            <table class="admin-table">
                <tr><th>ID</th><th>邮箱</th><th>送达时间</th><th>状态</th><th>操作</th></tr>
                <?php foreach($queue as $row): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td title="<?= $row['email'] ?>"><?= $row['email'] ?></td>
                    <td style="color:blue; font-weight:bold;"><?= $row['deliver_at'] ?></td>
                    <td><span class="badge <?= $row['email_status'] ?>"><?= strtoupper($row['email_status']) ?></span></td>
                    <td><?php if($row['email_status'] != 'sent'): ?><a href="?action=resend&id=<?= $row['id'] ?>" class="pixel-btn yellow sm">重发</a><?php endif; ?> <a href="?action=delete_capsule&id=<?= $row['id'] ?>&tab=Queue" class="pixel-btn red sm">删</a></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div id="Site" class="tab-content pixel-box" style="display:<?= $active_tab=='Site'?'block':'none' ?>;">
            <form method="POST">
                <label>网站标题</label><input type="text" name="site_title" class="pixel-input" value="<?= get_setting('site_title') ?>">
                <label>网站描述</label><input type="text" name="site_desc" class="pixel-input" value="<?= get_setting('site_desc') ?>">
                <label>关键词</label><input type="text" name="site_keywords" class="pixel-input" value="<?= get_setting('site_keywords') ?>">
                <label>Favicon URL</label><input type="text" name="site_favicon" class="pixel-input" value="<?= get_setting('site_favicon') ?>">
                <label>系统时区</label><input type="text" name="site_timezone" class="pixel-input" value="<?= get_setting('site_timezone') ?>">
                <button type="submit" name="save_site" class="pixel-btn blue">保存系统设置</button>
            </form>
        </div>

        <div id="SMTP" class="tab-content pixel-box" style="display:<?= $active_tab=='SMTP'?'block':'none' ?>;">
            <div style="margin-bottom:20px; padding:10px; border:2px dashed #000;">
                <strong>Core Status: </strong>
                <?php if($has_phpmailer): ?>
                    <span style="color:green; font-weight:bold;">PHPMailer Installed (v6.9)</span>
                <?php else: ?>
                    <span style="color:red; font-weight:bold;">Not Installed</span>
                    <p style="font-size:10px; color:#666;">Use the automated script to download files from GitHub.</p>
                    <a href="setup_mailer.php" class="pixel-btn yellow sm" target="_blank">>> Download & Install PHPMailer <<</a>
                <?php endif; ?>
            </div>

            <form method="POST">
                <label>Host</label><input type="text" name="smtp_host" class="pixel-input" value="<?= get_setting('smtp_host') ?>">
                <label>Port</label><input type="text" name="smtp_port" class="pixel-input" value="<?= get_setting('smtp_port') ?>">
                <label>User</label><input type="text" name="smtp_user" class="pixel-input" value="<?= get_setting('smtp_user') ?>">
                <label>Pass</label><input type="password" name="smtp_pass" class="pixel-input" value="<?= get_setting('smtp_pass') ?>">
                <label>Secure</label><input type="text" name="smtp_secure" class="pixel-input" value="<?= get_setting('smtp_secure') ?>">
                <button type="submit" name="save_smtp" class="pixel-btn blue">保存配置</button>
            </form>
            <hr><form method="POST"><input type="email" name="test_email" class="pixel-input" placeholder="Test Email" required><button type="submit" name="test_smtp" class="pixel-btn green">测试连接</button></form>
        </div>

        <div id="Content" class="tab-content pixel-box" style="display:<?= $active_tab=='Content'?'block':'none' ?>;">
            <form method="POST">
                <label>关于我们</label><textarea name="site_about" class="pixel-input" rows="5"><?= get_setting('site_about') ?></textarea>
                <label>页脚信息</label><input type="text" name="site_footer" class="pixel-input" value="<?= htmlspecialchars(get_setting('site_footer')) ?>">
                <label>成功提示语</label><textarea name="site_success_msg" class="pixel-input" rows="5" style="font-family:monospace;"><?= htmlspecialchars(get_setting('site_success_msg')) ?></textarea>
                <button type="submit" name="save_content" class="pixel-btn blue">更新内容</button>
            </form>
        </div>

        <div id="Letter" class="tab-content pixel-box" style="display:<?= $active_tab=='Letter'?'block':'none' ?>;">
            <h3>给用户的信</h3>
            <p style="font-size:10px; color:#666; margin-bottom:10px;">在这里编辑 "A LETTER" 页面显示的内容。</p>
            <form method="POST">
                <textarea name="site_letter_content" class="pixel-input" rows="15" style="font-family: 'Zpix', monospace; line-height: 1.5;"><?= htmlspecialchars(get_setting('site_letter_content')) ?></textarea>
                <button type="submit" name="save_letter" class="pixel-btn blue">保存内容</button>
            </form>
        </div>

    </div>

    <div id="fullTextModal" class="full-text-modal" onclick="this.style.display='none'"><div class="full-text-box" onclick="event.stopPropagation()"><div class="close-icon" onclick="document.getElementById('fullTextModal').style.display='none'">X</div><h3 style="border-bottom:2px dashed #000;">Full Content</h3><div id="fullTextContent" style="font-size:12px; line-height:1.6; white-space:pre-wrap;"></div></div></div>
</body>
</html>