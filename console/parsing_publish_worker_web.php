<?php
/**
 * HTTP wrapper for the publish-queue worker — lets the admin AJAX kick it off
 * fire-and-forget (ignore_user_abort, no time limit) so the queue starts
 * draining the moment a car is enqueued, without blocking the browser request.
 */

if (($_GET['token'] ?? '') !== 'cron2026') {
    http_response_code(403);
    die('Forbidden');
}
header('Content-Type: text/plain; charset=utf-8');
set_time_limit(0);
ignore_user_abort(true);

require __DIR__ . '/parsing_publish_worker.php';
