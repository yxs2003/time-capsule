<?php
/* letter.php - v7.0: Etching Style Stamps, Mobile Flow Fix */
require_once 'includes/functions.php';
$letter_content = get_setting('site_letter_content');
$site_title = get_setting('site_title');

// --- 蚀刻风格高精度印章 (Etching Style Stamps) ---
// 使用排线和断点模拟复古钢笔画质感
$stamps = [
    'compass' => [
        'color' => '#2c3e50', // 深蓝
        'label' => 'GUIDANCE',
        'sub'   => 'FIND YOUR WAY',
        // 指南针：复杂的星芒和刻度
        'icon' => '<circle cx="50" cy="50" r="22" fill="none" stroke="currentColor" stroke-width="0.5"/><path d="M50 15 L58 42 L85 50 L58 58 L50 85 L42 58 L15 50 L42 42 Z" fill="none" stroke="currentColor" stroke-width="1"/><path d="M50 25 L53 47 L75 50 L53 53 L50 75 L47 53 L25 50 L47 47 Z" fill="currentColor" opacity="0.2"/><line x1="50" y1="5" x2="50" y2="10" stroke="currentColor" stroke-width="1"/><line x1="50" y1="90" x2="50" y2="95" stroke="currentColor" stroke-width="1"/><line x1="90" y1="50" x2="95" y2="50" stroke="currentColor" stroke-width="1"/><line x1="5" y1="50" x2="10" y2="50" stroke="currentColor" stroke-width="1"/><circle cx="50" cy="50" r="2" fill="currentColor"/>'
    ],
    'quill' => [
        'color' => '#8e44ad', // 紫色
        'label' => 'WRITTEN',
        'sub'   => 'IN THE STARS',
        // 羽毛笔：细腻的羽毛纹理
        'icon' => '<path d="M30 80 Q 50 80, 70 20 C 80 10, 60 10, 50 30 Q 40 50, 30 80" fill="none" stroke="currentColor" stroke-width="1.2"/><path d="M50 30 Q 60 25, 75 25" stroke="currentColor" stroke-width="0.5"/><path d="M48 35 Q 58 30, 72 32" stroke="currentColor" stroke-width="0.5"/><path d="M45 42 Q 55 38, 68 40" stroke="currentColor" stroke-width="0.5"/><path d="M42 50 Q 52 46, 62 48" stroke="currentColor" stroke-width="0.5"/><path d="M40 58 Q 48 55, 55 56" stroke="currentColor" stroke-width="0.5"/><path d="M30 80 L20 90 M30 80 L40 90" stroke="currentColor" stroke-width="1"/>'
    ],
    'anchor' => [
        'color' => '#2980b9', // 海洋蓝
        'label' => 'ANCHOR',
        'sub'   => 'STAY GROUNDED',
        // 船锚：绳索缠绕细节
        'icon' => '<path d="M50 20 L50 75" stroke="currentColor" stroke-width="2"/><path d="M25 55 Q 25 85, 50 85 Q 75 85, 75 55" fill="none" stroke="currentColor" stroke-width="2"/><line x1="40" y1="30" x2="60" y2="30" stroke="currentColor" stroke-width="2"/><circle cx="50" cy="15" r="4" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M50 75 L45 82 L55 82 Z" fill="currentColor"/><path d="M55 25 Q 70 35, 60 50 Q 50 60, 40 50 Q 30 40, 45 60" fill="none" stroke="currentColor" stroke-width="1" stroke-dasharray="2,2"/>'
    ],
    'saturn' => [
        'color' => '#e67e22', // 橙色
        'label' => 'COSMOS',
        'sub'   => 'SPACE TIME',
        // 土星：环形细节和星球阴影
        'icon' => '<circle cx="50" cy="50" r="18" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M20 65 Q 50 85, 80 35" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M25 68 Q 50 90, 85 32" fill="none" stroke="currentColor" stroke-width="0.5"/><path d="M15 62 Q 45 80, 75 30" fill="none" stroke="currentColor" stroke-width="0.5"/><path d="M38 45 L62 45" stroke="currentColor" stroke-width="0.5" opacity="0.5"/><path d="M40 50 L60 50" stroke="currentColor" stroke-width="0.5" opacity="0.5"/><path d="M42 55 L58 55" stroke="currentColor" stroke-width="0.5" opacity="0.5"/>'
    ],
    'hourglass' => [
        'color' => '#c0392b', // 红色
        'label' => 'MEMORY',
        'sub'   => 'FLOWING TIME',
        // 沙漏：增加流沙线条
        'icon' => '<path d="M35 25 L65 25 L50 50 L65 75 L35 75 L50 50 Z" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M35 25 L65 25 M35 75 L65 75" stroke-width="2"/><line x1="50" y1="50" x2="50" y2="75" stroke="currentColor" stroke-width="0.8" stroke-dasharray="2,1"/><path d="M40 68 L60 68 M42 72 L58 72" stroke="currentColor" stroke-width="0.5"/>'
    ]
];

