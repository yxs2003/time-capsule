<?php
/* admin/setup_mailer.php */
session_start();
if(!isset($_SESSION['admin'])) { header("Location: login.php"); exit; }
set_time_limit(300); // 允许运行5分钟

$targetDir = __DIR__ . '/../includes/PHPMailer/';
$zipFile = __DIR__ . '/phpmailer.zip';
$url = 'https://github.com/PHPMailer/PHPMailer/archive/refs/tags/v6.9.1.zip';

function delTree($dir) {
   $files = array_diff(scandir($dir), array('.','..'));
   foreach ($files as $file) {
      (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
   }
   return rmdir($dir);
}

$step = $_GET['step'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mail Setup</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>body{background:#eee; padding:50px; text-align:center;}</style>
</head>
<body>
<div class="pixel-box" style="width:500px; margin:0 auto;">
    <h3>MAILER SETUP</h3>
    <div id="log" style="text-align:left; background:#000; color:#0f0; padding:10px; height:200px; overflow:auto; font-family:monospace; margin-bottom:20px;">
        > Ready to install PHPMailer...<br>
    </div>

    <?php if(!$step): ?>
        <button onclick="startDownload()" class="pixel-btn green">START DOWNLOAD & INSTALL</button>
    <?php endif; ?>
    
    <div id="progress" style="display:none; margin-top:10px;">
        <div style="border:2px solid #000; height:20px; width:100%;"><div id="bar" style="background:var(--green); width:0%; height:100%;"></div></div>
        <p>Downloading... <span id="pct">0%</span></p>
    </div>
</div>

<script>
function log(msg) {
    const box = document.getElementById('log');
    box.innerHTML += "> " + msg + "<br>";
    box.scrollTop = box.scrollHeight;
}

function startDownload() {
    document.querySelector('button').style.display = 'none';
    document.getElementById('progress').style.display = 'block';
    
    // 模拟进度条（因为PHP是同步的，这里做个假动画，实际在后台跑）
    let w = 0;
    const interval = setInterval(() => {
        if(w < 90) w++;
        document.getElementById('bar').style.width = w + '%';
        document.getElementById('pct').innerText = w + '%';
    }, 100);

    fetch('?step=download').then(res => res.json()).then(data => {
        clearInterval(interval);
        document.getElementById('bar').style.width = '100%';
        document.getElementById('pct').innerText = '100%';
        
        if(data.success) {
            log("Download Complete.");
            log("Extracting...");
            log("Done! PHPMailer installed.");
            setTimeout(() => { alert('安装成功！'); window.location.href='index.php?tab=SMTP'; }, 1000);
        } else {
            log("ERROR: " + data.msg);
            alert("Error: " + data.msg);
        }
    }).catch(e => {
        log("Network Error");
    });
}
</script>
</body>
</html>

<?php
if($step == 'download') {
    ob_clean(); // 清除之前的输出
    header('Content-Type: application/json');
    
    try {
        // 1. 下载
        $fp = fopen($zipFile, 'w+');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_FILE, $fp); 
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch); 
        if(curl_errno($ch)){ throw new Exception(curl_error($ch)); }
        curl_close($ch);
        fclose($fp);

        // 2. 解压
        $zip = new ZipArchive;
        if ($zip->open($zipFile) === TRUE) {
            if(is_dir($targetDir)) delTree($targetDir);
            mkdir($targetDir);
            $zip->extractTo($targetDir);
            $zip->close();
            
            // 移动文件 (GitHub下载的里面还有一层文件夹)
            $subDir = glob($targetDir . 'PHPMailer-*', GLOB_ONLYDIR);
            if(isset($subDir[0])) {
                $files = scandir($subDir[0]);
                foreach($files as $file) {
                    if($file != '.' && $file != '..') {
                        rename($subDir[0] . '/' . $file, $targetDir . '/' . $file);
                    }
                }
                rmdir($subDir[0]);
            }
            
            unlink($zipFile);
            echo json_encode(['success'=>true]);
        } else {
            throw new Exception("Unzip failed");
        }
    } catch (Exception $e) {
        echo json_encode(['success'=>false, 'msg'=>$e->getMessage()]);
    }
    exit;
}
?>