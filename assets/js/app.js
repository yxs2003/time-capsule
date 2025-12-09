/* app.js - Author: shiguang - v5.0 FULL FEATURES */

let activeCapsuleId = 0;

// --- 1. 自定义 Alert ---
function showPixelAlert(msg) {
    const box = document.getElementById('customAlert');
    if(box) {
        document.getElementById('alertMsg').innerText = msg;
        box.classList.add('show');
    } else {
        alert(msg); // 降级处理
    }
}
function closePixelAlert() {
    const box = document.getElementById('customAlert');
    if(box) box.classList.remove('show');
}
window.alert = showPixelAlert;

// --- 2. 冷却时间逻辑 (Anti-Spam) ---
const COOLDOWN_SEC = 60; // 冷却时间 60秒
let cooldownTimer = null;

function checkCooldown() {
    const lastTime = localStorage.getItem('last_comment_time');
    const btn = document.querySelector('#commentForm button');
    if (!lastTime || !btn) return;

    const now = Math.floor(Date.now() / 1000);
    const diff = now - parseInt(lastTime);

    if (diff < COOLDOWN_SEC) {
        // 还在冷却中
        const remain = COOLDOWN_SEC - diff;
        disableButton(btn, remain);
    } else {
        // 冷却结束
        enableButton(btn);
    }
}

function disableButton(btn, seconds) {
    btn.disabled = true;
    btn.style.opacity = '0.5';
    btn.style.cursor = 'not-allowed';
    btn.innerText = `WAIT ${seconds}s`; // 初始显示
    
    // 清除旧定时器，防止叠加
    if (cooldownTimer) clearInterval(cooldownTimer);

    let remain = seconds;
    cooldownTimer = setInterval(() => {
        remain--;
        if (remain <= 0) {
            enableButton(btn);
        } else {
            btn.innerText = `WAIT ${remain}s`;
        }
    }, 1000);
}

function enableButton(btn) {
    if (cooldownTimer) clearInterval(cooldownTimer);
    btn.disabled = false;
    btn.style.opacity = '1';
    btn.style.cursor = 'pointer';
    btn.innerText = 'SEND COMMENT';
}

