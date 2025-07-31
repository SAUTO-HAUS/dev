<?php
// Get display limit from various sources with fallback to default (25)
$display_limit = 25; // Default fallback

// Check for URL parameter first (for page reload approach)
if (isset($_GET['limit'])) {
    $limit_param = $_GET['limit'];
    if ($limit_param === 'all' || $limit_param === '0') {
        $display_limit = 999;
    } elseif (in_array((int)$limit_param, [25, 100])) {
        $display_limit = (int)$limit_param;
    }
}
// Check for POST parameter (for AJAX approach)
elseif (isset($_POST['limit'])) {
    $limit_param = $_POST['limit'];
    if ($limit_param === 'all' || $limit_param === '0') {
        $display_limit = 999;
    } elseif (in_array((int)$limit_param, [25, 100])) {
        $display_limit = (int)$limit_param;
    }
}

$i_max = $display_limit;
$pdo = (new \App\Db\Car())->getCarsCtlg($i_max);
$total_cars_fetched = count($pdo);
$has_more_cars = $total_cars_fetched > $i_max;
$i = 0;
?>

<div class="display-controls">
    <label for="cars-display-limit"><?= $lng['adm']['display_limit'] ?? 'Показать:' ?></label>
    <select id="cars-display-limit" name="limit">
        <option value="25" <?= $display_limit == 25 ? 'selected' : '' ?>>25</option>
        <option value="100" <?= $display_limit == 100 ? 'selected' : '' ?>>100</option>
        <option value="all" <?= $display_limit == 999 ? 'selected' : '' ?>><?= $lng['adm']['all'] ?? 'Все' ?></option>
    </select>
    <span class="loading-indicator" id="cars-loading" style="display: none;">⟳</span>
