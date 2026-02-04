<?php defined( '_DOIT' ) or die( 'Restricted access' ); 

// If this is a 404 page, show 404 content and exit
if (isset($GLOBALS['page_is_404']) && $GLOBALS['page_is_404'] === true) {
    include(_DEFAULT.'/404.php');
    exit;
}
?>
<style>
.payment-amount {
    color: #000;
}
.payment-number {
    color: #ff0000;
    font-weight: bold;
}
.testdrive-page {
    margin: 1.5rem 0 2.5rem;
    display: grid;
    gap: 2.5rem;
}
.testdrive-hero {
    position: relative;
    border-radius: 24px;
    padding: 2rem clamp(1.5rem, 3vw, 3rem);
    display: grid;
    gap: 2rem;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    background: linear-gradient(135deg, #101216 0%, #1b2128 55%, #232a33 100%);
    color: #f5f5f5;
    overflow: hidden;
    box-shadow: 0 24px 45px rgba(15, 17, 20, 0.2);
}
.testdrive-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background: url('/media/images/site/pattern.png') repeat;
    opacity: 0.08;
    pointer-events: none;
}
.testdrive-hero__content {
    position: relative;
    z-index: 1;
    display: grid;
    gap: 1rem;
}
.testdrive-hero__lead {
    font-size: clamp(1rem, 1.5vw, 1.15rem);
    line-height: 1.6;
    color: #e1e5ea;
    margin: 0;
}
.testdrive-hero__cta {
    font-weight: 600;
    color: #fff;
    margin: 0;
}
.testdrive-hero__badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}
.testdrive-hero__badge {
    background: rgba(255, 255, 255, 0.14);
    border-radius: 999px;
    padding: 0.4rem 0.9rem;
    font-size: 0.85rem;
    letter-spacing: 0.02em;
}
.testdrive-hero__visual {
    position: relative;
    z-index: 1;
    display: grid;
    gap: 1rem;
    align-content: center;
}
.testdrive-hero__icon {
    width: min(140px, 60%);
    filter: drop-shadow(0 18px 28px rgba(0, 0, 0, 0.35));
}
.testdrive-hero__photo {
    border-radius: 18px;
    min-height: 160px;
    background-size: cover;
    background-position: center;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
}
.testdrive-hero__photo.is-secondary {
    min-height: 120px;
    opacity: 0.9;
}
.testdrive-divider {
    height: 1px;
    background: linear-gradient(90deg, rgba(0, 0, 0, 0), #d9dde2, rgba(0, 0, 0, 0));
}
.testdrive-section {
    display: grid;
    gap: 1.5rem;
}
.testdrive-section__title {
    font-size: 1.35rem;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.testdrive-benefits {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 1rem;
}
.testdrive-benefit {
    background: #fff;
    border-radius: 16px;
    padding: 1rem 1.1rem;
    display: grid;
    grid-template-columns: 44px 1fr;
    gap: 0.75rem;
    align-items: center;
    box-shadow: 0 18px 30px rgba(17, 24, 39, 0.08);
}
.testdrive-benefit__icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #f1f4f7 var(--icon) no-repeat center / 60%;
}
.testdrive-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
}
.testdrive-step {
    background: #f7f9fb;
    border-radius: 16px;
    padding: 1rem 1.1rem;
    box-shadow: inset 0 0 0 1px #e2e7ec;
    display: grid;
    gap: 0.5rem;
}
.testdrive-step__num {
    font-weight: 700;
    color: #e2001a;
    font-size: 1.1rem;
}
.testdrive-trust {
    background: linear-gradient(135deg, #f6f7f8 0%, #ffffff 100%);
    border-radius: 16px;
    padding: 1.4rem 1.6rem;
    display: grid;
    gap: 0.6rem;
    box-shadow: 0 18px 28px rgba(15, 17, 20, 0.08);
}
.testdrive-trust p {
    margin: 0;
}
.testdrive-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1rem;
}
.testdrive-gallery__item {
    border-radius: 18px;
    overflow: hidden;
    min-height: 200px;
    background-size: cover;
    background-position: center;
    position: relative;
    box-shadow: 0 18px 30px rgba(17, 24, 39, 0.2);
}
.testdrive-gallery__item::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,0.35) 100%);
}
.td-game {
    background: radial-gradient(circle at top, rgba(50, 57, 66, 0.9), #0f1114);
    color: #f5f5f5;
    border-radius: 22px;
    padding: 1.4rem;
    display: grid;
    gap: 1rem;
    box-shadow: 0 25px 45px rgba(15, 17, 20, 0.35);
}
.td-game__header {
    display: grid;
    gap: 0.5rem;
}
.td-game__title {
    font-size: 1.3rem;
    margin: 0;
}
.td-game__hint {
    font-size: 0.95rem;
    color: #d4d7db;
    margin: 0;
}
.td-game__wrap {
    background: #171b20;
    border-radius: 16px;
    padding: 0.9rem;
    display: grid;
    gap: 0.85rem;
}
.td-game__canvas {
    width: 100%;
    height: 380px;
    border-radius: 12px;
    background: #0c0f13;
    display: block;
    touch-action: none;
    border: 1px solid rgba(255, 255, 255, 0.05);
}
.td-game__stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 0.5rem;
    font-size: 0.95rem;
    color: #c9ced4;
}
.td-game__stat {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    padding: 0.45rem 0.7rem;
}
.td-game__progress {
    width: 100%;
    height: 6px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.1);
    overflow: hidden;
}
.td-game__progress span {
    display: block;
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, #f2c94c, #f2994a);
}
.td-game__buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}
.td-game__button {
    background: #f5f5f5;
    border: 0;
    color: #111;
    padding: 0.6rem 1.2rem;
    border-radius: 999px;
    font-weight: 600;
    cursor: pointer;
}
.td-game__button--secondary {
    background: #2a2f36;
    color: #f5f5f5;
}
.td-game__result {
    font-size: 0.95rem;
    color: #d4d7db;
}
@media (max-width: 768px) {
    .td-game__canvas {
        height: 300px;
    }
    .testdrive-section__title {
        font-size: 1.15rem;
    }
    .testdrive-hero {
        padding: 1.5rem;
    }
}
</style>
<?php


