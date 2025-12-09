<?php
/* Author: shiguang */
// 安全密钥
$secret_key = "123"; 

header('Content-Type: text/html; charset=utf-8');

if (!isset($_GET['key']) || $_GET['key'] !== $secret_key) {
    die("Access Denied");
}

require_once 'includes/functions.php';

echo "<h2>Cron Job Debugger (Author: shiguang)</h2>";

$current_time = date('Y-m-d H:i:s');
$sql = "SELECT * FROM capsules WHERE email_status = 'pending' AND deliver_at <= ? LIMIT 10";
$stmt = $pdo->prepare($sql);
$stmt->execute([$current_time]);
$capsules = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($capsules) === 0) {
    echo "Nothing to send.";
    exit;
}

$smtp = new SimpleSMTP();

// HTML 邮件模板构建器
function build_email_content($title, $content, $create_date) {
    return '
    <!DOCTYPE html>
    <html>
    <body style="background-color: #eee; padding: 20px; font-family: monospace;">
        <div style="max-width: 600px; margin: 0 auto; background-color: #fffdf0; border: 4px solid #000; padding: 0;">
            <div style="background-color: #ff4d4d; height: 10px; border-bottom: 4px solid #000;"></div>
            <div style="padding: 30px;">
                <h2 style="margin-top: 0; color: #333; border-bottom: 2px dashed #333; padding-bottom: 10px;">'.$title.'</h2>
                
                <p style="font-size: 12px; color: #666; background: #eee; display: inline-block; padding: 5px;">
                    From the past: '.$create_date.'
                </p>
                
                <div style="font-size: 14px; line-height: 1.8; color: #222; margin-top: 20px; white-space: pre-wrap;">
'.$content.'
                </div>
                
                <div style="margin-top: 40px; text-align: right; font-size: 12px; color: #999;">
                    <p>— Sent via Time Capsule —</p>
                </div>
            </div>
            <div style="background-color: #3498db; height: 10px; border-top: 4px solid #000;"></div>
        </div>
    </body>
    </html>
    ';
}

foreach ($capsules as $c) {
    echo "Processing ID {$c['id']}... ";
    try {
        $html_body = build_email_content($c['title'], $c['content'], $c['created_at']);
        
        $res = $smtp->send($c['email'], "Time Capsule: " . $c['title'], $html_body);
        if($res) {
            $pdo->prepare("UPDATE capsules SET email_status = 'sent' WHERE id = ?")->execute([$c['id']]);
            echo "<span style='color:green'>SUCCESS</span><br>";
        }
    } catch (Exception $e) {
        $pdo->prepare("UPDATE capsules SET email_status = 'failed' WHERE id = ?")->execute([$c['id']]);
        echo "<span style='color:red'>ERROR: " . $e->getMessage() . "</span><br>";
    }
}
?>