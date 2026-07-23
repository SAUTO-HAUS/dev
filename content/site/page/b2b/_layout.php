<?php defined( '_DOIT' ) or die( 'Restricted access' );

/** Shared bootstrap for the B2B pages: translations, assets, render helpers. */

include_once( __DIR__ . '/_lang.php' );

if (!function_exists('b2b_assets')) {

    /**
     * Emits the module assets, each at most once per page.
     *
     * The header button renders on every page, so the CSS is needed site-wide;
     * the JS only where there is something interactive. Pass false to skip it.
     */
    function b2b_assets(bool $withJs = true): string
    {
        static $cssDone = false;
        static $jsDone  = false;

        $out = '';

        if (!$cssDone) {
            $cssDone = true;
            $path = _ROOT . '/content/site/css/b2b.css';
            $v = file_exists($path) ? date('YmdHis', filemtime($path)) : '1';
            $out .= '<link rel="stylesheet" href="/content/site/css/b2b.css?v='.$v.'" />';
        }

        if ($withJs && !$jsDone) {
            $jsDone = true;
            $path = _ROOT . '/content/site/js/b2b.js';
            $v = file_exists($path) ? date('YmdHis', filemtime($path)) : '1';
            $out .= '<script src="/content/site/js/b2b.js?v='.$v.'" defer></script>';
        }

        return $out;
    }

    /**
     * Page shell used by login and register.
     *
     * $modifier adds a class to .b2b-page; "b2b-page--form" makes the card span
     * the content width on desktop and lays the fields out in three columns.
     */
    function b2b_auth_card(string $title, string $subtitle, string $body, string $modifier = ''): string
    {
        return '
<div class="b2b-page'.($modifier !== '' ? ' '.htmlspecialchars($modifier, ENT_QUOTES, 'UTF-8') : '').'">
    <div class="b2b-card">
        <div class="b2b-card__head">
            <h1 class="b2b-card__ttl">'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</h1>
            <p class="b2b-card__sub">'.htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8').'</p>
        </div>
        '.$body.'
    </div>
</div>';
    }

    function b2b_status_label(string $status): string
    {
        return b2b_t('st_' . $status);
    }

    function b2b_region_label(string $region): string
    {
        return b2b_t('rg_' . $region);
    }

    function b2b_action_label(string $action): string
    {
        $t = b2b_lang();
        return htmlspecialchars($t['ac_' . $action] ?? $action, ENT_QUOTES, 'UTF-8');
    }

    function b2b_redirect(string $path): void
    {
        if (!headers_sent()) {
            header('Location: ' . $path, true, 302);
        }
        echo '<meta http-equiv="refresh" content="0;url='.htmlspecialchars($path, ENT_QUOTES, 'UTF-8').'">';
        exit;
    }

    function b2b_esc($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