//$browserz = get_browser(null, true);
//print_r($browserz);

echo '
<div id="services">';
	if ( !isset($t_mp[3]) ){
		// No service slug provided - show services list
	
		echo '
		<h1 class="ttl">'.( mb_strtoupper( $lng['w']['services'] ) ).'</h1>
		<div class="grp big">
			<a href="/'.$_COOKIE['lang'].'/services/transportation">
				<div class="img" style="background-image:url(/media/images/site/v2/'.$serv_arr['transportation']['img'].');"></div>
				<h3 class="ttl">'.$lng['p']['services']['transportation']['name'].'</h3>
				<div class="txt">'.$lng['p']['services']['transportation']['ttl'].'</div>
			</a>
		</div>
		
		<div class="grp menu">';
			foreach ($serv_arr as $k => $v){
				if ($v['grp']=='menu'){
					echo '
					<a href="/'.$_COOKIE['lang'].'/'.($k=='tradein' ? 'tradein' : 'services/'.$k).'">
						<div class="img" style="background-image:url(/media/images/site/v2/'.$v['img'].');"></div>
						<h3 class="ttl">'.$lng['p']['services'][ $k ]['name'].'</h3>
						<div class="txt">'.$lng['p']['services'][ $k ]['ttl'].'</div>
					</a>';
				}
			}
		echo '
		</div>
		
		<!--
		<h2 class="ttl">'.( mb_strtoupper( $lng['w']['calculate'] ) ).'</h2>
		<div class="grp calc">';
			foreach ($serv_arr as $k => $v){
				if ($v['grp']=='calc'){
					echo '
					<a href="/'.$_COOKIE['lang'].'/services/'.$k.'">
						<div class="img" style="background-image:url(/media/images/site/v2/'.$v['img'].');"></div>
						<h3 class="ttl">'.$lng['p']['services'][ $k ]['name'].'</h3>
						<div class="txt">'.$lng['p']['services'][ $k ]['ttl'].'</div>
					</a>';
				}
			}
		echo '
		</div>
		-->
		
		<h2 class="ttl">'.( mb_strtoupper( $lng['w']['information'] ) ).'</h2>
		<div class="grp info">';
			foreach ($serv_arr as $k => $v){
				if ($v['grp']=='info'){
					echo '
					<a href="/'.$_COOKIE['lang'].'/services/'.$k.'">
						<div class="img" style="background-image:url(/media/images/site/v2/'.$v['img'].');"></div>
						<h3 class="ttl">'.$lng['p']['services'][ $k ]['name'].'</h3>
						<div class="txt">'.$lng['p']['services'][ $k ]['ttl'].'</div>
					</a>';
				}
			}
		echo '
		</div>
		
		';
	
	
	
		/*foreach( $serv_arr as $k => $v ){
			echo '
			<a class="menu" href="/'.$_COOKIE['lang'].'/services/'.$k.'">
				<div class="img">
					<div class="def" style="background-image:url(/media/images/site/v2/'.$v['img'].');"></div>
			';
		}*/
	
	}else{
		// Service slug provided (404 check already done in body.php)
		if ($t_mp[3]!='transportation'){ // credit

            $rtrnCalculatorBlock = "";
		    if($t_mp[2]=='services' && $t_mp[3]=='credit'){ // credit calculator // called in body.php
                $rtrnCalculatorBlock = '
                            <div style="clear: both"> </div>
                            <div class="spc_bx  d_right_b spc_bx_calc_b"> 
                                <div class="calc_head"> '.$lng['w']['calc_title'].' </div>
                                
                                <div class="calc_block_sum">
                                    <!-- title and display of current slider value -->
                                    <div class="calc_inpt_cont">
                                        <div class="calc_ipt_tl">
                                            '.$lng['w']['calc_title_sum_tl'].'
                                        </div>
                                        <div class="calc_inpt_blk">
                                            <!-- display current slider value -->
                                            <input type="text" id="view_suma_creditului"  class="clacl_inpt_vie">
                                        </div>
                                    </div>
                                    <!-- slider element -->
                                    <input type="text" id="suma-creditului" >
                                </div>
                                    
                                <div class="calc_block_terms">
                                    <!-- title and display of current slider value -->
                                    <div class="calc_inpt_cont">
                                        <div class="calc_ipt_tl">
                                            '.$lng['w']['calc_title_term_tl'].'
                                        </div>
                                        <div class="calc_inpt_blk">
                                            <!-- display current slider value -->
                                            <input type="text" id="view_termen_creditului"  class="clacl_inpt_vie">
                                        </div>
                                    </div>
                                        <!-- slider element -->
                                    <input type="text" id="termen-creditului" name="termen_creditului">
                                </div>
                                
                                <div style="clear: both"> </div>
                                
                                <!-- display calculator results -->
                                <div class="calc_btt_word">
                                    <div class="calc_btt_left">
                                        '.$lng['w']['calc_title_rata'].'
                                    </div>
                                    <div class="calc_btt_right">
                                        <div class="calc_btt_r1">
                                            <span class="calc_btt_r1_nrl">60</span> '.$lng['w']['calc_title_luni'].'
                                        </div>
                                        <div class="calc_btt_r2">
                                            '.'<div class="payment-amount">'.$lng['w']['calc_title_plata'].' <span class="payment-number calc_btt_r2_nrl">0</span> '.$lng['w']['calc_title_plata2'].' <span class="payment-number calc_btt_r3_nrl">0</span></div>'.'
                                        </div>
                                    </div>
                                </div> 
                                
                                <div style="clear: both"> </div>
                                
                            </div> ';
                /*
                 * <div class="calc_btn_btt">
                                    <div class="calc_btn_point">
                                        '.$lng['w']['calc_title_btn'].'
                                    </div>
                                </div>
                 * */
            }

			// Load language file specific for insurance
if ($t_mp[3] == 'insurance') {
			include_once($_SERVER['DOCUMENT_ROOT'] . '/content/default/lang-insurance.php');
			include_once($_SERVER['DOCUMENT_ROOT'] . '/plugins/dev_tools/meta_gen.php');
			echo '
			<img class="m_img" src="/media/images/site/v2/'.$serv_arr[ $t_mp[3] ]['img'].'" />
			<h1>'.$sa['meta']['h1'].'</h1>';
			include_once($_SERVER['DOCUMENT_ROOT'] . '/content/site/page/insurance.php');
		} else {
	                        if ($t_mp[3] == 'tradein') {
	                                $lang = isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'ro';
	                                header('Location: /' . $lang . '/tradein', true, 301);
	                                exit;
	                        } elseif ($t_mp[3] == 'sale') {
	                                include(_SITE_PAGE.'/new_pages/sale/sale.php');
	                        } else {
	                                $lang_code = isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'ro';
	                                if ($t_mp[3] == 'testdrive' && $lang_code == 'ru') {
	                                    $serv_img = $serv_arr[$t_mp[3]]['img'];
	                                    $serv_h1 = $sa['meta']['h1'];
	                                    echo <<<HTML
	                                    <img class="m_img" src="/media/images/site/v2/{$serv_img}" />
	                                    <h1>{$serv_h1}</h1>
	                                    <div class="testdrive-page">
	                                        <section class="testdrive-hero">
	                                            <div class="testdrive-hero__content">
	                                                <p class="testdrive-hero__lead">Сомневаешься между моделями? Хочешь понять посадку, обзорность, динамику, багажник, тормоза? Это решается за 15 минут на реальной дороге — без гаданий и «потом разберусь».</p>
	                                                <p class="testdrive-hero__cta">Записывайся и приезжай — мы подготовим авто и маршрут.</p>
	                                                <div class="testdrive-hero__badges">
	                                                    <span class="testdrive-hero__badge">15–20 минут на маршруте</span>
	                                                    <span class="testdrive-hero__badge">Город + нормальная дорога</span>
	                                                    <span class="testdrive-hero__badge">Без давления</span>
	                                                </div>
	                                            </div>
	                                            <div class="testdrive-hero__visual">
	                                                <img class="testdrive-hero__icon" src="/media/images/site/v2/{$serv_img}" alt="Тест-драйв SAUTO">
	                                                <div class="testdrive-hero__photo" style="background-image:url('/media/images/site/v2/transportation_img.jpg')"></div>
	                                                <div class="testdrive-hero__photo is-secondary" style="background-image:url('/media/images/site/services/bnr_order.jpg')"></div>
	                                            </div>
	                                        </section>
	                                        <div class="testdrive-divider"></div>
	                                        <section class="testdrive-section">
	                                            <h2 class="testdrive-section__title">Зачем тест-драйв</h2>
	                                            <div class="testdrive-benefits">
	                                                <div class="testdrive-benefit" style="--icon:url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23e2001a%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><circle cx=%2212%22 cy=%2212%22 r=%229%22/><path d=%22M12 7v5l3 3%22/></svg>');">
	                                                    <div class="testdrive-benefit__icon"></div>
	                                                    <div>Посадка и удобство: руль, сиденье, зеркала.</div>
	                                                </div>
	                                                <div class="testdrive-benefit" style="--icon:url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23e2001a%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><path d=%22M2 12s4-6 10-6 10 6 10 6-4 6-10 6S2 12 2 12z%22/><circle cx=%2212%22 cy=%2212%22 r=%223%22/></svg>');">
	                                                    <div class="testdrive-benefit__icon"></div>
	                                                    <div>Обзорность и габариты: парковка без мата.</div>
	                                                </div>
	                                                <div class="testdrive-benefit" style="--icon:url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23e2001a%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><path d=%22M4 16h16%22/><path d=%22M6 16l4-6 4 6 4-8%22/></svg>');">
	                                                    <div class="testdrive-benefit__icon"></div>
	                                                    <div>Подвеска: как переживает молдавскую реальность.</div>
	                                                </div>
	                                                <div class="testdrive-benefit" style="--icon:url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23e2001a%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><path d=%22M3 12h18%22/><path d=%22M12 3l6 9-6 9%22/></svg>');">
	                                                    <div class="testdrive-benefit__icon"></div>
	                                                    <div>Динамика и тормоза в живом режиме.</div>
	                                                </div>
	                                                <div class="testdrive-benefit" style="--icon:url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23e2001a%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><path d=%22M3 11v2a9 9 0 0 0 18 0v-2%22/><path d=%22M8 7h8%22/></svg>');">
	                                                    <div class="testdrive-benefit__icon"></div>
	                                                    <div>Шумоизоляция без «на словах».</div>
	                                                </div>
	                                                <div class="testdrive-benefit" style="--icon:url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23e2001a%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><rect x=%223%22 y=%227%22 width=%2218%22 height=%2212%22 rx=%222%22/><path d=%22M7 7V5h10v2%22/></svg>');">
	                                                    <div class="testdrive-benefit__icon"></div>
	                                                    <div>Багажник вживую, а не по цифрам.</div>
	                                                </div>
	                                                <div class="testdrive-benefit" style="--icon:url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23e2001a%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><path d=%22M12 2v20%22/><path d=%22M5 12h14%22/></svg>');">
	                                                    <div class="testdrive-benefit__icon"></div>
	                                                    <div>Понимание «мое / не мое» за 10 минут.</div>
	                                                </div>
	                                            </div>
	                                        </section>
	                                        <section class="testdrive-section">
	                                            <h2 class="testdrive-section__title">Как это проходит</h2>
	                                            <div class="testdrive-steps">
	                                                <div class="testdrive-step">
	                                                    <div class="testdrive-step__num">01</div>
	                                                    <div>Выбираешь авто (или мы рекомендуем 2–3 варианта).</div>
	                                                </div>
	                                                <div class="testdrive-step">
	                                                    <div class="testdrive-step__num">02</div>
	                                                    <div>Запись на удобное время и короткий созвон.</div>
	                                                </div>
	                                                <div class="testdrive-step">
	                                                    <div class="testdrive-step__num">03</div>
	                                                    <div>Приезжаешь в SAUTO, быстрый инструктаж.</div>
	                                                </div>
	                                                <div class="testdrive-step">
	                                                    <div class="testdrive-step__num">04</div>
	                                                    <div>Маршрут 10–20 минут: город + нормальная дорога.</div>
	                                                </div>
	                                                <div class="testdrive-step">
	                                                    <div class="testdrive-step__num">05</div>
	                                                    <div>Возвращаешься — сравниваем и отвечаем на вопросы.</div>
	                                                </div>
	                                            </div>
	                                        </section>
	                                        <section class="testdrive-trust">
	                                            <p>Подскажем по выбору без давления. Тест-драйв — чтобы ты сам понял.</p>
	                                            <p>Никаких обещаний «лучше всех». Просто честно и по делу.</p>
	                                        </section>
	                                        <section class="testdrive-gallery">
	                                            <div class="testdrive-gallery__item" style="background-image:url('/media/images/site/v2/transportation_img.jpg')"></div>
	                                            <div class="testdrive-gallery__item" style="background-image:url('/media/images/site/services/bnr_order.jpg')"></div>
	                                        </section>
	                                        <section class="td-game" aria-labelledby="virtual-testdrive-title">
	                                            <div class="td-game__header">
	                                                <h2 class="td-game__title" id="virtual-testdrive-title">Виртуальный тест-драйв</h2>
	                                                <p class="td-game__hint">Удержи машину по центру, собирай значки и избегай препятствий. Управление — влево/вправо мышью, пальцем или стрелками.</p>
	                                            </div>
	                                            <div class="td-game__wrap">
	                                                <canvas class="td-game__canvas" id="testdrive-canvas"></canvas>
	                                                <div class="td-game__stats">
	                                                    <div class="td-game__stat">Время: <span id="td-time">0.0</span> сек</div>
	                                                    <div class="td-game__stat">Ошибки: <span id="td-errors">0</span></div>
	                                                    <div class="td-game__stat">Значки: <span id="td-pickups">0</span>/3</div>
	                                                    <div class="td-game__stat">Очки: <span id="td-score">0</span></div>
	                                                </div>
	                                                <div class="td-game__progress"><span id="td-progress-bar"></span></div>
	                                                <div class="td-game__buttons">
	                                                    <button class="td-game__button" id="td-start" type="button">Старт</button>
	                                                    <button class="td-game__button td-game__button--secondary" id="td-restart" type="button">Заново</button>
	                                                </div>
	                                                <div class="td-game__result" id="td-result">Проведи 20–30 секунд как на тест-драйве — почувствуй темп.</div>
	                                            </div>
	                                        </section>
	                                        <section class="testdrive-section">
	                                            <p>Ок, виртуально ты доехал. В реале интереснее — записывайся на тест-драйв через кнопки сайта или контакты.</p>
	                                        </section>
	                                    </div>
	                                    <script>
	                                    (() => {
	                                        const canvas = document.getElementById('testdrive-canvas');
	                                        if (!canvas) return;
	                                        const ctx = canvas.getContext('2d');
	                                        const timeEl = document.getElementById('td-time');
	                                        const errorsEl = document.getElementById('td-errors');
	                                        const pickupsEl = document.getElementById('td-pickups');
	                                        const scoreEl = document.getElementById('td-score');
	                                        const resultEl = document.getElementById('td-result');
	                                        const progressBar = document.getElementById('td-progress-bar');
	                                        const startBtn = document.getElementById('td-start');
	                                        const restartBtn = document.getElementById('td-restart');

	                                        const state = {
	                                            running: false,
	                                            finished: false,
	                                            startTime: 0,
	                                            lastTime: 0,
	                                            errors: 0,
	                                            pickups: 0,
	                                            score: 0,
	                                            distance: 0,
	                                            targetDistance: 2600,
	                                            speed: 0,
	                                            baseSpeed: 220,
	                                            maxSpeed: 360,
	                                            stripeOffset: 0,
	                                            flash: 0,
	                                            pointerActive: false,
	                                            carX: 0.5,
	                                            targetX: 0.5,
	                                            spawnTimer: 0,
	                                            audioReady: false
	                                        };

	                                        const road = {
	                                            widthRatio: 0.62,
	                                            left: 0,
	                                            right: 0,
	                                            laneCount: 3,
	                                            laneWidth: 0
	                                        };

	                                        const car = { width: 26, height: 48 };
	                                        const obstacles = [];
	                                        const pickups = [];
	                                        const keys = new Set();
	                                        const pickupLabels = ['Руль', 'Тормоз', 'Багажник'];

	                                        let audioCtx;

	                                        const clamp = (val, min, max) => Math.min(Math.max(val, min), max);

	                                        const setupAudio = () => {
	                                            if (state.audioReady) return;
	                                            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
	                                            state.audioReady = true;
	                                        };

	                                        const playTone = (frequency, duration = 0.12) => {
	                                            if (!state.audioReady || !audioCtx) return;
	                                            const oscillator = audioCtx.createOscillator();
	                                            const gain = audioCtx.createGain();
	                                            oscillator.frequency.value = frequency;
	                                            oscillator.type = 'sine';
	                                            gain.gain.value = 0.06;
	                                            oscillator.connect(gain);
	                                            gain.connect(audioCtx.destination);
	                                            oscillator.start();
	                                            oscillator.stop(audioCtx.currentTime + duration);
	                                        };

	                                        const resize = () => {
	                                            const ratio = window.devicePixelRatio || 1;
	                                            const rect = canvas.getBoundingClientRect();
	                                            canvas.width = rect.width * ratio;
	                                            canvas.height = rect.height * ratio;
	                                            ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
	                                            road.left = rect.width * (1 - road.widthRatio) / 2;
	                                            road.right = rect.width - road.left;
	                                            road.laneWidth = (road.right - road.left) / road.laneCount;
	                                            reset();
	                                        };

	                                        const reset = () => {
	                                            state.running = false;
	                                            state.finished = false;
	                                            state.startTime = 0;
	                                            state.lastTime = 0;
	                                            state.errors = 0;
	                                            state.pickups = 0;
	                                            state.score = 0;
	                                            state.distance = 0;
	                                            state.speed = 0;
	                                            state.stripeOffset = 0;
	                                            state.flash = 0;
	                                            state.carX = 0.5;
	                                            state.targetX = 0.5;
	                                            obstacles.length = 0;
	                                            pickups.length = 0;
	                                            updateUI();
	                                            draw();
	                                        };

	                                        const updateUI = () => {
	                                            errorsEl.textContent = state.errors;
	                                            pickupsEl.textContent = state.pickups;
	                                            scoreEl.textContent = Math.max(0, Math.floor(state.score));
	                                            const progress = clamp(state.distance / state.targetDistance, 0, 1);
	                                            progressBar.style.width = (progress * 100).toFixed(0) + '%';
	                                        };

	                                        const start = () => {
	                                            if (state.running) return;
	                                            setupAudio();
	                                            state.running = true;
	                                            state.finished = false;
	                                            state.startTime = performance.now();
	                                            state.lastTime = state.startTime;
	                                            resultEl.textContent = 'Держись дороги, собирай значки и избегай препятствий.';
	                                        };

	                                        const finish = () => {
	                                            state.running = false;
	                                            state.finished = true;
	                                            const success = state.errors <= 3;
	                                            if (success) {
	                                                resultEl.textContent = 'Ты аккуратный водитель. В реале станет еще понятнее — записывайся на тест-драйв.';
	                                            } else {
	                                                resultEl.textContent = 'Тем более нужен тест-драйв — проверим, как оно в реальности.';
	                                            }
	                                        };

	                                        const spawnObstacle = (height) => {
	                                            const lane = Math.floor(Math.random() * road.laneCount);
	                                            const x = road.left + road.laneWidth * (lane + 0.5);
	                                            const type = Math.random() > 0.6 ? 'barrier' : 'cone';
	                                            obstacles.push({ x, y: -40, type, width: type === 'barrier' ? 44 : 24, height: 30 });
	                                        };

	                                        const spawnPickup = () => {
	                                            if (state.pickups + pickups.length >= 3) return;
	                                            const lane = Math.floor(Math.random() * road.laneCount);
	                                            const x = road.left + road.laneWidth * (lane + 0.5);
	                                            pickups.push({ x, y: -60, radius: 16, label: pickupLabels[(state.pickups + pickups.length) % 3] });
	                                        };

	                                        const update = (timestamp) => {
	                                            if (!state.running) {
	                                                draw();
	                                                return;
	                                            }
	                                            const delta = (timestamp - state.lastTime) / 1000;
	                                            state.lastTime = timestamp;
	                                            const elapsed = (timestamp - state.startTime) / 1000;
	                                            timeEl.textContent = elapsed.toFixed(1);

	                                            state.speed = clamp(state.speed + 180 * delta, 0, state.maxSpeed);
	                                            const actualSpeed = Math.max(state.baseSpeed, state.speed);
	                                            state.distance += actualSpeed * delta;
	                                            state.score += actualSpeed * delta * 0.4;
	                                            state.stripeOffset = (state.stripeOffset + actualSpeed * delta) % 60;
	                                            state.spawnTimer -= delta;

	                                            if (state.spawnTimer <= 0) {
	                                                spawnObstacle(canvas.getBoundingClientRect().height);
	                                                if (Math.random() > 0.45) {
	                                                    spawnPickup();
	                                                }
	                                                state.spawnTimer = 0.55 + Math.random() * 0.5;
	                                            }

	                                            const roadWidth = road.right - road.left;
	                                            const carY = canvas.getBoundingClientRect().height * 0.72;
	                                            state.carX += (state.targetX - state.carX) * clamp(delta * 6, 0, 1);
	                                            state.carX = clamp(state.carX, 0.08, 0.92);
	                                            const carXpx = road.left + roadWidth * state.carX;

	                                            obstacles.forEach((obstacle) => {
	                                                obstacle.y += actualSpeed * delta;
	                                            });
	                                            pickups.forEach((pickup) => {
	                                                pickup.y += actualSpeed * delta * 0.95;
	                                            });

	                                            for (let i = obstacles.length - 1; i >= 0; i -= 1) {
	                                                const obstacle = obstacles[i];
	                                                if (obstacle.y > canvas.getBoundingClientRect().height + 60) {
	                                                    obstacles.splice(i, 1);
	                                                    continue;
	                                                }
	                                                const hitX = Math.abs(obstacle.x - carXpx) < (car.width / 2 + obstacle.width / 2);
	                                                const hitY = Math.abs(obstacle.y - carY) < (car.height / 2 + obstacle.height / 2);
	                                                if (hitX && hitY) {
	                                                    state.errors += 1;
	                                                    state.score = Math.max(0, state.score - 80);
	                                                    state.flash = 1;
	                                                    if (navigator.vibrate) {
	                                                        navigator.vibrate(50);
	                                                    }
	                                                    obstacles.splice(i, 1);
	                                                }
	                                            }

	                                            for (let i = pickups.length - 1; i >= 0; i -= 1) {
	                                                const pickup = pickups[i];
	                                                if (pickup.y > canvas.getBoundingClientRect().height + 60) {
	                                                    pickups.splice(i, 1);
	                                                    continue;
	                                                }
	                                                const hitX = Math.abs(pickup.x - carXpx) < (car.width / 2 + pickup.radius);
	                                                const hitY = Math.abs(pickup.y - carY) < (car.height / 2 + pickup.radius);
	                                                if (hitX && hitY) {
	                                                    state.pickups += 1;
	                                                    state.score += 150;
	                                                    playTone(660);
	                                                    pickups.splice(i, 1);
	                                                }
	                                            }

	                                            if (state.flash > 0) {
	                                                state.flash = Math.max(0, state.flash - delta * 2);
	                                            }

	                                            const progress = state.distance / state.targetDistance;
	                                            if (progress >= 1) {
	                                                if (state.pickups >= 2) {
	                                                    finish();
	                                                } else {
	                                                    resultEl.textContent = 'Собери минимум 2 значка перед финишем.';
	                                                }
	                                            }

	                                            updateUI();
	                                            draw();
	                                        };

	                                        const draw = () => {
	                                            const rect = canvas.getBoundingClientRect();
	                                            const width = rect.width;
	                                            const height = rect.height;
	                                            const roadWidth = road.right - road.left;
	                                            const carY = height * 0.72;
	                                            const carXpx = road.left + roadWidth * state.carX;

	                                            ctx.clearRect(0, 0, width, height);
	                                            ctx.fillStyle = '#0d1014';
	                                            ctx.fillRect(0, 0, width, height);

	                                            ctx.fillStyle = '#1a1f25';
	                                            ctx.fillRect(road.left, 0, roadWidth, height);

	                                            ctx.strokeStyle = '#2c323a';
	                                            ctx.lineWidth = 2;
	                                            for (let i = 1; i < road.laneCount; i += 1) {
	                                                const laneX = road.left + road.laneWidth * i;
	                                                ctx.setLineDash([18, 16]);
	                                                ctx.beginPath();
	                                                ctx.moveTo(laneX, -state.stripeOffset);
	                                                ctx.lineTo(laneX, height + 60);
	                                                ctx.stroke();
	                                            }
	                                            ctx.setLineDash([]);

	                                            ctx.strokeStyle = '#f2c94c';
	                                            ctx.lineWidth = 4;
	                                            ctx.beginPath();
	                                            ctx.moveTo(road.left + 2, 0);
	                                            ctx.lineTo(road.left + 2, height);
	                                            ctx.moveTo(road.right - 2, 0);
	                                            ctx.lineTo(road.right - 2, height);
	                                            ctx.stroke();

	                                            obstacles.forEach((obstacle) => {
	                                                if (obstacle.type === 'barrier') {
	                                                    ctx.fillStyle = '#e2001a';
	                                                    ctx.fillRect(obstacle.x - obstacle.width / 2, obstacle.y - obstacle.height / 2, obstacle.width, obstacle.height);
	                                                    ctx.fillStyle = '#f5f5f5';
	                                                    ctx.fillRect(obstacle.x - obstacle.width / 2, obstacle.y - 4, obstacle.width, 8);
	                                                } else {
	                                                    ctx.fillStyle = '#ff7a00';
	                                                    ctx.beginPath();
	                                                    ctx.moveTo(obstacle.x, obstacle.y - 14);
	                                                    ctx.lineTo(obstacle.x - 12, obstacle.y + 14);
	                                                    ctx.lineTo(obstacle.x + 12, obstacle.y + 14);
	                                                    ctx.closePath();
	                                                    ctx.fill();
	                                                }
	                                            });

	                                            pickups.forEach((pickup, index) => {
	                                                const pulse = 1 + Math.sin((performance.now() / 200) + index) * 0.08;
	                                                ctx.fillStyle = '#5ad1ff';
	                                                ctx.beginPath();
	                                                ctx.arc(pickup.x, pickup.y, pickup.radius * pulse, 0, Math.PI * 2);
	                                                ctx.fill();
	                                                ctx.fillStyle = '#0f1114';
	                                                ctx.font = '11px sans-serif';
	                                                ctx.fillText(pickup.label[0], pickup.x - 3, pickup.y + 4);
	                                            });

	                                            ctx.save();
	                                            ctx.translate(carXpx, carY);
	                                            ctx.fillStyle = '#f5f5f5';
	                                            ctx.fillRect(-car.width / 2, -car.height / 2, car.width, car.height);
	                                            ctx.fillStyle = '#1b1f24';
	                                            ctx.fillRect(-car.width / 2 + 4, -car.height / 2 + 6, car.width - 8, car.height - 20);
	                                            ctx.fillStyle = '#e2001a';
	                                            ctx.fillRect(-car.width / 2 + 4, car.height / 2 - 12, car.width - 8, 8);
	                                            ctx.restore();

	                                            if (state.flash > 0) {
	                                                ctx.fillStyle = `rgba(226, 0, 26, ${state.flash * 0.35})`;
	                                                ctx.fillRect(0, 0, width, height);
	                                            }
	                                        };

	                                        const setPointer = (event) => {
	                                            const rect = canvas.getBoundingClientRect();
	                                            const x = (event.clientX - rect.left - road.left) / (road.right - road.left);
	                                            state.targetX = clamp(x, 0.08, 0.92);
	                                        };

	                                        startBtn.addEventListener('click', start);
	                                        restartBtn.addEventListener('click', reset);
	                                        window.addEventListener('resize', resize);

	                                        document.addEventListener('keydown', (event) => {
	                                            keys.add(event.key.toLowerCase());
	                                        });
	                                        document.addEventListener('keyup', (event) => {
	                                            keys.delete(event.key.toLowerCase());
	                                        });

	                                        const handleKeyMovement = (delta) => {
	                                            if (keys.has('arrowleft') || keys.has('a')) {
	                                                state.targetX -= delta;
	                                            }
	                                            if (keys.has('arrowright') || keys.has('d')) {
	                                                state.targetX += delta;
	                                            }
	                                            state.targetX = clamp(state.targetX, 0.08, 0.92);
	                                        };

	                                        const loop = (timestamp) => {
	                                            handleKeyMovement(0.02);
	                                            update(timestamp);
	                                            requestAnimationFrame(loop);
	                                        };

	                                        canvas.addEventListener('pointerdown', (event) => {
	                                            state.pointerActive = true;
	                                            setPointer(event);
	                                            start();
	                                        });
	                                        canvas.addEventListener('pointermove', (event) => {
	                                            if (!state.pointerActive) return;
	                                            setPointer(event);
	                                        });
	                                        canvas.addEventListener('pointerup', () => {
	                                            state.pointerActive = false;
	                                        });
	                                        canvas.addEventListener('pointerleave', () => {
	                                            state.pointerActive = false;
	                                        });

	                                        resize();
	                                        reset();
	                                        requestAnimationFrame(loop);
	                                    })();
	                                    </script>
HTML;
	                                } else {
	                                    echo '
	                                    <img class="m_img" src="/media/images/site/v2/'.$serv_arr[ $t_mp[3] ]['img'].'" />
				            <h1>'.$sa['meta']['h1'].'</h1>
				            '. $rtrnCalculatorBlock .'
				            <h2 class="ttl">'.$lng['p']['services'][ $t_mp[3] ]['ttl'].'</h2>
				            <div class="txt">'.(isset($lang_offers[ $t_mp[3] ]['text']) ? $lang_offers[ $t_mp[3] ]['text'] : '').'</div>';
	                                }
			}
		}
		}
		
		if ($t_mp[3]=='order'){
			$pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_ctlg ORDER BY `br` ASC');
			$pdo->execute();
			foreach ($pdo as $r){	$c_brand[ $r['br'] ] = $r['br_nm']; }
			
			echo '
			<img src="/media/images/site/services/bnr_'.$t_mp[3].'.jpg" style="width:100%; margin:3rem 0; padding:0 2rem;" />
			
			<div class="func" style="display: none">
				<div class="ready"><b>'./*$gSdk3pF_sent.*/'</b></div>
				<div class="line"></div>
				<select name="brand" class="item brand need" tabindex="1"> <option value="">'.mb_strtoupper($lang_brand, "UTF-8").'</option>';
					foreach ($c_brand as $k => $v){ echo '<option value="'.$k.'">'.$v.'</option>'; }
				echo '
				</select>
				<select name="model" class="item model need" def_text="'.mb_strtoupper($lang_model, "UTF-8").'" tabindex="2"><option value="">'.mb_strtoupper($lang_model, "UTF-8").'</option></select>
				<select name="fuel" class="item fuel need" tabindex="8"> <option value="">'.mb_strtoupper($lang_fuel, "UTF-8").'</option>';
					foreach ($info_fuel as $k => $v){ echo '<option value="'.$k.'">'.$v.'</option>'; }
				echo '
				</select>
				<input name="year" class="item numInput no_need" size="16" tabindex="3" placeholder="'.mb_strtoupper($lang_year, "UTF-8").'" type="number" min="1900" max="'.(date('Y')).'" title="'.mb_strtoupper($lang_year, "UTF-8").'">
				<input name="engine" class="item numInput no_need" size="16" tabindex="7" placeholder="'.mb_strtoupper($lang_engine, "UTF-8").' cm³" type="number" min="1" max="10000" title="'.mb_strtoupper($lang_engine, "UTF-8").'">
				<textarea tabindex="14" name="xtra_info" class="no_need" cols="84" rows="5" spellcheck="false" placeholder="'.$lang_offers_order_textarea.'"></textarea>
				<input name="name" class="person need" size="16" tabindex="7" placeholder="'.mb_strtoupper($lang_your_name, "UTF-8").'" type="text" title="'.mb_strtoupper($lang_your_name, "UTF-8").'">
				<input name="phone" class="person numInput no_need" size="16" tabindex="7" placeholder="'.mb_strtoupper($lang_your_phone, "UTF-8").'" type="text" title="'.mb_strtoupper($lang_your_phone, "UTF-8").'">
				<input name="email" class="person need" size="16" tabindex="7" placeholder="EMAIL" type="text" title="EMAIL">
				<input type="button" value="'.$lang_send.'" class="order_submit">
				<input type="text" name="page" class="need" value="'.$_SERVER['REQUEST_URI'].'" style="display:none;" />
				<div class="line"></div>
			</div>';
					?>
            <script data-b24-form="inline/10/rh1qfd" data-skip-moving="true">
                (function(w,d,u){
                    var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/180000|0);
                    var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
                })(window,document,'https://cdn-ru.bitrix24.ru/b33145896/crm/form/loader_10.js');
            </script>
            <?php
		}elseif ($t_mp[3]=='transportation'){

			$transportPhone = \App\Helper\PhoneHelper::getGeneralPhone();
			$formattedTransportPhone = \App\Helper\PhoneHelper::formatPhone($transportPhone, 'display');
			
			echo '
			<div class="transportation">
				<h1>'.$lng['t']['services']['transportation']['name'].'</h1>
				<a href="tel:'.$transportPhone.'" class="trnsprt_call" title="'.$lng['w']['call'].'">'.$formattedTransportPhone.'</a>
				<img class="m_img" src="/media/images/site/v2/transportation_img.jpg" />
				<h2>'.$lng['t']['services']['transportation']['ttl_1'].'</h2>
				<div class="txt">'.$lng['t']['services']['transportation']['txt_1'].'</div>
				
				<div class="ln"></div>
				
				<div class="blk">
					<h2>'.$lng['t']['services']['transportation']['ttl_2'].'</h2>
					'.$lng['t']['services']['transportation']['txt_2'].'
					<div class="abv">'.$lng['t']['services']['transportation']['txt_3'].'</div>
				</div>
				<div class="call_now">
					<img src="/media/images/site/call_now.jpg" />
					<h3>'.$lng['t']['services']['transportation']['txt_4'].'<br/><a href="tel:'.$transportPhone.'">'.$formattedTransportPhone.'</a></h3>
				</div>
			</div>';

		}
		
		if ( in_array($t_mp[3], ['credit', 'transportation']) ){
			echo '<img src="/media/images/site/services/bnr_'.$t_mp[3].'.jpg" style="width:100%; margin:3rem 0; padding:0 2rem;" />';
		}
	}
echo '
</div>';

?>
