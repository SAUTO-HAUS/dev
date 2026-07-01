<?php defined( '_DOIT' ) or die( 'Restricted access' );

include_once _ADM_PAGE.'/parsing/parsing_lang.php';
$t = $parsing_lang;

// Settings: full-access ids only (see include/parsing_access.php).
require_once(_ADM_INCL.'/parsing_access.php');
if (!parsing_is_full($user_id ?? 0)) {
    echo '<span class="err">'.$t['access_denied'].'</span>';
    return;
}

$settings = [];
try {
    $stmt = $db->prepare('SELECT * FROM '.$prefx.'_parsing_settings');
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    
}

$priceConfigs = [];
try {
    $stmt = $db->prepare('SELECT * FROM '.$prefx.'_parsing_price_config WHERE active = 1 ORDER BY country_name');
    $stmt->execute();
    $priceConfigs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {

}

$euTiers = [];
try {
    $stmt = $db->prepare('SELECT * FROM '.$prefx.'_parsing_eu_tiers ORDER BY sort_order, price_from');
    $stmt->execute();
    $euTiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {

}

$euParams = [];
try {
    $stmt = $db->prepare('SELECT * FROM '.$prefx.'_parsing_eu_params ORDER BY sort_order, id');
    $stmt->execute();
    $euParams = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {

}

$commissionTiers = [];
try {
    $stmt = $db->prepare('SELECT * FROM '.$prefx.'_parsing_commission_tiers ORDER BY sort_order, price_from');
    $stmt->execute();
    $commissionTiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {

}

$krMarkupTiers = [];
try {
    $stmt = $db->prepare('SELECT * FROM '.$prefx.'_parsing_kr_markup_tiers ORDER BY sort_order, price_from');
    $stmt->execute();
    $krMarkupTiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {

}

$krParams = [];
try {
    $stmt = $db->prepare('SELECT * FROM '.$prefx.'_parsing_kr_params ORDER BY sort_order, id');
    $stmt->execute();
    $krParams = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {

}

$g = function($key, $default = '') use ($settings) {
    return htmlspecialchars($settings[$key] ?? $default);
};

// Flag icons shown in section titles instead of the "Europe" / "Korea" words.
$flagStyle = 'height:1.1em;width:auto;vertical-align:-0.15em;margin-right:0.4rem;';
$flagEu = '<img src="/content/admin/page/parsing/media-parsing/flag-europe.svg" alt="Europa" style="'.$flagStyle.'">';
$flagKr = '<img src="/content/admin/page/parsing/media-parsing/flag-korea.svg" alt="Coreea" style="'.$flagStyle.'">';

$rtrn = '
<link rel="stylesheet" href="/content/admin/page/parsing/parsing.css?v='.filemtime(_ADM_PAGE.'/parsing/parsing.css').'">
<div id="parsing-container">
    <div class="parsing-header">
        <h1>'.$t['page_settings'].'</h1>
    </div>

    <div class="parsing-tabs">
        <a href="/'.$admin_dir.'/parsing/filters" class="tab">'.$t['tab_filters'].'</a>
        <a href="/'.$admin_dir.'/parsing/ctlg" class="tab">'.$t['tab_ctlg'].'</a>
        <a href="/'.$admin_dir.'/parsing/published" class="tab">'.$t['tab_published'].'</a>
        <a href="/'.$admin_dir.'/parsing/settings" class="tab active">'.$t['tab_settings'].'</a>
        <a href="/'.$admin_dir.'/parsing/favorites" class="tab tab-favorites"><img src="/content/admin/page/parsing/media-parsing/favorite.png" alt=""> '.$t['tab_favorites'].'</a>
    </div>

    <form id="parsing-settings-form" onsubmit="parsingSaveSettings(event)">
        <fieldset>
            <legend>'.$t['settings_general'].'</legend>
            <label>'.$t['field_cron_freq'].'
                <select name="cron_frequency_minutes">
                    <option value="15" '.($g('cron_frequency_minutes')=='15'?'selected':'').'>'.$t['opt_15min'].'</option>
                    <option value="30" '.($g('cron_frequency_minutes')=='30'?'selected':'').'>'.$t['opt_30min'].'</option>
                    <option value="60" '.($g('cron_frequency_minutes')=='60'?'selected':'').'>'.$t['opt_1h'].'</option>
                    <option value="120" '.($g('cron_frequency_minutes')=='120'?'selected':'').'>'.$t['opt_2h'].'</option>
                    <option value="360" '.($g('cron_frequency_minutes')=='360'?'selected':'').'>'.$t['opt_6h'].'</option>
                </select>
            </label>
        </fieldset>

        <button type="submit" class="btn-primary">'.$t['btn_save_settings'].'</button>
    </form>

    <fieldset class="ol-cookie-box" style="margin-top:2rem;">
        <legend>OpenLane — Cookie</legend>
        <p class="muted">'.($t['ol_cookie_hint'] ?? 'Loghează-te pe openlane.eu, deschide DevTools → Network, alege un request „search" și copiază aici antetul „cookie" (și opțional „__requestverificationtoken").').'</p>
        <div id="ol-cookie-status" class="ol-cookie-status">…</div>
        <label style="display:block;margin-top:8px;">'.($t['cookie_label'] ?? 'Cookie').':
            <textarea id="ol-cookie-input" rows="4" style="width:100%;font-family:monospace;font-size:12px;" placeholder="cf_clearance=...; ASP.NET_SessionId=...; __RequestVerificationToken=..."></textarea>
        </label>
        <label style="display:block;margin-top:8px;">'.($t['ol_rvt_label'] ?? '__requestverificationtoken (opțional)').':
            <input type="text" id="ol-rvt-input" style="width:100%;font-family:monospace;font-size:12px;" placeholder="token din header __requestverificationtoken">
        </label>
        <div style="margin-top:10px;display:flex;gap:8px;">
            <button type="button" class="btn-primary" onclick="parsingOpenlaneSaveCookie()">'.($t['btn_save'] ?? 'Salvează').'</button>
            <button type="button" class="btn-secondary" onclick="parsingOpenlaneCheckCookie()">'.($t['ol_cookie_check'] ?? 'Verifică cookie').'</button>
        </div>
    </fieldset>

    <fieldset class="ol-cookie-box" style="margin-top:2rem;">
        <legend>e-CarsTrade — Cookie</legend>
        <p class="muted">'.($t['ec_cookie_hint'] ?? 'Loghează-te pe ru.ecarstrade.com, deschide DevTools → Network, alege un request „future_api.php" și copiază aici antetul „cookie".').'</p>
        <div id="ec-cookie-status" class="ol-cookie-status">…</div>
        <label style="display:block;margin-top:8px;">'.($t['cookie_label'] ?? 'Cookie').':
            <textarea id="ec-cookie-input" rows="4" style="width:100%;font-family:monospace;font-size:12px;" placeholder="eCT/PHPSESSID=...; eCT/user=...; eCT/eCT-User-Auth=true; ..."></textarea>
        </label>
        <div style="margin-top:10px;display:flex;gap:8px;">
            <button type="button" class="btn-primary" onclick="parsingEcarstradeSaveCookie()">'.($t['btn_save'] ?? 'Salvează').'</button>
            <button type="button" class="btn-secondary" onclick="parsingEcarstradeCheckCookie()">'.($t['ol_cookie_check'] ?? 'Verifică cookie').'</button>
        </div>
    </fieldset>

    <h2 style="margin-top:2.5rem;">'.$flagEu.$flagKr.$t['commission_title'].'</h2>
    <p class="muted">'.$t['commission_hint'].'</p>

    <form class="parsing-eu-section" data-section="commission" onsubmit="parsingSaveEu(event)">
        <table class="parsing-table" id="commission-table" data-prefix="ctier">
            <thead>
                <tr>
                    <th>'.$t['eu_col_price_from'].'</th>
                    <th>'.$t['eu_col_price_to'].'</th>
                    <th>'.$t['eu_col_commission'].'</th>
                    <th style="width:50px;"></th>
                </tr>
            </thead>
            <tbody>';

foreach ($commissionTiers as $tier) {
    $id = (int)$tier['id'];
    $to = ($tier['price_to'] === null || $tier['price_to'] === '') ? '' : (int)$tier['price_to'];
    $rtrn .= '
                <tr data-row>
                    <td><input type="number" step="1" min="0" class="int-only" name="ctier_'.$id.'_price_from" value="'.(int)$tier['price_from'].'"></td>
                    <td><input type="number" step="1" min="0" class="int-only" name="ctier_'.$id.'_price_to" value="'.$to.'" placeholder="'.$t['eu_price_to_unlimited'].'"></td>
                    <td><input type="number" step="1" min="0" class="int-only" name="ctier_'.$id.'_commission" value="'.(int)$tier['commission'].'"></td>
                    <td><button type="button" class="btn-icon" onclick="parsingTierRemove(this)" title="'.$t['tier_remove'].'">🗑</button></td>
                </tr>';
}

$rtrn .= '
            </tbody>
        </table>
        <button type="button" class="btn-secondary" onclick="parsingTierAdd(\'commission-table\')" style="margin-top:0.5rem;">+ '.$t['tier_add'].'</button>
        <button type="submit" class="btn-primary" style="margin-top:0.5rem;margin-left:0.5rem;">'.$t['btn_save_eu'].'</button>
    </form>

    <h2 style="margin-top:2.5rem;">'.$flagEu.$t['eu_tiers_title'].'</h2>
    <p class="muted">'.$t['eu_tiers_hint'].'</p>

    <form class="parsing-eu-section" data-section="delivery" onsubmit="parsingSaveEu(event)">
        <table class="parsing-table" id="delivery-table" data-prefix="tier">
            <thead>
                <tr>
                    <th>'.$t['eu_col_price_from'].'</th>
                    <th>'.$t['eu_col_price_to'].'</th>
                    <th>'.$t['eu_col_delivery'].'</th>
                    <th style="width:50px;"></th>
                </tr>
            </thead>
            <tbody>';

foreach ($euTiers as $tier) {
    $id = (int)$tier['id'];
    $to = ($tier['price_to'] === null || $tier['price_to'] === '') ? '' : (int)$tier['price_to'];
    $rtrn .= '
                <tr data-row>
                    <td><input type="number" step="1" min="0" class="int-only" name="tier_'.$id.'_price_from" value="'.(int)$tier['price_from'].'"></td>
                    <td><input type="number" step="1" min="0" class="int-only" name="tier_'.$id.'_price_to" value="'.$to.'" placeholder="'.$t['eu_price_to_unlimited'].'"></td>
                    <td><input type="number" step="1" min="0" class="int-only" name="tier_'.$id.'_delivery" value="'.(int)$tier['delivery'].'"></td>
                    <td><button type="button" class="btn-icon" onclick="parsingTierRemove(this)" title="'.$t['tier_remove'].'">🗑</button></td>
                </tr>';
}

$rtrn .= '
            </tbody>
        </table>
        <button type="button" class="btn-secondary" onclick="parsingTierAdd(\'delivery-table\')" style="margin-top:0.5rem;">+ '.$t['tier_add'].'</button>
        <button type="submit" class="btn-primary" style="margin-top:0.5rem;margin-left:0.5rem;">'.$t['btn_save_eu'].'</button>
    </form>

    <h2 style="margin-top:2.5rem;">'.$flagEu.$t['eu_params_title'].'</h2>
    <p class="muted">'.$t['eu_params_hint'].'</p>

    <form class="parsing-eu-section" data-section="eu_params" onsubmit="parsingSaveEu(event)">
        <table class="parsing-table">
            <thead>
                <tr>
                    <th>'.$t['eu_col_param'].'</th>
                    <th style="width:80px;text-align:center;">'.$t['eu_col_enabled'].'</th>
                    <th style="width:180px;">'.$t['eu_col_amount'].'</th>
                </tr>
            </thead>
            <tbody>';

foreach ($euParams as $param) {
    $id = (int)$param['id'];
    $key = $param['param_key'];
    $label = $t['eu_param_'.$key] ?? ucfirst(str_replace('_', ' ', $key));
    $checked = ((int)$param['enabled'] === 1) ? ' checked' : '';
    $isPercent = (($param['value_type'] ?? 'fixed') === 'percent');
    $unit = $isPercent ? '%' : '€';
    // Fixed amounts in € are whole numbers (int-only); percents keep one decimal.
    $step = $isPercent ? '0.1' : '1';
    $cls = $isPercent ? '' : ' int-only';
    $val = $isPercent ? rtrim(rtrim(number_format((float)$param['amount_eur'], 1, '.', ''), '0'), '.') : (string)(int)round((float)$param['amount_eur']);
    $rtrn .= '
                <tr>
                    <td><strong>'.htmlspecialchars($label).'</strong></td>
                    <td style="text-align:center;"><input type="checkbox" name="param_'.$id.'_enabled" value="1"'.$checked.'></td>
                    <td style="white-space:nowrap;"><input type="number" step="'.$step.'" min="0" class="'.trim($cls).'" name="param_'.$id.'_amount" value="'.$val.'" style="width:110px;display:inline-block;"> <span class="muted">'.$unit.'</span></td>
                </tr>';
}
if (empty($euParams)) {
    $rtrn .= '<tr><td colspan="3" class="empty-state">'.$t['empty_country_config'].'</td></tr>';
}

$rtrn .= '
            </tbody>
        </table>
        <button type="submit" class="btn-primary" style="margin-top:1rem;">'.$t['btn_save_eu'].'</button>
    </form>

    <h2 style="margin-top:2.5rem;">'.$flagKr.$t['kr_params_title'].'</h2>
    <p class="muted">'.$t['kr_params_hint'].'</p>

    <form class="parsing-eu-section" data-section="kr_params" onsubmit="parsingSaveEu(event)">
        <table class="parsing-table">
            <thead>
                <tr>
                    <th>'.$t['eu_col_param'].'</th>
                    <th style="width:80px;text-align:center;">'.$t['eu_col_enabled'].'</th>
                    <th style="width:180px;">'.$t['eu_col_amount'].'</th>
                </tr>
            </thead>
            <tbody>';

foreach ($krParams as $param) {
    $id = (int)$param['id'];
    $key = $param['param_key'];
    $label = $t['kr_param_'.$key] ?? ucfirst(str_replace('_', ' ', $key));
    $checked = ((int)$param['enabled'] === 1) ? ' checked' : '';
    $isPercent = (($param['value_type'] ?? 'fixed') === 'percent');
    $amount = (float)$param['amount_eur'];
    $unit = $isPercent ? '%' : '€';
    // Fixed amounts in € are whole numbers (int-only); percents keep one decimal.
    $step = $isPercent ? '0.1' : '1';
    $cls = $isPercent ? '' : ' int-only';
    $val = $isPercent ? rtrim(rtrim(number_format($amount, 1, '.', ''), '0'), '.') : (string)(int)round($amount);
    $rtrn .= '
                <tr>
                    <td><strong>'.htmlspecialchars($label).'</strong></td>
                    <td style="text-align:center;"><input type="checkbox" name="kr_'.$id.'_enabled" value="1"'.$checked.'></td>
                    <td style="white-space:nowrap;"><input type="number" step="'.$step.'" min="0" class="'.trim($cls).'" name="kr_'.$id.'_amount" value="'.$val.'" style="width:110px;display:inline-block;"> <span class="muted">'.$unit.'</span></td>
                </tr>';
}
if (empty($krParams)) {
    $rtrn .= '<tr><td colspan="3" class="empty-state">'.$t['empty_country_config'].'</td></tr>';
}

$rtrn .= '
            </tbody>
        </table>
        <button type="submit" class="btn-primary" style="margin-top:1rem;">'.$t['btn_save_eu'].'</button>
    </form>

    <h2 style="margin-top:2.5rem;">'.$flagKr.$t['kr_markup_title'].'</h2>
    <p class="muted">'.$t['kr_markup_hint'].'</p>

    <form class="parsing-eu-section" data-section="kr_markup" onsubmit="parsingSaveEu(event)">
        <table class="parsing-table" id="kr-markup-table" data-prefix="kmtier">
            <thead>
                <tr>
                    <th>'.$t['eu_col_price_from'].'</th>
                    <th>'.$t['eu_col_price_to'].'</th>
                    <th>'.$t['kr_markup_col'].'</th>
                    <th style="width:50px;"></th>
                </tr>
            </thead>
            <tbody>';

foreach ($krMarkupTiers as $tier) {
    $id = (int)$tier['id'];
    $to = ($tier['price_to'] === null || $tier['price_to'] === '') ? '' : (int)$tier['price_to'];
    $rtrn .= '
                <tr data-row>
                    <td><input type="number" step="1" min="0" class="int-only" name="kmtier_'.$id.'_price_from" value="'.(int)$tier['price_from'].'"></td>
                    <td><input type="number" step="1" min="0" class="int-only" name="kmtier_'.$id.'_price_to" value="'.$to.'" placeholder="'.$t['eu_price_to_unlimited'].'"></td>
                    <td><input type="number" step="1" min="0" class="int-only" name="kmtier_'.$id.'_markup" value="'.(int)$tier['markup'].'"></td>
                    <td><button type="button" class="btn-icon" onclick="parsingTierRemove(this)" title="'.$t['tier_remove'].'">🗑</button></td>
                </tr>';
}

$rtrn .= '
            </tbody>
        </table>
        <button type="button" class="btn-secondary" onclick="parsingTierAdd(\'kr-markup-table\')" style="margin-top:0.5rem;">+ '.$t['tier_add'].'</button>
        <button type="submit" class="btn-primary" style="margin-top:0.5rem;margin-left:0.5rem;">'.$t['btn_save_eu'].'</button>
    </form>
</div>
<script>
    window.PARSING_LANG = '.json_encode($t, JSON_UNESCAPED_UNICODE).';
</script>
<script src="/content/admin/page/parsing/parsing.js?v='.filemtime(_ADM_PAGE.'/parsing/parsing.js').'"></script>
';

echo $rtrn;
