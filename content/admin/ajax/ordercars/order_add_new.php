<?php use App\Services\Api999Service;
use App\Helper\CarValidator;

defined( '_DOIT' ) or die( 'Restricted access' );

$rtrn = ''; $zY = substr( md5( date('Y') ), 0, 4 ); $zM = substr( md5( date('m') ), 0, 4 );

if (__post('sub') == 'mo_search') {
    $brand = __post('br');
    
    $list = (new \App\Db\Car())->getCarListByBrand($brand);
    
    $models_html = '';
	foreach ($list as $r) {
        $models_html .= '<option value="'.$r['mo'].'">'.$r['mo_nm'].'</option>';
    }
    
	$rtrn = ['bx_id' => __post('bx_id'), 'str' => $models_html];

} elseif (__post('sub') == 'end') {
    try {
        $carData = [
            'yr' => __post('yr'),
            'vol' => __post('vol'),
            'hp' => __post('hp'),
            'mlg' => __post('mlg'),
            'sts' => __post('sts'),
            'prc' => __post('prc'),
            'bt' => __post('bt'),
            'fl' => __post('fl'),
            'tra' => __post('tra'),
            'wd' => __post('wd'),
            'clr' => __post('clr'),
            'gr' => __post('gr'),
            'cur' => __post('cur')
        ];
        
        $validation = CarValidator::validate($carData);
        if (!$validation['valid']) {
            $rtrn = ['error' => true, 'validation_errors' => $validation['errors']];
            echo json_encode($rtrn);
            exit;
        }
        
        $br = __post('br');
        $mo = __post('mo');

        // Auto-create the make/model in car_list when a parsing car carries a
        // brand/model the catalog doesn't have yet, so publishing never blocks.
        // The form may send EITHER an empty br/mo (no match) OR an injected slug
        // (the front-end added a temporary option). Either way, if the br+mo pair
        // doesn't exist in car_list yet, we create it from the raw source names.
        $rawBrandNm = trim((string)__post('parsing_brand_nm'));
        $rawModelNm = trim((string)__post('parsing_model_nm'));

        // LAST LINE OF DEFENCE before this file can INSERT a new car_list row:
        // fold a source's own naming into sauto's ("5er" → "5 Series", "E-Klasse"
        // → "E Class", "Golf VII" → "Golf"). The publish queue already resolves
        // this, but a manual Edit/Publish posts these raw names straight here —
        // without the fold they'd create a duplicate model beside the real one.
        if ($rawBrandNm !== '' && $rawModelNm !== '') {
            try {
                $pub = new \App\Services\Parsing\ParsingPublisher();
                $canon = $pub->resolveCanonicalNames($rawBrandNm, $rawModelNm, false);
                if ($canon) {
                    // Matched a real car_list entry — use its codes AND names.
                    if ($br === '') $br = (string)$canon['br'];
                    if ($mo === '') $mo = (string)$canon['mo'];
                    $rawBrandNm = (string)$canon['br_nm'];
                    $rawModelNm = (string)$canon['mo_nm'];
                }
            } catch (\Throwable $e) { /* fall through with the posted names */ }
        }

        if (($rawBrandNm !== '' || $rawModelNm !== '')) {
            $slugify = function (string $s): string {
                $s = trim($s);
                if (function_exists('transliterator_transliterate')) {
                    $t = @transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $s);
                    if ($t !== false) $s = $t;
                }
                $s = mb_strtolower($s, 'UTF-8');
                $s = preg_replace('/[^a-z0-9]+/u', '_', $s);
                return trim($s, '_');
            };

            // Does the submitted br+mo already exist? If yes, nothing to create.
            $exists = false;
            if ($br !== '' && $mo !== '') {
                $chk = $db->prepare("SELECT 1 FROM {$prefx}_car_list WHERE br=:br AND mo=:mo LIMIT 1");
                $chk->execute(['br' => $br, 'mo' => $mo]);
                $exists = (bool)$chk->fetchColumn();
            }

            if (!$exists) {
                // --- Brand: reuse an existing one (by code or name) else create. ---
                if ($rawBrandNm !== '') {
                    $brSlug = $br !== '' ? $br : $slugify($rawBrandNm);
                    $bs = $db->prepare("SELECT br, br_nm FROM {$prefx}_car_list WHERE br=:slug OR LOWER(br_nm)=LOWER(:nm) LIMIT 1");
                    $bs->execute(['slug' => $brSlug, 'nm' => $rawBrandNm]);
                    $bx = $bs->fetch(PDO::FETCH_ASSOC);
                    if ($bx) { $br = $bx['br']; $rawBrandNm = $bx['br_nm']; }
                    elseif ($br === '') { $br = $brSlug !== '' ? $brSlug : 'brand'; }
                }

                // --- Model: reuse if it exists for this brand, else INSERT. ---
                if ($rawModelNm !== '' && $br !== '') {
                    $moSlug = $mo !== '' ? $mo : $slugify($rawModelNm);
                    $ms = $db->prepare("SELECT mo FROM {$prefx}_car_list WHERE br=:br AND (mo=:slug OR LOWER(mo_nm)=LOWER(:nm)) LIMIT 1");
                    $ms->execute(['br' => $br, 'slug' => $moSlug, 'nm' => $rawModelNm]);
                    $mx = $ms->fetch(PDO::FETCH_ASSOC);

                    // Last-line defence against duplicates like "C 200" when "C Class"
                    // already exists: scan ALL of the brand's models and match by a
                    // normalized key (no spaces/dashes/case). If the raw model starts
                    // with, or is contained in, an existing model's normalized name,
                    // reuse that model instead of creating a new (wrong) one.
                    if (!$mx) {
                        $normKey = function ($s) {
                            return preg_replace('/[^a-z0-9]+/', '', mb_strtolower(trim((string)$s), 'UTF-8'));
                        };
                        $rawN = $normKey($rawModelNm);
                        if ($rawN !== '') {
                            $all = $db->prepare("SELECT mo, mo_nm FROM {$prefx}_car_list WHERE br=:br");
                            $all->execute(['br' => $br]);
                            $best = null; $bestLen = 0;
                            foreach ($all as $row) {
                                $optN = $normKey($row['mo_nm']);
                                if ($optN === '' || mb_strlen($optN) < 2) continue;
                                // Prefer the LONGEST existing model that the raw name
                                // starts with (e.g. "cclass..." starts with "cclass").
                                if (strpos($rawN, $optN) === 0 && mb_strlen($optN) > $bestLen) {
                                    $best = $row; $bestLen = mb_strlen($optN);
                                }
                            }
                            if ($best) $mx = ['mo' => $best['mo']];
                        }
                    }

                    if ($mx) { $mo = $mx['mo']; }
                    else {
                        $mo = $moSlug !== '' ? $moSlug : 'model';
                        // Brand display name: existing brand's, else the raw source name.
                        $bnmStmt = $db->prepare("SELECT br_nm FROM {$prefx}_car_list WHERE br=:br LIMIT 1");
                        $bnmStmt->execute(['br' => $br]);
                        $brandDisplay = $bnmStmt->fetchColumn();
                        if ($brandDisplay === false) $brandDisplay = $rawBrandNm !== '' ? $rawBrandNm : $br;
                        $ins = $db->prepare("INSERT INTO {$prefx}_car_list (br, mo, br_nm, mo_nm) VALUES (:br, :mo, :brnm, :monm)");
                        $ins->execute(['br' => $br, 'mo' => $mo, 'brnm' => $brandDisplay, 'monm' => $rawModelNm]);
                    }
                }
            }
        }

        if ($br && $mo) {
            $stmt = $db->prepare('SELECT * FROM '.$prefx.'_car_list WHERE `br`=:br AND `mo`=:mo');
            $stmt->execute(['br' => $br, 'mo' => $mo]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        $br_nm = $result['br_nm'] ?? NULL;
        $mo_nm = $result['mo_nm'] ?? NULL;

        if (!empty(__post('id'))) {
            $pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_ctlg WHERE `id`=:id LIMIT 1');
            $pdo->execute(['id' => __post('id')]);
            $r = $pdo->fetch();

            // Get the correct brand and model names from car_list table
            $car_info_stmt = $db->prepare('SELECT `br_nm`, `mo_nm` FROM '.$prefx.'_car_list WHERE `br`=:br AND `mo`=:mo LIMIT 1');
            $car_info_stmt->execute(['br' => __post('br'), 'mo' => __post('mo')]);
            $car_info = $car_info_stmt->fetch();
            
            $new_br_nm = $car_info ? $car_info['br_nm'] : $r['br_nm'];
            $new_mo_nm = $car_info ? $car_info['mo_nm'] : $r['mo_nm'];

            // Extract price from new POST data (999 features) if available
            $extracted_price = __post('prc', 0);
            $extracted_currency = __post('cur');
            
            
            // Try to extract price from various possible POST fields
            if (!empty($_POST['feature'])) {

                foreach ($_POST['feature'] as $feature_id => $feature_value) {
                    if (!empty($feature_value) && is_numeric($feature_value)) {
                        // Check if there's a corresponding unit
                        if (!empty($_POST['feature_units'][$feature_id])) {
                            $unit = strtolower($_POST['feature_units'][$feature_id]);
                            if (in_array($unit, ['eur', 'usd', 'mdl', 'ron'])) {
                                $extracted_price = (float)$feature_value;
                                $currency_map = ['eur' => 'EUR', 'usd' => 'USD', 'mdl' => 'MDL', 'ron' => 'RON'];
                                $extracted_currency = $currency_map[$unit] ?? $extracted_currency;

                                break;
                            }
                        }
                    }
                }
            }
            

            
            // Fallback: if no price found in features, try to get from existing 999 data
            if ($extracted_price == 0 && !empty($r['999'])) {

                $car999_data = json_decode($r['999'], true);
                if (!empty($car999_data['features'])) {
                    foreach ($car999_data['features'] as $feature) {
                        if (!empty($feature['value']) && !empty($feature['unit']) && 
                            is_numeric($feature['value']) && 
                            in_array(strtolower($feature['unit']), ['eur', 'usd', 'mdl', 'ron'])) {
                            $extracted_price = (float)$feature['value'];
                            $currency_map = ['eur' => 'EUR', 'usd' => 'USD', 'mdl' => 'MDL', 'ron' => 'RON'];
                            $extracted_currency = $currency_map[strtolower($feature['unit'])] ?? $extracted_currency;

                            break;
                        }
                    }
                }
            }

            
            // Update 999 JSON data with new price
            $updated_999_data = null;
            if (!empty($r['999'])) {
                $car999_data = json_decode($r['999'], true);
                if (!empty($car999_data['features'])) {
                    // Update price in 999 features
                    foreach ($car999_data['features'] as &$feature) {
                        if (!empty($feature['unit']) && 
                            in_array(strtolower($feature['unit']), ['eur', 'usd', 'mdl', 'ron'])) {
                            $feature['value'] = (string)$extracted_price;
                            $feature['unit'] = strtolower($extracted_currency);

                            break;
                        }
                    }
                    $updated_999_data = json_encode($car999_data);

                }
            }
            
            // Convert offer_timer from DD:HH:MM:SS to timestamp
            $offer_timer_str = __post('offer_timer', '60:00:00:00');
            $original_offer_timer_end = __post('original_offer_timer_end', 0);
            
            // Calculate what the current remaining time would be if timer wasn't changed
            $current_remaining_time = '';
            if (!empty($original_offer_timer_end) && $original_offer_timer_end > time()) {
                $time_remaining = $original_offer_timer_end - time();
                $days = floor($time_remaining / 86400);
                $hours = floor(($time_remaining % 86400) / 3600);
                $minutes = floor(($time_remaining % 3600) / 60);
                $seconds = $time_remaining % 60;
                $current_remaining_time = sprintf('%d:%02d:%02d:%02d', $days, $hours, $minutes, $seconds);
            }
            
            // Check if timer was actually changed by user
            $timer_was_changed = ($current_remaining_time !== $offer_timer_str);
            
            // Only recalculate offer_timer_end if timer was changed
            if ($timer_was_changed || empty($original_offer_timer_end)) {
                $timer_parts = explode(':', $offer_timer_str);
                $timer_seconds = 0;
                if (count($timer_parts) == 4) {
                    $days = (int)$timer_parts[0];
                    $hours = (int)$timer_parts[1];
                    $minutes = (int)$timer_parts[2];
                    $seconds = (int)$timer_parts[3];
                    $timer_seconds = ($days * 86400) + ($hours * 3600) + ($minutes * 60) + $seconds;
                }
                $offer_timer_end = time() + $timer_seconds;
            } else {
                // Keep the original timer end if timer wasn't changed
                $offer_timer_end = $original_offer_timer_end;
            }
            
            // Manual "out of stock" has the highest priority: ticking n_a=1 wins even
            // over a live timer, and the timer is dropped to 0 so the car reads as out
            // of stock. The cleanup cron then sees on_order + n_a=1 and deletes it.
            $na_to_save = (int)__post('n_a', 0);
            if ($na_to_save === 1) {
                $offer_timer_end = 0; // kill the timer when marked out of stock
            }

            $pdo = $db->prepare('UPDATE '.$prefx.'_car_ctlg SET
                `gr`=:gr, `br`=:br, `mo`=:mo, `br_nm`=:br_nm, `mo_nm`=:mo_nm, `yr`=:yr,
                `bt`=:bt, `sts`=:sts, `mlg`=:mlg, `unit`=:unit, `vol`=:vol, `hp`=:hp, `fl`=:fl,
                `tra`=:tra, `wd`=:wd, `clr`=:clr, `loc`=:loc, `txt`=:txt, `vin`=:vin, `vin_check_enabled`=:vin_check_enabled,
                `prc`=:prc, `cur`=:cur, `soon`=:soon, `n_a`=:n_a, `tva`=:tva, `top`=:top,
                `gift`=:gift, `is_at_client`=:is_at_client, `import_country_id`=:import_country_id, `catalog_type`=:catalog_type,
                `delivery_time`=:delivery_time, `advance_amount`=:advance_amount, `offer_timer`=:offer_timer, `offer_timer_end`=:offer_timer_end, `prc_t`=:prc_t, `prc_n`=:prc_n, `999`=:data_999 
                WHERE `id`=:id');
            $pdo->execute([
                'id' => __post('id'),
                'gr' => __post('gr'),
                'br' => __post('br'),
                'mo' => __post('mo'),
                'br_nm' => $new_br_nm,
                'mo_nm' => $new_mo_nm,
                'yr' => __post('yr'),
                'bt' => __post('bt'),
                'sts' => __post('sts'),
                'mlg' => __post('mlg'),
                'unit' => __post('unit'),
                'vol' => __post('vol'),
                'hp' => __post('hp'),
                'fl' => __post('fl'),
                'tra' => __post('tra'),
                'wd' => __post('wd'),
                'clr' => __post('clr'),
                'loc' => __post('loc', 0),
                'txt' => ( __post('txt')==null?'':__post('txt') ),
                'vin' => __post('vin', ''),
                'vin_check_enabled' => isset($_POST['vin_check_enabled']) ? (int)$_POST['vin_check_enabled'] : 0,
                'prc' => __post('prc', 0),
                'cur' => __post('cur'),
                'soon' => __post('soon', 0),
                'n_a' => $na_to_save,
                'tva' => __post('tva', 0),
                'top' => __post('top', 0),
                'gift' => __post('gift', 0),
                'is_at_client' => __post('is_at_client', 0),
                'import_country_id' => __post('import_country_id', 0),
                'catalog_type' => 'on_order',
                'delivery_time' => __post('delivery_time', 14),
                'advance_amount' => __post('advance_amount', 0),
                'offer_timer' => $offer_timer_str,
                'offer_timer_end' => $offer_timer_end,
                'prc_t' => (strtotime(__post('prc_t'))!=''&&strtotime(__post('prc_t'))!=0?strtotime(__post('prc_t')):0),
                'prc_n' => __post('prc_n', __post('prc', 0)),
                'data_999' => $updated_999_data
            ]);

            // n_a changed → keep 999 schedules in sync (postpone when out of stock,
            // restore when back in stock). Uses the actually-saved n_a (which a renewed
            // timer may have forced to 0). Same behavior as the quick av0/av1 toggle.
            $na_old = (int)($r['n_a'] ?? 0);
            if ($na_old === 0 && $na_to_save === 1) {
                na_postpone_schedules($db, $prefx, (int)__post('id'));
            } elseif ($na_old === 1 && $na_to_save === 0) {
                na_restore_schedules($db, $prefx, (int)__post('id'));
            }

            // --- CHANGELOG: log field edits ---
            car_changelog_log_diff($db, $prefx, __post('id'), $r, [
                'gr' => __post('gr'), 'br' => __post('br'), 'mo' => __post('mo'),
                'br_nm' => $new_br_nm, 'mo_nm' => $new_mo_nm, 'yr' => __post('yr'),
                'bt' => __post('bt'), 'sts' => __post('sts'), 'mlg' => __post('mlg'),
                'unit' => __post('unit'), 'vol' => __post('vol'), 'hp' => __post('hp'),
                'fl' => __post('fl'), 'tra' => __post('tra'), 'wd' => __post('wd'),
                'clr' => __post('clr'), 'loc' => __post('loc', 0),
                'txt' => (__post('txt')==null?'':__post('txt')),
                'vin' => __post('vin', ''), 'prc' => __post('prc', 0),
                'cur' => __post('cur'), 'soon' => __post('soon', 0),
                'n_a' => __post('n_a', 0), 'tva' => __post('tva', 0),
                'top' => __post('top', 0), 'gift' => __post('gift', 0),
                'import_country_id' => __post('import_country_id', 0)
            ], 'ordercars');

            //------- DELETE SOME IMGs
            if (!empty(__post('del_img'))) {
                $x1 = ['high', 'med'];
                $x2 = ['jpg', 'webp'];
                $changelog_photos_deleted = [];
                foreach (explode(',', __post('del_img')) as $v) {
                    $pdo = $db->prepare('SELECT `name` FROM '.$prefx.'_car_pht WHERE `id`=:id AND `it_id`=:it_id LIMIT 1');
                    $pdo->execute(['id' => $v, 'it_id' => __post('id')]);
                    $p = $pdo->fetch(PDO::FETCH_ASSOC);

                    foreach ($x1 as $v1) {
                        foreach ($x2 as $v2) {
                            if (file_exists($photo_folder.'/'.$r['p_path'].'/'.$r['id'].'/'.$v1.'/'.$p['name'].'.'.$v2)) {
                                unlink ($photo_folder.'/'.$r['p_path'].'/'.$r['id'].'/'.$v1.'/'.$p['name'].'.'.$v2);
                            }
                        }
                    }

                    $pdo = $db->prepare('DELETE FROM '.$prefx.'_car_pht WHERE `id`=:id AND `it_id`=:it_id ');
                    $pdo->execute(['id' => $v, 'it_id' => __post('id')]);

                    $changelog_photos_deleted[] = $p['name'] ?? ('photo_id:'.$v);
                }
                if (!empty($changelog_photos_deleted)) {
                    car_changelog_log($db, $prefx, [
                        'car_id' => __post('id'),
                        'action' => 'photo_delete',
                        'field_name' => 'photo',
                        'old_value' => count($changelog_photos_deleted).' фото: '.implode(', ', $changelog_photos_deleted),
                        'new_value' => null,
                        'catalog_type' => 'ordercars'
                    ]);
                }
            }

            //Update Main Photo
            if (!empty(__post('main_img'))) {
                // Get current main photo ID before update
                $pdo_old_main = $db->prepare('SELECT `id` FROM '.$prefx.'_car_pht WHERE `it_id`=:it_id AND `main`="1" LIMIT 1');
                $pdo_old_main->execute(['it_id' => __post('id')]);
                $old_main_photo = $pdo_old_main->fetch(PDO::FETCH_ASSOC);
                $old_main_id = $old_main_photo ? $old_main_photo['id'] : null;

                $pdo = $db->prepare('UPDATE ' . $prefx . '_car_pht SET `main`="0" WHERE `it_id`=:it_id AND `main`="1"');
                $pdo->execute(['it_id' => __post('id')]);
                $main_img = __post('main_img');
                if (is_numeric($main_img) && (int)$main_img > 10000) {
                    $pdo = $db->prepare('UPDATE ' . $prefx . '_car_pht SET `main`="1" WHERE `id`=:id AND `it_id`=:it_id');
                    $pdo->execute(['id' => $main_img, 'it_id' => __post('id')]);
                }
                // --- CHANGELOG: log main photo change only if it actually changed ---
                if ($old_main_id != __post('main_img')) {
                    car_changelog_log($db, $prefx, [
                        'car_id' => __post('id'),
                        'action' => 'photo_main',
                        'field_name' => 'main_photo',
                        'old_value' => $old_main_id ? 'photo_id:'.$old_main_id : null,
                        'new_value' => 'photo_id:' . __post('main_img'),
                        'catalog_type' => 'ordercars'
                    ]);
                }
            }

            if (!empty($r['999_id']) && __post('n_a', 0) != $r['n_a']) {
                $status = __post('n_a', 0) == 1 ? 'private' : 'public';
                (new Api999Service($r['999_api_id']))->changeAccessPolicy($r, $status);
            }
            
            // Update price on 999.md if 999 data was updated
            if (!empty($r['999_id']) && !empty($updated_999_data)) {

                try {
                    $car999_data = json_decode($updated_999_data, true);
                    if (!empty($car999_data['features'])) {
                        $api999 = new Api999Service($r['999_api_id']);
                        $result = $api999->updateAdvert($r['999_id'], $car999_data['features']);

                    }
                } catch (Exception $e) {

                }
            }
            // $last_id = __post('id');

            $last_id = __post('id');

            // webs25
            $sqlUpdate = '
              UPDATE '.$prefx.'_seo2
              SET
                `ttl` = :ttl,
                `h1`  = :h1,
                `dsc` = :dsc,
                `kwd` = :kwd,
                `txt` = :txt,
                `params_html` = :params_html
              WHERE
                `it_id` = :it_id
                AND `tp` = :tp
                AND `p1` = :p1
                AND `lng` = :lng
              LIMIT 1
             ';

            $upd = $db->prepare($sqlUpdate);

            $tp_fixed  = 'item';
            $p1_fixed  = 'ordercars';
            $it_fixed  = $last_id; /* искомый it_id */

            $updated_total = 0;
            $missed = [];          /* сюда сложим языки, где строка не нашлась */

            foreach ($lang_arr as $i => $lng) {
                /* Собираем значения из POST, как у тебя */
                $params = [
                    'ttl'         => __post('title_'.$lng, ''),
                    'h1'          => __post('h1_'.$lng, ''),
                    'dsc'         => __post('meta_desc_'.$lng, ''),
                    'kwd'         => __post('meta_key_'.$lng, ''),
                    'txt'         => '', /* оставил как у тебя; подставь если нужно */
                    'params_html' => __post('params_html_'.$lng, '', false),
                    'it_id'       => $it_fixed,
                    'tp'          => $tp_fixed,
                    'p1'          => $p1_fixed,
                    'lng'         => $lng,
                ];


                $r = $upd->execute($params);


                /* Если строки нет — rowCount будет 0. Мы НИЧЕГО не вставляем, просто отмечаем факт. */
                if ($upd->rowCount() > 0) {
                    $updated_total += $upd->rowCount();
                } else {
                    $missed[] = $lng; /* для отчёта */
                }
            }

        } else {

            $parsingIdGuard = (int)__post('parsing_id', 0);
            if ($parsingIdGuard > 0) {
                $g = $db->prepare("SELECT pc.car_ctlg_id
                    FROM {$prefx}_parsing_cars pc
                    JOIN {$prefx}_car_ctlg cc ON cc.id = pc.car_ctlg_id
                    WHERE pc.id = ? AND pc.car_ctlg_id IS NOT NULL AND pc.car_ctlg_id > 0
                    LIMIT 1");
                $g->execute([$parsingIdGuard]);
                $existingCtlgId = (int)$g->fetchColumn();
                if ($existingCtlgId > 0) {
                    $_dupLang = $_COOKIE['lang'] ?? 'ro';
                    $_dupMsg = [
                        'ro' => 'Această mașină este deja publicată pe sauto (anunț #'.$existingCtlgId.'). Nu s-a creat un duplicat.',
                        'ru' => 'Этот автомобиль уже опубликован на sauto (объявление #'.$existingCtlgId.'). Дубликат не создан.',
                        'en' => 'This car is already published on sauto (listing #'.$existingCtlgId.'). No duplicate was created.',
                    ];
                    $rtrn = [
                        'error'       => 'already_published',
                        'car_ctlg_id' => $existingCtlgId,
                        'message'     => $_dupMsg[$_dupLang] ?? $_dupMsg['ro'],
                    ];
                    return;
                }
            }

            // Convert offer_timer from DD:HH:MM:SS to timestamp for new car
            $offer_timer_str = __post('offer_timer', '60:00:00:00');
            $timer_parts = explode(':', $offer_timer_str);
            $timer_seconds = 0;
            if (count($timer_parts) == 4) {
                $days = (int)$timer_parts[0];
                $hours = (int)$timer_parts[1];
                $minutes = (int)$timer_parts[2];
                $seconds = (int)$timer_parts[3];
                $timer_seconds = ($days * 86400) + ($hours * 3600) + ($minutes * 60) + $seconds;
            }
            $offer_timer_end = time() + $timer_seconds;

            $pdo = $db->prepare('INSERT INTO ' . $prefx . '_car_ctlg (`gr`, `br`, `mo`, `br_nm`, `mo_nm`, `yr`, `vin`, `vin_check_enabled`, `bt`, `sts`, `mlg`, `unit`, `vol`, `hp`, `fl`, `tra`, `wd`, `clr`, `loc`, `txt`, `prc`, `cur`, `soon`, `n_a`, `top`, `tva`, `gift`, `is_at_client`, `import_country_id`, `catalog_type`, `delivery_time`, `advance_amount`, `offer_timer`, `offer_timer_end`, `p_path`, `date`, `author`, `vis`, `inf`, `telegram_published`, `facebook_published`, `parsing_id`, `parsing_source`)
                VALUES (:gr, :br, :mo, :br_nm, :mo_nm, :yr, :vin, :vin_check_enabled, :bt, :sts, :mlg, :unit, :vol, :hp, :fl, :tra, :wd, :clr, :loc, :txt, :prc, :cur, :soon, :n_a, :top, :tva, :gift, :is_at_client, :import_country_id, :catalog_type, :delivery_time, :advance_amount, :offer_timer, :offer_timer_end, :p_path, :date, :author, "1", "", 0, 0, :parsing_id, :parsing_source)');

            $pdo->execute([
                'gr' => __post('gr'),
                'br' => __post('br'),
                'mo' => __post('mo'),
                'br_nm' => $br_nm,
                'mo_nm' => $mo_nm,
                'yr' => __post('yr'),
                'vin' => __post('vin', ''),
                                'vin_check_enabled' => isset($_POST['vin_check_enabled']) ? (int)$_POST['vin_check_enabled'] : 0,
                'bt' => __post('bt'),
                'sts' => __post('sts') ,
                'mlg' => __post('mlg'),
                'unit' => __post('unit'),
                'vol' => __post('vol'),
                'hp' => __post('hp'),
                'fl' => __post('fl'),
                'tra' => __post('tra'),
                'wd' => __post('wd'),
                'clr' => __post('clr'),
                'loc' => __post('loc', 0),
                'txt' => __post('txt'),
                'prc' => __post('prc', 0),
                'cur' => __post('cur'),
                'soon' => __post('soon', 0),
                'n_a' => __post('n_a', 0),
                'top' => __post('top', 0),
                'tva' => __post('tva', 0),
                'gift' => __post('gift', 0),
                'is_at_client' => __post('is_at_client', 0),
                'import_country_id' => __post('import_country_id', 0),
                'catalog_type' => 'on_order',
                'delivery_time' => __post('delivery_time', 14),
                'advance_amount' => __post('advance_amount', 0),
                'offer_timer' => $offer_timer_str,
                'offer_timer_end' => $offer_timer_end,
                'p_path' => $zY . '/' . $zM,
                'date' => time(),
                'author' => __post('author') ?: ($_SESSION['user_name'] ?? ''),
                'parsing_id'     => __post('parsing_id') ?: null,
                'parsing_source' => __post('parsing_source') ?: null,
            ]);

            $last_id = $db->lastInsertId();

            // Mark the parsing entry as published when this car came from /parsing/ctlg.
            $parsingIdFromPost = (int)__post('parsing_id', 0);
            if ($parsingIdFromPost > 0 && $last_id) {
                try {
                    $db->prepare("UPDATE {$prefx}_parsing_cars
                                  SET status = 'published',
                                      published_sauto = 1,
                                      published_at = NOW(),
                                      car_ctlg_id = ?
                                  WHERE id = ?")
                       ->execute([$last_id, $parsingIdFromPost]);
                } catch (Throwable $e) { /* non-fatal */ }
            }

            // --- Auto-import images from parsing entry ---
            // If this car came from /parsing/ctlg via ?parsing_id=X, copy its
            // images directly into the sauto car folder + register in car_pht.
            $parsingIdPost = (int)__post('parsing_id', 0);
            if ($parsingIdPost > 0 && $last_id) {
                try {
                    $pStmt = $db->prepare("SELECT source, source_id, images_local, raw_data FROM {$prefx}_parsing_cars WHERE id = ?");
                    $pStmt->execute([$parsingIdPost]);
                    $pRow = $pStmt->fetch(PDO::FETCH_ASSOC);

                    if ($pRow && !empty($pRow['images_local'])) {
                        // Prefer the visual order sent by JS (drag/delete in preview).
                        // Falls back to the stored images_local order if not sent.
                        // Read raw (not via __post) — htmlspecialchars would corrupt
                        // the JSON quotes and break json_decode.
                        $orderJson = $_POST['parsing_image_order'] ?? '';
                        $orderedUrls = $orderJson ? json_decode($orderJson, true) : null;

                        if (is_array($orderedUrls) && !empty($orderedUrls)) {
                            $urls = array_values(array_filter($orderedUrls, 'is_string'));
                        } else {
                            $imgList = json_decode($pRow['images_local'], true) ?: [];
                            // Build list of absolute URLs / local paths.
                            $urls = [];
                            foreach ($imgList as $img) {
                                if (is_string($img) && $img !== '') {
                                    $urls[] = $img;
                                } elseif (is_array($img)) {
                                    if (!empty($img['url'])) {
                                        $urls[] = $img['url'];
                                    } elseif (!empty($img['path']) && !empty($img['name'])) {
                                        $urls[] = $_SERVER['DOCUMENT_ROOT'] . '/' . trim($img['path'], '/') . '/' . $img['name'];
                                    }
                                }
                            }
                        }
                        // Photo cap per source (same as ParsingPublisher): auction
                        // sources (eCarsTrade/OpenLane/Auto1) → 10, Encar → 20.
                        $imgCap = in_array($pRow['source'] ?? '', ['ecarstrade', 'openlane', 'auto1'], true) ? 10 : 20;
                        $urls = array_slice($urls, 0, $imgCap);

                        if (!empty($urls)) {
                            $zDir = _CAR_IMG;
                            $carDir = $_SERVER['DOCUMENT_ROOT'] . '/' . $zDir . '/' . $zY . '/' . $zM . '/' . $last_id . '/img';
                            if (!is_dir($carDir)) @mkdir($carDir, 0755, true);

                            $referer = 'https://www.encar.com/';
                            if (($pRow['source'] ?? '') === 'ecarstrade') $referer = 'https://ru.ecarstrade.com/';
                            if (($pRow['source'] ?? '') === 'openlane')   $referer = 'https://www.openlane.eu/';
                            $isEncar = ($pRow['source'] ?? '') === 'encar';

                            $insPht = $db->prepare('INSERT INTO '.$prefx.'_car_pht (`it_id`, `tp`, `path`, `name`, `ff`, `main`, `pos`)
                                VALUES (:it_id, :tp, :path, :name, :ff, :main, :pos)');

                            // Fetch all remote photos in PARALLEL (curl_multi) instead
                            // of one-by-one — 30 sequential round-trips to Korea were
                            // the main cause of the slow publish. Local files are read
                            // directly. Encar URLs are downscaled to 900px (lighter,
                            // still sharp; sauto re-processes on upload anyway).
                            $fetched = [];   // index => bytes, preserves original order
                            $remote  = [];   // index => prepared URL
                            foreach ($urls as $idx => $u) {
                                if (preg_match('#^https?://#i', $u)) {
                                    if ($isEncar && stripos($u, 'encar.com') !== false) {
                                        $u = preg_replace('/\?.*$/', '', $u);
                                        // Large variant WITH the small "encar" watermark
                                        // overlay (wtmk=w_mark_04.png) — drops the big
                                        // "encar.com" mark, the trick automenu.md uses.
                                        $u .= '?impolicy=heightRate&cw=1200&rh=700&cg=Center&wtmk=https://ci.encar.com/wt_mark/w_mark_04.png';
                                    }
                                    $remote[$idx] = $u;
                                } elseif (is_file($u)) {
                                    $b = @file_get_contents($u);
                                    if ($b) $fetched[$idx] = $b;
                                }
                            }
                            if (!empty($remote)) {
                                $mh = curl_multi_init();
                                $handles = [];
                                foreach ($remote as $idx => $u) {
                                    $ch = curl_init($u);
                                    curl_setopt_array($ch, [
                                        CURLOPT_RETURNTRANSFER => true,
                                        CURLOPT_FOLLOWLOCATION => true,
                                        CURLOPT_TIMEOUT        => 25,
                                        CURLOPT_SSL_VERIFYPEER => false,
                                        CURLOPT_HTTPHEADER     => [
                                            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                                            'Accept: image/*',
                                            'Referer: ' . $referer,
                                        ],
                                    ]);
                                    curl_multi_add_handle($mh, $ch);
                                    $handles[$idx] = $ch;
                                }
                                do {
                                    $status = curl_multi_exec($mh, $running);
                                    if ($running) curl_multi_select($mh, 1.0);
                                } while ($running && $status === CURLM_OK);
                                foreach ($handles as $idx => $ch) {
                                    $b = curl_multi_getcontent($ch);
                                    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                    if ($code === 200 && $b !== false && strlen($b) >= 1000) {
                                        $fetched[$idx] = $b;
                                    }
                                    curl_multi_remove_handle($mh, $ch);
                                    curl_close($ch);
                                }
                                curl_multi_close($mh);
                            }
                            ksort($fetched);   // keep the original visual order

                            // Photoroom TEMPORARILY DISABLED — Encar photos now come
                            // clean from the CDN (small "encar" watermark). Set
                            // $usePhotoroom = true to re-enable. Block stays intact.
                            $usePhotoroom = true;
                            $photoroom = ($usePhotoroom && $isEncar) ? new \App\Services\Parsing\PhotoroomService() : null;

                            $pos = 0;
                            foreach ($fetched as $bytes) {
                                $pos++;
                                $n_nm = 'car_' . $last_id . '_' . $pos;

                                if ($pos === 1 && $photoroom && $photoroom->isEnabled()) {
                                    $clean = $photoroom->removeBackgroundToWhiteJpeg($bytes);
                                    if ($clean !== null) $bytes = $clean;
                                }

                                // Write bytes directly — no PHP re-encode so quality is preserved.
                                // Create both /high/ and /med/ folders; put the original in /high/
                                // and a copy in /med/ (site reads /high/ for the slider).
                                $baseDir = $_SERVER['DOCUMENT_ROOT'] . '/' . $zDir . '/' . $zY . '/' . $zM . '/' . $last_id;
                                $highDir = $baseDir . '/high';
                                $medDir  = $baseDir . '/med';
                                if (!is_dir($highDir)) @mkdir($highDir, 0755, true);
                                if (!is_dir($medDir))  @mkdir($medDir,  0755, true);

                                $highPath = $highDir . '/' . $n_nm . '.jpg';
                                $medPath  = $medDir  . '/' . $n_nm . '.jpg';

                                $tmpRaw = $highPath . '.raw';
                                if (@file_put_contents($tmpRaw, $bytes) === false) continue;
                                $imgInfo = @getimagesize($tmpRaw);
                                $srcW = $imgInfo[0] ?? 0;
                                $src  = ($srcW > 0) ? @imagecreatefromjpeg($tmpRaw) : false;

                                // /high/: cap at 1600px (resize only if larger).
                                if ($src !== false && $srcW > 1600) {
                                    $hi = @imagescale($src, 1600);
                                    if ($hi !== false) { imagejpeg($hi, $highPath, 88); imagedestroy($hi); }
                                    else { @copy($tmpRaw, $highPath); }
                                } else {
                                    @copy($tmpRaw, $highPath);
                                }

                                // /med/: 800px (higher quality for the small mobile slider).
                                if ($src !== false && $srcW > 800) {
                                    $md = @imagescale($src, 800);
                                    if ($md !== false) { imagejpeg($md, $medPath, 92); imagedestroy($md); }
                                    else { @copy($highPath, $medPath); }
                                } else {
                                    @copy($highPath, $medPath);
                                }

                                if ($src !== false) imagedestroy($src);
                                @unlink($tmpRaw);

                                $insPht->execute([
                                    'it_id' => $last_id,
                                    'tp'    => 'img',
                                    'path'  => $zY . '/' . $zM,
                                    'name'  => $n_nm,
                                    'ff'    => 'jpg',
                                    'main'  => $pos === 1 ? 1 : 0,
                                    'pos'   => $pos,
                                ]);
                            }
                        }
                    }
                } catch (Throwable $e) {
                    @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/logs/order_add_new_debug.log',
                        '['.date('Y-m-d H:i:s')."] PARSING IMG IMPORT FAILED: ".$e->getMessage()."\n",
                        FILE_APPEND);
                }

            }

            // --- CHANGELOG: log car creation ---
            car_changelog_log($db, $prefx, [
                'car_id' => $last_id,
                'action' => 'create',
                'field_name' => null,
                'old_value' => null,
                'new_value' => ucwords($br_nm).' '.ucwords($mo_nm).' '.__post('yr'),
                'catalog_type' => 'ordercars'
            ]);

            //________________ SEO INSERT ________________
            $pdo_v = '';
            $pdo_ar = [];
            foreach($lang_arr as $i => $v){
                $pdo_v .= ($i > 0 ? ',' : '').'(:lng_'.$i.', :tp_'.$i.', :p1_'.$i.', :p2_'.$i.', :qr_'.$i.', :it_id_'.$i.', :ttl_'.$i.', :h1_'.$i.', :dsc_'.$i.', :kwd_'.$i.', :txt_'.$i.', :params_html_'.$i.')';
                $pdo_ar += [
                    'lng_'.$i=>$v,
                    'tp_'.$i=>'item',
                    'p1_'.$i=>'ordercars',
                    'p2_'.$i=>$last_id,
                    'qr_'.$i=>'',
                    'it_id_'.$i=>$last_id,
                    'ttl_'.$i => __post('title_'.$v, ''),
                    'h1_'.$i => __post('h1_'.$v, ''),
                    'dsc_'.$i => __post('meta_desc_'.$v, ''),
                    'kwd_'.$i => __post('meta_key_'.$v, ''),
                    'txt_'.$i => '',
                    'params_html_'.$i => __post('params_html_'.$v, '', false)
                ];
            }
            $pdo = $db->prepare('INSERT INTO '.$prefx.'_seo2 (`lng`, `tp`, `p1`, `p2`, `qr`, `it_id`, `ttl`, `h1`, `dsc`, `kwd`, `txt`, `params_html`) VALUES '.$pdo_v);
            $pdo->execute($pdo_ar);
        }

        // OpenLane: bake the Condition + Equipment report into parsing_cars.report_data
        // so the public product page shows it from the DB (no live OpenLane call).
        // Runs on BOTH insert and edit (the publish flow may take either path), keyed
        // off the parsing_id in POST.
        $parsingIdPub = (int)__post('parsing_id', 0);
        if ($parsingIdPub > 0) {
            try {
                $pcS = $db->prepare("SELECT source, raw_data, report_data FROM {$prefx}_parsing_cars WHERE id = ?");
                $pcS->execute([$parsingIdPub]);
                $pcR = $pcS->fetch(PDO::FETCH_ASSOC);
                $alreadyHas = $pcR && !empty($pcR['report_data']) && strpos((string)$pcR['report_data'], 'openlane_report') !== false;
                if ($pcR && ($pcR['source'] ?? '') === 'openlane' && !$alreadyHas) {
                    $rawOl  = json_decode($pcR['raw_data'] ?? '{}', true) ?: [];
                    $itemOl = (!empty($rawOl['CarId']) ? $rawOl : ($rawOl['raw_data'] ?? $rawOl));
                    $auctionId = (string)($itemOl['AuctionId'] ?? '');
                    $olAdapter = \App\Services\Parsing\AdapterFactory::create('openlane');
                    if ($auctionId !== '' && $olAdapter && method_exists($olAdapter, 'fetchDetailRaw')) {
                        $detailOl = $olAdapter->fetchDetailRaw($auctionId);
                        if (is_array($detailOl)) {
                            require_once(_ADM_PAGE.'/parsing/parsing_openlane_report.php');
                            $reportByLang = [];
                            foreach (['ro','ru','en'] as $rl) {
                                $reportByLang[$rl] = parsing_openlane_report_html($detailOl, $rl, 0);
                            }
                            $updRep = $db->prepare("UPDATE {$prefx}_parsing_cars SET report_data = ? WHERE id = ?");
                            $updRep->execute([json_encode(['openlane_report' => $reportByLang], JSON_UNESCAPED_UNICODE), $parsingIdPub]);
                        }
                    }
                }
            } catch (Throwable $e) {
                @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/logs/order_add_new_debug.log',
                    '['.date('Y-m-d H:i:s')."] OPENLANE REPORT SAVE FAILED (pub): ".$e->getMessage()."\n", FILE_APPEND);
            }
        }

        $rtrn = [
            'id' => $_POST['bx_id'],
            'last_id' => $last_id
        ];
    } catch (PDOException $e) {
        error_log("SQL Error in add_new.php: " . $e->getMessage());
        dd("SQL Error: " . $e->getMessage());
    } catch (Exception $e) {
        error_log("General Error in add_new.php: " . $e->getMessage());
        dd("General Error: " . $e->getMessage());
    }
} elseif (__post('sub') == 'file_load'){
    $last_id = __post('last_id'); // Get the last_id from POST data
    
    if (!empty($_FILES)) {
        //**********   FILE UPLOAD
        require_once($ajax_folder . '/order_file_upload.php');
    }
    
    $rtrn = [
        'img_qu' => __post('img_qu'),
        'bx_id' => __post('bx_id'),
        'last_id' => $last_id
    ];
} elseif (__post('sub') == 'make_it'){
    //**********    MAKE IT CARD
	$last_id = __post('last_id');
	
	$pdo = $db->prepare('UPDATE '.$prefx.'_car_ctlg SET `vis`="1" WHERE `id`=:id AND `act`="1"');
    $pdo->execute(['id'=>$last_id]);
	
	//________________ MAKE ITEM BOX ________________
	$pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_ctlg WHERE `id`=:id AND `act`="1" ');
    $pdo->execute(['id'=>$last_id]);
	
	foreach ($pdo as $r){
	//$r_cnt = mysqli_num_rows($result);
		$on_img =  $r['gift']==1 ? '<span class="top">Cadou</span>' : '';
		$on_img .= $r['tva']==1  ? '<span class="tva">TVA</span>' : '';
		$on_img .= $r['soon']==1 ? '<span class="soon" '.($_COOKIE['lang']=='ru'?'style="order:99;"':'').'>'.$lng['l']['stat']['soon1'].'</span>' : '';
		$on_img .= $r['n_a']==1  ? '<span class="not_av">'.$lng['l']['stat']['n_a1'].'</span>' : '';
		$on_img .= $r['top']==1  ? '<span class="top">'.$lng['l']['stat']['top1'].'</span>' : '';
		
		$pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_pht WHERE `it_id`= :it_id AND `main`="1"');
		$pdo->execute([ 'it_id' => $r['id'] ]);
		
		foreach ($pdo as $p){ $p_nm = $p['name']; $p_ff = $p['ff']; }
		
		$stts = ($r['vis']==0?' hided':'').($r['act']==0?' deleted':'');
		
		$z_msg = ( empty($r['txt']) || !trim($r['txt']) ) ? '' : 'act';
		$z_loc = ( in_array($user_login, ['comerzan']) ) ? 2 : 1;
		$av_k = ['id'=>0, 'br'=>0, 'mo'=>0, 'br_nm'=>1, 'mo_nm'=>1, 'yr'=>0, 'bt'=>0, 'mlg'=>1, 'unit'=>0, 'vol'=>0, 'hp'=>0, 'fl'=>0, 'tra'=>0, 'wd'=>0, 'sts'=>0, 'clr'=>0, 'prc'=>1, 'cur'=>1];
		
		$rtrn .= '
		<div class="bx'.$stts.'" data-id="'.$r['id'].'">
			<div class="icon comment '.$z_msg.'" title="'.$lng['w']['comment'].'"></div>
			<textarea class="comment_txt">'.$r['txt'].'</textarea>
			<div class="icon print '.$z_msg.'" title="'.$lng['w']['print'].'"></div>
			<div class="print_bx">
				<form target="_blank" action="/print.php" method="post">
					<input class="none" type="text" name="src" value="adm" />
					<input class="none" type="text" name="qSd4b_print" value="1" />
					<select name="loc" class="sel" title="'.$lng['w']['address'].'">
						<option value="1" '.(($z_loc==1)?'selected="selected"':'').'>'.$lng['t']['x']['address'][1].'</option>
						<option value="2" '.(($z_loc==2)?'selected="selected"':'').'>'.$lng['t']['x']['address'][2].'</option>
					</select>
					<select name="drct" class="sel" title="'.$lng['w']['orientation'].'">
						<option value="v" selected="selected">'.$lng['w']['vertically'].'</option>
						<option value="h">'.$lng['w']['horizontally'].'</option>
					</select>
					<select name="theme" class="sel" title="'.$lng['w']['clr_thm'].'">
						<option value="0">'.$lng['w']['grsc'].'</option>
						<option value="1" selected="selected">'.$lng['w']['clrd'].'</option>
					</select>
					
					<div class="ttl">'.$r['br_nm'].' '.$r['mo_nm'].' <span class="zx">id: '.$r['id'].'</span></div>
					
					<div class="cnt">';				
						foreach ($r as $k2 => $v2){
							if ( isset($av_k[$k2]) ){
								$rtrn .= '<label '.(($av_k[$k2]==1)?'class="act"':'').'>'.(($av_k[$k2]==1)?'<span class="ttl">'.$lng['l']['car']['spec'][$k2].'</span>':'').'<input type="text" name="'.$k2.'" value="'.$v2.'" /></label>';
							}
						}
						$rtrn .= '
						<label class="act"><span class="ttl">'.$lng['w']['exchange'].' [Trade-in]</span><input type="text" name="exchange" value="'.($r['prc']+1000).'" /></label>
						<label class="act"><span class="ttl">'.$lng['l']['car']['spec']['cons'].'</span><input type="text" name="cons" value="" placeholder="L/100" /></label>
						<label class="act"><span class="ttl">'.$lng['l']['car']['spec']['tnk'].'</span><input type="text" name="tnk" value="" placeholder="L" /></label>
					</div>
					
					<div class="cur none">';
						$pdo = $db->prepare('SELECT * FROM '.$prefx.'_exchange');
						$pdo->execute();
						foreach($pdo as $cur){
							$rtrn .= '<input type="text" name="cur_'.$cur['name'].'" value="'.$cur['value'].'" />';
						}
					$rtrn .= '
					</div>
					
					<input class="btn" type="submit" value="'.$lng['w']['further'].'" />
				</form>
			</div>
			<div class="adm_menu">';
				if( $r['act'] == 1 ){
					$rtrn .= '
					<div class="btn" data-fn="edit" title="'.$lng['adm']['edit'].'"> <div></div> </div>
					<div class="btn fn_av" data-fn="'.($r['n_a']==0?'av0':'av1').'" title="'.($r['n_a']==0?'+':'-').'" data-alt="'.($r['n_a']==0?'-':'+').'"> <div></div> </div>
					<div class="btn fn_hr" data-fn="'.($r['vis']==0?'reveal':'hide').'" title="'.$lng['adm'][($r['vis']==0?'reveal':'hide')].'" data-alt="'.$lng['adm'][($r['vis']==0?'hide':'reveal')].'" data-fn> <div></div> </div>
					<div class="btn fn_dre" data-fn="delete" title="'.$lng['adm']['delete'].'"> <div></div> </div>';
				} elseif ( $r['act'] == 0 ){
					$rtrn .= '
					<div class="btn fn_dre" data-fn="restore" title="'.$lng['adm']['restore'].'"> <div></div> </div>
					<div class="btn fn_dre" data-fn="erase" title="'.$lng['adm']['delete'].'"> <div></div> </div>';
				}
			$rtrn .= '
			</div>
			
			<div class="base_info">
				<div class="id" title="id">'.$r['id'].'</div>
				<div class="author" title="author">'.$r['author'].'</div>
				<div class="views" title="views"> '.$r['views'].' <div class="img"></div> </div>
				<div class="date" title="'.date('H:i:s', $r['date']).'">'.date('d.m.Y', $r['date']).'</div>
			</div>
			
			<div class="img" style="background-image:url(/'._CAR_IMG.'/'.$r['p_path'].'/'.$r['id'].'/med/'.$p_nm.$img_frmt.'), url(/media/images/site/no_image.png);">';
				//if($r['top']){$rtrn .= '<div class="top-sales" title="Top Sales">'.$lng['l']['stat']['top1'].'</div>';}
				if( $r['act'] == 0 ){$rtrn .= '<div class="remove_after" timer="'.( $r['del_t']-time() ).'" ra="'.$r['del_t'].'">**, **:**:**</div>';}
				$rtrn .= '
				<a class="url" href="'.$site_url.'/'.$_COOKIE['lang'].'/ordercars/'.$r['id'].'" target="_blank" title="To the item page"><div class="ico"></div></a>
				<div class="on_img ghost">'.$on_img.'</div>
			</div>
			
			<div class="nm">
				<span class="br">'.$r['br_nm'].'</span>
				<span class="mo">'.$r['mo_nm'].'</span>
			</div>
	
			<div class="info">';
				foreach( [ 'yr'=>['x'=>0], 'vol'=>['x'=>0, 'u'=>'cm3'], 'fl'=>['x'=>1], 'tra'=>['x'=>1] ] as $k => $v ){
					$rtrn .= '
					<div class="it">
						<span class="ttl">'.(isset($lng['l']['car']['spec'][$k])?$lng['l']['car']['spec'][$k]:strtoupper($k)).': </span>
						<span class="spc"></span>
						<span class="val">'.( $v['x']==0?$r[$k]:(isset($lng['l']['car'][$k][$r[$k]])?$lng['l']['car'][$k][$r[$k]]:$r[$k]) ).( isset($v['u'])?' '.$v['u']:'' ).'</span>
					</div>';
				}
			$rtrn .= '
			</div>
	
			<div class="prc_wrap">
				<div class="prc" title="'.$lng['w']['prc'].'">'.$r['prc'].' <span>'.$lng['l']['cur'][$r['cur']].'</span></div>
			</div>
		</div>';
	}
	
	//________________ RETURN ________________
	$rtrn = [ 'bx_id'=>$_POST['bx_id'], 'bx'=>$rtrn ];
}