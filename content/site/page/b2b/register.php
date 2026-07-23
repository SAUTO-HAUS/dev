<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * /{lang}/b2b-register - B2B signup (spec 2.1).
 * Posts to fn=b2b_register; on success the form is replaced by the blocking
 * confirmation message the spec requires, with no redirect.
 */

include_once( __DIR__ . '/_layout.php' );
include_once( _SITE_INCL . '/b2b/b2b_countries.php' );

$lang = $_COOKIE['lang'] ?? 'ro';
$t    = b2b_lang($lang);

// An authenticated partner has no business on the signup page.
if (b2b_is_client()) {
    b2b_redirect('/'.$lang.'/b2b/cabinet');
}

$csrf = b2b_esc(b2b_csrf_token());

echo b2b_assets();

$form = '
<form class="b2b-form" id="b2b-register-form" data-csrf="'.$csrf.'" novalidate>
    <div class="b2b-field">
        <label for="b2b-company">'.b2b_esc($t['company_name']).' *</label>
        <input type="text" id="b2b-company" name="company_name" maxlength="190" required autocomplete="organization" />
    </div>

    <div class="b2b-field-row">
        <div class="b2b-field">
            <label for="b2b-idno">'.b2b_esc($t['idno']).' *</label>
            <input type="text" id="b2b-idno" name="idno" inputmode="numeric" maxlength="13" pattern="\d{13}" required />
            <small class="b2b-hint">'.b2b_esc($t['idno_hint']).'</small>
        </div>
        <div class="b2b-field">
            <label for="b2b-repr">'.b2b_esc($t['representative']).' *</label>
            <input type="text" id="b2b-repr" name="representative_name" maxlength="190" required autocomplete="name" />
        </div>
    </div>

    <div class="b2b-field-row">
        <div class="b2b-field">
            <label for="b2b-email">'.b2b_esc($t['email']).' *</label>
            <input type="email" id="b2b-email" name="email" maxlength="190" required autocomplete="email" />
        </div>
        <div class="b2b-field">
            <label for="b2b-phone">'.b2b_esc($t['phone']).' *</label>
            <!-- Country picker + national number. The dialling code is prepended
                 on submit (b2b.js); the server normalises either way. -->
            <div class="b2b-phone">
                '.b2b_dial_picker('md').'
                <input type="tel" id="b2b-phone" name="phone" maxlength="32" required autocomplete="tel" placeholder="60 123 456" />
            </div>
            <small class="b2b-hint">'.b2b_esc($t['phone_hint']).'</small>
        </div>
    </div>

    <div class="b2b-field-row">
        <div class="b2b-field">
            <label for="b2b-pass">'.b2b_esc($t['password']).' *</label>
            <input type="password" id="b2b-pass" name="password" minlength="8" required autocomplete="new-password" />
            <small class="b2b-hint">'.b2b_esc($t['password_hint']).'</small>
        </div>
        <div class="b2b-field">
            <label for="b2b-pass2">'.b2b_esc($t['password_repeat']).' *</label>
            <input type="password" id="b2b-pass2" name="password_repeat" minlength="8" required autocomplete="new-password" />
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
