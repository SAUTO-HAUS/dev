<?php defined( '_DOIT' ) or die( 'Restricted access' );

// This is for ordercars - cars with catalog_type = 'on_order'
// Set default catalog_type for new cars in ordercars section
if (!isset($_POST['catalog_type']) && !isset($_GET['id'])) {
    $_POST['catalog_type'] = 'on_order';
}

// Override catalog_type for ordercars when saving
if (isset($_POST['submit']) || isset($_POST['apply'])) {
    $_POST['catalog_type'] = 'on_order';
}

$zY = substr( md5( date('Y') ), 0, 4 );
$zM = substr( md5( date('m') ), 0, 4 );
$bx_id = substr(bin2hex(random_bytes(4)), 0, 7);
$photo_folder = _CAR_IMG;

$new = true;
$new999 = true;
if (!empty(__get('id'))) {
    $car = (new \App\Db\Car())->getCarById(__get('id'));

    if (empty($car)) {
        echo ('Car not found');
        exit;
    }
    $new = false;
    $new999 = empty($car['999_id']);
}

// Resolve the real VIN for a parsing car the same way "Publish to sauto" does:
// prefer the stored vin, else pull the official VIN from the inspection report
// (report_data), else the 17-zero placeholder for Encar. Used both on prefill
// (new) and on edit, so the VIN auto-fills in both flows.
function resolve_parsing_vin(array $pcar): string {
    // Only a clean 17-char VIN counts. The stored vin should already be the full
    // detail VIN, but guard against older imports that saved a partial/masked one.
    $vin = strtoupper(preg_replace('/[^A-HJ-NPR-Z0-9]/i', '', (string)($pcar['vin'] ?? '')));
    if (strlen($vin) !== 17) $vin = '';
    // The inspection report only has a masked/partial VIN, so it's a last resort.
    if (empty($vin) && !empty($pcar['report_data'])) {
        $rd = json_decode($pcar['report_data'], true);
        $rvin = $rd['inspection']['master']['detail']['vin']
            ?? ($rd['record']['vin'] ?? '');
        $rvin = strtoupper(preg_replace('/[^A-HJ-NPR-Z0-9]/i', '', (string)$rvin));
        if (strlen($rvin) === 17) $vin = $rvin;
    }
    // No real VIN found → use the 17-zero placeholder for any parsing source
    // (Encar, OpenLane, eCarsTrade). Only kicks in when there's truly no VIN.
    if (empty($vin) && in_array($pcar['source'] ?? '', ['encar', 'openlane', 'ecarstrade'], true)) {
        $vin = '00000000000000000';
    }
    return $vin;
}

// Pre-fill form from parsing entry when ?parsing_id=XXX is supplied.
// This lets the user open a parsed car and review/save it as a sauto listing.
$parsing_prefill = null;
$parsing_id_url = $_GET['parsing_id'] ?? '';
@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/logs/parsing_prefill.log',
    '['.date('Y-m-d H:i:s').'] new='.($new ? 'true' : 'false').' parsing_id='.$parsing_id_url."\n", FILE_APPEND);
if ($new && !empty($parsing_id_url)) {
    $pid = (int)$parsing_id_url;
    try {
        $stmt = $db->prepare("SELECT * FROM {$prefx}_parsing_cars WHERE id = ?");
        $stmt->execute([$pid]);
        $pcar = $stmt->fetch(PDO::FETCH_ASSOC);
        @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/logs/parsing_prefill.log',
            '['.date('Y-m-d H:i:s').'] pcar_found='.($pcar ? 'yes' : 'no').' pid='.$pid."\n", FILE_APPEND);
        if ($pcar) {
            // Map parsing fuel codes -> sauto codes.
            // Parsing fuel code -> sauto fuel code (fl). Keep hybrid sub-types:
            //   hybrid        = full hybrid petrol      -> hbd
            //   hybrid_plugin = plug-in hybrid petrol   -> pih
            //   diesel_hybrid = plug-in hybrid diesel   -> pid
            $fuelMap = [
                'benzina' => 'gsl', 'gasoline' => 'gsl',
                'diesel' => 'dsl',
                'lpg' => 'gas',
                'hybrid' => 'hbd', 'gasoline_lpg' => 'gmn', 'gasoline_cng' => 'gmn',
                'electric' => 'elc',
                'diesel_hybrid' => 'pid',
                'hybrid_plugin' => 'pih',
            ];
            $gearMap = [
                'automat' => 'atm',
                'manual' => 'mnl',
                'semi-auto' => 'tpt',
                'cvt' => 'vrr',
            ];
            $bodyMap = [
                'sedan' => 'sdn', 'suv' => 'suv', 'hatchback' => 'hbk',
                'wagon' => 'unv', 'coupe' => 'cup', 'crossover' => 'crv',
                'minivan' => 'mnv', 'pickup' => 'pkp', 'van' => 'van',
                'convertible' => 'cbr', 'microbus' => 'mbs',
            ];
            $countryMap = [
                'encar' => 41,   // KR
                'openlane' => 11, // DE (fallback)
                'ecarstrade' => 2, // BE (fallback)
            ];
            // Map common Korean colour names (Encar) -> sauto codes.
            $colorMap = [
                '흰색'   => 'wht',  // white
                '검정'   => 'blk',  // black
                '검정색' => 'blk',
                '은색'   => 'slv',  // silver
                '회색'   => 'gra',  // gray
                '쥐색'   => 'gra',  // dark gray
                '빨간색' => 'red',
                '파란색' => 'blu',
                '갈색'   => 'brn',
                '베이지' => 'bge',
                '금색'   => 'gld',
                '노란색' => 'ylw',
                '주황색' => 'orn',
                '녹색'   => 'grn',
                '진한녹색' => 'd_grn',
                '연두색' => 'l_grn',
                '보라색' => 'prp',
                '분홍색' => 'pnk',
                '와인색' => 'vns',
                // English fallbacks already in our DB
                'white' => 'wht', 'black' => 'blk', 'silver' => 'slv',
                'gray' => 'gra', 'grey' => 'gra', 'red' => 'red',
                'blue' => 'blu', 'brown' => 'brn',
            ];

            $brandKey = strtolower(str_replace([' ', '-'], '_', (string)($pcar['brand'] ?? '')));
            // Group inferred from body type: vans/trucks/pickups -> commercial (com), rest -> personal (car).
            $bodyLower = strtolower((string)($pcar['body_type'] ?? ''));
            $commercialBodies = ['van','truck','pickup','minivan','microbus'];
            $groupCode = in_array($bodyLower, $commercialBodies, true) ? 'com' : 'car';

            // Price field = full landed cost in Moldova ("MD" price), the same
            // figure shown on the card and the public page. Encar uses the Korea
            // breakdown (sea RoRo), OpenLane / eCarsTrade the Europe breakdown
            // (road delivery). Falls back to the source price if not computable.
            $parsing_prc = $pcar['price_final_eur'] ? (int)round($pcar['price_final_eur']) : '';
            $pcarSrc = $pcar['source'] ?? '';
            if (in_array($pcarSrc, ['encar', 'openlane', 'ecarstrade'], true)) {
                include_once _ADM_PAGE.'/parsing/parsing_pricing.php';
                $bdCar = [
                    'price_eur' => (float)($pcar['price_eur'] ?? 0),
                    'fuel'      => (string)($pcar['fuel_type'] ?? ''),
                    'capacity'  => (int)($pcar['engine_volume'] ?? 0),
                    'year'      => (int)($pcar['year'] ?? 0),
                ];
                $bd = ($pcarSrc === 'encar')
                    ? parsing_md_breakdown_kr($db, $prefx, $bdCar)
                    : parsing_md_breakdown_eu($db, $prefx, $bdCar);
                if ($bd && !empty($bd['total'])) {
                    $parsing_prc = (int)round($bd['total']);
                }
            }

            // Real VIN: same resolution as "Publish to sauto" (see helper above).
            $parsing_vin = resolve_parsing_vin($pcar);

            // Import country: OpenLane gives the car's real country code
            // (CarCountryExtended, e.g. "it"/"be"/"fr"). Resolve it to the sauto
            // country id by matching against the real countries table (NOT a
            // guessed id map) so it can't point at the wrong country. Other
            // sources keep the per-source fallback.
            $importCountryId = $countryMap[$pcar['source']] ?? 39;
            if (($pcar['source'] ?? '') === 'openlane') {
                $rawData = !empty($pcar['raw_data']) ? (json_decode($pcar['raw_data'], true) ?: []) : [];
                // The OpenLane listing item (with CarCountryExtended) may sit at the
                // top level OR nested under raw_data (normalizeCarData wraps it).
                $olItem = $rawData;
                if (empty($olItem['CarCountryExtended']) && !empty($rawData['raw_data']) && is_array($rawData['raw_data'])) {
                    $olItem = $rawData['raw_data'];
                }
                $cc = strtolower(trim((string)(
                    $olItem['CarCountryExtended']
                    ?? $olItem['OriginCountryId']
                    ?? $olItem['CarEcadisCountryCountryId']
                    ?? ''
                )));
                try {
                    $matched = false;
                    if ($cc !== '') {
                        $cstmt = $db->prepare('SELECT id FROM countries WHERE LOWER(code) = ? LIMIT 1');
                        $cstmt->execute([$cc]);
                        $cid = $cstmt->fetchColumn();
                        if ($cid !== false) { $importCountryId = (int)$cid; $matched = true; }
                    }
                    // Country not in our DB (or missing) → use the generic "Europa"
                    // (code EU) option instead of defaulting to a wrong country.
                    if (!$matched) {
                        $eu = $db->query("SELECT id FROM countries WHERE code = 'EU' LIMIT 1")->fetchColumn();
                        if ($eu !== false) $importCountryId = (int)$eu;
                    }
                } catch (\Throwable $e) { /* keep fallback */ }
            }

            $parsing_prefill = [
                'parsing_id'        => $pid,
                'gr'                => $groupCode,
                'br'                => $brandKey,
                'br_nm'             => $pcar['brand'] ?? '',
                'mo'                => '',
                'mo_nm'             => $pcar['model'] ?? '',
                'yr'                => $pcar['year'] ?? '',
                'mlg'               => $pcar['km'] ?? '',
                'vol'               => $pcar['engine_volume'] ?? '',
                'hp'                => $pcar['power_hp'] ?? '',
                'fl'                => $fuelMap[$pcar['fuel_type']] ?? '',
                'tra'               => $gearMap[$pcar['gearbox']] ?? '',
                'bt'                => $bodyMap[strtolower((string)$pcar['body_type'])] ?? '',
                'vin'               => $parsing_vin,
                // Show the VIN on the public sauto page only when it's a REAL VIN
                // (not the 17-zero placeholder). Real VIN -> toggle ON; placeholder
                // -> toggle OFF (current behaviour).
                'vin_check_enabled' => (preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', (string)$parsing_vin) && $parsing_vin !== '00000000000000000') ? 1 : 0,
                'clr'               => $colorMap[trim((string)($pcar['color'] ?? ''))] ?? trim((string)($pcar['color'] ?? '')),
                'sts'               => $pcar['seats'] ?? '',
                'hp'                => $pcar['power_hp'] ?? '',
                'loc'               => '1',
                'wd'                => (function ($d) {
                    $m = ['4x4' => '44', 'fwd' => 'fr', 'rwd' => 're'];
                    return $m[$d] ?? '';
                })($pcar['drive_type'] ?? ''),
                'prc'               => $parsing_prc,
                'cur'               => 'EUR',
                'import_country_id' => $importCountryId,
                'title_ro'          => $pcar['title_ro'] ?? '',
                'description_ro'    => $pcar['description_ro'] ?? '',
                'images_local'      => $pcar['images_local'] ?? '[]',
                'source'            => $pcar['source'] ?? '',
                'source_url'        => $pcar['source_url'] ?? '',
            ];
        }
    } catch (Exception $e) {
        // ignore — form stays empty
    }
}

// On EDIT (?id=), if the car came from parsing and its VIN is still empty,
// auto-fill it from the parsing entry exactly like "Publish to sauto" does.
// This mirrors the comment auto-fill, which already works on edit.
if (!$new && empty($car['vin']) && !empty($car['parsing_id'])) {
    try {
        $stmt = $db->prepare("SELECT vin, source, report_data FROM {$prefx}_parsing_cars WHERE id = ?");
        $stmt->execute([(int)$car['parsing_id']]);
        $pcar_edit = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($pcar_edit) {
            $car['vin'] = resolve_parsing_vin($pcar_edit);
            // Placeholder VIN (17 zeros) must never show on the public page → keep
            // the VIN toggle OFF. A real VIN turns it ON.
            $car['vin_check_enabled'] = ($car['vin'] === '00000000000000000') ? 0 : 1;
        }
    } catch (Exception $e) {
        // ignore — VIN field stays empty
    }
}

$it_br = [];
$pdo = (new \App\Db\Brand())->getBrands();
foreach ($pdo as $r) {
    $it_br[ $r['br'] ] = $r['br_nm'];
}
$booster999 = [];
if (!empty($car['999_booster'])) {
    $booster999 = json_decode($car['999_booster'], true);
}

// Load only European countries for import country dropdown
$countries = (new \App\Db\Country())->getCountries(true); // true = European only

?>

