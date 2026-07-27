<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * /{lang}/b2b-register - B2B signup (spec 2.1).
 * Posts to fn=b2b_register; on success the form is replaced by the blocking
 * confirmation message the spec requires, with no redirect.
 */

include_once( __DIR__ . '/_layout.php' );

use App\Services\B2b\B2bPhone;

$lang = $_COOKIE['lang'] ?? 'ro';
$t    = b2b_lang($lang);

// An authenticated partner has no business on the signup page.
if (b2b_is_client()) {
    b2b_redirect('/'.$lang.'/b2b/cabinet');
}

$csrf = b2b_esc(b2b_csrf_token());

echo b2b_assets();

// Show/hide toggle reused by both password fields (eye + eye-off, one shown by CSS).
$eyeIcons =
    '<svg class="b2b-eye ico-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>'
  . '<svg class="b2b-eye ico-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/><line x1="3" y1="3" x2="21" y2="21"/></svg>';
$passToggle =
    '<button type="button" class="b2b-pass-toggle" data-b2b-pass-toggle'
  . ' data-show="'.b2b_esc($t['pass_show']).'" data-hide="'.b2b_esc($t['pass_hide']).'"'
  . ' aria-label="'.b2b_esc($t['pass_show']).'">'.$eyeIcons.'</button>';

$form = '
<form class="b2b-form" id="b2b-register-form" data-csrf="'.$csrf.'" novalidate>
    <div class="b2b-field">
        <span class="b2b-field__lbl">'.b2b_esc($t['person_type']).' *</span>
        <div class="b2b-radios">
            <label class="b2b-radio">
                <input type="radio" name="person_type" value="individual" checked />
                <span>'.b2b_esc($t['person_individual']).'</span>
            </label>
            <label class="b2b-radio">
                <input type="radio" name="person_type" value="company" />
                <span>'.b2b_esc($t['person_company']).'</span>
            </label>
        </div>
    </div>

    <div class="b2b-field-row">
        <div class="b2b-field">
            <label for="b2b-email">'.b2b_esc($t['email']).' *</label>
            <input type="email" id="b2b-email" name="email" maxlength="190" required autocomplete="email" />
        </div>
        <div class="b2b-field">
            <label for="b2b-phone">'.b2b_esc($t['phone']).' *</label>
            <!-- Moldova only: fixed prefix, 8 digits typed by the client. -->
            <div class="b2b-phone">
                <span class="b2b-phone__dial">'.b2b_esc(B2bPhone::MD_DIAL).'</span>
                <input type="tel" id="b2b-phone" name="phone" inputmode="numeric"
                       maxlength="'.(int)B2bPhone::MD_DIGITS.'" pattern="\d{'.(int)B2bPhone::MD_DIGITS.'}"
                       required autocomplete="tel" placeholder="60 123 456" />
            </div>
        </div>
    </div>

    <div class="b2b-field-row">
        <div class="b2b-field">
            <label for="b2b-name">'.b2b_esc($t['full_name']).' *</label>
            <input type="text" id="b2b-name" name="full_name" maxlength="190" required autocomplete="name" />
        </div>
        <div class="b2b-field">
            <label for="b2b-login">'.b2b_esc($t['login_field']).' *</label>
            <input type="text" id="b2b-login" name="login" minlength="4" maxlength="64" pattern="[A-Za-z0-9._-]{4,64}" required autocomplete="username" />
            <small class="b2b-hint">'.b2b_esc($t['login_hint']).'</small>
        </div>
    </div>

    <div class="b2b-field-row">
        <div class="b2b-field">
            <label for="b2b-pass">'.b2b_esc($t['password']).' *</label>
            <div class="b2b-pass-wrap">
                <input type="password" id="b2b-pass" name="password" minlength="6" required autocomplete="new-password" />
                '.$passToggle.'
            </div>
            <small class="b2b-hint">'.b2b_esc($t['password_hint']).'</small>
        </div>
        <div class="b2b-field">
            <label for="b2b-pass2">'.b2b_esc($t['password_repeat']).' *</label>
            <div class="b2b-pass-wrap">
                <input type="password" id="b2b-pass2" name="password_repeat" minlength="6" required autocomplete="new-password" />
                '.$passToggle.'
            </div>
        </div>
    </div>

    <div class="b2b-form__msg" role="alert" aria-live="polite"></div>

    <button type="submit" class="b2b-btn b2b-btn--primary b2b-btn--block">'.b2b_esc($t['reg_submit']).'</button>

    <p class="b2b-form__foot">
        '.b2b_esc($t['reg_have_account']).'
        <a href="/'.b2b_esc($lang).'/b2b-login">'.b2b_esc($t['login']).'</a>
    </p>
</form>

<div class="b2b-success" id="b2b-register-success" hidden>
    <div class="b2b-success__ico">&#10003;</div>
    <h2 class="b2b-success__ttl">'.b2b_esc($t['reg_success_ttl']).'</h2>
    <p class="b2b-success__txt">'.b2b_esc($t['reg_success']).'</p>
    <a class="b2b-btn b2b-btn--ghost" href="/'.b2b_esc($lang).'/ordercars">'.b2b_esc($t['back_to_site']).'</a>
</div>';

echo b2b_auth_card($t['reg_title'], $t['reg_sub'], $form, 'b2b-page--form');