</div>
<div class="ctlg_dspl_tp"></div>
<section class="ctlg">
    <a id="add_new" href="<?= '/'.$_COOKIE['lang'].'/'.$admin_dir.'/cars/detail' ?>" class="bx" title="<?= $lng['adm']['add'] ?>">
        <div>
            
        </div>
    </a>

    <?php foreach ($pdo as $r) :
        $on_img =  $r['gift']==1  ? '<span class="top">Cadou</span>' : '';
        $on_img .= $r['tva']==1  ? '<span class="tva">TVA</span>' : '';
        $on_img .= $r['soon']==1 ? '<span class="soon" '.($_COOKIE['lang']=='ruXXXXXXX'?'style="order:1;"':'').'>'.$lng['l']['stat']['soon1'].'</span>' : '';
        $on_img .= $r['n_a']==1  ? '<span class="not_av">'.$lng['l']['stat']['n_a1'].'</span>' : '';
        $on_img .= $r['top']==1  ? '<span class="top">'.$lng['l']['stat']['top1'].'</span>' : '';

        $new_item = 0;
        if ($r['new']!=''){
            if ( ( time() - $r['new'] ) > 172800 ) {
                //$pdo = $db->prepare('UPDATE '.$prefx.'_car_ctlg SET `new`=0 WHERE `id`=' . $r['id']);
                //$pdo->execute();
                //$pdo->closeCursor();
            } else {
                $new_item = 1;
            }
        }

        $pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_pht WHERE `it_id`= :it_id AND `main`="1"');
        $pdo->execute([ 'it_id' => $r['id'] ]);

        foreach ($pdo as $p) {
            $p_nm = $p['name'];
            $p_ff = $p['ff'];
        }

        $stts = ($r['vis']==0?' hided':'').($r['act']==0?' deleted':'');

        $z_msg = ( empty($r['txt']) || !trim($r['txt']) ) ? '' : 'act';
        $z_loc = ( in_array($user_login, ['comerzan', 'tudor l']) ) ? 2 : 1;
        $av_k = ['id'=>0, 'br'=>0, 'mo'=>0, 'br_nm'=>1, 'mo_nm'=>1, 'yr'=>0, 'bt'=>0, 'mlg'=>1, 'unit'=>0, 'vol'=>0, 'hp'=>0, 'fl'=>0, 'tra'=>0, 'wd'=>0, 'sts'=>0, 'clr'=>0, 'prc'=>1, 'cur'=>1];

        if ( $user_type=='x2' && $r['loc']!=2 ) {
            continue;
        }

        if (!empty($r['999_id'])) {
            $decode999 = (new \App\Services\Api999Service($r['999_api_id']))->getAdvert($r['999_id']);

            if (!isset($decode999['error']) && !empty($decode999) && empty($r['br'])) {
                $data999 = (new \App\Helper\DataTransform())->getKeyValue($decode999);
            }
        }
        ?>
        <div class="bx<?= $stts ?>" data-id="<?= $r['id'] ?>">
            <div class="log_sauto_999">
                <?php if (!empty($r['br'])) : ?>
                    <img src="/media/images/site/v2/logo_b.svg" class="log_sauto" title="Published on 999" alt="Published on SAUTO"/>
                <?php endif; ?>
                <?php if (!empty($r['999_id'])) : ?>
                    <?php $adverts = (new \App\Db\Adverts())->getActiveAdvertsByCarId($r['id']);
                        if (!empty($adverts)) :
                            $tooltip = __('cars.date_next_public') . ':<br>';
                            foreach ($adverts as $advert) {
                                $tooltip .= 'Clone #' . $advert['type'] . ' - ' . $advert['publish_datetime'] . '<br>';
                            }
                        endif;
                    ?>
                    <a href="https://999.md/<?= $r['999_id'] ?>" target="_blank">
                        <img src="/media/images/site/logo_999.svg" class="log_999"
                             data-tooltip="<?= $tooltip ?? '' ?>"
                             alt="Published on 999"/>
                    </a>
                <?php endif; ?>
            </div>

            <? if($r['telegram_published'] == 1 || $r['facebook_published'] == 1) {?>
                <div class="icon_list_cattg">

                    <? if($r['telegram_published'] == 1) {?>
                        <div class="icon_tg" title="Опубликовано в Telegram">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 496 512">
                                <!-- Синий круг -->
                                <circle cx="248" cy="256" r="248" fill="#0088cc"/>
                                <!-- Логотип Telegram (белый) -->
                                <path fill="#ffffff" d="M248,8C111.033,8,0,119.033,0,256S111.033,504,248,504,496,392.967,496,256,384.967,8,248,8ZM362.952,176.66
                                    c-3.732,39.215-19.881,134.378-28.1,178.3-3.476,18.584-10.322,24.816-16.948,25.425-14.4,1.326-25.338-9.517-39.287-18.661
                                    -21.827-14.308-34.158-23.215-55.346-37.177-24.485-16.135-8.612-25,5.342-39.5,3.652-3.793,67.107-61.51,68.335-66.746
                                    .153-.655.3-3.1-1.154-4.384s-3.59-.849-5.135-.5q-3.283.746-104.608,69.142-14.845,10.194-26.894,9.934
                                    c-8.855-.191-25.888-5.006-38.551-9.123-15.531-5.048-27.875-7.717-26.8-16.291q.84-6.7,18.45-13.7
                                    108.446-47.248,144.628-62.3c68.872-28.647,83.183-33.623,92.511-33.789,2.052-.034,6.639.474,9.61,2.885
                                    a10.452,10.452,0,0,1,3.53,6.716A43.765,43.765,0,0,1,362.952,176.66Z"/>
                            </svg>
                        </div>
                    <?} ?>

                    <? if($r['facebook_published'] == 1) {?>
                        <div class="icon_tg" title="Опубликовано в Facebook">
                            <svg style="top: 7px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                <!-- Синий круг -->
                                <circle cx="256" cy="256" r="256" fill="#1877F2"/>
                                <!-- Логотип Facebook (белый "f") -->
                                <path fill="#ffffff" d="M504 256C504 119 393 8 256 8S8 119 8 256c0 123.5 90.9 225.8 209 245v-173h-63v-72h63v-55
                                c0-62.3 37-96.5 93.7-96.5 27.1 0 55.5 4.8 55.5 4.8v61h-31.2
                                c-30.8 0-40.4 19.1-40.4 38.7v46.1h68.8l-11 72h-57.8v173
                                c118.1-19.2 209-121.5 209-245z"/>
                            </svg>
                        </div>
                    <?} ?>

                </div>
            <?} ?>


            <div class="icon comment <?= $z_msg ?>" title="<?= $lng['w']['comment'] ?>"></div>
            <textarea class="comment_txt"><?= ((isset($restrict_admin_menu[$user_id]['act']['com']['cars']) && $r['loc']==1) ? 'Informatie restrictionata' : $r['txt']) ?></textarea>
            <div class="icon print <?= $z_msg ?>" title="<?= $lng['w']['print'] ?>"></div>
            <div class="print_bx">
                <form target="_blank" action="/print.php" method="post">
                    <input class="none" type="text" name="src" value="adm" />
                    <input class="none" type="text" name="qSd4b_print" value="1" />
                    <select name="loc" class="sel" title="<?= $lng['w']['address'] ?>">
                        <option value="1" <?= (($z_loc==1)?'selected="selected"':'') ?>><?= $lng['t']['x']['address'][1] ?></option>
                        <option value="2" <?= (($z_loc==2)?'selected="selected"':'') ?>><?= $lng['t']['x']['address'][2] ?></option>
                    </select>
                    <select name="drct" class="sel" title="<?= $lng['w']['orientation'] ?>">
                        <option value="v" selected="selected"><?= $lng['w']['vertically'] ?></option>
                        <option value="h"><?= $lng['w']['horizontally'] ?></option>
                    </select>
                    <select name="theme" class="sel" title="<?= $lng['w']['clr_thm'] ?>">
                        <option value="0"><?= $lng['w']['grsc'] ?></option>
                        <option value="1" selected="selected"><?= $lng['w']['clrd'] ?></option>
                    </select>

                    <div class="ttl"><?= $r['br_nm'] ?> <?= $r['mo_nm'] ?>
                        <span class="zx">id: <?= $r['id'] ?></span>
                    </div>

                    <div class="cnt">
                        <?php foreach ($r as $k2 => $v2) :
                            if ( isset($av_k[$k2]) ) : ?>
                                <label <?= ($av_k[$k2] == 1) ? 'class="act"' : '' ?>>
                                    <?php if ($av_k[$k2]==1) : ?>
                                        <span class="ttl"><?= $lng['l']['car']['spec'][$k2] ?></span>
                                    <?php endif; ?>
                                    <input type="text" name="<?= $k2 ?>" value="<?= $v2 ?>" />
                                </label>
                            <?php endif;
                        endforeach; ?>

                        <label class="act">
                            <span class="ttl"><?= $lng['w']['exchange'] ?> [Trade-in]</span>
                            <input type="text" name="exchange" value="<?= ($r['prc']+1000) ?>" />
                        </label>
                        <label class="act">
                            <span class="ttl"><?= $lng['l']['car']['spec']['cons'] ?></span>
                            <input type="text" name="cons" value="" placeholder="L/100" />
                        </label>
                        <label class="act">
                            <span class="ttl"><?= $lng['l']['car']['spec']['tnk'] ?></span>
                            <input type="text" name="tnk" value="" placeholder="L" />
                        </label>
                    </div>

                    <div class="cur none">
                        <?php
                        $pdo = $db->prepare('SELECT * FROM '.$prefx.'_exchange');
                        $pdo->execute();
                        foreach($pdo as $cur) : ?>
                            <input type="text" name="cur_<?= $cur['name'] ?>" value="<?= $cur['value'] ?>" />
                        <?php endforeach; ?>
                    </div>

                    <input class="btn" type="submit" value="<?= $lng['w']['further'] ?>" />
                </form>
            </div>
            <div class="adm_menu">
                <?php if( $r['act'] == 1 ) : ?>
                    <a class="btn edit" href="<?= '/'.$_COOKIE['lang'].'/'.$admin_dir.'/cars/detail?id=' . $r['id'] ?>" title="<?= $lng['adm']['edit'] ?>">
                        <div></div>
                    </a>
                    <div class="btn fn_av" data-fn="<?= ($r['n_a']==0 ? 'av0' : 'av1') ?>" title="<?= ($r['n_a']==0?'Нет в наличии':'Есть в наличии') ?>" data-alt="<?= ($r['n_a']==0 ? '+' : '-') ?>">
                        <div></div>
                    </div>
                    <div class="btn fn_hr" data-fn="<?= ($r['vis']==0 ? 'reveal' : 'hide')?>" title="<?= $lng['adm'][($r['vis']==0 ? 'reveal' : 'hide')] ?>" data-alt="<?= $lng['adm'][($r['vis']==0?'hide':'reveal')] ?>">
                        <div></div>
                    </div>
                    <div class="btn fn_dre" data-fn="delete" title="<?= $lng['adm']['delete'] ?>">
                        <div></div>
                    </div>
                <?php elseif ( $r['act'] == 0 ) : ?>
                    <div class="btn fn_dre" data-fn="restore" title="<?= $lng['adm']['restore'] ?>">
                        <div></div>
                    </div>
                    <div class="btn fn_dre" data-fn="erase" title="<?= $lng['adm']['delete'] ?>">
                        <div></div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="base_info">
                <div class="id" title="id"><?= $r['id'] ?></div>
                <div class="author" title="author"><?= $r['author'] ?></div>
                <div class="views" title="views"> <?= $r['views'] ?> <div class="img"></div> </div>
                <div class="date" title="<?= date('H:i:s', $r['date'])?>"><?= date('d.m.Y', $r['date'])?></div>
            </div>

            <div class="img" style="background-image:url(/<?=_CAR_IMG?>/<?=$r['p_path']?>/<?=$r['id']?>/med/<?=$p_nm . $img_frmt?>), url(/media/images/site/no_image.png);">
                <?php if( $r['act'] == 0 ) : ?>
                    <div class="remove_after" timer="<?= ( $r['del_t']-time() ) ?>" ra="<?= $r['del_t'] ?>">**, **:**:**</div>
                <?php endif; ?>
                <a class="url" href="<?= $site_url.'/'.$_COOKIE['lang'].'/cars/'.$r['id'] ?>" target="_blank" title="To the item page">
                    <div class="ico"></div>
                </a>
                <div class="on_img ghost"><?= $on_img ?></div>
            </div>

            <div class="nm">
                <?php if (!empty($r['br'])) : ?>
                    <span class="br"><?=$r['br_nm']?></span>
                    <span class="mo"><?=$r['mo_nm']?></span>
                <?php elseif (!empty($data999)) : ?>
                    <span class="br"><?=$data999['20']?></span>
                    <span class="mo"><?=$data999['21'] ?? ''?></span>
                    <?php if (!empty($data999['2095'])) : ?>
                        <span class="mo"><?=$data999['2095'] ?? '-'?></span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="info">
                <?php if(!empty($r['br'])) : ?>
                    <?php foreach( [ 'yr'=>['x'=>0], 'vol'=>['x'=>0, 'u'=>'cm3'], 'fl'=>['x'=>1], 'tra'=>['x'=>1] ] as $k => $v ) : ?>
                        <div class="it">
                            <span class="ttl"><?= $lng['l']['car']['spec'][$k] ?? strtoupper($k) ?>: </span>
                            <span class="spc"></span>
                            <span class="val"><?= ( $v['x']==0 ? $r[$k] : ($lng['l']['car'][$k][$r[$k]] ?? $r[$k]) ) . ( isset($v['u']) ? (' ' . $v['u']) : '' ) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php elseif (!empty($data999)): ?>
                    <div class="it">
                        <span class="ttl"><?= __('cars.car_year') ?>: </span>
                        <span class="spc"></span>
                        <span class="val"><?= $data999[19] ?></span>
                    </div>
                    <div class="it">
                        <span class="ttl"><?= __('cars.engine_capacity') ?>: </span>
                        <span class="spc"></span>
                        <span class="val"><?= $data999[103] ?></span>
                    </div>
                    <div class="it">
                        <span class="ttl"><?= __('cars.fuel') ?>: </span>
                        <span class="spc"></span>
                        <span class="val"><?= $data999[151] ?></span>
                    </div>
                    <div class="it">
                        <span class="ttl"><?= __('cars.transmission') ?>: </span>
                        <span class="spc"></span>
                        <span class="val"><?= $data999[101] ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="prc_wrap">
                <?php if( $r['prc'] > 100 ) : ?>
                    <div class="prc" title="<?= $lng['w']['prc'] ?>">
                        <?= $r['prc'] ?> <span><?= $lng['l']['cur'][$r['cur']] ?></span>
                    </div>
                <?php elseif( !empty($data999['price']) ) : ?>
                    <div class="prc">
                        <?= $data999['price']['current_value'] ?> <span><?= $data999['price']['current_unit'] ?></span>
                    </div>
                <?php else : ?>
                    <div class="prc" title="<?= $lng['w']['prc'] ?>" style="font-size:.8rem;">
                        <?= $lng['w']['negociabil'] ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php
        $i++;
        if($i==$i_max){break;}
    endforeach; ?>
</section>

<?php if($has_more_cars && $display_limit != 999) : ?>
    <div id="more_it" data-i="1"><?= $lang_more ?></div>
<?php endif; ?>
<div id="it_cnt" data-count="<?= ($i_max+1) ?>" data-pos="<?= $r['id'] ?>"></div>