$stamp_key = array_rand($stamps);
$s = $stamps[$stamp_key];
$rotation = rand(-12, 12);
$date_str = date('Y.m.d'); 
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A Letter - <?= $site_title ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: #2d2d2d;
            /* 细腻的噪点背景 */
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.05'/%3E%3C/svg%3E");
            min-height: 100vh;
            display: flex; flex-direction: column; align-items: center;
        }

        /* --- Air Mail Header --- */
        .air-mail-header {
            width: 100%;
            background: #fff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            margin-bottom: 40px;
            position: relative; z-index: 10;
        }
        .air-mail-stripe {
            height: 10px; width: 100%;
            background: repeating-linear-gradient(
                -45deg,
                #e74c3c, #e74c3c 10px,
                #fff 10px, #fff 20px,
                #3498db 20px, #3498db 30px,
                #fff 30px, #fff 40px
            );
        }
        .header-content {
            padding: 12px 20px;
            text-align: center;
            display: flex; justify-content: space-between; align-items: center;
            max-width: 800px; margin: 0 auto;
        }
        .header-brand { font-size: 16px; font-weight: bold; color: #333; letter-spacing: 2px; }
        .header-tag { font-size: 10px; background: #333; color: #fff; padding: 2px 6px; border-radius: 2px; }

        /* --- Letter Paper --- */
        .letter-container {
            width: 100%; max-width: 680px;
            margin-bottom: 60px; /* 默认底部间距 */
            background-color: #fcfaf2;
            padding: 70px 80px 100px;
            position: relative;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            /* 纯净横线 */
            background-image: linear-gradient(#ddd 1px, transparent 1px);
            background-size: 100% 32px; line-height: 32px;
            border-radius: 2px;
        }
        
        /* 顶部打孔装饰 */
        .letter-container::before {
            content: ''; position: absolute; top: 0; left: 20px; right: 0; height: 40px;
            background-image: radial-gradient(circle, #2d2d2d 5px, transparent 6px);
            background-size: 100% 100%; background-position: -50% -25px; background-repeat: no-repeat;
            opacity: 0.1;
        }

        .letter-title {
            text-align: center; font-size: 28px; color: #333; margin-bottom: 45px;
            border-bottom: 3px solid #333; display: table; margin-left: auto; margin-right: auto;
            background: #fcfaf2; padding: 0 15px; font-weight: bold;
        }
        
        .letter-content {
            font-size: 14px; color: #333; white-space: pre-wrap;
            padding-top: 5px; line-height: 32px;
        }

        /* --- Stamp Box (Absolute within Letter) --- */
        .stamp-box {
            position: absolute; bottom: 50px; right: 50px; width: 160px; height: 160px;
            pointer-events: none; mix-blend-mode: multiply; opacity: 0.95;
            transform: rotate(<?= $rotation ?>deg); z-index: 5;
        }

        /* --- Return Button (Default Desktop: Fixed) --- */
        .return-btn-container {
            position: fixed; bottom: 30px; right: 30px; z-index: 100;
            filter: drop-shadow(4px 4px 0px rgba(0,0,0,0.5)); transition: transform 0.2s;
        }
        .return-btn-container:hover { transform: translateY(-4px); }
        
        .return-stamp {
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            width: 90px; height: 90px; background: #c0392b; color: #fff; text-decoration: none;
            border: 4px solid #fff; outline: 4px dashed #c0392b; outline-offset: -2px;
            box-shadow: inset 0 0 10px rgba(0,0,0,0.2);
        }

        /* --- 移动端适配 (关键修复) --- */
        @media (max-width: 768px) {
            .letter-container { 
                padding: 40px 25px 80px; 
                width: 94%; 
                margin-bottom: 20px; /* 减少信纸本身的底部间距 */
            }
            .stamp-box { width: 120px; height: 120px; bottom: 20px; right: 10px; }
            
            /* 移动端：取消固定定位，改为流式布局 */
            .return-btn-container { 
                position: static; /* 不再悬浮 */
                margin: 0 auto 40px; /* 居中，并在下方留出距离 */
                display: flex; justify-content: center;
                filter: none; /* 移动端去掉浮动阴影以提升性能 */
                transform: none;
            }
            .return-btn-container:hover { transform: none; }
            
            /* 按钮稍微变小适配手机 */
            .return-stamp { width: 80px; height: 80px; box-shadow: 4px 4px 0 rgba(0,0,0,0.3); }
        }
    </style>
</head>
<body>

    <header class="air-mail-header">
        <div class="air-mail-stripe"></div>
        <div class="header-content">
            <div class="header-brand"><?= strtoupper($site_title) ?></div>
            <div class="header-tag">PAR AVION</div>
        </div>
    </header>

    <main class="letter-container">
        <div class="letter-title">Dear You</div>
        <div class="letter-content"><?= $letter_content ?></div>

        <div class="stamp-box">
            <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" style="color: <?= $s['color'] ?>;">
                <circle cx="50" cy="50" r="46" fill="none" stroke="currentColor" stroke-width="1.5" />
                <circle cx="50" cy="50" r="38" fill="none" stroke="currentColor" stroke-width="0.5" stroke-dasharray="3,1" />
                
                <path id="curveTop" d="M 15 50 A 35 35 0 1 1 85 50" fill="none" />
                <text font-size="6" font-weight="bold" fill="currentColor" text-anchor="middle" letter-spacing="1">
                    <textPath xlink:href="#curveTop" startOffset="50%"><?= $s['label'] ?></textPath>
                </text>

                <path id="curveBot" d="M 20 55 A 30 30 0 0 0 80 55" fill="none" />
                 <text font-size="5" font-weight="bold" fill="currentColor" text-anchor="middle" letter-spacing="1">
                    <textPath xlink:href="#curveBot" startOffset="50%"><?= $date_str ?></textPath>
                </text>
                
                <g transform="translate(0, 0)">
                    <?= $s['icon'] ?>
                </g>
            </svg>
        </div>
    </main>

    <div class="return-btn-container">
        <a href="index.php" class="return-stamp">
            <span style="font-size:12px; font-weight:bold; border-bottom:2px solid #fff; padding-bottom:2px; margin-bottom:4px;">RETURN</span>
            <span style="font-size:8px;">TO HOME</span>
        </a>
    </div>

    <footer class="pixel-footer" style="background:transparent; border:none; margin-top:0; padding-top:0; position: relative; z-index: 1; text-shadow: 1px 1px 0 #000;">
        <p><?= html_entity_decode(get_setting('site_footer')) ?></p>
    </footer>
</body>
</html>