<div id="content_box" class="noselect" <?php if(!$new) : ?> data-car-id="<?=$car['id']?>" <?php endif; ?> back-url="<?= '/'.$_COOKIE['lang'].'/'.$admin_dir.'/ordercars/ctlg' ?>">
	<div class="bx id_<?= $bx_id ?>" data-bx_id="<?= $bx_id ?>" style="margin-bottom: 100px;">
		<div class="top" style="display: flex">
            <?php if ($user_name == 'Developer') : ?>
			    <div class="fill_fields" style="width:fit-content; position:absolute; top:0; left:4%; color:#00f; cursor:pointer; line-height:1.5rem;">Fill</div>
            <?php endif; ?>
			<div class="title"><?= !empty($car) ? (__('cars.edit_ad') . ' #' . $car['id']) : __('cars.new_ad') ?></div>
            <a class="close" href="<?= '/'.$_COOKIE['lang'].'/'.$admin_dir.'/ordercars/ctlg' ?>">X</a>
		</div>

		<?php if (!empty($car['is_at_client']) && $car['is_at_client'] == 1) : ?>
		<div class="is-at-client-banner" style="background: #ffc107; padding: 10px 20px; margin: 10px 0; text-align: center; font-weight: bold; font-size: 1.2rem; color: #000;">
			<?= $lang_is_at_client_badge ?>
		</div>
		<?php endif; ?>
		
		<?php 
		// Display warning banner if offer has expired
		if (!empty($car['offer_timer_end']) && $car['offer_timer_end'] < time()) : 
		?>
		<div class="offer-expired-warning" style="background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); border: 2px solid #dc3545; border-radius: 8px; padding: 20px; margin: 20px 0; box-shadow: 0 4px 6px rgba(220, 53, 69, 0.2);">
			<div style="display: flex; align-items: center; gap: 15px;">
				<div style="font-size: 48px; line-height: 1;">⚠️</div>
				<div style="flex: 1;">
					<h3 style="margin: 0 0 8px 0; color: #721c24; font-size: 1.4rem; font-weight: bold;">
						<?php 
							if ($_COOKIE['lang'] == 'ro') echo '⏰ OFERTA A EXPIRAT!';
							elseif ($_COOKIE['lang'] == 'ru') echo '⏰ ПРЕДЛОЖЕНИЕ ИСТЕКЛО!';
							else echo '⏰ OFFER EXPIRED!';
						?>
					</h3>
					<p style="margin: 0; color: #721c24; font-size: 1rem; line-height: 1.5;">
						<?php 
							if ($_COOKIE['lang'] == 'ro') {
								echo 'Această ofertă a expirat la <strong>' . date('d.m.Y H:i', $car['offer_timer_end']) . '</strong>.<br>';
								echo 'Actualizați câmpul "TIMER OFERTĂ" pentru a prelungi oferta sau modificați alte detalii după necesitate.';
							} elseif ($_COOKIE['lang'] == 'ru') {
								echo 'Это предложение истекло <strong>' . date('d.m.Y H:i', $car['offer_timer_end']) . '</strong>.<br>';
								echo 'Обновите поле "ТАЙМЕР ПРЕДЛОЖЕНИЯ" для продления предложения или измените другие детали по необходимости.';
							} else {
								echo 'This offer expired on <strong>' . date('d.m.Y H:i', $car['offer_timer_end']) . '</strong>.<br>';
								echo 'Update the "OFFER TIMER" field to extend the offer or modify other details as needed.';
							}
						?>
					</p>
				</div>
			</div>
		</div>
		<?php endif; ?>
		
		<form class="img_bx" id="img_bx" enctype="multipart/form-data">
			<h3 class="ttl ghost"><?=$lng['w']['imgs']?></h3>
			<div class="order_upload_hint" style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; padding: 10px; margin-bottom: 10px; font-size: 12px; color: #856404;">
				<strong>📋 <?php
					// Display requirements in current admin language
					if($_COOKIE['lang'] == 'ro') {
						echo 'Pentru automobile la comandă sunt necesare minim 5 fotografii în format JPEG';
					} elseif($_COOKIE['lang'] == 'ru') {
						echo 'Для автомобилей под заказ требуется минимум 5 фотографий в формате JPEG';
					} else {
						echo 'For custom order cars minimum 5 JPEG photos required';
					}
				?></strong>
			</div>
			<script>
			document.addEventListener('DOMContentLoaded', function() {
				// Validation function
				function validateJpegImages() {
					// Count uploaded JPEG vs other files
					const fileInput = document.querySelector('input[name="img[]"]');
					const uploadedFiles = fileInput ? Array.from(fileInput.files) : [];
					
					const jpegFiles = uploadedFiles.filter(file => 
						file.type === 'image/jpeg' || file.type === 'image/jpg' || 
						file.name.toLowerCase().endsWith('.jpg') || file.name.toLowerCase().endsWith('.jpeg')
					);
					
					const otherFiles = uploadedFiles.filter(file => 
						!(file.type === 'image/jpeg' || file.type === 'image/jpg' || 
						  file.name.toLowerCase().endsWith('.jpg') || file.name.toLowerCase().endsWith('.jpeg'))
					);
					
					// Count existing JPEG images
					const existingImages = document.querySelectorAll('.prv.imgs.ready .it img');
					let existingJpegCount = 0;
					existingImages.forEach(img => {
						const imgSrc = img.src;
						if (imgSrc && (imgSrc.includes('.jpg') || imgSrc.includes('.jpeg'))) {
							existingJpegCount++;
						}
					});
					
					const totalJpeg = existingJpegCount + jpegFiles.length;
					const totalOther = otherFiles.length;
					
					// Check if validation passes
					const hasMinimumJpeg = totalJpeg >= 5;
					const hasOnlyJpeg = otherFiles.length === 0;
					
					if (!hasMinimumJpeg || !hasOnlyJpeg) {
						let message;
						<?php if($_COOKIE['lang'] == 'ro'): ?>
							message = `📷 Imagini JPEG: ${totalJpeg} | Alte tipuri: ${totalOther}`;
							if (!hasMinimumJpeg) {
								message += `\n\n❌ ATENȚIE: Necesare minim 5 fotografii JPEG pentru publicare!`;
							}
							if (!hasOnlyJpeg) {
								message += `\n\n⚠️ Fișiere non-JPEG detectate:\n${otherFiles.map(f => f.name).join('\n')}\n\nPentru automobile la comandă sunt acceptate doar fișiere JPEG.`;
							}
						<?php elseif($_COOKIE['lang'] == 'ru'): ?>
							message = `📷 JPEG изображения: ${totalJpeg} | Другие типы: ${totalOther}`;
							if (!hasMinimumJpeg) {
								message += `\n\n❌ ВНИМАНИЕ: Требуется минимум 5 JPEG фотографий для публикации!`;
							}
							if (!hasOnlyJpeg) {
								message += `\n\n⚠️ Обнаружены не-JPEG файлы:\n${otherFiles.map(f => f.name).join('\n')}\n\nДля автомобилей под заказ принимаются только JPEG файлы.`;
							}
						<?php else: ?>
							message = `📷 JPEG images: ${totalJpeg} | Other types: ${totalOther}`;
							if (!hasMinimumJpeg) {
								message += `\n\n❌ WARNING: Minimum 5 JPEG photos required for publishing!`;
							}
							if (!hasOnlyJpeg) {
								message += `\n\n⚠️ Non-JPEG files detected:\n${otherFiles.map(f => f.name).join('\n')}\n\nFor custom order cars only JPEG files are accepted.`;
							}
						<?php endif; ?>
						
						alert(message);
						return false; // Block form submission
					}
					
					return true; // Allow form submission
				}
				
				// Add validation to Confirm button
				const confirmButton = document.querySelector('.confirm');
				if (confirmButton) {
					confirmButton.addEventListener('click', function(e) {
						if (!validateJpegImages()) {
							e.preventDefault(); // Stop the form submission
							e.stopPropagation();
							return false;
						}
					});
				}
			});
			</script>
			<label class="dd_plc">
				<input data-gr="new" class="f" type="file" multiple="multiple" name="img[]" accept=".jpg,.jpeg,image/jpeg" tabindex="1" />
				<div class="txt drag ghost">DROP HERE (JPEG only)</div>
				<div class="txt plus ghost"></div>
				<div class="inf h ghost"><?= $lng['w']['qu'] ?>: <span class="c">0</span> | <?= $lng['w']['sz'] ?>: <span class="s">0 B</span></div>
			</label>
			<div class="prv imgs h"><div class="its"></div></div>

            <?php if (!empty($car)) : ?>
                <input type="hidden" name="id" value="<?= $car['id'] ?>" />
                <div class="prv imgs ready">
                    <div class="its bx" data-bx_id="<?= $car['id'] ?>">
                        <?php $photos = (new \App\Db\CarPhoto())->getPhotosByCarId($_GET['id']); ?>
                        <?php foreach ($photos as $i => $p) :
                            $i++; ?>
                            <?php $p_ext = !empty($p['ff']) ? '.'.$p['ff'] : '.jpg'; ?>
                            <div class="it <?= $p['id'] ?> f_img ext" data-id="<?= $p['id'] ?>" this_img="/<?= $photo_folder ?>/<?= $p['path'] ?>/<?= $p['it_id'] ?>/high/<?= $p['name'] . $p_ext ?>" data-n="<?= $i ?>" data-pos="<?= $p['pos'] ?>">
                                <div class="drag_handle" title="Mută">⠿</div>
                                <input id="main_img_<?= $p['id'] ?>" type="radio" class="use main_img ext none" name="main_img" value="<?= $p['id'] ?>" data-id="<?= $p['id'] ?>" <?= ($p['main']=='1'?'checked="checked"':'') ?> />
                                <input id="del_img_<?= $p['id'] ?>" type="checkbox" class="use del_img ext none" name="del_img[]" value="<?= $p['id'] ?>" data-id="<?= $p['id'] ?>" />
                                <div class="ico ghost"></div>
                                <img class="img" src="/<?= $photo_folder ?>/<?= $p['path'] ?>/<?= $p['it_id'] ?>/med/<?= $p['name'] . $p_ext ?>" />
                                <div class="nm"><?= $i ?></div>
                                <label for="main_img_<?= $p['id'] ?>" class="btn do_main photo_action" title="<?= $adm_lang['main_photo'] ?>"></label>
                                <label for="del_img_<?= $p['id'] ?>" class="btn delete photo_action" title="<?= $adm_lang['delete'] ?>"></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="action">
                        <div class="it download_zip" title="Download ZIP" data-it_id="<?= $car['id'] ?>">ZIP</div>
                    </div>
                </div>
            <?php endif; ?>
		</form>

        <form class="site_999_block" id="sautoForm">
            <h3 class="ttl ghost site_999_block_title"><?= __('cars.sauto_block_title') ?></h3>
            <div class="main_info row">
                <input type="hidden" name="id" value="<?= $car['id'] ?? '' ?>" />
                <input type="hidden" name="catalog_type" value="on_order" />
                <?php
                // Mark the form as a parsing-origin car when prefilling from a
                // parsing entry (?parsing_id=) OR when editing an existing car
                // (?id=) that was originally imported from parsing. This flag
                // tells the front-end to skip costly OpenAI description generation
                // for parsing cars, on create AND on every later edit.
                $form_parsing_id = $parsing_prefill['parsing_id'] ?? ($car['parsing_id'] ?? null);
                $form_parsing_source = $parsing_prefill['source'] ?? ($car['parsing_source'] ?? '');
                ?>
                <?php if (!empty($form_parsing_id)) : ?>
                <input type="hidden" name="parsing_id" value="<?= (int)$form_parsing_id ?>" />
                <input type="hidden" name="parsing_source" value="<?= htmlspecialchars($form_parsing_source) ?>" />
                <?php
                // Raw brand/model names from the source. If the brand/model select
                // can't be matched (the make/model isn't in sauto yet), the server
                // creates it in car_list from these names so publishing never blocks.
                $form_brand_nm = $parsing_prefill['br_nm'] ?? '';
                $form_model_nm = $parsing_prefill['mo_nm'] ?? '';
                ?>
                <input type="hidden" name="parsing_brand_nm" value="<?= htmlspecialchars($form_brand_nm, ENT_QUOTES) ?>" />
                <input type="hidden" name="parsing_model_nm" value="<?= htmlspecialchars($form_model_nm, ENT_QUOTES) ?>" />
                <?php endif; ?>
                <div class="checks_cont">
                    <label>
                        <input type="checkbox" name="gift" class="no_need" tabindex="1"
                                onchange="this.value = +this.checked;"
                                <?= isset($car['gift']) && $car['gift'] == 1 ? 'checked' : '' ?>
                                value="<?= $car['gift'] ?? 0 ?>">
                        Cadou
                    </label>
                    <label>
                        <input type="checkbox" name="soon" class="no_need" tabindex="1"
                                onchange="this.value = +this.checked;"
                                <?= isset($car['soon']) && $car['soon'] == 1 ? 'checked' : '' ?>
                                value="<?= $car['soon'] ?? 0 ?>">
                        <?=$lang_soon?>
                    </label>
                    <label>
                        <input type="checkbox" name="tva" class="no_need" tabindex="1"
                                onchange="this.value = +this.checked;"
                                <?= isset($car['tva']) && $car['tva'] == 1 ? 'checked' : '' ?>
                                value="<?= $car['tva'] ?? 0 ?>">
                        TVA
                    </label>
                    <label>
                        <input type="checkbox" name="top" class="no_need" tabindex="1"
                                onchange="this.value = +this.checked;"
                                <?= isset($car['top']) && $car['top'] == 1 ? 'checked' : '' ?>
                                value="<?= $car['top'] ?? 0 ?>">
                        <?=$lang_top_sales?>
                    </label>
                    <label><input type="checkbox" name="n_a" class="no_need" tabindex="1"
                                <?= isset($car['n_a']) && $car['n_a'] == 1 ? 'checked' : '' ?>
                                value="<?= $car['n_a'] ?? 0 ?>">
                        <?=$lang_not_av?>
                    </label>
                    <label>
                        <input type="checkbox" name="is_at_client" class="no_need" tabindex="1"
                                onchange="this.value = +this.checked;"
                                <?= isset($car['is_at_client']) && $car['is_at_client'] == 1 ? 'checked' : '' ?>
                                value="<?= $car['is_at_client'] ?? 0 ?>">
                        <?=$lang_is_at_client?>
                    </label>
                </div>

                <!-----GROUP----->
                <div class="form-group col-md-3">
                    <div class="form-label-sm"><?= $lng['w']['group'] ?></div>
                    <select class="group need form-control" name="gr" tabindex="1" title="<?= mb_strtoupper($lng['w']['group'], "UTF-8")?>">
                        <?php foreach ($lng['l']['car']['gr'] as $k => $v) : ?>
                            <option value="<?= $k ?>" <?= ((isset($car['gr']) && $k==$car['gr']) ? 'selected' : '') ?> >
                                <?= $v ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-----BRAND----->
                <div class="form-group col-md-3">
                    <div class="form-label-sm"><?= $lang_brand ?></div>
                    <select class="brand need form-control" name="br" tabindex="1">
                        <option value=""><?= mb_strtoupper($lang_brand, "UTF-8") ?></option>
                        <?php foreach ($it_br as $k => $v) : ?>
                            <option value="<?= $k ?>" <?= ((isset($car['br']) && $k==$car['br']) ? 'selected' : '') ?>>
                                <?= $v ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-----MODEL----->
                <?php if (!empty($car)) : ?>
                <?php $list = (new \App\Db\Car())->getCarListByBrand($car['br'])?>
                <div class="form-group col-md-3">
                    <div class="form-label-sm"><?= $lang_model ?></div>
                    <select class="model need form-control" name="mo" def_text="<?= mb_strtoupper($lang_model, "UTF-8") ?>" tabindex="2">
                        <option value=""><?= mb_strtoupper($lang_model, "UTF-8") ?></option>
                        <?php foreach ($list as $v) : ?>
                            <option value="<?= $v['mo'] ?>" <?php if ($v['mo'] == $car['mo']) : ?> selected <?php endif; ?>><?= $v['mo_nm'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else : ?>
                <div class="form-group col-md-3">
                    <div class="form-label-sm"><?= $lang_model ?></div>
                    <select class="model need form-control" name="mo" def_text="<?= mb_strtoupper($lang_model, "UTF-8") ?>" tabindex="2">
                        <option value=""><?= mb_strtoupper($lang_model, "UTF-8") ?></option>
                    </select>
                </div>
                <?php endif; ?>

                <!-----COUNTRY OF IMPORT----->
                <div class="form-group col-md-4">
                    <div class="form-label-sm"><?= __('cars.import_country') ?></div>
                    <select class="country form-control" name="import_country_id" tabindex="9" title="<?= __('cars.import_country') ?>">
                        <?php if (!$new) : ?>
                        <option value=""><?= strtoupper(__('cars.import_country')) ?></option>
                        <?php endif; ?>
                        <?php if ($new) : ?>
                            <?php
                                // Pre-select the parsed car's real import country (e.g. Korea=41
                                // for Encar) when known; otherwise default to EU (Eurozona).
                                $prefillCountryId = (int)($parsing_prefill['import_country_id'] ?? 0);
                            ?>
                            <?php foreach ($countries as $country) : ?>
                                <?php
                                    $isSel = $prefillCountryId > 0
                                        ? ($country['id'] == $prefillCountryId)
                                        : ($country['code'] == 'EU');
                                ?>
                                <option value="<?= $country['id'] ?>" <?= $isSel ? 'selected' : '' ?> data-flag="<?= $country['flag'] ?>"
                                    <?= ($country['id'] == 41) ? 'style="color: #ff0000; font-weight: bold;"' : '' ?>>
                                    <?= $country['name'] ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <?php foreach ($countries as $country) : ?>
                                <option value="<?= $country['id'] ?>" 
                                    <?= (isset($car['import_country_id']) && $country['id'] == $car['import_country_id']) ? 'selected' : '' ?>
                                    data-flag="<?= $country['flag'] ?>"
                                    <?= ($country['id'] == 41) ? 'style="color: #ff0000; font-weight: bold;"' : '' ?>>
                                    <?= $country['name'] ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <!-----YEAR----->
                <div class="form-group col-md-3">
                    <div class="form-label-sm"><?= $lang_year ?></div>
                    <input class="year need nmb form-control" name="yr" tabindex="3" type="text"
                            placeholder="<?= mb_strtoupper($lang_year, "UTF-8") ?>"
                            title="<?= mb_strtoupper($lang_year, "UTF-8") ?>"
                            value="<?= $car['yr'] ?? '' ?>">
                </div>

                <!-----BODYTYPE----->
                <div class="form-group col-md-3">
                    <div class="form-label-sm"><?= $lang_bodytype ?></div>
                    <select class="bodytype need form-control" name="bt" tabindex="4">
                        <option value=""><?= mb_strtoupper($lang_bodytype, "UTF-8") ?></option>
                        <?php foreach ($lng['l']['car']['bt'] as $k => $v) : ?>
                            <option value="<?= $k ?>" <?= ((isset($car['bt']) && $k==$car['bt']) ? 'selected' : '') ?>>
                                <?= $v ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-----SEATS----->
                <div class="form-group col-md-3">
                    <div class="form-label-sm"><?= $lng['l']['car']['spec']['sts'] ?></div>
                    <input class="seats need nmb form-control" type="text" name="sts" tabindex="12"
                           value="<?= $car['sts'] ?? '' ?>"
                           placeholder="<?= mb_strtoupper($lng['l']['car']['spec']['sts'], "UTF-8") ?>"
                           title="<?= mb_strtoupper($lng['l']['car']['spec']['sts'], "UTF-8") ?>">
                </div>

                <!-----MILEAGE----->
                <div class="form-group col-md-40">
                    <div class="form-label-sm"><?= $lang_mileage ?></div>
                    <input class="mileage need nmb form-control" name="mlg" size="11" tabindex="5"
                           value="<?= $car['mlg'] ?? '' ?>"
                           placeholder="<?= mb_strtoupper($lang_mileage, "UTF-8") ?>" type="text"
                           title="<?= mb_strtoupper($lang_mileage, "UTF-8") ?>">
                </div>

                <!-----KM_OR_MI----->
                <div class="form-group col-md-10">
                    <div class="form-label-sm">km/mi</div>
                    <select class="km_or_mi need form-control" name="unit" tabindex="6">
                        <?php foreach ($info_km_or_mi as $k => $v) : ?>
                            <option value="<?= $k ?>" <?= ($car['unit'] ?? ($k == 'km' ? 'selected' : '')) ?>>
                                <?= $v ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-----ENGINE----->
                <div class="form-group col-md-40">
                    <div class="form-label-sm"><?= $lang_engine ?></div>
                    <input class="engine need nmb form-control" name="vol" size="16" tabindex="7"
                           value="<?= $car['vol'] ?? '' ?>"
                           placeholder="<?= mb_strtoupper($lang_engine, "UTF-8") ?>" type="text"
                           title="<?= mb_strtoupper($lang_engine, "UTF-8") ?>">
                </div>

                <!-----HP----->
                <div class="form-group col-md-10">
                    <div class="form-label-sm"><?= $lang_hp ?></div>
                    <input class="hp need nmb form-control" name="hp" size="16" tabindex="7"
                           value="<?= $car['hp'] ?? '' ?>"
                           placeholder="<?= mb_strtoupper($lang_hp, "UTF-8") ?>" type="text"
                           title="<?= mb_strtoupper($lang_hp, "UTF-8") ?>">
                </div>

                <!-----FUEL----->
                <div class="form-group col-md-2">
                    <div class="form-label-sm"><?= $lang_fuel ?></div>
                    <select class="fuel need form-control" name="fl" tabindex="8">
                        <option value=""><?= mb_strtoupper($lang_fuel, "UTF-8") ?></option>
                        <?php foreach ($lng['l']['car']['fl'] as $k => $v) : ?>
                            <option value="<?= $k ?>" <?= ((isset($car['fl']) && $k==$car['fl']) ? 'selected' : '') ?>>
                                <?= $v ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-----TRANSMISSION----->
                <div class="form-group col-md-2">
                    <div class="form-label-sm"><?= $lang_transmission ?></div>
                    <select class="transmission need form-control" name="tra" tabindex="9">
                        <option value=""><?= mb_strtoupper($lang_transmission, "UTF-8") ?></option>
                        <?php foreach ($lng['l']['car']['tra'] as $k => $v) : ?>
                            <option value="<?= $k ?>" <?= ((isset($car['tra']) && $k==$car['tra']) ? 'selected' : '') ?>>
                                <?= $v ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-----WHEEL DRIVE----->
                <div class="form-group col-md-3">
                    <div class="form-label-sm"><?= $lang_wheel_drive ?></div>
                    <select class="wheel_drive need form-control" name="wd" tabindex="10">
                        <option value=""><?= mb_strtoupper($lang_wheel_drive, "UTF-8") ?></option>
                        <?php foreach ($lng['l']['car']['wd'] as $k => $v) : ?>
                            <option value="<?= $k ?>" <?= ((isset($car['wd']) && $k==$car['wd']) ? 'selected' : '') ?>>
                                <?= $v ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-----COLOR----->
                <div class="form-group col-md-3">
                    <div class="form-label-sm"><?= $lang_color ?></div>
                    <select class="color need form-control" name="clr" tabindex="11">
                        <option value=""><?= mb_strtoupper($lang_color, "UTF-8") ?></option>
                        <?php foreach ($lng['l']['car']['clr'] as $k => $v) : ?>
                            <option value="<?= $k ?>" <?= ((isset($car['clr']) && $k==$car['clr']) ? 'selected' : '') ?>>
                                <?= $v ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-----LOCATION----->
                <div class="form-group col-md-3">
                    <div class="form-label-sm"><?= $lng['w']['address'] ?></div>
                    <select class="location need form-control" name="loc" title="<?= $lng['w']['address'] ?>" tabindex="12">
                        <?php foreach ($lng['t']['x']['address'] as $k => $v) : ?>
                            <option value="<?= ($k == 0 ? '' : $k) ?>" <?= ((isset($car['loc']) && $k==$car['loc']) ? 'selected' : '') ?>>
                                <?= ($k == 0 ? $lng['w']['address'] : $v) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                
                <!-----VIN CODE + VIN CHECK TOGGLE----->
                <div class="form-group col-md-6">
                    <div class="form-label-sm">VIN</div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <input class="vin need form-control" type="text" name="vin" tabindex="15"
                               value="<?= $car['vin'] ?? '' ?>"
                               placeholder="VIN КОД (17 символов)"
                               title="VIN КОД (17 символов)"
                               minlength="17"
                               maxlength="17"
                               pattern="[A-HJ-NPR-Z0-9]{17}"
                               oninput="this.value = this.value.toUpperCase().replace(/[^A-HJ-NPR-Z0-9]/g, '')"
                               style="flex:1;max-width:50%;">
                        <label style="position:relative;display:inline-block;width:50px;height:24px;flex-shrink:0;cursor:pointer;" title="VIN Check (CarVertical)">
                            <input type="checkbox" name="vin_check_enabled" style="opacity:0;width:0;height:0;"
                                   onchange="this.value=+this.checked;this.nextElementSibling.style.backgroundColor=this.checked?'#e2001a':'#ccc';this.nextElementSibling.nextElementSibling.style.left=this.checked?'29px':'3px';"
                                   value="<?= ($car['vin_check_enabled'] ?? 0) == 1 ? '1' : '0' ?>"
                                   <?= ($car['vin_check_enabled'] ?? 0) == 1 ? 'checked' : '' ?>>
                            <span style="position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background-color:<?= ($car['vin_check_enabled'] ?? 0) == 1 ? '#e2001a' : '#ccc' ?>;transition:.4s;border-radius:24px;"></span>
                            <span style="position:absolute;height:18px;width:18px;left:<?= ($car['vin_check_enabled'] ?? 0) == 1 ? '29px' : '3px' ?>;bottom:3px;background-color:white;transition:.4s;border-radius:50%;pointer-events:none;"></span>
                        </label>
                        <span style="font-size:13px;color:#666;margin-left:5px;">
                            <?php 
                                if ($_COOKIE['lang'] == 'ro') echo 'Afișează VIN în pagină';
                                elseif ($_COOKIE['lang'] == 'ru') echo 'Показать VIN на странице';
                                else echo 'Show VIN on page';
                            ?>
                        </span>
                    </div>
                </div>

                <!-----DELIVERY TIME----->
                <div class="form-group col-md-6">
                    <div class="form-label-sm"><?php 
                            if ($_COOKIE['lang'] == 'ro') echo 'Termen de livrare';
                            elseif ($_COOKIE['lang'] == 'ru') echo 'Срок поставки';
                            else echo 'Delivery time';
                        ?></div>
                    <input class="delivery_time form-control" type="number" name="delivery_time" tabindex="16"
                           value="<?= $car['delivery_time'] ?? '20' ?>"
                           placeholder="<?= __('cars.delivery_time') ?>"
                           title="<?= __('cars.delivery_time') ?>"
                           min="1"
                           max="365">
                </div>

                <!-----PRICE--->
                <div class="form-group col-md-90">
                    <div class="form-label-sm"><?= $lang_price ?></div>
                    <input class="price need nmb form-control" type="text" name="prc" tabindex="16"
                           value="<?= $car['prc'] ?? '' ?>"
                           placeholder="<?= mb_strtoupper($lang_price, "UTF-8") ?>"
                           title="<?= mb_strtoupper($lang_price, "UTF-8") ?>">
                </div>

                <!-----CURRENCY--->
                <div class="form-group col-md-10">
                    <div class="form-label-sm"><?= $lng['w']['currency'] ?? 'Val.' ?></div>
                    <select class="currency need form-control" name="cur" tabindex="17">
                        <?php foreach ($lng['l']['cur'] as $k => $v) : ?>
                            <option value="<?= $k ?>" <?= $car['cur'] ?? ($k == 'EUR' ? 'selected' : '') ?>>
                                <?= $v ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-----ADVANCE AMOUNT----->
                <div class="form-group col-md-6">
                    <div class="form-label-sm"><?php 
                            if ($_COOKIE['lang'] == 'ro') echo 'Suma avansului (70%)';
                            elseif ($_COOKIE['lang'] == 'ru') echo 'Сумма аванса (70%)';
                            else echo 'Advance amount (70%)';
                        ?></div>
                    <input class="advance_amount need nmb form-control" type="number" name="advance_amount" tabindex="18"
                           value="<?= $car['advance_amount'] ?? '' ?>"
                           placeholder="<?php 
                               if ($_COOKIE['lang'] == 'ro') echo 'SUMA AVANSULUI';
                               elseif ($_COOKIE['lang'] == 'ru') echo 'СУММА АВАНСА';
                               else echo 'ADVANCE AMOUNT';
                           ?>"
                           title="<?php 
                               if ($_COOKIE['lang'] == 'ro') echo 'SUMA AVANSULUI (70% DIN PREȚ)';
                               elseif ($_COOKIE['lang'] == 'ru') echo 'СУММА АВАНСА (70% ОТ ЦЕНЫ)';
                               else echo 'ADVANCE AMOUNT (70% OF PRICE)';
                           ?>"
                           min="0"
                           step="0.01">
                </div>
                
                <div class="form-group col-md-6">
                    <div class="form-label-sm"><?php 
                            if ($_COOKIE['lang'] == 'ro') echo 'Timer ofertă (Zile:Ore:Min:Sec)';
                            elseif ($_COOKIE['lang'] == 'ru') echo 'Таймер предложения (Дни:Часы:Мин:Сек)';
                            else echo 'Offer timer (Days:Hrs:Min:Sec)';
                        ?></div>
                    <?php
                    // Calculate remaining time from offer_timer_end if it exists
                    $timer_display_value = '60:00:00:00';
                    if (!empty($car['offer_timer_end']) && $car['offer_timer_end'] > time()) {
                        $time_remaining = $car['offer_timer_end'] - time();
                        $days = floor($time_remaining / 86400);
                        $hours = floor(($time_remaining % 86400) / 3600);
                        $minutes = floor(($time_remaining % 3600) / 60);
                        $seconds = $time_remaining % 60;
                        $timer_display_value = sprintf('%d:%02d:%02d:%02d', $days, $hours, $minutes, $seconds);
                    } elseif (!empty($car['offer_timer'])) {
                        $timer_display_value = $car['offer_timer'];
                    }
                    ?>
                    <input class="offer_timer nmb form-control" type="text" name="offer_timer" tabindex="19"
                           value="<?= $timer_display_value ?>"
                           data-original-value="<?= $timer_display_value ?>"
                           placeholder="60:00:00:00"
                           pattern="\d{1,3}:\d{2}:\d{2}:\d{2}"
                           title="<?php 
                               if ($_COOKIE['lang'] == 'ro') echo 'Format: Zile:Ore:Minute:Secunde (ex: 60:00:00:00)';
                               elseif ($_COOKIE['lang'] == 'ru') echo 'Формат: Дни:Часы:Минуты:Секунды (пример: 60:00:00:00)';
                               else echo 'Format: Days:Hours:Minutes:Seconds (ex: 60:00:00:00)';
                           ?>">
                    <!-- Hidden field to store the original offer_timer_end -->
                    <input type="hidden" name="original_offer_timer_end" value="<?= $car['offer_timer_end'] ?? '' ?>">
                </div>
            </div>
            <div class="add_info" style="margin-top: 10px">
                <!-----SEO----->
                <div class="seo">
                    <div class="button">SEO</div>
                    <div class="content">
                        <?php foreach($lang_arr as $v) : ?>
                            <label class="<?= $v ?>" data-changed="0">
                                <div class="lang_txt"><?= mb_strtoupper($v, "UTF-8") ?></div>
                                <input class="change_checker no_need" name="seo_changed_<?= $v ?>" type="hidden" value="0">
                                <input class="title no_need" name="title_<?= $v ?>" size="11" tabindex="1" placeholder="<?= mb_strtoupper($adm_lang['title'], "UTF-8") ?>" type="text" title="<?= mb_strtoupper($adm_lang['title'], "UTF-8")?>" value="">
                                <input class="meta_desc no_need" name="meta_desc_<?= $v ?>" size="11" tabindex="1" placeholder="<?= mb_strtoupper($adm_lang['meta_desc'], "UTF-8")?>" type="text" title="<?= mb_strtoupper($adm_lang['meta_desc'], "UTF-8")?>" value="">
                                <input class="h1 no_need" name="h1_<?= $v ?>" size="11" tabindex="1" placeholder="H1" type="text" title="H1" value="">
                                <input class="h2 no_need" name="h2_<?= $v ?>" size="11" tabindex="1" placeholder="H2" type="text" title="H2" value="">
                                <input class="meta_key no_need" name="meta_key_<?= $v ?>" size="11" tabindex="1" placeholder="<?= mb_strtoupper($adm_lang['meta_key'], "UTF-8")?>" type="text" title="<?= mb_strtoupper($adm_lang['meta_key'], "UTF-8")?>" value="">
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>



                <? // webs25 ?>
                <div class="txt">
                    <div class="button"> Характеристики 
                        <button type="button" id="gemini-generate-btn" onclick="generateWithGemini(); event.stopPropagation();" data-text="<?= $adm_lang['ai_generate'] ?>" data-loading="<?= $adm_lang['ai_generating'] ?>" data-success="<?= $adm_lang['ai_success'] ?>" style="background: #4285f4; color: #fff; border: none; padding: 8px 14px; border-radius: 4px; cursor: pointer; font-size: 13px; margin-left: 15px;">
                            🤖 <?= $adm_lang['ai_generate'] ?>
                        </button>
                    </div>
                    <div class="content">

                        <?
                        $ICON_SVG_PARAMS = [
                            /* Year of manufacture (calendar) */
                            'year' => '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <rect x="3" y="4" width="18" height="16" rx="2"></rect>
  <path d="M8 2v4M16 2v4M3 10h18"></path>
</svg>',

                            /* Mileage (speedometer/odometer) */
                            'mileage' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <path d="M20 13a8 8 0 10-16 0"></path>
  <path d="M12 13l3-4"></path>
  <rect x="6" y="14.5" width="12" height="3" rx="1"></rect>
</svg>
SVG
                            ,

                            /* Engine volume (motor) */
                            'engine' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <rect x="3" y="8" width="13" height="8" rx="2"></rect>
  <path d="M16 10h2l3 3v3h-3"></path>
  <path d="M7 6v2M11 6v2M7 16v2M11 16v2"></path>
</svg>
SVG
                            ,

                            /* Transmission (bidirectional arrows) */
                            'transmission' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <path d="M7 4v8a3 3 0 003 3h4"></path>
  <path d="M14 4l3 3-3 3"></path>
  <path d="M10 20l-3-3 3-3"></path>
  <path d="M14 15h3v5h-3"></path>
</svg>
SVG
                            ,

                            /* Fuel type (pump) */
                            'fuel' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <rect x="3" y="3" width="10" height="18" rx="2"></rect>
  <path d="M13 7H3"></path>
  <path d="M16 7l3 3v7a2 2 0 01-2 2h-1"></path>
  <path d="M18 13c0-1.5-1-2-2-2"></path>
</svg>
SVG
                            ,

                            /* Climate control (thermometer/snowflake) */
                            'climate' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <path d="M12 2v8"></path>
  <path d="M9 6h6"></path>
  <circle cx="12" cy="15" r="4"></circle>
  <path d="M12 11v8"></path>
</svg>
SVG
                            ,

                            /* Cruise control (compass) */
                            'cruise' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <circle cx="12" cy="12" r="9"></circle>
  <path d="M15 9l-3 6-3-1.5L15 9z"></path>
</svg>
SVG
                            ,

                            /* Parking sensors (letter P + arcs) */
                            'parking' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <path d="M6 20V4h6a4 4 0 010 8H6"></path>
  <path d="M17 8.5c1.5 1.2 1.5 3.8 0 5"></path>
  <path d="M19.5 7c2.4 2.2 2.4 6.8 0 9"></path>
</svg>
SVG
                            ,

                            /* Navigation (map pin/arrow) */
                            'navigation' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <circle cx="12" cy="10" r="3.5"></circle>
  <path d="M12 21c4-3.8 6-6.8 6-9a6 6 0 10-12 0c0 2.2 2 5.2 6 9z"></path>
</svg>
SVG
                            ,

                            /* Heated seats (seat + waves) */
                            'heated_seat' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <path d="M6 12v3a3 3 0 003 3h7"></path>
  <path d="M8 12V8a2 2 0 012-2h1a2 2 0 012 2v4"></path>
  <path d="M5 7c1 1 1 2 0 3M9 7c1 1 1 2 0 3M13 7c1 1 1 2 0 3"></path>
</svg>
SVG
                            ,

                            /* Bluetooth */
                            'bluetooth' => '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <path d="M7 7l10 10-5 5V2l5 5L7 17"></path>
</svg>',

                            /* USB (trident) */
                            'usb' => '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <path d="M12 3v12"></path>
  <path d="M9 6l3-3 3 3"></path>
  <circle cx="12" cy="18" r="3"></circle>
  <path d="M6 12h3M15 12h3"></path>
</svg>'
                        ];
                        ?>


                        <?php // webs25 ?>
                        <script>
                            /* ============================
                       Validation constants
                       ============================ */
                            const FORBIDDEN_TAGS = ['script','iframe','embed','object'];  /* Scripts and frames insertion forbidden + common dangerous */
                            const IMG_EXT_HOST_RE = /^https?:\/\//i;                      /* External images */
                            const ABS_URL_RE = /^https?:\/\//i;                           /* External links check */
                            const JS_URL_RE  = /^\s*javascript\s*:/i;                     /* javascript: in href/src forbidden */
                            const UNSAFE_STYLE_RE = /(expression\s*\(|url\s*\(\s*javascript\s*:)/i; /* dangerous inline styles */

                            /* ============================
                               sanitizeAndValidateHtml(inputHtml)
                               - cleans HTML (removes forbidden)
                               - collects messages for each case
                               - returns { cleanHtml, messages }
                               ============================ */
                            function sanitizeAndValidateHtml(inputHtml) {
                                const messages = [];            /* Store messages for each found case */
                                const externalLinks = [];       /* Collect external links for report */

                                /* Parse as HTML fragment in <template> */
                                const tpl = document.createElement('template');
                                tpl.innerHTML = inputHtml;

                                /* Walk through all elements */
                                const walker = document.createTreeWalker(tpl.content, NodeFilter.SHOW_ELEMENT);

                                const toRemove = []; /* elements to remove after walk */

                                while (walker.nextNode()) {
                                    const el = walker.currentNode;
                                    const tag = el.tagName ? el.tagName.toLowerCase() : '';

                                    /* 1) Forbidden tags: script/iframe/embed/object */
                                    if (FORBIDDEN_TAGS.indexOf(tag) !== -1) {
                                        messages.push({
                                            type: 'error',
                                            rule: 'forbiddenTag',
                                            text: `Forbidden tag <${tag}>`
                                        });
                                        toRemove.push(el);
                                        continue; /* to next element */
                                    }

                                    /* 2) Remove inline on* handlers (inline scripts) */
                                    [...el.attributes].forEach(attr => {
                                        const name = attr.name.toLowerCase();
                                        const value = attr.value || '';

                                        /* on* attributes (onclick, onload, ...) */
                                        if (name.startsWith('on')) {
                                            messages.push({
                                                type: 'error',
                                                rule: 'inlineHandler',
                                                text: ` inline handler "${name}" on tag <${tag}>.`
                                            });
                                            el.removeAttribute(attr.name);
                                            return;
                                        }

                                        /* javascript: in href/src */
                                        if ((name === 'href' || name === 'src') && JS_URL_RE.test(value)) {
                                            messages.push({
                                                type: 'error',
                                                rule: 'javascriptUrl',
                                                text: `Attribute ${name} with "javascript:" on tag <${tag}>.`
                                            });
                                            el.removeAttribute(attr.name);
                                            return;
                                        }

                                        /* dangerous inline styles (expression(), url(javascript:)) */
                                        if (name === 'style' && UNSAFE_STYLE_RE.test(value)) {
                                            messages.push({
                                                type: 'error',
                                                rule: 'unsafeStyle',
                                                text: `Dangerous inline-style on tag <${tag}>.`
                                            });
                                            el.removeAttribute('style');
                                            return;
                                        }
                                    });

                                    /* 3) External images check */
                                    if (tag === 'img') {
                                        const src = (el.getAttribute('src') || '').trim();

                                        /* external image: http/https → forbidden */
                                        if (IMG_EXT_HOST_RE.test(src)) {
                                            messages.push({
                                                type: 'error',
                                                rule: 'externalImage',
                                                text: `External image forbidden and removed: ${src}`
                                            });
                                            toRemove.push(el);
                                            continue;
                                        }
                                        /* data:, relative paths — keep */
                                    }

                                    /* 4) External links check (not forbidden, but logged) */
                                    if (tag === 'a') {
                                        const href = (el.getAttribute('href') || '').trim();

                                        if (JS_URL_RE.test(href)) {
                                            /* javascript: in link already removed above, but just in case — message */
                                            messages.push({
                                                type: 'error',
                                                rule: 'javascriptUrl',
                                                text: `Attribute href with "javascript:" on tag <a>.`
                                            });
                                            el.removeAttribute('href');
                                        } else if (ABS_URL_RE.test(href)) {
                                            /* absolute external link — just report */
                                            externalLinks.push(href);
                                        }
                                    }
                                }

                                /* Remove accumulated elements */
                                toRemove.forEach(node => node.remove());

                                /* If there are external links — output info for each */
                                externalLinks.forEach(url => {
                                    messages.push({
                                        type: 'info',
                                        rule: 'externalLink',
                                        text: `External link found: ${url}`
                                    });
                                });

                                /* Ready cleaned HTML */
                                const cleanHtml = tpl.innerHTML;

                                return { cleanHtml, messages };
                            }

                            /* ============================
                               attachTextareaValidation(textareaEl, messagesEl, onCleaned)
                               - attaches validation on input
                               - outputs messages to messagesEl
                               - callback onCleaned(cleanHtml) receives cleaned HTML
                               ============================ */
                            function attachTextareaValidation(textareaEl, messagesEl, onCleaned) {
                                function renderMessages(msgs) {
                                    /* Clear container and render message list as text strings */
                                    messagesEl.innerHTML = '';
                                    msgs.forEach(m => {
                                        const p = document.createElement('div');
                                        p.textContent = (m.type.toUpperCase()) + ': ' + m.text;
                                        /* can style by types: error/info */
                                        p.setAttribute('data-type', m.type);
                                        messagesEl.appendChild(p);
                                    });
                                }

                                function handle() {
                                    const { cleanHtml, messages } = sanitizeAndValidateHtml(textareaEl.value);
                                    renderMessages(messages);
                                    if (typeof onCleaned === 'function') {
                                        onCleaned(cleanHtml);
                                    }
                                }

                                /* first run + react to input */
                                handle();
                                textareaEl.addEventListener('input', handle);
                            }

                        </script>


                        <div class="iconsblklist" style="display: flex
;
    gap: 15px;
    flex-direction: row;
    flex-wrap: wrap;
    align-content: center;
    align-items: center;">
                            <?
                            foreach ($ICON_SVG_PARAMS AS $code => $svg ) {

                                ?>
                                <div class="iconsvg">
                                    <div class="iconsvgvg">
                                        <?=$svg?>
                                    </div>
                                    <div class="codesvg">
                                        <b>#<?=$code?></b>
                                    </div>
                                </div>
                                <?

                            }
                            ?>
                        </div>
                        <?php foreach($lang_arr as $v) : ?>

                            <?

                            $html = '';
                            if(@$car) {
                                $pdo = $db->prepare('SELECT * FROM ' . $prefx . '_seo2 WHERE `it_id`=:it_id AND lng = :lng AND tp = "item" AND p1 = "ordercars" LIMIT 1');
                                $pdo->execute(['it_id' => $car['id'], 'lng' => $v]);
                                $rseo = $pdo->fetch();
                                // var_dump( $rseo);

                                $html = $rseo['params_html'] ?? '';

                            }

                            /* remove entity escaping */
                            $html = htmlspecialchars_decode($html, ENT_QUOTES);

                            /* normalize line breaks */
                            $normalized = str_replace(["\r\n", "\r"], "\n", $html);

                            // $textareaSafe = htmlspecialchars($normalized, ENT_NOQUOTES, 'UTF-8');

                            // var_dump( $rseo);

                            /* For output INSIDE textarea must escape HTML entities */
                            $textareaSafe = htmlspecialchars($normalized, ENT_QUOTES, 'UTF-8');

                            /* For output in regular <div> with visible line breaks — use nl2br */
                            //  $divSafe = nl2br(htmlspecialchars($normalized, ENT_QUOTES, 'UTF-8'));

                            ?>
                            <div class="lang_txt"><?= mb_strtoupper($v, "UTF-8") ?></div>
                            <label class="comment" data-changed="0">
                                <textarea class="comment no_need" name="params_html_<?= $v ?>" id="params_html_<?= $v ?>" size="11" tabindex="1" wrap="soft" placeholder="Характеристики"><?php echo $textareaSafe; ?></textarea>



                                <!-- Where to output messages -->
                                <div style="color: red" id="html_checks_<?= $v ?>"></div>

                                <!-- (optional) preview of cleaned HTML -->
                                <div id="clean_preview_<?= $v ?>"></div>

                                <script>
                                    /* Attach validator to textarea */
                                    var ta = document.getElementById('params_html_<?= $v ?>');
                                    var out = document.getElementById('html_checks_<?= $v ?>');
                                    var preview = document.getElementById('clean_preview_<?= $v ?>');

                                    /* Callback: show cleaned HTML in preview (as innerHTML) */
                                    attachTextareaValidation(ta, out);
                                    /*
                                    attachTextareaValidation(ta, out, function(cleanHtml) {
                                        preview.innerHTML = cleanHtml;
                                    }); */
                                </script>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>



                <!-----COMMENT--->
                <div class="txt">
                    <div class="button"><?= $lng['w']['comment'] ?></div>
                    <div class="content">
                        <label class="comment" data-changed="0">
                            <textarea class="comment no_need" name="txt" size="11" tabindex="1" placeholder="<?= $lng['w']['comment'] ?>" title="<?=$lng['w']['comment'] ?>"><?= $car['txt'] ?? '' ?></textarea>
                        </label>
                    </div>
                </div>
            </div>

            <div class="price_info">

            </div>

            <!-----CONFIRM--->
            <button class="confirm" data-fn="add_new" data-processing="<?= __('cars.processing') ?>..." data-origin="<?= (!empty($car['bt'])) ? mb_strtoupper(__('cars.edit_publish_sauto'), "UTF-8") : mb_strtoupper(__('cars.confirm_publish_sauto'), "UTF-8") ?>"><?= (!empty($car['bt'])) ? mb_strtoupper(__('cars.edit_publish_sauto'), "UTF-8") : mb_strtoupper(__('cars.confirm_publish_sauto'), "UTF-8") ?></button>
        </form>

        <div class="btns_fb_tg">

            <a class="fb-share adm_tg_btn"
                <?/* href="https://www.facebook.com/sharer/sharer.php?u=<?= $site_url . '/ro/cars/' . $car['id'] ?>"
                target="_blank" rel="noopener noreferrer" */?>
            >
                <div style="display: flex; align-items: center; justify-content: center; gap: 15px;" onclick=" sendToFacebookCars() ">
                    <div style="display: flex; align-items: center;">
                        Опубликовать в facebook
                        <span class="icon_tg">
                                <svg style="top: 7px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                    <circle cx="256" cy="256" r="256" fill="#1877F2"/>
                                    <path fill="#ffffff" d="M504 256C504 119 393 8 256 8S8 119 8 256c0 123.5 90.9 225.8 209 245v-173h-63v-72h63v-55
                                    c0-62.3 37-96.5 93.7-96.5 27.1 0 55.5 4.8 55.5 4.8v61h-31.2
                                    c-30.8 0-40.4 19.1-40.4 38.7v46.1h68.8l-11 72h-57.8v173
                                    c118.1-19.2 209-121.5 209-245z"/>
                                </svg>
                            </span>
                    </div>
                    <?php
                    // Get Facebook schedule status + the actual scheduled time (if any).
                    require_once __DIR__ . '/../../../../App/Helper/RandomTimeHelper.php';
                    $facebookStatus = '';
                    $facebookStatusIcon = '';
                    $facebookStatusText = '';
                    $facebookStatusColor = '';
                    $facebookScheduledTime = ''; // real time of an existing schedule
                    if (!empty($car['id'])) {
                        try {
                            $catalogType = 'on_order';
                            $stmt = $db->prepare("SELECT status, scheduled_time FROM {$prefx}_scheduled_facebook_posts WHERE car_id = ? AND catalog_type = ? ORDER BY created_at DESC LIMIT 1");
                            $stmt->execute([$car['id'], $catalogType]);
                            $facebookSchedule = $stmt->fetch();
                            if ($facebookSchedule) {
                                $facebookStatus = $facebookSchedule['status'];
                                // Show the time it was actually scheduled for (HH:MM), not a new random.
                                if (!empty($facebookSchedule['scheduled_time'])) {
                                    $facebookScheduledTime = substr((string)$facebookSchedule['scheduled_time'], 0, 5);
                                }
                                switch ($facebookStatus) {
                                    case 'pending':
                                        $facebookStatusIcon = '⏳';
                                        $facebookStatusText = __('cars.status_pending');
                                        $facebookStatusColor = '#ffc107';
                                        break;
                                    case 'published':
                                        $facebookStatusIcon = '✅';
                                        $facebookStatusText = __('cars.status_published');
                                        $facebookStatusColor = '#28a745';
                                        break;
                                    case 'failed':
                                        $facebookStatusIcon = '❌';
                                        $facebookStatusText = __('cars.status_failed');
                                        $facebookStatusColor = '#dc3545';
                                        break;
                                    case 'cancelled':
                                        $facebookStatusIcon = '🚫';
                                        $facebookStatusText = __('cars.status_cancelled');
                                        $facebookStatusColor = '#6c757d';
                                        break;
                                }
                            }
                        } catch (Exception $e) {
                            // Ignore error
                        }
                    }
                    // If already scheduled, show that real time; otherwise pick a random one.
                    $facebook_schedule_time = $facebookScheduledTime !== ''
                        ? $facebookScheduledTime
                        : \App\Helper\RandomTimeHelper::generateRandomFacebookTime();
                    ?>
                    <input type="time" id="facebook_schedule_time" value="<?= $facebook_schedule_time ?>" style="padding: 5px; border: 1px solid #ccc; border-radius: 4px;" onclick="event.stopPropagation();">
                    <?php if ($facebookStatus): ?>
                        <div style="display: flex; align-items: center; gap: 4px;">
                            <span style="font-size: 14px;"><?= $facebookStatusIcon ?></span>
                            <span style="font-size: 10px; color: <?= $facebookStatusColor ?>; font-weight: 500;"><?= $facebookStatusText ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                    <?/*
                            <svg aria-hidden="true" width="16" height="16"><!-- иконка FB --></svg>
                            Опубликовать в facebook


                        <script>
                            // При желании открываем компактное окно
                            document.querySelector('.fb-share').addEventListener('click', e => {
                                e.preventDefault();
                                window.open(
                                    e.currentTarget.href,
                                    'fbshare',
                                    'width=600,height=500,toolbar=0,location=0');
                            });
                        </script> */?>
            </a>

            <div class="adm_tg_btn" style="display: flex; align-items: center; justify-content: center; gap: 10px;" onclick=" sendToTelegramCars() ">
                <div style="display: flex; align-items: center;">
                    Опубликовать в telegram
                    <span class="icon_tg">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 496 512">
                          <!-- Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com -->
                          <path d="M248,8C111.033,8,0,119.033,0,256S111.033,504,248,504,496,392.967,496,256,384.967,8,248,8ZM362.952,176.66
                            c-3.732,39.215-19.881,134.378-28.1,178.3-3.476,18.584-10.322,24.816-16.948,25.425-14.4,1.326-25.338-9.517-39.287-18.661
                            -21.827-14.308-34.158-23.215-55.346-37.177-24.485-16.135-8.612-25,5.342-39.5,3.652-3.793,67.107-61.51,68.335-66.746
                            .153-.655.3-3.1-1.154-4.384s-3.59-.849-5.135-.5q-3.283.746-104.608,69.142-14.845,10.194-26.894,9.934
                            c-8.855-.191-25.888-5.006-38.551-9.123-15.531-5.048-27.875-7.717-26.8-16.291q.84-6.7,18.45-13.7
                            108.446-47.248,144.628-62.3c68.872-28.647,83.183-33.623,92.511-33.789,2.052-.034,6.639.474,9.61,2.885
                            a10.452,10.452,0,0,1,3.53,6.716A43.765,43.765,0,0,1,362.952,176.66Z"/>
                        </svg>
                    </span>
                </div>
                <?php
                // Get Telegram schedule status + the actual scheduled time (if any).
                require_once __DIR__ . '/../../../../App/Helper/RandomTimeHelper.php';
                $telegramStatus = '';
                $telegramStatusIcon = '';
                $telegramStatusText = '';
                $telegramStatusColor = '';
                $telegramScheduledTime = ''; // real time of an existing schedule
                if (!empty($car['id'])) {
                    try {
                        $catalogType = 'on_order';
                        $stmt = $db->prepare("SELECT status, scheduled_time FROM {$prefx}_scheduled_telegram_posts WHERE car_id = ? AND catalog_type = ? ORDER BY created_at DESC LIMIT 1");
                        $stmt->execute([$car['id'], $catalogType]);
                        $telegramSchedule = $stmt->fetch();
                        if ($telegramSchedule) {
                            $telegramStatus = $telegramSchedule['status'];
                            // Show the time it was actually scheduled for (HH:MM), not a new random.
                            if (!empty($telegramSchedule['scheduled_time'])) {
                                $telegramScheduledTime = substr((string)$telegramSchedule['scheduled_time'], 0, 5);
                            }
                            switch ($telegramStatus) {
                                case 'pending':
                                    $telegramStatusIcon = '⏳';
                                    $telegramStatusText = __('cars.status_pending');
                                    $telegramStatusColor = '#ffc107';
                                    break;
                                case 'published':
                                    $telegramStatusIcon = '✅';
                                    $telegramStatusText = __('cars.status_published');
                                    $telegramStatusColor = '#28a745';
                                    break;
                                case 'failed':
                                    $telegramStatusIcon = '❌';
                                    $telegramStatusText = __('cars.status_failed');
                                    $telegramStatusColor = '#dc3545';
                                    break;
                                case 'cancelled':
                                    $telegramStatusIcon = '🚫';
                                    $telegramStatusText = __('cars.status_cancelled');
                                    $telegramStatusColor = '#6c757d';
                                    break;
                            }
                        }
                    } catch (Exception $e) {
                        // Ignore error
                    }
                }
                // If already scheduled, show that real time; otherwise pick a random one.
                $telegram_schedule_time = $telegramScheduledTime !== ''
                    ? $telegramScheduledTime
                    : \App\Helper\RandomTimeHelper::generateRandomTelegramTime();
                ?>
                <input type="time" id="telegram_schedule_time" value="<?= $telegram_schedule_time ?>" style="padding: 5px; border: 1px solid #ccc; border-radius: 4px;" onclick="event.stopPropagation();">
                <?php if ($telegramStatus): ?>
                    <div style="display: flex; align-items: center; gap: 4px;">
                        <span style="font-size: 14px;"><?= $telegramStatusIcon ?></span>
                        <span style="font-size: 10px; color: <?= $telegramStatusColor ?>; font-weight: 500;"><?= $telegramStatusText ?></span>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <div class="site_999_block" style="padding-bottom: 40px;">
            <h3 class="ttl site_999_block_title">
                <?= __('cars.999_block_title') ?>
                <?php if (!empty($car['999_id'])) : ?> (<a href="https://999.md/ru/<?=$car['999_id']?>" target="_blank" style="text-decoration: revert; font-size: 14px;">https://999.md/ru/<?=$car['999_id']?></a>)<?php endif; ?>
                <?php if (!empty($car['999_id'])) : ?>
                    <?php if (!empty($car['999_booster']) && $booster999['end_date'] >= time() && $booster999['status'] === 'in_progress') : ?>
                        <img src="/media/images/site/icon-chart-histogram-green.svg" class="booster" title="<?php if ($booster999['status'] === 'in_progress') : ?>Booster in progress<?php endif; ?>" alt="<?php if ($booster999['status'] === 'in_progress') : ?>Booster in progress<?php endif; ?>"/>
                    <?php else : ?>
                        <img src="/media/images/site/icon-chart-histogram-red.svg" class="booster" title="Booster stopped" alt="Booster stopped"/>
                    <?php endif; ?>
                <?php endif; ?>
            </h3>
            <?php include('order_order_999_form.php') ?>

        </div>
	</div>
</div>

<?php if (!empty($car['999_id'])) :
    $booster999Api = (new \App\Services\Api999Service())->getAdvertBoosterSettings($car['999_id']);
?>
<div id="boosterModal" class="modal">
    <div class="modal-content">
        <span class="closeBoosterModal">&times;</span>
        <h2>Настройки Booster</h2>
        <h3>Booster status: <?= $booster999['status'] ?? 'Не создан' ?></h3><br>

        <label for="period" style="display: flex">Period (days):</label>
        <input type="number" class="need form-control empty" id="period" min="1" max="999" value="<?= $booster999Api['period'] ?? ($booster999['period'] ?? '') ?>">
        <small class="hint">Period (days): (диапазон [1, 60] или 999 - бесконечно)</small>
        <br>
        <label for="dailyLimit" style="display: flex">Дневной бюджет, минимум 10 леев::</label>
        <input type="number" class="need form-control empty" id="dailyLimit" min="1" value="<?= $booster999Api['daily_limit'] ?? ($booster999['daily_limit'] ?? '') ?>">
        <br>
        <label for="click_price" style="display: flex">Цена за клик (бань), не больше дневного бюджета:</label>
        <input type="number" class="need form-control empty" id="click_price" min="0" value="<?= $booster999Api['click_price'] ?? ($booster999['click_price'] ?? '') ?>">
        <?php if (isset($booster999Api['days_left'])) : ?>
            <h3>Осталось: <?= $booster999Api['days_left'] ?> дней</h3>
        <?php endif; ?>
        <span class="errorBooster" style="color: red"></span>

        <div class="modal-buttons">
            <button id="saveBooster">Activate</button>
            <button id="pauseBooster" class="pause" style="<?php if (empty($booster999) || (!empty($booster999) && $booster999['status'] === 'pause')) : ?> pointer-events: none; opacity: 0.5; <?php endif; ?>">Pause</button>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const priceInput = document.querySelector('input[name="prc"]');
    const advanceInput = document.querySelector('input[name="advance_amount"]');
    
    // Function to calculate 70% advance
    function calculateAdvance() {
        const price = parseFloat(priceInput.value) || 0;
        const advance = Math.round(price * 0.7 * 100) / 100; // Round to 2 decimal places
        
        // Only auto-fill if advance field is empty or user hasn't manually changed it
        if (!advanceInput.dataset.userModified) {
            advanceInput.value = advance > 0 ? advance : '';
        }
    }
    
    // Calculate advance when price changes
    if (priceInput && advanceInput) {
        // Only calculate on page load if advance field is empty or zero (new car)
        const currentAdvance = parseFloat(advanceInput.value) || 0;
        if (currentAdvance === 0) {
            calculateAdvance();
        }
        
        // Recalculate when price changes
        priceInput.addEventListener('input', calculateAdvance);
        priceInput.addEventListener('change', calculateAdvance);
        
        // Mark as user-modified when advance is manually changed
        advanceInput.addEventListener('input', function() {
            this.dataset.userModified = 'true';
        });
        
        // Reset user-modified flag if advance is cleared
        advanceInput.addEventListener('focus', function() {
            if (this.value === '') {
                this.dataset.userModified = 'false';
            }
        });
    }

    // BRAND SYNC: Sauto form -> 999 form
    function syncBrandTo999() {
        const brandField = document.querySelector('select[name="br"]');
        if (!brandField || !brandField.value) return;
        
        const brandValue = brandField.value;
        const brand999Field = document.querySelector('select[name="feature[20]"]');
        if (!brand999Field) return;
        
        const options = brand999Field.querySelectorAll('option');
        let brandSynced = false;
        
        // Brand mapping: Sauto values -> 999 IDs
        const brandMapping = {
            'acura': ['392'], 
            'alfa_romeo': ['295'], 
            'audi': ['57'], 
            'bentley': ['288'], 
            'bmw': ['34'], 
            'brilliance': ['748'], 
            'byd': ['487'], 
            'cadillac': ['439'], 
            'chery': ['119'], 
            'chevrolet': ['167'], 
            'chrysler': ['101'], 
            'citroen': ['32'], 
            'cupra': ['24455'], 
            'dacia': ['375'], 
            'daewoo': ['99'], 
            'daihatsu': ['132'], 
            'dodge': ['89'], 
            'ds_automobiles': ['24352'], 
            'faw': ['504'],                 
            'fiat': ['41'], 
            'ford': ['139'], 
            'geely': ['587'], 
            'gmc': ['616'], 
            'great_wall': ['202'], 
            'haima': ['521'], 
            'haval': ['23260'], 
            'honda': ['149'], 
            'hummer': ['247'], 
            'hyundai': ['111'], 
            'infiniti': ['419'], 
            'isuzu': ['14'], 
            'iveco': ['1049'], 
            'jaguar': ['369'], 
            'jeep': ['186'], 
            'kia': ['130'], 
            'lamborghini': ['12462'], 
            'lancia': ['210'], 
            'land_rover': ['291'], 
            'lexus': ['136'], 
            'lifan': ['414'], 
            'lincoln': ['305'], 
            'lotus': ['1743'], 
            'maserati': ['1704'], 
            'mazda': ['45'], 
            'mercedes_benz': ['22'], 
            'mini': ['577'], 
            'mitsubishi': ['36'], 
            'nissan': ['28'], 
            'opel': ['1'], 
            'peugeot': ['76'], 
            'pontiac': ['284'], 
            'porsche': ['282'], 
            'renault': ['8'], 
            'renault_samsung': ['27737'], 
            'rolls_royce': ['266'], 
            'rover': ['62'], 
            'saab': ['344'], 
            'seat': ['200'], 
            'skoda': ['143'], 
            'smart': ['263'], 
            'ssangyong': ['397'], 
            'subaru': ['121'], 
            'suzuki': ['43'], 
            'tata': ['883'], 
            'tesla': ['17483'], 
            'toyota': ['47'], 
            'volkswagen': ['20'], 
            'volvo': ['193'], 
        };
        
        // Try mapping first
        if (brandMapping[brandValue]) {
            const mappedIds = brandMapping[brandValue];
            for (const mappedId of mappedIds) {
                if (!brandSynced) {
                    const matchedOption = Array.from(options).find(opt => opt.value === mappedId);
                    if (matchedOption) {
                        brand999Field.value = matchedOption.value;
                        brandSynced = true;
                        brand999Field.classList.remove('empty');
                        brand999Field.dispatchEvent(new Event('change', { bubbles: true }));
                        break;
                    }
                }
            }
        }
        
        // Fallback: exact value match
        if (!brandSynced) {
            options.forEach(option => {
                if (!brandSynced && option.value === brandValue) {
                    brand999Field.value = option.value;
                    brandSynced = true;
                    brand999Field.classList.remove('empty');
                    brand999Field.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }
        
        // Fallback: normalized text match (covers brands not in brandMapping, e.g.
        // newly auto-created ones). Normalize spaces/dashes/case so "Mercedes Benz"
        // == "Mercedes-Benz", and accept exact OR one starting with the other
        // (≥3 chars) so "Mercedes Benz" finds "Mercedes" — but NOT loose includes
        // that could match the wrong brand.
        if (!brandSynced) {
            const brandText = brandField.options[brandField.selectedIndex]?.textContent?.trim();
            if (brandText) {
                const nb = s => String(s).toLowerCase()
                    .normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[\s\-_]+/g, '').trim();
                const need = nb(brandText);
                let best = null;
                options.forEach(option => {
                    if (best || !option.value) return;
                    const o = nb(option.textContent);
                    if (o.length < 2 || need.length < 2) return;
                    if (o === need || (o.length >= 3 && need.startsWith(o)) || (need.length >= 3 && o.startsWith(need))) {
                        best = option;
                    }
                });
                if (best) {
                    brand999Field.value = best.value;
                    brandSynced = true;
                    brand999Field.classList.remove('empty');
                    brand999Field.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        }

        // Brand changed → the model list must be (re)loaded for it, so clear the
        // one-time guard in syncModelTo999.
        if (brandSynced) {
            syncModelTo999._loaded = false;
            if (window.jQuery) window.jQuery(brand999Field).trigger('change');
        }
    }

    // MODEL SYNC: Sauto form -> 999 form (depends on brand)
    function syncModelTo999() {
        const modelField = document.querySelector('select[name="mo"]');
        if (!modelField || !modelField.value) return;
        
        const modelValue = modelField.value;
        const modelText = modelField.options[modelField.selectedIndex]?.textContent?.trim();
        
        // Try feature[21] first (for subcategory 659 - Легковые автомобили)
        const model999SelectField = document.querySelector('select[name="feature[21]"]');
        
        // Try feature[585] (for subcategories 660, 662 - Автобусы, Грузовые)
        const model999InputField = document.querySelector('input[name="feature[585]"]');
        
        // If it's an input field (subcategories 660, 662), just copy the text
        if (model999InputField) {
            model999InputField.value = modelText || '';
            model999InputField.classList.remove('empty');
            return;
        }
        
        // If it's a select field (subcategory 659), match options
        const model999Field = model999SelectField;
        if (!model999Field) return;

        // The model list depends on brand. On Edit it often never auto-loads, so
        // if it has no real options yet, fetch them ourselves (same request the
        // .feature-select change handler uses), then retry the match.
        const hasRealOptions = Array.from(model999Field.options).some(o => o.value);
        if (!hasRealOptions) {
            const br999 = document.querySelector('select[name="feature[20]"]');
            const bx = model999Field.closest('.bx');
            // subcategory may be blank by now (the select gets reset); fall back to
            // the fixed group code so the request never goes out with empty params.
            const grp = <?= json_encode(($car['gr'] ?? '') === 'com' ? '660' : '659') ?>;
            const subcat = (bx && $(bx).find('.subcategory').val()) || grp;
            // Load the model list ONCE (guard against the previous infinite retry).
            if (window.jQuery && br999 && br999.value && !syncModelTo999._loaded) {
                syncModelTo999._loaded = true;
                window.jQuery.post('/ajax.php', {
                    tp: 'adm', pg: 'ordercars', fn: '999_catalog', sub: 'get_features_depends',
                    feature_id: model999Field.dataset.featureId || '21',
                    subcategory: subcat,
                    dependency_feature_id: br999.dataset.featureId || '20',
                    parent_option_id: br999.value,
                    bx_id: bx ? $(bx).data('bx_id') : ''
                }, function (response) {
                    const str = response && response.rtrn && response.rtrn.str;
                    if (str) {
                        const defText = $(model999Field).attr('def_text') || 'Select...';
                        $(model999Field).html('<option value="">' + defText + '</option>' + str);
                        setTimeout(syncModelTo999, 150); // now match with options present
                    }
                }, 'json');
            }
            return; // no blind retry loop — we either loaded once or we wait
        }
        syncModelTo999._tries = 0;

        const options = model999Field.querySelectorAll('option');
        let modelSynced = false;
        
        // If no options available, don't sync
        if (options.length <= 1) {
            return;
        }
        
        // Try exact value match first
        options.forEach(option => {
            if (!modelSynced && option.value === modelValue) {
                model999Field.value = option.value;
                modelSynced = true;
                model999Field.classList.remove('empty');
                model999Field.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        
        // Try text match with priority for exact matches. Normalize away spaces,
        // dashes and case so "C Class" == "C-Class" == "cclass" — but they stay
        // DISTINCT from "CLA" (cla), preventing the wrong model being picked.
        if (!modelSynced) {
            const modelText = modelField.options[modelField.selectedIndex]?.textContent?.trim();
            if (modelText) {
                const norm999 = s => String(s).toLowerCase()
                    .normalize('NFD').replace(/[̀-ͯ]/g, '')
                    .replace(/[\s\-_]+/g, '').trim();
                const needN = norm999(modelText);
                let bestMatch = null;
                let bestMatchScore = 0;

                options.forEach(option => {
                    if (!option.value) return;
                    const optN = norm999(option.textContent);
                    let score = 0;

                    if (optN === needN) {
                        score = 100; // Exact match (normalized) — highest priority
                    } else if (optN.length >= 3 && needN.length >= 3 && optN.includes(needN)) {
                        // Option contains the whole model word, prefer the SHORTEST
                        // such option (closest to the model, avoids over-long trims).
                        score = 80 - optN.length;
                    } else if (optN.length >= 3 && needN.length >= 3 && needN.includes(optN)) {
                        score = 60 - optN.length;
                    }

                    if (score > bestMatchScore) {
                        bestMatch = option;
                        bestMatchScore = score;
                    }
                });
                
                if (bestMatch) {
                    model999Field.value = bestMatch.value;
                    modelSynced = true;
                    model999Field.classList.remove('empty');
                    model999Field.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        }

        // Model set → the generation list depends on it, so (re)load + sync it.
        if (modelSynced) {
            syncGenerationTo999._loaded = false;
            setTimeout(syncGenerationTo999, 200);
        }
    }

        // SEATS SYNC: Sauto form -> 999 form
    function syncSeatsTo999() {
        const seatsField = document.querySelector('input[name="sts"]');
        if (!seatsField || !seatsField.value) return;
        
        const seats999Field = document.querySelector('input[name="feature[105]"]');
        if (!seats999Field) return;
        
        seats999Field.value = seatsField.value;
        seats999Field.classList.remove('empty');
    }

    // FORM LABEL: Set default "Другое"
    function setDefaultFormLabel() {
        const formLabelField = document.querySelector('select[name*="form-label"], select[name*="label"]');
        if (!formLabelField) return;
        
        const options = formLabelField.querySelectorAll('option');
        let labelSet = false;
        
        options.forEach(option => {
            if (!labelSet && (option.value === '18594' || option.textContent.trim() === 'Другое')) {
                formLabelField.value = option.value;
                labelSet = true;
                formLabelField.classList.remove('empty');
                formLabelField.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        
    }
    
    // PRICE SYNC: Sauto form -> 999 form
    function syncPriceTo999() {
        const priceField = document.querySelector('input[name="prc"]');
        if (!priceField || !priceField.value) return;
        
        const price999Field = document.querySelector('input[name="feature[2]"]');
        if (!price999Field) return;
        
        price999Field.value = priceField.value;
        price999Field.classList.remove('empty');
    }

    // YEAR SYNC: Sauto form -> 999 form
    function syncYearTo999() {
        const yearField = document.querySelector('input[name="yr"]');
        if (!yearField || !yearField.value) return;
        
        const year999Field = document.querySelector('input[name="feature[19]"]');
        if (!year999Field) return;
        
        year999Field.value = yearField.value;
        year999Field.classList.remove('empty');
    }

    // GENERATION SYNC: Based on year from Sauto form (depends on model)
    function syncGenerationTo999() {
        const yearField = document.querySelector('input[name="yr"]');
        if (!yearField || !yearField.value) return;
        
        const year = parseInt(yearField.value);
        if (isNaN(year) || year < 1900 || year > 2030) return;
        
        const generationField = document.querySelector('select[name="feature[2095]"]');
        if (!generationField) return;

        // Generation depends on the 999 model (feature[21]). On Edit its options
        // often don't auto-load, so fetch them once ourselves (same as the model),
        // then retry. Guarded so it can't loop.
        const hasGenOptions = Array.from(generationField.options).some(o => o.value);
        if (!hasGenOptions) {
            const mo999 = document.querySelector('select[name="feature[21]"]');
            const bx = generationField.closest('.bx');
            const grp = <?= json_encode(($car['gr'] ?? '') === 'com' ? '660' : '659') ?>;
            const subcat = (bx && $(bx).find('.subcategory').val()) || grp;
            if (window.jQuery && mo999 && mo999.value && !syncGenerationTo999._loaded) {
                syncGenerationTo999._loaded = true;
                window.jQuery.post('/ajax.php', {
                    tp: 'adm', pg: 'ordercars', fn: '999_catalog', sub: 'get_features_depends',
                    feature_id: generationField.dataset.featureId || '2095',
                    subcategory: subcat,
                    dependency_feature_id: mo999.dataset.featureId || '21',
                    parent_option_id: mo999.value,
                    bx_id: bx ? $(bx).data('bx_id') : ''
                }, function (response) {
                    const str = response && response.rtrn && response.rtrn.str;
                    if (str) {
                        const defText = $(generationField).attr('def_text') || 'Select...';
                        $(generationField).html('<option value="">' + defText + '</option>' + str);
                        setTimeout(syncGenerationTo999, 150);
                    }
                }, 'json');
            }
            return;
        }

        let generationSynced = false;

        Array.from(generationField.options).forEach(option => {
            if (generationSynced || !option.value) return;

            const optionText = option.textContent.trim();

            // Year range like "E84 (2009 - 2015)" / "U11 (2022 - prezent)" /
            // "XA50 (2018 - н.в)". End may be a year, Romanian "prezent" or
            // Russian "н.в" — all meaning "present".
            const yearRangeMatch = optionText.match(/\((\d{4})\s*[-–]\s*(\d{4}|н\.\s*в|prezent|present|н\.в)\)/i);

            if (yearRangeMatch) {
                const startYear = parseInt(yearRangeMatch[1]);
                const endYearText = yearRangeMatch[2].toLowerCase();
                const endYear = /^\d{4}$/.test(endYearText) ? parseInt(endYearText) : new Date().getFullYear();

                if (year >= startYear && year <= endYear) {
                    generationField.value = option.value;
                    generationSynced = true;
                    generationField.classList.remove('empty');
                    generationField.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        });
    }

    // BODY TYPE SYNC: Sauto form -> 999 form
    function syncBodyTypeTo999() {
        const bodyTypeField = document.querySelector('select[name="bt"]');
        if (!bodyTypeField || !bodyTypeField.value) return;
        
        const bodyTypeValue = bodyTypeField.value;
        const bodyType999Field = document.querySelector('select[name="feature[102]"]');
        if (!bodyType999Field) return;
        
        const options = bodyType999Field.querySelectorAll('option');
        let bodyTypeSynced = false;
        
        // Body type mapping
        const bodyTypeMapping = {
            'sdn': ['6'], // Sedan -> Седан
            'suv': ['18', '74'], // SUV -> Внедорожник sau Кроссовер
            'hbk': ['11'], // Hatchback -> Хетчбэк
            'unv': ['27'], // Universal -> Универсал
            'cup': ['96'], // Coupe -> Купе
            'crv': ['74'], // Crossover -> Кроссовер
            'mnv': ['49'], // Minivan -> Минивэн
            'pkp': ['61'], // Pickup -> Пикап
            'van': ['97'], // Furgon -> Фургон
            'mbs': ['53'], // Microbus -> Микровэн
            'cbr': ['156'], // Cabriolet -> Кабриолет
            'cmb': ['68'], // Combi -> Комби
            'rod': ['265'], // Roadster -> Родстер
        };
        
        // Try mapping first
        if (bodyTypeMapping[bodyTypeValue]) {
            const mappedIds = bodyTypeMapping[bodyTypeValue];
            for (const mappedId of mappedIds) {
                if (!bodyTypeSynced) {
                    const matchedOption = Array.from(options).find(opt => opt.value === mappedId);
                    if (matchedOption) {
                        bodyType999Field.value = matchedOption.value;
                        bodyTypeSynced = true;
                        bodyType999Field.classList.remove('empty');
                        bodyType999Field.dispatchEvent(new Event('change', { bubbles: true }));
                        break;
                    }
                }
            }
        }
        
        // Fallback: exact value match
        if (!bodyTypeSynced) {
            options.forEach(option => {
                if (!bodyTypeSynced && option.value === bodyTypeValue) {
                    bodyType999Field.value = option.value;
                    bodyTypeSynced = true;
                    bodyType999Field.classList.remove('empty');
                    bodyType999Field.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }
        
        // Fallback: text match
        if (!bodyTypeSynced) {
            const bodyTypeText = bodyTypeField.options[bodyTypeField.selectedIndex]?.textContent?.trim();
            if (bodyTypeText) {
                options.forEach(option => {
                    const optionText = option.textContent.trim();
                    if (!bodyTypeSynced && (
                        optionText.toLowerCase() === bodyTypeText.toLowerCase() ||
                        optionText.toLowerCase().includes(bodyTypeText.toLowerCase()) ||
                        bodyTypeText.toLowerCase().includes(optionText.toLowerCase())
                    )) {
                        bodyType999Field.value = option.value;
                        bodyTypeSynced = true;
                        bodyType999Field.classList.remove('empty');
                        bodyType999Field.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            }
        }
        
    }

    function syncMileageTo999() {
        const mileageField = document.querySelector('input[name="mlg"]');
        if (!mileageField || !mileageField.value) return;
        
        const mileageValue = mileageField.value;
        
        // Try feature[104] first 
        let mileage999Field = document.querySelector('input[name="feature[104]"]');
        
        // Try feature[1408] 
        if (!mileage999Field) {
            mileage999Field = document.querySelector('input[name="feature[1408]"]');
        }
        
        if (!mileage999Field) return;
        
        mileage999Field.value = mileageValue;
        mileage999Field.classList.remove('empty');
        
        const unitField = document.querySelector('select[name="unit"]');
        if (unitField && unitField.value) {
            const unitValue = unitField.value;
            const unit999Field = document.querySelector('select[name="feature_units[104]"]');
            if (unit999Field) {
                unit999Field.value = unitValue;
            }
        } 
    }

    function syncEngineVolumeTo999() {
        const engineVolumeField = document.querySelector('input[name="vol"]');
        if (!engineVolumeField || !engineVolumeField.value) return;

        const rawEngineVolume = String(engineVolumeField.value).trim().toLowerCase();
        const numericEngineVolume = parseFloat(rawEngineVolume.replace(',', '.').replace(/[^0-9.]/g, ''));
        if (!Number.isFinite(numericEngineVolume) || numericEngineVolume <= 0) return;

        const engineVolumeLiters = numericEngineVolume >= 50 ? (numericEngineVolume / 1000) : numericEngineVolume;
        const targetLitersRounded = Math.round(engineVolumeLiters * 10) / 10;
        
        let engineVolume999Field = document.querySelector('input[name="feature[103]"]');
        if (!engineVolume999Field) {
            engineVolume999Field = document.querySelector('select[name="feature[2553]"]');
        }
        
        if (!engineVolume999Field) return;
        
        if (engineVolume999Field.tagName === 'SELECT') {
            const options = engineVolume999Field.querySelectorAll('option');
            let volumeSynced = false;
            const volumeInLitersText = targetLitersRounded.toFixed(1);
            
            options.forEach(option => {
                if (!volumeSynced) {
                    const optionText = option.textContent.trim();

                    const m = optionText.match(/([0-9]+(?:[\.,][0-9]+)?)\s*л/i);
                    const optionLiters = m ? parseFloat(m[1].replace(',', '.')) : null;
                    if (optionLiters !== null && Number.isFinite(optionLiters)) {
                        const optionRounded = Math.round(optionLiters * 10) / 10;
                        if (optionRounded === targetLitersRounded) {
                            engineVolume999Field.value = option.value;
                            volumeSynced = true;
                            engineVolume999Field.classList.remove('empty');
                            engineVolume999Field.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }

                    if (!volumeSynced && optionText.includes(volumeInLitersText + ' л')) {
                        engineVolume999Field.value = option.value;
                        volumeSynced = true;
                        engineVolume999Field.classList.remove('empty');
                        engineVolume999Field.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            });
        } else {
            engineVolume999Field.value = numericEngineVolume;
            engineVolume999Field.classList.remove('empty');
        }
    }

    function syncHorsePowerTo999() {

        const horsePowerField = document.querySelector('input[name="hp"]');
        if (!horsePowerField || !horsePowerField.value) return;
        
        const horsePowerValue = horsePowerField.value;

        const horsePower999Field = document.querySelector('input[name="feature[107]"]');
        if (!horsePower999Field) return;
        
        horsePower999Field.value = horsePowerValue;
        horsePower999Field.classList.remove('empty');
    }

    function syncFuelTypeTo999() {

        const fuelTypeField = document.querySelector('select[name="fl"]');
        if (!fuelTypeField || !fuelTypeField.value) {
            return;
        }
        
        const fuelTypeValue = fuelTypeField.value;
        const fuelTypeText = fuelTypeField.options[fuelTypeField.selectedIndex]?.textContent?.trim();
        
        const fuelType999Field = document.querySelector('select[name="feature[151]"]');
        if (!fuelType999Field) {
            return;
        }
        
        const options = fuelType999Field.querySelectorAll('option');
        let fuelTypeSynced = false;
        
        const fuelTypeMapping = {
            'gsl': ['10'], 
            'gmn': ['159'], 
            'gpn': ['3'], 
            'hbd': ['161'], 
            'dsl': ['24'], 
            'pih': ['22987'], 
            'pid': ['43422'], 
            'elc': ['12617'], 
            'gas': ['21311'], 
        };

        if (fuelTypeMapping[fuelTypeValue]) {
            const mappedIds = fuelTypeMapping[fuelTypeValue];
            for (const mappedId of mappedIds) {
                if (!fuelTypeSynced) {
                    const matchedOption = Array.from(options).find(opt => opt.value === mappedId);
                    if (matchedOption) {
                        fuelType999Field.value = matchedOption.value;
                        fuelTypeSynced = true;
                        
                        fuelType999Field.classList.remove('empty');
                        fuelType999Field.dispatchEvent(new Event('change', { bubbles: true }));
                        break;
                    }
                }
            }
        }

        if (!fuelTypeSynced) {
            options.forEach(option => {
                if (!fuelTypeSynced && option.value === fuelTypeValue) {
                    fuelType999Field.value = option.value;
                    fuelTypeSynced = true;

                    fuelType999Field.classList.remove('empty');
                    fuelType999Field.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }
        
    }

    function syncTransmissionTo999() {
        
        const transmissionField = document.querySelector('select[name="tra"]');
        if (!transmissionField || !transmissionField.value) {
            return;
        }
        
        const transmissionValue = transmissionField.value;
        const transmissionText = transmissionField.options[transmissionField.selectedIndex]?.textContent?.trim();
        
        const transmission999Field = document.querySelector('select[name="feature[101]"]');
        if (!transmission999Field) {
            return;
        }
        
        const options = transmission999Field.querySelectorAll('option');
        let transmissionSynced = false;
        
        const transmissionMapping = {
            'tpt': ['16'], 
            'atm': ['16'], 
            'mnl': ['4'], 
            'rbt': ['1054'], 
            'vrr': ['1051'],
        };
                if (transmissionMapping[transmissionValue]) {
            const mappedIds = transmissionMapping[transmissionValue];
            for (const mappedId of mappedIds) {
                if (!transmissionSynced) {
                    const matchedOption = Array.from(options).find(opt => opt.value === mappedId);
                    if (matchedOption) {
                        transmission999Field.value = matchedOption.value;
                        transmissionSynced = true;
                        
                        transmission999Field.classList.remove('empty');
                        transmission999Field.dispatchEvent(new Event('change', { bubbles: true }));
                        break;
                    }
                }
            }
        }
        
        if (!transmissionSynced) {
            options.forEach(option => {
                if (!transmissionSynced && option.value === transmissionValue) {
                    transmission999Field.value = option.value;
                    transmissionSynced = true;
                    
                    transmission999Field.classList.remove('empty');
                    transmission999Field.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }
    }

    function syncWheelDriveTo999() {
        
        const wheelDriveField = document.querySelector('select[name="wd"]');
        if (!wheelDriveField || !wheelDriveField.value) return;
        
        const wheelDriveValue = wheelDriveField.value;
        
        const wheelDrive999Field = document.querySelector('select[name="feature[108]"]');
        if (!wheelDrive999Field) return;
        
        const options = wheelDrive999Field.querySelectorAll('option');
        let wheelDriveSynced = false;
        
        const wheelDriveMapping = {
            '44': ['17', '1086'],  
            're': ['25', '1065'],  
            'fr': ['5', '1065'],   
        };
        
        if (wheelDriveMapping[wheelDriveValue]) {
            const mappedIds = wheelDriveMapping[wheelDriveValue];
            for (const mappedId of mappedIds) {
                if (!wheelDriveSynced) {
                    const matchedOption = Array.from(options).find(opt => opt.value === mappedId);
                    if (matchedOption) {
                        wheelDrive999Field.value = matchedOption.value;
                        wheelDriveSynced = true;
                        
                        wheelDrive999Field.classList.remove('empty');
                        wheelDrive999Field.dispatchEvent(new Event('change', { bubbles: true }));
                        break;
                    }
                }
            }
        }
        
        if (!wheelDriveSynced) {
            options.forEach(option => {
                if (!wheelDriveSynced && option.value === wheelDriveValue) {
                    wheelDrive999Field.value = option.value;
                    wheelDriveSynced = true;
                    
                    wheelDrive999Field.classList.remove('empty');
                    wheelDrive999Field.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }
        
    }

    function syncColorTo999() {

        const colorField = document.querySelector('select[name="clr"]');
        if (!colorField || !colorField.value) return;
        
        const colorValue = colorField.value;
        
        const color999Field = document.querySelector('select[name="feature[17]"]');
        if (!color999Field) return;
        
        const options = color999Field.querySelectorAll('option');
        let colorSynced = false;
        
        const colorMapping = {
            'l_grn': ['176'], 
            'blu': ['40'], 
            'brn': ['208'], 
            'cmn': ['309'], 
            'cml': ['79'], 
            'bge': ['87'], 
            'wht': ['19'], 
            'vns': ['65'], 
            'azr': ['31'], 
            'ylw': ['179'], 
            'grn': ['13'], 
            'gld': ['72'], 
            'red': ['38'], 
            'orn': ['334'], 
            'pnk': ['554'], 
            'slv': ['56'], 
            'gra': ['50'], 
            'd_grn': ['12'], 
            'prp': ['93'], 
            'blk': ['7'], 
        };
        

        if (colorMapping[colorValue]) {
            const mappedIds = colorMapping[colorValue];
            for (const mappedId of mappedIds) {
                if (!colorSynced) {
                    const matchedOption = Array.from(options).find(opt => opt.value === mappedId);
                    if (matchedOption) {
                        color999Field.value = matchedOption.value;
                        colorSynced = true;
                        
                        color999Field.classList.remove('empty');
                        color999Field.dispatchEvent(new Event('change', { bubbles: true }));
                        break;
                    }
                }
            }
        }
        
        if (!colorSynced) {
            options.forEach(option => {
                if (!colorSynced && option.value === colorValue) {
                    color999Field.value = option.value;
                    colorSynced = true;
                    
                    color999Field.classList.remove('empty');
                    color999Field.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }
        
    }

    const brandField = document.querySelector('select[name="br"]');
    if (brandField) {
        brandField.addEventListener('change', function() {
            setTimeout(syncBrandTo999, 100);
        });
    }
    
    const modelField = document.querySelector('select[name="mo"]');
    if (modelField) {
        modelField.addEventListener('change', function() {
            setTimeout(syncModelTo999, 100);
        });
    }
    
    const priceField = document.querySelector('input[name="prc"]');
    if (priceField) {
        priceField.addEventListener('input', function() {
            setTimeout(syncPriceTo999, 100);
        });
    }
    
    const yearField = document.querySelector('input[name="yr"]');
    if (yearField) {
        yearField.addEventListener('input', function() {
            setTimeout(syncYearTo999, 100);
            setTimeout(syncGenerationTo999, 200);
        });
    }
    
    const bodyTypeField = document.querySelector('select[name="bt"]');
    if (bodyTypeField) {
        bodyTypeField.addEventListener('change', function() {
            setTimeout(syncBodyTypeTo999, 100);
        });
    }
    
    const mileageField = document.querySelector('input[name="mlg"]');
    if (mileageField) {
        mileageField.addEventListener('input', function() {
            setTimeout(syncMileageTo999, 100);
        });
    }
    
    const unitField = document.querySelector('select[name="unit"]');
    if (unitField) {
        unitField.addEventListener('change', function() {
            setTimeout(syncMileageTo999, 100);
        });
    }
    
    const engineVolumeField = document.querySelector('input[name="vol"]');
    if (engineVolumeField) {
        engineVolumeField.addEventListener('input', function() {
            setTimeout(syncEngineVolumeTo999, 100);
        });
    }
    
    const horsePowerField = document.querySelector('input[name="hp"]');
    if (horsePowerField) {
        horsePowerField.addEventListener('input', function() {
            setTimeout(syncHorsePowerTo999, 100);
        });
    }
    
    const fuelTypeField = document.querySelector('select[name="fl"]');
    if (fuelTypeField) {
        fuelTypeField.addEventListener('change', function() {
            setTimeout(syncFuelTypeTo999, 100);
        });
    }
    
    const transmissionField = document.querySelector('select[name="tra"]');
    if (transmissionField) {
        transmissionField.addEventListener('change', function() {
            setTimeout(syncTransmissionTo999, 100);
        });
    }
    
    const wheelDriveField = document.querySelector('select[name="wd"]');
    if (wheelDriveField) {
        wheelDriveField.addEventListener('change', function() {
            setTimeout(syncWheelDriveTo999, 100);
        });
    }
    
    const colorField = document.querySelector('select[name="clr"]');
    if (colorField) {
        colorField.addEventListener('change', function() {
            setTimeout(syncColorTo999, 100);
        });
    }
    
    // Listen for features reload event
    $(document).on('features999Loaded', function() {
        setTimeout(syncBrandTo999, 100); 
        setTimeout(syncPriceTo999, 200); 
        setTimeout(syncYearTo999, 300); 
        setTimeout(syncBodyTypeTo999, 400); 
        setTimeout(syncMileageTo999, 500); 
        setTimeout(syncSeatsTo999, 550);
        setTimeout(syncEngineVolumeTo999, 600); 
        setTimeout(syncHorsePowerTo999, 700); 
        setTimeout(syncFuelTypeTo999, 800); 
        setTimeout(syncTransmissionTo999, 900); 
        setTimeout(syncWheelDriveTo999, 1000); 
        setTimeout(syncColorTo999, 1100); 
        setTimeout(setDefaultFormLabel, 1200);
        setTimeout(syncModelTo999, 1500);
        setTimeout(syncGenerationTo999, 3000);
    });
    
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                const featuresContainer = document.querySelector('.features');
                if (featuresContainer && featuresContainer.children.length > 0) {
                    setTimeout(syncBrandTo999, 100); 
                    setTimeout(syncPriceTo999, 200); 
                    setTimeout(syncYearTo999, 300); 
                    setTimeout(syncBodyTypeTo999, 400); 
                    setTimeout(syncMileageTo999, 500); 
                    setTimeout(syncEngineVolumeTo999, 600); 
                    setTimeout(syncHorsePowerTo999, 700); 
                    setTimeout(syncFuelTypeTo999, 800); 
                    setTimeout(syncTransmissionTo999, 900); 
                    setTimeout(syncWheelDriveTo999, 1000); 
                    setTimeout(syncColorTo999, 1100); 
                    setTimeout(setDefaultFormLabel, 1200);
                    // Sync model LAST, after brand options are loaded
                    setTimeout(syncModelTo999, 1500);
                    // Sync generation AFTER model, when generation options are loaded
                    setTimeout(syncGenerationTo999, 3000); 
                    observer.disconnect(); 
                }
            }
        });
    });
    
    
    const featuresContainer = document.querySelector('.features');
    if (featuresContainer) {
        if (featuresContainer.children.length > 0) {
        
            setTimeout(syncBrandTo999, 100); 
            setTimeout(syncPriceTo999, 200); 
            setTimeout(syncYearTo999, 300); 
            setTimeout(syncBodyTypeTo999, 400); 
            setTimeout(syncMileageTo999, 500); 
            setTimeout(syncEngineVolumeTo999, 600); 
            setTimeout(syncHorsePowerTo999, 700); 
            setTimeout(syncFuelTypeTo999, 800); 
            setTimeout(syncTransmissionTo999, 900); 
            setTimeout(syncWheelDriveTo999, 1000); 
            setTimeout(syncColorTo999, 1100); 
            setTimeout(setDefaultFormLabel, 1200);
            // Sync model LAST, after brand options are loaded
            setTimeout(syncModelTo999, 1500);
            // Sync generation AFTER model, when generation options are loaded
            setTimeout(syncGenerationTo999, 3000);
        } else {

            observer.observe(featuresContainer, { childList: true, subtree: true });
        }
    }

    // Any car opened FROM parsing (?parsing_id=) — whether via "Edit" or any
    // autopublish flow (sauto / 999 / fb / tg) — must return to the parsing
    // "Published" page, not the default /ordercars/ctlg. finishProcess() reads
    // this back-url after publishing.
    if (/[?&](parsing_id=|autopublish(=1|999=1|fb=1|tg=1))\b/.test(window.location.search)) {
        const _lang = (document.cookie.split('; ').find(c => c.startsWith('lang=')) || 'lang=ro').split('=')[1];
        const _adminDir = <?= json_encode($admin_dir ?? 'adm') ?>;
        const _cb = document.getElementById('content_box');
        if (_cb) _cb.setAttribute('back-url', '/' + _lang + '/' + _adminDir + '/parsing/published');

        // Pre-load the 999 form on Edit too (not just on autopublish), so its
        // fields fill from sauto right away. The 999 .features only load when the
        // "offer type" select changes — so if it already has a value but the
        // features aren't loaded yet, trigger that change once.
        // Pre-load the 999 form on Edit: walk the dependent-select chain
        // (category → subcategory → offer type → features) so all 999 fields fill
        // from sauto. Each change is async (AJAX), so we wait for each step's
        // options to appear before selecting + triggering the next.
        const $j = window.jQuery;
        if ($j) {
            const fire = el => { if (el) $j(el).trigger('change'); };
            const setVal = (sel, val) => { const e = document.querySelector(sel); if (e && val) { e.value = val; } return e; };
            // Wait until <select> has a real value option, then run cb.
            const waitOpt = (sel, want, cb, tries) => {
                tries = tries || 0;
                const e = document.querySelector(sel);
                const has = e && Array.from(e.options).some(o => o.value && (!want || o.value === String(want)));
                if (has) { cb(e); }
                else if (tries < 40) { setTimeout(() => waitOpt(sel, want, cb, tries + 1), 250); }
            };
            setTimeout(function () {
                const offerSel = document.querySelector('.subcategory_offer_types');
                if (offerSel && offerSel.value) return; // already set up

                // On edit these selects are disabled; enable them so the dependent
                // AJAX chain (and change events) can run.
                ['.category', '.subcategory', '.subcategory_offer_types'].forEach(s => {
                    const e = document.querySelector(s); if (e) e.disabled = false;
                });

                const grp = <?= json_encode(($car['gr'] ?? '') === 'com' ? '660' : '659') ?>;
                const defOffer = '23844';

                // 1) Category is preselected (Auto) → trigger to load subcategories.
                fire(document.querySelector('.category'));
                // 2) Pick the subcategory, then trigger to load offer types.
                waitOpt('.subcategory', grp, () => {
                    setVal('.subcategory', grp);
                    fire(document.querySelector('.subcategory'));
                    // 3) Pick the offer type, then trigger to load the features.
                    waitOpt('.subcategory_offer_types', defOffer, () => {
                        setVal('.subcategory_offer_types', defOffer);
                        fire(document.querySelector('.subcategory_offer_types'));
                        // features load + features999Loaded fires → sync runs.
                    });
                });
            }, 1200);
        }
    }

    if (/[?&]autopublish999=1\b/.test(window.location.search)) {
        let done = false;
        const start = Date.now();
        const timer = setInterval(function () {
            if (done) return;
            if (Date.now() - start > 60000) { clearInterval(timer); return; }

            const btn = document.querySelector('button.confirm_999');
            if (!btn) return;

            const requiredSelects = document.querySelectorAll('#main_form_999 select.required, #main_form_999 select.feature-select.required');
            let allSelectsFilled = true;
            requiredSelects.forEach(function (s) {
                if (!s.value || s.value === '') allSelectsFilled = false;
            });
            const generation = document.querySelector('select[name="feature[2095]"]');
            const generationOk = !generation || (generation.value && generation.value !== '');

            // Phone: tick the first contact checkbox ourselves rather than waiting
            // for it to be pre-checked (it loads async after the 999 account is
            // chosen, so "wait for checked" stalled forever). The server forces the
            // account's own phone anyway, so this just passes the form's validation.
            const phoneBoxes = document.querySelectorAll('.form-check-input.contact');
            let phoneChecked = document.querySelectorAll('.form-check-input.contact:checked').length > 0;
            if (!phoneChecked && phoneBoxes.length) {
                phoneBoxes[0].checked = true;
                phoneBoxes[0].dispatchEvent(new Event('change', { bubbles: true }));
                phoneChecked = true;
            }
            // If the contact list still hasn't rendered after ~8s, stop blocking on
            // it — the server resolves the phone.
            const phoneOk = phoneChecked || (phoneBoxes.length === 0 && (Date.now() - start > 8000));

            if (!allSelectsFilled || !generationOk || !phoneOk) return;

            done = true;
            clearInterval(timer);

            const annType = document.getElementById('announcement_type');
            const isPersonal = annType && annType.value === 'sauto_personal';
            const genBtn = document.getElementById('generate_presets');

            // The category / subcategory / offer-type selects are DISABLED in edit
            // mode (id=...), so their values aren't serialized on submit — which
            // gave 999 an empty subcategory. Force them as hidden inputs (from the
            // select's value, falling back to the form's data-* defaults).
            (function forceCar999Fields() {
                const form = document.getElementById('main_form_999');
                if (!form) return;
                const put = (name, value) => {
                    if (!value) return;
                    let h = form.querySelector('input[type="hidden"][name="' + name + '"]');
                    if (!h) { h = document.createElement('input'); h.type = 'hidden'; h.name = name; form.appendChild(h); }
                    if (!h.value) h.value = value;
                };
                const selVal = sel => {
                    const el = form.querySelector('select[name="' + sel + '"]');
                    return el && el.value ? el.value : '';
                };
                put('car[category]',                selVal('car[category]')                || form.dataset.categoryId);
                put('car[subcategory]',             selVal('car[subcategory]')             || form.dataset.subcategoryId);
                put('car[subcategory_offer_types]', selVal('car[subcategory_offer_types]') || form.dataset.offerType);
            })();

            const submitNow = () => {
                if (window.jQuery) {
                    window.jQuery('#main_form_999').trigger('submit');
                } else {
                    btn.click();
                }
            };

            if (isPersonal && genBtn) {
                genBtn.click();

                let waits = 0;
                const schedTimer = setInterval(function () {
                    waits++;
                    const list = document.getElementById('schedules_list');
                    const hasSchedules = list && !document.getElementById('no_schedules_message')
                        && list.children.length > 0;
                    if (hasSchedules || waits > 25) {   // ~7.5s cap at 300ms steps
                        clearInterval(schedTimer);
                        submitNow();
                    }
                }, 300);
            } else {
                submitNow();
            }
        }, 300);   // poll faster so we submit the instant the form is ready
    }

    // Auto-publish to Facebook: call sendToFacebookCars() once available.
    if (/[?&]autopublishfb=1\b/.test(window.location.search)) {
        let done = false;
        const start = Date.now();
        const t = setInterval(function () {
            if (done) return;
            if (Date.now() - start > 30000) { clearInterval(t); return; }
            const fb = document.querySelector('.fb-share');
            if (!fb || typeof sendToFacebookCars !== 'function') return;
            done = true;
            clearInterval(t);
            try { sendToFacebookCars(); } catch (e) { fb.click(); }
            redirectToPublishedNow();
        }, 500);
    }

    // Auto-publish to Telegram: trigger sendToTelegramCars() once available.
    if (/[?&]autopublishtg=1\b/.test(window.location.search)) {
        let done = false;
        const start = Date.now();
        const t = setInterval(function () {
            if (done) return;
            if (Date.now() - start > 30000) { clearInterval(t); return; }
            const tg = document.querySelector('.adm_tg_btn');
            if (!tg || typeof sendToTelegramCars !== 'function') return;
            done = true;
            clearInterval(t);
            try { sendToTelegramCars(); } catch (e) { tg.click(); }
            redirectToPublishedNow();
        }, 500);
    }

    function parsingPublishedUrl() {
        const lang = (document.cookie.split('; ').find(c => c.startsWith('lang=')) || 'lang=ro').split('=')[1];
        const adminDir = <?= json_encode($admin_dir ?? 'adm') ?>;
        return '/' + lang + '/' + adminDir + '/parsing/published';
    }

    function redirectToPublishedNow() {
        setTimeout(function () { window.location.href = parsingPublishedUrl(); }, 200);
    }
});

// AI Generate Function - preia date din formular
function generateWithGemini() {
    const btn = document.getElementById('gemini-generate-btn');
    const originalText = '🤖 ' + btn.dataset.text;
    const loadingText = '⏳ ' + btn.dataset.loading;
    const successText = '✅ ' + btn.dataset.success;
    btn.innerHTML = loadingText;
    btn.disabled = true;
    
    const brand = document.querySelector('select[name="br"]');
    const model = document.querySelector('select[name="mo"]');
    const year = document.querySelector('input[name="yr"]');
    const mileage = document.querySelector('input[name="mlg"]');
    const volume = document.querySelector('input[name="vol"]');
    const hp = document.querySelector('input[name="hp"]');
    const fuel = document.querySelector('select[name="fl"]');
    const transmission = document.querySelector('select[name="tra"]');
    const wheelDrive = document.querySelector('select[name="wd"]');
    const color = document.querySelector('select[name="clr"]');
    const price = document.querySelector('input[name="prc"]');
    const currency = document.querySelector('select[name="cur"]');
    const importCountry = document.querySelector('select[name="import_country_id"]');
    
    const carData = {
        brand: brand ? brand.options[brand.selectedIndex]?.text || '' : '',
        model: model ? model.options[model.selectedIndex]?.text || '' : '',
        year: year ? year.value : '',
        mileage: mileage ? mileage.value : '',
        volume: volume ? volume.value : '',
        hp: hp ? hp.value : '',
        fuel: fuel ? fuel.options[fuel.selectedIndex]?.text || '' : '',
        transmission: transmission ? transmission.options[transmission.selectedIndex]?.text || '' : '',
        wheelDrive: wheelDrive ? wheelDrive.options[wheelDrive.selectedIndex]?.text || '' : '',
        color: color ? color.options[color.selectedIndex]?.text || '' : '',
        price: price ? price.value : '',
        currency: currency ? currency.options[currency.selectedIndex]?.text || '' : '',
        import_country: importCountry ? importCountry.options[importCountry.selectedIndex]?.text || '' : ''
    };
    
    // Get car_id if editing existing car
    const carIdInput = document.querySelector('input[name="id"]');
    const carId = carIdInput ? carIdInput.value : '';
    
    $.ajax({
        url: '/ajax.php',
        method: 'POST',
        data: {
            tp: 'adm',
            pg: 'cars',
            fn: 'ai_generate',
            lang: 'ro',
            from_form: '1',
            car_id: carId,
            car_type: 'order',
            brand: carData.brand,
            model: carData.model,
            year: carData.year,
            mileage: carData.mileage,
            volume: carData.volume,
            hp: carData.hp,
            fuel: carData.fuel,
            transmission: carData.transmission,
            wheelDrive: carData.wheelDrive,
            color: carData.color,
            price: carData.price,
            currency: carData.currency,
            import_country: carData.import_country
        },
        dataType: 'json',
        success: function(data) {
            btn.disabled = false;
            
            if (data.success) {
                btn.innerHTML = successText;
                const langs = ['ro', 'ru', 'en'];
                langs.forEach(function(lng) {
                    const textarea = document.getElementById('params_html_' + lng);
                    const htmlContent = data['html_' + lng];
                    if (textarea && htmlContent) {
                        textarea.value = htmlContent;
                        textarea.dispatchEvent(new Event('input', { bubbles: true }));
                        textarea.dispatchEvent(new Event('change', { bubbles: true }));
                        const label = textarea.closest('label');
                        if (label) {
                            label.setAttribute('data-changed', '1');
                            label.classList.add('changed');
                        }
                    }
                });
                // Reset button after 3 seconds
                btn.style.background = '#28a745';
                setTimeout(function() {
                    btn.innerHTML = originalText;
                    btn.style.background = '#4285f4';
                }, 3000);
            } else {
                btn.innerHTML = originalText;
                alert(' Ошибка: ' + (data.error || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });
}

</script>

<?php if ($parsing_prefill) : ?>
<script>
(function () {
    const data = <?= json_encode($parsing_prefill, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    window._parsingPrefilling = true;

    // Normalize a string for fuzzy matching (lowercase, collapse spaces, strip dashes).
    function norm(s) {
        // Lowercase, strip diacritics (Citroën → citroen, Škoda → skoda, Peugeot
        // stays), drop spaces/dashes/underscores. This lets source brand names with
        // accents match sauto's accent-free codes/labels.
        return String(s)
            .toLowerCase()
            .normalize('NFD').replace(/[̀-ͯ]/g, '') // strip combining accents
            .replace(/[\s\-_]+/g, '')
            .trim();
    }

    // Smart setter that tries multiple match strategies (value, exact text, normalized text).
    function setField(form, name, val, displayName) {
        const hasVal = val !== null && val !== undefined && val !== '';
        const hasDisp = displayName !== null && displayName !== undefined && displayName !== '';
        if (!hasVal && !hasDisp) return true;

        const el = form.querySelector('[name="' + name + '"]');
        if (!el) return false;

        if (el.tagName === 'SELECT') {
            // If the model was already set to a real option (e.g. by the AI match,
            // "E 220" -> "E Class"), keep it — don't re-search the raw name and end
            // up injecting a duplicate.
            if (name === 'mo' && el.value) {
                const cur = el.options[el.selectedIndex];
                if (cur && cur.value && cur.dataset.injected !== '1') return true;
            }
            let target = null;
            // 1. Exact value match.
            if (hasVal) {
                for (const opt of el.options) {
                    if (opt.value === String(val)) { target = opt.value; break; }
                }
                // 2. Normalized value match (handles "X5" vs "x5").
                if (target === null) {
                    const needV = norm(val);
                    for (const opt of el.options) {
                        if (norm(opt.value) === needV) { target = opt.value; break; }
                    }
                }
            }
            // 3. Display-name exact match.
            if (target === null && hasDisp) {
                const need = String(displayName).toLowerCase();
                for (const opt of el.options) {
                    if (opt.textContent.trim().toLowerCase() === need) { target = opt.value; break; }
                }
            }
            // 4. Display-name normalized match (case + spacing forgiving).
            if (target === null && hasDisp) {
                const needN = norm(displayName);
                for (const opt of el.options) {
                    if (norm(opt.textContent) === needN) { target = opt.value; break; }
                }
            }
            // 5. BRAND only: prefix match for sub-naming differences like
            //    "Mercedes" (Encar) vs "Mercedes Benz" (sauto), "VW" vs
            //    "Volkswagen". One side must START with the other (min 3 chars)
            //    to avoid false hits. Restricted to the brand field.
            if (target === null && name === 'br') {
                const cands = [];
                if (hasDisp) cands.push(norm(displayName));
                if (hasVal)  cands.push(norm(val));
                for (const need of cands) {
                    if (need.length < 3) continue;
                    for (const opt of el.options) {
                        if (!opt.value) continue;
                        const t = norm(opt.textContent), v = norm(opt.value);
                        if (t.startsWith(need) || need.startsWith(t)
                            || v.startsWith(need) || need.startsWith(v)) { target = opt.value; break; }
                    }
                    if (target !== null) break;
                }
            }
            // 6. BRAND/MODEL: still no match → the make/model isn't in sauto yet.
            //    Inject a new option (slug value + raw name) and select it, so the
            //    field isn't empty and passes validation. The server creates it in
            //    car_list on save (see order_add_new.php auto-create).
            if (target === null && (name === 'br' || name === 'mo') && hasDisp) {
                // For MODEL: never inject before the AI match has had its say, so
                // "E 220"/"320d" map to the existing "E Class"/"Seria 3" instead of
                // creating a duplicate. Only inject once AI settled with no match.
                if (name === 'mo') {
                    // Never overwrite a real (non-injected) value that's already
                    // selected — e.g. AI mapped "420 Gran Coupé" → "4 Series". Only
                    // a placeholder/empty/injected value may be replaced.
                    if (el.value) {
                        const curOpt = el.options[el.selectedIndex];
                        if (curOpt && curOpt.value && curOpt.dataset.injected !== '1') return true;
                    }
                    const brSel = form.querySelector('[name="br"]');
                    const brInjected = brSel && brSel.selectedOptions[0] && brSel.selectedOptions[0].dataset.injected === '1';
                    // Brand is brand-new → its model list is empty, AI can't help,
                    // so inject right away. Otherwise wait for the AI result.
                    if (!brInjected) {
                        if (!window._aiModelDone) return false;       // AI not done yet
                        if (el.options.length <= 1) return false;     // list not loaded
                    }
                }
                const raw = String(displayName).trim();
                const slug = raw.toLowerCase()
                    .normalize('NFD').replace(/[̀-ͯ]/g, '')
                    .replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
                if (slug !== '') {
                    // Reuse if we already injected it earlier.
                    for (const opt of el.options) { if (opt.value === slug) { target = slug; break; } }
                    if (target === null) {
                        const o = document.createElement('option');
                        o.value = slug; o.textContent = raw; o.dataset.injected = '1';
                        el.appendChild(o);
                        target = slug;
                    }
                }
            }
            if (target === null) return false;
            if (el.value === target) return true;
            el.value = target;
        } else {
            if (!hasVal) return true;
            if (String(el.value) === String(val)) return true;
            el.value = val;
        }

        if (name === 'br') return true;

        el.dispatchEvent(new Event('change', { bubbles: true }));
        el.dispatchEvent(new Event('input',  { bubbles: true }));
        if (window.jQuery) window.jQuery(el).trigger('change');
        return true;
    }

    // Refresh the country-flag image based on the currently selected option.
    function refreshFlag(form) {
        const sel = form.querySelector('[name="import_country_id"]');
        const img = form.querySelector('.country-flag-display');
        if (!sel || !img) return;
        const opt = sel.options[sel.selectedIndex];
        if (!opt) return;
        const flag = opt.getAttribute('data-flag');
        if (flag) img.src = '/media/images/flags/' + flag;
        const txt = opt.textContent.trim();
        if (txt) img.alt = ' ' + txt + ' ';
    }

    // Round the 70% advance amount sauto calculates from price.
    function roundAdvance(form) {
        const el = form.querySelector('[name="advance_amount"]');
        if (!el || !el.value) return;
        const n = parseFloat(String(el.value).replace(',', '.'));
        if (isNaN(n)) return;
        const rounded = Math.round(n);
        if (rounded === Number(el.value)) return;
        el.value = rounded;
        el.dispatchEvent(new Event('change', { bubbles: true }));
        if (window.jQuery) window.jQuery(el).trigger('change');
    }

    // Map of form field -> [data key, optional display-name key].
    const FIELDS = [
        ['gr',                'gr'],
        ['br',                'br',     'br_nm'],
        ['mo',                null,     'mo_nm'],   // model select is populated after brand change
        ['import_country_id', 'import_country_id'],
        ['yr',                'yr'],
        ['bt',                'bt'],
        ['sts',               'sts'],
        ['mlg',               'mlg'],
        ['vol',               'vol'],
        ['hp',                'hp'],
        ['fl',                'fl'],
        ['tra',               'tra'],
        ['wd',                'wd'],
        ['clr',               'clr'],
        ['loc',               'loc'],
        ['vin',               'vin'],
        ['prc',               'prc'],
        ['cur',               'cur'],
        ['title_ro',          'title_ro'],
        ['description_ro',    'description_ro'],
    ];

    // One pass — set every field that is not yet correct. Returns true when
    // all fields with data have been applied.
    function applyOnce(form) {
        let allDone = true;
        for (const [name, key, displayKey] of FIELDS) {
            const val = key ? data[key] : null;
            const displayName = displayKey ? data[displayKey] : null;
            if (val === undefined && !displayName) continue;
            const ok = setField(form, name, val ?? '', displayName);
            if (!ok) allDone = false;
        }
        // VIN-check toggle: ON when the prefill carries a real VIN (so it shows on
        // the public sauto page), OFF for the 17-zero placeholder. The checkbox is
        // visual-only, so update its checked state + the switch styling to match.
        if (data.vin_check_enabled !== undefined) {
            const vinChk = form.querySelector('[name="vin_check_enabled"]');
            if (vinChk) {
                const on = String(data.vin_check_enabled) === '1';
                vinChk.checked = on;
                vinChk.value = on ? '1' : '0';
                const track = vinChk.nextElementSibling;          // background span
                const knob  = track && track.nextElementSibling;  // round knob span
                if (track) track.style.backgroundColor = on ? '#e2001a' : '#ccc';
                if (knob)  knob.style.left = on ? '29px' : '3px';
            }
        }
        refreshFlag(form);
        roundAdvance(form);
        ensureModelsLoaded(form);
        // Source models (eCarsTrade "BMW 320", OpenLane free text) often don't
        // match sauto's canonical names ("Seria 3"). When the model select has
        // loaded its options but our model is still empty, ask AI to map the raw
        // name to the right option. Runs once.
        tryAiMatchModel(form);
        // After prefilling from parsing, flag any still-empty required fields in
        // red — same .empty styling as the manual-add validation — so the operator
        // sees at a glance what AI/source couldn't fill and must be set by hand.
        highlightEmptyNeeds(form);
        return allDone;
    }

    // Mark required (.need) fields that are still empty with the .empty class,
    // matching sauto's own validation look. Text/select fields go red when blank;
    // numeric (.need.nmb) fields go red when blank or 0. Filled fields are cleared.
    function highlightEmptyNeeds(form) {
        form.querySelectorAll('.need').forEach(el => {
            if (el.type === 'checkbox' || el.type === 'radio') return;
            const isNum = el.classList.contains('nmb');
            const v = (el.value || '').trim();
            const empty = isNum ? !(parseFloat(v) > 0) : (v === '');
            el.classList.toggle('empty', empty);
        });
    }

    // AI fallback for the model field: only when it's still empty after the normal
    // text matching, the option list is loaded, and we have a raw model name.
    // aiModelDone flips true once the AI answer has been processed (match or not),
    // which gates the "inject new model" step so we never create a duplicate model
    // (e.g. eCarsTrade "E 220" must map to the existing "E Class", not add "E 220").
    let aiModelTried = false;
    window._aiModelDone = false;
    function tryAiMatchModel(form) {
        if (aiModelTried) return;
        const rawModel = (data.mo_nm || '').trim();
        if (!rawModel) { window._aiModelDone = true; return; }
        const moSel = form.querySelector('[name="mo"]');
        if (!moSel || moSel.options.length <= 1) return; // options not loaded yet
        if (moSel.value) { window._aiModelDone = true; return; } // already matched
        aiModelTried = true;

        const options = Array.from(moSel.options)
            .filter(o => o.value !== '')
            .map(o => ({ value: o.value, text: o.textContent.trim() }));
        if (!options.length) { window._aiModelDone = true; return; }

        const body = new FormData();
        body.append('tp', 'adm');
        body.append('pg', 'parsing');
        body.append('action', 'match_model');
        body.append('brand', data.br_nm || '');
        body.append('raw_model', rawModel);
        body.append('options', JSON.stringify(options));
        fetch('/ajax.php', { method: 'POST', body, credentials: 'same-origin' })
            .then(r => r.json())
            .then(res => {
                if (res && res.success && res.value && !moSel.value) {
                    moSel.value = res.value;
                    moSel.dispatchEvent(new Event('change', { bubbles: true }));
                    moSel.dispatchEvent(new Event('input',  { bubbles: true }));
                    if (window.jQuery) window.jQuery(moSel).trigger('change');
                }
                window._aiModelDone = true; // AI settled → allow inject fallback
            })
            .catch(() => { aiModelTried = false; window._aiModelDone = true; });
    }

    // If brand is set but the model list is still empty for THAT brand, request
    // the model list from sauto (mo_search AJAX). We track which brand we fetched
    // for, so a later/corrected brand (e.g. after prefix-match "Mercedes" ->
    // "mercedes_benz") triggers a fresh fetch instead of being blocked.
    let modelsRequestedFor = '';
    function ensureModelsLoaded(form) {
        const moSel = form.querySelector('[name="mo"]');
        if (!moSel) return;
        const brSel = form.querySelector('[name="br"]');
        const br = (brSel && brSel.value) || '';   // always the REAL sauto code
        if (!br) return;                            // brand not matched yet
        if (modelsRequestedFor === br && moSel.options.length > 1) return; // done for this brand
        if (modelsRequestedFor === br) return;      // fetch already in flight

        modelsRequestedFor = br;
        const bx = form.querySelector('.bx');
        const bxId = bx ? bx.getAttribute('data-bx_id') : '';
        const body = new FormData();
        body.append('tp', 'adm');
        body.append('pg', 'ordercars');
        body.append('fn', 'add_new');
        body.append('sub', 'mo_search');
        body.append('br', br);
        body.append('bx_id', bxId || 'parsing-prefill');
        fetch('/ajax.php', { method: 'POST', body, credentials: 'same-origin' })
            .then(r => r.json())
            .then(resp => {
                const html = (resp && resp.rtrn && resp.rtrn.str) || '';
                if (!html) return;
                // Reset placeholder then append models, then let applyOnce match.
                const def = moSel.getAttribute('def_text') || 'МОДЕЛЬ';
                moSel.innerHTML = '<option value="">' + def + '</option>' + html;
                applyOnce(form); // re-run matching now that options exist
            })
            .catch(() => { if (modelsRequestedFor === br) modelsRequestedFor = ''; });
    }

    // Keep re-applying until the user submits. Sauto's own scripts can reset
    // values, so we keep guard up. Uses MutationObserver to react instantly
    // when the model list gets populated (no visible flicker).
    function start() {
        const form = document.querySelector('#content_box form, #content_box');
        if (!form) return;

        // Tell sauto's brand-change handler to stand down while we prefill — it
        // would otherwise wipe + re-fetch the model list, flashing the model.
        window._parsingPrefilling = true;

        // Model list is fetched by ensureModelsLoaded() inside applyOnce(), using
        // the brand select's REAL value AFTER it gets matched (incl. prefix-match
        // like "Mercedes" -> "mercedes_benz"). We don't pre-fetch with the raw
        // parsing key, which isn't a valid sauto brand code.

        const tick = () => applyOnce(form);
        tick();
        const handle = setInterval(tick, 80);

        // Observer: model <select> list changes → re-apply immediately.
        const moSel = form.querySelector('[name="mo"]');
        let moObserver = null;
        let valueGuard = null;
        if (moSel) {
            moObserver = new MutationObserver(() => tick());
            moObserver.observe(moSel, { childList: true });

            // If sauto's code resets mo to "" after we set it, snap back instantly
            // without waiting for the next interval tick.
            const wantedDisplay = (data.mo_nm || '').toLowerCase();
            valueGuard = (e) => {
                if (!wantedDisplay) return;
                if (!moSel.value) { setTimeout(tick, 0); return; }
                const cur = (moSel.options[moSel.selectedIndex]?.textContent || '').trim().toLowerCase();
                if (cur !== wantedDisplay) setTimeout(tick, 0);
            };
            moSel.addEventListener('change', valueGuard);
        }

        const stopGuard = () => {
            clearInterval(handle);
            window._parsingPrefilling = false;
            if (moObserver) moObserver.disconnect();
            if (valueGuard && moSel) moSel.removeEventListener('change', valueGuard);
        };
        form.addEventListener('submit', stopGuard, { once: true });
        const confirmBtn = form.querySelector('.confirm');
        if (confirmBtn) confirmBtn.addEventListener('click', stopGuard, { once: true });
        setTimeout(stopGuard, 30000);
        // Release the brand-handler lock after the prefill has settled (models
        // loaded + model set), so the user can still change the brand manually
        // afterwards and get a fresh model list. The value-guard keeps running.
        setTimeout(() => { window._parsingPrefilling = false; }, 4000);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }

    // Delay image downloads until after the form fields settle so they
    // don't compete for network/CPU with mo_search and model selection.
    setTimeout(loadParsingImages, 500);

    function loadParsingImages() {
    // Pre-load parsing images into the file input so the form shows thumbs.
    // The actual import is done server-side from these same URLs; we send the
    // current visual order (parsing_image_order) so the server respects drag
    // reordering and deletions made in the preview.
    if (data.images_local && data.parsing_id) {
        try {
            const imgs = typeof data.images_local === 'string' ? JSON.parse(data.images_local) : data.images_local;
            const urls = [];
            for (const it of (imgs || [])) {
                if (typeof it === 'string') urls.push(it);
                else if (it && it.url) urls.push(it.url);
                else if (it && it.path && it.name) urls.push('/' + String(it.path).replace(/^\/+/, '') + '/' + it.name);
            }
            if (urls.length) {
                const CONCURRENT = 6;
                // eCarsTrade/OpenLane: cap at 10 photos for sauto (their galleries
                // are large and we don't need them all); Encar keeps up to 30.
                const _src = (data.source || data.parsing_source || '');
                const _maxImgs = (_src === 'ecarstrade' || _src === 'openlane') ? 10 : 30;
                const list = urls.slice(0, _maxImgs);
                const results = new Array(list.length).fill(null);

                const fetchOne = (i) => {
                    const proxyUrl = '/ajax.php?tp=adm&pg=parsing&action=image_proxy&url=' + encodeURIComponent(list[i]);
                    return fetch(proxyUrl, { credentials: 'same-origin' })
                        .then(r => (r.ok && r.headers.get('content-type')?.includes('image')) ? r.blob() : null)
                        .then(b => {
                            if (b && b.size > 1000) {
                                const f = new File([b], 'parsing_' + (i + 1) + '.jpg', { type: 'image/jpeg' });
                                f._parsingUrl = list[i]; // remember source URL for ordering
                                results[i] = f;
                            }
                        })
                        .catch(() => null);
                };

                let next = 0;
                const fileInput = document.querySelector('input[name="img[]"]');
                const updateInput = () => {
                    if (!fileInput) return;
                    // Keep files in the original index order (no holes) so the
                    // preview's data-file-index lines up with fileInput.files.
                    const valid = results.filter(Boolean);
                    if (!valid.length) return;
                    const dt = new DataTransfer();
                    valid.forEach(f => dt.items.add(f));
                    fileInput.files = dt.files;
                    fileInput.dispatchEvent(new Event('change', { bubbles: true }));
                    // Tag each preview tile with its source URL so we can read
                    // the visual order at submit time.
                    const tiles = document.querySelectorAll('.prv.imgs:not(.ready) > .its > .it[data-file-index]');
                    tiles.forEach(tile => {
                        const fi = parseInt(tile.dataset.fileIndex, 10);
                        if (!isNaN(fi) && valid[fi] && valid[fi]._parsingUrl) {
                            tile.dataset.parsingUrl = valid[fi]._parsingUrl;
                        }
                    });
                };

                const worker = async () => {
                    while (next < list.length) {
                        const idx = next++;
                        await fetchOne(idx);
                        updateInput(); // refresh thumbs as soon as each one lands
                    }
                };
                const workers = [];
                for (let w = 0; w < CONCURRENT; w++) workers.push(worker());
                Promise.all(workers).then(updateInput);
            }
        } catch (e) { /* ignore */ }
    }
    } // end loadParsingImages

    // Fetch missing HP / drive type from AI in parallel (doesn't block prefill).
    if (data.parsing_id) {
        const form = document.querySelector('#content_box form, #content_box');
        if (form) {
            const hpEmpty = !(form.querySelector('[name="hp"]')?.value || data.hp);
            const wdEmpty = !(form.querySelector('[name="wd"]')?.value || data.wd);
            if (hpEmpty || wdEmpty) {
                const body = new FormData();
                body.append('tp', 'adm');
                body.append('pg', 'parsing');
                body.append('action', 'ai_enrich_specs');
                body.append('car_id', data.parsing_id);
                fetch('/ajax.php', { method: 'POST', body, credentials: 'same-origin' })
                    .then(r => r.json())
                    .then(res => {
                        if (!res || !res.success) return;
                        if (res.hp)         setField(form, 'hp', res.hp);
                        if (res.drive_type) setField(form, 'wd', ({'4x4':'44','fwd':'fr','rwd':'re'})[res.drive_type] || '');
                    })
                    .catch(() => {});
            }
        }
    }

    // Always pre-fill the mandatory comment (name="txt") for parsing cars, on
    // both Edit and Publish flows. Publish has its own writeSourceNote, but this
    // covers the Edit button too (no autopublish flag). Only fills if empty.
    if (data.parsing_id) {
        const writeParsingComment = () => {
            const txt = document.querySelector('[name="txt"]');
            if (!txt || txt.value.trim()) return true; // already has a comment
            const src   = (data.source || data.parsing_source || 'encar');
            const brand = (data.br_nm || '').trim();
            const model = (data.mo_nm || '').trim();
            const year  = (data.yr || '').toString().trim();
            const parts = [brand, model, year].filter(Boolean).join(' ');
            // Wait until brand/model are populated before writing.
            if (!brand && !model) return false;
            txt.value = (parts ? parts + '. ' : '') + 'Sursa: ' + src;
            txt.dispatchEvent(new Event('input', { bubbles: true }));
            txt.dispatchEvent(new Event('change', { bubbles: true }));
            return true;
        };
        if (!writeParsingComment()) {
            // br_nm/mo_nm may arrive a tick later — retry briefly.
            let tries = 0;
            const ci = setInterval(() => {
                if (writeParsingComment() || ++tries > 20) clearInterval(ci);
            }, 300);
        }
    }

    const wantAutoPublish = /[?&]autopublish=1\b/.test(window.location.search);
    if (wantAutoPublish && data.parsing_id) {
        const REQUIRED = ['gr', 'br', 'mo', 'yr', 'mlg', 'vol', 'hp', 'fl', 'tra', 'bt', 'clr', 'prc'];
        const form = document.querySelector('#content_box form, #content_box');

        // How many photos do we expect? (from the parsing entry, capped the same
        // way as the downloader: 10 for eCarsTrade/OpenLane, 30 otherwise). We must
        // wait for ALL of them, not just the first.
        let expectedImages = 0;
        try {
            const imgs = typeof data.images_local === 'string' ? JSON.parse(data.images_local) : data.images_local;
            const _src = (data.source || data.parsing_source || '');
            const _maxImgs = (_src === 'ecarstrade' || _src === 'openlane') ? 10 : 30;
            expectedImages = Math.min((Array.isArray(imgs) ? imgs.length : 0), _maxImgs);
        } catch (e) { expectedImages = 0; }

        const fieldsReady = () => {
            if (!form) return false;
            return REQUIRED.every(name => {
                const el = form.querySelector('[name="' + name + '"]');
                return el && String(el.value).trim() !== '';
            });
        };

        // Ready when downloads have stopped growing for ~4s (some images may
        // fail, so we can't wait for the exact expected count) and we have the
        // minimum 5 photos sauto requires. We treat "reached expected" as an
        // early exit only when we actually hit it.
        let lastCount = -1;
        let stableSince = 0;
        const imagesReady = () => {
            const fi = document.querySelector('input[name="img[]"]');
            const n = (fi && fi.files) ? fi.files.length : 0;
            if (n < 5) { // sauto needs at least 5
                if (n !== lastCount) { lastCount = n; stableSince = Date.now(); }
                return false;
            }
            // Count grew since last tick → reset the stability window.
            if (n !== lastCount) { lastCount = n; stableSince = Date.now(); return false; }
            // All expected arrived, or the count has been stable ~4s → done.
            if (expectedImages > 0 && n >= expectedImages) return true;
            return (Date.now() - stableSince) >= 4000;
        };

        // Fill the mandatory comment (name="txt") — publishing fails if empty.
        // Build a sensible default from brand/model/year + source.
        const writeSourceNote = () => {
            const src   = (data.source || data.parsing_source || 'encar');
            const brand = (data.br_nm || '').trim();
            const model = (data.mo_nm || '').trim();
            const year  = (data.yr || '').toString().trim();
            const parts = [brand, model, year].filter(Boolean).join(' ');
            const note  = (parts ? parts + '. ' : '') + 'Sursa: ' + src;

            // Mandatory comment field.
            const txt = document.querySelector('[name="txt"]');
            if (txt && !txt.value.trim()) {
                txt.value = note;
                txt.dispatchEvent(new Event('input', { bubbles: true }));
                txt.dispatchEvent(new Event('change', { bubbles: true }));
            }
        };

        let fired = false;
        const startedAt = Date.now();
        const apTimer = setInterval(() => {
            // Give up after 90s so we never hang (images can be slow).
            if (Date.now() - startedAt > 90000) { clearInterval(apTimer); return; }
            if (fired) return;
            if (!fieldsReady() || !imagesReady()) return;

            fired = true;
            clearInterval(apTimer);
            writeSourceNote();

            // Submit the sauto form by triggering its submit handler directly
            // (more reliable than clicking the button).
            const sf = document.getElementById('sautoForm');
            if (sf && window.jQuery) {
                window.jQuery(sf).trigger('submit');
            } else {
                const confirmBtn = document.querySelector('#sautoForm button.confirm, #content_box button.confirm');
                if (confirmBtn) confirmBtn.click();
            }

            const lang = (document.cookie.split('; ').find(c => c.startsWith('lang=')) || 'lang=ro').split('=')[1];
            const adminDir = <?= json_encode($admin_dir ?? 'adm') ?>;
            const watchDone = setInterval(() => {
                const cb = document.getElementById('content_box');
                const created = cb && cb.getAttribute('data-car-id');
                if (created) {
                    clearInterval(watchDone);
                    // Give the parallel photo uploads time to land in car_pht.
                    setTimeout(() => {
                        window.location.href = '/' + lang + '/' + adminDir + '/parsing/published';
                    }, 6000);
                }
            }, 400);
            setTimeout(() => clearInterval(watchDone), 120000);
        }, 300);
    }
})();
</script>
<?php endif; ?>

<?php include(__DIR__ . '/order_country_flags_include.php'); ?>