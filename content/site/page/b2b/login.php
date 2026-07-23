<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * /{lang}/b2b-login - two-step login (spec 2.1).
 *
 * Step 1: email + password -> fn=b2b_login (sends the SMS, creates no session).
 * Step 2: 6-digit code     -> fn=b2b_verify_otp (creates the session).
 *
 * Both steps live on this page; step 2 is a panel shown over the form, with an
 * expiry timer and a resend button.
 */

include_once( __DIR__ . '/_layout.php' );

use App\Services\B2b\B2bConfig;

$lang = $_COOKIE['lang'] ?? 'ro';
$t    = b2b_lang($lang);

if (b2b_is_client()) {
    b2b_redirect('/'.$lang.'/b2b/cabinet');
}

$csrf = b2b_esc(b2b_csrf_token());

echo b2b_assets();

// The six OTP cells.
$otpInputs = '';
for ($i = 0; $i < 6; $i++) {
    $otpInputs .= '<input type="text" class="b2b-otp__cell" inputmode="numeric" maxlength="1" '
                . 'pattern="\d" autocomplete="'.($i === 0 ? 'one-time-code' : 'off').'" '
                . 'aria-label="'.($i + 1).'" />';
}

$body = '
<form class="b2b-form" id="b2b-login-form" data-csrf="'.$csrf.'" data-otp-ttl="'.(int)B2bConfig::OTP_TTL.'" novalidate>
    <div class="b2b-field">
        <label for="b2b-login-email">'.b2b_esc($t['email']).'</label>
        <input type="email" id="b2b-login-email" name="email" maxlength="190" required autocomplete="email" />
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
</form>

<div class="b2b-otp" id="b2b-otp" hidden>
    <h2 class="b2b-otp__ttl">'.b2b_esc($t['otp_title']).'</h2>
    <p class="b2b-otp__sub">'.b2b_esc($t['otp_sub']).' <strong class="b2b-otp__phone"></strong></p>

    <div class="b2b-otp__cells">'.$otpInputs.'</div>

    <p class="b2b-otp__timer">
        <span class="b2b-otp__timer-label">'.b2b_esc($t['otp_expires']).'</span>
        <strong class="b2b-otp__countdown">05:00</strong>
    </p>

    <div class="b2b-form__msg" role="alert" aria-live="polite"></div>

    <button type="button" class="b2b-btn b2b-btn--primary b2b-btn--block" data-b2b-action="verify-otp">'.b2b_esc($t['otp_submit']).'</button>

    <div class="b2b-otp__foot">
        <button type="button" class="b2b-link" data-b2b-action="resend-otp">'.b2b_esc($t['otp_resend']).'</button>
        <button type="button" class="b2b-link" data-b2b-action="otp-back">'.b2b_esc($t['otp_back']).'</button>
    </div>
</div>';

echo b2b_auth_card($t['login_title'], $t['login_sub'], $body);
