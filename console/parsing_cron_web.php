<?php

if (($_GET['token'] ?? '') !== 'cron2026') {
    http_response_code(403);
    die('Forbidden');
}
header('Content-Type: text/plain; charset=utf-8');
set_time_limit(0);
ignore_user_abort(true);

require __DIR__ . '/parsing_cron.php';
