<?php
/* Author: shiguang */
if (!file_exists('installed.lock')) { header("Location: install.php"); exit; }
require_once 'includes/functions.php';

// 获取广场信件
$stmt = $pdo->query("SELECT * FROM capsules WHERE is_public=1 AND status='approved' ORDER BY created_at DESC LIMIT 20");
$letters = $stmt->fetchAll(PDO::FETCH_ASSOC);

function mask($s) { 
    if(empty($s)) return 'Anonymous';
    $p = explode('@', $s); 
    return substr($p[0], 0, 2) . '***@' . ($p[1] ?? 'xx.com'); 
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= get_setting('site_title') ?></title>
    <meta name="description" content="<?= htmlspecialchars(get_setting('site_desc')) ?>">
    <meta name="keywords" content="<?= htmlspecialchars(get_setting('site_keywords')) ?>">
    
    <link rel="icon" href="<?= get_setting('site_favicon') ?>">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="custom-alert-overlay" id="customAlert">
        <div class="custom-alert-box">
            <h3 style="margin-bottom:20px;">SYSTEM MESSAGE</h3>
            <p id="alertMsg" style="margin-bottom:20px; line-height:1.5;"></p>
            <button class="pixel-btn" onclick="closePixelAlert()">CONFIRM</button>
        </div>
    </div>

    <nav class="pixel-nav">
        <div class="nav-brand">
            <a href="index.php"><?= strtoupper(get_setting('site_title')) ?></a>
        </div>
        <div class="hamburger" onclick="toggleMenu()">
            <span></span><span></span><span></span>
        </div>
        <div class="nav-links" id="navLinks">
            <a href="index.php" class="active">HOME</a>
            <a href="letter.php">A LETTER</a>
            <a href="javascript:void(0)" onclick="document.getElementById('sq').scrollIntoView();toggleMenu()">SQUARE</a>
            <a href="javascript:void(0)" onclick="openModal('aboutModal');toggleMenu()">ABOUT</a>
            <?php if(isset($_SESSION['admin'])): ?><a href="admin/index.php" style="color:var(--red)">ADMIN</a><?php endif; ?>
        </div>
    </nav>

<section class="hero">
    <div class="capsule-container">
        <div class="capsule" id="capsuleBtn">
            <div class="cap-top"></div>
            <div class="cap-bottom"></div>
        </div>
        <div class="pixel-bubble" id="capsuleSpeech">Zzz...</div>
    </div>

    <div style="margin-top:60px; font-size:12px; cursor:pointer; color:var(--green);" onclick="triggerCapsule()" class="animate-blink">
        &gt; CLICK TO OPEN &lt;
    </div>
        <div style="position:absolute; bottom:30px; text-align:center; font-size:10px;" class="animate-bounce">
            SCROLL DOWN<br>▼
        </div>
    </section>
    
    <div class="overlay" id="writeOverlay">
        <div class="paper" id="paperForm">
            <div class="close-icon" onclick="closeOverlay()">X</div>
            <form id="capsuleForm">
                <h3 style="text-align:center; border-bottom:2px dashed #000; padding-bottom:15px;">TO: FUTURE</h3>
                <input type="email" name="email" class="pixel-input" placeholder="收件邮箱" required>
                <input type="text" name="title" class="pixel-input" placeholder="信件标题" required>
                <input type="datetime-local" name="deliver_at" class="pixel-input" required>
                <textarea name="content" class="pixel-input" rows="5" placeholder="写下此刻..." required></textarea>
                <label style="font-size:10px;"><input type="checkbox" name="is_public" value="1"> 允许匿名公开到广场</label>
                <button type="submit" class="pixel-btn red" style="width:100%; margin-top:15px;">SEND CAPSULE</button>
            </form>
        </div>
        <div class="success-code" id="successMsg" style="display:none; text-align:center;">
            <?= html_entity_decode(get_setting('site_success_msg')) ?>
            <br><br>
            <button class="pixel-btn" onclick="closeOverlay()">[ 返回 ]</button>
        </div>
    </div>

    <div class="overlay" id="aboutModal">
        <div class="pixel-box" style="width:400px; position:relative;">
            <div class="close-icon" onclick="document.getElementById('aboutModal').style.display='none'">X</div>
            <h3>// ABOUT US</h3>
            <p style="font-size:12px; line-height:1.8;"><?= nl2br(htmlspecialchars(get_setting('site_about'))) ?></p>
            <br><p style="font-size:10px; color:#666;"><?= get_setting('app_version') ?></p>
        </div>
    </div>

    <div class="overlay" id="commentModalOverlay">
        <div class="comment-modal-box">
            <div class="close-icon" onclick="closeCommentModal()">X</div>
            <h3 style="margin-bottom:10px; border-bottom:2px solid #000; padding-bottom:10px;">COMMENTS</h3>
            
            <div id="modal-comment-list" class="comment-scroll-area">
                Loading...
            </div>
            
            <div class="pagination-controls" id="modal-pg" style="display:none;">
                <span class="page-btn" onclick="changeCommentPage(-1)">Prev</span>
                <span id="modal-page-num">1</span>
                <span class="page-btn" onclick="changeCommentPage(1)">Next</span>
            </div>

            <div class="comment-form-area">
                <div id="reply-to-msg" style="font-size:10px; color:var(--blue); margin-bottom:5px; display:none;">
                    Reply to: <span id="reply-to-name" style="font-weight:bold;"></span> 
                    <span style="cursor:pointer; color:red; margin-left:10px;" onclick="cancelReply()">[x] CANCEL</span>
                </div>
                
                <form id="commentForm">
                    <input type="hidden" id="c_parent_id" value="0">
                    <div style="display:flex; gap:10px;">
                        <input type="text" id="c_nick" class="pixel-input" placeholder="昵称 *" style="flex:1;" required>
                        <input type="email" id="c_email" class="pixel-input" placeholder="邮箱 *" style="flex:1;" required>
                    </div>
                    <textarea id="c_content" class="pixel-input" rows="2" placeholder="说点什么..." required></textarea>
                    <button type="submit" class="pixel-btn" style="width:100%;">SEND COMMENT</button>
                </form>
            </div>
        </div>
    </div>

    <section class="square" id="sq">
        <h2 style="text-align:center; color:#fff; text-shadow:4px 4px 0 #000; margin-bottom:40px;">// TIME SQUARE //</h2>
        <div class="grid">
            <?php foreach($letters as $l): ?>
            <?php 
                $cc = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE capsule_id=? AND status='approved'");
                $cc->execute([$l['id']]);
                $cnt = $cc->fetchColumn();
            ?>
            <div class="card" id="card-<?= $l['id'] ?>">
                <div class="card-header">
                    <span><?= mask($l['email']) ?></span>
                    <span><?= date('Y-m-d', strtotime($l['created_at'])) ?></span>
                </div>
                
                <div class="card-content" onclick="this.classList.toggle('expanded')" title="点击展开/收起">
                    <?= nl2br(htmlspecialchars($l['content'])) ?>
                </div>
                
                <div class="card-actions">
                    <div class="action-btn" onclick="likeCapsule(<?= $l['id'] ?>)">
                        <span class="heart-icon" id="heart-<?= $l['id'] ?>">♥</span> 
                        <span id="like-count-<?= $l['id'] ?>"><?= $l['likes_count'] ?></span>
                    </div>
                    <div class="action-btn" onclick="openCommentModal(<?= $l['id'] ?>)">
                        <span>💬</span> <span><?= $cnt ?> 评论</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <footer class="pixel-footer">
        <p><?= html_entity_decode(get_setting('site_footer')) ?></p>
    </footer>

    <script src="assets/js/app.js"></script>
    <script>
        function openModal(id) { document.getElementById(id).style.display = 'flex'; }
        function toggleMenu() { document.getElementById('navLinks').classList.toggle('active'); }
    </script>
</body>
</html>