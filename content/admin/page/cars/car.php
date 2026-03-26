<?php defined( '_DOIT' ) or die( 'Restricted access' );

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

<div id="content_box" class="noselect" <?php if(!$new) : ?> data-car-id="<?=$car['id']?>" <?php endif; ?> back-url="<?= '/'.$_COOKIE['lang'].'/'.$admin_dir.'/cars/ctlg' ?>">
	<div class="bx id_<?= $bx_id ?>" data-bx_id="<?= $bx_id ?>" style="margin-bottom: 100px;">
		<div class="top" style="display: flex">
            <?php if ($user_name == 'Developer') : ?>
			    <div class="fill_fields" style="width:fit-content; position:absolute; top:0; left:4%; color:#00f; cursor:pointer; line-height:1.5rem;">Fill</div>
            <?php endif; ?>
			<div class="title"><?= !empty($car) ? (__('cars.edit_ad') . ' #' . $car['id']) : __('cars.new_ad') ?></div>
            <a class="close" href="<?= '/'.$_COOKIE['lang'].'/'.$admin_dir.'/cars/ctlg' ?>">X</a>
		</div>

		<?php if (!empty($car['is_at_client']) && $car['is_at_client'] == 1) : ?>
		<div class="is-at-client-banner" style="background: #ffc107; padding: 10px 20px; margin: 10px 0; text-align: center; font-weight: bold; font-size: 1.2rem; color: #000;">
			<?= $lang_is_at_client_badge ?>
		</div>
		<?php endif; ?>

		<form class="img_bx" id="img_bx" enctype="multipart/form-data">
			<h3 class="ttl ghost"><?=$lng['w']['imgs']?></h3>
			<label class="dd_plc">
				<input data-gr="new" class="f" type="file" multiple="multiple" name="img[]" tabindex="1" />
				<div class="txt drag ghost">DROP HERE</div>
				<div class="txt plus ghost"></div>
				<div class="inf h ghost"><?= $lng['w']['qu'] ?>: <span class="c">0</span> | <?= $lng['w']['sz'] ?>: <span class="s">0 B</span></div>
			</label>
			<div class="prv imgs h"><div class="its"></div></div>

            <?php if (!empty($car)) : ?>
                <input type="hidden" name="id" value="<?= $car['id'] ?>" />
                <div class="prv imgs ready">
                    <div class="its bx">
                        <?php $photos = (new \App\Db\CarPhoto())->getPhotosByCarId($_GET['id']); ?>
                        <?php foreach ($photos as $i => $p) :
                            $i++; ?>
                            <div class="it '<?= $p['id'] ?>' f_img ext" data-id="<?= $p['id'] ?>" this_img="/<?= $photo_folder ?>/<?= $p['path'] ?>/<?= $p['it_id'] ?>/high/<?= $p['name'] . $img_frmt ?>" data-n="<?= $i ?>" data-pos="<?= $p['pos'] ?>" style="order:<?= $i ?>;">
                                <input id="main_img_<?= $p['id'] ?>" type="radio" class="use main_img ext none" name="main_img" value="<?= $p['id'] ?>" data-id="<?= $p['id'] ?>" <?= ($p['main']=='1'?'checked="checked"':'') ?> />
                                <input id="del_img_<?= $p['id'] ?>" type="checkbox" class="use del_img ext none" name="del_img[]" value="<?= $p['id'] ?>" data-id="<?= $p['id'] ?>" />
                                <div class="ico ghost"></div>
                                <img class="img" src="/<?= $photo_folder ?>/<?= $p['path'] ?>/<?= $p['it_id'] ?>/med/<?= $p['name'] . $img_frmt ?>" />
                                <div class="nm"><?= $i ?></div>
                                <label for="main_img_<?= $p['id'] ?>" class="btn do_main photo_action" title="<?= $adm_lang['main_photo'] ?>"></label>
                                <label for="del_img_<?= $p['id'] ?>" class="btn delete photo_action" title="<?= $adm_lang['delete'] ?>"></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="action">
                        <div class="it download_zip" title="Download ZIP" data-it_id="<?= $car['id'] ?>">ZIP</div>
                        <!--<div class="it chng_pos" title="'.$lng['w']['chng_pos'].'" data-ttl="'.$lng['w']['acpt_chng'].'" data-txt="'.$lng['w']['acpt'].'" data-it_id="'.$_POST['id'].'">'.$lng['w']['chng_pos'].'</div>-->

                        <div class="it chng_pos" data-it_id="<?= $car['id'] ?>">
                            <div class="off" title="<?= $lng['w']['chng_pos'] ?>"><?= $lng['w']['chng_pos'] ?></div>
                            <div class="on" title="<?= $lng['w']['acpt_chng'] ?>"><?= $lng['w']['acpt'] ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
		</form>

        <form class="site_999_block" id="sautoForm">
            <h3 class="ttl ghost site_999_block_title"><?= __('cars.sauto_block_title') ?></h3>
            <div class="main_info row">
                <input type="hidden" name="id" value="<?= $car['id'] ?? '' ?>" />
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
                                onchange="this.value = +this.checked;"
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
                            <?php foreach ($countries as $country) : ?>
                                <?php if ($country['code'] == 'EU') : ?>
                                    <option value="<?= $country['id'] ?>" selected data-flag="<?= $country['flag'] ?>">
                                        <?= $country['name'] ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php foreach ($countries as $country) : ?>
                                <?php if ($country['code'] != 'EU') : ?>
                                    <option value="<?= $country['id'] ?>" data-flag="<?= $country['flag'] ?>">
                                        <?= $country['name'] ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <?php foreach ($countries as $country) : ?>
                                <option value="<?= $country['id'] ?>" 
                                    <?= (isset($car['import_country_id']) && $country['id'] == $car['import_country_id']) ? 'selected' : '' ?>
                                    data-flag="<?= $country['flag'] ?>">
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
                            /* Год выпуска (календарь) */
                            'year' => '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <rect x="3" y="4" width="18" height="16" rx="2"></rect>
  <path d="M8 2v4M16 2v4M3 10h18"></path>
