<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * /{lang}/b2b-login - single-step login.
 *
 * Login (or email) + password -> fn=b2b_login. Only a blocked account is
 * refused, so a valid password opens the session right away.
 */

include_once( __DIR__ . '/_layout.php' );

$lang = $_COOKIE['lang'] ?? 'ro';
$t    = b2b_lang($lang);

if (b2b_is_client()) {
    b2b_redirect('/'.$lang.'/b2b/cabinet');
}

$csrf = b2b_esc(b2b_csrf_token());

echo b2b_assets();

// Same show/hide toggle as the register page (eye + eye-off, one shown by CSS).
// The click handler is delegated globally in b2b.js, so only the markup is needed.
$eyeIcons =
    '<svg class="b2b-eye ico-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>'
  . '<svg class="b2b-eye ico-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/><line x1="3" y1="3" x2="21" y2="21"/></svg>';
$passToggle =
    '<button type="button" class="b2b-pass-toggle" data-b2b-pass-toggle'
  . ' data-show="'.b2b_esc($t['pass_show']).'" data-hide="'.b2b_esc($t['pass_hide']).'"'
  . ' aria-label="'.b2b_esc($t['pass_show']).'">'.$eyeIcons.'</button>';

$body = '
<form class="b2b-form" id="b2b-login-form" data-csrf="'.$csrf.'" novalidate>
    <div class="b2b-field">
        <label for="b2b-login-name">'.b2b_esc($t['email']).'</label>
        '// The field still posts as `login`: the server matches it against both
         // columns, so accounts created before the email-as-identifier change can
         // still sign in with the login they picked back then.
        .'<input type="email" id="b2b-login-name" name="login" maxlength="190" required autocomplete="email" />
    </div>

    <div class="b2b-field">
        <label for="b2b-login-pass">'.b2b_esc($t['password']).'</label>
        <div class="b2b-pass-wrap">
            <input type="password" id="b2b-login-pass" name="password" required autocomplete="current-password" />
            '.$passToggle.'
        </div>
    </div>

    <div class="b2b-form__msg" role="alert" aria-live="polite"></div>

    <button type="submit" class="b2b-btn b2b-btn--primary b2b-btn--block">'.b2b_esc($t['login_submit']).'</button>

    <p class="b2b-form__foot b2b-form__foot--forgot">
        <a href="/'.b2b_esc($lang).'/b2b-forgot">'.b2b_esc($t['login_forgot']).'</a>
    </p>
    <p class="b2b-form__foot">
        '.b2b_esc($t['login_no_account']).'
        <a href="/'.b2b_esc($lang).'/b2b-register">'.b2b_esc($t['register']).'</a>
    </p>
</form>';

echo b2b_auth_card($t['login_title'], $t['login_sub'], $body);
