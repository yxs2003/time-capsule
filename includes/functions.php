<?php
/* includes/functions.php */
require_once __DIR__ . '/db.php';

// 获取配置
function get_setting($key) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE key_name = ?");
    $stmt->execute([$key]);
    return $stmt->fetchColumn() ?: '';
}

// 保存配置
function save_setting($key, $val) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO settings (key_name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
    $stmt->execute([$key, $val, $val]);
}

// 设置时区
$timezone = get_setting('site_timezone');
if ($timezone) {
    date_default_timezone_set($timezone);
}

// 邮件发送类 (智能切换)
class SimpleSMTP {
    public function send($to, $subject, $body) {
        $host = get_setting('smtp_host');
        $user = get_setting('smtp_user');
        $pass = get_setting('smtp_pass');
        $port = get_setting('smtp_port');
        $secure = get_setting('smtp_secure'); // ssl or tls

        // 检查 PHPMailer 是否存在
        $phpmailer_path = __DIR__ . '/PHPMailer/src/PHPMailer.php';
        
        if (file_exists($phpmailer_path)) {
            // --- 使用 PHPMailer ---
            require_once __DIR__ . '/PHPMailer/src/Exception.php';
            require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
            require_once __DIR__ . '/PHPMailer/src/SMTP.php';

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = $host;
                $mail->SMTPAuth   = true;
                $mail->Username   = $user;
                $mail->Password   = $pass;
                $mail->SMTPSecure = $secure; 
                $mail->Port       = $port;
                $mail->CharSet    = 'UTF-8';

                $mail->setFrom($user, get_setting('site_title'));
                $mail->addAddress($to);

                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $body;

                $mail->send();
                return true;
            } catch (Exception $e) {
                throw new Exception($mail->ErrorInfo);
            }
        } else {
            // --- 使用原生 Socket (不推荐，但作为备用) ---
            return $this->nativeSend($to, $subject, $body, $host, $port, $user, $pass, $secure);
        }
    }

    private function nativeSend($to, $subject, $body, $host, $port, $user, $pass, $secure) {
        $transport = ($secure == 'ssl' ? 'ssl://' : '') . $host;
        $socket = fsockopen($transport, $port, $errno, $errstr, 10);
        if (!$socket) throw new Exception("Connect failed: $errstr");

        $this->server($socket, "220");
        fwrite($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n"); $this->server($socket, "250");
        fwrite($socket, "AUTH LOGIN\r\n"); $this->server($socket, "334");
        fwrite($socket, base64_encode($user) . "\r\n"); $this->server($socket, "334");
        fwrite($socket, base64_encode($pass) . "\r\n"); $this->server($socket, "235");

        fwrite($socket, "MAIL FROM: <" . $user . ">\r\n"); $this->server($socket, "250");
        fwrite($socket, "RCPT TO: <$to>\r\n"); $this->server($socket, "250");
        fwrite($socket, "DATA\r\n"); $this->server($socket, "354");

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";
        $headers .= "From: TimeCapsule <" . $user . ">\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: $subject\r\n";

        fwrite($socket, "$headers\r\n$body\r\n.\r\n"); $this->server($socket, "250");
        fwrite($socket, "QUIT\r\n"); fclose($socket);
        return true;
    }

    private function server($socket, $code) {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        if (substr($response, 0, 3) != $code) throw new Exception("SMTP Error: $response");
    }
}
?>