</svg>',

                            /* Пробег (спидометр/одометр) */
                            'mileage' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <path d="M20 13a8 8 0 10-16 0"></path>
  <path d="M12 13l3-4"></path>
  <rect x="6" y="14.5" width="12" height="3" rx="1"></rect>
</svg>
SVG
                            ,

                            /* Объём двигателя (мотор) */
                            'engine' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <rect x="3" y="8" width="13" height="8" rx="2"></rect>
  <path d="M16 10h2l3 3v3h-3"></path>
  <path d="M7 6v2M11 6v2M7 16v2M11 16v2"></path>
</svg>
SVG
                            ,

                            /* Трансмиссия (двунаправленные стрелки) */
                            'transmission' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <path d="M7 4v8a3 3 0 003 3h4"></path>
  <path d="M14 4l3 3-3 3"></path>
  <path d="M10 20l-3-3 3-3"></path>
  <path d="M14 15h3v5h-3"></path>
</svg>
SVG
                            ,

                            /* Тип топлива (колонка) */
                            'fuel' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <rect x="3" y="3" width="10" height="18" rx="2"></rect>
  <path d="M13 7H3"></path>
  <path d="M16 7l3 3v7a2 2 0 01-2 2h-1"></path>
  <path d="M18 13c0-1.5-1-2-2-2"></path>
</svg>
SVG
                            ,

                            /* Климат-контроль (термометр/снежинка) */
                            'climate' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <path d="M12 2v8"></path>
  <path d="M9 6h6"></path>
  <circle cx="12" cy="15" r="4"></circle>
  <path d="M12 11v8"></path>
</svg>
SVG
                            ,

                            /* Круиз-контроль (компас) */
                            'cruise' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <circle cx="12" cy="12" r="9"></circle>
  <path d="M15 9l-3 6-3-1.5L15 9z"></path>