// --- DOM 加载完成 ---
document.addEventListener('DOMContentLoaded', () => {
    
    // ===========================
    // PART A: 胶囊投递与动画逻辑
    // ===========================
    const capsuleBtn = document.getElementById('capsuleBtn');
    const bubble = document.getElementById('capsuleSpeech'); // 气泡元素
    const writeOverlay = document.getElementById('writeOverlay');
    const form = document.getElementById('capsuleForm');
    const paper = document.getElementById('paperForm');
    const successMsg = document.getElementById('successMsg');

    // 1. 打开胶囊 (点击触发)
    window.triggerCapsule = function() {
        if(capsuleBtn) {
            // 点击时立即停止所有调皮动画
            capsuleBtn.classList.remove('shaking', 'peeking');
            if(bubble) bubble.style.display = 'none';

            // 播放打开动画
            capsuleBtn.classList.add('open');
            setTimeout(() => { writeOverlay.style.display = 'flex'; }, 800);
        }
    };
    
    // 绑定点击事件
    if(capsuleBtn) capsuleBtn.addEventListener('click', window.triggerCapsule);

    // 2. 关闭遮罩
    window.closeOverlay = function() {
        writeOverlay.style.display = 'none';
        paper.style.display = 'block'; paper.classList.remove('fold');
        successMsg.style.display = 'none';
        
        if(capsuleBtn) {
            capsuleBtn.classList.remove('open');
            // 关闭后，稍等一会再允许它继续调皮，防止穿帮
            setTimeout(() => { capsuleBtn.classList.remove('shaking', 'peeking'); }, 100);
        }

        if(form) {
            form.reset();
            const btn = form.querySelector('button');
            btn.disabled = false; btn.innerText = 'SEND CAPSULE';
        }
    };

    // 3. 提交胶囊表单
    if(form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const btn = form.querySelector('button');
            btn.disabled = true; btn.innerText = 'SENDING...';

            fetch('api/submit.php', { method: 'POST', body: new FormData(form) })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    paper.classList.add('fold');
                    setTimeout(() => { paper.style.display = 'none'; successMsg.style.display = 'block'; }, 1400);
                } else {
                    alert(data.message);
                    btn.disabled = false; btn.innerText = 'SEND CAPSULE';
                }
            })
            .catch(err => { alert("网络错误"); btn.disabled = false; });
        });
    }

    // ===========================
    // PART B: 调皮胶囊 AI (随机动作)
    // ===========================
    const messages = [
        "快点我！", "里面有秘密...", "Zzz...", "好无聊啊", 
        "有人吗？", "Do not touch!", "我动了？", "嘿嘿...",
        "未来的你...", "寄封信吧"
    ];

    function randomAction() {
        // 如果胶囊不存在、正在被打开、或者正在写信，就不动
        if(!capsuleBtn || capsuleBtn.classList.contains('open') || writeOverlay.style.display === 'flex') return;

        const action = Math.random();
        
        if (action < 0.3) {
            // 动作 1: 剧烈抖动
            capsuleBtn.classList.add('shaking');
            setTimeout(() => capsuleBtn.classList.remove('shaking'), 500);
        } else if (action < 0.6) {
            // 动作 2: 偷看 (打开一条缝)
            capsuleBtn.classList.add('peeking');
            setTimeout(() => capsuleBtn.classList.remove('peeking'), 1500);
        } else {
            // 动作 3: 说话
            if(bubble) {
                const text = messages[Math.floor(Math.random() * messages.length)];
                bubble.innerText = text;
                bubble.style.display = 'block';
                setTimeout(() => bubble.style.display = 'none', 2000);
            }
        }
    }

    // 启动定时器：每 5 秒尝试搞怪一次
    if(capsuleBtn) {
        setInterval(randomAction, 5000);
    }

    // ===========================
    // PART C: 评论表单初始化
    // ===========================
    const commentForm = document.getElementById('commentForm');
    if(commentForm) {
        commentForm.addEventListener('submit', (e) => {
            e.preventDefault();
            submitComment();
        });
    }
});

// --- 点赞功能 ---
function likeCapsule(id) {
    const fd = new FormData(); fd.append('id', id);
    fetch('api/like.php', { method:'POST', body:fd }).then(res=>res.json()).then(data=>{
        if(data.success) {
            document.getElementById('like-count-'+id).innerText = data.new_count;
            document.getElementById('heart-'+id).classList.add('liked');
        } else alert(data.message);
    });
}

// --- 评论区功能 (Modal & Tree View) ---

function openCommentModal(id) {
    activeCapsuleId = id;
    document.getElementById('commentModalOverlay').style.display = 'flex';
    document.getElementById('modal-comment-list').innerHTML = '<div style="text-align:center; padding:20px;">Loading comments...</div>';
    
    cancelReply();
    
    // 打开弹窗时立即检查冷却状态
    checkCooldown();
    
    loadComments();
}

function closeCommentModal() {
    document.getElementById('commentModalOverlay').style.display = 'none';
    activeCapsuleId = 0;
}

// 加载评论 (一次性获取，前端组装树)
function loadComments() {
    if(!activeCapsuleId) return;
    
    fetch(`api/get_comments.php?id=${activeCapsuleId}&limit=500`)
    .then(res => res.json())
    .then(data => {
        const list = document.getElementById('modal-comment-list');
        const controls = document.getElementById('modal-pg');
        if(controls) controls.style.display = 'none'; 

        if(!data.comments || data.comments.length === 0) {
            list.innerHTML = '<div style="text-align:center; padding:20px; color:#999;">暂无评论，快来抢沙发！</div>';
        } else {
            // 1. 构建树
            const tree = buildCommentTree(data.comments);
            // 2. 递归渲染
            list.innerHTML = renderCommentTree(tree);
        }
    })
    .catch(err => {
        console.error(err);
        document.getElementById('modal-comment-list').innerHTML = '<div style="text-align:center; padding:20px; color:red;">加载失败，请刷新重试</div>';
    });
}

