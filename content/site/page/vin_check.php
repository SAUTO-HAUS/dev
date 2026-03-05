<?php defined('_DOIT') or die('Restricted access');

// VIN Check unavailable page
$texts = [
    'ro' => [
        'title' => 'VIN-cod indisponibil',
        'message' => 'Din păcate, pentru acest automobil VIN-codul nu este disponibil sau este indicat incorect.',
        'back' => 'Înapoi'
    ],
    'ru' => [
        'title' => 'VIN-код недоступен',
        'message' => 'К сожалению, для этого автомобиля VIN-код недоступен или указан неверно.',
        'back' => 'Назад'
    ],
    'en' => [
        'title' => 'VIN code unavailable',
        'message' => 'Unfortunately, the VIN code for this vehicle is unavailable or invalid.',
        'back' => 'Back'
    ]
];

$lang = $_COOKIE['lang'] ?? 'ro';
$t = $texts[$lang] ?? $texts['ro'];

$rtrn = '
<div style="min-height:60vh;display:flex;align-items:center;justify-content:center;padding:40px 20px;">
    <div style="max-width:500px;text-align:center;background:linear-gradient(135deg,#1a1a2e,#16213e);border-radius:20px;padding:50px 40px;box-shadow:0 10px 40px rgba(0,0,0,0.3);">
        <div style="width:80px;height:80px;margin:0 auto 25px;background:#e2001a;border-radius:50%;display:flex;align-items:center;justify-content:center;">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
        </div>
        <h1 style="font-size:28px;font-weight:700;color:#fff;margin-bottom:15px;">'.$t['title'].'</h1>
        <p style="font-size:16px;color:#aab;line-height:1.6;margin-bottom:30px;">'.$t['message'].'</p>
        <a href="javascript:history.back()" style="display:inline-block;padding:14px 40px;background:#e2001a;color:#fff;font-size:15px;font-weight:600;border-radius:50px;text-decoration:none;transition:all .3s;" onmouseover="this.style.transform=\'translateY(-2px)\'" onmouseout="this.style.transform=\'translateY(0)\'">'.$t['back'].'</a>
    </div>
</div>';
