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

    /**
     * Show/hide password toggle button (eye + eye-off). The click handler is
     * delegated globally in b2b.js, so pages only need this markup inside a
     * `.b2b-pass-wrap` next to the password input.
     */
    function b2b_pass_toggle(array $t): string
    {
        $eyes =
            '<svg class="b2b-eye ico-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>'
          . '<svg class="b2b-eye ico-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/><line x1="3" y1="3" x2="21" y2="21"/></svg>';
        $show = b2b_esc($t['pass_show'] ?? 'Arată parola');
        $hide = b2b_esc($t['pass_hide'] ?? 'Ascunde parola');
        return '<button type="button" class="b2b-pass-toggle" data-b2b-pass-toggle'
             . ' data-show="'.$show.'" data-hide="'.$hide.'" aria-label="'.$show.'">'.$eyes.'</button>';
    }

    /**
     * Cabinet hero + nav cards (identity, saved cars, invoices, compare).
     *
     * Shared by the cabinet tabs and the /compare page so a logged-in partner
     * keeps the same header everywhere: opens `.b2b-page--wide` and the hero,
     * leaving the caller to append its own `.b2b-panel` and close both divs.
     *
     * $active is the nav slug to highlight: 'cabinet' | 'invoices' | 'compare'.
     */
    function b2b_cabinet_hero(PDO $db, array $user, string $lang, string $active): string
    {
        $t      = b2b_lang($lang);
        $userId = (int)$user['id'];

        // Section counters (cheap COUNTs). Compare is client-side, so it stays 0
        // here and is filled from localStorage by the compare JS in body.php.
        $countSaved = $countInvoices = 0;
        try {
            $q = $db->prepare('SELECT COUNT(*) FROM '.\App\Services\B2b\B2bConfig::table('saved_cars').' WHERE b2b_user_id = :uid');
            $q->execute([':uid' => $userId]); $countSaved = (int)$q->fetchColumn();
            $q = $db->prepare('SELECT COUNT(*) FROM '.\App\Services\B2b\B2bConfig::table('invoices').' WHERE b2b_user_id = :uid');
            $q->execute([':uid' => $userId]); $countInvoices = (int)$q->fetchColumn();
        } catch (\Throwable $e) {}

        // Avatar initials from the display name (e.g. "Grigore Botnarenco" -> "GB").
        $displayName = \App\Services\B2b\B2bAuth::displayName($user);
        $initials = '';
        foreach (preg_split('/\s+/', trim($displayName)) ?: [] as $w) {
            if ($w !== '') { $initials .= mb_strtoupper(mb_substr($w, 0, 1)); if (mb_strlen($initials) >= 2) break; }
        }
        if ($initials === '') { $initials = 'B'; }

        // Feather-style icons for the nav cards.
        $icoHeart   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 1 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>';
        $icoDoc     = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>';
        $icoCompare = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 20h10"/><path d="M6 6l6-1 6 1"/><path d="M12 3v17"/><path d="M9 12L6 6l-3 6a3 3 0 0 0 6 0"/><path d="M21 12l-3-6-3 6a3 3 0 0 0 6 0"/></svg>';
        $cmpLabel   = ['ro' => 'Comparare', 'ru' => 'Сравнение', 'en' => 'Compare'][$lang] ?? 'Comparare';

        // [label, count, icon, href, extra-attr on the number span]
        // Order: favourites, compare, then payment invoices.
        $navItems = [
            'cabinet'  => [$t['tab_cars'],     $countSaved,    $icoHeart,   '/'.b2b_esc($lang).'/b2b/cabinet',  ' data-b2b-count="saved"'],
            'compare'  => [$cmpLabel,          0,              $icoCompare, '/'.b2b_esc($lang).'/compare',      ' data-cmp-count'],
            'invoices' => [$t['tab_invoices'], $countInvoices, $icoDoc,     '/'.b2b_esc($lang).'/b2b/invoices', ''],
        ];

        $out = '
<div class="b2b-page b2b-page--wide">
    <div class="b2b-hero">
        <div class="b2b-hero__id">
            <span class="b2b-hero__avatar">'.b2b_esc($initials).'</span>
            <div class="b2b-hero__text">
                '// Greeting on the left, "change password" on the right of the same line.
                // The modal it opens is appended below, so the button works on every
                // page that renders this hero — /compare included.
                .'<div class="b2b-hero__toprow">
                    <p class="b2b-hero__greet">'.b2b_esc($t['welcome']).'</p>
                    <button type="button" class="b2b-hero__pw" data-b2b-pw-toggle>'
                      .'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>'
                      .'<span>'.b2b_esc($t['pw_change_title']).'</span>
                    </button>
                </div>
                <h1 class="b2b-hero__name">'.b2b_esc($displayName).'</h1>
                <span class="b2b-badge b2b-badge--'.b2b_esc($user['status']).'">'.b2b_status_label((string)$user['status']).'</span>
            </div>
        </div>

        <div class="b2b-nav">';
        foreach ($navItems as $slug => [$label, $count, $ico, $href, $numAttr]) {
            $out .= '<a class="b2b-navcard'.($slug === $active ? ' is-active' : '').'" href="'.$href.'">'
                  . '<span class="b2b-navcard__ico">'.$ico.'</span>'
                  . '<span class="b2b-navcard__meta">'
                  . '<span class="b2b-navcard__num"'.$numAttr.'>'.(int)$count.'</span>'
                  . '<span class="b2b-navcard__label">'.b2b_esc($label).'</span>'
                  . '</span></a>';
        }
        $out .= '
        </div>
    </div>'.b2b_pw_modal($lang);

        return $out;
    }

    /**
     * "Change password" modal, closed by default. Rendered together with the hero
     * so its button never points at a modal that is not on the page; opened by
     * [data-b2b-pw-toggle] and closed by the x / backdrop / Escape (b2b.js).
     */
    function b2b_pw_modal(string $lang): string
    {
        $t    = b2b_lang($lang);
        $csrf = b2b_esc(b2b_csrf_token());

        return '
<div class="b2b-modal b2b-pw-modal" id="b2b-pw-modal" hidden>
    <div class="b2b-modal__backdrop" data-b2b-pw-close></div>
    <div class="b2b-modal__box">
        <button type="button" class="b2b-modal__x" data-b2b-pw-close aria-label="'.b2b_esc($t['pw_close']).'">&times;</button>
        <h2 class="b2b-modal__ttl">'.b2b_esc($t['pw_change_title']).'</h2>
        <form class="b2b-form" id="b2b-change-password-form" data-csrf="'.$csrf.'" novalidate>
            <div class="b2b-field">
                <label for="b2b-pw-cur">'.b2b_esc($t['pw_current']).'</label>
                <div class="b2b-pass-wrap">
                    <input type="password" id="b2b-pw-cur" name="current" required autocomplete="current-password" />
                    '.b2b_pass_toggle($t).'
                </div>
            </div>
            <div class="b2b-field">
                <label for="b2b-pw-new">'.b2b_esc($t['pw_new']).'</label>
                <div class="b2b-pass-wrap">
                    <input type="password" id="b2b-pw-new" name="new" minlength="6" required autocomplete="new-password" />
                    '.b2b_pass_toggle($t).'
                </div>
                <small class="b2b-hint">'.b2b_esc($t['password_hint']).'</small>
            </div>
            <div class="b2b-field">
                <label for="b2b-pw-new2">'.b2b_esc($t['pw_new_repeat']).'</label>
                <div class="b2b-pass-wrap">
                    <input type="password" id="b2b-pw-new2" name="confirm" minlength="6" required autocomplete="new-password" />
                    '.b2b_pass_toggle($t).'
                </div>
            </div>
            <div class="b2b-form__msg" role="alert" aria-live="polite"></div>
            <button type="submit" class="b2b-btn b2b-btn--primary b2b-btn--block">'.b2b_esc($t['pw_save']).'</button>
        </form>
    </div>
</div>';
    }
}
