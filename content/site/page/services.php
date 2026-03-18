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
    background: radial-gradient(circle at top left, rgba(248, 244, 236, 0.9), rgba(233, 236, 240, 0.6) 55%, rgba(245, 246, 247, 0.9));
    border-radius: 24px;
    padding: 2.5rem;
    display: grid;
    gap: 1.5rem;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    align-items: center;
    position: relative;
    overflow: hidden;
    box-shadow: 0 18px 40px rgba(15, 17, 20, 0.08);
}
.testdrive-hero::after {
    content: '';
    position: absolute;
    right: -20%;
    top: -35%;
    width: 60%;
    height: 120%;
    background: radial-gradient(circle, rgba(255, 200, 82, 0.18), transparent 70%);
    pointer-events: none;
}
.testdrive-hero__content {
    display: grid;
    gap: 0.75rem;
    position: relative;
    z-index: 1;
}
.testdrive-hero__title {
    font-size: clamp(1.8rem, 2.8vw, 2.6rem);
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    color: #111;
}
.testdrive-hero__media {
    position: relative;
    z-index: 1;
    display: grid;
    justify-items: end;
}
.testdrive-hero__media img {
    width: min(100%, 420px);
    border-radius: 18px;
    box-shadow: 0 16px 32px rgba(0, 0, 0, 0.2);
    object-fit: cover;
    aspect-ratio: 4 / 3;
}
.testdrive-hero__lead {
    font-size: 1.05rem;
    line-height: 1.55;
    color: #1b1b1b;
}
.testdrive-hero__cta {
    font-weight: 600;
    color: #111;
}
.testdrive-section {
    display: grid;
    gap: 1rem;
}
.testdrive-section__title {
    font-size: 1.3rem;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}
.testdrive-features {
    margin: 0;
    padding: 0;
    list-style: none;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}
