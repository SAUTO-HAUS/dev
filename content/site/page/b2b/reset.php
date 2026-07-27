<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * /{lang}/b2b-reset?token=… — set a new password from the emailed link.
 *
 * The token is validated on load (so an expired/used link shows a friendly
 * message instead of the form). The new password is typed here and submitted
 * with the token to fn=b2b_reset_password.
 */

include_once( __DIR__ . '/_layout.php' );

use App\Services\B2b\B2bPasswordReset;

$lang = $_COOKIE['lang'] ?? 'ro';
$t    = b2b_lang($lang);

if (b2b_is_client()) {
    b2b_redirect('/'.$lang.'/b2b/cabinet');
}

$csrf  = b2b_esc(b2b_csrf_token());
$token = (string)($_GET['token'] ?? '');
$valid = $token !== '' && B2bPasswordReset::findValid($token) !== null;

echo b2b_assets();

// Expired / already used / forged link: no form, just a way back.
if (!$valid) {
    $body = '<p class="b2b-card__note">'.b2b_esc($t['reset_invalid_txt']).'</p>'
          . '<a class="b2b-btn b2b-btn--primary b2b-btn--block" href="/'.b2b_esc($lang).'/b2b-forgot">'.b2b_esc($t['forgot_title']).'</a>';
    echo b2b_auth_card($t['reset_invalid_ttl'], '', $body);
    return;
}

$toggle = b2b_pass_toggle($t);

$body = '
<form class="b2b-form" id="b2b-reset-form" data-csrf="'.$csrf.'" novalidate>
    <input type="hidden" name="token" value="'.b2b_esc($token).'" />

    <div class="b2b-field">
        <label for="b2b-reset-pass">'.b2b_esc($t['pw_new']).'</label>
        <div class="b2b-pass-wrap">
            <input type="password" id="b2b-reset-pass" name="new" minlength="8" required autocomplete="new-password" />
            '.$toggle.'
        </div>
        <small class="b2b-hint">'.b2b_esc($t['password_hint']).'</small>
    </div>

    <div class="b2b-field">
        <label for="b2b-reset-pass2">'.b2b_esc($t['pw_new_repeat']).'</label>
        <div class="b2b-pass-wrap">
            <input type="password" id="b2b-reset-pass2" name="confirm" minlength="8" required autocomplete="new-password" />
            '.$toggle.'
        </div>
    </div>

    <div class="b2b-form__msg" role="alert" aria-live="polite"></div>

    <button type="submit" class="b2b-btn b2b-btn--primary b2b-btn--block">'.b2b_esc($t['reset_submit']).'</button>
</form>

<div class="b2b-success" id="b2b-reset-success" hidden>
    <div class="b2b-success__ico">&#10003;</div>
    <h2 class="b2b-success__ttl">'.b2b_esc($t['reset_success_ttl']).'</h2>
    <p class="b2b-success__txt">'.b2b_esc($t['reset_done_txt']).'</p>
    <a class="b2b-btn b2b-btn--primary b2b-btn--block" href="/'.b2b_esc($lang).'/b2b-login">'.b2b_esc($t['reset_to_login']).'</a>
</div>';

echo b2b_auth_card($t['reset_title'], $t['reset_sub'], $body);
