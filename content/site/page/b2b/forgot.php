<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * /{lang}/b2b-forgot — request a password-reset link.
 *
 * Enter the account email -> fn=b2b_forgot_password. The answer is always the
 * same generic message (see B2bPasswordReset::request), so this page cannot be
 * used to tell which emails are registered.
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
<form class="b2b-form" id="b2b-forgot-form" data-csrf="'.$csrf.'" novalidate>
    <div class="b2b-field">
        <label for="b2b-forgot-email">'.b2b_esc($t['forgot_email']).'</label>
        <input type="email" id="b2b-forgot-email" name="email" maxlength="190" required autocomplete="email" />
    </div>

    <div class="b2b-form__msg" role="alert" aria-live="polite"></div>

    <button type="submit" class="b2b-btn b2b-btn--primary b2b-btn--block">'.b2b_esc($t['forgot_submit']).'</button>

    <p class="b2b-form__foot">
        <a href="/'.b2b_esc($lang).'/b2b-login">'.b2b_esc($t['forgot_back']).'</a>
    </p>
</form>';

echo b2b_auth_card($t['forgot_title'], $t['forgot_sub'], $body);