</svg>
SVG
                            ,

                            /* Парктроники (буква P + дуги) */
                            'parking' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <path d="M6 20V4h6a4 4 0 010 8H6"></path>
  <path d="M17 8.5c1.5 1.2 1.5 3.8 0 5"></path>
  <path d="M19.5 7c2.4 2.2 2.4 6.8 0 9"></path>
</svg>
SVG
                            ,

                            /* Навигация (пин на карте/стрела) */
                            'navigation' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <circle cx="12" cy="10" r="3.5"></circle>
  <path d="M12 21c4-3.8 6-6.8 6-9a6 6 0 10-12 0c0 2.2 2 5.2 6 9z"></path>
</svg>
SVG
                            ,

                            /* Подогрев сидений (кресло + волны) */
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

                            /* USB (трезубец) */
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
                       Константы проверки
                       ============================ */
                            const FORBIDDEN_TAGS = ['script','iframe','embed','object'];  /* Вставка скриптов и фреймов запрещена + общие опасные */
                            const IMG_EXT_HOST_RE = /^https?:\/\//i;                      /* Внешние изображения */
                            const ABS_URL_RE = /^https?:\/\//i;                           /* Проверка внешних ссылок */
                            const JS_URL_RE  = /^\s*javascript\s*:/i;                     /* javascript: в href/src запрещено */
                            const UNSAFE_STYLE_RE = /(expression\s*\(|url\s*\(\s*javascript\s*:)/i; /* опасные inline-стили */

                            /* ============================
                               sanitizeAndValidateHtml(inputHtml)
                               - чистит HTML (удаляет запрещённое)
                               - собирает сообщения по каждому случаю
                               - возвращает { cleanHtml, messages }
                               ============================ */
                            function sanitizeAndValidateHtml(inputHtml) {
                                const messages = [];            /* Сюда складываем сообщения по каждому найденному случаю */
                                const externalLinks = [];       /* Соберём внешние ссылки для отчёта */

                                /* Парсим как HTML-фрагмент в <template> */
                                const tpl = document.createElement('template');
                                tpl.innerHTML = inputHtml;

                                /* Пройдёмся по wszystkim элементам */
                                const walker = document.createTreeWalker(tpl.content, NodeFilter.SHOW_ELEMENT);

                                const toRemove = []; /* сюда сложим элементы, которые удалим после прохода */

                                while (walker.nextNode()) {
                                    const el = walker.currentNode;
                                    const tag = el.tagName ? el.tagName.toLowerCase() : '';

                                    /* 1) Запрещённые теги: script/iframe/embed/object */
                                    if (FORBIDDEN_TAGS.indexOf(tag) !== -1) {
                                        messages.push({
                                            type: 'error',
                                            rule: 'forbiddenTag',
                                            text: `Запрещённый тег <${tag}>`
                                        });
                                        toRemove.push(el);
                                        continue; /* к следующему элементу */
                                    }

                                    /* 2) Удаляем inline-обработчики on* (инлайн-скрипты) */
                                    [...el.attributes].forEach(attr => {
                                        const name = attr.name.toLowerCase();
                                        const value = attr.value || '';

                                        /* on* атрибуты (onclick, onload, ...) */
                                        if (name.startsWith('on')) {
                                            messages.push({
                                                type: 'error',
                                                rule: 'inlineHandler',
                                                text: ` инлайн-обработчик "${name}" на теге <${tag}>.`
                                            });
                                            el.removeAttribute(attr.name);
                                            return;
                                        }

                                        /* javascript: в href/src */
                                        if ((name === 'href' || name === 'src') && JS_URL_RE.test(value)) {
                                            messages.push({
                                                type: 'error',
                                                rule: 'javascriptUrl',
                                                text: `Атрибут ${name} с "javascript:"  на теге <${tag}>.`
                                            });
                                            el.removeAttribute(attr.name);
                                            return;
                                        }

                                        /* опасные inline-стили (expression(), url(javascript:)) */
                                        if (name === 'style' && UNSAFE_STYLE_RE.test(value)) {
                                            messages.push({
                                                type: 'error',
                                                rule: 'unsafeStyle',
                                                text: `Опасный inline-style  на теге <${tag}>.`
                                            });
                                            el.removeAttribute('style');
                                            return;
                                        }
                                    });

                                    /* 3) Проверка внешних изображений */
                                    if (tag === 'img') {
                                        const src = (el.getAttribute('src') || '').trim();

                                        /* внешнее изображение: http/https → запрещено */
                                        if (IMG_EXT_HOST_RE.test(src)) {
                                            messages.push({
                                                type: 'error',
                                                rule: 'externalImage',
                                                text: `Внешнее изображение запрещено и удалено: ${src}`
                                            });
                                            toRemove.push(el);
                                            continue;
                                        }
                                        /* data:, относительные пути — оставляем */
                                    }

                                    /* 4) Проверка внешних ссылок (не запрещаем, но фиксируем) */
                                    if (tag === 'a') {
                                        const href = (el.getAttribute('href') || '').trim();

                                        if (JS_URL_RE.test(href)) {
                                            /* javascript: в ссылке уже снят выше, но на всякий — сообщение */
                                            messages.push({
                                                type: 'error',
                                                rule: 'javascriptUrl',
                                                text: `Атрибут href с "javascript:"  на теге <a>.`
                                            });
                                            el.removeAttribute('href');
                                        } else if (ABS_URL_RE.test(href)) {
                                            /* абсолютная внешняя ссылка — просто сообщаем */
                                            externalLinks.push(href);
                                        }
                                    }
                                }

                                /* Удаляем накопленные элементы */
                                toRemove.forEach(node => node.remove());

                                /* Если есть внешние ссылки — выводим инфо по каждой */
                                externalLinks.forEach(url => {
                                    messages.push({
                                        type: 'info',
                                        rule: 'externalLink',
                                        text: `Найдена внешняя ссылка: ${url}`
                                    });
                                });

                                /* Готовый очищенный HTML */
                                const cleanHtml = tpl.innerHTML;

                                return { cleanHtml, messages };
                            }

                            /* ============================
                               attachTextareaValidation(textareaEl, messagesEl, onCleaned)
                               - навешивает проверку при вводе
                               - выводит сообщения в messagesEl
                               - колбэк onCleaned(cleanHtml) получит очищенный HTML
                               ============================ */
                            function attachTextareaValidation(textareaEl, messagesEl, onCleaned) {
                                function renderMessages(msgs) {
                                    /* Очищаем контейнер и рендерим список сообщений как текстовые строки */
                                    messagesEl.innerHTML = '';
                                    msgs.forEach(m => {
                                        const p = document.createElement('div');
                                        p.textContent = (m.type.toUpperCase()) + ': ' + m.text;
                                        /* можно стилизовать по типам: error/info */
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

                                /* первая прогонка + реагируем на ввод */
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
                                // webs25
                                $pdo = $db->prepare('SELECT * FROM ' . $prefx . '_seo2 WHERE `it_id`=:it_id AND lng = :lng LIMIT 1');
                                $pdo->execute(['it_id' => $car['id'], 'lng' => $v]);
                                $rseo = $pdo->fetch();
                                // var_dump( $rseo);

                                $html = $rseo['params_html'];

                            }

                            /* убираем экранирование сущностей */
                            $html = htmlspecialchars_decode($html, ENT_QUOTES);

                            /* нормализуем переносы строк */
                            $normalized = str_replace(["\r\n", "\r"], "\n", $html);

                            // $textareaSafe = htmlspecialchars($normalized, ENT_NOQUOTES, 'UTF-8');

                            // var_dump( $rseo);

                            /* Для вывода ВНУТРИ textarea обязательно экранируем HTML-сущности */
                            $textareaSafe = htmlspecialchars($normalized, ENT_QUOTES, 'UTF-8');

                            /* Для вывода в обычный <div> с видимыми переносами — используем nl2br */
                            //  $divSafe = nl2br(htmlspecialchars($normalized, ENT_QUOTES, 'UTF-8'));

                            ?>
                            <div class="lang_txt"><?= mb_strtoupper($v, "UTF-8") ?></div>
                            <label class="comment" data-changed="0">
                                <textarea class="comment no_need" name="params_html_<?= $v ?>" id="params_html_<?= $v ?>" size="11" tabindex="1" wrap="soft" placeholder="Характеристики"><?php echo $textareaSafe; ?></textarea>



                                <!-- Куда выводить сообщения -->
                                <div style="color: red" id="html_checks_<?= $v ?>"></div>

                                <!-- (необязательно) предпросмотр очищенного HTML -->
                                <div id="clean_preview_<?= $v ?>"></div>

                                <script>
                                    /* Привязка валидатора к textarea */
                                    var ta = document.getElementById('params_html_<?= $v ?>');
                                    var out = document.getElementById('html_checks_<?= $v ?>');
                                    var preview = document.getElementById('clean_preview_<?= $v ?>');

                                    /* Колбэк: показываем очищенный HTML в превью (как innerHTML) */
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
                    // Generate random Facebook posting time between 18:00 and 22:00
                    require_once __DIR__ . '/../../../../App/Helper/RandomTimeHelper.php';
                    $random_schedule_time = \App\Helper\RandomTimeHelper::generateRandomFacebookTime();
                    ?>
                    <input type="time" id="facebook_schedule_time" value="<?= $random_schedule_time ?>" style="padding: 5px; border: 1px solid #ccc; border-radius: 4px;" onclick="event.stopPropagation();">
                    <?php
                    // Get Facebook schedule status
                    $facebookStatus = '';
                    $facebookStatusIcon = '';
                    $facebookStatusText = '';
                    $facebookStatusColor = '';
                    if (!empty($car['id'])) {
                        try {
                            $catalogType = 'in_stock';
                            $stmt = $db->prepare("SELECT status FROM {$prefx}_scheduled_facebook_posts WHERE car_id = ? AND catalog_type = ? ORDER BY created_at DESC LIMIT 1");
                            $stmt->execute([$car['id'], $catalogType]);
                            $facebookSchedule = $stmt->fetch();
                            if ($facebookSchedule) {
                                $facebookStatus = $facebookSchedule['status'];
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
                    ?>
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
                // Generate random Telegram posting time between 18:00 and 22:00
                require_once __DIR__ . '/../../../../App/Helper/RandomTimeHelper.php';
                $random_telegram_time = \App\Helper\RandomTimeHelper::generateRandomTelegramTime();
                ?>
                <input type="time" id="telegram_schedule_time" value="<?= $random_telegram_time ?>" style="padding: 5px; border: 1px solid #ccc; border-radius: 4px;" onclick="event.stopPropagation();">
                <?php
                // Get Telegram schedule status
                $telegramStatus = '';
                $telegramStatusIcon = '';
                $telegramStatusText = '';
                $telegramStatusColor = '';
                if (!empty($car['id'])) {
                    try {
                        $catalogType = 'in_stock';
                        $stmt = $db->prepare("SELECT status FROM {$prefx}_scheduled_telegram_posts WHERE car_id = ? AND catalog_type = ? ORDER BY created_at DESC LIMIT 1");
                        $stmt->execute([$car['id'], $catalogType]);
                        $telegramSchedule = $stmt->fetch();
                        if ($telegramSchedule) {
                            $telegramStatus = $telegramSchedule['status'];
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
                ?>
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
            <?php include('999_form.php') ?>

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
        
        // Fallback: text match
        if (!brandSynced) {
            const brandText = brandField.options[brandField.selectedIndex]?.textContent?.trim();
            if (brandText) {
                options.forEach(option => {
                    const optionText = option.textContent.trim();
                    if (!brandSynced && (
                        optionText.toLowerCase() === brandText.toLowerCase() ||
                        optionText.toLowerCase().includes(brandText.toLowerCase()) ||
                        brandText.toLowerCase().includes(optionText.toLowerCase())
                    )) {
                        brand999Field.value = option.value;
                        brandSynced = true;
                        brand999Field.classList.remove('empty');
                        brand999Field.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            }
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
        
        // Check if model field is disabled (depends on brand)
        if (model999Field.disabled) {
            return; // Don't sync if disabled
        }
        
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
            }
        });
        
        // Try text match with priority for exact and longer matches
        if (!modelSynced) {
            const modelText = modelField.options[modelField.selectedIndex]?.textContent?.trim();
            if (modelText) {
                let bestMatch = null;
                let bestMatchScore = 0;
                
                options.forEach(option => {
                    const optionText = option.textContent.trim();
                    let score = 0;
                    
                    if (optionText.toLowerCase() === modelText.toLowerCase()) {
                        score = 100; // Exact match - highest priority
                    } else if (optionText.toLowerCase().includes(modelText.toLowerCase())) {
                        score = 80 + optionText.length; // Option contains model text
                    } else if (modelText.toLowerCase().includes(optionText.toLowerCase())) {
                        score = 60 - optionText.length; // Model text contains option (prefer longer options)
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
        
        const options = generationField.querySelectorAll('option');
        if (options.length <= 1) {
            // Retry after 500ms if options not loaded yet
            setTimeout(syncGenerationTo999, 500);
            return;
        }
        
        let generationSynced = false;
        
        options.forEach(option => {
            if (generationSynced || !option.value) return;
            
            const optionText = option.textContent.trim();
            
            // Extract year ranges from text like "XA10 (1994 - 2000)" or "I (1995 - 2002)" or "XA50 (2018 - н.в)"
            const yearRangeMatch = optionText.match(/\((\d{4})\s*[-–]\s*(\d{4}|н\.в|н\. в)\)/);
            
            if (yearRangeMatch) {
                const startYear = parseInt(yearRangeMatch[1]);
                const endYearText = yearRangeMatch[2];
                
                let endYear;
                if (endYearText === 'н.в' || endYearText === 'н. в') {
                    // "н.в" means "настоящее время" (present time)
                    endYear = new Date().getFullYear();
                } else {
                    endYear = parseInt(endYearText);
                }
                
                // Check if car year falls within this generation range
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
        
        // Try feature[104] first (for subcategory 659 - Легковые автомобили)
        let mileage999Field = document.querySelector('input[name="feature[104]"]');
        
        // Try feature[1408] (for subcategories 660, 662 - Автобусы, Грузовые)
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
});

// AI Generate Function - preia date din formular
function generateWithGemini() {
    const btn = document.getElementById('gemini-generate-btn');
    const originalText = '🤖 ' + btn.dataset.text;
    const loadingText = '⏳ ' + btn.dataset.loading;
    const successText = '✅ ' + btn.dataset.success;
    btn.innerHTML = loadingText;
    btn.disabled = true;
    
    // Get selected language tab or default to 'ro'
    const activeLangTab = document.querySelector('.txt .content label.active');
    let lang = 'ro';
    if (activeLangTab) {
        lang = activeLangTab.className.replace('active', '').trim().split(' ')[0];
    }
    
    // Collect data from form fields
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
    
    // Send request with form data
    $.ajax({
        url: '/ajax.php',
        method: 'POST',
        data: {
            tp: 'adm',
            pg: 'cars',
            fn: 'ai_generate',
            lang: lang,
            from_form: '1',
            car_id: carId,
            car_type: 'in_stock',
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
                        // Also update the hidden change checker if exists
                        const changeChecker = document.querySelector('input[name="params_html_changed_' + lng + '"]');
                        if (changeChecker) changeChecker.value = '1';
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
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });
}
</script>

<?php include(__DIR__ . '/country_flags_include.php'); ?>