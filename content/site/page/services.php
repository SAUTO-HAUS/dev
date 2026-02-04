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
    gap: 2rem;
}
.testdrive-hero {
    display: grid;
    gap: 0.75rem;
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
.testdrive-list {
    margin: 0;
    padding-left: 1.2rem;
    display: grid;
    gap: 0.5rem;
}
.testdrive-steps {
    margin: 0;
    padding-left: 1.4rem;
    display: grid;
    gap: 0.6rem;
}
.testdrive-trust {
    background: #f6f7f8;
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    display: grid;
    gap: 0.6rem;
}
.testdrive-trust p {
    margin: 0;
}
.td-game {
    background: #0f1114;
    color: #f5f5f5;
    border-radius: 18px;
    padding: 1.25rem;
    display: grid;
    gap: 1rem;
}
.td-game__header {
    display: grid;
    gap: 0.5rem;
}
.td-game__title {
    font-size: 1.2rem;
    margin: 0;
}
.td-game__hint {
    font-size: 0.95rem;
    color: #d4d7db;
    margin: 0;
}
.td-game__wrap {
    background: #1b1f24;
    border-radius: 14px;
    padding: 0.75rem;
    display: grid;
    gap: 0.75rem;
}
.td-game__canvas {
    width: 100%;
    height: 360px;
    border-radius: 12px;
    background: #13161a;
    display: block;
    touch-action: none;
}
.td-game__stats {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem 1.5rem;
    font-size: 0.95rem;
    color: #c9ced4;
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
        height: 280px;
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
	                                            <p class="testdrive-hero__lead">Сомневаешься между моделями? Хочешь понять посадку, обзорность, динамику, багажник, тормоза? Это решается за 15 минут на реальной дороге — без гаданий и «потом разберусь».</p>
	                                            <p class="testdrive-hero__cta">Записывайся и приезжай — мы подготовим авто и маршрут.</p>
	                                        </section>
	                                        <section class="testdrive-section">
	                                            <h2 class="testdrive-section__title">Зачем тест-драйв</h2>
	                                            <ul class="testdrive-list">
	                                                <li>Посадка и удобство: руль, сиденье, зеркала.</li>
	                                                <li>Обзорность и габариты: парковка без мата.</li>
	                                                <li>Подвеска: как переживает молдавскую реальность.</li>
	                                                <li>Динамика и тормоза в живом режиме.</li>
	                                                <li>Шумоизоляция без «на словах».</li>
	                                                <li>Багажник вживую, а не по цифрам.</li>
	                                                <li>Понимание «мое / не мое» за 10 минут.</li>
	                                            </ul>
	                                        </section>
	                                        <section class="testdrive-section">
	                                            <h2 class="testdrive-section__title">Как это проходит</h2>
	                                            <ol class="testdrive-steps">
	                                                <li>Выбираешь авто (или мы рекомендуем 2–3 варианта).</li>
	                                                <li>Запись на удобное время.</li>
	                                                <li>Приезжаешь в SAUTO, короткий инструктаж.</li>
	                                                <li>Маршрут 10–20 минут (город + кусочек нормальной дороги).</li>
	                                                <li>Возвращаешься — отвечаем на вопросы, сравниваем варианты.</li>
	                                            </ol>
	                                        </section>
	                                        <section class="testdrive-trust">
	                                            <p>Подскажем по выбору без давления. Тест-драйв — чтобы ты сам понял.</p>
	                                            <p>Никаких обещаний «лучше всех». Просто честно и по делу.</p>
	                                        </section>
	                                        <section class="td-game" aria-labelledby="virtual-testdrive-title">
	                                            <div class="td-game__header">
	                                                <h2 class="td-game__title" id="virtual-testdrive-title">Виртуальный тест-драйв</h2>
	                                                <p class="td-game__hint">Проведи машинку до финиша — мышью или пальцем. Собери минимум 2 значка.</p>
	                                            </div>
	                                            <div class="td-game__wrap">
	                                                <canvas class="td-game__canvas" id="testdrive-canvas"></canvas>
	                                                <div class="td-game__stats">
	                                                    <div>Время: <span id="td-time">0.0</span> сек</div>
	                                                    <div>Ошибки: <span id="td-errors">0</span></div>
	                                                    <div>Значки: <span id="td-pickups">0</span>/3</div>
	                                                </div>
	                                                <div class="td-game__buttons">
	                                                    <button class="td-game__button" id="td-start" type="button">Старт</button>
	                                                    <button class="td-game__button td-game__button--secondary" id="td-restart" type="button">Заново</button>
	                                                </div>
	                                                <div class="td-game__result" id="td-result">Совет: на мобильном можно вести пальцем прямо по трассе.</div>
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
	                                        const resultEl = document.getElementById('td-result');
	                                        const startBtn = document.getElementById('td-start');
	                                        const restartBtn = document.getElementById('td-restart');

	                                        const state = {
	                                            running: false,
	                                            finished: false,
	                                            startTime: 0,
	                                            lastTime: 0,
	                                            errors: 0,
	                                            collected: 0,
	                                            collisionCooldown: 0,
	                                            pointerActive: false,
	                                            pointer: { x: 0, y: 0 }
	                                        };

	                                        const track = {
	                                            outerPadding: 20,
	                                            innerPadding: 120,
	                                            finishWidth: 80,
	                                            finishHeight: 20
	                                        };

	                                        const car = {
	                                            x: 0,
	                                            y: 0,
	                                            angle: 0,
	                                            speed: 0,
	                                            maxSpeed: 3.2
	                                        };

	                                        const pickups = [
	                                            { x: 0, y: 0, collected: false, label: 'Руль' },
	                                            { x: 0, y: 0, collected: false, label: 'Тормоз' },
	                                            { x: 0, y: 0, collected: false, label: 'Багажник' }
	                                        ];

	                                        const cones = [];

	                                        const keys = new Set();

	                                        const resize = () => {
	                                            const ratio = window.devicePixelRatio || 1;
	                                            const rect = canvas.getBoundingClientRect();
	                                            canvas.width = rect.width * ratio;
	                                            canvas.height = rect.height * ratio;
	                                            ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
	                                            setupTrack();
	                                        };

	                                        const setupTrack = () => {
	                                            const width = canvas.getBoundingClientRect().width;
	                                            const height = canvas.getBoundingClientRect().height;
	                                            car.x = track.outerPadding + 40;
	                                            car.y = height - track.outerPadding - 40;
	                                            car.angle = -Math.PI / 2;
	                                            car.speed = 0;
	                                            state.errors = 0;
	                                            state.collected = 0;
	                                            state.collisionCooldown = 0;
	                                            state.finished = false;
	                                            state.running = false;
	                                            state.startTime = 0;
	                                            state.lastTime = 0;
	                                            pickups.forEach((item, index) => {
	                                                item.collected = false;
	                                                item.x = track.outerPadding + 80 + index * 140;
	                                                item.y = track.outerPadding + 80 + index * 40;
	                                            });
	                                            cones.length = 0;
	                                            const conePoints = [
	                                                [width * 0.45, height * 0.25],
	                                                [width * 0.6, height * 0.45],
	                                                [width * 0.35, height * 0.6],
	                                                [width * 0.7, height * 0.7]
	                                            ];
	                                            conePoints.forEach(([x, y]) => cones.push({ x, y, r: 12 }));
	                                            updateUI();
	                                            draw();
	                                        };

	                                        const updateUI = () => {
	                                            errorsEl.textContent = state.errors;
	                                            pickupsEl.textContent = state.collected;
	                                        };

	                                        const start = () => {
	                                            if (state.running) return;
	                                            state.running = true;
	                                            state.finished = false;
	                                            state.startTime = performance.now();
	                                            state.lastTime = state.startTime;
	                                            resultEl.textContent = 'Держись трассы и собирай значки.';
	                                        };

	                                        const reset = () => {
	                                            setupTrack();
	                                            resultEl.textContent = 'Совет: на мобильном можно вести пальцем прямо по трассе.';
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

	                                        const checkRoadCollision = () => {
	                                            const width = canvas.getBoundingClientRect().width;
	                                            const height = canvas.getBoundingClientRect().height;
	                                            const outer = track.outerPadding;
	                                            const inner = track.innerPadding;
	                                            const insideOuter = car.x > outer && car.x < width - outer && car.y > outer && car.y < height - outer;
	                                            const insideInner = car.x > inner && car.x < width - inner && car.y > inner && car.y < height - inner;
	                                            return !insideOuter || insideInner;
	                                        };

	                                        const handleCollision = () => {
	                                            const now = performance.now();
	                                            if (state.collisionCooldown > now) return;
	                                            state.collisionCooldown = now + 500;
	                                            state.errors += 1;
	                                            car.speed *= 0.4;
	                                            updateUI();
	                                            if (navigator.vibrate) {
	                                                navigator.vibrate(50);
	                                            }
	                                        };

	                                        const update = (timestamp) => {
	                                            if (!state.running) {
	                                                draw();
	                                                return;
	                                            }
	                                            const delta = (timestamp - state.lastTime) / 16.6;
	                                            state.lastTime = timestamp;
	                                            const elapsed = (timestamp - state.startTime) / 1000;
	                                            timeEl.textContent = elapsed.toFixed(1);

	                                            const steerSpeed = 0.05 * delta;
	                                            const accel = 0.08 * delta;

	                                            if (keys.has('ArrowLeft') || keys.has('a')) {
	                                                car.angle -= steerSpeed;
	                                            }
	                                            if (keys.has('ArrowRight') || keys.has('d')) {
	                                                car.angle += steerSpeed;
	                                            }
	                                            if (keys.has('ArrowUp') || keys.has('w')) {
	                                                car.speed = Math.min(car.maxSpeed, car.speed + accel);
	                                            }
	                                            if (keys.has('ArrowDown') || keys.has('s')) {
	                                                car.speed = Math.max(-car.maxSpeed / 2, car.speed - accel);
	                                            }

	                                            if (state.pointerActive) {
	                                                const dx = state.pointer.x - car.x;
	                                                const dy = state.pointer.y - car.y;
	                                                const targetAngle = Math.atan2(dy, dx);
	                                                const angleDiff = Math.atan2(Math.sin(targetAngle - car.angle), Math.cos(targetAngle - car.angle));
	                                                car.angle += angleDiff * 0.08;
	                                                if (Math.hypot(dx, dy) > 10) {
	                                                    car.speed = Math.min(car.maxSpeed, car.speed + accel);
	                                                }
	                                            }

	                                            car.speed *= 0.98;
	                                            car.x += Math.cos(car.angle) * car.speed * 2.4;
	                                            car.y += Math.sin(car.angle) * car.speed * 2.4;

	                                            if (checkRoadCollision()) {
	                                                handleCollision();
	                                            }
	                                            cones.forEach((cone) => {
	                                                const dist = Math.hypot(car.x - cone.x, car.y - cone.y);
	                                                if (dist < cone.r + 10) {
	                                                    handleCollision();
	                                                }
	                                            });

	                                            pickups.forEach((item) => {
	                                                if (item.collected) return;
	                                                const dist = Math.hypot(car.x - item.x, car.y - item.y);
	                                                if (dist < 18) {
	                                                    item.collected = true;
	                                                    state.collected += 1;
	                                                    updateUI();
	                                                }
	                                            });

	                                            const width = canvas.getBoundingClientRect().width;
	                                            const finishX = width - track.outerPadding - track.finishWidth - 10;
	                                            const finishY = track.outerPadding + 10;
	                                            const inFinish = car.x > finishX && car.x < finishX + track.finishWidth && car.y > finishY && car.y < finishY + track.finishHeight;
	                                            if (inFinish) {
	                                                if (state.collected >= 2) {
	                                                    finish();
	                                                } else {
	                                                    resultEl.textContent = 'Собери минимум 2 значка, прежде чем финишировать.';
	                                                }
	                                            }

	                                            draw();
	                                        };

	                                        const draw = () => {
	                                            const width = canvas.getBoundingClientRect().width;
	                                            const height = canvas.getBoundingClientRect().height;
	                                            ctx.clearRect(0, 0, width, height);
	                                            ctx.fillStyle = '#1c2127';
	                                            ctx.fillRect(0, 0, width, height);

	                                            ctx.fillStyle = '#2b323a';
	                                            ctx.fillRect(track.outerPadding, track.outerPadding, width - track.outerPadding * 2, height - track.outerPadding * 2);

	                                            ctx.fillStyle = '#1c2127';
	                                            ctx.fillRect(track.innerPadding, track.innerPadding, width - track.innerPadding * 2, height - track.innerPadding * 2);

	                                            ctx.strokeStyle = '#f2c94c';
	                                            ctx.lineWidth = 3;
	                                            ctx.setLineDash([10, 10]);
	                                            ctx.strokeRect(track.outerPadding + 6, track.outerPadding + 6, width - track.outerPadding * 2 - 12, height - track.outerPadding * 2 - 12);
	                                            ctx.setLineDash([]);

	                                            const finishX = width - track.outerPadding - track.finishWidth - 10;
	                                            const finishY = track.outerPadding + 10;
	                                            ctx.fillStyle = '#4caf50';
	                                            ctx.fillRect(finishX, finishY, track.finishWidth, track.finishHeight);
	                                            ctx.fillStyle = '#0f1114';
	                                            ctx.font = '12px sans-serif';
	                                            ctx.fillText('ФИНИШ', finishX + 8, finishY + 14);

	                                            cones.forEach((cone) => {
	                                                ctx.fillStyle = '#ff7a00';
	                                                ctx.beginPath();
	                                                ctx.arc(cone.x, cone.y, cone.r, 0, Math.PI * 2);
	                                                ctx.fill();
	                                                ctx.strokeStyle = '#f5f5f5';
	                                                ctx.lineWidth = 2;
	                                                ctx.stroke();
	                                            });

	                                            pickups.forEach((item) => {
	                                                if (item.collected) return;
	                                                ctx.fillStyle = '#5ad1ff';
	                                                ctx.beginPath();
	                                                ctx.arc(item.x, item.y, 10, 0, Math.PI * 2);
	                                                ctx.fill();
	                                                ctx.fillStyle = '#0f1114';
	                                                ctx.font = '10px sans-serif';
	                                                ctx.fillText(item.label[0], item.x - 3, item.y + 3);
	                                            });

	                                            ctx.save();
	                                            ctx.translate(car.x, car.y);
	                                            ctx.rotate(car.angle);
	                                            ctx.fillStyle = '#f5f5f5';
	                                            ctx.fillRect(-10, -6, 20, 12);
	                                            ctx.fillStyle = '#ff4d4f';
	                                            ctx.fillRect(4, -4, 8, 8);
	                                            ctx.restore();
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

	                                        const setPointer = (event) => {
	                                            const rect = canvas.getBoundingClientRect();
	                                            state.pointer.x = event.clientX - rect.left;
	                                            state.pointer.y = event.clientY - rect.top;
	                                        };

	                                        canvas.addEventListener('pointerdown', (event) => {
	                                            state.pointerActive = true;
	                                            setPointer(event);
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
