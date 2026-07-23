<?php defined('_DOIT') or die('Restricted access');

/**
 * B2B module settings: SMS provider (2FA), WhatsApp, Super Admin contact and the
 * default advance. Values live in gh3sp_settings, so switching SMS provider or
 * enabling WhatsApp Cloud API needs no code change.
 */

use App\Services\B2b\B2bConfig;

$lang = $_COOKIE['lang'] ?? 'ro';
$t    = b2b_adm_lang($lang);

$smsDriver = B2bConfig::get('b2b_sms_driver', 'log');
$waDriver  = B2bConfig::get('b2b_whatsapp_driver', 'walink');
$advMode   = B2bConfig::get('b2b_advance_mode', 'fixed');
?>

<div class="b2ba" id="b2ba-settings" data-saved-msg="<?= b2b_adm_esc($t['saved']) ?>">
    <div class="b2ba-head">
        <h1 class="b2ba-h1"><?= b2b_adm_esc($t['settings_title']) ?></h1>
    </div>

    <div class="b2ba-msg" id="b2ba-msg" role="status" aria-live="polite"></div>

    <!-- ------------------------------------------------------------- SMS -->
    <div class="b2ba-card">
        <h2 class="b2ba-h2"><?= b2b_adm_esc($t['sms_section']) ?></h2>

        <div class="b2ba-grid">
            <label class="b2ba-field">
                <span><?= b2b_adm_esc($t['sms_driver']) ?></span>
                <select data-setting="b2b_sms_driver">
                    <option value="log"    <?= $smsDriver === 'log'    ? 'selected' : '' ?>><?= b2b_adm_esc($t['sms_driver_log']) ?></option>
                    <option value="smsmd"  <?= $smsDriver === 'smsmd'  ? 'selected' : '' ?>>SMS.md / gateway local</option>
                    <option value="twilio" <?= $smsDriver === 'twilio' ? 'selected' : '' ?>>Twilio</option>
                </select>
            </label>
            <label class="b2ba-field">
                <span><?= b2b_adm_esc($t['sms_sender']) ?></span>
                <input type="text" data-setting="b2b_sms_sender" value="<?= b2b_adm_esc(B2bConfig::get('b2b_sms_sender', 'SAUTO')) ?>">
            </label>
            <label class="b2ba-field b2ba-field--wide">
                <span><?= b2b_adm_esc($t['sms_api_key']) ?></span>
                <input type="password" data-setting="b2b_sms_api_key" value="<?= b2b_adm_esc(B2bConfig::get('b2b_sms_api_key')) ?>" autocomplete="new-password">
            </label>
            <label class="b2ba-field">
                <span><?= b2b_adm_esc($t['sms_user']) ?></span>
                <input type="text" data-setting="b2b_sms_api_user" value="<?= b2b_adm_esc(B2bConfig::get('b2b_sms_api_user')) ?>" autocomplete="off">
            </label>
            <label class="b2ba-field">
                <span><?= b2b_adm_esc($t['sms_pass']) ?></span>
                <input type="password" data-setting="b2b_sms_api_pass" value="<?= b2b_adm_esc(B2bConfig::get('b2b_sms_api_pass')) ?>" autocomplete="new-password">
            </label>
            <label class="b2ba-field b2ba-field--wide">
                <span><?= b2b_adm_esc($t['sms_endpoint']) ?></span>
                <input type="url" data-setting="b2b_sms_api_url" value="<?= b2b_adm_esc(B2bConfig::get('b2b_sms_api_url')) ?>" placeholder="https://api.sms.md/v1/send">
            </label>
            <label class="b2ba-field b2ba-field--wide">
                <span><?= b2b_adm_esc($t['sms_debug_email']) ?></span>
                <input type="email" data-setting="b2b_sms_debug_email" value="<?= b2b_adm_esc(B2bConfig::get('b2b_sms_debug_email')) ?>">
            </label>
        </div>
    </div>

    <!-- -------------------------------------------------------- WhatsApp -->
    <div class="b2ba-card">
        <h2 class="b2ba-h2"><?= b2b_adm_esc($t['wa_section']) ?></h2>

        <div class="b2ba-grid">
            <label class="b2ba-field">
                <span><?= b2b_adm_esc($t['wa_driver']) ?></span>
                <select data-setting="b2b_whatsapp_driver">
                    <option value="walink"    <?= $waDriver === 'walink'    ? 'selected' : '' ?>><?= b2b_adm_esc($t['wa_driver_link']) ?></option>
                    <option value="cloud_api" <?= $waDriver === 'cloud_api' ? 'selected' : '' ?>><?= b2b_adm_esc($t['wa_driver_api']) ?></option>
                </select>
            </label>
            <label class="b2ba-field">
                <span><?= b2b_adm_esc($t['wa_phone_id']) ?></span>
                <input type="text" data-setting="b2b_whatsapp_phone_id" value="<?= b2b_adm_esc(B2bConfig::get('b2b_whatsapp_phone_id')) ?>" autocomplete="off">
            </label>
            <label class="b2ba-field b2ba-field--wide">
                <span><?= b2b_adm_esc($t['wa_token']) ?></span>
                <input type="password" data-setting="b2b_whatsapp_token" value="<?= b2b_adm_esc(B2bConfig::get('b2b_whatsapp_token')) ?>" autocomplete="new-password">
            </label>
            <label class="b2ba-field">
                <span><?= b2b_adm_esc($t['wa_template']) ?></span>
                <input type="text" data-setting="b2b_whatsapp_template" value="<?= b2b_adm_esc(B2bConfig::get('b2b_whatsapp_template')) ?>">
            </label>
        </div>
    </div>

    <!-- ------------------------------------------------------ Super Admin -->
    <div class="b2ba-card">
        <h2 class="b2ba-h2"><?= b2b_adm_esc($t['admin_section']) ?></h2>

        <div class="b2ba-grid">
            <label class="b2ba-field">
                <span><?= b2b_adm_esc($t['admin_phone']) ?></span>
                <input type="tel" data-setting="b2b_superadmin_phone" value="<?= b2b_adm_esc(B2bConfig::get('b2b_superadmin_phone')) ?>" placeholder="+373 60 000 000">
            </label>
            <label class="b2ba-field">
                <span><?= b2b_adm_esc($t['admin_email']) ?></span>
                <input type="email" data-setting="b2b_superadmin_email" value="<?= b2b_adm_esc(B2bConfig::get('b2b_superadmin_email')) ?>">
            </label>
        </div>
    </div>

    <!-- --------------------------------------------------------- Invoices -->
    <div class="b2ba-card">
        <h2 class="b2ba-h2"><?= b2b_adm_esc($t['invoice_section']) ?></h2>

        <p class="b2ba-hint"><?= b2b_adm_esc($t['advance_hint']) ?></p>

        <div class="b2ba-grid">
            <label class="b2ba-field">
                <span><?= b2b_adm_esc($t['advance_mode']) ?></span>
                <select data-setting="b2b_advance_mode">
                    <option value="fixed"   <?= $advMode === 'fixed'   ? 'selected' : '' ?>><?= b2b_adm_esc($t['advance_mode_fixed']) ?></option>
                    <option value="percent" <?= $advMode === 'percent' ? 'selected' : '' ?>><?= b2b_adm_esc($t['advance_mode_percent']) ?></option>
                </select>
            </label>
            <label class="b2ba-field">
                <span><?= b2b_adm_esc($t['advance_default']) ?></span>
                <input type="number" min="0" step="1" data-setting="b2b_advance_default"
                       value="<?= b2b_adm_esc(B2bConfig::get('b2b_advance_default', '1000')) ?>">
            </label>
            <label class="b2ba-field">
                <span><?= b2b_adm_esc($t['advance_percent']) ?></span>
                <input type="number" min="0" max="100" step="0.1" data-setting="b2b_advance_percent"
                       value="<?= b2b_adm_esc(B2bConfig::get('b2b_advance_percent', '10')) ?>">
            </label>
            <label class="b2ba-field">
                <span><?= b2b_adm_esc($t['advance_max']) ?></span>
                <input type="number" min="0" step="1" data-setting="b2b_advance_max"
                       value="<?= b2b_adm_esc(B2bConfig::get('b2b_advance_max', '0')) ?>">
            </label>
        </div>

        <div class="b2ba-card__foot">
            <span></span>
            <button type="button" class="b2ba-btn b2ba-btn--primary" data-b2b-admin="save-settings">
                <?= b2b_adm_esc($t['save']) ?>
            </button>
        </div>
    </div>
</div>

<script src="/content/admin/include/b2b/b2b_admin.js?v=<?= file_exists(_ROOT.'/content/admin/include/b2b/b2b_admin.js') ? date('YmdHis', filemtime(_ROOT.'/content/admin/include/b2b/b2b_admin.js')) : '1' ?>" defer></script>
