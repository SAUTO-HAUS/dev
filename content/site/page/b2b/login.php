<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * /{lang}/b2b-login - single-step login.
 *
 * Login + password -> fn=b2b_login. Approval by the Super Admin is the only
 * gate, so a valid password on an active account opens the session right away.
 */

include_once( __DIR__ . '/_layout.php' );

$lang = $_COOKIE['lang'] ?? 'ro';
$t    = b2b_lang($lang);

if (b2b_is_client()) {
    b2b_redirect('/'.$lang.'/b2b/cabinet');
}

$csrf = b2b_esc(b2b_csrf_token());

echo b2b_assets();

$body = '
<form class="b2b-form" id="b2b-login-form" data-csrf="'.$csrf.'" novalidate>
    <div class="b2b-field">
        <label for="b2b-login-name">'.b2b_esc($t['login_field']).'</label>
        <input type="text" id="b2b-login-name" name="login" maxlength="64" required autocomplete="username" />
    </div>

    <div class="b2b-field">
        <label for="b2b-login-pass">'.b2b_esc($t['password']).'</label>
        <input type="password" id="b2b-login-pass" name="password" required autocomplete="current-password" />
    </div>

    <div class="b2b-form__msg" role="alert" aria-live="polite"></div>

    <button type="submit" class="b2b-btn b2b-btn--primary b2b-btn--block">'.b2b_esc($t['login_submit']).'</button>

    <p class="b2b-form__foot">
        '.b2b_esc($t['login_no_account']).'
        <a href="/'.b2b_esc($lang).'/b2b-register">'.b2b_esc($t['register']).'</a>
    </p>
</form>';

echo b2b_auth_card($t['login_title'], $t['login_sub'], $body);
