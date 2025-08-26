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
                </div>

                <!-----GROUP----->
                <div class="form-group col-md-3">
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
                    <select class="model need form-control" name="mo" def_text="<?= mb_strtoupper($lang_model, "UTF-8") ?>" tabindex="2">
                        <option value=""><?= mb_strtoupper($lang_model, "UTF-8") ?></option>
                        <?php foreach ($list as $v) : ?>
                            <option value="<?= $v['mo'] ?>" <?php if ($v['mo'] == $car['mo']) : ?> selected <?php endif; ?>><?= $v['mo_nm'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else : ?>
                <div class="form-group col-md-3">
                    <select class="model need form-control" name="mo" def_text="<?= mb_strtoupper($lang_model, "UTF-8") ?>" tabindex="2">
                        <option value=""><?= mb_strtoupper($lang_model, "UTF-8") ?></option>
                    </select>
                </div>
                <?php endif; ?>

                <!-----COUNTRY OF IMPORT----->
                <div class="form-group col-md-4">
                    <select class="country form-control" name="import_country_id" tabindex="9" title="<?= __('cars.import_country') ?>">
                        <option value=""><?= strtoupper(__('cars.import_country')) ?></option>
                        <?php foreach ($countries as $country) : ?>
                            <option value="<?= $country['id'] ?>" 
                                <?= ((isset($car['import_country_id']) && $country['id'] == $car['import_country_id']) ? 'selected' : '') ?>
                                data-flag="<?= $country['flag'] ?>">
                                <?= $country['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-----YEAR----->
                <div class="form-group col-md-3">
                    <input class="year need nmb form-control" name="yr" tabindex="3" type="text"
                            placeholder="<?= mb_strtoupper($lang_year, "UTF-8") ?>"
                            title="<?= mb_strtoupper($lang_year, "UTF-8") ?>"
                            value="<?= $car['yr'] ?? '' ?>">
                </div>

                <!-----BODYTYPE----->
                <div class="form-group col-md-3">
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
                    <input class="seats need nmb form-control" type="text" name="sts" tabindex="12"
                           value="<?= $car['sts'] ?? '' ?>"
                           placeholder="<?= mb_strtoupper($lng['l']['car']['spec']['sts'], "UTF-8") ?>"
                           title="<?= mb_strtoupper($lng['l']['car']['spec']['sts'], "UTF-8") ?>">
                </div>

                <!-----MILEAGE----->
                <div class="form-group col-md-40">
                    <input class="mileage need nmb form-control" name="mlg" size="11" tabindex="5"
                           value="<?= $car['mlg'] ?? '' ?>"
                           placeholder="<?= mb_strtoupper($lang_mileage, "UTF-8") ?>" type="text"
                           title="<?= mb_strtoupper($lang_mileage, "UTF-8") ?>">
                </div>

                <!-----KM_OR_MI----->
                <div class="form-group col-md-10">
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
                    <input class="engine need nmb form-control" name="vol" size="16" tabindex="7"
                           value="<?= $car['vol'] ?? '' ?>"
                           placeholder="<?= mb_strtoupper($lang_engine, "UTF-8") ?>" type="text"
                           title="<?= mb_strtoupper($lang_engine, "UTF-8") ?>">
                </div>

                <!-----HP----->
                <div class="form-group col-md-10">
                    <input class="hp need nmb form-control" name="hp" size="16" tabindex="7"
                           value="<?= $car['hp'] ?? '' ?>"
                           placeholder="<?= mb_strtoupper($lang_hp, "UTF-8") ?>" type="text"
                           title="<?= mb_strtoupper($lang_hp, "UTF-8") ?>">
                </div>

                <!-----FUEL----->
                <div class="form-group col-md-2">
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
                    <select class="location need form-control" name="loc" title="<?= $lng['w']['address'] ?>" tabindex="12">
                        <?php foreach ($lng['t']['x']['address'] as $k => $v) : ?>
                            <option value="<?= ($k == 0 ? '' : $k) ?>" <?= ((isset($car['loc']) && $k==$car['loc']) ? 'selected' : '') ?>>
                                <?= ($k == 0 ? $lng['w']['address'] : $v) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-----VIN CODE----->
                <div class="form-group col-md-6">
                    <input class="vin need form-control" type="text" name="vin" tabindex="15"
                           value="<?= $car['vin'] ?? '' ?>"
                           placeholder="VIN КОД (17 символов)"
                           title="VIN КОД (17 символов)"
                           minlength="17"
                           maxlength="17"
                           pattern="[A-HJ-NPR-Z0-9]{17}"
                           oninput="this.value = this.value.toUpperCase().replace(/[^A-HJ-NPR-Z0-9]/g, '')">
                </div>

                <!-----PRICE--->
                <div class="form-group col-md-90">
                    <input class="price need nmb form-control" type="text" name="prc" tabindex="16"
                           value="<?= $car['prc'] ?? '' ?>"
                           placeholder="<?= mb_strtoupper($lang_price, "UTF-8") ?>"
                           title="<?= mb_strtoupper($lang_price, "UTF-8") ?>">
                </div>

                <!-----CURRENCY--->
                <div class="form-group col-md-10">
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

            <a  class="fb-share  adm_tg_btn"
                <?/* href="https://www.facebook.com/sharer/sharer.php?u=<?= $site_url . '/ro/cars/' . $car['id'] ?>"
                target="_blank" rel="noopener noreferrer" */?>
            >

                <div class="" onclick=" sendToFacebookCars() ">
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
                </div>
            </a>


            <div class="adm_tg_btn" onclick=" sendToTelegramCars() ">
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

<?php include(__DIR__ . '/country_flags_include.php'); ?>