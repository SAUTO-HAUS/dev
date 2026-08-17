<?php

/**
 * Email alerts for B2B saved searches ("Filtrele mele").
 *
 * For every active partner it counts the cars published since the last alert
 * (gh3sp_b2b_saved_filters.notified_at) that match their saved filters, and
 * sends ONE email listing those filters, with a button to /{lang}/b2b/filters.
 * The watermark only moves once the message is actually out, so a mail failure
 * is retried on the next run instead of being lost.
 *
 * Hourly is a good cadence — it only writes when there is something new:
 *   0 * * * * cd /home/sautom/public_html && /usr/local/bin/php console/b2b_filter_notify.php >> logs/b2b_filter_notify.log 2>&1
 *
 * Browser (no shell on the server):
 *   https://www.sauto.md/console/b2b_filter_notify.php?token=cron2026[&dry=1]
 *
 * dry=1 reports what WOULD be sent without sending or moving the watermark.
 */

date_default_timezone_set('Europe/Chisinau');

define('_DOIT', 1);
define('_DEFAULT', 'content/default');

$IS_CLI = (php_sapi_name() === 'cli');
if (!$IS_CLI && (($_GET['token'] ?? '') !== 'cron2026')) {
    http_response_code(403);
    die('Forbidden');
}
if (!$IS_CLI) {
    header('Content-Type: text/plain; charset=utf-8');
}
set_time_limit(0);

// The docroot must be a real path before defines.php reads it (_ROOT), and the
// email links are built from the host — neither exists under CLI.
$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/..');
if (empty($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'www.sauto.md';
    $_SERVER['HTTPS']     = 'on';
}
chdir($_SERVER['DOCUMENT_ROOT']);

spl_autoload_register(function ($class) {
    $path = __DIR__ . '/../' . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (file_exists($path)) require_once $path;
});

require_once __DIR__ . '/../environment.php';
require_once __DIR__ . '/../' . _DEFAULT . '/defines.php';
require __DIR__ . '/../' . _DEFAULT . '/dbi.php';
require_once __DIR__ . '/../' . _DEFAULT . '/functions.php';

use App\Core\Container;
use App\Services\B2b\B2bFilterMailer;
use App\Services\B2b\B2bSavedFilter;

$prefx = 'gh3sp';
Container::set('db', $db);
Container::set('prefix', $prefx);

$dry = ($_GET['dry'] ?? '') === '1' || in_array('--dry', $argv ?? [], true);
$say = function (string $line) { echo date('Y-m-d H:i:s') . ' ' . $line . "\n"; };

if (!B2bFilterMailer::enabled()) {
    $say('disabled (settings b2b_filter_mail=0) — nothing to do');
    return;
}

B2bSavedFilter::ensureSchema();

/**
 * describe() labels, taken from the site's own catalog translations so the
 * email words a filter exactly as the cabinet does.
 *
 * language.php branches on the language cookie and expects the router's $t_mp,
 * so both are faked here; with the cookie already set to the wanted language it
 * takes no setcookie() path. A failure only costs the fuel/gearbox/region words
 * — describe() skips whatever label it is not given.
 */
$labelsFor = function (string $lang) {
    $out = [];

    try {
        include __DIR__ . '/../content/site/page/b2b/_lang.php';   // b2b_lang()
        $t = function_exists('b2b_lang') ? b2b_lang($lang) : [];

        $lang_arr     = ['ro', 'ru', 'en'];
        $default_lang = 'ro';
        $domain_name  = 'sauto.md';
        $time         = time() + 31536000;
        $t_mp         = ['', $lang];
        $n_row        = '&#013;';   // config.php normally supplies it
        $_COOKIE['lang'] = $lang;

        $lng = [];
        require __DIR__ . '/../' . _DEFAULT . '/language.php';     // rebuilds $lng

        $out['fl']  = $lng['l']['car']['fl']  ?? [];
        $out['tra'] = $lng['l']['car']['tra'] ?? [];
        $out['cm3'] = $lng['l']['unit']['cm3'] ?? 'cm3';
        $out['km']  = $lng['l']['unit']['km']  ?? 'km';

        foreach (['korea', 'europe', 'usa', 'china'] as $rg) {
            $out['rg_' . $rg] = $t['rg_' . $rg] ?? $rg;
        }
        $out['from'] = $t['flt_from'] ?? 'de la';
        $out['to']   = $t['flt_to']   ?? 'până la';
        $out['any']  = $t['flt_any']  ?? '—';
    } catch (Throwable $e) {
        // Labels are a nicety, the alert is not: carry on with what we have.
    }

    return $out;
};

$labels = ['ro' => $labelsFor('ro'), 'ru' => $labelsFor('ru')];

$stat = B2bFilterMailer::run($labels, $dry);

$say(($dry ? '[dry] ' : '')
    . 'partners=' . $stat['users']
    . ' filters=' . $stat['filters']
    . ' cars=' . $stat['cars']
    . ' sent=' . $stat['sent']
    . ' failed=' . $stat['failed']);