.testdrive-card {
    background: #ffffff;
    border-radius: 18px;
    padding: 1rem 1.1rem;
    display: grid;
    gap: 0.5rem;
    box-shadow: 0 12px 24px rgba(15, 17, 20, 0.08);
    border: 1px solid rgba(15, 17, 20, 0.05);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.testdrive-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 32px rgba(15, 17, 20, 0.12);
}
.testdrive-card__icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: #f2c94c;
    display: grid;
    place-items: center;
    color: #111;
}
.testdrive-card__text {
    font-size: 0.95rem;
    margin: 0;
    color: #1c2127;
}
.testdrive-steps {
    margin: 0;
    padding: 0;
    list-style: none;
    display: grid;
    gap: 0.9rem;
}
.testdrive-step {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 0.9rem;
    align-items: start;
    padding: 0.85rem 1rem;
    border-radius: 16px;
    background: #f7f7f8;
    border: 1px solid rgba(15, 17, 20, 0.05);
}
.testdrive-step__index {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #111;
    color: #fff;
    display: grid;
    place-items: center;
    font-weight: 600;
}
.testdrive-step__text {
    margin: 0;
    color: #1c2127;
    line-height: 1.45;
}
.testdrive-trust {
    background: #f6f7f8;
    border-radius: 18px;
    padding: 1.5rem 1.8rem;
    display: grid;
    gap: 0.6rem;
}
.testdrive-trust p {
    margin: 0;
}
.testdrive-trust__headline {
    font-weight: 600;
    color: #111;
}
.testdrive-trust__subtitle {
    color: #32363d;
}
.td-game {
    background: #0f1114;
    color: #f5f5f5;
    border-radius: 24px;
    padding: 1.5rem;
    display: grid;
    gap: 1.25rem;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
}
.td-game__header {
    display: grid;
    gap: 0.5rem;
}
.td-game__title {
    font-size: 1.25rem;
    margin: 0;
}
.td-game__hint {
    font-size: 0.95rem;
    color: #d4d7db;
    margin: 0;
}
.td-game__wrap {
    background: #1b1f24;
    border-radius: 18px;
    padding: 1rem;
    display: grid;
    gap: 1rem;
}
.td-game__canvas-wrap {
    position: relative;
    border-radius: 14px;
    overflow: hidden;
}
.td-game__canvas {
    width: 100%;
    height: 420px;
    background: #13161a;
    display: block;
    touch-action: none;
}
.td-game__overlay {
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    text-align: center;
    padding: 1.5rem;
    background: linear-gradient(135deg, rgba(15, 17, 20, 0.65), rgba(15, 17, 20, 0.4));
    color: #f5f5f5;
    gap: 0.5rem;
}
.td-game__overlay-title {
    font-size: 1.3rem;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.td-game__overlay-text {
    margin: 0;
    color: #d4d7db;
    font-size: 0.95rem;
}
.td-game__stats {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem 1.5rem;
    font-size: 0.95rem;
    color: #c9ced4;
}
.td-game__stat {
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.td-game__badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.2rem 0.6rem;
    border-radius: 999px;
    background: rgba(245, 245, 245, 0.08);
    font-size: 0.85rem;
    color: #f5f5f5;
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
    padding: 0.7rem 1.4rem;
    border-radius: 999px;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
}
.td-game__button:hover {
    transform: translateY(-2px);
}
.td-game__button--secondary {
    background: #2a2f36;
    color: #f5f5f5;
}
.td-game__result {
    font-size: 0.95rem;
    color: #d4d7db;
}
.td-game__result-overlay {
    position: absolute;
    inset: 0;
    display: none;
    place-items: center;
    text-align: center;
    padding: 1.5rem;
    background: rgba(15, 17, 20, 0.8);
    color: #f5f5f5;
    gap: 0.6rem;
}
.td-game__result-overlay.is-visible {
    display: grid;
}
.td-game__result-title {
    font-size: 1.2rem;
    margin: 0;
}
.td-game__result-meta {
    color: #d4d7db;
    font-size: 0.9rem;
}
.td-game__result-message {
    font-size: 0.95rem;
    margin: 0;
    color: #f5f5f5;
}
.td-game__result-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    justify-content: center;
}
.td-reveal {
    opacity: 0;
    transform: translateY(14px);
    transition: opacity 0.6s ease, transform 0.6s ease;
}
.td-reveal.is-visible {
    opacity: 1;
    transform: translateY(0);
}
@media (max-width: 768px) {
    .testdrive-hero {
        padding: 1.8rem;
    }
    .testdrive-hero__media {
        justify-items: start;
    }
    .td-game__canvas {
        height: 320px;
    }
    .testdrive-section__title {
        font-size: 1.15rem;
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
	                        } elseif ($t_mp[3] == 'order') {
	                                include(_SITE_PAGE.'/new_pages/order/order.php');
	                        } else {
	                                $lang_code = isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'ro';
	                            if ($t_mp[3] == 'testdrive' && $lang_code == 'ru') {
	                                    echo <<<'HTML'
	                                    <div class="testdrive-page">
	                                        <section class="testdrive-hero td-reveal">
	                                            <div class="testdrive-hero__content">
	                                                <h1 class="testdrive-hero__title">Твой будущий автомобиль ждет тебя на тест-драйве</h1>
	                                                <p class="testdrive-hero__lead">Хочешь понять — твое или нет? Посадка, обзорность, подвеска, шум, тормоза — это решается за 15 минут на реальной дороге.</p>
	                                                <p class="testdrive-hero__cta">Выбирай удобное время — подготовим автомобиль и маршрут для короткого, но честного тест-драйва.</p>
	                                            </div>
	                                            <div class="testdrive-hero__media">
	                                                <img src="/media/images/site/services/testdrive-hero.jpg" loading="lazy" alt="Автомобиль SAUTO на дороге" />
	                                            </div>
	                                        </section>
	                                        <section class="testdrive-section td-reveal">
	                                            <h2 class="testdrive-section__title">Зачем тест-драйв</h2>
	                                            <ul class="testdrive-features">
	                                                <li class="testdrive-card">
	                                                    <span class="testdrive-card__icon">
	                                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12h14M12 5v14"/></svg>
	                                                    </span>
	                                                    <p class="testdrive-card__text">Посадка и удобство</p>
	                                                </li>
	                                                <li class="testdrive-card">
	                                                    <span class="testdrive-card__icon">
	                                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="7"/><path d="M12 5v4l3 3"/></svg>
	                                                    </span>
	                                                    <p class="testdrive-card__text">Обзорность и габариты</p>
	                                                </li>
	                                                <li class="testdrive-card">
	                                                    <span class="testdrive-card__icon">
	                                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 16l4-4 4 4 4-4 4 4"/></svg>
	                                                    </span>
	                                                    <p class="testdrive-card__text">Подвеска на реальных дорогах</p>
	                                                </li>
	                                                <li class="testdrive-card">
	                                                    <span class="testdrive-card__icon">
	                                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 14h7l3-4 6 8"/></svg>
	                                                    </span>
	                                                    <p class="testdrive-card__text">Динамика и тормоза</p>
	                                                </li>
	                                                <li class="testdrive-card">
	                                                    <span class="testdrive-card__icon">
	                                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 8h16M4 16h10"/></svg>
	                                                    </span>
	                                                    <p class="testdrive-card__text">Шум и комфорт</p>
	                                                </li>
	                                                <li class="testdrive-card">
	                                                    <span class="testdrive-card__icon">
	                                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="7" width="16" height="10" rx="2"/></svg>
	                                                    </span>
	                                                    <p class="testdrive-card__text">Багажник и практичность</p>
	                                                </li>
	                                                <li class="testdrive-card">
	                                                    <span class="testdrive-card__icon">
	                                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 6v12M6 12h12"/></svg>
	                                                    </span>
	                                                    <p class="testdrive-card__text">«Мое/не мое» за 10 минут</p>
	                                                </li>
	                                                <li class="testdrive-card">
	                                                    <span class="testdrive-card__icon">
	                                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h6v12H4z"/><path d="M14 8h6v10h-6z"/></svg>
	                                                    </span>
	                                                    <p class="testdrive-card__text">Сравнение 2–3 авто за визит</p>
	                                                </li>
	                                            </ul>
	                                        </section>
	                                        <section class="testdrive-section td-reveal">
	                                            <h2 class="testdrive-section__title">Как это проходит</h2>
	                                            <ol class="testdrive-steps">
	                                                <li class="testdrive-step">
	                                                    <span class="testdrive-step__index">1</span>
	                                                    <p class="testdrive-step__text">Выбираешь авто (или мы предложим варианты).</p>
	                                                </li>
	                                                <li class="testdrive-step">
	                                                    <span class="testdrive-step__index">2</span>
	                                                    <p class="testdrive-step__text">Записываешься на удобное время.</p>
	                                                </li>
	                                                <li class="testdrive-step">
	                                                    <span class="testdrive-step__index">3</span>
	                                                    <p class="testdrive-step__text">Приезжаешь — быстрый инструктаж.</p>
	                                                </li>
	                                                <li class="testdrive-step">
	                                                    <span class="testdrive-step__index">4</span>
	                                                    <p class="testdrive-step__text">Едешь 10–20 минут по маршруту.</p>
	                                                </li>
	                                                <li class="testdrive-step">
	                                                    <span class="testdrive-step__index">5</span>
	                                                    <p class="testdrive-step__text">Возвращаешься — обсуждаем, сравниваем, отвечаем.</p>
	                                                </li>
	                                            </ol>
	                                        </section>
	                                        <section class="testdrive-trust td-reveal">
	                                            <p class="testdrive-trust__headline">Без давления и уговоров. Тест-драйв нужен, чтобы ты сам понял.</p>
	                                            <p class="testdrive-trust__subtitle">Подготовим авто и маршрут — приезжай и проверь.</p>
	                                            <p class="testdrive-trust__subtitle">Мы рядом, чтобы ответить на вопросы, но решение всегда за тобой.</p>
	                                        </section>
	                                        <section class="td-game td-reveal" aria-labelledby="virtual-testdrive-title">
	                                            <div class="td-game__header">
	                                                <h2 class="td-game__title" id="virtual-testdrive-title">Мини-игра: виртуальный тест-драйв</h2>
	                                                <p class="td-game__hint">Дорога едет вниз сама. Управляй только влево-вправо и собери три значка.</p>
	                                            </div>
	                                            <div class="td-game__wrap">
	                                                <div class="td-game__canvas-wrap">
	                                                    <canvas class="td-game__canvas" id="testdrive-canvas"></canvas>
	                                                    <div class="td-game__overlay" id="td-overlay">
	                                                        <h3 class="td-game__overlay-title">Готов к заезду?</h3>
	                                                        <p class="td-game__overlay-text">Проведи машину до финиша — мышью или пальцем. Только влево-вправо.</p>
	                                                    </div>
	                                                    <div class="td-game__result-overlay" id="td-result-overlay">
	                                                        <h3 class="td-game__result-title">Заезд завершен</h3>
	                                                        <div class="td-game__result-meta" id="td-result-meta"></div>
	                                                        <div class="td-game__result-meta" id="td-result-badges"></div>
	                                                        <p class="td-game__result-message" id="td-result-message"></p>
	                                                        <div class="td-game__result-actions">
	                                                            <button class="td-game__button" id="td-play-again" type="button">Заново</button>
	                                                        </div>
	                                                    </div>
	                                                </div>
	                                                <div class="td-game__stats">
	                                                    <div class="td-game__stat">Время: <span id="td-time">0.0</span> сек</div>
	                                                    <div class="td-game__stat">Ошибки: <span id="td-errors">0</span>/3</div>
	                                                    <div class="td-game__stat">Очки: <span id="td-score">0</span></div>
	                                                    <div class="td-game__badge">Руль <span id="td-badge-steer">0</span></div>
	                                                    <div class="td-game__badge">Тормоз <span id="td-badge-brake">0</span></div>
	                                                    <div class="td-game__badge">Багажник <span id="td-badge-trunk">0</span></div>
	                                                </div>
	                                                <div class="td-game__buttons">
	                                                    <button class="td-game__button" id="td-start" type="button">Старт</button>
	                                                    <button class="td-game__button td-game__button--secondary" id="td-restart" type="button">Заново</button>
	                                                </div>
	                                                <div class="td-game__result" id="td-result">Совет: на мобильном веди машину пальцем прямо по трассе.</div>
	                                            </div>
	                                        </section>
	                                        <section class="testdrive-section td-reveal">
	                                            <p>Ок, виртуально ты доехал. В реале будет интереснее — приезжай на тест-драйв и почувствуй разницу.</p>
	                                        </section>
	                                    </div>
	                                    <script>
	                                    (() => {
	                                        const canvas = document.getElementById('testdrive-canvas');
	                                        if (!canvas) return;
	                                        const ctx = canvas.getContext('2d');
	                                        const timeEl = document.getElementById('td-time');
	                                        const errorsEl = document.getElementById('td-errors');
	                                        const scoreEl = document.getElementById('td-score');
	                                        const badgeSteerEl = document.getElementById('td-badge-steer');
	                                        const badgeBrakeEl = document.getElementById('td-badge-brake');
	                                        const badgeTrunkEl = document.getElementById('td-badge-trunk');
	                                        const resultEl = document.getElementById('td-result');
	                                        const overlayEl = document.getElementById('td-overlay');
	                                        const resultOverlayEl = document.getElementById('td-result-overlay');
	                                        const resultMetaEl = document.getElementById('td-result-meta');
	                                        const resultBadgesEl = document.getElementById('td-result-badges');
	                                        const resultMessageEl = document.getElementById('td-result-message');
	                                        const playAgainBtn = document.getElementById('td-play-again');
	                                        const startBtn = document.getElementById('td-start');
	                                        const restartBtn = document.getElementById('td-restart');

	                                        const trackEvent = (name, params = {}) => {
	                                            if (window.yaCounter87984800 && typeof window.yaCounter87984800.reachGoal === 'function') {
	                                                window.yaCounter87984800.reachGoal(name, params);
	                                            }
	                                            if (window.dataLayer && Array.isArray(window.dataLayer)) {
	                                                window.dataLayer.push({ event: name, ...params });
	                                            }
	                                        };

	                                        const successMessages = [
	                                            'Ок, виртуально доехал. В реале будет интереснее — приезжай на тест-драйв.',
	                                            '0 ошибок — ты из тех, кто паркуется с первого раза. Подозрительно.',
	                                            'Собрал руль + тормоз + багажник — базовый курс пройден. Дальше только реальность.',
	                                            'Хорошо. Теперь проверь посадку и обзорность вживую — это решает.',
	                                            'Ты справился. Настоящий тест-драйв еще проще: дорога шире, эмоций больше.',
	                                            'Есть касания — нормально. В реале у нас конусы не пиксельные, но мы добрые.',
	                                            'Финиш! Теперь осталось главное — почувствовать машину телом, а не курсором.',
	                                            'Уровень «аккуратный водитель». Осталось выбрать свое авто.',
	                                            'Ты явно умеешь рулить. Проверь, как рулится именно эта модель.',
	                                            'Победа. Реальный тест-драйв — лучший анти-сомнения.',
	                                            'Проехал. Теперь вопрос один: когда записываемся?',
	                                            'Ок. Вживую подвеска и тормоза расскажут правду еще быстрее.'
	                                        ];

	                                        const failMessages = [
	                                            'Кажется, тебе нужен тест-драйв срочно — без шуток.',
	                                            'Ничего. В реале машина помогает больше, чем мышка.',
	                                            'Давай еще раз. В жизни тоже так.',
	                                            'Это была тренировка характера. В реале будет проще.',
	                                            'Пару касаний — и уже есть повод попробовать реальный тест-драйв.'
	                                        ];

	                                        const state = {
	                                            running: false,
	                                            finished: false,
	                                            startTime: 0,
	                                            lastTime: 0,
	                                            elapsed: 0,
	                                            errors: 0,
	                                            score: 0,
	                                            pointerActive: false,
	                                            pointerX: 0,
	                                            shake: 0,
	                                            flash: 0,
	                                            lineOffset: 0,
	                                            nextSpawn: 0,
	                                            collisionCooldown: 0,
	                                            badges: {
	                                                steer: 0,
	                                                brake: 0,
	                                                trunk: 0
	                                            }
	                                        };

	                                        const car = {
	                                            x: 0,
	                                            y: 0,
	                                            width: 40,
	                                            height: 70,
	                                            speed: 0
	                                        };

	                                        const obstacles = [];
	                                        const pickups = [];
	                                        const keys = new Set();

	                                        const config = {
	                                            duration: 30,
	                                            maxErrors: 3
	                                        };

	                                        const resize = () => {
	                                            const ratio = window.devicePixelRatio || 1;
	                                            const rect = canvas.getBoundingClientRect();
	                                            canvas.width = rect.width * ratio;
	                                            canvas.height = rect.height * ratio;
	                                            ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
	                                            resetGame();
	                                        };

	                                        const resetGame = () => {
	                                            state.running = false;
	                                            state.finished = false;
	                                            state.startTime = 0;
	                                            state.lastTime = 0;
	                                            state.elapsed = 0;
	                                            state.errors = 0;
	                                            state.score = 0;
	                                            state.lineOffset = 0;
	                                            state.nextSpawn = 0;
	                                            state.collisionCooldown = 0;
	                                            state.badges.steer = 0;
	                                            state.badges.brake = 0;
	                                            state.badges.trunk = 0;
	                                            obstacles.length = 0;
	                                            pickups.length = 0;
	                                            const { width, height } = getCanvasSize();
	                                            car.x = width / 2;
	                                            car.y = height - 90;
	                                            car.speed = 0;
	                                            overlayEl.style.display = 'grid';
	                                            resultOverlayEl.classList.remove('is-visible');
	                                            resultBadgesEl.textContent = '';
	                                            resultEl.textContent = 'Совет: на мобильном веди машину пальцем прямо по трассе.';
	                                            updateUI();
	                                            draw();
	                                        };

	                                        const getCanvasSize = () => ({
	                                            width: canvas.getBoundingClientRect().width,
	                                            height: canvas.getBoundingClientRect().height
	                                        });

	                                        const updateUI = () => {
	                                            timeEl.textContent = state.elapsed.toFixed(1);
	                                            errorsEl.textContent = state.errors;
	                                            scoreEl.textContent = state.score;
	                                            badgeSteerEl.textContent = state.badges.steer;
	                                            badgeBrakeEl.textContent = state.badges.brake;
	                                            badgeTrunkEl.textContent = state.badges.trunk;
	                                        };

	                                        const start = () => {
	                                            if (state.running) return;
	                                            if (state.finished) {
	                                                resetGame();
	                                            }
	                                            state.running = true;
	                                            state.finished = false;
	                                            state.startTime = performance.now();
	                                            state.lastTime = state.startTime;
	                                            overlayEl.style.display = 'none';
	                                            resultOverlayEl.classList.remove('is-visible');
	                                            trackEvent('testdrive_game_start');
	                                        };

	                                        const finish = (success) => {
	                                            state.running = false;
	                                            state.finished = true;
	                                            const messagePool = success ? successMessages : failMessages;
	                                            const message = messagePool[Math.floor(Math.random() * messagePool.length)];
	                                            resultMessageEl.textContent = message;
	                                            resultMetaEl.textContent = 'Время: ' + state.elapsed.toFixed(1) + ' сек · Ошибки: ' + state.errors + ' · Очки: ' + state.score;
	                                            resultBadgesEl.textContent =
	                                                'Собрано: Руль ' + state.badges.steer +
	                                                ' · Тормоз ' + state.badges.brake +
	                                                ' · Багажник ' + state.badges.trunk;
	                                            resultOverlayEl.classList.add('is-visible');
	                                            overlayEl.style.display = 'none';
	                                            trackEvent('testdrive_game_finish', {
	                                                time: state.elapsed.toFixed(1),
	                                                errors: state.errors,
	                                                score: state.score
	                                            });
	                                        };

	                                        const getLevelConfig = () => {
	                                            if (state.elapsed < 10) {
	                                                return { speed: 3.2, spawn: 900 };
	                                            }
	                                            if (state.elapsed < 20) {
	                                                return { speed: 4.1, spawn: 650 };
	                                            }
	                                            return { speed: 5, spawn: 520 };
	                                        };

	                                        const spawnObstacle = () => {
	                                            const { width } = getCanvasSize();
	                                            const roadWidth = width * 0.62;
	                                            const roadLeft = (width - roadWidth) / 2;
	                                            const x = roadLeft + 20 + Math.random() * (roadWidth - 40);
	                                            obstacles.push({
	                                                x,
	                                                y: -40,
	                                                width: 30,
	                                                height: 40,
	                                                type: Math.random() > 0.5 ? 'cone' : 'barrier'
	                                            });
	                                        };

	                                        const spawnPickup = () => {
	                                            const types = ['steer', 'brake', 'trunk'];
	                                            const { width } = getCanvasSize();
	                                            const roadWidth = width * 0.62;
	                                            const roadLeft = (width - roadWidth) / 2;
	                                            const type = types[Math.floor(Math.random() * types.length)];
	                                            pickups.push({
	                                                x: roadLeft + 30 + Math.random() * (roadWidth - 60),
	                                                y: -30,
	                                                size: 18,
	                                                type
	                                            });
	                                        };

	                                        const handleCollision = () => {
	                                            const now = performance.now();
	                                            if (state.collisionCooldown > now) return;
	                                            state.collisionCooldown = now + 400;
	                                            state.errors += 1;
	                                            state.shake = 6;
	                                            state.flash = 1;
	                                            if (navigator.vibrate) {
	                                                navigator.vibrate(30);
	                                            }
	                                            if (state.errors >= config.maxErrors) {
	                                                finish(false);
	                                            }
	                                        };

	                                        const update = (timestamp) => {
	                                            if (!state.running) {
	                                                draw();
	                                                return;
	                                            }

	                                            const delta = Math.min(1.6, (timestamp - state.lastTime) / 16.6);
	                                            state.lastTime = timestamp;
	                                            state.elapsed = (timestamp - state.startTime) / 1000;
	                                            const level = getLevelConfig();

	                                            if (state.elapsed >= config.duration) {
	                                                finish(true);
	                                                return;
	                                            }

	                                            if (timestamp > state.nextSpawn) {
	                                                spawnObstacle();
	                                                if (Math.random() > 0.55) {
	                                                    spawnPickup();
	                                                }
	                                                state.nextSpawn = timestamp + level.spawn;
	                                            }

	                                            if (keys.has('arrowleft') || keys.has('a')) {
	                                                car.x -= 6 * delta;
	                                            }
	                                            if (keys.has('arrowright') || keys.has('d')) {
	                                                car.x += 6 * delta;
	                                            }
	                                            if (state.pointerActive) {
	                                                car.x += (state.pointerX - car.x) * 0.18;
	                                            }

	                                            const { width, height } = getCanvasSize();
	                                            const roadWidth = width * 0.62;
	                                            const roadLeft = (width - roadWidth) / 2;
	                                            const roadRight = roadLeft + roadWidth;
	                                            car.x = Math.max(roadLeft + car.width / 2 + 6, Math.min(roadRight - car.width / 2 - 6, car.x));
	                                            car.y = height - 90;

	                                            const speed = level.speed * delta;
	                                            state.lineOffset += speed * 6;

	                                            obstacles.forEach((obstacle) => {
	                                                obstacle.y += speed * 6;
	                                            });
	                                            pickups.forEach((pickup) => {
	                                                pickup.y += speed * 6;
	                                            });

	                                            while (obstacles.length && obstacles[0].y > height + 60) {
	                                                obstacles.shift();
	                                            }
	                                            while (pickups.length && pickups[0].y > height + 60) {
	                                                pickups.shift();
	                                            }

	                                            const carBox = {
	                                                left: car.x - car.width / 2,
	                                                right: car.x + car.width / 2,
	                                                top: car.y - car.height / 2,
	                                                bottom: car.y + car.height / 2
	                                            };

	                                            obstacles.forEach((obstacle) => {
	                                                const hit =
	                                                    carBox.right > obstacle.x - obstacle.width / 2 &&
	                                                    carBox.left < obstacle.x + obstacle.width / 2 &&
	                                                    carBox.bottom > obstacle.y - obstacle.height / 2 &&
	                                                    carBox.top < obstacle.y + obstacle.height / 2;
	                                                if (hit) {
	                                                    handleCollision();
	                                                }
	                                            });

	                                            pickups.forEach((pickup) => {
	                                                if (pickup.collected) return;
	                                                const hit =
	                                                    carBox.right > pickup.x - pickup.size &&
	                                                    carBox.left < pickup.x + pickup.size &&
	                                                    carBox.bottom > pickup.y - pickup.size &&
	                                                    carBox.top < pickup.y + pickup.size;
	                                                if (hit) {
	                                                    pickup.collected = true;
	                                                    state.score += 120;
	                                                    state.badges[pickup.type] += 1;
	                                                }
	                                            });

	                                            state.score = Math.max(0, state.score - Math.floor(delta * 2));

	                                            updateUI();
	                                            draw();
	                                        };

	                                        const drawRoadLines = (roadLeft, roadWidth, height) => {
	                                            const lineHeight = 40;
	                                            const gap = 30;
	                                            let y = -lineHeight + (state.lineOffset % (lineHeight + gap));
	                                            ctx.strokeStyle = '#f2c94c';
	                                            ctx.lineWidth = 3;
	                                            while (y < height) {
	                                                ctx.beginPath();
	                                                ctx.moveTo(roadLeft + roadWidth / 2, y);
	                                                ctx.lineTo(roadLeft + roadWidth / 2, y + lineHeight);
	                                                ctx.stroke();
	                                                y += lineHeight + gap;
	                                            }
	                                        };

	                                        const draw = () => {
	                                            const { width, height } = getCanvasSize();
	                                            ctx.clearRect(0, 0, width, height);

	                                            ctx.fillStyle = '#0f1114';
	                                            ctx.fillRect(0, 0, width, height);

	                                            const roadWidth = width * 0.62;
	                                            const roadLeft = (width - roadWidth) / 2;
	                                            const roadRight = roadLeft + roadWidth;

	                                            ctx.fillStyle = '#1b2026';
	                                            ctx.fillRect(roadLeft, 0, roadWidth, height);

	                                            ctx.fillStyle = '#111418';
	                                            ctx.fillRect(roadLeft, 0, 8, height);
	                                            ctx.fillRect(roadRight - 8, 0, 8, height);

	                                            drawRoadLines(roadLeft, roadWidth, height);

	                                            obstacles.forEach((obstacle) => {
	                                                if (obstacle.type === 'cone') {
	                                                    ctx.fillStyle = '#ff7a00';
	                                                    ctx.beginPath();
	                                                    ctx.moveTo(obstacle.x, obstacle.y - obstacle.height / 2);
	                                                    ctx.lineTo(obstacle.x - obstacle.width / 2, obstacle.y + obstacle.height / 2);
	                                                    ctx.lineTo(obstacle.x + obstacle.width / 2, obstacle.y + obstacle.height / 2);
	                                                    ctx.closePath();
	                                                    ctx.fill();
	                                                    ctx.strokeStyle = '#fff3';
	                                                    ctx.stroke();
	                                                } else {
	                                                    ctx.fillStyle = '#e74c3c';
	                                                    ctx.fillRect(
	                                                        obstacle.x - obstacle.width / 2,
	                                                        obstacle.y - obstacle.height / 2,
	                                                        obstacle.width,
	                                                        obstacle.height
	                                                    );
	                                                }
	                                            });

	                                            pickups.forEach((pickup) => {
	                                                if (pickup.collected) return;
	                                                ctx.fillStyle = '#5ad1ff';
	                                                ctx.beginPath();
	                                                ctx.arc(pickup.x, pickup.y, pickup.size, 0, Math.PI * 2);
	                                                ctx.fill();
	                                                ctx.fillStyle = '#0f1114';
	                                                ctx.font = '11px sans-serif';
	                                                const label = pickup.type === 'steer' ? 'R' : pickup.type === 'brake' ? 'T' : 'B';
	                                                ctx.fillText(label, pickup.x - 4, pickup.y + 4);
	                                            });

	                                            ctx.save();
	                                            if (state.shake > 0) {
	                                                const offsetX = (Math.random() - 0.5) * state.shake;
	                                                const offsetY = (Math.random() - 0.5) * state.shake;
	                                                ctx.translate(offsetX, offsetY);
	                                                state.shake *= 0.9;
	                                            }
	                                            const drawRoundedRect = (x, y, w, h, r) => {
	                                                ctx.beginPath();
	                                                ctx.moveTo(x + r, y);
	                                                ctx.lineTo(x + w - r, y);
	                                                ctx.quadraticCurveTo(x + w, y, x + w, y + r);
	                                                ctx.lineTo(x + w, y + h - r);
	                                                ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
	                                                ctx.lineTo(x + r, y + h);
	                                                ctx.quadraticCurveTo(x, y + h, x, y + h - r);
	                                                ctx.lineTo(x, y + r);
	                                                ctx.quadraticCurveTo(x, y, x + r, y);
	                                                ctx.closePath();
	                                            };

	                                            ctx.fillStyle = '#f5f5f5';
	                                            drawRoundedRect(car.x - car.width / 2, car.y - car.height / 2, car.width, car.height, 8);
	                                            ctx.fill();
	                                            ctx.fillStyle = '#ff4d4f';
	                                            ctx.fillRect(car.x - 10, car.y - 10, 20, 20);
	                                            ctx.restore();

	                                            if (state.flash > 0) {
	                                                ctx.fillStyle = 'rgba(255, 0, 0, ' + (0.18 * state.flash) + ')';
	                                                ctx.fillRect(0, 0, width, height);
	                                                state.flash *= 0.7;
	                                            }
	                                        };

	                                        startBtn.addEventListener('click', start);
	                                        restartBtn.addEventListener('click', resetGame);
	                                        playAgainBtn.addEventListener('click', () => {
	                                            resetGame();
	                                            start();
	                                        });
	                                        window.addEventListener('resize', resize);

	                                        document.addEventListener('keydown', (event) => {
	                                            keys.add(event.key.toLowerCase());
	                                        });
	                                        document.addEventListener('keyup', (event) => {
	                                            keys.delete(event.key.toLowerCase());
	                                        });

	                                        const setPointer = (event) => {
	                                            const rect = canvas.getBoundingClientRect();
	                                            state.pointerX = event.clientX - rect.left;
	                                        };

	                                        canvas.addEventListener('pointerdown', (event) => {
	                                            state.pointerActive = true;
	                                            canvas.setPointerCapture(event.pointerId);
	                                            setPointer(event);
	                                            if (state.running) {
	                                                event.preventDefault();
	                                            }
	                                        });
	                                        canvas.addEventListener('pointermove', (event) => {
	                                            if (!state.pointerActive) return;
	                                            setPointer(event);
	                                            if (state.running) {
	                                                event.preventDefault();
	                                            }
	                                        });
	                                        canvas.addEventListener('pointerup', (event) => {
	                                            state.pointerActive = false;
	                                            canvas.releasePointerCapture(event.pointerId);
	                                        });
	                                        canvas.addEventListener('pointerleave', () => {
	                                            state.pointerActive = false;
	                                        });

	                                        const revealEls = document.querySelectorAll('.td-reveal');
	                                        if ('IntersectionObserver' in window) {
	                                            const observer = new IntersectionObserver(
	                                                (entries) => {
	                                                    entries.forEach((entry) => {
	                                                        if (entry.isIntersecting) {
	                                                            entry.target.classList.add('is-visible');
	                                                            observer.unobserve(entry.target);
	                                                        }
	                                                    });
	                                                },
	                                                { threshold: 0.2 }
	                                            );
	                                            revealEls.forEach((el) => observer.observe(el));
	                                        } else {
	                                            revealEls.forEach((el) => el.classList.add('is-visible'));
	                                        }

	                                        resize();
	                                        resetGame();
	                                        const loop = (timestamp) => {
	                                            update(timestamp);
	                                            requestAnimationFrame(loop);
	                                        };
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
			include(_SITE_PAGE.'/new_pages/order/order.php');
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