// 将扁平数组转换为树形结构
function buildCommentTree(comments) {
    const map = {};
    const roots = [];
    
    // 初始化 map
    comments.forEach((c) => {
        map[c.id] = { ...c, children: [] }; 
    });

    // 组装
    comments.forEach(c => {
        if (c.parent_id != 0 && map[c.parent_id]) {
            map[c.parent_id].children.push(map[c.id]);
        } else {
            roots.push(map[c.id]); // 顶层评论
        }
    });

    return roots;
}

// 递归生成 HTML (带颜色和缩进)
function renderCommentTree(nodes, depth = 0) {
    let html = '';
    nodes.forEach(node => {
        // 计算缩进：每级深度缩进 25px，最大缩进 100px
        const indent = Math.min(depth * 25, 100); 
        const borderColors = ['#ccc', '#3498db', '#9b59b6', '#e67e22', '#2ecc71'];
        const borderColor = borderColors[depth % borderColors.length];

        html += `
            <div class="comment-item" style="margin-left: ${indent}px; border-left: 4px solid ${borderColor};">
                <div style="color:${borderColor}; margin-bottom:5px; display:flex; justify-content:space-between; align-items:center;">
                    <span><b>${node.nickname}</b></span>
                    <span class="reply-btn" onclick="replyTo(${node.id}, '${node.nickname}')">REPLY</span>
                </div>
                <div style="margin-bottom:8px; font-size:10px; color:#999;">${node.created_at}</div>
                <div style="word-break:break-all; line-height:1.5;">
                    ${node.content}
                </div>
            </div>
        `;

        if (node.children.length > 0) {
            html += renderCommentTree(node.children, depth + 1);
        }
    });
    return html;
}

// 回复某人
function replyTo(commentId, nickname) {
    document.getElementById('c_parent_id').value = commentId;
    document.getElementById('reply-to-name').innerText = nickname;
    document.getElementById('reply-to-msg').style.display = 'block';
    
    const input = document.getElementById('c_content');
    input.focus();
    input.placeholder = `Reply to ${nickname}...`;
}

function cancelReply() {
    document.getElementById('c_parent_id').value = 0;
    document.getElementById('reply-to-msg').style.display = 'none';
    document.getElementById('c_content').placeholder = "说点什么...";
}

// 提交评论
function submitComment() {
    if(!activeCapsuleId) return;

    // 二次检查时间 (JS 校验)
    const lastTime = localStorage.getItem('last_comment_time');
    if (lastTime) {
        const diff = Math.floor(Date.now() / 1000) - parseInt(lastTime);
        if (diff < COOLDOWN_SEC) {
            alert(`请再等待 ${COOLDOWN_SEC - diff} 秒`);
            return;
        }
    }

    const nick = document.getElementById('c_nick').value;
    const email = document.getElementById('c_email').value;
    const content = document.getElementById('c_content').value;
    const parentId = document.getElementById('c_parent_id').value;
    
    if(!nick || !email || !content) { alert("请填写完整信息"); return; }
    
    const fd = new FormData();
    fd.append('capsule_id', activeCapsuleId);
    fd.append('nickname', nick);
    fd.append('email', email);
    fd.append('content', content);
    fd.append('parent_id', parentId);
    
    const btn = document.querySelector('#commentForm button');
    btn.disabled = true; 
    btn.innerText = 'SENDING...';

    fetch('api/comment.php', { method:'POST', body:fd })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            // --- 记录发送时间 ---
            localStorage.setItem('last_comment_time', Math.floor(Date.now() / 1000));
            
            alert("评论提交成功，等待审核。");
            document.getElementById('c_content').value = '';
            cancelReply();
            
            // 重新开始冷却计时
            checkCooldown();
        } else {
            alert(data.message);
            btn.disabled = false;
            btn.innerText = 'SEND COMMENT';
        }
    })
    .catch(() => {
        alert("提交失败，请检查网络");
        btn.disabled = false;
        btn.innerText = 'SEND COMMENT';
    });
}