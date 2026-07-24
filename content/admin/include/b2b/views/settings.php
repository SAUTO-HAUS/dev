<?php defined('_DOIT') or die('Restricted access');

/**
 * B2B module settings: Super Admin contact (WhatsApp / email) and the default
 * advance. Values live in gh3sp_settings.
 */

use App\Services\B2b\B2bConfig;

$lang = $_COOKIE['lang'] ?? 'ro';
$t    = b2b_adm_lang($lang);

$advMode = B2bConfig::get('b2b_advance_mode', 'fixed');
?>

<div class="b2ba" id="b2ba-settings" data-saved-msg="<?= b2b_adm_esc($t['saved']) ?>">
    <div class="b2ba-head">
        <h1 class="b2ba-h1"><?= b2b_adm_esc($t['settings_title']) ?></h1>
    </div>

    <div class="b2ba-msg" id="b2ba-msg" role="status" aria-live="polite"></div>

    <!-- ------------------------------------------------------ Super Admin -->
    <div class="b2ba-card">
        <h2 class="b2ba-h2"><?= b2b_adm_esc($t['admin_section']) ?></h2>

        <p class="b2ba-hint"><?= b2b_adm_esc($t['admin_hint']) ?></p>

        <div class="b2ba-persons">
            <div class="b2ba-person">
                <div class="b2ba-person__ttl"><?= b2b_adm_esc($t['admin_recipient']) ?> 1</div>
                <label class="b2ba-field">
                    <span><?= b2b_adm_esc($t['phone']) ?></span>
                    <input type="text" data-setting="b2b_superadmin_phone" value="<?= b2b_adm_esc(B2bConfig::get('b2b_superadmin_phone')) ?>" placeholder="+373 60 000 000">
                </label>
                <label class="b2ba-field">
                    <span><?= b2b_adm_esc($t['email']) ?></span>
                    <input type="email" data-setting="b2b_superadmin_email" value="<?= b2b_adm_esc(B2bConfig::get('b2b_superadmin_email')) ?>">
                </label>
            </div>
            <div class="b2ba-person">
                <div class="b2ba-person__ttl"><?= b2b_adm_esc($t['admin_recipient']) ?> 2 <span class="b2ba-person__opt"><?= b2b_adm_esc($t['optional']) ?></span></div>
                <label class="b2ba-field">
                    <span><?= b2b_adm_esc($t['phone']) ?></span>
                    <input type="text" data-setting="b2b_superadmin_phone_2" value="<?= b2b_adm_esc(B2bConfig::get('b2b_superadmin_phone_2')) ?>" placeholder="+373 61 111 111">
                </label>
                <label class="b2ba-field">
                    <span><?= b2b_adm_esc($t['email']) ?></span>
                    <input type="email" data-setting="b2b_superadmin_email_2" value="<?= b2b_adm_esc(B2bConfig::get('b2b_superadmin_email_2')) ?>">
                </label>
            </div>
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
